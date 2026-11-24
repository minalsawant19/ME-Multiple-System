<?php
include('dbcon.php');
require 'dompdf/autoload.inc.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ================== NUMBER TO WORDS ================== */
function numberToWords($number) {
    $words = [
        0=>'Zero',1=>'One',2=>'Two',3=>'Three',4=>'Four',5=>'Five',
        6=>'Six',7=>'Seven',8=>'Eight',9=>'Nine',10=>'Ten',
        11=>'Eleven',12=>'Twelve',13=>'Thirteen',14=>'Fourteen',
        15=>'Fifteen',16=>'Sixteen',17=>'Seventeen',18=>'Eighteen',
        19=>'Nineteen',20=>'Twenty',30=>'Thirty',40=>'Forty',
        50=>'Fifty',60=>'Sixty',70=>'Seventy',80=>'Eighty',90=>'Ninety'
    ];

    if ($number == 0) return 'Zero';
    $result = '';

    if ($number >= 100000) { $result .= numberToWords(intval($number/100000)) . ' Lakh '; $number %= 100000; }
    if ($number >= 1000)   { $result .= numberToWords(intval($number/1000)) . ' Thousand '; $number %= 1000; }
    if ($number >= 100)    { $result .= numberToWords(intval($number/100)) . ' Hundred '; $number %= 100; }
    if ($number > 0) {
        if ($number < 20) $result .= $words[$number];
        else { $result .= $words[intval($number/10)*10]; if ($number%10) $result .= ' '.$words[$number%10]; }
    }
    return trim($result);
}

/* ================== INPUTS ================== */
$employee_id = $_GET['employee_id'] ?? '';
$month = $_GET['month'] ?? '';

if (!$employee_id || !$month) die('Invalid Request');

/* ================== FETCH EMPLOYEE ================== */
$emp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_employee WHERE id='$employee_id'"));
if (!$emp) die('Employee not found');

/* ================== FETCH SALARY ================== */
$salary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        MAX(amount) AS earned_salary,
        SUM(advance) AS advance,
        SUM(profession_tax) AS profession_tax,
        SUM(reimbursement) AS reimbursement,
        MAX(per_day_amount) AS per_day_amount,
        MAX(basic) AS basic
    FROM tbl_salary
    WHERE employee_id='$employee_id'
      AND month='$month'
      AND payment_type='Salary'
"));

/* ================== FETCH ATTENDANCE ================== */
$attendance = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(
        CASE 
            WHEN status='Present' THEN 1
            WHEN status='Half Day' THEN 0.5
            ELSE 0
        END
    ) AS total_days
    FROM tbl_attendance
    WHERE employee_id='$employee_id'
      AND attendance_date BETWEEN '{$month}-01' AND LAST_DAY('{$month}-01')
"));

$current_present = $attendance['total_days'] ?? 0;
$total_days_in_month = date('t', strtotime($month.'-01'));
$emp_type = strtolower(str_replace([' ', '-'], '', $emp['employee_type']));

$basic = $salary['basic'] ?? 0;
$earned_salary = $salary['earned_salary'] ?? 0;
$advance = $salary['advance'] ?? 0;
$profession_tax = $salary['profession_tax'] ?? 0;
$reimbursement = $salary['reimbursement'] ?? 0;
$per_day = ($emp_type === 'perday') ? $salary['per_day_amount'] : ($salary['basic'] ?? 0) / 26;

/* Net Salary */
$net_salary = $earned_salary - $advance - $profession_tax + $reimbursement;
$month_label = date('F Y', strtotime($month.'-01'));

