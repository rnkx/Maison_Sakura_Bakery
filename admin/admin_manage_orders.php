<?php
session_start();
include("db.php"); // make sure this file connects via $conn

// Redirect if not logged in or not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Handle order cancellation (admin action)
if (isset($_GET['cancel_order_id'])) {
    $order_id = intval($_GET['cancel_order_id']);
    $cancelled_by = $_SESSION['fullname'];
    $cancelled_at = date('Y-m-d H:i:s');

    // --- 1️⃣ Update orders table ---
    $cancel_query = "UPDATE orders 
                     SET payment_status='Cancelled & Refunded', cancelled_by=?, cancelled_at=? 
                     WHERE order_id=?";
    $stmt = mysqli_prepare($conn, $cancel_query);
    mysqli_stmt_bind_param($stmt, "ssi", $cancelled_by, $cancelled_at, $order_id);
    $order_updated = mysqli_stmt_execute($stmt);

    // --- 2️⃣ Update customer_payment table ---
    $refund_query = "UPDATE customer_payments
                     SET payment_status='Cancelled & Refunded' 
                     WHERE order_id=?";
    $stmt_refund = mysqli_prepare($conn, $refund_query);
    mysqli_stmt_bind_param($stmt_refund, "i", $order_id);
    $payment_updated = mysqli_stmt_execute($stmt_refund);

    if ($order_updated && $payment_updated) {
        header("Location: admin_manage_orders.php?msg=🚫 Order cancelled & refunded successfully #$order_id cancelled & refunded successfully");
        exit();
    } else {
        header("Location: admin_manage_orders.php?msg=❌ Failed to cancel or refund order #$order_id");
        exit();
    }
}

// Handle marking order as received
if (isset($_GET['mark_received_id'])) {
    $order_id = intval($_GET['mark_received_id']);

    // --- 1️⃣ Update orders table ---
    $received_query = "UPDATE orders 
                       SET payment_status='Received' 
                       WHERE order_id=?";
    $stmt = mysqli_prepare($conn, $received_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    $order_updated = mysqli_stmt_execute($stmt);

    // --- 2️⃣ Update customer_payment table ---
    $payment_query = "UPDATE customer_payments 
                      SET payment_status='Received' 
                      WHERE order_id=?";
    $stmt_payment = mysqli_prepare($conn, $payment_query);
    mysqli_stmt_bind_param($stmt_payment, "i", $order_id);
    $payment_updated = mysqli_stmt_execute($stmt_payment);

    if ($order_updated && $payment_updated) {
        header("Location: admin_manage_orders.php?msg=✅ Order received successfully #$order_id marked as received successfully");
        exit();
    } else {
        header("Location: admin_manage_orders.php?msg=❌ Failed to update order #$order_id");
        exit();
    }
}

// Fetch all orders
$query = "SELECT * FROM orders ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Check for query errors
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Manage Orders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #f8fafc;
  margin: 20px;
  color: #333;
}
h2 {
  text-align: center;
  color: #e75480;
  margin-bottom: 20px;
}
.back-btn {
  display:inline-block;
  background-color:#e75480;
  color:white;
  padding:10px 18px;
  border-radius:8px;
  text-decoration:none;
  font-weight:bold;
  margin:10px 2.5%;
  transition:0.3s;
}
.back-btn:hover {
  background-color:#c73c65;
}
.message-box {
  background-color:#d4edda;
  color:#155724;
  border:1px solid #c3e6cb;
  padding:10px 15px;
  border-radius:8px;
  width:95%;
  margin:10px auto;
  text-align:center;
  font-weight:bold;
}
table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-top: 20px;
}
th, td {
  text-align: center;
  padding: 12px;
  border-bottom: 1px solid #eee;
}
th {
  background-color: #e75480;
  color: white;
}
tr:hover {
  background-color: #f9f9f9;
}
select, button {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  cursor: pointer;
}
.update-btn {
  background-color: #e75480;
  color: white;
  border: none;
  font-weight: bold;
  transition: 0.3s;
}
.update-btn:hover {
  background-color: #c73c65;
}

.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    align-items: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s ease, transform 0.2s ease;
    color: white;
    font-size: 14px;
}

.receive-btn {
    background-color: #28a745;
}

.receive-btn:hover {
    background-color: #218838;
    transform: scale(1.05);
}

.cancel-btn {
    background-color: #dc3545;
}

.cancel-btn:hover {
    background-color: #b02a37;
    transform: scale(1.05);
}

</style>
</head>
<body>



<a href="admin_index.php" class="back-btn">⬅️ Back to Dashboard</a>

<?php if (isset($_GET['msg'])): ?>
    <div class="msg-box"><?= htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<h2>📦 Customer Orders</h2>

<table>
    <tr>
        <th>Order ID</th>
        <th>User ID</th>
        <th>Total Price (RM)</th>
        <th>Payment Method</th>
        <th>Payment Status</th>
        <th>Pickup Date</th>
        <th>Pickup Time</th>
        <th>Created At</th>
        <th>Cancelled By</th>
        <th>Cancelled At</th>
        <th>Action</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr class="<?= $row['payment_status'] === 'Cancelled' ? 'cancelled' : ''; ?>">
            <td><?= htmlspecialchars($row['order_id']); ?></td>
            <td><?= htmlspecialchars($row['users_id']); ?></td>
            <td><?= htmlspecialchars($row['total_price']); ?></td>
            <td><?= htmlspecialchars($row['payment_method']); ?></td>
            <td><?= htmlspecialchars($row['payment_status']); ?></td>
            <td><?= htmlspecialchars($row['pickup_date']); ?></td>
            <td><?= htmlspecialchars($row['pickup_time']); ?></td>
            <td><?= htmlspecialchars($row['created_at']); ?></td>
            <td><?= htmlspecialchars($row['cancelled_by']); ?></td>
            <td><?= htmlspecialchars($row['cancelled_at']); ?></td>
            <td>
    <?php if ($row['payment_status'] === 'Cancelled & Refunded'): ?>
         <span style="color:red; font-weight:bold;">🚫 Cancelled</span>

    <?php elseif ($row['payment_status'] === 'Received'): ?>
        <span style="color:green; font-weight:bold;">✔ Received</span>

    <?php else: ?>
    <div class="action-buttons">
        <a href="?mark_received_id=<?= $row['order_id']; ?>" 
           class="btn receive-btn"
           onclick="return confirm('Confirm that this order has been received by the customer?');">
           <i class="fa-solid fa-box-open"></i> Order Received
        </a>
        <a href="?cancel_order_id=<?= $row['order_id']; ?>" 
           class="btn cancel-btn"
           onclick="return confirm('Are you sure you want to cancel this order?');">
           <i class="fa-solid fa-ban"></i> Cancel
        </a>
    </div>
<?php endif; ?>

</td>

        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>