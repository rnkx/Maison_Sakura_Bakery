<?php include("db.php"); 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Maison Sakura</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      .Reset-Button {
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
      .Reset-Button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      }
  </style>
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg rounded-3">
        <div class="card-body p-4">
          <div class="text-center mb-4">
            <img src="img/logo.jpg" alt="Maison Sakura Logo" width="100" height="100" class="mb-3">
            <h3 class="m-0">Reset Your Password</h3>
          </div>
 <!-- Error Message -->
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger text-center" role="alert">
              <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); 
              ?>
            </div>
          <?php endif; ?>
          <!-- Direct reset form -->
          <form action="superadmin_forgot_password_process.php" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Registered Email</label>
              <input type="email" name="email" class="form-control" 
                placeholder="Enter your registered email" required>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">New Password</label>
              <input type="password" name="password" class="form-control" 
                 minlength="4"
                placeholder="Enter new password (at least 4 characters)" required>
            </div>

            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" 
                      minlength="4"
                placeholder="Confirm new password" required>
            </div>

            <button type="submit" class="Reset-Button">Update Password</button>
          </form>

          <div class="text-center mt-3">
            <a href="login_superadmin.php">Back to Login</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
