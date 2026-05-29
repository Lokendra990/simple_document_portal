<?php
/**
 * Database Setup Script
 * setup.php - Initialize database and tables with proper schema
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'document_system';

// Create connection without selecting database
$conn = mysqli_connect($db_host, $db_user, $db_password);

if (!$conn) {
    die("<h1>❌ Connection Error</h1><p>Could not connect to MySQL: " . mysqli_connect_error() . "</p>");
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if (mysqli_query($conn, $sql)) {
    echo "<h1>✅ Setup Successful</h1>";
    echo "<p><strong>Database created successfully.</strong></p>";
} else {
    echo "<h1>❌ Error</h1>";
    echo "<p>Error creating database: " . mysqli_error($conn) . "</p>";
    exit;
}

// Select the database
if (!mysqli_select_db($conn, $db_name)) {
    die("<p>Error selecting database: " . mysqli_error($conn) . "</p>");
}

// Create documents table
$sql = "DROP TABLE IF EXISTS documents";
mysqli_query($conn, $sql); // Drop old table

$sql = "CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_size INT NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_at (uploaded_at),
    INDEX idx_original_name (original_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "<p><strong>Documents table created successfully.</strong></p>";
} else {
    echo "<p>Error creating table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "<p><strong>Users table created successfully.</strong></p>";
} else {
    echo "<p>Error creating users table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Insert default superadmin if missing
$default_username = 'superadmin';
$default_password = 'Admin@123';
$default_hash = password_hash($default_password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $default_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        $insert = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'superadmin')");
        if ($insert) {
            mysqli_stmt_bind_param($insert, 'ss', $default_username, $default_hash);
            mysqli_stmt_execute($insert);
            mysqli_stmt_close($insert);
            echo "<p><strong>Default superadmin account created.</strong></p>";
        }
    } else {
        mysqli_stmt_close($stmt);
        echo "<p><strong>Default superadmin account already exists.</strong></p>";
    }
} else {
    echo "<p>Could not verify default superadmin account.</p>";
}

// Check if uploads folder exists
if (!is_dir('uploads')) {
    if (mkdir('uploads', 0755, true)) {
        echo "<p><strong>✅ Uploads folder created with proper permissions.</strong></p>";
    } else {
        echo "<p><strong>⚠️ Warning:</strong> Could not create uploads folder. Please create it manually.</p>";
    }
} else {
    echo "<p><strong>✅ Uploads folder already exists.</strong></p>";
}

mysqli_close($conn);

// Get statistics
$stats = [];
$conn2 = mysqli_connect($db_host, $db_user, $db_password, $db_name);
if ($conn2) {
    $result = mysqli_query($conn2, "SELECT COUNT(*) as count FROM documents");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['count'] = $row['count'];
    }
    mysqli_close($conn2);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Infocrats Document Drive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .container { background: white; border-radius: 10px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { color: #27ae60; margin-bottom: 20px; }
        .btn { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <div class="alert alert-info">
            <h2>📋 Setup Complete</h2>
            <p>Your Infocrats Document Drive is ready to use!</p>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">✅ Database Setup</h5>
                <ul class="list-unstyled">
                    <li>✓ Database: <strong>document_system</strong></li>
                    <li>✓ Table: <strong>documents</strong></li>
                    <li>✓ Columns: original_name, stored_name, file_size, file_type, uploaded_at</li>
                    <li>✓ Current files: <strong><?php echo isset($stats['count']) ? $stats['count'] : 0; ?></strong></li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">📁 Folder Structure</h5>
                <ul class="list-unstyled">
                    <li>✓ uploads/ (for storing files)</li>
                    <li>✓ css/ (stylesheets)</li>
                    <li>✓ index.php (dashboard)</li>
                </ul>
            </div>
        </div>

        <div class="alert alert-success mt-3">
            <strong>🎉 Ready to use!</strong>
            <p>Click the button below to go to your Infocrats Document Drive.</p>
        </div>

        <a href="index.php" class="btn btn-primary btn-lg btn-block w-100">
            Go to Infocrats Document Drive →
        </a>
    </div>
</body>
</html>
