<?php
session_start();

// If not logged in OR not superadmin, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login_superadmin.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Superadmin Dashboard - Maison Sakura Bakery</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: #8B0000;
            color: white;
            padding: 15px;
            text-align: center;
        }
         footer {
            background-color: #8B0000;
            color: white;
            padding: 15px;
            text-align: center;
        }


        nav {
            background: #f2f2f2;
            padding: 10px;
            text-align: center;
        }

        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: #8B0000;
            font-weight: bold;
        }

        main {
            flex: 1;
            padding: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
  .card a {
          display: inline-block;
          margin-top: 10px;
          padding: 8px 16px;
          background-color: #8B0000;
          color: white;
          border-radius: 6px;
          text-decoration: none;
          font-weight: bold;
      }
        .card h3 {
            margin: 0 0 10px;
            color: #8B0000;
        }

       

        .logout-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #8B0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .logout-btn:hover {
            background: #a52a2a;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <h1>Maison Sakura Bakery - Superadmin Panel</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>👑</p>
    </header>

    <!-- Navigation -->
    <nav>
        <a href="manage_admin.php">Manage Admins</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="reports.php">Reports</a>
        <a href="superadmin_logout.php" class="logout-btn">Logout</a>
    </nav>

    <!-- Main Dashboard -->
    <main>
        <h2 align="center">Superadmin Dashboard</h2>
        <p align="center">Use the tools below to manage the entire Maison Sakura Bakery system.</p>

       <div class="dashboard">
    <!-- Manage Admins -->
    <div class="card">
      <h3>👥 Manage Admins</h3>
      <p>Create, update, or remove admin accounts.</p>
      <a href="manage_admin.php" class="btn-go">Go</a>
    </div>

    <!-- Audit Logs -->
    <div class="card">
      <h3>📜 Audit Logs</h3>
      <p>Track all changes made by superadmins.</p>
      <a href="audit_logs.php" class="btn-go">Go</a>
    </div>

    <!-- Reports -->
    <div class="card">
      <h3>📊 Reports</h3>
      <p>View sales, revenue, and performance analytics.</p>
      <a href="reports.php" class="btn-go">Go</a>
    </div>

  </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery | Superadmin Panel</p>
    </footer>
</body>
</html>
