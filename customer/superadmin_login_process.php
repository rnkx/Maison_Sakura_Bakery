<?php
session_start();
include("db.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // 📦 Make sure PHPMailer is installed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = "superadmin";

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT users_id, fullname, email, password, role 
                FROM users 
                WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($password === $row['password']) {
                // ✅ Step 1: Generate OTP
                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

                // ✅ Step 2: Save OTP to database
                $update = $conn->prepare("UPDATE users SET otp_code=?, otp_expiry=? WHERE email=?");
                $update->bind_param("sss", $otp, $expiry, $email);
                $update->execute();

                // ✅ Step 3: Send OTP email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'rngkxxx@gmail.com'; // 🔹 your Gmail
                    $mail->Password = 'dfup vcix ezxg vgaf';   // 🔹 your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('rngkxxx@gmail.com', 'Maison Sakura Security');
                    $mail->addAddress($email);

                    $mail->isHTML(false);
                    $mail->Subject = 'Your Maison Sakura Login OTP';
                    $mail->Body = "Hi {$row['fullname']},\n\nYour OTP is: $otp\n\nIt will expire in 5 minutes.";

                    $mail->send();

                    // ✅ Step 4: Go to OTP page
                    $_SESSION['pending_email'] = $email;
                    header("Location: verify_otp_superadmin.php");
                    exit();

                } catch (Exception $e) {
                    $_SESSION['error'] = "Failed to send OTP email. {$mail->ErrorInfo}";
                    header("Location: login_superadmin.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid password!";
                header("Location: login_superadmin.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "No superadmin account found!";
            header("Location: login_superadmin.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: login_superadmin.php");
        exit();
    }
}
?>
