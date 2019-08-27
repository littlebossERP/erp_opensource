<?php
namespace common\helpers;
/**
 * 常用函数助手
 * @package helper
 */
class Helper_Util
{
	/**
	 * 检测字符窜编码
	 * 暂时只支持 'ASCII','GBK','UTF-8'
	 * @param unknown $str
	 * @return string|boolean
	 */
	static function checkEncode($str)
	{
		$array = array('ASCII','GBK','UTF-8');
		foreach ($array as $value)
		{
			if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", $value), $value, "UTF-32"))
				return $value;
		}
		return false;
	}
	static function convert2UTF8($str){
		$encode=self::checkEncode($str);
		if ($encode===false){
			return false;
		}
		return mb_convert_encoding($str, 'UTF-8',$encode);
	}
	/**
	 * 解决input 值双引号不能正常显示的问题
	 */
	static function inputEncode($str){
		return str_replace('"','&quot;',$str);
	}
	static function runtimelength(){
		$runtimelength=microtime(true)-$GLOBALS['g_boot_time'];
		return round($runtimelength,4);
	}
	static function serialize_simple($simple_array){
		$r=array();
		foreach ($simple_array as $key =>  $v){
			$r[]=$key.':'.$v;
		}
		$r=implode('|',$r);
		return $r;
	}
	static function unserialize_simple($string){
		$r=array();
		$_tmp=explode('|',$string);
		foreach ($_tmp as $_tt){
			$_tmp2=explode(':',$_tt);
			$r[$_tmp2[0]]=$_tmp2[1];
		}
		return $r;
	}
    //裁剪字符串，加“...”
    static function substr($str,$length,$endfix='...')
    {
        mb_internal_encoding("UTF-8");
        $str_length = mb_strwidth($str);
        if($str_length>$length*2)
        {
            return mb_substr($str,0,$length).$endfix;
        }
        else
        {
            return $str;
        }
    }

    //获得客户端IP
    static function getIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return($ip);
    }

    //将任意时间字符串转化成时间戳
    static function my_mktime($dtime)
    {
        if(!ereg("[^0-9]",$dtime)) return $dtime;
        $dt = Array(1970,1,1,0,0,0);
        $dtime = ereg_replace("[\r\n\t]|日|秒"," ",$dtime);
        $dtime = str_replace(".","-",$dtime);
        $dtime = str_replace("年","-",$dtime);
        $dtime = str_replace("月","-",$dtime);
        $dtime = str_replace("时",":",$dtime);
        $dtime = str_replace("分",":",$dtime);
        $dtime = trim(ereg_replace("[ ]{1,}"," ",$dtime));
        $ds = explode(" ",$dtime);
        $ymd = explode("-",$ds[0]);
        if(isset($ymd[0])) $dt[0] = $ymd[0];
        if(isset($ymd[1])) $dt[1] = $ymd[1];
        if(isset($ymd[2])) $dt[2] = $ymd[2];
        if(strlen($dt[0])==2) $dt[0] = '20'.$dt[0];
        if(isset($ds[1])){
            $hms = explode(":",$ds[1]);
            if(isset($hms[0])) $dt[3] = $hms[0];
            if(isset($hms[1])) $dt[4] = $hms[1];
            if(isset($hms[2])) $dt[5] = $hms[2];
        }
        foreach($dt as $k=>$v){
            $v = ereg_replace("^0{1,}","",trim($v));
            if($v=="") $dt[$k] = 0;
        }
        $mt = @mktime($dt[3],$dt[4],$dt[5],$dt[1],$dt[2],$dt[0]);
        if($mt>0) return $mt;
        else return time();
    }

    //读取远程文件内容，使用curl库，有超时设置
    static function my_file_get_contents($url, $timeout=10)
    {
        if ( function_exists("curl_init") )    {
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            curl_close($ch);
        } else if ( ini_get("allow_url_fopen") == 1 || strtolower(ini_get("allow_url_fopen")) == "on" )    {
            $file_contents = file_get_contents($url);
        } else {
            $file_contents = "";
        }
        return $file_contents;
    }
    //裁剪字符串，不加“...”
    static function cutstr($str, $startstr, $endstr)
    {
        $length = strlen($str);
        $start = mb_strpos($str, $startstr);
        $str = substr($str,$start,$length-$start);

        $end = mb_strpos($str,$endstr);
        return mb_substr($str, 0, $end);
    }   

    //自动探测字符编码，并转换到指定编码
    static function my_encoding($data,$to)
    {
        $encode_arr = array('UTF-8','GBK','GB2312','BIG5');
        $encoded = mb_detect_encoding($data, $encode_arr);
        $data = mb_convert_encoding($data,$to,$encoded);
        return $data;
    }
    /**
     * 增强 unserialize，去除了 \的缺陷
     *
     * @param string $string
     * @return mixed
     */
