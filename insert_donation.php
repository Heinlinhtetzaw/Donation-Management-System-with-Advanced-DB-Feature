
<?php
require_once 'config.php';
require_once 'csrf.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: donate.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: donate.php");
    exit();
}

// Database connection
$conn = getDBConnection();
// Function to validate Myanmar Unicode phone number
function validateMyanmarPhoneNumber($phone) {
    // Myanmar Unicode numbers range: ၀ (U+1040) to ၉ (U+1049)
    return preg_match('/^([၀၁၂၃၄၅၆၇၈၉]{11}|[0-9]{11})$/u', $phone);
}
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $donor_name = trim($_POST["donor_name"] ?? '');
    $address = trim($_POST["address"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $amount = $_POST["amount"] ?? ''; // This may contain Myanmar numerals
    $foundation_id = (int) ($_POST["foundation_id"] ?? 0);
    $payment_method = $_POST["payment_method"] ?? '';
    $payment_status = "Pending"; // Default payment status

    // Convert Myanmar numerals to Western numerals
    $myanmarToWestern = [
        '၀' => '0',
        '၁' => '1',
        '၂' => '2',
        '၃' => '3',
        '၄' => '4',
        '၅' => '5',
        '၆' => '6',
        '၇' => '7',
        '၈' => '8',
        '၉' => '9'
    ];

    // Convert the amount to Western numerals
    $amount = strtr($amount, $myanmarToWestern);

    // Remove any non-numeric characters (e.g., "MMK") from the amount
    $amount = preg_replace('/[^0-9.]/', '', $amount);
    // Validate Myanmar Unicode phone number
    if (!validateMyanmarPhoneNumber($phone)) {
        die("ဖုန်းနံပါတ်သည် ဂဏန်း ၁၁ လုံးဖြစ်ရပါမည်။");
    }
    if ($donor_name === '' || $address === '') {
        die("Invalid donation details.");
    }

    $allowedMethods = ['Cash', 'Wavepay', 'Kpay'];
    if (!in_array($payment_method, $allowedMethods, true)) {
        die("Invalid payment method.");
    }

    if ($foundation_id <= 0) {
        die("Invalid foundation.");
    }

    if (!preg_match('/^\\d{1,7}(\\.\\d{1,2})?$/', $amount)) {
        die("Invalid amount.");
    }
    $amount = (float) $amount;
    $maxAmount = 1000000;
    if ($amount <= 0 || $amount > $maxAmount) {
        die("Invalid amount.");
    }

    // Insert data into the database
    $sql = "INSERT INTO donations (donor_name, address, phone, amount, foundation_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiss", $donor_name, $address, $phone, $amount, $foundation_id, $payment_method, $payment_status);

    if ($stmt->execute()) {
        header("location:donate.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
