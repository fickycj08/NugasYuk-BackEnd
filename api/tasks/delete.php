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
    sendResponse(401, "Anda harus login untuk menghapus tugas!");
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = $data['task_id'] ?? null;

    if (!$taskId) {
        sendResponse(400, "Task ID harus disertakan!");
    }

    try {
        // Cek apakah tugas ada dan ambil class_id
        $stmt = $pdo->prepare("SELECT class_id, created_by, deadline FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            sendResponse(404, "Tugas tidak ditemukan!");
        }

        $classId = $task['class_id'];
        $taskDeadline = $task['deadline'];

        // Cek apakah user adalah creator/admin kelas
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $role = $stmt->fetchColumn();

        if (!$role || ($role !== 'creator' && $role !== 'admin')) {
            sendResponse(403, "Anda tidak memiliki izin untuk menghapus tugas ini!");
        }

        // Hapus tugas dari database
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);

        sendResponse(200, "Tugas berhasil dihapus!", [
            "task_id" => $taskId,
            "deleted_at" => date("Y-m-d H:i:s"),
            "deadline" => $taskDeadline
        ]);
    } catch (PDOException $e) {
        error_log("Error delete task: " . $e->getMessage());
        sendResponse(500, "Gagal menghapus tugas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
