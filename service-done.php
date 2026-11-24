<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_inquiry WHERE id='$id'"));

$type = $_GET['type'] ?? 'service';

function getNextInvoiceNumber($conn) {
    $result = mysqli_query($conn, "SELECT gst_invoice_no FROM tbl_client ORDER BY id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    if($row && !empty($row['gst_invoice_no'])) {
        $lastNumber = (int) filter_var($row['gst_invoice_no'], FILTER_SANITIZE_NUMBER_INT);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 01;
    }
    return "" . $nextNumber;
}
?>

<div class="container-fluid">
    <div class="inquiry-container">
    <h2>Complete Service & Add Client</h2>

    <form action="service-done-code.php" method="POST">
        <input type="hidden" name="id" value="<?= $data['id']; ?>">
        
        <!--treder code-->
        <input type="hidden" name="client_type"
       value="<?= $_GET['type'] ?? 'service'; ?>">
       
       
       
            <h5 class="text-primary">Customer Details</h5><hr>

            <label>Name</label>
            <input type="text" value="<?= $data['name']; ?>" readonly>

            <label class="mt-3">Phone</label>
            <input type="text" class="" value="<?= $data['phone']; ?>" readonly>

            <label class="mt-3">Email</label>
            <input type="text"  value="<?= $data['email']; ?>" readonly>

            <label class="mt-3">Address</label>
            <textarea  readonly><?= $data['address']; ?></textarea>
            
            
             <?php if ($type === 'trader') { ?>

                <!-- TRADER FORM ONLY -->

                <hr>
                <h5 class="text-success">Trader Details</h5>
                <hr>

                <label>Terms &amp; Conditions</label>
                <textarea name="service_term_condition"
                          class="form-control"
                          rows="4"
                          required></textarea>

                <label class="mt-3">Description</label>
                <textarea name="service_covered"
                          class="form-control"
                          rows="4"
                          required></textarea>

                <label class="mt-3">Amount (₹)</label>
                <input type="text"
                       name="total_amount"
                       class="form-control"
                       min="0.01"
                       step="0.01"
                       required>

                <hr>
                <h5 class="text-warning">GST Details</h5>
                <hr>

                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           id="apply_gst"
                           name="apply_gst"
                           value="1"
                           onclick="toggleGSTFields()">

                    <label class="form-check-label" for="apply_gst">
                        Apply GST
                    </label>
                </div>

                <div id="gst_fields" style="display:none;">

                    <label class="mt-3">GST Rate</label>
                    <select name="gst_rate" class="form-control">
                        <option value="5">GST 5%</option>
                        <option value="18" selected>GST 18%</option>
                    </select>

                    <label class="mt-3">GST Type</label>
                    <select name="gst_type" class="form-control">
                        <option value="exclusive">
                            Exclusive - GST Extra
                        </option>
                        <option value="inclusive">
                            Inclusive - GST Included
                        </option>
                    </select>

                    <label class="mt-3">GST Number</label>
                    <input type="text"
                           name="gst_number"
                           class="form-control"
                           maxlength="15">

                    <label class="mt-3">Billing Address</label>
                    <textarea name="billing_address"
                              class="form-control"></textarea>

                    <label class="mt-3">Shipping Address</label>
                    <textarea name="shipping_address"
                              class="form-control"></textarea>

                </div>

            <?php } else { ?>

            <hr><h5 class="text-success">Service Details</h5><hr>
            <label>Service Name</label>
            <input type="text" name="service"  required>

            <label class="mt-3">Service Date</label>
            <input type="date" name="service_date" required>
            
            <label class="mt-3">Consumer No</label>
            <input type="text" name="consumer_no" >

            <label class="mt-3">Subdivision</label>
            <input type="text" name="subdivision" >

            <label class="mt-3">Technical Name</label>
            <input type="text" name="technical_name" >

            <label class="mt-3">Terms & Conditions</label>
            <textarea name="service_term_condition"  required></textarea>

            <label class="mt-3">Total Amount</label>
            <input type="number" name="total_amount"  required>

            <label class="mt-3">Warranty</label>
            <input type="text" name="warranty" required>

            <label class="mt-3">Service Covered</label>
            <textarea name="service_covered"  required></textarea>

            <hr><h5 class="text-warning">GST Details</h5><hr>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="apply_gst" name="apply_gst" value="1" onclick="toggleGSTFields()">
                <label class="form-check-label" for="apply_gst">Apply GST (18%)</label>
            </div>

            <div id="gst_fields" style="display:none;">
                
                 <!-- GST TYPE -->
    <label class="mt-3">GST Type</label>

    <select 
        name="gst_type" 
        id="gst_type" 
        class="form-control"
    >
        <option value="exclusive">Exclusive - GST Extra</option>
        <option value="inclusive">Inclusive - GST Included</option>
    </select>
                
                <label class="mt-3">Billing Address</label>
                <textarea name="billing_address" ></textarea>

                <label class="mt-3">Shipping Address</label>
                <textarea name="shipping_address" ></textarea>

                <!--<label class="mt-3">GST Invoice No</label>-->
                <!--<input type="text" name="gst_invoice_no"  value="<?= getNextInvoiceNumber($conn); ?>" readonly>-->
            </div>
              
        <?php } ?>      
 
        <button class="btn btn-primary btn-block mt-3">Save Client</button>
    </form>
</div>
</div>

<script>
function toggleGSTFields() {

    var checkbox = document.getElementById('apply_gst');
    var gstFields = document.getElementById('gst_fields');

    if (checkbox.checked) {
        gstFields.style.display = 'block';
    } else {
        gstFields.style.display = 'none';
    }
}
</script>

<?php 
include('includes/scripts.php'); 
include('includes/footer.php'); 
?>
