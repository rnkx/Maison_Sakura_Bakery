<?php
// customer_autoload.php
session_start();
require_once "db.php"; // database connection

// ✅ Redirect if user not logged in or not a customer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'] ?? null;

if ($users_id) {
    // ✅ Load customer details if missing in session
    if (empty($_SESSION['fullname']) || empty($_SESSION['profile_image'])) {
        $stmt = $conn->prepare("SELECT fullname, profile_image FROM users WHERE users_id = ?");
        $stmt->bind_param("i", $users_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['profile_image'] = !empty($row['profile_image']) 
                ? $row['profile_image'] 
                : "img/profile.jpg"; // fallback profile image
        }

        $stmt->close();
    }

    // ✅ Example: preload order count (optional)
    if (!isset($_SESSION['order_count'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE users_id = ?");
        $stmt->bind_param("i", $users_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SESSION['order_count'] = $row['total_orders'];
        } else {
            $_SESSION['order_count'] = 0;
        }

        $stmt->close();
    }
}
?>
