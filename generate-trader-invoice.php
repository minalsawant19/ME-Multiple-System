<?php
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

mysqli_set_charset($conn, 'utf8mb4');

$companyName = 'M E MULTIPLE SYSTEM';
$companyGST  = '27BEJPM5990K1ZR';
$bankAccount = '877020110000167';
$bankIFSC    = 'BKID0008770';
$bankName    = 'Bank of India';
$bankBranch  = 'Mansar';

$lavender = [197, 217, 165];
$lightLavender = [226, 239, 218];
$lineColor = [80, 100, 65];

if (!isset($_GET['client_id'], $_GET['created_at'])) {
    http_response_code(400);
    exit('Client ID or timestamp missing');
}

$client_id = (int) $_GET['client_id'];
$created_at = trim((string) $_GET['created_at']);

if ($client_id <= 0 || $created_at === '') {
    http_response_code(400);
    exit('Invalid invoice details');
}

/* FETCH INVOICE ITEMS */
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_invoice
    WHERE client_id = ? AND created_at = ?
    ORDER BY id ASC
");

if (!$stmt) {
    exit('Invoice query could not be prepared.');
}

mysqli_stmt_bind_param($stmt, 'is', $client_id, $created_at);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (!$items) {
    http_response_code(404);
    exit('Invoice not found');
}

$first = $items[0];
$client_name = $first['cust_name'] ?? 'Customer';

/* CUSTOMER GST */
$stmt = mysqli_prepare($conn, "
    SELECT gst_number
    FROM tbl_client
    WHERE id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, 'i', $client_id);
mysqli_stmt_execute($stmt);
$gstData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$gst_number = $gstData['gst_number'] ?? '';

/* INVOICE NUMBER */
$gst_invoice_no = 0;

foreach ($items as $item) {
    if (!empty($item['gst_invoice_no']) &&
        (int)$item['gst_invoice_no'] > 0) {
        $gst_invoice_no = (int)$item['gst_invoice_no'];
        break;
    }
}

