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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - Student Details</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">Manage Students</a>
                <a href="notifications.php">Notifications</a>
                <a href="messages.php">Messages</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="students.php" class="btn">Back to Students List</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2>
                        Student Details: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <!-- Basic Information -->
                        <div>
                            <h3>Basic Information</h3>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 40%;">Student ID:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">First Name:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['first_name']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Last Name:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Email:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Phone:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['phone'] ? htmlspecialchars($student['phone']) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Date of Birth:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['date_of_birth'] ? formatDate($student['date_of_birth']) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Gender:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['gender'] ? htmlspecialchars($student['gender']) : 'Not specified'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Department:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['department_name']); ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Father Details -->
                        <div>
                            <h3>Father Details</h3>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 40%;">Father's Name:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['father_name'] ? htmlspecialchars($student['father_name']) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Father's Phone:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['father_phone'] ? htmlspecialchars($student['father_phone']) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Occupation:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['father_occupation'] ? htmlspecialchars($student['father_occupation']) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Father's Email:</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['father_email'] ? htmlspecialchars($student['father_email']) : 'Not provided'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Address -->
                    <?php if ($student['address']): ?>
                        <div style="margin-top: 30px;">
                            <h3>Address</h3>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                                <?php echo nl2br(htmlspecialchars($student['address'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Education Details -->
                    <div style="margin-top: 30px;">
                        <h3>Education Details</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <!-- 10th Standard -->
                            <div>
                                <h4>10th Standard</h4>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 40%;">Percentage:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['tenth_percentage'] ? $student['tenth_percentage'] . '%' : 'Not provided'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Board:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['tenth_board'] ? htmlspecialchars($student['tenth_board']) : 'Not provided'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Year:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['tenth_year'] ? $student['tenth_year'] : 'Not provided'; ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- 12th Standard -->
                            <div>
                                <h4>12th Standard</h4>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 40%;">Percentage:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['twelfth_percentage'] ? $student['twelfth_percentage'] . '%' : 'Not provided'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Board:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['twelfth_board'] ? htmlspecialchars($student['twelfth_board']) : 'Not provided'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Year:</td>
                                        <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $student['twelfth_year'] ? $student['twelfth_year'] : 'Not provided'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Information -->
                    <div style="margin-top: 30px;">
                        <h3>Registration Information</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 20%;">Registered On:</td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo formatDateTime($student['created_at']); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Last Updated:</td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo formatDateTime($student['updated_at']); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-success" style="margin-right: 10px;">
                            Edit Student
                        </a>
                        <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                           class="btn btn-danger" style="margin-right: 10px;"
                           onclick="return confirmDelete('<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                            Delete Student
                        </a>
                        <a href="students.php" class="btn">Back to Students List</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>