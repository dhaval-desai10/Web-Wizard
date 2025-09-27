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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Portal - Manage Students</h1>
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
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="search-container">
            <h3>Search Students</h3>
            <form method="GET" class="search-form">
                <input type="hidden" name="action" value="search">
                
                <div class="form-group">
                    <label for="search">Search Term</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           placeholder="Student ID, Name, or Email">
                </div>
                
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo ($departmentFilter == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Search</button>
                    <a href="students.php" class="btn" style="background: #6c757d;">Clear</a>
                </div>
            </form>
        </div>

        <!-- Students List -->
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="margin: 0;">
                    Students List 
                    <?php if (!empty($searchTerm) || !empty($departmentFilter)): ?>
                        <span style="font-size: 14px; font-weight: normal;">
                            (<?php echo count($students); ?> results found)
                        </span>
                    <?php else: ?>
                        <span style="font-size: 14px; font-weight: normal;">
                            (<?php echo count($students); ?> total students)
                        </span>
                    <?php endif; ?>
                </h3>
            </div>
            
            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 40px;">
                    <h3>No students found</h3>
                    <?php if (!empty($searchTerm) || !empty($departmentFilter)): ?>
                        <p>Try adjusting your search criteria.</p>
                    <?php else: ?>
                        <p>No students are registered yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                <td><?php echo $student['phone'] ? htmlspecialchars($student['phone']) : '-'; ?></td>
                                <td><?php echo formatDate($student['created_at']); ?></td>
                                <td>
                                    <a href="student_detail.php?id=<?php echo $student['id']; ?>" 
                                       class="btn" style="padding: 5px 10px; margin-right: 5px; font-size: 12px;">
                                        View
                                    </a>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-success" style="padding: 5px 10px; margin-right: 5px; font-size: 12px;">
                                        Edit
                                    </a>
                                    <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                                       class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>