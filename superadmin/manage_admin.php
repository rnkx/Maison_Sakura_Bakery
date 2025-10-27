<?php
session_start();
include("db.php");

// ✅ Restrict access to superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login_superadmin.php");
    exit();
}

// ✅ Helper function to show session messages
function flash_message() {
    if (isset($_SESSION['message'])) {
        echo "<div style='background:#d4edda;color:#155724;padding:12px;border-radius:5px;
                    margin:15px auto;width:90%;max-width:600px;text-align:center;font-weight:bold;'>
                {$_SESSION['message']}
              </div>";
        unset($_SESSION['message']);
    }
}

// ✅ Function to record audit logs safely
function record_audit($conn, $action, $table, $record_id) {
  
     $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $action = mysqli_real_escape_string($conn, $action);
    $table = mysqli_real_escape_string($conn, $table);
       $record_id = intval($record_id);

   $sql = "INSERT INTO audit_logs (action, table_name, record_id, ip_address, created_at)
            VALUES ('$action', '$table', $record_id, '$ip', NOW())";

   
    if (!mysqli_query($conn, $sql)) {
        error_log("Audit Log Insert Failed: " . mysqli_error($conn) . " | SQL: $sql");
    }
}

/* ======================================================
   ✅ ADD NEW ADMIN
   ====================================================== */
if (isset($_POST['add_admin'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); // no hash (as requested)
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = 'admin';
    $created_at = date('Y-m-d H:i:s');
    $profile_image = '';

    // ✅ Handle profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $profile_image = $file_name;
        }
    }

    // ✅ Insert new admin
    $insert_sql = "INSERT INTO users (fullname, email, password, phone, role, profile_image, created_at)
                   VALUES ('$fullname', '$email', '$password', '$phone', '$role', '$profile_image', '$created_at')";

    if (mysqli_query($conn, $insert_sql)) {
        $new_admin_id = mysqli_insert_id($conn);
        record_audit($conn, "Added new admin: $fullname", 'users', $new_admin_id);
        $_SESSION['message'] = "✅ New admin added successfully.";
    } else {
        $_SESSION['message'] = "❌ Error adding admin: " . mysqli_error($conn);
    }

    header("Location: manage_admin.php");
    exit();
}

/* ======================================================
   ✅ UPDATE ADMIN
   ====================================================== */
if (isset($_POST['update_admin'])) {
    $users_id = intval($_POST['users_id']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Optional: Allow updating profile image too
    $profile_update = '';
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $profile_update = ", profile_image='$file_name'";
        }
    }

    $update_sql = "UPDATE users 
                   SET fullname='$fullname', email='$email', phone='$phone' $profile_update 
                   WHERE users_id=$users_id AND role='admin'";

    if (mysqli_query($conn, $update_sql)) {
        record_audit($conn, "Updated admin: $fullname", 'users', $users_id);
        $_SESSION['message'] = "✅ Admin details updated successfully.";
    } else {
        $_SESSION['message'] = "❌ Error updating admin: " . mysqli_error($conn);
    }

    header("Location: manage_admin.php");
    exit();
}

/* ======================================================
   ✅ DELETE ADMIN
   ====================================================== */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    $delete_sql = "DELETE FROM users WHERE users_id=$delete_id AND role='admin'";
    if (mysqli_query($conn, $delete_sql)) {
        record_audit($conn, "Deleted admin ID: $delete_id", 'users', $delete_id);
        $_SESSION['message'] = "🗑️ Admin deleted successfully.";
    } else {
        $_SESSION['message'] = "❌ Error deleting admin: " . mysqli_error($conn);
    }

    header("Location: manage_admin.php");
    exit();
}

/* ======================================================
   ✅ FETCH ALL ADMINS
   ====================================================== */
$admins = mysqli_query($conn, "SELECT * FROM users WHERE role='admin' ORDER BY users_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; padding: 20px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #007bff; color: white; }
        .btn { padding: 6px 10px; border-radius: 4px; text-decoration: none; color: white; font-size: 14px; }
        .add { background: #28a745; }
        .edit { background: #ffc107; color: black; }
        .delete { background: #dc3545; }
        input { width: 90%; padding: 5px; margin: 3px 0; }
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .back-btn { background: gray; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<h2>👥 Manage Admins</h2>
<form action="superadmin_index.php" method="get">
    <button class="back-btn">⬅️ Back to Dashboard</button>
</form>
<br/>
<?php flash_message(); ?>

<div class="form-container">
    <h3>Add New Admin</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="password" placeholder="Password" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <button type="submit" name="add_admin" class="btn add">Add Admin</button>
    </form>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Profile Image</th> 
        <th>Fullname</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($admins)) { ?>
    <tr>
        <form method="POST" enctype="multipart/form-data">
            <td><?= $row['users_id'] ?></td>
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
            <td><input type="text" name="fullname" value="<?= htmlspecialchars($row['fullname']) ?>" required></td>
            <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></td>
            <td><input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required></td>
            <td>
                <input type="hidden" name="users_id" value="<?= $row['users_id'] ?>">
                <button type="submit" name="update_admin" class="btn edit">Update</button>
                <a href="?delete=<?= $row['users_id'] ?>" onclick="return confirm('Delete this admin?')" class="btn delete">Delete</a>
            </td>
        </form>
    </tr>
    <?php } ?>
</table>

</body>
</html>