<?php
require_once '../../utils/cors_headers.php';
require_once '../../config/database.php';
require_once '../../utils/functions.php';
require_once '../../utils/validation.php';

// Proveri HTTP metodu
validateRequestMethod('POST');

// Preuzmi podatke
$data = getPostData();

// Validacija obaveznih polja
validateRequiredFields($data, ['email', 'password']);

try {
  // Čišćenje i validacija podataka
  $email = sanitizeInput($data['email']);
  validateEmail($email);

  if (empty($data['password'])) {
    throw new Exception('Lozinka je obavezna');
  }

  $database = new Database();
  $db = $database->getConnection();

  // Proveri da li korisnik postoji - now including role
  $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    throw new Exception('Korisnik sa ovom email adresom ne postoji');
  }

  // Proveri lozinku
  if (!password_verify($data['password'], $user['password'])) {
    throw new Exception('Pogrešna lozinka');
  }

  // Ukloni lozinku iz odgovora
  unset($user['password']);

  // Generiši token - pass role for inclusion in token
  $tokenValue = generateToken($user['id'], $user['username'], $user['role']);
  
  // Set token expiry time (15 minutes from now)
  $expiresAt = time() + (15 * 60); // 15 minutes in seconds
  
  // Create token object with value and expiry
  $token = [
    'value' => $tokenValue,
    'expiresAt' => $expiresAt
  ];

  // Pošalji uspešan odgovor
  sendResponse(200, 'Uspešna prijava', [
    'user' => $user,
    'token' => $token
  ]);

} catch (Exception $e) {
  sendResponse(400, $e->getMessage());
}
?>