<?php
session_start();
include('authentication.php');
include('dbcon.php');


// SAVE ATTENDANCE BEFORE ANY HTML
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $attendanceDate = $_POST['attendanceDate'];

    if (!empty($_POST['status'])) {

        foreach ($_POST['status'] as $employee_id => $status) {

            // Delete old attendance if exists
            mysqli_query($conn, "DELETE FROM tbl_attendance 
                                  WHERE employee_id='$employee_id' 
                                  AND attendance_date='$attendanceDate'");

            // Insert new attendance
            mysqli_query($conn, "INSERT INTO tbl_attendance 
                                 (employee_id, attendance_date, status) 
                                 VALUES ('$employee_id', '$attendanceDate', '$status')");
        }
    }

    $_SESSION['message'] = "Attendance Saved Successfully!";
    header("Location: view-attendance.php");
    exit;
}

// FETCH EMPLOYEES
$emp_query = mysqli_query($conn, "SELECT id, name FROM tbl_employee ORDER BY name ASC");

include('includes/header.php');
include('includes/navbar.php');
?>

<style>

</style>

<div class="container-fluid">

<div class="attendance-page">
    <h3 class="text-center">Mark Attendance</h3>

    <form method="POST">
        <label>Select Date</label>
        <input type="date" class="form-control mb-3" name="attendanceDate" required>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Half Day</th>
                    <th>Holiday</th>
                    <th>Week Off</th>
                </tr>
            </thead>
            <tbody>
                <?php while($emp = mysqli_fetch_assoc($emp_query)) { ?>
                <tr>
                    <td><?= $emp['name']; ?></td>
                    <?php
                    $statuses = ["Present","Absent","Leave","Half Day","Holiday","Week Off"];
                    foreach($statuses as $status) {
                        echo '<td><input type="radio" name="status['.$emp['id'].']" value="'.$status.'"></td>';

                    }
                    ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <button type="submit">Save Attendance</button>
    </form>
</div>
</div>
<?php
include('includes/scripts.php');
include('includes/footer.php'); 
?>