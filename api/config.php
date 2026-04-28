<?php
/**
 * Database Configuration
 * Update these values with your Supabase credentials
 */

define('SUPABASE_URL', 'https://YOUR_PROJECT_ID.supabase.co');
define('SUPABASE_KEY', 'YOUR_ANON_KEY');
define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_ROLE_KEY');

/**
 * Make API request to Supabase
 */
function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    switch($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

/**
 * Get all products
 */
function getProducts($category = null) {
    $endpoint = 'products?select=*';
    if ($category && $category !== 'all') {
        $endpoint .= '&category=eq.' . urlencode($category);
    }
    $endpoint .= '&order=created_at.desc';
    
    $result = supabaseRequest($endpoint);
    return $result['data'] ?? [];
}

/**
 * Get product by ID
 */
function getProduct($id) {
    $result = supabaseRequest('products?id=eq.' . $id . '&limit=1');
    return $result['data'][0] ?? null;
}

/**
 * Get products by maker
 */
function getMakerProducts($makerId) {
    $result = supabaseRequest('products?artisan_id=eq.' . $makerId . '&order=created_at.desc');
    return $result['data'] ?? [];
}

/**
 * Get all makers
 */
function getMakers() {
    $result = supabaseRequest('makers?select=id,name,whatsapp');
    return $result['data'] ?? [];
}

/**
 * Get unique makers from products
 */
function getUniqueMakers() {
    $products = getProducts();
    $makersMap = [];
    foreach ($products as $p) {
        if (!isset($makersMap[$p['artisan_id']])) {
            $makersMap[$p['artisan_id']] = [
                'id' => $p['artisan_id'],
                'name' => $p['artisan_name'],
                'whatsapp' => $p['artisan_whatsapp']
            ];
        }
    }
    return array_values($makersMap);
}

/**
 * Register new maker
 */
function registerMaker($name, $email, $password, $whatsapp) {
    // Check if email exists
    $check = supabaseRequest('makers?email=eq.' . urlencode($email) . '&limit=1');
    if (!empty($check['data'])) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    $data = [
        'name' => $name,
        'email' => $email,
        'password' => $password, // In production, hash this!
        'whatsapp' => $whatsapp
    ];
    
    $result = supabaseRequest('makers', 'POST', $data);
    
    if ($result['code'] === 201) {
        return ['success' => true, 'data' => $result['data'][0]];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

/**
 * Login maker
 */
function loginMaker($email, $password) {
    $result = supabaseRequest('makers?email=eq.' . urlencode($email) . '&password=eq.' . $password . '&limit=1');
    
    if (!empty($result['data'])) {
        return ['success' => true, 'data' => $result['data'][0]];
    }
    
    return ['success' => false, 'message' => 'Invalid credentials'];
}

/**
 * Add product
 */
function addProduct($name, $price, $story, $category, $imageUrl, $artisanId, $artisanName, $artisanWhatsapp) {
    $data = [
        'name' => $name,
        'price' => $price,
        'story' => $story,
        'category' => $category,
        'image_url' => $imageUrl,
        'artisan_id' => $artisanId,
        'artisan_name' => $artisanName,
        'artisan_whatsapp' => $artisanWhatsapp
    ];
    
    $result = supabaseRequest('products', 'POST', $data);
    
    if ($result['code'] === 201) {
        return ['success' => true, 'data' => $result['data'][0]];
    }
    
    return ['success' => false, 'message' => 'Failed to add product'];
}

/**
 * Search products
 */
function searchProducts($query) {
    $result = supabaseRequest('products?or=(name-like.*' . urlencode($query) . ',artisan_name-like.*' . urlencode($query) . ')');
    return $result['data'] ?? [];
}