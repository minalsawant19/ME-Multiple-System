<?php
include('authentication.php');
include('dbcon.php');

// Get employee ID
if (!isset($_GET['id'])) {
    header("Location: view-attendance.php");
    exit();
}

$id = $_GET['id'];

// Determine month & year
if (isset($_GET['month'])) {
    $month = $_GET['month']; // YYYY-MM
} else {
    $month = date('Y-m'); // current month
}

$year = date('Y', strtotime($month));
$monthNumber = date('m', strtotime($month));
$daysInMonth = date('t', strtotime($month));
$firstDayOfMonth = date('N', strtotime("$year-$monthNumber-01")); // 1=Monday

// Previous & Next month
$prevMonth = date('Y-m', strtotime("$year-$monthNumber-01 -1 month"));
$nextMonth = date('Y-m', strtotime("$year-$monthNumber-01 +1 month"));

// Fetch employee
$empQuery = mysqli_query($conn, "SELECT name FROM tbl_employee WHERE id='$id'");
$emp = mysqli_fetch_assoc($empQuery);

// Fetch attendance
$attQuery = mysqli_query(
    $conn,
    "SELECT attendance_date, status 
     FROM tbl_attendance 
     WHERE employee_id='$id'
     AND MONTH(attendance_date)='$monthNumber'
     AND YEAR(attendance_date)='$year'"
);

$attendance = [];
while ($row = mysqli_fetch_assoc($attQuery)) {
    $day = date('j', strtotime($row['attendance_date']));
    $attendance[$day] = $row['status'];
}

$present = $absent = $leave = $halfday = $holiday = 0;

foreach ($attendance as $status) {
    $status = strtolower($status);

    switch ($status) {
        case 'present':
            $present++;
            break;
        case 'absent':
            $absent++;
            break;
        case 'leave':
            $leave++;
            break;
        case 'half day':
        case 'halfday':
            $halfday++;
            break;
        case 'holiday':
            $holiday++;
            break;
    }
}

?>

<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

<!------------------- HTML ------------------->
<div class="container-fluid">
    <div class="inner-container">

        <!-- Header: Name left, Back button right -->
        <div class="header">
            <h2 style="margin:0;"><?= $emp['name']; ?> — Attendance</h2>
            <button
                type="button"
                class="btn btn-secondary btn-sm" style="width: fit-content;"
                onclick="window.location.href='view-attendance.php'">
                &laquo; Back
            </button>
        </div>

        <!-- Month Navigation -->
        <div class="nav-month">
            <a href="?id=<?= $id ?>&month=<?= $prevMonth ?>">&laquo; Previous</a>
            <span style="font-weight:bold;"><?= date('F Y', strtotime($month)) ?></span>
            <a href="?id=<?= $id ?>&month=<?= $nextMonth ?>">Next &raquo;</a>
        </div>

        <div class="attendance-summary-box">
            <span class="present">Present: <?= $present ?></span>
            <span class="absent">Absent: <?= $absent ?></span>
            <span class="leave">Leave: <?= $leave ?></span>
            <span class="halfday">Half Day: <?= $halfday ?></span>
            <span class="holiday">Holiday: <?= $holiday ?></span>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar">
            <!-- Weekday Headers -->
            <?php
            $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            foreach ($weekdays as $wd): ?>
                <div class="day-box"><?= $wd ?></div>
            <?php endforeach; ?>

            <!-- Empty boxes before first day -->
            <?php for ($empty = 1; $empty < $firstDayOfMonth; $empty++): ?>
                <div class="day-box"></div>
            <?php endfor; ?>

            <!-- Days with attendance -->
            <?php
            $statusMap = [
                'Present'   => 'P',
                'Absent'    => 'A',
                'Leave'     => 'L',
                'Half Day'  => 'HD',
                'Holiday'   => 'H',
                'Week Off'  => 'WO'
            ];

            for ($d = 1; $d <= $daysInMonth; $d++):
                $status = isset($attendance[$d]) ? $attendance[$d] : "";
                $shortStatus = isset($statusMap[$status]) ? $statusMap[$status] : "-";

                $dayOfWeek = date('N', strtotime("$year-$monthNumber-$d")); // 6=Sat,7=Sun
                $extraClass = ($dayOfWeek >= 6) ? 'weekend' : '';
            ?>
                <div class="day-box <?= strtolower(str_replace(' ', '', $status)) . " " . $extraClass; ?>">
                    <?= $d ?><br>
                    <?= $shortStatus ?>
                </div>
            <?php endfor; ?>

        </div>

    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>