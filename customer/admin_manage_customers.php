<?php
session_start();
include("db.php");

// ✅ Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// ✅ Fetch all customers (assuming role = 'customer')
$query = "SELECT * FROM users WHERE role = 'customer'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Maison Sakura Bakery</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff8fc;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #d63384;
            text-align: center;
            margin-bottom: 15px;
        }

        /* ✅ Back Button */
        .back-button {
            background-color: #d63384;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: block;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background-color: #b52e70;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #d63384;
            color: white;
        }

        tr:hover {
            background-color: #f9e3f0;
        }

        .action-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin: 2px;
            text-decoration: none;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #a71d2a;
        }
    </style>
</head>
<body>
<?php if (isset($_GET['msg'])): ?>
    <div style="background-color:#d4edda; color:#155724; padding:10px; border-radius:6px; margin-bottom:15px;">
        <?= htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>
    <h1>👩‍🍳 Manage Customers</h1>

    <!-- ✅ Back to Dashboard -->
    <form action="admin_index.php" method="get">
        <button type="submit" class="back-button">⬅️ Back to Dashboard</button>
    </form>

    <!-- ✅ Customers Table -->
    <table>
        <tr>
            <th>ID</th>
            <th>Profile Image</th> <!-- 🆕 Add this -->
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>

            <th>Actions</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['users_id']; ?></td>
                      <!-- 🖼️ Display profile image -->
                <td>
                    <?php if (!empty($row['profile_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['profile_image']); ?>" 
                             alt="Profile" 
                             width="60" height="60" 
                             style="border-radius:50%; object-fit:cover;">
                    <?php else: ?>
                        <img src="img/profile.jpg" 
                             alt="Default Profile" 
                             width="60" height="60" 
                             style="border-radius:50%; object-fit:cover;">
                    <?php endif; ?>
                </td>
                    <td><?= htmlspecialchars($row['fullname']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['phone']); ?></td>
                  <td>
    <a href="admin_view_customer_orders.php?users_id=<?= $row['users_id']; ?>" 
       class="action-btn" 
       style="background-color:#28a745;">🛒 View Orders</a>
    <a href="admin_delete_customer.php?users_id=<?= $row['users_id']; ?>" 
       class="action-btn delete-btn"
       onclick="return confirm('Are you sure you want to delete this customer?');">🗑️ Delete</a>
</td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No customers found.</td></tr>
        <?php endif; ?>

    </table>

</body>
</html>
