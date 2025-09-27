<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Get departments for dropdown
$departments = [];
try {
    $pdo = getConnection();
    $departments = getAllDepartments($pdo);
} catch (PDOException $e) {
    $error = 'Database error occurred. Please try again later.';
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    $departmentId = sanitizeInput($_POST['department_id']);
    
    // Server-side validation
    $errors = [];
    
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!validateEmail($email)) $errors[] = 'Invalid email format';
    if (empty($password)) $errors[] = 'Password is required';
    elseif (!validatePassword($password)) $errors[] = 'Password must be at least 6 characters';
    if (empty($confirmPassword)) $errors[] = 'Please confirm your password';
    elseif ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    if (empty($departmentId)) $errors[] = 'Department is required';
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email address is already registered';
            } else {
                // Generate student ID based on department and year
                $year = date('Y');
                $deptCode = '';
                foreach ($departments as $dept) {
                    if ($dept['id'] == $departmentId) {
                        $deptCode = strtoupper(substr($dept['name'], 0, 3));
                        break;
                    }
                }
                
                // Find next available student ID
                $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
                $stmt->execute([$year . $deptCode . '%']);
                $lastStudent = $stmt->fetch();
                
                if ($lastStudent) {
                    $lastNumber = (int)substr($lastStudent['student_id'], -4);
                    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $newNumber = '0001';
                }
                
                $studentId = $year . $deptCode . $newNumber;
                
                // Hash password
                $hashedPassword = hashPassword($password);
                
                // Insert student record with minimal data
                $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, email, password, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$studentId, $firstName, $lastName, $email, $hashedPassword, $departmentId]);
                
                $success = "Registration successful! Your Student ID is: <strong>$studentId</strong><br>Please complete your profile after logging in.";
                logActivity("New student registered: $studentId");
                
                // Clear form data after successful registration
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
            logActivity("Registration error: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Register</title>
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
<body class="font-inter min-h-screen bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-800">
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-72 h-72 bg-white opacity-10 rounded-full blur-3xl animate-pulse-slow"></div>
        <div class="absolute -bottom-10 -left-10 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl animate-pulse-slow"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>
    </div>
    
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo/Brand Section -->
            <div class="text-center mb-8 animate-fade-in">
                <div class="mx-auto w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Create Account</h1>
                <p class="text-indigo-100">Join our student portal today</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white bg-opacity-10 backdrop-blur-md rounded-3xl shadow-2xl p-8 animate-slide-up">
                <form method="POST" action="" class="space-y-6" onsubmit="return validateSimpleRegistration()">
                    <?php if ($error): ?>
                        <div class="bg-red-500 bg-opacity-10 border border-red-500 border-opacity-20 text-red-100 px-4 py-3 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div><?php echo $error; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-500 bg-opacity-10 border border-green-500 border-opacity-20 text-green-100 px-4 py-3 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div><?php echo $success; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="first_name" class="block text-sm font-medium text-white">
                                First Name
                            </label>
                            <input 
                                type="text" 
                                name="first_name" 
                                id="first_name" 
                                required
                                value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300"
                                placeholder="John"
                            >
                            <div id="first_name_error" class="text-red-300 text-sm hidden"></div>
                        </div>

                        <div class="space-y-2">
                            <label for="last_name" class="block text-sm font-medium text-white">
                                Last Name
                            </label>
                            <input 
                                type="text" 
                                name="last_name" 
                                id="last_name" 
                                required
                                value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300"
                                placeholder="Doe"
                            >
                            <div id="last_name_error" class="text-red-300 text-sm hidden"></div>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-white">
                            Email Address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300"
                                placeholder="john.doe@email.com"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="email_error" class="text-red-300 text-sm hidden"></div>
                    </div>

                    <!-- Department Field -->
                    <div class="space-y-2">
                        <label for="department_id" class="block text-sm font-medium text-white">
                            Department
                        </label>
                        <div class="relative">
                            <select 
                                name="department_id" 
                                id="department_id" 
                                required
                                class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300 appearance-none"
                            >
                                <option value="" class="bg-gray-800 text-white">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            class="bg-gray-800 text-white"
                                            <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="department_error" class="text-red-300 text-sm hidden"></div>
                    </div>

                    <!-- Password Fields -->
                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-2">
                            <label for="password" class="block text-sm font-medium text-white">
                                Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    required
                                    class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300"
                                    placeholder="Minimum 6 characters"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div id="password_error" class="text-red-300 text-sm hidden"></div>
                        </div>

                        <div class="space-y-2">
                            <label for="confirm_password" class="block text-sm font-medium text-white">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="confirm_password" 
                                    id="confirm_password" 
                                    required
                                    class="w-full px-4 py-3 bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent backdrop-blur-sm transition-all duration-300"
                                    placeholder="Repeat password"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div id="confirm_password_error" class="text-red-300 text-sm hidden"></div>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button 
                        type="submit"
                        class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 backdrop-blur-sm"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Create Account
                        </span>
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-8 text-center">
                    <p class="text-indigo-100">
                        Already have an account? 
                        <a href="index.php" class="text-white hover:text-indigo-200 font-semibold transition-colors duration-300 underline underline-offset-2">
                            Sign In
                        </a>
                    </p>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mt-6 bg-black bg-opacity-20 backdrop-blur-md rounded-2xl p-6 animate-fade-in">
                <h3 class="text-white font-semibold mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Quick Registration
                </h3>
                <p class="text-sm text-indigo-100">
                    Just provide your basic information to get started. You can complete your full profile after logging in.
                </p>
            </div>
        </div>
    </div>

    <script>
        function validateSimpleRegistration() {
            clearErrors();
            let isValid = true;
            
            // First Name validation
            const firstName = document.getElementById('first_name').value.trim();
            if (!firstName) {
                showError('first_name_error', 'First name is required');
                isValid = false;
            }
            
            // Last Name validation
            const lastName = document.getElementById('last_name').value.trim();
            if (!lastName) {
                showError('last_name_error', 'Last name is required');
                isValid = false;
            }
            
            // Email validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                showError('email_error', 'Email is required');
                isValid = false;
            } else if (!emailRegex.test(email)) {
                showError('email_error', 'Please enter a valid email address');
                isValid = false;
            }
            
            // Department validation
            const departmentId = document.getElementById('department_id').value;
            if (!departmentId) {
                showError('department_error', 'Please select a department');
                isValid = false;
            }
            
            // Password validation
            const password = document.getElementById('password').value;
            if (!password) {
                showError('password_error', 'Password is required');
                isValid = false;
            } else if (password.length < 6) {
                showError('password_error', 'Password must be at least 6 characters');
                isValid = false;
            }
            
            // Confirm Password validation
            const confirmPassword = document.getElementById('confirm_password').value;
            if (!confirmPassword) {
                showError('confirm_password_error', 'Please confirm your password');
                isValid = false;
            } else if (password !== confirmPassword) {
                showError('confirm_password_error', 'Passwords do not match');
                isValid = false;
            }
            
            return isValid;
        }
        
        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            
            // Add error styling to input
            const inputId = elementId.replace('_error', '');
            const inputElement = document.getElementById(inputId);
            if (inputElement) {
                inputElement.classList.add('border-red-500', 'border-opacity-50');
            }
        }
        
        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="_error"]');
            errorElements.forEach(element => {
                element.classList.add('hidden');
                element.textContent = '';
            });
            
            // Remove error styling from inputs
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.classList.remove('border-red-500', 'border-opacity-50');
            });
        }
        
        // Real-time validation
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value && this.value !== confirmPassword.value) {
                showError('confirm_password_error', 'Passwords do not match');
            } else {
                document.getElementById('confirm_password_error').classList.add('hidden');
                confirmPassword.classList.remove('border-red-500', 'border-opacity-50');
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value && password !== this.value) {
                showError('confirm_password_error', 'Passwords do not match');
            } else {
                document.getElementById('confirm_password_error').classList.add('hidden');
                this.classList.remove('border-red-500', 'border-opacity-50');
            }
        });
    </script>
</body>
</html>