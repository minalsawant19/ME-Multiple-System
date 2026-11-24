<?php
ob_start();

include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = (int)($_GET['client_id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// CLIENT DETAILS
$stmt = mysqli_prepare($conn, "
    SELECT * FROM tbl_client WHERE Id = ?
");

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);

$client = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$client) {
    die("Client Not Found");
}


 // TRADING HISTORY - GROUP WISE

$trading_stmt = mysqli_prepare($conn, "
    SELECT
        g.id AS group_id,
        g.client_id,
        g.trading_date,

        COUNT(t.Id) AS total_items,

        COALESCE(
            GROUP_CONCAT(
                t.item_name
                ORDER BY t.Id ASC
                SEPARATOR ', '
            ), ''
        ) AS item_names,

        COALESCE(SUM(t.base_amount), 0) AS base_amount,
        COALESCE(SUM(t.gst_amount), 0) AS gst_amount,
        COALESCE(SUM(t.grand_total), 0) AS grand_total,

        COALESCE(p.paid_amount, 0) AS paid_amount,

        (
            COALESCE(SUM(t.grand_total), 0)
            - COALESCE(p.paid_amount, 0)
        ) AS balance

    FROM tbl_trading_groups g

    LEFT JOIN tbl_trading_history t
        ON t.group_id = g.id
        AND t.client_id = g.client_id

    LEFT JOIN (
        SELECT
            group_id,
            client_id,
            SUM(paid_amount) AS paid_amount
        FROM tbl_payment
        WHERE group_id IS NOT NULL
        GROUP BY group_id, client_id
    ) p
        ON p.group_id = g.id
        AND p.client_id = g.client_id

    WHERE g.client_id = ?

    GROUP BY
        g.id,
        g.client_id,
        g.trading_date,
        p.paid_amount

    ORDER BY g.trading_date DESC, g.id DESC
");

mysqli_stmt_bind_param($trading_stmt, "i", $client_id);
mysqli_stmt_execute($trading_stmt);

$trading_history = mysqli_stmt_get_result($trading_stmt);

// TOTAL SUMMARY
$total_stmt = mysqli_prepare($conn, "
    SELECT
        COALESCE(SUM(t.grand_total), 0) AS total_trading,
        COALESCE(SUM(COALESCE(p.paid_amount, 0)), 0) AS total_paid

    FROM tbl_trading_history t

    LEFT JOIN (
        SELECT
            trading_history_id,
            SUM(COALESCE(paid_amount, 0)) AS paid_amount
        FROM tbl_payment
        GROUP BY trading_history_id
    ) p ON p.trading_history_id = t.Id

    WHERE t.client_id = ?
");

mysqli_stmt_bind_param($total_stmt, "i", $client_id);
mysqli_stmt_execute($total_stmt);

$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt));

$total_due = $summary['total_trading'] - $summary['total_paid'];



$invoice_stmt = mysqli_prepare($conn, "
    SELECT
        i.gst_invoice_no,
        i.client_id,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        i.created_at,
        c.added_by,
        COUNT(i.id) AS total_items,
        GROUP_CONCAT(
            i.item_name
            ORDER BY i.id ASC
            SEPARATOR ', '
        ) AS item_names
    FROM tbl_invoice i
    LEFT JOIN tbl_client c ON c.id = i.client_id
    WHERE i.client_id = ?
    GROUP BY
        i.gst_invoice_no,
        i.client_id,
        i.created_at,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        c.added_by
    ORDER BY i.created_at DESC
");

mysqli_stmt_bind_param($invoice_stmt, "i", $client_id);
mysqli_stmt_execute($invoice_stmt);

$client_invoices = mysqli_stmt_get_result($invoice_stmt);

?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
.client-detail {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.detail-box {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 15px;
}

.detail-box p {
    margin-bottom: 15px;
    overflow-wrap: anywhere;
}

.action-menu {
    min-width: 175px;
}

@media(max-width: 767px) {
    .client-detail {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid" style="color:black;">

    <!-- CLIENT DETAILS -->

    <div class="card shadow mb-4">

        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Client Details</h4>
        </div>

        <div class="card-body">

            <div class="client-detail">

                <div class="detail-box">

                    <p>
                        <strong>Client Name:</strong><br>
                        <?= e($client['cust_name']); ?>
                    </p>

                    <p>
                        <strong>Email:</strong><br>
                        <?= e($client['cust_email']); ?>
                    </p>

                    <p>
                        <strong>GST Number:</strong><br>
                        <?= e($client['gst_number'] ?? 'Not Added'); ?>
                    </p>

                </div>

                <div class="detail-box">

                    <p>
                        <strong>Phone:</strong><br>
                        <?= e($client['cust_phone']); ?>
                    </p>

                    <p>
                        <strong>Address:</strong><br>
                        <?= nl2br(e($client['cust_address'])); ?>
                    </p>

                    <p>
                        <strong>Shipping Address:</strong><br>
                        <?= nl2br(e($client['shipping_address'] ?? '')); ?>
                    </p>

                </div>

            </div>

            <a href="update-client.php?client_id=<?= $client_id; ?>"
               class="btn btn-success btn-sm mt-3">

                <i class="bi bi-pencil-square"></i>
                Update Client

            </a>

        </div>

    </div>


    

    <!-- TRADING HISTORY -->

    <div class="card shadow mb-4">

        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">

            <h4 class="mb-0">Trading History</h4>

            <a href="add-trading.php?client_id=<?= $client_id; ?>"
               class="btn btn-light btn-sm">

                <i class="bi bi-plus-circle"></i>
                Add Trading

            </a>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-striped text-center">

                    <thead class="table-light">
    <tr>
        <th>Sr.No.</th>
        <th>Date</th>
        <th>Item Name</th>
        <!--<th>Total Items</th>-->
        <!--<th>Base Amount</th>-->
        <!--<th>GST Amount</th>-->
        <th>Total Amount</th>
        <th>Paid Amount</th>
        <th>Balance</th>
        <th>Action</th>
    </tr>
</thead>

                   <tbody>

<?php if (mysqli_num_rows($trading_history) > 0): ?>

    <?php $i = mysqli_num_rows($trading_history); ?>

    <?php while ($row = mysqli_fetch_assoc($trading_history)): ?>

        <tr>

            <td><?= $i--; ?></td>

            <td><?= date('d-m-Y', strtotime($row['trading_date'])); ?></td>

            <td>
                <strong>
                    <?= e(explode(',', $row['item_names'])[0] ?? ''); ?>
                </strong>

                <?php if ($row['total_items'] > 1): ?>
                    <br>
                    <small class="text-muted">
                        + <?= $row['total_items'] - 1; ?> More Items
                    </small>
                <?php endif; ?>
            </td>

            <!--<td><?= (int)$row['total_items']; ?></td>-->

            <!--<td>-->
            <!--    ₹<?= number_format($row['base_amount'], 2); ?>-->
            <!--</td>-->

            <!--<td>-->
            <!--    ₹<?= number_format($row['gst_amount'], 2); ?>-->
            <!--</td>-->

            <td>
                <strong>
                    ₹<?= number_format($row['grand_total'], 2); ?>
                </strong>
            </td>

            <td class="text-success">
                <strong>
                    ₹<?= number_format($row['paid_amount'], 2); ?>
                </strong>
            </td>

            <td class="<?= $row['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                <strong>
                    ₹<?= number_format($row['balance'], 2); ?>
                </strong>
            </td>

            <td>

                <div class="dropdown">

                    <button class="btn btn-primary btn-sm dropdown-toggle"
                            type="button"
                            data-toggle="dropdown">
                        Action
                    </button>

                    <div class="dropdown-menu action-menu">

                        <!-- VIEW ITEMS -->

                        <button type="button"
                                class="dropdown-item text-info"
                                data-toggle="modal"
                                data-target="#tradingModal<?= (int)$row['group_id']; ?>">

                            <i class="bi bi-eye"></i>
                            View Items

                        </button>

                        <!-- EDIT ITEM -->

                        <?php
                        $firstItemStmt = mysqli_prepare($conn, "
                            SELECT Id
                            FROM tbl_trading_history
                            WHERE group_id = ? AND client_id = ?
                            ORDER BY Id ASC
                            LIMIT 1
                        ");

                        mysqli_stmt_bind_param(
                            $firstItemStmt,
                            "ii",
                            $row['group_id'],
                            $client_id
                        );

                        mysqli_stmt_execute($firstItemStmt);

                        $firstItem = mysqli_fetch_assoc(
                            mysqli_stmt_get_result($firstItemStmt)
                        );
                        ?>

                        <?php if ($firstItem): ?>

                            <a class="dropdown-item"
                               href="add-trading.php?group_id=<?= (int)$row['group_id']; ?>&client_id=<?= $client_id; ?>">

                                <i class="bi bi-pencil-square text-primary"></i>
                                Edit Item

                            </a>

                        <?php endif; ?>

                        <!-- GENERATE INVOICE -->

                        <a class="dropdown-item"
                           href="add-invoice.php?client_id=<?= $client_id; ?>&group_id=<?= (int)$row['group_id']; ?>">

                            <i class="bi bi-receipt text-success"></i>
                            Generate Invoice

                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- DELETE -->

                       <form action="add-trading-code.php"
      method="POST"
      onsubmit="return confirm('Are you sure you want to delete this complete trading group?');">

    <input type="hidden"
           name="group_id"
           value="<?= (int)$row['group_id']; ?>">

    <input type="hidden"
           name="client_id"
           value="<?= $client_id; ?>">

    <button type="submit"
            name="delete_trading_group"
            value="1"
            class="dropdown-item text-danger">

        <i class="bi bi-trash"></i>
        Delete Trading

    </button>

</form>

                    </div>

                </div>

            </td>

        </tr>

    <?php endwhile; ?>

<?php else: ?>

    <tr>
        <td colspan="10" class="text-muted">
            No Trading History Found
        </td>
    </tr>

<?php endif; ?>

</tbody>
                </table>

            </div>

        </div>
        
        

         <!-- ================= VIEW ITEMS MODALS ================= -->

<?php

$modalStmt = mysqli_prepare($conn, "
    SELECT id, trading_date
    FROM tbl_trading_groups
    WHERE client_id = ?
    ORDER BY trading_date DESC, id DESC
");

mysqli_stmt_bind_param($modalStmt, "i", $client_id);
mysqli_stmt_execute($modalStmt);

$modalGroups = mysqli_stmt_get_result($modalStmt);

?>

<?php while ($modal = mysqli_fetch_assoc($modalGroups)): ?>

<div class="modal fade"
     id="tradingModal<?= (int)$modal['id']; ?>"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered"
         role="document">

        <div class="modal-content">

            <div class="modal-header bg-info text-white">

                <h5 class="modal-title">
                    Trading Details -
                    <?= date('d-m-Y', strtotime($modal['trading_date'])); ?>
                </h5>

                <button type="button"
                        class="close text-white"
                        data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <?php

                $detailStmt = mysqli_prepare($conn, "
                    SELECT *
                    FROM tbl_trading_history
                    WHERE group_id = ?
                    AND client_id = ?
                    ORDER BY Id ASC
                ");

                mysqli_stmt_bind_param(
                    $detailStmt,
                    "ii",
                    $modal['id'],
                    $client_id
                );

                mysqli_stmt_execute($detailStmt);

                $details = mysqli_stmt_get_result($detailStmt);

                ?>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped text-center">

                        <thead class="table-light">

                            <tr>
                                <th>Sr.</th>
                                <th>Item Name</th>
                                <th>HSN</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>GST</th>
                                <th>GST Type</th>
                                <th>GST Amount</th>
                                <th>Total</th>
                                <th>Description</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php $sr = 1; ?>

                        <?php while ($item = mysqli_fetch_assoc($details)): ?>

                            <tr>

                                <td><?= $sr++; ?></td>

                                <td><?= e($item['item_name']); ?></td>

                                <td><?= e($item['hsn_code']); ?></td>

                                <td>
                                    <?= number_format((float)$item['qty'], 2); ?>
                                </td>

                                <td>
                                    ₹<?= number_format((float)$item['amount'], 2); ?>
                                </td>

                                <td>
                                    <?= number_format((float)$item['gst_rate'], 2); ?>%
                                </td>

                                <td><?= e(ucfirst($item['gst_type'])); ?></td>

                                

                                <td>
                                    ₹<?= number_format((float)$item['gst_amount'], 2); ?>
                                </td>

                                <td>
                                    <strong>
                                        ₹<?= number_format((float)$item['grand_total'], 2); ?>
                                    </strong>
                                </td>
                                
                                <td><?= e($item['description']); ?></td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <hr>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                    Close

                </button>

            </div>

        </div>

    </div>

</div>

<?php endwhile; ?>

<!-- ================= END VIEW ITEMS MODALS ================= -->

    
    
    <!-- ================= GENERATED INVOICES ================= -->

<div class="card shadow mb-4">

    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">

        <h4 class="mb-0">
            <i class="bi bi-receipt"></i>
            Generated Invoices
        </h4>

        <!--<a href="add-invoice.php?client_id=<?= $client_id; ?>"-->
        <!--   class="btn btn-light btn-sm">-->

        <!--    <i class="bi bi-plus-circle"></i>-->
        <!--    Generate New Invoice-->

        <!--</a>-->

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-hover text-center">

                <thead class="table-light">

                    <tr>
                        <th>Sr. No.</th>
                        <th>Invoice No</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Invoice Date</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($client_invoices) > 0): ?>

                    <?php $sr = 1; ?>

                    <?php while ($invoice = mysqli_fetch_assoc($client_invoices)): ?>

                        <tr>

                            <td><?= $sr++; ?></td>

                            <td>
                                <?= e($invoice['gst_invoice_no']); ?>
                            </td>

                            <td>
                                <?= e($invoice['cust_name']); ?>
                            </td>

                            <td>
                                <?= e($invoice['cust_phone']); ?>
                            </td>

                            <td>
                                <?= date('d-m-Y', strtotime($invoice['invoice_date'])); ?>
                            </td>

                            <td>

                                <?php if (!empty($invoice['pdf_file']) && file_exists($invoice['pdf_file'])): ?>

                                    <?php
                                    $isTrader = strtolower(trim($invoice['added_by'] ?? '')) === 'trader';

                                    $pdfPage = $isTrader
                                        ? 'generate-trader-invoice.php'
                                        : 'generate-invoice.php';

                                    $pdfUrl = $pdfPage
                                        . '?client_id=' . (int)$invoice['client_id']
                                        . '&created_at=' . urlencode($invoice['created_at']);
                                    ?>

                                    <div class="dropdown">

                                        <button class="btn btn-primary btn-sm dropdown-toggle"
                                                type="button"
                                                data-toggle="dropdown">

                                            <i class="bi bi-three-dots"></i>
                                            Action

                                        </button>

                                        <div class="dropdown-menu">

                                            <!-- VIEW PDF -->

                                            <a class="dropdown-item"
                                               href="<?= e($pdfUrl); ?>"
                                               target="_blank">

                                                <i class="bi bi-file-earmark-pdf text-success"></i>
                                                View PDF

                                            </a>

                                            <!-- EDIT INVOICE -->

                                            <a class="dropdown-item"
                                               href="add-invoice.php?client_id=<?= (int)$invoice['client_id']; ?>&edit=1">

                                                <i class="bi bi-pencil-square text-primary"></i>
                                                Edit Invoice

                                            </a>

                                            <!-- SEND INVOICE -->

                                            <a class="dropdown-item"
                                               href="send-invoice-email.php?client_id=<?= (int)$invoice['client_id']; ?>&created_at=<?= urlencode($invoice['created_at']); ?>"
                                               onclick="return confirm('Send invoice to client email?');">

                                                <i class="bi bi-envelope text-dark"></i>
                                                Send Invoice

                                            </a>

                                            <div class="dropdown-divider"></div>

                                            <!-- DELETE INVOICE -->

                                            <form action="save-invoice.php"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this invoice?');">

                                                <input type="hidden"
                                                       name="action"
                                                       value="delete">

                                                <input type="hidden"
                                                       name="client_id"
                                                       value="<?= (int)$invoice['client_id']; ?>">

                                                <input type="hidden"
                                                       name="created_at"
                                                       value="<?= e($invoice['created_at']); ?>">

                                                <button type="submit"
                                                        class="dropdown-item text-danger">

                                                    <i class="bi bi-trash"></i>
                                                    Delete Invoice

                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                <?php else: ?>

                                    <span class="text-danger">
                                        PDF Not Available
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-muted">
                            No Invoices Generated For This Client
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ================= END GENERATED INVOICES ================= -->


    </div>

</div>

<?php

include('includes/scripts.php');
include('includes/footer.php');

ob_end_flush();

?>
