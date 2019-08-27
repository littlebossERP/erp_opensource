<?php
namespace eagle\modules\tracking\helpers;
use eagle\modules\tracking\models\Tracking;
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
 
 
class CarrierTypeOfTrackNumber {
/**
 +---------------------------------------------------------------------------------------------
 *列出17track所有支持的快递服务对应的trackingNo静态规则
 *物流商单号规则来源：http://www.17track.net/zh-cn/rule-express.shtml
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * '*'数字类型
 * '#'字母类型
 * '!'数字或字母类型
 * ahead(x)		trackingNo前x位的字符类型
 * last(x)		trackingNo后x位的字符类型
 * numOnly		是否是纯数字运单
 * fixedLetterS	起始位置固定字符,type:array
 * fixedLetterE	结束位置固定字符,type:array
 +---------------------------------------------------------------------------------------------
 * 新增规则：
 *	1.纯数字：在快递名称(或简写/拼音缩写)对应元素下添加 array('len'=>x,'numOnly'=>true)
 *
 *	2.非纯数字：在快递名称(或简写/拼音缩写)对应元素下添加 example:
 *		array(
 *			'len'=>13,
 *			'ahead2'=>'##',
 *			'ahead3'=>'##*',
 *			'ahead5'=>'##***',
 *			'last2'=>'##',
 *			'last3'=>'*##',
 *			'numOnly'=>false,
 *			'fixedLetterS'=>array('GE','GD'...),//起始位置有固定字符才set，没有就不set
 *			'fixedLetterE'=>array('WW','W'...),//结束位置有固定字符才set，没有就不set
 *		)
 *新增快递服务商：
 *		添加$expresses元素,结构参照已有快递服务商
 *		static $expressCode 处添加新服务商对应Code
 * 
 +---------------------------------------------------------------------------------------------
 * @invoking					checkExpressOftrackingNo();
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		liang		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/

static $foreignExpressNames= array(
'100001'=>'DHL',
	'100002'=>'UPS',
	'100003'=>'Fedex',
	'100004'=>'TNT',
	'100007'=>'DPD',
	'100010'=>'DPD(UK)',
	'100011'=>'OneWorld',
	'100005'=>'GLS',
	'100008'=>'EShipper',
	'100009'=>'Toll',
	'100006'=>'Aramex'
);	
	
/*判断ship by name 里面有没有海外指定的 快递，如果有，不要用货代自己的api查询了
 * return true / false
* */
public static function isMarkedForeignExpress($shipBy){
	global $CACHE;
	$foreignExpressNames = self::$foreignExpressNames;
	
	if (!isset($CACHE['resultForIsMarkedForeignExpress'][$shipBy])){
		$CACHE['resultForIsMarkedForeignExpress'][$shipBy] = false;
		foreach ($foreignExpressNames as $code=>$shipName){
			$matched = (stripos(strtolower($shipName),strtolower($shipBy)) !== false or
					stripos(strtolower($shipBy),strtolower($shipName)) !== false  );
			if ($matched){
				$CACHE['resultForIsMarkedForeignExpress'][$shipBy] = true;
				break;
			}
		}
	}
	return $CACHE['resultForIsMarkedForeignExpress'][$shipBy];

}

public static function isOSHorForeignExpress($shipBy){
	 
	return  strpos($shipBy, "海外仓") !== false or self::isMarkedForeignExpress($shipBy);

}


static $expresses = array(
	'Aramex'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>11,
			'numOnly'=>true
		),
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>20,
			'numOnly'=>true
		)
	),
	'DHL'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>9,
			'numOnly'=>true
		)
	),
	'DPD'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>14,
			'numOnly'=>true
		),
		array(
			'len'=>15,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'*****',
			'last2'=>'*!',
			'last3'=>'**!',
			'numOnly'=>false
		)
	),
	'DPD(UK)'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>14,
			'numOnly'=>true
		),
		array(
			'len'=>15,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'*****',
			'last2'=>'*!',
			'last3'=>'**!',
			'numOnly'=>false
		)
	),
	'EShipper'=> array(
		array(
			'len'=>19,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false
		),
		array(
			'len'=>20,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'###!*',
			'last2'=>'*!',
			'last3'=>'**!',
			'numOnly'=>false
		),
		array(
			'len'=>21,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'#####',
			'last2'=>'*!',
			'last3'=>'**!',
			'numOnly'=>false
		)
	),
	'Fedex'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>15,
			'numOnly'=>true
		)
	),
	'GLS'=> array(
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>11,
			'numOnly'=>true
		),
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>14,
			'numOnly'=>true
		),
		array(
			'len'=>20,
			'numOnly'=>true
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterE'=>array('GB'),
		)
	),
	'OneWorld'=> array(
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>8,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('CZL'),
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterE'=>array('GB'),
		)
	),
	'TNT'=> array(
		array(
			'len'=>6,
			'numOnly'=>true
		),
		array(
			'len'=>9,
			'numOnly'=>true
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('GE','GD'),
			'fixedLetterE'=>array('WW'),
		)
	),
	'Toll'=> array(
		array(
			'len'=>9,
			'numOnly'=>true
		),
		array(
			'len'=>12,
			'numOnly'=>true
		)
	),
	'UPS'=> array(
		array(
			'len'=>9,
			'numOnly'=>true
		),
		array(
			'len'=>10,
			'numOnly'=>true
		),
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>18,
		//	'ahead2'=>'!!',
		//	'ahead3'=>'!!!',
		//	'ahead5'=>'!!!!!',
		///	'last2'=>'!!',
		//	'last3'=>'!!!',  //1ZX2988V0386478519
			'numOnly'=>false,
			'fixedLetterS'=>array('1Z'),
			/*
			'len'=>18,
			'ahead2'=>'*#',
			'ahead3'=>'*#!',
			'ahead5'=>'*#!!!',
			'last2'=>'##',
			'last3'=>'*##',  //1ZX2988V0386478519
			'numOnly'=>false,
			'fixedLetterS'=>array('1Z'),
			*/
		)
	),
	'百千诚物流'=> array(//百千诚物流(BQC)
		array(
			'len'=>9,
			'numOnly'=>true
		),
		array(
			'len'=>10,
			'numOnly'=>true
		)
	),
	'俄顺达'=> array(//俄顺达(EX_007)
		array(
			'len'=>12,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('EX007'),
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
		)
	),
	'俄速递'=> array(//俄速递(XRU)
		array(
			'len'=>12,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('XRU'),
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('XRU'),
		),
			array(
			'len'=>14,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('XRU'),
		)
	),
	'俄速通'=> array(//俄速通(Ruston)
		array(
			'len'=>11,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('CH'),
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('R','C'),
			'fixedLetterE'=>array('CN')
		)
	),
	'俄通收'=> array(//俄通收(RETS)
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
		),
		array( //TS 000 185 123 E
				'len'=>12,
				'ahead2'=>'##',
				'ahead3'=>'##*',
				'ahead5'=>'##***',
				'last2'=>'*#',
				'last3'=>'**#',
				'numOnly'=>false,
		)
	),
	'俄易达'=> array(//俄易达(RUSH)
		array(
			'len'=>7,
			'numOnly'=>true
		),
		array(
			'len'=>8,
			'ahead2'=>'#*',
			'ahead3'=>'#**',
			'ahead5'=>'#****',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
		),
		array(
			'len'=>8,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'***#*',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
		)
	),
	'飞特物流'=> array(//飞特物流
		array(
			'len'=>16,
			'ahead2'=>'#*',
			'ahead3'=>'#**',
			'ahead5'=>'#****',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('A')
		),
	),
	'万邑通'=> array(//万邑通
			array(
					'len'=>18,
					'ahead2'=>'##',
					'ahead3'=>'##*',
					'ahead5'=>'##***',
					'last2'=>'##',
					'last3'=>'*##',
					'numOnly'=>false,
					'fixedLetterS'=>array('ID'),
					'fixedLetterE'=>array('CN')
			),
	),
	'华翰物流'=> array(//华翰物流
		array(
			'len'=>11,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'###',
			'numOnly'=>false,
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('HH')
		)
	),
	'快达物流'=> array(//快达物流
		array(
			'len'=>10,
			'ahead2'=>'#*',
			'ahead3'=>'#**',
			'ahead5'=>'#****',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('Z')
		),
		array(
			'len'=>12,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('KD')
		)
	),
	'淼信国际'=> array(//淼信国际
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false
		)
	),
	'顺丰速运'=> array(//顺丰速运	//废弃，len设置为1w,
		array(
			'len'=>10000,
			//'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>10000,
			// 'len'=>17,
			'numOnly'=>true
		),
		array(
			'len'=>10000,
			// 'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('R'),
			'fixedLetterE'=>array('NL')
		)
	),
	'顺丰'=> array(//顺丰
		array(
			'len'=>12,
			'numOnly'=>true
		),
		array(
			'len'=>17,
			'numOnly'=>true
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('R'),
			'fixedLetterE'=>array('NL')
		)
	),
	'燕文物流'=> array(//燕文物流
		array(
			'len'=>11,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('Y')
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterE'=>array('YP','YW')
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('Y'),
			'fixedLetterE'=>array('CN')
		)
	),
	'云途物流'=> array(//云途物流	//废弃，len设置为1w,
		array(
			'len'=>10000,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		),
		array(
			'len'=>10000,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		),
		array(
			'len'=>10000,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		)
	),
	'云途'=> array(//云途物流
		array(
			'len'=>18,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		),
		array(
			'len'=>20,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		),
		array(
			'len'=>21,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('YT')
		)
	),
	'UBI'=> array(//UBI 888000001
		array(
			'len'=>16,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('BJ')
		)
	),
	'equick'=> array(
			array(
					'len'=>9,
					'numOnly'=>true
			),
			array(
					'len'=>10,
					'numOnly'=>true
			)
	),
	'全球邮政'=> array(//全球邮政
		array(
			'len'=>6,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'313'=>'Colombia(哥伦比亚)',
			)
		),
		array(
			'len'=>9,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('ML'),
			'EMS_designatedCountrys'=>array(
				'1103'=>'United Kingdom(英国)',
			)
		),
		array(
			'len'=>10,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
				'704'=>'Germany(德国)',
				'1406'=>'New Zealand(新西兰)',
			)
		),
		array(
			'len'=>10,
			'ahead2'=>'!!',
			'ahead3'=>'!!!',
			'ahead5'=>'!!!**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'EMS_designatedCountrys'=>array(
				'115'=>'Australia(澳大利亚EMS)',
			)
		),
		array(
			'len'=>10,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('IP'),
			'fixedLetterE'=>array('CO'),
			'EMS_designatedCountrys'=>array(
				'313'=>'Colombia(哥伦比亚)',
			)
		),
		array(
			'len'=>10,
			'ahead2'=>'#*',
			'ahead3'=>'#**',
			'ahead5'=>'#****',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('M'),
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
			)
		),
		array(
			'len'=>12,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'1918'=>'Spain(西班牙)',
				'310'=>'Chile(智利)'	,
				'704'=>'Germany(德国)',
				'115'=>'Australia(澳大利亚EMS)',
			)
		),
		array(
			'len'=>13,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2102'=>'Ukraine(乌克兰)',
				'301'=>'China(中国EMS)',
				'115'=>'Australia(澳大利亚EMS)',
				'116'=>'Austria(奥地利)',
			)
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'EMS_designatedCountrys'=>array(
				'0'=>'Unknown(无法区分)'
			)
		),
		array(
			'len'=>13,
			'ahead2'=>'*#',
			'ahead3'=>'*#*',
			'ahead5'=>'*#***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'EMS_designatedCountrys'=>array(
				'605'=>'France(法国小包)',
			)
		),	
		array(
			'len'=>13,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'*****',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterE'=>array('DE'),
			'EMS_designatedCountrys'=>array(
				'704'=>'Germany(德国)',
			)
		),
		array(
			'len'=>13,
			'ahead2'=>'*#',
			'ahead3'=>'*#*',
			'ahead5'=>'*#***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('6W','6M'),
			'EMS_designatedCountrys'=>array(
				'605'=>'France(法国EMS)',
			)
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('CV'),
			'EMS_designatedCountrys'=>array(
				'1802'=>'Romania(罗马尼亚)',
			)	
		),
		array(
			'len'=>13,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'####*',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterE'=>array('GB'),
			'EMS_designatedCountrys'=>array(
				'1103'=>'United Kingdom(英国)',
			)
		),
		array(
			'len'=>14,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'1803'=>'Russian Federation(俄罗斯)',
			)
		),
		array(
			'len'=>14,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'####*',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('P'),
			'EMS_designatedCountrys'=>array(
					'1103'=>'United Kingdom(英国)',
			)
		),
		array(
			'len'=>15,
			'ahead2'=>'**',
			'ahead3'=>'***',
			'ahead5'=>'*****',
			'last2'=>'*#',
			'last3'=>'**#',
			'numOnly'=>false,
			'fixedLetterS'=>array('K'),
			'EMS_designatedCountrys'=>array(
				'605'=>'France(法国EMS)',
			)
		),
		array(
			'len'=>16,
			'ahead2'=>'!!',
			'ahead3'=>'!!!',
			'ahead5'=>'!!!**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'EMS_designatedCountrys'=>array(
				'115'=>'Australia(澳大利亚EMS)',
			)
		),
		array(
			'len'=>16,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('JJD'),
			'EMS_designatedCountrys'=>array(
				'704'=>'Germany(德国)',
			)
		),
		array(
			'len'=>16,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'704'=>'Germany(德国)',
				'115'=>'Australia(澳大利亚EMS)',
				'304'=>'Canada(加拿大)',
				'1918'=>'Spain(西班牙)',
			)
		),
		array(
			'len'=>18,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'1925'=>'Switzerland(瑞士)',
				'206'=>'Belgium(比利时小包)',
			)
		),
		array(
			'len'=>18,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'##',
			'last3'=>'*##',
			'numOnly'=>false,
			'fixedLetterS'=>array('SCB'),
			'fixedLetterE'=>array('SA'),
			'EMS_designatedCountrys'=>array(
				'1907'=>'Saudi Arabia(沙特阿拉伯)',
			)
		),
		array(
			'len'=>20,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
			)
		),
		array(
			'len'=>21,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'####*',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('JJFI'),
			'EMS_designatedCountrys'=>array(
				'604'=>'Finland(芬兰)',
			)
		),
		array(
			'len'=>21,
			'ahead2'=>'##',
			'ahead3'=>'###',
			'ahead5'=>'###**',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('JJD'),
			'EMS_designatedCountrys'=>array(
				'704'=>'Germany(德国)',
			)
		),
		array(
			'len'=>22,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'704'=>'Germany(德国)',
				'2105'=>'United States(美国)',
				'116'=>'Austria(奥地利)',
			)
		),
		array(
			'len'=>23,
			'ahead2'=>'##',
			'ahead3'=>'##*',
			'ahead5'=>'##***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('GI','CD','CU','PA','VD'),
			'EMS_designatedCountrys'=>array(
				'1918'=>'Spain(西班牙)',
			)
		),
		array(
			'len'=>24,
			'ahead2'=>'#!',
			'ahead3'=>'#!*',
			'ahead5'=>'#!***',
			'last2'=>'**',
			'last3'=>'***',
			'numOnly'=>false,
			'fixedLetterS'=>array('P'),
			'EMS_designatedCountrys'=>array(
				'1918'=>'Spain(西班牙)',
			)
		),
		array(
			'len'=>24,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'206'=>'Belgium(比利时小包)',
			)
		),
		array(
			'len'=>26,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
			)
		),
		array(
			'len'=>28,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
			)
		),
		array(
			'len'=>30,
			'numOnly'=>true,
			'EMS_designatedCountrys'=>array(
				'2105'=>'United States(美国)',
			)
		)
	)
);

