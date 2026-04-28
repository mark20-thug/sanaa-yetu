<?php
/**
 * Makers API Endpoint
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
        $makers = getUniqueMakers();
        echo json_encode($makers);
        break;
        
    case 'register':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $result = registerMaker(
                $input['name'],
                $input['email'],
                $input['password'],
                $input['whatsapp']
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
            $result = loginMaker($input['email'], $input['password']);
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