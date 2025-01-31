<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/security.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($data['email'] ?? '');
    $otp = sanitizeInput($data['otp'] ?? '');
    $newPassword = $data['new_password'] ?? '';

    if (!$email || !$otp || !$newPassword) {
        sendResponse(400, "Email, OTP, dan password baru harus diisi!");
    }

    if (!validatePassword($newPassword)) {
        sendResponse(400, "Password minimal 8 karakter!");
    }

    try {
        // Cek OTP dan apakah masih berlaku
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW()");
        $stmt->execute([$email, $otp]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            sendResponse(400, "OTP tidak valid atau telah kadaluarsa!");
        }

        // Hash password baru
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update password di tabel users
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        // Hapus OTP dari database setelah digunakan
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        sendResponse(200, "Password berhasil diperbarui!");
    } catch (PDOException $e) {
        error_log("Error reset password: " . $e->getMessage());
        sendResponse(500, "Gagal mereset password. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
