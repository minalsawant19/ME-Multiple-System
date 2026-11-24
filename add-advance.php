<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id  = $_POST['employee_id'];
    $advance      = $_POST['advance'];
    $advance_date = $_POST['advance_date'];
    $remark       = $_POST['remark'];

    // Month auto from advance date
    $month = date('Y-m', strtotime($advance_date));

    // ✅ ALWAYS INSERT — NO CHECK, NO UPDATE
    $query = "INSERT INTO tbl_salary 
        (employee_id, month, advance, advance_date, remark, payment_type) 
        VALUES 
        ('$employee_id', '$month', '$advance', '$advance_date', '$remark', 'Advance')";

    if (mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success'>
                        Advance added successfully close
                    </div>";
    } else {
        $message = "<div class='alert alert-danger'>
                        Error: " . mysqli_error($conn) . "
                    </div>";
    }
}
?>

<div class="container-fluid">
 <div class="inquiry-container" >
    <h2>Add / Update Employee Advance</h2>
    <?= $message; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label>Employee</label>
            <select name="employee_id" class="form-control" required>
                <option value="">Select Employee</option>
                <?php
                $employee = mysqli_query($conn, "SELECT id, name FROM tbl_employee");
                while ($emp = mysqli_fetch_assoc($employee)) {
                    echo "<option value='{$emp['id']}'>{$emp['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Advance Amount</label>
            <input type="number" name="advance" step="0.01" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Advance Date</label>
            <input type="date" name="advance_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Remark (Optional)</label>
            <textarea name="remark" class="form-control"></textarea>
        </div>

        <button type="submit"   >Save Advance</button>
    </form>
</div>
</div>

<?php
include('includes/footer.php');
include('includes/scripts.php');
?>