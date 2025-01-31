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
    sendResponse(401, "Anda harus login untuk membuat tugas!");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $classId = sanitizeInput($data['class_id'] ?? '');
    $title = sanitizeInput($data['title'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $deadline = sanitizeInput($data['deadline'] ?? '');
    $type = sanitizeInput($data['type'] ?? 'Individu');
    $submissionInfo = sanitizeInput($data['submission_info'] ?? '');

    // Validasi input
    if (empty($classId) || empty($title) || empty($deadline)) {
        sendResponse(400, "Class ID, judul, dan deadline wajib diisi!");
    }

    // Validasi format deadline (YYYY-MM-DD HH:MM:SS)
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $deadline)) {
        sendResponse(400, "Format deadline tidak valid. Gunakan format: YYYY-MM-DD HH:MM:SS");
    }

    try {
        // Cek apakah user adalah creator/admin kelas
        $stmt = $pdo->prepare("
            SELECT role FROM class_members 
            WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $role = $stmt->fetchColumn();

        if (!$role || ($role !== 'creator' && $role !== 'admin')) {
            sendResponse(403, "Anda tidak memiliki izin untuk membuat tugas di kelas ini!");
        }

        // Insert tugas baru
        $stmt = $pdo->prepare("
            INSERT INTO tasks (class_id, title, description, deadline, type, submission_info, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, 'Belum Dikerjakan', ?)
        ");
        $stmt->execute([$classId, $title, $description, $deadline, $type, $submissionInfo, $userId]);

        sendResponse(201, "Tugas berhasil dibuat!", [
            "task_id" => $pdo->lastInsertId(),
            "class_id" => $classId,
            "title" => $title,
            "deadline" => $deadline,
            "type" => $type,
            "submission_info" => $submissionInfo
        ]);
    } catch (PDOException $e) {
        error_log("Error create task: " . $e->getMessage());
        sendResponse(500, "Gagal membuat tugas. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
