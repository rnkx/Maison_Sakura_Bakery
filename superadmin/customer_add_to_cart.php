<?php
session_start();
include("db.php");

// ✅ Ensure user is logged in as customer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    echo "Please log in as a customer.";
    exit();
}

// ✅ Get user ID
$users_id = $_SESSION['users_id'] ?? null;
if (!$users_id) {
    echo "Error: Customer not identified. Please log in again.";
    exit();
}

// ✅ Handle Add to Cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['products_id'])) {
    $products_id = intval($_POST['products_id']);

    // 🔎 Check if product exists & stock available
    $stmt = $conn->prepare("SELECT products_id, name, current_stock FROM products WHERE products_id = ?");
    $stmt->bind_param("i", $products_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        echo "Product not found.";
        exit();
    }

    if ($product['current_stock'] <= 0) {
        echo htmlspecialchars($product['name']) . " is out of stock.";
        exit();
    }

    // ✅ Check if product already in cart
    $stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE users_id = ? AND products_id = ?");
    $stmt->bind_param("ii", $users_id, $products_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // ✅ Update quantity
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE cart_id = ?");
        $stmt->bind_param("i", $existing['cart_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // ✅ Insert new cart row
        $stmt = $conn->prepare("INSERT INTO cart (users_id, products_id, quantity) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $users_id, $products_id);
        $stmt->execute();
        $stmt->close();
    }

    echo htmlspecialchars($product['name']) . " added to cart!";
} else {
    echo "Invalid request.";
}
?>
