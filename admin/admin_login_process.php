<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = "admin"; // fixed role

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT users_id, fullname, email, password, role,  profile_image 
                FROM users 
                WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Compare plain password
            if ($password === $row['password']) {
                //success
                 $_SESSION['users_id'] = $row['users_id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role']     = $row['role'];
                $_SESSION['email']    = $row['email'];
                $_SESSION['profile_image'] = $row['profile_image']; // ✅ fix
                
                header("Location: admin_index.php");
                exit();
            } else {
             
                $_SESSION['error'] = "Invalid password!";
                header("Location: login_admin.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "No admin account found with this email!";
            header("Location: login_admin.php");
            exit();
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: login_admin.php");
        exit();
    }

    $conn->close();
} else {
    header("Location: login_admin.php");
    exit();
}
?>



