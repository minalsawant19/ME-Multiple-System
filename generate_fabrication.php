<?php
require_once 'authentication.php';
require_once 'dbcon.php';
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

/* =========================
   HELPER FUNCTIONS
========================= */

function h($value)
{
    return (string)($value ?? '');
}

function money($value)
{
    return number_format((float)$value, 2);
}

/* =========================
   GET QUOTATION ID
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    exit('Invalid fabrication ID');
}

/* =========================
   FETCH QUOTATION
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM tbl_quotation
     WHERE id = ?
       AND quotation_type = 'fabrication'"
);

if (!$stmt) {
    exit('Database query failed');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$q = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$q) {
    exit('Fabrication record not found');
}

/* =========================
   FETCH QUOTATION ITEMS
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT item_name, quantity, unit, rate, amount
     FROM tbl_quotation_items
     WHERE quotation_id = ?
     ORDER BY id"
);

if (!$stmt) {
    exit('Items query failed');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$items = [];

while ($item = mysqli_fetch_assoc($result)) {
    $items[] = $item;
}

mysqli_stmt_close($stmt);

/* =========================
   PDF TEMPLATE
========================= */

$template = __DIR__ . '/templates/febrication-Quotations.pdf';

if (!file_exists($template)) {
    exit('PDF template not found');
}

/* =========================
   CREATE PDF
========================= */

$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);

$pageCount = $pdf->setSourceFile($template);

if ($pageCount < 1) {
    exit('PDF template has no pages');
}

$tpl = $pdf->importPage(1);
$size = $pdf->getTemplateSize($tpl);

$pdf->AddPage(
    $size['orientation'],
    [$size['width'], $size['height']]
);

$pdf->useTemplate($tpl);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(210, 210, 210);
$pdf->SetLineWidth(0.2);

/* =========================
   QUOTATION HEADING
========================= */

$pdf->SetFont('Helvetica', 'BU', 16);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetXY(13, 20);

$pdf->Cell(
    $size['width'] - 26,
    8,
    'QUOTATION',
    0,
    1,
    'C'
);

/* =========================
   REF NO. AND DATE
========================= */

$pdf->SetFont('Helvetica', '', 11);

$pdf->SetXY(13, 27);

$pdf->Cell(
    90,
    5,
    'Ref. No: ' . h($q['ref_no']),
    0,
    0,
    'L'
);

$pdf->SetXY(145, 27);

$pdf->Cell(
    45,
    5,
    'Date: ' . h($q['quotation_date']),
    0,
    0,
    'R'
);
/* =========================
   CUSTOMER DETAILS
========================= */

$pdf->SetFont('Helvetica', '', 10);

$pdf->SetXY(13, 39);
$pdf->Cell(90, 4, 'To,', 0, 1, 'L');

$pdf->SetX(13);
$pdf->SetFont('Helvetica', 'B', 10);

$pdf->MultiCell(
    100,
    4,
    h($q['client_name']),
    0,
    'L'
);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(13);

$pdf->MultiCell(
    100,
    4,
    h($q['client_address']),
    0,
    'L'
);

if (!empty($q['client_phone'])) {
    $pdf->SetX(13);

    $pdf->Cell(
        100,
        4,
        'Phone: ' . h($q['client_phone']),
        0,
        1,
        'L'
    );
}

if (!empty($q['customer_gstin'])) {
    $pdf->SetX(13);

    $pdf->Cell(
        100,
        4,
        'GSTIN: ' . h($q['customer_gstin']),
        0,
        1,
        'L'
    );
}

/* =========================
   TABLE SETTINGS
========================= */

$pageWidth = $size['width'];

$width = 175;
$left = ($pageWidth - $width) / 2;

$srW   = 12;
$itemW = 75;
$qtyW  = 18;
$unitW = 20;
$rateW = 25;
$amtW  = 25;

$y = 75;

$headerH = 7;
$rowH = 10;

$blue = [220, 237, 247];
$grey = [244, 244, 244];

/* =========================
   TABLE HEADER FUNCTION
========================= */

$drawTableHeader = function () use (
    $pdf,
    $left,
    $srW,
    $itemW,
    $qtyW,
    $unitW,
    $rateW,
    $amtW,
    $headerH,
    $blue
) {
    $pdf->SetFillColor(
        $blue[0],
        $blue[1],
        $blue[2]
    );

     $pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.2);
    $pdf->SetFont('Helvetica', 'B', 9);

    $pdf->SetXY($left, $pdf->GetY());

    $pdf->Cell($srW, $headerH, 'Sr. No.', 1, 0, 'C', true);
    $pdf->Cell($itemW, $headerH, 'Item', 1, 0, 'C', true);
    $pdf->Cell($qtyW, $headerH, 'Qty', 1, 0, 'C', true);
    $pdf->Cell($unitW, $headerH, 'Unit', 1, 0, 'C', true);
    $pdf->Cell($rateW, $headerH, 'Rate', 1, 0, 'C', true);
    $pdf->Cell($amtW, $headerH, 'Amount', 1, 1, 'C', true);

    $currentY = $pdf->GetY();

    $pdf->SetDrawColor(170, 211, 232);
    $pdf->SetLineWidth(0.8);

    $pdf->Line(
        $left,
        $currentY,
        $left + $srW + $itemW + $qtyW + $unitW + $rateW + $amtW,
        $currentY
    );

    $pdf->SetLineWidth(0.2);

    $pdf->SetY($currentY + 1);
};

