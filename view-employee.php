<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$id = $_GET['id'];
$emp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_employee WHERE id='$id'"));

?>

<div class="container-fluid">
    <button
        type="button"
        class="btn btn-secondary mb-3" style="width: fit-content;"
        onclick="window.location.href='view-all-employee.php?client_id=<?= $id; ?>'">
        &larr; Back
    </button>

    <div class="profile-wrapper">

        <!-- LEFT PANEL -->
        <div class="left-panel">
            <img src="uploads/<?= $emp['profile_photo']; ?>" class="profile-avatar">

            <h2 class="emp"><?= $emp['name']; ?></h2>
            <p><?= $emp['designation'] ?></p>

            <hr>

            <div class="info-item"><i class="fa-solid fa-phone"></i>
                <span><?= $emp['phone']; ?></span>
            </div>

            <div class="info-item"><i class="fa-solid fa-envelope"></i>
                <span><?= $emp['email']; ?></span>
            </div>

            <div class="info-item"><i class="fa-solid fa-cake-candles"></i>
                <span><?= $emp['dob']; ?></span>
            </div>

            <hr>

            <button class="edit-btn" onclick="window.location.href='update-employee.php?id=<?= $emp['id']; ?>'">
                <i class="fa-solid fa-pen-to-square"></i> Update Profile
            </button>
            <button class="btn-attendance" onclick="window.location.href='view-detail-attendance.php?id=<?= $emp['id']; ?>'">
                <i class="fa-solid fa-calendar-check"></i>View Attendance
            </button>
            <button class="salary" onclick="window.location.href='view-salary.php?employee_id=<?= $emp['id'] ?>'">
                <i class="fa-solid fa-calendar-check"></i>View Salary
            </button>
            <button class="btn-docs" onclick="openUploadPopup()">
                <i class="fa-solid fa-file-upload"></i> Upload Docs
            </button>
            <button class="btn btn-danger mt-2 w-100"
onclick="window.location.href='employee-resignation.php?employee_id=<?= $emp['id']; ?>'">

<i class="fa-solid fa-user-minus"></i>
Resignation

