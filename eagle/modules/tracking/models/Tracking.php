<?php

namespace eagle\modules\tracking\models;

use Yii;
use yii\data\Pagination;

/**
 * This is the model class for table "lt_tracking".
 *
 * @property integer $id
 * @property string $order_id
 * @property string $track_no
 * @property string $status
 * @property string $state
 * @property string $is_active
 * @property string $batch_no
 * @property string $create_time
 * @property string $update_time
 * @property string $from_nation
 * @property string $to_nation
 * @property string $notified_seller
 * @property string $notified_buyer
 * @property string $ship_by
 * @property string $delivery_fee
 * @property string $ship_out_date
 * @property string $all_event
 * @property string $addi_info
 */
class Tracking extends \eagle\models\tracking\Tracking
{	static public $IS_USER_REQUIRE_UPDATE = true;
	/*Below is CACHE for this Model.
	 * 当需要add 很多record 的时候，频繁使用 model new，save 会使得性能效率降低
	 * 建议把需要add 的所有tracking data，array 形式放入这个CACHE，
	 * 然后调用group insert 函数一次过 insert。
	 * 这个 CACHE 和进程有关，不同进程不会干扰
	 * */
	static public $Insert_Data_Buffer = array();
	static public $Insert_Data_Buffer_count = 0;
	static public $A_New_Record = null;
	static public $Update_Data_Buffer = array();
	static private  $status_map = array(
  				"checking"=>"查询等候中",
  				"shipping"=>"运输途中",
  				"no_info"=>"查询不到",
				"suspend"=>"延迟查询",
  				"ship_over_time"=>"运输过久",
				"delivery_failed"=>"投递失败",
  				"arrived_pending_fetch"=>"到达待取",
  				"received"=>"成功签收",
				"platform_confirmed"=>"买家已确认",
  				"rejected"=>"异常退回",
				"untrackable"=>"无法交运",
			    "unregistered"=>"无挂号",
				"expired"=>"过期物流号", //4个月以前的就是过期了
				"ignored"=>"忽略(不再查询)",
				"quota_insufficient"=>"配额不足",
			
  	);
	static private  $status_enMap = array(
			"checking"=>"Info Checking",
			"shipping"=>"In Transit",
			"no_info"=>"Not found",
			"suspend"=>"Checking Suspend",
			"delivery_failed"=>"Delivery Failure",
			"ship_over_time"=>"Transit Over time",
			"arrived_pending_fetch"=>"Pick Up",
			"received"=>"Delivered",
			"platform_confirmed"=>"Consignee Confirmed",
			"rejected"=>"Consignee Rejected",
			"untrackable"=>"Undeliverable",
			"unregistered"=>"No Registered",
			"expired"=>"Expired", //4个月以前的就是过期了
			"ignored"=>"Ignored",
			"quota_insufficient"=>"Quota Insufficent",
	);

	static private $state_map = array(
			"initial"=>"初始",
			"normal"=>"正常",
			"exception"=>"异常",
			"unshipped"=>"无法交运",
			"complete"=>"已完成",
			"deleted"=>"已删除"
	);
	//可以前端选择操作为忽略状态的tracking状态：
	static private	$canIgnoreStatusEn = ['checking','no_info','suspend','untrackable'];
	static private	$canIgnoreStatusZh = ['查询中','查询等候中','查询不到','延迟查询','无法交运'];
	public static function getCanIgnoreStatus($lan='EN'){
		if (strtoupper($lan)=='ZH') {
			return self::$canIgnoreStatusZh;
		}
		else 
			return self::$canIgnoreStatusEn;
	}
	
