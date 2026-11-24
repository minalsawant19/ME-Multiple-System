<?php
include('dbcon.php');
session_start();

if (isset($_POST['client_id']) && isset($_FILES['documents'])) {

    $id = $_POST['client_id'];  // Correct Client ID
    $uploadedFiles = [];
    $uploadDir = "uploads/client_docs/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Fetch existing documents
    $existingDocsQuery = "SELECT documents FROM tbl_client WHERE id='$id'";
    $result = mysqli_query($conn, $existingDocsQuery);
    $existingDocs = mysqli_fetch_assoc($result);
    $existingFiles = $existingDocs['documents'] ? json_decode($existingDocs['documents'], true) : [];

    // Upload files
    foreach ($_FILES['documents']['name'] as $key => $name) {
        if ($_FILES['documents']['error'][$key] == 0) {
            $tmpName = $_FILES['documents']['tmp_name'][$key];
            $fileName = time() . "_" . basename($name);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($tmpName, $filePath)) {
                $existingFiles[] = $fileName;
            }
        }
    }

    // Update DB
    if (!empty($existingFiles)) {
        $jsonFiles = json_encode($existingFiles);
        mysqli_query($conn, "UPDATE tbl_client SET documents='$jsonFiles' WHERE id='$id'");

        $_SESSION['message'] = "Documents uploaded successfully ✔";
        $_SESSION['msg_type'] = "success";
    }

    header("Location: view-client-detail.php?client_id=" . $id);
    exit;

} else {
    header("Location: view-client-detail.php");
    exit;
}
