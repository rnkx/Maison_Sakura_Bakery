<?php
session_start();
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'];
$today = date('Y-m-d');

// ✅ Check if already redeemed this year
$check_sql = "SELECT * FROM birthday_rewards WHERE users_id = '$users_id' AND YEAR(redeemed_at) = YEAR(CURDATE())";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    echo "<script>alert('You already redeemed your birthday reward this year!'); window.location='customer_index.php';</script>";
    exit();
}

// ✅ Insert redemption record
$insert_sql = "INSERT INTO birthday_rewards (users_id, reward_item, redeemed_at) VALUES ('$users_id', 'Free Cupcake', NOW())";
if (mysqli_query($conn, $insert_sql)) {

    // ✅ Log the action to audit logs
    $desc = "Customer ID $users_id redeemed a free birthday cupcake.";
    $log_sql = "INSERT INTO audit_logs (action, description, timestamp) VALUES ('Birthday Reward', '$desc', NOW())";
    mysqli_query($conn, $log_sql);

    echo "<script>alert('🎉 Happy Birthday! You have redeemed your free cupcake!'); window.location='customer_index.php';</script>";
} else {
    echo "<script>alert('❌ Error redeeming reward: " . mysqli_error($conn) . "');</script>";
}
?>