	static private $Track17_post_nation_url_map_json = '{"1021":{"a":"1021","b":"http://afghanpost.gov.af/"},"1022":{"a":"1022","b":"http://afghanpost.gov.af/"},"1023":{"a":"1023","b":"http://afghanpost.gov.af/"},"1031":{"a":"1031","b":"http://www.postashqiptare.al/"},"1032":{"a":"1032","b":"http://www.postashqiptare.al/"},"1033":{"a":"1033","b":"http://www.postashqiptare.al/"},"1041":{"a":"1041","b":"http://www.poste.dz/"},"1042":{"a":"1042","b":"http://www.poste.dz/"},"1043":{"a":"1043","b":"http://www.poste.dz/"},"1051":{"a":"1051","b":"http://www.laposte.fr/"},"1052":{"a":"1052","b":""},"1053":{"a":"1053","b":""},"1061":{"a":"1061","b":"http://www.correiosdeangola.co.ao/"},"1062":{"a":"1062","b":"http://www.correiosdeangola.co.ao/"},"1063":{"a":"1063","b":"http://www.correiosdeangola.co.ao/"},"1081":{"a":"1081","b":""},"1082":{"a":"1082","b":""},"1083":{"a":"1083","b":""},"1101":{"a":"1101","b":""},"1102":{"a":"1102","b":""},"1103":{"a":"1103","b":""},"1121":{"a":"1121","b":"http://www.correoargentino.com.ar/"},"1122":{"a":"1122","b":"http://www.correoargentino.com.ar/"},"1123":{"a":"1123","b":"http://www.correoargentino.com.ar/"},"1131":{"a":"1131","b":"http://www.haypost.am/"},"1132":{"a":"1132","b":"http://www.haypost.am/"},"1133":{"a":"1133","b":"http://www.haypost.am/"},"1151":{"a":"1151","b":"http://auspost.com.au/"},"1152":{"a":"1152","b":"http://auspost.com.au/"},"1153":{"a":"1153","b":"http://auspost.com.au/"},"1161":{"a":"1161","b":"http://www.post.at/"},"1162":{"a":"1162","b":"http://www.post.at/"},"1163":{"a":"1163","b":"http://www.post.at/"},"1171":{"a":"1171","b":"http://www.azems.az/"},"1172":{"a":"1172","b":"http://www.azems.az/"},"1173":{"a":"1173","b":"http://www.azems.az/"},"2011":{"a":"2011","b":"http://www.bahamas.gov.bs/"},"2012":{"a":"2012","b":"http://www.bahamas.gov.bs/"},"2013":{"a":"2013","b":"http://www.bahamas.gov.bs/"},"2021":{"a":"2021","b":"http://www.bahrain.bh/"},"2022":{"a":"2022","b":"http://www.bahrain.bh/"},"2023":{"a":"2023","b":"http://www.bahrain.bh/"},"2031":{"a":"2031","b":"http://www.bangladeshpost.gov.bd/"},"2032":{"a":"2032","b":"http://www.bangladeshpost.gov.bd/"},"2033":{"a":"2033","b":"http://www.bangladeshpost.gov.bd/"},"2041":{"a":"2041","b":"http://www.bps.gov.bb/"},"2042":{"a":"2042","b":"http://www.bps.gov.bb/"},"2043":{"a":"2043","b":"http://www.bps.gov.bb/"},"2051":{"a":"2051","b":"http://belpost.by/"},"2052":{"a":"2052","b":"http://belpost.by/"},"2053":{"a":"2053","b":"http://belpost.by/"},"2061":{"a":"2061","b":"http://www.bpost.be/"},"2062":{"a":"2062","b":"http://www.bpost.be/"},"2063":{"a":"2063","b":"http://www.bpost.be/"},"2071":{"a":"2071","b":"http://www.belizepostalservice.gov.bz"},"2072":{"a":"2072","b":"http://www.belizepostalservice.gov.bz"},"2073":{"a":"2073","b":"http://www.belizepostalservice.gov.bz"},"2081":{"a":"2081","b":"http://www.laposte.bj/"},"2082":{"a":"2082","b":"http://www.laposte.bj/"},"2083":{"a":"2083","b":"http://www.laposte.bj/"},"2101":{"a":"2101","b":"http://www.bhutanpost.com.bt/"},"2102":{"a":"2102","b":"http://www.bhutanpost.com.bt/"},"2103":{"a":"2103","b":"http://www.bhutanpost.com.bt/"},"2111":{"a":"2111","b":"http://www.correosbolivia.com/"},"2112":{"a":"2112","b":"http://www.correosbolivia.com/"},"2113":{"a":"2113","b":"http://www.correosbolivia.com/"},"2121":{"a":"2121","b":"http://www.posta.ba/"},"2122":{"a":"2122","b":"http://www.posta.ba/"},"2123":{"a":"2123","b":"http://www.posta.ba/"},"2131":{"a":"2131","b":"http://www.botspost.co.bw/"},"2132":{"a":"2132","b":"http://www.botspost.co.bw/"},"2133":{"a":"2133","b":"http://www.botspost.co.bw/"},"2151":{"a":"2151","b":"http://www.correios.com.br/"},"2152":{"a":"2152","b":"http://www.correios.com.br/"},"2153":{"a":"2153","b":"http://www.correios.com.br/"},"2161":{"a":"2161","b":"http://www.post.gov.bn/"},"2162":{"a":"2162","b":"http://www.post.gov.bn/"},"2163":{"a":"2163","b":"http://www.post.gov.bn/"},"2171":{"a":"2171","b":"http://www.bgpost.bg/"},"2172":{"a":"2172","b":"http://www.bgpost.bg/"},"2173":{"a":"2173","b":"http://www.bgpost.bg/"},"2181":{"a":"2181","b":"http://www.sonapost.bf/"},"2182":{"a":"2182","b":"http://www.sonapost.bf/"},"2183":{"a":"2183","b":"http://www.sonapost.bf/"},"2191":{"a":"2191","b":"http://www.poste.bi/"},"2192":{"a":"2192","b":"http://www.poste.bi/"},"2193":{"a":"2193","b":"http://www.poste.bi/"},"3011":{"a":"3011","b":"http://intmail.183.com.cn/"},"3012":{"a":"3012","b":"http://intmail.183.com.cn/"},"3013":{"a":"3013","b":"http://www.11183.com.cn/"},"3021":{"a":"3021","b":"http://www.cambodiapost.com.kh/"},"3022":{"a":"3022","b":"http://www.cambodiapost.com.kh/"},"3023":{"a":"3023","b":"http://www.cambodiapost.com.kh/"},"3031":{"a":"3031","b":"http://www.campost.cm/"},"3032":{"a":"3032","b":"http://www.campost.cm/"},"3033":{"a":"3033","b":"http://www.campost.cm/"},"3041":{"a":"3041","b":"http://www.canadapost.ca/"},"3042":{"a":"3042","b":"http://www.canadapost.ca/"},"3043":{"a":"3043","b":"http://www.canadapost.ca/"},"3061":{"a":"3061","b":"http://www.correios.cv/"},"3062":{"a":"3062","b":"http://www.correios.cv/"},"3063":{"a":"3063","b":"http://www.correios.cv/"},"3081":{"a":"3081","b":""},"3082":{"a":"3082","b":""},"3083":{"a":"3083","b":""},"3101":{"a":"3101","b":"http://www.correos.cl/"},"3102":{"a":"3102","b":"http://www.correos.cl/"},"3103":{"a":"3103","b":"http://www.correos.cl/"},"3121":{"a":"3121","b":"http://www.laposte.ci/"},"3122":{"a":"3122","b":"http://www.laposte.ci/"},"3123":{"a":"3123","b":"http://www.laposte.ci/"},"3131":{"a":"3131","b":"http://www.4-72.com.co/"},"3132":{"a":"3132","b":"http://www.4-72.com.co/"},"3133":{"a":"3133","b":"http://www.4-72.com.co/"},"3141":{"a":"3141","b":"http://www.lapostecomores.com/"},"3142":{"a":"3142","b":"http://www.lapostecomores.com/"},"3143":{"a":"3143","b":"http://www.lapostecomores.com/"},"3151":{"a":"3151","b":""},"3152":{"a":"3152","b":""},"3153":{"a":"3153","b":""},"3161":{"a":"3161","b":""},"3162":{"a":"3162","b":""},"3163":{"a":"3163","b":""},"3171":{"a":"3171","b":""},"3172":{"a":"3172","b":""},"3173":{"a":"3173","b":""},"3181":{"a":"3181","b":"http://www.correos.go.cr/"},"3182":{"a":"3182","b":"http://www.correos.go.cr/"},"3183":{"a":"3183","b":"http://www.correos.go.cr/"},"3191":{"a":"3191","b":"http://www.posta.hr/"},"3192":{"a":"3192","b":"http://www.posta.hr/"},"3193":{"a":"3193","b":"http://www.posta.hr/"},"3201":{"a":"3201","b":""},"3202":{"a":"3202","b":""},"3203":{"a":"3203","b":""},"3211":{"a":"3211","b":"http://www.mcw.gov.cy/"},"3212":{"a":"3212","b":"http://www.mcw.gov.cy/"},"3213":{"a":"3213","b":"http://www.mcw.gov.cy/"},"3221":{"a":"3221","b":"http://www.ceskaposta.cz/"},"3222":{"a":"3222","b":"http://www.ceskaposta.cz/"},"3223":{"a":"3223","b":"http://www.ceskaposta.cz/"},"3231":{"a":"3231","b":""},"3232":{"a":"3232","b":""},"3233":{"a":"3233","b":""},"4011":{"a":"4011","b":"http://www.postdanmark.dk/"},"4012":{"a":"4012","b":"http://www.postdanmark.dk/"},"4013":{"a":"4013","b":"http://www.postdanmark.dk/"},"4021":{"a":"4021","b":"http://www.laposte.dj/"},"4022":{"a":"4022","b":"http://www.laposte.dj/"},"4023":{"a":"4023","b":"http://www.laposte.dj/"},"4031":{"a":"4031","b":"http://publicworks.gov.dm/"},"4032":{"a":"4032","b":"http://publicworks.gov.dm/"},"4033":{"a":"4033","b":"http://publicworks.gov.dm/"},"4041":{"a":"4041","b":"http://www.inposdom.gob.do/"},"4042":{"a":"4042","b":"http://www.inposdom.gob.do/"},"4043":{"a":"4043","b":"http://www.inposdom.gob.do/"},"5011":{"a":"5011","b":"http://www.correosdelecuador.gob.ec/"},"5012":{"a":"5012","b":"http://www.correosdelecuador.gob.ec/"},"5013":{"a":"5013","b":"http://www.correosdelecuador.gob.ec/"},"5021":{"a":"5021","b":"http://www.egyptpost.org/"},"5022":{"a":"5022","b":"http://www.egyptpost.org/"},"5023":{"a":"5023","b":"http://www.egyptpost.org/"},"5031":{"a":"5031","b":"http://www.epg.gov.ae/"},"5032":{"a":"5032","b":"http://www.epg.gov.ae/"},"5033":{"a":"5033","b":"http://www.epg.gov.ae/"},"5041":{"a":"5041","b":"https://www.omniva.ee/"},"5042":{"a":"5042","b":"https://www.omniva.ee/"},"5043":{"a":"5043","b":"https://www.omniva.ee/"},"5051":{"a":"5051","b":"http://www.ethiopostal.com/"},"5052":{"a":"5052","b":"http://www.ethiopostal.com/"},"5053":{"a":"5053","b":"http://www.ethiopostal.com/"},"5061":{"a":"5061","b":"http://www.eriposta.com/"},"5062":{"a":"5062","b":"http://www.eriposta.com/"},"5063":{"a":"5063","b":"http://www.eriposta.com/"},"5071":{"a":"5071","b":""},"5072":{"a":"5072","b":""},"5073":{"a":"5073","b":""},"5081":{"a":"5081","b":""},"5082":{"a":"5082","b":""},"5083":{"a":"5083","b":""},"6031":{"a":"6031","b":"http://www.postfiji.com.fj/"},"6032":{"a":"6032","b":"http://www.postfiji.com.fj/"},"6033":{"a":"6033","b":"http://www.postfiji.com.fj/"},"6041":{"a":"6041","b":"http://www.posti.fi/"},"6042":{"a":"6042","b":"http://www.posti.fi/"},"6043":{"a":"6043","b":"http://www.posti.fi/"},"6051":{"a":"6051","b":"http://www.laposte.fr/"},"6052":{"a":"6052","b":"http://www.colissimo.fr/"},"6053":{"a":"6053","b":"http://www.chronopost.fr/"},"7011":{"a":"7011","b":"http://www.lapostedugabon.org/"},"7012":{"a":"7012","b":"http://www.lapostedugabon.org/"},"7013":{"a":"7013","b":"http://www.lapostedugabon.org/"},"7021":{"a":"7021","b":"http://www.gampost.gm/"},"7022":{"a":"7022","b":"http://www.gampost.gm/"},"7023":{"a":"7023","b":"http://www.gampost.gm/"},"7031":{"a":"7031","b":"http://www.gpost.ge/"},"7032":{"a":"7032","b":"http://www.gpost.ge/"},"7033":{"a":"7033","b":"http://www.gpost.ge/"},"7041":{"a":"7041","b":"http://www.deutschepost.de/"},"7042":{"a":"7042","b":"http://www.deutschepost.de/"},"7043":{"a":"7043","b":"http://www.deutschepost.de/"},"7051":{"a":"7051","b":"http://www.ghanapostgh.com/"},"7052":{"a":"7052","b":"http://www.ghanapostgh.com/"},"7053":{"a":"7053","b":"http://www.ghanapostgh.com/"},"7071":{"a":"7071","b":"http://www.elta.gr/"},"7072":{"a":"7072","b":"http://www.elta.gr/"},"7073":{"a":"7073","b":"http://www.elta.gr/"},"7091":{"a":"7091","b":"http://www.grenadapostal.com/"},"7092":{"a":"7092","b":"http://www.grenadapostal.com/"},"7093":{"a":"7093","b":"http://www.grenadapostal.com/"},"7121":{"a":"7121","b":"http://www.elcorreo.com.gt/"},"7122":{"a":"7122","b":"http://www.elcorreo.com.gt/"},"7123":{"a":"7123","b":"http://www.elcorreo.com.gt/"},"7131":{"a":"7131","b":"http://www.lapostegn.com/"},"7132":{"a":"7132","b":"http://www.lapostegn.com/"},"7133":{"a":"7133","b":"http://www.lapostegn.com/"},"7141":{"a":"7141","b":"http://guypost.gy/"},"7142":{"a":"7142","b":"http://guypost.gy/"},"7143":{"a":"7143","b":"http://guypost.gy/"},"7161":{"a":"7161","b":""},"7162":{"a":"7162","b":""},"7163":{"a":"7163","b":""},"8011":{"a":"8011","b":"http://www.hongkongpost.hk/"},"8012":{"a":"8012","b":"http://www.hongkongpost.hk/"},"8013":{"a":"8013","b":"http://www.hongkongpost.hk/"},"8021":{"a":"8021","b":"http://postehaiti.gouv.ht/"},"8022":{"a":"8022","b":"http://postehaiti.gouv.ht/"},"8023":{"a":"8023","b":"http://postehaiti.gouv.ht/"},"8041":{"a":"8041","b":"http://www.honducor.gob.hn/"},"8042":{"a":"8042","b":"http://www.honducor.gob.hn/"},"8043":{"a":"8043","b":"http://www.honducor.gob.hn/"},"8051":{"a":"8051","b":"http://posta.hu/"},"8052":{"a":"8052","b":"http://posta.hu/"},"8053":{"a":"8053","b":"http://posta.hu/"},"9011":{"a":"9011","b":"http://www.postur.is/"},"9012":{"a":"9012","b":"http://www.postur.is/"},"9013":{"a":"9013","b":"http://www.postur.is/"},"9021":{"a":"9021","b":"http://www.indiapost.gov.in/"},"9022":{"a":"9022","b":"http://www.indiapost.gov.in/"},"9023":{"a":"9023","b":"http://www.indiapost.gov.in/"},"9031":{"a":"9031","b":"http://www.posindonesia.co.id/"},"9032":{"a":"9032","b":"http://www.posindonesia.co.id/"},"9033":{"a":"9033","b":"http://www.posindonesia.co.id/"},"9041":{"a":"9041","b":"http://post.ir/"},"9042":{"a":"9042","b":"http://post.ir/"},"9043":{"a":"9043","b":"http://post.ir/"},"9051":{"a":"9051","b":"http://www.anpost.ie/"},"9052":{"a":"9052","b":"http://www.anpost.ie/"},"9053":{"a":"9053","b":"http://www.anpost.ie/"},"9061":{"a":"9061","b":"http://www.israelpost.co.il/"},"9062":{"a":"9062","b":"http://www.israelpost.co.il/"},"9063":{"a":"9063","b":"http://www.israelpost.co.il/"},"9071":{"a":"9071","b":"http://www.poste.it/"},"9072":{"a":"9072","b":"http://www.poste.it/"},"9073":{"a":"9073","b":"http://www.poste.it/"},"9081":{"a":"9081","b":"http://www.iraqipost.net/"},"9082":{"a":"9082","b":"http://www.iraqipost.net/"},"9083":{"a":"9083","b":"http://www.iraqipost.net/"},"10011":{"a":"10011","b":"http://www.jamaicapost.gov.jm/"},"10012":{"a":"10012","b":"http://www.jamaicapost.gov.jm/"},"10013":{"a":"10013","b":"http://www.jamaicapost.gov.jm/"},"10021":{"a":"10021","b":"http://www.post.japanpost.jp/"},"10022":{"a":"10022","b":"http://www.post.japanpost.jp/"},"10023":{"a":"10023","b":"http://www.post.japanpost.jp/"},"10031":{"a":"10031","b":"http://www.jordanpost.com.jo/"},"10032":{"a":"10032","b":"http://www.jordanpost.com.jo/"},"10033":{"a":"10033","b":"http://www.jordanpost.com.jo/"},"11011":{"a":"11011","b":"http://www.kazpost.kz/"},"11012":{"a":"11012","b":"http://www.kazpost.kz/"},"11013":{"a":"11013","b":"http://www.kazpost.kz/"},"11021":{"a":"11021","b":"http://www.posta.co.ke/"},"11022":{"a":"11022","b":"http://www.posta.co.ke/"},"11023":{"a":"11023","b":"http://www.posta.co.ke/"},"11031":{"a":"11031","b":"http://www.royalmail.com/"},"11032":{"a":"11032","b":"http://www.parcelforce.com/"},"11033":{"a":"11033","b":"http://www.parcelforce.com/"},"11041":{"a":"11041","b":""},"11042":{"a":"11042","b":""},"11043":{"a":"11043","b":""},"11051":{"a":"11051","b":"http://www.epost.go.kr/"},"11052":{"a":"11052","b":"http://www.epost.go.kr/"},"11053":{"a":"11053","b":"http://www.epost.go.kr/"},"11061":{"a":"11061","b":""},"11062":{"a":"11062","b":""},"11063":{"a":"11063","b":""},"11071":{"a":"11071","b":"http://www.postaekosoves.net/"},"11072":{"a":"11072","b":"http://www.postaekosoves.net/"},"11073":{"a":"11073","b":"http://www.postaekosoves.net/"},"11081":{"a":"11081","b":"http://moc.kw/"},"11082":{"a":"11082","b":"http://moc.kw/"},"11083":{"a":"11083","b":"http://moc.kw/"},"11091":{"a":"11091","b":"http://kyrgyzpost.kg/"},"11092":{"a":"11092","b":"http://kyrgyzpost.kg/"},"11093":{"a":"11093","b":"http://kyrgyzpost.kg/"},"12011":{"a":"12011","b":""},"12012":{"a":"12012","b":""},"12013":{"a":"12013","b":""},"12021":{"a":"12021","b":"http://www.pasts.lv/"},"12022":{"a":"12022","b":"http://www.pasts.lv/"},"12023":{"a":"12023","b":"http://www.pasts.lv/"},"12031":{"a":"12031","b":"http://www.libanpost.com/"},"12032":{"a":"12032","b":"http://www.libanpost.com/"},"12033":{"a":"12033","b":"http://www.libanpost.com/"},"12041":{"a":"12041","b":"http://lesothopost.org.ls/"},"12042":{"a":"12042","b":"http://lesothopost.org.ls/"},"12043":{"a":"12043","b":"http://lesothopost.org.ls/"},"12051":{"a":"12051","b":"http://www.mopt.gov.lr/"},"12052":{"a":"12052","b":"http://www.mopt.gov.lr/"},"12053":{"a":"12053","b":"http://www.mopt.gov.lr/"},"12061":{"a":"12061","b":"http://libyapost.ly/"},"12062":{"a":"12062","b":"http://libyapost.ly/"},"12063":{"a":"12063","b":"http://libyapost.ly/"},"12071":{"a":"12071","b":"http://www.post.li/"},"12072":{"a":"12072","b":"http://www.post.li/"},"12073":{"a":"12073","b":"http://www.post.li/"},"12081":{"a":"12081","b":"http://www.post.lt/"},"12082":{"a":"12082","b":"http://www.post.lt/"},"12083":{"a":"12083","b":"http://www.post.lt/"},"12091":{"a":"12091","b":"http://www.stluciapostal.com/"},"12092":{"a":"12092","b":"http://www.stluciapostal.com/"},"12093":{"a":"12093","b":"http://www.stluciapostal.com/"},"12101":{"a":"12101","b":"http://www.post.lu/"},"12102":{"a":"12102","b":"http://www.post.lu/"},"12103":{"a":"12103","b":"http://www.post.lu/"},"13011":{"a":"13011","b":"http://www.macaupost.gov.mo/"},"13012":{"a":"13012","b":"http://www.macaupost.gov.mo/"},"13013":{"a":"13013","b":"http://www.macaupost.gov.mo/"},"13021":{"a":"13021","b":"http://www.posta.mk/"},"13022":{"a":"13022","b":"http://www.posta.mk/"},"13023":{"a":"13023","b":"http://www.posta.mk/"},"13031":{"a":"13031","b":""},"13032":{"a":"13032","b":""},"13033":{"a":"13033","b":""},"13041":{"a":"13041","b":"http://www.malawiposts.com/"},"13042":{"a":"13042","b":"http://www.malawiposts.com/"},"13043":{"a":"13043","b":"http://www.malawiposts.com/"},"13051":{"a":"13051","b":"http://www.pos.com.my/"},"13052":{"a":"13052","b":"http://www.pos.com.my/"},"13053":{"a":"13053","b":"http://www.pos.com.my/"},"13061":{"a":"13061","b":"http://www.maldivespost.com/"},"13062":{"a":"13062","b":"http://www.maldivespost.com/"},"13063":{"a":"13063","b":"http://www.maldivespost.com/"},"13071":{"a":"13071","b":"http://www.laposte.ml/"},"13072":{"a":"13072","b":"http://www.laposte.ml/"},"13073":{"a":"13073","b":"http://www.laposte.ml/"},"13081":{"a":"13081","b":"http://www.maltapost.com/"},"13082":{"a":"13082","b":"http://www.maltapost.com/"},"13083":{"a":"13083","b":"http://www.maltapost.com/"},"13101":{"a":"13101","b":""},"13102":{"a":"13102","b":""},"13103":{"a":"13103","b":""},"13121":{"a":"13121","b":"http://www.mauripost.mr/"},"13122":{"a":"13122","b":"http://www.mauripost.mr/"},"13123":{"a":"13123","b":"http://www.mauripost.mr/"},"13131":{"a":"13131","b":"http://www.mauritiuspost.mu/"},"13132":{"a":"13132","b":"http://www.mauritiuspost.mu/"},"13133":{"a":"13133","b":"http://www.mauritiuspost.mu/"},"13141":{"a":"13141","b":"http://www.correosdemexico.gob.mx/"},"13142":{"a":"13142","b":"http://www.correosdemexico.gob.mx/"},"13143":{"a":"13143","b":"http://www.correosdemexico.gob.mx/"},"13151":{"a":"13151","b":""},"13152":{"a":"13152","b":""},"13153":{"a":"13153","b":""},"13161":{"a":"13161","b":"http://www.posta.md/"},"13162":{"a":"13162","b":"http://www.posta.md/"},"13163":{"a":"13163","b":"http://www.posta.md/"},"13171":{"a":"13171","b":"http://www.lapostemonaco.mc/"},"13172":{"a":"13172","b":"http://www.lapostemonaco.mc/"},"13173":{"a":"13173","b":"http://www.lapostemonaco.mc/"},"13181":{"a":"13181","b":"http://www.mongolpost.mn/"},"13182":{"a":"13182","b":"http://www.mongolpost.mn/"},"13183":{"a":"13183","b":"http://www.mongolpost.mn/"},"13191":{"a":"13191","b":"http://www.postacg.me/"},"13192":{"a":"13192","b":"http://www.postacg.me/"},"13193":{"a":"13193","b":"http://www.postacg.me/"},"13211":{"a":"13211","b":"http://www.poste.ma/"},"13212":{"a":"13212","b":"http://www.poste.ma/"},"13213":{"a":"13213","b":"http://www.poste.ma/"},"13221":{"a":"13221","b":"http://www.correios.co.mz/"},"13222":{"a":"13222","b":"http://www.correios.co.mz/"},"13223":{"a":"13223","b":"http://www.correios.co.mz/"},"13231":{"a":"13231","b":"http://www.myanmaposts.net.mm/"},"13232":{"a":"13232","b":"http://www.myanmaposts.net.mm/"},"13233":{"a":"13233","b":"http://www.myanmaposts.net.mm/"},"14011":{"a":"14011","b":"http://www.nampost.com.na/"},"14012":{"a":"14012","b":"http://www.nampost.com.na/"},"14013":{"a":"14013","b":"http://www.nampost.com.na/"},"14021":{"a":"14021","b":""},"14022":{"a":"14022","b":""},"14023":{"a":"14023","b":""},"14031":{"a":"14031","b":"http://www.gpo.gov.np/"},"14032":{"a":"14032","b":"http://www.gpo.gov.np/"},"14033":{"a":"14033","b":"http://www.gpo.gov.np/"},"14041":{"a":"14041","b":"http://postnlparcels.com/"},"14042":{"a":"14042","b":"http://parcels-uk.tntpost.com/"},"14043":{"a":"14043","b":"http://parcels-uk.tntpost.com/"},"14061":{"a":"14061","b":"http://www.nzpost.co.nz/"},"14062":{"a":"14062","b":"http://www.nzpost.co.nz/"},"14063":{"a":"14063","b":"http://www.nzpost.co.nz/"},"14071":{"a":"14071","b":"http://www.correos.gob.ni/"},"14072":{"a":"14072","b":"http://www.correos.gob.ni/"},"14073":{"a":"14073","b":"http://www.correos.gob.ni/"},"14081":{"a":"14081","b":"http://www.posten.no/"},"14082":{"a":"14082","b":"http://www.posten.no/"},"14083":{"a":"14083","b":"http://www.posten.no/"},"14091":{"a":"14091","b":"http://www.nigerposte.net/"},"14092":{"a":"14092","b":"http://www.nigerposte.net/"},"14093":{"a":"14093","b":"http://www.nigerposte.net/"},"14101":{"a":"14101","b":"http://www.nipost.gov.ng/"},"14102":{"a":"14102","b":"http://www.nipost.gov.ng/"},"14103":{"a":"14103","b":"http://www.nipost.gov.ng/"},"15011":{"a":"15011","b":"http://www.omanpost.om/"},"15012":{"a":"15012","b":"http://www.omanpost.om/"},"15013":{"a":"15013","b":"http://www.omanpost.om/"},"16011":{"a":"16011","b":"http://www.pakpost.gov.pk/"},"16012":{"a":"16012","b":"http://www.pakpost.gov.pk/"},"16013":{"a":"16013","b":"http://www.pakpost.gov.pk/"},"16021":{"a":"16021","b":"http://www.palpost.ps/"},"16022":{"a":"16022","b":"http://www.palpost.ps/"},"16023":{"a":"16023","b":"http://www.palpost.ps/"},"16031":{"a":"16031","b":"http://www.correospanama.gob.pa/"},"16032":{"a":"16032","b":"http://www.correospanama.gob.pa/"},"16033":{"a":"16033","b":"http://www.correospanama.gob.pa/"},"16041":{"a":"16041","b":"http://www.postpng.com.pg/"},"16042":{"a":"16042","b":"http://www.postpng.com.pg/"},"16043":{"a":"16043","b":"http://www.postpng.com.pg/"},"16051":{"a":"16051","b":"http://www.correoparaguayo.gov.py/"},"16052":{"a":"16052","b":"http://www.correoparaguayo.gov.py/"},"16053":{"a":"16053","b":"http://www.correoparaguayo.gov.py/"},"16061":{"a":"16061","b":"http://www.serpost.com.pe/"},"16062":{"a":"16062","b":"http://www.serpost.com.pe/"},"16063":{"a":"16063","b":"http://www.serpost.com.pe/"},"16071":{"a":"16071","b":"http://www.phlpost.gov.ph/"},"16072":{"a":"16072","b":"http://www.phlpost.gov.ph/"},"16073":{"a":"16073","b":"http://www.phlpost.gov.ph/"},"16081":{"a":"16081","b":"http://www.poczta-polska.pl/"},"16082":{"a":"16082","b":"http://www.poczta-polska.pl/"},"16083":{"a":"16083","b":"http://www.poczta-polska.pl/"},"16101":{"a":"16101","b":"http://www.ctt.pt/"},"16102":{"a":"16102","b":"http://www.ctt.pt/"},"16103":{"a":"16103","b":"http://www.ctt.pt/"},"16141":{"a":"16141","b":""},"16142":{"a":"16142","b":""},"16143":{"a":"16143","b":""},"17011":{"a":"17011","b":"http://www.qpost.com.qa/"},"17012":{"a":"17012","b":"http://www.qpost.com.qa/"},"17013":{"a":"17013","b":"http://www.qpost.com.qa/"},"18021":{"a":"18021","b":"http://www.posta-romana.ro/"},"18022":{"a":"18022","b":"http://www.posta-romana.ro/"},"18023":{"a":"18023","b":"http://www.posta-romana.ro/"},"18031":{"a":"18031","b":"http://www.russianpost.ru/"},"18032":{"a":"18032","b":"http://www.russianpost.ru/"},"18033":{"a":"18033","b":"http://www.russianpost.ru/"},"18041":{"a":"18041","b":"http://www.i-posita.rw/"},"18042":{"a":"18042","b":"http://www.i-posita.rw/"},"18043":{"a":"18043","b":"http://www.i-posita.rw/"},"19021":{"a":"19021","b":"http://www.svgpost.gov.vc/"},"19022":{"a":"19022","b":"http://www.svgpost.gov.vc/"},"19023":{"a":"19023","b":"http://www.svgpost.gov.vc/"},"19031":{"a":"19031","b":"http://www.correos.gob.sv/"},"19032":{"a":"19032","b":"http://www.correos.gob.sv/"},"19033":{"a":"19033","b":"http://www.correos.gob.sv/"},"19051":{"a":"19051","b":"http://www.poste.sm/"},"19052":{"a":"19052","b":"http://www.poste.sm/"},"19053":{"a":"19053","b":"http://www.poste.sm/"},"19061":{"a":"19061","b":"http://www.inh.st/correios.st.htm"},"19062":{"a":"19062","b":"http://www.inh.st/correios.st.htm"},"19063":{"a":"19063","b":"http://www.inh.st/correios.st.htm"},"19071":{"a":"19071","b":"http://www.sp.com.sa/"},"19072":{"a":"19072","b":"http://www.sp.com.sa/"},"19073":{"a":"19073","b":"http://www.sp.com.sa/"},"19081":{"a":"19081","b":"http://www.laposte.sn/"},"19082":{"a":"19082","b":"http://www.laposte.sn/"},"19083":{"a":"19083","b":"http://www.laposte.sn/"},"19091":{"a":"19091","b":"http://www.posta.rs/"},"19092":{"a":"19092","b":"http://www.posta.rs/"},"19093":{"a":"19093","b":"http://www.posta.rs/"},"19111":{"a":"19111","b":"http://www.seychellespost.gov.sc/"},"19112":{"a":"19112","b":"http://www.seychellespost.gov.sc/"},"19113":{"a":"19113","b":"http://www.seychellespost.gov.sc/"},"19121":{"a":"19121","b":"http://www.salpost.sl/"},"19122":{"a":"19122","b":"http://www.salpost.sl/"},"19123":{"a":"19123","b":"http://www.salpost.sl/"},"19131":{"a":"19131","b":"http://www.singpost.com/"},"19132":{"a":"19132","b":"http://www.speedpost.com.sg/"},"19133":{"a":"19133","b":"http://www.speedpost.com.sg/"},"19141":{"a":"19141","b":"http://www.posta.sk/"},"19142":{"a":"19142","b":"http://www.posta.sk/"},"19143":{"a":"19143","b":"http://www.posta.sk/"},"19151":{"a":"19151","b":"http://www.posta.si/"},"19152":{"a":"19152","b":"http://www.posta.si/"},"19153":{"a":"19153","b":"http://www.posta.si/"},"19161":{"a":"19161","b":"http://www.solomonpost.com.sb/"},"19162":{"a":"19162","b":"http://www.solomonpost.com.sb/"},"19163":{"a":"19163","b":"http://www.solomonpost.com.sb/"},"19171":{"a":"19171","b":"http://www.postoffice.co.za/"},"19172":{"a":"19172","b":"http://www.postoffice.co.za/"},"19173":{"a":"19173","b":"http://www.postoffice.co.za/"},"19181":{"a":"19181","b":"http://www.correos.es/"},"19182":{"a":"19182","b":"http://www.correos.es/"},"19183":{"a":"19183","b":"http://www.correos.es/"},"19191":{"a":"19191","b":"http://www.slpost.gov.lk/"},"19192":{"a":"19192","b":"http://www.slpost.gov.lk/"},"19193":{"a":"19193","b":"http://www.slpost.gov.lk/"},"19201":{"a":"19201","b":"http://www.sudapost.com/"},"19202":{"a":"19202","b":"http://www.sudapost.com/"},"19203":{"a":"19203","b":"http://www.sudapost.com/"},"19211":{"a":"19211","b":"http://www.surpost.com/"},"19212":{"a":"19212","b":"http://www.surpost.com/"},"19213":{"a":"19213","b":"http://www.surpost.com/"},"19231":{"a":"19231","b":"http://www.sptc.co.sz/"},"19232":{"a":"19232","b":"http://www.sptc.co.sz/"},"19233":{"a":"19233","b":"http://www.sptc.co.sz/"},"19241":{"a":"19241","b":"http://www.posten.se/"},"19242":{"a":"19242","b":"http://www.posten.se/"},"19243":{"a":"19243","b":"http://www.posten.se/"},"19251":{"a":"19251","b":"http://www.swisspost.ch/"},"19252":{"a":"19252","b":"http://www.swisspost.ch/"},"19253":{"a":"19253","b":"http://www.swisspost.ch/"},"19261":{"a":"19261","b":"http://www.syrianpost.gov.sy/"},"19262":{"a":"19262","b":"http://www.syrianpost.gov.sy/"},"19263":{"a":"19263","b":"http://www.syrianpost.gov.sy/"},"19271":{"a":"19271","b":"http://www.post.gov.kn/"},"19272":{"a":"19272","b":"http://www.post.gov.kn/"},"19273":{"a":"19273","b":"http://www.post.gov.kn/"},"19281":{"a":"19281","b":"http://samoapost.ws/"},"19282":{"a":"19282","b":"http://samoapost.ws/"},"19283":{"a":"19283","b":"http://samoapost.ws/"},"19291":{"a":"19291","b":"http://mipt.gov.so/"},"19292":{"a":"19292","b":"http://mipt.gov.so/"},"19293":{"a":"19293","b":"http://mipt.gov.so/"},"19301":{"a":"19301","b":""},"19302":{"a":"19302","b":""},"19303":{"a":"19303","b":""},"19321":{"a":"19321","b":""},"19322":{"a":"19322","b":""},"19323":{"a":"19323","b":""},"20011":{"a":"20011","b":"https://ipost.post.gov.tw/"},"20012":{"a":"20012","b":"https://ipost.post.gov.tw/"},"20013":{"a":"20013","b":"https://ipost.post.gov.tw/"},"20021":{"a":"20021","b":""},"20022":{"a":"20022","b":""},"20023":{"a":"20023","b":""},"20031":{"a":"20031","b":"http://www.posta.co.tz/"},"20032":{"a":"20032","b":"http://www.posta.co.tz/"},"20033":{"a":"20033","b":"http://www.posta.co.tz/"},"20041":{"a":"20041","b":"http://www.thailandpost.co.th/"},"20042":{"a":"20042","b":"http://www.thailandpost.co.th/"},"20043":{"a":"20043","b":"http://www.thailandpost.co.th/"},"20051":{"a":"20051","b":"http://www.laposte.tg/"},"20052":{"a":"20052","b":"http://www.laposte.tg/"},"20053":{"a":"20053","b":"http://www.laposte.tg/"},"20061":{"a":"20061","b":"http://www.tongapost.to/"},"20062":{"a":"20062","b":"http://www.tongapost.to/"},"20063":{"a":"20063","b":"http://www.tongapost.to/"},"20071":{"a":"20071","b":"http://www.ttpost.net/"},"20072":{"a":"20072","b":"http://www.ttpost.net/"},"20073":{"a":"20073","b":"http://www.ttpost.net/"},"20091":{"a":"20091","b":""},"20092":{"a":"20092","b":""},"20093":{"a":"20093","b":""},"20101":{"a":"20101","b":"http://www.poste.tn/"},"20102":{"a":"20102","b":"http://www.poste.tn/"},"20103":{"a":"20103","b":"http://www.poste.tn/"},"20111":{"a":"20111","b":"http://www.ptt.gov.tr/"},"20112":{"a":"20112","b":"http://www.ptt.gov.tr/"},"20113":{"a":"20113","b":"http://www.ptt.gov.tr/"},"20121":{"a":"20121","b":"http://www.turkmenpost.gov.tm/"},"20122":{"a":"20122","b":"http://www.turkmenpost.gov.tm/"},"20123":{"a":"20123","b":"http://www.turkmenpost.gov.tm/"},"21011":{"a":"21011","b":"http://www.ugapost.co.ug/"},"21012":{"a":"21012","b":"http://www.ugapost.co.ug/"},"21013":{"a":"21013","b":"http://www.ugapost.co.ug/"},"21021":{"a":"21021","b":"http://ukrposhta.ua/"},"21022":{"a":"21022","b":"http://ukrposhta.ua/"},"21023":{"a":"21023","b":"http://dpsz.ua/"},"21031":{"a":"21031","b":"http://www.pochta.uz/"},"21032":{"a":"21032","b":"http://www.pochta.uz/"},"21033":{"a":"21033","b":"http://ems.uz/"},"21041":{"a":"21041","b":"http://www.correo.com.uy/"},"21042":{"a":"21042","b":"http://www.correo.com.uy/"},"21043":{"a":"21043","b":"http://www.correo.com.uy/"},"21051":{"a":"21051","b":"http://www.usps.com/"},"21052":{"a":"21052","b":"http://www.usps.com/"},"21053":{"a":"21053","b":"http://www.usps.com/"},"22021":{"a":"22021","b":"http://www.vanuatupost.vu/"},"22022":{"a":"22022","b":"http://www.vanuatupost.vu/"},"22023":{"a":"22023","b":"http://www.vanuatupost.vu/"},"22031":{"a":"22031","b":"http://www.ipostel.gob.ve/"},"22032":{"a":"22032","b":"http://www.ipostel.gob.ve/"},"22033":{"a":"22033","b":"http://www.ipostel.gob.ve/"},"22041":{"a":"22041","b":"http://www.vnpost.vn/"},"22042":{"a":"22042","b":"http://www.vnpost.vn/"},"22043":{"a":"22043","b":"http://www.vnpost.vn/"},"22051":{"a":"22051","b":"http://www.vaticanstate.va/"},"22052":{"a":"22052","b":"http://www.vaticanstate.va/"},"22053":{"a":"22053","b":"http://www.vaticanstate.va/"},"23021":{"a":"23021","b":""},"23022":{"a":"23022","b":""},"23023":{"a":"23023","b":""},"25011":{"a":"25011","b":"http://www.post.ye/"},"25012":{"a":"25012","b":"http://www.post.ye/"},"25013":{"a":"25013","b":"http://www.post.ye/"},"26011":{"a":"26011","b":"http://www.zampost.com.zm/"},"26012":{"a":"26012","b":"http://www.zampost.com.zm/"},"26013":{"a":"26013","b":"http://www.zampost.com.zm/"},"26021":{"a":"26021","b":"http://www.zimpost.co.zw/"},"26022":{"a":"26022","b":"http://www.zimpost.co.zw/"},"26023":{"a":"26023","b":"http://www.zimpost.co.zw/"},"89011":{"a":"89011","b":""},"89012":{"a":"89012","b":""},"89013":{"a":"89013","b":""},"90011":{"a":"90011","b":"http://www.royalmail.com/"},"90012":{"a":"90012","b":"http://www.parcelforce.com/"},"90013":{"a":"90013","b":"http://www.parcelforce.com/"},"90021":{"a":"90021","b":"http://www.aps.ai/"},"90022":{"a":"90022","b":"http://www.aps.ai/"},"90023":{"a":"90023","b":"http://www.aps.ai/"},"90031":{"a":"90031","b":"http://www.ascension-island.gov.ac/"},"90032":{"a":"90032","b":"http://www.ascension-island.gov.ac/"},"90033":{"a":"90033","b":"http://www.ascension-island.gov.ac/"},"90041":{"a":"90041","b":"http://www.bpo.bm/"},"90042":{"a":"90042","b":"http://www.bpo.bm/"},"90043":{"a":"90043","b":"http://www.bpo.bm/"},"90051":{"a":"90051","b":"http://www.caymanpost.gov.ky/"},"90052":{"a":"90052","b":"http://www.caymanpost.gov.ky/"},"90053":{"a":"90053","b":"http://www.caymanpost.gov.ky/"},"90061":{"a":"90061","b":"http://www.post.gi/"},"90062":{"a":"90062","b":"http://www.post.gi/"},"90063":{"a":"90063","b":"http://www.post.gi/"},"90071":{"a":"90071","b":"http://www.guernseypost.com/"},"90072":{"a":"90072","b":"http://www.guernseypost.com/"},"90073":{"a":"90073","b":"http://www.guernseypost.com/"},"90081":{"a":"90081","b":"http://www.postoffice.gov.sh/"},"90082":{"a":"90082","b":"http://www.postoffice.gov.sh/"},"90083":{"a":"90083","b":"http://www.postoffice.gov.sh/"},"91011":{"a":"91011","b":""},"91012":{"a":"91012","b":""},"91013":{"a":"91013","b":""},"91021":{"a":"91021","b":"http://www.posten.ax/"},"91022":{"a":"91022","b":"http://www.posten.ax/"},"91023":{"a":"91023","b":"http://www.posten.ax/"},"92011":{"a":"92011","b":""},"92012":{"a":"92012","b":""},"92013":{"a":"92013","b":""},"92021":{"a":"92021","b":"http://www.npostna.com/"},"92022":{"a":"92022","b":"http://www.npostna.com/"},"92023":{"a":"92023","b":"http://www.npostna.com/"},"92031":{"a":"92031","b":"http://www.postaruba.com/"},"92032":{"a":"92032","b":"http://www.postaruba.com/"},"92033":{"a":"92033","b":"http://www.postaruba.com/"},"93011":{"a":"93011","b":""},"93012":{"a":"93012","b":""},"93013":{"a":"93013","b":""},"94011":{"a":"94011","b":""},"94012":{"a":"94012","b":""},"94013":{"a":"94013","b":""},"95011":{"a":"95011","b":""},"95012":{"a":"95012","b":""},"95013":{"a":"95013","b":""},"95021":{"a":"95021","b":"http://www.norfolkisland.gov.nf/"},"95022":{"a":"95022","b":"http://www.norfolkisland.gov.nf/"},"95023":{"a":"95023","b":"http://www.norfolkisland.gov.nf/"},"96011":{"a":"96011","b":""},"96012":{"a":"96012","b":""},"96013":{"a":"96013","b":""},"96021":{"a":"96021","b":"http://www.posta.fo/"},"96022":{"a":"96022","b":"http://www.posta.fo/"},"96023":{"a":"96023","b":"http://www.posta.fo/"},"96031":{"a":"96031","b":"http://www.post.gl/"},"96032":{"a":"96032","b":"http://www.post.gl/"},"96033":{"a":"96033","b":"http://www.post.gl/"},"97011":{"a":"97011","b":"http://www.laposte.fr/"},"97012":{"a":"97012","b":"http://www.colissimo.fr/"},"97013":{"a":"97013","b":"http://www.chronopost.fr/"},"97021":{"a":"97021","b":"http://www.opt.nc/"},"97022":{"a":"97022","b":"http://www.opt.nc/"},"97023":{"a":"97023","b":"http://www.opt.nc/"},"98011":{"a":"98011","b":"http://www.usps.com/"},"98012":{"a":"98012","b":"http://www.usps.com/"},"98013":{"a":"98013","b":"http://www.usps.com/"},"99011":{"a":"99011","b":""},"99012":{"a":"99012","b":""},"99013":{"a":"99013","b":""}}';
	static private $Track17_post_nation_url_map_array = '';
	
