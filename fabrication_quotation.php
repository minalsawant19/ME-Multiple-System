<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
include('dbcon.php');

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   VALIDATE INQUIRY ID
========================= */

$inquiry_id = filter_input(INPUT_GET, 'inquiry_id', FILTER_VALIDATE_INT);

if (!$inquiry_id || $inquiry_id <= 0) {
    exit('Invalid Inquiry ID');
}

/* =========================
   FETCH INQUIRY
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, phone, email, address
     FROM tbl_inquiry
     WHERE id = ?"
);

if (!$stmt) {
    exit('Database query failed');
}

mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
mysqli_stmt_execute($stmt);

$inq = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$inq) {
    exit('Inquiry not found');
}

/* =========================
   GENERATE REFERENCE NUMBER
========================= */

$query = mysqli_query(
    $conn,
    "SELECT ref_no
     FROM tbl_quotation
     WHERE ref_no REGEXP '^ME-QTY-[0-9]+$'
     ORDER BY CAST(
         SUBSTRING_INDEX(ref_no, '-', -1) AS UNSIGNED
     ) DESC
     LIMIT 1"
);

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $parts = explode('-', $row['ref_no']);
    $lastNo = (int) end($parts);
    $newNo = $lastNo + 1;
} else {
    $newNo = 29;
}

$ref_no = 'ME-QTY-' . $newNo;
?>

<style>
.fabrication-form {
    padding-bottom: 25px;
}

.fabrication-form label {
    font-weight: 600;
    margin-bottom: 5px;
}

.fabrication-form .form-control {
    margin-bottom: 12px;
}

.fabrication-form textarea {
    resize: vertical;
}

.fabrication-form .section-title {
    color: #007bff;
    font-weight: 600;
    margin-top: 20px;
}

.fabrication-form .table th,
.fabrication-form .table td {
    vertical-align: middle;
}

.fabrication-form .amount {
    text-align: right;
}

.fabrication-form .total-box {
    background: #f4f7fb;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
}

.fabrication-form .grand-total {
    font-weight: bold;
    color: #155724;
}

/* Keep two fields side-by-side on small screens too */
.fabrication-form .two-col {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 15px;
}

@media (max-width: 480px) {
    .fabrication-form .two-col {
        gap: 0 8px;
    }

    .fabrication-form .form-control {
        font-size: 13px;
    }

    .fabrication-form label {
        font-size: 12px;
    }

    .fabrication-form .table {
        font-size: 12px;
    }
}
</style>

