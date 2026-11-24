<?php
header('Content-Type: application/json');
include('dbcon.php');

$category = isset($_GET['category']) ? $_GET['category'] : '';

if ($category != '') {
    $query = "SELECT * FROM projects WHERE category='$category' ORDER BY id DESC";
} else {
    $query = "SELECT * FROM projects ORDER BY id DESC";
}

$result = mysqli_query($conn, $query);

$projects = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row['images'] = json_decode($row['images'], true);
    $projects[] = $row;
}

echo json_encode($projects);
?>