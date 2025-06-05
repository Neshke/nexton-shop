<?php
// Headers
require_once '../../utils/cors_headers.php';

include_once '../../config/database.php';
include_once '../../models/Order.php';

// Instantiate DB & connect
$database = new Database();
$db = $database->getConnection(); // Pretpostavka da je getConnection() ispravna metoda

// Instantiate order object
$order = new Order($db);

// Get user ID from query parameter
// Osiguraj da je korisnik_id sanitizovan ako dolazi direktno od korisnika
$korisnik_id_param = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT) : null;

if (!$korisnik_id_param) {
    http_response_code(400); // Bad Request
    echo json_encode(array('success' => false, 'message' => 'User ID nije prosleđen.'));
    exit;
}

// Get orders for the user
$result = $order->getOrdersByUser($korisnik_id_param); // Prosleđujemo sanitizovan ID
$num = $result->rowCount();

// Check if any orders
if ($num > 0) {
    $orders_arr = array();
    $orders_arr['data'] = array();
    // $orders_arr['status'] = 200; // Opciono, ako frontend ovo očekuje u telu
    // $orders_arr['success'] = true; // Opciono

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        extract($row); // Ekstrahuje promenljive: $id, $korisnik_id, $ime_prezime, itd.

        // Construct order item - popunjavamo sva polja koja se vraćaju iz baze
        $order_item = array(
            'id' => $id,
            'korisnik_id' => $korisnik_id, // Ovo je korisnik_id iz tabele porudzbina
            'ime_prezime' => isset($ime_prezime) ? $ime_prezime : null,
            'adresa' => isset($adresa) ? $adresa : null,
            'telefon' => isset($telefon) ? $telefon : null,
            'datum_porudzbine' => $datum_porudzbine,
            'status' => $status,
            'ukupan_iznos' => $ukupan_iznos,
            'kreirano_u' => isset($kreirano_u) ? $kreirano_u : null // Ako postoji ovo polje
            // Dodaj ostala polja ako ih imaš u SELECT upitu u modelu
        );
        array_push($orders_arr['data'], $order_item);
    }

    http_response_code(200);
    echo json_encode($orders_arr); // Vraća {"data": [...]} ili kompletan objekat ako su dodati status/success
} else {
    // No orders
    http_response_code(404); // Ispravan status za "nema resursa"
    echo json_encode(
        // Vraćamo konzistentan format poruke
        array('success' => false, 'message' => 'Nema pronađenih porudžbina za ovog korisnika.', 'data' => []) // data: [] je dobra praksa
    );
}
?>