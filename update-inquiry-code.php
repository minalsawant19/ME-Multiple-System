<?php
session_start();
include('dbcon.php');

$id       = $_POST['id'];
$type     = $_POST['type'];

$name     = $_POST['name'];
$phone    = $_POST['phone'];
$email    = $_POST['email'];
$address  = $_POST['address'];

$date     = $_POST['date'];
$msg      = $_POST['message'];

// First update basic customer details
$basicUpdate = "
    UPDATE tbl_inquiry SET 
        name='$name',
        phone='$phone',
        email='$email',
        address='$address'
    WHERE id='$id'
";
mysqli_query($conn, $basicUpdate);

// Now handle type-based updates
$redirectPage = "view-inquiry.php"; // default

if($type == "refollowup"){
    $update = "UPDATE tbl_inquiry 
               SET message='$msg',
                   re_followup_date='$date',
                   inq_date=NULL
               WHERE id='$id'";
    $redirectPage = "view-refollowup.php";
}

else if($type == "visit"){
    $update = "UPDATE tbl_inquiry 
               SET message='$msg',
                   visit_date='$date',
                   re_followup_date=NULL
               WHERE id='$id'";
    $redirectPage = "view-visit.php";
}

else if($type == "service"){
    $update = "UPDATE tbl_inquiry 
               SET message='$msg',
                   service_date='$date',
                   visit_date=NULL
               WHERE id='$id'";
    $redirectPage = "view-future.php";
}

else if($type == "schedule"){
    $update = "UPDATE tbl_inquiry 
               SET message='$msg',
                   schedule_date='$date',
                   service_date=NULL
               WHERE id='$id'";
    $redirectPage = "view-schedule.php";
}

// Execute the type-specific update
if(mysqli_query($conn, $update)){
    $_SESSION['status'] = ucfirst($type)." Updated Successfully!";
} else {
    $_SESSION['status'] = "Update Failed!";
}

// Redirect to the appropriate page
header("Location: $redirectPage");
exit();
?>
