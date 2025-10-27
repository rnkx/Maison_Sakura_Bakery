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
    die("Only Gmail or Hotmail email addresses are allowed.");
}
// Validate minimum length
if (strlen($password) < 4) {
    die("Password must be at least 4 characters long.");
}
 // Validate Malaysian phone number
    if (!preg_match("/^(\+60\d{8,9}|01\d{8,9})$/", $phone)) {
        die("Phone number must be a valid Malaysian number.");
    }
    // password match validation
    if ($password !== $confirm_password) {
        die("Passwords do not match!");
    }

    // Insert into DB (no hash, per your requirement)
    $sql = "INSERT INTO users (fullname, email, phone, password, role) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fullname, $email, $phone, $password, $role);

   if ($stmt->execute()) {
      echo "<p>Registration successful! </p>";   
    echo '<p><a href="login_customer.php"><button>Login Now</button></a></p>';
}

    $stmt->close();
    $conn->close();
}
?>

