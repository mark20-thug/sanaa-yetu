<?php
/**
 * Database Configuration
 * Update these values with your Supabase credentials
 */

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://YOUR_PROJECT_ID.supabase.co');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: 'YOUR_ANON_KEY');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: 'YOUR_SERVICE_ROLE_KEY');
define('ADMIN_API_KEY', getenv('ADMIN_API_KEY') ?: 'CHANGE_ME_ADMIN_KEY');

/**
 * Handle CORS using an allowlist.
 * Set ALLOWED_ORIGINS as comma-separated values in production.
 */
function handleCors() {
    $allowedOriginsRaw = getenv('ALLOWED_ORIGINS') ?: '';
    $allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOriginsRaw)));
    $allowedOrigins[] = 'http://localhost:8000';
    $allowedOrigins[] = 'http://127.0.0.1:8000';

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

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
    $endpoint = 'products?select=*&status=eq.approved';
    if ($category && $category !== 'all') {
        $endpoint .= '&category=eq.' . urlencode($category);
    }
    $endpoint .= '&order=is_featured.desc,created_at.desc';
    
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
function registerMaker($name, $email, $password, $whatsapp, $businessName = '', $location = '', $bio = '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }

    // Check if email exists
    $check = supabaseRequest('makers?email=eq.' . urlencode($email) . '&limit=1');
    if (!empty($check['data'])) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    $data = [
        'name' => $name,
        'business_name' => $businessName ?: $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'whatsapp' => $whatsapp,
        'location' => $location,
        'bio' => $bio,
        'payment_status' => 'unpaid',
        'approval_status' => 'pending',
        'verification_status' => 'unverified',
        'plan' => 'free',
        'max_products' => 10,
        'can_feature_products' => false
    ];
    
    $result = supabaseRequest('makers', 'POST', $data);
    
    if ($result['code'] === 201) {
        $maker = $result['data'][0];
        unset($maker['password']);
        return ['success' => true, 'data' => $maker];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

/**
 * Login maker
 */
function loginMaker($email, $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    $result = supabaseRequest('makers?email=eq.' . urlencode($email) . '&limit=1');
    if (empty($result['data'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    $maker = $result['data'][0];
    if (!isset($maker['password']) || !password_verify($password, $maker['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    if (($maker['payment_status'] ?? 'unpaid') !== 'paid') {
        return ['success' => false, 'message' => 'Payment required first. Submit payment reference before login.'];
    }
    if (($maker['approval_status'] ?? 'pending') !== 'approved') {
        return ['success' => false, 'message' => 'Your account is awaiting admin approval.'];
    }

    unset($maker['password']);
    return ['success' => true, 'data' => $maker];
}

function getMakerByEmail($email) {
    $result = supabaseRequest('makers?email=eq.' . urlencode($email) . '&limit=1');
    return $result['data'][0] ?? null;
}

function getMakerById($makerId) {
    $result = supabaseRequest('makers?id=eq.' . urlencode($makerId) . '&limit=1');
    return $result['data'][0] ?? null;
}

function submitMakerPayment($email, $reference, $selectedPlan = 'starter', $amount = 30000) {
    $planLimits = [
        'starter' => ['max_products' => 10, 'can_feature_products' => false, 'amount_ugx' => 30000],
        'pro' => ['max_products' => 100, 'can_feature_products' => false, 'amount_ugx' => 70000],
        'featured' => ['max_products' => 200, 'can_feature_products' => true, 'amount_ugx' => 120000]
    ];
    if (!isset($planLimits[$selectedPlan])) {
        return ['success' => false, 'message' => 'Invalid plan selected'];
    }
    if ($amount <= 0) {
        $amount = $planLimits[$selectedPlan]['amount_ugx'];
    }

    $maker = getMakerByEmail($email);
    if (!$maker) {
        return ['success' => false, 'message' => 'Maker account not found'];
    }

    $endpoint = 'makers?id=eq.' . urlencode($maker['id']);
    $payload = [
        'payment_status' => 'submitted',
        'payment_reference' => $reference,
        'approval_status' => 'pending',
        'plan' => $selectedPlan,
        'max_products' => $planLimits[$selectedPlan]['max_products'],
        'can_feature_products' => $planLimits[$selectedPlan]['can_feature_products'],
        'payment_amount_ugx' => $amount
    ];
    $result = supabaseRequest($endpoint, 'PATCH', $payload);
    if ($result['code'] >= 200 && $result['code'] < 300) {
        return ['success' => true, 'message' => 'Payment submitted. Awaiting admin approval.'];
    }

    return ['success' => false, 'message' => 'Failed to submit payment reference'];
}

function canMakerPublish($makerId) {
    $maker = getMakerById($makerId);
    if (!$maker) {
        return ['allowed' => false, 'message' => 'Maker not found'];
    }
    if (($maker['payment_status'] ?? 'unpaid') !== 'paid') {
        return ['allowed' => false, 'message' => 'Payment must be confirmed by admin before publishing products'];
    }
    if (($maker['approval_status'] ?? 'pending') !== 'approved') {
        return ['allowed' => false, 'message' => 'Admin approval required before publishing products'];
    }
    return ['allowed' => true, 'maker' => $maker];
}

/**
 * Add product
 */
function addProduct($name, $price, $story, $category, $imageUrl, $artisanId, $artisanName, $artisanWhatsapp) {
    $eligibility = canMakerPublish($artisanId);
    if (!$eligibility['allowed']) {
        return ['success' => false, 'message' => $eligibility['message']];
    }

    $data = [
        'name' => $name,
        'price' => $price,
        'story' => $story,
        'category' => $category,
        'image_url' => $imageUrl,
        'artisan_id' => $artisanId,
        'artisan_name' => $artisanName,
        'artisan_whatsapp' => $artisanWhatsapp,
        'status' => 'pending',
        'is_featured' => false
    ];
    
    $result = supabaseRequest('products', 'POST', $data);
    
    if ($result['code'] === 201) {
        return ['success' => true, 'data' => $result['data'][0], 'message' => 'Product submitted and awaiting admin approval'];
    }
    
    return ['success' => false, 'message' => 'Failed to add product'];
}

/**
 * Search products
 */
function searchProducts($query) {
    $result = supabaseRequest('products?status=eq.approved&or=(name-like.*' . urlencode($query) . ',artisan_name-like.*' . urlencode($query) . ')');
    return $result['data'] ?? [];
}

function isAdminRequest() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $provided = $headers['X-Admin-Key'] ?? $headers['x-admin-key'] ?? ($_GET['admin_key'] ?? '');
    return !empty($provided) && hash_equals(ADMIN_API_KEY, $provided);
}

function listPendingMakers() {
    $result = supabaseRequest('makers?approval_status=eq.pending&order=created_at.desc');
    return $result['data'] ?? [];
}

function reviewMaker($makerId, $approve, $notes = '') {
    $maker = getMakerById($makerId);
    if (!$maker) {
        return ['success' => false, 'message' => 'Maker not found'];
    }
    $payload = [
        'approval_status' => $approve ? 'approved' : 'rejected',
        'approval_notes' => $notes,
        'is_verified' => $approve,
        'verification_status' => $approve ? 'verified' : 'rejected',
        'payment_status' => $approve ? 'paid' : 'unpaid',
        'max_products' => $approve ? intval($maker['max_products'] ?? 10) : 10,
        'can_feature_products' => $approve ? !empty($maker['can_feature_products']) : false
    ];
    $result = supabaseRequest('makers?id=eq.' . urlencode($makerId), 'PATCH', $payload);
    if ($result['code'] >= 200 && $result['code'] < 300) {
        return ['success' => true, 'message' => $approve ? 'Maker approved successfully' : 'Maker rejected'];
    }
    return ['success' => false, 'message' => 'Failed to review maker'];
}

function listPendingProducts() {
    $result = supabaseRequest('products?status=eq.pending&order=created_at.desc');
    return $result['data'] ?? [];
}

function reviewProduct($productId, $approve, $reason = '') {
    $payload = [
        'status' => $approve ? 'approved' : 'rejected',
        'moderation_reason' => $approve ? '' : $reason
    ];
    $result = supabaseRequest('products?id=eq.' . urlencode($productId), 'PATCH', $payload);
    if ($result['code'] >= 200 && $result['code'] < 300) {
        return ['success' => true, 'message' => $approve ? 'Product approved successfully' : 'Product rejected'];
    }
    return ['success' => false, 'message' => 'Failed to review product'];
}

function rateMaker($makerId, $score, $comment, $raterToken) {
    if ($score < 1 || $score > 5) {
        return ['success' => false, 'message' => 'Score must be between 1 and 5'];
    }
    if (!$makerId || !$raterToken) {
        return ['success' => false, 'message' => 'maker_id and rater_token are required'];
    }

    $existing = supabaseRequest(
        'maker_ratings?maker_id=eq.' . urlencode($makerId) . '&rater_token=eq.' . urlencode($raterToken) . '&limit=1'
    );
    if (!empty($existing['data'])) {
        return ['success' => false, 'message' => 'You already rated this maker'];
    }

    $payload = [
        'maker_id' => $makerId,
        'score' => intval($score),
        'comment' => $comment,
        'rater_token' => $raterToken
    ];
    $result = supabaseRequest('maker_ratings', 'POST', $payload);
    if ($result['code'] === 201) {
        return ['success' => true, 'message' => 'Maker rating submitted'];
    }
    return ['success' => false, 'message' => 'Failed to submit maker rating'];
}

function rateProduct($productId, $makerId, $score, $comment, $raterToken) {
    if ($score < 1 || $score > 5) {
        return ['success' => false, 'message' => 'Score must be between 1 and 5'];
    }
    if (!$productId || !$makerId || !$raterToken) {
        return ['success' => false, 'message' => 'product_id, maker_id and rater_token are required'];
    }

    $existing = supabaseRequest(
        'product_ratings?product_id=eq.' . urlencode($productId) . '&rater_token=eq.' . urlencode($raterToken) . '&limit=1'
    );
    if (!empty($existing['data'])) {
        return ['success' => false, 'message' => 'You already rated this product'];
    }

    $payload = [
        'product_id' => $productId,
        'maker_id' => $makerId,
        'score' => intval($score),
        'comment' => $comment,
        'rater_token' => $raterToken
    ];
    $result = supabaseRequest('product_ratings', 'POST', $payload);
    if ($result['code'] === 201) {
        return ['success' => true, 'message' => 'Product rating submitted'];
    }
    return ['success' => false, 'message' => 'Failed to submit product rating'];
}

function getMakerRatingSummary($makerId) {
    $result = supabaseRequest('maker_ratings?maker_id=eq.' . urlencode($makerId) . '&select=score');
    $rows = $result['data'] ?? [];
    $count = count($rows);
    if ($count === 0) return ['avg' => 0, 'count' => 0];
    $sum = 0;
    foreach ($rows as $row) $sum += intval($row['score']);
    return ['avg' => round($sum / $count, 1), 'count' => $count];
}

function getProductRatingSummary($productId) {
    $result = supabaseRequest('product_ratings?product_id=eq.' . urlencode($productId) . '&select=score');
    $rows = $result['data'] ?? [];
    $count = count($rows);
    if ($count === 0) return ['avg' => 0, 'count' => 0];
    $sum = 0;
    foreach ($rows as $row) $sum += intval($row['score']);
    return ['avg' => round($sum / $count, 1), 'count' => $count];
}

function getRatingsAnalytics() {
    $makerRows = supabaseRequest('maker_ratings?select=score,created_at')['data'] ?? [];
    $productRows = supabaseRequest('product_ratings?select=score,created_at')['data'] ?? [];

    $makerCount = count($makerRows);
    $productCount = count($productRows);
    $makerAvg = 0;
    $productAvg = 0;

    if ($makerCount > 0) {
        $sum = 0;
        foreach ($makerRows as $row) $sum += intval($row['score']);
        $makerAvg = round($sum / $makerCount, 2);
    }
    if ($productCount > 0) {
        $sum = 0;
        foreach ($productRows as $row) $sum += intval($row['score']);
        $productAvg = round($sum / $productCount, 2);
    }

    $weekAgo = time() - (7 * 24 * 60 * 60);
    $makerRecent = 0;
    foreach ($makerRows as $row) {
        if (!empty($row['created_at']) && strtotime($row['created_at']) >= $weekAgo) $makerRecent++;
    }
    $productRecent = 0;
    foreach ($productRows as $row) {
        if (!empty($row['created_at']) && strtotime($row['created_at']) >= $weekAgo) $productRecent++;
    }

    return [
        'maker_rating_count' => $makerCount,
        'maker_rating_avg' => $makerAvg,
        'maker_ratings_last_7_days' => $makerRecent,
        'product_rating_count' => $productCount,
        'product_rating_avg' => $productAvg,
        'product_ratings_last_7_days' => $productRecent,
        'total_ratings' => $makerCount + $productCount
    ];
}