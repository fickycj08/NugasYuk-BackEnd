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
    sendResponse(401, "Anda harus login untuk melihat daftar tugas!");
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $classId = $_GET['class_id'] ?? null;

    if (!$classId) {
        sendResponse(400, "Class ID harus disertakan!");
    }

    try {
        // Cek apakah user adalah anggota kelas ini
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $isMember = $stmt->fetchColumn();

        if (!$isMember) {
            sendResponse(403, "Anda tidak memiliki akses ke kelas ini!");
        }

        // Ambil daftar tugas berdasarkan kelas
        $stmt = $pdo->prepare("
            SELECT id, title, description, deadline, type, submission_info, status, created_by, created_at
            FROM tasks
            WHERE class_id = ?
            ORDER BY deadline ASC
        ");
        $stmt->execute([$classId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse(200, "Daftar tugas berhasil diambil!", $tasks);
    } catch (PDOException $e) {
        error_log("Error fetch tasks: " . $e->getMessage());
        sendResponse(500, "Gagal mengambil daftar tugas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
