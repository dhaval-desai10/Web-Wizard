<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$error = '';
$student = null;

// Get student ID from URL
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId <= 0) {
    $error = 'Invalid student ID.';
} else {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, d.name as department_name 
            FROM students s 
            JOIN departments d ON s.department_id = d.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $error = 'Student not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error occurred.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - Admin Portal</title>
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
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
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
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
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
                <a href="students.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-blue-500 text-sm font-medium text-blue-600">
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

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 animate-fade-in">
        <?php if ($error): ?>
            <!-- Error Message -->
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center animate-slide-up">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <?php echo $error; ?>
            </div>
            <div class="text-center">
                <a href="students.php" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Students List
                </a>
            </div>
        <?php else: ?>
            <!-- Student Profile Header -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-16 w-16">
                                <div class="h-16 w-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-xl">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h1 class="text-2xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </h1>
                                <p class="text-sm text-gray-600 mt-1">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-2">
                                    <?php echo htmlspecialchars($student['department_name']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Information Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Basic Information -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Basic Information
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">First Name</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($student['first_name']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Last Name</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($student['last_name']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($student['email']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['phone'] ? htmlspecialchars($student['phone']) : 'Not provided'; ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['date_of_birth'] ? formatDate($student['date_of_birth']) : 'Not provided'; ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Gender</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['gender'] ? htmlspecialchars($student['gender']) : 'Not specified'; ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Father Details -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Father Details
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Father's Name</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['father_name'] ? htmlspecialchars($student['father_name']) : 'Not provided'; ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Father's Phone</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['father_phone'] ? htmlspecialchars($student['father_phone']) : 'Not provided'; ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Occupation</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['father_occupation'] ? htmlspecialchars($student['father_occupation']) : 'Not provided'; ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Father's Email</dt>
                                <dd class="text-sm text-gray-900"><?php echo $student['father_email'] ? htmlspecialchars($student['father_email']) : 'Not provided'; ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Address Section -->
            <?php if ($student['address']): ?>
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Address
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($student['address']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Education Details -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        Education Details
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 10th Standard -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-base font-semibold text-gray-900 mb-3">10th Standard</h4>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Percentage</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['tenth_percentage'] ? $student['tenth_percentage'] . '%' : 'Not provided'; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Board</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['tenth_board'] ? htmlspecialchars($student['tenth_board']) : 'Not provided'; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Year</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['tenth_year'] ? $student['tenth_year'] : 'Not provided'; ?></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- 12th Standard -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-base font-semibold text-gray-900 mb-3">12th Standard</h4>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Percentage</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['twelfth_percentage'] ? $student['twelfth_percentage'] . '%' : 'Not provided'; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Board</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['twelfth_board'] ? htmlspecialchars($student['twelfth_board']) : 'Not provided'; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Year</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $student['twelfth_year'] ? $student['twelfth_year'] : 'Not provided'; ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Information -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Registration Information
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Registered On</dt>
                            <dd class="text-sm text-gray-900"><?php echo formatDateTime($student['created_at']); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="text-sm text-gray-900"><?php echo formatDateTime($student['updated_at']); ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4 mb-6">
                <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                   class="inline-flex items-center px-6 py-3 border border-green-300 text-sm font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Student
                </a>
                <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                   class="inline-flex items-center px-6 py-3 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200"
                   onclick="return confirmDelete('<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Student
                </a>
                <a href="students.php" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Students List
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/validation.js"></script>
    <script>
        function confirmDelete(studentName) {
            return confirm(`Are you sure you want to delete ${studentName}? This action cannot be undone.`);
        }
    </script>
</body>
</html>