<?php

// $conn = new mysqli(
//     "sql103.infinityfree.com",
//     "if0_41870224",
//     "sajeer1122",
//     "if0_41870224_hirex"
// );

$conn = new mysqli("localhost","root","","hirex");


if($conn->connect_error){
    die("Database connection failed: " . $conn->connect_error);
}
?>