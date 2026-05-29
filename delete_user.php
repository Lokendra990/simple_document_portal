<?php
/**
 * Delete user account (superadmin only)
 */

include 'auth.php';
require_superadmin();

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid user ID.');
    }

    $user_id = intval($_GET['id']);
    $current_user = get_logged_in_user();

    if ($current_user['id'] === $user_id) {
        throw new Exception('You cannot delete your own account while logged in.');
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Unable to delete user.');
    }

    mysqli_stmt_close($stmt);
    $_SESSION['message'] = 'User deleted successfully.';
    $_SESSION['message_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

header('Location: users.php');
exit;
?>