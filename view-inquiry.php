<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');
?>

<style>
    .quotation-dropdown {
        position: relative;
        display: block;
        width: 100%;
    }

    .quotation-btn {
        width: 100%;
        padding: 9px 12px;
        border: none;
        border-radius: 6px;
        background: linear-gradient(135deg, #5b5bd6, #3939a9);
        color: #fff;
        font-weight: 600;
        font-size: 12px;
        transition: 0.3s ease;
    }

    .quotation-btn:hover {
        background: linear-gradient(135deg, #4545b8, #292980);
        color: #fff;
        box-shadow: 0 4px 10px rgba(57, 57, 169, 0.25);
    }

    .quotation-menu {
        width: 100%;
        padding: 6px;
        margin-top: 5px !important;
        border: none;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .quotation-menu .dropdown-item {
        padding: 10px 12px;
        margin-bottom: 4px;
        border-radius: 5px;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s ease;
    }

    .quotation-menu .dropdown-item:last-child {
        margin-bottom: 0;
    }

    .quotation-menu .add-option {
        color: #198754;
    }

    .quotation-menu .add-option:hover {
        background: #e8f7ee;
    }

    .quotation-menu .fabrication-option {
        color: #0d6efd;
    }

    .quotation-menu .fabrication-option:hover {
        background: #eaf2ff;
    }
</style>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search Bar -->
        <form method="GET" class="mr-2">
            <input data-table="inquiryTable" type="text" name="search" class="form-control" placeholder="Search Inquiry..."
                value="">
        </form>
    </div>


    <h1 class="h3 text-gray-800 text-center mb-4">View Inquiries</h1>
    <!-- Inquiry Table -->
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">

                <?php
                if (isset($_GET['search'])) {
                    $search = $_GET['search'];
                    $query = "SELECT * FROM tbl_inquiry 
                              WHERE inq_date IS NOT NULL
               AND visit_date IS NULL
              AND re_followup_date IS NULL
              AND (
                  name LIKE '%$search%' 
                  OR phone LIKE '%$search%'
                  OR email LIKE '%$search%'
              )
                              ORDER BY id DESC";
                } else {
                    $query = "SELECT * FROM tbl_inquiry WHERE inq_date IS NOT NULL
              AND re_followup_date Is NULL AND visit_date IS  NULL  ORDER BY `Id`  DESC";
                }

                $query_run = mysqli_query($conn, $query);
                ?>

                <table class="table table-bordered text-center" id="inquiryTable">
                    <thead class="">
                        <tr>
                            <th width="5%">Sr.No.</th>
                            <th width="10%">Name</th>
                            <th width="10%">Phone</th>
                            <th width="10%">Email</th>
                            <th width="15%">Address</th>
                            <th width="20%">Message</th>
                            <th width="15%">Inquiry Date</th>
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
                                    <td><?= $serial_no++; ?></td> <!-- Display the serial number here -->
                                    <td><?= $row['name']; ?></td>
                                    <td><?= $row['phone']; ?></td>
                                    <td><?= $row['email']; ?></td>
                                    <td><?= $row['address']; ?></td>
                                    <td><?= $row['message']; ?></td>
                                    <td><?= $row['inq_date']; ?></td>

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
                                                    
                                                    <!-- NEW BUTTON -->
                                            <div class="dropdown mt-2">
    <button class="btn quotation-btn dropdown-toggle"
            type="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
        <i class="fas fa-file-invoice"></i> Send Quotation
    </button>

    <div class="dropdown-menu quotation-menu">
        <a class="dropdown-item add-option"
           href="add_quotation.php?inquiry_id=<?= (int)$row['id']; ?>">
            <i class="fas fa-file-circle-plus mr-2"></i>
            Add Quotation
        </a>

        <a class="dropdown-item fabrication-option"
          href="fabrication_quotation.php?inquiry_id=<?= (int)$row['id']; ?>">
            <i class="fas fa-industry mr-2"></i>
            Fabrication
        </a>
    </div>
</div>
                                            
                                             <!-- DELETE BUTTON -->
                                                            <a href="delete-inquiry.php?id=<?= $row['id']; ?>"
                                                                class="btn btn-danger btn-sm btn-block mt-2"
                                                                onclick="return confirm('Are you sure you want to delete this inquiry?');">
                                                                Delete
                                                            </a>
                                            <?php
                                    $qRes = mysqli_query(
                                        $conn,
                                        "SELECT id FROM tbl_quotation WHERE inquiry_id='{$row['id']}' LIMIT 1"
                                    );

                                    if (mysqli_num_rows($qRes) > 0) {
                                        $q = mysqli_fetch_assoc($qRes);
                                    ?>
                                        <a href="generate_quotation.php?id=<?= $q['id'] ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-info mt-2">
                                            View Quotation
                                        </a>
                                    <?php
                                    } else {
                                        echo '<span class="badge bg-warning mt-2">Quotation Not Added</span>';
                                    }
                                    ?>
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

<script>
  
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>