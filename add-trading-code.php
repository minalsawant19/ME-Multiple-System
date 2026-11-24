<?php

include('authentication.php');
include('dbcon.php');

function validDate($date) {

    $obj = DateTime::createFromFormat('!Y-m-d', $date);

    return $obj && $obj->format('Y-m-d') === $date;
}

function calculateItem($qty, $amount, $rate, $type) {

    if (
        !is_finite($qty) ||
        !is_finite($amount) ||
        $qty <= 0 ||
        $amount < 0 ||
        !in_array($rate, [5.0, 18.0], true) ||
        !in_array($type, ['inclusive', 'exclusive'], true)
    ) {
        throw new Exception("Invalid Item Details");
    }

    $input = round($qty * $amount, 2);

    if (!is_finite($input)) {
        throw new Exception("Invalid Amount");
    }

    if ($type === 'inclusive') {

        $base = round($input / (1 + $rate / 100), 2);
        $gst = round($input - $base, 2);
        $total = $input;

    } else {

        $base = $input;
        $gst = round($base * $rate / 100, 2);
        $total = round($base + $gst, 2);

    }

    return [$base, $gst, $total];
}

function getPostedItems() {

    $names = $_POST['item_name'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $hsns = $_POST['hsn_code'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    $rates = $_POST['gst_rate'] ?? [];
    $types = $_POST['gst_type'] ?? [];
    $descriptions = $_POST['description'] ?? [];

    if (
        !is_array($names) ||
        count($names) < 1 ||
        count($names) > 100 ||
        !is_array($qtys) ||
        !is_array($hsns) ||
        !is_array($amounts) ||
        !is_array($rates) ||
        !is_array($types) ||
        !is_array($descriptions)
    ) {
        throw new Exception("Invalid Item Data");
    }

    $count = count($names);

    if (
        count($qtys) !== $count ||
        count($hsns) !== $count ||
        count($amounts) !== $count ||
        count($rates) !== $count ||
        count($types) !== $count ||
        count($descriptions) !== $count
    ) {
        throw new Exception("Item Data Mismatch");
    }

    $items = [];

    foreach ($names as $i => $name) {

        if (
            !is_string($name) ||
            !is_string($hsns[$i]) ||
            !is_string($types[$i]) ||
            !is_string($descriptions[$i]) ||
            !is_numeric($qtys[$i]) ||
            !is_numeric($amounts[$i]) ||
            !is_numeric($rates[$i])
        ) {
            throw new Exception("Invalid Item Values");
        }

        $name = trim($name);
        $qty = (float)$qtys[$i];
        $hsn = trim($hsns[$i]);
        $amount = (float)$amounts[$i];
        $rate = (float)$rates[$i];
        $type = $types[$i];
        $description = trim($descriptions[$i]);

        if ($name === '') {
            throw new Exception("Item Name Required");
        }

        [$base, $gst, $total] =
            calculateItem($qty, $amount, $rate, $type);

        $items[] = [
            'name' => $name,
            'qty' => $qty,
            'hsn' => $hsn,
            'amount' => $amount,
            'rate' => $rate,
            'type' => $type,
            'base' => $base,
            'gst' => $gst,
            'total' => $total,
            'description' => $description
        ];

    }

    return $items;
}


// =====================================
// ADD MULTIPLE ITEMS - GROUP WISE
// =====================================

if (isset($_POST['add_trading'])) {

    $client_id = (int)($_POST['client_id'] ?? 0);
    $trading_date = $_POST['trading_date'] ?? '';

    mysqli_begin_transaction($conn);

    try {

        if ($client_id <= 0 || !validDate($trading_date)) {
            throw new Exception("Invalid Trading Details");
        }

        // Check client

        $check = mysqli_prepare($conn, "
            SELECT Id FROM tbl_client WHERE Id = ?
        ");

        mysqli_stmt_bind_param($check, "i", $client_id);
        mysqli_stmt_execute($check);

        $client = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

        if (!$client) {
            throw new Exception("Client Not Found");
        }

        // Get all posted items

        $items = getPostedItems();

        // Create one trading group

        $groupStmt = mysqli_prepare($conn, "
            INSERT INTO tbl_trading_groups
            (client_id, trading_date)
            VALUES (?, ?)
        ");

        mysqli_stmt_bind_param(
            $groupStmt,
            "is",
            $client_id,
            $trading_date
        );

        if (!mysqli_stmt_execute($groupStmt)) {
            throw new Exception(mysqli_stmt_error($groupStmt));
        }

        $group_id = mysqli_insert_id($conn);

        // Combined calculations

        $totalBase = 0;
        $totalGST = 0;
        $grandTotal = 0;

        $first_history_id = 0;

        // Insert all items

        foreach ($items as $item) {

            $totalBase = round($totalBase + $item['base'], 2);
            $totalGST = round($totalGST + $item['gst'], 2);
            $grandTotal = round($grandTotal + $item['total'], 2);

            $stmt = mysqli_prepare($conn, "
                INSERT INTO tbl_trading_history
                (
                    client_id,
                    group_id,
                    item_name,
                    qty,
                    hsn_code,
                    amount,
                    gst_rate,
                    gst_type,
                    base_amount,
                    gst_amount,
                    grand_total,
                    trading_date,
                    description
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iisdsddsdddss",
                $client_id,
                $group_id,
                $item['name'],
                $item['qty'],
                $item['hsn'],
                $item['amount'],
                $item['rate'],
                $item['type'],
                $item['base'],
                $item['gst'],
                $item['total'],
                $trading_date,
                $item['description']
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            $history_id = mysqli_insert_id($conn);

            // Keep first item ID for compatibility
            if ($first_history_id === 0) {
                $first_history_id = $history_id;
            }
        }

        // ONE PAYMENT ENTRY FOR ALL ITEMS

        $payment = mysqli_prepare($conn, "
            INSERT INTO tbl_payment
            (
                client_id,
                group_id,
                trading_history_id,
                total_amount,
                paid_amount,
                due_amount,
                due_date
            )
            VALUES (?, ?, ?, ?, 0, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $payment,
            "iiidds",
            $client_id,
            $group_id,
            $first_history_id,
            $grandTotal,
            $grandTotal,
            $trading_date
        );

        if (!mysqli_stmt_execute($payment)) {
            throw new Exception(mysqli_stmt_error($payment));
        }

        mysqli_commit($conn);

        header(
            "Location: view-trader-details.php?client_id=" . $client_id
        );

        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die("Insert Error: " . $e->getMessage());

    }
}


// =====================================
// UPDATE SINGLE ITEM
// =====================================


// =====================================
// UPDATE COMPLETE TRADING GROUP
// =====================================

elseif (isset($_POST['update_trading_group'])) {

    $group_id = (int)($_POST['group_id'] ?? 0);
    $client_id = (int)($_POST['client_id'] ?? 0);
    $trading_date = $_POST['trading_date'] ?? '';

    mysqli_begin_transaction($conn);

    try {

        if (
            $group_id <= 0 ||
            $client_id <= 0 ||
            !validDate($trading_date)
        ) {
            throw new Exception("Invalid Trading Details");
        }

        // Check group belongs to this client

        $groupCheck = mysqli_prepare($conn, "
            SELECT id
            FROM tbl_trading_groups
            WHERE id = ? AND client_id = ?
            FOR UPDATE
        ");

        mysqli_stmt_bind_param(
            $groupCheck,
            "ii",
            $group_id,
            $client_id
        );

        mysqli_stmt_execute($groupCheck);

        $group = mysqli_fetch_assoc(
            mysqli_stmt_get_result($groupCheck)
        );

        if (!$group) {
            throw new Exception("Trading Group Not Found");
        }

        // Get all posted items

        $items = getPostedItems();

        // Fetch existing group items

        $existingStmt = mysqli_prepare($conn, "
            SELECT Id
            FROM tbl_trading_history
            WHERE group_id = ? AND client_id = ?
            ORDER BY Id ASC
            FOR UPDATE
        ");

        mysqli_stmt_bind_param(
            $existingStmt,
            "ii",
            $group_id,
            $client_id
        );

        mysqli_stmt_execute($existingStmt);

        $existingResult = mysqli_stmt_get_result($existingStmt);

        $existingItems = [];

        while ($row = mysqli_fetch_assoc($existingResult)) {
            $existingItems[] = $row;
        }

        if (!$existingItems) {
            throw new Exception("No Existing Trading Items Found");
        }

        // Fetch single group payment

        $paymentCheck = mysqli_prepare($conn, "
            SELECT id, trading_history_id, paid_amount
            FROM tbl_payment
            WHERE group_id = ? AND client_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        mysqli_stmt_bind_param(
            $paymentCheck,
            "ii",
            $group_id,
            $client_id
        );

        mysqli_stmt_execute($paymentCheck);

        $paymentData = mysqli_fetch_assoc(
            mysqli_stmt_get_result($paymentCheck)
        );

        if (!$paymentData) {
            throw new Exception("Group Payment Record Not Found");
        }

        // Calculate combined totals

        $totalBase = 0;
        $totalGST = 0;
        $grandTotal = 0;

        foreach ($items as $item) {

            $totalBase = round(
                $totalBase + $item['base'],
                2
            );

            $totalGST = round(
                $totalGST + $item['gst'],
                2
            );

            $grandTotal = round(
                $grandTotal + $item['total'],
                2
            );
        }

        // Protect received payment

        $paid = (float)$paymentData['paid_amount'];

        if ($grandTotal < $paid) {
            throw new Exception(
                "New group total cannot be less than paid amount."
            );
        }

        // Update trading date

        $dateStmt = mysqli_prepare($conn, "
            UPDATE tbl_trading_groups
            SET trading_date = ?
            WHERE id = ? AND client_id = ?
        ");

        mysqli_stmt_bind_param(
            $dateStmt,
            "sii",
            $trading_date,
            $group_id,
            $client_id
        );

        if (!mysqli_stmt_execute($dateStmt)) {
            throw new Exception(mysqli_stmt_error($dateStmt));
        }

        // Update existing items and insert new items

        foreach ($items as $index => $item) {

            if (isset($existingItems[$index])) {

                // Update existing item

                $history_id = (int)$existingItems[$index]['Id'];

                $stmt = mysqli_prepare($conn, "
                    UPDATE tbl_trading_history SET
                        item_name = ?,
                        qty = ?,
                        hsn_code = ?,
                        amount = ?,
                        gst_rate = ?,
                        gst_type = ?,
                        base_amount = ?,
                        gst_amount = ?,
                        grand_total = ?,
                        trading_date = ?,
                        description = ?
                    WHERE Id = ?
                    AND group_id = ?
                    AND client_id = ?
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "sdsddsdddssiii",
                    $item['name'],
                    $item['qty'],
                    $item['hsn'],
                    $item['amount'],
                    $item['rate'],
                    $item['type'],
                    $item['base'],
                    $item['gst'],
                    $item['total'],
                    $trading_date,
                    $item['description'],
                    $history_id,
                    $group_id,
                    $client_id
                );

            } else {

                // Insert newly added item

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO tbl_trading_history
                    (
                        client_id,
                        group_id,
                        item_name,
                        qty,
                        hsn_code,
                        amount,
                        gst_rate,
                        gst_type,
                        base_amount,
                        gst_amount,
                        grand_total,
                        trading_date,
                        description
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdsddsdddss",
                    $client_id,
                    $group_id,
                    $item['name'],
                    $item['qty'],
                    $item['hsn'],
                    $item['amount'],
                    $item['rate'],
                    $item['type'],
                    $item['base'],
                    $item['gst'],
                    $item['total'],
                    $trading_date,
                    $item['description']
                );
            }

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        // Delete removed items
        // Existing first item is retained as payment reference.

        for (
            $i = count($items);
            $i < count($existingItems);
            $i++
        ) {

            $deleteId = (int)$existingItems[$i]['Id'];

            $deleteStmt = mysqli_prepare($conn, "
                DELETE FROM tbl_trading_history
                WHERE Id = ?
                AND group_id = ?
                AND client_id = ?
            ");

            mysqli_stmt_bind_param(
                $deleteStmt,
                "iii",
                $deleteId,
                $group_id,
                $client_id
            );

            if (!mysqli_stmt_execute($deleteStmt)) {
                throw new Exception(mysqli_stmt_error($deleteStmt));
            }
        }

        // Update one group payment record

        $newDue = round($grandTotal - $paid, 2);

        $paymentUpdate = mysqli_prepare($conn, "
            UPDATE tbl_payment SET
                total_amount = ?,
                due_amount = ?,
                due_date = ?
            WHERE id = ?
            AND group_id = ?
            AND client_id = ?
        ");

        $paymentId = (int)$paymentData['id'];

        mysqli_stmt_bind_param(
            $paymentUpdate,
            "ddsiii",
            $grandTotal,
            $newDue,
            $trading_date,
            $paymentId,
            $group_id,
            $client_id
        );

        if (!mysqli_stmt_execute($paymentUpdate)) {
            throw new Exception(mysqli_stmt_error($paymentUpdate));
        }

        mysqli_commit($conn);

        header(
            "Location: view-trader-details.php?client_id=" .
            $client_id . "&msg=Trading+Group+Updated"
        );

        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die("Group Update Error: " . $e->getMessage());
    }
}



elseif (isset($_POST['delete_trading_group'])) {

    $group_id = (int)($_POST['group_id'] ?? 0);
    $client_id = (int)($_POST['client_id'] ?? 0);

    mysqli_begin_transaction($conn);

    try {

        if ($group_id <= 0 || $client_id <= 0) {
            throw new Exception("Invalid Trading Details");
        }

        // Check group
        $check = mysqli_prepare($conn, "
            SELECT id
            FROM tbl_trading_groups
            WHERE id = ? AND client_id = ?
            FOR UPDATE
        ");

        mysqli_stmt_bind_param($check, "ii", $group_id, $client_id);
        mysqli_stmt_execute($check);

        $group = mysqli_fetch_assoc(
            mysqli_stmt_get_result($check)
        );

        if (!$group) {
            throw new Exception("Trading Group Not Found");
        }

        // Check payment
        $paymentCheck = mysqli_prepare($conn, "
            SELECT id, paid_amount
            FROM tbl_payment
            WHERE group_id = ? AND client_id = ?
            FOR UPDATE
        ");

        mysqli_stmt_bind_param(
            $paymentCheck,
            "ii",
            $group_id,
            $client_id
        );

        mysqli_stmt_execute($paymentCheck);

        $payments = mysqli_stmt_get_result($paymentCheck);

        $paymentIds = [];

        while ($payment = mysqli_fetch_assoc($payments)) {

            if ((float)$payment['paid_amount'] > 0) {
                throw new Exception(
                    "Payment received. Refund/reverse payment before deleting."
                );
            }

            $paymentIds[] = (int)$payment['id'];
        }

        // Delete payment records
        $deletePayment = mysqli_prepare($conn, "
            DELETE FROM tbl_payment
            WHERE group_id = ? AND client_id = ?
        ");

        mysqli_stmt_bind_param(
            $deletePayment,
            "ii",
            $group_id,
            $client_id
        );

        if (!mysqli_stmt_execute($deletePayment)) {
            throw new Exception(mysqli_stmt_error($deletePayment));
        }

        // Delete all trading items
        $deleteItems = mysqli_prepare($conn, "
            DELETE FROM tbl_trading_history
            WHERE group_id = ? AND client_id = ?
        ");

        mysqli_stmt_bind_param(
            $deleteItems,
            "ii",
            $group_id,
            $client_id
        );

        if (!mysqli_stmt_execute($deleteItems)) {
            throw new Exception(mysqli_stmt_error($deleteItems));
        }

        // Delete trading group
        $deleteGroup = mysqli_prepare($conn, "
            DELETE FROM tbl_trading_groups
            WHERE id = ? AND client_id = ?
        ");

        mysqli_stmt_bind_param(
            $deleteGroup,
            "ii",
            $group_id,
            $client_id
        );

        if (!mysqli_stmt_execute($deleteGroup)) {
            throw new Exception(mysqli_stmt_error($deleteGroup));
        }

        mysqli_commit($conn);

        header(
            "Location: view-trader-details.php?client_id=" .
            $client_id . "&msg=Trading+Group+Deleted"
        );

        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die("Delete Error: " . $e->getMessage());
    }
}


else {

    header("Location: index.php");
    exit;

}

?>
