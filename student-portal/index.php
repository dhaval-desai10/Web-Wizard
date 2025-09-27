<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check remember me cookie
$rememberedStudentId = getRememberMeCookie();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = sanitizeInput($_POST['student_id']);
    $password = sanitizeInput($_POST['password']);
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($studentId) || empty($password)) {
        $error = 'Please enter both Student ID and Password';
    } else {
        try {
            $pdo = getConnection();
            
            // Check if it's admin login
            if ($studentId === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_username'] = ADMIN_USERNAME;
                
                if ($rememberMe) {
                    setRememberMeCookie('admin');
                }
                
                logActivity("Admin logged in");
                header('Location: admin/dashboard.php');
                exit();
            } else {
                // Student login
                $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
                $stmt->execute([$studentId]);
                $student = $stmt->fetch();
                
                if ($student && verifyPassword($password, $student['password'])) {
                    $_SESSION['user_id'] = $student['id'];
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
                    $_SESSION['department_id'] = $student['department_id'];
                    
                    if ($rememberMe) {
                        setRememberMeCookie($student['student_id']);
                    }
                    
                    logActivity("Student {$student['student_id']} logged in");
                    header('Location: student/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid Student ID or Password';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
            logActivity("Login error: " . $e->getMessage());
        }
    }
}

// Auto-login from remember me cookie
if (!isLoggedIn() && $rememberedStudentId) {
    if ($rememberedStudentId === 'admin') {
        $_SESSION['admin'] = true;
        $_SESSION['admin_username'] = ADMIN_USERNAME;
        header('Location: admin/dashboard.php');
        exit();
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$rememberedStudentId]);
            $student = $stmt->fetch();
            
            if ($student) {
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
                $_SESSION['department_id'] = $student['department_id'];
                header('Location: student/dashboard.php');
                exit();
            }
        } catch (PDOException $e) {
            clearRememberMeCookie();
        }
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
    <title>Student Portal - Login</title>
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
<body class="font-inter min-h-screen bg-gray-50">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-gradient-to-br from-red-50 via-pink-50 to-rose-50">
        <div class="absolute inset-0 bg-white bg-opacity-80"></div>
        <div class="absolute -top-10 -right-10 w-72 h-72 bg-gradient-to-r from-red-400 to-pink-400 opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-96 h-96 bg-gradient-to-r from-pink-400 to-rose-400 opacity-10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo/Brand Section -->
            <div class="text-center mb-8 animate-fade-in">
                <div class="mx-auto w-16 h-16 bg-gradient-to-r from-red-600 to-pink-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Student Portal</h1>
                <p class="text-gray-600">Welcome back! Please sign in to continue.</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white shadow-lg rounded-2xl p-8 animate-slide-up">
                <form method="POST" onsubmit="return validateLogin()" class="space-y-6">
                    <?php if ($error): ?>
                        <div class="bg-red-500 bg-opacity-10 border border-red-500 border-opacity-20 text-red-100 px-4 py-3 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <?php echo $error; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-500 bg-opacity-10 border border-green-500 border-opacity-20 text-green-100 px-4 py-3 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <?php echo $success; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Student ID Field -->
                    <div class="space-y-2">
                        <label for="student_id" class="block text-sm font-medium text-gray-700">
                            Student ID or Admin Username
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="student_id" 
                                id="student_id" 
                                required
                                value="<?php echo $rememberedStudentId ? htmlspecialchars($rememberedStudentId) : ''; ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300"
                                placeholder="Enter Student ID or 'admin'"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="student_id_error" class="text-red-500 text-sm hidden"></div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300"
                                placeholder="Enter your password"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="password_error" class="text-red-500 text-sm hidden"></div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember_me" 
                                name="remember_me" 
                                type="checkbox" 
                                <?php echo $rememberedStudentId ? 'checked' : ''; ?>
                                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 focus:ring-2"
                            >
                            <label for="remember_me" class="ml-2 text-sm text-gray-600">
                                Remember me
                            </label>
                        </div>
                        <div class="text-sm">
                            <a href="#" class="text-red-600 hover:text-red-700 font-medium transition-colors duration-300">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-lg"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Sign In
                        </span>
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-8 text-center">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="text-red-600 hover:text-red-700 font-semibold transition-colors duration-300">
                            Create Account
                        </a>
                    </p>
                </div>
            </div>

            
        </div>
    </div>

    <script src="assets/js/validation.js"></script>
</body>
</html>