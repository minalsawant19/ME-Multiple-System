<?php
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');
include('dbcon.php');
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between mb-3">
        <form method="GET" class="mr-2">
            <input
                type="text"
                name="search"
                placeholder="Search by Name / Phone / Date"
                value=""
            >
        </form>
    </div>

    <h2 class="text-center mb-4">All Generated Quotations</h2>

    <div class="containers">

        <?php
        // ===============================
        // SEARCH LOGIC WITH JOIN
        // ===============================
        if (isset($_GET['search']) && $_GET['search'] !== '') {

            $search = mysqli_real_escape_string($conn, $_GET['search']);

            $query = "
                SELECT 
                    q.id,
                    q.pdf_file,
                    q.quotation_date,
                    i.name AS client_name,
                    i.phone AS client_phone
                FROM tbl_quotation q
                INNER JOIN tbl_inquiry i ON i.id = q.inquiry_id
                WHERE 
                    i.name LIKE '%$search%'
                    OR i.phone LIKE '%$search%'
                    OR DATE_FORMAT(q.quotation_date,'%d-%m-%Y') LIKE '%$search%'
                    OR q.quotation_date LIKE '%$search%'
                ORDER BY q.id DESC
            ";

        } else {

            $query = "
                SELECT 
                    q.id,
                    q.pdf_file,
                    q.quotation_date,
                    i.name AS client_name,
                    i.phone AS client_phone
                FROM tbl_quotation q
                INNER JOIN tbl_inquiry i ON i.id = q.inquiry_id
                ORDER BY q.id DESC
            ";
        }

        $query_run = mysqli_query($conn, $query);
        if (!$query_run) {
            die("Query Failed: " . mysqli_error($conn));
        }
        ?>

        <!-- TABLE -->
        <table class="table table-bordered table-hover text-center">
            <thead class="bg-dark text-white">
                <tr>
                    <th>Sr. No.</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Quotation Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $sr = 1;

                if (mysqli_num_rows($query_run) > 0) :
                    while ($row = mysqli_fetch_assoc($query_run)) :
                ?>
                        <tr>
                            <td><?= $sr++; ?></td>
                            <td><?= htmlspecialchars($row['client_name']); ?></td>
                            <td><?= htmlspecialchars($row['client_phone']); ?></td>
                            <td><?= date('d-m-Y', strtotime($row['quotation_date'])); ?></td>
                            <td>
                                <button class="actionBtn btn btn-sm btn-primary">Action</button>

                                        <div class="actionBox mt-2" style="display: none;">
                                <?php if (!empty($row['pdf_file'])) : ?>
                                    <button type="button"
        class="btn btn-sm btn-dark mb-2"
        onclick="window.open('<?= htmlspecialchars($row['pdf_file']); ?>', '_blank')">
    View PDF
</button>

                                <?php else : ?>
                                    <span class="badge bg-secondary">Not Available</span>
                                <?php endif; ?>
                                
                                <!-- Delete Button -->
                    <form method="POST" action="delete_quotation.php"  onsubmit="return confirm('Are you sure you want to delete this quotation?');">
                        <input type="hidden" name="quotation_id" value="<?= $row['id']; ?>">
                        <!-- SEND QUOTATION BUTTON -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning btn-block mb-2"
                                        onclick="window.location.href='send-quotation-email.php?quotation_id=<?= $row['id']; ?>'">
                                        Send Quotation
                                    </button>
                        <button type="submit" class="btn btn-sm btn-danger d-block mb-1">Delete</button>
                    </form>
                    </div>
                            </td>
                        </tr>
                <?php
                    endwhile;
                else :
                ?>
                    <tr>
                        <td colspan="5">No Quotations Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

<?php
include('includes/scripts.php');
include('includes/footer.php');
?>