<?php

//connect the server to the program
$serverName = 'localhost';
$user = 'root';
$password = '';
$database = 'dms';


//craete a new conenciton object
$conn = new mysqli($serverName, $user, $password, $database);

//check the connection
if($conn->connect_error){
    die("error".$conn->connect_error);
}


?>
