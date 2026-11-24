<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include('authentication.php');
include('dbcon.php');

// Check if form is submitted
if(isset($_POST)) {

    // Personal Details
    $title          = $_POST['title'] ?? '';
    $name           = $_POST['name'] ?? '';
    $phone          = $_POST['phone'] ?? '';
    $dob            = $_POST['dob'] ?? '';
    $gender         = $_POST['gender'] ?? '';
    $email          = $_POST['email'] ?? '';
    $relationship   = $_POST['relationship'] ?? '';
    $alt_phone      = $_POST['alt_phone'] ?? '';
    $mother_name    = $_POST['mother_name'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $current_address = $_POST['current_address'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';

    // Education
    $tenth          = $_POST['tenth'] ?? '';
    $twelfth        = $_POST['twelfth'] ?? '';
    $graduation     = $_POST['graduation'] ?? '';
    $post_graduation = $_POST['post_graduation'] ?? '';

    // Experience
    $experience_type = $_POST['experience_type'] ?? '';
    $previous_experience = $_POST['previous_experience'] ?? '';
    $work_experience_years = !empty($_POST['work_experience_years'])
    ? (int)$_POST['work_experience_years']
    : 0;
    $previous_company_name = $_POST['previous_company_name'] ?? '';
    $previous_designation = $_POST['previous_designation'] ?? '';
    $previous_salary = (
    isset($_POST['previous_salary']) &&
    $_POST['previous_salary'] !== ''
)
    ? (float)$_POST['previous_salary']
    : 0.00;

    // Employee Details
    $employee_type = $_POST['employee_type'] ?? '';
    $department    = $_POST['department'] ?? '';
    $package = (
    isset($_POST['package']) && $_POST['package'] !== ''
)
    ? (float)$_POST['package']
    : 0.00;
    $joining_date  = $_POST['joining_date'] ?? '';
    $designation   = $_POST['designation'] ?? '';

    // Bank Details
    $account_no    = $_POST['account_no'] ?? '';
    $bank_name     = $_POST['bank_name'] ?? '';
    $branch_name   = $_POST['branch_name'] ?? '';
    $ifsc_code     = $_POST['ifsc_code'] ?? '';

    // KYC Details
    $aadhar_no     = $_POST['aadhar_no'] ?? '';
    $pan_no        = $_POST['pan_no'] ?? '';

    // PF & ESIC
    $uan_no        = $_POST['uan_no'] ?? '';
    $pf_no         = $_POST['pf_no'] ?? '';
    $esic_no       = $_POST['esic_no'] ?? '';

    // Profile Photo Upload
    $profile_photo = '';
    if(isset($_FILES['profile_photo']['name']) && $_FILES['profile_photo']['name'] != ''){
        $file_name = time().'_'.$_FILES['profile_photo']['name'];
        $file_tmp  = $_FILES['profile_photo']['tmp_name'];
        $file_path = 'uploads/profile_photos/'.$file_name;
        move_uploaded_file($file_tmp, $file_path);
        $profile_photo = $file_name;
    }

    // Insert into Database
    $query = "INSERT INTO tbl_employee 
    (title, name, phone, dob, gender, email, relationship, alt_phone, mother_name, marital_status, current_address, permanent_address,
     tenth, twelfth, graduation, post_graduation,
     experience_type, previous_experience, work_experience_years, previous_company_name, previous_designation, previous_salary,
     employee_type, department, package, joining_date, designation,
     account_no, bank_name, branch_name, ifsc_code,
     aadhar_no, pan_no, profile_photo,
     uan_no, pf_no, esic_no)
     VALUES
    ('$title','$name','$phone','$dob','$gender','$email','$relationship','$alt_phone','$mother_name','$marital_status','$current_address','$permanent_address',
     '$tenth','$twelfth','$graduation','$post_graduation',
     '$experience_type','$previous_experience','$work_experience_years','$previous_company_name','$previous_designation','$previous_salary',
     '$employee_type','$department','$package','$joining_date','$designation',
     '$account_no','$bank_name','$branch_name','$ifsc_code',
     '$aadhar_no','$pan_no','$profile_photo',
     '$uan_no','$pf_no','$esic_no')";

    if(mysqli_query($conn, $query)){
        $_SESSION['status'] = "Employee Added Successfully!";
        header("Location: view-all-employee.php");
        exit();
    } else {
        $_SESSION['status'] = "Failed to Add Employee!";
        header("Location: add-employee.php");
        exit();
    }
}
?>
