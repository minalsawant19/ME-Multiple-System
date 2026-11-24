<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');

$client_id = $_GET['id'] ?? 0;
$group_id = (int)($_GET['group_id'] ?? 0);

$is_trader = ($group_id > 0);



/* =========================================================
   FETCH CLIENT INFO
========================================================= */

$client_query = mysqli_query(
    $conn,
    "SELECT * FROM tbl_client WHERE Id='$client_id' LIMIT 1"
);

$client = mysqli_fetch_assoc($client_query);

if (!$client) {

    echo "<script>
            alert('Client not found!');
            window.location.href='due-payment.php';
          </script>";

    exit();
}


/* =========================================================
   FETCH TOTAL AMOUNT AND TOTAL PAID FROM TBL_PAYMENT
========================================================= */

if ($is_trader) {

    // TRADER: Only selected group payment
    $payment_summary = mysqli_fetch_assoc(
        mysqli_query($conn, "
            SELECT
                MAX(total_amount) AS total_amount,
                SUM(paid_amount) AS total_paid
            FROM tbl_payment
            WHERE client_id = '$client_id'
            AND group_id = '$group_id'
        ")
    );

} else {

    // NORMAL CLIENT: OLD LOGIC
    $payment_summary = mysqli_fetch_assoc(
        mysqli_query($conn, "
            SELECT
                COALESCE(MAX(total_amount), 0) AS total_amount,
                COALESCE(SUM(paid_amount), 0) AS total_paid
            FROM tbl_payment
            WHERE client_Id = '$client_id'
        ")
    );

}

$total_amount = (float) ($payment_summary['total_amount'] ?? 0);
$total_paid   = (float) ($payment_summary['total_paid'] ?? 0);

$due_amount = max(0, round($total_amount - $total_paid, 2));


/* =========================================================
   HANDLE FORM SUBMISSION
========================================================= */

if (isset($_POST['submit'])) {

    $new_paid = floatval($_POST['paid_amount'] ?? 0);

    $payment_date = $_POST['payment_date'] ?? '';

    $due_date = trim($_POST['due_date'] ?? '');

    $payment_gateway = trim(
        $_POST['payment_gateway'] ?? ''
    );


    /* =====================================================
       VALIDATE PAYMENT METHOD
    ===================================================== */

    if ($payment_gateway == '') {

        echo "<script>
                alert('Please select or enter payment method!');
                window.history.back();
              </script>";

        exit();
    }


    /* =====================================================
       VALIDATE PAYMENT AMOUNT
    ===================================================== */

    if ($new_paid <= 0) {

        echo "<script>
                alert('Please enter a valid payment amount!');
                window.history.back();
              </script>";

        exit();
    }


    /* =====================================================
       PAYMENT CANNOT BE MORE THAN DUE
    ===================================================== */

    if ($new_paid > $due_amount) {

        echo "<script>
                alert('Payment amount cannot be greater than due amount!');
                window.history.back();
              </script>";

        exit();
    }


    /* =====================================================
       DUE DATE
    ===================================================== */

    if ($due_date == '') {

        $due_date_sql = "NULL";

    } else {

        $due_date = mysqli_real_escape_string(
            $conn,
            $due_date
        );

        $due_date_sql = "'$due_date'";
    }


    /* =====================================================
       NEW DUE AFTER PAYMENT
    ===================================================== */

    $updated_due =
        $total_amount -
        ($total_paid + $new_paid);


    /* =====================================================
       ESCAPE VALUES
    ===================================================== */

    $payment_gateway = mysqli_real_escape_string(
        $conn,
        $payment_gateway
    );

    $payment_date = mysqli_real_escape_string(
        $conn,
        $payment_date
    );


    /* =====================================================
       INSERT PAYMENT HISTORY
    ===================================================== */

   if ($is_trader) {

    $insert_sql = "
        INSERT INTO tbl_payment
        (
            client_id,
            group_id,
            total_amount,
            paid_amount,
            due_amount,
            payment_date,
            due_date,
            payment_gateway
        )
        VALUES
        (
            '$client_id',
            '$group_id',
            '$total_amount',
            '$new_paid',
            '$updated_due',
            '$payment_date',
            $due_date_sql,
            '$payment_gateway'
        )
    ";

} else {

    // YOUR OLD INSERT QUERY AS IT IS
    $insert_sql = "
        INSERT INTO tbl_payment
        (
            client_Id,
            total_amount,
            paid_amount,
            due_amount,
            payment_date,
            due_date,
            payment_gateway
        )
        VALUES
        (
            '$client_id',
            '$total_amount',
            '$new_paid',
            '$updated_due',
            '$payment_date',
            $due_date_sql,
            '$payment_gateway'
        )
    ";

}


    /* =====================================================
       EXECUTE INSERT
    ===================================================== */

    if (mysqli_query($conn, $insert_sql)) {

        echo "<script>
                alert('Payment recorded successfully!');
                window.location.href='due-payment.php';
              </script>";

        exit();

    } else {

        echo "Error: " . mysqli_error($conn);
    }
}

?>


<div class="inquiry-container">

    <h3 class="text-center">
        Add Payment for
        <?= htmlspecialchars($client['cust_name']); ?>
    </h3>


    <form action="" method="POST">
        
        <?php if ($is_trader) { ?>
    <input type="hidden" name="group_id"
           value="<?= $group_id; ?>">
<?php } ?>


        <!-- =================================================
             TOTAL AMOUNT
        ================================================== -->

        <label>Total Amount</label>

        <input
            type="text"
            value="<?= number_format($total_amount, 2); ?>"
            readonly
        >


        <!-- =================================================
             TOTAL PAID
        ================================================== -->

        <label>Total Paid So Far</label>

        <input
            type="text"
            value="<?= number_format($total_paid, 2); ?>"
            readonly
        >


        <!-- =================================================
             DUE AMOUNT
        ================================================== -->

        <label>Due Amount</label>

        <input
            type="text"
            value="<?= number_format($due_amount, 2); ?>"
            readonly
        >


        <!-- =================================================
             NEW PAYMENT
        ================================================== -->

        <label>New Payment</label>

        <input
            type="number"
            name="paid_amount"
            placeholder="Enter Paid Amount"
            min="0.01"
            max="<?= $due_amount; ?>"
            step="0.01"
            required
        >


        <!-- =================================================
             PAYMENT DATE
        ================================================== -->

        <label>Payment Date</label>

        <input
            type="date"
            name="payment_date"
            required
        >


        <!-- =================================================
             DUE DATE
        ================================================== -->

        <label>Due Date</label>

        <input
            type="date"
            name="due_date"
        >


        <!-- =================================================
             PAYMENT GATEWAY
        ================================================== -->

        <label>Payment Gateway</label>

        <div id="payment_gateway_container">

            <select
                name="payment_gateway"
                id="payment_gateway"
                required
            >

                <option value="">
                    -- Select Payment Method --
                </option>

                <option value="Cash">
                    Cash
                </option>

                <option value="Google Pay">
                    Google Pay
                </option>

                <option value="PhonePe">
                    PhonePe
                </option>

                <option value="Bank Transfer">
                    Bank Transfer
                </option>

                <option value="Cheque">
                    Cheque
                </option>

                <option value="Other">
                    Other
                </option>

            </select>

        </div>


        <!-- =================================================
             SUBMIT BUTTON
        ================================================== -->

        <button
            type="submit"
            name="submit"
        >
            Add Payment
        </button>

    </form>

</div>


<script>

/* =========================================================
   PAYMENT GATEWAY
   OTHER = SAME FIELD BECOMES TEXT INPUT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const container =
        document.getElementById(
            "payment_gateway_container"
        );


    container.addEventListener("change", function (event) {

        if (
            event.target &&
            event.target.id === "payment_gateway" &&
            event.target.value === "Other"
        ) {

            container.innerHTML = `
                <input
                    type="text"
                    name="payment_gateway"
                    id="payment_gateway"
                    placeholder="Enter Payment Method"
                    required
                    autofocus
                >
            `;

        }

    });

});

</script>


<?php

include('includes/scripts.php');
include('includes/footer.php');

?>