if ($gst_invoice_no <= 0) {
    mysqli_begin_transaction($conn);

    try {
        $r = mysqli_query($conn, "
            SELECT MAX(CAST(gst_invoice_no AS UNSIGNED)) AS last_inv
            FROM tbl_invoice
            WHERE gst_invoice_no IS NOT NULL
              AND gst_invoice_no <> ''
            FOR UPDATE
        ");

        if (!$r) {
            throw new Exception(mysqli_error($conn));
        }

        $numberRow = mysqli_fetch_assoc($r);
        $gst_invoice_no = (int)($numberRow['last_inv'] ?? 0) + 1;

        $stmt = mysqli_prepare($conn, "
            UPDATE tbl_invoice
            SET gst_invoice_no = ?
            WHERE client_id = ? AND created_at = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            'iis',
            $gst_invoice_no,
            $client_id,
            $created_at
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        exit('Invoice number error: ' . $e->getMessage());
    }
}

/* TOTALS AND TAX ANALYSIS */
$grandTotal = 0.0;
$totalBase = 0.0;
$totalCGST = 0.0;
$totalSGST = 0.0;
$totalQty = 0.0;
$taxGroups = [];

foreach ($items as $item) {
    $base = (float)($item['base_amount'] ?? 0);
    $cgst = (float)($item['cgst_amt'] ?? 0);
    $sgst = (float)($item['sgst_amt'] ?? 0);
    $qty = (float)($item['qty'] ?? 0);
    $cgstPer = (float)($item['cgst_per'] ?? 0);
    $sgstPer = (float)($item['sgst_per'] ?? 0);
    $hsn = trim((string)($item['hsn'] ?? 'N/A'));

    $grandTotal += (float)($item['item_total'] ?? 0);
    $totalBase += $base;
    $totalCGST += $cgst;
    $totalSGST += $sgst;
    $totalQty += $qty;

    $key = $hsn . '|' . $cgstPer . '|' . $sgstPer;

    if (!isset($taxGroups[$key])) {
        $taxGroups[$key] = [
            'hsn' => $hsn,
            'taxable' => 0.0,
            'cgst_per' => $cgstPer,
            'cgst_amt' => 0.0,
            'sgst_per' => $sgstPer,
            'sgst_amt' => 0.0
        ];
    }

    $taxGroups[$key]['taxable'] += $base;
    $taxGroups[$key]['cgst_amt'] += $cgst;
    $taxGroups[$key]['sgst_amt'] += $sgst;
}

$grandTotal = round($grandTotal, 2);
$totalBase = round($totalBase, 2);
$totalCGST = round($totalCGST, 2);
$totalSGST = round($totalSGST, 2);
$totalQty = round($totalQty, 3);

function invMoney($value) {
    return number_format((float)$value, 2, '.', ',');
}

function amountInWords($amount) {
    $amount = round((float)$amount, 2);
    $rupees = (int)floor($amount);
    $paise = (int)round(($amount - $rupees) * 100);

    $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three',
        4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven',
        8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty',
        5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy',
        8 => 'Eighty', 9 => 'Ninety'
    ];

    $convert = function ($n) use (&$convert, $ones, $tens) {
        $n = (int)$n;

        if ($n === 0) return '';
        if ($n < 20) return $ones[$n];

        if ($n < 100) {
            return trim(
                $tens[(int)floor($n / 10)] . ' ' . $ones[$n % 10]
            );
        }

        if ($n < 1000) {
            return trim(
                $ones[(int)floor($n / 100)] .
                ' Hundred ' . $convert($n % 100)
            );
        }

        foreach ([
            10000000 => 'Crore',
            100000 => 'Lakh',
            1000 => 'Thousand'
        ] as $divisor => $label) {
            if ($n >= $divisor) {
                return trim(
                    $convert((int)floor($n / $divisor)) .
                    ' ' . $label . ' ' . $convert($n % $divisor)
                );
            }
        }

        return '';
    };

    $words = $convert($rupees);
    if ($words === '') $words = 'Zero';

    $words .= ' Rupees';

    if ($paise > 0) {
        $paiseWords = $convert($paise);

        if ($paiseWords !== '') {
            $words .= ' and ' . $paiseWords . ' Paise';
        }
    }

    return $words . ' Only';
}

/* PDF CLASS */
class InvoicePDF extends Fpdi {
    public $companyName = '';
    public $companyGST = '';
    public $lavender = [197, 217, 165];
    public $lightLavender = [226, 239, 218];
    public $lineColor = [80, 100, 65];
    public $templateId = 0;

    public function Header() {
        // Company header is provided by the template PDF.
    }

    public function drawCompanyHeader($continued = false) {
        if ($this->templateId > 0) {
            $this->useTemplate($this->templateId, 0, 0);
        }
    }

    public function drawItemHeader() {
        $headers = [
            ['Sl', 8],
            ['Description of Goods', 65],
            ['HSN/SAC', 22],
            ['Quantity', 25],
            ['Rate', 25],
            ['per', 12],
            ['Amount', 33]
        ];

        $this->SetFillColor(...$this->lavender);
        $this->SetDrawColor(...$this->lineColor);
        $this->SetFont('Times', 'B', 8);

        foreach ($headers as $h) {
            $this->Cell($h[1], 8, $h[0], 1, 0, 'C', true);
        }

        $this->Ln();
    }

    public function drawSectionTitle($title, $x, $y, $w) {
        $this->SetXY($x, $y);
        $this->SetFillColor(...$this->lavender);
        $this->SetFont('Times', 'B', 9);
        $this->Cell($w, 6, $title, 1, 1, 'C', true);
    }

    public function NbLines($w, $txt) {
        $cw = $this->CurrentFont['cw'];

        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);

        if ($nb > 0 && $s[$nb - 1] === "\n") $nb--;

        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];

            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }

            if ($c === ' ') $sep = $i;
            $l += $cw[$c] ?? 0;

            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) $i++;
                } else {
                    $i = $sep + 1;
                }

                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }

        return $nl;
    }
}


