<?php
namespace common\api\ebayinterface;

use Exception;
use common\api\ebayinterface\base;
use eagle\modules\customer\models\EbayMymessage;
use eagle\modules\customer\helpers\EbayMyMessageHelper;
/**
 * 获得站内信列表
 * @package interface.ebay.tradingapi
 */
class getmymessages extends base{
	public $verb='GetMyMessages';

	public $EntriesPerPage=null;
	public $PageNumber=null;
	public $StartTime=null;
	public $EndTime=null;
	public $DetailLevel='ReturnMessages';
	public $MessageID=null;
	public $FolderID=null;

	/**
	 * api
	 *
	 * @param Ebay_User $ebay_user
	 * @param int $startTime
	 * @param int $endTime
	 * @param int $page
	 */
	public function api(){
		$xmlArr=array();
		if($this->DetailLevel){
			$xmlArr['DetailLevel']=$this->DetailLevel;
		}
		if ($this->StartTime){
			$xmlArr['StartTime']=self::dateTime($this->StartTime);
			$xmlArr['EndTime']=self::dateTime($this->EndTime);
		}
		if ($this->EntriesPerPage){
			$xmlArr['Pagination']=array(
				'EntriesPerPage'=>$this->EntriesPerPage,
				'PageNumber'=>$this->PageNumber,
			);
		}
		if (strlen($this->MessageID)){
			$xmlArr['MessageIDs']['MessageID']=$this->MessageID;
		}
		if($this->FolderID){
			$xmlArr['FolderID'] = $this->FolderID;
		}
		
		return $this->setRequestBody($xmlArr)->sendRequest();
	}


	/**
	 * 请求同步
	 * @param unknown_type $eu
	 * @param unknown_type $ModTimeFrom
	 * @param unknown_type $ModTimeTo
	 * @author lxqun
	 * @date 2014-3-16
	 */
	static function cronRequest($eu,$StartTime,$EndTime){
		
 
		$api=new self();
		$api->resetConfig($eu->DevAcccountID);
		$api->eBayAuthToken=$eu->token;
		$api->StartTime=$StartTime;
		$api->EndTime=$EndTime;
		$api->DetailLevel='ReturnHeaders';
		$api->EntriesPerPage=50;
		$api->PageNumber=1;
		try{
			do{
				$responseArr=$api->api();

				if($api->responseIsFailure()){  //接口失败 退出
					break;
				}

				//保存 全部的message,只是 检查messageid 信息. 
				$messageids=array();
				if (isset($responseArr['Messages']['Message']['MessageID'])){
					$responseArr['Messages']['Message']=array($responseArr['Messages']['Message']);
				}
				//没有条目
				if(!isset($responseArr['Messages']['Message'])) {
					return true;
					break;
				}
				
				foreach ($responseArr['Messages']['Message'] as $msgNode){
					$emsg=EbayMymessage::findOne(['messageid'=>$msgNode['MessageID']]);
					if (empty($emsg)){
						array_push($messageids,$msgNode['MessageID']);
					}else {
						$emsg->replied=$msgNode['Replied']=='true'?1:0;
						$emsg->is_read=$msgNode['Read']=='true'|| $emsg->is_read?1:0;
						$emsg->save();
					}
				}
				//需要新同步进来的 message 
				foreach ($messageids as $messageid){
					self::cronRequestOne($eu,$messageid);
				}

				// 翻页 及退出
				//if($responseArr['PageNumber']>=$responseArr['PaginationResult']['TotalNumberOfPages']){
				//      break 1;
				//}else{
				$api->PageNumber++;
				//}

			}while(1);

		}catch(Exception $ex){
			echo "Error Message :  ". $ex->getMessage();
		}
		return false;
	}

	/**
	 * 取得 一条message 
	 * @param unknown_type $eu
	 * @param unknown_type $messageid
	 * @param int	$FolderID	0:inBox，1:sendBox
	 * @author lxqun
	 * @date 2014-3-23
	 */
	static function cronRequestOne($eu,$messageid,$FolderID=0){
		try{
			$api=new self();
			$api->resetConfig($eu->DevAcccountID);
			$api->eBayAuthToken=$eu->token;
			$api->DetailLevel='ReturnMessages';
			$api->FolderID=$FolderID;
			$api->MessageID= $messageid;
			$responseArr=$api->api();
			if (!$api->responseIsFailure()){
				if (isset($responseArr['Messages']['Message']['MessageID'])){
					$responseArr['Messages']['Message']=array($responseArr['Messages']['Message']);
				}
				//没有条目
				if(!isset($responseArr['Messages']['Message'])) return ;
				foreach($responseArr['Messages']['Message'] as $responseArr_i){
					EbayMyMessageHelper::apiSave($responseArr_i, $eu);
				}

			}else{
				throw new Exception('requset message :'.$messageid.' Failure.');
			}
		}catch(Exception $ex){
			\Yii::info($ex->getMessage());
		}
	}
	
	/**
	 * 取得 一条message , 添加 return 通知接口调用结果
	 * @param SaasEbayUser $eu
	 * @param int $messageid
	 * @param int	$FolderID	0:inBox，1:sendBox
	 * @author dzt 2015-07-22
	 */
	static function cronRequestOne2($eu,$messageid,$FolderID=0){
		try{
			$api = new self();
			$api->resetConfig($eu->DevAcccountID);
			$api->eBayAuthToken = $eu->token;
			$api->DetailLevel = 'ReturnMessages';
			$api->FolderID=$FolderID;
			$api->MessageID = $messageid;
			$responseArr = $api->api();
			if (!$api->responseIsFailure()){
				if (isset($responseArr['Messages']['Message']['MessageID'])){
					$responseArr['Messages']['Message']=array($responseArr['Messages']['Message']);
				}
				//没有条目
				if(!isset($responseArr['Messages']['Message'])) 
					return array('success'=>false , 'error_message'=>"api return no info for messageid:$messageid ."."selleruserid:".$eu->selleruserid."uid:".$eu->uid);
				
				// 因为每次都只拿一条message detail所以 确认是 只返回 $responseArr['Messages']['Message'][0] 的
				return array('success'=>true , 'error_message'=>'' , 'return_messages'=>$responseArr['Messages']['Message'][0]);
			}else{
				throw new Exception('requset message :'.$messageid.' Failure.');
			}
		}catch(Exception $ex){
			\Yii::info($ex->getMessage());
			return array('success'=>false , 'error_message'=>print_r($ex,true));
		}
	}
}
?>
