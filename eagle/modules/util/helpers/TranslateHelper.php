<?php
namespace eagle\modules\util\helpers;

use Yii;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\SysLogHelper;

class TranslateHelper
{   //百度翻译api 
	const CURL_TIMEOUT=  10 ;
	const URL= "http://api.fanyi.baidu.com/api/trans/vip/translate";
	// TODO add BD translate account info @xxx@
	const APP_ID= "@xxx@"; //替换为您的APPID
	const SEC_KEY="@xxx@";//替换为您的密钥

	const TRANSLATE_CSV_FILE_DIR     = 'locale';
	const CSV_SEPARATOR     = ',';
	const SCOPE_SEPARATOR   = '::';
	const CACHE_TAG         = 'translate';
	
	// 注意key值与 locale目录下的目录名一致
	public static $_translate_lan_type = array(
			'zh-cn'=>'简体中文',
			'zh-hk'=>'繁體中文',
			//	    'en-us'=>'English'
	);
	
	/**
	 * Cache Switcher
	 * @var bool
	*/
	protected static $_canUseCache = TRUE;
	
	/**
	 * Cache identifier
	 *
	 * @var string
	 */
	protected static $_cacheId;
	
	/**
	 * Translation data
	 *
	 * @var array
	 */
	static $_data = array();
	
	/**
	 * Mark down the current module id loaded in $_data
	 *
	 * @var string
	*/
	static $_currentModule = '';
	
	/**
	 * Translation data wrapped by module
	 *
	 * @var array
	 */
	static $_wrappedMetaData = array();
	
	/**
	 *
	 * 获取csv文件内容时的: 最大行，字段分界符 和字段环绕符。
	 * @var int , string , string
	*/
	protected static $_lineLength= 0;
	protected static $_delimiter = self::CSV_SEPARATOR;
	protected static $_enclosure = '"';
	
	/**
	 * Translation language
	 *
	 * @var string
	 */
	protected static $_language = 'zh-cn';
	private static $setCookieLan = false;
	
	/**
	 +----------------------------------------------------------
	 * Initialization translation data
	 +----------------------------------------------------------
	 * @access public static
	 +----------------------------------------------------------
	 * @param   string $area , bool $forceReload
	 +----------------------------------------------------------
	 * @return  null
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/02/04				初始化
	 +----------------------------------------------------------
	 **/
	public static function loadToCache( $loadFileScope = 'module',$forceReload = false){
		if (!$forceReload) {
			//            if (self::$_canUseCache) {
			//                self::$_data = self::_loadCache();
			//                if (self::$_data !== false) {
			//                    return ;
			//                }
			//            }else {
			//            	Mage::app()->removeCache(self::getCacheId());
			//            }
		}
	
		if(Yii::$app instanceof \yii\console\Application){
			return ;
		}
	
		self::$_language = self::getCurrentLanguague();
	
		if('zh-cn' ==  self::$_language)
			return ;
	
		if('all' == $loadFileScope ||
		(false == $forceReload && empty(self::$_wrappedMetaData))){
	
			$modules = array();
			$root = Yii::getAlias('@eagle');
			$dir = $root.DIRECTORY_SEPARATOR.self::TRANSLATE_CSV_FILE_DIR
			.DIRECTORY_SEPARATOR.self::$_language;
			if(is_dir($dir)){
				$handler = opendir($dir);
				while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
					if (stripos($filename, '.csv')) {
						$filenameStrArr = explode(".", $filename);
						if(!empty($filenameStrArr[0])){
							array_push($modules , $filenameStrArr[0]);
						}
					}
				}
				closedir($handler);
				foreach ($modules as $moduleName) {
					self::_loadModuleTranslation($moduleName, $forceReload);
				}
			}
				
			// self::_loadThemeTranslation($forceReload);
			// self::_loadDbTranslation($forceReload);
	
			//			if (!$forceReload && self::$_canUseCache) {
			//				self::_saveCache();
			//			}
		}
	
		if(!is_null(Yii::$app->controller) && !is_null(Yii::$app->controller->module)){
			$module = Yii::$app->controller->module->id;
		}
	
