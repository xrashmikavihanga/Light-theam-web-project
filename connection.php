<?php


$serverName = 'localhost';
$user = 'root';
$password = '';
$database = 'dms';


$conn = new mysqli($serverName, $user, $password, $database);


if($conn->connect_error){
    die("error".$conn->connect_error);
}


?>
