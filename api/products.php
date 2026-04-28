<?php
/**
 * Products API Endpoint
 */

header('Content-Type: application/json');
require_once 'config.php';
handleCors();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
            if (
                !is_array($input) ||
                empty(trim($input['name'] ?? '')) ||
                empty(trim($input['price'] ?? '')) ||
                empty(trim($input['category'] ?? '')) ||
                empty(trim($input['artisan_id'] ?? '')) ||
                empty(trim($input['artisan_name'] ?? '')) ||
                empty(trim($input['artisan_whatsapp'] ?? ''))
            ) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required product fields']);
                break;
            }

            $result = addProduct(
                trim($input['name']),
                trim($input['price']),
                trim($input['story'] ?? ''),
                trim($input['category']),
                trim($input['image_url'] ?? ''),
                trim($input['artisan_id']),
                trim($input['artisan_name']),
                trim($input['artisan_whatsapp'])
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