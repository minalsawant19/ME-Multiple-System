<?php
include 'dbcon.php';

// ===============================
// VALIDATION
// ===============================
if (!isset($_GET['id'])) {
    die('Quotation ID missing');
}

$id = intval($_GET['id']);

// ===============================
// FETCH DATA
// ===============================
$qry = mysqli_query($conn, "SELECT * FROM tbl_quotation WHERE id='$id'");
if (!$qry || mysqli_num_rows($qry) == 0) {
    die('Quotation not found');
}

 
$q = mysqli_fetch_assoc($qry);

// ===============================
// LOAD FPDI + FPDF
// ===============================
require __DIR__.'/vendor/autoload.php';

use setasign\Fpdi\Fpdi;


// ===============================
// INIT PDF
// ===============================
$pdf = new Fpdi();
$pdf->AddPage();

// ===============================
// LOAD TEMPLATE (FIRST PAGE)
// ===============================
$template = __DIR__ . '/templates/ME-Quotations3.pdf';
$pdf->setSourceFile($template);
$tpl = $pdf->importPage(1);
$pdf->useTemplate($tpl, 0, 0, 210);


// ===============================
// FONT
// ===============================
$pdf->SetFont('Times','',11);

// ===============================
// HEADER
// ===============================

// Ref No
$pdf->SetXY(28, 23);
$pdf->Write(6, $q['ref_no']);

// Date
$pdf->SetXY(180, 23);
$pdf->Write(6, date('d/m/Y', strtotime($q['quotation_date'])));

// Client fectch data
$toX = 20;
$toY = 40;

// Start Y
$currentY = $toY;

// To,
$pdf->SetFont('Times','',11);
$pdf->SetXY($toX, $currentY);
$pdf->Write(6, "");
$currentY += 5;

// Client Name (BOLD)
$pdf->SetFont('Times','B',11);
$pdf->SetXY($toX, $currentY);
$pdf->Write(6, strtoupper($q['client_name']));
$currentY += 5;

// Address (MULTI LINE – AUTO HEIGHT)
$pdf->SetFont('Times','',11);
$pdf->SetXY($toX, $currentY);
$pdf->MultiCell(70, 4.5, strtoupper($q['client_address']));

// Update Y AFTER address
$currentY = $pdf->GetY();

// Mobile number — use MultiCell for auto line height update
$pdf->SetXY($toX, $currentY);
$pdf->MultiCell(70, 5, "Mo. No. " . $q['client_phone']);

// Update Y after mobile number
$currentY = $pdf->GetY();

// Optional Connection No (if exists)
if (!empty($q['connection_no'])) {
    $pdf->SetXY($toX, $currentY);
    $pdf->MultiCell(70, 5, "CON. NO. " . $q['connection_no']);
    $currentY = $pdf->GetY() + 2;
}

// Property Type (BOLD)
$pdf->SetFont('Times','B',11);
$pdf->SetXY($toX, $currentY);
$pdf->Write(6, strtoupper($q['property_type']));


// ===============================
// SUBJECT
// ===============================
$pdf->SetFont('Times','BU',11);
// $pdf->SetXY(14, 65);
// $pdf->Write(6, "Subject : ");
// $pdf->SetFont('Times','BU',11);
$pdf->SetXY(30, 77.8); // subject text thoda right shift
$pdf->MultiCell(170, 6, $q['subject']);



$pdf->SetFont('Times','BU',10);
$pdf->SetXY(81, 107);

$bodyText =$q['plant_capacity_kw'].  "   "
    ;

$pdf->MultiCell(170, 6, $bodyText);



// ===============================
// PAGE 2 â€“ SUMMARY OF PROPOSAL
// ===============================

$tableStartX = 30;   // table left
$tableStartY = 35;   // table top (first row)

$colWidths = [
    95,   // Column 1 (Description)
    65    // Column 2 (Details)
];

$rowY = [
    1 => 0,    // Proposed PV Plant Size
    2 => 5,    // System Type
    3 => 11,   // Monthly Generation
    4 => 17,   // Yearly Generation
    5 => 22,   // GST
    6 => 28,   // Total System Cost
    7 => 34,   // Annual Saving
    8 => 40    // Payback Years
];


function writeCell($pdf, $row, $col, $text, $sx, $sy, $cw, $rowY)
{
    $x = $sx;
    for ($i = 1; $i < $col; $i++) {
        $x += $cw[$i - 1];
    }

    $y = $sy + $rowY[$row];

    $pdf->SetXY($x + 2, $y + 3);
    $pdf->Cell($cw[$col - 1] - 4, 6, $text, 0, 0, 'C');
}


$pdf->AddPage();
$tpl2 = $pdf->importPage(2);
$pdf->useTemplate($tpl2, 0, 0, 210);

$pdf->SetFont('Times','',11);

