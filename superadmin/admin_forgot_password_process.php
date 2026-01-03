<?php
include("db.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ------------------------------------------
    // 📌 Basic validation
    // ------------------------------------------
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: admin_forgot_password.php");
        exit();
    }

    // Email format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: admin_forgot_password.php");
        exit();
    }

    // Passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: admin_forgot_password.php");
        exit();
    }

    // Password strength rule (uppercase, lowercase, number, min 8)
    if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/", $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters, include uppercase, lowercase, and a number.";
        header("Location: admin_forgot_password.php");
        exit();
    }

    // ------------------------------------------
    // 📌 Check if admin user exists
    // ------------------------------------------
    $stmt = $conn->prepare("SELECT users_id FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No admin found with that email.";
        header("Location: admin_forgot_password.php");
        exit();
    }

    // ------------------------------------------
    // 🔐 Securely hash password
    // ------------------------------------------
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update password
    $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt2->bind_param("ss", $hashedPassword, $email);
    $stmt2->execute();

    $_SESSION['success'] = "Password updated successfully. You can now login.";
    header("Location: login_admin.php");
    exit();
}
?>
