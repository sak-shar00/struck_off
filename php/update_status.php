<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rollno = $_POST['rollno'];
    $comments = $_POST['comments'];
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Find student ID based on roll number
    $stmt = $conn->prepare("SELECT id FROM Students WHERE roll_number = ?");
    $stmt->bind_param("s", $rollno);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();

    if ($student_id) {
        // Update the status based on the role
        $status = '';

        if ($role == 'Dealing Hand') {
            $status = 'Level 2'; // Next level after Dealing Hand
        } elseif ($role == 'In-Charge') {
            $status = 'Level 3'; // Next level after In-Charge
        } elseif ($role == 'DR') {
            $status = 'Level 4'; // Next level after DR
        } elseif ($role == 'Dean Academics') {
            $status = 'Completed'; // Final stage
        }

        $stmt = $conn->prepare("UPDATE CancellationRequests SET comments = ?, status = ? WHERE student_id = ? AND status = 'Level 1'");
        $stmt->bind_param("ssi", $comments, $status, $student_id);
        $stmt->execute();
        $stmt->close();

        echo "Remarks submitted. The request has been moved to the next level.";
    } else {
        echo "Invalid roll number.";
    }

    $conn->close();
}
?>
