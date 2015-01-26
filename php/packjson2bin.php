<?php
header("Content-type:application/octet-stream");
require 'myjson.php';

$json= urldecode($_GET['json']);
$obj = json_decode($json);
//这里不允许使用json_decode($json,true),subobject 转换为array后不能区分结构

$_array = '';
JsonPack::Write($_array,$obj);//将json写入二进制字符串_array
echo $_array;
?>