<?php
$connect_error = 'Sorry, we\'re experiencing connection problems.';
try{
    $pdo = new PDO('mysql:host=localhost;dbname=lr', 'root', '01246582881');
} catch (PDOException $ex) {
    print $connect_error . "<br/>";
    die();
}

?>