<?php
include('authentication.php'); // must include before content
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="inquiry-container">
        <h2>Add Vendor</h2>

        <form action="vendor-code.php" method="POST">
            <label>Vendor Name</label>
            <input type="text" name="vendor_name" placeholder="Enter Vendor Name" required />

            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="Enter Phone Number" required />

            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter Email Address" />

            <label>Address</label>
            <textarea name="address" placeholder="Enter Address" required></textarea>

            <button type="submit" name="save_vendor">Submit Vendor</button>
        </form>
    </div>
</div>
<!-- /.container-fluid -->
</div>
<!-- End of Main Content -->

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>