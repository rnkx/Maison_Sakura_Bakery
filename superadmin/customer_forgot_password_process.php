<?php
include("db.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: customer_forgot_password.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: customer_forgot_password.php");
        exit();
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT users_id FROM users WHERE email = ? AND role = 'customer'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No customer found with that email.";
        header("Location: customer_forgot_password.php");
        exit();
    }

    

    // Update password directly
    $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt2->bind_param("ss", $password, $email);
    $stmt2->execute();

    $_SESSION['success'] = "Password updated successfully. You can now login.";
    header("Location: login_customer.php");
    exit();
}
?>
