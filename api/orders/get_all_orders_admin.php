<?php
// Headers
require_once '../../utils/cors_headers.php';
// Optional: Add authentication check for admin users here

include_once '../../config/database.php';
include_once '../../models/Order.php'; // Ispravljena putanja do modela

// Instantiate DB & connect
$database = new Database();
$db = $database->getConnection();

// Instantiate order object
$order = new Order($db);

// Get all orders
$result = $order->getAllOrders();
$num = $result->rowCount();

// Check if any orders
if ($num > 0) {
    // Orders array
    $orders_arr = array();
    $orders_arr['data'] = array();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        // Construct order item
        $order_item = array(
            'id' => $id,
            'korisnicko_ime' => $korisnicko_ime, // Username from the JOIN in Order class
            'datum_porudzbine' => $datum_porudzbine,
            'status' => $status,
            'ukupan_iznos' => $ukupan_iznos,
            'kreirano_u' => $kreirano_u
        );

        // Push to "data"
        array_push($orders_arr['data'], $order_item);
    }

    // Turn to JSON & output
    http_response_code(200);
    echo json_encode($orders_arr);
} else {
    // No orders
    http_response_code(404);
    echo json_encode(
        array('message' => 'Nema pronađenih porudžbina.')
    );
}
?>