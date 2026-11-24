<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">
    
     <!-- Search + Filter Buttons -->
        <form method="GET" class="w-25">
            <input type="text" name="search" id="txt_searchall" class="form-control"
                placeholder="Search Client..."
                value="<?= $_GET['search'] ?? '' ?>">
        </form>

    <!-- Date Filter Form -->
    <form method="GET" action="" class="mb-4">
        <div class="row ">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary mt-5">Search</button>
            </div>
        </div>
    </form>

    <h1 class="h3 text-gray-800 text-center mb-4">Payment History</h1>

    <div class="containers" >
        <div class="card-body" >
            <div class="table-responsive">

                <?php
                // Get the start and end date from the GET request
                $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

                // Initial query without date filtering
                $query = "
                    SELECT 
                        c.cust_name,
                        c.cust_phone,
                        p.paid_amount,
                        p.payment_date,
                        p.payment_gateway,
                        p.id AS payment_id,
                        c.id AS client_id
                    FROM tbl_payment p
                    INNER JOIN tbl_client c ON c.Id = p.client_Id
                    WHERE p.paid_amount > 0
                ";

                // If both start and end date are provided, add the date filter to the query
                if (!empty($start_date) && !empty($end_date)) {
                    $query .= " WHERE p.payment_date BETWEEN '$start_date' AND '$end_date' ";
                }

                // Ordering by payment date
                $query .= " ORDER BY p.payment_date DESC";

                $query_run = mysqli_query($conn, $query);

                // Calculate total paid amount
                $total_paid_amount = 0;
                if (mysqli_num_rows($query_run) > 0) {
                    while ($row = mysqli_fetch_assoc($query_run)) {
                        $total_paid_amount += $row['paid_amount'];
                    }
                }
                ?>

                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Paid Amount</th>
                            <th>Payment Date</th>
                            <th>Payment Gateway</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$serial_no = 1;
mysqli_data_seek($query_run, 0); // reset pointer
if (mysqli_num_rows($query_run) > 0) {
    $rows_shown = 0;
    while ($row = mysqli_fetch_assoc($query_run)) {
        // Skip rows with NULL paid_amount
        if (!isset($row['paid_amount'])) {
            continue;
        }
        $rows_shown++;
?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><?= $row['cust_name']; ?></td>
                                    <td><?= $row['cust_phone']; ?></td>
                                    <td><?= number_format($row['paid_amount'], 2); ?></td>
                                    <td><?= $row['payment_date']; ?></td>
                                    <td><?= $row['payment_gateway']; ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='5'>No Payments Found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Total Paid Amount at the bottom -->
                    
                    <div class="">
                        <strong>Total Paid Amount:</strong> <?= number_format($total_paid_amount, 2); ?>
                    </div>
              
            </div>
        </div>
    </div>

</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
