<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$employee_id = $_GET['employee_id'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

if ($employee_id == '') {
    echo "<div class='alert alert-danger text-center'>Employee not selected</div>";
    exit;
}

/* Employee Info */
$emp = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT name FROM tbl_employee WHERE id='$employee_id'"
));

$month_start = $month . "-01";
$month_end = date("Y-m-t", strtotime($month_start));

/* 🔹 Previous months total advances */
$prev_adv = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT IFNULL(SUM(advance),0) AS total_adv
    FROM tbl_salary
    WHERE employee_id='$employee_id'
      AND payment_type='Advance'
      AND advance_date < '$month_start'
"))['total_adv'];

/* 🔹 Previous months deductions */
/* 🔹 Previous months deductions (Correct YEAR/MONTH logic) */
$prev_ded = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT IFNULL(SUM(advance),0) AS total_ded
    FROM tbl_salary
    WHERE employee_id='$employee_id'
      AND payment_type='Salary'
      AND advance > 0
      AND (
            YEAR(CONCAT(month,'-01')) < YEAR('$month_start')
         OR (
                YEAR(CONCAT(month,'-01')) = YEAR('$month_start')
            AND MONTH(CONCAT(month,'-01')) < MONTH('$month_start')
         )
      )
"))['total_ded'];

$carry_forward = $prev_adv - $prev_ded;

/* 🔹 Current month advances (MULTIPLE ENTRIES) */
$advances = mysqli_query($conn, "
    SELECT advance_date, advance, remark
    FROM tbl_salary
    WHERE employee_id='$employee_id'
      AND payment_type='Advance'
      AND advance_date BETWEEN '$month_start' AND '$month_end'
    ORDER BY advance_date ASC
");

/* 🔹 Current month deductions */
$deductions = mysqli_query($conn, "
    SELECT month, advance
    FROM tbl_salary
    WHERE employee_id='$employee_id'
      AND payment_type='Salary'
      AND advance > 0
      AND YEAR(CONCAT(month,'-01')) = YEAR('$month_start')
      AND MONTH(CONCAT(month,'-01')) = MONTH('$month_start')

");

/* 🔹 Totals */
$total_adv_current = 0;
while ($a = mysqli_fetch_assoc($advances)) {
    $total_adv_current += (float)$a['advance'];
}
mysqli_data_seek($advances, 0);

$total_ded_current = 0;
while ($d = mysqli_fetch_assoc($deductions)) {
    $total_ded_current += (float)$d['advance'];
}
mysqli_data_seek($deductions, 0);

$total_with_carry = $carry_forward + $total_adv_current;
$pending = $total_with_carry - $total_ded_current;
?>

<div class="container-fluid">
    <a href="view-advance.php" class="btn btn-secondary btn-sm mb-2">← Back</a>

    <div class="card shadow p-4 mb-5">
        <h4 class="text-center mb-3">
            Advance Details – <?= strtoupper($emp['name']) ?>
        </h4>

        <!-- FILTER -->
        <form method="get" class="row mb-3">
            <input type="hidden" name="employee_id" value="<?= $employee_id ?>">
            <div class="col-md-4">
                
                <input type="month" name="month" class="form-control" value="<?= $month ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>

        <!-- Carry Forward -->
        <div class="alert alert-info">
            <strong>Carry Forward:</strong> ₹<?= number_format($carry_forward,2) ?>
        </div>

        <!-- Advances -->
        <h5>Advance Taken (<?= date('F Y', strtotime($month)) ?>)</h5>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($advances) > 0): ?>
                    <?php while ($a = mysqli_fetch_assoc($advances)): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($a['advance_date'])) ?></td>
                            <td>₹<?= number_format($a['advance'],2) ?></td>
                            <td><?= $a['remark'] ?: '-' ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No advance this month</td></tr>
                <?php endif; ?>
                <tr class="fw-bold">
                    <td>Total (This Month)</td>
                    <td colspan="2">₹<?= number_format($total_adv_current,2) ?></td>
                </tr>
                <tr class="fw-bold table-light">
                    <td>Total Including Carry</td>
                    <td colspan="2">₹<?= number_format($total_with_carry,2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions -->
        <h5>Deductions</h5>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Deducted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($deductions) > 0): ?>
                    <?php while ($d = mysqli_fetch_assoc($deductions)): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($d['month'])) ?></td>
                            <td>₹<?= number_format($d['advance'],2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">No deduction</td></tr>
                <?php endif; ?>
                <tr class="fw-bold">
                    <td>Total Deduction</td>
                    <td>₹<?= number_format($total_ded_current,2) ?></td>
                </tr>
            </tbody>
        </table>

        <h4 class="text-end" style="color:<?= $pending > 0 ? 'red' : 'green' ?>">
            Pending Advance: ₹<?= number_format($pending,2) ?>
        </h4>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>