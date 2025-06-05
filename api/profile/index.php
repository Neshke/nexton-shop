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

// Get User ID from authenticated token
$authenticated_user_id = null;
$token_data = get_user_from_token();

if ($token_data && isset($token_data['user_id'])) {
    $authenticated_user_id = $token_data['user_id'];
} else {
    http_response_code(401);
    echo json_encode([
        'status' => 401,
        'message' => 'Unauthorized. User not authenticated or token invalid.'
    ]);
    exit;
}

switch ($method) {
    case 'GET':
        // Get current user's profile
        try {
            $userData = $user->getUserById($authenticated_user_id);

            if ($userData) {
                // Sanitize user data for safe output
                $safeUserData = $user->sanitizeUserData($userData);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 200,                    'message' => 'User profile found',
                    'data' => $safeUserData
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 404,
                    'message' => 'User profile not found'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Error fetching user profile',
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        // Update current user's profile
        try {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);

            if (!$data) {
                http_response_code(400);
                echo json_encode([
                    'status' => 400,
                    'message' => 'Invalid JSON input.'
                ]);
                break;
            }

            // Validate input data
            $validationErrors = $user->validateProfileData($data);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validationErrors
                ]);
                break;
            }

            // Check if user exists
            if (!$user->userExists($authenticated_user_id)) {
                http_response_code(404);
                echo json_encode([
                    'status' => 404,
                    'message' => 'User not found for update.'
                ]);
                break;
            }

            // Update the user profile
            if ($user->updateProfile($authenticated_user_id, $data)) {
                // Fetch updated user data to return
                $updatedUserData = $user->getUserById($authenticated_user_id);
                $safeUserData = $user->sanitizeUserData($updatedUserData);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 200,
                    'message' => 'Profile updated successfully',
                    'data' => $safeUserData
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 500,
                    'message' => 'Unable to update profile. No changes made or database error.'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Error updating user profile',
                'error' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'status' => 405,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>
