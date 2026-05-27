<?php
$raw = file_get_contents('php://input');
var_dump($raw);
var_dump(json_decode($raw, true));
?>