/* INITIALIZE PDF WITH TEMPLATE */

$template = __DIR__ . '/templates/Invoice-ME7 (3).pdf';

if (!is_file($template) || !is_readable($template)) {
    http_response_code(500);
    exit('Template PDF missing or unreadable: ' . $template);
}

$pdf = new InvoicePDF('P', 'mm', 'A4');

$pdf->companyName = $companyName;
$pdf->companyGST = $companyGST;
$pdf->lavender = $lavender;
$pdf->lightLavender = $lightLavender;
$pdf->lineColor = $lineColor;

$pdf->SetMargins(10, 8, 10);
$pdf->SetAutoPageBreak(false);

$pdf->SetTitle('Tax Invoice - ' . $gst_invoice_no);
$pdf->SetAuthor($companyName);

/* Load template PDF */
$pageCount = $pdf->setSourceFile($template);

if ($pageCount < 1) {
    exit('Template PDF has no pages.');
}

/* Import first page */
$templateId = $pdf->importPage(1);
$templateSize = $pdf->getTemplateSize($templateId);

/* Add page matching template orientation and size */
$pdf->AddPage(
    $templateSize['orientation'],
    [$templateSize['width'], $templateSize['height']]
);

/* Apply template at its original size */
$pdf->useTemplate($templateId, 0, 0);

$pdf->templateId = $templateId;



/* ---------------- INVOICE META AND BILLING ---------------- */
$invoiceDate = !empty($first['invoice_date']) ? date('d-M-Y', strtotime($first['invoice_date'])) : '';
$dueDate = !empty($first['due_date']) ? date('d-M-Y', strtotime($first['due_date'])) : '';

$pdf->SetDrawColor($lineColor[0], $lineColor[1], $lineColor[2]);
$pdf->SetFont('Times', '', 8.5);
$pdf->SetXY(10, 35);
$pdf->Cell(95, 5, 'Invoice No.: ' . $gst_invoice_no, 1, 0, 'L');
$pdf->Cell(95, 5, 'Dated: ' . $invoiceDate, 1, 1, 'L');
$pdf->SetX(10);
$pdf->Cell(95, 5, 'Due Date: ' . $dueDate, 1, 0, 'L');
$pdf->Cell(95, 5, 'Company GST : ' . $companyGST, 1, 1, 'L');

$billing = strtoupper($first['cust_name'] ?? '') . "\n"
    . ($first['billing_address'] ?? '') . "\n"
    . 'Phone: ' . ($first['cust_phone'] ?? '') . "\n"
    . ($first['cust_email'] ?? '') . "\n"
    . 'State: Maharashtra | Code: 27' . "\n"
    . 'GSTIN/UIN: ' . ($gst_number ?: 'N/A');

$shipping = strtoupper($first['cust_name'] ?? '') . "\n"
    . ($first['shipping_address'] ?? '') . "\n"
    . 'Phone: ' . ($first['cust_phone'] ?? '') . "\n"
    . ($first['cust_email'] ?? '') . "\n"
    . 'State: Maharashtra | Code: 27' . "\n"
    . 'GSTIN/UIN: ' . ($gst_number ?: 'N/A');

$pdf->SetFillColor($lightLavender[0], $lightLavender[1], $lightLavender[2]);
$pdf->SetFont('Times', 'B', 8.5);
$pdf->SetXY(10, 47);
$pdf->Cell(95, 5, 'Buyer (Bill to)', 1, 0, 'L', true);
$pdf->Cell(95, 5, 'Consignee (Ship to)', 1, 1, 'L', true);

$pdf->SetFont('Times', '', 7.8);
$pdf->SetXY(10, 52);
$pdf->MultiCell(95, 3.5, $billing, 0, 'L');
$pdf->SetXY(105, 52);
$pdf->MultiCell(95, 3.5, $shipping, 0, 'L');
$pdf->Rect(10, 47, 95, 27);
$pdf->Rect(105, 47, 95, 27);


