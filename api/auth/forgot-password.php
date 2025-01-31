<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/security.php';
require_once __DIR__ . '/../../utils/email.php'; // Pastikan ini ada!

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($data['email'] ?? '');

    if (!$email || !validateEmail($email)) {
        sendResponse(400, "Email tidak valid!");
    }

    try {
        // Cek apakah email terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            sendResponse(404, "Email tidak ditemukan!");
        }

        // Generate kode OTP (6 digit)
        $otp = random_int(100000, 999999);
        $expiresAt = date("Y-m-d H:i:s", strtotime("+10 minutes")); // OTP berlaku 10 menit

        // Simpan OTP ke database
        $stmt = $pdo->prepare("
            INSERT INTO password_resets (email, otp, expires_at) 
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = ?, expires_at = ?
        ");
        $stmt->execute([$email, $otp, $expiresAt, $otp, $expiresAt]);

        // Kirim email dengan kode OTP
        $subject = "Reset Password - NugasYuk";
        $body = "
            <h3>Kode OTP Reset Password</h3>
            <p>Gunakan kode OTP berikut untuk mereset password Anda:</p>
            <h2>$otp</h2>
            <p>Kode ini berlaku selama 10 menit.</p>
        ";

        if (sendEmail($email, $subject, $body)) {
            sendResponse(200, "Kode OTP telah dikirim ke email Anda.");
        } else {
            sendResponse(500, "Gagal mengirim email OTP. Silakan coba lagi.");
        }
    } catch (PDOException $e) {
        error_log("Error send OTP: " . $e->getMessage());
        sendResponse(500, "Gagal mengirim OTP. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
