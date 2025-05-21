<?php
session_start(); 
include "config.php";

$isLoggedIn = isset($_SESSION['user_id']);

if (isset($_SESSION['loginError'])) {
    $loginError = $_SESSION['loginError'];
    unset($_SESSION['loginError']); 
}


if (isset($_SESSION['registerSuccess'])) {
    $registerSuccess = $_SESSION['registerSuccess'];
    unset($_SESSION['registerSuccess']);
}

if (isset($_SESSION['registerError'])) {
    $registerError = $_SESSION['registerError'];
    unset($_SESSION['registerError']);
}

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT balance FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userBalance = $row['balance'];
    } else {
        $userBalance = 1000; 
    }
    $stmt->close();
} else {
    $userBalance = 0; 
}

$query = "SELECT *, DATE_FORMAT(release_date, '%Y') as year FROM movies WHERE availability = 'available' ORDER BY release_date DESC";

$result = $conn->query($query);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Gallery</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<div class="header">
    <div class="header-logo">
        <span class="header-text">Block</span>
        <div class="circle-text">Bustah</div>
    </div>


    <div class="header-search">
        <div class="search-form">
            <input type="text" id="liveSearchQuery" placeholder="Search for movies..." required>
        </div>
    </div>


    <button id="filterToggle" class="filter-toggle-button">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
    </button>


    <!-- Filter Form -->
    <div id="filterMenu" class="filter-menu">
        <form action="filter.php" method="GET" class="filter-form">
            <!-- Release Date Filter -->
            <select name="release_date" id="releaseDateFilter">
                <option value="">Release Date</option>
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
            </select>

            <!-- Genre Filter -->
            <select name="genre" id="genreFilter">
                <option value="">Genre</option>
                <option value="Action">Action</option>
                <option value="Comedy">Comedy</option>
                <option value="Drama">Drama</option>
                <option value="Family">Family</option>
                <option value="Horror">Horror</option>
                <option value="Musical">Musical</option>
                <option value="Romance">Romance</option>
                <option value="Sci-Fi">Sci-Fi</option>
                <option value="Fantasy">Fantasy</option>
            </select>


            <!-- Rating Filter -->
            <select name="rating" id="ratingFilter">
                <option value="">Rating</option>
                <option value="highest">Highest</option>
                <option value="lowest">Lowest</option>
            </select>

            <!-- Price Filter -->
            <select name="price" id="priceFilter">
                <option value="">Price</option>
                <option value="highest">Highest</option>
                <option value="lowest">Lowest</option>
            </select>

            <button type="submit" class="filter-button" disabled>Information</button>
        </form>
    </div>

    <div class="user-controls">
        <?php if ($isLoggedIn): ?>
            <div class="user-profile">
                <div class="username-trigger">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                    <div class="user-options">
                        <a href="rental_history.php" class="button">Rental History</a>
                        <a href="settings.php" class="button">Settings</a>
                    </div>
                </div>
                <a href="logout.php" class="button logout">Logout</a>
            </div>
        <?php else: ?>
            <button id="loginButton" class="button">Login</button>
            <button id="registerButton" class="button">Register</button>
        <?php endif; ?>
    </div>

    
    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>

            <h2>Block Bustah</h2>

            <form id="loginForm" action="login.php" method="post">
                <input type="text" name="username" placeholder="Enter Username" required>
                <input type="password" name="password" placeholder="Enter Password" required>
                <br><?php if (isset($loginError) && $loginError !== ''): ?>
                <p class="login-error"><?php echo $loginError; ?></p>
            <?php endif; ?></br>
                <button type="submit" name="submit">Log In</button>
                
            </form>

        </div>
    </div>

        <!-- Registration Modal -->
    <div id="registerModal" class="modal">
            <div class="modal-content">

                <span class="close">&times;</span>

                <h2>Block Bustah</h2>
                
                <form id="registerForm" action="register.php" method="post">
                    <input type="text" name="username" placeholder="Enter Username" required>
                    <input type="password" name="password" placeholder="Enter Password" required>
                    <input type="email" name="email" placeholder="Enter Email" required>

                    <?php if (isset($registerSuccess) && $registerSuccess !== ''): ?>
                        <p class="register-success"><?php echo $registerSuccess; ?></p>
                    <?php endif; ?>

                    <?php if (isset($registerError) && $registerError !== ''): ?>
                        <p class="register-error"><?php echo $registerError; ?></p>
                    <?php endif; ?>

                    <button type="submit" name="submit">Register</button>
                </form>
            </div>
        </div>
    </div> 


    <div class="movie-gallery">
        <?php while($movie = $result->fetch_assoc()): ?>    
            <div class="movie-card"
                data-movie-id="<?php echo htmlspecialchars($movie['movie_id']); ?>"
                data-title="<?php echo htmlspecialchars($movie['title']); ?>"
                data-synopsis="<?php echo htmlspecialchars($movie['synopsis']); ?>"
                data-rating="<?php echo htmlspecialchars($movie['rating']); ?>"
                data-price="<?php echo htmlspecialchars($movie['price']); ?>"
                data-imageurl="<?php echo htmlspecialchars($movie['image_url']); ?>"
                data-video-url="<?php echo htmlspecialchars($movie['video_url']); ?>"
                data-availability="<?php echo htmlspecialchars($movie['availability']); ?>"
                data-stocks="<?php echo htmlspecialchars($movie['stocks']); ?>">

                <div class="movie-image" style="background-image: url('<?php echo htmlspecialchars($movie['image_url']); ?>');">
                    <div class="movie-synopsis">
                        <?php echo htmlspecialchars($movie['synopsis']); ?>
                    </div>
                </div>
                <div class="movie-info">
                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p class="release-date"><?php echo date('Y', strtotime($movie['release_date'])); ?></p>
                    <?php if ($movie['stocks'] > 0): ?>
                    <a href="#" class="rent-button" data-movie-id="<?php echo htmlspecialchars($movie['movie_id']); ?>">Rent</a>
                    <?php else: ?>
                    <button class="out-of-stock-button" disabled>Out of Stock</button>
                    <?php endif; ?>

                </div>
            </div>
        <?php endwhile; ?>
    </div>


    <!-- Rental Modal -->

    <div id="rentalModal" class="modal">
        <input type="hidden" id="selectedMovieId" value="">
        <div class="modal-content">
            <span class="close" onclick="closeModal('rentalModal')">&times;</span>
            <h2 id="rentalMovieTitle"></h2>
            <div id="rentalTrailerContainer"></div>
            <p>Rating: <span id="rentalMovieRating"></span></p>
            <p>Price: ₱<span id="rentalMoviePrice"></span></p>
            <input type="number" id="rentalDays" min="1" placeholder="Number of days" onchange="updateTotalCost()">
            <p>Total Cost: ₱<span id="rentalTotalCost"></span></p>
            <p>Current Balance: ₱<span id="userBalance"></span></p>
            <button id="checkoutButton">Check Out</button>
        </div>
    </div>


    <div class="pagination">
    </div>

    <script>
        var isLoggedIn = <?php echo json_encode($isLoggedIn ? 'true' : 'false'); ?>;
        var userBalance = <?php echo json_encode($userBalance); ?>;
    </script>



    <script src="scripts.js"></script>


</body>

<footer class="site-footer">
    <div class="container">
        <div class="footer-about">
            <h4>About Block Bustah</h4>
            <p>Christopher John P. Rigor - Earl Josh Santos - Thomas Jacob Cammayo V</p>
            <p>© Information Management Final Output</p>
        </div>
</html>
