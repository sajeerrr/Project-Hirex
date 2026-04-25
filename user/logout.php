<?php
/**
 * Logout Script - HireX
 * Purpose: Securely end user session and redirect to login page
 */

// Start the session (required to access/destroy it)
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    // Set cookie expiration to past date to delete it
    setcookie(
        session_name(), 
        '', 
        time() - 3600, 
        '/', 
        '', 
        isset($_SERVER['HTTPS']), 
        true  // HttpOnly
    );
}

// Destroy the session completely
session_destroy();

// Clear any session-related cookies (additional security)
$cookies = ['user_id', 'user_token', 'remember_me'];
foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie(
            $cookie, 
            '', 
            time() - 3600, 
            '/', 
            '', 
            isset($_SERVER['HTTPS']), 
            true
        );
    }
}

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
header("Location: ../index.php");

// Ensure no further code executes
exit;
?>
