<?php
namespace common\helpers;

class Helper_Currency{
    /**
     * 货币转换
     *
     * @param float $amount   金额
     * @param string $to_code 目标货币代号  CNY USD EUR...
     * @param string $from_code  源货币代号 CNY USD EUR...
     * @param int $round        保留小数位数
     * @return float
     */
	//这个数据来自国家外汇管理局，每个月更新一次，所有货币1元对 USD 的汇价，例如 1 RMB = USD 0.150802268066112
	//这个版本汇率时间是 2016-11-30， 更新by 杨增强
	//各种货币对美元折算率表 http://www.safe.gov.cn/wps/portal/sy/tjsj_dmzsl
	private static $ratio_currency_vs_USD=array(
'AED'=>0.272233635065343,
'ALL'=>0.00882184288297825,
'AOA'=>0.00603256377928056,
'ARS'=>0.0561837207669078,
'AUD'=>0.797705215284693,
'BAM'=>0.597853705198338,
'BGN'=>0.60033018159988,
'BHD'=>2.65164070268479,
'BND'=>0.736702519522617,
'BOB'=>0.14336917562724,
'BRL'=>0.31922364808785,
'BWP'=>0.09795,
'BYN'=>0.515862780500387,
'CAD'=>0.802921986237237,
'CHF'=>1.03243018295855,
'CLP'=>0.00152982391726712,
'CNY'=>0.148625953063924,
'COP'=>0.000333283340832209,
'CZK'=>0.0450562076190047,
'DKK'=>0.158011857393073,
'DZD'=>0.00922567949435896,
'EGP'=>0.0558815311539536,
'EUR'=>1.17502192232808,
'GBP'=>1.31428444034897,
'GHS'=>0.226372382569327,
'GYD'=>0.00481324605313824,
'HKD'=>0.128053148640816,
'HRK'=>0.158478605388273,
'HUF'=>0.00385760883160102,
'IDR'=>0.000075041272699985,
'ILS'=>0.281195079086116,
'INR'=>0.0155969741870077,
'IQD'=>0.000856164383561644,
'IRR'=>3.05670181873758E-05,
'ISK'=>0.00969602947592961,
'JOD'=>1.41143260409315,
'JPY'=>0.00904819939657863,
'KES'=>0.00962463907603465,
'KRW'=>0.000890135671461484,
'KWD'=>3.31076495224222,
'KZT'=>0.00305730926211841,
'LAK'=>0.000120813874748632,
'LBP'=>0.000663042036865137,
'LKR'=>0.00651062860119144,
'LYD'=>0.728703636231145,
'MAD'=>0.105513057240834,
'MDL'=>0.0553403431101273,
'MKD'=>0.0190876121397213,
'MMK'=>0.000732869182850861,
'MNT'=>0.000410172272354389,
'MOP'=>0.124293083089926,
'MUR'=>0.0298775022408127,
'MVR'=>0.0643086816720257,
'MWK'=>0.00138230374742546,
'MXN'=>0.0563275801803699,
'MYR'=>0.233648194595155,
'NGN'=>0.002716754702143,
'NOK'=>0.126318165106174,
'NPR'=>0.00974184120798831,
'NZD'=>0.751690620216102,
'OMR'=>2.5974025974026,
'PEN'=>0.30826140567201,
'PHP'=>0.019790223629527,
'PKR'=>0.00949171847563001,
'PLN'=>0.276143497201747,
'PYG'=>0.00018001800180018,
'QAR'=>0.274574409665019,
'RON'=>0.257605811587109,
'RSD'=>0.0097661018604424,
'RUB'=>0.0167830835579259,
'SAR'=>0.266631898862481,
'SDG'=>0.149774589243189,
'SDR'=>1.40554,
'SEK'=>0.123361514827294,
'SGD'=>0.737050963839306,
'SLL'=>0.000131509731720147,
'SRD'=>0.134066228716986,
'SSP'=>0.00796644533226052,
'SYP'=>0.00194061711624297,
'THB'=>0.0299981739961498,
'TND'=>0.415713988775722,
'TRY'=>0.283026970586186,
'TWD'=>0.0330403753386638,
'TZS'=>0.000446627958910228,
'UAH'=>0.0385653682992673,
'UGX'=>0.000277546489036914,
'UYU'=>0.0352671486510316,
'UZS'=>0.000245679118503323,
'VEF'=>0.100125156445557,
'VND'=>4.39976241282971E-05,
'XAF'=>0.00176029995511235,
'YER'=>0.00399760143913652,
'ZAR'=>0.076904663698605,
'ZMW'=>0.112359550561798,
			
			
			
			
'USD'=>1,
	);
	
	static function convertThisCurrencyToUSD($OrigCurrency3Char='',$amount=0){
		
		if (empty($OrigCurrency3Char) or empty(self::$ratio_currency_vs_USD[$OrigCurrency3Char]))
			return -1;
		else
			return $amount * self::$ratio_currency_vs_USD[$OrigCurrency3Char];
	}
	
