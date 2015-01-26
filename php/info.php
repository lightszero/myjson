<?php
//header("Content-type:application/octet-stream");
require 'myjson.php';
//$json ='"a"';//direct string ok
//$json ='"abcccdf"';//dict string ok
//$json ='"啊哇士大夫撒地方"';//dict string chinese ok
//$json ='null';//number null ok
//$json ='true';//number null ok
//$json ='false';//number null ok
//$json ='123456.12';//number float ok
//$json ='2551231';//number int ok
//$json ='[1,2,3,4,5,6,7,8]';//array ok
//$json ='["1asdf",[2,3,4],3,4,5,6,7,8]';//array2 ok
$json ='{"arr":[1.13,-2,"3",null,true],"abc":1,"t":"","bbb":{"aaaad":2,"ddd":444}}';//obj ok
//$json ='{"aaa":1,"bcc":2}';//obj2
$obj = json_decode($json);
echo 'json len='.strlen($json).'<br/>';
var_dump($obj);
echo '<br/>';
echo '=>Json2Bin:<br/>';
//这里不允许使用json_decode($json,true),subobject 转换为array后不能区分结构
$_array = '';
JsonPack::Write($_array,$obj);//将json写入二进制字符串_array
echo $_array;
echo '<br/>length='.strlen($_array).'<br/>';
echo '=>Bin2Json:<br/>';
$objout = JsonPack::Read($_array);
var_dump($objout);
?>