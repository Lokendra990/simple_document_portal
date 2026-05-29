================================================================================
INFOCRATS DOCUMENT DRIVE
Version 1.0
================================================================================

PROJECT OVERVIEW:
A complete PHP + MySQL web application for uploading, downloading, managing
and searching documents with a professional UI, security features, and
dashboard statistics.

================================================================================
FEATURES
================================================================================

✅ CORE FEATURES:
- Upload documents with file validation
- Download documents with secure access
- Delete documents from system
- Search documents by filename
- Real-time file list display
- Dashboard with statistics
- Automatic ZIP compression for files > 10MB

✅ DASHBOARD FEATURES:
- Total uploaded files count
- Total storage size
- Latest uploaded files (5 files)
- File type display
- Upload date and time

✅ FILE UPLOAD RULES:
- Allowed Extensions: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG
- Maximum File Size: 50MB (without compression)
- Maximum File Size: 200MB (with automatic ZIP compression)
- Files over 50MB automatically compressed to ZIP
- Automatic file renaming using: timestamp_randomnumber_originalname
- Prevents duplicate file overwrite
- Validates file extension and size

✅ SECURITY FEATURES:
- File extension validation
- File size validation
- File type verification
- Sanitized file names
- Prepared SQL statements (prevent SQL injection)
- Directory traversal prevention
- Executable file blocking
- User authentication and role-based access control

================================================================================
PROJECT STRUCTURE
================================================================================

infocrats_document_drive/
├── index.php                (Main dashboard and file listing)
├── upload.php               (File upload handler with validation)
├── download.php             (Secure file download handler)
├── delete.php               (File deletion handler)
├── db.php                   (Database connection configuration)
├── database.sql             (Database schema and setup)
├── css/
│   └── style.css           (Professional responsive styling)
├── uploads/                 (Uploaded files storage folder)
└── README.txt              (This file)

================================================================================
REQUIREMENTS
================================================================================

SERVER REQUIREMENTS:
- PHP 7.0 or higher (tested with PHP 8.2.12)
- MySQL 5.7 or higher
- Apache/XAMPP/WAMP
- PHP Extensions: ZipArchive (for automatic compression)

FILE PERMISSIONS:
- uploads/ folder: Write permissions (755 or 777)
- All PHP files: Read permissions (644)

BROWSER COMPATIBILITY:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (responsive design)

================================================================================
INSTALLATION STEPS
================================================================================

STEP 1: COPY PROJECT FILES
1. Copy the entire 'simple_document_portal' folder to:
   - XAMPP: C:\xampp\htdocs\simple_document_portal
   - WAMP: C:\wamp64\www\simple_document_portal
   - Linux: /var/www/html/simple_document_portal

STEP 2: CREATE UPLOADS FOLDER
1. Create a folder named 'uploads' in the project directory
2. Set permissions to 755 or 777
3. Ensure the folder is writable

STEP 3: CREATE DATABASE
1. Start MySQL service
2. Open phpMyAdmin or MySQL command line
3. Run the database.sql file:
   - In phpMyAdmin: Import database.sql
   - In MySQL CLI: mysql -u root -p < database.sql
4. This will create:
   - Database: document_system
   - Tables: documents, users
   - Default superadmin account: superadmin / Admin@123

STEP 4: START SERVER
1. Start Apache and MySQL services
2. For XAMPP: Use Control Panel to start Apache and MySQL
3. For WAMP: Click on WAMP icon and select start services

STEP 5: ACCESS APPLICATION
1. Open your browser
2. Navigate to: http://localhost/simple_document_portal
3. You should see the Infocrats Document Drive dashboard

================================================================================
DATABASE SETUP
================================================================================

DATABASE NAME:
document_system

TABLE NAME:
documents

TABLE STRUCTURE:
- id (INT) - Primary Key, Auto Increment
- original_name (VARCHAR 255) - Original uploaded filename
- stored_name (VARCHAR 255) - Renamed filename (unique)
- file_size (INT) - File size in bytes
- file_type (VARCHAR 50) - File extension (pdf, doc, etc.)
- uploaded_at (TIMESTAMP) - Upload date and time

