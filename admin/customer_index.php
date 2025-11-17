<?php session_start(); 
include("db.php");
// If not logged in OR not customer, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}
// Capture search keyword & category
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build query with conditions
if (!empty($search) && !empty($category)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND (name LIKE ? OR description LIKE ?)");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("sss", $category, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($category)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM products ORDER BY products_id DESC");
}


?> 
<!DOCTYPE html> 
<html lang="en"> 
    <head> <meta charset="UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <title>Maison Sakura Bakery</title> <link rel="stylesheet" href="css/style.css"> 
        <style>
            body { 
                font-family: Arial, sans-serif; 
                   margin: 0; 
                   padding: 0; 
                   background-color: #fff8f9; 
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
            margin-top: 297px; /*adjust the footer and should always sticks to the bottom*/
        }
        
        footer a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
 
            header h1 { 
                font-size: 22px; 
                margin: 0; 
                
            } nav ul { 
                list-style: none; margin: 0; padding: 0; display: flex; align-items: center; gap: 20px; } 
            nav ul li { position: relative; }
            nav ul li a { 
                text-decoration: none; 
                color: white; font-weight: 600; 
                padding: 8px 14px; 
                border-radius: 5px; 
                transition: background 0.3s; } 
            nav ul li a:hover { 
                background-color: rgba(255,255,255,0.2); 
            } /* Special user button */ 
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
            main { text-align: center; } /* Hero Section */ 
            .hero { background: url('img/bakery-banner.jpg') no-repeat center center/cover; 
                   color: white; padding: 100px 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.5); }
            .hero h2 { font-size: 40px; margin-bottom: 10px; }
            .hero p { font-size: 18px; margin-bottom: 20px; } 
            .hero a { background: #e75480; color: white; padding: 12px 25px; font-weight: bold; border-radius: 30px; 
                     text-decoration: none; transition: background 0.3s; } .hero a:hover { background: #c73c65; } 
                     /* Featured Products */
                     .products { padding: 50px 20px; }
                     .products h3 { color: #e75480; margin-bottom: 30px; font-size: 28px; } 
                     .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
                     .product { background: white; border-radius: 10px; 
                               box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 15px; text-align: center; } 
                     .product img { max-width: 100%; border-radius: 10px; } .product h4 { margin: 15px 0 10px; color: #333; } 
                     .product p { color: #e75480; font-weight: bold; }     
/* Welcome Section */
    .welcome-section {
      text-align: center;
      max-width: 700px;
      margin: 0 auto 40px;
    }
    .welcome-section h3 { color: black; margin-bottom: 15px; }
    .welcome-section p { color: black; margin-bottom: 20px; }

  .read-more-btn {
  display: inline-block;
  padding: 12px 25px;
  background-color: #e75480;
  color: #fff;
  text-decoration: none;
  border-radius: 12px;
  font-weight: bold;
  transition: 0.3s;
}

.read-more-btn:hover {
  background-color: #c73c65;
}

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
    .price small {
  font-size: 12px;
  margin-left: 4px;
}

        </style> 
    </head>
    <body> 
        <!-- Header -->
        <header> 
            <img src="img/logo.jpg" alt="Maison Sakura Logo" width="100" height="100" class="me-3">
           
            <nav> <ul> <li><a href="customer_index.php">Home</a></li>
                    <li><a href="customer_products.php">Shop</a></li>
                    <li><a href="customer_about.php">About Us</a></li>
                    <li><a href="customer_contact.php">Contact</a></li> 
                  <li class="user-dropdown"> 
  <a>
   <img src="<?= !empty($_SESSION['profile_image']) 
                        ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) 
                        : 'img/profile.jpg'; ?>" 
                        alt="Profile"  
                        style="width:35px; height:35px; border-radius:50%; object-fit:cover; margin-right:8px; vertical-align:middle;">

    <b>Hi, <?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>
  <div class="user-dropdown-content">
    <a href="customer_profile.php">View Profile</a>
    <a href="customer_orders.php">My Orders</a>
   
  </div>
</li>

                            <li><a href="customer_logout.php">Logout</a></li> </ul> </nav> </header> <!-- Hero Section -->
                            <main> <div class="hero"> <h2>Freshly Baked Happiness</h2>
                                    <p>Delicious bread, cakes, and pastries made with love every day.</p>
                                    <br/> 
                                    <a href="customer_products.php" class="shop-btn">Shop Now</a> </div> </main>
                            
                            <!-- Welcome Section -->
  <section class="products"> <div class="product-grid">
          <div class="product">
    <h3 align="center" style="color:black;">Welcome to Maison Sakura Bakery</h3>  
    <p style="color:black;">
      At Maison Sakura Bakery, we bring the sweetness of Japan to your table.  
      From fluffy cheesecakes to freshly baked croissants, our passion is to  
      create delightful treats made with love and the finest ingredients.
    </p>
    <br/>
    <a href="customer_about.php" class="shop-btn" style="background: #e75480; color: white; padding: 12px 25px; font-weight: bold; border-radius: 30px; 
                     text-decoration: none; transition: background 0.3s;">Read More</a>
                     <br/>
                     <br/>
    </div>
  </div>
  </section>
              <!-- Product Section -->
<main>
  <h2 style="text-align:center;">Our Products</h2>
  <div class="products-grid">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          // ✅ Find correct image path
          $imagePath = 'img/no-image.png';
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

           <p style="color:#e25822;">⚖️ <?= htmlspecialchars($row['weight']); ?> g</p>
          <p style="color:#e25822;">🍽️ <?= htmlspecialchars($row['calories']); ?> kcal</p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No products available.</p>
    <?php endif; ?>
  </div>
</main>

                         
                         <section class="products">
                                 <div class="product">
                                     <h3 align="center" style="color:black">Operating Hours</h3><div class="product-grid">
        <p align="center" style="color:black">
            Monday – Friday: 10:00 AM – 10:00 PM <br/>
            <br/>
            Saturday – Sunday: 9:00 AM – 10:00 PM <br/>
            <br/>
            Public Holidays: Open as usual
        </p>
        </div>
                                 </div>
    </section>
                             <section class="products">
                                 <div class="product">
                                     <h3 align="center" style="color:black">Address</h3><div class="product-grid">
        <p align="center" style="color:black">
            42A Jalan Southbay 4, 11960 Batu Maung, Pulau Pinang</p>

                                 </div>
    </section>

            <footer> <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p> <p> 
                                    <a href="customer_about.php" style="color: white;">About</a> | 
                                    <a href="customer_contact.php" style="color: white;">Contact</a> | 
                                    <a href="customer_privacy_policy.php" style="color: white;">Privacy Policy</a> | 
                                    <a href="customer_terms&conditions.php" style="color: white;">Terms & Conditions</a> </p> 
                            </footer> 
                            
    </body> 
</html>




