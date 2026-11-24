<?php
include('authentication.php');
include('dbcon.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request.');
}

/* =========================
   RECEIVE FORM DATA
========================= */

$inquiry_id = filter_input(
    INPUT_POST,
    'inquiry_id',
    FILTER_VALIDATE_INT
);

$date = trim($_POST['quotation_date'] ?? '');
$gstin = strtoupper(trim($_POST['customer_gstin'] ?? ''));

$names = $_POST['item_name'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$units = $_POST['unit'] ?? [];
$rates = $_POST['rate'] ?? [];

$cgst_rate = filter_var(
    $_POST['cgst_rate'] ?? null,
    FILTER_VALIDATE_FLOAT
);

$sgst_rate = filter_var(
    $_POST['sgst_rate'] ?? null,
    FILTER_VALIDATE_FLOAT
);

/* =========================
   BASIC VALIDATION
========================= */

if (
    !$inquiry_id ||
    $inquiry_id <= 0 ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
    !checkdate(
        (int)substr($date, 5, 2),
        (int)substr($date, 8, 2),
        (int)substr($date, 0, 4)
    ) ||
    $cgst_rate === false ||
    $sgst_rate === false ||
    $cgst_rate === null ||
    $sgst_rate === null ||
    !is_finite((float)$cgst_rate) ||
    !is_finite((float)$sgst_rate) ||
    $cgst_rate < 0 ||
    $sgst_rate < 0 ||
    $cgst_rate > 100 ||
    $sgst_rate > 100 ||
    !is_array($names) ||
    !is_array($quantities) ||
    !is_array($units) ||
    !is_array($rates) ||
    count($names) < 1 ||
    count($names) > 100 ||
    count($names) !== count($quantities) ||
    count($names) !== count($units) ||
    count($names) !== count($rates)
) {
    exit('Invalid fabrication details.');
}

/* =========================
   ALLOWED UNITS
========================= */

$allowedUnits = [
    'NOS',
    'KGS',
    'SQF',
    'RFT',
    'SQM',
    'UNT'
];

/* =========================
   AMOUNT IN WORDS
========================= */

function amountInWords($amount)
{
    $ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five',
        'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen',
        'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen',
        'Nineteen'
    ];

    $tens = [
        '', '', 'Twenty', 'Thirty', 'Forty',
        'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'
    ];

    $convert = function ($n) use (&$convert, $ones, $tens) {
        $n = (int)$n;

        if ($n < 20) {
            return $ones[$n];
        }

        if ($n < 100) {
            return $tens[(int)($n / 10)] .
                ($n % 10 ? ' ' . $ones[$n % 10] : '');
        }

        if ($n < 1000) {
            return $ones[(int)($n / 100)] . ' Hundred' .
                ($n % 100 ? ' ' . $convert($n % 100) : '');
        }

        return '';
    };

    $rupees = (int)floor($amount);
    $paise = (int)round(($amount - $rupees) * 100);

    if ($paise === 100) {
        $rupees++;
        $paise = 0;
    }

    $parts = [];

    foreach (
        [
            10000000 => 'Crore',
            100000 => 'Lakh',
            1000 => 'Thousand'
        ] as $div => $label
    ) {
        if ($rupees >= $div) {
            $parts[] = $convert((int)floor($rupees / $div))
                . ' ' . $label;

            $rupees %= $div;
        }
    }

    if ($rupees > 0) {
        $parts[] = $convert($rupees);
    }

    if (!$parts) {
        $parts[] = 'Zero';
    }

    $words = 'Rupees ' . implode(' ', $parts);

    if ($paise > 0) {
        $words .= ' and ' . $convert($paise) . ' Paise';
    }

    return $words . ' Only';
}

/* =========================
   SAVE FABRICATION
========================= */

