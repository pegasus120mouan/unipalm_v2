<?php
session_start();

// Unset all known auth-related session variables
unset($_SESSION['user_id']);
unset($_SESSION['nom']);
unset($_SESSION['prenoms']);
unset($_SESSION['user_role']);
unset($_SESSION['avatar']);

// Optionally clear the whole session array
$_SESSION = [];

// Destroy the session cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;
?>