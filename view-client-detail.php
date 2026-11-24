<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = $_GET['client_id'] ?? 0;

// Fetch Client Info
$client = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_client WHERE Id='$client_id'"));

// Payment Summary
$payment_summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(paid_amount) AS total_paid,
        MAX(due_date) AS last_due_date
    FROM tbl_payment 
    WHERE client_Id='$client_id'
"));


/* *********** PAYMENT CALCULATION *********** */

$base_input = (float) ($client['total_amount'] ?? 0);
$gst_rate = (float) ($client['gst_rate'] ?? 18);

if (!in_array($gst_rate, [5.0, 18.0], true)) {
    $gst_rate = 18.0;
}

$gst_amount = 0;
$grand_total = $base_input;
$base_amount = $base_input;

if ((int) ($client['apply_gst'] ?? 0) === 1) {

    if (($client['gst_type'] ?? 'exclusive') === 'inclusive') {

        $grand_total = $base_input;

        $base_amount = round(
            $grand_total / (1 + ($gst_rate / 100)),
            2
        );

        $gst_amount = round(
            $grand_total - $base_amount,
            2
        );

    } else {

        $base_amount = $base_input;

        $gst_amount = round(
            $base_amount * $gst_rate / 100,
            2
        );

        $grand_total = round(
            $base_amount + $gst_amount,
            2
        );
    }
}

$total_paid = (float) ($payment_summary['total_paid'] ?? 0);
$balance = round($grand_total - $total_paid, 2);

$is_trader = (($client['added_by'] ?? '') === 'trader');


// Fetch Payment History
$payments = mysqli_query($conn, "SELECT * FROM tbl_payment WHERE client_Id='$client_id' ORDER BY payment_date DESC");


// Fetch Pending Work
$pending_works = mysqli_query($conn, "SELECT * FROM tbl_pending_work WHERE client_id='$client_id' ORDER BY due_date ASC");

// // Fetch Uploaded Documents
// $documents = mysqli_query($conn, "SELECT * FROM  WHERE client_id='$client_id' ORDER BY id DESC");

