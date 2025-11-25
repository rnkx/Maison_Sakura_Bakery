<?php 
session_start(); 
include("db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "vendor/autoload.php";

// If not logged in OR not customer, redirect to login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit();
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize user inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
      $errors = [];
        // Validate email (only Gmail & Hotmail)
if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail|hotmail)\.com$/", $email)) {
     echo "<p style='color:red;'>Only Gmail or Hotmail email addresses with .com are allowed.</p>";
    echo '<p><button onclick="window.history.back()">Back</button></p>';
    exit();
}
        // Basic validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($message)) $errors[] = "Message cannot be empty.";
    
   $mail = new PHPMailer(true);
   
    if (empty($errors)) {
        // 1️⃣ Save to database
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);
        
          $dbSaved = $stmt->execute();
        $stmt->close();
        
         // 2️⃣ Send email
        $mailSent = false;
        $mailError = '';
        $mail = new PHPMailer(true);
   
   try{
       $mail -> isSMTP();
       $mail -> Host = "smtp.gmail.com";
       $mail -> SMTPAuth = true;
       $mail -> Username = "rngkxxx@gmail.com"; //my  gmail
       $mail -> Password = "qnpy aqpr fhex xbte"; //App Password
       $mail -> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail -> Port = 587;
       
       $mail -> setFrom("rngkxxx@gmail.com", "Rachel");
       $mail -> addAddress("rachel.ng.ker.xin@gmail.com", "Rachel Ng");
       
       $mail -> Subject = "Customer Feedback for Maison Sakura Bakery";
       $mail -> Body = "Name: $name\n".
                       "Email: $email\n".
                       "Message: $message";
           $mailSent = $mail->send();
        } catch (Exception $e) {
            $mailError = $mail->ErrorInfo;
        }

        // Show result
        if ($dbSaved && $mailSent) {
            echo "<h2>Thank you, $name!</h2>";
            echo "<p>Your message has been saved and an email has been sent. We’ll get back to you at $email.</p>";
             echo '<p><a href="customer_index.php"><button type="button">Back to Home Page</button></a></p>';
        } elseif ($dbSaved && !$mailSent) {
            echo "<h2>Thank you, $name!</h2>";
            echo "<p>Your message has been saved, but email could not be sent. Error: $mailError</p>";
             echo '<p><a href="customer_index.php"><button type="button">Back to Home Page</button></a></p>';
        } else {
            echo "<h2>Sorry!</h2>";
            echo "<p>There was a problem saving your message. Please try again.</p>";
             echo '<p><a href="customer_index.php"><button type="button">Back to Home Page</button></a></p>';
        }
    } else {
        // Show validation errors
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
    }

    $conn->close();
}
?>