<?php
session_start();
include("db.php");

// -------------------------
// Admin access guard
// -------------------------
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$msg = "";

// -------------------------
// CONFIG
// -------------------------
$UPLOAD_DIR = __DIR__ . '/uploads/';
$DEFAULT_IMAGE = 'default.png';
$low_stock_threshold = 0;
$near_expiry_days = 2;

// Ensure upload dir exists
if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);

// -------------------------
// UTILITY FUNCTIONS
// -------------------------
function validate_expiry($input) {
    $today = new DateTime(date('Y-m-d'));
    if (empty($input)) return [true, date('Y-m-d', strtotime('+3 days'))];
    $expiry = DateTime::createFromFormat('Y-m-d', $input);
    if (!$expiry) return [false, "⚠️ Invalid expiry date format."];
    if ($expiry < $today) return [false, "⚠️ Expiry date cannot be in the past."];
    return [true, $expiry->format('Y-m-d')];
}

function getRawName($rawId, $conn) {
    $stmt = $conn->prepare("SELECT name FROM raw_items WHERE raw_id = ?");
    $stmt->bind_param("i", $rawId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? "Unknown Item";
}

function uploadImage($file, $prefix = '') {
    if (empty($file['name'])) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    $filename = $prefix . time() . "_" . preg_replace('/[^a-zA-Z0-9_\-\.]/','_',$file['name']);
    return move_uploaded_file($file['tmp_name'], "uploads/$filename") ? $filename : null;
}

// -------------------------
// AUTO RESET EXPIRED STOCK
// -------------------------
$conn->query("UPDATE products SET current_stock = 0 WHERE expiry_date IS NOT NULL AND expiry_date <= CURDATE() AND current_stock > 0");

// -------------------------
// HANDLE FORM SUBMISSIONS
// -------------------------

// -------- ADD PRODUCT --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $discount = floatval($_POST['discount_percent'] ?? 0);
    $max_stock = intval($_POST['max_stock'] ?? 0);
    $initial_stock = intval($_POST['initial_stock'] ?? 0);
    $calories = $weight > 0 ? round($weight * 4.2, 2) : 0;
    $expiry_date = $_POST['expiry_date'] ?? '';
    [$validExpiry, $expiry_result] = validate_expiry($expiry_date);

    if (!$validExpiry) {
        $msg = $expiry_result;
    } else {
        $expiry_date = $expiry_result;
        $imageName = uploadImage($_FILES['image'] ?? [], "product_") ?: $DEFAULT_IMAGE;

        // Build recipe
        $recipe = [];
        foreach ($_POST['recipe'] ?? [] as $rid => $qty) {
            $rid = intval($rid);
            $qty = floatval($qty);
            if ($rid > 0 && $qty > 0) $recipe[$rid] = $qty;
        }

        mysqli_begin_transaction($conn);
        try {
            // Check raw stock BEFORE inserting product
            if (!empty($recipe) && $initial_stock > 0) {
                $insufficient = [];
                foreach ($recipe as $rid => $qty) {
                    $stmt = $conn->prepare("SELECT current_stock FROM raw_items WHERE raw_id = ?");
                    $stmt->bind_param("i", $rid); $stmt->execute(); $stmt->bind_result($stock); $stmt->fetch(); $stmt->close();
                    if ($stock < $qty * $initial_stock) $insufficient[] = getRawName($rid, $conn);
                }
                if (!empty($insufficient)) throw new Exception("❌ Not enough raw materials: ".implode(", ", $insufficient));
            }

            // Insert product
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_percent, weight, image, current_stock, calories, max_stock, date_issued, expiry_date, expiry_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 3)");
            $stmt->bind_param("ssddssiiis", $name,$desc,$price,$discount,$weight,$imageName,$initial_stock,$calories,$max_stock,$expiry_date);
            if (!$stmt->execute()) throw new Exception("Failed to insert product: " . $stmt->error);
            $productId = $stmt->insert_id;
            $stmt->close();

            // Insert recipe
            if (!empty($recipe)) {
                $rStmt = $conn->prepare("INSERT INTO product_recipes (products_id, raw_id, quantity) VALUES (?, ?, ?)");
                foreach ($recipe as $rid => $qty) {
                    $rStmt->bind_param("iid", $productId, $rid, $qty);
                    if (!$rStmt->execute()) throw new Exception("Failed to insert recipe: " . $rStmt->error);
                }
                $rStmt->close();
            }

            // Deduct raw stock
            if (!empty($recipe) && $initial_stock > 0) {
                $upd = $conn->prepare("UPDATE raw_items SET current_stock=current_stock-? WHERE raw_id=?");
                $log = $conn->prepare("INSERT INTO raw_item_stock_history (raw_id, stock_change, reason, date_updated, created_at) VALUES (?, ?, ?, NOW(), NOW())");
                $reason = "Used for new product (ID: $productId)";
                foreach ($recipe as $rid => $qty) {
                    $deduct = $qty * $initial_stock;
                    $upd->bind_param("di", $deduct, $rid); $upd->execute();
                    $neg = -$deduct;
                    $log->bind_param("ids", $rid, $neg, $reason); $log->execute();
                }
                $upd->close();
                $log->close();
            }

            mysqli_commit($conn);
            $msg = "✅ Product added successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            if ($imageName !== $DEFAULT_IMAGE && file_exists("uploads/$imageName")) unlink("uploads/$imageName");
            $msg = $e->getMessage();
        }
    }
}


