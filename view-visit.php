<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search Bar -->
        <form method="GET" class="mr-2">
            <input type="text" name="search" class="form-control" placeholder="Search Inquiry..."
                value="<?php if (isset($_GET['search']))
                    echo $_GET['search']; ?>">
        </form>
    </div>


    <h1 class="h3 text-gray-800 text-center mb-4">View Visit</h1>
    <!-- Inquiry Table -->
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">

                <?php
                if (isset($_GET['search'])) {
                    $search = $_GET['search'];
                    $query = "SELECT * FROM tbl_inquiry 
                              WHERE name LIKE '%$search%' 
                              OR phone LIKE '%$search%'
                              OR email LIKE '%$search%'
                              ORDER BY id DESC";
                } else {
                    $query = "SELECT * FROM tbl_inquiry WHERE visit_date IS NOT NULL AND service_date IS  NULL  ORDER BY `Id`  DESC";
                }

                $query_run = mysqli_query($conn, $query);
                ?>

                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th width="5%">Sr.No</th>
                            <th width="10%">Name</th>
                            <th width="10%">Phone</th>
                            <th width="10%">Email</th>
                            <th width="15%">Address</th>
                            <th width="20%">Message</th>
                            <th width="15%">Visit Date</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $serial_no = 1;
                        if (mysqli_num_rows($query_run) > 0) {
                            while ($row = mysqli_fetch_assoc($query_run)) {
                                ?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><?= $row['name']; ?></td>
                                    <td><?= $row['phone']; ?></td>
                                    <td><?= $row['email']; ?></td>
                                    <td><?= $row['address']; ?></td>
                                    <td><?= $row['message']; ?></td>
                                    <td><?= $row['visit_date']; ?></td>

                                    <td>
                                        <button class="actionBtn btn btn-sm btn-primary">Action</button>

                                        <div class="actionBox mt-2" style="display: none;">
                                            <a href="update-inquiry.php?id=<?= $row['id']; ?>&type=refollowup"
                                                class="btn btn-warning btn-sm btn-block">Re-Followup</a>

                                                <a href="update-inquiry.php?id=<?= $row['id']; ?>&type=visit"
                                                    class="btn btn-info btn-sm btn-block">Visit</a>

                                                <a href="update-inquiry.php?id=<?= $row['id']; ?>&type=service"
                                                    class="btn btn-success btn-sm btn-block">Service</a>

                                                <a href="update-inquiry.php?id=<?= $row['id']; ?>&type=schedule"
                                                    class="btn btn-dark btn-sm btn-block">Schedule</a>
                                                    
                                                    <!-- DELETE BUTTON -->
                                                            <a href="delete-inquiry.php?id=<?= $row['id']; ?>"
                                                                class="btn btn-danger btn-sm btn-block mt-2"
                                                                onclick="return confirm('Are you sure you want to delete this inquiry?');">
                                                                Delete
                                                            </a>

                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='8'>No Inquiry Found</td></tr>";
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