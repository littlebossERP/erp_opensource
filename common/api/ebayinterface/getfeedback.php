<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use console\helpers\QueueGetorderHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\models\CmEbayFeedback;
use eagle\modules\order\models\OdOrder;
use eagle\modules\tracking\models\Tracking;
/**
 * 获得评价列表
 * @package interface.ebay.tradingapi
 */
class getfeedback extends base{
	//从ebay获取 评价
	public $verb='GetFeedback';
	
	public function api($ItemID=null,$TransactionID=null,$FeedbackID=null,$UserID=null,$page=''){
		$xmlArr=array();
		if (!is_null($ItemID)){
			$xmlArr=array(
				//'TransactionID'=>$TransactionID,
				'ItemID'=>$ItemID,
			);
		}elseif (!is_null($FeedbackID)){
			$xmlArr=array(
				'FeedbackID'=>$FeedbackID,
			);
		}elseif (!is_null($UserID)) {
			$xmlArr=array(
				'UserID'=>$UserID
			);
		}
		$xmlArr['FeedbackType']='FeedbackReceivedAsSeller';
		$xmlArr['DetailLevel']='ReturnAll';
		$xmlArr['Pagination']=array(
			'EntriesPerPage'=>200,
			'PageNumber'=>empty($page)?1:(int)$page
		);

		if(isset($this->_before_request_xmlarray['OutputSelector'])){
			unset($xmlArr['DetailLevel']);
			$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
		}

		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}

