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

session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "Anda harus login untuk melihat notifikasi!");
}

try {
    // Ambil notifikasi pengguna
    $stmt = $pdo->prepare("SELECT id, task_id, message, is_read, triggered_at FROM notifications WHERE user_id = ? ORDER BY triggered_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(200, "Notifikasi berhasil diambil!", $notifications);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    sendResponse(500, "Gagal mengambil notifikasi.");
}
?>
