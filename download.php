<?php
/**
 * File Download Handler
 * download.php - Securely handles file downloads with proper headers
 */

include 'auth.php';
require_login();

try {
    // Validate ID parameter
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid file ID.');
    }

    $file_id = intval($_GET['id']);

    // Fetch file details using prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, original_name, stored_name, file_type, file_size FROM documents WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $file_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Query failed: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('File not found.');
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Build file path - prevent directory traversal
    $uploads_dir = 'uploads/';
    $stored_name = $row['stored_name'];
    
    // Security check: ensure file is in uploads directory
    $real_path = realpath($uploads_dir . $stored_name);
    $uploads_real = realpath($uploads_dir);
    
    if ($real_path === false || strpos($real_path, $uploads_real) !== 0) {
        throw new Exception('Invalid file path.');
    }

    // Verify file exists
    if (!file_exists($real_path)) {
        throw new Exception('File does not exist on server.');
    }

    // Verify file is readable
    if (!is_readable($real_path)) {
        throw new Exception('File is not readable.');
    }

    $original_name = $row['original_name'];
    $file_type = $row['file_type'];

    // Clear any output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    if ($file_type === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($real_path) !== true) {
            throw new Exception('Unable to open compressed archive.');
        }

        if ($zip->numFiles === 0) {
            $zip->close();
            throw new Exception('Compressed archive is empty.');
        }

        $innerName = $zip->getNameIndex(0);
        $innerStat = $zip->statIndex(0);

        if (!$innerName || !$innerStat || !isset($innerStat['size'])) {
            $zip->close();
            throw new Exception('Invalid compressed file contents.');
        }

        $download_name = basename($innerName);
        header('Content-Disposition: attachment; filename="' . addslashes($download_name) . '"');
        header('Content-Length: ' . $innerStat['size']);

        $stream = $zip->getStream($innerName);
        if ($stream === false) {
            $zip->close();
            throw new Exception('Unable to read compressed file contents.');
        }

        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
        }

        fclose($stream);
        $zip->close();
        exit;
    }

    // Normal file download
    $file_size = filesize($real_path);
    if ($file_size === false) {
        throw new Exception('Cannot determine file size.');
    }

    header('Content-Disposition: attachment; filename="' . addslashes($original_name) . '"');
    header('Content-Length: ' . $file_size);

    // Read and output file in chunks (for large files)
    $chunk_size = 8192; // 8KB chunks
    $handle = fopen($real_path, 'rb');
    
    if ($handle === false) {
        throw new Exception('Cannot open file for reading.');
    }

    while (!feof($handle)) {
        $chunk = fread($handle, $chunk_size);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
    }
    
    fclose($handle);
    exit;

} catch (Exception $e) {
    // Log error and redirect
    error_log('Download error: ' . $e->getMessage());
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Download Error</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <h1 class="error">❌ Download Error</h1>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <a href="index.php">← Back to Documents</a>
    </body>
    </html>';
    exit;
}
?>
