<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

// DEFAULT QUERY
$query = "SELECT * FROM tbl_expenses WHERE 1";
// ORDER BY DATE
$query .= " ORDER BY expense_date DESC";

$expenses = mysqli_query($conn, $query);

// Calculate total expenses based on filters
$total_query = str_replace("SELECT *", "SELECT SUM(amount) AS total", $query);
$total_result = mysqli_fetch_assoc(mysqli_query($conn, $total_query));
$total_expenses = $total_result['total'] ?? 0;

?>


<!-- TABLE -->
<div class="container-fluid">
    <h2 class="h3 text-gray-800 text-center mb-4">All Expenses</h2>
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Expense</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Paid By</th>
                            <th>Service</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (mysqli_num_rows($expenses) > 0) {
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($expenses)) { ?>
                                <tr>
                                    <td><?= $i++; ?></td>
                                    <td><?= $row['expense']; ?></td>
                                    <td><?= $row['expense_date']; ?></td>
                                    <td>₹<?= $row['amount']; ?></td>
                                    <td><?= $row['paid_by']; ?></td>
                                    <td><?= $row['service']; ?></td>
                                </tr>
                        <?php }
                        } else {
                            echo "<tr><td colspan='6'>No Expenses Found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- TOTAL EXPENSES (Bottom Section) -->
            <div class="alert alert-info text-center mt-4">
                <h4>Total Expenses: <strong class="text-danger">₹<?= number_format($total_expenses, 2); ?></strong></h4>
            </div>

        </div>
    </div>
</div>

<?php include('includes/scripts.php');
include('includes/footer.php'); ?>