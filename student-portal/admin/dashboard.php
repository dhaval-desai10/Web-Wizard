<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';

// Get statistics
try {
    $pdo = getConnection();
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];
    
    // Students by department
    $stmt = $pdo->query("
        SELECT d.name, COUNT(s.id) as count 
        FROM departments d 
        LEFT JOIN students s ON d.id = s.department_id 
        GROUP BY d.id, d.name 
        ORDER BY d.name
    ");
    $departmentStats = $stmt->fetchAll();
    
    // Total notifications
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotifications = $stmt->fetch()['total'];
    
    // Total messages
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
    $totalMessages = $stmt->fetch()['total'];
    
    // Recent registrations (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM students 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $recentRegistrations = $stmt->fetch()['count'];
    
    // Get recent students
    $stmt = $pdo->query("
        SELECT s.*, d.name as department_name 
        FROM students s 
        JOIN departments d ON s.department_id = d.id 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $recentStudents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error occurred.';
    $totalStudents = 0;
    $departmentStats = [];
    $totalNotifications = 0;
    $totalMessages = 0;
    $recentRegistrations = 0;
    $recentStudents = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Portal</title>
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
                <a href="dashboard.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-red-500 text-sm font-medium text-red-600">
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
                <a href="notifications.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Welcome to Admin Dashboard</h2>
            <p class="mt-1 text-sm text-gray-600">Manage students, notifications, and system settings</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-blue-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-2xl font-bold text-gray-900"><?php echo $totalStudents; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-green-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">New Students (7 days)</dt>
                                <dd class="text-2xl font-bold text-gray-900"><?php echo $recentRegistrations; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-yellow-500">
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
                                <dd class="text-2xl font-bold text-gray-900"><?php echo $totalNotifications; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-purple-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Messages</dt>
                                <dd class="text-2xl font-bold text-gray-900"><?php echo $totalMessages; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Students by Department -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
                    <h3 class="text-lg font-semibold text-white">Students by Department</h3>
                </div>
                <div class="p-6">
                    <?php if (!empty($departmentStats)): ?>
                        <div class="space-y-4">
                            <?php foreach ($departmentStats as $dept): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dept['name']); ?></span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-3">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $totalStudents > 0 ? ($dept['count'] / $totalStudents) * 100 : 0; ?>%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900"><?php echo $dept['count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No department data available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Students -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
                    <h3 class="text-lg font-semibold text-white">Recent Registrations</h3>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentStudents)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recentStudents as $student): ?>
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white text-xs font-medium">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($student['student_id'] . ' • ' . $student['department_name']); ?>
                                        </p>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php echo formatDateTime($student['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="students.php" class="text-sm text-green-600 hover:text-green-500 font-medium">View all students →</a>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No recent registrations.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600">
                <h3 class="text-lg font-semibold text-white">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <a href="students.php" class="group flex flex-col items-center p-6 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors duration-200">
                        <div class="w-16 h-16 bg-blue-100 group-hover:bg-blue-200 rounded-xl flex items-center justify-center mb-4 transition-colors duration-200">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 mb-2">Manage Students</h4>
                        <p class="text-xs text-gray-500 text-center">View, edit, and manage student records</p>
                    </a>

                    <a href="notifications.php" class="group flex flex-col items-center p-6 bg-gray-50 rounded-xl hover:bg-yellow-50 transition-colors duration-200">
                        <div class="w-16 h-16 bg-yellow-100 group-hover:bg-yellow-200 rounded-xl flex items-center justify-center mb-4 transition-colors duration-200">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 mb-2">Send Notifications</h4>
                        <p class="text-xs text-gray-500 text-center">Create and manage notifications</p>
                    </a>

                    <a href="messages.php" class="group flex flex-col items-center p-6 bg-gray-50 rounded-xl hover:bg-purple-50 transition-colors duration-200">
                        <div class="w-16 h-16 bg-purple-100 group-hover:bg-purple-200 rounded-xl flex items-center justify-center mb-4 transition-colors duration-200">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 group-hover:text-purple-700 mb-2">Send Messages</h4>
                        <p class="text-xs text-gray-500 text-center">Communicate with students</p>
                    </a>

                    <div class="group flex flex-col items-center p-6 bg-gray-50 rounded-xl">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">View Reports</h4>
                        <p class="text-xs text-gray-500 text-center">Generate system reports</p>
                        <span class="text-xs text-gray-400 mt-2">Coming Soon</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>