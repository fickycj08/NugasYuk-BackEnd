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
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "Anda harus login untuk melihat anggota kelas!");
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $classId = $_GET['class_id'] ?? null;

    if (!$classId) {
        sendResponse(400, "Class ID harus disertakan!");
    }

    try {
        // Cek apakah user adalah anggota kelas
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $isMember = $stmt->fetchColumn();

        if (!$isMember) {
            sendResponse(403, "Anda tidak memiliki akses ke kelas ini!");
        }

        // Ambil daftar anggota kelas
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, cm.role, cm.joined_at
            FROM class_members cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.class_id = ?
            ORDER BY cm.role DESC, u.name ASC
        ");
        $stmt->execute([$classId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse(200, "Daftar anggota kelas berhasil diambil!", $members);
    } catch (PDOException $e) {
        error_log("Error fetch class members: " . $e->getMessage());
        sendResponse(500, "Gagal mengambil daftar anggota kelas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
