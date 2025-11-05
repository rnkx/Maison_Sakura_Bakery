<?php
session_start();
include("db.php");

// ✅ Restrict access to superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login_superadmin.php");
    exit();
}

// ===========================
// 📊 DASHBOARD STATISTICS
// ===========================

// Normalize payment_status filter
$paid_statuses = "('payment success','received')";

// ---------------------------
// Total successful orders
$total_sales_sql = "
    SELECT COUNT(*) AS total_sales 
    FROM orders 
    WHERE TRIM(LOWER(payment_status)) IN $paid_statuses
";
$total_sales_result = mysqli_query($conn, $total_sales_sql);
$total_sales = mysqli_fetch_assoc($total_sales_result)['total_sales'] ?? 0;

// ---------------------------
// Total revenue (before discount)
$total_revenue_sql = "
    SELECT SUM(oi.price_original * oi.quantity) AS total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE TRIM(LOWER(o.payment_status)) IN $paid_statuses
";
$total_revenue_result = mysqli_query($conn, $total_revenue_sql);
$total_revenue = mysqli_fetch_assoc($total_revenue_result)['total_revenue'] ?? 0;

// ---------------------------
// Total revenue after discount
$revenue_after_discount_sql = "
    SELECT SUM(oi.price_after_discount * oi.quantity) AS revenue_after_discount
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE TRIM(LOWER(o.payment_status)) IN $paid_statuses
";
$rev_discount_result = mysqli_query($conn, $revenue_after_discount_sql);
$revenue_after_discount = mysqli_fetch_assoc($rev_discount_result)['revenue_after_discount'] ?? 0;

// ---------------------------
// Revenue lost due to discount
$lost_revenue = $total_revenue - $revenue_after_discount;

// ---------------------------
// Unique customers
$total_customers_sql = "
    SELECT COUNT(DISTINCT users_id) AS total_customers 
    FROM orders 
    WHERE TRIM(LOWER(payment_status)) IN $paid_statuses
";
$total_customers_result = mysqli_query($conn, $total_customers_sql);
$total_customers = mysqli_fetch_assoc($total_customers_result)['total_customers'] ?? 0;

// ---------------------------
// Cancelled & Refunded orders
$cancelled_sql = "
    SELECT COUNT(*) AS total_cancelled 
    FROM orders 
    WHERE TRIM(LOWER(payment_status)) = 'cancelled & refunded'
";
$cancelled_result = mysqli_query($conn, $cancelled_sql);
$total_cancelled = mysqli_fetch_assoc($cancelled_result)['total_cancelled'] ?? 0;

// ===========================
// 📈 MONTHLY REVENUE TREND (After Discount) — Full Year 2025
// ===========================
$monthly_sql = "
    SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS month,
           SUM(oi.price_after_discount * oi.quantity) AS revenue
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.order_id
    WHERE TRIM(LOWER(o.payment_status)) IN $paid_statuses
      AND YEAR(o.created_at) = 2025
    GROUP BY month
    ORDER BY month ASC
";
$monthly_result = mysqli_query($conn, $monthly_sql);

// Build associative array for quick lookup
$revenue_by_month = [];
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $revenue_by_month[$row['month']] = (float)$row['revenue'];
}

// Generate all months of 2025 (even missing)
$months = [];
$revenues = [];
for ($m = 1; $m <= 12; $m++) {
    $month_key = sprintf("2025-%02d", $m);
    $months[] = $month_key;
    $revenues[] = $revenue_by_month[$month_key] ?? 0; // Default 0 if no data
}

// ===========================
// 📈 YEARLY REVENUE TREND (After Discount) — From 2022 to 2025
// ===========================
$yearly_sql = "
    SELECT YEAR(o.created_at) AS year,
           SUM(oi.price_after_discount * oi.quantity) AS revenue
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.order_id
    WHERE TRIM(LOWER(o.payment_status)) IN $paid_statuses
      AND YEAR(o.created_at) BETWEEN 2022 AND 2025
    GROUP BY year
    ORDER BY year ASC
