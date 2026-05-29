<?php
/**
 * File Recovery Script
 * recover.php - Scan uploads folder and restore missing database entries
 */

session_start();
include 'auth.php';
require_superadmin();

$recovery_log = [];
$files_recovered = 0;
$files_skipped = 0;

try {
    $uploads_dir = 'uploads/';
    
    if (!is_dir($uploads_dir)) {
        throw new Exception('Uploads folder not found.');
    }

    // Get all files in uploads folder
    $files = array_diff(scandir($uploads_dir), ['.', '..']);
    
    if (empty($files)) {
        $recovery_log[] = "ℹ️ No files found in uploads folder.";
    } else {
        // Process each file
        foreach ($files as $file) {
            $file_path = $uploads_dir . $file;
            
            // Skip if not a file
            if (!is_file($file_path)) {
                continue;
            }

            // Skip temporary files
            if (strpos($file, 'temp_') === 0) {
                $files_skipped++;
                continue;
            }

            // Check if file already in database
            $stmt = mysqli_prepare($conn, "SELECT id FROM documents WHERE stored_name = ?");
            mysqli_stmt_bind_param($stmt, "s", $file);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            if (mysqli_num_rows($result) > 0) {
                // File already in database, skip
                $files_skipped++;
                continue;
            }

            // Parse filename
            $original_name = $file;
            $file_size = filesize($file_path);
            $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            // Validate file extension
            $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
            if (!in_array($file_type, $allowed_extensions)) {
                $files_skipped++;
                $recovery_log[] = "⚠️ Skipped: {$file} (unsupported type)";
                continue;
            }

            // Check file size
            if ($file_size === 0) {
                $files_skipped++;
                $recovery_log[] = "⚠️ Skipped: {$file} (empty file)";
                continue;
            }

            // Insert into database
            $stmt = mysqli_prepare($conn, "INSERT INTO documents (original_name, stored_name, file_size, file_type) VALUES (?, ?, ?, ?)");
            
            if (!$stmt) {
                $recovery_log[] = "❌ Failed: {$file} (database error)";
                $files_skipped++;
                continue;
            }

            mysqli_stmt_bind_param($stmt, "ssii", $original_name, $file, $file_size, $file_type);

            if (mysqli_stmt_execute($stmt)) {
                $files_recovered++;
                $recovery_log[] = "✅ Recovered: {$original_name} (" . round($file_size / (1024 * 1024), 2) . " MB)";
            } else {
                $files_skipped++;
                $recovery_log[] = "❌ Failed: {$file} (could not insert)";
            }

            mysqli_stmt_close($stmt);
        }
    }

    $_SESSION['recovery_log'] = $recovery_log;
    $_SESSION['files_recovered'] = $files_recovered;
    $_SESSION['files_skipped'] = $files_skipped;
    $_SESSION['recovery_complete'] = true;

} catch (Exception $e) {
    $_SESSION['recovery_error'] = $e->getMessage();
}

header('Location: index.php');
exit;
?>