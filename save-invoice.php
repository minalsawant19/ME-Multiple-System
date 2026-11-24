<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request');
}

function esc($conn, $value) {
    return mysqli_real_escape_string(
        $conn,
        (string)($value ?? '')
    );
}

/* ================= DELETE ================= */

if (($_POST['action'] ?? '') === 'delete') {

    $client_id = (int)($_POST['client_id'] ?? 0);
    $created_at = esc($conn, $_POST['created_at'] ?? '');

    $res = mysqli_query($conn, "
        SELECT pdf_file
        FROM tbl_invoice
        WHERE client_id = $client_id
        AND created_at = '$created_at'
        LIMIT 1
    ");

    if ($res && ($row = mysqli_fetch_assoc($res))) {

        if (
            !empty($row['pdf_file']) &&
            is_file($row['pdf_file'])
        ) {
            unlink($row['pdf_file']);
        }
    }

    mysqli_query($conn, "
        DELETE FROM tbl_invoice
        WHERE client_id = $client_id
        AND created_at = '$created_at'
    ") or die(mysqli_error($conn));

    header("Location: view-invoice.php?msg=Invoice+Deleted");
    exit;
}


/* ================= INPUTS ================= */

$client_id = (int)($_POST['client_id'] ?? 0);
$group_id = (int)($_POST['group_id'] ?? 0);

$inquiry_id = $_POST['inquiry_id'] ?? null;

$invoice_date = $_POST['invoice_date'] ?? '';
$due_date = $_POST['due_date'] ?? '';

$is_edit = ($_POST['edit_mode'] ?? '0') === '1';

$gst_invoice_no = $_POST['gst_invoice_no'] ?? '';
$created_at = $_POST['created_at'] ?? '';

$item_name = $_POST['item_name'] ?? [];
$hsn = $_POST['hsn'] ?? [];
$qty = $_POST['qty'] ?? [];

$item_total = $_POST['item_total'] ?? [];
$gst_type = $_POST['gst_type'] ?? [];
$gst_mode = $_POST['gst_mode'] ?? [];

$service_charge = $_POST['service_charge'] ?? [];
$posted_rate = $_POST['rate'] ?? [];


if (!$client_id || !$invoice_date || !$due_date) {
    die('Required invoice details missing');
}

if (!is_array($item_name) || count($item_name) === 0) {
    die('Please add at least one item');
}


/* ================= CLIENT ================= */

$clientRes = mysqli_query($conn, "
    SELECT *
    FROM tbl_client
    WHERE id = $client_id
    LIMIT 1
");

$client = mysqli_fetch_assoc($clientRes);

if (!$client) {
    die('Client not found');
}

$is_trader = strtolower(
    trim($client['added_by'] ?? '')
) === 'trader';


$cust_name = $client['cust_name'] ?? '';
$cust_phone = $client['cust_phone'] ?? '';
$cust_email = $client['cust_email'] ?? '';

$billing_address = $client['billing_address'] ?? '';
$shipping_address = $client['shipping_address'] ?? '';


/* ================= EDIT / NEW ================= */

$pdf_file = '';

if ($is_edit) {

    if ($gst_invoice_no === '' || $created_at === '') {
        die('Edit data missing');
    }

    $invoice_no_sql = esc($conn, $gst_invoice_no);
    $created_at_sql = esc($conn, $created_at);

    $check = mysqli_query($conn, "
        SELECT pdf_file
        FROM tbl_invoice
        WHERE client_id = $client_id
        AND gst_invoice_no = '$invoice_no_sql'
        AND created_at = '$created_at_sql'
        LIMIT 1
    ");

    $existing = $check
        ? mysqli_fetch_assoc($check)
        : null;

    if (!$existing) {
        die('Invoice not found');
    }

    $pdf_file = $existing['pdf_file'] ?? '';

    mysqli_query($conn, "
        DELETE FROM tbl_invoice
        WHERE client_id = $client_id
        AND gst_invoice_no = '$invoice_no_sql'
        AND created_at = '$created_at_sql'
    ") or die(mysqli_error($conn));

} else {

    $r = mysqli_query($conn, "
        SELECT MAX(CAST(gst_invoice_no AS UNSIGNED)) AS last
        FROM tbl_invoice
    ");

    $row = mysqli_fetch_assoc($r);

    $gst_invoice_no = (int)($row['last'] ?? 0) + 1;

    $created_at = date('Y-m-d H:i:s');
}


/* ================= CALCULATE ITEMS ================= */

$subtotal = 0;
$total_cgst = 0;
$total_sgst = 0;
$grand_total = 0;

$items_to_insert = [];

for ($i = 0; $i < count($item_name); $i++) {

    $name = trim((string)($item_name[$i] ?? ''));

    if ($name === '') {
        continue;
    }

    $qty_val = (float)($qty[$i] ?? 0);

    if ($qty_val <= 0) {
        die('Invalid Qty for item: ' . htmlspecialchars($name));
    }


    /* GST RATE */

    $gst = (float)($gst_type[$i] ?? 0);

    if ($gst == 18) {

        $cgst_per = 9;
        $sgst_per = 9;

    } elseif ($gst == 5) {

        $cgst_per = 2.5;
        $sgst_per = 2.5;

    } else {

        die('Invalid GST Rate for item: ' . htmlspecialchars($name));

    }


    /* GST MODE */

    $mode = strtolower(
        trim($gst_mode[$i] ?? '')
    );

    if (!in_array($mode, ['inclusive', 'exclusive'], true)) {
        die('Invalid GST Mode for item: ' . htmlspecialchars($name));
    }


    /* ================= TRADER ================= */

    if ($is_trader) {

        /*
         * Trader:
         * service_charge = per unit amount
         */

        $raw_charge = $service_charge[$i]
            ?? $posted_rate[$i]
            ?? '';

        if (
            $raw_charge === '' ||
            !is_numeric($raw_charge)
        ) {
            die('Service Charge missing for item: ' . htmlspecialchars($name));
        }

        $rate = (float)$raw_charge;

        if ($rate < 0) {
            die('Invalid Rate');
        }

        $service_charge_value = round($rate, 2);

        // Quantity x per-unit rate
        $entered_total = $qty_val * $rate;


    } else {

        /*
         * Normal Client:
         * item_total = amount entered
         */

        $raw_total = $item_total[$i] ?? '';

        if (
            $raw_total === '' ||
            !is_numeric($raw_total)
        ) {
            die('Item Amount missing for item: ' . htmlspecialchars($name));
        }

        $entered_total = (float)$raw_total;

        if ($entered_total < 0) {
            die('Invalid Item Total');
        }

        $service_charge_value = 0;

    }


    /* ================= GST CALCULATION ================= */

    if ($mode === 'exclusive') {

        /*
         * GST EXCLUSIVE
         *
         * Entered amount does not include GST.
         *
         * Example:
         * 1000 + 18% = 1180
         */

        $base_amount = $entered_total;

        $total_tax = $base_amount * $gst / 100;

    } else {

        /*
         * GST INCLUSIVE
         *
         * Entered amount already includes GST.
         *
         * Example:
         * 1180 includes 18% GST.
         */

        $base_amount = $entered_total / (1 + ($gst / 100));

        $total_tax = $entered_total - $base_amount;

    }


    /* ================= CGST / SGST ================= */

    $base_amount = round($base_amount, 2);

   // Calculate total GST first
$total_tax = round(
    $base_amount * $gst / 100,
    2
);

// Split GST equally into CGST and SGST
$cgst_amt = round($total_tax / 2, 2);

$sgst_amt = round($total_tax - $cgst_amt, 2);

// Final amount
$total_amt = round(
    $base_amount + $total_tax,
    2
);

    /* ================= RATE ================= */

    $rate = round(
        $base_amount / $qty_val,
        2
    );


    /* ================= TOTALS ================= */

    $subtotal += $base_amount;

    $total_cgst += $cgst_amt;

    $total_sgst += $sgst_amt;

    $grand_total += $total_amt;


    /* ================= STORE ITEM ================= */

    $items_to_insert[] = [

        'item_name' => $name,

        'hsn' => $hsn[$i] ?? '',

        'qty' => $qty_val,

        'rate' => $rate,

        'service_charge' => $service_charge_value,

        'gst_mode' => $mode,

        'base_amount' => $base_amount,

        'cgst_per' => $cgst_per,

        'cgst_amt' => $cgst_amt,

        'sgst_per' => $sgst_per,

        'sgst_amt' => $sgst_amt,

        'item_total' => $total_amt

    ];

}


if (!$items_to_insert) {
    die('Please enter at least one valid item');
}


/* ================= FINAL TOTALS ================= */

$subtotal = round($subtotal, 2);

$total_cgst = round($total_cgst, 2);

$total_sgst = round($total_sgst, 2);

$grand_total = round($grand_total, 2);


/* ================= SQL VALUES ================= */

$invoice_no_sql = esc($conn, $gst_invoice_no);

$created_at_sql = esc($conn, $created_at);

$invoice_date_sql = esc($conn, $invoice_date);

$due_date_sql = esc($conn, $due_date);


$inquiry_sql = (
    $inquiry_id !== null &&
    $inquiry_id !== ''
)
    ? (string)(int)$inquiry_id
    : 'NULL';


$cust_name_sql = esc($conn, $cust_name);

$cust_phone_sql = esc($conn, $cust_phone);

$cust_email_sql = esc($conn, $cust_email);

$billing_sql = esc($conn, $billing_address);

$shipping_sql = esc($conn, $shipping_address);

$pdf_sql = esc($conn, $pdf_file);


/* ================= INSERT ITEMS ================= */

foreach ($items_to_insert as $item) {

    $name_sql = esc($conn, $item['item_name']);

    $hsn_sql = esc($conn, $item['hsn']);

    $qty_sql = $item['qty'];

    $rate_sql = $item['rate'];

    $service_charge_sql = $item['service_charge'];

    $gst_mode_sql = esc($conn, $item['gst_mode']);

    $base_sql = $item['base_amount'];

    $cgst_per_sql = $item['cgst_per'];

    $cgst_amt_sql = $item['cgst_amt'];

    $sgst_per_sql = $item['sgst_per'];

    $sgst_amt_sql = $item['sgst_amt'];

    $item_total_sql = $item['item_total'];


    mysqli_query($conn, "

        INSERT INTO tbl_invoice (

            client_id,
            group_id,
            inquiry_id,
            gst_invoice_no,

            invoice_date,
            due_date,

            cust_name,
            cust_phone,
            cust_email,

            billing_address,
            shipping_address,

            item_name,
            hsn,
            qty,
            rate,
            service_charge,

            gst_mode,

            base_amount,

            cgst_per,
            cgst_amt,

            sgst_per,
            sgst_amt,

            item_total,

            subtotal,
            total_cgst,
            total_sgst,
            grand_total,

            pdf_file,
            created_at

        ) VALUES (

            $client_id,
            $group_id,
            $inquiry_sql,
            '$invoice_no_sql',

            '$invoice_date_sql',
            '$due_date_sql',

            '$cust_name_sql',
            '$cust_phone_sql',
            '$cust_email_sql',

            '$billing_sql',
            '$shipping_sql',

            '$name_sql',
            '$hsn_sql',
            '$qty_sql',
            '$rate_sql',
            '$service_charge_sql',

            '$gst_mode_sql',

            '$base_sql',

            '$cgst_per_sql',
            '$cgst_amt_sql',

            '$sgst_per_sql',
            '$sgst_amt_sql',

            '$item_total_sql',

            '$subtotal',
            '$total_cgst',
            '$total_sgst',
            '$grand_total',

            '$pdf_sql',
            '$created_at_sql'

        )

    ") or die(mysqli_error($conn));

}


/* ================= REDIRECT TO PDF ================= */

if ($is_trader) {

    $pdfPage = 'generate-trader-invoice.php';

} else {

    $pdfPage = 'generate-invoice.php';

}

header(
    "Location: " . $pdfPage .
    "?client_id=" . $client_id .
    "&created_at=" . urlencode($created_at)
);

exit;

?>
