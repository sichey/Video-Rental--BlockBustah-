<?php
session_start();
include "config.php"; 


error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/apache/logs/error.log');


if (isset($_SESSION['username'], $_POST['movieId'], $_POST['rentalDays'])) {
    $username = $_SESSION['username'];
    $movieId = $_POST['movieId'];
    $rentalDays = $_POST['rentalDays'];

    error_log("Username: $username, Movie ID: $movieId, Rental Days: $rentalDays");

    
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    try {
        
        $stmt = $conn->prepare("SELECT title FROM movies WHERE movie_id = ?");
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $rentalTitle = $row['title'];
        } else {
            throw new Exception("Movie not found.");
        }
        $stmt->close();

        
        $expirationDate = date('Y-m-d', strtotime("+$rentalDays days"));

        
        $stmt = $conn->prepare("INSERT INTO rentals (username, movie_id, rental_title, rental_date, expiration_date) VALUES (?, ?, ?, CURDATE(), ?)");
        
        $stmt->bind_param("siss", $username, $movieId, $rentalTitle, $expirationDate);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE movies SET stocks = stocks - 1 WHERE movie_id = ?");
        $stmt->bind_param("i", $movieId); 
        $stmt->execute();
        $stmt->close();

        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Rental processed successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Rental processing failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
