<?php
// Šifra koju ćeš koristiti
$plainPassword = 'admin123';

// Heširaj šifru
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Base64 encoding šifre (za tvoju informaciju)
$base64Password = base64_encode($plainPassword);

echo "=== INFORMACIJE ===\n";
echo "Plain Password: $plainPassword\n";
echo "Base64 Encoded: $base64Password\n";
echo "\n";

echo "=== SQL UPITI ===\n\n";

// SQL za brisanje starog admina
echo "-- Obriši starog admin korisnika\n";
echo "DELETE FROM users WHERE email = 'admin@example.com';\n\n";

// SQL za kreiranje novog admina
echo "-- Kreiraj novog admin korisnika\n";
echo "INSERT INTO users (username, email, password, role, kreirano_at) VALUES \n";
echo "('admin', 'admin@example.com', '$hashedPassword', 'admin', NOW());\n\n";

echo "\n=== KREDENCIJALI ZA LOGOVANJE ===\n";
echo "Email: admin@example.com\n";
echo "Password: $plainPassword\n";
echo "Base64: $base64Password\n";
?>
