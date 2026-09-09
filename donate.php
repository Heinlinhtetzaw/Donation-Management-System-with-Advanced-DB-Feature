<?php
require_once 'config.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$result = $conn->query('SELECT fid, fname FROM foundations ORDER BY fname');
$foundations = [];
while ($foundation = $result->fetch_assoc()) {
    $foundations[] = $foundation;
}
$conn->close();

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Now</title>
    <link rel="stylesheet" href="css/donate.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <?php render_public_navigation('donate'); ?>

    <main class="hero">
        <div class="form-container">
            <h2>Donate Now</h2>
            <?php if ($message = get_flash('success')): ?>
                <p class="message-success"><?= e($message) ?></p>
            <?php endif; ?>
            <?php if ($message = get_flash('error')): ?>
                <p class="message-error"><?= e($message) ?></p>
            <?php endif; ?>

            <form action="insert_donation.php" method="POST" id="donate-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <label for="donor_name">အလှူရှင်အမည်:</label>
                <input type="text" name="donor_name" id="donor_name" maxlength="100" autocomplete="name" required>

                <label for="address">နေရပ်လိပ်စာ:</label>
                <input type="text" name="address" id="address" maxlength="100" autocomplete="street-address" required>

                <label for="phone">ဖုန်းနံပါတ်:</label>
                <input type="tel" name="phone" id="phone" maxlength="11" inputmode="numeric" autocomplete="tel"
                    pattern="(?:[0-9]{11}|[၀-၉]{11})" aria-describedby="phone-error" required>
                <span id="phone-error" class="message-error" hidden>ဖုန်းနံပါတ်သည် ဂဏန်း ၁၁ လုံးဖြစ်ရပါမည်။</span>
                <span id="phone-error" class="message-error" hidden>ဖုန်းနံပါတ်သည် အင်္ဂလိပ် သို့မဟုတ် မြန်မာ ဂဏန်း ၁၁ လုံးဖြစ်ရပါမည်။</span>

                <label for="amount">အလှူငွေပမာဏ:</label>
                <input type="number" name="amount" id="amount" step="0.01" min="1" max="1000000" required>

                <label for="foundation_id">လှူဒါန်းမည့်ကျောင်း:</label>
                <select name="foundation_id" id="foundation_id" required<?= $foundations === [] ? ' disabled' : '' ?>>
                    <?php if ($foundations === []): ?>
                        <option value="">No foundations available</option>
                    <?php else: ?>
                        <option value="">Choose a foundation</option>
                        <?php foreach ($foundations as $foundation): ?>
                            <option value="<?= (int) $foundation['fid'] ?>"><?= e($foundation['fname']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <label for="payment_method">ငွေပေးချေမှု:</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="Wavepay">Wavepay</option>
                    <option value="Kpay">Kpay</option>
                </select>

                <input type="submit" value="Donate Now"<?= $foundations === [] ? ' disabled' : '' ?>>
            </form>
        </div>
    </main>

    <?php render_public_footer(); ?>
    <script src="js/donate.js"></script>
</body>
</html>
