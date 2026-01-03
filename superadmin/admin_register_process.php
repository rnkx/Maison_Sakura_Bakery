<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname']  ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // -----------------------------
    // Email validation (Gmail/Hotmail only)
    // -----------------------------
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail|hotmail)\.com$/", $email)) {
        echo "<p style='color:red;'>Invalid email address. Only Gmail or Hotmail with .com allowed.</p>";
        echo '<p><button onclick="window.history.back()">Back</button></p>';
        exit();
    }

    // -----------------------------
    // Password strength validation
    // -----------------------------
    if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/", $password)) {
        echo "<p style='color:red;'>Password must be at least 8 characters, include uppercase, lowercase, and a number.</p>";
        echo '<p><button onclick="window.history.back()">Back</button></p>';
        exit();
    }

    // -----------------------------
    // Confirm password match
    // -----------------------------
    if ($password !== $confirm_password) {
        echo "<p style='color:red;'>Passwords do not match!</p>";
        echo '<p><button onclick="window.history.back()">Back</button></p>';
        exit();
    }

    // -----------------------------
    // Malaysian phone validation
    // -----------------------------
    if (!preg_match("/^(\+60\d{8,9}|01\d{8,9})$/", $phone)) {
        echo "<p style='color:red;'>Phone number must be a valid Malaysian number.</p>";
        echo '<p><button onclick="window.history.back()">Back</button></p>';
        exit();
    }

    // -----------------------------
    // Hash password securely
    // -----------------------------
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // -----------------------------
    // Insert into database
    // -----------------------------
    $sql = "INSERT INTO users (fullname, email, phone, password, role) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fullname, $email, $phone, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "<p>Registration successful!</p>";   
        echo '<p><a href="login_admin.php"><button>Login Now</button></a></p>';
    } else {
        echo "<p style='color:red;'>Error: Duplicate Email Account! Please try a different email account.</p>";
        echo '<p><a href="register_admin.php"><button>Back</button></a></p>';
    }

    $stmt->close();
    $conn->close();
}
?>