$pdf->SetXY(10, 84);
$pdf->drawItemHeader();
$y = $pdf->GetY();

$tableStartY = 84; // Complete table ki starting position
/* ---------------- ITEM ROWS ---------------- */
$sr = 1;
foreach ($items as $item) {
    $description = trim((string)($item['item_name'] ?? ''));
    $hsn = (string)($item['hsn'] ?? '');
    $qty = (float)($item['qty'] ?? 0);
    $base = (float)($item['base_amount'] ?? 0);
    $rate = $qty != 0 ? $base / $qty : 0;
    $amount = (float)($item['item_total'] ?? 0);

    $qtyText = number_format($qty, 3, '.', '') . ' NOS';
    $pdf->SetFont('Times', '', 8);
    $lines = $pdf->NbLines(63, $description);
    $rowH = max(7, $lines * 3.7 + 2);

    if ($y + $rowH > 150) {
        $pdf->AddPage();
        $pdf->drawCompanyHeader(true);
        $pdf->SetXY(10, 38);
        $pdf->SetFont('Times', 'B', 9);
        $pdf->Cell(190, 5, 'Invoice No.: ' . $gst_invoice_no, 0, 1, 'R');
        $pdf->SetXY(10, 45);
        $pdf->drawItemHeader();
        $y = $pdf->GetY();
    }

    $x = 10;
    $pdf->SetXY($x, $y); $pdf->Cell(8, $rowH, (string)$sr, 1, 0, 'C'); $x += 8;

    $pdf->SetXY($x, $y);
    $pdf->MultiCell(65, 3.7, $description, 0, 'C');
    $pdf->Rect($x, $y, 65, $rowH); $x += 65;

    $pdf->SetXY($x, $y); $pdf->Cell(22, $rowH, $hsn, 1, 0, 'C'); $x += 22;
    $pdf->SetXY($x, $y); $pdf->Cell(25, $rowH, $qtyText, 1, 0, 'C'); $x += 25;
    $pdf->SetXY($x, $y); $pdf->Cell(25, $rowH, invMoney($rate), 1, 0, 'C'); $x += 25;
    $pdf->SetXY($x, $y); $pdf->Cell(12, $rowH, 'NOS', 1, 0, 'C'); $x += 12;
    $pdf->SetXY($x, $y); $pdf->Cell(33, $rowH, invMoney($base), 1, 0, 'C');

    $y += $rowH;
    $sr++;
}

/* ---------------- SUMMARY / TAX ANALYSIS ---------------- */
$summaryHeight = 6 * (count($taxGroups) + 6) + 25;
if ($y + $summaryHeight > 275) {
    $pdf->AddPage();
    $pdf->drawCompanyHeader(true);
    $pdf->SetXY(10, 38);
    $pdf->SetFont('Times', 'B', 9);
    $pdf->Cell(190, 5, 'Invoice No.: ' . $gst_invoice_no . ' - Summary', 0, 1, 'R');
    $y = 45;
} else {
    $y += 2;
}

// Totals: labels on the left, values on the right
$pdf->SetFont('Times', '', 8.5);

// Base Amount
$pdf->SetXY(10, $y);
$pdf->Cell(120, 5, '', 0, 0, 'C');
$pdf->Cell(60, 5, invMoney($totalBase), 0, 1, 'R');
$y += 5;

// OUTPUT SGST
$pdf->SetXY(10, $y);
$pdf->Cell(120, 5, 'SGST', 0, 0, 'C');
$pdf->Cell(60, 5, invMoney($totalSGST), 0, 1, 'R');
$y += 5;

// OUTPUT CGST
$pdf->SetXY(10, $y);
$pdf->Cell(120, 5, 'CGST', 0, 0, 'C');
$pdf->Cell(60, 5, invMoney($totalCGST), 0, 1, 'R');
$y += 5;