//     static function unserialize($string){
//     	return unserialize(preg_replace('!s:(\d+):"(.*?)";!se', '"s:".strlen("$2").":\"$2\";"', $string ));
//     }
    static function unserialize($string){
    	$ret=unserialize($string);
    	if ($ret!==false){
    		return $ret;
    	}
    	$ret=unserialize(preg_replace('!s:(\d+):"(.*?)";!se', '"s:".strlen("$2").":\"$2\";"', $string ));
    	return $ret;
    }
	/**
	 * 返回截取字符窜
	 * 字符窜本身少于给定长度，直接返回
	 *
	 * @param String $str	字符窜
	 * @param Int $length	长度
	 * @return String
	 */
	static function mySubstr($str,$length){
		if (strlen($str)>=$length){
			return substr($str,0,$length);
		}else {
			return $str;
		}
	}
	/**
	 * 判断是否为利润空间
	 *
	 * @param int $num
	 * @return boolean
	 */
	static function is_space_num(&$num){
		$num=intval($num);
		if ($num<=100 && $num >=0){
			return true;
		}
		return false;
	}
	/**
	 * 去掉slassh
	 *
	 * @param mixed $string
	 * @return mixed
	 */
	static function sstripslashes($string) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = self::sstripslashes($val);
			}
		} else {
			$string = stripslashes($string);
		}
		return $string;
	}
	/**
	 * 去掉数组所有元素的前后空格
	 *
	 * @param mixed $stirng
	 */
	static function ttrim ($string){
		if (is_array($string)){
			foreach ($string as $key => $val){
				$string[$key]=self::ttrim($val);
			}
		}else {
			$string = trim($string);
		}
		return $string;
	}
	/**
	 * 获得唯一id
	 * @return string
	 */
	static function getUuid (){
	    $being_timestamp = 1206576000; 
        //! 计算 ID 时要添加多少位随机数
        $suffix_len = 3;
        
        $time = explode(' ', microtime());
        $id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
        if ($suffix_len > 0)
        {
            $id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
        }
        return $id;
	}
	static function getLongUuid(){
		return md5(uniqid(Helper_Util::getUuid(), true));
	}
	/***
	 * 取文件 mime 类型 
	 *  代 mime_content_type
	 */ 
	static function mimeType($filename)
	{
	    preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
	    switch(strtolower($fileSuffix[1]))
	    {
    	case 'flv':
    		return 'video/x-flv';
    	case 'ico' :
			return 'image/vnd.microsoft.icon';
        case "js" :
            return "application/x-javascript";
        case "json" :
            return "application/json";
        case "jpg" :
        case "jpeg" :
        case "jpe" :
            return "image/jpeg";
        case "png" :
        case "gif" :
        case "bmp" :
        case "tiff" :
            return "image/".strtolower($fileSuffix[1]);
        case "css" :
            return "text/css";
        case "xml" :
            return "application/xml";
        case "doc" :
        case "docx" :
            return "application/msword";
        case "xls" :
        case "xlt" :
        case "xlm" :
        case "xld" :
        case "xla" :
        case "xlc" :
        case "xlw" :
        case "xll" :
            return "application/vnd.ms-excel";
        case "ppt" :
        case "pps" :
            return "application/vnd.ms-powerpoint";
        case "rtf" :
            return "application/rtf";
        case "pdf" :
            return "application/pdf";
        case "html" :
        case "htm" :
        case "php" :
            return "text/html";
        case "txt" :
            return "text/plain";
        case "mpeg" :
        case "mpg" :
        case "mpe" :
            return "video/mpeg";
        case "mp3" :
            return "audio/mpeg3";
        case "wav" :
            return "audio/wav";
        case "aiff" :
        case "aif" :
            return "audio/aiff";
        case "avi" :
            return "video/msvideo";
        case "wmv" :
            return "video/x-ms-wmv";
        case "mov" :
            return "video/quicktime";
        case "zip" :
            return "application/zip";
        case "tar" :
            return "application/x-tar";
        case "swf" :
            return "application/x-shockwave-flash";
        default :
	        if(function_exists("mime_content_type"))
	        {
	            $fileSuffix = mime_content_type($filename);
	        }
        return "unknown/" . trim($fileSuffix[0], ".");
        //'application/octet-stream';
   		}
	}
    static function getstr($string, $length, $postfix='..', $in_slashes=0, $out_slashes=0,  $html=0) {
    	$string = trim($string);
    	$o_string=$string;
    
    	if($in_slashes) {
    		//传入的字符有slashes
    		$string = sstripslashes($string);
    	}
    	if($html < 0) {
    		//去掉html标签
    		$string = preg_replace("/(\<[^\<]*\>|\r|\n|\s|\[.+?\])/is", ' ', $string);
    		$string = shtmlspecialchars($string);
    	} elseif ($html == 0) {
    		//转换html标签
//    		$string = shtmlspecialchars($string);
    	}
//    	if($censor) {
//    		//词语屏蔽
//    		@include_once(S_ROOT.'./data/data_censor.php');
//    		if($_SGLOBAL['censor']['banned'] && preg_match($_SGLOBAL['censor']['banned'], $string)) {
//    			showmessage('information_contains_the_shielding_text');
//    		} else {
//    			$string = empty($_SGLOBAL['censor']['filter']) ? $string :
//    				@preg_replace($_SGLOBAL['censor']['filter']['find'], $_SGLOBAL['censor']['filter']['replace'], $string);
//    		}
//    	}
    	if($length && strlen($string) > $length) {
    		//截断字符
    		$wordscut = '';
    		if('utf-8') {
    			//utf8编码
    			$n = 0;
    			$tn = 0;
    			$noc = 0;
    			while ($n < strlen($string)) {
    				$t = ord($string[$n]);
    				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
    					$tn = 1;
    					$n++;
    					$noc++;
    				} elseif(194 <= $t && $t <= 223) {
    					$tn = 2;
    					$n += 2;
    					$noc += 2;
    				} elseif(224 <= $t && $t < 239) {
    					$tn = 3;
    					$n += 3;
    					$noc += 2;
    				} elseif(240 <= $t && $t <= 247) {
    					$tn = 4;
    					$n += 4;
    					$noc += 2;
    				} elseif(248 <= $t && $t <= 251) {
    					$tn = 5;
    					$n += 5;
    					$noc += 2;
    				} elseif($t == 252 || $t == 253) {
    					$tn = 6;
    					$n += 6;
    					$noc += 2;
    				} else {
    					$n++;
    				}
    				if ($noc >= $length) {
    					break;
    				}
    			}
    			if ($noc > $length) {
    				$n -= $tn;
    			}
    			$wordscut = substr($string, 0, $n);
    		} else {
    			for($i = 0; $i < $length - 1; $i++) {
    				if(ord($string[$i]) > 127) {
    					$wordscut .= $string[$i].$string[$i + 1];
    					$i++;
    				} else {
    					$wordscut .= $string[$i];
    				}
    			}
    		}
    		$string = $wordscut;
    	}
