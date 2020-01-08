<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use console\helpers\QueueGetorderHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;

/**
 * 获得指定eBay用户的Transaction列表
 * @package interface.ebay.tradingapi
 *
 */
class getsellertransactions extends base{

	//从ebay获取相应itemid的信息
	public $verb = 'GetSellerTransactions';

	public $EntriesPerPage=null;
	public $PageNumber=null;
	public $ModTimeFrom=null;
	public $ModTimeTo=null;
	public $NumberOfDays=null;

	public function api(){

		$xmlArr=array(
			'DetailLevel'=>'ReturnAll',
			'IncludeContainingOrder'=>true,
		);
		if ($this->NumberOfDays){
			$xmlArr['NumberOfDays']=$this->NumberOfDays;
		}
		if ($this->ModTimeFrom){
			$xmlArr['ModTimeFrom']=$this->ModTimeFrom;
			$xmlArr['ModTimeTo']=$this->ModTimeTo;
		}
		if ($this->EntriesPerPage){
			$xmlArr['Pagination']=array(
				'EntriesPerPage'=>$this->EntriesPerPage,
				'PageNumber'=>$this->PageNumber,
			);
		}

		if(isset($this->_before_request_xmlarray['OutputSelector'])){
			unset($xmlArr['DetailLevel']);
			$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
		}
		$r=$this->setRequestBody($xmlArr)->sendRequest();
		return $r;
	}
	

