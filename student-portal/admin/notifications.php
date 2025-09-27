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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.5s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-inter bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gradient-to-r from-red-600 to-pink-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-semibold text-gray-900">Admin Portal</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, Administrator</span>
                    <div class="relative inline-block text-left">
                        <div class="w-8 h-8 bg-gradient-to-r from-red-600 to-pink-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-sm">A</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Secondary Navigation -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="students.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Students
                </a>
                <a href="notifications.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-yellow-500 text-sm font-medium text-yellow-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                    </svg>
                    Notifications
                </a>
                <a href="messages.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Messages
                </a>
                <a href="../logout.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-red-500 hover:text-red-700 hover:border-red-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
                <a href="students.php">Manage Students</a>
                <a href="notifications.php">Notifications</a>
                <a href="messages.php">Messages</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 animate-fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 animate-fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'create'): ?>
            <!-- Create Notification Form -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-slide-up">
                <div class="px-6 py-4 bg-gradient-to-r from-yellow-500 to-orange-500">
                    <h2 class="text-xl font-semibold text-white">Send New Notification</h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <div class="space-y-2">
                        <label for="title" class="block text-sm font-medium text-gray-700">
                            Notification Title *
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required
                            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all duration-200"
                            placeholder="Enter notification title"
                        >
                    </div>
                    
                    <div class="space-y-2">
                        <label for="department_id" class="block text-sm font-medium text-gray-700">
                            Target Audience
                        </label>
                        <select 
                            id="department_id" 
                            name="department_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all duration-200"
                        >
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?> Department Only
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-sm text-gray-500">Select a specific department or leave blank to send to all students</p>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="message" class="block text-sm font-medium text-gray-700">
                            Message *
                        </label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="6" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all duration-200"
                            placeholder="Enter your notification message here..."
                        ><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="attachment" class="block text-sm font-medium text-gray-700">
                            Attachment (Optional)
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors duration-200">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="attachment" class="relative cursor-pointer bg-white rounded-md font-medium text-yellow-600 hover:text-yellow-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-yellow-500">
                                        <span>Upload a file</span>
                                        <input 
                                            type="file" 
                                            id="attachment" 
                                            name="attachment" 
                                            class="sr-only"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="validateFile(this, ['pdf', 'jpg', 'jpeg', 'png'])"
                                        >
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, JPG, JPEG, PNG up to 5MB</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center space-x-4">
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold rounded-lg hover:from-yellow-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transform hover:scale-105 transition-all duration-200"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                            </svg>
                            Send Notification
                        </button>
                        <a 
                            href="notifications.php" 
                            class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Notifications List -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">All Notifications</h2>
                        <p class="mt-1 text-sm text-gray-600">Manage and send notifications to students</p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <a 
                            href="notifications.php?action=create" 
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold rounded-lg hover:from-yellow-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transform hover:scale-105 transition-all duration-200"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Send New Notification
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-fade-in">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No notifications sent yet</h3>
                        <p class="text-gray-600 mb-6">Start keeping your students informed by sending your first notification.</p>
                        <a 
                            href="notifications.php?action=create" 
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold rounded-lg hover:from-yellow-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transform hover:scale-105 transition-all duration-200"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                            </svg>
                            Send First Notification
                        </a>
                    </div>
                </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-fade-in hover:shadow-xl transition-shadow duration-300">
                            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-white">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-3">
                                        <span class="bg-white bg-opacity-20 text-white px-3 py-1 rounded-full text-sm font-medium">
                                            <?php echo $notification['department_name'] ? htmlspecialchars($notification['department_name']) : 'All Departments'; ?>
                                        </span>
                                        <button 
                                            onclick="if(confirm('Are you sure you want to delete this notification?')) window.location.href='notifications.php?action=delete&id=<?php echo $notification['id']; ?>'"
                                            class="text-white hover:text-red-200 transition-colors duration-200"
                                            title="Delete notification"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Sent on <?php echo formatDateTime($notification['created_at']); ?>
                                    </div>
                                </div>
                                
                                <div class="prose max-w-none mb-4">
                                    <p class="text-gray-700 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </p>
                                </div>
                                
                                <?php if ($notification['file_path']): ?>
                                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <?php if ($notification['file_type'] === 'image'): ?>
                                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4 flex-1">
                                                <p class="text-sm font-medium text-gray-900">Attachment:</p>
                                                <a 
                                                    href="../uploads/notifications/<?php echo htmlspecialchars($notification['file_path']); ?>" 
                                                    target="_blank"
                                                    class="text-sm text-blue-600 hover:text-blue-500 underline"
                                                >
                                                    <?php echo htmlspecialchars(basename($notification['file_path'])); ?>
                                                </a>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <a 
                                                    href="../uploads/notifications/<?php echo htmlspecialchars($notification['file_path']); ?>" 
                                                    target="_blank"
                                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications yet</h3>
                    <p class="text-gray-500 mb-6">Start by creating your first notification above.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a 
                href="dashboard.php" 
                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // File validation function
        function validateFile(input, allowedTypes) {
            const file = input.files[0];
            if (file) {
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.split('.').pop();
                
                if (!allowedTypes.includes(fileExtension)) {
                    alert('Invalid file type. Please select a file with one of these extensions: ' + allowedTypes.join(', '));
                    input.value = '';
                    return false;
                }
                
                // Check file size (5MB limit)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File size too large. Please select a file smaller than 5MB.');
                    input.value = '';
                    return false;
                }
                
                // Update file name display
                const label = input.parentNode.querySelector('span');
                if (label) {
                    label.textContent = file.name;
                }
            }
        }
        
        // Add fade-in animation to elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>