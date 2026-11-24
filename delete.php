<?php
include('authentication.php');
include('dbcon.php');

if (isset($_GET['id'])) {

    $id = mysqli_real_escape_string($conn, $_GET['id']);

    $delete_query = "DELETE FROM tbl_delete WHERE Id='$id'";
    $delete_run = mysqli_query($conn, $delete_query);

    if ($delete_run) {
        $_SESSION['message'] = "Record Permanently Deleted";
    } else {
        $_SESSION['message'] = "Delete Failed";
    }

    header("Location: view-delete.php");
    exit();
}
?>