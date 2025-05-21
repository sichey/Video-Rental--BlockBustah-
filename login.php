<?php
session_start();
include "config.php";

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $enteredPassword = $_POST['password'];

    if ($username == 'admin' && $enteredPassword == 'admin123') {
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        
        header('Location: admin_panel.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT password FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $storedHash = $row['password'];
        
        if (password_verify($enteredPassword, $storedHash)) {
            $_SESSION['user_id'] = $username; 
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit();
        } else {
            
            $_SESSION['loginError'] = "Incorrect username or password. Please try again.";
            header('Location: index.php');
            exit();
        }

    } else {
        
         $_SESSION['loginError'] = "Incorrect username or password. Please try again.";
         header('Location: index.php');
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
