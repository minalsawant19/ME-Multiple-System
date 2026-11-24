<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = $_GET['client_id'] ?? 0;

// Fetch pending works
$pending_works = mysqli_query($conn, "SELECT * FROM tbl_pending_work WHERE client_id='$client_id' ORDER BY due_date ASC");
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search Bar -->
        <form method="GET" class="mr-2">
            <input data-table="inquiryTable" type="text" name="search" class="form-control" placeholder="Search Inquiry..."
                value="<?php if (isset($_GET['search']))
                            echo $_GET['search']; ?>">
        </form>
    </div>

    <h4 class="mb-4 text-center">Pending Work for Client ID: <?= $client_id; ?></h4>
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table ">
                    <thead class="">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Work Description</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serial_no=1;
                        if (mysqli_num_rows($pending_works) > 0) {
                            while ($row = mysqli_fetch_assoc($pending_works)) { ?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><?= $row['work_description']; ?></td>
                                    <td><?= $row['assigned_to']; ?></td>
                                    <td><?= $row['due_date']; ?></td>
                                    <td><?= $row['status']; ?></td>
                                    <td>
                                        <a href="update-pending-work.php?work_id=<?= $row['id']; ?>" class="btn btn-sm btn-dark">Update</a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="5">No Pending Work Found</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    include('includes/scripts.php');
    include('includes/footer.php');
    ?>