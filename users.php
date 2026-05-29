<?php
/**
 * User management page for superadmin
 */

include 'auth.php';
require_superadmin();

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '' || $password === '' || !in_array($role, ['superadmin', 'user'], true)) {
        $message = 'All fields are required and role must be valid.';
        $message_type = 'error';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $username, $password_hash, $role);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'User created successfully.';
                $message_type = 'success';
            } else {
                $message = 'Unable to create user. Username may already exist.';
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = 'Database error when creating user.';
            $message_type = 'error';
        }
    }
}

$users = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Infocrats Document Drive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>User Management</h1>
                <p class="text-muted">Create and delete users. Super admin access only.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary me-2">← Back to Dashboard</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Create New User</div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user">User</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3">Create User</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-dark text-white">Existing Users</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($current_user['id'] !== (int)$user['id']): ?>
                                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted">Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>