<?php
// ─────────────────────────────────────────
//  LuxeBags — Shared Configuration
// ─────────────────────────────────────────

define('SITE_NAME', 'LuxeBags');
define('SITE_EMAIL', 'hello@luxebags.co.ke');

// Files where data is stored (no database needed)
define('ORDERS_FILE',   __DIR__ . '/data/orders.json');
define('MESSAGES_FILE', __DIR__ . '/data/messages.json');

// Admin password (change this to something strong)
define('ADMIN_PASSWORD', 'luxebags2025');

// Create data folder if it doesn't exist
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// ── Helpers ──────────────────────────────

function read_json($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function write_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)));
}

function json_response($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>
