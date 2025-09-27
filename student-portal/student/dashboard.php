<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireStudent();

$success = '';
$error = '';

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
}

// Check if profile is incomplete
$isProfileIncomplete = empty($student['phone']) || empty($student['date_of_birth']) || 
                      empty($student['gender']) || empty($student['address']);

// Get notifications for student's department
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE department_id IS NULL OR department_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$student['department_id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    // Continue without notifications
}

// Get messages for student's department
$messages = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE department_id IS NULL OR department_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$student['department_id']]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    // Continue without messages
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
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
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
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
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-semibold text-gray-900">Student Portal</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($student['first_name']); ?></span>
                    <div class="relative inline-block text-left">
                        <button type="button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button">
                            <div class="w-8 h-8 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-medium text-sm"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Secondary Navigation -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-indigo-500 text-sm font-medium text-indigo-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="profile.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>
                <a href="notifications.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                    </svg>
                    Notifications
                    <?php if (count($notifications) > 0): ?>
                        <span class="ml-1 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Messages
                    <?php if (count($messages) > 0): ?>
                        <span class="ml-1 bg-blue-500 text-white text-xs rounded-full px-2 py-1"><?php echo count($messages); ?></span>
                    <?php endif; ?>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Profile Completion Alert -->
        <?php if ($isProfileIncomplete): ?>
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 animate-fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Complete Your Profile:</strong> Please update your profile with additional information like phone, date of birth, and address.
                            <a href="profile.php" class="font-medium underline text-yellow-800 hover:text-yellow-600 ml-2">Complete Profile →</a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 118 0v2m-4 0a2 2 0 104 0m-4 0a2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Student ID</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($student['student_id']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Department</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($student['department_name']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Notifications</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?php echo count($notifications); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Messages</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?php echo count($messages); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Student Information -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                    <h3 class="text-lg font-semibold text-white">Personal Information</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Full Name:</span>
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Student ID:</span>
                            <span class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($student['student_id']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Email:</span>
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Department:</span>
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($student['department_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Phone:</span>
                            <span class="text-sm text-gray-900"><?php echo $student['phone'] ? htmlspecialchars($student['phone']) : '<span class="text-gray-400">Not provided</span>'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Date of Birth:</span>
                            <span class="text-sm text-gray-900"><?php echo $student['date_of_birth'] ? formatDate($student['date_of_birth']) : '<span class="text-gray-400">Not provided</span>'; ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="profile.php" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Update Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
                    <h3 class="text-lg font-semibold text-white">Recent Activity</h3>
                </div>
                <div class="p-6">
                    <!-- Recent Notifications -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Notifications</h4>
                        <?php if (!empty($notifications)): ?>
                            <div class="space-y-3">
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-2 h-2 bg-yellow-400 rounded-full mt-2"></div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo formatDateTime($notification['created_at']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <a href="notifications.php" class="text-sm text-indigo-600 hover:text-indigo-500">View all notifications →</a>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No notifications available.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Messages -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Messages</h4>
                        <?php if (!empty($messages)): ?>
                            <div class="space-y-3">
                                <?php foreach (array_slice($messages, 0, 3) as $message): ?>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo formatDateTime($message['created_at']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <a href="messages.php" class="text-sm text-blue-600 hover:text-blue-500">View all messages →</a>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No messages available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-blue-600">
                <h3 class="text-lg font-semibold text-white">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="profile.php" class="group flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 transition-colors duration-200">
                        <div class="w-12 h-12 bg-indigo-100 group-hover:bg-indigo-200 rounded-lg flex items-center justify-center mb-3 transition-colors duration-200">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900 group-hover:text-indigo-700">Update Profile</span>
                    </a>

                    <a href="notifications.php" class="group flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-yellow-50 transition-colors duration-200">
                        <div class="w-12 h-12 bg-yellow-100 group-hover:bg-yellow-200 rounded-lg flex items-center justify-center mb-3 transition-colors duration-200">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900 group-hover:text-yellow-700">View Notifications</span>
                    </a>

                    <a href="messages.php" class="group flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-200">
                        <div class="w-12 h-12 bg-blue-100 group-hover:bg-blue-200 rounded-lg flex items-center justify-center mb-3 transition-colors duration-200">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900 group-hover:text-blue-700">Check Messages</span>
                    </a>

                    <a href="../logout.php" class="group flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-red-50 transition-colors duration-200">
                        <div class="w-12 h-12 bg-red-100 group-hover:bg-red-200 rounded-lg flex items-center justify-center mb-3 transition-colors duration-200">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900 group-hover:text-red-700">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>