/* =========================
   DRAW FIRST TABLE HEADER
========================= */

$pdf->SetY($y);
$drawTableHeader();
$y = $pdf->GetY();


/* =========================
   ITEM ROWS WITH AUTO WRAPPING
========================= */

$lineHeight = 5;
$minRowH = 10;

foreach ($items as $index => $item) {

    $itemName = trim(h($item['item_name']));
    $textWidth = $itemW - 4;

    // Calculate wrapped text lines
    $words = preg_split('/\s+/', $itemName);
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {

        $testLine = ($currentLine === '')
            ? $word
            : $currentLine . ' ' . $word;

        if ($pdf->GetStringWidth($testLine) <= $textWidth) {
            $currentLine = $testLine;
            continue;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        // Break long words if required
        $currentLine = '';
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {

            if (
                $currentLine !== '' &&
                $pdf->GetStringWidth($currentLine . $char) > $textWidth
            ) {
                $lines[] = $currentLine;
                $currentLine = '';
            }

            $currentLine .= $char;
        }
    }

    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }

    if (empty($lines)) {
        $lines[] = '';
    }

    // Same height for all columns in this row
    $currentRowH = max(
        $minRowH,
        count($lines) * $lineHeight + 2
    );

    // Page break check
    if ($y + $currentRowH > 225) {

        $nextPage = $pdf->PageNo() + 1;

        if ($nextPage > $pageCount) {
            exit(
                'Items exceed template pages. ' .
                'Please add more pages to the PDF template.'
            );
        }

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate($pdf->importPage($nextPage));

        $pdf->SetY(75);
        $drawTableHeader();
        $y = $pdf->GetY();
    }

    // Alternate row background
    $fill = ($index % 2 === 1);

    if ($fill) {
        $pdf->SetFillColor(
            $grey[0],
            $grey[1],
            $grey[2]
        );
    }

    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Helvetica', '', 9);

    $x = $left;

    // Serial number
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $srW,
        $currentRowH,
        (string)($index + 1),
        1,
        0,
        'C',
        $fill
    );

    $x += $srW;

    // Item column border with fixed row height
    $pdf->Rect(
        $x,
        $y,
        $itemW,
        $currentRowH,
        $fill ? 'DF' : 'D'
    );

    // Print wrapped text inside item column
    $textY = $y + 1;

    foreach ($lines as $line) {

        $pdf->SetXY($x + 1.5, $textY);

        $pdf->Cell(
            $itemW - 3,
            $lineHeight,
            $line,
            0,
            0,
            'L'
        );

        $textY += $lineHeight;
    }

    $x += $itemW;

    // Quantity
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $qtyW,
        $currentRowH,
        h($item['quantity']),
        1,
        0,
        'C',
        $fill
    );

    $x += $qtyW;

    // Unit
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $unitW,
        $currentRowH,
        h($item['unit']),
        1,
        0,
        'C',
        $fill
    );

    $x += $unitW;

    // Price
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $rateW,
        $currentRowH,
        money($item['rate']),
        1,
        0,
        'C',
        $fill
    );

    $x += $rateW;

    // Amount
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        $amtW,
        $currentRowH,
        money($item['amount']),
        1,
        0,
        'R',
        $fill
    );

    // Next row position
    $y += $currentRowH;
}


/* =========================
   TOTALS
========================= */

// Check whether totals and terms fit on the current page.
// If not, use another available template page.

$estimatedExtraHeight = 8 * 4 + 5 + 8 + 2 + (4 * 6) + 25 + 10;

if ($y + 2 + $estimatedExtraHeight > 280) {

    $nextPage = $pdf->PageNo() + 1;

    if ($nextPage <= $pageCount) {

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 30;
    } else {
        exit(
            'Not enough template pages for totals and terms. ' .
            'Please add another page to the PDF template.'
        );
    }
}

$y += 2;


