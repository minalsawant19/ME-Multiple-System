<?php
session_start();
include('authentication.php');
include('dbcon.php');

$month = isset($_GET['month']) ? $_GET['month'] : date("Y-m");

// Fetch all employees
$emp_query = mysqli_query($conn, "SELECT id, name FROM tbl_employee ORDER BY id ASC");

$search = isset($_GET['search']) ? trim($_GET['search']) : '';


if ($search !== '') {
    $emp_query = mysqli_query(
        $conn,
        "SELECT id, name FROM tbl_employee 
         WHERE name LIKE '%$search%' 
         ORDER BY id ASC"
    );
} else {
    $emp_query = mysqli_query(
        $conn,
        "SELECT id, name FROM tbl_employee ORDER BY id ASC"
    );
}

// Extract year and month for SQL
$year = date('Y', strtotime($month));
$monthNumber = date('m', strtotime($month));
$daysInMonth = date('t', strtotime($month));
?>

<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

<div class="container-fluid">
    <div class="attendance-page">
        <!-- Search + Title -->
        <div class="top-section">
            <form method="GET" class="search-form">
                <input type="month" name="month" value="<?= $month ?>" hidden>
                <input type="text" name="search" class="search-bar" placeholder="Search Employee..." value="">

            </form>
            <h1>View Employee Attendance</h1>
        </div>

        <div class="filter-card">
            <label>Select Month :</label>
            <input type="month" id="month" class="month-input" value="<?= $month; ?>">
            <button onclick="filterMonth()" class="view-btn">View</button>
        </div>

        <div class="legend-bar">
            <span class="lg p">P Present</span>
            <span class="lg a">A Absent</span>
            <span class="lg l">L Leave</span>
            <span class="lg hd">HD Half Day</span>
            <span class="lg h">H Holiday</span>
            <span class="lg wo">WO Week Off</span>
        </div>

        <div class="table-wrapper">
            <table class="att-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Employee</th>

                        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                            <th><?= $d; ?><br><?= date('D', strtotime("$year-$monthNumber-$d")); ?></th>
                        <?php endfor; ?>

                    </tr>
                </thead>

                <tbody>
                    <?php
                    $i = 1;
                    while ($emp = mysqli_fetch_assoc($emp_query)) {
                        $emp_id = $emp['id'];

                        // Fetch attendance for this employee
                        $att_data_query = mysqli_query(
                            $conn,
                            "SELECT attendance_date, status 
                     FROM tbl_attendance 
                     WHERE employee_id='$emp_id'
                     AND MONTH(attendance_date)='$monthNumber'
                     AND YEAR(attendance_date)='$year'"
                        );

                        $attendance = [];
                        while ($row = mysqli_fetch_assoc($att_data_query)) {
                            $day = date('j', strtotime($row['attendance_date']));
                            $attendance[$day] = $row['status'];
                        }
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td class="emp-name">
                                <?= $emp['name']; ?>
                                <a style="text-decoration: none;" href="view-detail-attendance.php?id=<?= $emp_id ?>&month=<?= $month ?>" class="mini-view-btn">View</a>


                            </td>

                            <?php
                            $status_short = [
                                "Present" => "P",
                                "Absent" => "A",
                                "Leave" => "L",
                                "Half Day" => "HD",
                                "Holiday" => "H",
                                "Week Off" => "WO"
                            ];

                            for ($d = 1; $d <= $daysInMonth; $d++):
                                $status = isset($attendance[$d]) ? $attendance[$d] : "";
                                $class = "";

                                if ($status == "Present") $class = "present";
                                if ($status == "Absent") $class = "absent";
                                if ($status == "Leave") $class = "leave";
                                if ($status == "Half Day") $class = "halfday";
                                if ($status == "Holiday") $class = "holiday";
                                if ($status == "Week Off") $class = "weekoff";

                                $display = isset($status_short[$status]) ? $status_short[$status] : "";
                            ?>
                                <td class="<?= $class; ?>">
                                    <?= $display; ?>
                                </td>
                            <?php endfor; ?>

                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    function filterMonth() {
        let month = document.getElementById("month").value;
        window.location.href = "?month=" + month;
    }
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>