writeCell($pdf,1,2,$q['plant_capacity_kw']." KWp",$tableStartX,$tableStartY,$colWidths,$rowY);
writeCell($pdf,2,2,$q['system_type'],$tableStartX,$tableStartY,$colWidths,$rowY);
writeCell($pdf,3,2,$q['monthly_generation']." Units",$tableStartX,$tableStartY,$colWidths,$rowY);
writeCell($pdf,4,2,$q['yearly_generation']." Units",$tableStartX,$tableStartY,$colWidths,$rowY);
writeCell($pdf,5,2,"Included",$tableStartX,$tableStartY,$colWidths,$rowY);
// ----- TOTAL SYSTEM COST (Bold + Underline) -----
$pdf->SetFont('Times','BU',11);
writeCell(
    $pdf,
    6,
    2,
    "INR ".number_format($q['total_amount'],2),
    $tableStartX,
    $tableStartY,
    $colWidths,
    $rowY
);

// ----- ANNUAL SAVING (Bold + Underline) -----
$pdf->SetFont('Times','BU',11);
writeCell(
    $pdf,
    7,
    2,
    "INR ".number_format($q['annual_saving'],2),
    $tableStartX,
    $tableStartY,
    $colWidths,
    $rowY
);

// Reset font back to normal (IMPORTANT)
$pdf->SetFont('Times','',11);

writeCell($pdf,8,2,$q['payback_years']." Years",$tableStartX,$tableStartY,$colWidths,$rowY);


/* ================= HELPER FUNCTIONS ================= */

$pdf->SetFont('Times','',10);
// Normal single-line cell
function put($pdf, $row, $col, $text, $sx, $sy, $cw, $ry)
{
    $x = $sx;
    for ($i = 1; $i < $col; $i++) {
        $x += $cw[$i - 1];
    }
    $y = $sy + $ry[$row];

    $pdf->SetXY($x + 2, $y + 3);
    $pdf->Cell($cw[$col - 1] - 4, 6, $text, 0, 0, 'C');
}

// Calculate wrapped text height WITHOUT NbLines
function getWrappedTextHeight($pdf, $text, $maxWidth, $lineHeight)
{
    $words = explode(' ', $text);
    $line = '';
    $lines = 1;

    foreach ($words as $word) {
        $testLine = $line . $word . ' ';
        if ($pdf->GetStringWidth($testLine) > $maxWidth) {
            $lines++;
            $line = $word . ' ';
        } else {
            $line = $testLine;
        }
    }
    return $lines * $lineHeight;
}

// Specification column (wrapped + vertically centered)
function putSpecWrap($pdf, $row, $text, $sx, $sy, $cw, $ry, $rowHeight = 14)
{
    $x = $sx + $cw[0];            // SPEC column start
    $y = $sy + $ry[$row];

    $lineHeight = 5;
    $usableWidth = $cw[1] - 18;

    $textHeight = getWrappedTextHeight(
        $pdf,
        $text,
        $usableWidth,
        $lineHeight
    );

    $vCenterY = $y + (($rowHeight - $textHeight) / 2);

    $pdf->SetXY($x + 18, $vCenterY);
    $pdf->MultiCell(
        $usableWidth,
        $lineHeight,
        $text,
        0,
        'C'
    );
}

/* ================= TABLE SETTINGS ================= */

$startX = -5;
$startY = 115;

// Column widths
$colWidth = [
    30, // ITEM
    90, // SPECIFICATION
    45, // MAKE
    35  // QTY
];

// Row Y positions
$rowY = [
    1 => -3,     // Panels
    2 => 10,     // Inverter
    3 => 27,     // Cables/Wires 1
    4 => 45,     // Cables/Wires 2
    5 => 61,     // Mounting
    6 => 80,     // DCJB
    7 => 96,     // ACDB
    8 => 112,    // Earthing
    9 => 125     // Net Meter
];

/* ================= SAMPLE DATA ================= */

// $q = [
//     'panel_spec'    => 'DCR 545-Watt Mon crystalline PV Module 3kW',
//     'panel_make'    => 'Adani Solar 540Wp',
//     'panel_qty'     => '10 Nos',

//     'inverter_spec' => 'Grid tie inverter Single phase 3kW With Wi-Fi kit',
//     'inverter_make' => 'Sungrow 10kW',
//     'inverter_qty'  => '1 Nos',

//     'cable_make'    => 'Polycab Solar DC',
//     'mounting_make' => 'HDSL Structure',
//     'dc_jb_make'    => 'Havells',
//     'acdb_make'     => 'L&T',
//     'earthing_make' => 'Copper Bonded',
//     'earthing_qty'  => '3 Nos',
//     'net_meter_make'=> 'Utility Approved',
//     'net_meter_qty' => '1 Nos'
// ];

/* ================= TABLE DATA ================= */

// PANELS
putSpecWrap($pdf, 1, $q['panel_spec'], $startX, $startY, $colWidth, $rowY, 14);
putSpecWrap($pdf, 2, $q['inverter_spec'], $startX, $startY, $colWidth, $rowY, 14);

