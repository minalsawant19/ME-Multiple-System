<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

// Fetch all pending works with client info
$pending_works = mysqli_query($conn, "
    SELECT pw.*, c.cust_name 
    FROM tbl_pending_work pw
    LEFT JOIN tbl_client c ON pw.client_id = c.Id
    WHERE pw.status != 'Completed'
    ORDER BY pw.due_date ASC
");
?>
<div class="container-fluid">
    <h2 class="mb-4 text-center" >All Pending Work</h2>
    <div class="containers">
        <div class="card-body">
   
        <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="">
                <tr>
                    <th>Sr. No.</th>
                    <th>Client Name</th>
                    <th>Work Description</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php  
                $serial_no = 1;
                if(mysqli_num_rows($pending_works) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($pending_works)): ?>
                        <tr>
                            <td><?= $serial_no++; ?></td>
                            <td><?= $row['cust_name'] ?? "N/A"; ?></td>
                            <td><?= $row['work_description']; ?></td>
                            <td><?= $row['assigned_to']; ?></td>
                            <td><?= $row['due_date']; ?></td>
                            <td>
                                <?php if($row['status'] == 'Completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="window.location.href='update-pending-work.php?work_id=<?= $row['id']; ?>' " class="btn btn-sm btn-dark">Update</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No Pending Work Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>
<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
