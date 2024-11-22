<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Dean Academics') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rollno = $_POST['rollno'];

    // Fetch student details and cancellation request details
    $stmt = $conn->prepare("SELECT s.name, s.roll_number, 
                                    c.level1_comments, c.level2_comments, c.level3_comments, c.level4_comments, c.status
                             FROM Students s
                             JOIN CancellationRequests c ON s.id = c.student_id
                             WHERE s.roll_number = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $rollno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Include the PDF library
        require('fpdf.php');
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Student Cancellation Report', 0, 1, 'C');
        $pdf->Ln(10);

        // Fetch and display student name and roll number
        $student = $result->fetch_assoc();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Name: ' . $student['name']);
        $pdf->Ln();
        $pdf->Cell(0, 10, 'Roll Number: ' . $student['roll_number']);
        $pdf->Ln();
        $pdf->Ln(); // Extra space before comments

        // Reset the result pointer and loop through all remarks
        $stmt->data_seek(0);
        $pdf->SetFont('Arial', '', 12);
        
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(0, 10, 'Status: ' . $row['status']);
            $pdf->Ln();
            $pdf->MultiCell(0, 10, 'Level 1 Comments: ' . ($row['level1_comments'] ?: 'No comments'));
            $pdf->Ln();
            $pdf->MultiCell(0, 10, 'Level 2 Comments: ' . ($row['level2_comments'] ?: 'No comments'));
            $pdf->Ln();
            $pdf->MultiCell(0, 10, 'Level 3 Comments: ' . ($row['level3_comments'] ?: 'No comments'));
            $pdf->Ln();
            $pdf->MultiCell(0, 10, 'Level 4 Comments: ' . ($row['level4_comments'] ?: 'No comments'));
            $pdf->Ln();
            $pdf->Ln(); // Extra space between different remarks
        }

        // Output PDF
        $pdf->Output('D', 'Cancellation_Report_' . $student['roll_number'] . '.pdf');
    } else {
        echo "No details found for the provided roll number.";
    }

    $stmt->close();
    $conn->close();
}
?>