try {
    mysqli_begin_transaction($conn);

    /* =========================
       FETCH INQUIRY
    ========================= */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT name, phone, email, address
         FROM tbl_inquiry
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);

    $inq = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$inq) {
        throw new Exception('Inquiry not found.');
    }

    /* =========================
       VALIDATE AND CALCULATE ITEMS
    ========================= */

    $items = [];
    $subtotal = 0;

    foreach ($names as $i => $item_name) {
        $item_name = trim((string)$item_name);

        if (
            !array_key_exists($i, $quantities) ||
            !array_key_exists($i, $units) ||
            !array_key_exists($i, $rates)
        ) {
            throw new Exception('Invalid item details.');
        }

        $unit = strtoupper(trim((string)$units[$i]));

        if (
            $item_name === '' ||
            !is_numeric($quantities[$i]) ||
            !is_numeric($rates[$i]) ||
            !in_array($unit, $allowedUnits, true)
        ) {
            throw new Exception('Invalid item name, unit, quantity or rate.');
        }

        $qty = (float)$quantities[$i];
        $rate = (float)$rates[$i];

        if (
            !is_finite($qty) ||
            !is_finite($rate) ||
            $qty <= 0 ||
            $rate < 0 ||
            $qty > 100000000 ||
            $rate > 100000000
        ) {
            throw new Exception('Invalid quantity or rate.');
        }

        $amount = round($qty * $rate, 2);
        $subtotal = round($subtotal + $amount, 2);

        $items[] = [
            'name' => $item_name,
            'qty' => $qty,
            'unit' => $unit,
            'rate' => $rate,
            'amount' => $amount
        ];
    }

    /* =========================
       GST CALCULATION
    ========================= */

    $cgst_amount = round(
        $subtotal * $cgst_rate / 100,
        2
    );

    $sgst_amount = round(
        $subtotal * $sgst_rate / 100,
        2
    );

    $grand_total = round(
        $subtotal + $cgst_amount + $sgst_amount,
        2
    );

    $words = amountInWords($grand_total);

    /* =========================
       GENERATE REFERENCE NUMBER
    ========================= */

    $refQuery = mysqli_query(
        $conn,
        "SELECT MAX(
            CAST(SUBSTRING_INDEX(ref_no, '-', -1) AS UNSIGNED)
         ) AS last_no
         FROM tbl_quotation
         WHERE ref_no REGEXP '^ME-QTY-[0-9]+$'"
    );

    $refRow = mysqli_fetch_assoc($refQuery);

    $newNo = ((int)($refRow['last_no'] ?? 0)) + 1;

    $ref_no = 'ME-QTY-' . max(29, $newNo);

    /* =========================
       SAVE QUOTATION HEADER
    ========================= */

    $name = $inq['name'] ?? '';
    $address = $inq['address'] ?? '';
    $phone = $inq['phone'] ?? '';
    $email = $inq['email'] ?? '';

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tbl_quotation
        (
            inquiry_id,
            ref_no,
            quotation_date,
            client_name,
            client_address,
            client_phone,
            client_email,
            total_amount,
            quotation_type,
            customer_gstin,
            subtotal,
            cgst_rate,
            sgst_rate,
            cgst_amount,
            sgst_amount,
            amount_in_words
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'fabrication', ?, ?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'issssssdsddddds',
        $inquiry_id,
        $ref_no,
        $date,
        $name,
        $address,
        $phone,
        $email,
        $grand_total,
        $gstin,
        $subtotal,
        $cgst_rate,
        $sgst_rate,
        $cgst_amount,
        $sgst_amount,
        $words
    );

    mysqli_stmt_execute($stmt);

    $quotation_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    /* =========================
       SAVE QUOTATION ITEMS
    ========================= */

    $item_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tbl_quotation_items
        (
            quotation_id,
            item_name,
            quantity,
            unit,
            rate,
            amount
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($items as $item) {
        mysqli_stmt_bind_param(
            $item_stmt,
            'isdsdd',
            $quotation_id,
            $item['name'],
            $item['qty'],
            $item['unit'],
            $item['rate'],
            $item['amount']
        );

        mysqli_stmt_execute($item_stmt);
    }

    mysqli_stmt_close($item_stmt);

    /* =========================
       COMMIT TRANSACTION
    ========================= */

    mysqli_commit($conn);

    /* =========================
       GENERATE PDF
    ========================= */

    header(
        'Location: generate_fabrication.php?id=' .
        $quotation_id
    );

    exit;

} catch (Throwable $e) {
    try {
        mysqli_rollback($conn);
    } catch (Throwable $rollbackError) {
        error_log(
            'Fabrication rollback error: ' .
            $rollbackError->getMessage()
        );
    }

    error_log(
        'Fabrication save error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo 'Fabrication could not be saved. Please check the server error log.';
}
?>
