<?php
/**
 * Products API Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route requests
switch ($action) {
    case 'list':
        $category = $_GET['category'] ?? null;
        $products = getProducts($category);
        echo json_encode($products);
        break;
        
    case 'get':
        $id = $_GET['id'] ?? '';
        $product = getProduct($id);
        echo json_encode($product);
        break;
        
    case 'maker':
        $makerId = $_GET['maker_id'] ?? '';
        $products = getMakerProducts($makerId);
        echo json_encode($products);
        break;
        
    case 'search':
        $query = $_GET['q'] ?? '';
        $products = searchProducts($query);
        echo json_encode($products);
        break;
        
    case 'add':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $result = addProduct(
                $input['name'],
                $input['price'],
                $input['story'] ?? '',
                $input['category'],
                $input['image_url'] ?? '',
                $input['artisan_id'],
                $input['artisan_name'],
                $input['artisan_whatsapp']
            );
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        // Default: return all products
        $products = getProducts();
        echo json_encode($products);
}