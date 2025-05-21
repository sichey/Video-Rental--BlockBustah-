<?php
session_start();
include 'config.php'; 

$whereClauses = [];
$params = [];
$paramsType = '';


if (!empty($_GET['release_date'])) {
    $orderBy = $_GET['release_date'] === 'newest' ? 'release_date DESC' : 'release_date ASC';
} else {
    $orderBy = 'release_date DESC';
}


if (!empty($_GET['genre']) && $_GET['genre'] !== '') {
    $whereClauses[] = "genre = ?";
    $params[] = $_GET['genre'];
    $paramsType .= 's'; 
}


if (!empty($_GET['rating'])) {

    $orderBy = 'rating ' . ($_GET['rating'] === 'highest' ? 'DESC' : 'ASC');
}


if (!empty($_GET['price'])) {
    $orderBy = 'price ' . ($_GET['price'] === 'highest' ? 'DESC' : 'ASC');
}


$query = "SELECT * FROM movies";
if (!empty($whereClauses)) {
    $query .= ' WHERE ' . implode(' AND ', $whereClauses);
}
$query .= ' ORDER BY ' . $orderBy;

$stmt = $conn->prepare($query);


if (!empty($params)) {
    $stmt->bind_param($paramsType, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$movies = $result->fetch_all(MYSQLI_ASSOC);

ob_start();

if ($result->num_rows > 0) {
    foreach ($movies as $movie) {
        echo '<div class="movie-card">';
        echo '<div class="movie-image" style="background-image: url(' . htmlspecialchars($movie['image_url']) . ');"></div>';
        
        echo '<div class="movie-info">';
        echo '<h3>' . htmlspecialchars($movie['title']) . '</h3>';
        
        echo '<p class="release-date">' . date('Y', strtotime(htmlspecialchars($movie['release_date']))) . '</p>';
        echo '<div class="release-date">' . htmlspecialchars($movie['synopsis']) . '</div>';
        
        echo '<div class="rating-price-container">';
        echo '<div class="rating"><i class="fas fa-star hl mr-1"></i>' . htmlspecialchars($movie['rating']) . '</div>';
        echo '<div class="price">₱' . htmlspecialchars($movie['price']) . '</div>';
        
 
        echo '</div>';
        
        /*if ($movie['stocks'] > 0) {
            echo '<a href="#" class="rent-button" data-movie-id="'.htmlspecialchars($movie['movie_id']).'">Rent</a>';
        } else {
            echo '<button class="out-of-stock-button" disabled>Out of Stock</button>';
        }*/
    
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<p>No movies found based on filter criteria.</p>';
}


$movieCardsHtml = ob_get_clean();
echo $movieCardsHtml;

$stmt->close();
$conn->close();