	static private $status_set_state_map = array(
			"checking"=>"initial",
			"shipping"=>"normal",
			"suspend"=>"normal",
			"no_info"=>"normal",
			"ship_over_time"=>"exception",
			"expired"=>"complete",
			"arrived_pending_fetch"=>"exception",
			"delivery_failed"=>"exception",
			"received"=>"complete",
			"platform_confirmed"=>"complete",
			"unregistered"=>"complete",
			"rejected"=>"exception",
			"untrackable"=>"unshipped",
			"ignored"=>"complete",
			"quota_insufficient"=>"exception",
	);
	
	static private $parcel_17Track_status_map = array(
			"0" =>"no_info",
			"10"=>"shipping",
			"20"=>"ship_over_time",
			"30"=>"arrived_pending_fetch",
			"35"=>"delivery_failed",
			"40"=>"received",
			"50"=>"rejected"		
	);	
	
	static private $Track17_express_url_map_array = array('100002'=>'http://www.ups.com/',
			'190002'=>'http://www.flytexpress.com/',
			'100001'=>'http://www.dhl.com/',
			'190008'=>'http://www.yunpost.cn/',
			'100003'=>'http://www.fedex.com/',
			'190011'=>'http://www.1001000.com/',
			'100004'=>'http://www.tnt.com/',
			'190007'=>'http://www.xru.com/',
			'190009'=>'http://www.kuaidaexp.com/',
			'100007'=>'http://www.dpd.com/',
			'190003'=>'http://www.hh-exp.com/',
			'100011'=>'http://www.oneworldexpress.com/',
			'190012'=>'http://www.yw56.com.cn/',
			'190013'=>'http://www.mxe56.com/',
			'100005'=>'http://gls-group.eu/',
			'190014'=>'http://www.4000588103.com/',
			'100012'=>'http://www.sfb2c.com/',
			'190015'=>'http://www.ruston.cc/',
			'100008'=>'http://www.bpostinternational.com/',
			'100009'=>'http://www.tollgroup.com/',
			'190017'=>'http://ets-express.com/',
			'100006'=>'http://www.aramex.com/',
			'190016'=>'http://www.007ex.com/',
	);
	
