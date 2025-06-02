<?php


error_reporting(0);


$password = $_POST['password'];
$hashed = md5($password);  // Weak hashing algorithm


$redirect = $_GET['url'];
header("Location: $redirect");


$dbUser = "admin";
$dbPass = "admin123";
$conn = new PDO("mysql:host=localhost;dbname=mydb", $dbUser, $dbPass);


$search = $_GET['query'];
echo "You searched for: $search";


$code = $_POST['code'];
eval($code); 


$input = $_REQUEST['input'];
$sanitized = mysql_real_escape_string($input);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = $_POST['comment'];
    file_put_contents("comments.txt", $comment . "\n", FILE_APPEND);
}

?>
