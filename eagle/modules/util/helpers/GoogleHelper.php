<?php
namespace eagle\modules\util\helpers;
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
use yii;
use eagle\modules\tracking\helpers\HttpHelper;
use eagle\modules\util\models\TranslateCache;

class GoogleHelper {
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Google Translate的结果进行快递行业的术语替换，提高翻译质量
	 +---------------------------------------------------------------------------------------------
	 *
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  text                 原来的文字
	 * @param  fromLang				原来的语言，例如 fr,en,zh-cn
	 * @param  toLang               要转化成的语言，例如 fr,en,zh-cn
	 * 中文 大陆：zh-cn
	 * 中文 台湾：zh-tw
	 * 中文 香港： zh-hk 
	 +---------------------------------------------------------------------------------------------
	 * @return						翻译后，使用物流行业匹配优化后的文字.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function google_translate($text,$fromLang,$toLang='zh-cn' ){
		$text = trim($text);
		if (empty($text))
			return "";
		
		//step 0, replace the ";",":" with ","
		$text = str_replace(";",",",$text );
		$text = str_replace(".",",",$text );
		$text = str_replace("，",",",$text );
		
		$lines = explode(",", $text);
		$new_lines = array();
		foreach ($lines as $aLine){
			//Step 1, translate,这样cache里面就不带变化数字的纯 语句，有利于减少cache容量以及提高cache命中率
			$new_lines[] = self::google_translate_one_line($aLine, $fromLang, $toLang);
		}
		return implode(",", $new_lines);
	}//end of call google

	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Google Translate的结果进行快递行业的术语替换，提高翻译质量
	 +---------------------------------------------------------------------------------------------
	 *
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  text                 原来的文字
	 * @param  fromLang				原来的语言，例如 fr,en,zh-cn
	 * @param  toLang               要转化成的语言，例如 fr,en,zh-cn
	 * 中文 大陆：zh-cn
	 * 中文 台湾：zh-tw
	 * 中文 香港： zh-hk
	 +---------------------------------------------------------------------------------------------
	 * @return						翻译后，使用物流行业匹配优化后的文字.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function google_translate_one_line($text,$fromLang,$toLang='zh-cn'){
		global $CACHE;
		$text = trim($text);
		if (empty($text))
			return "";
		
		//step 0.1, 把原文变成pattern
		$text_masked = $text;
		//step 1, 提取 aLine 里面的数字，换成特定的数字，用来等一会替换
		$digit_arr = array();
		preg_match_all('/\d+/',$text_masked,$digit_arr);
			
		$digit_arr = isset($digit_arr[0])?$digit_arr[0]:array();
		//从数字大的开始替换，否则会吧小的重复替换掉的,e.g. "1-19-1900-9-09"		
		//为了确保先做 09 的替换，再做9的替换，需要排序让 09 在 9 的前面
		//吧“9” 和 "09" 变成 "x     9" 和 "x    09" ， 大家总长度一样，那么 "x    09" 应该会在前面了
		$ind = 0;
		$mask_to_digit = array();
		 
		foreach ($digit_arr as $aDigit){
			$mask = "#".chr( 65 + $ind )."#";
			$text_masked = self::str_replace_once (""."$aDigit","$mask",$text_masked);
			$mask_to_digit["$mask"] = $aDigit;
			$ind ++;
		}
	//	echo "$text = > $text_masked <br>";
		$masked_result ='';
		//step 1, check if there is RAM cache, translated before
		if (isset($CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked]))
			$masked_result = $CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked];
		
		//step 1, check if there is DataBase cache, translated before
		if ($masked_result == ''){
			$aTranslateCache = TranslateCache::find()->andWhere("from_lang=:from_lang and to_lang=:to_lang and input_text=:text",
				array(":from_lang"=>$fromLang,":to_lang"=>$toLang,":text"=>$text_masked, ))->one();
		
			if ($aTranslateCache !== null){
				$CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked] =self::trans_enhancement( $aTranslateCache->text_out , $toLang) ;
				$masked_result = $CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked];
			}
		}
		
		//step 3, call google proxy to translate now
		if ($masked_result ==''){
		$text1 = urlencode($text);
		$fromLang1 = urlencode($fromLang);
		$toLang1 = urlencode($toLang);
		$resultStr = '';
		$expected_token ="HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA";
		$url = "http://5.9.7.104/google_proxy/ApiEntry.php?token=$expected_token&text=$text1&fromLang=$fromLang1&toLang=$toLang1";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$resultStr = curl_exec($curl);
		//$resultStr = curl_getinfo($curl);
		curl_close($curl);
		
		$proxy_return = json_decode($resultStr,true);
		
		if (isset($proxy_return['translated']))
			$proxy_return['translated'] = base64_decode($proxy_return['translated']);
		
		if (!isset($proxy_return['translated']))
			$proxy_return['translated'] = 'N/A';
		
		$aTranslateCache = new TranslateCache();
		$aTranslateCache->from_lang = $fromLang;
		$aTranslateCache->to_lang = $toLang;
		$aTranslateCache->input_text = $text_masked;
		$aTranslateCache->text_out = $proxy_return['translated'];
		
		//step 3.1, mask the result before writing to db, for further use
		foreach ($mask_to_digit as $mask=>$digit){
			$aTranslateCache->text_out = self::str_replace_once("$digit","$mask",$aTranslateCache->text_out);
		}
		
		//step 3.2: save the data to cache table
		if ( $aTranslateCache->save() ){//save successfull
		}else{
			$message = TranslateHelper::t("EGHP001 保存google translate结果失败") ;
			foreach ($aTranslateCache->errors as $k => $anError){
				$message .=   ($message==""?"":"<br>"). $k.":".$anError[0];
			}
			\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
		}//end of save failed
		
		$CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked] = self::trans_enhancement( $aTranslateCache->text_out , $toLang) ;
		}//end of using google translate to get and save masked result
		
		$masked_result = $CACHE['GoogleHelper']['trans_result'][$fromLang][$toLang][$text_masked];
		
		//step final, 把masked 的结果改成没有masked 的digits
		$unmasked_result = $masked_result;
		foreach ($mask_to_digit as $mask=>$digit){
			$unmasked_result = self::str_replace_once("$mask","$digit",$unmasked_result);
		}
		
		return $unmasked_result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Google Translate的结果进行快递行业的术语替换，提高翻译质量
	 +---------------------------------------------------------------------------------------------
	
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  text                 Google翻译后的文字
	 * @param  lang					使用的语言，如果该语言没有定义好术语匹配，则不会进行任何匹配替换
	 +---------------------------------------------------------------------------------------------
	 * @return						匹配优化后的文字
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function trans_enhancement($text,$lang='zh-cn'){
		$word_mapping['zh-cn'] = array(
				'受援国'=>'收件人',
				'试图'=>'尝试',
				'随着'=>'正在',
				'送货'=>'递送',
				'设施'=>'中转站',
				'设备'=>'中转站',
				'仪器'=>'中转站',
				'清仓'=>'清点',
				'排序'=>'分拣',
				'本地'=>'当地',				
				'行者'=>'离开',
				'基金'=>'中转站',
				'拿起'=>'收件',
				'收集'=>'取件',
				'REP 。持久性有机污染物'=>'REP. POP',
				'分布式'=>'递送',
				'原产'=>'来源',
				'始发后'=>'承运物流商',
				'这种'=>'这个',				
				'启动'=>'离开',
				'预期'=>'等待',
				'作者'=>'递送站',
				'计数器'=>'处理中心',
				'除去'=>'取件',
				'倍'=>'文件',
				'因素'=>'中转站',
				'工厂'=>'中转站',
				'加工'=>'在处理',
				'发货'=>'递送',
				'搁置'=>'暂停',
				'邀请'=>'处理',
				'在车厂'=>'当地代理处',
				'位置'=>'中转站',			
				'没有收到'=>'未能签收',
				'出货联系'=>'联系发货人',
				'货单'=>'货件',
				'出身'=>'来源',
				'完整'=>'完成',
				'完全'=>'完成',
				'交付'=>'递送',
				'张贴'=>'递送',
				'活动'=>'处理',
				'通关'=>'清关',
				'投递库'=>'中转站',
				'车辆'=>'车队',
				'约定'=>'日程',
				'预通知'=>'入关预约',
				'项目'=>'包裹',
				'项'=>'包裹', //这个需要在“项目” 的下面，按照优先顺序来做mapping
				'发布'=>'到达/通行',
				'车厂'=>'中转站',
				'实现'=>'成功',
				'接收器'=>'收件人',
				'评论'=>'备注',
				'网关'=>'关口',
				'发送'=>'递送',
				'支持'=>'承运',
				'交货'=>'递送',
				'进度'=>'进行中',
				'数量'=>'编码',
				'回报'=>'返回',
				'对内'=>'入境',
				'入站'=>'入境',
				'集线器'=>'集中处',
				'整合'=>'合并',
				'接机'=>'收件',
				'清除'=>'清',
				'运营'=>'操作',
				'团结'=>'联盟',
				'机构'=>'中转站',
				'传递'=>'递送',
				'机制'=>'机器',
				'派遣'=>'递送',
				'非限定'=>'未命名',
				'接受'=>'收件',
				'公布'=>'放行',
				'接待'=>'接收',
				'传送'=>'递送',
				'发帖'=>'递送',
				'收藏'=>'收件',
				'起源于'=>'来源地',
				'产地'=>'来源地',	
				'更衣室'=>'储物柜',
				'承诺'=>'派送成功',
				'收据'=>'收件',
				'换取'=>'转运',
				'办公'=>'处理站',
				'经营'=>'操作',
				'一块'=>'包裹',
				'单位'=>'中转站',
				'赶到'=>'到达',
				'代码'=>'物流号',
				'对象'=>'包裹', //object
				'对'=>'有关',  //这个必须在 “对象” 之后
				'回升'=>'收件',
				'注册'=>'挂号',
				'单元'=>'中转站',
				'监护室'=>'中转站',
				'处理厂'=>'中转站',
				'后'=>'邮政',
				'拾起'=>'收件',
				'起源'=>'来源',
				'室'=>'机构',
				'交换'=>'中转',
				'左'=>'离开了',
				'内向'=>'内部',
				'外向'=>'外部',
				'people_s代表'=>'中华人民共和国',
				'密切'=>'关闭',
				'藏'=>'仓库',
				'参考'=>'号码',
				'他'=>'意大利',
				'她'=>'意大利',
				'它'=>'意大利',
				'顺序'=>'订单',
				'参照'=>'编号',
				
		);
		
		$word_mapping2['zh-cn'] = array(
				'国家物流号'=>'国家代码',
				);
		
		if (!isset($word_mapping[$lang]))
			return $text;
		
		foreach ($word_mapping[$lang] as $word => $replacement){
			$text = str_replace($word,$replacement,$text);
		}//end of each word mapping
		
		foreach ($word_mapping2[$lang] as $word => $replacement){
			$text = str_replace($word,$replacement,$text);
		}//end of each word mapping
		return $text;
	}
	
	
	public static function str_replace_once($needle, $replace, $haystack) {
		// Looks for the first occurence of $needle in $haystack
		// and replaces it with $replace.
		$pos = strpos($haystack, $needle);
		if ($pos === false) {
			return $haystack;
		}
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	}
	
}

 



?>