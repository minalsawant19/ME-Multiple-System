<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');

$inquiry_id = $_GET['inquiry_id'];
$inq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_inquiry WHERE id='$inquiry_id'"));


// ================= AUTO REF NO =================
$query = mysqli_query($conn, "SELECT ref_no FROM tbl_quotation ORDER BY id DESC LIMIT 1");

if(mysqli_num_rows($query) > 0)
{
    $row = mysqli_fetch_assoc($query);

    // Example: ME-QTY-26
    $lastRef = $row['ref_no'];

    $parts = explode('-', $lastRef);
    $lastNo = intval(end($parts));

    $newNo = $lastNo + 1;
}
else
{
    // First Ref No
    $newNo = 29;
}

$ref_no = "ME-QTY-" . $newNo;


?>

<style>
  label {
    font-weight: 500
  }

  textarea {
    resize: none
  }
</style>

<div class="inquiry-container">
  <form action="save_quotation.php" method="POST">

    <input type="hidden" name="inquiry_id" value="<?= $inquiry_id ?>">

    <h2 class="text-center">Add Quotation</h2>

    <!-- ================= QUOTATION INFO ================= -->
    <h5 class="text-primary">Quotation Info</h5>
    <hr>
    <div class="row">
      <div class="col">
        <label>Ref No</label>
        <input
    type="text"
    name="ref_no"
    value="<?= $ref_no; ?>"
    class="form-control"
    readonly
>
      </div>
      <div class="col">
        <label>Quotation Date</label>
        <input type="date" name="quotation_date" required>
      </div>
    </div>

    <!-- ================= CLIENT DETAILS ================= -->
    <h5 class="text-primary mt-3">Client Details</h5>
    <hr>
    <div class="row">
      <div class="col">
        <label>Client Name</label>
        <input value="<?= $inq['name'] ?>" readonly>
      </div>
      <div class="col">
        <label>Phone</label>
        <input value="<?= $inq['phone'] ?>" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Address</label>
        <textarea rows="2" readonly><?= $inq['address'] ?></textarea>
      </div>
      <div class="col">
        <label>Phase Type</label>
        <select name="property_type" id="property_type" required onchange="calculateSummary()">
          <option value="">Select</option>
          <option value="Residential - Single Phase">Residential - Single Phase</option>
          <option value="Residential - Three Phase">Residential - Three Phase</option>
          <option value="Commercial - Single Phase">Commercial - Single Phase</option>
          <option value="Commercial - Three Phase">Commercial - Three Phase</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Quotation Subject</label>
        <textarea name="subject" rows="2" required></textarea>
      </div>
    </div>

    <!-- Summary of proposal -->
    <h5 class="text-primary mt-3">Summary of Proposal</h5>
    <hr>

    <div class="row">
      <div class="col">
        <label>PV Plant Size (KWp)</label>
        <input type="number" step="0.1"
          name="plant_capacity_kw"
          id="plant_capacity_kw"
          required
          oninput="calculateSummary()">
      </div>
      <div class="col">
        <label>System Type</label>
        <input name="system_type" value="Grid-Tie" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Power Generation / Month (kWh)</label>
        <input name="monthly_generation" id="monthly_generation" readonly>
      </div>
      <div class="col">
        <label>Power Generation / Year (kWh)</label>
        <input name="yearly_generation" id="yearly_generation" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Min. Annual Saving (INR)</label>
        <input name="annual_saving" id="annual_saving" readonly>
      </div>
      <div class="col">
        <label>Investment Recovery (Years)</label>
        <input name="payback_years" id="payback_years" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Total System Cost (INR)</label>
        <input type="number"
          name="total_amount"
          id="total_amount"
          required
          oninput="calculateSummary()">
      </div>
    </div>

    <!-- Component Details -->
    <h5 class="text-primary">
      Component Details
    </h5> 
    <hr> 
    <table class="table table-bordered"> 
      <thead class="thead-light"> 
        <tr> 
          <th>Item</th> 
          <th>Specification</th> 
          <th>Make</th> 
          <th>Quantity / Standard</th> 
        </tr> 
      </thead> 
      <tbody> 
        <!-- ===== 1. SOLAR PANEL (SPEC MANUAL) ===== --> 
         <tr> 
          <td>Solar Panels</td> 
          <td>
            <textarea name="panel_spec" rows="3"></textarea>
          </td> 
          <td><input name="panel_make"></td> 
          <td><input name="panel_qty"></td> 
        </tr> 
        <!-- ===== 2. INVERTER (SPEC MANUAL) ===== --> 
         <tr> 
          <td>Inverter</td> 
          <td>
            <textarea name="inverter_spec" rows="3"></textarea>
          </td> 
          <td><input name="inverter_make"></td> 
          <td><input name="inverter_qty"></td> 
        </tr> 
        <!-- ===== REST SAME LOGIC ===== --> 
         <tr> 
          <td>Cables / Wires</td> 
          <td>As per IEC Standards</td> 
          <td><input name="cable_make"></td> 
          <td class="text-muted text-center">As per IEC</td> 
        </tr> 
        <tr> 
          <td>Mounting Structure</td> 
          <td>HDGI Structure</td> 
          <td><input name="mounting_make"></td> 
          <td class="text-muted text-center">Standards</td> 
        </tr> 
        <tr> 
          <td>DC Junction Box</td> 
          <td>IP65 with SPD</td> 
          <td><input name="dc_jb_make"></td> 
          <td class="text-muted text-center">IEC 60364-5-53</td> 
        </tr> 
        <tr> 
          <td>AC Distribution Board</td> 
          <td>MCB + SPD</td> 
          <td><input name="acdb_make"></td> 
          <td class="text-muted text-center">IEC 60364-5-53</td> 
        </tr> 
        <tr> 
          <td>Earthing</td> 
          <td>Copper Bonded Earthing</td> 
          <td><input name="earthing_make"></td> 
          <td><input name="earthing_qty"></td> 
        </tr> 
        <tr> 
          <td>Net Meter</td> 
          <td>Bi-directional Energy Meter</td> 
          <td><input name="net_meter_make"></td> 
          <td><input name="net_meter_qty"></td> 
        </tr>
      </tbody> 
      </table>

    <!-- Commercial -->
    <h5 class="text-primary mt-3">Commercial</h5>
    <hr>

    <div class="row">
      <div class="col">
        <label>Supply & Installation</label>
        <input
          name="supply_installation_amount"
          id="supply_amount"
          readonly>
      </div>
      <div class="col">
        <label>GST @5%</label>
        <input
          name="gst_5_amount"
          id="gst5_amount"
          readonly>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Commercial Total</label>
        <input
          name="commercial_total"
          id="commercial_total"
          readonly>
      </div>
      <div class="col">
        <label>Govt. Subsidy</label>
        <input
          name="govt_subsidy_amount"
          id="govt_subsidy"
          readonly>
      </div>
    </div>

    <label>Actual Project Cost</label>
    <input
      name="actual_project_cost"
      id="actual_cost"
      readonly>

    <button class="mt-4">Save Quotation</button>

  </form>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>

