<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');

$edit_mode = false;
$client_id = (int)($_GET['client_id'] ?? 0);
$group_id = (int)($_GET['group_id'] ?? 0);

if ($client_id <= 0) {
    die('Client ID missing');
}

$clientQry = mysqli_query($conn, "
    SELECT c.*,
           v.vendor_name,
           v.phone AS vendor_phone,
           v.email AS vendor_email,
           v.address AS vendor_address
    FROM tbl_client c
    LEFT JOIN tbl_vendor v ON c.vendor_id = v.id
    WHERE c.id = $client_id
    LIMIT 1
") or die(mysqli_error($conn));

$client = mysqli_fetch_assoc($clientQry);

if (!$client) {
    die('Client not found');
}

$is_trader = strtolower(trim($client['added_by'] ?? '')) === 'trader';

$gst_invoice_no = '';
$invoice_date = '';
$due_date = '';
$items = [];
$created_at = '';


// ======================================
// EDIT INVOICE
// ======================================

if (isset($_GET['edit']) && $_GET['edit'] == 1) {

    $edit_mode = true;

    $invRes = mysqli_query($conn, "
        SELECT *
        FROM tbl_invoice
        WHERE client_id = $client_id
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ") or die(mysqli_error($conn));

    $invoice_data = mysqli_fetch_assoc($invRes);

    if (!$invoice_data) {
        die('Invoice not found for editing');
    }

    $gst_invoice_no = $invoice_data['gst_invoice_no'];
    $invoice_date = $invoice_data['invoice_date'];
    $due_date = $invoice_data['due_date'];
    $created_at = $invoice_data['created_at'];

    $invoiceNoEsc = mysqli_real_escape_string(
        $conn,
        (string)$gst_invoice_no
    );

    $itemsRes = mysqli_query($conn, "
        SELECT *
        FROM tbl_invoice
        WHERE client_id = $client_id
          AND gst_invoice_no = '$invoiceNoEsc'
          AND created_at = '" .
          mysqli_real_escape_string($conn, $created_at) . "'
        ORDER BY id ASC
    ") or die(mysqli_error($conn));

    while ($item = mysqli_fetch_assoc($itemsRes)) {
        $items[] = $item;
    }
}


// ======================================
// FETCH TRADER ITEMS GROUP WISE
// ======================================

$trader_items = [];

if ($is_trader && !$edit_mode) {

    if ($group_id <= 0) {
        die('Trading Group ID missing');
    }

    $traderStmt = mysqli_prepare($conn, "
        SELECT
            Id,
            item_name,
            qty,
            hsn_code,
            amount,
            gst_rate,
            gst_type,
            base_amount,
            gst_amount,
            grand_total,
            description,
            trading_date
        FROM tbl_trading_history
        WHERE client_id = ?
          AND group_id = ?
        ORDER BY Id ASC
    ");

    mysqli_stmt_bind_param(
        $traderStmt,
        "ii",
        $client_id,
        $group_id
    );

    mysqli_stmt_execute($traderStmt);

    $traderResult = mysqli_stmt_get_result($traderStmt);

    while ($traderRow = mysqli_fetch_assoc($traderResult)) {
        $trader_items[] = $traderRow;
    }

    if (empty($trader_items)) {
        die('No trading items found in this group');
    }
}

$inquiry_id = $client['inquiry_id'] ?? null;


// ======================================
// FORM ITEMS
// ======================================

if ($edit_mode && !empty($items)) {

   $form_items = $items;

foreach ($form_items as &$formItem) {
    $formItem['gst_mode'] = $formItem['gst_mode'] ?? 'inclusive';
}
unset($formItem);

} elseif ($is_trader && !empty($trader_items)) {

    $form_items = [];

    foreach ($trader_items as $traderItem) {

        $form_items[] = [

            'item_name' => $traderItem['item_name'],

            'hsn' => $traderItem['hsn_code'],

            'qty' => $traderItem['qty'],

            'service_charge' => $traderItem['amount'],

            'rate' => $traderItem['amount'],

            'item_total' => $traderItem['grand_total'],

            'base_amount' => $traderItem['base_amount'],

            'gst_amount' => $traderItem['gst_amount'],

            'cgst_per' => (float)$traderItem['gst_rate'] / 2

        ];
    }

} else {

    $form_items = [[
        'item_name' => '',
        'hsn' => '',
        'qty' => 1,
        'service_charge' => '',
        'rate' => '',
        'item_total' => '',
        'cgst_per' => 0
    ]];

}

?>

<div class="container-fluid">

    <div class="inquiry-container">

        <h2 class="text-center">
            <?= $edit_mode ? 'Edit Invoice' : 'Generate Invoice' ?>
            <?= $is_trader ? '(Trader)' : '(Normal Client)' ?>
        </h2>


        <?php if (!empty($client['vendor_name'])): ?>

            <h4>Vendor Details</h4>

            <p><b>Vendor Name:</b>
                <?= htmlspecialchars($client['vendor_name']) ?>
            </p>

            <p><b>Phone:</b>
                <?= htmlspecialchars($client['vendor_phone'] ?? '') ?>
            </p>

            <p><b>Email:</b>
                <?= htmlspecialchars($client['vendor_email'] ?? '') ?>
            </p>

            <p><b>Address:</b>
                <?= htmlspecialchars($client['vendor_address'] ?? '') ?>
            </p>

            <hr>

        <?php endif; ?>


        <h4>Client Details</h4>

        <p><b>Name:</b>
            <?= htmlspecialchars($client['cust_name'] ?? '') ?>
        </p>

        <p><b>Phone:</b>
            <?= htmlspecialchars($client['cust_phone'] ?? '') ?>
        </p>

        <p><b>Email:</b>
            <?= htmlspecialchars($client['cust_email'] ?? '') ?>
        </p>

        <p><b>Billing Address:</b>
            <?= htmlspecialchars($client['billing_address'] ?? '') ?>
        </p>

        <p><b>Shipping Address:</b>
            <?= htmlspecialchars($client['shipping_address'] ?? '') ?>
        </p>

        <hr>


        <form method="POST" action="save-invoice.php" target="_blank">

            <input type="hidden"
                   name="client_id"
                   value="<?= $client_id ?>">
                   
            <input type="hidden"
       name="group_id"
       value="<?= $group_id; ?>">       

            <input type="hidden"
                   name="inquiry_id"
                   value="<?= htmlspecialchars((string)$inquiry_id) ?>">

            <input type="hidden"
                   name="gst_invoice_no"
                   value="<?= htmlspecialchars((string)$gst_invoice_no) ?>">

            <input type="hidden"
                   name="created_at"
                   value="<?= htmlspecialchars($created_at) ?>">

            <input type="hidden"
                   name="edit_mode"
                   value="<?= $edit_mode ? '1' : '0' ?>">

            <input type="hidden"
                   name="is_trader"
                   value="<?= $is_trader ? '1' : '0' ?>">


            <h4>Invoice Information</h4>

            <label>Invoice Date</label>

            <input type="date"
                   name="invoice_date"
                   class="form-control mb-3"
                   value="<?= htmlspecialchars($invoice_date) ?>"
                   required>


            <label>Due Date</label>

            <input type="date"
                   name="due_date"
                   class="form-control mb-3"
                   value="<?= htmlspecialchars($due_date) ?>"
                   required>


            <hr>

            <h3>Service / Item</h3>


            <div id="items-wrapper">

                <?php foreach ($form_items as $item): ?>

                    <div class="item-row border rounded p-3 mb-3">

                        <label>Item Name</label>

                        <input type="text"
                               name="item_name[]"
                               class="form-control mb-2"
                               value="<?= htmlspecialchars($item['item_name'] ?? '') ?>"
                               required>


                        <label>HSN</label>

                        <input type="text"
                               name="hsn[]"
                               class="form-control mb-2"
                               value="<?= htmlspecialchars($item['hsn'] ?? '') ?>">


                        <label>Quantity</label>

                        <input type="number"
                               name="qty[]"
                               class="qty form-control mb-2"
                               min="1"
                               step="1"
                               value="<?= htmlspecialchars($item['qty'] ?? 1) ?>"
                               required>


                        <?php if ($is_trader): ?>


                            <label>Service Charge / Rate (per unit, GST excluded)</label>

                            <input type="number"
                                   name="service_charge[]"
                                   class="service-charge form-control mb-2"
                                   min="0"
                                   step="0.01"
                                   value="<?= htmlspecialchars($item['service_charge'] ?? $item['rate'] ?? '') ?>"
                                   required>
                                   
                                   
                                   <label>GST Type</label>

<select name="gst_mode[]" class="gst-mode form-control mb-2" required>

    <option value="exclusive"
        <?= ($item['gst_mode'] ?? 'exclusive') == 'exclusive' ? 'selected' : '' ?>>
        GST Exclusive
    </option>

    <option value="inclusive"
        <?= ($item['gst_mode'] ?? 'exclusive') == 'inclusive' ? 'selected' : '' ?>>
        GST Inclusive
    </option>

</select>


                            <label>GST Rate</label>

                            <select name="gst_type[]"
                                    class="gst form-control mb-2"
                                    required>

                                <option value="">Select GST</option>

                                <option value="5"
                                    <?= (float)($item['cgst_per'] ?? 0) == 2.5 ? 'selected' : '' ?>>
                                    5%
                                </option>

                                <option value="18"
                                    <?= (float)($item['cgst_per'] ?? 0) == 9 ? 'selected' : '' ?>>
                                    18%
                                </option>

                            </select>
                            
                            
                            


                            <label>Taxable Amount</label>

                            <input type="number"
                                   class="taxable form-control mb-2"
                                   value="<?= htmlspecialchars($item['base_amount'] ?? '0.00') ?>"
                                   readonly>


                            <label>GST Amount</label>

                            <input type="number"
                                   class="gst-amount form-control mb-2"
                                   value="<?= htmlspecialchars($item['gst_amount'] ?? '0.00') ?>"
                                   readonly>


                            <label>Total Amount (GST Included)</label>

                            <input type="number"
                                   name="item_total[]"
                                   class="item-total form-control mb-2"
                                   value="<?= htmlspecialchars($item['item_total'] ?? '0.00') ?>"
                                   readonly>


                        
<?php else: ?>

    <label>Service Charges / Item Amount</label>

    <input type="text"
           name="item_total[]"
           class="normal-total form-control mb-2"
           min="0"
           step="0.01"
           value="<?= htmlspecialchars($item['item_total'] ?? '') ?>"
           required>

    <label>GST Type</label>

    <select name="gst_mode[]"
            class="gst-mode form-control mb-2"
            required>

        <option value="exclusive"
            <?= ($item['gst_mode'] ?? 'inclusive') == 'exclusive' ? 'selected' : '' ?>>
            GST Exclusive
        </option>

        <option value="inclusive"
            <?= ($item['gst_mode'] ?? 'inclusive') == 'inclusive' ? 'selected' : '' ?>>
            GST Inclusive
        </option>

    </select>

    <label>GST Rate</label>

    <select name="gst_type[]"
            class="gst form-control mb-2"
            required>

        <option value="">Select GST</option>

        <option value="5"
            <?= (float)($item['cgst_per'] ?? 0) == 2.5 ? 'selected' : '' ?>>
            5%
        </option>

        <option value="18"
            <?= (float)($item['cgst_per'] ?? 0) == 9 ? 'selected' : '' ?>>
            18%
        </option>

    </select>

    <label>Taxable Amount</label>
    <input type="number"
           class="taxable form-control mb-2"
           readonly>

    <label>GST Amount</label>
    <input type="number"
           class="gst-amount form-control mb-2"
           readonly>

    <label>Total Amount (GST Included)</label>
    <input type="number"
           class="item-total form-control mb-2"
           readonly>

<?php endif; ?>



                        <button type="button"
                                class="btn btn-danger btn-sm remove-btn"
                                onclick="removeItem(this)">

                            Remove Item

                        </button>

                    </div>

                <?php endforeach; ?>

            </div>


            <button type="button"
                    class="btn btn-info"
                    onclick="addItem()">

                + Add Item

            </button>


            <button type="submit"
                    class="btn btn-primary mt-3">

                <?= $edit_mode ? 'Update Invoice' : 'Save & Generate Invoice' ?>

            </button>


        </form>

    </div>

</div>


<script>


function calculateInvoice() {

    document.querySelectorAll('#items-wrapper .item-row').forEach(row => {

        const qty = parseFloat(row.querySelector('.qty')?.value) || 0;

        const chargeInput = row.querySelector('.service-charge');
        const gstInput = row.querySelector('.gst');
        const modeInput = row.querySelector('.gst-mode');

        const taxableInput = row.querySelector('.taxable');
        const gstAmountInput = row.querySelector('.gst-amount');

        const totalInput = row.querySelector('.item-total');
        const normalInput = row.querySelector('.normal-total');

        if (!gstInput || !modeInput) return;

        const gstRate = parseFloat(gstInput.value) || 0;
        const mode = modeInput.value;

        let enteredAmount = 0;

        // Trader: per unit rate
        if (chargeInput) {
            enteredAmount = qty * (parseFloat(chargeInput.value) || 0);
        }

        // Normal Client: entered total amount
        else {
            enteredAmount = parseFloat(normalInput?.value) || 0;
        }

        let taxable = 0;

        if (mode === 'exclusive') {

            taxable = enteredAmount;

        } else {

            taxable = gstRate > 0
                ? enteredAmount / (1 + gstRate / 100)
                : enteredAmount;

        }

        taxable = Math.round((taxable + Number.EPSILON) * 100) / 100;
        
        // Calculate complete GST first
const gstAmount = Math.round(
    (taxable * gstRate / 100 + Number.EPSILON) * 100
) / 100;

// Calculate final total
const total = Math.round(
    (taxable + gstAmount + Number.EPSILON) * 100
) / 100;

        // const cgst = Math.round(taxable * gstRate / 200 * 100) / 100;
        // const sgst = Math.round(taxable * gstRate / 200 * 100) / 100;

        // const gstAmount = cgst + sgst;
        // const total = taxable + gstAmount;

        if (taxableInput) {
            taxableInput.value = taxable.toFixed(2);
        }

        if (gstAmountInput) {
            gstAmountInput.value = gstAmount.toFixed(2);
        }

        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }

    });

}


function addItem() {

    const wrapper = document.getElementById('items-wrapper');
    const firstRow = wrapper.querySelector('.item-row');

    if (!firstRow) return;

    const clone = firstRow.cloneNode(true);

    clone.querySelectorAll('input').forEach(input => {

        if (input.classList.contains('qty')) {
            input.value = 1;
        } else if (
            input.classList.contains('taxable') ||
            input.classList.contains('gst-amount') ||
            input.classList.contains('item-total')
        ) {
            input.value = '0.00';
        } else {
            input.value = '';
        }

    });

    clone.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });

    wrapper.appendChild(clone);

    calculateInvoice();

}


function removeItem(button) {

    const wrapper = document.getElementById('items-wrapper');

    if (wrapper.querySelectorAll('.item-row').length > 1) {

        button.closest('.item-row').remove();

        calculateInvoice();

    } else {

        alert('At least one item is required.');

    }

}


document.getElementById('items-wrapper').addEventListener('input', event => {

    if (event.target.matches('.qty, .service-charge, .normal-total')) {
        calculateInvoice();
    }

});


document.getElementById('items-wrapper').addEventListener('change', event => {

    if (event.target.matches('.gst, .gst-mode')) {
        calculateInvoice();
    }

});


calculateInvoice();

</script>


<?php

include('includes/scripts.php');
include('includes/footer.php');

?>