/**
 +---------------------------------------------------------------------------------------------
 *快递服务商对应Code
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 *新增快递服务商：添加元素 
 *		服务商Code => 服务商名称
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		liang		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/

static $expressCode = array(
	'0'=>'全球邮政',
	'100001'=>'DHL',
	'100002'=>'UPS',
	'100003'=>'Fedex',
	'100004'=>'TNT',
	'100007'=>'DPD',
	'100010'=>'DPD(UK)',
	'100011'=>'OneWorld',
	'100005'=>'GLS',
	'100012'=>'顺丰速运',
	'100008'=>'EShipper',
	'100009'=>'Toll',
	'100006'=>'Aramex',
	'190002'=>'飞特物流',
	'190008'=>'云途物流',
	'190011'=>'百千诚物流',
	'190007'=>'俄速递',
	'190009'=>'快达物流',
	'190003'=>'华翰物流',
	'190012'=>'燕文物流',
	'190013'=>'淼信国际',
	'190014'=>'俄易达',
	'190015'=>'俄速通',
	'190017'=>'俄通收',
	'190016'=>'俄顺达',
	'888000001'=>'UBI',
	'888000002'=>'equick',
	'999000001'=>'速卖通线上发货',
	'999000002'=>'4PX',
	'999000003'=>'CNE',
	'999000004'=>'万邑通',
	'999000005'=>'顺丰',
	'999000006'=>'云途',
	'999000007'=>'BRT',
	'999000008'=>'colissimo',
	'999000009'=>'139express',
	'999000010'=>'Pony',
);

/*
 * 17 track 查询不到的物流
 */
