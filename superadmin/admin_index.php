
<?php
session_start();


// If not logged in OR not admin, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maison Sakura Bakery - Admin Panel</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
      /* Reset + Flex Layout for Sticky Footer */
      html, body {
          height: 100%;
          margin: 0;
          padding: 0;
      }
      body {
          display: flex;
          flex-direction: column;
          min-height: 100vh;
          font-family: Arial, sans-serif;
          background-color: #fff8f9;
      }
      main {
          flex: 1;
          padding: 30px;
      }

      /* Header */
      header {
          background-color: #e75480;
          color: white;
          padding: 15px 40px;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      header h1 {
          font-size: 22px;
          margin: 0;
      }

    nav ul { 
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
            } 

      /* User button */
      .user-dropdown > a {
          background: linear-gradient(45deg, #ffb6c1, #e75480);
          color: white !important;
          font-weight: 700;
          padding: 10px 20px;
          border-radius: 50px;
          box-shadow: 0 4px 10px rgba(231, 84, 128, 0.4);
          border: 2px solid #fff;
          cursor: pointer;
      }
      .user-dropdown-content {
          display: none;
          position: absolute;
          right: 0;
          background-color: white;
          min-width: 180px;
          border-radius: 8px;
          box-shadow: 0 4px 10px rgba(0,0,0,0.15);
          z-index: 1;
          padding: 5px 0;
      }
      .user-dropdown-content a {
          color: #333 !important;
          padding: 10px 15px;
          text-decoration: none;
          display: block;
          font-weight: 500;
      }
      .user-dropdown-content a:hover {
          background-color: #f4f4f4;
      }
      .user-dropdown:hover .user-dropdown-content {
          display: block;
      }

      /* Dashboard Cards */
      .dashboard {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
          gap: 20px;
          margin-top: 30px;
      }
      .card {
          background: white;
          border-radius: 12px;
          padding: 20px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
          transition: transform 0.2s ease;
          text-align: center;
      }
      .card:hover {
          transform: translateY(-5px);
      }
      .card h3 {
          margin: 10px 0;
          font-size: 20px;
          color: #e75480;
      }
      .card p {
          margin: 5px 0;
          font-size: 16px;
          color: #444;
      }
      .card a {
          display: inline-block;
          margin-top: 10px;
          padding: 8px 16px;
          background-color: #e75480;
          color: white;
          border-radius: 6px;
          text-decoration: none;
          font-weight: bold;
      }
      .card a:hover {
          background-color: #c73c65;
      }

      /* Footer */
      footer {
          background-color: #e75480;
          color: white;
          padding: 15px 40px;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      footer a {
          color: white;
          text-decoration: none;
          margin: 0 5px;
      }
      footer a:hover {
          text-decoration: underline;
      }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
       <img src="img/logo.jpg" alt="Maison Sakura Logo" width="100" height="100" class="me-3">
      <h1>Admin Dashboard</h1>
     
      <nav>
          <ul>

          <li>      <a href="admin_manage_orders.php">Orders</a>  </li>  
            <li>    <a href="admin_manage_products.php">Products</a>  </li>  
            <li>    <a href="admin_manage_customers.php">Customers</a>  </li> 
                          <li><a href="admin_manage_raw_items.php">Raw Items</a></li>
            <li>    <a href="admin_view_messages.php">Messages</a>
      </li>  
                <li class="user-dropdown"> 
  <a href="admin_profile.php">
    <img src="<?= !empty($_SESSION['profile_image']) 
                        ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) 
                        : 'img/profile.jpg'; ?>" 
                        alt="Profile"  
                        style="width:35px; height:35px; border-radius:50%; object-fit:cover; margin-right:8px; vertical-align:middle;">
    <b>Hi, <?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>

</li>
              <li><a href="admin_logout.php">Logout</a></li>
          </ul>
      </nav>
  </header>

  <!-- Main Content -->
  <main>
      <h2 align="center">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></h2>
      <p align="center">Use the dashboard below to manage the bakery system effectively.</p>
      <br/><!-- comment -->
      <br/>
      <h2 align="center">Quick Access</h2>
 <div class="dashboard">

    <!-- Orders -->
    <div class="card">
        <h2>📦 Orders</h2>
        <p>View and process customer orders.</p>
        <a href="admin_manage_orders.php">Go</a>
    </div>

    <!-- Bakery Products -->
    <div class="card">
        <h2>🍞 Bakery Products</h2>
        <p>Track stock, expiry, and bake new products.</p>
        <a href="admin_manage_products.php">Go</a>
    </div>

    <!-- Raw Items -->
    <div class="card">
        <h2>🧂 Raw Items</h2>
        <p>Manage flour, sugar, eggs, and other ingredients. Restock with new expiry dates.</p>
        <a href="admin_manage_raw_items.php">Go</a>
    </div>

    <!-- Customers -->
    <div class="card">
        <h2>👥 Customers</h2>
        <p>Manage registered customers.</p>
        <a href="admin_manage_customers.php">Go</a>
    </div>

    <!-- Messages -->
    <div class="card">
        <h2>✉️ Messages</h2>
        <p>View customer inquiries & feedback.</p>
        <a href="admin_view_messages.php">Go</a>
    </div>

</div>
  </main>

  <!-- Footer -->
  <footer>
      <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. Admin Panel.</p>
      <p>

          <a href="admin_privacy_policy.php">Privacy Policy</a> |
          <a href="admin_terms&conditions.php">Terms & Conditions</a>
      </p>
  </footer>

</body>
</html>