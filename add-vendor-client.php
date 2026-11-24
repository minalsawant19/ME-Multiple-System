<?php
ob_start();
include('dbcon.php');
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');

/* ===============================
   FORM SUBMIT HANDLE
================================ */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $vendor_id     = $_POST['vendor_id'];
    $name          = $_POST['client_name'];
    $mobile        = $_POST['mobile'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];
    $work          = $_POST['work'];
    $total_amount  = $_POST['total_amount'];
    $service_date  = $_POST['service_date']; // ⭐ IMPORTANT

    /* -------- INSERT CLIENT -------- */
    $insert_client = "
        INSERT INTO tbl_client
        (cust_name, cust_phone, cust_email, cust_address, service, total_amount, service_date, added_by, vendor_id)
        VALUES
        ('$name', '$mobile', '$email', '$address', '$work', '$total_amount', '$service_date', 'vendor', '$vendor_id')
    ";
    mysqli_query($conn, $insert_client) or die(mysqli_error($conn));

    $client_id = mysqli_insert_id($conn);

    /* -------- INSERT DUE PAYMENT -------- */
    $insert_payment = "
        INSERT INTO tbl_payment
        (client_id, vendor_id, total_amount, paid_amount, due_amount, payment_date, due_date)
        VALUES
        ('$client_id', '$vendor_id', '$total_amount', 0, '$total_amount', CURDATE(), '$service_date')
    ";
    mysqli_query($conn, $insert_payment) or die(mysqli_error($conn));

    /* -------- REDIRECT -------- */
    header("Location: view-client.php");
    exit;
}

/* ===============================
   FETCH VENDOR DATA
================================ */
$vendor_id = $_GET['vendor_id'] ?? 0;
$vendor = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM tbl_vendor WHERE id='$vendor_id'")
);
?>

<div class="container-fluid">
    <a href="view-vendor.php" class="btn btn-secondary mt-2">Back</a>
    <div class="inquiry-container">
        <h3 class="text-center">Add Client for Vendor: <?= $vendor['vendor_name']; ?></h3>
        <form action="" method="POST" class="mt-4">
            <input type="hidden" name="vendor_id" value="<?= $vendor_id; ?>">
            <label>Client Name</label>
            <input type="text" name="client_name" required>
            <label>Mobile Number</label>
            <input type="text" name="mobile" required>
            <label>Email</label>
            <input type="email" name="email">
            <label>Address</label>
            <textarea name="address"></textarea>
            <label>Work to do</label>
            <textarea name="work"></textarea>
            <label>Total Amount</label>
            <input type="number" name="total_amount" required>
            <label class="mt-3">Service Date</label> <input type="date" name="service_date" required>
            <button type="submit" class="">Submit</button>
        </form>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>