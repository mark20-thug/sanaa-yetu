<?php
/**
 * Ratings API Endpoint
 */

header('Content-Type: application/json');
require_once 'config.php';
handleCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'rate_maker':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $makerId = trim($input['maker_id'] ?? '');
        $score = intval($input['score'] ?? 0);
        $comment = trim($input['comment'] ?? '');
        $raterToken = trim($input['rater_token'] ?? '');
        echo json_encode(rateMaker($makerId, $score, $comment, $raterToken));
        break;

    case 'rate_product':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = trim($input['product_id'] ?? '');
        $makerId = trim($input['maker_id'] ?? '');
        $score = intval($input['score'] ?? 0);
        $comment = trim($input['comment'] ?? '');
        $raterToken = trim($input['rater_token'] ?? '');
        echo json_encode(rateProduct($productId, $makerId, $score, $comment, $raterToken));
        break;

    case 'maker_summary':
        $makerId = trim($_GET['maker_id'] ?? '');
        if (!$makerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'maker_id is required']);
            break;
        }
        echo json_encode(['success' => true, 'data' => getMakerRatingSummary($makerId)]);
        break;

    case 'product_summary':
        $productId = trim($_GET['product_id'] ?? '');
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id is required']);
            break;
        }
        echo json_encode(['success' => true, 'data' => getProductRatingSummary($productId)]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Unknown ratings action']);
}
