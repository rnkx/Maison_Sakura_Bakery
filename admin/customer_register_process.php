<?php
// external php files connection
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname']  ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? ''; // ✅ new field
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    

// Validate email (only Gmail & Hotmail)
if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail|hotmail)\.com$/", $email)) {
    echo "<p style='color:red;'>Invalid email address.</p>";
    echo '<p><button onclick="window.history.back()">Back</button></p>';
    exit();
}

// Validate minimum length
if (strlen($password) < 4) {
    echo "<p style='color:red;'>Password must be at least 4 characters long.</p>";
    echo '<p><button onclick="window.history.back()">Back</button></p>';
    exit();
}

// Validate Malaysian phone number
if (!preg_match("/^(\+60\d{8,9}|01\d{8,9})$/", $phone)) {
    echo "<p style='color:red;'>Phone number must be a valid Malaysian number.</p>";
    echo '<p><button onclick="window.history.back()">Back</button></p>';
    exit();
}

// Password match validation
if ($password !== $confirm_password) {
    echo "<p style='color:red;'>Passwords do not match!</p>";
    echo '<p><button onclick="window.history.back()">Back</button></p>';
    exit();
}


    // Insert into DB (no hash, per your requirement)
    $sql = "INSERT INTO users (fullname, email, phone, password, role) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fullname, $email, $phone, $password, $role);

   if ($stmt->execute()) {
      echo "<p>Registration successful! </p>";   
    echo '<p><a href="login_customer.php"><button>Login Now</button></a></p>';
}else {
        echo "<p style='color:red;'>Error: Duplicate Email Account! Please try a different email account</p>";
         echo '<p><a href="register_customer.php"><button>Back</button></a></p>';
    }

    $stmt->close();
    $conn->close();
}
?>

