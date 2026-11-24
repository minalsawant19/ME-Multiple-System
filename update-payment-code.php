<?php
session_start();
include('dbcon.php');

$id = (int)($_POST['id'] ?? 0);

$paid_amount = (float)($_POST['paid_amount'] ?? 0);
$payment_date = $_POST['payment_date'] ?? '';
$payment_gateway = $_POST['payment_gateway'] ?? '';
$due_date = $_POST['due_date'] ?? '';

if ($id <= 0 || $paid_amount <= 0) {
    die("Invalid Payment Details");
}

/* =========================================
   FETCH PAYMENT ROW
========================================= */

$pay = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM tbl_payment
    WHERE id = $id
    LIMIT 1
"));

if (!$pay) {
    die("Payment Record Not Found");
}

$client_id = (int)$pay['client_id'];
$total_amount = (float)$pay['total_amount'];
$old_paid = (float)$pay['paid_amount'];

$group_id = (int)($pay['group_id'] ?? 0);

/* =========================================
   CHECK TRADER
========================================= */

$clientQuery = mysqli_query($conn, "
    SELECT added_by
    FROM tbl_client
    WHERE Id = $client_id
    LIMIT 1
");

$clientData = mysqli_fetch_assoc($clientQuery);

$is_trader = strtolower(trim($clientData['added_by'] ?? '')) === 'trader';

/* =========================================
   CALCULATE PAYMENT
========================================= */

if ($is_trader && $group_id > 0) {

    // Trader: Calculate only selected group payment

    $groupSummary = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            MAX(total_amount) AS total_amount,
            SUM(paid_amount) AS total_paid
        FROM tbl_payment
        WHERE client_id = $client_id
        AND group_id = $group_id
    "));

    $total_amount = (float)$groupSummary['total_amount'];
    $old_paid = (float)$groupSummary['total_paid'];

    $new_paid = $old_paid + $paid_amount;
    $new_due = round($total_amount - $new_paid, 2);

    if ($new_due < 0) {
        die("Payment cannot exceed group due amount");
    }

    // Update only selected group main payment row
    $update = mysqli_query($conn, "
        UPDATE tbl_payment SET
            paid_amount = '$new_paid',
            due_amount = '$new_due',
            payment_date = NULL,
            payment_gateway = NULL,
            due_date = '$due_date'
        WHERE client_id = $client_id
        AND group_id = $group_id
        AND payment_date IS NULL
        LIMIT 1
    ");

    if (!$update || mysqli_affected_rows($conn) < 0) {
        die(mysqli_error($conn));
    }

    // Insert payment history for same group
    $stmt = mysqli_prepare($conn, "
        INSERT INTO tbl_payment
        (
            client_id,
            group_id,
            trading_history_id,
            total_amount,
            paid_amount,
            due_amount,
            payment_date,
            payment_gateway,
            due_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $history_id = (int)$pay['trading_history_id'];

    mysqli_stmt_bind_param(
        $stmt,
        "iiiddddss",
        $client_id,
        $group_id,
        $history_id,
        $total_amount,
        $paid_amount,
        $new_due,
        $payment_date,
        $payment_gateway,
        $due_date
    );

    if (!mysqli_stmt_execute($stmt)) {
        die(mysqli_stmt_error($stmt));
    }

} else {

    // NORMAL CLIENT: OLD LOGIC AS IT IS

    $new_paid = $old_paid + $paid_amount;
    $new_due = $total_amount - $new_paid;

    $updateQuery = "UPDATE tbl_payment SET
                        paid_amount='$new_paid',
                        due_amount='$new_due',
                        payment_date='$payment_date',
                        payment_gateway='$payment_gateway',
                        due_date='$due_date'
                    WHERE id='$id'";

    mysqli_query($conn, $updateQuery);

    $insertQuery = "INSERT INTO tbl_payment
        (client_id,total_amount,paid_amount,due_amount,payment_date,payment_gateway,due_date)
        VALUES
        ('$client_id','$total_amount','$paid_amount','$new_due',
         '$payment_date','$payment_gateway','$due_date')";

    mysqli_query($conn, $insertQuery);
}

/* =========================================
   SUCCESS
========================================= */

$_SESSION['status'] = "Payment Updated Successfully!";

header("Location: view-payment.php");
exit();

?>
