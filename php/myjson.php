<?php
class JsonPack
{
	public static function Write(&$binstr_writebuf,$json,$pubdict=null,$riseDictByKey=false,$riseDictByString=false)
	{
		$_bufdata='';
		$localdict=Array();
		$seek=0;
		JsonPack::PackJson($_bufdata,$seek,$json,$pubdict,$localdict,$riseDictByKey,$riseDictByString);
		$_bufdict='';
		$seekdict=0;
		JsonPack::WriteStrDict($_bufdict,$seekdict,$localdict);
		$binstr_writebuf.=$_bufdict;
		$binstr_writebuf.=$_bufdata;
	}
	public static function Read(&$binstr_writebuf,$pubdict=null)
	{
		$bytesarray = unpack('C*',$binstr_writebuf);
		$seek=1;
		$localdict =JsonPack::ReadStrDict($bytesarray,$seek);
		return JsonPack::UnPackJson($bytesarray,$seek,$pubdict,$localdict);
	}
	static function WriteUIntSingle(&$binstr_writebuf,&$seek,$number)
	{
		$bytelen = 1;
		$c = (int)$number;
		while ($c >= 0x100)
		{
			$c = floor($c/ 0x100);
			$bytelen++;
		}
		if ($number < 128)
		{
			$binstr_writebuf.=pack("C",$number);
			$seek++;
		}
		else if ($number < 31 * 256)
		{
			$high = floor($number / 256);
			$low = $number % 256;
			$binstr_writebuf.=pack("C",128 |$high);
			$seek++;
			$binstr_writebuf.=pack("C",$low);
			$seek++;
		}
		else if ($number < 15 * 256 * 256)
		{
			$high = floor($number / 256 / 256);
			$midle = floor(($number / 256) % 256);
			$low = ($number % 256);

			$binstr_writebuf.=pack("C",128 | 64 | $high);
			$seek++;
			$binstr_writebuf.=pack("C",$midle);
			$seek++;
			$binstr_writebuf.=pack("C",$low);
			$seek++;
		}
	}
	static function ReadIntSingle($bytearray,&$seek)
	{
		$t = $bytearray[$seek];
		$seek++;
        if (($t & 128) > 0)
        {
            if (($t & 64) > 0)
            {
                if (($t & 32) == 0)
                {
                    $h = ($t % 32);
                    $m = $bytearray[$seek];$seek++;
                    $l = $bytearray[$seek];$seek++;
                    return h * 256 * 256 + m * 256 + l;
                }
                else
                {
                    throw 'NotSupportedException()';
                }
            }
            else
            {
				$low =  $bytearray[$seek];$seek++;
                return $t % 64 * 256 + low;
            }
        }
        else
        {
            return $t;
        }
	}
	static function WriteStrDict(&$binstr_writebuf,&$seek,$dict)
	{
		JsonPack::WriteUIntSingle($binstr_writebuf,$seek,count($dict));
		for($i=0;$i<count($dict);$i++)
		{
			$str=$dict[$i];
			JsonPack::WriteUIntSingle($binstr_writebuf,$seek,strlen($str));
			$binstr_writebuf.=$str;
			$seek+=strlen($str);
		}
	}
	static function ReadStrDict($bytearray,&$seek)
	{
		$list = array();
        $c = JsonPack::ReadIntSingle($bytearray,$seek);
        for ($i = 0; $i < $c; $i++)
        {
            $slen = JsonPack::ReadIntSingle($bytearray,$seek);
			$str=JsonPack::ReadStringPiece($bytearray,$seek,$slen);
			$list[]=$str;
        }
        return $list;
	}
	static function MakeStringTag($inDict, $isPubDict, $keylength)
	{
		$tag = 128 | 64;//stringtag
		if ($inDict==true)
			$tag |= 32;
		if ($isPubDict==true)
			$tag |= 16;

		$tag |= ($keylength );

		return $tag;
	}
	static function MakeNumberTag($isFloat, $isBool, $isNull, $isNeg, $datalength)
	{
		$tag = 128 | 0;//numbertag
		if ($isFloat)
			$tag |= 32;
		if ($isBool)
			$tag |= 16;
		if ($isNull)
			$tag |= 8;
		if ($isNeg)
			$tag |= 4;
		if ($isFloat)
			$tag |= (4 - 1);
		elseif (!$isBool && !$isNull)
			$tag |= ($datalength - 1);
		return $tag;
	}
	static function MakeArrayTag($arraycount, $bytelen)
    {
        $tag = 0 | 0;//arraytag
        if ($arraycount < 32)
        {
            $tag |= 32;
            $tag |= $arraycount;
        }
        else
        {
            $tag |= ($bytelen - 1);
        }
        return $tag;
    }
    static function MakeObjectTag($arraycount,$bytelen)
    {
        $tag = 0 | 64;//objecttag
        if ($arraycount < 32)
        {
            $tag |= 32;
            $tag |= $arraycount;
        }
        else
        {
            $tag |= ($bytelen - 1);
        }
        return $tag;
    }
	static function ReadCountHead($bytearray,&$seek, $tagfirst)
    {
        $b32 = ($tagfirst & 32) > 0;
        if (!$b32)
        {
            $blen = $tagfirst % 32 + 1;
			return JsonPack::ReadIntPiece($bytearray,$seek,$blen);
        }
        else
        {
            return $tagfirst % 32;
        }
    }
	static function WriteStringDataDirect(&$binstr_writebuf,&$seek,$string)
	{
		   $tag = JsonPack::MakeStringTag(false, false,strlen($string));
		   $binstr_writebuf.=pack("C",$tag);
		   $seek++;
		   $binstr_writebuf.=$string;
		   $seek+=strlen($string);
	}
	static function WriteIntPiece(&$binstr_writebuf,&$seek, $piece,$intv)
	{
		for($i=0;$i<$piece;$i++)
		{
			$low = ($intv%0x100);
			$intv= floor($intv/0x100);
			$binstr_writebuf.=pack("C",$low);
			$seek++;
		}
	}
	static function ReadStringPiece($bytearray,&$seek,$piece)
	{
		$str='';
		for($cc=0;$cc<$piece;$cc++)
		{
			$str.=pack('C',$bytearray[$seek]);$seek++;
		}
		return $str;
	}
	static function ReadIntPiece($bytearray,&$seek,$piece)
	{
		$i =0;
		for($cc=0;$cc<$piece;$cc++)
		{
			$c=$bytearray[$seek];$seek++;
			if($cc==0)
			{
				$i=$c;
			}
			elseif($cc==1)
			{
				$i+=256*$c;
			}
			elseif($cc==2)
			{
				$i+=256*256*$c;
			}
			elseif($cc==3)
			{
				$i+=256*256*256*$c;
			}
		}
		return $i;
	}
	static function ReadFloatPiece($bytearray,&$seek,$piece)
	{
		$str='';
		for($cc=0;$cc<$piece;$cc++)
		{
			$str.=pack('C',$bytearray[$seek]);$seek++;
		}
		$ufloat=unpack("f",$str);
		return $ufloat[1];
	}
	static function ReadString($bytearray,&$seek, $tagfirst, $pubdict, $localdict)
    {
        $inDict = ($tagfirst & 32) > 0;
        $isPubDict = ($tagfirst & 16) > 0;
        $keylength = $tagfirst % 16 ;
        if ($inDict)
        {
            $id = JsonPack::ReadIntPiece($bytearray,$seek,$keylength);
            if ($isPubDict)
            {
                return $pubdict[$id];
            }
            else
            {
                return $localdict[$id];
            }
        }
        else
        {
			return JsonPack::ReadStringPiece($bytearray,$seek,$keylength);
        }

    }
	static function WriteStringDataDict(&$binstr_writebuf,&$seek, $isPubDict, $pid)
	{
		$bytelen = 1;
		$c = $pid;
		while ($c >= 0x100)
		{
			$c = floor($c/0x100);
			$bytelen++;
		}
		$tag = JsonPack::MakeStringTag(true, $isPubDict, $bytelen);
		$binstr_writebuf.=pack("C",$tag);
		$seek++;
		JsonPack::WriteIntPiece($binstr_writebuf,$seek,$bytelen,$pid);
	}
	static function WriteFloatData(&$binstr_writebuf,&$seek, $number)
    {
		$tag = JsonPack::MakeNumberTag(true, false, false,false, 4);
		$binstr_writebuf.=pack("C",$tag);
		$seek++;
		$binstr_writebuf.=pack("f",$number);
        $seek+=4;
    }
	static function WriteIntData(&$binstr_writebuf,&$seek, $number)
    {
        $bytelen = 1;
        $c = $number;
		if ($number < 0)
            $c *= -1;
        while ($c >= 0x100)
        {
            $c = floor($c/0x100);
            $bytelen++;
        }
        $tag=JsonPack::MakeNumberTag(false, false, false, ($number < 0),$bytelen);
		$binstr_writebuf.=pack("C",$tag);
		$seek++;
		JsonPack::WriteIntPiece($binstr_writebuf,$seek,$bytelen,$c);
    }
	static function WriteArrayCountHead(&$binstr_writebuf,&$seek, $arraycount)
    {
		$bytelen = 1;
        $c = $arraycount;
        while ($c >= 0x100)
        {
            $c /= 0x100;
            $bytelen++;
        }
		$tag = JsonPack::MakeArrayTag($arraycount, $bytelen);
        $binstr_writebuf.=pack("C",$tag);
		$seek++;
        if ($arraycount >= 32)
        {
			JsonPack::WriteIntPiece($binstr_writebuf,$seek,$bytelen,$arraycount);
        }
    }
	static function WriteObjectCountHead(&$binstr_writebuf,&$seek, $arraycount)
    {
		$bytelen = 1;
        $c = $arraycount;
        while ($c >= 0x100)
        {
            $c /= 0x100;
            $bytelen++;
        }
		$tag = JsonPack::MakeObjectTag($arraycount, $bytelen);
        $binstr_writebuf.=pack("C",$tag);
		$seek++;
        if ($arraycount >= 32)
        {
			JsonPack::WriteIntPiece($binstr_writebuf,$seek,$bytelen,$arraycount);
        }
    }
	static function GetKey($dict,$str)
	{
		if(empty($dict))return -1;
		for($i=0;$i<count($dict);$i++)
		{
			if($dict[$i]==$str)
				return $i;
		}
	}
	static function PackJsonString(&$binstr_writebuf,&$seek,$string,$pubdict,&$localdict)
	{
			if(strlen($string)<2)
			{//直接写入
				JsonPack::WriteStringDataDirect($binstr_writebuf,$seek, $string);
			}
			else
			{
				$pid = JsonPack::GetKey($pubdict, $string);
				if ($pid >= 0)//公共字典
				{
					JsonPack::WriteStringDataDict($binstr_writebuf,$seek, true, $pid);
				}
				else //本地字典
				{
					if (in_array($string,$localdict) == false)
					{
						$localdict[]=($string);
					}
					$pid = JsonPack::GetKey($localdict, $string);
					JsonPack::WriteStringDataDict($binstr_writebuf,$seek, false, $pid);
				}
			}
	}
	static function PackJsonNumber(&$binstr_writebuf,&$seek,$number)
	{
		if ($number===null)
		{
			$binstr_writebuf.=pack("C",JsonPack::MakeNumberTag(false, false, true,false, 0));
			$seek++;
		}
		elseif (is_bool($number)==true)
		{
			$binstr_writebuf.=pack("C",JsonPack::MakeNumberTag(false, true, $number,false, 0));
			$seek++;
		}
		elseif(is_float($number))
		{
			JsonPack::WriteFloatData($binstr_writebuf,$seek, $number);
		}
		else
		{
			JsonPack::WriteIntData($binstr_writebuf,$seek, $number);
		}
	}
	static function PackJsonArray(&$binstr_writebuf,&$seek,$json, &$pubdict,&$localdict,$riseDictByKey,$riseDictByString)
    {
        JsonPack::WriteArrayCountHead($binstr_writebuf,$seek, count($json));
        for ($i = 0; $i < count($json); $i++)
        {
            JsonPack::PackJson($binstr_writebuf,$seek, $json[$i], $pubdict, $localdict, $riseDictByKey, $riseDictByString);
        }
    }
	static function PackJsonObject(&$binstr_writebuf,&$seek,$_object, &$pubdict,&$localdict,$riseDictByKey,$riseDictByString)
    {
		$ocount = count((array)$_object);
        JsonPack::WriteObjectCountHead($binstr_writebuf,$seek,$ocount);
		foreach ($_object as $key=>$value)
        {
            if (strlen($key) < 2)
            {
                JsonPack::WriteStringDataDirect($binstr_writebuf,$seek, $key);
            }
            else
            {
                $pid = JsonPack::GetKey($pubdict, $key);
                if ($pid >= 0)//公共字典
                {
                    JsonPack::WriteStringDataDict($binstr_writebuf,$seek, true, $pid);
                }
                else //本地字典
                {
                    if ($riseDictByKey)
                    {
                        $pid = JsonPack::GetFreeKey($pubdict);
                        $pubdict[]=(key);
                        JsonPack::WriteStringDataDict($binstr_writebuf,$seek, true, $pid);
                    }
                    else
                    {
                        if (in_array($key,$localdict) == false)
                        {
                            $localdict[]=($key);
                        }
                        $pid = JsonPack::GetKey($localdict, $key);
                        JsonPack::WriteStringDataDict($binstr_writebuf,$seek, false, $pid);
                    }
                }

            }
        }
        foreach ($_object as $key=>$value)
        {
			JsonPack::PackJson($binstr_writebuf,$seek, $value, $pubdict, $localdict, $riseDictByKey, $riseDictByString);        
		}
    }
	

