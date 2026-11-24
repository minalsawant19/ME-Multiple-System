<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('authentication.php');
include('dbcon.php');

if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    // FETCH RECORD
    $select_query = "SELECT * FROM tbl_inquiry WHERE id='$id' LIMIT 1";
    $select_run = mysqli_query($conn, $select_query);

    if (mysqli_num_rows($select_run) > 0) {

        $row = mysqli_fetch_assoc($select_run);

        // NULL SAFE DATES
        $follow_up_date = !empty($row['re_followup_date']) ? $row['re_followup_date'] : null;
        $visit_date     = !empty($row['visit_date']) ? $row['visit_date'] : null;
        $service_date   = !empty($row['service_date']) ? $row['service_date'] : null;
        $schedule_date  = !empty($row['schedule_date']) ? $row['schedule_date'] : null;

        // INSERT INTO tbl_delete
        $insert_query = "INSERT INTO tbl_delete
        (
            cust_name,
            cust_phone,
            cust_email,
            cust_address,
            cust_message,
            inq_date,
            follow_up_date,
            visit_date,
            service_date,
            schedule_date
        )
        VALUES
        (
            '{$row['name']}',
            '{$row['phone']}',
            '{$row['email']}',
            '{$row['address']}',
            '{$row['message']}',
            '{$row['inq_date']}',
            " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ",
            " . ($visit_date ? "'$visit_date'" : "NULL") . ",
            " . ($service_date ? "'$service_date'" : "NULL") . ",
            " . ($schedule_date ? "'$schedule_date'" : "NULL") . "
        )";

        $insert_run = mysqli_query($conn, $insert_query);

        if ($insert_run) {

            // DELETE FROM tbl_inquiry
            $delete_query = "DELETE FROM tbl_inquiry WHERE id='$id'";
            $delete_run = mysqli_query($conn, $delete_query);

            if ($delete_run) {
                $_SESSION['message'] = "Inquiry Deleted & Stored Successfully";
            } else {
                $_SESSION['message'] = "Delete Failed";
            }

        } else {
            $_SESSION['message'] = "Failed To Store Deleted Data";
        }

    } else {
        $_SESSION['message'] = "Inquiry Not Found";
    }

    header("Location: view-inquiry.php");
    exit();
}
?>