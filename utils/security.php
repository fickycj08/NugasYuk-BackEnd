<?php
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 8; // Minimal 8 karakter
}
function validateToken($token) {
    session_id($token); // Set session ID dari token
    session_start();
    return isset($_SESSION['user_id']); // Cek apakah user_id ada di session
}
?>