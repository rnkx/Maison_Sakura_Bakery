<?php
session_start();
include("db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // ✅ PHPMailer autoload

$error = "";
$success = "";

// ✅ Redirect if no pending email session
if (!isset($_SESSION['pending_email'])) {
    header("Location: login_superadmin.php");
    exit();
}

$email = $_SESSION['pending_email'];

// ✅ Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify'])) {
    $otp = trim($_POST['otp'] ?? '');

    $sql = "SELECT users_id, otp_code, otp_expiry, fullname, role 
            FROM users 
            WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($otp === $row['otp_code'] && strtotime($row['otp_expiry']) > time()) {
            // ✅ OTP correct and still valid
            $_SESSION['users_id'] = $row['users_id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];

            // Clear OTP
            $clear = $conn->prepare("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE email=?");
            $clear->bind_param("s", $email);
            $clear->execute();

            unset($_SESSION['pending_email']);
            header("Location: superadmin_index.php");
            exit();
        } else {
            $error = "❌ Invalid or expired OTP. Please request a new one.";
        }
    } else {
        $error = "⚠️ Account not found.";
    }
}

// ✅ Handle resend OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend'])) {
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $update = $conn->prepare("UPDATE users SET otp_code=?, otp_expiry=? WHERE email=?");
    $update->bind_param("sss", $otp, $expiry, $email);
    $update->execute();

    // ✅ Send new OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rngkxxx@gmail.com'; // ✅ your Gmail
        $mail->Password = 'dfup vcix ezxg vgaf'; // ✅ Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rngkxxx@gmail.com', 'Maison Sakura Security');
        $mail->addAddress($email);

        $mail->isHTML(false);
        $mail->Subject = 'New OTP for Super Admin Login';
        $mail->Body = "Dear Super Admin,\n\nYour new OTP is: $otp\n\nThis code will expire in 5 minutes.\n\nIf you didn’t request this, please ignore this email.\n\n- Maison Sakura Security";

        $mail->send();
        $success = "✅ A new OTP has been sent to your email address!";
    } catch (Exception $e) {
        $error = "⚠️ Failed to resend OTP. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP - Super Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-lg p-4 rounded-3">
        <h3 class="text-center mb-4">Email Verification (OTP)</h3>

        <!-- ✅ Error message -->
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ✅ Success message -->
        <?php if (!empty($success)): ?>
          <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP Code</label>
            <input type="text" name="otp" id="otp" class="form-control text-center" maxlength="6" required>
          </div>

          <div class="d-flex justify-content-between">
            <button type="submit" name="verify" class="btn btn-primary w-50 me-2">Verify OTP</button>
            <button type="submit" name="resend" class="btn btn-outline-secondary w-50" id="resendBtn">Resend OTP</button>
          </div>
            
        </form>
 <script>
  // 🧠 If user clicks "Resend", disable required validation for OTP input
  document.getElementById("resendBtn").addEventListener("click", function() {
    document.getElementById("otp").removeAttribute("required");
  });
</script>
        <p class="text-center mt-3 text-muted">This OTP will expire in 5 minutes.</p>
        <div class="text-center mt-2">
          <a href="login_superadmin.php" class="text-decoration-none">&larr; Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
