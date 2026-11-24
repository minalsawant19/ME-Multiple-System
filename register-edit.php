<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');


if(isset($_GET['id']))
{
    $user_id = $_GET['id'];

    $query = "SELECT * FROM users WHERE id='$user_id'";
    $query_run = mysqli_query($conn, $query);

    if(mysqli_num_rows($query_run) > 0)
    {
        $row = mysqli_fetch_array($query_run);
    }
    else
    {
        echo "<h4>No Record Found</h4>";
        exit();
    }
}
else
{
    echo "<h4>No User ID Found</h4>";
    exit();
}
?>

<div class="container-fluid">
    <a href="register.php" class="btn btn-secondary">&larr; Back</a>
    <div class="inquiry-container">
            <h2>Update User</h2>
            <form action="code.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">

                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo $row['name']; ?>" class="form-control" required>

                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo $row['phone']; ?>" class="form-control" required>
          
        
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo $row['email']; ?>" class="form-control" required>
              
          
                    <label>Password</label>
                    <input type="password" name="password" value="<?php echo $row['password']; ?>" class="form-control">
      

                    <button type="submit" name="update_user" class="">Update</button>
                    
            </form>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
