<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>


<div class="container-fluid">

    <!-- Search & Filter Section -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search Bar -->
        <form method="GET" class="mr-2">
            <input type="text" name="search" id="txt_searchall" class="form-control" placeholder="Search Vendor..." id="vendorSearch"
                value="<?php if (isset($_GET['search'])) echo $_GET['search']; ?>">
        </form>

        
    </div>

    <h1 class="h3 text-gray-800 text-center mb-4">View Vendors</h1>

    <!-- Vendor Table -->
    <div class="containers">

        <?php
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';

       $query = "SELECT v.* FROM tbl_vendor v WHERE 1 ";

        if ($type == 'active') {
    $query .= " AND v.status='active'";
} elseif ($type == 'inactive') {
    $query .= " AND v.status='inactive'";
}

if (!empty($search)) {
    $query .= " AND (
        v.vendor_name LIKE '%$search%'
        OR v.phone LIKE '%$search%'
        OR v.email LIKE '%$search%'
    )";
}

$query .= " GROUP BY v.id ORDER BY v.id DESC";

        $query_run = mysqli_query($conn, $query);
        ?>

        <table class="table table-bordered text-center" id="vendorTable">
            <thead>
                <tr>
                    <th width="5%">Sr.No.</th>
                    <th width="15%">Name</th>
                    <th width="12%">Phone</th>
                    <th width="15%">Email</th>
                    <th width="20%">Address</th>
                    <th width="10%">Balance</th>
                    <th width="12%">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $serial_no = 1;
                $total_balance = 0;
                if (mysqli_num_rows($query_run) > 0) {
                    while ($row = mysqli_fetch_assoc($query_run)) {
                        
                       $vendor_balance = 0;

                 $client_query = mysqli_query($conn,"
                    SELECT * FROM tbl_client
                    WHERE vendor_id='{$row['id']}'
                    AND added_by='vendor'
                    ");

                 while($client = mysqli_fetch_assoc($client_query))
                 {
                    $amount = floatval($client['total_amount']);

                    if($client['apply_gst'] == 1)
                    {
                        if($client['gst_type'] == 'inclusive')
                        {
                            $grand_total = $amount;
                        }
                        else
                        {
                            $grand_total = $amount + ($amount * 0.18);
                        }
                    }
                    else
                    {
                        $grand_total = $amount;
                    }

                    $payment = mysqli_fetch_assoc(mysqli_query($conn,"
                        SELECT IFNULL(SUM(paid_amount),0) AS paid
                        FROM tbl_payment
                        WHERE client_id='{$client['id']}'
                        "));

                    $paid = floatval($payment['paid']);

                    $vendor_balance += ($grand_total - $paid);
                }

                $total_balance += $vendor_balance;
                ?>
                        <tr>
                            <td><?= $serial_no++; ?></td>
                            <td><?= $row['vendor_name']; ?></td>
                            <td><?= $row['phone']; ?></td>
                            <td><?= $row['email']; ?></td>
                            <td><?= $row['address']; ?></td>
                            <td>₹ <?= number_format($vendor_balance, 2); ?></td>
                            <td>
                                <div class="d-flex flex-column align-items-center">
                                    <!-- Add Client Page -->
                                    <button onclick="window.location.href='add-vendor-client.php?vendor_id=<?= $row['id']; ?>'"
                                        class="btn btn-success btn-sm mb-3" style="width: fit-content;">
                                        Add Client
                                    </button>
                                
                                    <!-- View Vendor Details Button -->
                                    <button onclick="window.location.href='view-vendor-client.php?id=<?= $row['id']; ?>'"
                                        class="btn btn-sm btn-primary" style="width: fit-content;">
                                        View Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>No Vendors Found</td></tr>";
                }
                ?>
            </tbody>
            
            <tfoot>
    <tr>
        <th colspan="5" class="text-end">Total Balance</th>
        <th>₹ <?= number_format($total_balance, 2); ?></th>
        <th></th>
    </tr>
</tfoot>
        </table>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<?php
include('includes/scripts.php');
include('includes/footer.php');
?>