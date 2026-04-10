<?php
// ─────────────────────────────────────────
//  LuxeBags — Order Processor
//  Called via AJAX from checkout.html
// ─────────────────────────────────────────

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// ── Collect & validate fields ─────────────
$firstName   = sanitize($_POST['firstName']   ?? '');
$lastName    = sanitize($_POST['lastName']    ?? '');
$email       = sanitize($_POST['email']       ?? '');
$phone       = sanitize($_POST['phone']       ?? '');
$address     = sanitize($_POST['address']     ?? '');
$city        = sanitize($_POST['city']        ?? '');
$county      = sanitize($_POST['county']      ?? '');
$notes       = sanitize($_POST['notes']       ?? '');
$payment     = sanitize($_POST['payment']     ?? '');
$mpesaPhone  = sanitize($_POST['mpesaPhone']  ?? '');
$cartJSON    = $_POST['cart']                 ?? '[]';
$total       = sanitize($_POST['total']       ?? '0');

$errors = [];

if (empty($firstName))  $errors[] = 'First name is required.';
if (empty($lastName))   $errors[] = 'Last name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}
if (empty($phone))      $errors[] = 'Phone number is required.';
if (empty($address))    $errors[] = 'Delivery address is required.';
if (empty($city))       $errors[] = 'City is required.';
if (empty($payment))    $errors[] = 'Payment method is required.';

// Validate cart
$cart = json_decode($cartJSON, true);
if (empty($cart) || !is_array($cart)) {
    $errors[] = 'Your cart is empty.';
}

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

// ── Generate order number ─────────────────
$orderNumber = 'LB-' . strtoupper(substr(uniqid(), -6));

// ── Build order record ────────────────────
$orders = read_json(ORDERS_FILE);

$newOrder = [
    'id'          => uniqid('ord_'),
    'orderNumber' => $orderNumber,
    'customer'    => [
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'fullName'  => $firstName . ' ' . $lastName,
        'email'     => $email,
        'phone'     => $phone,
    ],
    'delivery'    => [
        'address' => $address,
        'city'    => $city,
        'county'  => $county,
        'notes'   => $notes,
    ],
    'payment'     => [
        'method'     => $payment,
        'mpesaPhone' => $mpesaPhone,
    ],
    'cart'        => $cart,
    'total'       => $total,
    'status'      => 'pending',
    'placedAt'    => date('Y-m-d H:i:s'),
];

$orders[] = $newOrder;
write_json(ORDERS_FILE, $orders);

// ── Respond ───────────────────────────────
json_response(true, 'Order placed successfully!', [
    'orderNumber' => $orderNumber,
    'id'          => $newOrder['id'],
]);
?>
