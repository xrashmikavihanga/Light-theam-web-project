<?php

include('connection.php');

$sql = "SELECT * FROM users";

$result = $conn->query($sql);

if($result && $result->num_rows > 0){
    echo "-------------------\n";
    while($row = $result->fetch_row()){
        echo "{$row[0]}\t| {$row[1]}|\n";
    }
}

?>