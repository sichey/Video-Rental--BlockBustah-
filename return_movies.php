<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_POST['return_selected']) || empty($_POST['return_movies'])) {
    $_SESSION['js_alert'] = "No movies selected for return.";
    header('Location: rental_history.php');
    exit;
}

$username = $_SESSION['username'];
$return_movies = $_POST['return_movies'];

$conn->begin_transaction();
try {
    $expired_query = "SELECT rental_id, movie_id FROM rentals WHERE expiration_date < CURDATE() AND username = ?";
    $expired_stmt = $conn->prepare($expired_query);
    $expired_stmt->bind_param('s', $username);
    $expired_stmt->execute();
    $expired_rentals = $expired_stmt->get_result();

    while ($expired_rental = $expired_rentals->fetch_assoc()) {
        $update_stock_stmt = $conn->prepare("UPDATE movies SET stocks = stocks + 1 WHERE movie_id = ?");
        $update_stock_stmt->bind_param('i', $expired_rental['movie_id']);
        $update_stock_stmt->execute();

        $delete_rental_stmt = $conn->prepare("DELETE FROM rentals WHERE rental_id = ?");
        $delete_rental_stmt->bind_param('i', $expired_rental['rental_id']);
        $delete_rental_stmt->execute();
    }

    $conn->commit();
    
    if ($expired_rentals->num_rows > 0) {
        $_SESSION['js_alert'] = "Expired rentals have been automatically returned.";
    }


    $returned_something = false; 

    foreach ($return_movies as $rental_id) {
        
        $check_query = "SELECT movie_id FROM rentals WHERE rental_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $rental_id);
        $check_stmt->execute();
        $rental = $check_stmt->get_result()->fetch_assoc();
        
        if ($rental) {
            
            $update_query = "UPDATE movies SET stocks = stocks + 1 WHERE movie_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('i', $rental['movie_id']);
            if ($update_stmt->execute()) {
                $returned_something = true; 
            }
    
            
            $delete_query = "DELETE FROM rentals WHERE rental_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $rental_id);
            $delete_stmt->execute();
        }
    }
    

    if ($returned_something) {
        $conn->commit();
        $_SESSION['js_alert'] = "Selected movies have been successfully returned.";
    } else {
        $conn->rollback();
        $_SESSION['js_alert'] = "No movies were returned. Please select movies to return.";
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['js_alert'] = "Error processing movie returns: " . $e->getMessage();
}

header('Location: rental_history.php');
exit;
?>
