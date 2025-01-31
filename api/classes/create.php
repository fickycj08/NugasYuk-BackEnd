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
    sendResponse(401, "Anda harus login untuk membuat kelas!");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = sanitizeInput($data['name'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $category = sanitizeInput($data['category'] ?? '');

    if (empty($name) || empty($category)) {
        sendResponse(400, "Nama dan kategori kelas wajib diisi!");
    }

    // Validasi panjang nama kelas (3 - 50 karakter)
    if (strlen($name) < 3 || strlen($name) > 50) {
        sendResponse(400, "Nama kelas harus antara 3 - 50 karakter.");
    }

    try {
        // Generate kode kelas yang unik
        $code = generateUniqueCode($pdo);

        // Insert ke tabel `classes`
        $stmt = $pdo->prepare("INSERT INTO classes (name, description, category, code, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $category, $code, $userId]);

        // Ambil ID kelas yang baru dibuat
        $classId = $pdo->lastInsertId();

        // Masukkan user pembuat kelas ke dalam `class_members` sebagai 'creator'
        $stmt = $pdo->prepare("INSERT INTO class_members (user_id, class_id, role) VALUES (?, ?, 'creator')");
        $stmt->execute([$userId, $classId]);

        sendResponse(201, "Kelas berhasil dibuat!", ["class_id" => $classId, "code" => $code]);
    } catch (PDOException $e) {
        error_log("Error create class: " . $e->getMessage());
        sendResponse(500, "Gagal membuat kelas: " . $e->getMessage());
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}

// Fungsi untuk generate kode unik
function generateUniqueCode($pdo, $length = 6) {
    do {
        $code = generateRandomCode($length);
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
    } while ($exists);

    return $code;
}

// Fungsi untuk generate random kode (aman)
function generateRandomCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $maxIndex = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $maxIndex)];
    }
    return $code;
}
?>
