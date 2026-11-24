<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">

    <!-- Search + Filter Buttons -->
    <div class="d-flex justify-content-between mb-3">
        <form method="GET" class="w-25">
            <input type="text" name="search" id="txt_searchall" class="form-control"
                placeholder="Search Client..."
                value="<?= $_GET['search'] ?? '' ?>">
        </form>

        <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center text-center">
            <a href="view-gst-client.php" class="btn btn-primary btn-sm mb-2 mb-sm-0 mr-sm-2">View ME GST Clients</a>
            <a href="view-client.php" class="btn btn-dark btn-sm mb-2 mb-sm-0 mr-sm-2">All Clients</a>
            <a href="view-client.php?type=me" class="btn btn-primary btn-sm mb-2 mb-sm-0 mr-sm-2">ME Non-GST Clients</a>
            <a href="view-client.php?type=vendor" class="btn btn-warning btn-sm mb-2 mb-sm-0 mr-sm-2">Vendor Clients</a>
            <a href="view-client.php?type=trader"
            class="btn btn-info btn-sm mb-2 mb-sm-0 mr-sm-2">
            Trader Clients
        </a>
        </div>



    </div>

    <?php
$type = $_GET['type'] ?? '';

if ($type === 'trader') {
    $heading = 'View Trader Clients';
} elseif ($type === 'vendor') {
    $heading = 'View Vendor Clients';
} elseif ($type === 'me') {
    $heading = 'View ME Clients';
} else {
    $heading = 'View Clients';
}
?>

<h1 class="h3 text-gray-800 text-center mb-4">
    <?= htmlspecialchars($heading); ?>
</h1>

    <div class="containers">
        <?php
        
$type   = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');

$finalQuery = "
    SELECT
        c.*,
        v.vendor_name,
        (
            SELECT p.total_amount
            FROM tbl_payment p
            WHERE p.client_id = c.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS final_amount
    FROM tbl_client c
    LEFT JOIN tbl_vendor v ON c.vendor_id = v.id
    WHERE 1=1
";

// ME Clients
if ($type === 'me') {
    $finalQuery .= " AND c.added_by = 'admin' AND c.apply_gst = 0";
}

// Vendor Clients
elseif ($type === 'vendor') {
    $finalQuery .= " AND c.added_by = 'vendor' AND c.apply_gst = 0";
}

// Trader Clients: GST aur non-GST dono
elseif ($type === 'trader') {
    $finalQuery .= " AND c.added_by = 'trader'";
}

// All Clients: existing non-GST list
elseif ($type === '') {
    $finalQuery .= " AND c.apply_gst = 0";
}

// Search
if ($search !== '') {
    $search = mysqli_real_escape_string($conn, $search);

    $finalQuery .= " AND (
        c.cust_name LIKE '%$search%' OR
        c.cust_phone LIKE '%$search%' OR
        c.cust_email LIKE '%$search%'
    )";
}

$finalQuery .= " ORDER BY c.id DESC";

$query_run = mysqli_query($conn, $finalQuery);

if (!$query_run) {
    die("Query Error: " . mysqli_error($conn));
}

        ?>

        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Service / Work</th>
                    <th>Total Amount</th>
                    <th>Added By</th>
                    <th>Vendor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serial = 1;
                if (mysqli_num_rows($query_run) > 0) {
                    while ($row = mysqli_fetch_assoc($query_run)) {
                ?>
                        <tr>
                            <td><?= $serial++; ?></td>
                            <td><?= $row['cust_name']; ?></td>
                            <td><?= $row['cust_phone']; ?></td>
                            <td><?= $row['cust_email']; ?></td>
                            <td><?= $row['cust_address']; ?></td>
                            <td><?= $row['service'] ?? 'N/A'; ?></td>
                            <td><?= $row['final_amount'] ?? 'N/A'; ?></td>
                            <td>
                                <?php if ($row['added_by'] == 'vendor') { ?>
                                    <span class="badge bg-warning text-dark">Vendor</span>
                                    <?php } elseif ($row['added_by'] == 'trader') { ?>

                            <span class="badge bg-info text-dark">
                                Trader
                            </span>
                                <?php } else { ?>
                                    <span class="badge bg-primary">ME</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?= ($row['vendor_name']) ? $row['vendor_name'] : '-'; ?>
                            </td>
                           <td>
    <?php if ($row['added_by'] == 'trader') { ?>

        <a href="view-trader-details.php?client_id=<?= $row['id']; ?>"
           class="btn btn-sm btn-info">
            View Details
        </a>

    <?php } else { ?>

        <a href="view-client-detail.php?client_id=<?= $row['id']; ?>"
           class="btn btn-sm btn-primary">
            View Details
        </a>

    <?php } ?>
</td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='10'>No Clients Found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>