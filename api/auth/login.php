<?php
require_once "../../config/database.php";
require_once "../../utils/response.php";
require_once "../../utils/security.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($data['email'] ?? '');
    $password = sanitizeInput($data['password'] ?? '');

    if (!$email || !$password) {
        sendResponse(400, "Email dan password harus diisi!");
    }


    try {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            // Simpan session & kirim token
            $_SESSION['user_id'] = $user['id'];
            sendResponse(200, "Login berhasil!", [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $email,
                "token" => session_id()
            ]);
        } else {
            sendResponse(401, "Email atau password salah!");
        }
    } catch (PDOException $e) {
        error_log("Gagal login: " . $e->getMessage()); // Log error di server
        sendResponse(500, "Terjadi kesalahan server. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diperbolehkan!");
}
?>