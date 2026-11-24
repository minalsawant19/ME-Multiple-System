<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

// Get Vendor ID from URL
$id = $_GET['id'] ?? 0;


// Fetch Vendor Details
$vendor = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM tbl_vendor WHERE id='$id'")
);

// Fetch Clients added by this Vendor
$clients_query = mysqli_query($conn, "
    SELECT * FROM tbl_client 
    WHERE added_by='vendor' AND vendor_id='$id'
");

?>

<div class="container-fluid" style="color: black; font-size:large">
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h4>Vendor Details</h4>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?= $vendor['vendor_name']; ?></p>
            <p><strong>Phone:</strong> <?= $vendor['phone']; ?></p>
            <p><strong>Email:</strong> <?= $vendor['email']; ?></p>
            <p><strong>Address:</strong> <?= $vendor['address']; ?></p>
        </div>

    </div>
    <h4 class="mb-3">Clients Added by This Vendor</h4>

    <?php if (mysqli_num_rows($clients_query) > 0) { ?>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Balance</th>
                    <th>Payments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                
<?php
$serial = 1;

while ($client = mysqli_fetch_assoc($clients_query)) {
    
    

   $client_id = $client['id'];

/* PAYMENT SUMMARY */
$payment_summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT IFNULL(SUM(paid_amount),0) AS total_paid
    FROM tbl_payment
    WHERE client_Id = '$client_id'
"));

$total_paid = $payment_summary['total_paid'];

/* PAYMENT HISTORY */
$payments = mysqli_query($conn, "
    SELECT * FROM tbl_payment
    WHERE client_id = '$client_id'
    ORDER BY payment_date DESC
");

   $base_input = floatval($client['total_amount']);
                       $gst_rate = 0.18;

                       if ($client['apply_gst'] == 1) {

                        if ($client['gst_type'] == 'inclusive') {

                            $grand_total = $base_input;
                            $base_amount = $grand_total / (1 + $gst_rate);
                            $gst_amount = $grand_total - $base_amount;

                        } else {

                            $base_amount = $base_input;
                            $gst_amount = $base_amount * $gst_rate;
                            $grand_total = $base_amount + $gst_amount;
                        }

                    } else {

    // GST not applicable
                        $base_amount = $base_input;
                        $gst_amount = 0;
                        $grand_total = $base_input;
                    }


$total_paid = floatval($payment_summary['total_paid'] ?? 0);
$balance = $grand_total - $total_paid;
?>

<tr>

    <td><?= $serial++; ?></td>
                        <td>
                            <a href="view-client-detail.php?client_id=<?= $client['id']; ?>">
                                <?= $client['cust_name']; ?>
                            </a>
                        </td>
                        <td><?= $client['cust_phone']; ?></td>
                        <td>
    ₹ <?= number_format($balance, 2); ?>
</td>

                         <td>
                             <button class="btn btn-primary btn-sm"
        onclick="togglePayment(<?= $client_id ?>)">
    View Payment
</button>
                         </td>

                        <td>
    <a href="add-invoice.php?client_id=<?= $client['id']; ?>"
       class="btn btn-success btn-sm">
        Add GST Invoice
    </a>
</td>
                    </tr>
                    
                    
                    
                    <tr id="paymentRow<?= $client_id ?>" style="display:none;">
    <td colspan="6">

        <div class="card mb-4 shadow">
            <div class="card-header bg-info text-white">
                <h4>Payment Details - <?= $client['cust_name']; ?></h4>
            </div>

            <div class="card-body">

                <div class="row mb-3">

                    <div class="col-md-4">
                            <strong>Base Amount:</strong>
                            <?= number_format($base_amount,2); ?>
                        </div>

                        <?php if($client['apply_gst']==1){ ?>
                            <div class="col-md-4">
                                <strong>GST (18%):</strong>
                                <?= number_format($gst_amount,2); ?>
                            </div>

                            <div class="col-md-4">
                                <strong>Grand Total:</strong>
                                <?= number_format($grand_total,2); ?>
                            </div>
                        <?php } else { ?>
                            <div class="col-md-4">
                                <strong>Total Amount:</strong>
                                <?= number_format($grand_total,2); ?>
                            </div>
                        <?php } ?>

                    <div class="col-md-4 mt-3">
                        <strong>Total Paid:</strong>
                        <?= number_format($total_paid,2); ?>
                    </div>

                    <div class="col-md-4 mt-3">
                        <strong>Balance:</strong>
                        <?= number_format($balance,2); ?>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table table-bordered text-center">

                        <thead>
                            <tr>
                                <th>Sr.No.</th>
                                <th>Payment Date</th>
                                <th>Paid Amount</th>
                                <th>Payment Gateway</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                        $payments2 = mysqli_query($conn,"
                            SELECT * FROM tbl_payment
                            WHERE client_id='$client_id'
                            ORDER BY payment_date DESC
                        ");

                        $srno = 1;

                        if(mysqli_num_rows($payments2)>0){

                            while($row=mysqli_fetch_assoc($payments2)){
                        ?>

                            <tr>
                                <td><?= $srno++; ?></td>
                                <td><?= $row['payment_date']; ?></td>
                                <td><?= $row['paid_amount']; ?></td>
                                <td><?= $row['payment_gateway']; ?></td>
                            </tr>

                        <?php
                            }
                        }else{
                            echo "<tr><td colspan='4'>No Payment Records Found</td></tr>";
                        }
                        ?>

                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </td>
</tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No clients added by this vendor.</p>
    <?php } ?>

    <a href="view-vendor.php" class="btn btn-secondary mt-3">Back to Vendors</a>
</div>

<script>
function togglePayment(id)
{
    let row = document.getElementById('paymentRow'+id);

    if(row.style.display === 'none' || row.style.display === '')
    {
        row.style.display = 'table-row';
    }
    else
    {
        row.style.display = 'none';
    }
}
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>