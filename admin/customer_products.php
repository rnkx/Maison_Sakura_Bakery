<?php
session_start();
include("db.php");

// If not logged in OR not customer, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

// Capture search keyword & category
$search = $_GET['search'] ?? '';



// Get customer_id from session 
$users_id = $_SESSION['users_id'] ?? ($_SESSION['id'] ?? null);
$fullname = $_SESSION['fullname'] ?? '';

// Build query with conditions

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($name)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM products ORDER BY products_id DESC");
}

$cart_count = 0;
if (isset($_SESSION['users_id'])) {
    $uid = $_SESSION['users_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE users_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maison Sakura Bakery | Shop</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin:0;
      padding:0;
      background-color:#fff8f9;
    }
    header, footer {
      background-color: #e75480;
      color: white;
      padding: 15px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  /* Footer styling - this will stay at bottom */
        footer {
            flex-shrink: 0;
            background-color: #e75480;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 500px; /*adjust the footer and should always sticks to the bottom*/
        }
        
        footer a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
 
    nav ul {
      list-style: none; margin: 0; padding: 0; display: flex; gap: 20px;
    }
    nav ul li a {
      text-decoration: none; color: white; font-weight: 600;
      padding: 8px 14px; border-radius: 5px;
    }
    nav ul li a:hover { background-color: rgba(255,255,255,0.2); }

    main { padding: 40px 20px; }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    .product-card {
      background:white; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
      overflow:hidden; text-align:center; padding:15px;
    }
    .product-card img {
      max-width:100%; height:200px; object-fit:cover; border-radius:10px;
    }
    .product-card h4 { margin:15px 0 8px; color:#333; }
    .product-card p { font-size:14px; color:#555; min-height:50px; }
    .price { color:#e75480; font-weight:bold; margin:10px 0; }

    .order-btn {
      display:inline-block; padding:10px 20px; background:#e75480;
      color:white; font-weight:bold; border-radius:30px; text-decoration:none;
      transition:0.3s;
    }
    .order-btn:hover { background:#c73c65; }
    .search-bar {
      display: flex;
      justify-content: center;
      margin: 20px 0;
      gap: 10px;
      flex-wrap: wrap;
    }
    .search-bar input, .search-bar select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 20px;
    }
    .search-bar button {
      padding: 10px 20px;
      border: none;
      background: #e75480;
      color: white;
      border-radius: 20px;
      cursor: pointer;
    }
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
            .price small {
  font-size: 12px;
  margin-left: 4px;
}

  </style>
</head>
<body>

<!-- Header -->
<header>
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="80" height="80">
   <!-- Search + Filter -->
  <div class="search-bar">
    <form action="customer_products.php" method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
      <input type="text" name="search" placeholder="Search for cakes, bread, pastries..." 
             value="<?php echo htmlspecialchars($search); ?>">
    
      <button type="submit">Search</button>
    </form>
  </div>
  <nav>
    <ul>
      <li><a href="customer_index.php">Home</a></li>
      <li><a href="customer_products.php">Shop</a></li>
      <li><a href="customer_about.php">About Us</a></li>
      <li><a href="customer_contact.php">Contact</a></li>
       <!-- Cart Page -->
    <li>
      <a href="customer_cart.php">
        Cart (<?php echo $cart_count; ?>)
      </a>
    </li>
       <li class="user-dropdown"> 
  <a>
   <img src="<?php echo !empty($_SESSION['profile_image']) ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) : 'img/profile.jpg'; ?>" 
     alt="Profile" 
     style="width:35px; height:35px; border-radius:50%; object-fit:cover; margin-right:8px; vertical-align:middle;">

    <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>
  <div class="user-dropdown-content">
    <a href="customer_profile.php">View Profile</a>
    <a href="customer_orders.php">My Orders</a>

  </div>
</li>

                            <li><a href="customer_logout.php">Logout</a></li> </ul> </nav> </header>
  
<main>
  <h2 style="text-align:center; margin-top:40px;">Our Products</h2>

  <div class="products-grid">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          // ✅ Determine the correct image path
          $imagePath = 'img/no-image.png'; // Default placeholder
          if (!empty($row['image'])) {
              if (file_exists("uploads/" . $row['image'])) {
                  $imagePath = "uploads/" . $row['image'];
              } elseif (file_exists("admin/uploads/" . $row['image'])) {
                  $imagePath = "admin/uploads/" . $row['image'];
              } elseif (file_exists("customer/uploads/" . $row['image'])) {
                  $imagePath = "customer/uploads/" . $row['image'];
              }
          }
        ?>

        <div class="product-card">
          <img src="<?= htmlspecialchars($imagePath); ?>" alt="<?= htmlspecialchars($row['name']); ?>">
          
          <h4><?= htmlspecialchars($row['name']); ?></h4>
          <h5><?= htmlspecialchars($row['description']); ?></h5>

          <p style="color:#e25822; margin:5px 0;">⚖️ <?= htmlspecialchars($row['weight']); ?> g</p>
          <p style="color:#e25822; margin:5px 0;">🍽️ <?= htmlspecialchars($row['calories']); ?> kcal</p>

      <?php
$originalPrice = (float)$row['price'];
$discountPercent = isset($row['discount_percent']) ? (float)$row['discount_percent'] : 0;

// Calculate discounted price
if ($discountPercent > 0) {
    $discountedPrice = $originalPrice - ($originalPrice * $discountPercent / 100);
} else {
    $discountedPrice = $originalPrice;
}
?>
<p class="price">
    <?php if ($discountPercent > 0): ?>
        <span style="text-decoration: line-through; color: gray; font-size:14px;">
            RM <?= number_format($originalPrice, 2); ?>
        </span><br>
        <span style="color:#e75480; font-weight:bold;">
            RM <?= number_format($discountedPrice, 2); ?> 
            <small style="color:#28a745;">(-<?= $discountPercent; ?>%)</small>
        </span>
    <?php else: ?>
        <span style="color:#e75480; font-weight:bold;">
            RM <?= number_format($originalPrice, 2); ?>
        </span>
    <?php endif; ?>
</p>

          <a href="javascript:void(0);" 
             class="order-btn" 
             onclick="addToCart(<?= (int)$row['products_id']; ?>)">
             🛒 Add to Cart
          </a>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No products available at the moment.</p>
    <?php endif; ?>
  </div>
</main>


<!-- Footer -->
<footer>
  <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p>
  <p>
    <a href="customer_about.php" style="color:white;">About</a> |
    <a href="customer_contact.php" style="color:white;">Contact</a> |
    <a href="customer_privacy_policy.php" style="color:white;">Privacy Policy</a> |
    <a href="customer_terms&conditions.php" style="color:white;">Terms & Conditions</a>
  </p>
</footer>

<!-- JS for Cart -->
<script>
function addToCart(products_id) {
  fetch('customer_add_to_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'products_id=' + products_id
  })
  .then(res => res.text())
  .then(msg => alert(msg))
  .catch(err => alert("Error: " + err));
}
</script>
</body>
</html>

