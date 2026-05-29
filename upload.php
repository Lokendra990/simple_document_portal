<?php
/**
 * File Upload Handler with Automatic Compression
 * upload.php - Handles file upload, validation, compression, and database storage
 */

session_start();
include 'auth.php';
require_login();

// Allowed file extensions
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
$max_file_size_no_compress = 50 * 1024 * 1024; // 50MB - compress files larger than this
$max_file_size_total = 200 * 1024 * 1024; // 200MB - absolute maximum

// Initialize response
$_SESSION['message'] = '';
$_SESSION['message_type'] = 'success';
$_SESSION['message_time'] = time();

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file selected or upload failed.');
    }

    $file = $_FILES['file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    // Sanitize file name
    $file_name_clean = htmlspecialchars(basename($file_name));
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name_clean, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($file_ext, $allowed_extensions)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', array_map('strtoupper', $allowed_extensions)));
    }

    // Validate file size
    if ($file_size > $max_file_size_total) {
        throw new Exception('File size exceeds 200MB absolute limit. Your file is ' . round($file_size / (1024 * 1024), 2) . 'MB');
    }

    // Validate file size is not empty
    if ($file_size === 0) {
        throw new Exception('File is empty. Please upload a file with content.');
    }

    // Check if uploads folder exists
    $uploads_dir = 'uploads/';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    // Generate unique filename: timestamp_randomnumber_originalname
    $timestamp = time();
    $random_number = rand(100000, 999999);
    
    // Flag for compression
    $was_compressed = false;
    $compression_ratio = 0;
    $original_file_size = $file_size;
    
    // Determine if compression is needed
    if ($file_size > $max_file_size_no_compress) {
        $stored_filename = $timestamp . '_' . $random_number . '_' . pathinfo($file_name_clean, PATHINFO_FILENAME) . '.zip';
        $was_compressed = true;
    } else {
        $stored_filename = $timestamp . '_' . $random_number . '_' . $file_name_clean;
    }

    // Prevent path traversal attacks
    if (strpos($stored_filename, '..') !== false || strpos($stored_filename, '/') !== false) {
        throw new Exception('Invalid filename.');
    }

    $upload_path = $uploads_dir . $stored_filename;

    // Move uploaded file to temporary location
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        throw new Exception('Failed to save file. Please try again.');
    }

    try {
        // Compress if needed
        if ($was_compressed) {
            // Create ZIP archive
            $zip = new ZipArchive();
            $zip_path = $uploads_dir . 'temp_' . $timestamp . '.zip';
            
            if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
                unlink($upload_path);
                throw new Exception('Failed to create ZIP archive.');
            }

            // Add file to ZIP
            if (!$zip->addFile($upload_path, basename($file_name_clean))) {
                $zip->close();
                unlink($upload_path);
                unlink($zip_path);
                throw new Exception('Failed to add file to ZIP archive.');
            }

            // Close ZIP
            if (!$zip->close()) {
                unlink($upload_path);
                unlink($zip_path);
                throw new Exception('Failed to finalize ZIP archive.');
            }

            // Replace original file with compressed ZIP
            unlink($upload_path);
            rename($zip_path, $upload_path);

            // Get compressed file size
            $compressed_size = filesize($upload_path);
            $compression_ratio = round((1 - ($compressed_size / $original_file_size)) * 100, 2);
            $file_size = $compressed_size;

            // Update file type
            $file_ext = 'zip';
            $file_name_clean = pathinfo($file_name_clean, PATHINFO_FILENAME) . '.zip';
        }
    } catch (Exception $e) {
        // Cleanup on compression failure
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        throw new Exception('Compression error: ' . $e->getMessage());
    }

    // Verify file was actually uploaded (security check)
    if (!is_uploaded_file($upload_path) && !file_exists($upload_path)) {
        throw new Exception('File verification failed. File may not have been saved correctly.');
    }

    // Prepare data for database
    $original_name = $file_name_clean;
    $file_type = $was_compressed ? 'zip' : $file_ext;

    // Insert into database using prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO documents (original_name, stored_name, file_size, file_type) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }

    // Bind parameters (s = string, i = integer)
    mysqli_stmt_bind_param($stmt, "ssii", $original_name, $stored_filename, $file_size, $file_type);

    // Execute query
    if (!mysqli_stmt_execute($stmt)) {
        // Delete the uploaded file if database insert fails
        unlink($upload_path);
        throw new Exception('Failed to save file information to database.');
    }

    mysqli_stmt_close($stmt);

    // Success message with compression info
    if ($was_compressed) {
        $_SESSION['message'] = "✅ File '{$file_name_clean}' uploaded and compressed! Original: " . 
                               round($original_file_size / (1024 * 1024), 2) . "MB → Compressed: " . 
                               round($file_size / (1024 * 1024), 2) . "MB (Saved: {$compression_ratio}%)";
    } else {
        $_SESSION['message'] = "✅ File '{$file_name_clean}' uploaded successfully! (" . round($file_size / 1024, 2) . " KB)";
    }
    $_SESSION['message_type'] = 'success';

} catch (Exception $e) {
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Redirect back to index
header('Location: index.php');
exit;
?>