	static private $Track17_Nation_map = array(
			'0'=>"Unknown",'102'=>"Afghanistan",'103'=>"Albania",'104'=>"Algeria",'105'=>"Andorra",'106'=>"Angola",'108'=>"Antarctica",
			'110'=>"Antigua and Barbuda",'112'=>"Argentina",'113'=>"Armenia",'115'=>"Australia",'116'=>"Austria",'117'=>"Azerbaijan",'201'=>"Bahamas",'202'=>"Bahrain",'203'=>"Bangladesh",'204'=>"Barbados",'205'=>"Belarus",'206'=>"Belgium",'207'=>"Belize",'208'=>"Benin",'210'=>"Bhutan",'211'=>"Bolivia",'212'=>"Bosnia and Herzegovina",'213'=>"Botswana",'215'=>"Brazil",'216'=>"Brunei",'217'=>"Bulgaria",'218'=>"Burkina Faso",'219'=>"Burundi",'301'=>"China",'302'=>"Cambodia",'303'=>"Cameroon",'304'=>"Canada",'306'=>"Cape Verde",'308'=>"Central African Republic",'310'=>"Chile",'312'=>"Ivory Coast",'313'=>"Colombia",'314'=>"Comoros",'315'=>"Congo-Brazzaville",'316'=>"Congo-Kinshasa",'317'=>"Cook Islands",'318'=>"Costa Rica",'319'=>"Croatia",'320'=>"Cuba",'321'=>"Cyprus",'322'=>"Czech Republic",'323'=>"Chad",'401'=>"Denmark",'402'=>"Djibouti",'403'=>"Dominica",'404'=>"Dominican Republic",'501'=>"Ecuador",'502'=>"Egypt",'503'=>"United Arab Emirates",'504'=>"Estonia",'505'=>"Ethiopia",'506'=>"Eritrea",'507'=>"Equatorial Guinea",'508'=>"East Timor",'603'=>"Fiji",'604'=>"Finland",'605'=>"France",'701'=>"Gabon",'702'=>"Gambia",'703'=>"Georgia",'704'=>"Germany",'705'=>"Ghana",'707'=>"Greece",'709'=>"Grenada",'712'=>"Guatemala",'713'=>"Guinea",'714'=>"Guyana",'716'=>"Guinea-Bissau",'801'=>"Hong Kong CN",'802'=>"Haiti",'804'=>"Honduras",'805'=>"Hungary",'901'=>"Iceland",'902'=>"India",'903'=>"Indonesia",'904'=>"Iran",'905'=>"Ireland",'906'=>"Israel",'907'=>"Italy",'908'=>"Iraq",'1001'=>"Jamaica",'1002'=>"Japan",'1003'=>"Jordan",'1101'=>"Kazakhstan",'1102'=>"Kenya",'1103'=>"United Kingdom",'1104'=>"Kiribati",'1105'=>"Korea, South",'1106'=>"Korea, North",'1107'=>"Kosovo",'1108'=>"Kuwait",'1109'=>"Kyrgyzstan",'1201'=>"Laos",'1202'=>"Latvia",'1203'=>"Lebanon",'1204'=>"Lesotho",'1205'=>"Liberia",'1206'=>"Libya",'1207'=>"Liechtenstein",'1208'=>"Lithuania",'1209'=>"Saint Lucia",'1210'=>"Luxembourg",'1301'=>"Macao CN",'1302'=>"Macedonia",'1303'=>"Madagascar",'1304'=>"Malawi",'1305'=>"Malaysia",'1306'=>"Maldives",'1307'=>"Mali",'1308'=>"Malta",'1310'=>"Marshall Islands",'1312'=>"Mauritania",'1313'=>"Mauritius",'1314'=>"Mexico",'1315'=>"Federated States of Micronesia",'1316'=>"Moldova",'1317'=>"Monaco",'1318'=>"Mongolia",'1319'=>"Montenegro",'1321'=>"Morocco",'1322'=>"Mozambique",'1323'=>"Myanmar",'1401'=>"Namibia",'1402'=>"Nauru",'1403'=>"Nepal",'1404'=>"Netherlands",'1406'=>"New Zealand",'1407'=>"Nicaragua",'1408'=>"Norway",'1409'=>"Niger",'1410'=>"Nigeria",'1501'=>"Oman",'1601'=>"Pakistan",'1602'=>"Palestine",'1603'=>"Panama",'1604'=>"Papua New Guinea",'1605'=>"Paraguay",'1606'=>"Peru",'1607'=>"Philippines",'1608'=>"Poland",'1610'=>"Portugal",'1614'=>"Palau",'1701'=>"Qatar",'1802'=>"Romania",'1803'=>"Russian Federation",'1804'=>"Rwanda",'1902'=>"Saint Vincent and the Grenadines",'1903'=>"El Salvador",'1905'=>"San Marino",'1906'=>"Sao Tome and Principe",'1907'=>"Saudi Arabia",'1908'=>"Senegal",'1909'=>"Serbia",'1911'=>"Seychelles",'1912'=>"Sierra Leone",'1913'=>"Singapore",'1914'=>"Slovakia",'1915'=>"Slovenia",'1916'=>"Solomon Islands",'1917'=>"South Africa",'1918'=>"Spain",'1919'=>"Sri Lanka",'1920'=>"Sudan",'1921'=>"Suriname",'1923'=>"Swaziland",'1924'=>"Sweden",'1925'=>"Switzerland",'1926'=>"Syrian Arab Republic",'1927'=>"Saint Kitts and Nevis",'1928'=>"Samoa",'1929'=>"Somalia",'1930'=>"Scotland",'1932'=>"South Ossetia",'2001'=>"Taiwan CN",'2002'=>"Tajikistan",'2003'=>"Tanzania",'2004'=>"Thailand",'2005'=>"Togo",'2006'=>"Tonga",'2007'=>"Trinidad and Tobago",'2009'=>"Tuvalu",'2010'=>"Tunisia",'2011'=>"Turkey",'2012'=>"Turkmenistan",'2101'=>"Uganda",'2102'=>"Ukraine",'2103'=>"Uzbekistan",'2104'=>"Uruguay",'2105'=>"United States",'2202'=>"Vanuatu",'2203'=>"Venezuela",'2204'=>"Vietnam",'2205'=>"Vatican City",'2302'=>"Western Sahara",'2501'=>"Yemen",'2601'=>"Zambia",'2602'=>"Zimbabwe",'8901'=>"Overseas Territory ES",'9001'=>"Overseas Territory GB",'9002'=>"Anguilla GB",'9003'=>"Ascension GB",'9004'=>"Bermuda GB",'9005'=>"Cayman Islands GB",'9006'=>"Gibraltar GB",'9007'=>"Guernsey GB",'9008'=>"Saint Helena GB",'9101'=>"Overseas Territory FI",'9102'=>"脜aland Islands FI",'9201'=>"Overseas Territory NL",'9202'=>"Antilles NL",'9203'=>"Aruba NL",'9301'=>"Overseas Territory PT",'9401'=>"Overseas Territory NO",'9501'=>"Overseas Territory AU",'9502'=>"Norfolk Island AU",'9601'=>"Overseas Territory DK",'9602'=>"Faroe Islands DK",'9603'=>"Greenland DK",'9701'=>"Overseas Territory FR",'9702'=>"New Caledonia FR",'9801'=>"Overseas Territory US",'9901'=>"Overseas Territory NZ"
	);
	
