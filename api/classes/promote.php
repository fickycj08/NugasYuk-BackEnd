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
    sendResponse(401, "Anda harus login untuk mengubah peran anggota!");
}

if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents('php://input'), true);
    $classId = $data['class_id'] ?? null;
    $memberId = $data['member_id'] ?? null;
    $newRole = $data['new_role'] ?? null; // Bisa "admin" atau "member"

    if (!$classId || !$memberId || !$newRole) {
        sendResponse(400, "Class ID, Member ID, dan Role baru harus disertakan!");
    }

    if (!in_array($newRole, ['admin', 'member'])) {
        sendResponse(400, "Role baru harus 'admin' atau 'member'!");
    }

    try {
        // Cek apakah user adalah creator kelas
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$userId, $classId]);
        $userRole = $stmt->fetchColumn();

        if ($userRole !== 'creator') {
            sendResponse(403, "Hanya creator kelas yang bisa mengubah peran anggota!");
        }

        // Cek apakah member ada dalam kelas dan bukan creator
        $stmt = $pdo->prepare("
            SELECT role FROM class_members WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$memberId, $classId]);
        $memberRole = $stmt->fetchColumn();

        if (!$memberRole) {
            sendResponse(404, "Member tidak ditemukan dalam kelas!");
        }

        if ($memberRole === 'creator') {
            sendResponse(400, "Anda tidak bisa mengubah role creator!");
        }

        if ($memberRole === $newRole) {
            sendResponse(400, "Member sudah memiliki role tersebut!");
        }

        // Update role member
        $stmt = $pdo->prepare("
            UPDATE class_members SET role = ? WHERE user_id = ? AND class_id = ?
        ");
        $stmt->execute([$newRole, $memberId, $classId]);

        sendResponse(200, "Peran anggota berhasil diperbarui!", [
            "member_id" => $memberId,
            "class_id" => $classId,
            "new_role" => $newRole
        ]);
    } catch (PDOException $e) {
        error_log("Error update member role: " . $e->getMessage());
        sendResponse(500, "Gagal mengubah peran anggota. Silakan coba lagi.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}
?>
