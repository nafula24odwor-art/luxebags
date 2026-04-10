<?php
// ─────────────────────────────────────────
//  LuxeBags — Contact Form Processor
//  Called via AJAX from index.html
// ─────────────────────────────────────────

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once 'config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// ── Collect & validate fields ─────────────
$firstName = sanitize($_POST['firstName'] ?? '');
$lastName  = sanitize($_POST['lastName']  ?? '');
$email     = sanitize($_POST['email']     ?? '');
$phone     = sanitize($_POST['phone']     ?? '');
$message   = sanitize($_POST['message']   ?? '');

$errors = [];

if (empty($firstName)) $errors[] = 'First name is required.';
if (empty($lastName))  $errors[] = 'Last name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if (empty($message)) $errors[] = 'Message cannot be empty.';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

// ── Save message ──────────────────────────
$messages = read_json(MESSAGES_FILE);

$newMessage = [
    'id'         => uniqid('msg_'),
    'firstName'  => $firstName,
    'lastName'   => $lastName,
    'fullName'   => $firstName . ' ' . $lastName,
    'email'      => $email,
    'phone'      => $phone,
    'message'    => $message,
    'status'     => 'unread',
    'receivedAt' => date('Y-m-d H:i:s'),
];

$messages[] = $newMessage;
write_json(MESSAGES_FILE, $messages);

// ── Respond ───────────────────────────────
json_response(true, 'Message received! We will get back to you shortly.', [
    'id' => $newMessage['id']
]);
?>
