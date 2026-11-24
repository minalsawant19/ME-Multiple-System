<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$work_id = $_GET['work_id'] ?? 0;

// Fetch Pending Work Details
$work = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_pending_work WHERE id='$work_id'"));

$client_id = $work['client_id'] ?? 0;

// Fetch Client Info
$client = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_client WHERE Id='$client_id'"));
?>

<div class="container-fluid">
    <a href="pending-work.php?client_id=<?= $client_id; ?>" class="btn btn-secondary">&larr; Back</a>
    <div class="inquiry-container">
        <h2 class="text-center">Update Pending Work for <?= $client['cust_name']; ?></h2>
        <div class="card-body">
            <form action="update-pending-code-work.php" method="POST">
                <input type="hidden" name="work_id" value="<?= $work_id; ?>">
                <input type="hidden" name="client_id" value="<?= $client_id; ?>">

                <div class="form-group">
                    <label for="work_description">Work Description <span class="text-danger">*</span></label>
                    <textarea name="work_description" id="work_description" class="form-control" rows="3" required><?= $work['work_description']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <input type="text" name="assigned_to" id="assigned_to" class="form-control" value="<?= $work['assigned_to']; ?>">
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" value="<?= $work['due_date']; ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Pending" <?= ($work['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Completed" <?= ($work['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <button type="submit" name="update_pending_work" class="">Update Pending Work</button>
                
            </form>
        </div>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>
