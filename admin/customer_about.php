<?php session_start(); 
include("db.php");
// If not logged in OR not customer, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
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
    .choose-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 columns */
    gap: 20px; /* space between items */
    text-align: center;
    max-width: 800px;
    margin: 0 auto; /* center align */
  }

  .choose-grid div {
    background: #fff;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column; /* vertical arrangement inside each cell */
    align-items: center;
    justify-content: center;
  }

  .choose-grid img {
    width: 80px;
    height: 80px;
    margin-bottom: 10px;
  }
        </style> 
    </head>
    <body> 
    
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

    <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>
  <div class="user-dropdown-content">
    <a href="customer_profile.php">View Profile</a>
    <a href="customer_orders.php">My Orders</a>
   
  </div>
</li>

                            <li><a href="customer_logout.php">Logout</a></li> </ul> </nav> </header> 

        <br/><!-- comment -->
        <br/><!-- comment -->
<main style="max-width: 1200px; margin: 2rem auto; padding: 0 20px; font-family: Arial, sans-serif; line-height: 1.8;">
  <!-- About Us Section -->
  <section class="about-us" style="margin-bottom: 4rem;">
    <h1 style="color: black; text-align: center; margin-bottom: 2rem;">About Us</h1>
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 2rem;">
      <div style="flex: 1; min-width: 300px;">
        <p>
          At <strong>Maison Sakura Bakery</strong>, we bring the sweetness of Japan to your table.  
          From fluffy cheesecakes inspired by Japanese delicacies to buttery, freshly baked croissants,
          our passion lies in creating delightful treats made with love, care, and the finest ingredients.
        </p>
        <p>
          We are devoted to crafting a wide variety of fresh, high-quality breads, pastries, and cakes that 
          embody both tradition and innovation. Every product is made by hand with meticulous attention to detail,
          ensuring that each bite is not only delicious but also a reflection of our commitment to excellence.  
        </p>
        <p>
          More than just a bakery, our mission is to bring warmth, joy, and unforgettable moments to our community.  
          Whether you’re stopping by for a morning pastry, ordering a custom cake for a celebration, or simply treating 
          yourself after a long day, we strive to make every visit feel special.  
        </p>
      </div>
      <div style="flex: 1; min-width: 300px; text-align: center; color: black;">
        <img src="img/about.jpg" alt="Our Bakery" style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
      </div>
    </div>
  </section>

  <!-- Our Story Section -->
  <section class="our-story" style="margin-bottom: 4rem;">
    <h2 style="color: black; text-align: center; margin-bottom: 2rem;">Our Story</h2>
    <div style="display: flex; flex-wrap: wrap-reverse; align-items: center; gap: 2rem;">
      <div style="flex: 1; min-width: 300px; text-align: center;">
        <img src="img/bakery.jpg" alt="Bakery Story" style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
      </div>
      <div style="flex: 1; min-width: 300px;">
        <p>
          Established in 2025, we began as a humble neighborhood bakery with a heartfelt vision: to share the warmth of 
          traditional recipes while embracing the creativity of modern flavors. What started as a small family-run shop quickly 
          became a beloved gathering place for our community, where the aroma of freshly baked bread and pastries filled the air 
          each morning.
        </p>
        <p>
          Over the years, our bakery has grown, not just in size but also in spirit. With every loaf, cake, and pastry, 
          we continue to carry forward our promise of quality, freshness, and authenticity. Each product is made with care, 
          using only the finest ingredients and time-honored techniques, blended with a touch of innovation to surprise and delight 
          our customers. 
        </p>
        <p>
          As our team expanded, so did our passion for creating meaningful experiences—whether it’s preparing custom cakes for 
          life’s most memorable celebrations or simply offering a warm smile to brighten someone’s day. No matter how much we grow,
          our mission remains the same: to bring joy, comfort, and connection through every bite we create.
        </p>
      </div>
    </div>
  </section>

  <!-- Why Choose Us Section -->
  <section class="why-choose-us">
    <h2 style="color: black; text-align: center; margin-bottom: 2rem;">Why Choose Us?</h2>
    <div class="choose-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; text-align: center;">
      <div>
    <img src="https://img.icons8.com/fluency/96/bread.png" alt="Fresh Bread">
    <p>Freshly baked daily with the finest ingredients.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/cupcake.png" alt="Cupcakes">
    <p>A wide selection of sweet and savory delights.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/handshake.png" alt="Friendly Service">
    <p>Friendly service and cozy atmosphere.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/birthday-cake.png" alt="Custom Cakes">
    <p>Custom cakes and catering for all occasions.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/organic-food.png" alt="Organic Ingredients">
    <p>Made with natural and organic ingredients for healthier choices.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/chef-hat.png" alt="Skilled Bakers">
    <p>Crafted by skilled bakers with passion and expertise.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/clock.png" alt="On-Time Service">
    <p>Punctual service and timely delivery for every order.</p>
  </div>
  <div>
    <img src="https://img.icons8.com/fluency/96/happy.png" alt="Customer Satisfaction">
    <p>Dedicated to customer satisfaction and memorable experiences.</p>
  </div>
        <div>
  <img src="https://img.icons8.com/fluency/96/medal.png" alt="Award Winning">
  <p>Award-winning recipes and recognized for quality excellence.</p>
</div>
    </div>
  </section>

</main>

<footer> 
  <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p> 
  <p> 
    <a href="customer_about.php">About</a> | 
    <a href="customer_contact.php">Contact</a> | 
    <a href="customer_privacy_policy.php">Privacy Policy</a> | 
    <a href="customer_terms&conditions.php">Terms & Conditions</a> 
  </p> 
</footer> 

</body>
</html>