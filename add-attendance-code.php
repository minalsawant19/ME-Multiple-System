<?php
session_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');


// Fetch all employees BEFORE HTML
$emp_query = mysqli_query($conn, "SELECT id, name FROM tbl_employee ORDER BY name ASC");
if (!$emp_query) {
    die("Employee Query Failed: " . mysqli_error($conn));
}

// Save attendance when form submits
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $attendanceDate = $_POST['attendanceDate'];
    $statuses = $_POST['status'];

    foreach ($statuses as $employee_id => $status) {
        $check = mysqli_query($conn, "SELECT * FROM tbl_attendance 
                                      WHERE employee_id='$employee_id' 
                                      AND attendance_date='$attendanceDate'");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "INSERT INTO tbl_attendance 
                                 (employee_id, attendance_date, status) 
                                 VALUES ('$employee_id', '$attendanceDate', '$status')");
        }
    }

    if(mysqli_query($conn, $query)){ $_SESSION['message'] = "Attendance Added Successfully!"; header("Location: view-attendance.php"); } else { echo "Error: " . mysqli_error($con); }
}
?>
