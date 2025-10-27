<?php
// db.php - Database connection file

$host = "localhost";
$user = "root";   // no hardcode
$pass = "";
$dbname = "maison_sakura";
//connect to database
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
