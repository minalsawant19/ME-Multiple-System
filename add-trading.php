<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = (int)($_GET['client_id'] ?? 0);
$group_id = (int)($_GET['group_id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// CLIENT DETAILS
$stmt = mysqli_prepare($conn, "
    SELECT Id, cust_name
    FROM tbl_client
    WHERE Id = ?
");

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);

$client = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$client) {
    die("Client Not Found");
}

$is_edit = $group_id > 0;
$items = [];
$trading_date = date('Y-m-d');

// EDIT GROUP: FETCH COMPLETE GROUP
if ($is_edit) {

    $groupStmt = mysqli_prepare($conn, "
        SELECT id, trading_date
        FROM tbl_trading_groups
        WHERE id = ? AND client_id = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param(
        $groupStmt,
        "ii",
        $group_id,
        $client_id
    );

    mysqli_stmt_execute($groupStmt);

    $group = mysqli_fetch_assoc(
        mysqli_stmt_get_result($groupStmt)
    );

    if (!$group) {
        die("Trading Group Not Found");
    }

    $trading_date = $group['trading_date'];

    $itemStmt = mysqli_prepare($conn, "
        SELECT *
        FROM tbl_trading_history
        WHERE group_id = ? AND client_id = ?
        ORDER BY Id ASC
    ");

    mysqli_stmt_bind_param(
        $itemStmt,
        "ii",
        $group_id,
        $client_id
    );

    mysqli_stmt_execute($itemStmt);

    $result = mysqli_stmt_get_result($itemStmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    if (!$items) {
        die("No Items Found In This Group");
    }

} else {

    // NEW TRADING
    $items[] = [
        'item_name' => '',
        'qty' => '',
        'hsn_code' => '',
        'amount' => '',
        'gst_rate' => 5,
        'gst_type' => 'exclusive',
        'description' => ''
    ];
}
?>

<div class="container-fluid">

    <div class="inquiry-container">

        <div class="card-header bg-info text-white">
            <h4 class="mb-0">
                <?= $is_edit ? 'Edit Complete Trading Group' : 'Add Trading Details'; ?>
            </h4>
        </div>

        <div class="card-body">

            <h5>
                Client Name: <?= e($client['cust_name']); ?>
            </h5>

            <?php if ($is_edit): ?>
                <p class="text-muted">
                    Trading Group ID: <?= $group_id; ?>
                </p>
            <?php endif; ?>

            <hr>

            <form action="add-trading-code.php" method="POST">

                <input type="hidden"
                       name="client_id"
                       value="<?= $client_id; ?>">

                <?php if ($is_edit): ?>

                    <input type="hidden"
                           name="group_id"
                           value="<?= $group_id; ?>">

                    <input type="hidden"
                           name="update_trading_group"
                           value="1">

                <?php else: ?>

                    <input type="hidden"
                           name="add_trading"
                           value="1">

                <?php endif; ?>

                <!-- TRADING DATE -->

                <div class="form-group">

                    <label>Trading Date</label>

                    <input type="date"
                           name="trading_date"
                           class="form-control"
                           value="<?= e($trading_date); ?>"
                           required>

                </div>

                <hr>

                <!-- ITEMS -->

                <div id="items-container">

                    <?php foreach ($items as $item): ?>

                    <div class="item-row border rounded p-3 mb-3">
                        

                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <label>Item Name</label>

                                <input type="text"
                                       name="item_name[]"
                                       class="form-control"
                                       value="<?= e($item['item_name']); ?>"
                                       required>

                            </div>

                            <div class="col-md-12 mb-3">

                                <label>Quantity</label>

                                <input type="text"
                                       name="qty[]"
                                       class="form-control qty"
                                       step="0.01"
                                       min="0.01"
                                       value="<?= e($item['qty']); ?>"
                                       required>

                            </div>

                            <div class="col-md-12 mb-3">

                                <label>HSN Code</label>

                                <input type="text"
                                       name="hsn_code[]"
                                       class="form-control"
                                       value="<?= e($item['hsn_code']); ?>">

                            </div>

                            <div class="col-md-12 mb-3">

                                <label>Amount Per Item</label>

                                <input type="text"
                                       name="amount[]"
                                       class="form-control amount"
                                       step="0.01"
                                       min="0"
                                       value="<?= e($item['amount']); ?>"
                                       required>

                            </div>

                            <div class="col-md-12 mb-3">

                                <label>GST</label>

                                <select name="gst_rate[]"
                                        class="form-control gst-rate">

                                    <option value="5"
                                        <?= (float)$item['gst_rate'] == 5 ? 'selected' : ''; ?>>
                                        5%
                                    </option>

                                    <option value="18"
                                        <?= (float)$item['gst_rate'] == 18 ? 'selected' : ''; ?>>
                                        18%
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-12 mb-3">

                                <label>Type</label>

                                <select name="gst_type[]"
                                        class="form-control gst-type">

                                    <option value="exclusive"
                                        <?= $item['gst_type'] == 'exclusive' ? 'selected' : ''; ?>>
                                        Exclusive
                                    </option>

                                    <option value="inclusive"
                                        <?= $item['gst_type'] == 'inclusive' ? 'selected' : ''; ?>>
                                        Inclusive
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-12 mb-2">

                                <label>Description</label>

                                <textarea name="description[]"
                                          class="form-control"
                                          rows="2"><?= e($item['description']); ?></textarea>

                            </div>

                        </div>

                        <div class="text-right mb-3">

                            <strong>Base:</strong>
                            ₹<span class="item-base">0.00</span>

                            |

                            <strong>GST:</strong>
                            ₹<span class="item-gst">0.00</span>

                            |

                            <strong>Total:</strong>
                            ₹<span class="item-total">0.00</span>

                        </div>

                        <div class="text-right">

                            <button type="button"
                                    class="btn btn-danger btn-sm remove-item">

                                <i class="bi bi-trash"></i>
                                Remove Item

                            </button>

                        </div>

                    </div>

                    <?php endforeach; ?>

                </div>

                <button type="button"
                        id="add-item"
                        class="btn btn-success">

                    <i class="bi bi-plus-circle"></i>
                    Add More Item

                </button>

                <hr>

                <!-- TOTAL SUMMARY -->

                <div class="card bg-light">

                    <div class="card-body">

                        <h5>Amount Summary</h5>

                        <div class="row">

                            <div class="col-md-4">
                                <strong>Base:</strong>
                                ₹<span id="base">0.00</span>
                            </div>

                            <div class="col-md-4">
                                <strong>GST:</strong>
                                ₹<span id="gst">0.00</span>
                            </div>

                            <div class="col-md-4">
                                <strong>Grand Total:</strong>
                                ₹<span id="total">0.00</span>
                            </div>

                        </div>

                    </div>

                </div>

                <button type="submit"
                        class="btn btn-primary mt-4">

                    <i class="bi bi-save"></i>

                    <?= $is_edit ? 'Update Complete Group' : 'Save Trading'; ?>

                </button>

                <a href="view-trader-details.php?client_id=<?= $client_id; ?>"
                   class="btn btn-secondary mt-4">

                    Cancel

                </a>

            </form>

        </div>

    </div>

</div>

<script>

const container = document.getElementById('items-container');
const addButton = document.getElementById('add-item');

addButton.addEventListener('click', function() {

    const first = container.querySelector('.item-row');
    const clone = first.cloneNode(true);

    clone.querySelectorAll('input, textarea').forEach(el => {
        el.value = '';
    });

    clone.querySelectorAll('select').forEach(el => {
        el.selectedIndex = 0;
    });

    clone.querySelector('.item-base').innerText = '0.00';
    clone.querySelector('.item-gst').innerText = '0.00';
    clone.querySelector('.item-total').innerText = '0.00';

    container.appendChild(clone);

    calculateTotal();
});

container.addEventListener('click', function(e) {

    const button = e.target.closest('.remove-item');

    if (!button) return;

    const rows = container.querySelectorAll('.item-row');

    if (rows.length > 1) {

        button.closest('.item-row').remove();

        calculateTotal();

    } else {

        alert('At least one item is required.');

    }

});

container.addEventListener('input', calculateTotal);
container.addEventListener('change', calculateTotal);

function calculateTotal() {

    let totalBase = 0;
    let totalGST = 0;
    let grandTotal = 0;

    container.querySelectorAll('.item-row').forEach(row => {

        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let amount = parseFloat(row.querySelector('.amount').value) || 0;
        let rate = parseFloat(row.querySelector('.gst-rate').value) || 0;
        let type = row.querySelector('.gst-type').value;

        let inputAmount = qty * amount;

        let base = 0;
        let gst = 0;
        let total = 0;

        if (type === 'inclusive') {

            total = inputAmount;
            base = total / (1 + rate / 100);
            gst = total - base;

        } else {

            base = inputAmount;
            gst = base * rate / 100;
            total = base + gst;

        }

        base = Math.round(base * 100) / 100;
        gst = Math.round(gst * 100) / 100;
        total = Math.round(total * 100) / 100;

        row.querySelector('.item-base').innerText = base.toFixed(2);
        row.querySelector('.item-gst').innerText = gst.toFixed(2);
        row.querySelector('.item-total').innerText = total.toFixed(2);

        totalBase += base;
        totalGST += gst;
        grandTotal += total;

    });

    document.getElementById('base').innerText = totalBase.toFixed(2);
    document.getElementById('gst').innerText = totalGST.toFixed(2);
    document.getElementById('total').innerText = grandTotal.toFixed(2);

}

calculateTotal();

</script>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>
