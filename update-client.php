<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = $_GET['client_id'] ?? 0;

// Fetch existing client info
$client = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_client WHERE id='$client_id'"));

// Generate next GST invoice number
function getNextInvoiceNumber($conn) {
    $result = mysqli_query($conn, "SELECT gst_invoice_no FROM tbl_client ORDER BY id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($result);

    if($row && !empty($row['gst_invoice_no'])) {
        $lastNumber = (int) filter_var($row['gst_invoice_no'], FILTER_SANITIZE_NUMBER_INT);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1001;
    }

    return "" . $nextNumber;
}

// Handle form submission
if (isset($_POST['update'])) {

    $cust_name = mysqli_real_escape_string($conn, $_POST['cust_name']);
    $cust_phone = mysqli_real_escape_string($conn, $_POST['cust_phone']);
    $cust_email = mysqli_real_escape_string($conn, $_POST['cust_email']);
    $cust_address = mysqli_real_escape_string($conn, $_POST['cust_address']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $service_date = $_POST['service_date'];
    $warranty = mysqli_real_escape_string($conn, $_POST['warranty']);
    $service_covered = mysqli_real_escape_string($conn, $_POST['service_covered']);
    $terms = mysqli_real_escape_string($conn, $_POST['service_term_condition']);

    $total_amount = floatval($_POST['total_amount']);

    $consumer_no = mysqli_real_escape_string($conn, $_POST['consumer_no']);
    $subdivision = mysqli_real_escape_string($conn, $_POST['subdivision']);
    $technical_name = mysqli_real_escape_string($conn, $_POST['technical_name']);

    $apply_gst = isset($_POST['apply_gst']) ? 1 : 0;

    // GST TYPE
    $gst_type = $_POST['gst_type'] ?? 'exclusive';

    $billing_address = mysqli_real_escape_string($conn, $_POST['billing_address'] ?? '');
    $shipping_address = mysqli_real_escape_string($conn, $_POST['shipping_address'] ?? '');
    
    $gst_number = mysqli_real_escape_string(
    $conn,
    $_POST['gst_number'] ?? ''
);

    $gst_invoice_no = $client['gst_invoice_no'];

    // Generate GST invoice no
    if($apply_gst && empty($gst_invoice_no)){
        $gst_invoice_no = getNextInvoiceNumber($conn);
    }

     // =========================
    // GST CALCULATION FIXED
    // =========================
    if($apply_gst){

        if($gst_type == "inclusive"){

            $base_amount = round($total_amount / 1.18, 2);
            $gst_amount  = round($total_amount - $base_amount, 2);
            $final_amount = $total_amount;

        } else {

            $base_amount = $total_amount;
            $gst_amount  = round($total_amount * 0.18, 2);
            $final_amount = round($total_amount + $gst_amount, 2);
        }

    } else {

        $base_amount = $total_amount;
        $gst_amount = 0;
        $final_amount = $total_amount;
    }

    // UPDATE CLIENT
    $update_query = "UPDATE tbl_client SET

        cust_name='$cust_name',
        cust_phone='$cust_phone',
        cust_email='$cust_email',
        cust_address='$cust_address',
        service='$service',
        service_date='$service_date',
        warranty='$warranty',
        service_covered='$service_covered',
        service_term_condition='$terms',

        total_amount='$total_amount',

        consumer_no='$consumer_no',
        subdivision='$subdivision',
        technical_name='$technical_name',

        apply_gst='$apply_gst',
        gst_type='$gst_type',
        gst_amount='$gst_amount',

        billing_address='$billing_address',
        shipping_address='$shipping_address',
        gst_number='$gst_number',
        gst_invoice_no='$gst_invoice_no'

        WHERE id='$client_id'";

    if (mysqli_query($conn, $update_query)) {

        // =========================
        // UPDATE tbl_payment ALSO
        // =========================

        $checkPayment = mysqli_query($conn, "SELECT * FROM tbl_payment WHERE client_id='$client_id'");

        if(mysqli_num_rows($checkPayment) > 0){

            mysqli_query($conn, "
                UPDATE tbl_payment SET
                total_amount='$final_amount',
                due_amount='$final_amount',
                due_date='$service_date'
                WHERE client_id='$client_id'
            ");

        } else {

            mysqli_query($conn, "
                INSERT INTO tbl_payment
                (client_id, total_amount, due_amount, due_date)
                VALUES
                ('$client_id', '$final_amount', '$final_amount', '$service_date')
            ");
        }

        $_SESSION['status'] = "Client Updated Successfully!";
        header("Location: view-client-detail.php?client_id=$client_id");
        exit();

    } else {

        $error = "Error : ".mysqli_error($conn);
    }
}
?>

<div class="inquiry-container">

    <h2 class="text-center">Update Client Details</h2>

    <?php
    if(isset($error)){
        echo "<div class='alert alert-danger'>$error</div>";
    }
    ?>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="cust_name"
            value="<?= $client['cust_name']; ?>" required>

        <label>Phone</label>
        <input type="text" name="cust_phone"
            value="<?= $client['cust_phone']; ?>" required>

        <label>Email</label>
        <input type="email" name="cust_email"
            value="<?= $client['cust_email']; ?>">

        <label>Address</label>
        <textarea name="cust_address"><?= $client['cust_address']; ?></textarea>

        <label>Service</label>
        <input type="text" name="service"
            value="<?= $client['service']; ?>" required>

        <label>Service Date</label>
        <input type="date" name="service_date"
            value="<?= $client['service_date']; ?>" required>

        <label>Warranty</label>
        <input type="text" name="warranty"
            value="<?= $client['warranty']; ?>">

        <label>Service Covered</label>
        <textarea name="service_covered"><?= $client['service_covered']; ?></textarea>

        <label>Terms & Conditions</label>
        <textarea name="service_term_condition"><?= $client['service_term_condition']; ?></textarea>

        <label>Total Amount</label>
        <input type="number"
            step="0.01"
            name="total_amount"
            value="<?= $client['total_amount']; ?>"
            required>

        <label>Consumer No</label>
        <input type="text"
            name="consumer_no"
            value="<?= $client['consumer_no']; ?>">

        <label>Sub Division</label>
        <input type="text"
            name="subdivision"
            value="<?= $client['subdivision']; ?>">

        <label>Technical Name</label>
        <input type="text"
            name="technical_name"
            value="<?= $client['technical_name']; ?>">

        <hr>

        <!-- GST APPLY -->
        <div class="form-check mb-3">

            <input class="form-check-input"
                type="checkbox"
                id="apply_gst"
                name="apply_gst"
                value="1"
                <?= $client['apply_gst'] ? 'checked' : ''; ?>
                onclick="toggleGSTFields()">

            <label class="form-check-label">
                Apply GST (18%)
            </label>
        </div>

        <!-- GST FIELDS -->
        <div id="gst_fields"
            style="display: <?= $client['apply_gst'] ? 'block' : 'none'; ?>;">

            <!-- GST TYPE -->
            <label>GST Type</label>

            <select name="gst_type" class="form-control mb-3">

                <option value="exclusive"
                    <?= ($client['gst_type'] ?? '') == 'exclusive' ? 'selected' : ''; ?>>
                    Exclusive GST
                </option>

                <option value="inclusive"
                    <?= ($client['gst_type'] ?? '') == 'inclusive' ? 'selected' : ''; ?>>
                    Inclusive GST
                </option>

            </select>

            <label>Billing Address</label>
            <textarea name="billing_address"><?= $client['billing_address']; ?></textarea>

            <label>Shipping Address</label>
            <textarea name="shipping_address"><?= $client['shipping_address']; ?></textarea>
            
            <label>GST Number (GSTIN)</label>
<input
    type="text"
    name="gst_number"
    class="form-control mb-3"
    placeholder="Enter GST Number"
    maxlength="15"
    style="text-transform: uppercase;"
    value="<?= htmlspecialchars($client['gst_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
>

        </div>

        <button type="submit" name="update" class="btn btn-primary">
            Update Client
        </button>

    </form>

</div>

<script>

function toggleGSTFields() {

    var gstFields = document.getElementById('gst_fields');

    if(document.getElementById('apply_gst').checked){

        gstFields.style.display = 'block';

    } else {

        gstFields.style.display = 'none';
    }
}

</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>