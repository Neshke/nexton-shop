<?php
require_once __DIR__ . '/../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Dodato za SMTP::DEBUG_SERVER

/**
 * Šalje email potvrdu korisniku nakon uspešnog kreiranja porudžbine.
 *
 * @param string $userEmail Email adresa korisnika.
 * @param string $userName Ime korisnika.
 * @param mixed $orderId ID porudžbine.
 * @param float $totalAmount Ukupan iznos porudžbine.
 * @param array $orderDetails Opciono, niz sa detaljima stavki porudžbine. Svaka stavka treba da bude niz sa 'name', 'quantity', 'price'.
 * @return bool True ako je email uspešno poslat, false u suprotnom.
 */
function sendOrderConfirmationEmail($userEmail, $userName, $orderId, $totalAmount, $orderDetails = []) {
    $mail = new PHPMailer(true);

    try {
        // Podešavanja servera
        // -------------------------------------------------------------------------------------
        // SMTP DEBUGGING:
        // SMTP::DEBUG_OFF (0) = isključeno (koristiti u produkciji)
        // SMTP::DEBUG_CLIENT (1) = prikazuje poruke koje šalje klijent
        // SMTP::DEBUG_SERVER (2) = prikazuje poruke koje šalje klijent i server (korisno za debug)
        // SMTP::DEBUG_CONNECTION (3) = kao 2, plus informacije o inicijalnoj konekciji
        // SMTP::DEBUG_LOWLEVEL (4) = prikazuje sve niske nivoe komunikacije
        $mail->SMTPDebug = 0; 

        // Kaže PHPMailer-u da koristi SMTP protokol
        $mail->isSMTP();

        // SMTP Host za Gmail
        $mail->Host       = 'smtp.gmail.com';

        // SMTP Autentifikacija: Da li SMTP server zahteva korisničko ime i lozinku?
        // Skoro uvek je true.
        $mail->SMTPAuth   = true;

        // Tvoja Gmail adresa
        $mail->Username   = 'anesic9@gmail.com';

        // TVOJA GMAIL LOZINKA ili APP PASSWORD
        // VAŽNO: Ako koristiš 2-FAKTORSKU AUTENTIFIKACIJU na Gmailu,
        // MORAŠ generisati "App Password" na svom Google nalogu i uneti je ovde.
        // Tvoja regularna Gmail lozinka NEĆE raditi sa 2FA.
        // Ako ne koristiš 2FA, unesi svoju regularnu Gmail lozinku,
        // ali možda ćeš morati da omogućiš "Less secure app access" na Google nalogu.
        $mail->Password   = 'xzkt fsit lvbp peim';

        // Enkripcija i port za Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        // Alternativno, možeš probati SMTPS (SSL) ako STARTTLS ne radi:
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        // $mail->Port       = 465;

        $mail->CharSet = 'UTF-8';

        // Primaoci
        // Postavi "From" email na tvoju Gmail adresu ili drugu adresu koju Gmail dozvoljava da šalješ kao
        $mail->setFrom('anesic9@gmail.com', 'NextOn Shop'); // Prilagodi "Naziv Vaše Prodavnice"
        // Email adresa i ime primaoca (korisnik koji je napravio porudžbinu)
        $mail->addAddress($userEmail, $userName);

        // Sadržaj
        $mail->isHTML(true); 
        $mail->Subject = 'Potvrda porudžbine #' . htmlspecialchars($orderId);

        // Definicija boja i stilova
        $primaryColor = '#6366f1'; // Ljubičasta sa sajta
        $backgroundColor = '#f4f4f7'; // Svetlo siva pozadina za ceo email
        $containerBackgroundColor = '#ffffff'; // Bela pozadina za kontejner sadržaja
        $textColor = '#333333'; // Glavna boja teksta
        $lightTextColor = '#555555'; // Svetlija boja teksta

        // Početak HTML tela emaila
        $emailBody = '<body style="margin: 0; padding: 0; background-color: ' . $backgroundColor . '; font-family: Arial, sans-serif;">';
        $emailBody .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: ' . $backgroundColor . '; padding: 20px;">';
        $emailBody .= '<tr><td>';
        
        // Glavni kontejner za sadržaj
        $emailBody .= '<table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; margin: 0 auto; background-color: ' . $containerBackgroundColor . '; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">';
        
        // Header emaila
        $emailBody .= '<tr><td style="background-color: ' . $primaryColor . '; padding: 25px 30px; text-align: center; color: #ffffff;">';
        $emailBody .= '<h1 style="margin: 0; font-size: 28px; font-weight: bold;">Potvrda Vaše Porudžbine</h1>';
        $emailBody .= '</td></tr>';

        // Sadržaj emaila
        $emailBody .= '<tr><td style="padding: 30px 30px 40px 30px; color: ' . $textColor . '; line-height: 1.65;">';
        $emailBody .= '<h2 style="color: ' . $primaryColor . '; font-size: 22px; margin-top: 0; margin-bottom: 15px;">Hvala na porudžbini!</h2>';
        $emailBody .= '<p style="margin-bottom: 10px;">Poštovani/a ' . htmlspecialchars($userName) . ',</p>';
        $emailBody .= '<p style="margin-bottom: 20px;">Vaša porudžbina broj <strong style="color: ' . $primaryColor . '; font-weight: bold;">#' . htmlspecialchars($orderId) . '</strong> je uspešno primljena i trenutno se obrađuje.</p>';
        
        // Detalji porudžbine
        if (!empty($orderDetails) && is_array($orderDetails)) {
            $emailBody .= '<h3 style="color: ' . $primaryColor . '; font-size: 18px; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid ' . $primaryColor . '; padding-bottom: 8px;">Detalji porudžbine:</h3>';
            $emailBody .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">';
            
            // Zaglavlje tabele stavki
            $emailBody .= '<thead><tr>';
            $emailBody .= '<th style="text-align: left; padding: 8px 0; color: ' . $lightTextColor . '; border-bottom: 1px solid #dddddd; font-size: 14px;">Stavka</th>';
            $emailBody .= '<th style="text-align: center; padding: 8px 0; color: ' . $lightTextColor . '; border-bottom: 1px solid #dddddd; font-size: 14px;">Količina</th>';
            $emailBody .= '<th style="text-align: right; padding: 8px 0; color: ' . $lightTextColor . '; border-bottom: 1px solid #dddddd; font-size: 14px;">Cena</th>';
            $emailBody .= '</tr></thead>';
            
            $emailBody .= '<tbody>';
            foreach ($orderDetails as $item) {
                $itemName = isset($item['name']) ? $item['name'] : 'Nepoznata stavka';
                $itemQuantity = isset($item['quantity']) ? $item['quantity'] : 'N/A';
                $itemPricePerUnit = isset($item['price']) ? $item['price'] : 0;
                $itemTotalPrice = (isset($item['price']) && isset($item['quantity'])) ? number_format($itemPricePerUnit * $item['quantity'], 2, ',', '.') : 'N/A';

                $emailBody .= '<tr>';
                $emailBody .= '<td style="padding: 10px 0; color: ' . $textColor . '; border-bottom: 1px solid #eeeeee; font-size: 15px;">' . htmlspecialchars($itemName) . '</td>';
                $emailBody .= '<td style="text-align: center; padding: 10px 0; color: ' . $textColor . '; border-bottom: 1px solid #eeeeee; font-size: 15px;">' . htmlspecialchars($itemQuantity) . '</td>';
                $emailBody .= '<td style="text-align: right; padding: 10px 0; color: ' . $textColor . '; border-bottom: 1px solid #eeeeee; font-size: 15px; font-weight: bold;">' . htmlspecialchars($itemTotalPrice) . ' €</td>';
                $emailBody .= '</tr>';
            }
            $emailBody .= '</tbody></table>';
        }

        // Ukupan iznos
        $emailBody .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 25px;"><tr>';
        $emailBody .= '<td style="text-align: right; padding-top: 15px; font-size: 18px; color: ' . $textColor . ';">Ukupan iznos:</td>';
        $emailBody .= '<td style="text-align: right; padding-top: 15px; font-size: 20px; color: ' . $primaryColor . '; font-weight: bold;">' . htmlspecialchars(number_format($totalAmount, 2, ',', '.')) . ' €</td>';
        $emailBody .= '</tr></table>';
        
        $emailBody .= '<p style="margin-top: 30px; margin-bottom: 10px;">Uskoro ćemo Vas obavestiti o statusu Vaše porudžbine.</p>';
        $emailBody .= '<p style="margin-bottom: 0;">Srdačan pozdrav,<br>NextOn Shop </p>';
        $emailBody .= '</td></tr>';

        // Footer emaila
        $emailBody .= '<tr><td style="background-color: #e9e9ef; padding: 20px; text-align: center; color: ' . $lightTextColor . '; font-size: 12px; border-top: 1px solid #dddddd;">';
        $emailBody .= '&copy; ' . date('Y') . ' NextOn Shop. Sva prava zadržana.<br>';
        // $emailBody .= '<a href="https://vasasajt.com" style="color: ' . $primaryColor . '; text-decoration: none;">Posetite naš sajt</a>';
        $emailBody .= '</td></tr>';

        $emailBody .= '</table>';
        $emailBody .= '</td></tr>';
        $emailBody .= '</table>';
        $emailBody .= '</body>';
        
        $mail->Body    = $emailBody;
        $mail->AltBody = strip_tags(str_replace(["<br>", "<li>", "</li>", "</tr>", "</td>", "</th>"], ["\n", "- ", "\n", "\n", " | ", " | "], preg_replace('/<table[^>]*>(.*?)<\/table>/is', '', $emailBody)));

        
        $mail->Body    = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody)); 

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ispis detaljne greške ako SMTPDebug nije dovoljan ili za druge izuzetke
        error_log("Email nije poslat. PHPMailer greška: {$mail->ErrorInfo}");
        // Možete dodati i echo za direktan prikaz u Postmanu ako je potrebno za debug
        // echo "PHPMailer ErrorInfo: {$mail->ErrorInfo}"; 
        return false;
    }
}
?>
