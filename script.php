<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadTableData(tableId, tableName, columnList, query = '') {
    $.ajax({
        url: "fetch_table.php",
        method: "POST",
        data: {
            table: tableName,
            columns: columnList,
            search: query
        },
        success: function(data) {
            $("#" + tableId + " tbody").html(data);
        }
    });
}

$(".search-input").on("keyup", function(){
    let tableId = $(this).data("table-id");
    let tableName = $(this).data("table-name");
    let columns = $(this).data("columns");
    let search = $(this).val();
    loadTableData(tableId, tableName, columns, search);
});

// On page load — load all rows
$(".search-input").each(function(){
    let tableId = $(this).data("table-id");
    let tableName = $(this).data("table-name");
    let columns = $(this).data("columns");
    loadTableData(tableId, tableName, columns);
});
</script>
