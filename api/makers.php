<?php
/**
 * Makers API Endpoint
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
        $makers = getUniqueMakers();
        echo json_encode($makers);
        break;
        
    case 'register':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (
                !is_array($input) ||
                empty(trim($input['name'] ?? '')) ||
                empty(trim($input['email'] ?? '')) ||
                empty($input['password']) ||
                empty(trim($input['whatsapp'] ?? ''))
            ) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                break;
            }

            $result = registerMaker(
                trim($input['name']),
                trim($input['email']),
                $input['password'],
                trim($input['whatsapp'])
            );
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'login':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (
                !is_array($input) ||
                empty(trim($input['email'] ?? '')) ||
                empty($input['password'])
            ) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                break;
            }

            $result = loginMaker(trim($input['email']), $input['password']);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        $makers = getUniqueMakers();
        echo json_encode($makers);
}