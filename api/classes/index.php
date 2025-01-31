<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/security.php';

// Validasi token
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (!validateToken($token)) {
    sendResponse(401, "Token tidak valid. Silakan login kembali.");
}

// Pastikan user sudah login
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "Anda harus login untuk melihat daftar kelas!");
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        // Ambil daftar kelas yang dibuat atau diikuti oleh user
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.description, c.category, c.code, c.created_by, cm.role
            FROM classes c
            JOIN class_members cm ON c.id = cm.class_id
            WHERE cm.user_id = ?
        ");
        $stmt->execute([$userId]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse(200, "Daftar kelas berhasil diambil.", $classes);
    } catch (PDOException $e) {
        error_log("Error fetch classes: " . $e->getMessage());
        sendResponse(500, "Gagal mengambil daftar kelas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>