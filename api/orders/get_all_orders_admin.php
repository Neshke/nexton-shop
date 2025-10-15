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

        // Dohvatanje stavki za trenutnu porudžbinu
        $stmt_items = $db->prepare("
            SELECT
                sp.id AS stavka_id,
                sp.proizvod_id,
                p.naziv AS naziv_proizvoda,
                sp.kolicina,
                sp.cena_po_komadu,
                (sp.kolicina * sp.cena_po_komadu) AS ukupna_cena_stavke
            FROM
                stavke_porudzbine sp
            JOIN
                products p ON sp.proizvod_id = p.id
            WHERE
                sp.porudzbina_id = :order_id
        ");
        $stmt_items->bindParam(':order_id', $id);
        $stmt_items->execute();
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Construct order item
        $order_item = array(
            'id' => $id,
            'korisnik_id' => isset($korisnik_id) ? $korisnik_id : null, // Dodato da se vrati korisnik_id
            'korisnicko_ime' => isset($korisnicko_ime) ? $korisnicko_ime : null, // Username from the JOIN in Order class
            'ime_prezime' => isset($ime_prezime) ? $ime_prezime : null, // Dodato ime_prezime
            'adresa' => isset($adresa) ? $adresa : null, // Dodata adresa
            'telefon' => isset($telefon) ? $telefon : null, // Dodat telefon
            'datum_porudzbine' => $datum_porudzbine,
            'status' => $status,
            'ukupan_iznos' => $ukupan_iznos,
            'kreirano_u' => $kreirano_u,
            'stavke' => $items // Dodajemo stavke porudžbine
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