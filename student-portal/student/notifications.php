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
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
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
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-sm"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
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
                <a href="profile.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>
                <a href="notifications.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-orange-500 text-sm font-medium text-orange-600">
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 animate-fade-in">
        <div class="px-4 sm:px-0">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-white flex items-center">
                                    <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                                    </svg>
                                    Notifications
                                </h1>
                                <p class="text-orange-100 mt-1">Stay updated with important announcements</p>
                            </div>
                            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <span class="text-white font-medium"><?php echo count($notifications); ?> Notifications</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Messages -->
            <?php if (isset($error)): ?>
                <div class="mb-6 animate-slide-up">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 11.414l2.707-2.707a1 1 0 111.414 1.414L11.414 12.5l2.293 2.293a1 1 0 01-1.414 1.414L10 13.414l-2.707 2.707a1 1 0 01-1.414-1.414L8.586 12.5 6.293 10.207a1 1 0 011.414-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notifications List -->
            <div class="space-y-4">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $index => $notification): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 animate-slide-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="p-6">
                                <!-- Notification Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-red-500 rounded-lg flex items-center justify-center animate-pulse-slow">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-xl font-semibold text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h3>
                                            <div class="flex items-center space-x-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <?php echo $notification['department_name'] ? htmlspecialchars($notification['department_name']) : 'All Departments'; ?>
                                                </span>
                                                <p class="text-sm text-gray-500 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php
                                        $isRecent = strtotime($notification['created_at']) > strtotime('-7 days');
                                        if ($isRecent):
                                        ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5 animate-pulse"></span>
                                                Recent
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($notification['file_path'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                </svg>
                                                Attachment
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Notification Content -->
                                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-lg p-4 mb-4 border-l-4 border-orange-400">
                                    <div class="text-gray-800 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </div>
                                </div>

                                <!-- Attachment Section -->
                                <?php if (!empty($notification['file_path'])): ?>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="bg-orange-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-orange-900">
                                                            <?php echo htmlspecialchars(basename($notification['file_path'])); ?>
                                                        </p>
                                                        <p class="text-xs text-orange-600">Click to download attachment</p>
                                                    </div>
                                                </div>
                                                <a href="../uploads/<?php echo htmlspecialchars($notification['file_path']); ?>" 
                                                   download
                                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-orange-700 bg-orange-100 hover:bg-orange-200 transition-all duration-200 shadow-sm hover:shadow-md">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center animate-slide-up">
                        <div class="w-16 h-16 bg-gradient-to-r from-orange-100 to-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-5"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Notifications</h3>
                        <p class="text-gray-500 max-w-sm mx-auto">
                            There are no notifications available at this time. We'll notify you when there are new announcements from your department or administration.
                        </p>
                        <div class="mt-6">
                            <a href="dashboard.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                </svg>
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script>
        // Smooth scroll animation for cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all notification cards
        document.querySelectorAll('.animate-slide-up').forEach(card => {
            observer.observe(card);
        });

        // Add hover effects to notification cards
        document.querySelectorAll('.bg-white.rounded-xl').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.01)';
                card.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
                card.style.boxShadow = '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';
            });
        });

        // Auto-refresh notifications every 5 minutes
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(() => {
                        // Optionally show a subtle indication that data was refreshed
                        console.log('Notifications refreshed');
                    })
                    .catch(error => console.log('Refresh failed:', error));
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>
