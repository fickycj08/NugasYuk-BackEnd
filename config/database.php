<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start(); // Pastikan session aktif di semua file

$host = "localhost"; // Ubah sesuai server hosting
$dbname = "u775836159_nugasyuk_db"; // Nama database
$username = "u775836159_nugasyuk_app"; // Username database
$password = "2cI:FfOwm3n"; // Password database

// Koneksi menggunakan PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Koneksi database gagal: " . $e->getMessage()); // Log error di server
    die(json_encode(["status" => "error", "message" => "Terjadi kesalahan server. Silakan coba lagi."]));
}
?>