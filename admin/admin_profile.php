<?php
session_start();
include("db.php");

// ✅ Ensure logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}



$users_id = $_SESSION['users_id'];

// ✅ Fetch current admin info
$stmt = $conn->prepare("SELECT fullname, email, phone, profile_image FROM users WHERE users_id = ?");
$stmt->bind_param("i", $users_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$success = "";
$error = "";

// ✅ Update profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $profile_image = $user['profile_image']; // keep old image by default
    
    if (!preg_match("/^[\w\.\-]+@(gmail|hotmail)\.com$/", $email)) {
        $error = "Email must be Gmail or Hotmail.";
    } elseif (!preg_match("/^(\+60\d{8,9}|01\d{8,9})$/", $phone)) {
        $error = "Phone must be a valid Malaysia number (+60XXXXXXXXX).";
    } else {
        // ✅ Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
            $targetFile = $targetDir . $fileName;

            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                    $profile_image = $fileName;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Only JPG, PNG, and GIF files are allowed.";
            }
        }

        if (empty($error)) {
            // ✅ Secure password (only hash if changed)
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, password=?, profile_image=? WHERE users_id=?");
                $stmt->bind_param("sssssi", $fullname, $email, $phone, $password, $profile_image, $users_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, profile_image=? WHERE users_id=?");
                $stmt->bind_param("ssssi", $fullname, $email, $phone, $profile_image, $users_id);
            }

            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                $_SESSION['fullname'] = $fullname;
                $_SESSION['profile_image'] = $profile_image;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | Maison Sakura Bakery</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: #fff8f9;
      margin: 0;
      padding: 0;
    }
    header, footer {
      background: #e75480;
      color: white;
      padding: 15px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
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
            margin-top: 197px; /*adjust the footer and should always sticks to the bottom*/
        }
        
        footer a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
 
    main {
      padding: 30px;
      max-width: 600px;
      margin: auto;
    }
    h2 { color: #e75480; }
    form {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #444;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }
    button {
      background: #e75480;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
    }
    button:hover { background: #c7436b; }
    .success { color: green; margin-bottom: 15px; }
    .error { color: red; margin-bottom: 15px; }
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
            }   /* User button */
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
  </style>
</head>
<body>
<header>
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="80" height="80">
  <h1>Admin Dashboard</h1>
  <nav>
    <ul>
      <li><a href="admin_index.php">Dashboard</a></li>
      <li><a href="manage_orders.php">Orders</a></li>
      <li><a href="manage_products.php">Products</a></li>
      <li><a href="manage_customers.php">Customers</a></li>
      <li><a href="admin_view_messages.php">Messages</a></li>
    <li class="user-dropdown"> 
        <a href="admin_profile.php">
        <img src="<?= !empty($_SESSION['profile_image']) ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) : 'img/profile.jpg'; ?>" 
             alt="Profile"  
             style="width:35px; height:35px; border-radius:50%; object-fit:cover; vertical-align:middle;">
        <b><?= htmlspecialchars($_SESSION['fullname']); ?></b>
        </a>
      
      </li>
      <li><a href="admin_logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main>
  <h2 style="color:black;" align="center">My Profile</h2>

  <?php if ($success): ?><p class="success"><?= $success; ?></p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= $error; ?></p><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <img src="<?= !empty($user['profile_image']) ? 'uploads/' . htmlspecialchars($user['profile_image']) : 'img/profile.jpg'; ?>" 
         alt="Profile Picture" 
         style="width:120px; height:120px; border-radius:50%; object-fit:cover;">

    <label>Change Profile Photo</label>
    <input type="file" name="profile_image" accept="image/*">

    <label>Full Name</label>
    <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']); ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

    <label>Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" required>

    <label>Password (leave blank to keep unchanged)</label>
    <input type="password" name="password" placeholder="Enter new password">

    <button type="submit">Update Profile</button>
  </form>
</main>


 <footer> <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p> <p> 
                                    <a href="admin_about.php" style="color: white;">About</a> | 
                                    <a href="admin_contact.php" style="color: white;">Contact</a> | 
                                    <a href="admin_privacy_policy.php" style="color: white;">Privacy Policy</a> | 
                                    <a href="admin_terms&conditions.php" style="color: white;">Terms & Conditions</a> </p> 
                            </footer>
</body>
</html>