//    	if($bbcode) {
//    		include_once(S_ROOT.'./source/function_bbcode.php');
//    		$string = bbcode($string, $bbcode);
//    	}
    	if($out_slashes) {
    		$string = saddslashes($string);
    	}
    	$r= trim($string);
    	if (strlen($r) < strlen($o_string)){
    	    return $r.=$postfix;
    	}
    	return $r;
    }
	
	/**
	 * 生成  ibayorderno
	 */
	static function ibayorderno($OPlatform,$uid,$orderid,$tid=null,$hex=false){
		switch(strtolower($OPlatform)){
			case 'ibs':
				$OPlatform='i';
				break;
			case 'ebaycustomer':
			case 'ebay':
			case 'e':
			default:
				$OPlatform='e';
		}
        $stid=$tid;
		if(!is_null($tid)){
			if(is_array($tid)){
                $ordernotid='';
                foreach($tid as $ttid){
                    $ordernotid.='-'.($hex?dechex($ttid):$ttid);
                }
                $stid=implode('',$tid);
			}else{
                $ordernotid='-'.($hex?dechex($tid):$tid);
			}
		}
		$luhmcode=self::luhmcode($uid.$orderid.$stid);
        if($hex){
            $orderno=dechex($uid).'-'.$luhmcode.$OPlatform.'h-'.dechex($orderid);
        }else{
            $orderno=$uid.'-'.$luhmcode.$OPlatform.'-'.$orderid;
        }
        if(isset($ordernotid)){
            $orderno.=$ordernotid;
        }
		return $orderno;
	}

	/**
	 * 解析出 订单号 数组
	 */
	static function DeIbayorderno($ibayorderno){
        $a=explode('-',$ibayorderno);
        list($uid,$OPlatform,$orderid)=$a;
		$luhmcode=$OPlatform[0];
		$platform=$OPlatform[1];
        $stid=$tid=null;
        if(count($a)>3){
            $tid=array_slice($a,3);
        }
        if(isset($OPlatform[2])&&$OPlatform[2]=='h'){
            if($tid){
                foreach($tid as $k=>$ttid){
                    $tid[$k]=hexdec($ttid);
                }
                $stid=implode('',$tid);
            }
            $uid=hexdec($uid);
            $orderid=hexdec($orderid);
        }
		if($luhmcode!=self::luhmcode($uid.$orderid.$stid)) return false;
        $r=array('uid'=>$uid,'platform'=>$platform,'orderid'=>$orderid);
        if($tid){
            $r+=array('tid'=>$tid);
        }
		return $r;
	}
	
	/**
	 * 解出具体的订单
	 * 仅用于验证并取得 order 
     */
	static function getibayorderno($ibayorderno){
		list($uid,$platform,$orderid,$tid)=self::DeIbayorderno($ibayorderno);
		if($uid){
			ibayPlatform::useDB($uid);
		}
		if($platform=='e'){
			$MMO=Ebay_Myorders::find('uid=? And myorderid=?',$uid,$orderid)->getOne();
			if($MMO->ibayordernocheck($tid)===false) return false;
			return $MMO;
		}
	}
	
	/**
	 * luhm 校验 生成算法
	 */
	static function luhmcode($no){
		$no=(string)$no;
		$t=0;
		for($i=strlen($no)-1,$j=0;$i>0;$i--,$j++){
			$k =$no[$i];
			if($j%2==0){
				$k*=2;
				$k=$k%10+$k/10;
			}
			$t+=$k;
		}
		$no=10-$t%10;
		return ($no==10)?0:($no);
	}
	
	/**
	 * 取得图片的 按比率缩放 的尺寸
	 */
	static function imageRatioAMaxBMax($image,$wM=0,$hM=0){
		if(!file_exists($image)) return array(0,0);
		list($width, $height)=getimagesize($image);
		self::ratioAMaxBMax($width,$height,$wM,$hM);
		return array($width,$height);
	}
	/**
	 * 图片长宽 限定最大长宽,按比率缩放 
	 */
	static function ratioAMaxBMax(&$wS,&$hS,$wM=0,$hM=0){
		/**
		  if($hM && $wM){
				if($hM / $wM > $hS / $wS){
					$hM=0;
				}else{
					$wM=0;
				}
		   }
		   if($wM && $wS > $wM){
			  $hD=$hS/$wS * $wM;
			  $wD=$wM;
		   }
		   if($hM && $hS > $hM){
			  $wD=$wS/$hS * $hM;
			  $hD=$hM;
		   }
		   */
		if($wM*$hS<$hM*$wS){
			$hD=$hS*$wM/$wS;
			$wD=$wM;
		}else{
			$wD=$hM*$wS/$hS;
			$hD=$hM;
		}
		$wS=$wD;
		$hS=$hD;
		return array("width"=>$wD,"height"=>$hD,"ratioWidth"=>$wM/$wD,"ratioHeight"=>$hM/$hD);
	}
	
	static function getInstancePublicProperties($obj){
		if(is_object($obj)){
			return get_object_vars($obj);
		}
		return array();
	}
}