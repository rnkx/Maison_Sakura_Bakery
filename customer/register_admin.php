<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Register - Maison Sakura</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="css/style.css">
</head>
<style> .login-link {
        text-align: center;
        margin-top: 20px;
        color: #555;
      }
      .login-link a {
        color: #ff6b6b;
        text-decoration: none;
        font-weight: 500;
      }
      .login-link a:hover {
        text-decoration: underline;
      }
      .Register-Button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(to right, #ff9a9e 0%, #fad0c4 100%);
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
      }
</style>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg rounded-3">
        <div class="card-body p-4">
             <div class="d-flex align-items-center justify-content-center mb-4">
                 <!-- Logo -->
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="100" height="100" class="me-3">

  <!-- Title -->
  <h3 class="m-0">Admin Registration</h3>
</div>
         
          
          <!-- Registration form for Admin -->
          <form action="admin_register_process.php" method="POST">
            <div class="mb-3">
              <label for="fullname" class="form-label">Full Name</label>
            <input type="text" name="fullname" class="form-control" 
         required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
             <input type="email" name="email" class="form-control" 
         pattern="^[a-zA-Z0-9._%+-]+@(gmail|hotmail)\.com$"
         title="Only Gmail or Hotmail addresses with .com are allowed"
         required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
             <input type="password" name="password" class="form-control" 
         minlength="4"
         placeholder="At least 4 characters"
         required>
            </div>
<div class="mb-3">
  <label for="confirm_password" class="form-label">Confirm Password</label>
 <input type="password" name="confirm_password" class="form-control" 
         minlength="4"
       
         required>
</div>
              <div class="mb-3">
  <label for="phone" class="form-label">Phone Number</label>
 <input type="tel" name="phone" class="form-control" 
         placeholder="+60123456789 or 0123456789"
         pattern="^(\+60\d{8,9}|01\d{8,9})$"
         title="Phone must be a valid Malaysian number (e.g., +60123456789 or 0123456789)"
         required>
</div>
<div class="form-check mb-3">
  <input class="form-check-input" type="checkbox" required>
  <label class="form-check-label">I agree to the Terms & Conditions</label>
</div>

            <!-- Hidden role for admin -->
            <input type="hidden" name="role" value="admin">
            <button type="submit" class="Register-Button">Register as Admin</button>
          </form>
          <br/>
          <div class="login-link">
   <p>Already have an account? <a href="login_admin.php">Login here</a></p>
        </div>
            <div class="login-link">
   <p><a href="admin_terms&conditions.php">Terms & Conditions</a></p>
        </div>
            <div class="login-link">
   <p><a href="admin_privacy_policy.php">Privacy Policy</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<br/>
<br/>
</body>
</html>
