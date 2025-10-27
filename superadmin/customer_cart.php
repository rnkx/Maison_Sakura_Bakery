<?php
session_start();
include("db.php");

// ✅ Ensure only customers can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'] ?? ($_SESSION['id'] ?? null);
$fullname = $_SESSION['fullname'] ?? '';

// ✅ Handle remove item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND users_id = ?");
    $stmt->bind_param("ii", $remove_id, $users_id);
    $stmt->execute();
    header("Location: customer_cart.php");
    exit();
}

// ✅ Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $qty) {
        $qty = max(1, intval($qty));
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND users_id = ?");
        $stmt->bind_param("iii", $qty, $cart_id, $users_id);
        $stmt->execute();
    }
    header("Location: customer_cart.php");
    exit();
}

// ✅ Fetch cart items with discount info
$sql = "
    SELECT 
        c.cart_id, 
        c.quantity, 
        p.name, 
        p.price, 
        p.discount_percent,
        p.image 
    FROM cart c 
    JOIN products p ON c.products_id = p.products_id 
    WHERE c.users_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $users_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// ✅ Calculate totals
$cart_count = 0;
$total_price = 0;
foreach ($cart_items as $item) {
    $cart_count += $item['quantity'];
    $discounted_price = $item['price'] * (1 - ($item['discount_percent'] / 100));
    $total_price += $item['quantity'] * $discounted_price;
}
$cart_items->data_seek(0); // reset pointer
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Cart | Maison Sakura Bakery</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { font-family: Arial, sans-serif; background:#fff8f9; margin:0; padding:0; }
    header, footer {
      background-color: #e75480;
      color: white;
      padding: 15px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    footer {
      flex-shrink: 0;
      background-color: #e75480;
      color: white;
      padding: 20px;
      text-align: center;
      margin-top: 310px;
    }
    nav ul { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
    nav ul li a { text-decoration:none; color:white; font-weight:600; padding:8px 14px; border-radius:5px; }
    nav ul li a:hover { background:rgba(255,255,255,0.2); }
    main { padding:40px 20px; }
    table { width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 10px rgba(0,0,0,0.1); border-radius:10px; overflow:hidden; }
    th, td { padding:15px; text-align:center; border-bottom:1px solid #ddd; }
    th { background:#e75480; color:white; }
    td img { width:80px; height:80px; object-fit:cover; border-radius:10px; }
    .update-btn, .checkout-btn {
      padding:10px 20px; border:none; border-radius:30px; cursor:pointer; font-weight:bold;
    }
    .update-btn { background:#f0ad4e; color:white; }
    .update-btn:hover { background:#ec971f; }
    .checkout-btn { background:#5cb85c; color:white; margin-top:20px; display:inline-block; text-decoration:none; }
    .checkout-btn:hover { background:#449d44; }
    .remove-link { color:red; text-decoration:none; font-weight:bold; }
    .remove-link:hover { text-decoration:underline; }
    /* Special user button */ 
            .user-dropdown > a { 
                background: 
                    linear-gradient(45deg, #ffb6c1, #e75480); 
                color: white !important; 
                font-weight: 700; 
                padding: 10px 20px; 
                border-radius: 50px; 
                box-shadow: 0 4px 10px rgba(231, 84, 128, 0.4); 
                border: 2px solid #fff; cursor: pointer; } 
            /* Dropdown */ 
            .user-dropdown-content 
            { display: none; 
              position: absolute; 
              right: 0; 
              background-color: white; 
              min-width: 180px; 
              border-radius: 8px; 
              box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
              z-index: 1; padding: 5px 0; } 
            .user-dropdown-content a 
            { color: #333 !important; 
              padding: 10px 15px; 
              text-decoration: none; 
              display: block; 
              font-weight: 500; } 
            .user-dropdown-content a:hover { background-color: #f4f4f4; } 
            .user-dropdown:hover .user-dropdown-content { display: block; }
  </style>
</head>
<body>

<!-- Header -->
<header>
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="80" height="80">
  <nav>
    <ul>
      <li><a href="customer_index.php">Home</a></li>
      <li><a href="customer_products.php">Shop</a></li>
      <li><a href="customer_about.php">About Us</a></li>
      <li><a href="customer_contact.php">Contact</a></li>
      <li><a href="customer_cart.php">Cart (<?= $cart_count; ?>)</a></li>
     <li class="user-dropdown"> 
  <a>
      <img src="<?= !empty($_SESSION['profile_image']) 
                        ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) 
                        : 'img/profile.jpg'; ?>" 
                        alt="Profile"  
                        style="width:35px; height:35px; border-radius:50%; object-fit:cover; margin-right:8px; vertical-align:middle;">

    <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>
  <div class="user-dropdown-content">
    <a href="customer_profile.php">View Profile</a>
    <a href="customer_orders.php">My Orders</a>
 
  </div>
</li>
      <li><a href="customer_logout.php">Logout</a></li>
    </ul>
  </nav>
</header>
<main>
<h2 style="text-align:center;">My Shopping Cart</h2>

<?php if ($cart_items->num_rows > 0): ?>
<form method="POST">
<table>
<tr>
    <th>Product</th>
    <th>Image</th>
    <th>Price (RM)</th>
    <th>Quantity</th>
    <th>Subtotal (RM)</th>
    <th>Action</th>
</tr>

<?php $total_price = 0; ?>
<?php while ($row = $cart_items->fetch_assoc()): ?>
<?php
$originalPrice = (float)$row['price'];
$discountPercent = (float)($row['discount_percent'] ?? 0);
$discountedPrice = $discountPercent > 0 ? $originalPrice * (1 - $discountPercent/100) : $originalPrice;
$subtotal = $row['quantity'] * $discountedPrice;
$total_price += $subtotal;

// Image path logic
$imagePath = 'img/no-image.png';
if (!empty($row['image'])) {
    if (file_exists("uploads/" . $row['image'])) $imagePath = "uploads/" . $row['image'];
    elseif (file_exists("admin/uploads/" . $row['image'])) $imagePath = "admin/uploads/" . $row['image'];
    elseif (file_exists("customer/uploads/" . $row['image'])) $imagePath = "customer/uploads/" . $row['image'];
}
?>
<tr>
<td style="font-weight:600;"><?= htmlspecialchars($row['name']) ?></td>
<td><img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($row['name']) ?>"></td>
<td>
<?php if ($discountPercent > 0): ?> <!-- got discount -->
    <span style="text-decoration: line-through; color: gray; font-size:14px;">RM <?= number_format($originalPrice,2) ?></span><br> <!--cancel original price-->
    <span style="color:#e75480; font-weight:bold;">RM <?= number_format($discountedPrice,2) ?> <small style="color:#28a745;">(-<?= $discountPercent ?>%)</small></span> <!-- price after discount-->
<?php else: ?>  <!-- no discount -->
    <span style="color:#e75480; font-weight:bold;">RM <?= number_format($originalPrice,2) ?></span>
<?php endif; ?>
</td>
<td>
<input type="number" name="quantities[<?= $row['cart_id'] ?>]" value="<?= $row['quantity'] ?>" min="1" style="width:70px; text-align:center; border:1px solid #ccc; border-radius:6px;">
</td>
<td style="color:#e25822; font-weight:bold;">RM <?= number_format($subtotal,2) ?></td> <!--calculate subtotal-->
<td>
<a href="customer_cart.php?remove=<?= $row['cart_id'] ?>" onclick="return confirm('Remove this item from your cart?');" style="background-color:#dc3545; color:white; padding:6px 12px; border-radius:6px; text-decoration:none;">✖ Remove</a>
</td>
</tr>
<?php endwhile; ?>

<tr>
<td colspan="4" style="text-align:right; font-weight:bold;">Total:</td>
<td colspan="2" style="font-weight:bold; color:#e75480;">RM <?= number_format($total_price,2) ?></td> <!-- calculate total price-->
</tr>
</table>

<div style="text-align:right; margin-top:15px;">
<button type="submit" name="update_cart" class="update-btn">Update Cart</button>
<a href="customer_checkout.php" class="checkout-btn">Proceed to Checkout</a>
</div>
</form>
<?php else: ?>
<p style="text-align:center; font-size:18px;">Your cart is empty. <a href="customer_products.php" style="color:#e75480;">Shop now</a>.</p>
<?php endif; ?>
</main>
<!-- Footer -->
<footer>
  <p>&copy; <?= date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p>
</footer>

</body>
</html>
