<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Agar user logged out hai → login page pe bhejo
if(!isset($_SESSION['auth'])){
    $_SESSION['auth_status'] = "Login to Access Dashboard";
    header("Location: ../login.php"); // ../ because included from admin folder
    exit(0);
}
?>
