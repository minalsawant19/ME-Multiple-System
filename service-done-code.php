<?php

session_start();
include('dbcon.php');

/* =========================================================
   GET INQUIRY ID
========================================================= */

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

$client_type = $_POST['client_type'] ?? 'service';

if (!in_array($client_type, ['trader', 'service'], true)) {
    $client_type = 'service';
}

$is_trader = ($client_type === 'trader');

if ($id <= 0) {
    $_SESSION['status'] = "Invalid Inquiry ID!";
    header("Location: service-done.php");
    exit();
}

/* =========================================================
   GET INQUIRY
========================================================= */

$inqQuery = mysqli_query(
    $conn,
    "SELECT * FROM tbl_inquiry WHERE id='$id' LIMIT 1"
);

if (!$inqQuery || mysqli_num_rows($inqQuery) == 0) {
    $_SESSION['status'] = "Inquiry not found!";
    header("Location: service-done.php?id=$id");
    exit();
}

$inq = mysqli_fetch_assoc($inqQuery);

/* =========================================================
   DUPLICATE CLIENT CHECK
========================================================= */

$duplicateQuery = mysqli_query(
    $conn,
    "SELECT id FROM tbl_client WHERE inquiry_id='$id' LIMIT 1"
);

if ($duplicateQuery && mysqli_num_rows($duplicateQuery) > 0) {

    $existingClient = mysqli_fetch_assoc($duplicateQuery);

    $_SESSION['status'] =
        "Client already added for this inquiry! Client ID: " .
        $existingClient['id'];

    header("Location: service-done.php?id=$id");
    exit();
}

/* =========================================================
   POST VALUES
========================================================= */

$service = trim($_POST['service'] ?? '');
$service_date = trim($_POST['service_date'] ?? '');
$service_tc = trim($_POST['service_term_condition'] ?? '');

$total_amount = isset($_POST['total_amount'])
    ? (float) $_POST['total_amount']
    : 0;

$warranty = trim($_POST['warranty'] ?? '');
$service_covered = trim($_POST['service_covered'] ?? '');

$apply_gst = isset($_POST['apply_gst']) ? 1 : 0;

$gst_type = $_POST['gst_type'] ?? 'exclusive';

$gst_rate = isset($_POST['gst_rate'])
    ? (float) $_POST['gst_rate']
    : 18;

$gst_number = trim($_POST['gst_number'] ?? '');