		if('module' == $loadFileScope){
			if( ( empty($module) && 'basic' == self::$_currentModule ) || ( !empty($module) && $module == self::$_currentModule ) ){
				return ;
			}
			 
			if(empty($module)){
				$area = array('basic');
				self::$_currentModule = 'basic';
			}else{
				$area = array('basic',$module);
				self::$_currentModule = $module;
			}
		}else if('js' == $loadFileScope){
			$area = array('basicJs');
		}else{
			$area = array();
		}
		 
	
		self::$_data = array();
		foreach($area as $moduleName){
			if(isset(self::$_wrappedMetaData[$moduleName])){
				self::$_data = array_merge(self::$_data,self::$_wrappedMetaData[$moduleName]);
			}
		}
		return ;
	}
	
	/**
	 * Translate
	 *
	 * @param   array $args
	 * @return  string
	 */
	public static function t(){
		$args = func_get_args();
		$text = array_shift($args);
	
		if (is_string($text) && ''==$text
		|| is_null($text)
		|| is_bool($text)
		|| is_object($text)) {
			return '';
		}
	
		self::loadToCache();
	
		// if(!is_null(Yii::app()->getController()) && !is_null(Yii::app()->getController()->getModule())){
		// $module = Yii::app()->getController()->getModule()->id;
		// $code = $module. self::SCOPE_SEPARATOR .$text;
		// }else{
		// $module = 'application';
		// $code = $module. self::SCOPE_SEPARATOR .$text;
		// }
	
		// $translated = self::_getTranslatedString($text,$code);
	
		$translated = self::_getTranslatedString($text);
		$result = @vsprintf($translated, $args);
	
		if ($result === false) {
			$result = $translated;
		}
	
		return $result;
	}
	
	
	/**
	 * Return translated string from text.
	 *
	 * @param string $text
	 * @return string
	 */
	protected static function _getTranslatedString($text,$code = ''){
		$translated = '';
		if (array_key_exists($code, self::getData())) {
			$translated = self::$_data[$code];
		} elseif (array_key_exists($text, self::getData())) {
			$translated = self::$_data[$text];
		} else {
			$translated = $text;
		}
		return $translated;
	}
	
	/**
	 * Retrieve translation data
	 *
	 * @return array
	 */
	public static function getData(){
		if (is_null(self::$_data)) {
			return array();
		}
		return self::$_data;
	}
	
	/**
	 * Retrieve js translation dictionary
	 *
	 * @return array
	 */
	public static function getJsDictionary(){
		self::loadToCache('js');
		if (is_null(self::$_data)) {
			return array();
		}
		return self::$_data;
	}
	
	/**
	 * Adding translation data
	 *
	 * @param array $data
	 * @param string $scope
	 * @return array $_data
	 */
	protected static function _addData($data, $scope, $forceReload=false){
		foreach ($data as $key => $value) {
			if ($key === $value) {
				continue;
			}
			$key    = self::_prepareDataString($key);
			$value  = self::_prepareDataString($value);
	
			if ($scope && 'basic' != $scope && !$forceReload ) {
				$scopeKey = $scope . self::SCOPE_SEPARATOR . $key;
				self::$_data[$scopeKey] = $value;
			} else {
				self::$_data[$key] = $value;
			}
		}
	
		return self::$_data;
	}
	
	protected static function _prepareDataString($string){
		return str_replace('""', '"', $string);
	}
	
	/**
	 * Retrieve cache identifier
	 *
	 * @return string
	 */
	public function getCacheId(){
		if (is_null(self::$_cacheId)) {
			self::$_cacheId = 'translate';
		}
		return self::$_cacheId;
	}
	
	
	/**
	 * Loading data cache
	 *
	 * @param   string $area
	 * @return  array | false
	 */
	protected static function _loadCache(){
		if (!self::$_canUseCache) {
			return false;
		}
		$data = Mage::app()->loadCache(self::getCacheId());
		$data = unserialize($data);
		return $data;
	}
	
	/**
	 * Saving data cache
	 *
	 * @param   string $area
	 */
	protected static function _saveCache(){
		if (!self::$_canUseCache) {
			return ;
		}
		Mage::app()->saveCache(serialize(self::getData()), self::getCacheId(), array(self::CACHE_TAG), null);
		return ;
	}
	
	/**
	 * Loading data from module translation files
	 *
	 * @param   string $moduleName
	 * @param   string $files
	 * @return  null
	 */
	protected static function _loadModuleTranslation($moduleName,$forceReload=false) {
		// 获取翻译词典，例如en目录下的csv文件
		$root = Yii::getAlias('@eagle');
		$fileName = $moduleName.'.csv';
		$file = $root.DIRECTORY_SEPARATOR.self::TRANSLATE_CSV_FILE_DIR
		.DIRECTORY_SEPARATOR.self::$_language.DIRECTORY_SEPARATOR.$fileName;
		 
		// 将$moduleName add 到data 的key里
		// self::_addData(self::_getFileData($file), $moduleName, $forceReload);
		// self::_addData(self::_getFileData($file), false, $forceReload);
		self::$_wrappedMetaData[$moduleName] = self::_getFileData($file);
		return ;
	}
	
	/**
	 * Retrieve data from file
	 *
	 * @param   string $file
	 * @return  array
	 */
	protected static function _getFileData($file){
		$data = array();
		if (file_exists($file)) {
			$data = self::getCsvFileDataPairs($file);
		}
		return $data;
	}
	
	/**
	 * Retrieve CSV file data as array
	 *
	 * @param   string $file
	 * @return  array
	 */
	public static function getCsvFileData($file)
	{
		$data = array();
		if (!file_exists($file)) {
			throw new \Exception('File "'.$file.'" do not exists');
		}
	
		$fh = fopen($file, 'r');
		while ($rowData = fgetcsv($fh, self::$_lineLength, self::$_delimiter, self::$_enclosure)) {
			$data[] = $rowData;
		}
		fclose($fh);
		return $data;
	}
	
	/**
	 * Retrieve CSV file data as pairs
	 *
	 * @param   string $file
	 * @param   int $keyIndex
	 * @param   int $valueIndex
	 * @return  array
	 */
	public static function getCsvFileDataPairs($file, $keyIndex=0, $valueIndex=1)
	{
		$data = array();
		$csvData = self::getCsvFileData($file);
		foreach ($csvData as $rowData) {
			if (isset($rowData[$keyIndex])) {
				$data[$rowData[$keyIndex]] = isset($rowData[$valueIndex]) ? $rowData[$valueIndex] : null;
			}
		}
		return $data;
	}
	
	// 设置当前语言的language code
	public static function setCurrentLanguague($lanCode){
		if(in_array($lanCode, array_keys(self::$_translate_lan_type))){
			setcookie('lan', $lanCode, time() + 3600 );
		}else {
			setcookie('lan', 'zh-cn', time() + 3600 );
		}
	}
	
	// 获取当前语言的language code
	public static function getCurrentLanguague(){
	    // dzt20190308 当次访问已经发出设置setcookie lan 的header 但未到客户端，所以$_COOKIE['lan']还是空的，避免后面再重复设置
	    if(!empty(self::$setCookieLan)){
	        return self::$setCookieLan;
	    }
	    
		if(empty($_COOKIE['lan'])){
		    self::$setCookieLan = 'zh-cn';
			setcookie('lan', 'zh-cn' , time() + 3600 );
		}
		return isset($_COOKIE['lan'])?$_COOKIE['lan']:'zh-cn';
	}
	
