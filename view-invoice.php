<?php
include('authentication.php');
include('dbcon.php');
require __DIR__.'/vendor/autoload.php';
use setasign\Fpdi\Fpdi;

$filter_applied = false;

/* ===== CHECK FILTER ===== */
if (!empty($_GET['start_date']) || !empty($_GET['end_date'])) {
    $filter_applied = true;
}

// ================= HANDLE PRINT & DOWNLOAD ALL =================
if (isset($_GET['action'])) {

    $date_condition = '';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
        $end_date   = mysqli_real_escape_string($conn, $_GET['end_date']);
        $date_condition = "WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
        $filter_applied = true;
    } elseif (!empty($_GET['start_date'])) {
        $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
        $date_condition = "WHERE invoice_date >= '$start_date'";
        $filter_applied = true;
    } elseif (!empty($_GET['end_date'])) {
        $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
        $date_condition = "WHERE invoice_date <= '$end_date'";
        $filter_applied = true;
    }

    if (!empty($_GET['cust_name'])) {
        $cust_name = mysqli_real_escape_string($conn, $_GET['cust_name']);
        $date_condition .= ($date_condition ? " AND " : " WHERE ") . "cust_name LIKE '%$cust_name%'";
        $filter_applied = true;
    }

   $query = "
    SELECT 
        i.gst_invoice_no,
        i.client_id,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        i.created_at,
        c.added_by
    FROM tbl_invoice i
    LEFT JOIN tbl_client c ON c.id = i.client_id
    $date_condition
    GROUP BY i.gst_invoice_no
    ORDER BY CAST(i.gst_invoice_no AS UNSIGNED) DESC
";

    $query_run = mysqli_query($conn, $query) or die(mysqli_error($conn));

    if (mysqli_num_rows($query_run) > 0) {
        $pdf = new Fpdi();
        mysqli_data_seek($query_run, 0);

        while ($row = mysqli_fetch_assoc($query_run)) {
            if (!empty($row['pdf_file']) && file_exists($row['pdf_file'])) {
                $pageCount = $pdf->setSourceFile($row['pdf_file']);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplId);
                }
            }
        }

        $filename = 'all_invoices_' . date('Ymd_His') . '.pdf';
        if ($_GET['action'] === 'print') {
            $pdf->Output('I', $filename); // inline in browser
        } elseif ($_GET['action'] === 'download') {
            $pdf->Output('D', $filename); // download
        }
        exit;
    }
}

// ================= LOAD HEADER / NAVBAR =================
include('includes/header.php');
include('includes/navbar.php');
?>

<div class="container-fluid">

    <!-- ================= FILTER FORM ================= -->
    <form method="GET" action="" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <input type="date" name="start_date"
                    value="<?= isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : '' ?>"
                    class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" name="end_date"
                    value="<?= isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : '' ?>"
                    class="form-control">
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            
            <?php if($filter_applied) { ?>
<div class="col-md-3">
    <a href="?<?php 
        $query = $_GET; 
        $query['action'] = 'print'; 
        echo http_build_query($query); ?>" 
       class="btn btn-info" target="_blank">
       🖨️ Print All
    </a>
</div>

<div class="col-md-3">
    <a href="?<?php 
        $query = $_GET; 
        $query['action'] = 'download'; 
        echo http_build_query($query); ?>" 
       class="btn btn-danger" target="_blank">
       ⬇️ Download All PDFs
    </a>
</div>
<?php } ?>
            
        </div>
    </form>
    
     <form method="GET" action="" class="mb-4">
    <div class="row">

        <div class="col-md-4">
            <input type="text" name="cust_name"
                placeholder="Search Customer Name"
                value="<?= isset($_GET['cust_name']) ? htmlspecialchars($_GET['cust_name']) : '' ?>"
                class="form-control">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-success">Search</button>
        </div>

    </div>
