<?php
/**
 * Infocrats Document Drive - Main Dashboard
 * Index.php - Display uploaded documents and upload form
 */

session_start();
include 'auth.php';
require_login();

// Clear old messages after 5 seconds
if (isset($_SESSION['message'])) {
    $_SESSION['message_time'] = $_SESSION['message_time'] ?? time();
    if (time() - $_SESSION['message_time'] > 5) {
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        unset($_SESSION['message_time']);
    }
}

$current_user = get_logged_in_user();

// Get statistics
$stats = [];

// Total files count
$query = "SELECT COUNT(*) as total_files, SUM(file_size) as total_size FROM documents";
$result = mysqli_query($conn, $query);
$stats_row = mysqli_fetch_assoc($result);
$stats['total_files'] = $stats_row['total_files'] ?? 0;
$stats['total_size'] = $stats_row['total_size'] ?? 0;

// Get latest uploaded files (5 latest)
$query = "SELECT id, original_name, file_size, file_type, uploaded_at FROM documents ORDER BY uploaded_at DESC LIMIT 5";
$latest_files = mysqli_query($conn, $query);

// Search functionality
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['search']));
}

// Get all documents with search filter
$where_clause = $search_term ? "WHERE original_name LIKE '%$search_term%'" : '';
$query = "SELECT id, original_name, stored_name, file_size, file_type, uploaded_at FROM documents $where_clause ORDER BY uploaded_at DESC";
$documents = mysqli_query($conn, $query);
$document_count = mysqli_num_rows($documents);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infocrats Document Drive</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2">📄 Infocrats Document Drive</h1>
                <p class="text-muted mb-0">Upload, manage, and download your documents.</p>
            </div>
            <div class="text-end">
                <div class="mb-1">
                    <strong><?php echo htmlspecialchars($current_user['username']); ?></strong>
                    <span class="badge bg-<?php echo $current_user['role'] === 'superadmin' ? 'warning text-dark' : 'secondary'; ?>">
                        <?php echo htmlspecialchars(ucfirst($current_user['role'])); ?>
                    </span>
                </div>
                <div>
                    <?php if ($current_user['role'] === 'superadmin'): ?>
                        <a href="users.php" class="btn btn-sm btn-outline-primary me-2">User Management</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <strong><?php echo $_SESSION['message_type'] === 'error' ? '❌ Error!' : '✅ Success!'; ?></strong>
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Recovery Info -->
        <?php if (isset($_SESSION['files_recovered']) && $_SESSION['files_recovered'] > 0): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>📁 File Recovery Complete!</strong>
                <br>Recovered: <strong><?php echo $_SESSION['files_recovered']; ?></strong> file(s)
                <?php if ($_SESSION['files_skipped'] > 0): ?>
                    | Skipped: <strong><?php echo $_SESSION['files_skipped']; ?></strong>
                <?php endif; ?>
                <?php if (!empty($_SESSION['recovery_log'])): ?>
                    <details style="margin-top: 10px;">
                        <summary>View Details</summary>
                        <div style="margin-top: 10px; background: white; padding: 10px; border-radius: 4px;">
                            <?php foreach ($_SESSION['recovery_log'] as $log): ?>
                                <div><?php echo htmlspecialchars($log); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
                unset($_SESSION['files_recovered']);
                unset($_SESSION['files_skipped']);
                unset($_SESSION['recovery_log']);
            ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['recovery_error'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ Recovery Notice:</strong>
                <?php echo htmlspecialchars($_SESSION['recovery_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['recovery_error']); ?>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_files']; ?></div>
                    <div class="stat-label">Total Files</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($stats['total_size'] / (1024 * 1024), 2); ?> MB</div>
                    <div class="stat-label">Total Size</div>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="card mb-4 upload-section">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📤 Upload Document</h5>
            </div>
            <div class="card-body">
                <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="mb-3">
                        <label for="file" class="form-label">Choose File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <small class="text-muted">
                            Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG | 
                            Up to 50MB (normal) | Over 50MB auto-compresses to ZIP
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" id="uploadSpinner" role="status" aria-hidden="true"></span>
                        Upload File
                    </button>
                </form>
                
                <!-- Recovery Section -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p class="text-muted mb-2"><small>💾 <strong>Recover Old Files:</strong> If files are missing from the list but still in the uploads folder, click below to scan and recover them.</small></p>
                    <a href="recover.php" class="btn btn-outline-secondary btn-sm">
                        🔄 Scan & Recover Files
                    </a>
                </div>
            </div>
        </div>

        <!-- Latest Files Section -->
        <?php if ($stats['total_files'] > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">⭐ Latest Uploaded Files</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($latest_files)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($row['original_name'], 0, 30)); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper($row['file_type']); ?></span></td>
                                        <td><?php echo round($row['file_size'] / 1024, 2); ?> KB</td>
                                        <td><?php echo date('M d, H:i', strtotime($row['uploaded_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">🔍 Search Documents</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control" name="search" placeholder="Search by filename..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-secondary">Search</button>
                    <?php if ($search_term): ?>
                        <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📋 Uploaded Documents 
                    <span class="badge bg-light text-dark"><?php echo $document_count; ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($document_count > 0): ?>
                    <form id="bulkDownloadForm" action="download_multiple.php" method="POST">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button type="submit" id="bulkDownloadBtn" class="btn btn-sm btn-primary" disabled>
                                ⬇️ Download Selected
                            </button>
                            <div class="text-muted small">Select files using checkboxes to download them together as one ZIP.</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center"><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Upload Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = mysqli_fetch_assoc($documents)): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input file-checkbox" name="files[]" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['original_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo strtoupper($row['file_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo round($row['file_size'] / 1024, 2); ?> KB</td>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['uploaded_at'])); ?></td>
                                        <td class="text-center">
                                            <a href="download.php?id=<?php echo $row['id']; ?>&file=<?php echo urlencode($row['stored_name']); ?>" 
                                               class="btn btn-sm btn-success me-2" title="Download">
                                                ⬇️ Download
                                            </a>
                                            <?php if ($current_user['role'] === 'superadmin'): ?>
                                                <button type="button" onclick="deleteFile(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['original_name']); ?>')" 
                                                        class="btn btn-sm btn-danger" title="Delete">
                                                    🗑️ Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <?php if ($search_term): ?>
                            <p>No documents found matching "<?php echo htmlspecialchars($search_term); ?>"</p>
                        <?php else: ?>
                            <p>No documents uploaded yet. Start by uploading your first document!</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteFileName"></strong>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteLink" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation function
        function deleteFile(id, filename) {
            document.getElementById('deleteFileName').textContent = filename;
            document.getElementById('deleteLink').href = 'delete.php?id=' + id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Show spinner during upload
        document.getElementById('uploadForm').addEventListener('submit', function() {
            document.getElementById('uploadSpinner').classList.remove('d-none');
        });

        // Bulk download checkbox handling
        const selectAllCheckbox = document.getElementById('selectAll');
        const bulkDownloadBtn = document.getElementById('bulkDownloadBtn');
        const fileCheckboxes = document.querySelectorAll('.file-checkbox');

        function updateBulkButton() {
            const anyChecked = Array.from(fileCheckboxes).some(cb => cb.checked);
            bulkDownloadBtn.disabled = !anyChecked;
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = fileCheckboxes.length > 0 && Array.from(fileCheckboxes).every(cb => cb.checked);
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                fileCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkButton();
            });
        }

        fileCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkButton));
        updateBulkButton();
    </script>
</body>
</html>
