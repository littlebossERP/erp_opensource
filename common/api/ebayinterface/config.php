<?php
namespace common\api\ebayinterface;
use eagle\models\EbayDeveloperAccountInfo;
/**
 * eBay接口基本设置和参数配置
 * @package interface.ebay.tradingapi
 *
 */
class config {
	/**
	 *  toggle to true if going against production  Or false to Sandbox
	 *
	 * @var bool
	 */
	static $production=0;
	/**
	 * 返回 notification 接受地址
	 *
	 * @param bool $production 
	 * @return string
	 */
	static function  getApllicationURL($production=false){
		if ($production){
			return 'http://proxy.xlb.com/proxy.php?production=1';
		}else {
			return 'mailto://xxx@qq.com';
		}
	}
    
	// TODO ebay dev account 
	static $token=array(
        'devID'=>'',
        'appID'=>'',
        'certID'=>'',
        'runame'=>'',
    );
	
    static $tokenSandbox=array(
        'devID'=>'3508915e-f265-499c-b15e-2f262fc7bed1',
        'appID'=>'jiongqia-littlebo-SBX-b45f30466-1fa4aec8',
        'certID'=>'SBX-45f3046680a9-abe4-47e2-ac4a-3a08',
        'runame'=>'jiongqiang_xiao-jiongqia-little-rjjlm',
    );
    
    
    static function getConfig($production=null){
    	if(is_null($production)) $production=self::$production;
    	if(!$production){
    		return self::getConfigSandbox();
    	}
        $config=self::$token;
        
        //set the Server to use (Sandbox or Production)
        $config["serverUrl"] = 'https://api.ebay.com/ws/api.dll';      // server URL different for prod and sandbox
            
        // the token representing the eBay user to assign the call with
        // this token is a long string - don't insert new lines - different from prod token
 		$config["tokenUrl"]='https://signin.ebay.com/ws/eBayISAPI.dll?SignIn&RuName='.$config["runame"].'&SessID=';
		$config["host"]='api.ebay.com';
		$config["port"]='443';
		$config["path"]='/ws/api.dll';
        // shipping 
        $config["shoppingUrl"]='http://open.api.ebay.com/shopping?';
        return $config;
    }
    
    static function getConfigByDevAccountID($production=null,$devAccountID){
    	if(is_null($production)) $production=self::$production;
    	if(!$production){
    		return self::getConfigSandbox();
    	}else{
    		$config = self::getProductConfig($devAccountID);
    	}
    	//20161124commentkh $config=self::$token; //ebay 账号封号问题
    
    	//set the Server to use (Sandbox or Production)
    	$config["serverUrl"] = 'https://api.ebay.com/ws/api.dll';      // server URL different for prod and sandbox
    
    	// the token representing the eBay user to assign the call with
    	// this token is a long string - don't insert new lines - different from prod token
    	$config["tokenUrl"]='https://signin.ebay.com/ws/eBayISAPI.dll?SignIn&RuName='.$config["runame"].'&SessID=';
    	$config["host"]='api.ebay.com';
    	$config["port"]='443';
    	$config["path"]='/ws/api.dll';
    	// shipping
    	$config["shoppingUrl"]='http://open.api.ebay.com/shopping?';
    	return $config;
    }
        
    static function getConfigSandbox(){
        $config=self::$tokenSandbox;
        $config["siteID"]=0;
        //set the Server to use (Sandbox or Production)
        $config["serverUrl"] = 'https://api.sandbox.ebay.com/ws/api.dll';
        // the token representing the eBay user to assign the call with
        // this token is a long string - don't insert new lines - different from prod token
		$config["tokenUrl"]='https://signin.sandbox.ebay.com/ws/eBayISAPI.dll?SignIn&RuName='.$config["runame"].'&SessID=';
		$config["host"]='api.sandbox.ebay.com';
		$config["port"]='443';
		$config["path"]='/ws/api.dll';
		$config["shoppingUrl"]='http://open.api.sandbox.ebay.com/shopping?';
        return $config;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 根据分配的开发者编号， 获取ebay 开发者账号相关的信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     +---------------------------------------------------------------------------------------------
     * @return	int					dev account id
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2016/11/24				初始化
     +---------------------------------------------------------------------------------------------
     **/
    static function getProductConfig($devAccountID){
    	
    	$devAccountInfo = EbayDeveloperAccountInfo::findOne($devAccountID);
    	
    	if (!empty($devAccountInfo)){
    		$token=array(
    				'devID'=>trim($devAccountInfo->devID),
    				'appID'=>trim($devAccountInfo->appID),
    				'certID'=>trim($devAccountInfo->certID),
    				'runame'=>trim($devAccountInfo->runame),
    		);
    		return $token;
    	}else{
    		//echo "找不到对应的开发者账号！";
    		return self::$token; //防止 没有 index 报错
    	}
    }//end of function getProductConfig
}
?>