	static private $Track17_Nation_Code_map_Standard_Nation_Code = array(
			'102'=>'AF',
			'103'=>'AL',
			'104'=>'DZ',
			'106'=>'AO',
			'108'=>'AQ',
			'110'=>'AG',
			'112'=>'AR',
			'113'=>'AM',
			'115'=>'AU',
			'116'=>'AT',
			'117'=>'AZ',
			'201'=>'BS',
			'202'=>'BH',
			'203'=>'BD',
			'204'=>'BB',
			'205'=>'BY',
			'206'=>'BE',
			'207'=>'BZ',
			'208'=>'BJ',
			'210'=>'BT',
			'211'=>'BO',
			'212'=>'BA',
			'213'=>'BW',
			'215'=>'BR',
			'216'=>'BN',
			'217'=>'BG',
			'218'=>'BF',
			'219'=>'BI',
			'301'=>'CN',
			'302'=>'KH',
			'303'=>'CM',
			'304'=>'CA',
			'306'=>'CV',
			'308'=>'CF',
			'310'=>'CL',
			'313'=>'CO',
			'314'=>'KM',
			'317'=>'CK',
			'318'=>'CR',
			'320'=>'CU',
			'321'=>'CY',
			'323'=>'TD',
			'401'=>'DK',
			'402'=>'DJ',
			'403'=>'DM',
			'501'=>'EC',
			'502'=>'EG',
			'503'=>'AE',
			'504'=>'EE',
			'505'=>'ET',
			'506'=>'ER',
			'507'=>'GQ',
			'508'=>'TP',
			'603'=>'FJ',
			'604'=>'FI',
			'605'=>'FR',
			'701'=>'GA',
			'702'=>'GM',
			'703'=>'GE',
			'704'=>'DE',
			'705'=>'GH',
			'707'=>'GR',
			'709'=>'GD',
			'712'=>'GT',
			'713'=>'GN',
			'714'=>'GY',
			'801'=>'HK',
			'802'=>'HT',
			'804'=>'HN',
			'805'=>'HU',
			'901'=>'IS',
			'902'=>'IN',
			'903'=>'ID',
			'904'=>'IR',
			'905'=>'IE',
			'906'=>'IL',
			'907'=>'IT',
			'908'=>'IQ',
			'1001'=>'JM',
			'1002'=>'JP',
			'1003'=>'JO',
			'1101'=>'KZ',
			'1102'=>'KE',
			'1103'=>'GB',
			'1104'=>'KI',
			'1108'=>'KW',
			'1201'=>'LA',
			'1202'=>'LV',
			'1203'=>'LB',
			'1204'=>'LS',
			'1205'=>'LR',
			'1206'=>'LY',
			'1207'=>'LI',
			'1208'=>'LT',
			'1209'=>'LC',
			'1210'=>'LU',
			'1301'=>'MO',
			'1303'=>'MG',
			'1304'=>'MW',
			'1305'=>'MY',
			'1306'=>'MV',
			'1307'=>'ML',
			'1308'=>'MT',
			'1310'=>'MH',
			'1312'=>'MR',
			'1313'=>'MU',
			'1314'=>'MX',
			'1316'=>'MD',
			'1317'=>'MC',
			'1318'=>'MN',
			'1319'=>'ME',
			'1321'=>'MA',
			'1322'=>'MZ',
			'1323'=>'MM',
			'1401'=>'NA',
			'1402'=>'NR',
			'1403'=>'NP',
			'1404'=>'NL',
			'1406'=>'NZ',
			'1407'=>'NI',
			'1408'=>'NO',
			'1409'=>'NE',
			'1410'=>'NG',
			'1501'=>'OM',
			'1601'=>'PK',
			'1603'=>'PA',
			'1604'=>'PG',
			'1605'=>'PY',
			'1606'=>'PE',
			'1607'=>'PH',
			'1608'=>'PL',
			'1610'=>'PT',
			'1614'=>'PW',
			'1701'=>'QA',
			'1802'=>'RO',
			'1803'=>'RU',
			'1804'=>'RW',
			'1903'=>'SV',
			'1905'=>'SM',
			'1906'=>'ST',
			'1907'=>'SA',
			'1908'=>'SN',
			'1909'=>'RS',
			'1911'=>'SC',
			'1912'=>'SL',
			'1913'=>'SG',
			'1914'=>'SK',
			'1915'=>'SI',
			'1916'=>'SB',
			'1917'=>'ZA',
			'1918'=>'ES',
			'1919'=>'LK',
			'1920'=>'SD',
			'1921'=>'SR',
			'1923'=>'SZ',
			'1924'=>'SE',
			'1925'=>'CH',
			'1927'=>'KN',
			'1928'=>'WS',
			'1929'=>'SO',
			'2001'=>'TW',
			'2002'=>'TJ',
			'2003'=>'TZ',
			'2004'=>'TH',
			'2005'=>'TG',
			'2006'=>'TO',
			'2007'=>'TT',
			'2009'=>'TV',
			'2010'=>'TN',
			'2011'=>'TR',
			'2012'=>'TM',
			'2101'=>'UG',
			'2102'=>'UA',
			'2103'=>'UZ',
			'2104'=>'UY',
			'2105'=>'US',
			'2202'=>'VU',
			'2203'=>'VE',
			'2204'=>'VN',
			'2302'=>'EH',
			'2501'=>'YE',
			'2602'=>'ZW',
			'9603'=>'GL'
	);
	
