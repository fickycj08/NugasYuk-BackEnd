<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/security.php';

// Validasi token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token || !validateToken($token)) {
    sendResponse(401, "Token tidak valid. Silakan login kembali.");
}

// Pastikan user sudah login
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "Anda harus login untuk bergabung ke kelas!");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $classCode = sanitizeInput($data['code'] ?? '');

    if (empty($classCode)) {
        sendResponse(400, "Kode kelas harus diisi!");
    }

    try {
        // Cari kelas berdasarkan kode
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE code = ?");
        $stmt->execute([$classCode]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            sendResponse(404, "Kelas tidak ditemukan!");
        }

        $classId = $class['id'];

        // Cek apakah user sudah tergabung dalam kelas
        $stmt = $pdo->prepare("SELECT * FROM class_members WHERE user_id = ? AND class_id = ?");
        $stmt->execute([$userId, $classId]);
        $isMember = $stmt->fetch();

        if ($isMember) {
            sendResponse(400, "Anda sudah tergabung dalam kelas ini!");
        }

        // Tambahkan user ke `class_members` sebagai 'member'
        $stmt = $pdo->prepare("INSERT INTO class_members (user_id, class_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$userId, $classId]);

        sendResponse(201, "Berhasil bergabung ke kelas!", ["class_id" => $classId, "code" => $classCode]);
    } catch (PDOException $e) {
        error_log("Error join class: " . $e->getMessage());
        sendResponse(500, "Gagal bergabung ke kelas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
