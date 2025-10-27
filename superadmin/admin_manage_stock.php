<?php
session_start();
include("db.php");

// ✅ Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$msg = "";

// ✅ Handle stock change (add/remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $products_id = intval($_POST['products_id']);
    $stock_change = intval($_POST['stock_change']);
    $reason = trim(mysqli_real_escape_string($conn, $_POST['reason']));

    if ($products_id && $stock_change !== 0 && !empty($reason)) {
        // 1️⃣ Get current + max stock
        $stmt = mysqli_prepare($conn, "SELECT current_stock, max_stock FROM products WHERE products_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $products_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_stock, $max_stock);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // 2️⃣ Calculate new stock
        $new_stock = $current_stock + $stock_change;

        // 3️⃣ Validation rules
        if ($current_stock > 0 && $stock_change > 0) {
            $msg = "⚠️ Cannot restock now because current stock is not 0 (Current Stock = $current_stock). Only restock when current stock is zero.";
        } elseif ($new_stock > $max_stock) {
            $msg = "⚠️ Storage Full! Maximum stock for this product is $max_stock. Current stock remains $current_stock.";
        } elseif ($new_stock < 0) {
            $msg = "⚠️ Invalid operation. Stock cannot go below zero.";
        } else {
            // 4️⃣ Record change in history
            $stmt = mysqli_prepare($conn, "
                INSERT INTO product_stock (products_id, stock_change, reason, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            mysqli_stmt_bind_param($stmt, "iis", $products_id, $stock_change, $reason);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 5️⃣ Update stock in `products`
            $stmt = mysqli_prepare($conn, "UPDATE products SET current_stock = ? WHERE products_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_stock, $products_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $msg = "✅ Stock updated successfully! New stock: $new_stock.";
        }
    } else {
        $msg = "⚠️ Please fill all fields correctly.";
    }
}

// ✅ Handle max stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_max_stock'])) {
    $products_id = intval($_POST['products_id']);
    $new_max_stock = intval($_POST['max_stock']);
    if ($products_id > 0 && $new_max_stock >= 0) {
        $stmt = mysqli_prepare($conn, "UPDATE products SET max_stock = ? WHERE products_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $new_max_stock, $products_id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "✅ Maximum stock updated successfully!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "⚠️ Invalid maximum stock value.";
    }
}

// ✅ Fetch all products
$products_query = "
    SELECT products_id, name, image, current_stock, max_stock
    FROM products
    ORDER BY products_id ASC
";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Product Stock - Maison Sakura Bakery</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #fff8fc;
        padding: 20px;
    }
    h1 {
        color: #d63384;
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: center;
    }
    th {
        background-color: #d63384;
        color: white;
    }
    tr:hover {
        background-color: #f9e3f0;
    }
    form {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    input, select {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }
    button {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        cursor: pointer;
    }
    button:hover {
        background-color: #218838;
    }
    .msg {
        text-align: center;
        margin: 15px 0;
        font-weight: bold;
    }
    .success { color: #28a745; }
    .warning { color: #e25822; }
    .back-btn {
        padding: 8px 15px;
        background-color: #e75480;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    .back-btn:hover { background-color: #c93c6c; }
    .edit-btn {
        background-color: #ffc107;
        color: #333;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
    }
    .edit-btn:hover { background-color: #e0a800; }
</style>
</head>
<body>

<h1>📦 Manage Product Stock</h1>

<!-- ✅ Message -->
<?php if (!empty($msg)): ?>
    <p class="msg <?= (strpos($msg, '⚠️') !== false) ? 'warning' : 'success' ?>">
        <?= htmlspecialchars($msg); ?>
    </p>
<?php endif; ?>

<!-- 🔙 Back Button -->
<form action="admin_index.php" method="get">
    <button type="submit" class="back-btn">⬅️ Back to Dashboard</button>
</form>

<!-- 🧾 Stock Update Form -->
<form method="post">
    <select name="products_id" required>
        <option value="">Select Product</option>
        <?php
        $products_dropdown = mysqli_query($conn, $products_query);
        while ($product = mysqli_fetch_assoc($products_dropdown)):
        ?>
            <option value="<?= $product['products_id']; ?>">
                <?= htmlspecialchars($product['name']); ?> 
                (Current: <?= $product['current_stock']; ?> / Max: <?= $product['max_stock']; ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <input type="number" name="stock_change" placeholder="Stock change (+/-)" required>
    <input type="text" name="reason" placeholder="Reason for change" required>
    <button type="submit" name="update_stock">Update Stock</button>
</form>

<!-- 📋 Product Table -->
<table>
    <thead>
        <tr>
            <th>Product ID</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Maximum Stock</th>
            <th>Update Max Stock</th>
            <th>Stock History</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
        <tr>
            <td><?= $product['products_id']; ?></td>
            <td>
                <img src="uploads/<?= htmlspecialchars($product['image']); ?>" 
                     alt="<?= htmlspecialchars($product['name']); ?>" 
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
            </td>
            <td><?= htmlspecialchars($product['name']); ?></td>
            <td><?= $product['current_stock']; ?></td>
            <td><?= $product['max_stock']; ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="products_id" value="<?= $product['products_id']; ?>">
                    <input type="number" name="max_stock" value="<?= $product['max_stock']; ?>" min="0" required>
                    <button type="submit" name="update_max_stock" class="edit-btn">💾 Save</button>
                </form>
            </td>
            <td>
                <a href="admin_stock_history.php?products_id=<?= $product['products_id']; ?>" style="color:#17a2b8;">
                    View History
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
