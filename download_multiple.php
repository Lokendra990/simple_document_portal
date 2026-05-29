<?php
/**
 * Bulk Download Handler
 * download_multiple.php - Package selected files into a ZIP for download
 */

session_start();
include 'auth.php';
require_login();

$uploads_dir = 'uploads/';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['files']) || !is_array($_POST['files'])) {
        throw new Exception('No files selected for download.');
    }

    $file_ids = array_map('intval', $_POST['files']);
    $file_ids = array_filter($file_ids, fn($id) => $id > 0);

    if (empty($file_ids)) {
        throw new Exception('Please select one or more files before downloading.');
    }

    $id_list = implode(',', array_map('intval', $file_ids));
    $query = "SELECT id, original_name, stored_name, file_type FROM documents WHERE id IN ($id_list) ORDER BY FIELD(id, $id_list)";
    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        throw new Exception('Selected files were not found.');
    }

    $temp_zip = tempnam(sys_get_temp_dir(), 'infocrats_bulk_');
    if ($temp_zip === false) {
        throw new Exception('Unable to create temporary download file.');
    }

    $zip_path = $temp_zip . '.zip';
    rename($temp_zip, $zip_path);

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
        throw new Exception('Unable to create ZIP archive.');
    }

    $uploads_real = realpath($uploads_dir);
    if ($uploads_real === false) {
        throw new Exception('Uploads directory is missing.');
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $stored_name = $row['stored_name'];
        $original_name = $row['original_name'];
        $file_type = $row['file_type'];

        $file_path = $uploads_dir . $stored_name;
        $real_path = realpath($file_path);

        if ($real_path === false || strpos($real_path, $uploads_real) !== 0) {
            continue;
        }

        if ($file_type === 'zip') {
            $innerZip = new ZipArchive();
            if ($innerZip->open($real_path) === true && $innerZip->numFiles > 0) {
                $innerName = $innerZip->getNameIndex(0);
                $stream = $innerZip->getStream($innerName);

                if ($stream !== false) {
                    $temp_inner = tempnam(sys_get_temp_dir(), 'infocrats_inner_');
                    if ($temp_inner !== false) {
                        $out = fopen($temp_inner, 'wb');
                        if ($out !== false) {
                            while (!feof($stream)) {
                                $chunk = fread($stream, 8192);
                                if ($chunk === false) {
                                    break;
                                }
                                fwrite($out, $chunk);
                            }
                            fclose($out);
                            $zip->addFile($temp_inner, basename($original_name));
                            unlink($temp_inner);
                        }
                    }
                    fclose($stream);
                }
                $innerZip->close();
                continue;
            }
            if ($innerZip->isOpen()) {
                $innerZip->close();
            }
            $zip->addFile($real_path, basename($original_name) . '.zip');
            continue;
        }

        $zip->addFile($real_path, basename($original_name));
    }

    $zip->close();

    if (!file_exists($zip_path)) {
        throw new Exception('Failed to create download archive.');
    }

    $download_name = 'infocrats_download_' . time() . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . addslashes($download_name) . '"');
    header('Content-Length: ' . filesize($zip_path));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($zip_path);
    unlink($zip_path);
    exit;

} catch (Exception $e) {
    $_SESSION['message'] = 'Bulk download error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}