</button>

        </div>


        <!-- RIGHT PANEL CONTENT -->
        <div class="right-panel">

            <!-- TABS -->
            <div class="tabs">
                <button class="tab active" data-target="personal">Personal</button>
                <button class="tab" data-target="employee">Employee</button>
                <button class="tab" data-target="education">Education</button>
                <button class="tab" data-target="bank">Bank</button>
                <button class="tab" data-target="kyc">KYC</button>
                <button class="tab" data-target="documents">Documents</button>
            </div>


            <!-- TAB CONTENT -->

            <!-- PERSONAL -->
            <div class="content active" id="personal">
                <div class="row"><span>Gender:</span>
                    <p><?= $emp['gender']; ?></p>
                </div>
                <div class="row"><span>Current Address:</span>
                    <p><?= $emp['current_address']; ?></p>
                </div>
                <div class="row"><span>Permanent Address:</span>
                    <p><?= $emp['permanent_address']; ?></p>
                </div>
            </div>

            <!-- EMPLOYEE -->
            <div class="content" id="employee">
                <div class="row"><span>Type:</span>
                    <p><?= $emp['employee_type']; ?></p>
                </div>
                <div class="row"><span>Department:</span>
                    <p><?= $emp['department']; ?></p>
                </div>
                <div class="row"><span>Joining Date:</span>
                    <p><?= $emp['joining_date']; ?></p>
                </div>
                <div class="row"><span>Package:</span>
                    <p><?= $emp['package']; ?></p>
                </div>
                <div class="row"><span>Designation:</span>
                    <p><?= $emp['designation']; ?></p>
                </div>
            </div>

            <!-- EDUCATION -->
            <div class="content" id="education">
                <div class="row"><span>10th:</span>
                    <p><?= $emp['tenth']; ?></p>
                </div>
                <div class="row"><span>12th:</span>
                    <p><?= $emp['twelfth']; ?></p>
                </div>
                <div class="row"><span>Graduation:</span>
                    <p><?= $emp['graduation']; ?></p>
                </div>
                <div class="row"><span>Post Graduation:</span>
                    <p><?= $emp['post_graduation']; ?></p>
                </div>
            </div>

            <!-- BANK -->
            <div class="content" id="bank">
                <div class="row"><span>Account No:</span>
                    <p><?= $emp['account_no']; ?></p>
                </div>
                <div class="row"><span>Bank Name:</span>
                    <p><?= $emp['bank_name']; ?></p>
                </div>
                <div class="row"><span>Branch:</span>
                    <p><?= $emp['branch_name']; ?></p>
                </div>
                <div class="row"><span>IFSC:</span>
                    <p><?= $emp['ifsc_code']; ?></p>
                </div>
            </div>

            <!-- KYC -->
            <div class="content" id="kyc">
                <div class="row"><span>Aadhar:</span>
                    <p><?= $emp['aadhar_no']; ?></p>
                </div>
                <div class="row"><span>PAN:</span>
                    <p><?= $emp['pan_no']; ?></p>
                </div>
                <div class="row"><span>UAN:</span>
                    <p><?= $emp['uan_no']; ?></p>
                </div>
                <div class="row"><span>PF No:</span>
                    <p><?= $emp['pf_no']; ?></p>
                </div>
                <div class="row"><span>ESIC No:</span>
                    <p><?= $emp['esic_no']; ?></p>
                </div>
            </div>

            <div class="content" id="documents">
                <!-- TOGGLE HEADER -->
                <div class="doc-toggle-header" onclick="toggleDocs()">
                    <span>Documents</span>
                    <i id="docArrow" class="arrow">&#9656;</i>
                </div>

                <!-- COLLAPSIBLE DOCUMENT BOX -->
                <div id="docBox" class="document-box">

                    <?php
                    if (!empty($emp['documents'])) {

                        $documents = json_decode($emp['documents'], true);

                        if (!empty($documents)) {

                            foreach ($documents as $doc) {

                                // File path
                                $filePath = "uploads/docs/" . $doc;

                                // ===============================
                                // REMOVE RANDOM NUMBER FROM NAME
                                // ===============================
                                $displayName = $doc;

                                // agar filename me "_" hai to uske baad ka naam lo
                                if (strpos($doc, '_') !== false) {
                                    $displayName = substr($doc, strpos($doc, '_') + 1);
                                }
                    ?>

                                <div class="document-item">
                                    <div class="doc-name">
                                        <?= htmlspecialchars($displayName); ?>
                                    </div>

                                    <button
                                        type="button"
                                        class="docs" style="width: fit-content;"
                                        onclick="window.open('<?= htmlspecialchars($filePath); ?>', '_blank')">
                                        View
                                    </button>
                                </div>

                    <?php
                            }
                        } else {
                            echo "<p>No documents uploaded.</p>";
                        }
                    } else {
                        echo "<p>No documents uploaded.</p>";
                    }
                    ?>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- UPLOAD DOCUMENT POPUP -->
<div id="uploadPopup" class="popup-overlay">
    <div class="popup-box">
        <h2>Upload Documents</h2>
        <p class="subtitle">Select document file and upload</p>

        <form id="uploadForm" action="upload-docs-code.php?id=<?= $emp['id']; ?>" method="POST" enctype="multipart/form-data">

            <label class="input-label">Upload Document</label>
            <input type="file" name="documents[]" multiple class="file-input" required>

            <input type="hidden" name="submit" value="<?= $emp['id']; ?>">

            <button type="submit" class="upload-btn">Upload</button>
        </form>

        <button class="close-btn" onclick="closeUploadPopup()">Close</button>
    </div>
</div>


<script>
    document.querySelectorAll(".tab").forEach(tab => {
        tab.addEventListener("click", () => {

            document.querySelector(".tab.active").classList.remove("active");
            tab.classList.add("active");

            let target = tab.getAttribute("data-target");

            document.querySelector(".content.active").classList.remove("active");
            document.getElementById(target).classList.add("active");
        });
    });

    function openUploadPopup() {
        document.getElementById("uploadPopup").style.display = "flex";
    }

    function closeUploadPopup() {
        document.getElementById("uploadPopup").style.display = "none";
    }


    function toggleDocs() {
        const box = document.getElementById("docBox");
        const arrow = document.getElementById("docArrow");

        if (box.style.maxHeight) {
            box.style.maxHeight = null; // Close
            arrow.style.transform = "rotate(0deg)";
        } else {
            box.style.maxHeight = box.scrollHeight + "px"; // Open
            arrow.style.transform = "rotate(90deg)";
        }
    }
</script>
<?php
include('includes/scripts.php');
include('includes/footer.php');
?>