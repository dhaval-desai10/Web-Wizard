<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get departments for filter/search
$departments = getAllDepartments(getConnection());

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT student_id, first_name, last_name FROM students WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $studentToDelete = $stmt->fetch();
        
        if ($studentToDelete) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $result = $stmt->execute([$_GET['id']]);
            
            if ($result) {
                logActivity("Admin deleted student: {$studentToDelete['student_id']} ({$studentToDelete['first_name']} {$studentToDelete['last_name']})");
                $success = 'Student record deleted successfully.';
            } else {
                $error = 'Failed to delete student record.';
            }
        } else {
            $error = 'Student not found.';
        }
    } catch (PDOException $e) {
        $error = 'Database error occurred while deleting student.';
        logActivity("Delete student error: " . $e->getMessage());
    }
    
    // Redirect to clear URL parameters
    header("Location: students.php" . ($success ? "?success=" . urlencode($success) : ""));
    exit();
}

// Handle search
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$departmentFilter = isset($_GET['department']) ? (int)$_GET['department'] : '';

// Build query based on search criteria
$whereClause = "";
$params = [];

if (!empty($searchTerm)) {
    $whereClause .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($departmentFilter)) {
    $whereClause .= " AND s.department_id = ?";
    $params[] = $departmentFilter;
}

// Get students
$students = [];
try {
    $pdo = getConnection();
    $sql = "
        SELECT s.*, d.name as department_name 
        FROM students s 
        JOIN departments d ON s.department_id = d.id 
        WHERE 1=1 $whereClause 
        ORDER BY s.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error occurred while loading students.';
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
    <title>Manage Students - Admin Portal</title>
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
        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center animate-slide-up">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center animate-slide-up">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search Students
                    </h3>
                </div>
            </div>
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="search">
                    
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Term</label>
                        <input type="text" id="search" name="search" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>"
                               placeholder="Student ID, Name, or Email">
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select id="department" name="department" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($departmentFilter == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-md hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </button>
                        <a href="students.php" 
                           class="px-4 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white shadow-lg rounded-lg border border-gray-200 animate-slide-up">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Students List
                        <?php if (!empty($searchTerm) || !empty($departmentFilter)): ?>
                            <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                <?php echo count($students); ?> results found
                            </span>
                        <?php else: ?>
                            <span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">
                                <?php echo count($students); ?> total students
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
            </div>
            
            <?php if (empty($students)): ?>
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No students found</h3>
                    <?php if (!empty($searchTerm) || !empty($departmentFilter)): ?>
                        <p class="mt-2 text-gray-500">Try adjusting your search criteria.</p>
                    <?php else: ?>
                        <p class="mt-2 text-gray-500">No students are registered yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                                                    <span class="text-white font-medium text-sm">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($student['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($student['student_id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($student['department_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $student['phone'] ? htmlspecialchars($student['phone']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($student['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="student_detail.php?id=<?php echo $student['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-green-300 text-xs font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                            <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-200"
                                               onclick="return confirmDelete('<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Back to Dashboard -->
        <div class="text-center mt-6">
            <a href="dashboard.php" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </main>

    <script src="../assets/js/validation.js"></script>
    <script>
        function confirmDelete(studentName) {
            return confirm(`Are you sure you want to delete ${studentName}? This action cannot be undone.`);
        }
    </script>
</body>
</html>