/*
 * yzq 2017-3-21
 * $keepOriginal : default is true, when true, return the string will contains ( $originalStr )
 * TranslateHelper::toChinesePrompt($str);
 * */
public static function toChinesePrompt($str,$keepOriginal=true){
	$to = 'zh';
	$from = 'auto';
	if ($keepOriginal)
		$tail = "(".$str.")";
	else
		$tail = '';
	
	//step 1: check if redis already have this cache
	
	$resultStr = RedisHelper::RedisGet("Utility", "Translate:".$str);
	
	if (empty($resultStr)){
		//echo "Not found cache, ask baidu translate ";
		$journal_id = SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array('Not found cache, ask baidu translate',$str));
		
		$ret = self::translate($str, $from, $to);
		$resultStr='';
		if (!empty($ret['trans_result'])){
			foreach ($ret['trans_result'] as $aLine){
				$resultStr .= ( ($resultStr ==''?'':"\n") . $aLine['dst'] );
			}
			
			RedisHelper::RedisSet("Utility", "Translate:".$str,$resultStr );
			
		}else
			$resultStr = $str;
	}else{
		//echo "Got cache, do not ask baidu translate ";
		$journal_id = SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array('Got cache, do not ask baidu translate',$resultStr));
		
	}
	
	return $resultStr . $tail;
}
	
	
	
