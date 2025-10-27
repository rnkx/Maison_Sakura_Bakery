<?php
session_start();
include("db.php");

// ✅ Ensure customer is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}

$users_id       = $_SESSION['users_id'];
$pickup_date    = trim($_POST['pickup_date'] ?? '');
$pickup_time    = trim($_POST['pickup_time'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$discount_code  = trim($_POST['discount_code'] ?? ''); // optional

$allowed_ewallets = ["TouchNGo", "GrabPay", "Boost"];

// Begin transaction
$conn->begin_transaction();

try {
    // ---------- 1️⃣ Validate pickup ----------
    if (empty($pickup_date) || empty($pickup_time)) throw new Exception("Pickup date and time required.");
    $pickup_datetime = DateTime::createFromFormat("Y-m-d H:i", $pickup_date . " " . $pickup_time);
    if (!$pickup_datetime) throw new Exception("Invalid pickup date/time format.");

    $now = new DateTime();
    $max = (clone $now)->modify("+3 days");
    if ($pickup_datetime <= $now) throw new Exception("Pickup must be in the future.");
    if ($pickup_datetime > $max) throw new Exception("Pickup cannot exceed 3 days.");

    // Check operating hours
    $day = (int)$pickup_datetime->format("N");
    $time_val = $pickup_datetime->format("H:i");
    list($hour, $minute) = explode(":", $time_val);
    $total_minutes = $hour * 60 + $minute;

    $operating_hours = [
        ["days" => [1,2,3,4,5,6], "start" => "09:00", "end" => "21:00"],
        ["days" => [7], "start" => "10:00", "end" => "18:00"],
    ];

    $allowed = false;
    foreach ($operating_hours as $h) {
        if (in_array($day, $h["days"])) {
            list($sh, $sm) = explode(":", $h["start"]);
            list($eh, $em) = explode(":", $h["end"]);
            $start_minutes = $sh * 60 + $sm;
            $end_minutes = $eh * 60 + $em;
            if ($total_minutes >= $start_minutes && $total_minutes <= $end_minutes) {
                $allowed = true;
                break;
            }
        }
    }
    if (!$allowed) throw new Exception("Pickup time outside operating hours.");

    // ---------- 2️⃣ Insert order with temporary total_price = 0 ----------
    $stmt_order = $conn->prepare("
        INSERT INTO orders (users_id, total_price, pickup_date, pickup_time, payment_method, payment_status)
        VALUES (?, 0, ?, ?, ?, 'Payment Success')
    ");
    $stmt_order->bind_param("isss", $users_id, $pickup_date, $pickup_time, $payment_method);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // ---------- 3️⃣ Process cart items ----------
    $stmt = $conn->prepare("
        SELECT c.products_id, c.quantity, p.price, p.discount_percent, p.current_stock
        FROM cart c
        JOIN products p ON c.products_id = p.products_id
        WHERE c.users_id = ?
    ");
    $stmt->bind_param("i", $users_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    $stmt->close();

    if ($cart_result->num_rows === 0) throw new Exception("Cart is empty.");

    $total_price = 0;

    while ($row = $cart_result->fetch_assoc()) {
        $product_id = $row['products_id'];
        $quantity   = (int)$row['quantity'];
        $price_original = (float)$row['price'];
        $discount_percent = (float)$row['discount_percent'];
        $current_stock = (int)$row['current_stock'];

        if ($current_stock < $quantity) throw new Exception("Not enough stock for product ID $product_id.");

        $price_after_discount = round($price_original * (1 - $discount_percent / 100), 2);
        $line_total = $price_after_discount * $quantity;
        $total_price += $line_total;

        // Insert order_items
        $stmt_item = $conn->prepare("
            INSERT INTO order_items
            (order_id, products_id, quantity, price_original, price_after_discount, discount_percent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt_item->bind_param("iidddd", $order_id, $product_id, $quantity, $price_original, $price_after_discount, $discount_percent);
        $stmt_item->execute();
        $stmt_item->close();

        // Update stock
        $stmt_stock = $conn->prepare("UPDATE products SET current_stock = current_stock - ? WHERE products_id = ?");
        $stmt_stock->bind_param("ii", $quantity, $product_id);
        $stmt_stock->execute();
        $stmt_stock->close();
    }

    // ---------- 4️⃣ Update order total ----------
    $stmt_total = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
    $stmt_total->bind_param("di", $total_price, $order_id);
    $stmt_total->execute();
    $stmt_total->close();

    // ---------- 5️⃣ Insert into customer_payments ----------
    $stmt_payment = $conn->prepare("
        INSERT INTO customer_payments
        (order_id, users_id, amount, payment_method, payment_status, created_at)
        VALUES (?, ?, ?, ?, 'Payment Success', NOW())
    ");
    $stmt_payment->bind_param("iids", $order_id, $users_id, $total_price, $payment_method);
    $stmt_payment->execute();
    $stmt_payment->close();

    // ---------- 6️⃣ Clear cart ----------
    $stmt_clear = $conn->prepare("DELETE FROM cart WHERE users_id = ?");
    $stmt_clear->bind_param("i", $users_id);
    $stmt_clear->execute();
    $stmt_clear->close();

    // ---------- 7️⃣ Commit ----------
    $conn->commit();

    header("Location: customer_order_confirmation.php?order_id=$order_id");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='color:red; padding:15px; border:1px solid red; border-radius:5px;'>⚠️ ".htmlspecialchars($e->getMessage())."</div>";
    echo '<a href="customer_checkout.php"><button style="padding:10px 20px; background:#cc0000; color:#fff; border:none; border-radius:5px; cursor:pointer;">⬅ Back to Checkout</button></a>';
    exit();
}
?>
