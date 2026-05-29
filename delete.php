<?php
/**
 * File Delete Handler
 * delete.php - Handles file deletion from database and filesystem
 */

session_start();
include 'auth.php';
require_superadmin();

try {
    // Validate ID parameter
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid file ID.');
    }

    $file_id = intval($_GET['id']);

    // Fetch file details using prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, stored_name, original_name FROM documents WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $file_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Query failed.');
    }

    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('File not found.');
    }

    $row = mysqli_fetch_assoc($result);
    $stored_name = $row['stored_name'];
    $original_name = $row['original_name'];
    mysqli_stmt_close($stmt);

    // Build file path - prevent directory traversal
    $uploads_dir = 'uploads/';
    
    // Security check: ensure file is in uploads directory
    $real_path = realpath($uploads_dir . $stored_name);
    $uploads_real = realpath($uploads_dir);
    
    if ($real_path === false || strpos($real_path, $uploads_real) !== 0) {
        throw new Exception('Invalid file path.');
    }

    // Delete file from filesystem if it exists
    if (file_exists($real_path)) {
        if (!unlink($real_path)) {
            throw new Exception('Failed to delete file from server.');
        }
    }

    // Delete record from database
    $stmt = mysqli_prepare($conn, "DELETE FROM documents WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $file_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete database record.');
    }

    mysqli_stmt_close($stmt);

    // Set success message
    $_SESSION['message'] = "File '{$original_name}' has been deleted successfully.";
    $_SESSION['message_type'] = 'success';
    $_SESSION['message_time'] = time();

} catch (Exception $e) {
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    $_SESSION['message_time'] = time();
}

// Redirect back to index
header('Location: index.php');
exit;
?>