";
$yearly_result = mysqli_query($conn, $yearly_sql);

// Build associative array
$revenue_by_year = [];
while ($row = mysqli_fetch_assoc($yearly_result)) {
    $revenue_by_year[$row['year']] = (float)$row['revenue'];
}

// Generate all years 2022–2025 (even missing)
$years = [];
$yearly_revenues = [];
for ($y = 2022; $y <= 2025; $y++) {
    $years[] = $y;
    $yearly_revenues[] = $revenue_by_year[$y] ?? 0;
}


// ===========================
// 💰 TOP SELLING PRODUCTS (by revenue after discount)
// ===========================
$top_products_sql = "
    SELECT p.name,
           SUM(oi.price_after_discount * oi.quantity) AS revenue_after_discount,
           SUM(oi.price_original * oi.quantity) AS revenue_before_discount,
           SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN products p ON oi.products_id = p.products_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE TRIM(LOWER(o.payment_status)) IN $paid_statuses
    GROUP BY p.products_id
    ORDER BY revenue_after_discount DESC
    LIMIT 5
";
$top_products_result = mysqli_query($conn, $top_products_sql);
$top_products = mysqli_fetch_all($top_products_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales & Revenue Reports - Superadmin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#fdf0f4; font-family:Arial,sans-serif; }
.card { border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
h2,h4 { color:#e75480; }
</style>
</head>
<body>
<div class="container my-5">
    <h2 class="text-center mb-4">📊 Sales & Revenue Reports</h2>

    <div class="row text-center mb-4">
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Total Successful Orders</h5>
                <h3 class="text-primary"><?= $total_sales ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Total Revenue (Before Discount)</h5>
                <h3 class="text-success">RM <?= number_format($total_revenue,2) ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Revenue After Discounts</h5>
                <h3 class="text-success">RM <?= number_format($revenue_after_discount,2) ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Revenue Lost Due to Discounts</h5>
                <h3 class="text-warning">RM <?= number_format($lost_revenue,2) ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Unique Customers</h5>
                <h3 class="text-info"><?= $total_customers ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3">
                <h5>Cancelled & Refunded Orders</h5>
                <h3 class="text-danger"><?= $total_cancelled ?></h3>
            </div>
        </div>
    </div>

<!-- Monthly Revenue Chart -->
<div class="card p-3 mb-4">
    <h4 class="text-center mb-3">Monthly Revenue Trend (Jan–Dec 2025, After Discount)</h4>
    <canvas id="monthlyRevenueChart"></canvas>
</div>

<!-- Yearly Revenue Chart -->
<div class="card p-3 mb-4">
    <h4 class="text-center mb-3">Yearly Revenue Trend (2022–2025, After Discount)</h4>
    <canvas id="yearlyRevenueChart"></canvas>
</div>


    <!-- Top Selling Products -->
    <div class="card p-3 mb-4">
        <h4 class="mb-3">Top 5 Products by Revenue</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Total Sold</th>
                    <th>Revenue Before Discount (RM)</th>
                    <th>Revenue After Discount (RM)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($top_products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= $product['total_sold'] ?></td>
                    <td><?= number_format($product['revenue_before_discount'],2) ?></td>
                    <td><?= number_format($product['revenue_after_discount'],2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-4">
        <a href="superadmin_index.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
    </div>
</div>


<script>
// Monthly Revenue Chart
const ctxMonthly = document.getElementById('monthlyRevenueChart');
new Chart(ctxMonthly, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Monthly Revenue (RM)',
            data: <?= json_encode($revenues) ?>,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Yearly Revenue Chart
const ctxYearly = document.getElementById('yearlyRevenueChart');
new Chart(ctxYearly, {
    type: 'line',
    data: {
        labels: <?= json_encode($years) ?>,
        datasets: [{
            label: 'Yearly Revenue (RM)',
            data: <?= json_encode($yearly_revenues) ?>,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.3,
            fill: true,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
