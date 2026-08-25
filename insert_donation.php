<?php
require_once 'config.php';
require_once 'csrf.php';

require_post_request('donate.php');
require_valid_csrf('donate.php');

function validateMyanmarPhoneNumber($phone) {
    return preg_match('/^([၀၁၂၃၄၅၆၇၈၉]{11}|[0-9]{11})$/u', $phone);
}

$donorName = trim($_POST['donor_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$amount = strtr((string) ($_POST['amount'] ?? ''), [
    '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
    '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
]);
$foundationId = positive_int($_POST['foundation_id'] ?? null);
$paymentMethod = $_POST['payment_method'] ?? '';

if ($donorName === '' || $address === '' || !validateMyanmarPhoneNumber($phone)) {
    set_flash('error', 'Please enter your name, address, and a valid 11-digit phone number.');
    redirect_to('donate.php');
}

if ($foundationId === null || !in_array($paymentMethod, ['Cash', 'Wavepay', 'Kpay'], true)) {
    set_flash('error', 'Please select a valid foundation and payment method.');
    redirect_to('donate.php');
}

if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0 || (float) $amount > 1000000) {
    set_flash('error', 'Please enter a donation amount between 1 and 1,000,000 MMK.');
    redirect_to('donate.php');
}

$conn = getDBConnection();
$foundationCheck = $conn->prepare('SELECT 1 FROM foundations WHERE fid = ?');
$foundationCheck->bind_param('i', $foundationId);
$foundationCheck->execute();
if ($foundationCheck->get_result()->num_rows !== 1) {
    $foundationCheck->close();
    $conn->close();
    set_flash('error', 'The selected foundation is no longer available.');
    redirect_to('donate.php');
}
$foundationCheck->close();

$status = 'Pending';
$stmt = $conn->prepare('INSERT INTO donations (donor_name, address, phone, amount, foundation_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)');
// The amount remains a validated string, preventing PHP float rounding before MySQL stores it.
$stmt->bind_param('ssssiss', $donorName, $address, $phone, $amount, $foundationId, $paymentMethod, $status);

if ($stmt->execute()) {
    set_flash('success', 'Thank you. Your donation has been recorded as pending verification.');
} else {
    error_log('Donation insert failed: ' . $stmt->error);
    set_flash('error', 'Your donation could not be recorded. Please try again.');
}

$stmt->close();
$conn->close();
redirect_to('donate.php');
