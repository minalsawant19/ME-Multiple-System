<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<!-- Begin Page Content -->
<div class="form-section">
    <h2>Add Employee Details</h2>

    <form action="add-employee-code.php" method="POST" enctype="multipart/form-data">

    <!-- PERSONAL DETAILS -->
    <div class="section-header">
        <span><i class="fa-solid fa-user"></i> Personal Details</span>
        <i class="fa-solid fa-chevron-down arrow"></i>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col">
                <label>Title</label>
                <select name="title">
                    <option>Mr.</option>
                    <option>Mrs.</option>
                    <option>Ms.</option>
                </select>
            </div>

            <div class="col">
                <label>Name</label>
                <input type="text" name="name" placeholder="Enter employee's name" required/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="Enter phone number" required/>
            </div>

            <div class="col">
                <label>Date of Birth</label>
                <input type="date" name="dob" required/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Gender</label>
                <select name="gender">
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="col">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter email address" required/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Relationship</label>
                <select name="relationship">
                    <option>Select Relation</option>
                    <option>Father</option>
                    <option>Mother</option>
                    <option>Husband</option>
                    <option>Wife</option>
                </select>
            </div>

            <div class="col">
                <label>Alternate Phone</label>
                <input type="text" name="alternate_phone" placeholder="Enter alternate phone number"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Mother's Name</label>
                <input type="text" name="mother_name" placeholder="Enter mother's name"/>
            </div>

            <div class="col">
                <label>Marital Status</label>
                <select name="marital_status">
                    <option>Single</option>
                    <option>Married</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Current Address</label>
                <input type="text" name="current_address" placeholder="Enter current address"/>
            </div>

            <div class="col">
                <label>Permanent Address</label>
                <input type="text" name="permanent_address" placeholder="Enter permanent address"/>
            </div>
        </div>
    </div>

    <!-- EDUCATION DETAILS -->
    <div class="section-header">
        <span><i class="fa-solid fa-graduation-cap"></i> Education Details</span>
        <i class="fa-solid fa-chevron-down arrow"></i>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col">
                <label>10th</label>
                <input type="text" name="tenth" placeholder="Enter 10th qualification"/>
            </div>

            <div class="col">
                <label>12th</label>
                <input type="text" name="twelfth" placeholder="Enter 12th qualification"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Graduation</label>
                <input type="text" name="graduation" placeholder="Enter graduation qualification"/>
            </div>

            <div class="col">
                <label>Post Graduation</label>
                <input type="text" name="post_graduation" placeholder="Enter post graduation qualification"/>
            </div>
        </div>
    </div>

    <!-- EMPLOYEE DETAILS -->
    <div class="section-header">
        <span><i class="fa-solid fa-id-card"></i> Employee Details</span>
        <i class="fa-solid fa-chevron-down arrow"></i>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col">
                <label>Employee Type</label>
                <select name="employee_type">
                    <option>Select Type</option>
                    <option value="fulltime">Full-Time</option>
                    <option value="parttime">Part-Time</option>
                    <option value="perday">Per Day</option>
                </select>
            </div>

            <div class="col">
                <label>Department</label>
                <input type="text" name="department" placeholder="Department"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Package</label>
                <input type="text" name="package" placeholder="Enter package"/>
            </div>

            <div class="col">
                <label>Joining Date</label>
                <input type="date" name="joining_date"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Designation</label>
                <input type="text" name="designation" placeholder="Enter designation"/>
            </div>
        </div>
    </div>

    <!-- KYC DETAILS -->
    <div class="section-header">
        <span><i class="fa-solid fa-folder-open"></i> KYC Details</span>
        <i class="fa-solid fa-chevron-down arrow"></i>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col">
                <label>Aadhar No</label>
                <input type="text" name="aadhar_no" placeholder="Enter Aadhar Number"/>
            </div>

            <div class="col">
                <label>PAN Card No</label>
                <input type="text" name="pan_no" placeholder="Enter PAN Number"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>Profile Photo</label>
                <input type="file" name="profile_photo" accept="image/*"/>
            </div>
        </div>
    </div>

    <!-- PROVIDENT FUND & ESIC -->
    <div class="section-header">
        <span><i class="fa-solid fa-shield"></i> Provident Fund & ESIC</span>
        <i class="fa-solid fa-chevron-down arrow"></i>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col">
                <label>UAN No</label>
                <input type="text" name="uan_no" placeholder="Enter UAN Number"/>
            </div>

            <div class="col">
                <label>PF No</label>
                <input type="text" name="pf_no" placeholder="Enter PF Number"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>ESIC No</label>
                <input type="text" name="esic_no" placeholder="Enter ESIC Number"/>
            </div>
        </div>
    </div>

    <button type="submit" class="submit-btn">Submit</button>
    </form>
</div>

<script>
document.querySelectorAll(".section-header").forEach((header) => {
    header.addEventListener("click", () => {
        header.classList.toggle("active");
        let body = header.nextElementSibling;
        body.style.display = body.style.display === "block" ? "none" : "block";
    });
});
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
