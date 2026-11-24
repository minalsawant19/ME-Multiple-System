<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

/* ================== FUNCTION TO CONVERT NUMBER TO WORDS ================== */
function numberToWords($number)
{
    $words = [
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety'
    ];

    if ($number == 0) return 'Zero';
    $result = '';

    if ($number >= 100000) {
        $result .= numberToWords(intval($number / 100000)) . ' Lakh ';
        $number %= 100000;
    }
    if ($number >= 1000) {
        $result .= numberToWords(intval($number / 1000)) . ' Thousand ';
        $number %= 1000;
    }
    if ($number >= 100) {
        $result .= numberToWords(intval($number / 100)) . ' Hundred ';
        $number %= 100;
    }
    if ($number > 0) {
        if ($number < 20) {
            $result .= $words[$number];
        } else {
            $result .= $words[intval($number / 10) * 10];
            if ($number % 10) $result .= ' ' . $words[$number % 10];
        }
    }
    return trim($result);
}

/* ================== INPUTS ================== */
$employee_id = $_GET['employee_id'] ?? '';
$month       = $_GET['month'] ?? '';
$show_salary = isset($_GET['view_salary']);

if (!$employee_id) {
    echo "<div class='alert alert-danger text-center'>Employee not selected</div>";
    include('includes/footer.php');
    exit;
}

/* ================== FETCH EMPLOYEE ================== */
$emp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, package, employee_type FROM tbl_employee WHERE id='$employee_id'"));
if (!$emp) {
    echo "<div class='alert alert-danger text-center'>Employee not found</div>";
    include('includes/footer.php');
    exit;
}

$emp_type = strtolower(str_replace([' ', '-'], '', $emp['employee_type']));

/* ================== DEFAULTS ================== */
$salary = null;
$current_present = 0;
$per_day = 0;
$earned_salary = 0;
$net_salary = 0;
$advance = 0;
$profession_tax = 0;
$reimbursement = 0;
$basic_salary = $emp['package'] ?? 0;

if ($show_salary && $month) {

    /* ==== UPDATED QUERY (FIX DOUBLE SALARY ISSUE) ==== */
    $salary = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            MAX(amount) AS earned_salary,
            SUM(advance) AS advance,
            SUM(profession_tax) AS profession_tax,
            SUM(reimbursement) AS reimbursement,
            MAX(per_day_amount) AS per_day_amount,
            MAX(basic) AS basic
        FROM tbl_salary
        WHERE employee_id='$employee_id'
        AND month='$month'
        AND payment_type='Salary'
    "));

    // Fetch attendance
    $attendance = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT SUM(
            CASE 
                WHEN status='Present' THEN 1 
                WHEN status='Half Day' THEN 0.5 
                ELSE 0 
            END
        ) AS total_days
        FROM tbl_attendance
        WHERE employee_id='$employee_id'
          AND attendance_date BETWEEN '{$month}-01' AND LAST_DAY('{$month}-01')
    "));
    $current_present = $attendance['total_days'] ?? 0;

    if ($salary && $salary['earned_salary'] !== null) {

        $advance = $salary['advance'] ?? 0;
        $profession_tax = $salary['profession_tax'] ?? 0;
        $reimbursement = $salary['reimbursement'] ?? 0;

        if ($emp_type === 'perday') {
            $per_day = $salary['per_day_amount'];
        } else {
            $per_day = ($salary['basic'] ?? 0) / 26;
        }

        // Earned salary taken once (not summed)
        $earned_salary = $salary['earned_salary'];

        // Net salary
        $net_salary = $earned_salary - $advance - $profession_tax + $reimbursement;
    }
}

$month_label = date('F Y', strtotime($month . '-01'));
?>

<style>
    strong {
        color: black;
        font-weight: 600;
    }
</style>

<div class="container-fluid">

    <div class="card shadow p-4 mb-4 text-center">
        <h3 class="mb-4 text-uppercase">
            Salary Details For <?= strtoupper($emp['name']) ?>
        </h3>

        <form method="GET" class="row justify-content-center align-items-center g-3">
            <input type="hidden" name="employee_id" value="<?= $employee_id ?>">
            <div class="col-auto mb-3"><strong>Select Month :</strong></div>
            <div class="col-auto">
                <input type="month" name="month" class="form-control" value="<?= $month ?>" required>
            </div>
            <div class="col-auto mb-3">
                <button name="view_salary" class="btn btn-primary px-4">View Salary</button>
            </div>
            <div class="col-auto mb-3">
                <button type="button" class="btn btn-success px-4"
                    onclick="window.location.href='view-advance.php?employee_id=<?= $employee_id ?>'">
                    💰 View Advance
                </button>
            </div>
        </form>
    </div>

    <?php if ($show_salary) { ?>
        <?php if (!$salary || $salary['earned_salary'] === null) { ?>
            <div class="text-center text-danger fw-semibold">
                No salary records found for the selected month.
            </div>
        <?php } else { ?>
            <div class="card shadow p-4 mb-4">
                <div class="row text-center mb-3">
                    <div class="col-md-3"><strong>Month</strong><br><?= $month_label ?></div>
                    <div class="col-md-3"><strong>Per Day Amount</strong><br>₹<?= number_format($per_day, 2) ?></div>
                    <div class="col-md-3"><strong>Working Days</strong><br><?= $current_present ?></div>
                    <div class="col-md-2"><strong>Net Salary</strong><br>₹<?= number_format($net_salary, 2) ?></div>
                </div>

                <table class="table table-bordered text-center">
                    <tr>
                        <th>Basic Salary</th>
                        <td>₹<?= number_format($salary['basic'], 2) ?></td>
                    </tr>
                    <tr>
                        <th>Earned Salary</th>
                        <td>₹<?= number_format($earned_salary, 2) ?></td>
                    </tr>
                    <tr>
                        <th>Advance</th>
                        <td>₹<?= number_format($advance, 2) ?></td>
                    </tr>
                    <tr>
                        <th>Profession Tax</th>
                        <td>₹<?= number_format($profession_tax, 2) ?></td>
                    </tr>
                    <tr>
                        <th>Reimbursement</th>
                        <td>₹<?= number_format($reimbursement, 2) ?></td>
                    </tr>
                    <tr class="table-success fw-bold">
                        <th>Net Salary</th>
                        <th>₹<?= number_format($net_salary, 2) ?></th>
                    </tr>
                </table>

                <p class="mt-3"><strong>Amount in Words :</strong>
                    <strong class="text-uppercase"><?= numberToWords(round($net_salary)) ?> Only</strong>
                </p>

                <div class="text-right mt-2">
                    <button class="btn btn-success" style="width: fit-content;" onclick="window.location.href='send_salary_slip.php?employee_id=<?= $employee_id ?>&month=<?= $month ?>'">
                        📤 Send Salary
                    </button>
                    <button class="btn btn-primary ms-2" style="width: fit-content;"
                        onclick="window.location.href='send_salary_slip.php?employee_id=<?= $employee_id ?>&month=<?= $month ?>&download=1'">
                        ⬇️ Download Slip
                    </button>
                </div>
            </div>
        <?php } ?>
    <?php } ?>

</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>