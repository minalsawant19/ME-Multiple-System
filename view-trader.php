<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">

    <!-- Search Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">

        <form method="GET" class="mr-2 mb-2">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="Search Trader..."
                   value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
        </form>

    </div>

    <h1 class="h3 text-gray-800 text-center mb-4">
        View Traders
    </h1>

    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">

                <?php

                $search = trim($_GET['search'] ?? '');

               $stmt = mysqli_prepare($conn, "
    SELECT c.*

    FROM tbl_client c

    WHERE c.added_by = 'trader'

    AND (
        c.cust_name LIKE ?
        OR c.cust_phone LIKE ?
        OR c.cust_email LIKE ?
    )

    ORDER BY c.id DESC
");

$searchTerm = "%" . $search . "%";

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $searchTerm,
    $searchTerm,
    $searchTerm
);

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

                ?>

                <table class="table table-bordered text-center">

                    <thead>
                        <tr>
                            <th width="5%">Sr.No.</th>
                            <th width="12%">Name</th>
                            <th width="10%">Phone</th>
                            <th width="12%">Email</th>
                            <th width="15%">Address</th>
                            <th width="10%">Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php

                    $serial_no = 1;

                    if ($query_run && mysqli_num_rows($query_run) > 0) {

                        while ($row = mysqli_fetch_assoc($query_run)) {

                    ?>

                        <tr>

                            <td><?= $serial_no++; ?></td>

                            <td>
                                <?= htmlspecialchars($row['cust_name']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['cust_phone']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['cust_email']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['cust_address']); ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($row['service_date']); ?>
                            </td>

                            <td>

                                <!-- View Details Button -->
                                <button
                                    onclick="window.location.href='view-trader-details.php?client_id=<?= (int)$row['id']; ?>'"
                                    class="actionBtn btn btn-sm btn-primary mb-2">

                                    <i class="fas fa-eye"></i>
                                    View Details

                                </button>

                                <!-- Generate Invoice Button -->
                                <!--<button-->
                                <!--    type="button"-->
                                <!--    class="btn btn-dark btn-sm mb-2"-->
                                <!--    style="width:fit-content"-->

                                <!--    onclick="window.location.href='add-invoice.php?client_id=<?= (int)$row['id']; ?>'">-->

                                <!--    <i class="bi bi-printer"></i>-->
                                <!--    Generate Invoice-->

                                <!--</button>-->

                            </td>

                        </tr>

                    <?php

                        }

                    } else {

                        echo "<tr>
                            <td colspan='9'>No Traders Found</td>
                        </tr>";

                    }

                    ?>

                    </tbody>

                </table>

                <?php mysqli_stmt_close($stmt); ?>

            </div>
        </div>
    </div>

</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
