<?php
session_start();
include("db.php");

// ✅ Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$msg = "";

// =====================
// AUTO-RESET EXPIRED RAW ITEM STOCK
// =====================
mysqli_query($conn, "
    UPDATE raw_items 
    SET current_stock = 0 
    WHERE expiry_date IS NOT NULL 
      AND expiry_date <= CURDATE() 
      AND current_stock > 0
");

// =====================
// ADD NEW RAW ITEM
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_raw_item'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $max_stock = intval($_POST['max_stock']);
    $current_stock = intval($_POST['initial_stock'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $min_date = date('Y-m-d', strtotime('+3 days'));

    if ($expiry_date < $min_date) {
        $msg = "⚠️ Expiry date must be at least 3 days from today.";
    } else {
        $stmt = $conn->prepare("INSERT INTO raw_items (name, unit, current_stock, max_stock, expiry_date, date_issued) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssdds", $name, $unit, $current_stock, $max_stock, $expiry_date);
        if ($stmt->execute()) {
            $msg = "✅ Raw item added successfully!";
        } else {
            $msg = "❌ Failed to add raw item.";
        }
        $stmt->close();
    }
}


// =====================
// UPDATE STOCK + EXPIRY DATE
// =====================
if (isset($_POST['update_stock'])) {
    $id = intval($_POST['raw_id'] ?? 0);
    $stock_change = intval($_POST['stock_change']);
    $reason = trim(mysqli_real_escape_string($conn, $_POST['reason']));
    $expiry_date = $_POST['expiry_date'] ?? '';
    $min_expiry = date('Y-m-d', strtotime('+3 days'));

    if ($expiry_date < $min_expiry) {
        $msg = "⚠️ Expiry date must be at least 3 days from today.";
    } else {
        $stmt = $conn->prepare("SELECT current_stock, max_stock FROM raw_items WHERE raw_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($current_stock, $max_stock);
        $stmt->fetch();
        $stmt->close();

        $new_stock = $current_stock + $stock_change;

        if ($new_stock < 0) {
            $msg = "⚠️ Stock cannot go below zero.";
        } elseif ($new_stock > $max_stock) {
            $msg = "⚠️ Storage full! Max stock = $max_stock.";
        } else {
            $stmt = $conn->prepare("UPDATE raw_items SET current_stock=?, date_issued= NOW(), expiry_date=? WHERE raw_id=?");
            $stmt->bind_param("isi", $new_stock, $expiry_date, $id);
            $stmt->execute();
            $stmt->close();

            // ✅ Record stock change
            $stmt = $conn->prepare("INSERT INTO raw_item_stock_history (raw_id, stock_change, reason, date_updated, created_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("ids", $id, $stock_change, $reason);
            $stmt->execute();
            $stmt->close();

            $msg = "✅ Stock and expiry date updated successfully!";
        }
    }
}

// =====================
// UPDATE MAX STOCK
// =====================
if (isset($_POST['update_max_stock'])) {
    $id = intval($_POST['raw_id'] ?? 0);
    $max = intval($_POST['max_stock']);
    if ($id > 0 && $max >= 0) {
        $stmt = $conn->prepare("UPDATE raw_items SET max_stock=? WHERE raw_id=?");
        $stmt->bind_param("ii", $max, $id);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ Max stock updated!";
    }
}



// =====================
// DELETE RAW ITEM
// =====================
if (isset($_POST['delete_raw_item'])) {
    $id = intval($_POST['raw_id'] ?? 0);
     $stmt = $conn->prepare("DELETE FROM raw_items WHERE raw_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $msg = "🗑️ Raw item deleted successfully!";
}

// =====================
// FETCH ALL RAW ITEMS
// =====================
$raw_items = mysqli_query($conn, "SELECT * FROM raw_items ORDER BY raw_id DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Manage Raw Items</title>
<style>
body { font-family: Arial, sans-serif; background:#fff8f5; padding:20px; }
h2 { text-align:center; color:#c56b6b; }
table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; border-radius:12px; }
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#c56b6b; color:#fff; }
tr:hover { background:#fff2ef; }
input, textarea { padding:5px; border-radius:6px; border:1px solid #ccc; width:90%; }
button { border:none; border-radius:6px; padding:6px 12px; cursor:pointer; }
.update-btn { background:#e8a0a0; color:white; }
.update-btn:hover { background:#d98585; }
.delete-btn { background:#dc3545; color:white; }
.delete-btn:hover { background:#b02a37; }
.success-msg { background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin:10px 0; text-align:center; font-weight:bold; }
img { border-radius:8px; object-fit:cover; }
</style>
</head>
<body>

<?php if(!empty($msg)): ?>
    <div class="success-msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<h2>🛒 Raw Items Management</h2>
<form action="admin_index.php" method="get">
    <button type="submit" class="back-btn">⬅️ Back to Dashboard</button><br><br>
</form>

<!-- Add New Raw Item Form -->
<form method="POST" enctype="multipart/form-data" style="margin-bottom:20px;">
    <input type="text" name="name" placeholder="Raw Item Name" required><br><br>
    <input type="text" name="unit" placeholder="Unit (e.g., kg, pcs)" required><br><br>
    <input type="number" name="max_stock" placeholder="Maximum Stock" min="0" required><br><br>
    <input type="number" name="initial_stock" placeholder="Initial Stock" min="0"><br><br>
    Expiry Date: 
    <?php $min_expiry = date('Y-m-d', strtotime('+3 days')); ?>
    <input type="date" name="expiry_date" min="<?= $min_expiry ?>"  placeholder="Expiry Date" style="width:83.5%;" required><br><br>
    <button type="submit" name="add_raw_item">➕ Add Raw Item</button>
</form>

<!-- Raw Items Table -->
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Unit</th>
    <th>Current Stock</th>
    <th>Max Stock</th>
    <th>Date Issued</th>
    <th>Expiry Date</th>
    <th>Actions</th>
</tr>

<?php while($r = mysqli_fetch_assoc($raw_items)): ?>
<tr>
    <td><?= $r['raw_id'] ?></td>
  
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td><?= htmlspecialchars($r['unit']) ?></td>
    <td>
        <?= $r['current_stock'] ?>
        <form method="POST">
            <input type="hidden" name="raw_id" value="<?= $r['raw_id'] ?>">
            <input type="number" name="stock_change" placeholder="+/-" required><br><br>
            <input type="text" name="reason" placeholder="Reason" required><br><br>
            <?php $min_expiry = date('Y-m-d', strtotime('+3 days')); ?>
            <input type="date" name="expiry_date" value="<?= $r['expiry_date'] ?>" min="<?= $min_expiry ?>" required><br><br>
            <button type="submit" name="update_stock" class="update-btn">Update Stock</button>
        </form>
    </td>
    <td>
        <form method="POST">
            <input type="hidden" name="raw_id" value="<?= $r['raw_id'] ?>">
            <input type="number" name="max_stock" value="<?= $r['max_stock'] ?>" min="0" required><br><br>
            <button type="submit" name="update_max_stock" class="update-btn">Update Max</button>
        </form>
    </td>
    <td><?= $r['date_issued'] ?></td>
    <td><?= $r['expiry_date'] ?></td>
    <td>
        <form method="POST" onsubmit="return confirm('Delete this raw item?');">
            <input type="hidden" name="raw_id" value="<?= $r['raw_id'] ?>">
            <button type="submit" name="delete_raw_item" class="delete-btn">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
