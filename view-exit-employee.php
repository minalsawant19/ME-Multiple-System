<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$employees = mysqli_query($conn, "SELECT * FROM tbl_employee ORDER BY id DESC");

// Get search term if submitted, otherwise empty
$search = (isset($_GET['search']) && $_GET['search'] !== '') ? trim($_GET['search']) : '';

// Build query
if ($search !== '') {
    $query = "SELECT * FROM tbl_employee 
              WHERE name LIKE '%$search%' 
              OR phone LIKE '%$search%' 
              OR designation LIKE '%$search%' 
              OR department LIKE '%$search%' 
              ORDER BY id DESC";
} else {
    // Show all rows if no search
    $query = "SELECT * FROM tbl_employee WHERE resignation_type='With Notice'
OR resignation_type='Without Notice' ORDER BY id DESC";
}

// Execute query
$employees = mysqli_query($conn, $query);
// ?>

<div class="container-fluid">
    <!-- Search Section -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search Bar -->
        <form method="GET" class="mr-2">
            <input
             type="text" name="search" class="form-control" placeholder="Search Client..."
                value="">
        </form>
        <div>

<a href="view-exit-employee.php" 
class="btn btn-success btn-sm">

<i class="fa fa-users"></i>
View Exit Employee

</a>


<a href="view-abscond.php"
class="btn btn-danger btn-sm">

<i class="fa fa-user-slash"></i>
View Abscond

</a>

</div>
    </div>
    
    

    <h1 class="h3 text-gray-800 text-center mb-4">Exist Employees</h1>

    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">
                <table id="employeeTable" class="table table-bordered text-center">
                    <thead class="">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Resignation Type</th>
                            <th>View</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serial_no = 1;
                        if (mysqli_num_rows($employees) > 0) {
                            while ($row = mysqli_fetch_assoc($employees)) { ?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><img src="uploads/<?= $row['profile_photo'] ?>" width="60"></td>
                                    <td><?= $row['name'] ?></td>
                                    <td><?= $row['phone'] ?></td>
                                    <td><?= $row['designation'] ?></td>
                                    <td><?= $row['resignation_type'] ?></td>
                                    <td><button onclick="window.location.href='view-employee.php?id=<?= $row['id'] ?>'" class="btn btn-info btn-sm">View</button></td>
                                    <td><button onclick="window.location.href='update-employee.php?id=<?= $row['id'] ?>'" class="btn btn-dark btn-sm">Update</button></td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="7">No Employees Found</td>
                            </tr>
                        <?php } ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php
include('script.php'); 
include('includes/scripts.php');
include('includes/footer.php');
?>

