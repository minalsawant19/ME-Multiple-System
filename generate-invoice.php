<?php
include 'dbcon.php';
require __DIR__.'/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

/* ================= VALIDATION ================= */
if (!isset($_GET['client_id']) || !isset($_GET['created_at'])) {
    die('Client ID or timestamp missing');
}

$client_id  = (int)$_GET['client_id'];
$created_at = mysqli_real_escape_string($conn, $_GET['created_at']);

/* ================= FETCH DATA ================= */
$qry = mysqli_query($conn,"
    SELECT * FROM tbl_invoice
    WHERE client_id = $client_id
      AND created_at = '$created_at'
    ORDER BY id ASC
");

if (mysqli_num_rows($qry) == 0) die('Invoice not found');

$first = mysqli_fetch_assoc($qry);
$client_name = $first['cust_name'];

// Fetch GST Number from tbl_client
$gstQry = mysqli_query($conn, "
    SELECT gst_number
    FROM tbl_client
    WHERE id = $client_id
");

$gstData = mysqli_fetch_assoc($gstQry);
$gst_number = $gstData['gst_number'] ?? '';

if (!empty($first['gst_invoice_no'])) {

    // already generated invoice → reuse
    $gst_invoice_no = $first['gst_invoice_no'];

} else {

    // generate NEW invoice number
    $r = mysqli_query($conn,"
        SELECT MAX(gst_invoice_no) AS last_inv
        FROM tbl_invoice
        WHERE gst_invoice_no IS NOT NULL
    ") or die(mysqli_error($conn));

    $row = mysqli_fetch_assoc($r);
    $gst_invoice_no = ($row['last_inv'] > 0) ? $row['last_inv'] + 1 : 1;
}


/* ================= TOTAL ================= */
$grandTotal = 0;
mysqli_data_seek($qry,0);
while($r=mysqli_fetch_assoc($qry)){
    $grandTotal += $r['item_total'];
}
$grandTotal = round($grandTotal,2);

/* ================= PDF INIT ================= */
$pdf = new Fpdi();
$pdf->AddPage();

$template = __DIR__.'/templates/Invoice-ME7.pdf';
$pdf->setSourceFile($template);
$tpl = $pdf->importPage(1);
$pdf->useTemplate($tpl,0,0,210);

/* ================= HEADER ================= */
$pdf->SetFont('Times','B',11);
$pdf->SetXY(32,44);  $pdf->Write(6,$gst_invoice_no);
$pdf->SetXY(35,35);  $pdf->Write(6,date('d-m-Y',strtotime($first['invoice_date'])));
$pdf->SetXY(158,35); $pdf->Write(6,date('d-m-Y',strtotime($first['due_date'])));

/* ================= ADDRESSES ================= */
$pdf->SetFont('Times','B',10);

$billing = strtoupper($first['cust_name'])."\n".$first['billing_address']."\nPh: ".$first['cust_phone']."\n".$first['cust_email'] . "\nGSTIN: " . ($gst_number ?: 'N/A');
;
$shipping = strtoupper($first['cust_name'])."\n".$first['shipping_address']."\nPh: ".$first['cust_phone']."\n".$first['cust_email'] . "\nGSTIN: " . ($gst_number ?: 'N/A');
;

$pdf->SetXY(13,70);  $pdf->MultiCell(90,3.5,$billing);
$pdf->SetXY(107,70); $pdf->MultiCell(90,3.5,$shipping);

/* ================= ITEM TABLE ================= */
function rowHeight($pdf,$w,$lh,$txt){
    $x=$pdf->GetX(); $y=$pdf->GetY();
    $pdf->MultiCell($w,$lh,$txt,0);
    $h=$pdf->GetY()-$y;
    $pdf->SetXY($x,$y);
    return $h;
}

$pdf->SetFont('Times','',9);
$y = 108.5; 
$lh = 4; 
$sr = 1;
$PAGE_BOTTOM = 230;

mysqli_data_seek($qry,0);
while($row=mysqli_fetch_assoc($qry)){

    $pdf->SetXY(20.5,$y);
    $h = rowHeight($pdf,49,$lh,$row['item_name']);
    $rowH = max($h,3.5);

    /* ❗ STOP — NO SECOND PAGE */
    if ($y + $rowH > $PAGE_BOTTOM) {
        break;
    }

    $pdf->SetXY(13.5,$y); 
    $pdf->Cell(7,$rowH,$sr,1,0,'C');

    $pdf->SetXY(20.5,$y); 
    $pdf->MultiCell(49.3,$lh,$row['item_name'],1);

    $x=69.8;
    $pdf->SetXY($x,$y); 
$pdf->Cell(17.3,$rowH,$row['hsn'],1,0,'C'); 
$x+=17.3;

$pdf->SetXY($x,$y); 
$pdf->Cell(9.5,$rowH,$row['qty'],1,0,'C'); 
$x+=9.5;

$pdf->SetXY($x,$y); 
$pdf->Cell(
    19,
    $rowH,
    number_format((float)$row['base_amount'], 2, '.', ','),
    1,
    0,
    'C'
); 
$x+=19;

$pdf->SetXY($x,$y); 
$pdf->Cell(
    12.6,
    $rowH,
    number_format((float)$row['cgst_per'], 2, '.', ',').'%',
    1,
    0,
    'C'
); 
$x+=12.6;

$pdf->SetXY($x,$y); 
$pdf->Cell(
    17.5,
    $rowH,
    number_format((float)$row['cgst_amt'], 2, '.', ','),
    1,
    0,
    'C'
); 
$x+=17.5;

$pdf->SetXY($x,$y); 
$pdf->Cell(
    11.2,
    $rowH,
    number_format((float)$row['sgst_per'], 2, '.', ',').'%',
    1,
    0,
    'C'
); 
$x+=11.2;

$pdf->SetXY($x,$y); 
$pdf->Cell(
    15.7,
    $rowH,
    number_format((float)$row['sgst_amt'], 2, '.', ','),
    1,
    0,
    'C'
); 
$x+=15.7;

/* FINAL AMOUNT */
$pdf->SetXY($x,$y); 
$pdf->Cell(
    26,
    $rowH,
    number_format((float)$row['item_total'], 2, '.', ','),
    1,
    1,
    'C'
);


    $y += $rowH;
    $sr++;
}

function amountInWords($number){
    $no = floor($number);
    $words = [
        0=>'','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
        'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen',
        'Eighteen','Nineteen',20=>'Twenty',30=>'Thirty',40=>'Forty',50=>'Fifty',
        60=>'Sixty',70=>'Seventy',80=>'Eighty',90=>'Ninety'
    ];
    $digits = ['','Hundred','Thousand','Lakh','Crore'];
    $str = [];
    $i = 0;

    while ($no > 0) {
        $divider = ($i == 1) ? 10 : 100;
        $num = $no % $divider;
        $no = floor($no / $divider);

        if ($num) {
            if ($num < 21)
                $str[] = $words[$num].' '.$digits[$i];
            else
                $str[] = $words[floor($num/10)*10].' '.$words[$num%10].' '.$digits[$i];
        }
        $i++;
    }

    return trim(implode(' ', array_reverse($str))).' Rupees Only';
}

/* ================= GRAND TOTAL ================= */
$pdf->SetFont('Times','B',9);
$pdf->SetXY(13.5,$y);
$pdf->Cell(159,8,'GRAND TOTAL',1,0,'C');
$pdf->Cell(26,8,number_format($grandTotal,2),1,1,'C');

/* ================= AMOUNT IN WORDS ================= */
$y += 8;

$pdf->SetFont('Times','B',9.5);
$pdf->SetFillColor(197, 217, 165); 
$pdf->SetXY(13.5,$y);
$pdf->Cell(
    185,
    7,
    'Total Amount (in words): '.amountInWords($grandTotal),
    1,
    1,
    'C',
    true
);

$y = $pdf->GetY();


/* ================= BANK DETAILS ================= */
$y = $pdf->GetY() + 2;

$pdf->SetFont('Times','B',8.5);
$pdf->SetFillColor(197, 217, 165);
$pdf->SetXY(13.5,$y);
$pdf->Cell(185,7,'Bank Details',1,1,'C',true);

$pdf->SetFont('Times','',9);
$pdf->SetX(13.5);
$pdf->MultiCell(185,3.8,
"M E MULTIPLE SYSTEM
Bank A/C Number: 877020110000167        IFSC: BKID0008770
Bank Name: Bank of India                 Branch: Mansar
GSTN: 27BEJPM5990K1ZR",1,'C');


/* ================= NOTES ================= */
// $y = $pdf->GetY() + 1.5;

// $pdf->SetFont('Times','BU',10);
// $pdf->SetXY(13.5,$y);
// $pdf->Cell(185,6,'Notes',0,1,'L');

// $pdf->SetFont('Times','',10);
// $pdf->SetX(13.5);

// $notes =
// "1. Tata make Module Warranty - 25 Years
// 2. Goodwe Make Invertor Warranty - 10 Years";

// $pdf->MultiCell(185,3.2,$notes,0);


/* ================= TERMS ================= */
$y = $pdf->GetY() + 1;

$pdf->SetFont('Times','BU',8.5);
$pdf->SetXY(13.5,$y);
$pdf->Cell(185,6,'Terms and Conditions',0,1,'L');

$pdf->SetFont('Times','',8.5);
$pdf->SetX(13.5);

$terms =
"1. Goods once sold will not be taken back.
2. Interest @18% P.A will be charged if payment is delayed.
3. If cheque is bounced, payment must be done via RTGS/NEFT with Rs.650 charges.
4. We are not responsible for loss or damage after goods are handed to carrier.";

$pdf->MultiCell(185,3.5,$terms,0);


/* ================= STAMP & SIGN ================= */
$y = $pdf->GetY() + 1;

$pdf->SetFont('Times','B',10);
$pdf->SetXY(15,$y);
$pdf->Cell(80,6,'For ME Multiple System',0,1,'L');

$stampPath = __DIR__.'/img/stamp.png';
if (file_exists($stampPath)) {
    $pdf->Image($stampPath,20,$y+6,28);
}

$pdf->SetXY(15,$y+22);
$pdf->Cell(80,6,'Authorized Signatory',0,1,'L');


/* ================= SAVE ================= */
$path = 'invoices/'.$client_name.'_'.$gst_invoice_no.'.pdf';
$pdf->Output('F', $path); // Save PDF on server

mysqli_query($conn,"
UPDATE tbl_invoice
SET gst_invoice_no=$gst_invoice_no,
    pdf_file='$path'
WHERE client_id=$client_id AND created_at='$created_at'
");

/* ================= OUTPUT PDF TO BROWSER ================= */
$pdf->Output('I', $client_name.'_'.$gst_invoice_no.'.pdf'); // 'I' = inline, opens in browser
exit;