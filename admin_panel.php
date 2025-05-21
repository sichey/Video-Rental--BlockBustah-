<?php
session_start();
include "config.php";

$query = "SELECT * FROM movies";
$result = $conn->query($query);

$conn->query("ALTER TABLE movies AUTO_INCREMENT = 1");

$movies = [];
if ($result && $result->num_rows > 0) {
    
    $movies = $result->fetch_all(MYSQLI_ASSOC);
}

mysqli_query($conn, "ALTER TABLE movies AUTO_INCREMENT = 1");

$movieUpdatedSuccessfully = false;
$movieAddedSuccessfully = false;

if (isset($_POST['submit'])) {
    
    $title = $_POST['title'];
    $synopsis = $_POST['synopsis'];
    $genre = $_POST['genre'];
    $availability = $_POST['availability'];
    $stocks = $_POST['stocks'];
    $release_date = $_POST['release_date'];
    $image_url = $_POST['image_url'];
    $rating = $_POST['rating'];
    $price = $_POST['price'];
    $video_url = $_POST['video_url'];

    $stmt = $conn->prepare("INSERT INTO movies (title, synopsis, genre, availability, stocks, release_date, image_url, rating, price, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissdds", $title, $synopsis, $genre, $availability, $stocks, $release_date, $image_url, $rating, $price, $video_url);

    
    if ($stmt->execute()) {
        echo "<script>alert('Movie Added Successfully');</script>";
        $movieAddedSuccessfully = true;
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }


    
    header('Location: admin_panel.php');
    exit();
    $stmt->close();
    $conn->close();
}


