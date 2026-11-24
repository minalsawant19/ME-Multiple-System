<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
?>

<div class="container-fluid">
    <div class="inquiry-container">

        <h2>Add New Trader</h2>

        <form action="add-trader-code.php" method="POST">

            <h5 class="text-primary">Trader Details</h5>
            <hr>

            <label>Trader Name</label>
            <input type="text" name="cust_name"
                   class="form-control" required>

            <label class="mt-3">Mobile Number</label>
            <input type="text" name="cust_phone"
                   class="form-control" required>

            <label class="mt-3">Email</label>
            <input type="email" name="cust_email"
                   class="form-control">

            <label class="mt-3">Address</label>
            <textarea name="cust_address"
                      class="form-control"></textarea>

            <label class="mt-3">GST Number</label>
            <input type="text" name="gst_number"
                   class="form-control" maxlength="15">

            <label class="mt-3">Date</label>
            <input type="date" name="service_date"
                   class="form-control"
                   value="<?= date('Y-m-d'); ?>"
                   required>

            <label class="mt-3">Shipping Address</label>
            <textarea name="shipping_address"
                      class="form-control"></textarea>

            <button type="submit" name="add_trader"
                    class="btn btn-primary btn-block mt-4">
                Save Trader
            </button>

        </form>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
