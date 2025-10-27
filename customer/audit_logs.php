<?php
session_start();
include("db.php");

// ✅ Ensure only superadmin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login_superadmin.php");
    exit();
}

// ✅ Fetch audit logs joined with user details (if users_id exists)
$query = "
    SELECT 
        al.log_id,
        COALESCE('System') AS user_name,
        al.action,
        al.table_name,
        al.record_id,
        al.ip_address,
        al.created_at
    FROM audit_logs al
    ORDER BY al.created_at DESC
";

$result = mysqli_query($conn, $query);

// ✅ Error handling
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs | Maison Sakura Bakery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1100px;
        }
        .table {
            background: white;
        }
        .table thead th {
            background-color: #2c3e50;
            color: white;
        }
        h2 {
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">🧾 System Audit Logs</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead>
                    <tr class="text-center">
                        <th>Log ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table Name</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="text-center"><?php echo htmlspecialchars($row['log_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td><?php echo htmlspecialchars($row['table_name']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['record_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center mt-4">
            ⚠️ No audit logs found in the system.
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="superadmin_index.php" class="btn btn-secondary">
            ⬅ Back to Dashboard
        </a>
    </div>
</div>

</body>
</html>
