<?php
include('dbcon.php');

$table = $_POST['table'];   // table name
$columns = explode(",", $_POST['columns']); // column list for search
$search = trim($_POST['search']);

$sql = "SELECT * FROM $table";

if($search != ''){
    $sql .= " WHERE ";
    $conditions = [];
    foreach($columns as $col){
        $conditions[] = "$col LIKE '%$search%'";
    }
    $sql .= implode(" OR ", $conditions);
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        echo "<tr>";
        foreach($columns as $col){
            echo "<td>".$row[$col]."</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='".count($columns)."'>No Records Found</td></tr>";
}
?>
