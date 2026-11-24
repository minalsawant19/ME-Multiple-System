<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$id = $_GET['id'];
$type = $_GET['type'];

$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_inquiry WHERE id='$id'"));
?>
<div class="container-fluid">
    <div class="inquiry-container">

    <h2>
        <?= ucfirst($type) ?>
    </h3>

    <!-- Inquiry Basic Details (Editable) -->
    <form action="update-inquiry-code.php" method="POST">

        <input type="hidden" name="id" value="<?= $data['id']; ?>">
        <input type="hidden" name="type" value="<?= $type ?>">

            <h5 class="text-primary">Customer Details</h5>
            <hr>

                    <label>Name</label>
                    <input type="text" name="name" value="<?= $data['name']; ?>" required>
               
                    <label>Phone</label>
                    <input type="text" name="phone"  value="<?= $data['phone']; ?>" required>
                
                    <label>Email</label>
                    <input type="email" name="email"  value="<?= $data['email']; ?>">

               
                    <label>Address</label>
                    <input type="text" name="address"  value="<?= $data['address']; ?>">

       

        <?php if ($type == "refollowup") { ?>
            <h5 class="text-primary">Re-Followup Details</h5>
            <hr>

            <label>Re-Followup Remark</label>
            <textarea name="message"  required><?= $data['message']; ?></textarea>

            <label class="mt-3">Re-Followup Date</label>
            <input type="date" name="date"  required>
        <?php } ?>

        <?php if ($type == "visit") { ?>
            <h5 class="text-primary">Visit Details</h5>
            <hr>

            <label>Visit Remark</label>
            <textarea name="message" class="form-control" required><?= $data['message']; ?></textarea>

            <label class="mt-3">Visit Date</label>
            <input type="date" name="date"  required>
        <?php } ?>

        <?php if ($type == "service") { ?>
            <h5 class="text-primary">Service Details</h5>
            <hr>

            <label>Service Remark</label>
            <textarea name="message" required><?= $data['message']; ?></textarea>

            <label class="mt-3">Service Date</label>
            <input type="date" name="date"  required>
        <?php } ?>

        <?php if ($type == "schedule") { ?>
            <h5 class="text-primary">Schedule Details</h5>
            <hr>

            <label>Schedule Remark</label>
            <textarea name="message"required><?= $data['message']; ?></textarea>

            <label class="mt-3">Schedule Date</label>
            <input type="date" name="date" required>
        <?php } ?>

        <button type="submit" class="">Submit</button>
    </form>
    </div>
</div>

<?php 
include('includes/scripts.php');
include('includes/footer.php'); 
?>