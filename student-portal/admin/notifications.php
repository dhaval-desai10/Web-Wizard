<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get departments for dropdown
$departments = getAllDepartments(getConnection());

// Handle create notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $title = sanitizeInput($_POST['title']);
    $message = sanitizeInput($_POST['message']);
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    // Server-side validation
    $errors = [];
    
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($message)) $errors[] = 'Message is required';
    
    $uploadedFile = null;
    
    // Handle file upload if provided
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['attachment'], '../uploads/notifications/', ['pdf', 'jpg', 'jpeg', 'png']);
        
        if ($uploadResult['success']) {
            $uploadedFile = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            $fileType = null;
            if ($uploadedFile) {
                $fileType = getFileType($uploadedFile);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (title, message, file_path, file_type, department_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$title, $message, $uploadedFile, $fileType, $departmentId]);
            
            if ($result) {
                $targetAudience = $departmentId ? getDepartmentName($pdo, $departmentId) : 'All Departments';
                logActivity("Admin created notification: '$title' for $targetAudience");
                $success = 'Notification sent successfully!';
                
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Failed to send notification.';
                if ($uploadedFile) {
                    deleteFile('../uploads/notifications/' . $uploadedFile);
                }
            }
            
        } catch (PDOException $e) {
            $error = 'Database error occurred.';
            logActivity("Create notification error: " . $e->getMessage());
            if ($uploadedFile) {
                deleteFile('../uploads/notifications/' . $uploadedFile);
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Handle delete notification
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT title, file_path FROM notifications WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $notificationToDelete = $stmt->fetch();
        
        if ($notificationToDelete) {
            // Delete file if exists
            if ($notificationToDelete['file_path']) {
                deleteFile('../uploads/notifications/' . $notificationToDelete['file_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $result = $stmt->execute([$_GET['id']]);
            
            if ($result) {
                logActivity("Admin deleted notification: {$notificationToDelete['title']}");
                $success = 'Notification deleted successfully.';
            } else {
                $error = 'Failed to delete notification.';
            }
        } else {
            $error = 'Notification not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error occurred while deleting notification.';
    }
    
    // Redirect to clear URL parameters
    header("Location: notifications.php" . ($success ? "?success=" . urlencode($success) : ""));
    exit();
}

// Get all notifications
$notifications = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT n.*, d.name as department_name 
        FROM notifications n 
        LEFT JOIN departments d ON n.department_id = d.id 
        ORDER BY n.created_at DESC
    ");
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error occurred while loading notifications.';
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - Admin Notifications</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">Manage Students</a>
                <a href="notifications.php">Notifications</a>
                <a href="messages.php">Messages</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($action === 'create'): ?>
            <!-- Create Notification Form -->
            <div class="form-container">
                <h2>Send New Notification</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Notification Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required placeholder="Enter notification title">
                    </div>
                    
                    <div class="form-group">
                        <label for="department_id">Target Audience</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?> Department Only
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Select a specific department or leave blank to send to all students</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="form-control" rows="6" 
                                  required placeholder="Enter your notification message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="attachment">Attachment (Optional)</label>
                        <input type="file" id="attachment" name="attachment" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               onchange="validateFile(this, ['pdf', 'jpg', 'jpeg', 'png'])">
                        <small>Allowed file types: PDF, JPG, JPEG, PNG. Maximum size: 5MB</small>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn">Send Notification</button>
                        <a href="notifications.php" class="btn" style="background: #6c757d; margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Notifications List -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>All Notifications</h2>
                <a href="notifications.php?action=create" class="btn">Send New Notification</a>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px;">
                            <h3>No notifications sent yet</h3>
                            <p>Click the button above to send your first notification to students.</p>
                            <a href="notifications.php?action=create" class="btn">Send Notification</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($notification['title']); ?></h3>
                            <div>
                                <span style="background: rgba(255,255,255,0.2); color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                                    <?php echo $notification['department_name'] ? htmlspecialchars($notification['department_name']) : 'All Departments'; ?>
                                </span>
                                <a href="notifications.php?action=delete&id=<?php echo $notification['id']; ?>" 
                                   style="color: white; text-decoration: none; font-size: 12px;"
                                   onclick="return confirm('Are you sure you want to delete this notification?')">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 15px;">
                                <small style="color: #666;">
                                    Sent on <?php echo formatDateTime($notification['created_at']); ?>
                                </small>
                            </div>
                            
                            <div style="line-height: 1.6; margin-bottom: 15px;">
                                <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                            </div>
                            
                            <?php if ($notification['file_path']): ?>
                                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <strong>Attachment:</strong>
                                    <a href="../uploads/notifications/<?php echo htmlspecialchars($notification['file_path']); ?>" 
                                       target="_blank" style="margin-left: 10px;">
                                        <?php if ($notification['file_type'] === 'pdf'): ?>
                                            📄 <?php echo htmlspecialchars($notification['file_path']); ?>
                                        <?php else: ?>
                                            🖼️ <?php echo htmlspecialchars($notification['file_path']); ?>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>