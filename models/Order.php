<?php

class Order {

    // Database connection and table name
    private $conn;
    private $table_name = "porudzbine"; // Or "orders" if you used the English version

    // Object properties
    public $id;
    public $korisnik_id; // user_id
    public $ime_prezime; // NOVO: Ime i prezime naručioca
    public $adresa;      // NOVO: Adresa za dostavu
    public $telefon;     // NOVO: Kontakt telefon
    public $datum_porudzbine; // order_date
    public $status;
    public $ukupan_iznos; // total_amount
    public $kreirano_u; // created_at
    public $azurirano_u; // updated_at

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new order
    public function createOrder($korisnik_id_param, $ime_prezime_param, $adresa_param, $telefon_param, $ukupan_iznos_param, $status_param = 'na cekanju') {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " 
                    (korisnik_id, ime_prezime, adresa, telefon, ukupan_iznos, status) 
                  VALUES 
                    (:korisnik_id, :ime_prezime, :adresa, :telefon, :ukupan_iznos, :status)";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $korisnik_id_sanitized = is_null($korisnik_id_param) ? null : htmlspecialchars(strip_tags($korisnik_id_param));
        $ime_prezime_sanitized = htmlspecialchars(strip_tags($ime_prezime_param));
        $adresa_sanitized = htmlspecialchars(strip_tags($adresa_param));
        $telefon_sanitized = htmlspecialchars(strip_tags($telefon_param));
        $ukupan_iznos_sanitized = htmlspecialchars(strip_tags($ukupan_iznos_param));
        $status_sanitized = htmlspecialchars(strip_tags($status_param));

        // Bind values
        if (is_null($korisnik_id_sanitized)) {
            $stmt->bindParam(":korisnik_id", $korisnik_id_sanitized, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":korisnik_id", $korisnik_id_sanitized, PDO::PARAM_INT);
        }
        $stmt->bindParam(":ime_prezime", $ime_prezime_sanitized);
        $stmt->bindParam(":adresa", $adresa_sanitized);
        $stmt->bindParam(":telefon", $telefon_sanitized);
        $stmt->bindParam(":ukupan_iznos", $ukupan_iznos_sanitized);
        $stmt->bindParam(":status", $status_sanitized);

        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            // Postavi i ostala svojstva objekta ako je potrebno nakon kreiranja
            $this->korisnik_id = $korisnik_id_sanitized;
            $this->ime_prezime = $ime_prezime_sanitized;
            $this->adresa = $adresa_sanitized;
            $this->telefon = $telefon_sanitized;
            $this->ukupan_iznos = $ukupan_iznos_sanitized;
            $this->status = $status_sanitized;
            return true;
        }
        return false;
    }

    // Fetch orders by user ID
    public function getOrdersByUser($korisnik_id_param) { 
        $query = "SELECT id, korisnik_id, ime_prezime, adresa, telefon, datum_porudzbine, status, ukupan_iznos, kreirano_u 
                  FROM " . $this->table_name . " 
                  WHERE korisnik_id = :korisnik_id ORDER BY kreirano_u DESC";

        $stmt = $this->conn->prepare($query);
        $korisnik_id_sanitized = htmlspecialchars(strip_tags($korisnik_id_param));
        $stmt->bindParam(":korisnik_id", $korisnik_id_sanitized);
        $stmt->execute();
        return $stmt;
    }

    // Fetch all orders (for admin)
    public function getAllOrders() {
        $query = "SELECT p.id, p.korisnik_id, u.username as korisnicko_ime, p.ime_prezime, p.adresa, p.telefon, p.datum_porudzbine, p.status, p.ukupan_iznos, p.kreirano_u 
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.korisnik_id = u.id
                  ORDER BY p.kreirano_u DESC"; 

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>