	/**
	 * 将通过 api取得的值 保存到数据库中
	 *
	 * @see  
	 * 	FeedbackResponse 和FollowUp 是相对的，
	 *  -	卖家对中差评Reply买家FollowUp，卖家对好评FollowUp买家Reply
	 * 
	 * @param array $feedbackReponseArray
	 * @return bool
	 */
	public function save($feedbackReponseArray, $selleruserid,$uid=0){

		if(empty($feedbackReponseArray['FeedbackDetailArray'])) return false;
		$fds=$feedbackReponseArray['FeedbackDetailArray']['FeedbackDetail'];
		if (isset($fds['FeedbackID'])){
			$fds=array($fds);
		}
		
		$count=0;
		foreach ($fds as $feedbackDetail){
			if (!isset($feedbackDetail['TransactionID'])){
				//$feedbackDetail['TransactionID']='0'; #特殊情况
				echo "\n feedback have no transaction id, continue;";
				continue;
			}
			
			$coomaoTransaction=OdEbayTransaction::find()->where(['transactionid'=>$feedbackDetail['TransactionID'],'itemid'=>$feedbackDetail['ItemID']])->One();
			if (empty($coomaoTransaction)){
				echo "\n feedback have no transaction table info, continue;";
				continue;
			}
			
			var_dump($feedbackDetail);
			$feedback=CmEbayFeedback::find()->where(['feedback_id'=>$feedbackDetail['FeedbackID']])->One();
			if(empty($feedback)){
				$feedback = new CmEbayFeedback();
				$feedback->setAttributes(array(
						//'ebay_uid'=>empty($coomaoTransaction->uid)?0:$coomaoTransaction->uid,
						//'selleruserid'=>$coomaoTransaction->selleruserid,
						'selleruserid'=>$selleruserid,
						'feedback_id'=>$feedbackDetail['FeedbackID'],
						'commenting_user'=>$feedbackDetail['CommentingUser'],
						'commenting_user_score'=>$feedbackDetail['CommentingUserScore'],
						'comment_text'=>$feedbackDetail['CommentText'],
						'comment_time'=>strtotime($feedbackDetail['CommentTime']),
						'comment_type'=>$feedbackDetail['CommentType'],
						'feedback_score'=>$feedbackReponseArray['FeedbackScore'],
						'feedback_response'=>empty($feedbackDetail['FeedbackResponse'])?'N/A':$feedbackDetail['FeedbackResponse'], #回复
						'followup'=>empty($feedbackDetail['Followup'])?'N/A':$feedbackDetail['Followup'],	#跟踪
						'role'=>$feedbackDetail['Role'],
						'transaction_id'=>$feedbackDetail['TransactionID'],
						'itemid'=>$feedbackDetail['ItemID'],
						'has_read'=>0,
						'od_ebay_transaction_id'=>empty($coomaoTransaction->id)?0:$coomaoTransaction->id,
				),false);
			}else{
				if($feedback->feedback_id!==$feedbackDetail['FeedbackID'] || $feedback->comment_text!==$feedbackDetail['CommentText'] || $feedback->comment_type!==$feedbackDetail['CommentType']){
					$feedback->feedback_id = $feedbackDetail['FeedbackID'];
					$feedback->commenting_user=$feedbackDetail['CommentingUser'];
					$feedback->commenting_user_score=$feedbackDetail['CommentingUserScore'];
					$feedback->comment_text=$feedbackDetail['CommentText'];
					$feedback->comment_time=strtotime($feedbackDetail['CommentTime']);
					$feedback->comment_type=$feedbackDetail['CommentType'];
					$feedback->feedback_score=$feedbackReponseArray['FeedbackScore'];
					$feedback->role=$feedbackDetail['Role'];
				}else{
					continue;//feedback内容无变化
				}
			}
			#默认好评为已读
			if ($feedback->comment_type == 'Positive'){
				$feedback->has_read=1;
				//如果客人设置了‘评价相关邮件’，则发送该邮件。
				/*
				$cid=Ebay_Mailtemplatecategory::find('uid=? and usefor like (?)',$coomaoTransaction->uid,'评价相关邮件')->getOne()->mailtemplatecategory_id;
				$em=Ebay_Mailtemplate::find('uid=? and mailtemplatecategory_id = ?',$coomaoTransaction->uid,$cid)->getOne();
				if ($em->ebay_mailtemplate_id && !$coomaoTransaction->isNewRecord){
					System::sendMail($coomaoTransaction->myorders->ship_email,$em->title,$em->content,SaasEbayUser::model()->where('selleruserid=?',$coomaoTransaction->selleruserid)->getOne()->email);
				}
				 */
			}
			if($feedback->save()){
				//更新transaction的buyer_feedback
				if (!empty($coomaoTransaction->id)){
					//$tr = OdEbayTransaction::findOne($coomaoTransaction->id);
					
					//$tr->seller_commenttype=$feedback->comment_type;
					//$tr->seller_commenttext=$feedback->comment_text;
					
					$command = \Yii::$app->get('subdb')->createCommand("update od_ebay_transaction set seller_commenttype=:comment_type,seller_commenttext=:comment_text where id=".$coomaoTransaction->id);
					$command->bindValue(":comment_type",$feedback->comment_type,\PDO::PARAM_STR);
					$command->bindValue(":comment_text",$feedback->comment_text,\PDO::PARAM_STR);
					$affectRows = $command->execute();
				}
				
				//更新到order表
				if(!empty($feedbackDetail['OrderLineItemID'])){
					$order = OdOrder::find()->where(['order_source'=>'ebay','order_source_order_id'=>$feedbackDetail['OrderLineItemID']])->one();
					if(!empty($order)){
						$order->seller_commenttype = $feedback->comment_type;
						//要和原数据进行合并，并加入buyer to seller 标签，且要将order的求评价notified set为Y
						if(strlen($order->seller_commenttext)<255){
							if(!strpos($order->seller_commenttext, '[BuyerToSeller]')===false){
								$tmp_commenttext = $order->seller_commenttext.'[BuyerToSeller]';
								if(strlen($tmp_commenttext)<255){
									$order->seller_commenttext = $tmp_commenttext.$feedback->comment_text;
									if(strlen($order->seller_commenttext)>255){
										$order->seller_commenttext = substr($order->seller_commenttext, 0,250).'...';
									}
								}
							}else{
								if(!strpos($order->seller_commenttext, $feedback->comment_text)===false){
									$tmp_commenttext = $order->seller_commenttext.'[BuyerToSeller]';
									if(strlen($tmp_commenttext)<255){
										$order->seller_commenttext = $tmp_commenttext.$feedback->comment_text;
										if(strlen($order->seller_commenttext)>255){
											$order->seller_commenttext = substr($order->seller_commenttext, 0,250).'...';
										}
									}
								}
							}
						}
						$order->shipping_notified = 'Y';
						$order->pending_fetch_notified = 'Y';
						$order->rejected_notified = 'Y';
						$order->received_notified = 'Y';
						try{
							$track = Tracking::find(['order_id'=>$order->order_source_order_id,'platform'=>'ebay'])->one();
							if(!empty($track)){
								$track->shipping_notified = 'Y';
								$track->pending_fetch_notified = 'Y';
								$track->rejected_notified = 'Y';
								$track->received_notified = 'Y';
								if(!$track->save())
									echo "\n update to tracker notified fialed!";
							}
						}catch (\Exception $e) {
					        echo "\n update to tracker notified Exception:".$e->getMessage();
					    }
						if(!$order->save()){
							echo "\n ".print_r($order->getErrors(),true);
							return false;
						}
					}
				}
				//保存到客服模块
				try{
					echo "\ try to save feedback to cs modules;";
					if(!empty($uid))
						\eagle\modules\message\apihelpers\MessageEbayApiHelper::ebayFeedbackToEagleMsg($feedbackDetail, $uid, $selleruserid);
				}catch (\Exception $e) {
			        echo "\n update to tracker notified Exception:".$e->getMessage();
			    }
				
				$count++;
			}else{
				echo "\n ".print_r($feedback->getErrors(),true);
				return false;
			}
		}
		echo "\n save success count:$count";
		return true;
	}

