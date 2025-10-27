<?php
session_start();
include("db.php");

// ✅ Only admin or superadmin can access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','superadmin'])) {
    header("Location: login_admin.php");
    exit();
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount_type = $_POST['discount_type'];
    $condition_type = $_POST['condition_type'];
    $discount_value = $_POST['discount_value'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $query = "INSERT INTO discounts (title, description, discount_type, condition_type, discount_value, start_date, end_date) 
              VALUES ('$title', '$description', '$discount_type', '$condition_type', '$discount_value', '$start_date', '$end_date')";
    mysqli_query($conn, $query);
}

// ✅ Fetch all discounts
$result = mysqli_query($conn, "SELECT * FROM discounts ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Discounts</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <h2 class="text-center mb-4">🎁 Manage Discounts</h2>

    <!-- Add new discount -->
    <form method="POST" class="card p-4 mb-4 shadow-sm">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>Discount Type</label>
                <select name="discount_type" class="form-control" required>
                    <option value="percentage">Percentage Off</option>
                    <option value="free_item">Free Item</option>
                </select>
            </div>
            <div class="col-md-12">
                <label>Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label>Condition</label>
                <select name="condition_type" class="form-control" required>
                    <option value="birthday">Birthday</option>
                    <option value="general">General</option>
                    <option value="first_order">First Order</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Discount Value (RM / %)</label>
                <input type="number" name="discount_value" class="form-control" step="0.01">
            </div>
            <div class="col-md-3">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control">
            </div>
        </div>
        <button type="submit" class="btn btn-success mt-3">Add Discount</button>
    </form>

    <!-- Display all discounts -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Condition</th>
                <th>Value</th>
                <th>Active</th>
                <th>Start–End</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['discount_type']) ?></td>
                <td><?= htmlspecialchars($row['condition_type']) ?></td>
                <td><?= $row['discount_type'] == 'free_item' ? '🎁 Free Item' : $row['discount_value'] . '%' ?></td>
                <td><?= $row['is_active'] ? '✅ Active' : '❌ Inactive' ?></td>
                <td><?= $row['start_date'] ?> → <?= $row['end_date'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="text-center mt-3">
        <a href="superadmin_index.php" class="btn btn-secondary">⬅ Back</a>
    </div>
</div>

</body>
</html>
