<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer manual tanpa Composer
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'support@nugasyuk.com'; // Pastikan tidak ada typo!
        $mail->Password = '1mMilw~L6'; // Pastikan password benar!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Debugging (Opsional, jika perlu)
        // $mail->SMTPDebug = 2; 
        // $mail->Debugoutput = 'html';

        // Pengirim & Penerima
        $mail->setFrom($mail->Username, 'NugasYuk Support'); 
        $mail->addAddress($to);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email gagal dikirim: " . $mail->ErrorInfo);
        return false;
    }
}
?>
