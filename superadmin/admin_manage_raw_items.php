<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
include("db.php");

// ✅ Admin Access Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// =====================
// CONFIGURATION
// =====================
date_default_timezone_set('Asia/Kuala_Lumpur');
$msg = "";
$low_stock_threshold = 10;
$expiry_warning_days = 2;
$today = new DateTime();

// =====================
// EMAIL FUNCTION
// =====================
function sendAlertEmail($to, $subject, $messageBody) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rachel.ng.ker.xin@gmail.com';
        $mail->Password = 'pcsg mvvd axfo sddx'; // Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rachel.ng.ker.xin@gmail.com', 'Maison Sakura System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($messageBody);
        $mail->AltBody = strip_tags($messageBody);
        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
    }
}

// =====================
// RESET EXPIRED STOCK
// =====================
$conn->query("
    UPDATE raw_items 
    SET current_stock = 0 
    WHERE expiry_date IS NOT NULL 
      AND expiry_date <= CURDATE() 
      AND current_stock > 0
");

// =====================
// HELPER: SAFE PREPARED STATEMENT
// =====================
function prepareStmt($conn, $query) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    return $stmt;
}

// =====================
// ADD RAW ITEM
// =====================
if (isset($_POST['add_raw_item'])) {
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $max_stock = intval($_POST['max_stock']);
    $current_stock = intval($_POST['initial_stock'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $min_date = date('Y-m-d', strtotime('+3 days'));

    if ($expiry_date < $min_date) {
        $msg = "⚠️ Expiry date must be at least 3 days from today.";
    } else {
        $stmt = prepareStmt($conn, "
            INSERT INTO raw_items (name, unit, current_stock, max_stock, expiry_date, date_issued)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssdds", $name, $unit, $current_stock, $max_stock, $expiry_date);
        $msg = $stmt->execute() ? "✅ Raw item added successfully!" : "❌ Failed to add raw item.";
        $stmt->close();
    }
}

// =====================
// UPDATE STOCK
// =====================
if (isset($_POST['update_stock'])) {
    $id = intval($_POST['raw_id']);
    $stock_change = intval($_POST['stock_change']);
    $reason = trim($_POST['reason']);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $min_expiry = date('Y-m-d', strtotime('+3 days'));

    if ($expiry_date < $min_expiry) {
        $msg = "⚠️ Expiry date must be at least 3 days from today.";
    } else {
        // Fetch current and max stock
        $stmt = prepareStmt($conn, "SELECT current_stock, max_stock FROM raw_items WHERE raw_id=?");
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
            // Update stock
            $stmt = prepareStmt($conn, "
                UPDATE raw_items 
                SET current_stock=?, date_issued=NOW(), expiry_date=? 
                WHERE raw_id=?
            ");
            $stmt->bind_param("isi", $new_stock, $expiry_date, $id);
            $stmt->execute();
            $stmt->close();

            // Log change
            $stmt = prepareStmt($conn, "
                INSERT INTO raw_item_stock_history (raw_id, stock_change, reason, date_updated, created_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->bind_param("ids", $id, $stock_change, $reason);
            $stmt->execute();
            $stmt->close();

            $msg = "✅ Stock and expiry updated successfully!";
        }
    }
}

// =====================
// UPDATE MAX STOCK
// =====================
if (isset($_POST['update_max_stock'])) {
    $id = intval($_POST['raw_id']);
    $max = intval($_POST['max_stock']);

    if ($id > 0 && $max >= 0) {
        $stmt = prepareStmt($conn, "UPDATE raw_items SET max_stock=? WHERE raw_id=?");
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
    $id = intval($_POST['raw_id']);
    $stmt = prepareStmt($conn, "DELETE FROM raw_items WHERE raw_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $msg = "🗑️ Raw item deleted successfully!";
}

// =====================
// FETCH RAW ITEMS
// =====================
$raw_items = $conn->query("
    SELECT raw_id, name, unit, current_stock, max_stock, date_issued, expiry_date
    FROM raw_items 
    ORDER BY raw_id DESC
", MYSQLI_STORE_RESULT);
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
button { border:none; border-radius:6px; padding:6px 12px; cursor:pointer; }
.update-btn { background:#e8a0a0; color:white; }
.update-btn:hover { background:#d98585; }
.delete-btn { background:#dc3545; color:white; }
.delete-btn:hover { background:#b02a37; }
.low-stock { background:#fff7e6; }
.success-msg { background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin:10px 0; text-align:center; font-weight:bold; }
.alert-icon { color:#ff6600; margin-left:4px; font-weight:bold; }
</style>
</head>
<body>

<?php if(!empty($msg)): ?>
<div class="success-msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<h2>🧺 Manage Raw Items</h2>
<form action="admin_index.php" method="get">
    <button type="submit">⬅️ Back to Dashboard</button>
</form>

<!-- Add Raw Item -->
<form method="POST" style="margin:20px 0;">
    <input type="text" name="name" placeholder="Raw Item Name" required><br><br>
    <input type="text" name="unit" placeholder="Unit (e.g., kg, pcs)" required><br><br>
    <input type="number" name="max_stock" placeholder="Maximum Stock" min="0" required><br><br>
    <input type="number" name="initial_stock" placeholder="Initial Stock" min="0"><br><br>
    Expiry Date:
    <input type="date" name="expiry_date" min="<?= date('Y-m-d', strtotime('+3 days')) ?>" required><br><br>
    <button type="submit" name="add_raw_item">➕ Add Raw Item</button>
</form>

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

<?php
$email_sent = []; // To prevent duplicate notifications

while ($r = $raw_items->fetch_assoc()):
    $expiry_date = !empty($r['expiry_date']) ? new DateTime($r['expiry_date']) : null;
    $days_to_expiry = $expiry_date ? $today->diff($expiry_date)->days : null;
    $low_stock = ($r['current_stock'] <= $low_stock_threshold);
    $near_expiry = ($expiry_date && $expiry_date >= $today && $days_to_expiry <= $expiry_warning_days);
    $is_expired = ($expiry_date && $expiry_date < $today);

    // ⚠️ Send one-time alert email
    if (($low_stock || $near_expiry) && !isset($email_sent[$r['raw_id']])) {
        $to = "rachel.ng.ker.xin@gmail.com";
        $subject = "⚠️ Inventory Alert: {$r['name']} (Raw ID: {$r['raw_id']})";
        $message = "
        Dear Admin,<br><br>
        Alert for raw material <b>{$r['name']}</b> (ID: {$r['raw_id']}):<br>
        Current Stock: {$r['current_stock']}<br>
        Expiry Date: {$r['expiry_date']}<br><br>
        " . ($low_stock ? "⚠️ Low Stock<br>" : "") .
        ($near_expiry ? "⏳ Expiring Soon<br>" : "") . "
        <br>Please dispose your old raw items and restock a new raw items immediately.<br><br>
        Maison Sakura System
        ";
        sendAlertEmail($to, $subject, $message);
        $email_sent[$r['raw_id']] = true;
    }
?>
<tr class="<?= ($low_stock || $near_expiry) ? 'low-stock' : '' ?>">
    <td><?= $r['raw_id'] ?></td>
    <td>
        <strong><?= htmlspecialchars($r['name']) ?></strong>
        <?php if($low_stock): ?><span class="alert-icon">⚠️</span><?php endif; ?>
        <?php if($near_expiry): ?><span class="alert-icon">⏳</span><?php endif; ?>
        <?php if($is_expired): ?><span class="alert-icon" style="color:red;">❌</span><?php endif; ?>
    </td>
    <td><?= htmlspecialchars($r['unit']) ?></td>
    <td>
        <?= htmlspecialchars($r['current_stock']) ?>
        <?php if($r['current_stock'] == 0): ?>
            <span style="color:red; font-weight:bold;">⚠️ Out of Stock!</span>
        <?php elseif($low_stock): ?>
            <span style="color:#ff6600; font-weight:bold;">⚠️ Low Stock!</span>
        <?php endif; ?>

        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="raw_id" value="<?= $r['raw_id'] ?>">
            <input type="number" name="stock_change" placeholder="+/-" required><br><br>
            <input type="text" name="reason" placeholder="Reason" required><br><br>
            Expiry Date:
            <input type="date" name="expiry_date" value="<?= htmlspecialchars($r['expiry_date']) ?>" min="<?= date('Y-m-d', strtotime('+3 days')) ?>" required><br><br>
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
    <td><?= htmlspecialchars($r['date_issued']) ?></td>
    <td>
        <?= htmlspecialchars($r['expiry_date']) ?>
        <?php if($is_expired): ?><span style="color:red; font-weight:bold;">(Expired)</span>
        <?php elseif($near_expiry): ?><span style="color:#ff6600; font-weight:bold;">(Expiring Soon)</span><?php endif; ?>
    </td>
    <td>
        <form method="POST" onsubmit="return confirm('Delete this item?');">
            <input type="hidden" name="raw_id" value="<?= $r['raw_id'] ?>">
            <button type="submit" name="delete_raw_item" class="delete-btn">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; $raw_items->free(); ?>
</table>

</body>
</html>