USERS TABLE STRUCTURE:
- id (INT) - Primary Key, Auto Increment
- username (VARCHAR 100) - Unique login name
- password_hash (VARCHAR 255) - Password hash
- role (ENUM) - User role: superadmin or user
- created_at (TIMESTAMP) - Account creation date

INDEXES:
- Primary Key on id
- Unique constraint on stored_name
- Index on uploaded_at (for sorting)
- Index on original_name (for searching)

SQL FILE LOCATION:
database.sql (in project root)

================================================================================
USER GUIDE
================================================================================

UPLOADING A FILE:
1. Log in with your portal account
2. Click "Choose File" button
3. Select a file from your computer
4. Ensure file is within allowed types and 10MB size limit
5. Click "Upload File" button
6. Wait for upload confirmation message
7. File will appear in the documents table

DOWNLOADING A FILE:
1. Find the file in the "Uploaded Documents" table
2. Click the "⬇️ Download" button
3. File will download with its original name
4. Check your Downloads folder

DELETING A FILE:
1. Find the file in the "Uploaded Documents" table
2. Click the "🗑️ Delete" button (superadmin only)
3. Confirm deletion in the popup modal
4. File will be removed from system and database
5. Success message will appear

SEARCHING FILES:
1. Go to "Search Documents" section
2. Type the filename or partial filename
3. Click "Search" button
4. Results will filter automatically
5. Click "Clear" to show all files

VIEWING STATISTICS:
- Dashboard shows total files count
- Dashboard shows total storage size
- Latest Files section shows 5 most recent uploads
- Each file row shows: name, type, size, upload date

================================================================================
FILE NAMING CONVENTION
================================================================================

ORIGINAL UPLOAD (Files ≤ 10MB):
- File: document.pdf
- Size: 2.5MB
- Upload Time: May 26, 2024 17:30:45

STORED AS:
- Filename: 1716724245_567890_document.pdf
- Where:
  * 1716724245 = Unix timestamp
  * 567890 = Random 6-digit number
  * document.pdf = Original filename

LARGE FILE UPLOAD (Files > 10MB):
- File: video.mp4 (50MB) - NOT SUPPORTED
- File: largedocument.pdf (50MB) - Will be auto-compressed
- Upload Time: May 26, 2024 17:30:45

AUTO-COMPRESSED AS:
- Filename: 1716724245_567890_largedocument.zip
- Compression: Automatic ZIP archive created
- Size: ~15MB (depending on file content)
- Display Name: "largedocument.zip [COMPRESSED]"

BENEFITS:
- Unique identifier for each file
- Prevents file overwrites
- Chronological sorting possible
- Maintains original filename for user
- Automatic compression for large files saves space

================================================================================
AUTOMATIC COMPRESSION FEATURE
================================================================================

OVERVIEW:
The portal includes automatic file compression for files exceeding 10MB. This
allows you to upload large files without hitting size limits.

HOW IT WORKS:
1. User uploads a file larger than 10MB
2. System automatically compresses file to ZIP format
3. Compressed file stored in uploads folder
4. Original filename preserved in database
5. User sees compression details in success message

COMPRESSION DETAILS:
- Trigger: Files > 10MB
- Method: ZIP archive (using PHP ZipArchive)
- Max Size: 200MB before compression
- Compression Ratio: Varies by file type
  * PDF: 5-15% reduction
  * Documents: 20-40% reduction
  * Images: 10-20% reduction (already compressed)
  * Videos: 0-5% reduction (already compressed)

EXAMPLE COMPRESSION:
Original: "presentation.pdf" (50.65MB)
Compressed: "presentation.zip [COMPRESSED]" (12.3MB) - 76% saved!

DOWNLOAD COMPRESSED FILES:
1. Find compressed file in documents table
2. File shows "[COMPRESSED]" tag
3. File type badge shows "ZIP"
4. Download button provides the ZIP file
5. Extract ZIP on your computer to get original file

