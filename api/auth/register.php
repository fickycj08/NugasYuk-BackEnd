<?php
require_once "../../config/database.php";
require_once "../../utils/response.php";
require_once "../../utils/security.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = sanitizeInput($data['name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$name || !$email || !$password) {
        sendResponse(400, "Semua field harus diisi!");
    }

    if (!validateEmail($email)) {
        sendResponse(400, "Format email tidak valid!");
    }

    if (!validatePassword($password)) {
        sendResponse(400, "Password minimal 8 karakter, harus ada huruf besar & angka!");
    }

    // Cek apakah email sudah ada
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        sendResponse(409, "Email sudah digunakan!");
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        sendResponse(201, "Registrasi berhasil!");
    } catch (PDOException $e) {
        error_log("Gagal registrasi: " . $e->getMessage());
        sendResponse(500, "Terjadi kesalahan server.");
    }
} else {
    sendResponse(405, "Metode tidak diperbolehkan!");
}
?>
