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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - My Profile</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="notifications.php">Notifications</a>
                <a href="messages.php">Messages</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2>Update Profile</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateProfileUpdate()">
                <!-- Basic Information -->
                <h3>Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                        <small>Student ID cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['department_name']); ?>" readonly>
                        <small>Department cannot be changed</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        <div id="first_name_error" class="error-message" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        <div id="last_name_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        <div id="email_error" class="error-message" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['phone']); ?>">
                        <div id="phone_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                               value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>
                
                <!-- Father Details -->
                <h3>Father Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="father_name">Father's Name</label>
                        <input type="text" id="father_name" name="father_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="father_phone">Father's Phone</label>
                        <input type="tel" id="father_phone" name="father_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_phone']); ?>">
                        <div id="father_phone_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="father_occupation">Father's Occupation</label>
                        <input type="text" id="father_occupation" name="father_occupation" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_occupation']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="father_email">Father's Email</label>
                        <input type="email" id="father_email" name="father_email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['father_email']); ?>">
                        <div id="father_email_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- Education Details -->
                <h3>Education Details</h3>
                
                <h4>10th Standard</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tenth_percentage">Percentage</label>
                        <input type="number" id="tenth_percentage" name="tenth_percentage" class="form-control" 
                               step="0.01" min="0" max="100"
                               value="<?php echo htmlspecialchars($student['tenth_percentage']); ?>">
                        <div id="tenth_percentage_error" class="error-message" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tenth_board">Board</label>
                        <input type="text" id="tenth_board" name="tenth_board" class="form-control" 
                               value="<?php echo htmlspecialchars($student['tenth_board']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tenth_year">Year</label>
                        <input type="number" id="tenth_year" name="tenth_year" class="form-control" 
                               min="1990" max="<?php echo date('Y'); ?>"
                               value="<?php echo htmlspecialchars($student['tenth_year']); ?>">
                        <div id="tenth_year_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <h4>12th Standard</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="twelfth_percentage">Percentage</label>
                        <input type="number" id="twelfth_percentage" name="twelfth_percentage" class="form-control" 
                               step="0.01" min="0" max="100"
                               value="<?php echo htmlspecialchars($student['twelfth_percentage']); ?>">
                        <div id="twelfth_percentage_error" class="error-message" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="twelfth_board">Board</label>
                        <input type="text" id="twelfth_board" name="twelfth_board" class="form-control" 
                               value="<?php echo htmlspecialchars($student['twelfth_board']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="twelfth_year">Year</label>
                        <input type="number" id="twelfth_year" name="twelfth_year" class="form-control" 
                               min="1990" max="<?php echo date('Y'); ?>"
                               value="<?php echo htmlspecialchars($student['twelfth_year']); ?>">
                        <div id="twelfth_year_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">Update Profile</button>
                    <a href="dashboard.php" class="btn" style="background: #6c757d; margin-left: 10px;">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>