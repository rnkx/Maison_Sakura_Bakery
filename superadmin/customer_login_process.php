<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = "customer"; // fixed role

    if (!empty($email) && !empty($password)) {
        // ✅ Get user info
        $sql = "SELECT users_id, fullname, email, password, role, profile_image 
                FROM users 
                WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // ⚠️ If password is stored as plain text
            if ($password === $row['password']) {
                // ✅ Store data in session
                $_SESSION['users_id'] = $row['users_id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role']     = $row['role'];
                $_SESSION['email']    = $row['email'];
                $_SESSION['profile_image'] = $row['profile_image']; // ✅ fix

                header("Location: customer_index.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid password!";
                header("Location: login_customer.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "No customer account found with this email!";
            header("Location: login_customer.php");
            exit();
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: login_customer.php");
        exit();
    }

    $conn->close();
} else {
    header("Location: login_customer.php");
    exit();
}
?>
