<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

// When form submitted
if (isset($_POST['submit'])) {
    $expense = mysqli_real_escape_string($conn, $_POST['expense']);
    $expense_date = $_POST['expense_date'];
    $amount = $_POST['amount'];
    $paid_by = mysqli_real_escape_string($conn, $_POST['paid_by']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);

    $query = "INSERT INTO tbl_expenses (expense, expense_date, amount, paid_by, service)
              VALUES ('$expense', '$expense_date', '$amount', '$paid_by', '$service')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['status'] = "Expense Added Successfully!";
        header("Location: view-expenses.php");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="container-fluid">

    <div class="inquiry-container ">
            <h2>Add Expense</h2>

            <?php if (isset($error)) { ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php } ?>

            <form action="" method="POST">
                <label>Expense Name</label>
                <input type="text" name="expense"  required>

                <label>Expense Date</label>
                <input type="date" name="expense_date" required>
            
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required>
              
                <label>Paid By</label>
                <select name="paid_by" required>
                        <option value="">-- Select --</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Google Pay">Google Pay</option>
                        <option value="PhonePe">PhonePe</option>
                </select>

                <label>Related Service (Optional)</label>
                <input type="text" name="service" placeholder="Service Name">

                <button type="submit" name="submit" class="">Add Expense</button>
            </form>
    </div>
</div>

<?php include('includes/scripts.php'); 
include('includes/footer.php'); ?>

<?php ob_end_flush(); ?>
