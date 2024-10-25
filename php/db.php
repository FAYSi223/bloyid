<?php

$host = "mysq-bloyid-bloyid.j.aivencloud.com";
$port = "13978";
$dbname = "defaultdb";
$user = "avnadmin";
$pass = "";

try {
    $con = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
} catch (PDOException $e) {
    die("Connection to our Database failed: " . $e->getMessage());
}
?>