$pdf->SetFillColor($lavender[0], $lavender[1], $lavender[2]);
$pdf->SetDrawColor($lineColor[0], $lineColor[1], $lineColor[2]);
$pdf->SetFont('Times', 'B', 9);
$pdf->SetXY(10, $y);

// Sl + Description + HSN = 95 mm
$pdf->Cell(95, 7, 'Total', 1, 0, 'C', true);

// Quantity ke niche Total Qty = 25 mm
$pdf->Cell(25, 7, number_format($totalQty, 3) . ' NOS', 1, 0, 'C', true);

// Rate + per = 37 mm
$pdf->Cell(37, 7, '', 1, 0, 'C', true);

// Amount ke niche Grand Total = 33 mm
$pdf->Cell(33, 7, 'Rs. ' . invMoney($grandTotal), 1, 1, 'C', true);

$y += 7;

// Amount Chargeable ke baad table ka bottom position
$pdf->SetFont('Times', 'B', 8.5);
$pdf->SetXY(10, $y);

$pdf->MultiCell(
    190,
    5,
    'Amount Chargeable (in words): INR ' . amountInWords($grandTotal),
    1,
    'C',
    true
);

$y = $pdf->GetY();

// Complete item + summary table ka outer border
$pdf->SetDrawColor(
    $lineColor[0],
    $lineColor[1],
    $lineColor[2]
);

$pdf->Rect(
    10,
    $tableStartY,
    190,
    $y - $tableStartY
);

$y += 2;



$pdf->drawSectionTitle('Tax Analysis', 10, $y, 190);
$y = $pdf->GetY();

$taxHeaders = [
    ['HSN/SAC', 28], ['Taxable Value', 32], ['CGST Rate', 22],
    ['CGST Amount', 27], ['SGST Rate', 22], ['SGST Amount', 27],
    ['Total Tax', 32]
];
$pdf->SetXY(10, $y);
$pdf->SetFillColor($lightLavender[0], $lightLavender[1], $lightLavender[2]);
$pdf->SetFont('Times', 'B', 7.2);
foreach ($taxHeaders as $h) {
    $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
}
$pdf->Ln();
$y = $pdf->GetY();

