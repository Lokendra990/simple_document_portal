<?php
/**
 * Database Connection Configuration
 * Infocrats Document Drive
 * 
 * Supports environment variables for cloud deployment (Render, Docker, etc)
 * Fallback to local development defaults if env vars not set
 */

// Database configuration with environment variable support
// Priority: $_ENV (set by hosting) > getenv() (PHP config) > defaults (local dev)
$db_host = !empty($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : (getenv('DB_HOST') ?: 'localhost');
$db_user = !empty($_ENV['DB_USER']) ? $_ENV['DB_USER'] : (getenv('DB_USER') ?: 'root');
$db_password = !empty($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : (getenv('DB_PASSWORD') ?: '');
$db_name = !empty($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : (getenv('DB_NAME') ?: 'document_system');

// Create connection with error handling
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check connection
if (!$conn) {
    // Production-safe error message (don't expose sensitive info)
    $error_msg = "Database connection failed";
    
    // For debugging, check if we're in development
    $is_development = (in_array($db_host, ['localhost', '127.0.0.1', 'db']) && 
                       in_array($db_user, ['root', 'default']));
    
    if ($is_development) {
        // Dev mode: show full error
        die("<h1>❌ Database Connection Error</h1>" .
            "<p><strong>Host:</strong> $db_host</p>" .
            "<p><strong>User:</strong> $db_user</p>" .
            "<p><strong>Database:</strong> $db_name</p>" .
            "<p><strong>Error:</strong> " . mysqli_connect_error() . "</p>" .
            "<p><em>Check your environment variables or run setup.php</em></p>");
    } else {
        // Production mode: generic error
        die("<h1>⚠️ Service Unavailable</h1>" .
            "<p>The database service is currently unavailable.</p>" .
            "<p>Please contact support if the problem persists.</p>");
    }
}

// Set charset to utf8mb4 for proper unicode support
mysqli_set_charset($conn, "utf8mb4");

// Enable error reporting - convert errors to exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
