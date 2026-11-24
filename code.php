<?php
session_start();
include('authentication.php');
include('dbcon.php');



// LOGOUT -----------------------------
if(isset($_POST['logout_btn']))
{
    unset($_SESSION['auth']);
    unset($_SESSION['auth_user']);

    $_SESSION['status'] = "Logged Out Successfully!";
    header("Location: login.php");
    exit();
}



if(isset($_POST['save_user']))
    {
      $name = $_POST['name'];
      $phone = $_POST['phone'];
      $email = $_POST['email'];
      $password = $_POST['password'];

      $user_query = "INSERT INTO users (name,phone,email,password) VALUES ('$name','$phone','$email','$password')";
      $user_query_run = mysqli_query($conn, $user_query);

      if($user_query_run)
      {
        $_SESSION['status'] = "User added successfuly";
        header("Location: register.php");
      }
      else{
        $_SESSION['status'] = "User added Failed";
        header("Location: register.php");
      }

    }




    // ---------------- Update User ------------------

if(isset($_POST['update_user']))
{
    $user_id  = $_POST['user_id'];
    $name     = $_POST['name'];
    $phone    = $_POST['phone'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

   
        $query = "UPDATE users SET name='$name', phone='$phone', email='$email' , password='$password' WHERE id='$user_id'";
         $query_run = mysqli_query($conn, $query);

    if($query_run)
    {
        $_SESSION['status'] = "User Updated Successfully!";
        header("Location: register.php");
    }
    else
    {
        $_SESSION['status'] = "Something Went Wrong!";
        header("Location: register.php");
    }
}

// DELETE USER
if (isset($_POST['delete_user_btn'])) {

  $user_id = $_POST['delete_user_id'];

  $query = "DELETE FROM users WHERE id='$user_id'";
  $query_run = mysqli_query($conn, $query);

  if ($query_run) {
    $_SESSION['status'] = "User Deleted Successfully";
  } else {
    $_SESSION['status'] = "User Deletion Failed";
  }

  header("Location: register.php");
  exit();
}
?>
?>