/*翻译入口
 *  param: $query , 要翻译的语句内容，例如 "hello, I am fine.
 *  and you?"
 *  param: $from, 原来的 语言，例如 en，fra，如果实在不知道，就写 auto
 *  param： $to， 翻译的目标语言，例如 zh，en
 *  
语言简写	名称
auto	自动检测
zh	中文
en	英语
yue	粤语
wyw	文言文
jp	日语
kor	韩语
fra	法语
spa	西班牙语
th	泰语
ara	阿拉伯语
ru	俄语
pt	葡萄牙语
de	德语
it	意大利语
el	希腊语
nl	荷兰语
pl	波兰语
bul	保加利亚语
est	爱沙尼亚语
dan	丹麦语
fin	芬兰语
cs	捷克语
rom	罗马尼亚语
slo	斯洛文尼亚语
swe	瑞典语
hu	匈牙利语
cht	繁体中文
 * */
public static function translate($query, $from, $to)
{   $digest= md5($query);
	$keyL2 = $to."-@#-".$digest;
	//先看看这个query 的string在 redis有没有现成的，如果有，就不需要搞了，直接读取redis的
	$resultRedis = RedisHelper::RedisGet("TranslateResultCache", $keyL2 );
	if (!empty($resultRedis))
		return json_decode($resultRedis, true) ;
	
	 
	$x =   rand(1,999)  % 2;
	if ($x == 0  ){
		$appId = self::APP_ID;
		$secKey= self::SEC_KEY;
	}else {
		// TODO add BD translate account info @xxx@
		$appId = '@xxx@';
		$secKey= '@xxx@';
	}
		
    $args = array(
        'q' => $query,
        'appid' => $appId,
        'salt' => rand(10000,99999),
        'from' => $from,
        'to' => $to,

    );
    $args['sign'] = self::buildSign($query,$appId, $args['salt'], $secKey);
    $ret = self::call(self::URL, $args);
    //写入到redis
    RedisHelper::RedisSet("TranslateResultCache", $keyL2,$ret );
    $ret = json_decode($ret, true);

    return $ret; 
}

//加密
private static function buildSign($query, $appID, $salt, $secKey)
{/*{{{*/
    $str = $appID . $query . $salt . $secKey;
    $ret = md5($str);
    return $ret;
}/*}}}*/

//发起网络请求
private static function call($url, $args=null, $method="post", $testflag = 0, $timeout = self::CURL_TIMEOUT, $headers=array())
{/*{{{*/
    $ret = false;
    $i = 0; 
    while($ret === false) 
    {
        if($i > 1)
            break;
        if($i > 0) 
        {
            sleep(1);
        }
        $ret = self::callOnce($url, $args, $method, false, $timeout, $headers);
        $i++;
    }
    return $ret;
}/*}}}*/

private static function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = self::CURL_TIMEOUT, $headers=array())
{/*{{{*/
    $ch = curl_init();
    if($method == "post") 
    {
        $data = self::convert($args);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    else 
    {
        $data = self::convert($args);
        if($data) 
        {
            if(stripos($url, "?") > 0) 
            {
                $url .= "&$data";
            }
            else 
            {
                $url .= "?$data";
            }
        }
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if(!empty($headers)) 
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if($withCookie)
    {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
    }
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}/*}}}*/

private static function convert(&$args)
{/*{{{*/
    $data = '';
    if (is_array($args))
    {
        foreach ($args as $key=>$val)
        {
            if (is_array($val))
            {
                foreach ($val as $k=>$v)
                {
                    $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                }
            }
            else
            {
                $data .="$key=".rawurlencode($val)."&";
            }
        }
        return trim($data, "&");
    }
    return $args;
}
	
    
}