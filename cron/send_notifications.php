<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/email.php';

try {
    // Ambil tugas yang deadline dalam 1 hari ke depan
    $stmt = $pdo->prepare("
        SELECT t.id as task_id, t.title, t.deadline, cm.user_id, u.email 
        FROM tasks t
        JOIN class_members cm ON t.class_id = cm.class_id
        JOIN users u ON cm.user_id = u.id
        WHERE t.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$tasks) {
        file_put_contents(__DIR__ . '/cron_debug.txt', date('Y-m-d H:i:s') . " - Tidak ada tugas yang mendekati deadline.\n", FILE_APPEND);
        exit("❌ Tidak ada tugas yang mendekati deadline.\n");
    }

    foreach ($tasks as $task) {
        if (!isset($task['user_id']) || !isset($task['task_id'])) {
            file_put_contents(__DIR__ . '/cron_error.txt', date('Y-m-d H:i:s') . " - ERROR: user_id atau task_id kosong!\n", FILE_APPEND);
            continue;
        }

        $message = "📌 Pengingat: Tugas '{$task['title']}' akan jatuh tempo pada " . $task['deadline'] . ".";

        // Simpan notifikasi ke database
        file_put_contents(__DIR__ . '/cron_debug.txt', date('Y-m-d H:i:s') . " - Menambahkan notifikasi untuk User ID: {$task['user_id']}, Task ID: {$task['task_id']}\n", FILE_APPEND);

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, task_id, message, triggered_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$task['user_id'], $task['task_id'], $message]);

        // Kirim email notifikasi
        $subject = "📌 Pengingat Deadline: {$task['title']}";
        $body = "<p>Halo,</p><p>Tugas <b>{$task['title']}</b> akan jatuh tempo pada <b>{$task['deadline']}</b>.</p><p>Segera selesaikan sebelum terlambat!</p>";
        sendEmail($task['email'], $subject, $body);
    }

    echo "✅ Notifikasi berhasil dikirim!";
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/cron_error.txt', date('Y-m-d H:i:s') . " - Gagal insert ke DB: " . $e->getMessage() . "\n", FILE_APPEND);
    exit("❌ Gagal mengirim notifikasi.");
}
?>
