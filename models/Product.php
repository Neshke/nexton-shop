<?php
class Product
{
  // Database connection and table name
  private $conn;
  private $table_name = "products";

  // Object properties
  public $id;
  public $kategorija_id;
  public $naziv;
  public $slug;
  public $opis;
  public $cena;
  public $akcijska_cena;
  public $slika_url;
  public $dodatne_slike;
  public $kolicina_na_stanju;
  public $istaknuto;
  public $aktivan;
  public $kreirano_at;
  public $azurirano_at;
  public $kategorija_naziv;

  // Constructor with database connection
  public function __construct($db)
  {
    $this->conn = $db;
  }

  // Get all products
  public function getAll()
  {
    $query = "SELECT 
                    p.id, p.naziv, p.slug, p.opis, p.cena, p.akcijska_cena, 
                    p.kategorija_id, p.slika_url, p.kolicina_na_stanju, p.istaknuto, p.aktivan, 
                    p.kreirano_at, c.naziv as kategorija_naziv 
                FROM 
                    " . $this->table_name . " p
                    LEFT JOIN categories c ON p.kategorija_id = c.id
                ORDER BY 
                    p.kreirano_at DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();

    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $products[] = $row;
    }

    return $products;
  }

  // Get one product
  public function getOne()
  {
    $query = "SELECT 
                    p.id, p.naziv, p.slug, p.opis, p.cena, p.akcijska_cena, 
                    p.kategorija_id, p.slika_url, p.dodatne_slike, p.kolicina_na_stanju, p.istaknuto, 
                    p.aktivan, p.kreirano_at, c.naziv as kategorija_naziv 
                FROM 
                    " . $this->table_name . " p
                    LEFT JOIN categories c ON p.kategorija_id = c.id
                WHERE 
                    p.id = :id
                LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $this->id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      return $row;
    }

    return false;
  }

  // Get products by category
  public function getByCategory()
  {
    $query = "SELECT 
                    p.id, p.naziv, p.slug, p.opis, p.cena, p.akcijska_cena, 
                    p.kategorija_id, p.slika_url, p.kolicina_na_stanju, p.istaknuto, p.aktivan, 
                    p.kreirano_at, c.naziv as kategorija_naziv 
                FROM 
                    " . $this->table_name . " p
                    LEFT JOIN categories c ON p.kategorija_id = c.id
                WHERE 
                    p.kategorija_id = :kategorija_id
                ORDER BY 
                    p.kreirano_at DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':kategorija_id', $this->kategorija_id);
    $stmt->execute();

    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $products[] = $row;
    }

    return $products;
  }

  // Create product
  public function create()
  {
    $query = "INSERT INTO " . $this->table_name . " 
                (naziv, slug, opis, cena, kategorija_id, kolicina_na_stanju, slika_url, istaknuto, aktivan) 
                VALUES 
                (:naziv, :slug, :opis, :cena, :kategorija_id, :kolicina_na_stanju, :slika_url, :istaknuto, :aktivan)";

    $stmt = $this->conn->prepare($query);

    // Sanitize inputs
    $this->naziv = htmlspecialchars(strip_tags($this->naziv));
    $this->slug = htmlspecialchars(strip_tags($this->slug));
    $this->opis = htmlspecialchars(strip_tags($this->opis));
    $this->cena = floatval($this->cena);
    $this->kategorija_id = intval($this->kategorija_id);
    $this->kolicina_na_stanju = intval($this->kolicina_na_stanju);
    $this->istaknuto = intval($this->istaknuto);
    $this->aktivan = intval($this->aktivan);

    // Bind values
    $stmt->bindParam(':naziv', $this->naziv);
    $stmt->bindParam(':slug', $this->slug);
    $stmt->bindParam(':opis', $this->opis);
    $stmt->bindParam(':cena', $this->cena);
    $stmt->bindParam(':kategorija_id', $this->kategorija_id);
    $stmt->bindParam(':kolicina_na_stanju', $this->kolicina_na_stanju);
    $stmt->bindParam(':slika_url', $this->slika_url);
    $stmt->bindParam(':istaknuto', $this->istaknuto);
    $stmt->bindParam(':aktivan', $this->aktivan);

    // Execute query
    if ($stmt->execute()) {
      $this->id = $this->conn->lastInsertId();
      return true;
    }

    return false;
  }

  // Update product
  public function update()
  {
    // Ensure ID is set for the update
    if (empty($this->id)) {
      error_log("Product ID is missing for update.");
      return false;
    }

    $query = "UPDATE " . $this->table_name . " 
                SET 
                    naziv = :naziv,
                    slug = :slug,
                    opis = :opis,
                    cena = :cena,
                    akcijska_cena = :akcijska_cena,
                    kategorija_id = :kategorija_id,
                    kolicina_na_stanju = :kolicina_na_stanju,
                    slika_url = :slika_url,
                    istaknuto = :istaknuto,
                    aktivan = :aktivan,
                    azurirano_at = NOW()
                WHERE 
                    id = :id";

    $stmt = $this->conn->prepare($query);

    // Sanitize inputs (similar to create, but allow nulls for COALESCE in API controller if needed)
    $this->naziv = htmlspecialchars(strip_tags($this->naziv));
    $this->slug = htmlspecialchars(strip_tags($this->slug)); // Sanitize slug
    $this->opis = htmlspecialchars(strip_tags($this->opis));
    $this->cena = !is_null($this->cena) ? floatval($this->cena) : null;
    $this->akcijska_cena = !is_null($this->akcijska_cena) ? floatval($this->akcijska_cena) : null; // Sanitize akcijska_cena
    $this->kategorija_id = !is_null($this->kategorija_id) ? intval($this->kategorija_id) : null;
    $this->kolicina_na_stanju = !is_null($this->kolicina_na_stanju) ? intval($this->kolicina_na_stanju) : null;
    // slika_url can be a URL, so strip_tags might be too aggressive. Consider other validation if needed.
    $this->slika_url = !is_null($this->slika_url) ? htmlspecialchars(strip_tags($this->slika_url)) : null; 
    $this->istaknuto = !is_null($this->istaknuto) ? intval($this->istaknuto) : null;
    $this->aktivan = !is_null($this->aktivan) ? intval($this->aktivan) : null;

    // Bind parameters
    $stmt->bindParam(':id', $this->id);
    $stmt->bindParam(':naziv', $this->naziv);
    $stmt->bindParam(':slug', $this->slug); // Bind slug
    $stmt->bindParam(':opis', $this->opis);
    $stmt->bindParam(':cena', $this->cena);
    $stmt->bindParam(':akcijska_cena', $this->akcijska_cena); // Bind akcijska_cena
    $stmt->bindParam(':kategorija_id', $this->kategorija_id);
    $stmt->bindParam(':kolicina_na_stanju', $this->kolicina_na_stanju);
    $stmt->bindParam(':slika_url', $this->slika_url);
    $stmt->bindParam(':istaknuto', $this->istaknuto);
    $stmt->bindParam(':aktivan', $this->aktivan);

    // Execute query
    try {
      if ($stmt->execute()) {
        // Check if any row was actually updated
        return $stmt->rowCount() > 0;
      }
    } catch (PDOException $e) {
      // Log error for debugging
      error_log("Error updating product: " . $e->getMessage());
      return false;
    }

    return false;
  }

  // Delete product
  public function delete()
  {
    $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $this->id);

    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Create slug from product name
  public function createSlug($string)
  {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
  }

  /**
     * Delete the image file associated with this product
     *
     * @return boolean True if image was deleted or didn't exist, False on failure
     */
    public function deleteProductImage() {
        // If no image URL is set, return true (nothing to delete)
        if (empty($this->slika_url)) {
            return true;
        }

        // Get the image filename from the URL
        $imagePath = parse_url($this->slika_url, PHP_URL_PATH);
        if (!$imagePath) {
            return false;
        }
        
        // Extract filename from path
        $filename = basename($imagePath);
        
        // Construct the full path to the image file
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/nexton/api/uploads/';
        $fullPath = $uploadDir . $filename;
        
        // Check if the file exists and delete it
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        // Return true if file doesn't exist (nothing to delete)
        return true;
    }
}
?> 