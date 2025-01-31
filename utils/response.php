<?php
function sendResponse($status, $message, $data = null) {
    header("Content-Type: application/json");
    http_response_code($status);

    $response = [
        "status" => $status < 400 ? "success" : "error",
        "message" => $message,
        "data" => $data
    ];

    if ($status >= 500) { // Jika terjadi error server
        error_log("Server Error: " . json_encode($response));
    }

    echo json_encode($response);
    exit;
}
?>