	//these are for runtime cached
	static private  $Track17_Nation_map_FLIP = '';
	static private  $status_map_FLIP = '';
	static private  $state_map_FLIP = '';
	static private $Track17_Nation_Code_map_Standard_Nation_Code_FLIP='';
	/*
	private $QueryParcelConditionMapping = [
		'normal_parcel'=>['source'=>['M','E'] ,'state'=>['normal','initial']],
		'shipping_parcel'=>['source'=>['M','E'] , 'status'=>['shipping']],
		'no_info_parcel'=>['source'=>['M','E'] , 'status'=>['no_info']],
		'exception_parcel'=>['source'=>['M','E'] , 'state' => ['exception' , 'unshipped']],
		'rejected_parcel'=>['source'=>['M','E'] ,  'status'=>['rejected']],
		'ship_over_time_parcel'=>['source'=>['M','E'] , 'status'=>['ship_over_time']],
		'arrived_pending_fetch_parcel'=>['source'=>['M','E'] ,  'status'=>['arrived_pending_fetch']],
		'unshipped_parcel'=>['source'=>['M','E'] , 'state' => ['unshipped']],
		'received_parcel'=>['source'=>['M','E'] , 'state' => ['complete']],
	];
	*/
	
	static private $Track17_Parcel_Type = array('0'=>'',"1"=>"小包","2"=>"大包","3"=>"EMS");
	
	static private $QueryParcelConditionMapping = [
	'normal_parcel'=>['state'=>['normal','initial']],
	'shipping_parcel'=>['status'=>['shipping']],
	'no_info_parcel'=>[ 'status'=>['no_info','checking']],
	'suspend_parcel'=>['status'=>['suspend']],
	'exception_parcel'=>[ 'state' => ['exception' , 'unshipped']],
	'rejected_parcel'=>[ 'status'=>['rejected']],
	'ship_over_time_parcel'=>[ 'status'=>['ship_over_time']],
	'arrived_pending_fetch_parcel'=>[ 'status'=>['arrived_pending_fetch']],
	'delivery_failed_parcel'=>      [ 'status'=>['delivery_failed']],
	'unshipped_parcel'=>[ 'state' => ['unshipped']],
	'received_parcel'=>['status' => ['received']],
	'expired_parcel'=>['status' => ['expired']],
	'unregistered_parcel'=>['status' => ['unregistered']],
	'completed_parcel'=>['status' => ['platform_confirmed','received']],
	'platform_confirmed_parcel'=>['status' => ['platform_confirmed']],
	'ignored_parcel'=>['status' => ['ignored']],
	'quota_insufficient'=>['status'=>['quota_insufficient']],
	];
		
	static private $status_set_class_mapping = [
			"checking"=>"default",
			"shipping"=>"primary",
			"no_info"=>"default",
			"ship_over_time"=>"danger",
			"arrived_pending_fetch"=>"danger",
			"delivery_failed"=>"danger",
			"received"=>"success",
			"rejected"=>"danger",
	];
	
	static public function get17TrackParcelTypeLabel($code){
		$label = '';
		$map = self::$Track17_Parcel_Type;
		if (isset($map[$code]))
			$label = $map[$code];
		
		return $label;
	}
  	static public function getChineseStatus($sysStatus='',$wholeMap = false){
  		//when not passed valid sysStatus, return the whole mapping array
  		if ($wholeMap)  	  
  			return self::$status_map;
  		else{
  		//when specified a sysStatus, return its chinese label
  			$allMap = self::$status_map;
  			if (isset($allMap[$sysStatus]))
  				return 	$allMap[$sysStatus];
  			else 
  				return "--";
  		}
  	}//end of function getChineseStatuss
  	
  	static public function getSysStatus($chineseStatus='',$wholeMap = false){
  		//when not passed valid sysStatus, return the whole mapping array
  		//use cache for flip version
  		if (self::$status_map_FLIP == '')
  			self::$status_map_FLIP = array_flip(self::$status_map);
  		
  		$allMap = self::$status_map_FLIP;
  		if ($wholeMap)
  			return self::$allMap;
  		else{
  			//when specified a sysStatus, return its chinese label
  			if (isset($allMap[$chineseStatus]))
  				return 	$allMap[$chineseStatus];
  			else
  				return "--";
  		}
  	}//end of function getSysStatus
  	
  	static public function getChineseState($sysState='',$wholeMap = false){
  		//when not passed valid sysState, return the whole mapping array
  		if ($wholeMap)
  			return self::$state_map;
  		else{
  			//when specified a sysState, return its chinese label
  			$allMap = self::$state_map;
  			if (isset($allMap[$sysState]))
  				return 	$allMap[$sysState];
  			else
  				return "--";
  		}
  	}//end of function getChineseStates
  	
  	static public function getEnglishStatus($sysState='',$wholeMap = false){
  		//when not passed valid sysState, return the whole mapping array
  		if ($wholeMap)
  			return self::$status_enMap;
  		else{
  			//when specified a sysState, return its chinese label
  			$allMap = self::$status_enMap;
  			if (isset($allMap[$sysState]))
  				return 	$allMap[$sysState];
  			else
  				return "--";
  		}
  	}//end of function getChineseStates
  	 
  	static public function getSysState($chineseState='',$wholeMap = false){
  		//when not passed valid sysState, return the whole mapping array
  		//use cache for flip version
  		if (self::$state_map_FLIP == '')
  			self::$state_map_FLIP = array_flip(self::$state_map);
  		
  		$allMap = self::$state_map_FLIP;
  		if ($wholeMap  )
  			return self::$allMap;
  		else{
  			//when specified a sysState, return its chinese label
  			if (isset($allMap[$chineseState]))
  				return 	$allMap[$chineseState];
  			else
  				return "--";
  		}
  	}//end of function getSysState
  	
  	static public function get17TrackNationEnglish($code='',$wholeMap = false){
  		//when not passed valid sysStatus, return the whole mapping array
  		if ($wholeMap)
  			return self::$Track17_Nation_map;
  		else{
  			//when specified a sysStatus, return its chinese label
  			$allMap = self::$Track17_Nation_map;
  			if (isset($allMap[$code]))
  				return 	$allMap[$code];
  			else
  				return $code;
  		}
  	}//end of function get17TrackNationEnglish
  	
  	static public function getParcelStatusBy17TrackStatus($code='',$wholeMap = false){
  		//when not passed valid sysStatus, return the whole mapping array
  		if ($wholeMap)
  			return self::$parcel_17Track_status_map;
  		else{
  			//when specified a sysStatus, return its chinese label
  			$allMap = self::$parcel_17Track_status_map;
  			if (isset($allMap[$code]))
  				return 	$allMap[$code];
  			else
  				return "checking";
  		}
  	}//end of function getParcelStatusBy17TrackStatus
  	
  	static public function getParcelStateByStatus($status_code='',$wholeMap = false){
  		//when not passed valid sysStatus, return the whole mapping array
  		if ($wholeMap)
  			return self::$status_set_state_map;
  		else{
  			//when specified a sysStatus, return its chinese label
  			$allMap = self::$status_set_state_map;
 
  			if (isset($allMap[$status_code ]))
  				return 	$allMap[$status_code];
  			else
  				return "--";
  		}
  	}//end of function getParcelStateByStatus  

  	static public function get17TrackNationCodeByStandardNationCode($code){  	
  		if ($code == 'UK')
  			$code = 'GB';
  		//use cache for flip version
  		if (self::$Track17_Nation_Code_map_Standard_Nation_Code_FLIP == '')
  			self::$Track17_Nation_Code_map_Standard_Nation_Code_FLIP = array_flip(self::$Track17_Nation_Code_map_Standard_Nation_Code);
  		
  			$allMap = self::$Track17_Nation_Code_map_Standard_Nation_Code_FLIP;

  			//when specified a sysState, return its chinese label
  			if (isset($allMap[$code]))
  				return 	$allMap[$code];
  			else
  				return "";
  	}//end of get17TrackNationCodeByStandardNationCode

  	static public function get17TrackNationUrlByCode($code){
  		//use cache for flip version
  		if (self::$Track17_post_nation_url_map_array == '')
  			self::$Track17_post_nation_url_map_array = json_decode(self::$Track17_post_nation_url_map_json,true);
  	
  		$allMap = self::$Track17_post_nation_url_map_array;
  	
  		//when specified a sysState, return its chinese label
  		if (isset($allMap[$code]['b']))
  			return $allMap[$code]['b'];
  		else
  			return "";
  	}

  	static public function get17TrackExpressUrlByCode($code){
  		//use cache for flip version
  		
  		$allMap = self::$Track17_express_url_map_array;
  		 
  		//when specified a sysState, return its chinese label
  		if (isset($allMap[$code] ))
  			return $allMap[$code] ;
  		else
  			return "";
  	}
  	 	
  	static public function getTrClassByState($status_code=''){
  		switch($status_code):
  		case 'complete':
  			$tr_class = "success";
  		break;
  		case 'exception':
  			$tr_class = "danger";
  			break;
  		case 'unshipped':
  			$tr_class = "warning";
  			break;
  		default:
  			$tr_class = "";
  		
  		endswitch;
  		return $tr_class;
  	}
  	
  	
  	
  	
  	/**
  	 +---------------------------------------------------------------------------------------------
  	 * 根据 参数type 获取对应 condition
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * @access static
  	 +---------------------------------------------------------------------------------------------
  	 * @param $type					Tracking Parcel 的类型
  	 +---------------------------------------------------------------------------------------------
  	 * @return						array condition 数据
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * log			name	date					note
  	 * @author		lkh		2015/2/15				初始化
  	 +---------------------------------------------------------------------------------------------
  	 **/
  	static public function getTrackingConditionByClassification($type){
  		try {
  			if (!empty(self::$QueryParcelConditionMapping[$type]))
  				return self::$QueryParcelConditionMapping[$type];
  			else
  				return [];
  		} catch (Exception $e) {
  			return [];
  		}
  	}
  	
  	/**
  	 +---------------------------------------------------------------------------------------------
  	 * 从已经load到的model的字段‘addi_info’中，以json格式读取 收件人国家这个字段
  	 +---------------------------------------------------------------------------------------------
  	 * @access static
  	 +---------------------------------------------------------------------------------------------
  	 * @param
  	 +---------------------------------------------------------------------------------------------
  	 * @return						array(trackingModel1,trackingModel2,trackingModel3)
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * log			name	date					note
  	 * @author		yzq	2015/2/15				初始化
  	 +---------------------------------------------------------------------------------------------
  	 **/
  	public function getConsignee_country_code(){
  		$this->addi_info =str_replace("`",'"',$this->addi_info);  
  		$addi_info = json_decode($this->addi_info,true);
  		$consignee_country_code = isset($addi_info['consignee_country_code'])?$addi_info['consignee_country_code']:"";
  		if (empty($consignee_country_code) and !empty($this->to_nation))
  			$consignee_country_code = $this->to_nation;
  		return $consignee_country_code;
  	}
  	
  	/**
  	 +---------------------------------------------------------------------------------------------
  	 * 从已经load到的model的字段‘addi_info’中，以json格式保存 收件人国家这个字段
  	 +---------------------------------------------------------------------------------------------
  	 * @access static
  	 +---------------------------------------------------------------------------------------------
  	 * @param
  	 +---------------------------------------------------------------------------------------------
  	 * @return						array(trackingModel1,trackingModel2,trackingModel3)
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * log			name	date					note
  	 * @author		yzq	2015/2/15				初始化
  	 +---------------------------------------------------------------------------------------------
  	 **/
  	public function setConsignee_country_code($standardNationCode){
  		$this->addi_info =str_replace("`",'"',$this->addi_info);
  		$addi_info = json_decode($this->addi_info,true);
  		$addi_info['consignee_country_code'] = $standardNationCode;
  		$this->addi_info = json_encode($addi_info);  		
  		return self::save();
  	}
  	
