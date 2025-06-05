<?php
/**
 * Šalje JSON odgovor sa odgovarajućim statusnim kodom
 */
function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

/**
 * Proverava HTTP metodu zahteva
 */
function validateRequestMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        sendResponse(405, 'Method Not Allowed');
    }
}

/**
 * Preuzima POST podatke iz tela zahteva
 */
function getPostData() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(400, 'Invalid JSON data');
    }
    
    return $data;
}

/**
 * Čisti ulazne podatke od potencijalno opasnih karaktera
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Proverava obavezna polja u zahtevu
 */
function validateRequiredFields($data, $required) {
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendResponse(400, "Missing required field: {$field}");
        }
    }
}

/**
 * Generiše JWT token za autentifikaciju
 */
function generateToken($userId, $username, $role = 'user') {
    $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
    $issuedAt = time();
    $expire = $issuedAt + 7200; // 2 hours

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expire,
        'user_id' => $userId,
        'username' => $username,
        'role' => $role // Add role to the payload
    ];

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    // Log token creation for debugging
    error_log("Generated token for user: $username with ID: $userId");
    
    return $token;
}
?>
