<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = $_GET['client_id'] ?? 0;

// Fetch Client Info
$client = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_client WHERE Id='$client_id'"));
?>

<div class="container-fluid">
    <a href="pending-work.php?client_id=<?= $client_id; ?>" class="btn btn-secondary"> &larr; Back</a>
    <div class="inquiry-container">
        
            <h2 class="text-center">Add Pending Work for <?= $client['cust_name']; ?></h2>
     
        <div class="card-body">
            <form action="add-pending-code.php" method="POST">
                <input type="hidden" name="client_id" value="<?= $client_id; ?>">

                <div class="form-group">
                    <label for="work_description">Work Description <span class="text-danger">*</span></label>
                    <textarea name="work_description" id="work_description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <input type="text" name="assigned_to" id="assigned_to" class="form-control">
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-control">
                </div>

                <button type="submit" name="add_pending_work" class="">Add Pending Work</button>
                <br>
                
            </form>
        </div>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>
