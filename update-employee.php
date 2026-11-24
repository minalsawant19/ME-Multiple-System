<?php
ob_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

if(!isset($_GET['id'])){
    header("Location: view-employee.php");
    exit;
}

$id = (int)$_GET['id'];


// ====================== UPDATE EMPLOYEE ===========================
if(isset($_POST['update_employee'])){

    // Profile photo handling
    if(!empty($_FILES['profile_photo']['name'])){
        $profile_photo = time().'_'.basename($_FILES['profile_photo']['name']);
        move_uploaded_file($_FILES['profile_photo']['tmp_name'], 'uploads/'.$profile_photo);
    } else {
        $profile_photo = $_POST['old_profile_photo'] ?? '';
    }

    // -------- ONLY FIELDS PRESENT IN FORM WILL BE UPDATED --------
    $updateFields = [];

    foreach ($_POST as $key => $value) {

        if ($key == "update_employee" || $key == "old_profile_photo") continue;

        // Skip profile photo (handled separately)
        if ($key == "profile_photo") continue;

        // Empty fields do NOT overwrite existing values
        if ($value !== "") {
            $value = mysqli_real_escape_string($conn, $value);
            $updateFields[] = "$key='$value'";
        }
    }

    // Add profile photo to update list
    $updateFields[] = "profile_photo='$profile_photo'";

    // Create final update query
    $updateQuery = "UPDATE tbl_employee SET ".implode(", ", $updateFields)." WHERE id=$id";

    if(mysqli_query($conn, $updateQuery)){
        $_SESSION['message'] = "Employee updated successfully!";
        header("Location: view-employee.php?id=$id");
        exit;
    } 
    else {
        echo "Update failed: " . mysqli_error($conn);
    }
}



// =============== FETCH EMPLOYEE DATA ===========================
$employee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_employee WHERE id=$id"));
?>


<!-- =========================================
      UPDATE EMPLOYEE FORM
========================================= -->
<div class="container-fluid">
<a href="view-all-employee.php?client_id=<?= $id; ?>" class="btn btn-secondary mb-3">&larr; Back</a>

