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
    sendResponse(401, "Anda harus login untuk mengubah profil!");
}

if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = sanitizeInput($data['name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $newPassword = $data['password'] ?? '';
    $profilePicture = $data['profile_picture'] ?? ''; // Base64 encoded image (opsional)

    try {
        // Validasi email
        if ($email && !validateEmail($email)) {
            sendResponse(400, "Format email tidak valid!");
        }

        // Validasi password
        if ($newPassword && !validatePassword($newPassword)) {
            sendResponse(400, "Password minimal 8 karakter dengan kombinasi huruf besar, kecil, dan angka!");
        }

        // Update data
        $updateFields = [];
        $params = [];

        if ($name) {
            $updateFields[] = "name = ?";
            $params[] = $name;
        }

        if ($email) {
            // Cek apakah email sudah digunakan
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                sendResponse(400, "Email sudah digunakan!");
            }
            $updateFields[] = "email = ?";
            $params[] = $email;
        }

        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateFields[] = "password = ?";
            $params[] = $hashedPassword;
        }

        if ($profilePicture) {
            // Simpan gambar ke server (contoh: folder uploads)
            $imagePath = saveBase64Image($profilePicture, $userId);
            $updateFields[] = "profile_picture = ?";
            $params[] = $imagePath;
        }

        if (empty($updateFields)) {
            sendResponse(400, "Tidak ada data yang diubah!");
        }

        // Eksekusi query
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $userId;
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        sendResponse(200, "Profil berhasil diperbarui!");
    } catch (PDOException $e) {
        error_log("Error update profile: " . $e->getMessage());
        sendResponse(500, "Gagal memperbarui profil.");
    }
} else {
    sendResponse(405, "Metode tidak diizinkan!");
}

// Fungsi untuk menyimpan gambar Base64
function saveBase64Image($base64, $userId) {
    $dir = __DIR__ . '/../../uploads/profiles/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $data = explode(',', $base64);
    $format = str_replace(['data:image/', ';base64'], '', $data[0]);
    $imageData = base64_decode($data[1]);
    $filename = "user_$userId." . $format;
    $filepath = $dir . $filename;

    file_put_contents($filepath, $imageData);
    return "https://nugasyuk.com/uploads/profiles/" . $filename;
}
?>