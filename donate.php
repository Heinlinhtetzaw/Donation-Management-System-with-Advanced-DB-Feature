<?php
require_once 'config.php';
require_once 'csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Now</title>
    <link rel="stylesheet" href="css/donate.css"> <!-- Link to your existing CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Include SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- Include SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo"><image src="./image/logooo2.jpg">
            <span>Charity Donation Management</span>
        </div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i>Home</a></li>
            <li><a href="about.php"><i class="fas fa-building"></i>About</a></li>
            <li><a href="news.php"><i class="fas fa-newspaper"></i>News</a></li>
            <li class="active"><a href="donate.php"><i class="fas fa-donate"></i>Donate</a></li>
            <li><a href="signup.php" class="btn admin"><i class="fas fa-user-plus"></i>Signup</a></li>
        </ul>
    </nav>

<!-- Hero Section -->
<div class="hero">
    <!-- Donations Form -->
    <div class="form-container">
        <h2>Donate Now</h2>
        <?php if ($message = get_flash('success')): ?><p class="message-success"><?php echo e($message); ?></p><?php endif; ?>
        <?php if ($message = get_flash('error')): ?><p class="message-error"><?php echo e($message); ?></p><?php endif; ?>
        <form action="insert_donation.php" method="POST" id="donate-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <!-- Donor Name -->
            <label for="donor_name">အလှူရှင်အမည်:</label>
            <input type="text" name="donor_name" id="donor_name" required>

            <!-- Address -->
            <label for="address">နေရိပ်လိပ်စာ:</label>
            <input type="text" name="address" id="address" required>

            <!-- Phone -->
            <label for="phone">ဖုန်းနံပါတ်:</label>
            <input type="tel" name="phone" id="phone" required maxlength="11" oninput="validatePhoneNumber()" inputmode="numeric">
            <span id="phone-error" style="color: red; display: none;">ဖုန်းနံပါတ်သည် ဂဏန်း ၁၁ လုံးဖြစ်ရပါမည်။</span>

            <!-- Amount -->
            <label for="amount">အလှူငွေပမာဏ:</label>
            <input type="number" name="amount" id="amount" step="0.01" min="1" max="1000000" required>

            <!-- Foundation Name (Dropdown) -->
            <label for="foundation_id">လှူဒါန်းမည့်ကျောင်း:</label>
            <select name="foundation_id" id="foundation_id" required>
                <?php
                // Fetch foundation names from the database
                $conn = getDBConnection();

                $sql = "SELECT fid, fname FROM foundations";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row["fid"] . '">' . htmlspecialchars($row["fname"]) . '</option>';
                    }
                } else {
                    echo '<option value="">No foundations found</option>';
                }

                $conn->close();
                ?>
            </select>

            <!-- Payment Method -->
            <label for="payment_method">ငွေပေးချေမှု:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="Cash">Cash</option>
                <option value="Wavepay">Wavepay</option>
                <option value="Kpay">Kpay</option>
            </select>

            <!-- Submit Button -->
             <input type="submit" value="Donate Now" id="donateButton">
        </form>
    </div>
</div>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>About Us</h3>
                <p>We are a donation hub dedicated to helping communities in need. Join us in making a difference!</p>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="news.php">News</a></li>
                    <li><a href="donate.php">Donate</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p>Email: info@charitydonation.com</p>
                <p>Phone: +95 9679 181 879</p>
            </div>
        </div>
        <div class="footer-section social">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Charity Donation Management. All rights reserved.</p>
        </div>
    </footer>
    <script>
  function validatePhoneNumber() {
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phone-error');
    const phoneValue = phoneInput.value;

    // Myanmar Unicode numbers range: ၀ (U+1040) to ၉ (U+1049)
    // Allow both Myanmar Unicode numbers (၀-၉) and Arabic numerals (0-9)
    const myanmarNumberRegex = /^[၀၁၂၃၄၅၆၇၈၉]{11}$|^[0-9]{11}$/;

    // Check if the phone number is exactly 11 digits (either Myanmar or Arabic)
    if (!myanmarNumberRegex.test(phoneValue)) {
      phoneError.style.display = 'inline'; // Show error message
      return false; // Prevent form submission
    } else {
      phoneError.style.display = 'none'; // Hide error message
      return true; // Allow form submission
    }
  }

  // Get the form and donate button
  const donateForm = document.getElementById('donate-form');
  const donateButton = document.getElementById('donateButton');

  // Add a submit event listener to the form
  donateForm.addEventListener('submit', function(event) {
    // Prevent the form from submitting immediately
    event.preventDefault();

    // Validate the phone number before showing SweetAlert
    if (!validatePhoneNumber()) {
      return; // Stop if phone number is invalid
    }

    // Show SweetAlert
    Swal.fire({
      title: 'လုပ်ဆောင်မူအောင်မြင်ပါသည်!',
      text: 'ပါဝင်လှူဒါန်းသည့်အတွက်အထူးကျေးဇူးတင်ရှိပါသည်!',
      icon: 'success',
      confirmButtonText: 'Close',
    }).then((result) => {
      if (result.isConfirmed) {
        // Submit the form after the user confirms the alert
        donateForm.submit();
      }
    });
  });
</script>
</body>
</html>
