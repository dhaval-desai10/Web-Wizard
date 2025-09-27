<?php
// Security and utility functions

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Indian format)
function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

// Validate student ID
function validateStudentId($studentId) {
    return preg_match('/^[A-Za-z0-9]{6,20}$/', $studentId);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin']);
}

// Check if admin is logged in
function isAdmin() {
    return isset($_SESSION['admin']);
}

// Check if student is logged in
function isStudent() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

// Redirect if not student
function requireStudent() {
    if (!isStudent()) {
        header('Location: ../index.php');
        exit();
    }
}

// Generate random student ID
function generateStudentId() {
    $departments = ['CS', 'IT', 'EC', 'ME', 'CE', 'EE'];
    $year = date('y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $departments[array_rand($departments)] . $year . $random;
}

// Format date for display
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

// Upload file with validation
function uploadFile($file, $uploadDir, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
    }
    
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    // Validate file size (max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB'];
    }
    
    // Generate unique filename
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $uploadPath];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Delete file
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

// Get file type for notifications
function getFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension === 'pdf') {
        return 'pdf';
    } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        return 'image';
    }
    return null;
}

// Validate percentage
function validatePercentage($percentage) {
    $num = floatval($percentage);
    return $num >= 0 && $num <= 100;
}

// Validate year
function validateYear($year) {
    $currentYear = date('Y');
    $num = intval($year);
    return $num >= 1990 && $num <= $currentYear;
}

// Get department name by ID
function getDepartmentName($pdo, $departmentId) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $result = $stmt->fetch();
        return $result ? $result['name'] : 'Unknown';
    } catch (PDOException $e) {
        return 'Unknown';
    }
}

// Get all departments
function getAllDepartments($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Check if student ID exists
function studentIdExists($pdo, $studentId, $excludeId = null) {
    try {
        $sql = "SELECT id FROM students WHERE student_id = ?";
        $params = [$studentId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return true; // Assume exists to be safe
    }
}

// Check if email exists
function emailExists($pdo, $email, $excludeId = null) {
    try {
        $sql = "SELECT id FROM students WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return true; // Assume exists to be safe
    }
}

// Set remember me cookie
function setRememberMeCookie($studentId) {
    $cookieValue = base64_encode($studentId . '|' . time());
    setcookie('remember_student', $cookieValue, time() + (30 * 24 * 60 * 60), '/'); // 30 days
}

// Get remember me cookie
function getRememberMeCookie() {
    if (isset($_COOKIE['remember_student'])) {
        $cookieData = base64_decode($_COOKIE['remember_student']);
        $parts = explode('|', $cookieData);
        if (count($parts) === 2) {
            return $parts[0]; // Return student ID
        }
    }
    return null;
}

// Clear remember me cookie
function clearRememberMeCookie() {
    setcookie('remember_student', '', time() - 3600, '/');
}

// Log activity (simple logging)
function logActivity($message) {
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>