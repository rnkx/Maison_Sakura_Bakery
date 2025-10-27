<?php
session_start();
include("db.php");

// Ensure admin access only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Check if users_id is provided and numeric
if (!isset($_GET['users_id']) || !is_numeric($_GET['users_id'])) {
    die("Customer ID is missing or invalid.");
}

$users_id = intval($_GET['users_id']);

// Fetch customer to confirm deletion
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

// Handle deletion when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE users_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $users_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        mysqli_stmt_close($delete_stmt);
        // Redirect with success message
        header("Location: admin_manage_customers.php?msg=Customer+deleted+successfully");
        exit();
    } else {
        die("Error deleting customer: " . mysqli_error($conn));
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

