<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy the session
session_destroy();

// Set success message for next page
session_start();
$_SESSION['success_message'] = "您已成功登出 / You have been logged out successfully";

// Redirect to home page
header('Location: index.php');
exit;
?>
