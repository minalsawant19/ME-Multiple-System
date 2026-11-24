<?php
session_start();
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

/*==========================
  GET EMPLOYEE DETAILS
===========================*/
if (!isset($_GET['employee_id'])) {
    echo "<script>alert('Employee ID Missing');window.location='view-all-employee.php';</script>";
    exit();
}

$employee_id = intval($_GET['employee_id']);

$stmt = $conn->prepare("SELECT * FROM tbl_employee WHERE id=?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Employee Not Found');window.location='view-all-employee.php';</script>";
    exit();
}

$employee = $result->fetch_assoc();


/*==========================
   SAVE RESIGNATION
===========================*/
if(isset($_POST['submit']))
{

    $employee_id = $_POST['employee_id'];

    $resignation_type = mysqli_real_escape_string($conn,$_POST['resignation_type']);
    $resignation_date = !empty($_POST['resignation_date']) ? $_POST['resignation_date'] : NULL;
    $notice_period = !empty($_POST['notice_period']) ? $_POST['notice_period'] : NULL;
    $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : NULL;
    $abscond_date = !empty($_POST['abscond_date']) ? $_POST['abscond_date'] : NULL;
    $reason = mysqli_real_escape_string($conn,$_POST['reason_for_leaving']);

    $query="UPDATE tbl_employee SET

    resignation_type='$resignation_type',
    resignation_date=".($resignation_date ? "'$resignation_date'" : "NULL").",
    notice_period=".($notice_period ? "'$notice_period'" : "NULL").",
    release_date=".($release_date ? "'$release_date'" : "NULL").",
    abscond_date=".($abscond_date ? "'$abscond_date'" : "NULL").",
    reason_for_leaving='$reason'

    WHERE id='$employee_id'
    ";

    $query_run=mysqli_query($conn,$query);

    if($query_run)
    {
        $_SESSION['success']="Employee Resigned Successfully";

        header("Location:view-employee-detail.php?id=".$employee_id);
        exit();
    }
    else
    {
        $_SESSION['error']="Something Went Wrong";
    }

}
?>

<style>
.container-fluid .row{
    display:flex !important;
    flex-wrap:wrap !important;
    margin-left:-15px !important;
    margin-right:-15px !important;
    border-bottom:none !important;
    padding-bottom:0 !important;
    gap:0 !important;
}
</style>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-danger text-white">

<h4>
Employee Resignation
<a href="view-employee.php?id=<?= $employee['id']; ?>" class="btn btn-light btn-sm float-right">Back</a>
</h4>

</div>

<div class="card-body">

<form method="POST">

<input type="hidden" name="employee_id" value="<?= $employee['id']; ?>">

<div class="row">
    <div class="col-md-6 mb-3">
    <label><b>Employee Name</b></label>
    <input type="text" class="form-control"
        value="<?= $employee['name']; ?>" readonly>
</div>


<div class="col-md-6 mb-3">
    <label><b>Resignation Type</b></label>

    <select name="resignation_type"
        id="resignation_type"
        class="form-control" required>

        <option value="">Select</option>

        <option value="With Notice"
            <?= ($employee['resignation_type']=="With Notice")?'selected':''; ?>>
            With Notice
        </option>

        <option value="Without Notice"
            <?= ($employee['resignation_type']=="Without Notice")?'selected':''; ?>>
            Without Notice
        </option>

        <option value="Abscond"
            <?= ($employee['resignation_type']=="Abscond")?'selected':''; ?>>
            Abscond
        </option>

    </select>

</div>

<div class="col-md-6 mb-3" id="resignation_date_div">

<label><b>Resignation Date</b></label>

<input type="date"
name="resignation_date"
class="form-control"
value="<?= $employee['resignation_date']; ?>">

</div>

<div class="col-md-6 mb-3" id="notice_div">

<label><b>Notice Period</b></label>

<input type="date"
name="notice_period"
class="form-control"
value="<?= $employee['notice_period']; ?>">

</div>

<div class="col-md-6 mb-3" id="release_div">

<label><b>Release Date</b></label>

<input type="date"
name="release_date"
class="form-control"
value="<?= $employee['release_date']; ?>">

</div>

<div class="col-md-6 mb-3"
id="abscond_div"
style="display:none;">

<label><b>Abscond Date</b></label>

<input type="date"
name="abscond_date"
class="form-control"
value="<?= $employee['abscond_date']; ?>">

</div>

<div class="col-md-12 mb-3">

<label><b>Reason For Leaving</b></label>

<textarea
name="reason_for_leaving"
rows="5"
class="form-control"
required><?= $employee['reason_for_leaving']; ?></textarea>

</div>

<div class="col-md-12">

<button
type="submit"
name="submit"
class="btn btn-danger">

<i class="fa fa-sign-out-alt"></i>

Submit Resignation

</button>

<a href="view-employee.php?id=<?= $employee['id']; ?>"
class="btn btn-secondary">

Cancel

</a>

</div>

</div>

</form>

</div>

</div>

</div>
<script>
function toggleFields() {

    var type = document.getElementById("resignation_type").value;

    var resignationDiv = document.getElementById("resignation_date_div");
    var noticeDiv = document.getElementById("notice_div");
    var releaseDiv = document.getElementById("release_div");
    var abscondDiv = document.getElementById("abscond_div");

    if (type == "With Notice") {

        resignationDiv.style.display = "block";
        noticeDiv.style.display = "block";
        releaseDiv.style.display = "block";
        abscondDiv.style.display = "none";

    }
    else if (type == "Without Notice") {

        resignationDiv.style.display = "block";
        noticeDiv.style.display = "none";
        releaseDiv.style.display = "block";
        abscondDiv.style.display = "none";

    }
    else if (type == "Abscond") {

        resignationDiv.style.display = "none";
        noticeDiv.style.display = "none";
        releaseDiv.style.display = "none";
        abscondDiv.style.display = "block";

    }
    else {

        resignationDiv.style.display = "none";
        noticeDiv.style.display = "none";
        releaseDiv.style.display = "none";
        abscondDiv.style.display = "none";

    }
}

document.getElementById("resignation_type").addEventListener("change", toggleFields);

// Page load par bhi current value ke hisaab se fields set kare
window.onload = toggleFields;
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>