$pdf->SetFont('Times','BU',10);
put($pdf, 1, 3, $q['panel_make'], $startX, $startY, $colWidth, $rowY);
put($pdf, 1, 4, $q['panel_qty'],  $startX, $startY, $colWidth, $rowY);

// INVERTER
put($pdf, 2, 3, $q['inverter_make'], $startX, $startY, $colWidth, $rowY);
put($pdf, 2, 4, $q['inverter_qty'],  $startX, $startY, $colWidth, $rowY);

// CABLES / WIRES
put($pdf, 3, 3, $q['cable_make'], $startX, $startY, $colWidth, $rowY);
put($pdf, 4, 3, $q['cable_make'], $startX, $startY, $colWidth, $rowY);

// MOUNTING
put($pdf, 5, 3, $q['mounting_make'], $startX, $startY, $colWidth, $rowY);

// DCJB
put($pdf, 6, 3, $q['dc_jb_make'], $startX, $startY, $colWidth, $rowY);

// ACDB
put($pdf, 7, 3, $q['acdb_make'], $startX, $startY, $colWidth, $rowY);

// EARTHING
put($pdf, 8, 3, $q['earthing_make'], $startX, $startY, $colWidth, $rowY);
put($pdf, 8, 4, $q['earthing_qty'],  $startX, $startY, $colWidth, $rowY);

// NET METER
put($pdf, 9, 3, $q['net_meter_make'], $startX, $startY, $colWidth, $rowY);
put($pdf, 9, 4, $q['net_meter_qty'],  $startX, $startY, $colWidth, $rowY);


// ===============================
// PAGE 3 â€“ COMMERCIAL
// ===============================
$pageNo = 3;
$tpl = $pdf->importPage($pageNo);
$pdf->AddPage();
$pdf->useTemplate($tpl, 0, 0, 210);

$pdf->SetFont('Helvetica','',10);


$pdf->SetXY(91, 40);

$pdf->Cell(10, 5, $q['plant_capacity_kw']);

$amountX = 148;   // Amount column X
$baseY   = 37;    // Table start Y

$rowY = [
    1 => 2.5,     // Supply & Installation
    2 => 8.5,     // GST @ 5%
    3 => 14,    // Net Metering (Included)
    4 => 22,    // Total
    5 => 29,    // Load Expansion
    6 => 36,    // Structure 18% GST
    7 => 41,    // Govt Subsidy
    8 => 47     // Actual Project Cost
];

function commercialRow($pdf, $row, $text, $x, $baseY, $rowY, $align = 'C'){
    $amountColWidth = 65; // 👈 template ke Amount column ke barabar
    $pdf->SetXY($x, $baseY + $rowY[$row]);
    $pdf->Cell($amountColWidth, 8, $text, 0, 0, $align);
}

$supply   = number_format($q['supply_installation_amount'],2);
$gst5     = number_format($q['gst_5_amount'],2);
$total    = number_format($q['commercial_total'],2);
$subsidy  = number_format($q['govt_subsidy_amount'],2);
$actual   = number_format($q['actual_project_cost'],2);


$pdf->SetFont('Helvetica','B',11);

// Row 1 â€“ Supply
commercialRow($pdf,1,"INR ".$supply,$amountX,$baseY,$rowY);

// Row 2 â€“ GST
commercialRow($pdf,2,"INR ".$gst5,$amountX,$baseY,$rowY);

// Row 4 â€“ Total
commercialRow($pdf,4,"INR ".$total,$amountX,$baseY,$rowY);

// Row 7 – Govt Subsidy
if (
    stripos($q['property_type'], 'residential') !== false
    && $q['govt_subsidy_amount'] > 0
) {
    commercialRow($pdf, 7, "INR ".$subsidy, $amountX, $baseY, $rowY);
} else {
   commercialRow($pdf, 7, "NA", $amountX, $baseY, $rowY, 'C');
}

// Row 8 â€“ Actual Project Cost
commercialRow($pdf,8,"INR ".$actual,$amountX,$baseY,$rowY);


$pdf->AddPage();
$tpl2 = $pdf->importPage(4);
$pdf->useTemplate($tpl2, 0, 0, 210);


$pdf->AddPage();
$tpl2 = $pdf->importPage(5);
$pdf->useTemplate($tpl2, 0, 0, 210);


$outDir = __DIR__ . '/upload-quotations/';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$clientName = $q['client_name'];
$clientNameSafe = preg_replace('/[^A-Za-z0-9\-]/', '_', $clientName);

$fileName = 'Quotation_' . $clientNameSafe . '.pdf';
$outPath = $outDir . $fileName;

$pdf->Output('F', $outPath);

$fileUrl = 'upload-quotations/' . $fileName;

mysqli_query($conn, "
    UPDATE tbl_quotation 
    SET pdf_file='$fileUrl'
    WHERE id='$id'
") or die(mysqli_error($conn));

header("Location: view-quotations.php");
exit;