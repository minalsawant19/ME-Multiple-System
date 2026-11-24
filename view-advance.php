<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

// Employee filter
$employee_name = $_GET['employee_name'] ?? '';

// Build query with optional employee name filter
$where = "WHERE 1"; // default condition
if(!empty($employee_name)){
    $name_safe = mysqli_real_escape_string($conn, $employee_name);
    $where .= " AND e.name LIKE '%$name_safe%'";
}

// Fetch employees with advance info
$employeeAdvance = mysqli_query($conn, "
    SELECT 
        e.id AS employee_id,
        e.name,
        IFNULL(SUM(CASE WHEN s.payment_type='Advance' THEN s.advance ELSE 0 END),0) AS total_advance,
        IFNULL(SUM(CASE WHEN s.payment_type='Salary' THEN s.advance ELSE 0 END),0) AS total_deducted
    FROM tbl_employee e
    LEFT JOIN tbl_salary s ON s.employee_id = e.id
    $where
    GROUP BY e.id, e.name
    HAVING (total_advance - total_deducted) > 0
    ORDER BY e.name
");
?>

<div class="container-fluid">
    
    <!-- Employee Search -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label"><strong>Search Employee</strong></label>
            <input type="text" name="employee_name" value="" placeholder="Enter employee name" class="form-control">
        </div>
        <div class="col-md-2 d-flex align-items-end mb-3">
            <button class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <h3 class="text-center mb-4">Employee Pending Advance</h3>
    <div class="containers">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Pending Advance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($employeeAdvance) > 0) { ?>
                    <?php while ($emp = mysqli_fetch_assoc($employeeAdvance)) { 
                        $pending = $emp['total_advance'] - $emp['total_deducted'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['name']) ?></td>
                            <td>₹<?= number_format($pending,2) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" style="width: fit-content;"
                                    onclick="window.location.href='view-advance-detail.php?employee_id=<?= $emp['employee_id'] ?>'">
                                    Details
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3">No advance records found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>