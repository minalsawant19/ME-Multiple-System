<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>
    <!-- Begin Page Content -->
<div class="container-fluid">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <!-- Page Heading -->
                <h1 class="h3 mb-2 text-gray-800">Users</h1>
                <button class="" style="width: fit-content;" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>

   <?php
     if(isset($_SESSION['status']))
     {
        echo "<h4>".$_SESSION['status']."</h4>";
        unset($_SESSION['status']);
     }
   ?>
      <h2 class="text-center">View All Users</h2>
    <!-- User Table -->
    <div class="containers">
        <div class="card-body">
            <div class="table-responsive">

                <table class="" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $serial_no = 1;
                        $query = "SELECT * FROM users";
                        $query_run = mysqli_query($conn,$query);

                        if(mysqli_num_rows($query_run) > 0)
                        {
                               foreach($query_run as $row)
                               {
                                ?>
                                <tr>
                                    <td><?= $serial_no++; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['password']; ?></td>
                                    <td>
                                        <form action="code.php" method="POST" class="d-inline">
                                            <a href="register-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">Update</a>
                                            <input type="hidden" name="delete_user_id" value="<?= $row['id']; ?>">
                                            <button type="submit" name="delete_user_btn" class="btn btn-danger btn-sm" style="width: fit-content;"
                                                onclick="return confirm('Are you sure you want to delete this user?');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                           <?php
                               }
                        }
                        else{
                          ?> 
                          <tr>
                            <td>No Record Fount</td>
                          </tr> 
                         <?php
                        } 
                        ?>         
                    </tbody>

                </table>

            </div>
        </div>
    </div>
</div>


<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center">Add New User</h5>
            </div>

            <form action="code.php" method="POST">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
                    <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </form>

        </div>

    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
