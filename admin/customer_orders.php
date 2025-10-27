<?php
session_start();
include("db.php");

// Ensure customer is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id = $_SESSION['users_id'];

// Fetch orders
$stmt_orders = $conn->prepare("
    SELECT o.order_id, o.total_price, o.payment_method, o.payment_status, 
           o.pickup_date, o.pickup_time, o.created_at
    FROM orders o
    WHERE o.users_id = ?
    ORDER BY o.created_at DESC
");
$stmt_orders->bind_param("i", $users_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$orders = $result_orders->fetch_all(MYSQLI_ASSOC);
$stmt_orders->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <title>My Orders | Maison Sakura Bakery</title>
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
      header { 
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
            margin-top: 400px; /*adjust the footer and should always sticks to the bottom*/
        }
        
        footer a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
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
            }
    .card {
      background: white; padding: 20px; border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;
      max-width: 800px; margin-left:auto; margin-right:auto;
    }
    h2, h3 { color:#e75480; margin-bottom:10px; }
    table { width:100%; border-collapse: collapse; margin-top:10px; }
    th, td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
    th { background:#fce4ec; }
    .total { text-align:right; font-weight:bold; color:#e75480; }
    .btn {
      display:inline-block; margin-top:10px; padding:8px 15px;
      background:#e75480; color:white; text-decoration:none;
      border-radius:6px; font-weight:bold; font-size:14px;
    }
    .btn:hover { background:#c73c65; }
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
            .order-card { background:white; padding:70px; border-radius:12px; margin-bottom:60px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.order-card h3 { margin:0 0 10px 0; color:#e75480; }
.order-item { display:flex; justify-content:space-between; border-bottom:1px solid #f0f0f0; padding:5px 0; }
.order-item:last-child { border-bottom:none; }
button.print-btn { margin-top:20px; width:100%; padding:10px; background:#e75480; color:white; border:none; border-radius:6px; cursor:pointer; font-size:16px; }
button.print-btn:hover { background:#e75480; }
    .cancel-btn:hover { background:#cc0000; }
    @media print { button.print-btn, .cancel-btn { display:none; } }
    .alert { padding:10px; margin:15px auto; border-radius:6px; max-width:800px; }
    .alert-success { border:1px solid green; color:green; background:#f3fff3; }
  </style>
</head>
<body>
    <header>
  <img src="img/logo.jpg" alt="Maison Sakura Logo" width="80" height="80">
     <nav> <ul> <li><a href="customer_index.php">Home</a></li>
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


                            <li><a href="customer_logout.php">Logout</a></li> </ul> </nav>
</header>
 <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="alert alert-success" style="padding:10px; margin-bottom:15px; border:1px solid green; color:green; border-radius:5px;">
    Your payment and checkout is completed.
  </div>
<?php endif; ?>
<br/>
<br/>
<main>
<h2 align="center">My Orders</h2>
<br/>

<?php if(empty($orders)): ?>
<p>You have no orders yet. <a href="customer_products.php">Shop now</a></p>
<?php else: ?>
<?php foreach($orders as $order): ?>
<div class="order-card" id="order-<?= $order['order_id']; ?>">
<div class="receipt-content">
<h3><strong>Payment Status:</strong> <?= htmlspecialchars($order['payment_status']); ?></h3>
<p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']); ?></p>
<p><strong>Placed on:</strong> <?= date("d M Y, H:i", strtotime($order['created_at'])); ?></p>
<p><strong>Pickup:</strong> <?= date("d M Y", strtotime($order['pickup_date'])); ?> at <?= htmlspecialchars($order['pickup_time']); ?></p>
<p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>
<p><strong>All goods are non-refundable unless system issues occur</strong></p>

<h4>Items:</h4>
<?php
// ✅ Fetch order items including original price, discounted price, and discount percent
$stmt_items = $conn->prepare("
    SELECT oi.quantity, oi.price_original, oi.price_after_discount, oi.discount_percent, p.name
    FROM order_items oi
    JOIN products p ON oi.products_id = p.products_id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order['order_id']);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$stmt_items->close(); // ✅ keep only this one

$subtotal = 0;
$total_savings = 0;
while($item = $result_items->fetch_assoc()):
    $discounted_price = $item['price_original'] * (1 - ($item['discount_percent']/100));
    $line_total = $discounted_price * $item['quantity'];
    $savings = ($item['price_original'] - $discounted_price) * $item['quantity'];
    $subtotal += $line_total;
    $total_savings += $savings;
?>
<div class="order-item">
<span>
<?= htmlspecialchars($item['name']); ?>  
(<?php if($item['discount_percent']>0): ?>
    <del>RM <?= number_format($item['price_original'],2); ?></del> RM <?= number_format($discounted_price,2); ?>
<?php else: ?>
    RM <?= number_format($item['price_original'],2); ?>
<?php endif; ?> × <?= $item['quantity']; ?>)
</span>
<span>RM <?= number_format($line_total,2); ?></span>
</div>
<?php endwhile; ?>


<div class="total">Total: RM <?= number_format($subtotal,2); ?></div>
<?php if($total_savings>0): ?>
<div class="savings">You Saved: RM <?= number_format($total_savings,2); ?></div>
<?php endif; ?>

</div>

<div style="display:flex; justify-content:space-between; gap:10px; margin-top:15px;">
<button class="print-btn" onclick="printOrder('order-<?= $order['order_id']; ?>')">Print Receipt</button>
<button class="print-btn" onclick="downloadPDF('order-<?= $order['order_id']; ?>')">Download PDF</button>
</div>
<br/>
</div>
<?php endforeach; ?>
<?php endif; ?>
</main>

 <footer> <p>&copy; <?php echo date("Y"); ?> Maison Sakura Bakery. All rights reserved.</p> <p> 
                                    <a href="customer_about.php" style="color: white;">About</a> | 
                                    <a href="customer_contact.php" style="color: white;">Contact</a> | 
                                    <a href="customer_privacy_policy.php" style="color: white;">Privacy Policy</a> | 
                                    <a href="customer_terms&conditions.php" style="color: white;">Terms & Conditions</a> </p> 
                            </footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
function printOrder(orderId) {
    const content = document.getElementById(orderId).querySelector(".receipt-content").innerHTML;
    const printWindow = window.open('', '', 'width=700,height=900');

    printWindow.document.write(`
        <html>
        <head>
            <title>Maison Sakura Bakery Receipt</title>
            <style>
                body { font-family: 'Arial', sans-serif; padding: 30px; background-color: #fff8f9; color: #333; }
                .header { text-align:center; margin-bottom:30px; }
                .header img { width:100px; height:100px; border-radius:50%; }
                h2 { color:#e75480; }
                .order-item { display:flex; justify-content:space-between; border-bottom:1px dashed #ccc; padding:5px 0; }
                .total { margin-top:10px; text-align:right; font-weight:bold; color:#e75480; }
                footer { text-align:center; font-size:12px; color:#666; margin-top:30px; }
                .watermark {
                    position: fixed;
                    top: 40%;
                    left: 20%;
                    font-size: 50px;
                    color: rgba(231,84,128,0.1);
                    transform: rotate(-30deg);
                    pointer-events: none;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="img/logo.jpg" alt="Maison Sakura Logo">
                <h2>Maison Sakura Bakery</h2>
                <p><strong>Official Receipt</strong></p>
            </div>

            <div class="watermark">Maison Sakura Bakery</div>
            ${content}

            <footer>
                <p>Maison Sakura Bakery © ${new Date().getFullYear()}</p>
                <p>All sales are final. For assistance, contact support@maisonsakura.com</p>
            </footer>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}


async function downloadPDF(orderId) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Branding
    const logoUrl = 'img/logo.jpg';
    const orderElement = document.getElementById(orderId).querySelector(".receipt-content");
    const textLines = orderElement.innerText.split("\n");
    const pageWidth = doc.internal.pageSize.width;

    // Load logo
    const logo = new Image();
    logo.src = logoUrl;
    await new Promise(r => logo.onload = r);
    doc.addImage(logo, 'JPEG', 80, 10, 50, 50);
    doc.setFontSize(16);
    doc.setTextColor(231, 84, 128);
    doc.text("Maison Sakura Bakery", pageWidth / 2, 70, { align: "center" });

    doc.setFontSize(11);
    doc.setTextColor(60, 60, 60);
    let y = 85;
    textLines.forEach(line => {
        if (y > 270) { // New page if too long
            doc.addPage();
            y = 20;
        }
        doc.text(line, 15, y);
        y += 8;
    });

    // Footer watermark
    doc.setFontSize(10);
    doc.setTextColor(150);
    doc.text("Maison Sakura Bakery © " + new Date().getFullYear(), pageWidth / 2, 285, { align: "center" });

    // Generate verification code (to prevent fake receipts)
    const verificationCode = Math.random().toString(36).substring(2, 8).toUpperCase();
    doc.setTextColor(231, 84, 128);
    doc.text("Verification Code: " + verificationCode, 15, 275);

    doc.save(`MaisonSakura_Receipt_${orderId}.pdf`);
}
</script>
</body>
</html>