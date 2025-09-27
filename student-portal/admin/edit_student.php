<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$student = null;

// Get departments for dropdown
$departments = getAllDepartments(getConnection());

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    // Sanitize all inputs
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $dateOfBirth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $address = sanitizeInput($_POST['address']);
    $departmentId = sanitizeInput($_POST['department_id']);
    
    // Father details
    $fatherName = sanitizeInput($_POST['father_name']);
    $fatherPhone = sanitizeInput($_POST['father_phone']);
    $fatherOccupation = sanitizeInput($_POST['father_occupation']);
    $fatherEmail = sanitizeInput($_POST['father_email']);
    
    // Education details
    $tenthPercentage = sanitizeInput($_POST['tenth_percentage']);
    $tenthBoard = sanitizeInput($_POST['tenth_board']);
    $tenthYear = sanitizeInput($_POST['tenth_year']);
    $twelfthPercentage = sanitizeInput($_POST['twelfth_percentage']);
    $twelfthBoard = sanitizeInput($_POST['twelfth_board']);
    $twelfthYear = sanitizeInput($_POST['twelfth_year']);
    
    // Server-side validation
    $errors = [];
    
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!validateEmail($email)) $errors[] = 'Please enter a valid email address';
    
    if (empty($departmentId)) $errors[] = 'Department is required';
    
    if (!empty($phone) && !validatePhone($phone)) $errors[] = 'Please enter a valid 10-digit phone number';
    if (!empty($fatherPhone) && !validatePhone($fatherPhone)) $errors[] = 'Please enter a valid father phone number';
    if (!empty($fatherEmail) && !validateEmail($fatherEmail)) $errors[] = 'Please enter a valid father email address';
    
    if (!empty($tenthPercentage) && !validatePercentage($tenthPercentage)) $errors[] = 'Please enter a valid 10th percentage (0-100)';
    if (!empty($twelfthPercentage) && !validatePercentage($twelfthPercentage)) $errors[] = 'Please enter a valid 12th percentage (0-100)';
    if (!empty($tenthYear) && !validateYear($tenthYear)) $errors[] = 'Please enter a valid 10th year';
    if (!empty($twelfthYear) && !validateYear($twelfthYear)) $errors[] = 'Please enter a valid 12th year';
    
    // Check if email already exists for another student
    if (empty($errors)) {
        try {
            if (emailExists($pdo, $email, $studentId)) {
                $errors[] = 'Email address already exists';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    }
    
    if (empty($errors)) {
        try {
            // Update student record
            $stmt = $pdo->prepare("
                UPDATE students SET
                    first_name = ?, last_name = ?, email = ?, phone = ?, 
                    date_of_birth = ?, gender = ?, address = ?, department_id = ?,
                    father_name = ?, father_phone = ?, father_occupation = ?, father_email = ?,
                    tenth_percentage = ?, tenth_board = ?, tenth_year = ?,
                    twelfth_percentage = ?, twelfth_board = ?, twelfth_year = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $firstName, $lastName, $email, $phone,
                $dateOfBirth, $gender, $address, $departmentId,
                $fatherName, $fatherPhone, $fatherOccupation, $fatherEmail,
                $tenthPercentage, $tenthBoard, $tenthYear,
                $twelfthPercentage, $twelfthBoard, $twelfthYear,
                $studentId
            ]);
            
            if ($result) {
                logActivity("Admin updated student: {$student['student_id']} ({$firstName} {$lastName})");
                $success = 'Student profile updated successfully!';
                
                // Refresh student data
                $stmt = $pdo->prepare("
                    SELECT s.*, d.name as department_name 
                    FROM students s 
                    JOIN departments d ON s.department_id = d.id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$studentId]);
                $student = $stmt->fetch();
                
            } else {
                $error = 'Profile update failed. Please try again.';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
            logActivity("Edit student error: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Admin Portal</title>
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
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center animate-slide-up">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center animate-slide-up">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($student): ?>
            <!-- Edit Student Header -->
            <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12">
                                <div class="h-12 w-12 bg-gradient-to-r from-green-600 to-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h1 class="text-xl font-bold text-gray-900">
                                    Edit Student Profile
                                </h1>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    (ID: <?php echo htmlspecialchars($student['student_id']); ?>)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="POST" onsubmit="return validateProfileUpdate()">
                <!-- Basic Information Section -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Basic Information
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500" 
                                       value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                                <p class="mt-1 text-xs text-gray-500">Student ID cannot be changed</p>
                            </div>
                            
                            <div>
                                <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department <span class="text-red-500">*</span></label>
                                <select id="department_id" name="department_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo ($student['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="first_name" name="first_name" required maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>">
                            </div>
                            
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="last_name" name="last_name" required maxlength="50"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" required maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['email']); ?>">
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" id="phone" name="phone" maxlength="15"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['phone']); ?>">
                            </div>
                            
                            <div>
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo $student['date_of_birth']; ?>">
                            </div>
                            
                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <select id="gender" name="gender"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea id="address" name="address" rows="3" maxlength="500"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Enter full address..."><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                </div>

                <!-- Father Details Section -->
                <div class="bg-white shadow-lg rounded-lg border border-gray-200 mb-6 animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Father Details
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="father_name" class="block text-sm font-medium text-gray-700 mb-2">Father's Name</label>
                                <input type="text" id="father_name" name="father_name" maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['father_name']); ?>">
                            </div>
                            
                            <div>
                                <label for="father_phone" class="block text-sm font-medium text-gray-700 mb-2">Father's Phone</label>
                                <input type="tel" id="father_phone" name="father_phone" maxlength="15"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['father_phone']); ?>">
                            </div>
                            
                            <div>
                                <label for="father_occupation" class="block text-sm font-medium text-gray-700 mb-2">Father's Occupation</label>
                                <input type="text" id="father_occupation" name="father_occupation" maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['father_occupation']); ?>">
                            </div>
                            
                            <div>
                                <label for="father_email" class="block text-sm font-medium text-gray-700 mb-2">Father's Email</label>
                                <input type="email" id="father_email" name="father_email" maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($student['father_email']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Education Details Section -->
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
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- 10th Standard -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-base font-semibold text-gray-900 mb-4">10th Standard</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label for="tenth_percentage" class="block text-sm font-medium text-gray-700 mb-2">Percentage</label>
                                        <input type="number" id="tenth_percentage" name="tenth_percentage" step="0.01" min="0" max="100"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo $student['tenth_percentage']; ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="tenth_board" class="block text-sm font-medium text-gray-700 mb-2">Board</label>
                                        <input type="text" id="tenth_board" name="tenth_board" maxlength="100"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo htmlspecialchars($student['tenth_board']); ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="tenth_year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                        <input type="number" id="tenth_year" name="tenth_year" min="1980" max="2030"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo $student['tenth_year']; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- 12th Standard -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-base font-semibold text-gray-900 mb-4">12th Standard</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label for="twelfth_percentage" class="block text-sm font-medium text-gray-700 mb-2">Percentage</label>
                                        <input type="number" id="twelfth_percentage" name="twelfth_percentage" step="0.01" min="0" max="100"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo $student['twelfth_percentage']; ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="twelfth_board" class="block text-sm font-medium text-gray-700 mb-2">Board</label>
                                        <input type="text" id="twelfth_board" name="twelfth_board" maxlength="100"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo htmlspecialchars($student['twelfth_board']); ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="twelfth_year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                        <input type="number" id="twelfth_year" name="twelfth_year" min="1980" max="2030"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               value="<?php echo $student['twelfth_year']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4 mb-6">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Student Profile
                    </button>
                    
                    <a href="student_detail.php?id=<?php echo $student['id']; ?>" 
                       class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Details
                    </a>
                    
                    <a href="students.php" 
                       class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Students List
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script src="../assets/js/validation.js"></script>
    <script>
        function validateProfileUpdate() {
            // Add any custom validation here
            return true;
        }
    </script>
</body>
</html>