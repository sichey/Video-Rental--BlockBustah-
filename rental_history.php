<?php
session_start();
include "config.php"; 

if (!isset($_SESSION['username'])) {
    
    echo "<p>User not logged in. Please <a href='login.php'>login</a>.</p>";
    exit;
}


$username = $_SESSION['username'];


$query = "SELECT rental_id, movie_id, rental_title AS title, rental_date, expiration_date FROM rentals WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

$rentals = $result->fetch_all(MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <div class="user-controls">
    <a href="index.php" class="button home-button">Home</a>
    
</div>

<div class="header">
    <h1>Block Bustah</h1>
</div>

    <title>Rental History</title>
    <link rel='stylesheet' href='styles.css'> 
</head>
<body>

    <?php if (isset($_SESSION['js_alert'])): ?>
        <script>alert('<?= $_SESSION['js_alert'] ?>');</script>
        <?php unset($_SESSION['js_alert']); ?>
    <?php endif; ?>
    
    <h3>Rental History</h3>
    <div class="rental-history-container">
        <?php if (count($rentals) > 0): ?>
        <form method="POST" action="return_movies.php">
            <table class="movie-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Rental Date</th>
                        <th>Expiration Date</th>
                        <th>Select</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td><?= htmlspecialchars($rental['title']) ?></td>
                        <td><?= htmlspecialchars($rental['rental_date']) ?></td>
                        <td><?= htmlspecialchars($rental['expiration_date']) ?></td>
                        <td>
                        <input type="checkbox" name="return_movies[]" value="<?= htmlspecialchars($rental['rental_id']) ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="return_selected" class="button-return">Return Movies</button>
        </form>

        <?php else: ?>
        <p>No rental history found.</p>
        <?php endif; ?>
    </div>

</body>
</html>