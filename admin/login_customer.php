<?php include("db.php");
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Login - Maison Sakura</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      .Login-Button {
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
      .login-link {
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
  </style>
  <!-- link to other css file -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg rounded-3">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-center mb-4">
            <img src="img/logo.jpg" alt="Maison Sakura Logo" width="100" height="100" class="me-3">
            <h3 class="m-0">Customer Login</h3>
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

          <!-- Login form -->
          <form action="customer_login_process.php" method="POST">
            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" 
                     placeholder="Enter your email" required>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-control" 
                     placeholder="Enter your password" required>
            </div>

            <!-- Hidden role -->
            <input type="hidden" name="role" value="customer">

            <!-- Submit -->
            <button type="submit" class="Login-Button">Login</button>
          </form>

          <!-- Links -->
          <div class="login-link">
            <p>Don’t have an account? <a href="register_customer.php">Register here</a></p>
            <p><a href="customer_forgot_password.php">Forgot Password?</a></p>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<br/><br/>
</body>
</html>
