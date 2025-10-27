<?php
session_start();
include("db.php");

// ✅ Ensure customer logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'];
$order_id = intval($_GET['order_id'] ?? 0);

// ✅ Fetch order details
$stmt = $conn->prepare("
    SELECT order_id, total_price, payment_method, payment_status, pickup_date, pickup_time, created_at
    FROM orders
    WHERE order_id = ? AND users_id = ?
");
$stmt->bind_param("ii", $order_id, $users_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) die("⚠️ Order not found.");

// ✅ Fetch order items including discounts
$stmt_items = $conn->prepare("
    SELECT p.name, oi.quantity, oi.price_original, oi.price_after_discount, oi.discount_percent
    FROM order_items oi
    JOIN products p ON oi.products_id = p.products_id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
$stmt_items->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt | Maison Sakura Bakery</title>
  <style>
    body { font-family: "Courier New", monospace; max-width: 500px; margin:auto; padding:20px; background:#fff; }
    h1, h2 { text-align:center; color:#e75480; margin-bottom:5px; }
    .header { text-align:center; }
    .header img { width:80px; height:80px; margin-bottom:5px; }
    .info, .items, .total-section { margin-top:15px; }
    .items table { width:100%; border-collapse: collapse; }
    .items th, .items td { padding:6px; border-bottom:1px dashed #ccc; text-align:left; }
    .items th { text-align:left; background:#f9f9f9; }
    .discount { color:#28a745; font-weight:bold; }
    .total-section { margin-top:10px; text-align:right; font-weight:bold; font-size:16px; }
    button.print-btn { margin-top:20px; width:100%; padding:10px; background:#e75480; color:white; border:none; border-radius:6px; cursor:pointer; font-size:16px; }
    button.print-btn:hover { background:#c73c65; }
    @media print { button.print-btn { display:none; } }
  </style>
</head>
<body>
<div class="header">
    <img src="img/logo.jpg" alt="Maison Sakura Logo">
    <h1>Maison Sakura Bakery</h1>
    <p><strong>Official Receipt</strong></p>
</div>

<div class="info">
    <p><strong>Order #:</strong> <?= $order['order_id']; ?></p>
    <p><strong>Placed on:</strong> <?= date("d M Y, H:i", strtotime($order['created_at'])); ?></p>
    <p><strong>Pickup:</strong> <?= date("d M Y", strtotime($order['pickup_date'])); ?> at <?= $order['pickup_time']; ?></p>
    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']); ?> - <?= htmlspecialchars($order['payment_status']); ?></p>
</div>

<div class="items">
    <table>
        <tr>
            <th>Product</th><th>Qty</th><th>Original</th><th>Discount</th><th>Final</th><th>Subtotal</th>
        </tr>
        <?php
        $total_before = 0;
        $total_after  = 0;
        while ($item = $items->fetch_assoc()):
            $subtotal_before = $item['price_original'] * $item['quantity'];
            $subtotal_after  = $item['price_after_discount'] * $item['quantity'];
            $total_before += $subtotal_before;
            $total_after  += $subtotal_after;
        ?>
        <tr>
            <td><?= htmlspecialchars($item['name']); ?></td>
            <td><?= $item['quantity']; ?></td>
            <td>RM <?= number_format($item['price_original'], 2); ?></td>
            <td><?= $item['discount_percent']; ?>%</td>
            <td>RM <?= number_format($item['price_after_discount'], 2); ?></td>
            <td>RM <?= number_format($subtotal_after, 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="total-section">
    <p>Total Before Discount: RM <?= number_format($total_before, 2); ?></p>
    <p>Discount Saved: <span class="discount">RM <?= number_format($total_before - $total_after, 2); ?></span></p>
    <p><strong>Total Paid: RM <?= number_format($total_after, 2); ?></strong></p>
</div>

<hr>
<p style="font-size:13px; color:#888; text-align:center;">
    Maison Sakura Bakery • All sales are final • For order verification, email <u>rachel.ng.ker.xin@gmail.com</u>
</p>

<p style="text-align:center; color:#e75480; font-size:14px;">Verification Code: 
    <strong><?= strtoupper(substr(md5($order['order_id'] . $order['created_at']), 0, 8)); ?></strong>
</p>

<button class="print-btn" onclick="window.print();">🧾 Print Receipt</button>
</body>

</html>