<div class="container-fluid fabrication-form">

    <h2 class="text-center mb-4">Add Fabrication</h2>

    <form action="save_fabrication.php"
          method="POST"
          id="fabricationForm">

        <input type="hidden"
               name="inquiry_id"
               value="<?= (int)$inq['id'] ?>">

        <input type="hidden"
               name="ref_no"
               value="<?= h($ref_no) ?>">

        <!-- =========================
             FABRICATION DETAILS
        ========================== -->

        <h5 class="section-title">Fabrication Details</h5>
        <hr>

        <div class="two-col">

            <div>
                <label>Reference No.</label>
                <input type="text"
                       class="form-control"
                       value="<?= h($ref_no) ?>"
                       readonly>
            </div>

            <div>
                <label>Quotation Date</label>
                <input type="date"
                       name="quotation_date"
                       class="form-control"
                       value="<?= date('Y-m-d') ?>"
                       required>
            </div>

        </div>

        <!-- =========================
             CUSTOMER DETAILS
        ========================== -->

        <h5 class="section-title">Customer Details</h5>
        <hr>

        <div class="two-col">

            <div>
                <label>Customer Name</label>
                <input type="text"
                       name="client_name"
                       class="form-control"
                       value="<?= h($inq['name']) ?>"
                       readonly>
            </div>

            <div>
                <label>Phone</label>
                <input type="text"
                       name="client_phone"
                       class="form-control"
                       value="<?= h($inq['phone']) ?>"
                       readonly>
            </div>

            <div>
                <label>Address</label>
                <textarea name="client_address"
                          class="form-control"
                          rows="2"
                          readonly><?= h($inq['address']) ?></textarea>
            </div>

            <div>
                <label>Customer GSTIN</label>
                <input type="text"
                       name="customer_gstin"
                       class="form-control"
                       placeholder="Enter customer GSTIN"
                       maxlength="15"
                       style="text-transform: uppercase;">
            </div>

        </div>

        <!-- =========================
             ITEM DETAILS
        ========================== -->

        <h5 class="section-title">Item Details</h5>
        <hr>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">

                <thead class="thead-light">
                    <tr>
                        <th style="min-width:180px;">
                            Item Description
                        </th>

                        <th style="min-width:100px;">
                            Quantity
                        </th>
                        
                        <th style="min-width:110px;">Unit</th>

                        <th style="min-width:130px;">
                            Rate (₹)
                        </th>

                        <th style="min-width:130px;">
                            Amount (₹)
                        </th>

                        <th style="min-width:90px;">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody id="itemsBody">

                    <tr>
                        <td>
                            <input type="text"
                                   name="item_name[]"
                                   class="form-control item-name"
                                   placeholder="Enter item name"
                                   required>
                        </td>

                        <td>
                            <input type="number"
                                   name="quantity[]"
                                   class="form-control qty"
                                   min="0.01"
                                   step="0.01"
                                   value="1"
                                   required>
                        </td>
                        
                        <td>
                            <select name="unit[]" class="form-control unit" required>
                                <option value="NOS">NOS</option>
                                <option value="KGS">KGS</option>
                                <option value="SQF">SQF</option>
                                <option value="RFT">RFT</option>
                                <option value="SQM">SQM</option>
                                <option value="UNT">UNT</option>
                            </select>
                        </td>

                        <td>
                            <input type="number"
                                   name="rate[]"
                                   class="form-control rate"
                                   min="0"
                                   step="0.01"
                                   value="0"
                                   required>
                        </td>

                        <td>
                            <input type="text"
                                   class="form-control amount"
                                   value="0.00"
                                   readonly>
                        </td>

                        <td>
                            <button type="button"
                                    class="btn btn-danger btn-sm remove-item">
                                Remove
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <button type="button"
                id="addItem"
                class="btn btn-success btn-sm">
            + Add Item
        </button>

        <!-- =========================
             GST & TOTAL
        ========================== -->

        <h5 class="section-title">GST &amp; Total</h5>
        <hr>

        <div class="total-box">

            <div class="two-col">

                <div>
                    <label>Subtotal (₹)</label>
                    <input type="text"
                           id="subtotal"
                           class="form-control"
                           value="0.00"
                           readonly>
                </div>

                <div>
                    <label>CGST (%)</label>
                    <input type="number"
                           name="cgst_rate"
                           id="cgstRate"
                           class="form-control"
                           value="9"
                           min="0"
                           max="100"
                           step="0.01"
                           required>
                </div>

                <div>
                    <label>SGST (%)</label>
                    <input type="number"
                           name="sgst_rate"
                           id="sgstRate"
                           class="form-control"
                           value="9"
                           min="0"
                           max="100"
                           step="0.01"
                           required>
                </div>

                <div>
                    <label>CGST Amount (₹)</label>
                    <input type="text"
                           id="cgstAmount"
                           class="form-control"
                           value="0.00"
                           readonly>
                </div>

                <div>
                    <label>SGST Amount (₹)</label>
                    <input type="text"
                           id="sgstAmount"
                           class="form-control"
                           value="0.00"
                           readonly>
                </div>

                <div>
                    <label>Grand Total (₹)</label>
                    <input type="text"
                           id="grandTotal"
                           class="form-control grand-total"
                           value="0.00"
                           readonly>
                </div>

            </div>
        </div>

        <!-- =========================
             BUTTONS
        ========================== -->

        <div class="mt-4 mb-4">

            <button type="submit"
                    class="btn btn-primary"
                    id="saveBtn">
                Save Fabrication
            </button>
            <br>
            <br>

            <a href="view-inquiry.php"
               class="btn btn-secondary">
                Back
            </a>

        </div>

    </form>
</div>

