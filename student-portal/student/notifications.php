<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireStudent();

// Get student data
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, d.name as department_name 
        FROM students s 
        JOIN departments d ON s.department_id = d.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: ../logout.php');
        exit();
    }
} catch (PDOException $e) {
    $error = 'Database error occurred.';
    $student = null;
}

// Get notifications for student's department
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.*, d.name as department_name 
        FROM notifications n 
        LEFT JOIN departments d ON n.department_id = d.id 
        WHERE n.department_id IS NULL OR n.department_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$student['department_id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Could not load notifications.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - Notifications</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="notifications.php">Notifications</a>
                <a href="messages.php">Messages</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>All Notifications</h2>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3>No notifications available</h3>
                        <p>Check back later for updates from your department or general announcements.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f9f9f9;">
                            <div style="display: flex; justify-content: between; align-items: start;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 10px 0; color: #333;">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h3>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <span style="background: #667eea; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo $notification['department_name'] ? htmlspecialchars($notification['department_name']) : 'All Departments'; ?>
                                        </span>
                                        <span style="color: #666; font-size: 12px; margin-left: 10px;">
                                            Posted on <?php echo formatDateTime($notification['created_at']); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="line-height: 1.6; margin-bottom: 15px;">
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </div>
                                    
                                    <?php if ($notification['file_path']): ?>
                                        <div style="margin-top: 15px;">
                                            <a href="../uploads/notifications/<?php echo htmlspecialchars($notification['file_path']); ?>" 
                                               target="_blank" 
                                               class="btn" 
                                               style="display: inline-block; padding: 8px 16px; font-size: 14px;">
                                                <?php if ($notification['file_type'] === 'pdf'): ?>
                                                    📄 View PDF Attachment
                                                <?php else: ?>
                                                    🖼️ View Image Attachment
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>