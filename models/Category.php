<?php
class Category
{
  // Database connection and table name
  private $conn;
  private $table_name = "categories";

  // Object properties
  public $id;
  public $naziv;            // name
  public $url_slug;         // slug
  public $opis;             // description
  public $slika_url;        // image_url
  public $aktivan;          // is_active
  public $kreirano_at;      // created_at
  public $azurirano_at;     // updated_at

  // Constructor with database connection
  public function __construct($db)
  {
    $this->conn = $db;
  }

  // Get all categories
  public function getAll()
  {
    $query = "SELECT 
                    id, naziv, url_slug, opis, slika_url, aktivan, kreirano_at
                FROM 
                    " . $this->table_name . "
                ORDER BY 
                    naziv ASC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();

    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $categories[] = $row;
    }

    return $categories;
  }

  // Get one category
  public function getOne()
  {
    $query = "SELECT 
                    id, naziv, url_slug, opis, slika_url, aktivan, kreirano_at
                FROM 
                    " . $this->table_name . "
                WHERE 
                    id = :id
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

  // Create category
  public function create()
  {
    $query = "INSERT INTO " . $this->table_name . " 
                (naziv, url_slug, opis, slika_url, aktivan) 
                VALUES 
                (:naziv, :url_slug, :opis, :slika_url, :aktivan)";

    $stmt = $this->conn->prepare($query);

    // Sanitize inputs
    $this->naziv = htmlspecialchars(strip_tags($this->naziv));
    $this->url_slug = htmlspecialchars(strip_tags($this->url_slug));
    $this->opis = htmlspecialchars(strip_tags($this->opis));
    $this->aktivan = intval($this->aktivan);

    // Bind values
    $stmt->bindParam(':naziv', $this->naziv);
    $stmt->bindParam(':url_slug', $this->url_slug);
    $stmt->bindParam(':opis', $this->opis);
    $stmt->bindParam(':slika_url', $this->slika_url);
    $stmt->bindParam(':aktivan', $this->aktivan);

    // Execute query
    if ($stmt->execute()) {
      $this->id = $this->conn->lastInsertId();
      return true;
    }

    return false;
  }

  // Update category
  public function update()
  {
    $query = "UPDATE " . $this->table_name . " 
                SET 
                    naziv = COALESCE(:naziv, naziv),
                    opis = COALESCE(:opis, opis),
                    slika_url = COALESCE(:slika_url, slika_url),
                    aktivan = COALESCE(:aktivan, aktivan)
                WHERE 
                    id = :id";

    $stmt = $this->conn->prepare($query);

    // Bind parameters
    $stmt->bindParam(':id', $this->id);
    $stmt->bindParam(':naziv', $this->naziv);
    $stmt->bindParam(':opis', $this->opis);
    $stmt->bindParam(':slika_url', $this->slika_url);
    $stmt->bindParam(':aktivan', $this->aktivan);

    // Execute query
    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Delete category
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

  // Create slug from category name
  public function createSlug($string)
  {
    $url_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $url_slug;
  }
}
?>