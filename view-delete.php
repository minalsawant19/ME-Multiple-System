<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');
?>

<div class="container-fluid">

    <h1 class="h3 text-gray-800 text-center mb-4">
        Deleted Inquiries
    </h1>

    <div class="card shadow mb-4">
        <div class="card-body">

            <div class="table-responsive">

                <?php
                $query = "SELECT * FROM tbl_delete ORDER BY Id DESC";
                $query_run = mysqli_query($conn, $query);
                ?>

                <table class="table table-bordered text-center">

                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Sr.No.</th>
                            <!-- <th>Services</th> -->
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Message</th>
                            <th>Inquiry Date</th>
                            <th>Followup Date</th>
                            <th>Visit Date</th>
                            <th>Service Date</th>
                            <th>Schedule Date</th>
                            <th>Action</th>
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

                                    <!-- <td><?= $row['services']; ?></td> -->

                                    <td><?= $row['cust_name']; ?></td>

                                    <td><?= $row['cust_phone']; ?></td>

                                    <td><?= $row['cust_email']; ?></td>

                                    <td><?= $row['cust_address']; ?></td>

                                    <td><?= $row['cust_message']; ?></td>

                                    <td><?= $row['inq_date']; ?></td>

                                    <td><?= $row['follow_up_date']; ?></td>

                                    <td><?= $row['visit_date']; ?></td>

                                    <td><?= $row['service_date']; ?></td>

                                    <td><?= $row['schedule_date']; ?></td>
                                    <td>
                                        <a href="delete.php?id=<?= $row['id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Permanently Delete This Record?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>

                                <?php
                            }

                        } else {

                            echo "<tr>
                            <td colspan='12'>
                            No Deleted Inquiry Found
                            </td>
                            </tr>";
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