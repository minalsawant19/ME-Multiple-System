<?php
include('authentication.php');
include('dbcon.php');

if (isset($_POST['quotation_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['quotation_id']);

    // Optional: First fetch PDF to delete the file
    $result = mysqli_query($conn, "SELECT pdf_file FROM tbl_quotation WHERE id='$id' LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    if ($row && !empty($row['pdf_file']) && file_exists($row['pdf_file'])) {
        unlink($row['pdf_file']); // Delete the PDF from server
    }

    // Delete the quotation from the database
    $delete_query = "DELETE FROM tbl_quotation WHERE id='$id'";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['message'] = "Quotation deleted successfully!";
    } else {
        $_SESSION['message'] = "Failed to delete quotation!";
    }
}

header("Location: view-quotations.php"); // Redirect back to your main page
exit;
?>
