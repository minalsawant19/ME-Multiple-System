<?php
session_start();
include('dbcon.php');

if(isset($_POST['add_pending_work'])){
    $client_id = $_POST['client_id'];
    $work_description = mysqli_real_escape_string($conn, $_POST['work_description']);
    $assigned_to = mysqli_real_escape_string($conn, $_POST['assigned_to']);
    $due_date = $_POST['due_date'];

    $query = "INSERT INTO tbl_pending_work (client_id, work_description, assigned_to, due_date, status) 
              VALUES ('$client_id', '$work_description', '$assigned_to', '$due_date', 'Pending')";
    $query_run = mysqli_query($conn, $query);

    if($query_run){
        $_SESSION['status'] = "Pending work added successfully!";
        header("Location: pending-work.php?client_id=$client_id");
        exit();
    } else {
        $_SESSION['status'] = "Failed to add pending work!";
        header("Location: add-pending-work.php?client_id=$client_id");
        exit();
    }
}
?>