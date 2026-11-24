<?php
$servername = "localhost";
$username = "fsd83429ult423ip_me_multiplesystem";
$password = "3Wyb9)j**;=^hvEF";
$dbname = "fsd83429ult423ip_me_multisystem";

// Create connection
$conn = mysqli_connect("localhost", "fsd83429ult423ip_me_multiplesystem", "3Wyb9)j**;=^hvEF", "fsd83429ult423ip_me_multisystem");
// Check connection
if (!$conn) {

   die("Connection failed: " . mysqli_connect_error());
}else{
    //  echo "Connected successfully";
}

// mysqli_close($conn);   
?>