/* ================== GENERATE PDF HTML ================== */
$html = '
<style>
body { font-family: DejaVu Sans, sans-serif; font-size:14px; }
.outer { border:1px solid #000; padding:10px; }
.center { text-align:center; }
.bold { font-weight:bold; }
.logo { max-height:70px; }
table { width:100%; border-collapse:collapse; margin-top:8px; }
td, th { padding:4px; }
th{background-color: rgb(197, 217, 165);}
.border td, .border th { border:1px solid #000; }
.right { text-align:right; }
</style>

<div class="outer">

<div class="center">
<img src="http://memultiplesystem.shulventures.com/admin/img/multiple.jpeg" class="logo"><br>
<div>Plot No. 21/A, Jiddi App., 2nd floor, 3rd Bus Stop, Gopal Nagar, Nagpur - 22</div>
<div class="bold">Salary Slip for the Month of '.$month_label.'</div>
</div>

<hr><br>

<table>
<tr>
<td><b>Name of the Employee:</b> '.$emp['name'].'</td>
<td><b>Employee ID:</b> '.$emp['id'].'</td>
</tr>
<tr>
<td><b>PAN No:</b> '.($emp['pan_no'] ?? '-').'</td>
<td><b>Designation:</b> '.($emp['designation'] ?? '-').'</td>
</tr>
<tr>
<td><b>Bank Name:</b> '.($emp['bank_name'] ?? '-').'</td>
<td><b>Account No.:</b> '.($emp['account_no'] ?? '-').'</td>
</tr>
<tr>
<td><b>Total Days in Month:</b> '.$total_days_in_month.'</td>
<td><b>Working Days:</b> '.$current_present.'</td>
</tr>
</table>

<br>

<table class="border">
<tr class="bold">
<th>Earnings</th><th>Amount (₹)</th>
<th>Deductions</th><th>Amount (₹)</th>
</tr>
<tr>
<td>Earned Salary</td><td class="center">'.number_format($earned_salary,2).'</td>
<td>Advance</td><td class="center">'.number_format($advance,2).'</td>
</tr>
<tr>
<td>Reimbursement</td><td class="center">'.number_format($reimbursement,2).'</td>
<td>Profession Tax</td><td class="center">'.number_format($profession_tax,2).'</td>
</tr>
<tr class="bold">
<td class="center">Total Earnings</td><td class="center">'.number_format($earned_salary + $reimbursement,2).'</td>
<td class="center">Total Deductions</td><td class="center">'.number_format($advance + $profession_tax,2).'</td>
</tr>
<tr class="bold" style="background-color:rgb(197,217,165);">
<td colspan="3" class="center">Net Salary</td><td class="center">'.number_format($net_salary,2).'</td>
</tr>
<tr>
<td colspan="4" class="left"><strong>In Words: </strong>'.ucwords(numberToWords(round($net_salary))).' Only</td>
</tr>
</table>

<br>
<div class="center" style="font-size:12px;">This is a system-generated payslip and does not require signature.</div>
</div>
';

/* ================== GENERATE PDF ================== */
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

/* ================== DOWNLOAD PDF ================== */
$download = $_GET['download'] ?? 0;
if ($download == 1) {
    if (ob_get_length()) ob_end_clean(); // clear output buffer
    $dompdf->stream("SalarySlip_{$emp['name']}_{$month_label}.pdf", ["Attachment" => 1]);
    exit;
}

/* ================== SEND EMAIL ================== */
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'mail.shulventures.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@shulventures.com';
    $mail->Password   = 'Shul@2018';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@shulventures.com','ME Multiple System');
    $mail->addAddress($emp['email'],$emp['name']);
    $mail->addStringAttachment($pdfOutput,"SalarySlip_{$emp['name']}_{$month_label}.pdf");
    $mail->addCC('vilas@shulventures.com');

    $mail->isHTML(true);
    $mail->Subject = "Salary Slip for $month_label";
    $mail->Body    = "Dear {$emp['name']},<br><br>Please find attached your salary slip for <b>$month_label</b>.<br><br>Regards,<br>ME Multiple System";

    $mail->send();
    echo "<script>alert('✅ Salary Slip sent successfully to {$emp['email']}'); history.back();</script>";

} catch (Exception $e) {
    echo "<script>alert('❌ Email failed: {$mail->ErrorInfo}'); history.back();</script>";
}
?>