$pdf->SetFont('Times', '', 7.5);
foreach ($taxGroups as $group) {
    if ($y + 6 > 278) {
        $pdf->AddPage();
        $pdf->drawCompanyHeader(true);
        $pdf->SetXY(10, 38);
        $pdf->SetFont('Times', 'B', 9);
        $pdf->Cell(190, 5, 'Invoice No.: ' . $gst_invoice_no . ' - Tax Analysis Continued', 0, 1, 'R');
        $pdf->SetXY(10, 45);
        foreach ($taxHeaders as $h) {
            $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', '', 7.5);
    }

    $pdf->SetXY(10, $y);
    $pdf->Cell(28, 6, $group['hsn'], 1, 0, 'C');
    $pdf->Cell(32, 6, invMoney($group['taxable']), 1, 0, 'C');
    $pdf->Cell(22, 6, rtrim(rtrim(number_format($group['cgst_per'], 2), '0'), '.') . '%', 1, 0, 'C');
    $pdf->Cell(27, 6, invMoney($group['cgst_amt']), 1, 0, 'C');
    $pdf->Cell(22, 6, rtrim(rtrim(number_format($group['sgst_per'], 2), '0'), '.') . '%', 1, 0, 'C');
    $pdf->Cell(27, 6, invMoney($group['sgst_amt']), 1, 0, 'C');
    $pdf->Cell(32, 6, invMoney($group['cgst_amt'] + $group['sgst_amt']), 1, 1, 'C');
    $y = $pdf->GetY();
}

$pdf->SetFont('Times', 'B', 7.5);
$pdf->SetXY(10, $y);
$pdf->Cell(28, 6, 'Total', 1, 0, 'C');
$pdf->Cell(32, 6, invMoney($totalBase), 1, 0, 'C');
$pdf->Cell(22, 6, '', 1, 0);
$pdf->Cell(27, 6, invMoney($totalCGST), 1, 0, 'C');
$pdf->Cell(22, 6, '', 1, 0);
$pdf->Cell(27, 6, invMoney($totalSGST), 1, 0, 'C');
$pdf->Cell(32, 6, invMoney($totalCGST + $totalSGST), 1, 1, 'C');
$y = $pdf->GetY() + 2;

/* ---------------- TAX AMOUNT IN WORDS ---------------- */
$pdf->SetFont('Times', 'B', 7.5);
$pdf->SetXY(10, $y);
$pdf->MultiCell(190, 4, 'Tax Amount (in words): INR ' . amountInWords($totalCGST + $totalSGST), 0, 'C');
$y = $pdf->GetY() + 1;

/* ---------------- BANK DETAILS / DECLARATION / SIGNATURE ---------------- */
if ($y + 48 > 286) {
    $pdf->AddPage();
    $pdf->drawCompanyHeader(true);
    $pdf->SetXY(10, 38);
    $pdf->SetFont('Times', 'B', 9);
    $pdf->Cell(190, 5, 'Invoice No.: ' . $gst_invoice_no . ' - Details', 0, 1, 'R');
    $y = 45;
}

$pdf->drawSectionTitle("Company's Bank Details", 10, $y, 190);
$y = $pdf->GetY();

$pdf->SetFont('Times', '', 8);
$pdf->SetXY(10, $y);
$bankText = "A/c Holder's Name: " . $companyName . "\n"
    . "Bank Name: " . $bankName . "\n"
    . "A/c No.: " . $bankAccount . "\n"
    . "Branch & IFS Code: " . $bankBranch . " & " . $bankIFSC;
$pdf->MultiCell(190, 4, $bankText, 1, 'C');
$y = $pdf->GetY() + 2;

$pdf->SetFont('Times', 'B', 8.5);
$pdf->SetXY(10, $y);
$pdf->Cell(190, 5, 'Declaration', 0, 1, 'L');
$y = $pdf->GetY();

$pdf->SetFont('Times', '', 7.8);
$pdf->SetXY(10, $y);
$pdf->MultiCell(120, 4, 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.', 0, 'L');

$signY = max($pdf->GetY() + 1, $y);
$pdf->SetFont('Times', 'B', 8.5);
$pdf->SetXY(5, $signY);
$pdf->Cell(70, 5, 'For ' . $companyName, 0, 1, 'C');

$stampPath = __DIR__ . '/img/stamp.png';
if (file_exists($stampPath)) {
    $pdf->Image($stampPath, 24, $signY + 4, 24);
}
$pdf->SetFont('Times', 'B', 8);
$pdf->SetXY(2, $signY + 20);
$pdf->Cell(70, 5, 'Authorised Signatory', 0, 1, 'C');

$pdf->SetFont('Times', 'I', 7.5);
$pdf->SetXY(10, 289);
$pdf->Cell(190, 4, 'This is a Computer Generated Invoice', 0, 0, 'C');

/* ---------------- SAVE AND DISPLAY PDF ---------------- */
$outputDir = __DIR__ . '/invoices';
if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    exit('Unable to create invoices directory');
}

$safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $client_name);
$fileName = $safeName . '_' . $gst_invoice_no . '.pdf';
$absolutePath = $outputDir . '/' . $fileName;
$relativePath = 'invoices/' . $fileName;

$pdf->Output('F', $absolutePath);

$stmt = mysqli_prepare($conn, "
    UPDATE tbl_invoice
    SET gst_invoice_no = ?, pdf_file = ?
    WHERE client_id = ? AND created_at = ?
");
mysqli_stmt_bind_param($stmt, 'isis', $gst_invoice_no, $relativePath, $client_id, $created_at);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    exit('PDF saved, but database update failed.');
}
mysqli_stmt_close($stmt);

$pdf->Output('I', $fileName);
exit;