  	/**
  	 +---------------------------------------------------------------------------------------------
  	 * 如果没有任何数据，提供Sample Data，让用户简单看到效果
  	 * 这个Sample Data是模拟的
  	 +---------------------------------------------------------------------------------------------
  	 * @access static
  	 +---------------------------------------------------------------------------------------------
  	 * @param 
  	 +---------------------------------------------------------------------------------------------
  	 * @return						array(trackingModel1,trackingModel2,trackingModel3)
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * log			name	date					note
  	 * @author		yzq	2015/2/15				初始化
  	 +---------------------------------------------------------------------------------------------
  	 **/
  	static public function getTrackingSampleData(){
  		$trackingArr = array();
  		
  		$data = json_decode(base64_decode(self::$sampleData_json_base64),true);
  		
  		$pagination = new Pagination([
  				'pageSize' => '20',
  				'totalCount' => count($data['data']),
  				]);
  		
  		$data['pagination'] = $pagination;
  		return $data;
  		echo "Start to show data decoded <br>";
  		foreach ($data  as $aData){
  			echo print_r($aData,true)."<br> ";
  			echo "done Fuck  <br>";
  			$aTracking = new self();
  			$aTracking->setAttributes($aData);
  			$trackingArr[] = $aTracking->getAttributes();
  		}//end of each sample data
  		return $trackingArr;
  	}
  	
  	/**
  	 +---------------------------------------------------------------------------------------------
  	 * 根据 参数 status  获取对应关键 css 
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * @access
  	 +---------------------------------------------------------------------------------------------
  	 * @param $status					Tracking status  
  	 +---------------------------------------------------------------------------------------------
  	 * @return						string css keyword
  	 *
  	 +---------------------------------------------------------------------------------------------
  	 * log			name	date					note
  	 * @author		lkh		2015/3/10				初始化
  	 +---------------------------------------------------------------------------------------------
  	 **/
  	static public function getCssKeyWordByStatus($status_code){
  		try {
  			$classList = self::$status_set_class_mapping;
  			//$status_code = trim($status_code);
  			if (array_key_exists($status_code , $classList))
  				return $classList[$status_code];
  			else
  				return "";
  		} catch (Exception $e) {
  			echo $e->getMessage();
  			return "";
  		}
  	}//end of getCssKeyWordByStatus
  	
  	
  	