$billing_address = trim($_POST['billing_address'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');

$gst_invoice_no = trim($_POST['gst_invoice_no'] ?? '');

$consumer_no = trim($_POST['consumer_no'] ?? '');
$subdivision = trim($_POST['subdivision'] ?? '');
$technical_name = trim($_POST['technical_name'] ?? '');

/* =========================================================
   TRADER / ADMIN DEFAULT VALUES
========================================================= */

if ($is_trader) {

    // Trader form does not contain these service fields.
    $service = 'Trader';

    // Use today's date when the trader form has no service date.
    $service_date = date('Y-m-d');

    $warranty = '';

    // Trader description is stored in service_covered.
}

/* =========================================================
   VALIDATION
========================================================= */

if ($total_amount <= 0) {

    $_SESSION['status'] = "Please enter a valid amount!";
    header("Location: service-done.php?id=$id&type=$client_type");
    exit();
}

if ($is_trader && $service_covered === '') {

    $_SESSION['status'] = "Please enter the trader description!";
    header("Location: service-done.php?id=$id&type=trader");
    exit();
}

if (!in_array($gst_type, ['exclusive', 'inclusive'], true)) {
    $gst_type = 'exclusive';
}

// Only allow the GST rates provided by the form.
if (!in_array($gst_rate, [5.0, 18.0], true)) {
    $gst_rate = 18.0;
}

if ($apply_gst == 0) {
    $gst_rate = 0;
}

/* =========================================================
   GST CALCULATION
========================================================= */

$gst_amount = 0;
$total_incl_gst = $total_amount;

if ($apply_gst == 1) {

    if ($gst_type === 'exclusive') {

        // Amount is before GST.
        $gst_amount = round(
            $total_amount * $gst_rate / 100,
            2
        );

        $total_incl_gst = round(
            $total_amount + $gst_amount,
            2
        );

    } elseif ($gst_type === 'inclusive') {

        // Amount already includes GST.
        $total_incl_gst = round($total_amount, 2);

        $basic_amount = round(
            $total_amount / (1 + ($gst_rate / 100)),
            2
        );

        $gst_amount = round(
            $total_incl_gst - $basic_amount,
            2
        );
    }
}

/* =========================================================
   ESCAPE DATA
========================================================= */

$cust_name = mysqli_real_escape_string(
    $conn, $inq['name'] ?? ''
);

$cust_phone = mysqli_real_escape_string(
    $conn, $inq['phone'] ?? ''
);

$cust_email = mysqli_real_escape_string(
    $conn, $inq['email'] ?? ''
);

$cust_address = mysqli_real_escape_string(
    $conn, $inq['address'] ?? ''
);

$service = mysqli_real_escape_string($conn, $service);
$service_date = mysqli_real_escape_string($conn, $service_date);
$service_tc = mysqli_real_escape_string($conn, $service_tc);
$warranty = mysqli_real_escape_string($conn, $warranty);
$service_covered = mysqli_real_escape_string($conn, $service_covered);

$billing_address = mysqli_real_escape_string($conn, $billing_address);
$shipping_address = mysqli_real_escape_string($conn, $shipping_address);
$gst_invoice_no = mysqli_real_escape_string($conn, $gst_invoice_no);
$gst_number = mysqli_real_escape_string($conn, $gst_number);

$consumer_no = mysqli_real_escape_string($conn, $consumer_no);
$subdivision = mysqli_real_escape_string($conn, $subdivision);
$technical_name = mysqli_real_escape_string($conn, $technical_name);

$gst_type = mysqli_real_escape_string($conn, $gst_type);

$added_by = $is_trader ? 'trader' : 'admin';

$added_by = mysqli_real_escape_string($conn, $added_by);

/* =========================================================
   INSERT CLIENT
========================================================= */

$query = "INSERT INTO tbl_client
(
    cust_name,
    cust_phone,
    cust_email,
    cust_address,
    service,
    service_date,
    service_term_condition,
    total_amount,
    gst_amount,
    warranty,
    service_covered,
    inquiry_id,
    apply_gst,
    gst_type,
    gst_rate,
    gst_number,
    billing_address,
    shipping_address,
    gst_invoice_no,
    consumer_no,
    subdivision,
    technical_name,
    added_by
)
VALUES
(
    '$cust_name',
    '$cust_phone',
    '$cust_email',
    '$cust_address',
    '$service',
    '$service_date',
    '$service_tc',
    '$total_amount',
    '$gst_amount',
    '$warranty',
    '$service_covered',
    '$id',
    '$apply_gst',
    '$gst_type',
    '$gst_rate',
    '$gst_number',
    '$billing_address',
    '$shipping_address',
    '$gst_invoice_no',
    '$consumer_no',
    '$subdivision',
    '$technical_name',
    '$added_by'
)";

/* =========================================================
   SAVE CLIENT AND PAYMENT
========================================================= */

if (mysqli_query($conn, $query)) {

    $client_id = mysqli_insert_id($conn);

    $paymentAmount = (float) $total_incl_gst;

    $paymentQuery = "
        INSERT INTO tbl_payment
        (
            client_id,
            total_amount,
            due_date
        )
        VALUES
        (
            '$client_id',
            '$paymentAmount',
            '$service_date'
        )
    ";

    if (mysqli_query($conn, $paymentQuery)) {

        $_SESSION['status'] =
            "Client Added Successfully! " .
            "GST: ₹" . number_format($gst_amount, 2) .
            " | Final Amount: ₹" .
            number_format($total_incl_gst, 2);

    } else {

        $_SESSION['status'] =
            "Client added, but payment entry failed!";
    }

    header("Location: view-client.php");
    exit();

} else {

    $_SESSION['status'] =
        "Failed to Add Client! " . mysqli_error($conn);

    header("Location: service-done.php?id=$id&type=$client_type");
    exit();
}

?>
