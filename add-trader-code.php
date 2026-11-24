<?php
include('authentication.php');
include('dbcon.php');

if (isset($_POST['add_trader'])) {

    $cust_name = mysqli_real_escape_string($conn, $_POST['cust_name']);
    $cust_phone = mysqli_real_escape_string($conn, $_POST['cust_phone']);
    $cust_email = mysqli_real_escape_string($conn, $_POST['cust_email'] ?? '');
    $cust_address = mysqli_real_escape_string($conn, $_POST['cust_address'] ?? '');

    $gst_number = mysqli_real_escape_string($conn, $_POST['gst_number'] ?? '');
    $service_date = mysqli_real_escape_string($conn, $_POST['service_date']);

    $billing_address = $cust_address;
    $shipping_address = mysqli_real_escape_string($conn, $_POST['shipping_address'] ?? '');

    $added_by = 'trader';

    $query = "INSERT INTO tbl_client (
        cust_name,
        cust_phone,
        cust_email,
        cust_address,
        gst_number,
        service_date,
        billing_address,
        shipping_address,
        added_by
    ) VALUES (
        '$cust_name',
        '$cust_phone',
        '$cust_email',
        '$cust_address',
        '$gst_number',
        '$service_date',
        '$billing_address',
        '$shipping_address',
        '$added_by'
    )";

    if (mysqli_query($conn, $query)) {

        $_SESSION['status'] = "Trader Added Successfully!";
        $_SESSION['status_code'] = "success";

        header("Location: view-trader.php");
        exit();

    } else {

        $_SESSION['status'] = "Error: " . mysqli_error($conn);
        $_SESSION['status_code'] = "error";

        header("Location: add-trader.php");
        exit();
    }

} else {

    header("Location: add-trader.php");
    exit();
}
?>