<script>
(function () {
    'use strict';

    const body = document.getElementById('itemsBody');
    const form = document.getElementById('fabricationForm');

    const cgstRate = document.getElementById('cgstRate');
    const sgstRate = document.getElementById('sgstRate');

    function roundMoney(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    function calculate() {
        let subtotal = 0;

        body.querySelectorAll('tr').forEach(function (row) {
            const qtyInput = row.querySelector('.qty');
            const rateInput = row.querySelector('.rate');
            const amountInput = row.querySelector('.amount');

            const qty = Math.max(0, parseFloat(qtyInput.value) || 0);
            const rate = Math.max(0, parseFloat(rateInput.value) || 0);

            const amount = roundMoney(qty * rate);

            amountInput.value = amount.toFixed(2);
            subtotal += amount;
        });

        subtotal = roundMoney(subtotal);

        const cr = Math.min(
            100,
            Math.max(0, parseFloat(cgstRate.value) || 0)
        );

        const sr = Math.min(
            100,
            Math.max(0, parseFloat(sgstRate.value) || 0)
        );

        const cgst = roundMoney(subtotal * cr / 100);
        const sgst = roundMoney(subtotal * sr / 100);
        const grandTotal = roundMoney(subtotal + cgst + sgst);

        document.getElementById('subtotal').value =
            subtotal.toFixed(2);

        document.getElementById('cgstAmount').value =
            cgst.toFixed(2);

        document.getElementById('sgstAmount').value =
            sgst.toFixed(2);

        document.getElementById('grandTotal').value =
            grandTotal.toFixed(2);
    }

    document.getElementById('addItem').addEventListener(
        'click',
        function () {
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    <input type="text"
                           name="item_name[]"
                           class="form-control item-name"
                           placeholder="Enter item name"
                           required>
                </td>

                <td>
                    <input type="number"
                           name="quantity[]"
                           class="form-control qty"
                           min="0.01"
                           step="0.01"
                           value="1"
                           required>
                </td>
                
                 <td>
        <select name="unit[]"
                class="form-control unit"
                required>
            <option value="NOS">NOS</option>
            <option value="KGS">KGS</option>
            <option value="SQF">SQF</option>
            <option value="RFT">RFT</option>
            <option value="SQM">SQM</option>
            <option value="UNT">UNT</option>
        </select>
    </td>

                <td>
                    <input type="number"
                           name="rate[]"
                           class="form-control rate"
                           min="0"
                           step="0.01"
                           value="0"
                           required>
                </td>

                <td>
                    <input type="text"
                           class="form-control amount"
                           value="0.00"
                           readonly>
                </td>

                <td>
                    <button type="button"
                            class="btn btn-danger btn-sm remove-item">
                        Remove
                    </button>
                </td>
            `;

            body.appendChild(row);
            calculate();
        }
    );

    body.addEventListener('input', function (event) {
        if (
            event.target.matches('.qty, .rate')
        ) {
            calculate();
        }
    });

    body.addEventListener('click', function (event) {
        if (event.target.matches('.remove-item')) {
            if (body.querySelectorAll('tr').length > 1) {
                event.target.closest('tr').remove();
                calculate();
            } else {
                alert('At least one item is required.');
            }
        }
    });

    cgstRate.addEventListener('input', calculate);
    sgstRate.addEventListener('input', calculate);

    form.addEventListener('submit', function (event) {
        const rows = body.querySelectorAll('tr');

        if (rows.length === 0) {
            event.preventDefault();
            alert('Please add at least one item.');
            return;
        }

        let valid = true;

        rows.forEach(function (row) {
            const name = row.querySelector('.item-name').value.trim();
            const qty = parseFloat(row.querySelector('.qty').value);
            const rate = parseFloat(row.querySelector('.rate').value);

            if (
                name === '' ||
                !Number.isFinite(qty) ||
                qty <= 0 ||
                !Number.isFinite(rate) ||
                rate < 0
            ) {
                valid = false;
            }
        });

        if (!valid) {
            event.preventDefault();
            alert('Please enter valid item names, quantities and rates.');
        }
    });

    calculate();
})();
</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
