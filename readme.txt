MyJson 一个C#编写的Json读写类
	在U3D使用过程中发现没有顺手的Json处理类库，于是愤然自写一枚

2015-01-26 v0.01 
	独立成库提交
	Myjson分为三个部分
	1.Myjson.cs
		Myjson本体
	2.MyJsonCompress.cs
		Myjson二进制存取扩展库
		因为大部分情况下二进制存取比文本拥有相当的压缩率
		所以称为MyJsonCompress
	2.MyJson.Php
		Myjson php二进制存取扩展库
		info.php 测试程序