static $non17TrackExpressCode = array('888000001','888000002','999000001','999000002','999000003','999000004','999000005','999000006','999000007','999000008','999000009','999000010');

public static function getNon17TrackExpressCode(){
	return self::$non17TrackExpressCode;
}

/**
 +---------------------------------------------------------------------------------------------
 *获得trackingNo结构规则
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param $trackingNo	一个tracking no
 +---------------------------------------------------------------------------------------------
 * '*'数字类型
 * '#'字母类型
 * '!'数字或字母类型
 * ahead(x)		trackingNo前x位的字符类型
 * last(x)		trackingNo后x位的字符类型
 * numOnly		是否是纯数字运单
 * fixedLetterS	起始位置固定字符,type:array
 * fixedLetterE	结束位置固定字符,type:array
 +---------------------------------------------------------------------------------------------
 * @invoking		checkExpressOftrackingNo($trackingNo);
 * @return 			array, example:
 *			array(//type1
 *				'len'=>13,
 *				'ahead2'=>'##',
 *				'ahead3'=>'##*',
 *				'ahead5'=>'##***',
 *				'last2'=>'##',
 *				'last3'=>'*##',
 *				'numOnly'=>false,
 *				'fixedLetterS'=>array('GE','GD'...),
 *				'fixedLetterE'=>array('WW','W'...)
 *			)
 *		OR:
 *			array(//type2
 * 				'len'=>13,
 *				'numOnly'=>true
 *			)	
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		liang		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function structureOftrackingNo($trackingNo){
	$trackingNo = self::slenderTrackNo($trackingNo);
	$trackingNo = preg_replace('/\s/','',$trackingNo);
	$info=array(
			'len' => 0,
			'ahead2' => 0,
			'ahead3' => 0,
			'ahead5' => 0,
			'last2' => 0,
			'last3' => 0,
			'numOnly' =>false,
			'fixedLetterS' => 0,
			'fixedLetterE' => 0,
	);
	
	if ($trackingNo=='')	return $info;
	$len = strlen($trackingNo);
	$s1 = '!';
	$s2 = '!';
	$s3 = '!';
	$s4 = '!';
	$s5 = '!';
	$e1 = '!';
	$e2 = '!';
	$e3 = '!';
	if(preg_match('/^\d+$/',$trackingNo)){//正则结果若是全数字，立即返回结构type1
		$info=array(
			'len' => $len,
			'numOnly' => true
		);
		return $info;
	}else{//正则结果不是全数字，组成结构type2
		//检测第1位字符类型
		if (preg_match('/^\d(\w)+$/',$trackingNo))
			$s1 = '*';
		if (preg_match('/^[A-Za-z](\w)+$/',$trackingNo))
			$s1 = '#';
		//检测第2位字符类型
		if (preg_match('/^(\w)\d(\w)+$/',$trackingNo))
			$s2 = '*';
		if (preg_match('/^(\w)[A-Za-z](\w)+$/',$trackingNo))
			$s2 = '#';
		//检测第3位字符类型
		if (preg_match('/^(\w){2}\d(\w)+$/',$trackingNo))
			$s3 = '*';
		if (preg_match('/^(\w){2}[A-Za-z](\w)+$/',$trackingNo))
			$s3 = '#';
		//检测第4位字符类型
		if (preg_match('/^(\w){3}\d(\w)+$/',$trackingNo))
			$s4 = '*';
		if (preg_match('/^(\w){3}[A-Za-z](\w)+$/',$trackingNo))
			$s4 = '#';
		//检测第5位字符类型
		if (preg_match('/^(\w){4}\d(\w)+$/',$trackingNo))
			$s5 = '*';
		if (preg_match('/^(\w){4}[A-Za-z](\w)+$/',$trackingNo))
			$s5 = '#';	
		//检测倒数第3位字符类型
		if (preg_match('/^(\w)+\d(\w){2}$/',$trackingNo))
			$e3 = '*';
		if (preg_match('/^(\w)+[A-Za-z](\w){2}$/',$trackingNo))
			$e3 = '#';
		//检测倒数第2位字符类型
		if (preg_match('/^(\w)+\d(\w)$/',$trackingNo))
			$e2 = '*';
		if (preg_match('/^(\w)+[A-Za-z](\w)$/',$trackingNo))
			$e2 = '#';
		//检测倒数第1位字符类型
		if (preg_match('/^(\w)+\d$/',$trackingNo))
			$e1 = '*';
		if (preg_match('/^(\w)+[A-Za-z]$/',$trackingNo))
			$e1 = '#';
	}
	//组成起始、结尾几位的字符类型
	$ahead2 = $s1.$s2;
	$ahead3 = $s1.$s2.$s3;
	$ahead5 = $s1.$s2.$s3.$s4.$s5;
	$last3 = $e3.$e2.$e1;
	$last2 = $e2.$e1;
	//将开头1-5长度的字符分别放入数组
	//支持物流商中，俄顺达 的起始固定字符最长，为5位
	//若新增规则或者快递商起始固定字符长度增加，数组要相应增加元素，下同
	$fixedLetterS = array(
		substr($trackingNo,0,1),
		substr($trackingNo,0,2),
		substr($trackingNo,0,3),
		substr($trackingNo,0,4),
		substr($trackingNo,0,5),
	);
	$fixedLetterE = array(
		substr($trackingNo,-1,1),
		substr($trackingNo,-2,2)
	);

	//组成结构规则type2
	$info=array(
		'len' => $len,
		'ahead2' => $ahead2,
		'ahead3' => $ahead3,
		'ahead5' => $ahead5,
		'last2' => $last2,
		'last3' => $last3,
		'numOnly' =>false,
		'fixedLetterS' => $fixedLetterS,
		'fixedLetterE' => $fixedLetterE,
	);
	return $info;//返回 结构type1
}


/**
 +---------------------------------------------------------------------------------------------
 *分析trackingNo可能属于哪种17track支持的快递方式
 +---------------------------------------------------------------------------------------------
 * @access public
 +---------------------------------------------------------------------------------------------
 * @param $trackingNo	trackingNo str
 +---------------------------------------------------------------------------------------------
 * @invoking		checkExpressOftrackingNo($trackingNo);
 * @return 			array
 *                  例如 array( 
 *                  	'0'=>'全球邮政',
 *                 		'100001'=>'DHL',
 *                  	'100002'=>'UPS',
 *                  	... ,
 *                  )
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		liang		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function checkExpressOftrackingNo($trackingNo){
	$trackingNo = self::slenderTrackNo($trackingNo);
	//获取trackingNo结构
	$info = self::structureOftrackingNo($trackingNo);
	//获取17track支持的快递商trackingNo规则
	$expresses = self::$expresses;
	$match = array();
	
	foreach($expresses as $e => $arr){
		foreach($arr as $aPossible){
			//开始对比trackingNo结构和17track快递商trackingNo规则
			if ($info['len']==$aPossible['len']){//运单长度符合此规则
				if ($info['numOnly']){//运单纯数字
					if($aPossible['numOnly']){//运单及此规则均为纯数字，返回符合
						//返回快递商对应Code
						$codes = self::$expressCode;
						$code = $e;//如果没有对应code,code用$e代替
						foreach($codes as $c=>$n){
							if($e == $n){
								$code = $c;
								break;
							}
						}
						$match[$code] = $e;
						if($code=='0' && isset($aPossible['EMS_designatedCountrys'])){
							$match['EMS_designatedCountrys'] = $aPossible['EMS_designatedCountrys'];
						}
					}
				}else{//运单不是纯数字
					if($aPossible['numOnly'])//此规则是纯数字，continue
						continue;
					else{//运单和此规则都不是纯数字
						$m = true;//匹配标识
						if(isset($aPossible['ahead2']) and $info['ahead2']!==$aPossible['ahead2']) $m = false;
						if(isset($aPossible['ahead3']) and $info['ahead3']!==$aPossible['ahead3']) $m = false;
						if(isset($aPossible['ahead5']) and $info['ahead5']!==$aPossible['ahead5']) $m = false;
						if(isset($aPossible['last2']) and $info['last2']!==$aPossible['last2']) $m = false;
						if(isset($aPossible['last3']) and $info['last3']!==$aPossible['last3']) $m = false;
						
						//运单结构和规则结构 固定字符数组求交集
						if (isset($aPossible['fixedLetterS'])){
							$intersect_S = array_intersect($info['fixedLetterS'],$aPossible['fixedLetterS']);
							if(count($intersect_S)<=0) $m = false;
						}
						if (isset($aPossible['fixedLetterE'])){
							$intersect_E = array_intersect($info['fixedLetterE'],$aPossible['fixedLetterE']);
							if(count($intersect_E)<=0) $m = false;
						}
						
						if($m){//此规则的所有预设条件都被满足，返回符合
							//返回快递商对应Code
							$codes = self::$expressCode;
							$code = $e;//如果没有对应code,code用$e代替
							foreach($codes as $c=>$n){
								if($e == $n){
									$code = $c;
									break;
								}
							}
							$match[$code] = $e;
							if($code=='0' && isset($aPossible['EMS_designatedCountrys'])){
								$match['EMS_designatedCountrys'] = $aPossible['EMS_designatedCountrys'];
							}
						}							
					}
				}
			}
			else continue;//运单长度不符合当前规则长度
		}
	}

	//吧全球邮政的可能的国家放到 cache中，由另外一个function 提供获取的接口
	global $CACHE;	
	if (isset($match['EMS_designatedCountrys']) ){
		$aTracking = new Tracking();
		//全球邮政的国家识别，因为太多没有一一使用显式规则，不上一个规则，如果号码的 前二位或者 后二位是国家标准code的话，加上这个国家吧
		if (strlen($trackingNo) >= 2){
			$first2 = substr($trackingNo,0,2);
			$last2 = substr($trackingNo, strlen($trackingNo) -2,2);
			$first2_17TrackNationCode = Tracking::get17TrackNationCodeByStandardNationCode($first2);
			$last2_17TrackNationCode = Tracking::get17TrackNationCodeByStandardNationCode($last2);
			if ( !empty($first2_17TrackNationCode) ){
				$match['EMS_designatedCountrys'][$first2_17TrackNationCode] = Tracking::get17TrackNationEnglish($first2_17TrackNationCode) ;
			}
			if ( !empty($last2_17TrackNationCode) ){
				$match['EMS_designatedCountrys'][$last2_17TrackNationCode] = Tracking::get17TrackNationEnglish($last2_17TrackNationCode);
			}
			//$match['EMS_designatedCountrys']  get17TrackNationCodeByStandardNationCode
		}
		$CACHE['track_no']['global_post'][$trackingNo]['EMS_designatedCountrys'] = $match['EMS_designatedCountrys'];
		unset($match['EMS_designatedCountrys']);
	}

	return $match;
}

/**
 +---------------------------------------------------------------------------------------------
 * 返回所有可能性的快递code
 +---------------------------------------------------------------------------------------------
 * @access public
 +---------------------------------------------------------------------------------------------
 * @param  
 +---------------------------------------------------------------------------------------------
 * @return 			array('0'=>'全球邮政',
					'100001'=>'DHL', ... )
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		yzq		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getAllExpressCode(){
	return  self::$expressCode;	
}

public static function testShipBySpecifiedCarrier($shipBy=''){
	global $CACHE;
	$carriers_array = [];
	if (empty($shipBy))
		return $carriers_array;
	
	if (empty($CACHE['getAllExpressCode']))		
		$CACHE['getAllExpressCode'] = CarrierTypeOfTrackNumber::getAllExpressCode( );
	
	$allShipBy = $CACHE['getAllExpressCode']; 
	//array('0'=>'全球邮政',	'100001'=>'DHL', ... )
	foreach ($allShipBy as $shipCode=>$shipName){
		if ($shipCode<>'0') //如果不是中国邮政，普通名字匹配就可以
			$matched = (stripos(strtolower($shipName),strtolower($shipBy)) !== false or
					stripos(strtolower($shipBy),strtolower($shipName)) !== false  );
		else{ // 中国邮政，关键字比较多，这里展开一下
			$matched = false;
			$shipNames = array('邮政','post','中邮','USPS','E邮','EUB');
			foreach ($shipNames as $name1){
				$matched = (stripos(strtolower($name1),strtolower($shipBy)) !== false or stripos(($shipBy),strtolower($name1)) !== false);
				if ($matched) break;
			}//end of each possible for carrier code 1
		}
	
		if ($matched)
			$carriers_array[$shipCode] = $shipName;
	}//end of each shipBy method
	return $carriers_array;
}

/**
 +---------------------------------------------------------------------------------------------
 * 返回所有全球邮政的可能国家代码
 +---------------------------------------------------------------------------------------------
 * @access public
 +---------------------------------------------------------------------------------------------
 * @param
 +---------------------------------------------------------------------------------------------
 * @return 			array(
			*                  		'2105'=>'United States(美国)',
			*							'704'=>'Germany(德国)',
			*                  	)
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		yzq		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getGlobalPostNationCode($trackingNo){
	$trackingNo = self::slenderTrackNo($trackingNo);
	//吧全球邮政的可能的国家放到 cache中
	global $CACHE;
	if (!isset($CACHE['track_no']['global_post'][$trackingNo]['EMS_designatedCountrys'])){
		self::checkExpressOftrackingNo($trackingNo);
	}
 
	return isset($CACHE['track_no']['global_post'][$trackingNo]['EMS_designatedCountrys']) ? $CACHE['track_no']['global_post'][$trackingNo]['EMS_designatedCountrys'] : array();
}

/**
 +---------------------------------------------------------------------------------------------
 * 返回字符串的代码格式
 +---------------------------------------------------------------------------------------------
 * @access public
 +---------------------------------------------------------------------------------------------
 * @param  str		    		任意字符串，例如 RG5498751CN
 * @param  keepLetter			true/false, default aflse
 *                              when it is false: 字符串中的英文字母不会被 #代替，
 *                              when it is true：字符串中的英文字母被 #代替，
 +---------------------------------------------------------------------------------------------
 * @return 			        代码格式，* 代表数字，#代表字符，例如 ##*******##
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		yzq		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getCodeFormatOfString($str,$keepLetter=false){
	$str = self::slenderTrackNo($str);
	
	$format = '';
	for($i = 0; $i<strlen($str) ; $i++){
		$c = substr($str,$i,1);
		$format .= is_numeric($c) ?"*":  ($keepLetter ? $c : "#");
	}

	return $format;
}


/**
 +---------------------------------------------------------------------------------------------
 * 统一格式化有效的字符，提出-，。 之类的无效track no 符号
 +---------------------------------------------------------------------------------------------
 * @access public
 +---------------------------------------------------------------------------------------------
 * @param  $track_no	   		任意字符串，例如 RG5-498-751CN
 +---------------------------------------------------------------------------------------------
 * @return 			                        有效的track no，提出特殊符号后的，例如 RG5498751CN
 +---------------------------------------------------------------------------------------------
 * log			name		date			note
 * @author		yzq		2015/3/4		初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function slenderTrackNo($track_no){
	$str = trim($track_no);
	$slender_code = '';
	for($i = 0; $i<strlen($str); $i++){
		$c = substr($str,$i,1);
		if(ereg('[a-zA-Z]', $c) or ereg('[0-9]', $c)){
			$slender_code .= $c;
		}
	}
	return strtoupper( $slender_code );
}

}//end of class 17Track Parsing 
