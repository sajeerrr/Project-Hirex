<?php

$conn = new mysqli("localhost","root","","hirex");

if($conn->connect_error){
    die("Database connection failed");
}

?>