$drawTotal = function (
    $label,
    $value,
    $bold = false
) use (
    $pdf,
    &$y,
    $left,
    $srW,
    $itemW,
    $qtyW,
    $unitW,
    $rateW,
    $amtW
) {

    // Label Item column ke niche
    $labelX = $left + $srW;

    // Amount column ki exact position
    $amountX = $left + $srW + $itemW
        + $qtyW + $unitW + $rateW;

    $totalWidth = $srW + $itemW + $qtyW
        + $unitW + $rateW + $amtW;

    $pdf->SetDrawColor(210, 210, 210);
    $pdf->SetLineWidth(0.5);

    $pdf->Line(
        $left,
        $y,
        $left + $totalWidth,
        $y
    );

    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont(
        'Helvetica',
        $bold ? 'B' : '',
        10
    );

    // Subtotal, CGST, SGST, Grand Total
    $pdf->SetXY($labelX, $y + 1);

    $pdf->Cell(
        $itemW + $qtyW + $unitW + $rateW,
        7,
        $label,
        0,
        0,
        'L'
    );

    // Values Amount column ke niche right aligned
    $pdf->SetXY($amountX, $y + 1);

    $pdf->Cell(
        $amtW,
        7,
        $value,
        0,
        0,
        'R'
    );

    $y += 8;
};


$drawTotal(
    'Subtotal',
    money($q['subtotal'])
);

$drawTotal(
    'CGST',
    money($q['cgst_amount'])
);

$drawTotal(
    'SGST',
    money($q['sgst_amount'])
);

$drawTotal(
    'Grand Total',
    'Rs. ' . money($q['total_amount']),
    true
);

/* =========================
   AMOUNT IN WORDS
========================= */

$y += 5;

$words = 'Amount in Words: ' .
    h($q['amount_in_words']);

$pdf->SetFillColor(220, 237, 242);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 9);

$pdf->SetXY($left, $y);

$pdf->MultiCell(
    $width,
    5,
    $words,
    0,
    'C',
    true
);

$y = $pdf->GetY() + 7;

/* =========================
   TERMS & CONDITIONS
========================= */

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetXY($left, $y);

$pdf->Cell(
    $width,
    6,
    'Terms & Conditions',
    0,
    1,
    'L'
);

$y = $pdf->GetY() + 2;

$terms = [
    '1. Extra Work will be charged.',
    '2. NO CREDIT PLEASE',
    '3. Warranty: As mentioned on Product.',
    '4. Validity: This quotation is valid for 7 days.'
];

$pdf->SetFont('Helvetica', '', 9);

foreach ($terms as $term) {

    if ($y + 6 > 280) {

        $nextPage = $pdf->PageNo() + 1;

        if ($nextPage > $pageCount) {
            exit(
                'Not enough template pages for terms and conditions.'
            );
        }

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 25;
    }

    $pdf->SetXY($left + 3, $y);

    $pdf->MultiCell(
        $width - 6,
        5,
        $term,
        0,
        'L'
    );

    $y = $pdf->GetY() + 1;
}

/* =========================
   COMPUTER GENERATED NOTE
========================= */

$y += 15;

if ($y + 10 > 285) {

    $nextPage = $pdf->PageNo() + 1;

    if ($nextPage <= $pageCount) {

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 260;

    } else {
        $y = 280;
    }
}

$pdf->SetFont('Helvetica', 'I', 9);
$pdf->SetXY($left, $y);

$pdf->MultiCell(
    $width,
    5,
    '(This is Computer generated PDF, hence signature or stamp not required)',
    0,
    'C'
);

/* =========================
   SAVE PDF TO FOLDER
========================= */

$outDir = __DIR__ . '/upload-quotations/';

if (!is_dir($outDir)) {

    if (
        !mkdir($outDir, 0755, true) &&
        !is_dir($outDir)
    ) {
        exit('Unable to create upload folder');
    }
}

/* Safe filename */

$clientNameSafe = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $q['client_name'] ?? 'Client'
);

$fileName = 'Fabrication_' .
    $clientNameSafe . '_' . $id . '.pdf';

$outPath = $outDir . $fileName;

/* Save PDF */

$pdf->Output('F', $outPath);

if (!is_file($outPath)) {
    exit('PDF could not be saved');
}

/* =========================
   UPDATE DATABASE PATH
========================= */

$fileUrl = 'upload-quotations/' . $fileName;

$stmt = mysqli_prepare(
    $conn,
    "UPDATE tbl_quotation
     SET pdf_file = ?
     WHERE id = ?
       AND quotation_type = 'fabrication'"
);

if (!$stmt) {
    exit('Unable to prepare database update');
}

mysqli_stmt_bind_param(
    $stmt,
    'si',
    $fileUrl,
    $id
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    exit('Unable to update quotation PDF path');
}

mysqli_stmt_close($stmt);

/* =========================
   REDIRECT TO VIEW PAGE
========================= */

header('Location: view-quotations.php');
exit;
?>
