<?php
session_start();
include("db.php"); // adjust path

// If not logged in OR not admin, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

// ✅ Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM messages WHERE users_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_view_messages.php?deleted=1");
    exit();
}

// ✅ Fetch all messages
$sql = "
SELECT m.*, u.profile_image
FROM messages m
LEFT JOIN users u ON m.users_id = u.users_id
ORDER BY m.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Messages - Maison Sakura Bakery</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        h1 { color: #d63384; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #f5f5f5; }
        tr:nth-child(even) { background: #fafafa; }
        a.delete-btn {
            color: #fff;
            background: #dc3545;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
        }
        a.delete-btn:hover { background: #c82333; }
        .msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .action-buttons {
    display: flex;
    gap: 6px;
}

a.btn {
    color: #fff;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    display: inline-block;
    text-align: center;
    transition: background 0.2s ease;
}

a.delete-btn {
    background: #dc3545;
}
a.delete-btn:hover {
    background: #c82333;
}

a.reply-btn {
    background: #28a745;
}
a.reply-btn:hover {
    background: #218838;
}
.back-button {
    background-color: #d63384;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.back-button:hover {
    background-color: #b52e70;
}

    </style>
</head>
<body>
    <h1>📩 Customer Messages</h1>
 <form action="admin_index.php" method="get" style="margin-bottom: 15px;">
    <button type="submit" class="back-button">⬅️ Back to Dashboard</button>
</form>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="msg">Message deleted successfully ✅</div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>        
            <th>Name</th>
            <th>Email</th>
            <th>Message</th>
            <th>Received At</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['users_id'] ?></td>
           
    
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>
                <a href="mailto:<?= htmlspecialchars($row['email']) ?>">
                    <?= htmlspecialchars($row['email']) ?>
                </a>
            </td>
            <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
            <td><?= $row['created_at'] ?></td>
           <td>
    <div class="action-buttons">
        <a class="btn delete-btn" 
           href="admin_view_messages.php?delete=<?= $row['users_id'] ?>" 
           onclick="return confirm('Are you sure you want to delete this message?');">
           Delete
        </a>
        <a class="btn reply-btn" 
           href="admin_reply.php?email=<?= urlencode($row['email']) ?>&name=<?= urlencode($row['name']) ?>">
           Reply
        </a>
    </div>
</td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
<?php $conn->close(); ?>
