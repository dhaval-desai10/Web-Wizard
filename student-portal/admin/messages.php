<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get departments for dropdown
$departments = getAllDepartments(getConnection());

// Handle create message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    // Server-side validation
    $errors = [];
    
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message)) $errors[] = 'Message is required';
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO messages (subject, message, department_id) 
                VALUES (?, ?, ?)
            ");
            
            $result = $stmt->execute([$subject, $message, $departmentId]);
            
            if ($result) {
                $targetAudience = $departmentId ? getDepartmentName($pdo, $departmentId) : 'All Departments';
                logActivity("Admin sent message: '$subject' to $targetAudience");
                $success = 'Message sent successfully!';
                
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Failed to send message.';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error occurred.';
            logActivity("Send message error: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Handle delete message
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT subject FROM messages WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $messageToDelete = $stmt->fetch();
        
        if ($messageToDelete) {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $result = $stmt->execute([$_GET['id']]);
            
            if ($result) {
                logActivity("Admin deleted message: {$messageToDelete['subject']}");
                $success = 'Message deleted successfully.';
            } else {
                $error = 'Failed to delete message.';
            }
        } else {
            $error = 'Message not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error occurred while deleting message.';
    }
    
    // Redirect to clear URL parameters
    header("Location: messages.php" . ($success ? "?success=" . urlencode($success) : ""));
    exit();
}

// Get all messages
$messages = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT m.*, d.name as department_name 
        FROM messages m 
        LEFT JOIN departments d ON m.department_id = d.id 
        ORDER BY m.created_at DESC
    ");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error occurred while loading messages.';
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
    <title>Messages - Admin Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - Admin Messages</h1>
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
            <!-- Create Message Form -->
            <div class="form-container">
                <h2>Send New Message</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" class="form-control" 
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" 
                               required placeholder="Enter message subject">
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
                        <textarea id="message" name="message" class="form-control" rows="8" 
                                  required placeholder="Enter your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn">Send Message</button>
                        <a href="messages.php" class="btn" style="background: #6c757d; margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Messages List -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>All Messages</h2>
                <a href="messages.php?action=create" class="btn">Send New Message</a>
            </div>
            
            <?php if (empty($messages)): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px;">
                            <h3>No messages sent yet</h3>
                            <p>Click the button above to send your first message to students.</p>
                            <a href="messages.php?action=create" class="btn">Send Message</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($message['subject']); ?></h3>
                            <div>
                                <span style="background: rgba(255,255,255,0.2); color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                                    <?php echo $message['department_name'] ? htmlspecialchars($message['department_name']) : 'All Departments'; ?>
                                </span>
                                <a href="messages.php?action=delete&id=<?php echo $message['id']; ?>" 
                                   style="color: white; text-decoration: none; font-size: 12px;"
                                   onclick="return confirm('Are you sure you want to delete this message?')">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 15px;">
                                <small style="color: #666;">
                                    Sent on <?php echo formatDateTime($message['created_at']); ?>
                                </small>
                            </div>
                            
                            <div style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
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