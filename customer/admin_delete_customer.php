<?php
session_start();
include("db.php");

// ✅ Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// ✅ Validate and sanitize user ID
if (!isset($_GET['users_id']) || !is_numeric($_GET['users_id'])) {
    die("Invalid customer ID.");
}

$users_id = intval($_GET['users_id']);

// ✅ Fetch customer info
$customer_query = "SELECT fullname, email FROM users WHERE users_id = ?";
$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $users_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$customer) {
    die("Customer not found.");
}

// ✅ Handle deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check for existing orders before deleting
    $order_check_query = "SELECT * FROM orders WHERE users_id = ?";
    $check_stmt = mysqli_prepare($conn, $order_check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $users_id);
    mysqli_stmt_execute($check_stmt);
    $order_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($order_result) > 0) {
        mysqli_stmt_close($check_stmt);
        header("Location: admin_manage_customers.php?msg=Cannot+delete+customer+with+existing+orders");
        exit();
    }
    mysqli_stmt_close($check_stmt);

    // Proceed with deletion if no orders found
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE users_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $users_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        mysqli_stmt_close($delete_stmt);
        header("Location: admin_manage_customers.php?msg=Customer+deleted+successfully");
        exit();
    } else {
        header("Location: admin_manage_customers.php?msg=Error+deleting+customer");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Customer - Maison Sakura Bakery</title>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #fff8fc; padding: 20px; }
h1 { color: #d63384; }
button { background-color: #d63384; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; margin: 5px; }
button:hover { background-color: #b52e70; }
a { text-decoration: none; color: #444; margin-left: 10px; }
</style>
</head>
<body>

<h1>⚠️ Delete Customer</h1>
<p>Are you sure you want to delete <strong><?= htmlspecialchars($customer['fullname']); ?></strong> (<?= htmlspecialchars($customer['email']); ?>)?</p>

<form method="post">
    <button type="submit">Yes, Delete</button>
    <a href="admin_manage_customers.php">Cancel</a>
</form>

</body>
</html>