	static function PackJson(&$binstr_writebuf,&$seek,$json,&$pubdict,&$localdict,$riseDictByKey,$riseDictByString)
	{
	
		if(is_string($json))
		{
			//string v = node.AsString();
			if ($riseDictByString==true && empty($json)==false && strlen($json) > 1 && in_array($json,$pubdict) == false)
			{
				$pubdict[]=$json;
			}
			JsonPack::PackJsonString($binstr_writebuf,$seek, $json, $pubdict, $localdict);
		}
		elseif(is_int($json)||is_float($json)||is_bool($json)||$json===null)
		{
			JsonPack::PackJsonNumber($binstr_writebuf,$seek,$json);
		}
		elseif(is_array($json))
		{
			JsonPack::PackJsonArray($binstr_writebuf,$seek,$json,$pubdict,$localdict,$riseDictByKey,$riseDictByString);
		}
		elseif(is_object($json))
		{
			JsonPack::PackJsonObject($binstr_writebuf,$seek,$json,$pubdict,$localdict,$riseDictByKey,$riseDictByString);
		}

	}
	static function UnPackJsonNumber($bytearray,&$seek,$tagfirst)
    {
        $isFloat = ($tagfirst & 32) > 0;
        $isBool = ($tagfirst & 16) > 0;
        $isNull = ($tagfirst & 8) > 0;
		$isNeg = ($tagfirst & 4) > 0;
        $blen = $tagfirst % 4 + 1;
        if ($isBool)
        {
            return $isNull;
        }
        else if ($isNull)
        {
            return null;
        }
        else if ($isFloat)
        {
			return JsonPack::ReadFloatPiece($bytearray,$seek,$blen);
        }
        else
        {
			$v = JsonPack::ReadIntPiece($bytearray,$seek,$blen);
			return $isNeg ? -$v:$v;
        }

        return number;
    }
	static function UnPackJsonString($bytearray,&$seek,$tagfirst, $pubdict, $localdict)
    {
        return JsonPack::ReadString($bytearray,$seek, $tagfirst, $pubdict, $localdict);
    }
	static function UnPackJsonArray($bytearray,&$seek, $tagfirst, $pubdict, $localdict)
    {
		$_array =array();
        $count = JsonPack::ReadCountHead($bytearray,$seek, $tagfirst);
        for ($i = 0; $i < $count; $i++)
        {
            $_array[]=JsonPack::UnPackJson($bytearray,$seek, $pubdict, $localdict);
        }
        return $_array;
    }
	static function UnPackJsonObject($bytearray,&$seek, $tagfirst, $pubdict, $localdict)
    {
		$_object=new stdClass();
       
        $count = JsonPack::ReadCountHead($bytearray,$seek, $tagfirst);
        $keys = array();
        for ($i = 0; $i < $count; $i++)
        {
            $ft = $bytearray[$seek];$seek++;
            $keys[]=JsonPack::ReadString($bytearray,$seek, $ft, $pubdict, $localdict);
        }
		for ($i = 0; $i < $count; $i++)
        {
            $_object->$keys[$i] = JsonPack::UnPackJson($bytearray,$seek, $pubdict, $localdict);
        }
        return $_object;
    }
	static function UnPackJson($bytearray, &$seek,$pubdict, $localdict)
    {
	
        $b = $bytearray[$seek];$seek++;
        $t1 = ($b & 128) > 0;
        $t2 = ($b & 64) > 0;

        if ($t1 && !$t2)//number
        {
            return JsonPack::UnPackJsonNumber($bytearray, $seek, $b);
        }
        else if ($t1 && $t2)//string
        {
            return JsonPack::UnPackJsonString($bytearray, $seek, $b, $pubdict, $localdict);
        }
        else if (!$t1 && !$t2)//array
        {
            return JsonPack::UnPackJsonArray($bytearray, $seek,$b, $pubdict, $localdict);
        }
        else//object
        {
			return JsonPack::UnPackJsonObject($bytearray, $seek,$b, $pubdict, $localdict);
        }
	}
}
?>