	/**
	 * 将所有订单插入到 订单队列表中
	 * @param  $eu  Ebay_User , Array() .
	 * 
	 */
	static function cronInsertIntoQueueGetOrder($eu,$NumberOfDays=0,$ModTimeFrom=0,$ModTimeTo=0,$externalid=0 , $default_status=0){
		set_time_limit(0);
		$api=new GetSellerTransactions;
		$api->resetConfig($eu['DevAcccountID']);
		$api->eBayAuthToken=$eu['token'];
		$api->EntriesPerPage=50;
		$api->PageNumber=1;
		$api->_before_request_xmlarray['OutputSelector']=array(
			'TransactionArray.Transaction.ContainingOrder.OrderID',
			'TransactionArray.Transaction.TransactionID',
			'TransactionArray.Transaction.Item.ItemID',
			'TransactionArray.Transaction.PayPalEmailAddress',
			'PageNumber',
			'PaginationResult.TotalNumberOfPages',
			'PaginationResult.TotalNumberOfEntries',
		);
		do{
			if($ModTimeFrom){
				if(empty($ModTimeTo)){
					$ModTimeTo=time();
				}
				$api->ModTimeFrom =base::dateTime($ModTimeFrom);
				$api->ModTimeTo =base::dateTime($ModTimeTo);
			}
			if(!empty($NumberOfDays)){
				$api->NumberOfDays=$NumberOfDays;
			}
			$api->api();

			if(! $api->responseIsFailure()){
				echo "\n api request success!";
				$requestArr=$api->_last_response_xmlarray;
				echo "\n ".(__function__)." v1.6 puid=".@$eu['uid'].",selleruserid=".@$eu['selleruserid']." api request ".@$requestArr['Ack']."!"." ModTimeFrom=".@$api->ModTimeFrom." ModTimeTo=".@$api->ModTimeTo;
				//记录 请求 结果
				echo PHP_EOL . __METHOD__ .' -- _last_response_xmlarray:'.json_encode($requestArr).PHP_EOL;
// 				\Yii::info(print_r($requestArr,1) . '   '. __METHOD__ .' -- _last_response_xmlarray');
				$orderids=array();
				$orderlineitemids=array();
				if(isset($requestArr['TransactionArray']['Transaction']['TransactionID'])){
					$requestArr['TransactionArray']['Transaction']=array(
						$requestArr['TransactionArray']['Transaction']
					);
				}

				
				$PayPalEmailAddress_s=array();
				//取得所有
				if(isset($requestArr['TransactionArray']) && $requestArr['TransactionArray']['Transaction'])
					foreach($requestArr['TransactionArray']['Transaction'] as $T){
						if (isset($T['TransactionID'])){
							$OrderLineItemID= $T['Item']['ItemID'].'-'.$T['TransactionID'];
							$orderlineitemids[$OrderLineItemID]=$OrderLineItemID;
							$orderids[$T['ContainingOrder']['OrderID']] =$T['ContainingOrder']['OrderID'];
							if(isset($T['PayPalEmailAddress'])){
								$PayPalEmailAddress_s[$OrderLineItemID]=$T['PayPalEmailAddress'];
							}
							
							echo PHP_EOL.(__FUNCTION__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']." ItemID-TransactionID=".$OrderLineItemID;
						}else{
							/**/
							$OrderLineItemID= $T['Item']['ItemID'];
							$orderlineitemids[$OrderLineItemID]=$OrderLineItemID;
							$orderids[$T['ContainingOrder']['OrderID']] =$T['ContainingOrder']['OrderID'];
							if(isset($T['PayPalEmailAddress'])){
								$PayPalEmailAddress_s[$OrderLineItemID]=$T['PayPalEmailAddress'];
							}
							
							echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']." no TransactionID and result=".json_encode($T);
						}
					}

				if(count($orderlineitemids)){
					foreach($orderlineitemids as $orderid){
						if(isset($PayPalEmailAddress_s[$orderid])){
							$PayPalEmailAddress=$PayPalEmailAddress_s[$orderid];
						}else{
							$PayPalEmailAddress=null;
						}
						QueueGetorderHelper::Add($orderid,$eu['selleruserid'],$eu['selleruserid'],$externalid ,$PayPalEmailAddress,$default_status);
					}
				}

				if($requestArr['PageNumber']>=$requestArr['PaginationResult']['TotalNumberOfPages']){
					echo "\n current page number is ".$requestArr['PageNumber']." and TotalNumberOfPages=".$requestArr['PaginationResult']['TotalNumberOfPages'];
					if (strtolower($requestArr['Ack']) == 'success'){
						echo "\n  all request completed,will return true;";
						return true;
					}else{
						if (isset($requestArr['Errors']['ErrorCode'] )){
							if($requestArr['Errors']['ErrorCode'] == '21918011'){
								echo "\n  all request completed,will return true;";
								//能正常拉取订单的 warning
								return true;
							}else{
								echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']." short message:".$requestArr['Errors']['ShortMessage']." and error code = ".$requestArr['Errors']['ErrorCode'];
								print_r($requestArr);
							}
						}else{
							echo "\n ".$requestArr['Ack']."no  error code !";
							print_r($requestArr);
						}
						
					}
					
					break 1;
				}else{
					$api->PageNumber=$requestArr['PageNumber']+1;
					echo "\n page +1 ".$api->PageNumber." and TotalNumberOfPages is ".$requestArr['PaginationResult']['TotalNumberOfPages'];
					//$api->EntriesPerPage=$requestArr['PaginationResult']['TotalNumberOfEntries'];
				}
				
				if (($eu['sync_order_retry_count'] > 0) || (!empty($eu['error_message'])) ){
					$effect = SaasEbayUser::updateAll(['sync_order_retry_count'=>0,'error_message'=>''],['selleruserid'=>$eu['selleruserid']]);
					echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']."  set ebay user sync_order_retry_count = 0  and error_message=".$eu['error_message']." =>'' efftct=".$effect." and retry count=".$eu['sync_order_retry_count'];
				}
				
			}else{
				echo "\n E001 api request failure!";
				if (!empty($api->_last_response_xmlarray)){
					$requestArr = $api->_last_response_xmlarray;
					echo "\n  this request ack ".@$requestArr['Ack'] ."!";
					try {
						/* 以下五种错误需要将同步 关闭
						 * 841 = Requested user is suspended.
						 * 931 = Auth token is invalid.
						 * 932 = Auth token is hard expired.
						* 16110 = Token has been revoked by the user.
						* 17470 = Please login again now. Your security token has expired.
						* 163  Inactive application or developer 开发者账号问题
						*/
						if ($requestArr['Ack'] =='Failure' && in_array($requestArr['Errors']['ErrorCode'] , ['841','931','932' , '16110','17470','163']) ){
							//20171019 加入重试10次的机制
							if ($eu['sync_order_retry_count']>10){
								$effect = SaasEbayAutosyncstatus::updateAll(['status'=>0],['selleruserid'=>$eu['selleruserid']]);
								echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']." ".$requestArr['Errors']['ShortMessage'].",then set status = 0  efftct=".$effect." and retry count=".$eu['sync_order_retry_count'];
								$effect = SaasEbayUser::updateAll(['item_status'=>0 ,'sync_order_retry_count'=>0,'error_message'=>$requestArr['Errors']['ShortMessage']],['selleruserid'=>$eu['selleruserid']]);
								echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']."  set ebay user item_status = 0  efftct=".$effect." and retry count=".$eu['sync_order_retry_count'];
							}else{
								$effect = SaasEbayUser::updateAll(['sync_order_retry_count'=>$eu['sync_order_retry_count']+1 ,'error_message'=>$requestArr['Errors']['ShortMessage']],['selleruserid'=>$eu['selleruserid']]);
								echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']."  set ebay user sync_order_retry_count +1  efftct=".$effect." and retry count=".$eu['sync_order_retry_count'];
							}
							
							
						}else{
							//warning 的情况 
							/*
							 * 21918011 = An error has occurred while fetching listing item details through WMM Service API call
							 */
							
							if($requestArr['Errors']['ErrorCode'] == '21918011'){
								print_r($requestArr);
								//应急处理， 避免重试次数过多
								//return true;
							}
							echo "\n ".(__function__)." puid=".$eu['uid']." selleruserid:".$eu['selleruserid']." short message:".$requestArr['Errors']['ShortMessage']." and error code = ".$requestArr['Errors']['ErrorCode'];
						}
						
					} catch (\Exception $ex) {
						echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
					}
					
					
				}else{
					echo "\n can't get respone ";
				}
				break 1;
			}
		}while(1);
		return false;
	}


}
?>