STORAGE BENEFITS:
- Reduces server disk usage
- Faster uploads for large files
- Lower bandwidth consumption
- Automatic space optimization

================================================================================
ALLOWED FILE TYPES
================================================================================

DOCUMENTS:
- .pdf (PDF Document)
- .doc (Microsoft Word 97-2003)
- .docx (Microsoft Word 2007+)
- .xls (Microsoft Excel 97-2003)
- .xlsx (Microsoft Excel 2007+)

IMAGES:
- .jpg (JPEG Image)
- .jpeg (JPEG Image)
- .png (PNG Image)

MAXIMUM FILE SIZE: 10MB (10,485,760 bytes)

================================================================================
SECURITY FEATURES EXPLAINED
================================================================================

1. FILE EXTENSION VALIDATION
   - Only whitelisted extensions allowed
   - Prevents executable files (exe, bat, sh, etc.)
   - Extension checked with strtolower() for case-insensitive comparison

2. FILE SIZE VALIDATION
   - Checked against 10MB limit
   - Empty files rejected
   - User-friendly error messages

3. SANITIZATION
   - File names sanitized with htmlspecialchars()
   - Prevents XSS attacks
   - Special characters escaped

4. SQL INJECTION PREVENTION
   - Prepared statements used for all queries
   - Parameter binding prevents SQL injection
   - Only mysqli_prepare() and mysqli_stmt_bind_param()

5. DIRECTORY TRAVERSAL PREVENTION
   - realpath() function verifies file location
   - Prevents ".." path traversal attempts
   - Files restricted to uploads/ directory only

6. SECURE FILE HANDLING
   - Files served with proper headers
   - Original filename shown during download
   - File chunks read for large files
   - Output buffering handled properly

================================================================================
TROUBLESHOOTING
================================================================================

ISSUE: "Connection failed: Unknown database 'document_system'"
SOLUTION:
1. Verify MySQL is running
2. Confirm database was created (check in phpMyAdmin)
3. Run database.sql file if not created
4. Verify db.php connection credentials match your MySQL setup

ISSUE: "File size exceeds 10MB limit"
SOLUTION:
This message should no longer appear. Files over 10MB are automatically 
compressed to ZIP. If you see this error:
1. Ensure PHP ZipArchive extension is installed
2. Check PHP configuration in php.ini
3. Verify file is under 200MB limit
4. Try uploading smaller files first

ISSUE: Compression not working
SOLUTION:
1. Verify ZipArchive extension installed: php -m | grep zip
2. Check PHP settings:
   - memory_limit = 512M (or higher)
   - max_execution_time = 300 (or higher)
3. Ensure uploads/ folder has write permissions
4. Try with smaller file first
5. Check PHP error logs for details

ISSUE: Compressed file is same size as original
SOLUTION:
1. This is normal for already-compressed files (MP4, JPG, PNG, ZIP)
2. PDF files typically compress well (5-15% reduction)
3. Document files compress better (20-40% reduction)
4. Video files don't compress much (already compressed)

ISSUE: "uploads" folder not found
SOLUTION:
1. Create 'uploads' folder in project root
2. Set permissions: chmod 755 uploads/ (Linux/Mac)
3. Set permissions: Right-click → Properties → Security → Full Control (Windows)
4. Restart web server

ISSUE: Cannot download files
SOLUTION:
1. Verify file exists in uploads/ folder
2. Check file permissions are readable
3. Verify database record exists
4. Check file_id in download link is correct

ISSUE: "Invalid file type" error
SOLUTION:
1. Check file extension is in allowed list
2. Ensure file has correct extension
3. Rename file if extension is wrong
4. Check file wasn't corrupted during upload

ISSUE: Page appears with no styling (CSS not loading)
SOLUTION:
1. Verify css/ folder exists with style.css
2. Check file path: should be css/style.css
3. Clear browser cache (Ctrl+Shift+Delete)
4. Check browser console for 404 errors
5. Verify server configured to serve CSS files

