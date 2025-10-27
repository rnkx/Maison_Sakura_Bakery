<?php
session_start();
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['products_id'])) {
    header("Location: admin_manage_stock.php");
    exit();
}

$products_id = intval($_GET['products_id']);

// Fetch product name
$product_query = "SELECT name FROM products WHERE products_id = ?";
$stmt = mysqli_prepare($conn, $product_query);
mysqli_stmt_bind_param($stmt, "i", $products_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) die("Product not found.");

// Fetch stock history
$history_query = "SELECT stock_change, reason, created_at FROM product_stock WHERE products_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt, "i", $products_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock History - <?= htmlspecialchars($product['name']); ?></title>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #fff8fc; padding: 20px; }
h1 { color: #d63384; text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
th { background-color: #d63384; color: white; }
tr:hover { background-color: #f9e3f0; }
.back-btn { display:block; margin-bottom: 20px; background-color:#d63384; color:white; border:none; padding:10px 18px; border-radius:8px; cursor:pointer; text-align:center; width:180px; }
.back-btn:hover { background-color:#b52e70; }
</style>
</head>
<body>

<form action="admin_manage_stock.php" method="get">
    <button type="submit" class="back-btn">⬅️ Back to Stock</button>
</form>

<h1>📄 Stock History: <?= htmlspecialchars($product['name']); ?></h1>

<?php if(mysqli_num_rows($history_result) > 0): ?>
<table>
    <tr>
        <th>Change</th>
        <th>Reason</th>
        <th>Date</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($history_result)): ?>
        <tr>
            <td><?= $row['stock_change']; ?></td>
            <td><?= htmlspecialchars($row['reason']); ?></td>
            <td><?= date("d M Y, H:i", strtotime($row['created_at'])); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p style="text-align:center;">No stock history found for this product.</p>
<?php endif; ?>

</body>
</html>
