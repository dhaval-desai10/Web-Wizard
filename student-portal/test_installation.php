<?php
// Installation Test Script
// Run this file to test if your setup is working correctly

echo "<!DOCTYPE html>";
echo "<html><head><title>Student Portal - Installation Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style></head><body>";
echo "<h1>Student Portal - Installation Test</h1>";

$errors = [];
$warnings = [];
$success = [];

// Test 1: Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $success[] = "PHP Version: " . PHP_VERSION . " ✓";
} else {
    $errors[] = "PHP Version: " . PHP_VERSION . " (Minimum required: 7.4.0)";
}

// Test 2: Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'fileinfo', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "PHP Extension '$ext': Available ✓";
    } else {
        $errors[] = "PHP Extension '$ext': Missing";
    }
}

// Test 3: Check file permissions
$upload_dir = 'uploads/notifications/';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    $success[] = "Upload directory: Writable ✓";
} else {
    $warnings[] = "Upload directory: May not be writable (File uploads might fail)";
}

// Test 4: Test database connection
try {
    require_once 'includes/config.php';
    $pdo = getConnection();
    $success[] = "Database Connection: Success ✓";
    
    // Test 5: Check if tables exist
    $tables = ['departments', 'students', 'notifications', 'messages'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            $success[] = "Database Table '$table': Exists ✓";
        } else {
            $errors[] = "Database Table '$table': Missing (Run database_setup.sql)";
        }
    }
    
    // Test 6: Check if default departments exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments");
    $dept_count = $stmt->fetch()['count'];
    if ($dept_count > 0) {
        $success[] = "Default Departments: $dept_count departments loaded ✓";
    } else {
        $warnings[] = "Default Departments: No departments found (Run database_setup.sql)";
    }
    
} catch (Exception $e) {
    $errors[] = "Database Connection: Failed - " . $e->getMessage();
}

// Test 7: Check configuration
$config_issues = [];
if (!defined('DB_HOST')) $config_issues[] = "DB_HOST not defined";
if (!defined('DB_NAME')) $config_issues[] = "DB_NAME not defined";
if (!defined('ADMIN_USERNAME')) $config_issues[] = "ADMIN_USERNAME not defined";
if (!defined('ADMIN_PASSWORD')) $config_issues[] = "ADMIN_PASSWORD not defined";

if (empty($config_issues)) {
    $success[] = "Configuration: All constants defined ✓";
} else {
    $errors[] = "Configuration: Missing constants - " . implode(', ', $config_issues);
}

// Display results
echo "<h2>Test Results</h2>";

if (!empty($success)) {
    echo "<h3 class='success'>✓ Successful Checks</h3><ul>";
    foreach ($success as $msg) {
        echo "<li class='success'>$msg</li>";
    }
    echo "</ul>";
}

if (!empty($warnings)) {
    echo "<h3 class='warning'>⚠ Warnings</h3><ul>";
    foreach ($warnings as $msg) {
        echo "<li class='warning'>$msg</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 class='error'>✗ Errors</h3><ul>";
    foreach ($errors as $msg) {
        echo "<li class='error'>$msg</li>";
    }
    echo "</ul>";
}

// Overall status
echo "<h2>Overall Status</h2>";
if (empty($errors)) {
    if (empty($warnings)) {
        echo "<div class='success'><h3>🎉 Installation Complete!</h3>";
        echo "<p>Your Student Portal is ready to use.</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li><a href='index.php'>Go to Login Page</a></li>";
        echo "<li>Admin Login: username = 'admin', password = 'admin123'</li>";
        echo "<li><a href='register.php'>Student Registration</a></li>";
        echo "</ul></div>";
    } else {
        echo "<div class='warning'><h3>⚠ Installation Mostly Complete</h3>";
        echo "<p>Your system is working but has some warnings. Please review the warnings above.</p>";
        echo "<p><a href='index.php'>Proceed to Login Page</a></p></div>";
    }
} else {
    echo "<div class='error'><h3>❌ Installation Issues Found</h3>";
    echo "<p>Please fix the errors above before using the system.</p>";
    echo "<p><strong>Common Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Database errors: Import database_setup.sql in phpMyAdmin</li>";
    echo "<li>Connection errors: Check XAMPP MySQL is running</li>";
    echo "<li>Permission errors: Check file/folder permissions</li>";
    echo "</ul></div>";
}

// System information
echo "<h2>System Information</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><td><strong>PHP Version</strong></td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td><strong>Server Software</strong></td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
echo "<tr><td><strong>Document Root</strong></td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td><strong>Current Directory</strong></td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td><strong>Upload Max Size</strong></td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td><strong>Post Max Size</strong></td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "</table>";

echo "<hr><p><small>Delete this file (test_installation.php) after successful testing.</small></p>";
echo "</body></html>";
?>