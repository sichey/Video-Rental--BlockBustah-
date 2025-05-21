<?php

session_start();
include "config.php"; 
error_log('Session username: ' . (isset($_SESSION['username']) ? $_SESSION['username'] : 'No username in session'));


if (isset($_SESSION['username']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username']; 
    $newBalance = $_POST['newBalance']; 
    
    error_log('Attempting to update balance. Username: ' . $username . ', New Balance: ' . $newBalance);

    
    $stmt = $conn->prepare("UPDATE account SET balance = ? WHERE username = ?");
    $stmt->bind_param("ds", $newBalance, $username); 

    
    if ($stmt->execute()) {
        
        echo json_encode(['success' => true]);
        error_log('Balance updated successfully for username: ' . $username);
        

    } else {
        
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        error_log('Error updating balance for username ' . $username . ': ' . $stmt->error);
    }

    
    $stmt->close();
} else {
    
    echo json_encode(['success' => false, 'error' => 'Not logged in or incorrect request method.']);
}
?>
