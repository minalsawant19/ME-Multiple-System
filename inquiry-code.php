<?php
session_start();
include('dbcon.php');

if(isset($_POST['save_inquiry'])){
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $message = $_POST['message'];
    $source = $_POST['source']; // 👈 important

    $stmt = $conn->prepare("INSERT INTO tbl_inquiry (name, phone, email, address, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $phone, $email, $address, $message);

    if($stmt->execute()){
        if($source == "website"){
            echo "<script>
                    alert('✅ Thank you! Our company will contact you soon...');
                    window.location.href='contact.html';
                  </script>";
        }
        else if($source == "admin"){
            $_SESSION['status'] = "Inquiry Added Successfully!";
            header("Location: view-inquiry.php");
        }

    } else {

        if($source == "website"){
            echo "<script>
                    alert('❌ Failed to send message!');
                    window.location.href='contact.html';
                  </script>";
        }
        else{
            $_SESSION['status'] = "Failed to Add Inquiry!";
            header("Location: add-inquiry.php");
        }

    }
    exit();

} else {
    header("Location: contact.html");
    exit();
}
?>