<script>
  function calculateSummary() {

    let kw = parseFloat(document.getElementById('plant_capacity_kw').value) || 0;
    let totalCost = parseFloat(document.getElementById('total_amount').value) || 0;

    let monthlyGen = kw * 120;
    let yearlyGen = monthlyGen * 12;
    let annualSaving = yearlyGen * 13;
    let payback = annualSaving > 0 ? totalCost / annualSaving : 0;

    document.getElementById('monthly_generation').value = monthlyGen.toFixed(0);
    document.getElementById('yearly_generation').value = yearlyGen.toFixed(0);
    document.getElementById('annual_saving').value = annualSaving.toFixed(0);
    document.getElementById('payback_years').value = payback.toFixed(1);

    autoCommercialFromTotal();
  }

  function autoCommercialFromTotal() {

    let kw = parseFloat(document.getElementById('plant_capacity_kw').value) || 0;
    let totalCost = parseFloat(document.getElementById('total_amount').value) || 0;
    let propertyType = document.getElementById('property_type').value;

    let subsidy = 0;

    // ✅ SUBSIDY ONLY FOR RESIDENTIAL
    if (propertyType.includes('Residential')) {
      if (kw > 0 && kw <= 2) subsidy = 68000;
      else if (kw > 2) subsidy = 78000;
    }

    let supply = totalCost / 1.05;
    let gst5 = supply * 0.05;
    let commercialTotal = supply + gst5;
    let actualCost = commercialTotal - subsidy;

    document.getElementById('govt_subsidy').value = subsidy.toFixed(2);
    document.getElementById('supply_amount').value = supply.toFixed(2);
    document.getElementById('gst5_amount').value = gst5.toFixed(2);
    document.getElementById('commercial_total').value = commercialTotal.toFixed(2);
    document.getElementById('actual_cost').value = actualCost.toFixed(2);
  }
</script>