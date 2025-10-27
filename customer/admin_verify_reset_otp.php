<?php
session_start();
include("db.php");

$error = "";
$success = "";

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['new_password'])) {
    header("Location: admin_forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'] ?? '';

    $sql = "SELECT otp_code, otp_expiry FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($otp === $row['otp_code'] && strtotime($row['otp_expiry']) > time()) {
            // ✅ OTP is valid
            $new_password = $_SESSION['new_password'];

            // ✅ Update password
            $update = $conn->prepare("UPDATE users SET password = ?, otp_code=NULL, otp_expiry=NULL WHERE email = ?");
            $update->bind_param("ss", $new_password, $email);
            $update->execute();

            unset($_SESSION['reset_email'], $_SESSION['new_password']);

            $_SESSION['success'] = "Password reset successful. You can now login.";
            header("Location: login_admin.php");
            exit();
        } else {
            $error = "Invalid or expired OTP!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Reset OTP - Super Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-lg p-4 rounded-3">
        <h3 class="text-center mb-4">Verify OTP</h3>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP</label>
            <input type="text" name="otp" id="otp" class="form-control text-center" maxlength="6" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Verify & Reset Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
