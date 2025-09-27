<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireStudent();

$success = '';
$error = '';

// Get departments for dropdown
$departments = getAllDepartments(getConnection());

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $dateOfBirth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $address = sanitizeInput($_POST['address']);
    
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
            if (emailExists($pdo, $email, $_SESSION['user_id'])) {
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
                    date_of_birth = ?, gender = ?, address = ?,
                    father_name = ?, father_phone = ?, father_occupation = ?, father_email = ?,
                    tenth_percentage = ?, tenth_board = ?, tenth_year = ?,
                    twelfth_percentage = ?, twelfth_board = ?, twelfth_year = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $firstName, $lastName, $email, $phone,
                $dateOfBirth, $gender, $address,
                $fatherName, $fatherPhone, $fatherOccupation, $fatherEmail,
                $tenthPercentage, $tenthBoard, $tenthYear,
                $twelfthPercentage, $twelfthBoard, $twelfthYear,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                // Update session data
                $_SESSION['student_name'] = $firstName . ' ' . $lastName;
                
                logActivity("Student {$student['student_id']} updated profile");
                $success = 'Profile updated successfully!';
                
                // Refresh student data
                $stmt = $pdo->prepare("
                    SELECT s.*, d.name as department_name 
                    FROM students s 
                    JOIN departments d ON s.department_id = d.id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $student = $stmt->fetch();
                
            } else {
                $error = 'Profile update failed. Please try again.';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
            logActivity("Profile update error: " . $e->getMessage());
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
    <title>My Profile - Student Portal</title>
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
                        'scale-in': 'scaleIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
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
                <a href="profile.php" class="inline-flex items-center px-1 pt-1 pb-4 border-b-2 border-green-500 text-sm font-medium text-green-600">
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
                    <div class="bg-gradient-to-r from-green-500 via-teal-500 to-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-white flex items-center">
                                    <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    My Profile
                                </h1>
                                <p class="text-green-100 mt-1">Manage your personal information and academic details</p>
                            </div>
                            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <span class="text-white font-medium">ID: <?php echo htmlspecialchars($student['student_id']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Messages -->
            <?php if (!empty($success)): ?>
                <div class="mb-6 animate-scale-in">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    <?php echo $success; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (!empty($error)): ?>
                <div class="mb-6 animate-scale-in">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 11.414l2.707-2.707a1 1 0 111.414 1.414L11.414 12.5l2.293 2.293a1 1 0 01-1.414 1.414L10 13.414l-2.707 2.707a1 1 0 01-1.414-1.414L8.586 12.5 6.293 10.207a1 1 0 011.414-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-red-800">
                                    <?php echo $error; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <form method="POST" class="space-y-8">
                <!-- Personal Information Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up">
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 px-6 py-3">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Personal Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       required 
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                            </div>
                            
                            <div class="space-y-1">
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       required 
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                            </div>

                            <div class="space-y-1">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       value="<?php echo htmlspecialchars($student['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                            </div>

                            <div class="space-y-1">
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($student['phone']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200"
                                       placeholder="10-digit phone number">
                            </div>

                            <div class="space-y-1">
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                <input type="date" 
                                       id="date_of_birth" 
                                       name="date_of_birth" 
                                       value="<?php echo $student['date_of_birth']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                            </div>

                            <div class="space-y-1">
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                <select id="gender" 
                                        name="gender"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="space-y-1 md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea id="address" 
                                          name="address" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200"
                                          placeholder="Enter your complete address"><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Father's Details Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up" style="animation-delay: 0.1s">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-3">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Father's Details
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label for="father_name" class="block text-sm font-medium text-gray-700">Father's Name</label>
                                <input type="text" 
                                       id="father_name" 
                                       name="father_name" 
                                       value="<?php echo htmlspecialchars($student['father_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                       placeholder="Enter father's name">
                            </div>
                            
                            <div class="space-y-1">
                                <label for="father_phone" class="block text-sm font-medium text-gray-700">Father's Phone</label>
                                <input type="tel" 
                                       id="father_phone" 
                                       name="father_phone" 
                                       value="<?php echo htmlspecialchars($student['father_phone']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                       placeholder="10-digit phone number">
                            </div>

                            <div class="space-y-1">
                                <label for="father_occupation" class="block text-sm font-medium text-gray-700">Father's Occupation</label>
                                <input type="text" 
                                       id="father_occupation" 
                                       name="father_occupation" 
                                       value="<?php echo htmlspecialchars($student['father_occupation']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                       placeholder="Enter father's occupation">
                            </div>

                            <div class="space-y-1">
                                <label for="father_email" class="block text-sm font-medium text-gray-700">Father's Email</label>
                                <input type="email" 
                                       id="father_email" 
                                       name="father_email" 
                                       value="<?php echo htmlspecialchars($student['father_email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                       placeholder="father@example.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Education Details Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up" style="animation-delay: 0.2s">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-3">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Education Details
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- 10th Grade Information -->
                        <div class="mb-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                                <div class="w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-sm font-medium mr-2">10</div>
                                10th Grade Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1">
                                    <label for="tenth_percentage" class="block text-sm font-medium text-gray-700">Percentage</label>
                                    <input type="number" 
                                           id="tenth_percentage" 
                                           name="tenth_percentage" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           value="<?php echo $student['tenth_percentage']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200"
                                           placeholder="85.50">
                                </div>

                                <div class="space-y-1">
                                    <label for="tenth_board" class="block text-sm font-medium text-gray-700">Board</label>
                                    <input type="text" 
                                           id="tenth_board" 
                                           name="tenth_board" 
                                           value="<?php echo htmlspecialchars($student['tenth_board']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200"
                                           placeholder="CBSE, ICSE, State Board, etc.">
                                </div>

                                <div class="space-y-1">
                                    <label for="tenth_year" class="block text-sm font-medium text-gray-700">Year</label>
                                    <input type="number" 
                                           id="tenth_year" 
                                           name="tenth_year" 
                                           min="2000" 
                                           max="<?php echo date('Y'); ?>"
                                           value="<?php echo $student['tenth_year']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200"
                                           placeholder="<?php echo date('Y'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 12th Grade Information -->
                        <div>
                            <h3 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                                <div class="w-6 h-6 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center text-sm font-medium mr-2">12</div>
                                12th Grade Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1">
                                    <label for="twelfth_percentage" class="block text-sm font-medium text-gray-700">Percentage</label>
                                    <input type="number" 
                                           id="twelfth_percentage" 
                                           name="twelfth_percentage" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           value="<?php echo $student['twelfth_percentage']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors duration-200"
                                           placeholder="78.25">
                                </div>

                                <div class="space-y-1">
                                    <label for="twelfth_board" class="block text-sm font-medium text-gray-700">Board</label>
                                    <input type="text" 
                                           id="twelfth_board" 
                                           name="twelfth_board" 
                                           value="<?php echo htmlspecialchars($student['twelfth_board']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors duration-200"
                                           placeholder="CBSE, ICSE, State Board, etc.">
                                </div>

                                <div class="space-y-1">
                                    <label for="twelfth_year" class="block text-sm font-medium text-gray-700">Year</label>
                                    <input type="number" 
                                           id="twelfth_year" 
                                           name="twelfth_year" 
                                           min="2000" 
                                           max="<?php echo date('Y'); ?>"
                                           value="<?php echo $student['twelfth_year']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors duration-200"
                                           placeholder="<?php echo date('Y'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information (Read-only) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up" style="animation-delay: 0.3s">
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-3">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Academic Information
                            <span class="ml-2 text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">Read Only</span>
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Student ID</label>
                                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-500">
                                    <?php echo htmlspecialchars($student['student_id']); ?>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-500">
                                    <?php echo htmlspecialchars($student['department_name']); ?>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-500">
                                    <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-500">
                                    <?php echo $student['updated_at'] ? date('M j, Y g:i A', strtotime($student['updated_at'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center animate-slide-up" style="animation-delay: 0.4s">
                    <button type="submit" 
                            class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Profile
                    </button>
                    
                    <a href="dashboard.php" 
                       class="inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['first_name', 'last_name', 'email'];
            let hasErrors = false;
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                const value = field.value.trim();
                
                if (!value) {
                    hasErrors = true;
                    field.classList.add('border-red-500', 'bg-red-50');
                    field.classList.remove('border-gray-300');
                } else {
                    field.classList.remove('border-red-500', 'bg-red-50');
                    field.classList.add('border-gray-300');
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
            }
        });

        // Email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.classList.add('border-red-500', 'bg-red-50');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500', 'bg-red-50');
                this.classList.add('border-gray-300');
            }
        });

        // Phone validation
        ['phone', 'father_phone'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                    if (this.value.length > 10) {
                        this.value = this.value.substr(0, 10);
                    }
                });

                field.addEventListener('blur', function() {
                    const phone = this.value.trim();
                    if (phone && phone.length !== 10) {
                        this.classList.add('border-red-500', 'bg-red-50');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-500', 'bg-red-50');
                        this.classList.add('border-gray-300');
                    }
                });
            }
        });

        // Percentage validation
        ['tenth_percentage', 'twelfth_percentage'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('blur', function() {
                    const percentage = parseFloat(this.value);
                    if (this.value && (isNaN(percentage) || percentage < 0 || percentage > 100)) {
                        this.classList.add('border-red-500', 'bg-red-50');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-500', 'bg-red-50');
                        this.classList.add('border-gray-300');
                    }
                });
            }
        });

        // Auto-hide success messages
        setTimeout(() => {
            const successAlert = document.querySelector('.bg-green-50');
            if (successAlert) {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => successAlert.remove(), 300);
            }
        }, 5000);

        // Smooth focus animations
        document.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease-out';
            });

            field.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>