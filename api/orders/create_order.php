<?php
// Headers
require_once '../../utils/cors_headers.php';

include_once '../../config/database.php'; 
include_once '../../models/Order.php';    
include_once '../../helpers/email_helper.php'; 

// Instantiate DB & connect
$database = new Database();
$db = $database->getConnection(); 

// Instantiate order object
$order = new Order($db);

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

// Validacija osnovnih podataka - proširi po potrebi
if (
    !isset($data->ime_prezime) || empty(trim($data->ime_prezime)) ||
    !isset($data->adresa) || empty(trim($data->adresa)) ||
    !isset($data->telefon) || empty(trim($data->telefon)) ||
    !isset($data->ukupan_iznos) || !is_numeric($data->ukupan_iznos) ||
    !isset($data->stavke) || !is_array($data->stavke) || empty($data->stavke)
) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Nisu prosleđeni svi obavezni podaci (ime, adresa, telefon, iznos, stavke) ili su neispravnog formata.']);
    exit;
}

// Priprema podataka za model Order - sanitizacija je već u modelu, ali može i ovde
$korisnik_id_val = isset($data->korisnik_id) && !empty($data->korisnik_id) ? filter_var($data->korisnik_id, FILTER_SANITIZE_NUMBER_INT) : null;
$ime_prezime_val = trim(strip_tags($data->ime_prezime));
$adresa_val = trim(strip_tags($data->adresa));
$telefon_val = trim(strip_tags($data->telefon));
$ukupan_iznos_val = filter_var($data->ukupan_iznos, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$status_val = !empty($data->status) ? strip_tags($data->status) : 'na cekanju';

// Započni transakciju
$db->beginTransaction();

try {
    // Kreiraj porudžbinu pozivom metode iz Order modela
    // Prosleđuju se sanitizovane vrednosti direktno metodi
    if ($order->createOrder($korisnik_id_val, $ime_prezime_val, $adresa_val, $telefon_val, $ukupan_iznos_val, $status_val)) {
        $order_id = $order->id; // Dohvati ID kreirane porudžbine iz modela

        // Unos stavki porudžbine (tabela: stavke_porudzbine)
        // ISPRAVLJENO: Korišćenje `porudzbina_id` umesto `order_id` u SQL upitu
        // ISPRAVLJENO: Korišćenje `proizvod_id` umesto `product_id` u SQL upitu
        $sql_item = "INSERT INTO stavke_porudzbine (porudzbina_id, proizvod_id, kolicina, cena_po_komadu) 
                     VALUES (:porudzbina_id, :proizvod_id, :kolicina, :cena_po_komadu)";
        $stmt_item = $db->prepare($sql_item);

        foreach ($data->stavke as $item) {
            if (!isset($item->product_id) || !isset($item->kolicina) || !isset($item->cena_po_komadu)) {
                throw new Exception("Nedostaju podaci za jednu od stavki porudžbine.");
            }

            // $product_id promenljiva i dalje može da se zove tako, jer $item->product_id dolazi sa frontenda
            $product_id_from_payload = filter_var($item->product_id, FILTER_SANITIZE_NUMBER_INT);
            $kolicina = filter_var($item->kolicina, FILTER_SANITIZE_NUMBER_INT);
            $cena_po_komadu = filter_var($item->cena_po_komadu, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            // ISPRAVLJENO: Korišćenje `:porudzbina_id` za bindParam i prosleđivanje $order_id
            $stmt_item->bindParam(':porudzbina_id', $order_id, PDO::PARAM_INT);
            // ISPRAVLJENO: Korišćenje `:proizvod_id` za bindParam i prosleđivanje $product_id_from_payload
            $stmt_item->bindParam(':proizvod_id', $product_id_from_payload, PDO::PARAM_INT);
            $stmt_item->bindParam(':kolicina', $kolicina, PDO::PARAM_INT);
            $stmt_item->bindParam(':cena_po_komadu', $cena_po_komadu); 
            
            if (!$stmt_item->execute()) {
                $errorInfo = $stmt_item->errorInfo();
                throw new Exception("Greška pri unosu stavke porudžbine (Product ID: $product_id): " . $errorInfo[2]);
            }
        }

        // Potvrdi transakciju ako je sve uspešno
        $db->commit();

        // Slanje email potvrde
        $user_email_to_send = null;
        $user_name_to_send = $ime_prezime_val; 

        // ISPRAVLJENO: Koristi se email_za_potvrdu iz payload-a
        if (!empty($data->email_za_potvrdu)) {
            $user_email_to_send = filter_var($data->email_za_potvrdu, FILTER_SANITIZE_EMAIL);
        }
        
        // Deo za ime korisnika ostaje isti, ako se ime šalje odvojeno za email
        // Ako se ime za email ne šalje posebno, ovaj deo se može ukloniti ili prilagoditi
        if (!empty($data->ime_korisnika) && $data->ime_korisnika !== $ime_prezime_val) {
            $user_name_to_send = strip_tags($data->ime_korisnika);
        }
        
        if ($user_email_to_send && function_exists('sendOrderConfirmationEmail')) {
            if (sendOrderConfirmationEmail($user_email_to_send, $user_name_to_send, $order_id, $ukupan_iznos_val)) {
                // Uklonjeno: error_log("Email potvrde za porudžbinu #{$order_id} poslat na {$user_email_to_send}.");
            } else {
                error_log("Neuspešno slanje email potvrde za porudžbinu #{$order_id} na {$user_email_to_send}.");
            }
        } else {
            error_log("Email korisnika nije prosleđen ili funkcija sendOrderConfirmationEmail ne postoji za porudžbinu #{$order_id}.");
        }

        http_response_code(201); // 201 Created
        echo json_encode(
            ['success' => true, 'message' => 'Porudžbina je uspešno kreirana.', 'order_id' => $order_id]
        );

    } else {
        // Ako $order->createOrder() ne uspe, baci izuzetak da bi se uradio rollback
        throw new Exception("Kreiranje osnovne porudžbine nije uspelo. Proverite logove modela Order.");
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500); // Internal Server Error (ili 503 Service Unavailable)
    error_log("API Error create_order.php: " . $e->getMessage()); 
    echo json_encode(
        ['success' => false, 'message' => 'Porudžbina nije kreirana. Došlo je do greške na serveru.']
        // 'debug_error' => $e->getMessage() // Samo za razvojno okruženje
    );
}
?>
