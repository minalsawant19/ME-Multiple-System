<?php
include('dbcon.php');

if(isset($_POST['save_vendor'])){
    $name = $_POST['vendor_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    mysqli_query($conn,"INSERT INTO tbl_vendor (vendor_name, phone, email, address) 
                        VALUES ('$name','$phone','$email','$address')");

    $_SESSION['status'] = "Vendor Added Successfully";
    header("Location: view-vendor.php");
    exit();
}
?>