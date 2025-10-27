<?php
session_start();
include("db.php");

// ✅ Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// ✅ Get raw_id from GET
if (!isset($_GET['raw_id'])) {
    header("Location: admin_manage_raw_items.php");
    exit();
}

$raw_id = intval($_GET['raw_id']);

// ✅ Fetch raw item info
$stmt = mysqli_prepare($conn, "SELECT name, unit FROM raw_items WHERE raw_id=?");
mysqli_stmt_bind_param($stmt, "i", $raw_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $raw_name, $raw_unit);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// ✅ Fetch stock history
$history_result = mysqli_query($conn, "SELECT * FROM raw_item_stock_history WHERE raw_id=$raw_id ORDER BY changed_at DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock History - <?= htmlspecialchars($raw_name) ?></title>
<style>
body { font-family: Arial; background:#fff8f9; padding:20px; }
h1 { text-align:center; color:#e75480; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background-color:#e75480; color:white; }
tr:hover { background:#fff2f2; }
.back-btn { display:inline-block; margin-bottom:10px; padding:6px 12px; background:#e75480; color:white; text-decoration:none; border-radius:6px; }
</style>
</head>
<body>

<a href="admin_manage_raw_items.php" class="back-btn">⬅️ Back to Raw Items</a>
<h1>📜 Stock History - <?= htmlspecialchars($raw_name) ?> (<?= htmlspecialchars($raw_unit) ?>)</h1>

<?php if(mysqli_num_rows($history_result) == 0): ?>
<p style="text-align:center; color:#e75480;">No stock changes recorded yet.</p>
<?php else: ?>
<table>
<tr>
<th>#</th>
<th>Change</th>
<th>Reason</th>
<th>Timestamp</th>
</tr>

<?php $i=1; while($row = mysqli_fetch_assoc($history_result)): ?>
<tr>
<td><?= $i++; ?></td>
<td>
<?= $row['stock_change'] > 0 ? '+' : '' ?><?= $row['stock_change']; ?>
</td>
<td><?= htmlspecialchars($row['reason']); ?></td>
<td><?= $row['changed_at']; ?></td>
</tr>
<?php endwhile; ?>

</table>
<?php endif; ?>

</body>
</html>
