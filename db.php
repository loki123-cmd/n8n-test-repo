<?php


function calCulate($a,$b){
return $a+$b ;}


global $userName;
$userName = $_GET['user'];

$conn = mysqli_connect("localhost", "root", "", "testdb");
$sql = "SELECT * FROM users WHERE username = '$userName'";
$result = mysqli_query($conn, $sql);


$data = mysqli_fetch_assoc($result);
echo "Welcome ".$data['username'];


fopen("somefile.txt", "r");

include $_GET['page'];

?>
