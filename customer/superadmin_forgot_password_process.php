<?php
session_start();
include("db.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: superadmin_forgot_password.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: superadmin_forgot_password.php");
        exit();
    }

    // ✅ Check if superadmin exists
    $stmt = $conn->prepare("SELECT users_id, fullname FROM users WHERE email = ? AND role = 'superadmin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No superadmin found with that email.";
        header("Location: superadmin_forgot_password.php");
        exit();
    }

    $user = $result->fetch_assoc();

    // ✅ Generate OTP
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // ✅ Store OTP temporarily in DB
    $update = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
    $update->bind_param("sss", $otp, $expiry, $email);
    $update->execute();

    // ✅ Store new password temporarily in session (not in DB yet)
    $_SESSION['reset_email'] = $email;
    $_SESSION['new_password'] = $password;

    // ✅ Send OTP via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rngkxxx@gmail.com';
        $mail->Password = 'dfup vcix ezxg vgaf'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rngkxxx@gmail.com', 'Maison Sakura Security');
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = 'Password Reset Verification (Maison Sakura)';
        $mail->Body = "Hi {$user['fullname']},\n\nYour OTP to reset the password is: $otp\nThis code expires in 5 minutes.";

        $mail->send();

        $_SESSION['success'] = "An OTP has been sent to your email.";
        header("Location: superadmin_verify_reset_otp.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to send OTP email. {$mail->ErrorInfo}";
        header("Location: superadmin_forgot_password.php");
        exit();
    }
}
?>