// -------- UPDATE IMAGE --------
if (isset($_POST['update_image'])) {
    $id = intval($_POST['product_id']);
    $newImage = uploadImage($_FILES['image'] ?? [], "product_");
    if ($newImage) {
        $stmt = $conn->prepare("SELECT image FROM products WHERE products_id=?");
        $stmt->bind_param("i",$id); $stmt->execute(); $stmt->bind_result($oldImage); $stmt->fetch(); $stmt->close();
        if ($oldImage && $oldImage !== $DEFAULT_IMAGE && file_exists("uploads/$oldImage")) unlink("uploads/$oldImage");
        $stmt = $conn->prepare("UPDATE products SET image=? WHERE products_id=?");
        $stmt->bind_param("si",$newImage,$id);
        $msg = $stmt->execute() ? "✅ Product image updated!" : "❌ Failed to update image.";
        $stmt->close();
    } else $msg = "⚠️ Please upload a valid image file.";
}

// -------- UPDATE PRICE / DISCOUNT / MAX STOCK --------
foreach (['price','discount_percent','max_stock'] as $field) {
    $postKey = "update_" . $field;
    if (isset($_POST[$postKey])) {
        $id = intval($_POST['product_id']);
        $val = ($field==='max_stock') ? intval($_POST[$field]) : floatval($_POST[$field]);
        $stmt = $conn->prepare("UPDATE products SET $field=? WHERE products_id=?");
        $stmt->bind_param(($field==='max_stock')?"ii":"di",$val,$id);
        $msg = $stmt->execute() ? "✅ $field updated!" : "❌ Failed to update $field.";
        $stmt->close();
    }
}

