<?php
class Database
{
  // Database configuration
  private $host = "localhost";
  private $db_name = "nextonshop";
  private $username = "root";
  private $password = "";
  private $charset = "utf8mb4";
  private $conn = null;

  public function getConnection()
  {
    try {
      if ($this->conn === null) {
        $dsn = "mysql:host=" . $this->host .
          ";dbname=" . $this->db_name .
          ";charset=" . $this->charset;

        $options = [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ];

        $this->conn = new PDO($dsn, $this->username, $this->password, $options);
      }
      return $this->conn;
    } catch (PDOException $e) {
      throw new Exception("Connection error: " . $e->getMessage());
    }
  }
}
?>