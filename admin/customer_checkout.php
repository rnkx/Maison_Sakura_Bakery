<?php
session_start();
include("db.php");

// Ensure customer is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'];
$fullname = $_SESSION['fullname'] ?? '';

// Fetch cart items with discount
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity, p.products_id, p.name, p.price, p.discount_percent, p.image
    FROM cart c
    JOIN products p ON c.products_id = p.products_id
    WHERE c.users_id = ?
");
$stmt->bind_param("i", $users_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Prepare items array and calculate totals
$items = [];
$total_price = 0;
$total_savings = 0;
while ($row = $cart_items->fetch_assoc()) {
    $discounted_price = $row['price'] * (1 - ($row['discount_percent'] / 100));
    $subtotal = $discounted_price * $row['quantity'];
    $savings = ($row['price'] - $discounted_price) * $row['quantity'];

    $total_price += $subtotal;
    $total_savings += $savings;

    $items[] = array_merge($row, [
        'discounted_price' => $discounted_price,
        'subtotal' => $subtotal,
        'savings' => $savings
    ]);
}

// If cart empty
if (empty($items)) {
    echo "<p style='text-align:center;'>Your cart is empty. <a href='customer_products.php'>Shop now</a></p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | Maison Sakura Bakery</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
  body {
      font-family: Arial, sans-serif;
      margin:0;
      padding:0;
      background-color:#fff8f9;
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
            margin-top: 297px; /*adjust the footer and should always sticks to the bottom*/
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
      flex: 1;
      padding: 30px;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }
    h2, h3 { color: #e75480; }

    /* Cart summary */
    .cart-card {
      background: white;
      padding: 20px;
      border-radius: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .cart-item {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 15px;
      border-bottom: 1px solid #f0f0f0;
      padding-bottom: 10px;
    }
    .cart-item img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 8px;
    }
    .cart-item h4 {
      margin: 0;
      font-size: 16px;
      color: #333;
    }
    .cart-item p { margin: 5px 0; color: #666; }
    .cart-total {
      text-align: right;
      font-size: 18px;
      font-weight: bold;
      margin-top: 20px;
      color: #e75480;
    }

    /* Checkout form */
    .checkout-form {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .checkout-form label {
      display: block;
      margin-top: 15px;
      font-weight: 600;
      color: #444;
    }
    .checkout-form input,
    .checkout-form select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    .checkout-form button {
      width: 100%;
      padding: 12px;
      margin-top: 20px;
      background: #e75480;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
  

    footer {
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }
    @media (max-width: 900px) {
      main {
        grid-template-columns: 1fr;
      }
    }
    nav ul {
      list-style: none; margin: 0; padding: 0; display: flex; gap: 20px;
    }
    nav ul li a {
      text-decoration: none; color: white; font-weight: 600;
      padding: 8px 14px; border-radius: 5px;
    }
    nav ul li a:hover { background-color: rgba(255,255,255,0.2); }
/* Special user button */ 
            .user-dropdown > a { 
                background: 
                    linear-gradient(45deg, #ffb6c1, #e75480); 
                color: white !important; 
                font-weight: 700; 
                padding: 10px 20px; 
                border-radius: 50px; 
                box-shadow: 0 4px 10px rgba(231, 84, 128, 0.4); 
                border: 2px solid #fff; cursor: pointer; } 
            /* Dropdown */ 
            .user-dropdown-content 
            { display: none; 
              position: absolute; 
              right: 0; 
              background-color: white; 
              min-width: 180px; 
              border-radius: 8px; 
              box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
              z-index: 1; padding: 5px 0; } 
            .user-dropdown-content a 
            { color: #333 !important; 
              padding: 10px 15px; 
              text-decoration: none; 
              display: block; 
              font-weight: 500; } 
            .user-dropdown-content a:hover { background-color: #f4f4f4; } 
            .user-dropdown:hover .user-dropdown-content { display: block; }
          
            .checkout-form button:hover:enabled { background:#c73c65; }
    .error { color:red; font-size:0.9em; display:block; }
                 @media(max-width:900px){main{grid-template-columns:1fr;}}
 
  </style>
</head>
<body>
<header>
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="80" height="80">
 <nav>
    <ul>
      <li><a href="customer_index.php">Home</a></li>
      <li><a href="customer_products.php">Shop</a></li>
      <li><a href="customer_about.php">About Us</a></li>
      <li><a href="customer_contact.php">Contact</a></li>
       <li class="user-dropdown"> 
  <a>
   <img src="<?= !empty($_SESSION['profile_image']) 
                        ? 'uploads/' . htmlspecialchars($_SESSION['profile_image']) 
                        : 'img/profile.jpg'; ?>" 
                        alt="Profile"  
                        style="width:35px; height:35px; border-radius:50%; object-fit:cover; margin-right:8px; vertical-align:middle;">

    <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b>
  </a>
  <div class="user-dropdown-content">
    <a href="customer_profile.php">View Profile</a>
    <a href="customer_orders.php">My Orders</a>
  
  </div>
</li>

                            <li><a href="customer_logout.php">Logout</a></li> </ul> </nav> </header>



<main>
<!-- Cart Summary -->
<div class="cart-card">
  <h2 class="cart-title">🛒 Your Shopping Cart</h2>

  <?php foreach($items as $item): ?>
      <?php
        // Determine image path
        $imagePath = 'img/no-image.png';
        if (!empty($item['image'])) {
            if(file_exists("uploads/".$item['image'])) $imagePath = "uploads/".$item['image'];
            elseif(file_exists("admin/uploads/".$item['image'])) $imagePath = "admin/uploads/".$item['image'];
            elseif(file_exists("customer/uploads/".$item['image'])) $imagePath = "customer/uploads/".$item['image'];
        }
      ?>
      <div class="cart-item">
        <img src="<?= htmlspecialchars($imagePath); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
        <div class="cart-item-info">
          <h4><?= htmlspecialchars($item['name']); ?></h4>
          <?php if($item['discount_percent'] > 0): ?>
            <p>
              <span style="text-decoration:line-through; color:#888;">RM <?= number_format($item['price'],2); ?></span>
              <span style="color:#e25822; font-weight:bold;">RM <?= number_format($item['discounted_price'],2); ?></span>
              × <?= $item['quantity']; ?>
            </p>
          <?php else: ?>
            <p>RM <?= number_format($item['price'],2); ?> × <?= $item['quantity']; ?></p>
          <?php endif; ?>
        </div>
        <div class="cart-item-total">RM <?= number_format($item['subtotal'],2); ?></div>
      </div>
  <?php endforeach; ?>

  <hr class="cart-divider">

  <div class="cart-total">
    <strong>Total:</strong> RM <?= number_format($total_price,2); ?>
    <?php if($total_savings > 0): ?>
      <br><small style="color:green;">You Saved: RM <?= number_format($total_savings,2); ?></small>
    <?php endif; ?>
  </div>
</div>
  <!-- Checkout Form -->
  <div class="checkout-form">
    <h2>Pickup & Payment</h2>
    <form method="POST" action="customer_checkout_process.php" id="checkoutForm" novalidate>
      <label for="pickup_date">Pickup Date:</label>
      <input type="date" id="pickup_date" name="pickup_date"
             min="<?= date('Y-m-d'); ?>" max="<?= date('Y-m-d', strtotime('+3 days')); ?>" required>
      <span class="error" id="pickupDateError"></span>

      <label for="pickup_time">Pickup Time:</label>
      <input type="time" id="pickup_time" name="pickup_time" required>
      <span class="error" id="pickupTimeError"></span>

<!-- Payment Method -->
<label for="payment_method">Payment Method:</label>
<select name="payment_method" id="payment_method">
  <option value="">-- Select --</option>
  <option value="Card"> 💳 Credit/Debit Card</option>
  <option value="TouchNGo"> 📱 Touch 'n Go</option>
  <option value="GrabPay"> 📱 GrabPay</option>
  <option value="Boost"> 📱 Boost</option>
</select>
<span class="error" id="paymentMethodError"></span>

<!-- Payment logo -->
<div id="payment-logo" style="margin:15px 0;"></div>

<!-- E-Wallet -->
<div id="ewallet-fields" style="display:none;">
  <p><strong>E-Wallet Details</strong></p>

  <label for="ewallet_phone">Phone Number:</label>
  <input type="text" id="ewallet_phone" name="ewallet_phone" maxlength="11" placeholder="01XXXXXXXX">
  <span class="error" id="ewalletError"></span>

  <div id="otp-wrapper">
    <label for="ewallet_otp">Transaction PIN:</label>
    <input type="password" id="ewallet_otp" name="ewallet_otp" maxlength="6" placeholder="6-digit PIN">
    <span class="error" id="otpError"></span>
  </div>
</div>

<!-- Card -->
<div id="card-fields" style="display:none;">
  <label>Card Number:</label>
  <input type="text" id="card_number" name="card_number" maxlength="16">
  <span class="error" id="cardNumberError"></span>

  <label>Card Holder Name:</label>
  <input type="text" id="card_name" name="card_name">
  <span class="error" id="cardNameError"></span><br/>

  <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
    <div style="display:flex; flex-direction:column;">
      <label for="expiry_month">Expiry Month:</label>
      <input type="number" id="expiry_month" name="expiry_month" min="1" max="12" style="width:150px;">
      <span class="error" id="expiryMonthError"></span>
    </div>
    <div style="display:flex; flex-direction:column;">
      <label for="expiry_year">Expiry Year:</label>
      <input type="number" id="expiry_year" name="expiry_year" 
             min="<?= date('Y'); ?>" max="<?= date('Y')+10; ?>" style="width:180px;">
      <span class="error" id="expiryYearError"></span>
    </div>
  </div>

  <label>CVV:</label>
  <input type="password" id="cvv" name="cvv" maxlength="3">
  <span class="error" id="cvvError"></span>
</div>
      <input type="hidden" name="total_price" value="<?= $total_price; ?>">
      <button type="submit" id="placeOrder">Place Order</button>
    </form>
  </div>
</main>

<footer>
  <p>&copy; <?= date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
  /** ---------------- Element References ---------------- */
  const checkoutForm = document.getElementById("checkoutForm");
  const paymentMethod = document.getElementById("payment_method");
  const ewalletFields = document.getElementById("ewallet-fields");
  const cardFields = document.getElementById("card-fields");
  const otpWrapper = document.getElementById("otp-wrapper");
  const pickupDate = document.getElementById("pickup_date");
  const pickupTime = document.getElementById("pickup_time");
  const paymentLogo = document.getElementById("payment-logo");

  /** ---------------- Toggle Payment Fields ---------------- */
  function togglePaymentFields() {
    const method = paymentMethod.value;
    cardFields.style.display = (method === "Card") ? "block" : "none";
    ewalletFields.style.display = ["TouchNGo","GrabPay","Boost"].includes(method) ? "block" : "none";

    // Handle OTP wrapper for GrabPay
    if (method === "GrabPay") {
      otpWrapper.style.display = "none";
      document.getElementById("ewallet_otp").value = ""; // clear PIN
    } else if (["TouchNGo","Boost"].includes(method)) {
      otpWrapper.style.display = "block";
    }
  }

  /** ---------------- Show Logo ---------------- */
  function showLogo() {
    const method = paymentMethod.value;
    const logos = {
      "Card": "img/card.png",
      "TouchNGo": "img/tng.png",
      "GrabPay": "img/grabpay.png",
      "Boost": "img/boost.png"
    };
    paymentLogo.innerHTML = logos[method] 
      ? `<img src="${logos[method]}" alt="${method} Logo" style="width:100px; height:auto; border-radius:8px;">` 
      : "";
  }

  /** ---------------- Form Validation ---------------- */
  function validatePickup() {
    let valid = true;
    const dateVal = pickupDate.value;
    const timeVal = pickupTime.value;
    const dateError = document.getElementById("pickupDateError");
    const timeError = document.getElementById("pickupTimeError");

    dateError.textContent = "";
    timeError.textContent = "";

    if (!dateVal) {
      dateError.textContent = "Pickup date is required.";
      valid = false;
    }
    if (!timeVal) {
      timeError.textContent = "Pickup time is required.";
      valid = false;
    }

    return valid;
  }

  function validatePayment() {
    let valid = true;
    const method = paymentMethod.value;
    const paymentError = document.getElementById("paymentMethodError");
    paymentError.textContent = "";

    if (!method) {
      paymentError.textContent = "Please select a payment method.";
      return false;
    }

    // E-Wallet Validation
    if (["TouchNGo", "GrabPay", "Boost"].includes(method)) {
      const phone = document.getElementById("ewallet_phone").value.trim();
      const otp = document.getElementById("ewallet_otp").value.trim();
      const phoneErr = document.getElementById("ewalletError");
      const otpErr = document.getElementById("otpError");
      phoneErr.textContent = "";
      otpErr.textContent = "";

      if (!phone) {
        phoneErr.textContent = "Phone number is required.";
        valid = false;
      } else if (!/^01[0-9]{8,9}$/.test(phone)) {
        phoneErr.textContent = "Invalid Malaysian phone number.";
        valid = false;
      }

      if (method !== "GrabPay") {
        if (!otp) {
          otpErr.textContent = "Transaction PIN is required.";
          valid = false;
        } else if (!/^\d{6}$/.test(otp)) {
          otpErr.textContent = "PIN must be 6 digits.";
          valid = false;
        }
      }
    }

    // Card Validation
    if (method === "Card") {
      const num = document.getElementById("card_number").value.trim();
      const name = document.getElementById("card_name").value.trim();
      const cvv = document.getElementById("cvv").value.trim();
      const month = document.getElementById("expiry_month").value.trim();
      const year = document.getElementById("expiry_year").value.trim();
      document.getElementById("cardNumberError").textContent = "";
      document.getElementById("cardNameError").textContent = "";
      document.getElementById("cvvError").textContent = "";
      document.getElementById("expiryMonthError").textContent = "";
      document.getElementById("expiryYearError").textContent = "";

      if (!num) {
        document.getElementById("cardNumberError").textContent = "Card number is required.";
        valid = false;
      } else if (!/^\d{16}$/.test(num)) {
        document.getElementById("cardNumberError").textContent = "Card number must be 16 digits.";
        valid = false;
      }

      if (!name) {
        document.getElementById("cardNameError").textContent = "Cardholder name is required.";
        valid = false;
      }

      if (!cvv) {
        document.getElementById("cvvError").textContent = "CVV is required.";
        valid = false;
      } else if (!/^\d{3}$/.test(cvv)) {
        document.getElementById("cvvError").textContent = "CVV must be 3 digits.";
        valid = false;
      }

      if (!month) {
        document.getElementById("expiryMonthError").textContent = "Expiry month is required.";
        valid = false;
      }
      if (!year) {
        document.getElementById("expiryYearError").textContent = "Expiry year is required.";
        valid = false;
      }
    }

    return valid;
  }

  /** ---------------- Event Listeners ---------------- */
  checkoutForm.addEventListener("submit", (e) => {
    if (!validatePickup() || !validatePayment()) e.preventDefault();
  });

  paymentMethod.addEventListener("change", () => { 
    togglePaymentFields(); 
    showLogo(); 
  });

  togglePaymentFields(); 
  showLogo();
});
</script>
</body>
</html>