	public function dsrapi($token=null,$sellerid,$id){
		//用来获得用户的dsr数据
		if (!is_null($token)){
			$xmlArr=array(
				'RequesterCredentials'=>array(
					'eBayAuthToken'=>$token,
				),
				'UserID'=>$sellerid,
			);
		}
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
		//   	    $this->savedsr($result,$id);
		//    	return;
	}

	public function dodsrapi($id){
		$eu = SaasEbayUser::find()->where(['selleruserid'=>$id])->One();
		$token = $eu->token;
		$sellerid = $eu->selleruserid;
		return $this->dsrapi($token,$sellerid,$id);
	}

	public function savedsr($rs,$id){
		$eu = SaasEbayUser::find()->where(['selleruserid'=>$id])->One();
		$eu->dsr=$rs['FeedbackSummary']['SellerRatingSummaryArray'];
		$eu->save();
		return;
	}

	/**
	 * 开始同步 Feedback 
	 * @param Ebay User 用户表 $eu
	 * @author lxqun
	 * @date 2014-3-16
	 */
	static function cronRequest($eu){
		$api= new  self;
		$uid=$eu->uid;
 
		$api->resetConfig($eu->DevAcccountID); //授权配置
		$api->eBayAuthToken=$eu->token;
		/*
		$api->_before_request_xmlarray['OutputSelector']=array(
					'FeedbackDetailArray',
					'EntriesPerPage',
					'PageNumber',
					'PaginationResult',
				);
		 */
		$r=$api->api(null ,null,null,null);
		$selleruserid=$eu['selleruserid'];
		if (!$api->responseIsFailure()){
			$api->save($r, $selleruserid,$uid);
			echo $selleruserid.' Get Feedback Success.<br>' ;
			return true;
		} 
		echo  $selleruserid.' Get Feedback Failure.<br>' ;
		return false;
	}
	
	static function cronRequest_website($eu,$page=''){
		$api= new  self;
		$uid=$eu->uid;
	 
		$api->resetConfig($eu->DevAcccountID); //授权配置
		
		$api->eBayAuthToken=$eu->token;
		/*
			$api->_before_request_xmlarray['OutputSelector']=array(
					'FeedbackDetailArray',
					'EntriesPerPage',
					'PageNumber',
					'PaginationResult',
			);
		*/
		
		$selleruserid=$eu['selleruserid'];
		
		if($page==0){
			$orders = OdOrder::find()->where(['order_source'=>'ebay','selleruserid'=>$selleruserid ])->asArray()->all();
			$total_orders = count($orders);
			$total_pages = ceil($total_orders / 200);
			$current_page = 1;
			do{
				$current_page++;
				$r=$api->api(null ,null,null,null,$current_page);
				
				if (!$api->responseIsFailure()){
					$api->save($r, $selleruserid);
					echo $selleruserid.' Get Feedback Success.<br>' ;
				}
				else
					echo  $selleruserid.' Get Feedback Failure.<br>' ;
			}while ($current_page < $total_pages);
			
		}else{
			
			$r=$api->api(null ,null,null,null,$page);
			
			if (!$api->responseIsFailure()){
				$api->save($r, $selleruserid);
				echo $selleruserid.' Get Feedback Success.<br>' ;
				return true;
			}
			else 
				echo  $selleruserid.' Get Feedback Failure.<br>' ;
		}
		return false;
	}
}
?>
