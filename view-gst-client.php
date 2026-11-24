<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">

     <!-- Search + Filter Buttons -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">

    <!-- Search Bar -->
    <form method="GET" class="mr-2 mb-2">
        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search Client..."
               value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
    </form>

</div>

    <h1 class="h3 text-gray-800 text-center mb-4">View GST Clients</h1>

    <!-- Client Table -->
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">

                <?php
                if (isset($_GET['search'])) {
                    $search = $_GET['search'];
                    $query = "SELECT c.*,
            IFNULL(p.total_amount,0) AS final_amount
        FROM tbl_client c
        LEFT JOIN tbl_payment p ON p.client_id = c.id
        WHERE c.apply_gst = 1
        AND (
            c.cust_name LIKE '%$search%' OR
            c.cust_phone LIKE '%$search%' OR
            c.cust_email LIKE '%$search%'
        )
        GROUP BY c.id
        ORDER BY c.id DESC";
                } else {
                    $query = "SELECT c.*,
            IFNULL(p.total_amount,0) AS final_amount
        FROM tbl_client c
        LEFT JOIN tbl_payment p ON p.client_id = c.id
        WHERE c.apply_gst = 1
        GROUP BY c.id
        ORDER BY c.id DESC";
                }

                $query_run = mysqli_query($conn, $query);
                ?>

                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th width="5%">Sr.No.</th>
                            <th width="12%">Name</th>
                            <th width="10%">Phone</th>
                            <th width="12%">Email</th>
                            <th width="15%">Address</th>
                            <th width="10%">Service</th>
                            <th>Total Amount</th>
                            <th width="10%">Service Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $serial_no= 1;
                        if (mysqli_num_rows($query_run) > 0) {
                            while ($row = mysqli_fetch_assoc($query_run)) {
                        ?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><?= $row['cust_name']; ?></td>
                                    <td><?= $row['cust_phone']; ?></td>
                                    <td><?= $row['cust_email']; ?></td>
                                    <td><?= $row['cust_address']; ?></td>
                                    <td><?= $row['service']; ?></td>
                                    <td><?= number_format($row['final_amount'], 2); ?></td>
                                    <td><?= $row['service_date']; ?></td>
                                    <td>
                                        <!-- View Payment / Details Button -->
                                        <button onclick="window.location.href='view-client-detail.php?client_id=<?= $row['id']; ?>'" class="actionBtn btn btn-sm btn-primary mb-2"> 
                                        View Details</button>
                                        
                                         <!-- Generate Invoice -->
                                        <button type="button" class="btn btn-dark btn-sm mb-2" style="width:fit-content"
                                            onclick="window.location.href='add-invoice.php?client_id=<?= $row['id'] ?>'">
                                            <i class="bi bi-printer"></i> Generate Invoice
                                        </button>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='9'>No Clients Found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