if (isset($_POST['update'])) {
    $id = $_POST['movie_id'];
    $title = $_POST['title'];
    $synopsis = $_POST['synopsis'];
    $genre = $_POST['genre'];
    $availability = $_POST['availability'];
    $stocks = $_POST['stocks'];
    $release_date = $_POST['release_date'];
    $image_url = $_POST['image_url'];
    $rating = $_POST['rating'];
    $price = $_POST['price'];
    $video_url = $_POST['video_url'];

    $stmt = $conn->prepare("UPDATE movies SET title = ?, synopsis = ?, genre = ?, availability = ?, stocks = ?, release_date = ?, image_url = ?, rating = ?, price = ?, video_url = ? WHERE movie_id = ?");
    $stmt->bind_param("ssssissddsi", $title, $synopsis, $genre, $availability, $stocks, $release_date, $image_url, $rating, $price, $video_url, $id);


    if ($stmt->execute()) {
        echo "<script>alert('Movie Updated Successfully');</script>";
        $movieUpdatedSuccessfully = true;
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    if ($movieUpdatedSuccessfully) {
        
        $_SESSION['message'] = 'Movie Updated Successfully';

        
        header('Location: admin_panel.php');
        exit;
    }
} else if (isset($_SESSION['message'])) {
    
    echo "<script>alert('{$_SESSION['message']}');</script>";
    unset($_SESSION['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    if ($movieAddedSuccessfully) {
        
        $_SESSION['message'] = 'Movie Added Successfully';

        
        header('Location: admin_panel.php');
        exit;
    }
} else if (isset($_SESSION['message'])) {
    echo "<script>alert('{$_SESSION['message']}');</script>";
    unset($_SESSION['message']);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    
    $movieId = $_POST['movie_id'];

    
    $stmt = $conn->prepare('DELETE FROM movies WHERE movie_id = ?');
    $stmt->bind_param('i', $movieId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        
        $_SESSION['message'] = 'Movie Deleted Successfully';
        header('Location: admin_panel.php');
        exit;

    } else {
        $_SESSION['message'] = 'Error: Could not delete movie';

        header('Location: admin_panel.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    
    $movieId = $_POST['movie_id'];

    $conn->begin_transaction();

    try {
        
        $stmt = $conn->prepare('DELETE FROM movies WHERE movie_id = ?');
        $stmt->bind_param('i', $movieId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            
            $conn->commit();

            $_SESSION['message'] = 'Movie Deleted Successfully';
        } else {
            
            $conn->rollback();

            $_SESSION['message'] = 'Error: Could not delete movie';
        }
    } catch (Exception $e) {
        
        $conn->rollback();

        $_SESSION['message'] = 'Error: ' . $e->getMessage();
    }

    header('Location: admin_panel.php');
    exit;
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Movie Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="user-controls">
        <a href="logout.php" class="button">Logout</a>
    </div>
    
    <h3>Movie Management</h3>
    <form method="post" class="movie-form">
        <input type="text" name="title" placeholder="Movie Title" required>
        <textarea name="synopsis" placeholder="Synopsis" required></textarea>
        <input type="text" name="genre" placeholder="Genre" required>
        <select name="availability" required>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
        </select>
        <input type="number" name="stocks" placeholder="Number of Stocks" required>
        <input type="date" name="release_date" placeholder="Release Date" required>
        <input type="text" name="image_url" placeholder="Image URL" required>
        <input type="number" step="0.1" name="rating" placeholder="Rating" required>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <input type="text" name="video_url" placeholder="Trailer Video URL" required>
        <button type="submit" name="submit">Add Movie</button>
    </form>

    <table class="movie-table">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Synopsis</th>
            <th>Genre</th>
            <th>Availability</th>
            <th>Stocks</th>
            <th>Release Date</th>
            <th>Image URL</th>
            <th>Rating</th>
            <th>Price</th>

        </tr>
        
        <?php foreach ($movies as $movie): ?>
        <tr>
            <td><?php echo htmlspecialchars($movie['movie_id']); ?></td>
            <td><?php echo htmlspecialchars($movie['title']); ?></td>
            <td><?php echo htmlspecialchars($movie['synopsis']); ?></td>
            <td><?php echo htmlspecialchars($movie['genre']); ?></td>
            <td><?php echo htmlspecialchars($movie['availability']); ?></td>
            <td><?php echo htmlspecialchars($movie['stocks']); ?></td>
            <td><?php echo htmlspecialchars($movie['release_date']); ?></td>
            <td><img src="<?php echo htmlspecialchars($movie['image_url']); ?>" alt="Movie Image" style="width: 100px; height: auto;"></td>
            <td><?php echo htmlspecialchars($movie['rating']); ?></td>
            <td>₱<?php echo htmlspecialchars($movie['price']); ?></td>
            
            <td>
                <button onclick="showUpdateForm(<?php echo $movie['movie_id']; ?>)">Edit</button>
                
                <form id="update-form-<?php echo $movie['movie_id']; ?>" method="post" style="display: none;">
                    <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movie['movie_id']); ?>">
                    <input type="text" name="title" placeholder="Title" value="<?php echo htmlspecialchars($movie['title']); ?>">
                    <textarea name="synopsis" placeholder="Synopsis"><?php echo htmlspecialchars($movie['synopsis']); ?></textarea>
                    <input type="text" name="genre" placeholder="Genre" value="<?php echo htmlspecialchars($movie['genre']); ?>">
                    <select name="availability">
                        <option value="Available" <?php echo $movie['availability'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Unavailable" <?php echo $movie['availability'] == 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                    <input type="number" name="stocks" placeholder="Stocks" value="<?php echo htmlspecialchars($movie['stocks']); ?>">
                    <input type="date" name="release_date" value="<?php echo htmlspecialchars($movie['release_date']); ?>">
                    <input type="text" name="image_url" placeholder="Image URL" value="<?php echo htmlspecialchars($movie['image_url']); ?>">
                    <input type="number" step="0.1" name="rating" placeholder="Rating" value="<?php echo htmlspecialchars($movie['rating']); ?>">
                    <input type="number" step="0.01" name="price" placeholder="Price" value="<?php echo htmlspecialchars($movie['price']); ?>">
                    <input type="text" name="video_url" placeholder="Trailer Video URL" value="<?php echo htmlspecialchars($movie['video_url']); ?>">
                    <input type="submit" name="update" value="Update">
                </form>
                <form method="post" onsubmit="return confirmDelete()">
                    <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movie['movie_id']); ?>">
                    <input type="submit" name="delete" value="Delete">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <script src="scripts.js"></script>
</body>
</html>
