<?php
session_start();
include("db.php");

// ✅ Ensure logged in as customer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'];
$order_id = $_GET['order_id'] ?? ($_SESSION['order_id'] ?? null);

if (!$order_id || !is_numeric($order_id)) {
    die("Invalid order request.");
}

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

if (!$order) die("Order not found.");

// ✅ Fetch order items (with discount info)
$stmt_items = $conn->prepare("
    SELECT p.name, oi.quantity, oi.price_original, oi.price_after_discount, oi.discount_percent
    FROM order_items oi
    JOIN products p ON oi.products_id = p.products_id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$order_items_result = $stmt_items->get_result();
$stmt_items->close();

// ---------- Calculate totals ----------
$items_data = [];
$total_paid = 0;
$total_savings = 0;

while ($item = $order_items_result->fetch_assoc()) {
    $quantity = (int)$item['quantity'];
    $price_original = (float)$item['price_original'];
    $price_after_discount = (float)$item['price_after_discount'];
    $discount_percent = (float)$item['discount_percent'];

    $line_total = $price_after_discount * $quantity;
    $savings = ($price_original - $price_after_discount) * $quantity;

    $total_paid += $line_total;
    $total_savings += $savings;

    $items_data[] = array_merge($item, [
        'line_total' => $line_total,
        'savings' => $savings
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Confirmation | Maison Sakura Bakery</title>
<style>
body { font-family: Arial, sans-serif; background:#fff8f9; padding:40px; }
.confirmation { background:#fff; padding:30px; border-radius:12px; max-width:650px; margin:auto; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { color:#e75480; text-align:center; }
h3 { color:#333; border-bottom:2px solid #e75480; padding-bottom:5px; }
.items { margin-top:20px; }
.item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee; }
.item del { color:#888; margin-right:5px; }
.total { margin-top:10px; text-align:right; font-weight:bold; color:#e75480; }
.savings { margin-top:5px; text-align:right; color:green; font-weight:bold; }
.info { margin-bottom:15px; }
.btn { display:inline-block; padding:10px 20px; background-color:#e75480; color:white; font-weight:bold; text-decoration:none; border-radius:6px; text-align:center; transition:background-color 0.3s ease; }
.btn:hover { background-color:#c73c65; }
.footer { text-align:center; margin-top:20px; }
</style>
</head>
<body>
<div class="confirmation">
  <h2>🎉 Thank you, <?= htmlspecialchars($_SESSION['fullname']); ?>!</h2>
  <p style="text-align:center;">Your order <b>#<?= $order['order_id']; ?></b> has been placed successfully.</p>

  <div class="info">
    <p><strong>Pickup Date:</strong> <?= date("d M Y", strtotime($order['pickup_date'])); ?></p>
    <p><strong>Pickup Time:</strong> <?= htmlspecialchars($order['pickup_time']); ?></p>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>
    <p><strong>Payment Status:</strong> <?= htmlspecialchars($order['payment_status']); ?></p>
    <p><em>All goods are non-refundable unless system issues occur.</em></p>
  </div>

  <div class="items">
    <h3>🧁 Order Items</h3>
    <?php if (count($items_data) > 0): ?>
      <?php foreach ($items_data as $item): ?>
        <div class="item">
          <span>
            <?= htmlspecialchars($item['name']); ?> × <?= $item['quantity']; ?>
            <?php if ($item['discount_percent'] > 0): ?>
              <br><del>RM <?= number_format($item['price_original'], 2); ?></del>
              RM <?= number_format($item['price_after_discount'], 2); ?> (<?= $item['discount_percent']; ?>% off)
            <?php else: ?>
              <br>RM <?= number_format($item['price_original'], 2); ?>
            <?php endif; ?>
          </span>
          <span>RM <?= number_format($item['line_total'], 2); ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:red;">No items found for this order.</p>
    <?php endif; ?>

    <div class="total">Total Paid: RM <?= number_format($total_paid, 2); ?></div>
    <?php if ($total_savings > 0): ?>
      <div class="savings">You Saved: RM <?= number_format($total_savings, 2); ?></div>
    <?php endif; ?>
  </div>

  <div class="footer">
    <a href="customer_orders.php" class="btn">View My Orders</a>
  </div>
</div>
</body>
</html>
