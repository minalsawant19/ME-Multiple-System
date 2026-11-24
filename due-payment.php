<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">
    
   
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

    <h1 class="h3 text-gray-800 text-center mb-4">Due Payments</h1>

    <div class="containers">

        <?php
        // Get filter values from GET request
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

       
/* ================= NORMAL CLIENT QUERY ================= */

$normalQuery = "
    SELECT
        c.Id AS client_id,

        CASE
            WHEN c.vendor_id IS NOT NULL
            THEN CONCAT(
                '<span class=\"badge bg-warning text-dark\">',
                v.vendor_name,
                '</span> ',
                c.cust_name
            )
            ELSE c.cust_name
        END AS display_name,

        c.vendor_id,
        c.cust_phone,
        c.total_amount,

        IFNULL(SUM(p.paid_amount), 0) AS total_paid,

        (
            p.total_amount - IFNULL(SUM(p.paid_amount), 0)
        ) AS due_amount,

        MAX(p.due_date) AS due_date,

        NULL AS group_id,
        'normal' AS payment_type

    FROM tbl_client c

    LEFT JOIN tbl_vendor v ON c.vendor_id = v.id

    LEFT JOIN tbl_payment p ON c.Id = p.client_Id

    WHERE LOWER(TRIM(c.added_by)) != 'trader'
";

if ($start_date) {
    $normalQuery .= " AND p.payment_date >= '$start_date'";
}

if ($end_date) {
    $normalQuery .= " AND p.payment_date <= '$end_date'";
}

$normalQuery .= "
    GROUP BY c.Id, c.cust_name, c.cust_phone, c.total_amount
    HAVING due_amount > 0
";




/* ================= TRADER QUERY ================= */
$traderQuery = "
    SELECT
        c.Id AS client_id,

        CASE
            WHEN c.vendor_id IS NOT NULL
            THEN CONCAT(
                '<span class=\"badge bg-warning text-dark\">',
                v.vendor_name,
                '</span> ',
                c.cust_name
            )
            ELSE c.cust_name
        END AS display_name,

        c.vendor_id,
        c.cust_phone,

        MAX(main_payment.total_amount) AS total_amount,

        COALESCE(SUM(history.paid_amount), 0) AS total_paid,

        MAX(main_payment.total_amount)
        - COALESCE(SUM(history.paid_amount), 0) AS due_amount,

        MAX(main_payment.due_date) AS due_date,

        main_payment.group_id AS group_id,

        'trader' AS payment_type

    FROM tbl_client c

    LEFT JOIN tbl_vendor v
        ON c.vendor_id = v.id

    INNER JOIN tbl_payment main_payment
        ON main_payment.client_id = c.Id

    LEFT JOIN tbl_payment history
        ON history.client_id = main_payment.client_id
        AND history.group_id = main_payment.group_id
        AND history.payment_date IS NOT NULL

    WHERE LOWER(TRIM(c.added_by)) = 'trader'

    AND main_payment.group_id IS NOT NULL

    AND main_payment.payment_date IS NULL
";

if ($start_date) {
    $traderQuery .= "
        AND main_payment.due_date >= '$start_date'
    ";
}

if ($end_date) {
    $traderQuery .= "
        AND main_payment.due_date <= '$end_date'
    ";
}

$traderQuery .= "
    GROUP BY
        c.Id,
        c.cust_name,
        c.cust_phone,
        c.vendor_id,
        main_payment.group_id

    HAVING due_amount > 0
";


/* ================= UNION BOTH ================= */

$query = "
    $normalQuery

    UNION ALL

    $traderQuery

    ORDER BY due_date DESC
";

$query_run = mysqli_query($conn, $query);

if (!$query_run) {
    die(mysqli_error($conn));
}

$total_due_amount = 0;

        ?>

        <table class="table table-bordered text-center">
            <thead class="">
                <tr>
                    <th>Sr. No.</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <!--<th>Total Amount</th>-->
                    <th>Total Paid</th>
                    <th>Due Amount</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serial_no = 1;
                if (mysqli_num_rows($query_run) > 0) {
                    while ($row = mysqli_fetch_assoc($query_run)) {
                        // Add the due amount to the total
                        $total_due_amount += $row['due_amount'];
                ?>
                        <tr>
                            <td><?= $serial_no++; ?></td>
                            <td>
                            <a href="view-client-detail.php?client_id=<?= $row['client_id']; ?>">
                                    <?= $row['display_name']; ?>
                                </a>
                        </td>
                           
                            <td><?= $row['cust_phone']; ?></td>
                            <!--<td><?= $row['total_amount']; ?></td>-->
                            <td><?= $row['total_paid']; ?></td>
                            <td><?= $row['due_amount']; ?></td>
                            <td><?= $row['due_date']; ?></td>
                            <td>
                            <?php if ($row['payment_type'] === 'trader') { ?>

    <button
        onclick="window.location.href='update-payment.php?id=<?= (int)$row['client_id']; ?>&group_id=<?= (int)$row['group_id']; ?>'"
        class="btn btn-sm btn-dark">
        Update
    </button>

<?php } else { ?>

    <button
        onclick="window.location.href='update-payment.php?id=<?= (int)$row['client_id']; ?>'"
        class="btn btn-sm btn-dark">
        Update
    </button>

<?php } ?>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>No Due Payments Found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <!-- Total Due Amount at the bottom -->
        <div class="">
            <strong>Total Due Amount:</strong> <?= number_format($total_due_amount, 2); ?>
        </div>
    </div>

</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>