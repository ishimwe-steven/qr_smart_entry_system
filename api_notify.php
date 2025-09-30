<?php
// api_notify.php
// Place this in your web project root (same parent as the 'security' folder).
// Example: C:\xampp\htdocs\smartentry\api_notify.php

// CONFIG: change this to a strong secret and keep it the same in the ESP32 sketch.
$SECRET_KEY = 'hT7k9Qz!29xLmP@f3yVg';

// Optional: adjust path to your dashboard (relative)
$DASHBOARD_PATH = '/security/dashboard.php'; // keep leading slash if in same web root

// Accept GET or POST
$method = $_SERVER['REQUEST_METHOD'];
$input_key = $method === 'POST' ? ($_POST['key'] ?? $_GET['key'] ?? null) : ($_GET['key'] ?? null);
$scan_qr   = $method === 'POST' ? ($_POST['scan_qr'] ?? $_GET['scan_qr'] ?? null) : ($_GET['scan_qr'] ?? null);
$no_redirect = isset($_REQUEST['no_redirect']) && ($_REQUEST['no_redirect'] == '1');

// Basic validation
if (!$input_key || $input_key !== $SECRET_KEY) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Forbidden: invalid key.";
    exit;
}

if (!$scan_qr) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Bad Request: missing scan_qr parameter.";
    exit;
}

// Normalize value for URL (encode)
$encoded = urlencode($scan_qr);
$redirect_url = $DASHBOARD_PATH . '?scan_qr=' . $encoded;

// If caller asked JSON (or no_redirect), return JSON
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if ($no_redirect || strpos($accept, 'application/json') !== false) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => true,
        'redirect' => $redirect_url,
        'scan_qr' => $scan_qr
    ]);
    exit;
}

// Otherwise do a top-level redirect (302) — good when the caller is a browser
header("Location: $redirect_url", true, 302);
exit;
