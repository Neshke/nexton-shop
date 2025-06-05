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
validateRequiredFields($data, ['username', 'email', 'password']);

// Čišćenje podataka
$username = sanitizeInput($data['username']);
$email = sanitizeInput($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);
// Default role is 'user' unless specified as 'admin' by an admin user
$role = isset($data['role']) && $data['role'] === 'admin' ? 'admin' : 'user';

try {
  // Validate all input fields
  validateUsername($username);
  validateEmail($email);
  validatePassword($data['password']);

  $database = new Database();
  $db = $database->getConnection();

  // Additional validation - check if username is taken
  $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
  $stmt->execute([$username]);
  if ($stmt->fetch()) {
    throw new Exception('Korisničko ime je već zauzeto');
  }

  // Proveri da li email već postoji
  $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    sendResponse(400, 'Email adresa je već zauzeta');
  }

  // Kreiraj novog korisnika - now including role
  $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
  $stmt->execute([$username, $email, $password, $role]);

  $userId = $db->lastInsertId();

  // Generiši token - now including role
  $tokenValue = generateToken($userId, $username, $role);
  
  // Set token expiry time (15 minutes from now)
  $expiresAt = time() + (15 * 60); // 15 minutes in seconds
  
  // Create token object with value and expiry
  $token = [
    'value' => $tokenValue,
    'expiresAt' => $expiresAt
  ];

  sendResponse(201, 'Uspešna registracija', [
    'user' => [
      'id' => $userId,
      'username' => $username,
      'email' => $email,
      'role' => $role
    ],
    'token' => $token
  ]);

} catch (Exception $e) {
  sendResponse(400, $e->getMessage());
}
?>