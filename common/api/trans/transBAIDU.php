<?php
namespace common\api\trans;

use common\helpers\SubmitGate;
use eagle\models\EbayCategory;
class transBAIDU{
	
	private $submitGate = null;
	static private $APP_ID = "20151230000008425";
	static private $SEC_KEY = "iGk0Uf7EIX_b9DlN2pTn";
	static private $api = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
	
	public function __construct(){
	
		$this->submitGate = new SubmitGate();
	}
	/**
	 * 百度翻译查询函数
	 * @param $query =>'apple'	需要翻译的字符串
	 * @param $from =>'auto'	(语言列表：http://api.fanyi.baidu.com/api/trans/product/apidoc?qq-pf-to=pcqq.discussion)
	 * @param $to =>'zh'	<不可为auto>(语言列表：http://api.fanyi.baidu.com/api/trans/product/apidoc?qq-pf-to=pcqq.discussion)
	 *
	 * @return 
	 * 	Array ( [response] => Array ( 
	 * 							[code] => 0 		//	0（成功）/ 1(失败)
	 * 							[msg] => 超时		//	  失败原因
	 * 							[data] => 苹果			//	翻译结果
	 * 						) 
	 * )
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/30				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	static public function translate($query, $from, $to)
	{
		$words = self::splitWord($query);
		if(!isset($words['key'])){
			return self::translateOne($query,$from,$to);
		}
		foreach($words['key'] as $key){
			$q = $words['data'][$key];
			$res = self::translateOne($q,$from,$to);
			if($res['response']['code'] == 1){
				return $res;
			}
			else{
				$to_word = $res['response']['data'];
				if(empty($to_word)){
					$preg = "/([\s]+)/";
					$arr = preg_split($preg, $q, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
					foreach($arr as $one2){
						$one2 = trim($one2);
						if(!empty($one2)){
							$res2 = self::translateOne($one2,$from,$to);
							if($res2['response']['code'] == 1){
								return $res;
							}
							else{
								$to_word .= $res2['response']['data'];
							}
						}
					}
				}
				$words['data'][$key] = $to_word;
			}
		}
		$word = '';
		foreach ($words['data'] as $data){
			$word .= $data;
		}
		return self::output($word,0,'翻译成功');
	}
	static public function translateOne($one,$from,$to){
		$submitGate = new SubmitGate();
		$salt = rand(10000,99999);
		$sign = md5(self::$APP_ID.$one.$salt.self::$SEC_KEY);
		$api = self::$api.'q='.$one.'&from='.$from.'&to='.$to.'&appid='.self::$APP_ID.'&salt='.$salt.'&sign='.$sign;
		$response = $submitGate->mainGate($api, '', 'curl', 'GET');
		
		if($response['error']){return $response;}
		$response = json_decode($response['data'], true);
		$msg = "翻译过程出错：";
		if(isset($response['error_code'])){
			switch ($response['error_code']){
				case '52000':$msg='成功';break;
				case '52001':$msg='请求超时';break;
				case '52002':$msg='系统错误';break;
				case '52003':$msg='未授权用户';break;
				case '54000':$msg='必填参数为空';break;
				case '58000':$msg='客户端IP非法';break;
				case '54001':$msg='签名错误';break;
				case '54003':$msg='访问频率受限';break;
				case '58001':$msg='译文语言方向不支持';break;
				case '54004':$msg='账户余额不足';break;
				case '54005':$msg='长query请求频繁';break;
			}
			$msg.='('.$response['error_msg'].')';
			return self::output('',1,$msg);
		}
		else{
			return self::output($response['trans_result'][0]['dst'],0,'翻译成功');
		}
	}
	protected static function output($data, $code = 0, $msg = '') {
		$output = ['response'=>['code'=>$code, 'msg'=>$msg, 'data'=>$data]];
		return $output;
	}
	//根据特殊字符拆分单词
	protected static function splitWord($str){
		$preg = "/([-\+&~!@#\$%\^\(\)\[\]\{\}\*\|\\:;".'"'."'<>\?,.`]+)/";
		$arr = preg_split($preg, $str, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE); 
		$word = [];
		$i = 0;
		foreach ($arr as $s){
			$ss = trim($s);
			if (!empty($ss)  && !is_numeric($ss[0])){
				$word[] = $i;
				$arr[$i] = $ss;
			}
			$i++;
		}
		return ['data'=>$arr,'key'=>$word];
	}
	
	static public function getEbayCNameByID($id){
		$e = EbayCategory::find()->select(['id','name','name_zh'])->andWhere(['id'=>$id])->one();
		$res = self::translate($e->name, 'auto', 'zh')['response'];
		if($res['code'] === 0){
			$e->name_zh = $res['msg'];
			$e->save();
			return self::output(['name_zh'=>$e->name_zh],0,'保存成功');
		}
		else{
			return self::output('',1,'保存失败',$res['msg']);
		}
	}
}
?>