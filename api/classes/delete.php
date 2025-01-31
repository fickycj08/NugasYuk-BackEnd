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
    sendResponse(401, "Anda harus login untuk menghapus/keluar dari kelas!");
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents('php://input'), true);
    $classId = $data['class_id'] ?? null;

    if (!$classId) {
        sendResponse(400, "ID kelas harus disertakan!");
    }

    try {
        // Cek apakah kelas ada
        $stmt = $pdo->prepare("SELECT created_by FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            sendResponse(404, "Kelas tidak ditemukan!");
        }

        $isCreator = $class['created_by'] == $userId;

        if ($isCreator) {
            // Hapus kelas & semua data terkait
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM tasks WHERE class_id = ?")->execute([$classId]);
            $pdo->prepare("DELETE FROM class_members WHERE class_id = ?")->execute([$classId]);
            $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$classId]);
            $pdo->commit();

            sendResponse(200, "Kelas telah dihapus beserta semua tugas dan anggotanya.");
        } else {
            // Hanya keluar dari kelas
            $stmt = $pdo->prepare("DELETE FROM class_members WHERE user_id = ? AND class_id = ?");
            $stmt->execute([$userId, $classId]);

            sendResponse(200, "Berhasil keluar dari kelas!");
        }
    } catch (PDOException $e) {
        error_log("Error delete/leave class: " . $e->getMessage());
        sendResponse(500, "Gagal menghapus/keluar dari kelas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
