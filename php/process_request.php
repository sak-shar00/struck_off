<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rollno = $_POST['rollno'];
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
        // Insert new cancellation request
        $stmt = $conn->prepare("INSERT INTO CancellationRequests (student_id, status) VALUES (?, 'Level 1')");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        echo "Request submitted. Please proceed with comments based on your role.";
    } else {
        echo "Invalid roll number.";
    }

    $conn->close();
}
?>
