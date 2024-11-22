<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Initialize variables
$rollno = '';
$remarks = '';
$show_form = true;
$pdf_generation_button = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rollno = $_POST['rollno'];
    $remarks = $_POST['remarks'];

    // Find student ID based on roll number
    $stmt = $conn->prepare("SELECT id FROM Students WHERE roll_number = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $rollno);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();

    if ($student_id) {
        // Determine status based on role
        $status = '';
        $level_comments = '';

        switch ($role) {
            case 'Dealing Hand':
                $status = 'Level 1'; 
                $level_comments = 'level1_comments';
                break;
            case 'In-Charge':
                $status = 'Level 2'; 
                $level_comments = 'level2_comments';
                break;
            case 'DR':
                $status = 'Level 3'; 
                $level_comments = 'level3_comments';
                break;
            case 'Dean Academics':
                $status = 'Level 4'; 
                $level_comments = 'level4_comments';
                break;
        }

        // Insert or update cancellation request
        $stmt = $conn->prepare("INSERT INTO CancellationRequests (student_id, $level_comments, status) 
                                VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE $level_comments = VALUES($level_comments), status = VALUES(status)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iss", $student_id, $remarks, $status);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        $show_form = false;
        if ($role == 'Dean Academics') {
            $pdf_generation_button = true;
        }
        echo "Request submitted. The request has been updated based on your role.";
    } else {
        echo "Invalid roll number.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Roll Number Cancellation</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="text-center">Student Roll Number Cancellation System</h1>
        <?php if ($show_form): ?>
            <div class="card shadow-sm p-4 mt-4">
                <form id="requestForm" action="index.php" method="post">
                    <div class="form-group">
                        <label for="rollno">Roll Number:</label>
                        <input type="number" id="rollno" name="rollno" value="<?php echo htmlspecialchars($rollno); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks">Remarks:</label>
                        <textarea id="remarks" name="remarks" placeholder="Enter remarks..." class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($pdf_generation_button): ?>
            <div class="mt-4 text-center">
                <form action="generate_pdf.php" method="post">
                    <input type="hidden" name="rollno" value="<?php echo htmlspecialchars($rollno); ?>">
                    <button type="submit" class="btn btn-success">Generate PDF</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
