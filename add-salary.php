<?php
session_start();
include('authentication.php');
include('dbcon.php');

/* ================= DEFAULTS ================= */
$employee_id      = $_POST['employee_id'] ?? '';
$month            = $_POST['month'] ?? '';
$remark           = $_POST['remark'] ?? '';
$profession_tax   = $_POST['profession_tax'] ?? 0;
$reimbursement    = $_POST['reimbursement'] ?? 0;

$advance          = 0;
$current_present  = 0;
$employee_package = 0;
$per_day_amount   = 0;
$earned           = 0;
$final_salary     = 0;
$employee_type    = '';

/* ================= FETCH EMPLOYEES ================= */
$employees = mysqli_query($conn,
    "SELECT Id,name,package,employee_type FROM tbl_employee"
);

/* ================= FETCH EMPLOYEE DETAILS ================= */
if ($employee_id) {

    $emp = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT package,employee_type
        FROM tbl_employee
        WHERE Id='$employee_id'
    "));

    $employee_package = $emp['package'] ?? 0;

    $employee_type = strtolower(
        str_replace([' ', '-'], '', $emp['employee_type'])
    );

    $per_day_amount =
        ($employee_type === 'perday')
        ? $employee_package
        : $employee_package / 26;
}

/* ================= FETCH ATTENDANCE ================= */
if ($employee_id && $month) {

    $att = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT SUM(
            CASE
                WHEN status='Present' THEN 1
                WHEN status='Half Day' THEN 0.5
                ELSE 0
            END
        ) AS total_days
        FROM tbl_attendance
        WHERE employee_id='$employee_id'
        AND attendance_date BETWEEN '{$month}-01'
        AND LAST_DAY('{$month}-01')
    "));

    $current_present = $att['total_days'] ?? 0;

    /* ===== FETCH PENDING ADVANCE ===== */
    $advData = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            SUM(CASE WHEN payment_type='Advance'
                THEN COALESCE(advance,0) ELSE 0 END) total_advance,

            SUM(CASE WHEN payment_type IN ('Salary','PerDay')
                THEN COALESCE(advance,0) ELSE 0 END) total_deducted

        FROM tbl_salary
        WHERE employee_id='$employee_id'
    "));

    $advance =
        ($advData['total_advance'] ?? 0)
        - ($advData['total_deducted'] ?? 0);

    if ($advance < 0) $advance = 0;
}

/* ================= CALCULATIONS ================= */
$earned       = $per_day_amount * $current_present;
$final_salary = $earned - $advance - $profession_tax + $reimbursement;

/* ================= SAVE SALARY ================= */
if (isset($_POST['save_salary']) && $employee_id && $month) {

    $input_advance  = $_POST['advance'] ?? 0;
    $profession_tax = $_POST['profession_tax'] ?? 0;
    $reimbursement  = $_POST['reimbursement'] ?? 0;

    // deduct only available pending advance
    $advance_to_deduct = min($advance, $input_advance);

    mysqli_query($conn, "
        INSERT INTO tbl_salary
        (employee_id,month,payment_type,
         present_days,basic,per_day_amount,
         profession_tax,reimbursement,
         amount,advance,remark)
        VALUES
        ('$employee_id','$month','Salary',
         '$current_present','$employee_package',
         '$per_day_amount','$profession_tax',
         '$reimbursement','$earned',
         '$advance_to_deduct','$remark')
    ");

    $_SESSION['status'] = "Salary saved successfully";
    header("Location:add-salary.php");
    exit();
}

include('includes/header.php');
include('includes/navbar.php');
include('message.php');
?>

<div class="container-fluid">
<div class="inquiry-container">
<h3 class="text-center">Add Salary</h3>

<form method="POST">

<label>Employee</label>
<select name="employee_id" onchange="this.form.submit()" required>
<option value="">-- Select --</option>
<?php while ($e = mysqli_fetch_assoc($employees)) { ?>
<option value="<?= $e['Id'] ?>"
<?= ($employee_id == $e['Id']) ? 'selected' : '' ?>>
<?= $e['name'] ?> (Salary: <?= $e['package'] ?>)
</option>
<?php } ?>
</select>

<label class="mt-2">Month</label>
<input type="month" name="month"
value="<?= $month ?>" onchange="this.form.submit()" required>

<label class="mt-2">Working Days</label>
<input value="<?= $current_present ?>" readonly>

<label class="mt-2">Per Day Amount</label>
<input value="<?= round($per_day_amount,2) ?>" readonly>

<label class="mt-2">Earned Salary</label>
<input id="earned" value="<?= round($earned,2) ?>" readonly>

<div class="row mt-3">

<div class="col">
<label>Advance Deduction</label>
<input type="number" name="advance"
id="advance"
value="<?= $advance ?>"
min="0" max="<?= $advance ?>"
oninput="calcSalary()">
</div>

<div class="col">
<label>Profession Tax</label>
<input type="number" name="profession_tax"
id="profession_tax"
value="<?= $profession_tax ?>"
oninput="calcSalary()">
</div>

<div class="col">
<label>Reimbursement</label>
<input type="number" name="reimbursement"
id="reimbursement"
value="<?= $reimbursement ?>"
oninput="calcSalary()">
</div>

</div>

<label class="mt-3">Net Salary</label>
<input id="net_salary"
value="<?= round($final_salary,2) ?>" readonly>

<label class="mt-2">Remark</label>
<input type="text" name="remark" value="<?= $remark ?>">

<button type="submit" name="save_salary"
class="btn btn-primary mt-3">
Save Salary
</button>

</form>
</div>
</div>

<script>
function calcSalary() {
    let earned =
        parseFloat(document.getElementById('earned').value) || 0;
    let adv =
        parseFloat(document.getElementById('advance').value) || 0;
    let tax =
        parseFloat(document.getElementById('profession_tax').value) || 0;
    let reimb =
        parseFloat(document.getElementById('reimbursement').value) || 0;

    let net = earned - adv - tax + reimb;

    document.getElementById('net_salary').value =
        net.toFixed(2);
}
</script>

<?php
include('includes/footer.php');
include('includes/scripts.php');
?>