<?php
require_once 'config.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/DonationAdminService.php';

require_post_request('donate.php');
require_valid_csrf('donate.php');

function is_valid_myanmar_phone($phone) {
    return preg_match('/^[0-9]{11}$/', $phone) === 1;
}

$donorName = request_string($_POST, 'donor_name');
$address = request_string($_POST, 'address');
$phone = normalize_myanmar_digits(request_string($_POST, 'phone'));
$amount = normalize_myanmar_digits(request_string($_POST, 'amount'));
$foundationId = positive_int($_POST['foundation_id'] ?? null);
$paymentMethod = request_string($_POST, 'payment_method');

if (!has_text_length($donorName, 1, 100) || !has_text_length($address, 1, 100) || !is_valid_myanmar_phone($phone)) {
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
$conn->begin_transaction();

try {
    // Keep the selected foundation from being deleted until this transaction commits.
    $foundationCheck = $conn->prepare('SELECT 1 FROM foundations WHERE fid = ? LOCK IN SHARE MODE');
    $foundationCheck->bind_param('i', $foundationId);
    $foundationCheck->execute();
    $exists = $foundationCheck->get_result()->num_rows === 1;
    $foundationCheck->close();
    if (!$exists) {
        throw new RuntimeException('The selected foundation is no longer available.');
    }

    $donorId = ensure_donor($conn, $donorName, $address, $phone);
    $status = 'Pending';
    $stmt = $conn->prepare('INSERT INTO donations (donor_id, donor_name, address, phone, amount, foundation_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    // The amount remains a validated string, preventing PHP float rounding before MySQL stores it.
    $stmt->bind_param('issssiss', $donorId, $donorName, $address, $phone, $amount, $foundationId, $paymentMethod, $status);
    if (!$stmt->execute()) {
        throw new RuntimeException('Donation insert failed: ' . $stmt->error);
    }
    $donationId = (int) $conn->insert_id;
    $stmt->close();

    $reference = donation_reference_for_id($donationId);
    $referenceUpdate = $conn->prepare('UPDATE donations SET reference_code = ? WHERE id = ?');
    $referenceUpdate->bind_param('si', $reference, $donationId);
    $referenceUpdate->execute();
    $referenceUpdate->close();

    $history = $conn->prepare("INSERT INTO donation_status_history (donation_id, previous_status, new_status, note, changed_by) VALUES (?, NULL, 'Pending', 'Donation submitted', 'system')");
    $history->bind_param('i', $donationId);
    $history->execute();
    $history->close();
    $conn->commit();
    set_flash('success', 'Thank you. Your donation reference is ' . $reference . ' and is pending verification.');
} catch (DomainException $exception) {
    $conn->rollback();
    set_flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Donation insert failed: ' . $exception->getMessage());
    set_flash('error', $exception->getMessage() === 'The selected foundation is no longer available.' ? $exception->getMessage() : 'Your donation could not be recorded. Please try again.');
}

$conn->close();
redirect_to('donate.php');
