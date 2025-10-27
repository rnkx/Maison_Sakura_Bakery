<?php
session_start();
include("db.php");

// Redirect if not logged in or not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Load PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Admin email account
$admin_email = "rngkxxx@gmail.com";
$admin_name  = "Rachel Ng";

// Get customer info from URL
$customer_email = $_GET['email'] ?? '';
$customer_name  = $_GET['name'] ?? '';

$error_message = '';
$success_message = '';
$reply_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_content = trim($_POST['reply'] ?? '');

    if (empty($customer_email)) {
        $error_message = "Customer email missing!";
    } elseif (empty($reply_content)) {
        $error_message = "Reply message cannot be empty.";
    } else {
        $mail = new PHPMailer(true);
        try {
            // Local SMTP configuration
             $mail -> isSMTP();
       $mail -> Host = "smtp.gmail.com";
       $mail -> SMTPAuth = true;
       $mail -> Username = "rachel.ng.ker.xin@gmail.com"; //my admin gmail
       $mail -> Password = "qydc xxfw loem sobo"; //Admin App Password
       $mail -> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail -> Port = 587;

            // Sender (admin)
            $mail->setFrom($admin_email, $admin_name);
            $mail->addAddress($customer_email, $customer_name);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Reply from Maison Sakura Bakery";
            $mail->Body    = "
                <p>Dear " . htmlspecialchars($customer_name) . ",</p>
                <p>" . nl2br(htmlspecialchars($reply_content)) . "</p>
                <br>
                <p>Best regards,<br>Maison Sakura Bakery Team</p>
            ";

            $mail->send();
            $success_message = "Reply sent successfully to $customer_email ✅";
            $reply_content = ''; // clear form
        } catch (Exception $e) {
            $error_message = "Reply could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reply to Customer</title>
    <style>
    body {
        font-family: "Poppins", sans-serif;
        background-color: #fff8f5;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 650px;
        background: #ffffff;
        margin: 60px auto;
        padding: 30px 40px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 {
        color: #c56b6b;
        text-align: center;
        font-size: 24px;
    }
    p {
        color: #444;
        text-align: center;
    }
    label {
        display: block;
        margin-top: 15px;
        font-weight: bold;
        color: #333;
    }
    textarea {
        width: 100%;
        height: 150px;
        padding: 12px;
        margin-top: 8px;
        border-radius: 10px;
        border: 1px solid #ddd;
        font-size: 15px;
        resize: none;
        font-family: "Poppins", sans-serif;
        background-color: #fffaf9;
    }
    button {
        display: block;
        width: 100%;
        padding: 12px;
        margin-top: 20px;
        background-color: #e8a0a0;
        color: white;
        font-size: 16px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: 0.3s;
    }
    button:hover {
        background-color: #d98585;
    }
    .msg {
        text-align: center;
        padding: 12px;
        border-radius: 8px;
        margin-top: 15px;
    }
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    a.back-link {
        display: inline-block;
        margin-top: 20px;
        text-decoration: none;
        color: #c56b6b;
        font-weight: bold;
        text-align: center;
        width: 100%;
    }
    a.back-link:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
    <div class="container">
    <h2>Reply to <?= htmlspecialchars($customer_name) ?></h2>
    <p>Email: <b><?= htmlspecialchars($customer_email) ?></b></p>

    <?php if ($success_message): ?>
        <div class="msg success"><?= $success_message ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="msg error"><?= $error_message ?></div>
    <?php endif; ?>
<br/>
     <form method="POST">
        <label for="reply">Your Reply:</label>
        <textarea name="reply" placeholder="Write your message here..." required><?= htmlspecialchars($reply_content) ?></textarea>
        <button type="submit">📩 Send Reply</button>
    </form>


    <p><a href="admin_view_messages.php" class="back-link">⬅ Back to Messages</a></p>
    </div>
</body>
</html>
