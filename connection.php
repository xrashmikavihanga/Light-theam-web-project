<?php


$serverName = 'localhost';
$user = 'root';
$password = '';
$database = 'dms';

//connect the database using OOP Methode
$conn = new mysqli($serverName, $user, $password, $database);


if($conn->connect_error){
    die("error".$conn->connect_error);
}


?>