// Check if invoice exists for this client
$invoiceQry = mysqli_query($conn, "
    SELECT gst_invoice_no, created_at, pdf_file 
    FROM tbl_invoice 
    WHERE client_id='$client_id' 
    ORDER BY id DESC
    LIMIT 1
");

$invoice = mysqli_fetch_assoc($invoiceQry); // null if no invoice

?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


<div class="container-fluid" style="color: black; font-size:large">

    <!-- *************************** CLIENT + SERVICE DETAILS SIDE BY SIDE *************************** -->

    <!-- LEFT SIDE: CLIENT DETAILS -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h4>Client Details</h4>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?= $client['cust_name']; ?></p>
            <p><strong>Phone:</strong> <?= $client['cust_phone']; ?></p>
            <p><strong>Email:</strong> <?= $client['cust_email']; ?></p>
            <p><strong>Address:</strong> <?= $client['cust_address']; ?></p>
            <button onclick="window.location.href='update-client.php?client_id=<?= $client['id']; ?>'" class="btn btn-success btn-sm mt-2 mr-3" style="width: fit-content;">Update Client</button>

            <!-- Upload Button -->
            <button class="btn btn-dark btn-sm mt-2 mr-3" style="width: fit-content;" onclick="openUploadPopup()">
                <i class="fa-solid fa-file-upload"></i> Upload Docs
            </button>

            <?php
            // Decode documents
            $documents = !empty($client['documents']) ? json_decode($client['documents'], true) : [];
            ?>

            <!-- Toggle Button -->
            <button class="btn btn-danger btn-sm mt-2 mr-3 text-left text-decoration-none" type="button"
                id="toggleDocumentsButton" style="border: none; width:fit-content">
                Uploaded Documents
                <i class="fas fa-chevron-down" id="toggleIcon"></i>
            </button>
            
            
            <?php if ($client['apply_gst'] == 1): ?>
    <!-- GST Applied: Show Generate/View Invoice buttons -->
            <?php if ($invoice): ?>
                <?php if (!empty($invoice['pdf_file']) && file_exists($invoice['pdf_file'])): ?>
                    <button type="button" class="btn btn-success btn-sm mt-2" style="width: fit-content;"
                        onclick="window.open('<?= htmlspecialchars($invoice['pdf_file']); ?>', '_blank')">
                        <i class="bi bi-eye"></i> View Invoice
                    </button>

                <?php else: ?>
                    <button type="button" class="btn btn-primary btn-sm mt-2" style="width: fit-content;"
                        onclick="window.location.href='generate-invoice.php?client_id=<?= $client['id']; ?>&created_at=<?= urlencode($invoice['created_at']); ?>'">
                        <i class="bi bi-receipt"></i> Generate Invoice
                    </button>

                <?php endif; ?>
            <?php else: ?>
                <button type="button" class="btn btn-primary btn-sm mt-2" style="width: fit-content;"
                    onclick="window.location.href='add-invoice.php?client_id=<?= $client['id']; ?>'">
                    <i class="bi bi-receipt"></i> Generate Invoice
                </button>

            <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3" id="documents-list">

                <!-- If NO documents uploaded -->
                <?php if (empty($documents)): ?>
                    <p class="text-muted mt-2" id="documentsContent" style="display: none;">No Documents Uploaded</p>

                <?php else: ?>
                    <div id="documentsContent" style="display:none;">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($documents as $doc):

                                $filePath = "uploads/client_docs/" . $doc;

                                // ðŸ”¥ Remove random prefix before _
                                $displayName = $doc;
                                if (strpos($doc, '_') !== false) {
                                    $displayName = substr($doc, strpos($doc, '_') + 1);
                                }
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center border-0">
                                    <?= htmlspecialchars($displayName); ?>
                                    <a href="<?= $filePath; ?>" target="_blank"
                                        class="btn btn-sm btn-secondary">
                                        View
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>



   
<!-- SERVICE / TRADER DETAILS -->

<div class="card shadow mb-4">

    <div class="card-header bg-info text-white">
        <h4>
            <?= $is_trader ? 'Trader Details' : 'Service Details'; ?>
        </h4>
    </div>

    <div class="card-body">

        <?php if ($is_trader): ?>

            <p>
                <strong>Description:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['service_covered'] ?? ''
                )); ?>
            </p>

            <p>
                <strong>Terms &amp; Conditions:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['service_term_condition'] ?? ''
                )); ?>
            </p>

            <p>
                <strong>Amount:</strong>
                ₹<?= number_format(
                    (float) ($client['total_amount'] ?? 0),
                    2
                ); ?>
            </p>

        <?php else: ?>

            <p>
                <strong>Service:</strong>
                <?= htmlspecialchars($client['service'] ?? ''); ?>
            </p>

            <p>
                <strong>Service Date:</strong>
                <?= htmlspecialchars($client['service_date'] ?? ''); ?>
            </p>

            <p>
                <strong>Warranty:</strong>
                <?= htmlspecialchars($client['warranty'] ?? ''); ?>
            </p>

            <p>
                <strong>Service Covered:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['service_covered'] ?? ''
                )); ?>
            </p>

            <p>
                <strong>Terms &amp; Conditions:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['service_term_condition'] ?? ''
                )); ?>
            </p>

            <p>
                <strong>Consumer No:</strong>
                <?= htmlspecialchars($client['consumer_no'] ?? ''); ?>
            </p>

            <p>
                <strong>Subdivision:</strong>
                <?= htmlspecialchars($client['subdivision'] ?? ''); ?>
            </p>

            <p>
                <strong>Technician Name:</strong>
                <?= htmlspecialchars($client['technical_name'] ?? ''); ?>
            </p>

        <?php endif; ?>

    </div>
</div>


    <!-- *********************** PENDING WORK DETAILS ************************ -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h4>Pending Work</h4>
        </div>
        <div class="card-body">
            <button onclick="window.location.href='add-pending-work.php?client_id=<?= $client_id; ?>'" class="btn btn-success btn-sm mb-3" style="width: fit-content;">Add Pending Work</button>

            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th>Sr.No.</th>
                            <th>Work Description</th>
                            <th>Assigned To</th>
                            <th>Service Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($pending_works) > 0) {
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($pending_works)) {
                        ?>
                                <tr>
                                    <td><?= $count++; ?></td>
                                    <td><?= $row['work_description']; ?></td>
                                    <td><?= $row['assigned_to']; ?></td>
                                    <td><?= $row['due_date']; ?></td>
                                    <td><?= $row['status']; ?></td>
                                    <td>
                                        <button onclick="window.location.href='update-pending-work.php?work_id=<?= $row['id']; ?>'" class="btn btn-sm btn-dark">Update</button>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='6'>No Pending Work Found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


   
<!-- GST DETAILS -->

<?php if ((int) ($client['apply_gst'] ?? 0) === 1): ?>

    <div class="card shadow mb-4">

        <div class="card-header bg-info text-white">
            <h4>GST Details</h4>
        </div>

        <div class="card-body">

            <p>
                <strong>GST Applied:</strong>
                Yes (<?= rtrim(rtrim(
                    number_format($gst_rate, 2, '.', ''),
                    '0'
                ), '.'); ?>%)
            </p>

            <p>
                <strong>GST Type:</strong>
                <?= ($client['gst_type'] ?? '') === 'inclusive'
                    ? 'Inclusive - GST Included'
                    : 'Exclusive - GST Extra'; ?>
            </p>

            <p>
                <strong>GST Amount:</strong>
                ₹<?= number_format($gst_amount, 2); ?>
            </p>

            <p>
                <strong>GST Number (GSTIN):</strong>
                <?= htmlspecialchars(
                    $client['gst_number'] ?? 'Not Added'
                ); ?>
            </p>

            <p>
                <strong>Billing Address:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['billing_address'] ?? ''
                )); ?>
            </p>

            <p>
                <strong>Shipping Address:</strong>
                <?= nl2br(htmlspecialchars(
                    $client['shipping_address'] ?? ''
                )); ?>
            </p>

        </div>
    </div>