<div class="inquiry-container">
    <h2>Update Employee Details</h2>

    <form action="" method="POST" enctype="multipart/form-data">

        <input type="hidden" name="old_profile_photo" value="<?= $employee['profile_photo'] ?>">

        <!-- PERSONAL DETAILS -->
        <h4>Personal Details</h4>

        <div class="row">
            <div class="col">
                <label>Title</label>
                <select name="title">
                    <option value="Mr." <?= ($employee['title'] == "Mr.") ? "selected" : "" ?>>Mr.</option>
                    <option value="Mrs." <?= ($employee['title'] == "Mrs.") ? "selected" : "" ?>>Mrs.</option>
                    <option value="Ms." <?= ($employee['title'] == "Ms.") ? "selected" : "" ?>>Ms.</option>
                </select>
            </div>

            <div class="col">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($employee['name']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($employee['phone']) ?>" required>
            </div>
            <div class="col">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($employee['dob']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Gender</label>
                <select name="gender">
                    <option value="Male" <?= ($employee['gender'] == "Male") ? "selected" : "" ?>>Male</option>
                    <option value="Female" <?= ($employee['gender'] == "Female") ? "selected" : "" ?>>Female</option>
                    <option value="Other" <?= ($employee['gender'] == "Other") ? "selected" : "" ?>>Other</option>
                </select>
            </div>
            <div class="col">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Current Address</label>
                <input type="text" name="current_address" value="<?= htmlspecialchars($employee['current_address']) ?>">
            </div>
            <div class="col">
                <label>Permanent Address</label>
                <input type="text" name="permanent_address" value="<?= htmlspecialchars($employee['permanent_address']) ?>">
            </div>
        </div>

        <!-- Profile Photo -->
        <label>Old Profile Photo</label><br>
        <img src="uploads/<?= htmlspecialchars($employee['profile_photo']) ?>" width="90" height="90"><br><br>
        <label>Change Photo</label>
        <input type="file" name="profile_photo">
        <br><br>


        <!-- EDUCATION DETAILS -->
        <h4>Education Details</h4>

        <div class="row">
            <div class="col">
                <label>10th</label>
                <input type="text" name="tenth" value="<?= htmlspecialchars($employee['tenth']) ?>">
            </div>

            <div class="col">
                <label>12th</label>
                <input type="text" name="twelfth" value="<?= htmlspecialchars($employee['twelfth']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Graduation</label>
                <input type="text" name="graduation" value="<?= htmlspecialchars($employee['graduation']) ?>">
            </div>

            <div class="col">
                <label>Post Graduation</label>
                <input type="text" name="post_graduation" value="<?= htmlspecialchars($employee['post_graduation']) ?>">
            </div>
        </div>

        <!-- EMPLOYEE DETAILS -->
        <h4>Employee Details</h4>

        <div class="row">
    <div class="col">
        <label>Employee Type</label>
        <select name="employee_type" required>
            <option value="">Select Employee Type</option>
            <option value="Full-Time" <?= ($employee['employee_type'] == "Full-Time") ? "selected" : "" ?>>Full-Time</option>
            <option value="Part-Time" <?= ($employee['employee_type'] == "Part-Time") ? "selected" : "" ?>>Part-Time</option>
            <option value="Per Day" <?= ($employee['employee_type'] == "Per Day") ? "selected" : "" ?>>Per Day</option>
        </select>
    </div>

            <div class="col">
                <label>Department</label>
                <input type="text" name="department" value="<?= htmlspecialchars($employee['department']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Package</label>
                <input type="text" name="package" value="<?= htmlspecialchars($employee['package']) ?>">
            </div>

            <div class="col">
                <label>Joining Date</label>
                <input type="date" name="joining_date" value="<?= htmlspecialchars($employee['joining_date']) ?>">
            </div>

            <div class="col">
                <label>Designation</label>
                <input type="text" name="designation" value="<?= htmlspecialchars($employee['designation']) ?>">
            </div>
        </div>

        <!-- BANK DETAILS -->
        <h4>Bank Details</h4>

        <div class="row">
            <div class="col">
                <label>Account No</label>
                <input type="text" name="account_no" value="<?= htmlspecialchars($employee['account_no']) ?>">
            </div>

            <div class="col">
                <label>Bank Name</label>
                <input type="text" name="bank_name" value="<?= htmlspecialchars($employee['bank_name']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Branch Name</label>
                <input type="text" name="branch_name" value="<?= htmlspecialchars($employee['branch_name']) ?>">
            </div>

            <div class="col">
                <label>IFSC Code</label>
                <input type="text" name="ifsc_code" value="<?= htmlspecialchars($employee['ifsc_code']) ?>">
            </div>
        </div>

        <!-- GOVT DETAILS -->
        <div class="row">
            <div class="col">
                <label>Aadhar No</label>
                <input type="text" name="aadhar_no" value="<?= htmlspecialchars($employee['aadhar_no']) ?>">
            </div>

            <div class="col">
                <label>PAN No</label>
                <input type="text" name="pan_no" value="<?= htmlspecialchars($employee['pan_no']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>UAN No</label>
                <input type="text" name="uan_no" value="<?= htmlspecialchars($employee['uan_no']) ?>">
            </div>

            <div class="col">
                <label>PF No</label>
                <input type="text" name="pf_no" value="<?= htmlspecialchars($employee['pf_no']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>ESIC No</label>
                <input type="text" name="esic_no" value="<?= htmlspecialchars($employee['esic_no']) ?>">
            </div>
        </div>

        <!-- SUBMIT -->
        <button type="submit" name="update_employee" class="">Update</button>

    </form>
</div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
ob_end_flush();
?>
