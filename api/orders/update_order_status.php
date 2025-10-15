<?php
require_once '../../config/database.php';
require_once '../../utils/auth_middleware.php'; // Correctly includes functions like requireAdmin()
require_once '../../utils/cors_headers.php'; 
require_once '../../models/Order.php';

// Log all incoming POST data for debugging
// error_log("update_order_status.php POST data: " . file_get_contents("php://input"));

// Authenticate user and check if admin
$userData = requireAdmin(); // Use requireAdmin() which is defined in auth_middleware.php

if (!$userData) { // requireAdmin() will exit on failure, but this is a fallback.
    http_response_code(403); 
    echo json_encode(["message" => "Nemate administratorska prava ili sesija nije validna."]);
    exit;
}

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $raw_input = file_get_contents("php://input");
    // Log raw input
    // error_log("Raw input to update_order_status.php: " . $raw_input);
    
    $data = json_decode($raw_input);

    // Log decoded data
    // if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    //     error_log("JSON decode error: " . json_last_error_msg());
    // } else {
    //     error_log("Decoded data: order_id=" . ($data->order_id ?? 'null') . ", status=" . ($data->status ?? 'null'));
    // }

    if (!isset($data->order_id) || !isset($data->status)) {
        http_response_code(400); // Bad Request
        echo json_encode(["message" => "Nedostaju ID porudžbine ili novi status."]);
        exit;
    }

    $order_id = filter_var($data->order_id, FILTER_VALIDATE_INT);
    $new_status_from_input = $data->status; // Use status directly from input

    // Log the received status before validation
    // error_log("Received status for order ID $order_id: \'$new_status_from_input\' (length: " . strlen($new_status_from_input) . ")");
    // foreach (str_split($new_status_from_input) as $char) {
    //    error_log("Char: " . $char . " ASCII: " . ord($char));
    // }

    $allowed_statuses = ["Na cekanju", "Zavrsena", "Otkazana"]; // Removed "U obradi" and "Poslato"
    
    // Validate order_id
    if ($order_id === false || $order_id <= 0) { // Check if filter_var failed or value is not positive
        http_response_code(400); 
        echo json_encode(["message" => "Nevažeći ID porudžbine."]);
        exit;
    }

    // Validate that the input status is one of the allowed literal strings (strict comparison)
    if (!in_array($new_status_from_input, $allowed_statuses, true)) { 
        // Log failure details
        // error_log("Status validation failed for order ID $order_id. Input: \'$new_status_from_input\'. Not in allowed list.");
        http_response_code(400);
        echo json_encode([
            "message" => "Nevažeći status. Primljeni status: \'" . htmlspecialchars($new_status_from_input) . "\'. Dozvoljeni statusi su: " . implode(", ", $allowed_statuses)
        ]);
        exit;
    }
    
    $order = new Order($pdo);

    // Pass the validated $new_status_from_input directly to the model method
    if ($order->updateStatus($order_id, $new_status_from_input)) {
        // error_log("Successfully updated status for order ID $order_id to \'$new_status_from_input\'.");
        http_response_code(200);
        echo json_encode(["message" => "Status porudžbine je uspešno ažuriran na \'" . htmlspecialchars($new_status_from_input) . "\'."]);
    } else {
        // error_log("Failed to update status for order ID $order_id to \'$new_status_from_input\'. Model returned false (rowCount might be 0 or execute failed).");
        http_response_code(500); 
        echo json_encode(["message" => "Greška prilikom ažuriranja statusa porudžbine ili status nije promenjen (proverite da li porudžbina postoji i da li je status različit od trenutnog)."]);
    } // Closes: if ($order->updateStatus(...))
} else { // Corresponds to: if ($method === 'POST')
    http_response_code(405); 
    echo json_encode(["message" => "Metoda nije dozvoljena."]);
} // Closes: if ($method === 'POST')
?>
