<?php
require_once 'functions.php';

/**
 * Verifies JWT token from Authorization header
 * Returns payload if valid, or false if invalid
 */
function verifyToken() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        error_log("Authentication failed: No valid Authorization header found");
        return false;
    }
    
    $jwt = $matches[1];
    $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
    
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) {
        error_log("Authentication failed: Token doesn't have three parts");
        return false;
    }
    
    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
    
    // Verify signature
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
    $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    if (!hash_equals($expectedSignature, $signature)) {
        error_log("Authentication failed: Invalid signature");
        return false;
    }
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
    
    // Check if token has expired
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        error_log("Authentication failed: Token expired");
        return false;
    }
    
    return $payload;
}

/**
 * Verifies a specific JWT token passed as parameter
 * Returns payload if valid, or sends an error response
 */
function verifySpecificToken($token) {
    if (empty($token)) {
        sendResponse(401, 'No token provided');
    }

    $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        sendResponse(401, 'Invalid token format');
    }
    
    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
    
    // Verify signature
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
    $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    if (!hash_equals($expectedSignature, $signature)) {
        sendResponse(401, 'Invalid token signature');
    }
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
    
    // Check if token has expired
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        sendResponse(401, 'Token expired');
    }
    
    return $payload;
}

/**
 * Require authentication for a route
 * Returns payload if authenticated, dies with 401 if not
 */
function requireAuth() {
    $payload = verifyToken();
    if (!$payload) {
        // Get the Authorization header for debugging
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 'Not provided';
        
        // Log the error details
        error_log("Authentication failed. Auth header: " . substr($authHeader, 0, 20) . "...");
        
        http_response_code(401);
        echo json_encode([
            'status' => 401,
            'message' => 'Unauthorized - Valid authentication token required',
            'debug_info' => [
                'has_auth_header' => !empty($headers['Authorization']),
                'server_time' => time(),
                'request_method' => $_SERVER['REQUEST_METHOD']
            ]
        ]);
        exit;
    }
    return $payload;
}

/**
 * Require specific role for a route
 * Returns payload if authenticated with proper role, dies with 403 if not
 */
function requireRole($requiredRole) {
    $payload = requireAuth();
    
    if (!isset($payload['role']) || $payload['role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode([
            'status' => 403,
            'message' => 'Forbidden - You do not have permission to access this resource'
        ]);
        exit;
    }
    
    return $payload;
}

/**
 * Check if user has admin role
 * Returns payload if admin, dies with 403 if not
 */
function requireAdmin() {
    return requireRole('admin');
}

/**
 * Get user data from token without requiring authentication
 * Returns decoded token payload or false if invalid
 */
function get_user_from_token() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return false;
    }
    
    $jwt = $matches[1];
    $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
    
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) {
        return false;
    }
    
    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
    
    // Verify signature
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
    $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
    
    if (!$payload || !isset($payload['user_id'])) {
        return false;
    }
    
    // Check token expiration
    if (isset($payload['exp']) && time() > $payload['exp']) {
        return false;
    }
    
    return $payload;
}
?>
