<?php
/**
 * Login page for portal users
 */

session_start();
include 'auth.php';

$message = '';
$message_type = 'error';

// Ensure the users table exists before attempting login
$can_attempt_login = true;
try {
    $check_users_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (!$check_users_table || mysqli_num_rows($check_users_table) === 0) {
        $message = 'Portal is not initialized. Please run <a href="setup.php">setup.php</a> to create the database tables and default superadmin account.';
        $can_attempt_login = false;
    }
} catch (mysqli_sql_exception $e) {
    $message = 'Portal initialization failed. Please run <a href="setup.php">setup.php</a> first.';
    $can_attempt_login = false;
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_attempt_login) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = 'Please enter both username and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                header('Location: index.php');
                exit;
            }
        }
        $message = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Infocrats Document Drive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔐 Infocrats Document Drive Login</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>