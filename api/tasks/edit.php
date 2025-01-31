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
    sendResponse(401, "Anda harus login untuk mengedit tugas!");
}

if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = $data['task_id'] ?? null;
    $title = sanitizeInput($data['title'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $deadline = sanitizeInput($data['deadline'] ?? '');
    $type = sanitizeInput($data['type'] ?? 'Individu');
    $submissionInfo = sanitizeInput($data['submission_info'] ?? '');

    if (!$taskId || empty($title) || empty($deadline)) {
        sendResponse(400, "Task ID, judul, dan deadline wajib diisi!");
    }

    // Validasi format deadline (YYYY-MM-DD HH:MM:SS)
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $deadline)) {
        sendResponse(400, "Format deadline tidak valid. Gunakan format: YYYY-MM-DD HH:MM:SS");
    }

    try {
        // Cek apakah tugas ada dan ambil class_id
        $stmt = $pdo->prepare("SELECT class_id, created_by FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            sendResponse(404, "Tugas tidak ditemukan!");
        }

        $classId = $task['class_id'];

        // Cek apakah user adalah creator/admin kelas
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $role = $stmt->fetchColumn();

        if (!$role || ($role !== 'creator' && $role !== 'admin')) {
            sendResponse(403, "Anda tidak memiliki izin untuk mengedit tugas ini!");
        }

        // Update tugas
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, deadline = ?, type = ?, submission_info = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $deadline, $type, $submissionInfo, $taskId]);

        sendResponse(200, "Tugas berhasil diperbarui!", [
            "task_id" => $taskId,
            "title" => $title,
            "deadline" => $deadline,
            "type" => $type,
            "submission_info" => $submissionInfo
        ]);
    } catch (PDOException $e) {
        error_log("Error edit task: " . $e->getMessage());
        sendResponse(500, "Gagal mengedit tugas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