<?php endif; ?>


    <!-- ************************ PAYMENT DETAILS ************************ -->

    <div class="card mb-4 shadow">
        <div class="card-header bg-info text-white">
            <h4>Payment Details</h4>
        </div>

        <div class="card-body">
            <div class="row mb-3">

                <div class="col-md-4">
                    <strong>Base Amount:</strong> <?= number_format($base_amount, 2); ?>
                </div>

                <?php if ($client['apply_gst'] == 1): ?>
                    <div class="col-md-4">
                        <strong>GST (<?= rtrim(rtrim(
    number_format($gst_rate, 2, '.', ''),
    '0'
), '.'); ?>%):</strong>
₹<?= number_format($gst_amount, 2); ?>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <strong>Grand Total:</strong> <?= number_format($grand_total, 2); ?>
                </div>

                <div class="col-md-4 mt-3">
                    <strong>Total Paid:</strong> <?= number_format($total_paid, 2); ?>
                </div>

                <div class="col-md-4 mt-3">
                    <strong>Balance:</strong> <?= number_format($balance, 2); ?>
                </div>

            </div>

            <!-- PAYMENT HISTORY -->
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Payment Date</th>
                            <th>Paid Amount</th>
                            <th>Payment Gateway</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$serial_no = 1;
$rows_shown = 0;

if (mysqli_num_rows($payments) > 0) {

    while ($row = mysqli_fetch_assoc($payments)) {

        // Skip NULL and 0 amount entries
        if (
            !isset($row['paid_amount']) ||
            (float)$row['paid_amount'] <= 0
        ) {
            continue;
        }

        $rows_shown++;
        ?>

        <tr>
            <td><?= $serial_no++; ?></td>
            <td><?= htmlspecialchars($row['payment_date']); ?></td>
            <td><?= number_format((float)$row['paid_amount'], 2); ?></td>
            <td><?= htmlspecialchars($row['payment_gateway']); ?></td>
        </tr>

        <?php
    }

    if ($rows_shown == 0) {
        echo "<tr><td colspan='4'>No Payment Records Found</td></tr>";
    }

} else {
    echo "<tr><td colspan='4'>No Payment Records Found</td></tr>";
}
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>


<!-- UPLOAD DOCUMENT POPUP -->
<div id="uploadPopup" class="popup-overlay">
    <div class="popup-box">
        <h2>Upload Documents</h2>
        <p class="subtitle">Select document file and upload</p>

        <form id="uploadForm" action="upload-client-docs.php?id=<?= $client['id']; ?>" method="POST" enctype="multipart/form-data">

            <label class="input-label">Upload Document</label>
            <input type="file" name="documents[]" multiple class="file-input" required>

            <input type="hidden" name="client_id" value="<?= $client['id']; ?>">

            <button type="submit" class="upload-btn">Upload</button>
        </form>

        <button class="close-btn" onclick="closeUploadPopup()">Close</button>
    </div>
</div>

<script>
    function openUploadPopup() {
        document.getElementById("uploadPopup").style.display = "flex";
    }

    function closeUploadPopup() {
        document.getElementById("uploadPopup").style.display = "none";
    }

    // Get references to the elements
    const toggleButton = document.getElementById('toggleDocumentsButton');
    const documentsContent = document.getElementById('documentsContent');
    const toggleIcon = document.getElementById('toggleIcon');

    // Function to toggle visibility of documents
    toggleButton.addEventListener('click', function() {
        // Toggle the display of the document content
        if (documentsContent.style.display === 'none' || documentsContent.style.display === '') {
            documentsContent.style.display = 'block';
            documentsContent.style.transition = '0.35s ease-out';
            toggleIcon.classList.remove('fa-chevron-down');
            toggleIcon.classList.add('fa-chevron-up');
        } else {
            documentsContent.style.display = 'none';
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
        }
    });
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
<?php ob_end_flush(); ?>