</form>

    <h2 class="text-center mb-4">All Generated Invoices</h2>

    <div class="containers">

        <?php
        /* ================= DATE FILTER ================= */
        $date_condition = '';
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
            $end_date   = mysqli_real_escape_string($conn, $_GET['end_date']);
            $date_condition = "WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
        } elseif (!empty($_GET['start_date'])) {
            $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
            $date_condition = "WHERE invoice_date >= '$start_date'";
        } elseif (!empty($_GET['end_date'])) {
            $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
            $date_condition = "WHERE invoice_date <= '$end_date'";
        }
        if (!empty($_GET['cust_name'])) {
    $cust_name = mysqli_real_escape_string($conn, $_GET['cust_name']);
    $date_condition .= ($date_condition ? " AND " : " WHERE ") . "cust_name LIKE '%$cust_name%'";
}

       $query = "
    SELECT 
        i.gst_invoice_no,
        i.client_id,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        i.created_at,
        c.added_by
    FROM tbl_invoice i
    LEFT JOIN tbl_client c ON c.id = i.client_id
    $date_condition
    GROUP BY i.gst_invoice_no
    ORDER BY CAST(i.gst_invoice_no AS UNSIGNED) DESC
";


        $query_run = mysqli_query($conn, $query) or die(mysqli_error($conn));
        ?>

        <!-- ================= TABLE ================= -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center">
                <thead class="bg-dark text-white">
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
                    <?php
                    $sr = 1;
                    if (mysqli_num_rows($query_run) > 0):
                        while ($row = mysqli_fetch_assoc($query_run)):

                            if (!empty($row['pdf_file']) && file_exists($row['pdf_file'])) {
                                $pdfLink = $row['pdf_file'];
                    ?>
                                <tr>
                                    <td><?= $sr++; ?></td>
                                    <td><?= htmlspecialchars($row['gst_invoice_no']); ?></td>
                                    <td><?= htmlspecialchars($row['cust_name']); ?></td>
                                    <td><?= htmlspecialchars($row['cust_phone']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                                    <td> <!-- center all content inside td -->

                                        <button class="actionBtn btn btn-sm btn-primary">Action</button>

                                        <!-- ACTION BUTTONS (Initially Hidden) -->
                                        <div class="actionBox mt-2" style="display: none;">
                                            <!-- VIEW PDF BUTTON -->
                                            
<?php
$isTrader = strtolower(trim($row['added_by'] ?? '')) === 'trader';

$pdfPage = $isTrader
    ? 'generate-trader-invoice.php'
    : 'generate-invoice.php';

$pdfUrl = $pdfPage
    . '?client_id=' . (int)$row['client_id']
    . '&created_at=' . urlencode($row['created_at']);
?>

<button type="button"
    class="btn btn-success btn-sm btn-block"
    onclick="window.open('<?= htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') ?>', '_blank');">
    <i class="bi bi-file-earmark-pdf"></i> View PDF
</button>


                                            <!-- EDIT INVOICE BUTTON -->
                                            <button type="button"
                                                class="btn btn-warning btn-sm btn-block mb-2"
                                                onclick="window.location='add-invoice.php?client_id=<?= (int)$row['client_id']; ?>&edit=1';">
                                                <i class="bi bi-pencil-square"></i> Edit Invoice
                                            </button>


                                            <!-- SEND INVOICE BUTTON -->
                                            <form action="send-invoice-email.php" method="get" style="display:block;">
                                                <input type="hidden" name="client_id" value="<?= (int)$row['client_id']; ?>">
                                                <input type="hidden" name="created_at" value="<?= htmlspecialchars($row['created_at']); ?>">
                                                <button type="submit"
                                                    class="btn btn-dark btn-sm btn-block mb-2"

                                                    onclick="return confirm('Send invoice to client email?');">
                                                    <i class="bi bi-envelope"></i> Send Invoice
                                                </button>
                                            </form>

                                            <form action="save-invoice.php" method="post" style="display:block; margin-top:3px;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="client_id" value="<?= (int)$row['client_id']; ?>">
                                                <input type="hidden" name="created_at" value="<?= htmlspecialchars($row['created_at']); ?>">
                                                <button type="submit"
                                                    class="btn btn-danger btn-sm btn-block"

                                                    onclick="return confirm('Are you sure you want to delete this invoice?');">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td><?= $sr++; ?></td>
                                    <td><?= htmlspecialchars($row['gst_invoice_no']); ?></td>
                                    <td><?= htmlspecialchars($row['cust_name']); ?></td>
                                    <td><?= htmlspecialchars($row['cust_phone']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                                    <td>PDF Not Available</td>
                                </tr>
                        <?php
                            }
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="6">No Invoices Found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>