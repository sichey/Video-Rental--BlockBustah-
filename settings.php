<?php
session_start();
include "config.php";

if (isset($_SESSION['js_alert'])) {
    echo "<script>alert('" . $_SESSION['js_alert'] . "');</script>";
    unset($_SESSION['js_alert']); 
}


if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_account'])) {
        // Handle account update
        $new_username = $_POST['new_username'] ?? $username;
        $new_email = $_POST['new_email'] ?? '';
        $new_password = $_POST['new_password'] ?? ''; 

        // Update the user account
        $stmt = $conn->prepare("UPDATE account SET username = ?, email = ? WHERE username = ?");
        $stmt->bind_param('sss', $new_username, $new_email, $username);

        // Check if the password field is filled to update it
        if (!empty($new_password)) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE account SET username = ?, email = ?, password = ? WHERE username = ?");
            $stmt->bind_param('ssss', $new_username, $new_email, $new_password_hashed, $username);
        } else {
            $stmt = $conn->prepare("UPDATE account SET username=?, email=? WHERE username=?");
            $stmt->bind_param('sss', $new_username, $new_email, $username);
        }

        if ($stmt->execute()) {
            
            if ($username != $new_username) {
                $_SESSION['username'] = $new_username;
            }
            $_SESSION['js_alert'] = "Account updated successfully";
        } else {
            $_SESSION['js_alert'] = "Error updating account";
        }

        header('Location: settings.php');
        exit;

    } /*elseif (isset($_POST['delete_account'])) {
        // Handle account deletion
        $stmt = $conn->prepare("DELETE FROM account WHERE username = ?");
        $stmt->bind_param('s', $username);

        if ($stmt->execute()) {
            // Success, destroy the session and redirect to login page
            $_SESSION['js_alert'] = "Account deleted successfully";
            header('Location: logout.php');
            exit;
        } else {
            // Error during deletion
            $_SESSION['js_alert'] = "Error updating account";
        } 
    } */
}

if (isset($_POST['delete_account'])) {
    $conn->begin_transaction();
    try {
        // Retrieve all rentals for the user
        $rentals_stmt = $conn->prepare("SELECT movie_id FROM rentals WHERE username = ?");
        $rentals_stmt->bind_param('s', $username);
        $rentals_stmt->execute();
        $rentals_result = $rentals_stmt->get_result();
        
        while ($rental = $rentals_result->fetch_assoc()) {
            // Increment stocks for each rented movie
            $update_stock_stmt = $conn->prepare("UPDATE movies SET stocks = stocks + 1 WHERE movie_id = ?");
            $update_stock_stmt->bind_param('i', $rental['movie_id']);
            $update_stock_stmt->execute();
        }

        // Delete the rentals for the user
        $delete_rentals_stmt = $conn->prepare("DELETE FROM rentals WHERE username = ?");
        $delete_rentals_stmt->bind_param('s', $username);
        $delete_rentals_stmt->execute();

        // Delete the account
        $delete_account_stmt = $conn->prepare("DELETE FROM account WHERE username = ?");
        $delete_account_stmt->bind_param('s', $username);
        $delete_account_stmt->execute();

        $conn->commit();

        $_SESSION['js_alert'] = "Account and associated rentals deleted successfully.";
        header('Location: logout.php'); 
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['js_alert'] = "Error deleting account.";
        header('Location: settings.php');
        exit;
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['top_up'])) {
    $top_up_amount = $_POST['top_up_amount'] ?? 0;
    $top_up_amount = filter_var($top_up_amount, FILTER_VALIDATE_FLOAT); 
    
    if ($top_up_amount > 0) {
        
        $stmt = $conn->prepare("UPDATE account SET balance = balance + ? WHERE username = ?");
        $stmt->bind_param('ds', $top_up_amount, $username); 

        if ($stmt->execute()) {
            $_SESSION['js_alert'] = "Balance topped up successfully by ₱" . $top_up_amount;
        } else {
            $_SESSION['js_alert'] = "Error topping up balance";
        }
        header('Location: settings.php');
        exit;
    } else {
        $_SESSION['js_alert'] = "Invalid top-up amount";
        header('Location: settings.php');
        exit;
    }
}

// Fetch user info
$stmt = $conn->prepare("SELECT username, email, balance FROM account WHERE username = ? LIMIT 1"); 
if ($stmt === false) {
    
    die("Failed to prepare statement: " . $conn->error);
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();





if (!$user_info) {
    echo "User not found or error fetching user data";
    exit;
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function toggleEditForm() {
            var form = document.getElementById('editForm');
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
    <div class="container settings-container">
        <h1>Account Settings</h1>
        <div class="account-info">
            <p>Username: <span id="currentUsername"><?= htmlspecialchars($user_info['username']) ?></span></p>
            <p>Email: <span id="currentEmail"><?= htmlspecialchars($user_info['email']) ?></span></p>
            <p>Balance: ₱<span id="currentBalance"><?= htmlspecialchars($user_info['balance']) ?></span></p>
        </div>
        <div id="editForm" class="edit-form" style="display: none;">
            <form method="POST" action="settings.php">
                <label for="new_username">New Username:</label>
                <input type="text" id="new_username" name="new_username" value="<?= htmlspecialchars($user_info['username']) ?>">
                <label for="new_email">New Email:</label>
                <input type="email" id="new_email" name="new_email" value="<?= htmlspecialchars($user_info['email']) ?>">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password">
                <button type="submit" name="update_account" class="button">Update Account</button>
            </form>
            <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete your account?');">
                <button type="submit" name="delete_account" class="button">Delete Account</button>
            </form>

            <div class="top-up-form">
            <h3>Balance Top-Up</h3>
            <form method="POST" action="settings.php">
                <label for="top_up_amount">Amount:</label>
                <input type="number" id="top_up_amount" name="top_up_amount" step="0.01" min="0.01" placeholder="Enter amount to deposit">
                <button type="submit" name="top_up" class="button">Deposit</button>
            </form>
        </div>
        </div>
        <button onclick="toggleEditForm()" class="button edit-button">Edit</button>
        <a href="index.php" class="button homie-button">Home</a>
    </div>
</body>
</html>
