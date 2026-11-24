<?php
include 'dbcon.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

/* ================= VALIDATION ================= */
if (!isset($_GET['client_id']) || !isset($_GET['created_at'])) {
    echo "<script>alert('Invalid request'); history.back();</script>";
    exit;
}

$client_id  = (int)$_GET['client_id'];
$created_at = mysqli_real_escape_string($conn, $_GET['created_at']);

/* ================= FETCH INVOICE ================= */
$res = mysqli_query($conn,"
    SELECT cust_name, cust_email, pdf_file, gst_invoice_no
    FROM tbl_invoice
    WHERE client_id = $client_id
      AND created_at = '$created_at'
    LIMIT 1
");

if (mysqli_num_rows($res) == 0) {
    echo "<script>alert('Invoice not found'); history.back();</script>";
    exit;
}

$row = mysqli_fetch_assoc($res);

$clientName  = $row['cust_name'];
$clientEmail = $row['cust_email'];
$pdfPath     = __DIR__.'/'.$row['pdf_file'];
$invoiceNo   = $row['gst_invoice_no'];

if (!file_exists($pdfPath)) {
    echo "<script>alert('Invoice PDF not found'); history.back();</script>";
    exit;
}

/* ================= SEND EMAIL ================= */
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'mail.shulventures.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@shulventures.com';
    $mail->Password   = 'Shul@2018';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@shulventures.com', 'ME Multiple System');
    $mail->addAddress($clientEmail, $clientName);

    // ✅ CC
    $mail->addCC('vilas@shulventures.com');

    $mail->addAttachment($pdfPath);

    $mail->isHTML(true);
    $mail->Subject = "Invoice #$invoiceNo";
    $mail->Body = "
        Dear <b>$clientName</b>,<br><br>
        Please find attached your invoice <b>#{$invoiceNo}</b>.<br><br>
        Regards,<br>
        <b>ME Multiple System</b>
    ";

    $mail->send();

    // ✅ POPUP + SAME PAGE
    echo "
    <script>
        alert('✅ Invoice sent successfully to $clientEmail');
        history.back();
    </script>
    ";

} catch (Exception $e) {
    echo "
    <script>
        alert('❌ Email failed: {$mail->ErrorInfo}');
        history.back();
    </script>
    ";
}