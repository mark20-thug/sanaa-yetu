<?php
/**
 * Admin API Endpoint
 * Protected by X-Admin-Key header or admin_key query param.
 */

header('Content-Type: application/json');
require_once 'config.php';
handleCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isAdminRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized admin request']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'pending_makers':
        echo json_encode(['success' => true, 'data' => listPendingMakers()]);
        break;

    case 'review_maker':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $makerId = trim($input['maker_id'] ?? '');
        $decision = trim($input['decision'] ?? '');
        $notes = trim($input['notes'] ?? '');
        if (!$makerId || !in_array($decision, ['approve', 'reject'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'maker_id and decision are required']);
            break;
        }
        $result = reviewMaker($makerId, $decision === 'approve', $notes);
        echo json_encode($result);
        break;

    case 'pending_products':
        echo json_encode(['success' => true, 'data' => listPendingProducts()]);
        break;

    case 'review_product':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = trim($input['product_id'] ?? '');
        $decision = trim($input['decision'] ?? '');
        $reason = trim($input['reason'] ?? '');
        if (!$productId || !in_array($decision, ['approve', 'reject'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id and decision are required']);
            break;
        }
        $result = reviewProduct($productId, $decision === 'approve', $reason);
        echo json_encode($result);
        break;

    case 'ratings_analytics':
        echo json_encode(['success' => true, 'data' => getRatingsAnalytics()]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Unknown admin action']);
}