  	static private $sampleData_json_base64 = 'eyJwYWdpbmF0aW9uIjp7InBhZ2VQYXJhbSI6InBhZ2UiLCJwYWdlU2l6ZVBhcmFtIjoicGVyLXBhZ2UiLCJmb3JjZVBhZ2VQYXJhbSI6dHJ1ZSwicm91dGUiOm51bGwsInBhcmFtcyI6bnVsbCwidXJsTWFuYWdlciI6bnVsbCwidmFsaWRhdGVQYWdlIjp0cnVlLCJ0b3RhbENvdW50IjoiNyIsImRlZmF1bHRQYWdlU2l6ZSI6MjAsInBhZ2VTaXplTGltaXQiOlsxLDUwXX0sImNvbmRpdGlvbiI6IiAxICIsImRhdGEiOlt7ImlkIjoiMSIsIm9yZGVyX2lkIjoiXHU3OTNhXHU4MzAzXHU4YmEyXHU1MzU1OTExOTFDWTYwVSIsInRyYWNrX25vIjoiXHU3OTNhXHU4MzAzUkgxNTIxMjcyNzlDTiIsInN0YXR1cyI6Ilx1OGZkMFx1OGY5M1x1OTAxNFx1NGUyZCIsInN0YXRlIjoiXHU2YjYzXHU1ZTM4Iiwic291cmNlIjoiTSIsInBsYXRmb3JtIjpudWxsLCJwYXJjZWxfdHlwZSI6IjEiLCJpc19hY3RpdmUiOiJZIiwiYmF0Y2hfbm8iOiJNMjAxNTAyMTYiLCJjcmVhdGVfdGltZSI6IjIwMTUtMDItMTYiLCJ1cGRhdGVfdGltZSI6IjIwMTUtMDItMTYgMTc6MTU6NTMiLCJmcm9tX25hdGlvbiI6IkNOIiwidG9fbmF0aW9uIjoiRlIiLCJtYXJrX2hhbmRsZWQiOiJOIiwibm90aWZpZWRfc2VsbGVyIjoiTiIsIm5vdGlmaWVkX2J1eWVyIjoiTiIsInNoaXBfYnkiOiI0UFgiLCJkZWxpdmVyeV9mZWUiOiIxMi4yNTM1Iiwic2hpcF9vdXRfZGF0ZSI6IjIwMTUtMDItMDIiLCJ0b3RhbF9kYXlzIjoiLTEiLCJhbGxfZXZlbnQiOiJbe1wid2hlblwiOlwiMjAxNS0wMi0xNSAxNjoyM1wiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0xNSAxMzo0MlwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWYwMFxcdTYyYzZcIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMiAxMTowMlwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTVjMDFcXHU1M2QxXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMTIgMTE6MDJcIixcIndoZXJlXCI6XCJcXHU1ZTdmXFx1NTU0NlxcdTU2ZmRcXHU5NjQ1XFx1NWMwZlxcdTUzMDVcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU2NTM2XFx1NWJjNFwifV0iLCJhZGRpX2luZm8iOm51bGx9LHsiaWQiOiIyIiwib3JkZXJfaWQiOiJcdTc5M2FcdTgzMDNcdThiYTJcdTUzNTU5ODkyMUNZRlU3IiwidHJhY2tfbm8iOiJcdTc5M2FcdTgzMDNSSDE1MjEyNzI2NUNOIiwic3RhdHVzIjoiXHU4ZmQwXHU4ZjkzXHU5MDE0XHU0ZTJkIiwic3RhdGUiOiJcdTZiNjNcdTVlMzgiLCJzb3VyY2UiOiJNIiwicGxhdGZvcm0iOm51bGwsInBhcmNlbF90eXBlIjoiMSIsImlzX2FjdGl2ZSI6IlkiLCJiYXRjaF9ubyI6Ik0yMDE1MDIxNiIsImNyZWF0ZV90aW1lIjoiMjAxNS0wMi0xNiIsInVwZGF0ZV90aW1lIjoiMjAxNS0wMi0xNiAxNzoxNTo1MyIsImZyb21fbmF0aW9uIjoiQ04iLCJ0b19uYXRpb24iOiJGUiIsIm1hcmtfaGFuZGxlZCI6Ik4iLCJub3RpZmllZF9zZWxsZXIiOiJOIiwibm90aWZpZWRfYnV5ZXIiOiJOIiwic2hpcF9ieSI6IjRQWCIsImRlbGl2ZXJ5X2ZlZSI6IjE2LjQxNjUiLCJzaGlwX291dF9kYXRlIjoiMjAxNS0wMi0wMiIsInRvdGFsX2RheXMiOiItMSIsImFsbF9ldmVudCI6Ilt7XCJ3aGVuXCI6XCIyMDE1LTAyLTE1IDE2OjE3XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU0ZTkyXFx1NjM2MlxcdTVjNDBcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU1YzAxXFx1NTNkMVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTE1IDEzOjQyXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU0ZTkyXFx1NjM2MlxcdTVjNDBcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU1ZjAwXFx1NjJjNlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTEyIDExOjAyXCIsXCJ3aGVyZVwiOlwiXFx1NWU3ZlxcdTU1NDZcXHU1NmZkXFx1OTY0NVxcdTVjMGZcXHU1MzA1XCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMiAxMTowMlwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTY1MzZcXHU1YmM0XCJ9XSIsImFkZGlfaW5mbyI6bnVsbH0seyJpZCI6IjMiLCJvcmRlcl9pZCI6Ilx1NzkzYVx1ODMwM1x1OGJhMlx1NTM1NTkxOTk0RDI5OTkiLCJ0cmFja19ubyI6Ilx1NzkzYVx1ODMwM1JIMTUwMzk0MTkyQ04iLCJzdGF0dXMiOiJcdTYyMTBcdTUyOWZcdTdiN2VcdTY1MzYiLCJzdGF0ZSI6Ilx1NWRmMlx1NWI4Y1x1NjIxMCIsInNvdXJjZSI6Ik0iLCJwbGF0Zm9ybSI6bnVsbCwicGFyY2VsX3R5cGUiOiIxIiwiaXNfYWN0aXZlIjoiWSIsImJhdGNoX25vIjoiTTIwMTUwMjE2IiwiY3JlYXRlX3RpbWUiOiIyMDE1LTAyLTE2IiwidXBkYXRlX3RpbWUiOiIyMDE1LTAyLTE2IDE3OjE1OjU0IiwiZnJvbV9uYXRpb24iOiJDTiIsInRvX25hdGlvbiI6IkZSIiwibWFya19oYW5kbGVkIjoiTiIsIm5vdGlmaWVkX3NlbGxlciI6Ik4iLCJub3RpZmllZF9idXllciI6Ik4iLCJzaGlwX2J5IjoiREhMIiwiZGVsaXZlcnlfZmVlIjoiMjAuMTI3MCIsInNoaXBfb3V0X2RhdGUiOiIyMDE1LTAyLTAyIiwidG90YWxfZGF5cyI6IjciLCJhbGxfZXZlbnQiOiJbe1wid2hlblwiOlwiMjAxNS0wMi0xMyAwMDowMFwiLFwid2hlcmVcIjpcIlwiLFwid2hhdFwiOlwiRGlzdHJpYnVcXHUwMGU5IE5BTlRFUlJFIFBEQzEgKDkyKS5cIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMyAwMDowMFwiLFwid2hlcmVcIjpcIlwiLFwid2hhdFwiOlwiRW4gY291cnMgZGUgdHJhaXRlbWVudCBcXHUwMGUwIE5BTlRFUlJFIFBEQzEgKDkyKS5cIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMSAwMDowMFwiLFwid2hlcmVcIjpcIlwiLFwid2hhdFwiOlwiQXJyaXZcXHUwMGU5ZSBlbiBGcmFuY2UgQ0hJTkUgKFJFUC4gUE9QKSAoQ04pLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA3IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJEXFx1MDBlOXBhcnQgcGF5cyBkJ29yaWdpbmUgQ0hJTkUgKFJFUC4gUE9QKSAoQ04pLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA4IDEwOjU2XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NzliYlxcdTVmMDBcXHU0ZWE0XFx1ODIyYVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA4IDA4OjI1XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NTIzMFxcdThmYmVcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNyAxMjoyN1wiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNyAxMjowNlwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWYwMFxcdTYyYzZcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNiAxMTo0M1wiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTVjMDFcXHU1M2QxXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDYgMTE6NDNcIixcIndoZXJlXCI6XCJcXHU1ZTdmXFx1NTU0NlxcdTU2ZmRcXHU5NjQ1XFx1NWMwZlxcdTUzMDVcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU2NTM2XFx1NWJjNFwifV0iLCJhZGRpX2luZm8iOm51bGx9LHsiaWQiOiI0Iiwib3JkZXJfaWQiOiJcdTc5M2FcdTgzMDNcdThiYTJcdTUzNTU5ODkzOUNZSjlHIiwidHJhY2tfbm8iOiJcdTc5M2FcdTgzMDNSSDE0NjU4ODMzOUNOIiwic3RhdHVzIjoiXHU4ZmQwXHU4ZjkzXHU5MDE0XHU0ZTJkIiwic3RhdGUiOiJcdTZiNjNcdTVlMzgiLCJzb3VyY2UiOiJNIiwicGxhdGZvcm0iOm51bGwsInBhcmNlbF90eXBlIjoiMSIsImlzX2FjdGl2ZSI6IlkiLCJiYXRjaF9ubyI6Ik0yMDE1MDIxNiIsImNyZWF0ZV90aW1lIjoiMjAxNS0wMi0xNiIsInVwZGF0ZV90aW1lIjoiMjAxNS0wMi0xNiAxNzoxNTozNCIsImZyb21fbmF0aW9uIjoiQ04iLCJ0b19uYXRpb24iOiJGUiIsIm1hcmtfaGFuZGxlZCI6Ik4iLCJub3RpZmllZF9zZWxsZXIiOiJOIiwibm90aWZpZWRfYnV5ZXIiOiJOIiwic2hpcF9ieSI6IkRITCIsImRlbGl2ZXJ5X2ZlZSI6IjE0LjE1NDAiLCJzaGlwX291dF9kYXRlIjoiMjAxNS0wMi0wMiIsInRvdGFsX2RheXMiOiItMSIsImFsbF9ldmVudCI6Ilt7XCJ3aGVuXCI6XCIyMDE1LTAyLTE2IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJFbiBjb3VycyBkZSB0cmFpdGVtZW50IFBFUklHVUVVWCBQREMxICgyNCkuXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMTQgMDA6MDBcIixcIndoZXJlXCI6XCJcIixcIndoYXRcIjpcIkVuIGF0dGVudGUgZGUgc2Vjb25kZSBwclxcdTAwZTlzZW50YXRpb24gXFx1MDBlMCBQRVJJR1VFVVggUERDMSAoMjQpLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTE0IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJFbiBjb3VycyBkZSB0cmFpdGVtZW50IFxcdTAwZTAgUEVSSUdVRVVYIFBEQzEgKDI0KS5cIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMCAwMDowMFwiLFwid2hlcmVcIjpcIlwiLFwid2hhdFwiOlwiQXJyaXZcXHUwMGU5ZSBlbiBGcmFuY2UgQ0hJTkUgKFJFUC4gUE9QKSAoQ04pLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA2IDA5OjMzXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NzliYlxcdTVmMDBcXHU0ZWE0XFx1ODIyYVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA2IDA5OjAwXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NTIzMFxcdThmYmVcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNSAwOTowM1wiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNSAwODo1MVwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWYwMFxcdTYyYzZcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNCAxMTo0NlwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTVjMDFcXHU1M2QxXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDQgMTE6NDZcIixcIndoZXJlXCI6XCJcXHU1ZTdmXFx1NTU0NlxcdTU2ZmRcXHU5NjQ1XFx1NWMwZlxcdTUzMDVcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU2NTM2XFx1NWJjNFwifV0iLCJhZGRpX2luZm8iOm51bGx9LHsiaWQiOiI1Iiwib3JkZXJfaWQiOiJcdTc5M2FcdTgzMDNcdThiYTJcdTUzNTU5MTEzMENZMktJIiwidHJhY2tfbm8iOiJcdTc5M2FcdTgzMDNSSDE0NjU1MTc4NENOIiwic3RhdHVzIjoiXHU4ZmQwXHU4ZjkzXHU5MDE0XHU0ZTJkIiwic3RhdGUiOiJcdTZiNjNcdTVlMzgiLCJzb3VyY2UiOiJNIiwicGxhdGZvcm0iOm51bGwsInBhcmNlbF90eXBlIjoiMSIsImlzX2FjdGl2ZSI6IlkiLCJiYXRjaF9ubyI6Ik0yMDE1MDIxNiIsImNyZWF0ZV90aW1lIjoiMjAxNS0wMi0xNiIsInVwZGF0ZV90aW1lIjoiMjAxNS0wMi0xNiAxNzoxNTo1MyIsImZyb21fbmF0aW9uIjoiQ04iLCJ0b19uYXRpb24iOiJGUiIsIm1hcmtfaGFuZGxlZCI6Ik4iLCJub3RpZmllZF9zZWxsZXIiOiJOIiwibm90aWZpZWRfYnV5ZXIiOiJOIiwic2hpcF9ieSI6IkRITCIsImRlbGl2ZXJ5X2ZlZSI6IjE0LjY5NzAiLCJzaGlwX291dF9kYXRlIjoiMjAxNS0wMi0wMiIsInRvdGFsX2RheXMiOiItMSIsImFsbF9ldmVudCI6Ilt7XCJ3aGVuXCI6XCIyMDE1LTAyLTEzIDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJBcnJpdlxcdTAwZTllIGVuIEZyYW5jZSBDSElORSAoUkVQLiBQT1ApIChDTikuXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDQgMDA6MDBcIixcIndoZXJlXCI6XCJcIixcIndoYXRcIjpcIkRcXHUwMGU5cGFydCBwYXlzIGQnb3JpZ2luZSBDSElORSAoUkVQLiBQT1ApIChDTikuXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDYgMTc6MzJcIixcIndoZXJlXCI6XCJcXHU0ZTBhXFx1NmQ3N1xcdTZkNjZcXHU0ZTFjXCIsXCJ3aGF0XCI6XCJcXHU3OWJiXFx1NWYwMFxcdTRlYTRcXHU4MjJhXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDQgMjI6NTlcIixcIndoZXJlXCI6XCJcXHU0ZTBhXFx1NmQ3N1xcdTZkNjZcXHU0ZTFjXCIsXCJ3aGF0XCI6XCJcXHU1MjMwXFx1OGZiZVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA0IDEwOjIwXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU0ZTkyXFx1NjM2MlxcdTVjNDBcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU1YzAxXFx1NTNkMVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA0IDEwOjA4XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU0ZTkyXFx1NjM2MlxcdTVjNDBcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU1ZjAwXFx1NjJjNlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTAzIDExOjA2XCIsXCJ3aGVyZVwiOlwiXFx1NWU3ZlxcdTU1NDZcXHU1NmZkXFx1OTY0NVxcdTVjMGZcXHU1MzA1XCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wMyAxMTowNlwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTY1MzZcXHU1YmM0XCJ9XSIsImFkZGlfaW5mbyI6bnVsbH0seyJpZCI6IjYiLCJvcmRlcl9pZCI6Ilx1NzkzYVx1ODMwM1x1OGJhMlx1NTM1NTkxMTMwQ1kyMjMiLCJ0cmFja19ubyI6Ilx1NzkzYVx1ODMwM1JIMTQ2NTUxNzc1Q04iLCJzdGF0dXMiOiJcdTYyMTBcdTUyOWZcdTdiN2VcdTY1MzYiLCJzdGF0ZSI6Ilx1NWRmMlx1NWI4Y1x1NjIxMCIsInNvdXJjZSI6Ik0iLCJwbGF0Zm9ybSI6bnVsbCwicGFyY2VsX3R5cGUiOiIxIiwiaXNfYWN0aXZlIjoiWSIsImJhdGNoX25vIjoiTTIwMTUwMjE2IiwiY3JlYXRlX3RpbWUiOiIyMDE1LTAyLTE2IiwidXBkYXRlX3RpbWUiOiIyMDE1LTAyLTE2IDE3OjE2OjAwIiwiZnJvbV9uYXRpb24iOiJDTiIsInRvX25hdGlvbiI6IkJFIiwibWFya19oYW5kbGVkIjoiTiIsIm5vdGlmaWVkX3NlbGxlciI6Ik4iLCJub3RpZmllZF9idXllciI6Ik4iLCJzaGlwX2J5IjoiNFBYIiwiZGVsaXZlcnlfZmVlIjoiMTQuNjk3MCIsInNoaXBfb3V0X2RhdGUiOiIyMDE1LTAyLTAzIiwidG90YWxfZGF5cyI6IjkiLCJhbGxfZXZlbnQiOiJbe1wid2hlblwiOlwiMjAxNS0wMi0xMSAxNzoxOFwiLFwid2hlcmVcIjpcIlBQIFNQQVIgUEVUSVQgV0FSRVRcIixcIndoYXRcIjpcIkl0ZW0gZGVsaXZlcmVkXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMTEgMDk6MjVcIixcIndoZXJlXCI6XCJQUCBTUEFSIFBFVElUIFdBUkVUXCIsXCJ3aGF0XCI6XCJBd2FpdGluZyBwaWNrIHVwIGJ5IGFkZHJlc3NlZVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTEwIDEyOjM5XCIsXCJ3aGVyZVwiOlwiQU5ERU5ORSBNQUlMXCIsXCJ3aGF0XCI6XCJJdGVtIHByZXNlbnRlZCA6IGFkZHJlc3NlZSBhYnNlbnQgLSBtZXNzYWdlIGxlZnQgaW4gYWRkcmVzc2VlJ3MgbGV0dGVyYm94XCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDkgMjI6MTFcIixcIndoZXJlXCI6XCJORVcgQ0hBUkxFUk9JIFhcIixcIndoYXRcIjpcIkl0ZW0gaGFzIGJlZW4gc29ydGVkXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDkgMDk6NTVcIixcIndoZXJlXCI6XCJCRUJSVUFcIixcIndoYXRcIjpcIkFycml2YWwgYXQgaW50ZXJuYXRpb25hbCBvZmZpY2Ugb2YgZXhjaGFuZ2VcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNSAwODowNlwiLFwid2hlcmVcIjpcIkNOU0hBQVwiLFwid2hhdFwiOlwiRGVwYXJ0dXJlIGZyb20gaW50ZXJuYXRpb25hbCBkZXBvdFwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA1IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NzliYlxcdTVmMDBcXHU0ZWE0XFx1ODIyYVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA0IDIyOjU5XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NTIzMFxcdThmYmVcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNCAwMTo1MVwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wMyAxOTo1OFwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWYwMFxcdTYyYzZcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wMyAxMTo1MVwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTVjMDFcXHU1M2QxXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDMgMTE6NTFcIixcIndoZXJlXCI6XCJcXHU1ZTdmXFx1NTU0NlxcdTU2ZmRcXHU5NjQ1XFx1NWMwZlxcdTUzMDVcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU2NTM2XFx1NWJjNFwifV0iLCJhZGRpX2luZm8iOm51bGx9LHsiaWQiOiI3Iiwib3JkZXJfaWQiOiJcdTc5M2FcdTgzMDNcdThiYTJcdTUzNTU5MTEzMENZMjIxIiwidHJhY2tfbm8iOiJcdTc5M2FcdTgzMDNSSDE0NjU1MTc2N0NOIiwic3RhdHVzIjoiXHU4ZmQwXHU4ZjkzXHU5MDE0XHU0ZTJkIiwic3RhdGUiOiJcdTZiNjNcdTVlMzgiLCJzb3VyY2UiOiJNIiwicGxhdGZvcm0iOm51bGwsInBhcmNlbF90eXBlIjoiMSIsImlzX2FjdGl2ZSI6IlkiLCJiYXRjaF9ubyI6Ik0yMDE1MDIxNiIsImNyZWF0ZV90aW1lIjoiMjAxNS0wMi0xNiIsInVwZGF0ZV90aW1lIjoiMjAxNS0wMi0xNiAxNzoxNjoxNiIsImZyb21fbmF0aW9uIjoiQ04iLCJ0b19uYXRpb24iOiJGUiIsIm1hcmtfaGFuZGxlZCI6Ik4iLCJub3RpZmllZF9zZWxsZXIiOiJOIiwibm90aWZpZWRfYnV5ZXIiOiJOIiwic2hpcF9ieSI6IjRQWCIsImRlbGl2ZXJ5X2ZlZSI6IjE0LjY5NzAiLCJzaGlwX291dF9kYXRlIjoiMjAxNS0wMi0wNCIsInRvdGFsX2RheXMiOiItMSIsImFsbF9ldmVudCI6Ilt7XCJ3aGVuXCI6XCIyMDE1LTAyLTE2IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJFbiBjb3VycyBkZSB0cmFpdGVtZW50IFlaRVVSRSBNT1VMSU5TIFBQREMgKDAzKS5cIn0se1wid2hlblwiOlwiMjAxNS0wMi0xMyAwMDowMFwiLFwid2hlcmVcIjpcIlwiLFwid2hhdFwiOlwiQXJyaXZcXHUwMGU5ZSBlbiBGcmFuY2UgQ0hJTkUgKFJFUC4gUE9QKSAoQ04pLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA0IDAwOjAwXCIsXCJ3aGVyZVwiOlwiXCIsXCJ3aGF0XCI6XCJEXFx1MDBlOXBhcnQgcGF5cyBkJ29yaWdpbmUgQ0hJTkUgKFJFUC4gUE9QKSAoQ04pLlwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA2IDE3OjMyXCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NzliYlxcdTVmMDBcXHU0ZWE0XFx1ODIyYVwifSx7XCJ3aGVuXCI6XCIyMDE1LTAyLTA0IDIyOjU5XCIsXCJ3aGVyZVwiOlwiXFx1NGUwYVxcdTZkNzdcXHU2ZDY2XFx1NGUxY1wiLFwid2hhdFwiOlwiXFx1NTIzMFxcdThmYmVcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNCAxMDoyMFwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWMwMVxcdTUzZDFcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wNCAxMDowOFwiLFwid2hlcmVcIjpcIlxcdTRlMGFcXHU2ZDc3XFx1NGU5MlxcdTYzNjJcXHU1YzQwXCIsXCJ3aGF0XCI6XCJcXHU1ZGYyXFx1NWYwMFxcdTYyYzZcIn0se1wid2hlblwiOlwiMjAxNS0wMi0wMyAxMTowNlwiLFwid2hlcmVcIjpcIlxcdTVlN2ZcXHU1NTQ2XFx1NTZmZFxcdTk2NDVcXHU1YzBmXFx1NTMwNVwiLFwid2hhdFwiOlwiXFx1NWRmMlxcdTVjMDFcXHU1M2QxXCJ9LHtcIndoZW5cIjpcIjIwMTUtMDItMDMgMTE6MDZcIixcIndoZXJlXCI6XCJcXHU1ZTdmXFx1NTU0NlxcdTU2ZmRcXHU5NjQ1XFx1NWMwZlxcdTUzMDVcIixcIndoYXRcIjpcIlxcdTVkZjJcXHU2NTM2XFx1NWJjNFwifV0iLCJhZGRpX2luZm8iOm51bGx9XX0=';  	
}
?>