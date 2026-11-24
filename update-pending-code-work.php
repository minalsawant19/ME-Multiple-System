<?php
session_start();
include('dbcon.php');

if(isset($_POST['update_pending_work'])){
    $work_id = $_POST['work_id'];
    $client_id = $_POST['client_id'];
    $work_description = mysqli_real_escape_string($conn, $_POST['work_description']);
    $assigned_to = mysqli_real_escape_string($conn, $_POST['assigned_to']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $query = "UPDATE tbl_pending_work SET 
                work_description='$work_description', 
                assigned_to='$assigned_to', 
                due_date='$due_date', 
                status='$status'
              WHERE id='$work_id'";
    $query_run = mysqli_query($conn, $query);

    if($query_run){
        $_SESSION['status'] = "Pending work updated successfully!";
        header("Location: view-client-detail.php?client_id=$client_id");
        exit();
    } else {
        $_SESSION['status'] = "Failed to update pending work!";
        header("Location: update-pending-work.php?work_id=$work_id");
        exit();
    }
}
?>