    static function convert($amount,$to_code,$from_code ='CNY',$round=3){
    	/*$ratio_EUR_CNY = 8.6;
    	$ratio_USD_CNY = 6.56;// dzt20160526 从6.2 改为6.56
    	$ratio_GBP_CNY = 10.6;
    	$ratio_HKD_CNY = 0.8;
    	$ratio_CNY_CNY = 1;
    	$ratio_AUD_CNY = 4.8;
    	$ratio_CAD_CNY = 5;
		$ratio_JPY_CNY=0.05;
		$ratio_MYR_CNY=1.49;  // 马来西亚
		$ratio_IDR_CNY=0.0004;//印尼
		$ratio_PHP_CNY=0.1357; //菲律宾
		$ratio_SGD_CNY=4.4684;//新加坡
		$ratio_THB_CNY=0.1769; //泰国
        $ratio_VND_CNY=0.0003; // 越南盾
        		
        $ratio_ARS_CNY=0.6939; // 阿根廷比索
        $ratio_CLP_CNY=0.0091; // 智利比索
        $ratio_COP_CNY=0.0022; // 哥伦比亚比索
        $ratio_VND_ECS=0.0002; // 厄瓜多尔苏克雷
        $ratio_MXN_CNY=0.3830; // 墨西哥元        
        $ratio_PAB_CNY=6.3283; // 巴拿马巴波亚
        $ratio_PEN_CNY=1.9227; // 秘鲁索尔
        $ratio_VEB_CNY=0.9935; // 委内瑞拉玻利瓦尔
        
        $ratio_ARS_CNY=0.6939; // 阿根廷比索
        $ratio_CLP_CNY=0.0091; // 智利比索
        $ratio_COP_CNY=0.0022; // 哥伦比亚比索
        $ratio_VND_ECS=0.0002; // 厄瓜多尔苏克雷
        $ratio_MXN_CNY=0.3830; // 墨西哥元
        $ratio_PAB_CNY=6.3283; // 巴拿马巴波亚
        $ratio_PEN_CNY=1.9227; // 秘鲁索尔
        $ratio_VEB_CNY=0.9935; // 委内瑞拉玻利瓦尔
        
        
        
        $ratio_AOA_CNY=0.0469; // 1 安哥拉宽扎(AOA) = 0.0469 人民币(CNY)
        $ratio_XAF_CNY=0.011; // 1 喀麦隆（中部非洲金融共同体法郎 (XAF)）=  0.011 人民币(CNY)
        $ratio_EGP_CNY=0.79; // 1 埃及镑 (EGP)= 0.79
        $ratio_GHS_CNY=1.64; // 加纳塞地 (GHS)=1.64
        $ratio_XOF_CNY=0.011; //科特迪瓦 西非金融共同体法郎 (XOF)=  0.011 人民币(CNY)
        $ratio_KES_CNY=0.062;//肯尼亚先令 (KES)=0.062
        $ratio_NGN_CNY=0.032;//尼日利亚耐拉 (NGN)=0.032
        $ratio_MAD_CNY=0.65;//摩洛哥迪拉姆 (MAD)=0.65
        $ratio_TZS_CNY=0.0029;//坦桑尼亚先令 (TZS)=0.0029
        $ratio_UGX_CNY=0.0018;//乌干达先令 (UGX)=0.0018
        
		*/
    	
        $to_code=strtoupper($to_code);
        $from_code=strtoupper($from_code);
        
        $ratio = 1;
       // eval("\$ratio =  \$ratio_".$from_code."_CNY / \$ratio_".$to_code."_CNY ".";");

        $ratio = self::$ratio_currency_vs_USD[$from_code] / self::$ratio_currency_vs_USD[$to_code];
        return round($amount * $ratio ,$round);
    }
    /**
     * 货币显示
     *
     * @param float $amount  金额
     * @param string $code   显示币种
     * @param string $from_code 原始币种
     * @param int $round 保留小数位数
     * @return string
     */
    static function fetchDisplay($amount,$code = 'CNY',$from_code=null,$round=3){
        $code=strtoupper($code);
        if (is_null($from_code)){
            $from_code =$code;
        }
        $from_code=strtoupper($from_code);
        if ($code == $from_code){
            return self::_add_prefix_and_postfix(round($amount,$round),$code);
        }
        return self::_add_prefix_and_postfix(self::convert($amount,$code,$from_code,$round),$code);
    }
    /**
     * 增加前缀后缀
     *
     * @param float $amount  金额
     * @param string $code   币种
     * @return string
     */
    static function _add_prefix_and_postfix($amount,$code){
        static $_symbol;
        if (!isset($_symbol[$code])){
//          $_symbol[$code]=Currencies::find()->getByCode($code);
        }
        $negative = '';// abs($amount) == $amount?'':'-';
//      $amount=abs($amount);
        if ($code == 'CNY'){
            return $negative.'￥'.$amount.' RMB';
        }elseif ($code == 'USD') {
            return $negative.'＄'.$amount.' USD';
        }elseif ($code == 'EUR'){
            return $negative.'€'.$amount.' EUR';
        }
        return $negative.$amount. ' '.$code;
    }
    
    //获取是否存在该货币
    public static function getCurrencyIsExist($currency_type){
    	if(isset(self::$ratio_currency_vs_USD[$currency_type])){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    //根据每天自动拉取的汇率转换USD
    static function convertThisCurrencyToUSDFromDay($OrigCurrency3Char='', $amount=0){
    	if (empty($OrigCurrency3Char)){
    		return -1;
    	}
    	if($OrigCurrency3Char == 'RMB'){
    		$OrigCurrency3Char = 'CNY';
    	}
    	
    	$EXCHANGE_RATE = \eagle\modules\statistics\helpers\ProfitHelper::GetExchangeRate($OrigCurrency3Char, "USD", true);
		if(!empty($EXCHANGE_RATE)){
			return $EXCHANGE_RATE * $amount;
		}
		else{
			return -1;
		}
    }
    
}
?>
