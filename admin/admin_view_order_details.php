<?php
session_start();
include("db.php");
if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin'){ header("Location: login_admin.php"); exit();}
$order_id = intval($_GET['order_id'] ?? 0);
if(!$order_id) die("Invalid order");

// Fetch order & payment
$stmt = $conn->prepare("
SELECT o.order_id, o.pickup_date, o.pickup_time, o.created_at,
       COALESCE(cp.amount,0) AS amount_paid,
       COALESCE(cp.payment_method,o.payment_method) AS payment_method,
       COALESCE(cp.payment_status,o.payment_status) AS payment_status
FROM orders o
LEFT JOIN customer_payments cp ON cp.order_id=o.order_id
WHERE o.order_id=?
");
$stmt->bind_param("i",$order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch order items with discount
$stmt = $conn->prepare("
SELECT p.name, oi.quantity, oi.price_original, oi.price_after_discount, oi.discount_percent
FROM order_items oi
JOIN products p ON oi.products_id=p.products_id
WHERE oi.order_id=?
");
$stmt->bind_param("i",$order_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Calculate totals
$total_paid=0;$total_savings=0;
$items_data=[];
while($item=$items->fetch_assoc()){
    $line_total=$item['price_after_discount']*$item['quantity'];
    $savings=($item['price_original']-$item['price_after_discount'])*$item['quantity'];
    $total_paid+=$line_total;
    $total_savings+=$savings;
    $items_data[] = array_merge($item,['line_total'=>$line_total,'savings'=>$savings]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Details</title>
<style>
body{font-family:Poppins,sans-serif;background:#fff8fc;padding:20px;}
h1,h2{text-align:center;color:#d63384;}
table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;margin-top:20px;}
th,td{border:1px solid #ddd;padding:12px;text-align:center;}
th{background-color:#d63384;color:white;}
tr:hover{background:#f9e3f0;}
.total{font-weight:bold;color:#d63384;text-align:right;}
.savings{font-weight:bold;color:green;text-align:right;}
</style>
</head>
<body>
<a href="javascript:history.back()" style="text-decoration:none;padding:8px 16px;background:#d63384;color:white;border-radius:6px;">⬅️ Back</a>
<h1>Order #<?= $order['order_id']; ?> Details</h1>
<p><strong>Pickup:</strong> <?= $order['pickup_date']; ?> <?= $order['pickup_time']; ?></p>
<p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']); ?> | <?= htmlspecialchars($order['payment_status']); ?> | Paid RM <?= number_format($order['amount_paid'],2); ?></p>

<table>
<tr><th>Item</th><th>Price</th><th>Qty</th><th>Line Total</th></tr>
<?php foreach($items_data as $item): ?>
<tr>
<td><?= htmlspecialchars($item['name']); ?> <?php if($item['discount_percent']>0): ?>(<?= $item['discount_percent']; ?>% off)<?php endif;?></td>
<td>RM <?= number_format($item['price_after_discount'],2); ?></td>
<td><?= $item['quantity']; ?></td>
<td>RM <?= number_format($item['line_total'],2); ?></td>
</tr>
<?php endforeach; ?>
<tr><td colspan="3" class="total">Total Paid:</td><td>RM <?= number_format($total_paid,2); ?></td></tr>
<?php if($total_savings>0): ?>
<tr><td colspan="3" class="savings">You Saved:</td><td>RM <?= number_format($total_savings,2); ?></td></tr>
<?php endif; ?>
</table>

</body>
</html>