ISSUE: Search not working
SOLUTION:
1. Ensure database has documents
2. Check original_name column has values
3. Try searching for partial filenames
4. Verify MySQL search index is working

================================================================================
PERFORMANCE TIPS
================================================================================

1. LARGE FILES:
   - Use chunked reading for files over 50MB
   - Increase PHP memory_limit if needed
   - Monitor disk space

2. MANY FILES:
   - Database queries optimized with indexes
   - Pagination recommended for 1000+ files
   - Archive old files periodically

3. OPTIMIZATION:
   - Enable gzip compression in Apache
   - Use CDN for CSS/JavaScript if needed
   - Implement caching headers

4. BACKUP:
   - Regular backup of database
   - Backup uploads/ folder
   - Test restore procedures

================================================================================
MAINTENANCE
================================================================================

REGULAR TASKS:
1. Monitor disk space usage
2. Check error logs (Apache, MySQL)
3. Verify uploads/ folder permissions
4. Backup database monthly
5. Clean old temporary files

DATABASE MAINTENANCE:
- Run OPTIMIZE TABLE documents; monthly
- Check for orphaned records
- Verify index fragmentation

SECURITY:
- Keep PHP and MySQL updated
- Monitor for suspicious uploads
- Review access logs
- Update allowed file types if needed

================================================================================
FUTURE ENHANCEMENTS
================================================================================

PLANNED FEATURES:
- User authentication and login
- File sharing and permissions
- File versioning and rollback
- Advanced search filters
- File preview functionality
- Bulk operations (zip download, batch delete)
- Email notifications
- Activity logging
- File tagging and categorization
- Drag-and-drop upload
- Virus scanning
- Cloud storage integration

================================================================================
TECHNICAL DETAILS
================================================================================

CODE STRUCTURE:
- Clean, beginner-friendly code
- Well-commented for learning
- Follows PHP best practices
- Uses Bootstrap 5 for UI
- Responsive mobile design

LIBRARIES USED:
- Bootstrap 5.3.0 (CSS Framework)
- Vanilla JavaScript (no jQuery)
- MySQLi (Database)
- PHP native functions

DESIGN PATTERN:
- Procedural PHP (beginner-friendly)
- MVC-ready structure
- Separation of concerns
- Reusable components

================================================================================
CLOUD DEPLOYMENT (RENDER.COM)
================================================================================

OVERVIEW:
This application is fully compatible with Render.com cloud platform.
All database connections use environment variables for secure cloud deployment.

ENVIRONMENT VARIABLES REQUIRED:
For Render.com deployment, set these in Render Environment Variables:
- DB_HOST: Your MySQL hostname from Render (e.g., mysql.someregion.render.com)
- DB_USER: Your MySQL username
- DB_PASSWORD: Your MySQL password
- DB_NAME: Your database name (e.g., document_system)

RENDER DEPLOYMENT STEPS:

1. PREPARE YOUR REPOSITORY
   a. Ensure Dockerfile exists in project root (already included)
   b. Ensure docker-compose.yml exists (already included)
   c. Git commit and push all files

2. CREATE MYSQL DATABASE ON RENDER
   a. Go to Render.com dashboard
   b. Create new MySQL database service
   c. Choose: PostgreSQL or MySQL (select MySQL)
   d. Set database name: document_system
   e. Note the database credentials
   f. Wait for database to be ready

3. CREATE WEB SERVICE ON RENDER
   a. Click "New" → "Web Service"
   b. Connect your GitHub repository
   c. Choose: Docker for Runtime
   d. Set build command: (leave empty - Docker will build)
   e. Set start command: (leave empty - Docker will start)
   f. Set instance type: Free or paid (based on needs)

4. SET ENVIRONMENT VARIABLES
   a. In Render Web Service settings
   b. Add environment variable: DB_HOST
      Value: your-mysql-hostname.render.com (from MySQL service)
   c. Add environment variable: DB_USER
      Value: your_mysql_username
   d. Add environment variable: DB_PASSWORD
      Value: your_mysql_password
   e. Add environment variable: DB_NAME
      Value: document_system
   f. Save variables and restart deployment

