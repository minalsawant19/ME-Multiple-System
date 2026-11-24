<?php
ob_start();
include('authentication.php'); // session check
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php'); // database connection

// Form submission
if (isset($_POST['submit'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);

    // Handle multiple image uploads
    $imageFiles = $_FILES['images']; 
    $imageNames = [];

    $uploadDir = "uploads/";

    for ($i = 0; $i < count($imageFiles['name']); $i++) {
        $filename = time().'_'.$imageFiles['name'][$i];
        $tmpName = $imageFiles['tmp_name'][$i];
        if (move_uploaded_file($tmpName, $uploadDir.$filename)) {
            $imageNames[] = $filename;
        }
    }

    $imagesJSON = json_encode($imageNames);

    // Insert into DB
    $query = "INSERT INTO projects (title, description, category, images)
              VALUES ('$title', '$description', '$category', '$imagesJSON')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['status'] = "Project Added Successfully ✅";
        header("Location: admin.php"); // redirect to same page
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="container-fluid">

    <div class="inquiry-container">
        <h2>Add Solar Project</h2>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php } ?>
        <?php if (isset($_SESSION['status'])) { ?>
            <div class="alert alert-success"><?= $_SESSION['status']; unset($_SESSION['status']); ?></div>
        <?php } ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <label>Project Title</label>
            <input type="text" name="title" placeholder="Enter project title" required>

            <label>Project Description</label>
            <textarea name="description" placeholder="Enter project description" required></textarea>

            <label>Category</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
                <option value="industrial">Industrial</option>
            </select>

            <label>Project Images</label>
            <input type="file" name="images[]" multiple accept="image/*" required>

            <button type="submit" name="submit">Add Project</button>
        </form>
    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>