<?php
include('dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

/* ================= VALIDATION ================= */
if (!isset($_GET['quotation_id'])) {
    die("<div class='alert alert-danger text-center'>Invalid Request</div>");
}

$quotation_id = (int)$_GET['quotation_id'];

/* ================= FETCH QUOTATION ================= */
$query = "
    SELECT 
        q.pdf_file,
        q.quotation_date,
        i.name AS client_name,
        i.email AS client_email
    FROM tbl_quotation q
    LEFT JOIN tbl_inquiry i ON i.id = q.inquiry_id
    WHERE q.id = $quotation_id
    LIMIT 1
";

$res = mysqli_query($conn, $query) or die(mysqli_error($conn));

if (mysqli_num_rows($res) == 0) {
    die("<div class='alert alert-danger text-center'>Quotation not found</div>");
}

$row = mysqli_fetch_assoc($res);

$clientName  = $row['client_name'];
$clientEmail = $row['client_email'];
$pdfPath     = __DIR__ . '/' . $row['pdf_file'];

if (!file_exists($pdfPath)) {
    die("<div class='alert alert-danger text-center'>Quotation PDF not found</div>");
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

    // CC (optional)
    $mail->addCC('vilas@shulventures.com');

    $mail->addAttachment($pdfPath);

    $mail->isHTML(true);
    $mail->Subject = "Quotation from ME Multiple System";
    $mail->Body = "
        Dear <b>{$clientName}</b>,<br><br>
        Please find attached the quotation for your reference.<br><br>
        If you have any questions, feel free to contact us.<br><br>
        Regards,<br>
        <b>ME Multiple System</b>
    ";

    $mail->send();

    
    // ✅ POPUP + SAME PAGE
    echo "
    <script>
        alert('✅ Quotation sent successfully to $clientEmail');
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
?>