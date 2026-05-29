<?php
/**
 * Authentication helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'db.php';

/**
 * Require the user to be logged in.
 */
function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require superadmin access.
 */
function require_superadmin()
{
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
        $_SESSION['message'] = 'Access denied. Super admin only.';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

/**
 * Get the currently logged in user from session.
 */
function get_logged_in_user()
{
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'] ?? 'user',
        ];
    }
    return null;
}

/**
 * Set login session values.
 */
function login_user(array $user)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
}

/**
 * Logout the current user.
 */
function logout_user()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
?>