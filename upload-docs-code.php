<?php
include('dbcon.php');

if (isset($_POST['submit']) && isset($_FILES['documents'])) {

    $id = $_POST['submit'];  // Use 'submit' value for employee ID
    $uploadedFiles = [];
    $uploadDir = "uploads/docs/";

    // Make sure upload directory exists
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Fetch existing documents (if any)
    $existingDocsQuery = "SELECT documents FROM tbl_employee WHERE id='$id'";
    $result = mysqli_query($conn, $existingDocsQuery);
    $existingDocs = mysqli_fetch_assoc($result);
    $existingFiles = $existingDocs['documents'] ? json_decode($existingDocs['documents'], true) : [];

    // Loop through all uploaded files
    foreach ($_FILES['documents']['name'] as $key => $name) {
        if ($_FILES['documents']['error'][$key] == 0) {
            $tmpName = $_FILES['documents']['tmp_name'][$key];
            $fileName = time() . "_" . basename($name);
            $filePath = $uploadDir . $fileName;

            // Move the uploaded file to the directory
            if (move_uploaded_file($tmpName, $filePath)) {
                // Append new file to existing files array
                $existingFiles[] = $fileName;
            }
        }
    }

    // If files are uploaded, store the updated list of files in the database
    if (!empty($existingFiles)) {
        $jsonFiles = json_encode($existingFiles);  // Convert the array to JSON
        $updateSql = "UPDATE tbl_employee SET documents='$jsonFiles' WHERE id='$id'";
        mysqli_query($conn, $updateSql);

        $_SESSION['message'] = "Documents uploaded successfully ✔";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "No file selected ❌";
        $_SESSION['msg_type'] = "error";
    }

    // Redirect back to view page
    header("Location: view-employee.php?id=" . $id);
    exit;
} else {
    header("Location: view-employee.php");
    exit;
}
