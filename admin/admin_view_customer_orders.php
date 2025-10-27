<?php
session_start();
include("db.php");
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}
if (!isset($_GET['users_id'])) header("Location: admin_manage_customers.php");
$users_id = intval($_GET['users_id']);

// Fetch customer info
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE users_id=?");
$stmt->bind_param("i",$users_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch orders with payment info
$stmt = $conn->prepare("
SELECT o.order_id, COALESCE(cp.amount,0) AS amount_paid, 
       COALESCE(cp.payment_method,o.payment_method) AS payment_method,
       COALESCE(cp.payment_status,o.payment_status) AS payment_status,
       o.pickup_date, o.pickup_time, o.created_at
FROM orders o
LEFT JOIN customer_payments cp ON cp.order_id=o.order_id
WHERE o.users_id=? ORDER BY o.created_at DESC
");
$stmt->bind_param("i",$users_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Orders</title>
<style>
body{font-family:Poppins,sans-serif;background:#fff8fc;padding:20px;}
h1,h2{text-align:center;color:#d63384;}
table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;}
th,td{border:1px solid #ddd;padding:12px;text-align:center;}
th{background-color:#d63384;color:white;}
tr:hover{background:#f9e3f0;}
.btn{display:inline-block;padding:6px 12px;border-radius:6px;text-decoration:none;color:white;font-weight:500;}
.details-btn{background:#17a2b8;}
.details-btn:hover{opacity:0.9;}
</style>
</head>
<body>
<a href="admin_manage_customers.php" class="btn" style="background:#d63384;margin-bottom:20px;">⬅️ Back</a>
<h1><?= htmlspecialchars($customer['fullname']); ?>'s Orders</h1>

<?php if($result->num_rows>0): ?>
<table>
<tr>
<th>Order ID</th>
<th>Amount Paid (RM)</th>
<th>Payment Method</th>
<th>Payment Status</th>
<th>Pickup Date</th>
<th>Pickup Time</th>
<th>Placed On</th>
<th>Details</th>
</tr>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?= $row['order_id']; ?></td>
<td><?= number_format($row['amount_paid'],2); ?></td>
<td><?= htmlspecialchars($row['payment_method']); ?></td>
<td><?= htmlspecialchars($row['payment_status']); ?></td>
<td><?= htmlspecialchars($row['pickup_date']); ?></td>
<td><?= htmlspecialchars($row['pickup_time']); ?></td>
<td><?= date("d M Y H:i",strtotime($row['created_at'])); ?></td>
<td>
<a href="admin_view_order_details.php?order_id=<?= $row['order_id']; ?>" class="btn details-btn">📄 Details</a>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p style="text-align:center;">No orders found for this customer.</p>
<?php endif; ?>
</body>
</html>
