<?php
session_start();
include "config.php";

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT * FROM account WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        
        $_SESSION['registerError'] = "Username or email already exists!";
    } else {
        
        $insertStmt = $conn->prepare("INSERT INTO account (username, password, email) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $username, $hashed_password, $email);

        if ($insertStmt->execute()) {
            
            $_SESSION['registerSuccess'] = "User Registered Successfully.";
        } else {
            
            $_SESSION['registerError'] = "User Registration Failed: " . $insertStmt->error;
        }
        $insertStmt->close();
    }
    
    $stmt->close();
    $conn->close();

    
    header('Location: index.php');
    exit();
}
?>