// -------- UPDATE STOCK --------
if (isset($_POST['update_stock'])) {
    $id = intval($_POST['product_id']);
    $change = intval($_POST['stock_change']);
    $reason = trim($_POST['reason'] ?? 'Stock update');
    $expiry = $_POST['expiry_date'] ?? date('Y-m-d');

    $stmt = $conn->prepare("SELECT current_stock, max_stock FROM products WHERE products_id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->bind_result($currentStock,$maxStock); $stmt->fetch(); $stmt->close();
    $newStock = max(0, $currentStock + $change);

    if ($change>0 && $currentStock>0) $msg="⚠️ Current stock must be 0 before producing a new batch.";
    elseif ($change>0 && $newStock>$maxStock) $msg="⚠️ Cannot exceed max stock ($maxStock).";
    else {
        if ($change>0) {
            mysqli_begin_transaction($conn);
            try {
                $recipeRes = $conn->prepare("SELECT pr.raw_id, pr.quantity, ri.current_stock, ri.name FROM product_recipes pr INNER JOIN raw_items ri ON pr.raw_id=ri.raw_id WHERE pr.products_id=?");
                $recipeRes->bind_param("i",$id); $recipeRes->execute();
                $result = $recipeRes->get_result(); $recipe = $result->fetch_all(MYSQLI_ASSOC); $recipeRes->close();

                if (empty($recipe)) throw new Exception("❌ No recipe found.");
                $insufficient = [];
                foreach ($recipe as $item) {
                    if ($item['current_stock'] < $item['quantity'] * $change) {
                        $insufficient[] = $item['name'];
                    }
                }
                if (!empty($insufficient)) {
                    throw new Exception("❌ Insufficient raw materials: " . implode(", ", $insufficient));
                }

                $upd = $conn->prepare("UPDATE raw_items SET current_stock=current_stock-? WHERE raw_id=?");
                $logRaw = $conn->prepare("INSERT INTO raw_item_stock_history (raw_id, stock_change, reason, date_updated, created_at) VALUES (?,?,?,NOW(),NOW())");
                foreach ($recipe as $item) {
                    $deduct = $item['quantity']*$change;
                    $upd->bind_param("di",$deduct,$item['raw_id']); $upd->execute();
                    $neg = -$deduct; $logRaw->bind_param("ids",$item['raw_id'],$neg,$reason); $logRaw->execute();
                }
                $upd->close(); $logRaw->close();

                $p = $conn->prepare("UPDATE products SET current_stock=?, expiry_date=?, date_issued=NOW() WHERE products_id=?");
                $p->bind_param("isi",$newStock,$expiry,$id); $p->execute(); $p->close();

                $msg="✅ Successfully produced $change units.";
                mysqli_commit($conn);
            } catch (Exception $e) { mysqli_rollback($conn); $msg=$e->getMessage(); }
        } else {
            $p = $conn->prepare("UPDATE products SET current_stock=?, expiry_date=?, date_issued=NOW() WHERE products_id=?");
            $p->bind_param("isi",$newStock,$expiry,$id); $p->execute(); $p->close();
            $msg="✅ Product stock updated.";
        }
    }
}

// -------- DELETE PRODUCT --------
if (isset($_POST['delete_product'])) {
    $id = intval($_POST['product_id']);
    $sel = $conn->prepare("SELECT image FROM products WHERE products_id=?");
    $sel->bind_param("i",$id); $sel->execute(); $sel->bind_result($img); $sel->fetch(); $sel->close();
    if ($img && $img !== $DEFAULT_IMAGE && file_exists("uploads/$img")) unlink("uploads/$img");
    $del = $conn->prepare("DELETE FROM products WHERE products_id=?");
    $del->bind_param("i",$id);
    $msg = $del->execute() ? "🗑️ Product deleted!" : "❌ Failed to delete product.";
    $del->close();
}

// -------------------------
// FETCH DATA
// -------------------------
$products = $conn->query("SELECT * FROM products ORDER BY products_id DESC");
$raw_list = $conn->query("SELECT * FROM raw_items ORDER BY name ASC");
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Manage Products</title>
<style>
body { font-family: Arial, sans-serif; background:#fff8f5; padding:20px; }
h2 { text-align:center; color:#c56b6b; }
.container { max-width:1200px; margin:0 auto; }
.panel { background:#fff; padding:15px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.04); margin-bottom:20px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #eee; padding:10px; text-align:center; }
th { background:#c56b6b; color:#fff; }
tr:hover { background:#fff2ef; }
input, textarea, select { padding:8px; border-radius:6px; border:1px solid #ccc; width:95%; box-sizing:border-box; }
button { border:none; border-radius:6px; padding:8px 12px; cursor:pointer; }
.success-msg { background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin:10px 0; font-weight:bold; text-align:center; }
.error-msg { background:#f8d7da; color:#721c24; padding:10px; border-radius:8px; margin:10px 0; font-weight:bold; text-align:center; }
img { border-radius:8px; object-fit:cover; }
.small { font-size:0.9em; color:#666; }
.recipe-row { display:flex; gap:10px; align-items:center; margin-bottom:8px; }
.out-of-stock { background:#f8d7da; }
.low-stock { background:#fff3cd; }
</style>
<script>
function confirmDelete() {
    return confirm("⚠️ Are you sure you want to delete this product?");
}
</script>
</head>
<body>
<div class="container">

<?php if($msg): ?>
<div class="<?= (strpos($msg,'❌')===0 || strpos($msg,'⚠️')===0) ? 'error-msg' : 'success-msg' ?>">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<h2>🍞 Product & Stock Management</h2>
<form action="admin_index.php" method="get"><button type="submit">⬅️ Back to Dashboard</button></form>
<br>

<!-- Add Product -->
<div class="panel">
<form method="POST" enctype="multipart/form-data">
<h3>Add New Product</h3>
<div style="display:flex; gap:10px; flex-wrap:wrap;">
<div style="flex:1; min-width:250px;">
    <input type="text" name="name" placeholder="Product Name" required><br><br>
    <textarea name="description" placeholder="Description" required></textarea><br><br>
    <input type="number" step="0.1" name="weight" placeholder="Weight (g)" required><br><br>
    <input type="number" step="0.01" name="price" placeholder="Price (RM)" required><br><br>
</div>
<div style="flex:1; min-width:250px;">
    <input type="number" step="0.01" name="discount_percent" placeholder="Discount (%)"><br><br>
    <input type="number" name="max_stock" placeholder="Maximum Stock" required><br><br>
    <input type="number" name="initial_stock" placeholder="Initial Stock" required><br><br>
    <input type="date" name="expiry_date" min="<?= date('Y-m-d', strtotime('+3 days')) ?>"><br><br>
    <input type="file" name="image" accept="image/*">
</div>
</div>

<h4>Recipe (Raw Items per product)</h4>
<div style="max-height:200px; overflow:auto; border:1px solid #eee; padding:10px; border-radius:8px;">
<?php while($raw = mysqli_fetch_assoc($raw_list)): ?>
<div class="recipe-row">
    <div style="flex:1; text-align:left;"><strong><?= htmlspecialchars($raw['name']) ?></strong> (<?= htmlspecialchars($raw['unit']) ?>)</div>
    <div style="width:150px;"><input type="number" step="0.01" name="recipe[<?= $raw['raw_id'] ?>]" placeholder="qty/unit"></div>
</div>
<?php endwhile; ?>
</div><br>
<button type="submit" name="add_product" style="background:#c56b6b;color:#fff;">➕ Add Product</button>
</form>
</div>

<!-- Existing Products -->
<div class="panel">
<h3>Existing Products</h3>
<table>
<tr>
<th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Discount</th><th>Price After Discount</th>
<th>Stock</th><th>Max Stock</th><th>Date Issued</th><th>Expiry</th><th>Status</th><th>Actions</th>
</tr>
<?php while($p = mysqli_fetch_assoc($products)):
$today = new DateTime();
$expiryDate = !empty($p['expiry_date']) ? new DateTime($p['expiry_date']) : null;
$interval = $expiryDate ? $today->diff($expiryDate)->days : null;
$out_of_stock = $p['current_stock']==0;
$low_stock = $p['current_stock'] <= $low_stock_threshold && !$out_of_stock;
$near_expiry = $expiryDate && $expiryDate >= $today && $interval <= $near_expiry_days;
$expired = $expiryDate && $expiryDate < $today;
$row_class = $out_of_stock ? 'out-of-stock' : (($low_stock||$near_expiry)?'low-stock':'');
?>
<tr class="<?= $row_class ?>">
<td><?= $p['products_id'] ?></td>
<td>
<?php if($p['image']): ?><img src="uploads/<?= htmlspecialchars($p['image']) ?>" width="80"><br>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<input type="file" name="image" accept="image/*"><br><br>
<button type="submit" name="update_image" class="small">Update</button>
</form><?php endif; ?>
</td>
<td style="text-align:left;"><strong><?= htmlspecialchars($p['name']) ?></strong><br>
<span class="small"><?= htmlspecialchars($p['description']) ?></span>
<?php if($out_of_stock): ?><span title="Out of Stock">🚫</span>
<?php elseif($low_stock || $near_expiry): ?><span title="Low stock / near expiry">⚠️</span><?php endif; ?>
</td>
<td>
<form method="POST">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<input type="number" step="0.01" name="price" value="<?= $p['price'] ?>"><br><br>
<button type="submit" name="update_price">Update</button>
</form>
</td>
<td>
<form method="POST">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<input type="number" step="0.01" name="discount_percent" value="<?= $p['discount_percent'] ?>"><br><br>
<button type="submit" name="update_discount">Update</button>
</form>
</td>
<td>RM <?= number_format($p['price']*(1-$p['discount_percent']/100),2) ?></td>
<td>
<strong><?= $p['current_stock'] ?></strong>
<form method="POST">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<input type="number" name="stock_change" placeholder="+ produce / - adjust"><br><br>
<input type="text" name="reason" placeholder="Reason"><br><br>
<input type="date" name="expiry_date" min="<?= date('Y-m-d', strtotime('+3 days')) ?>" value="<?= $p['expiry_date'] ?>"><br><br>
<button type="submit" name="update_stock" style="background:#e8a0a0;color:#fff;">Update Stock</button>
</form>
</td>
<td>
<form method="POST">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<input type="number" name="max_stock" value="<?= $p['max_stock'] ?>"><br><br>
<button type="submit" name="update_max_stock">Update</button>
</form>
</td>
<td><?= $p['date_issued'] ?></td>
<td><?= $p['expiry_date'] ?></td>
<td>
<?php if($expired): ?>Expired ❌
<?php elseif($out_of_stock): ?>Out of Stock 🚫
<?php elseif($near_expiry): ?>Expiring Soon ⚠️
<?php elseif($low_stock): ?>Low Stock ⚠️
<?php else: ?>Available ✅
<?php endif; ?>
</td>
<td>
<form method="POST" onsubmit="return confirmDelete();">
<input type="hidden" name="product_id" value="<?= $p['products_id'] ?>">
<button type="submit" name="delete_product">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>
</body>
</html>

