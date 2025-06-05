<?php
// Include CORS headers first, before any output
require_once '../../utils/cors_headers.php';

// Include database connection and User model
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../utils/auth_middleware.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create User object
$user = new User($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Only allow POST method for password changes
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 405,
        'message' => 'Method not allowed. Only POST is supported.'
    ]);
    exit;
}

// Get User ID from authenticated token
$token_data = get_user_from_token();

if (!$token_data || !isset($token_data['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 401,
        'message' => 'Unauthorized. User not authenticated or token invalid.'
    ]);
    exit;
}

$authenticated_user_id = $token_data['user_id'];

try {
    // Get input data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid JSON input.'
        ]);
        exit;
    }

    // Validate required fields
    if (!isset($data['currentPassword']) || !isset($data['newPassword']) || !isset($data['confirmPassword'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Missing required fields: currentPassword, newPassword, confirmPassword'
        ]);
        exit;
    }

    $currentPassword = trim($data['currentPassword']);
    $newPassword = trim($data['newPassword']);
    $confirmPassword = trim($data['confirmPassword']);

    // Basic validation
    if (empty($currentPassword)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Current password is required.'
        ]);
        exit;
    }

    if (empty($newPassword)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'New password is required.'
        ]);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'New password and confirmation do not match.'
        ]);
        exit;
    }

    // Password strength validation
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'New password must be at least 6 characters long.'
        ]);
        exit;
    }

    // Check if new password is different from current
    if ($currentPassword === $newPassword) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'New password must be different from current password.'
        ]);
        exit;
    }

    // Verify current password and change to new password
    if ($user->changePassword($authenticated_user_id, $currentPassword, $newPassword)) {
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'message' => 'Password changed successfully.'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Failed to change password. Current password may be incorrect.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Error changing password',
        'error' => $e->getMessage()
    ]);
}
?>
