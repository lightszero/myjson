<?php
include_once 'myjson.php';
$url = urldecode($_GET['url']);
@$addjson = urldecode($_GET['json']);
$up = $_GET['u']; //上行数据处理方法 1.jsonbin + base64  2.jsonbin +gzip +base64
$down = $_GET['d']; //下行数据处理方法 0.json文本 1.jsonbin 2.jsonbin+gzip
if(!empty($addjson))
{
	$addjson = base64_decode($addjson);
	if($up==2)
	{
	  //ungizp
	  //$addjson = unpack($addjson);
		$addjson = gzinflate  ($addjson);
	}
	$json_string = json_encode(JsonPack::Read($addjson,true));
	$url .= $json_string;
}

$html = stripslashes(stripslashes(file_get_contents($url))); 
if($down==0)
{
	echo $html;
}
else
{
	$html = json_decode($html);
	$bindata = '';
	JsonPack::Write($bindata,$html);
	if($down==2)
	{
		$bindata = gzencode($bindata,9);
	}
	echo $bindata;
}