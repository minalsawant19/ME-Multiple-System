<?php
include('dbcon.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request');
}

// ===============================
// FETCH CLIENT (FROM INQUIRY)
// ===============================
$inquiry_id = intval($_POST['inquiry_id']);

$inqRes = mysqli_query($conn, "SELECT * FROM tbl_inquiry WHERE id='$inquiry_id'");
if (!$inqRes || mysqli_num_rows($inqRes) == 0) {
    die("Inquiry not found");
}
$inq = mysqli_fetch_assoc($inqRes);

// ===============================
// BASIC DETAILS
// ===============================
$ref_no         = $_POST['ref_no'];
$quotation_date = $_POST['quotation_date'];

$property_type  = $_POST['property_type']; // Residential / Commercial
$subject        = $_POST['subject'];

$plant_capacity_kw = floatval($_POST['plant_capacity_kw']);
$system_type       = $_POST['system_type'];
$total_amount      = floatval($_POST['total_amount']);

// ===============================
// AUTO CALCULATIONS (SUMMARY)
// ===============================
$monthly_generation = $plant_capacity_kw * 120;
$yearly_generation  = $monthly_generation * 12;
$annual_saving      = $yearly_generation * 13;

$payback_years = ($annual_saving > 0)
    ? round($total_amount / $annual_saving, 1)
    : 0;

// ===============================
// COMPONENT DETAILS
// ===============================
$panel_spec    = $_POST['panel_spec'];
$panel_make    = $_POST['panel_make'];
$panel_qty     = $_POST['panel_qty'];

$inverter_spec = $_POST['inverter_spec'];
$inverter_make = $_POST['inverter_make'];
$inverter_qty  = $_POST['inverter_qty'];

$cable_make    = $_POST['cable_make'];
$mounting_make = $_POST['mounting_make'];

$dc_jb_make    = $_POST['dc_jb_make'];
$acdb_make     = $_POST['acdb_make'];

$earthing_make = $_POST['earthing_make'];
$earthing_qty  = $_POST['earthing_qty'];

$net_meter_make = $_POST['net_meter_make'];
$net_meter_qty  = $_POST['net_meter_qty'];

// ===============================
// COMMERCIAL CALCULATIONS
// ===============================
$supply_installation_amount = floatval($_POST['supply_installation_amount']);

// ===============================
// GOVT SUBSIDY (BACKEND SAFETY)
// ===============================
$govt_subsidy_amount = 0;

// Apply subsidy ONLY for Residential
if (strpos($property_type, 'Residential') !== false) {
    if ($plant_capacity_kw > 0 && $plant_capacity_kw <= 2) {
        $govt_subsidy_amount = 68000;
    } elseif ($plant_capacity_kw > 2) {
        $govt_subsidy_amount = 78000;
    }
} else {
    // Commercial → NO subsidy
    $govt_subsidy_amount = 0;
}

// GST & totals
$gst_5_amount        = round($supply_installation_amount * 0.05, 2);
$commercial_total    = round($supply_installation_amount + $gst_5_amount, 2);
$actual_project_cost = round($commercial_total - $govt_subsidy_amount, 2);

// ===============================
// INSERT INTO tbl_quotation
// ===============================
$sql = "INSERT INTO tbl_quotation SET
    inquiry_id = '$inquiry_id',

    ref_no = '$ref_no',
    quotation_date = '$quotation_date',

    client_name = '{$inq['name']}',
    client_address = '{$inq['address']}',
    client_phone = '{$inq['phone']}',
    client_email = '{$inq['email']}',

    property_type = '$property_type',
    subject = '$subject',

    plant_capacity_kw = '$plant_capacity_kw',
    system_type = '$system_type',

    monthly_generation = '$monthly_generation',
    yearly_generation  = '$yearly_generation',

    total_amount = '$total_amount',
    annual_saving = '$annual_saving',
    payback_years = '$payback_years',

    panel_spec = '$panel_spec',
    panel_make = '$panel_make',
    panel_qty  = '$panel_qty',

    inverter_spec = '$inverter_spec',
    inverter_make = '$inverter_make',
    inverter_qty  = '$inverter_qty',

    cable_make = '$cable_make',
    mounting_make = '$mounting_make',

    dc_jb_make = '$dc_jb_make',
    acdb_make  = '$acdb_make',

    earthing_make = '$earthing_make',
    earthing_qty  = '$earthing_qty',

    net_meter_make = '$net_meter_make',
    net_meter_qty  = '$net_meter_qty',

    supply_installation_amount = '$supply_installation_amount',
    gst_5_amount = '$gst_5_amount',
    commercial_total = '$commercial_total',
    govt_subsidy_amount = '$govt_subsidy_amount',
    actual_project_cost = '$actual_project_cost'
";

if (!mysqli_query($conn, $sql)) {
    die("DB Error: " . mysqli_error($conn));
}

$quotation_id = mysqli_insert_id($conn);

// ===============================
// REDIRECT TO PDF
// ===============================
header("Location: generate_quotation.php?id=" . $quotation_id);
exit;