5. INITIALIZE DATABASE (FIRST TIME ONLY)
   a. Once deployment is successful
   b. Navigate to: https://your-app.render.com/setup.php
   c. This will create all required tables
   d. Default superadmin account will be created
   e. You should see success messages

6. VERIFY DEPLOYMENT
   a. Navigate to: https://your-app.render.com
   b. You should see the login page
   c. Log in with: superadmin / Admin@123
   d. Upload a test file
   e. Verify download works

STORAGE NOTES FOR RENDER:
- Render uses ephemeral file system (files deleted on redeploy)
- For persistent storage, use Render Disk or cloud storage
- Current uploads/ folder works for temporary files
- Consider implementing backup/export functionality

DATABASE CONNECTION TROUBLESHOOTING:
If you see "Database connection failed":
1. Check environment variables are set correctly in Render dashboard
2. Verify MySQL database is created and running
3. Confirm database credentials are accurate
4. Check MySQL service is in the same Render region if possible
5. Wait 5-10 minutes for DNS propagation
6. Restart the web service deployment

SECURITY BEST PRACTICES:
1. Change default superadmin password after first login
2. Use strong passwords for database
3. Keep credentials in Render environment variables, not code
4. Enable HTTPS (Render provides this automatically)
5. Regular backups of database
6. Monitor access logs for suspicious activity

================================================================================
DOCKER DEPLOYMENT (LOCAL)
================================================================================

LOCAL DOCKER DEPLOYMENT STEPS:

1. ENSURE DOCKER IS INSTALLED
   a. Install Docker Desktop (https://www.docker.com/products/docker-desktop)
   b. Start Docker application

2. BUILD AND RUN WITH DOCKER COMPOSE
   a. Navigate to project folder
   b. Run: docker-compose up --build
   c. Wait for MySQL to initialize (takes 30-60 seconds)
   d. You should see: "web_1 ... Listening on port 80"

3. ACCESS APPLICATION
   a. Open browser
   b. Navigate to: http://localhost:8000
   c. You should see the login page

4. INITIALIZE DATABASE (FIRST TIME)
   a. Navigate to: http://localhost:8000/setup.php
   b. Follow setup instructions
   c. Database tables will be created
   d. Default superadmin account created

5. LOG IN
   a. Username: superadmin
   b. Password: Admin@123
   c. Change password after first login

6. STOP DOCKER CONTAINERS
   a. Press Ctrl+C in terminal
   b. Or run: docker-compose down

DOCKER BENEFITS:
- Same environment everywhere (local, cloud, production)
- No need to install PHP, MySQL separately
- Automatic database initialization
- Easy to scale and manage
- Consistent with cloud deployment

================================================================================
SUPPORT & LICENSE
================================================================================

COMPATIBILITY:
- Windows (XAMPP/WAMP or Docker)
- Linux (Apache/MySQL or Docker)
- macOS (MAMP or Docker)
- Cloud (Render.com, Docker containers)

BROWSER SUPPORT:
- Desktop: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Mobile: iOS 12+, Android 6+

VERSION: 1.0 (Updated with Cloud Deployment)
LAST UPDATED: May 29, 2024
CREATED FOR: Beginners to learn PHP + MySQL web development

================================================================================
QUICK START CHECKLIST
================================================================================

□ Copy project to htdocs/www folder
□ Create uploads/ folder with write permissions
□ Create database using database.sql
□ Start Apache and MySQL services
□ Navigate to http://localhost/simple_document_portal
□ Upload a test file
□ Download the test file
□ Delete the test file
□ Search for files
□ Check dashboard statistics

================================================================================
END OF README
================================================================================

For more information or issues, check the error logs in:
- Apache Error Log: C:\xampp\apache\logs\error.log
- MySQL Error Log: C:\xampp\mysql\data\*.err
- Browser Console: F12 → Console tab

================================================================================
