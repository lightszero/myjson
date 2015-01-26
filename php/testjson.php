<?php
$array = array();
@$a = urldecode($_GET['a']);
@$b = urldecode($_GET['b']);
@$c = json_decode(urldecode($_GET['c']));

$array = array();
$array[] = $a;
$array[] = $b;
$array[] = $c;

echo json_encode($array);


