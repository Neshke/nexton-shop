<?php
require_once '../../utils/cors_headers.php';
require_once '../../config/database.php';
require_once '../../utils/validation.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Šalje email poruku sa kontakt forme
 * 
 * @param string $name Ime pošiljaoca
 * @param string $email Email adresa pošiljaoca
 * @param string $message Poruka pošiljaoca
 * @return bool True ako je email uspešno poslat, false u suprotnom.
 */
function sendContactEmail($name, $email, $message) {
    $mail = new PHPMailer(true);

    try {
        // Podešavanja servera
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'anesic9@gmail.com'; // Vaš email
        $mail->Password   = 'ikxj pegf nqvc kmju'; // Vaša lozinka ili app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Primaoci
        $mail->setFrom('anesic9@gmail.com', 'Nexton E-Commerce');
        $mail->addAddress('anesic9@gmail.com', 'Admin'); // Adresa administratora
        $mail->addReplyTo($email, $name); // Omogućava direktan odgovor pošiljaocu

        // Sadržaj
        $mail->isHTML(true);
        $mail->Subject = 'Nova kontakt poruka sa sajta';
        
        $emailBody = "<h2>Nova kontakt poruka</h2>";
        $emailBody .= "<p><strong>Ime:</strong> " . htmlspecialchars($name) . "</p>";
        $emailBody .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $emailBody .= "<p><strong>Poruka:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
        $emailBody .= "<hr>";
        $emailBody .= "<p><small>Ova poruka je poslata sa kontakt forme na vašem sajtu.</small></p>";
        
        $mail->Body    = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email nije poslat. PHPMailer greška: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and decode the JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    
    // Check if required fields are provided
    if (!isset($data->name) || !isset($data->email) || !isset($data->message)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Sva polja su obavezna'
        ]);
        exit;
    }
    
    // Sanitize input data
    $name = htmlspecialchars(strip_tags($data->name));
    $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags($data->message));
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'Neispravan format email adrese'
        ]);
        exit;
    }
    
    try {
        // Send the email
        $emailSent = sendContactEmail($name, $email, $message);
        
        if ($emailSent) {
            http_response_code(200);
            echo json_encode([
                'status' => 200,
                'message' => 'Poruka je uspešno poslata'
            ]);
        } else {
            throw new Exception("Greška prilikom slanja emaila.");
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 500,
            'message' => 'Došlo je do greške prilikom slanja poruke: ' . $e->getMessage()
        ]);
    }
} else {
    // Handle non-POST requests
    http_response_code(405);
    echo json_encode([
        'status' => 405,
        'message' => 'Method Not Allowed'
    ]);
}
?>
