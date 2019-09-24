<?php
namespace eagle\modules\message\controllers;

use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use eagle\modules\message\models\Customer;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\helpers\CustomerTagHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
use yii\behaviors\AttributeBehavior;
use eagle\modules\message\apihelpers\MessageAliexpressApiHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\models\SysCountry;
use common\helpers\Helper_Array;
use yii\base\ExitException;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\message\models\CustomerTags;
use eagle\modules\message\models\Tag;
use yii\db\Query;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\message\CustomerMsgTemplate;
use eagle\models\message\CustomerMsgTemplateDetail;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\message\models\CmEbayUsercase;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\getebaydetails;
use common\api\ebayinterface\resolution\getebpcasedetail;
use common\api\ebayinterface\resolution\getactivityoptions;
use eagle\models\EbayCountry;
use eagle\modules\listing\models\EbayItem;
use common\api\ebayinterface\resolution\offerothersolution;
use common\api\ebayinterface\resolution\offerrefunduponreturn;
use common\api\ebayinterface\resolution\escalatetocustomersupport;
use common\api\ebayinterface\resolution\providetrackinginfo;
use common\api\ebayinterface\resolution\provideshippinginfo;
use common\api\ebayinterface\base;
use common\api\ebayinterface\resolution\providerefundinfo;
use common\api\ebayinterface\resolution\issuepartialrefund;
use common\api\ebayinterface\resolution\issuefullrefund;
use common\api\ebayinterface\resolution\appealtocustomersupport;
use common\api\ebayinterface\resolution\offerpartialrefund;
use yii\helpers\Json;
use eagle\modules\order\openapi\OrderApi;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\permission\apihelpers\UserApiHelper;

class AllCustomerController extends \eagle\components\Controller{
    public $enableCsrfValidation = FALSE;
    /**
     +---------------------------------------------------------------------------------------------
     * 根据未读的状态来显示所有用户信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3				
     +---------------------------------------------------------------------------------------------
     **/
    public function actionCustomerList(){
    	$canAccessModule = UserApiHelper::checkModulePermission("message");
    	if(!$canAccessModule)
    		return $this->render('//errorview_no_close',['title'=>'操作受限','error'=>'您没有权限操作本模块!']);
    	//ebay站内信可能存在数据丢失暂时放在这里做conversion
    	MessageApiHelper::ebayMsgConversion();
    	
        $where=' 1 ';
        
        //$query= Customer::find()->orderBy(['msg_sent_error'=>SORT_DESC,'os_flag'=>SORT_DESC,'last_message_time'=>SORT_DESC]); //
		//红色信封在前， 黄色接着的排序写法
		$query= Customer::find()->orderBy("case when `msg_sent_error`='Y' then 10 else `os_flag` end DESC , `last_message_time` DESC"); //
		
		$allPlatformArr = MessageApiHelper::getPlatformAccountList();
// 		$query->andWhere(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount']]);
		
		//主子帐号 star
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		//true 为主账号， 不需要增加平台过滤 ， false 为子账号， 需要增加权限控制
		$authorize_query = array();
		$allPlatforms = array();
		if ($isParent == false){
		    $UserAuthorizePlatform = \eagle\modules\permission\apihelpers\UserApiHelper::getUserAuthorizePlatform();
		    
		    //店铺级别权限 	start	2017-03-21 lzhl	ADD
		    $authorizePlatformAccounts = UserHelper::getUserAuthorizePlatformAccounts($UserAuthorizePlatform);
		    if(!empty($authorizePlatformAccounts)){
		    	$authorize_query = ['or'];
		    	foreach ($authorizePlatformAccounts as $authorize_platform=>$authorize_accounts){
		    		$authorize_query[] = ['platform_source'=>$authorize_platform,'seller_id'=>array_keys($authorize_accounts)];
		    	}
		    	//$query->andWhere($authorize_query);
		    }
		    //店铺级别权限 	end
		    
		    /*//权限增加店铺级别前
		    if (!in_array('all',$UserAuthorizePlatform)){
		        if(!empty($UserAuthorizePlatform)){
		            foreach ($UserAuthorizePlatform as $v){
		                if(isset($allPlatformArr[$v])){
		                    $allPlatforms += $allPlatformArr[$v];
		                }
		            }
		        }
		    }else{
		        $allPlatforms = $allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount'];
		    }
		    */
		}else{
			//权限增加店铺级别前
		    //$allPlatforms = $allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount'];
		}
		//主子帐号 starend
		
		//$query->andWhere(['in','seller_id',$allPlatforms]);//权限增加店铺级别前
		if($isParent == false){//权限增加店铺级别后
			if(empty($authorize_query))
				return $this->render('//errorview_no_close',['title'=>'操作受限','error'=>'您还没有获得任何平台账号管理权限!']);
			else 
				$query->andWhere($authorize_query);
		}
		
        //测试 排序sql
		//$tmpCommand = $query->createCommand();
		//echo "<br>".$tmpCommand->getRawSql();
		
        if(!empty($_REQUEST['search'])){
            //去掉模糊查找的引号。以免SQL注入
            $search = str_replace("'","",$_REQUEST['search']);
            $search = str_replace('"',"",$_REQUEST['search']);
            $where .= " and (last_order_id like '%$search%' or customer_nickname like '%$search%' )";
        }else{
            $search="";
        }
        //标签筛选
        if(!empty($_REQUEST['message_tag'])){
            $where .= " and id in ( select customer_id from cs_customer_tags where tag_id = '{$_REQUEST['message_tag']}')";
        }
        
        if(!empty($_REQUEST['customer_startdate'])){
            //去掉模糊查找的引号。以免SQL注入
            $customer_startdate = str_replace("'","",$_REQUEST['customer_startdate']);
            $customer_startdate = str_replace('"',"",$_REQUEST['customer_startdate']);
            $customer_startdate .=  " 00:00:00";
            $where .= " and ( last_order_time >='$customer_startdate')";
            $startdate=$_REQUEST['customer_startdate'];
        }else {
            $startdate="";
        }
        
        if(!empty($_REQUEST['customer_enddate'])){
            //去掉模糊查找的引号。以免SQL注入
            $customer_enddate = str_replace("'","",$_REQUEST['customer_enddate']);
            $customer_enddate = str_replace('"',"",$_REQUEST['customer_enddate']);
            $customer_enddate .=  " 23:59:59";
            $where .= " and ( last_order_time <='$customer_enddate')";
            $enddate=$_REQUEST['customer_enddate'];
        }else {
            $enddate="";
        }
        
        if(!empty($_REQUEST['accounts'])){
            $where .= " and ( seller_id = '{$_REQUEST['accounts']}')";
        }
        
        if(!empty($_REQUEST['countrys'])){
            $where .= " and ( nation_code = '{$_REQUEST['countrys']}')";
        }
        
//         if(!empty($_REQUEST['selected_type'])){
//             $selected_type=$_REQUEST['selected_type'];
//         }else {
//             $selected_type='';
//         }
        
        //保存查找信息
        $save=array();
        $save['search']=$search;
        $save['customer_startdate']=$startdate;
        $save['customer_enddate']=$enddate;
        
        //订单金额过滤
        $amount_min='';
        if(isset($_REQUEST['amount_min'])){
        	$_REQUEST['amount_min'] = trim($_REQUEST['amount_min']);
        	if(is_numeric($_REQUEST['amount_min'])){
        		$where .= " and ( life_order_amount >= ".$_REQUEST['amount_min'].")";
        		$amount_min = $_REQUEST['amount_min'];
        	}
        }
        $amount_max='';
        if(isset($_REQUEST['amount_max'])){
        	$_REQUEST['amount_max'] = trim($_REQUEST['amount_max']);
        	if(is_numeric($_REQUEST['amount_max'])){
        		$where .= " and ( life_order_amount <= ".$_REQUEST['amount_max'].")";
        		$amount_max = $_REQUEST['amount_max'];
        	}
        }
        $save['amount_min'] = $amount_min;
        $save['amount_max'] = $amount_max;
        //订单数过滤
        $order_min='';
        if(isset($_REQUEST['order_min'])){
        	$_REQUEST['order_min'] = trim($_REQUEST['order_min']);
        	if(is_numeric($_REQUEST['order_min'])){
        		$order_min = $_REQUEST['order_min'];
        		$where .= " and ( life_order_count >= ".$_REQUEST['order_min'].")";
        	}
        }
        $order_max='';
        if(isset($_REQUEST['order_max'])){
        	$_REQUEST['order_max'] = trim($_REQUEST['order_max']);
        	if(is_numeric($_REQUEST['order_max'])){
        		$order_max = $_REQUEST['order_max'];
        		$where .= " and ( life_order_count <= ".$_REQUEST['order_max'].")";
        	}
        }
        $save['order_min'] = $order_min;
        $save['order_max'] = $order_max;
       
        //
        $pages = new Pagination(['totalCount' =>$query->andWhere($where)->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);//defaultPageSize默认页数,'params'=>$_REQUEST带参过滤
        $query->andWhere($where);
        $query->offset($pages->offset)->limit($pages->limit);
        //测试 排序sql
        //$tmpCommand = $query->createCommand();
        //echo "<br>".$tmpCommand->getRawSql();
//         exit();
        $customers = $query->asArray()->all();
        
        //$account=Customer::find()->distinct('seller_id')->select('seller_id')->where(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']])->all();//平台帐号
        $account_query=Customer::find()->distinct('seller_id,platform_source')->select('seller_id,platform_source')->where($authorize_query);
        //测试 排序sql
        //$tmpCommand = $account_query->createCommand();
        //echo "<br>".$tmpCommand->getRawSql();
        $account = $account_query->all();
        
        //$nation=Customer::find()->distinct('nation_code')->select('nation_code')->where(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']])->all();
        $nation_query=Customer::find()->distinct('nation_code')->select('nation_code')->where($authorize_query);
        //测试 排序sql
        //$tmpCommand = $nation_query->createCommand();
        //echo "<br>".$tmpCommand->getRawSql();
        $nation = $nation_query->all();
        
        $no_answer = Customer::find()->where(['os_flag'=>1])->andWhere(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']])->asArray()->all();//查看是否有没回复信息
//         print_r($no_answer);
        foreach ($customers as $cusKey => $customer){
            $customers[$cusKey]['track_no'] = '';
        
            if($customer['last_order_id']!=""){
                $trackingOne=Tracking::find()->select('track_no')->where(['order_id'=>$customer['last_order_id'],'seller_id'=>$customer['seller_id']])
                ->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
                if (count($trackingOne)!=0){
                    $customers[$cusKey]['track_no'] = $trackingOne['track_no'];
                }
                unset($trackingOne);
            }
        }
        $countrys=array();
        foreach ($nation as $nation_zh){
            if($nation_zh['nation_code']!=null){
                $countrys[$nation_zh['nation_code']]=StandardConst::$COUNTRIES_CODE_NAME_CN[$nation_zh['nation_code']];
            }
        }
        
        //获取不同平台的卖家账号
        $selleruserids = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(false, true, true);
        
        if(!empty($selleruserids['wish'])){
        	$tmp_wish_a = $selleruserids['wish'];
        	unset($selleruserids['wish']);
        	$selleruserids['wish'] = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($tmp_wish_a);
        }
        
        $tag_class_list = CustomerTagHelper::getTagColorMapping();
        $tags=Tag::find()->where(" tag_id in (select distinct tag_id from cs_customer_tags where 1) ")->all();
        AppTrackerApiHelper::actionLog("Message", "view customer list" ); //查看客户列表        
        
        return $this->render('customer-list',['customers'=>$customers,'pages'=>$pages,'account'=>$account,'save'=>$save,
        		'countrys'=>$countrys,'no_answer'=>$no_answer,'tag_class_list'=>$tag_class_list,'tags'=>$tags,'selleruserids'=>$selleruserids]);
}
    
    /**
     +---------------------------------------------------------------------------------------------
     * 根据信息未读的状态以及最近的订单，显示对应用户和卖家的所有对话信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    public function actionShowMessage(){
        $message_state=array();
        $message_state['os_flag']=$_GET['os_flag'];
        $message_state['msg_sent_error']=$_GET['msg_sent_error'];
        $message_state['message_status']=$_GET['status'];
        $result = array();
        $result['success'] = false;
        if(!empty($_GET['ticket_id'])){ //是否有ticket_id
            $ticket_id=$_GET['ticket_id'];
            $related_type=$_GET['related_type'];
        }else{
        	if(!empty($_GET['related_id'])){//有单号优先以单号查找
        		if($_GET['os_flag']==0){//无“没有回复的状态”,msg_sent_error有可能为Y，但由于经过view层经过处理，基本不会出现0+Y的组合出现绿色信封
        			$sessions=TicketSession::find()->where(['related_id'=>$_GET['related_id']])->orderBy(['lastmessage'=>SORT_DESC,])->one();//在已回复的条件下，取最后的消息
        		}
        		if($_GET['os_flag']==1&&$message_state['message_status']=="remind"){//有“没有回复的状态”
        			$sessions=TicketSession::find()->where(['related_id'=>$_GET['related_id'],'has_replied'=>0,])->orderBy(['lastmessage'=>SORT_DESC,])->one();//拿最后的消息
        		}
        		if($message_state['msg_sent_error']=="Y"&&$message_state['message_status']=="remove"){//有发送失败的状态
        			$sessions=TicketSession::find()->where(['related_id'=>$_GET['related_id'],'msg_sent_error'=>"Y",])->orderBy(['lastmessage'=>SORT_DESC,])->one();
        		}
        		if(empty($sessions)){//判断是否有相关session
        			echo "没有找到相关信息";
        			exit();
        		}else{
        			$ticket_id=$sessions->ticket_id;
        			$related_type=$sessions->related_type;
        			$customer_id = $sessions->buyer_id;
        			$_GET['seller_id'] = $sessions->seller_id;
        			$_GET['customer_id'] = $sessions->buyer_id;
        		}
        	}
            elseif(!empty($_GET['seller_id'])&&!empty($_GET['customer_id']) && empty($_GET['related_id'])){
                $customer_id=$_GET['customer_id'];
                if($_GET['os_flag']==0){//无“没有回复的状态”,msg_sent_error有可能为Y，但由于经过view层经过处理，基本不会出现0+Y的组合出现绿色信封
                    $sessions=TicketSession::find()->where(['seller_id'=>$_GET['seller_id'],'buyer_id'=>$_GET['customer_id']])->orderBy(['lastmessage'=>SORT_DESC,])->one();//在已回复的条件下，取最后的消息
                }
                if($_GET['os_flag']==1&&$message_state['message_status']=="remind"){//有“没有回复的状态”
                    $sessions=TicketSession::find()->where(['seller_id'=>$_GET['seller_id'],'buyer_id'=>$_GET['customer_id'],'has_replied'=>0,])->orderBy(['lastmessage'=>SORT_DESC,])->one();//拿最后的消息 
                }
                if($message_state['msg_sent_error']=="Y"&&$message_state['message_status']=="remove"){//有发送失败的状态
                    $sessions=TicketSession::find()->where(['seller_id'=>$_GET['seller_id'],'buyer_id'=>$_GET['customer_id'],'msg_sent_error'=>"Y",])->orderBy(['lastmessage'=>SORT_DESC,])->one();
                }
                if(empty($sessions)){//判断是否有相关session
                    echo "没有找到相关信息";
                    exit();
                }else{
                    $ticket_id=$sessions->ticket_id;
                    $related_type=$sessions->related_type;
                }
                
            }else{
                $customer_id=null;
            }
        }
        $con=MessageApiHelper::msgUpateHasRead($ticket_id, null);//批量更新已读状态
        if($con['success']!=true){
            echo $con['error'];
            exit();
        }
        
        
        //获取站内信message和类型
        $connect=array();
        $headDatas=array();
        $msg_sort=array();
        $msg_sort['sort']='t.platform_time';
        $msg_sort['order']='asc';
        $message=MessageApiHelper::getTicketMsgList($ticket_id,$msg_sort); 
        if($message['success']==1){
             $connect=$message['msgList']['msgdata'];
             $headDatas=$message['msgList']['headData'];
        }

        
        //统计订单历史记录条数
        if(!empty($_GET['platform_source'])&&!empty($_GET['customer_id']))
        {
            $customerArr=['source_buyer_user_id' => $_GET['customer_id']];
            $result=OrderTrackerApiHelper::getOrderList($_GET['platform_source'],$customerArr);
        }
        $all_list=array();
        if($result['success']==1){
            $all_list=$result['orderArr']['data'];
        }
        $track=OrderTrackerApiHelper::getTrackingNoByOrderId($_GET['seller_id'], $headDatas['related_id']);//获取物流跟踪号
        $count=count($all_list);//历史次数
//         print_r($all_list);
//         print_r($count);exit();
        $track_no=$track['track_no'];//物流号
        $track_status=$track['status'];//物流状态
        //获取订单历史的数据
        $order_history=array();
        $order_history['order_source']=$_GET['platform_source'];
        $order_history['buyer_id']=$_GET['customer_id'];
        
        //统计该平台下该账户的所有失败信息
        if(!empty($headDatas)&&$headDatas['msg_sent_error']=='Y'){
            $sql="select count(1) as error_no from cs_ticket_message m
                left join cs_ticket_session s on m.ticket_id = s.ticket_id
                where m.status = 'F' and s.platform_source=:platform_source and s.seller_id=:seller_id 
                group by s.platform_source , s.seller_id";
            $customerCommand = Yii::$app->subdb->createCommand($sql);
            $customerCommand->bindValue(':platform_source', $headDatas['platform_source'], \PDO::PARAM_STR);//防止sql注入
            $customerCommand->bindValue(':seller_id', $headDatas['seller_id'], \PDO::PARAM_STR);
            $affectcustomerRows = $customerCommand->queryAll();//查找
            if(!empty($affectcustomerRows)){
                $error_message = $affectcustomerRows[0]['error_no'];
            }else{
                $error_message = 0;
            }
        }else{
            $error_message = 0;
        }
        //查找模版信息
        $language_data=array();
        $language_puid = '1';
        $puid = \Yii::$app->user->identity->getParentUid();
        if(!empty($puid)){
	        $language_puid .= " and puid in (0,{$puid})";
	    }else{
	        $language_puid .= " and puid = 0";
	    }
        $query = CustomerMsgTemplate::find()->andWhere($language_puid)->orderBy(['seq'=>SORT_ASC,]);
        $language_data['data'] = $query->asArray()->all();
        
        //若最后消息对应的类型不是O（订单），则要重新判断
        if($related_type=="P"){
            $message_type=OrderTrackerApiHelper::getOrderDetailOrSkuDetail($_GET['platform_source'], $_GET['customer_id'], $headDatas['related_id']);
            if($message_type['type']=='order'&&!empty($message_type['dataInfo'])){
                $product_list=$message_type['dataInfo'];
                if(!empty($product_list)){
                    $product_list['list_num']=$count;
                    $product_list['track_no']=$track_no;
                    $product_list['status']=$track_status;
                    $product_list['type']="order";

                }
                
            }else if($message_type['type']=='sku'&&!empty($message_type['dataInfo'])){
              $product_list=$message_type['dataInfo'];
              $product_list['list_num']=$count;
              $product_list['type']="sku";

            }else{
                $product_list=null;
            }
            
            return $this->renderAjax('detail-message',['connect'=>$connect,'headDatas'=>$headDatas,'product_list'=>$product_list,'order_history'=>$order_history,'message_state'=>$message_state,'error_message_num'=>$error_message,'data'=>$language_data,]);
        }
        //查看是否有订单号       
        if(!empty($headDatas['related_id'])){
            $product_list =OrderTrackerApiHelper::getOrderDetailByOrderNo($headDatas['related_id']);
        }else if($count!=0){//当最后一条消息为商品时，查看是否有订单，有则显示最后一张订单的相关信息
            if(!empty($all_list[0]['order_source_order_id'])){
               $product_list=OrderTrackerApiHelper::getOrderDetailByOrderNo($all_list[0]['order_source_order_id']);
            }
        }else{
            $product_list=null;
        }
        if(!empty($product_list)){
            $product_list['list_num']=$count;
            $product_list['track_no']=$track_no;
            $product_list['status']=$track_status;
            $product_list['type']="order";

        }
        
        //判断下一个上一个
        $upOrDownDiv=array('cursor'=>0,'up'=>'','down'=>'');
        $rtnArr=[];
        if(!empty($_REQUEST['upOrDownText'])){
        	$rtnArr=json_decode(base64_decode($_REQUEST['upOrDownText']),true);
        	if(!empty($rtnArr)){
        		$thisParameters = @$_REQUEST['seller_id'].','.@$_REQUEST['customer_id'].','.@$_REQUEST['platform_source'].','.@$_REQUEST['ticket_id'].','.$_REQUEST['related_type'].','.@$_REQUEST['os_flag'].','.@$_REQUEST['msg_sent_error'].','.@$_REQUEST['status'];
//         		var_dump($thisParameters);
        		foreach ($rtnArr as $rtnIndex=> $rtnKeys){
        			$comp_str = implode(',', $rtnKeys);
//         			var_dump($comp_str);
        			if($thisParameters==$comp_str){
        				if($rtnIndex==0)
        					$upOrDownDiv['cursor']=1;
        				else if($rtnIndex==(count($rtnArr)-1))
        					$upOrDownDiv['cursor']=3;
        				else
        					$upOrDownDiv['cursor']=2;
        
        				$upOrDownDiv['up']=empty($rtnArr[$rtnIndex-1])?'':implode('@@',$rtnArr[$rtnIndex-1]);
        				$upOrDownDiv['down']=empty($rtnArr[$rtnIndex+1])?'':implode('@@',$rtnArr[$rtnIndex+1]);
        
        				if(empty($upOrDownDiv['up']) && empty($upOrDownDiv['down']))
        					$upOrDownDiv['cursor']=0;
        				break;
        			}
        		}
        	}
        }
        
        
        //获取订单历史的数据

        AppTrackerApiHelper::actionLog("Message", "view message detail" );//查看消息详细内容
        return $this->renderAjax('detail-message',[
        		'connect'=>$connect,
        		'headDatas'=>$headDatas,
        		'product_list'=>$product_list,
        		'order_history'=>$order_history,
        		'message_state'=>$message_state,
        		'error_message_num'=>$error_message,
        		'data'=>$language_data,
        		'upOrDownDiv'=>$upOrDownDiv,
        	]);
//         return $this->renderAjax('sku-detail-message',['connect'=>$connect,'headDatas'=>$headDatas,'list_head'=>$list_head,'product_list'=>$product_list]);

       
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 根据order_id来获取订单的所有信息
     +---------------------------------------------------------------------------------------------
     **/
    public function actionGetOrderId(){
        if (!empty($_GET['order_no'])){
        	AppTrackerApiHelper::actionLog( "Message", "view order detail" , ['paramstr1'=>$_GET['order_no']] );//查看相关订单详情
            $rtn = OrderTrackerApiHelper::getOrderDetailByOrderNo($_GET['order_no']);
            return $this->renderAjax('_view_order_info', ['orderData'=>$rtn]);
        }
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 根据用户所选择的不同条件来筛选所有信息并显示
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
//     public function actionSelectList(){
//         $where=' 1 '; 
//         $query=Customer::find()->orderBy(['os_flag'=>SORT_DESC,'last_message_time'=>SORT_DESC]);
       
//         if(!empty($_REQUEST['search'])){
//             //去掉模糊查找的引号。以免SQL注入
//             $search = str_replace("'","",$_REQUEST['search']);
//             $search = str_replace('"',"",$_REQUEST['search']);
//             $where .= " and (last_order_id like '%$search%' or customer_nickname like '%$search%' )";
//         }else{
//             $search="";
//         }
        
//         if(!empty($_REQUEST['customer_startdate'])){
//             //去掉模糊查找的引号。以免SQL注入
//             $customer_startdate = str_replace("'","",$_REQUEST['customer_startdate']);
//             $customer_startdate = str_replace('"',"",$_REQUEST['customer_startdate']);
//             $customer_startdate .=  " 00:00:00";
//             $where .= " and ( last_order_time >='$customer_startdate')";
//             $startdate=$_REQUEST['customer_startdate'];
//         }else {
//             $startdate="";
//         }
        
//         if(!empty($_REQUEST['customer_enddate'])){
//             //去掉模糊查找的引号。以免SQL注入
//             $customer_enddate = str_replace("'","",$_REQUEST['customer_enddate']);
//             $customer_enddate = str_replace('"',"",$_REQUEST['customer_enddate']);
//             $customer_enddate .=  " 23:59:59";       
//             $where .= " and ( last_order_time <='$customer_enddate')";
//             $enddate=$_REQUEST['customer_enddate'];
//         }else {
//             $enddate="";
//         }
        
//         if(!empty($_REQUEST['accounts'])){
//             $where .= " and ( seller_id = '{$_REQUEST['accounts']}')";
//         }
        
//         if(!empty($_REQUEST['countrys'])){
//             $where .= " and ( nation_code = '{$_REQUEST['countrys']}')";
//         }
        
//         //保存查找信息
//         $save=array();
//         $save['search']=$search;
//         $save['customer_startdate']=$startdate;
//         $save['customer_enddate']=$enddate;
        
//         $pages = new Pagination(['totalCount' =>$query->andWhere($where)->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);//defaultPageSize默认页数,'params'=>$_REQUEST带参过滤
//         $customers = $query->andWhere($where)->offset($pages->offset)->limit($pages->limit)->asArray()->all();
//         $account=Customer::find()->distinct('seller_id')->select('seller_id')->all();//平台帐号
//         $nation=Customer::find()->distinct('nation_code')->select('nation_code')->all();
//         $no_answer = Customer::find()->where(['os_flag'=>1])->asArray()->one();//查看是否有没回复信息
//         foreach ($customers as $cusKey => $customer){
//             $customers[$cusKey]['track_no'] = '';
        
//             if($customer['last_order_id']!=""){
//                 $trackingOne==Tracking::find()->select('track_no')->where(['order_id'=>$customer['last_order_id'],'seller_id'=>$customer['seller_id']])
//                 ->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
//                 if (count($trackingOne)!=0){
//                     $customers[$cusKey]['track_no'] = $trackingOne['track_no'];
//                 }
//                 unset($trackingOne);
//             }
//         }
//         $countrys=array();
//         foreach ($nation as $nation_zh){
//             if($nation_zh['nation_code']!=null){
//                 $countrys[$nation_zh['nation_code']]=StandardConst::$COUNTRIES_CODE_NAME_CN[$nation_zh['nation_code']];
//             }
//         }
//         return $this->render('customer-list',['customers'=>$customers,'pages'=>$pages,'account'=>$account,'save'=>$save,'countrys'=>$countrys,'no_answer'=>$no_answer]);
             
//     }
    /**
     +---------------------------------------------------------------------------------------------
     * 根据物流号显示所有详细的物流信息
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDetailLocation(){
//         TrackingHelper::generateTrackingEventHTML($TrackingList);
//         $aa=TrackingHelper::getTrackingAllEvents('RR015092670VN');
//         print_r($aa);
        $track_no=array();
        $track_no[]=$_REQUEST['track_no'];
        //AppTrackerApiHelper::actionLog("Message", "view tracking detail" ,['paramstr1'=>$track_no]);//查看相关物流详情
		 
        $locations=TrackingHelper::generateTrackingEventHTML($track_no,[],true);
        return $this->renderPartial('detail-location',['locations'=>$locations,]);
    }
    /**
     +---------------------------------------------------------------------------------------------
     * 订单历史的所有信息并显示
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    public function actionOrderList(){
        if(!empty($_GET['platform_source'])&&!empty($_GET['customer_id']))
        {
            $customerArr=['source_buyer_user_id' => $_GET['customer_id']];   
            $result=OrderTrackerApiHelper::getOrderList($_GET['platform_source'],$customerArr);
        }
        $all_list=array();
        if($result['success']==1){
            $all_list=$result['orderArr']['data'];
        }else{
            return ;
        }
        $style_list=$_GET['style_list'];
//         print_r($all_list);
//         echo $all_list[0]['order_source_order_id'];
//         exit();                                                        
//         echo $_GET['platform_source'];
//         echo $_GET['customer_id'];
        AppTrackerApiHelper::actionLog("Message", "view customer order history"  );//查看客户订单历史
        return $this->renderAjax('order-list',['all_list'=>$all_list,'style_list'=>$style_list]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 没有订单号但有商品sku时，根据sku查找相关的订单历史
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    //根据sku找订单历史
    public function actionSkuList(){
        if(!empty($_GET['platform_source'])&&!empty($_GET['customer_id'])&&!empty($_GET['sku_no']))
        {
            $customerArr=['source_buyer_user_id' => $_GET['customer_id']];
            $skulist=['sku'=>$_GET['sku_no']];
            $result=OrderTrackerApiHelper::getOrderList($_GET['platform_source'],$customerArr,$skulist);
        }
        $all_list=array();
        if($result['success']==1){
            $all_list=$result['orderArr']['data'];
        }else{
            return ;
        }
        $style_list=$_GET['style_list'];
        //         print_r($all_list);
        //         exit();
        //         echo $_GET['platform_source'];
        //         echo $_GET['customer_id'];
        AppTrackerApiHelper::actionLog("Message", "view order history by sku"  );//没有订单号但有商品sku时，根据sku查找相关的订单历史
        return $this->renderAjax('order-list',['all_list'=>$all_list,'style_list'=>$style_list]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 用于message对话时进行发送消息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    //回复消息
    public function actionSentMessage(){
        //print_r($_GET);
        if(empty($_GET['message'])){
            return;
        }
        if(!empty($_GET['ticket_id'])){
            $other=TicketSession::find()->where(['ticket_id'=>$_GET['ticket_id'],])->one();
        }
        $message=array();
        $message['platform_source']=$other->platform_source;
        $message['msgType']=$other->message_type;
        $message['puid']=\Yii::$app->user->identity->getParentUid();
        $message['contents']=$_GET['message'];
        $message['ticket_id']=$other->ticket_id;
        $message['orderId']=$other->related_id;
        $message["item_id"]=$other->item_id;
        $nickname=$other->seller_nickname;
        $date=date("Y-m-d H:i:s",time());
        $result=MessageApiHelper::sendMsgToPlatform($message);
//         print_r($result);
//         exit();
        $html='';
       if($result['success']==1){
            
            $html="<div class='right-message'>".
            "<div class='message-content'><p>{$_GET['message']}</p></div>".
            "<div class='message-header'><div class='message-bottom'>回复&nbsp;&nbsp;</div><div class='message-bottom'>{$nickname}&nbsp;&nbsp;{$date}</div></div>".
            "</div>";
        }
        AppTrackerApiHelper::actionLog("Message", "send message"  );//发送消息
        return $html;
         
    }
    /**
     +---------------------------------------------------------------------------------------------
     * 对应客户的所有信息记录
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    //消息记录
    public function actionMessageRecord(){
        if(!empty($_GET['platform_source'])&&!empty($_GET['customer_id'])&&!empty($_GET['seller_id'])){
             $message=MessageApiHelper::getTicketSessionList($_GET['customer_id'], $_GET['platform_source'], $_GET['seller_id']);
        }
        $message_list=array();
        if($message['success']==1){
            $message_list=$message['ticketSessionList']['data'];
        }
        AppTrackerApiHelper::actionLog("Message", "view customer msg history"  );//查看客户历史消息记录
        return $this->renderAjax('message-record',['message_list'=>$message_list]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 所有站内信的显示
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    //站内信显示
    public function actionShowLetter(){
        $platform = '';
    	$where="";
    	$conn=\Yii::$app->subdb;
    	$queryTmp = new Query;
    	$queryTmp->select("t.*")
    	->from("cs_ticket_session t")
    	->leftJoin("cs_ticket_message a1", " a1.ticket_id = t.ticket_id ")
    	->where(['and'," t.message_type<>0 and a1.ticket_id=t.ticket_id "]);
    	
    	$queryTmp->distinct();
    	
    	$queryTmp->orderBy(['t.has_read'=>SORT_ASC,'t.has_replied'=>SORT_ASC,'t.msg_sent_error'=>SORT_DESC,'t.lastmessage'=>SORT_DESC]);
    	
//         $where=' 1 and ( message_type<>0 )';
//         $query=TicketSession::find()->orderBy(['has_read'=>SORT_ASC,'has_replied'=>SORT_ASC,'lastmessage'=>SORT_DESC]);
        //左边菜单的筛选条件
        if(!empty($_REQUEST['select_platform'])){
//             $where .= " and ( platform_source = '{$_REQUEST['select_platform']}')";
            $platform = $_REQUEST['select_platform'];
            $queryTmp->andWhere(['and', "t.platform_source=:platform"],[':platform'=>$_REQUEST['select_platform']]);
            //只针对CD，处理异常session,对于未回复中有特殊状态进行处理,closed状态未回复，与已回复同等地位，按时间排序,open状态没回复，查看OMS该订单是否为取消或退款状态，是则与closed状态未回复，与已回复同等地位，按时间排序,否则，优先级最高
            //处理方式主要讲异常的是否回复状态由0转成1，利用cd中未用字段original_msg_type，保存一个'error_states',将回复状态由0变为1，因此回复状态1与error_states组成新状态 未回复(异常状态)，其他真正未回复优先级就最高
            if($platform == 'cdiscount'){
                $error_state = ['CancelledByCustomer','RefusedBySeller','AutomaticCancellation','PaymentRefused','ShipmentRefusedBySeller','RefusedNoShipment'];//cd订单异常状态
                $session_results = TicketSession::find()->where(['platform_source'=>$platform,'has_replied'=>0])->all();
                if(!empty($session_results)){
                    foreach ($session_results as $session_result){
                        if($session_result->session_status == 'Closed'){
                            $session_result->has_replied = 1;
                            $session_result->original_msg_type = 'error_states';
                            $session_result->save();
                        }else if($session_result->session_status == 'Open'){
                            $order_result = OdOrder::find()->where(["order_source"=>"cdiscount","order_source_order_id"=>$session_result->related_id])->one();
                            if(!empty($order_result)){//查看是否异常状态
                                if(in_array($order_result->order_source_status, $error_state)){
                                    $session_result->has_replied = 1;
                                    $session_result->original_msg_type = 'error_states';
                                    $session_result->save();
                                }
                            }
                        }
                    }
                }
            
            }
        } 
        //左边菜单的筛选条件
        if(!empty($_REQUEST['select_type'])){
//             $where .= " and ( related_type = '{$_REQUEST['select_type']}')";

//             $queryTmp->andWhere(['and', "t.related_type=:related_type"],[':related_type'=>$_REQUEST['select_type']]);
            if($_REQUEST['select_platform'] == "aliexpress"&&$_REQUEST['select_type'] == "M"){
                $queryTmp->andWhere(['and', "t.related_type=:related_type1 or t.related_type is null"],[':related_type1'=>$_REQUEST['select_type']]);
            }else{
                $queryTmp->andWhere(['and', "t.related_type=:related_type"],[':related_type'=>$_REQUEST['select_type']]);
            }
        } 
        //信息类型
        if(!empty($_REQUEST['message_type'])){
            $queryTmp->andWhere(['and', "t.message_type=:message_type"],[':message_type'=>$_REQUEST['message_type']]);
        }  
        if(!empty($_REQUEST['letter_search'])){
            //去掉模糊查找的引号。以免SQL注入
            $search = str_replace("'","",$_REQUEST['letter_search']);
            $search = str_replace('"',"",$_REQUEST['letter_search']);
//             $where .= " and (buyer_id like '%$search%' or buyer_nickname like '%$search%' )";
            
            $queryTmp->andWhere(['and', "t.buyer_id like :search or t.buyer_nickname like :search or a1.related_id=:search1 or t.item_id=:search1"],[':search'=>'%'.$_REQUEST['letter_search'].'%',':search1'=>$_REQUEST['letter_search']]);
        }else{
            $search="";
        }
        
        if(!empty($_REQUEST['letterstartdate'])){
            //去掉模糊查找的引号。以免SQL注入
            $customer_startdate = str_replace("'","",$_REQUEST['letterstartdate']);
            $customer_startdate = str_replace('"',"",$_REQUEST['letterstartdate']);
            $customer_startdate .=  " 00:00:00";
//             $where .= " and ( lastmessage >='$customer_startdate')";
            $startdate=$_REQUEST['letterstartdate'];
            
            $queryTmp->andWhere(['and', "t.lastmessage>=:letterstartdate"],[':letterstartdate'=>$customer_startdate]);
        }else {
            $startdate="";
        }
        
        if(!empty($_REQUEST['letterenddate'])){
            //去掉模糊查找的引号。以免SQL注入
            $customer_enddate = str_replace("'","",$_REQUEST['letterenddate']);
            $customer_enddate = str_replace('"',"",$_REQUEST['letterenddate']);
            $customer_enddate .=  " 23:59:59";
//             $where .= " and ( lastmessage <='$customer_enddate')";
            $enddate=$_REQUEST['letterenddate'];
            
            $queryTmp->andWhere(['and', "t.lastmessage<=:letterenddate"],[':letterenddate'=>$customer_enddate]);
        }else {
            $enddate="";
        }
        //权限
        $isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
        if($isParent==false && !empty($_REQUEST['select_platform']) && empty($_REQUEST['account'])){
        	$AuthorizeAccounts = PlatformAccountApi::getPlatformAuthorizeAccounts($_REQUEST['select_platform']);
        	if(!empty($AuthorizeAccounts))
        		$queryTmp->andWhere(['in','t.seller_id', array_keys($AuthorizeAccounts)]);
        	else 
        		$queryTmp->andWhere("t.id is null");//没有账号权限时，强制使sql查不到数据
        }
        if(!empty($_REQUEST['account'])){
//             $where .= " and ( seller_id = '{$_REQUEST['account']}')";
            $queryTmp->andWhere(['and', "t.seller_id=:account"],[':account'=>$_REQUEST['account']]);
        }
        
        if(!empty($_REQUEST['type'])){
//             $where .= " and ( related_type = '{$_REQUEST['type']}')";
            $queryTmp->andWhere(['and', "t.related_type=:type"],[':type'=>$_REQUEST['type']]);
        }
        
        if(isset($_REQUEST['read'])){
            if($_REQUEST['read']==null){
                $where .="";
            }else if($_REQUEST['read'] == 2){//cd订单特殊处理
//                 $where .= " and ( has_replied = '{$_REQUEST['read']}')";
                $queryTmp->andWhere(['and', "t.has_replied=:read and t.original_msg_type=:error"],[':read'=>1,':error'=>'error_states']);
            }else{
                $queryTmp->andWhere(['and', "t.has_replied=:read"],[':read'=>$_REQUEST['read']]);
            }
        }
        //cdiscount的状态和类型
        if(!empty($_REQUEST['session_type'])){
            $queryTmp->andWhere(['and', "t.session_type=:sessionType"],[':sessionType'=>$_REQUEST['session_type']]);
        }
        if(!empty($_REQUEST['session_status'])){
            $queryTmp->andWhere(['and', "t.session_status=:sessionStatus"],[':sessionStatus'=>$_REQUEST['session_status']]);
        }
        
        //标签筛选
        if(!empty($_REQUEST['message_tag'])){
        	$queryTmp->andWhere( " t.ticket_id in ( select cs_ticket_id from cs_ticket_message_tags where tag_id = '{$_REQUEST['message_tag']}')");
        }
        
        //判断选择状态
        if(!empty($_REQUEST['selected_type'])){
            $selected_type=$_REQUEST['selected_type'];
        }else {
            $selected_type='';
        }
        
        //保存查找信息
        $save=array();
        $save['search']=$search;
        $save['letter_startdate']=$startdate;
        $save['letter_enddate']=$enddate;
        
        $allAuthorizeSellerIds = [];
        if(empty($_REQUEST['select_platform'])){
	        $allAuthorizePlatformAccountsArr = UserHelper::getUserAllAuthorizePlatformAccountsArr();
	       
	        if(empty($allAuthorizePlatformAccountsArr)){
	        	$queryTmp->andWhere("t.id is null");//没有账号权限时，强制使sql查不到数据
	        }else{
	        	foreach ($allAuthorizePlatformAccountsArr as $platform=>$accounts){
	        		if(!empty($accounts)){
	        			foreach ($accounts as $key=>$name)
							$allAuthorizeSellerIds[] = $key;
					}
	        	}
	        }
	        //$queryTmp->andWhere(['in','t.seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount']+$allPlatformArr['priceminister']]);
	        $queryTmp->andWhere(['in','t.seller_id',$allAuthorizeSellerIds]);
        }
        $DataCount = $queryTmp->count("1", $conn);

        $pages = new Pagination(['totalCount' =>$DataCount, 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);//defaultPageSize默认页数,'params'=>$_REQUEST带参过滤
//         $letters = $query->andWhere($where)->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        
        $queryTmp->limit($pages->limit);
        $queryTmp->offset($pages->offset);
        
        $letters = $queryTmp->createCommand($conn)->queryAll();
        //测试 排序sql
        //$tmpCommand = $queryTmp->createCommand($conn);
		//echo "<br>".$tmpCommand->getRawSql();
		//exit();
        
        $accounts=TicketSession::find()->distinct('seller_id,platform_source')->select('seller_id,platform_source')->all();//平台帐号
//         $accounts=TicketSession::find()->distinct('seller_id')->select('platform_source, seller_id')->all();//平台帐号
        //只显示有权限的账号，lrq20170828
        $platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
        foreach($accounts as $key => $val){
        	if(!empty($platformAccountInfo[$val->platform_source]) && !in_array($val->seller_id, $platformAccountInfo[$val->platform_source])){
        		unset($accounts[$key]);
        	}
        }
        
        $non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();
        foreach ($letters as $cusKey => $customer){
            $letters[$cusKey]['track_no'] = '';
            if($customer['related_type']=='P'||$customer['related_type']=='O'||$customer['related_type']=='Q'){//Q也是关于cd的订单的
                if($customer['related_id']!=""){
                    $trackingOne=Tracking::find()->where(['order_id'=>$customer['related_id'],'seller_id'=>$customer['seller_id']])
                    ->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
                    if (count($trackingOne)!=0){
                        $letters[$cusKey]['track_no'] = $trackingOne['track_no'];
                        //print_r($trackingOne);
                        if(!in_array($trackingOne['carrier_type'],$non17Track)){
                        	$letters[$cusKey]['tracking_info_type'] = '17track';
                        }else 
                        	$letters[$cusKey]['tracking_info_type'] = '';
                    }
                    unset($trackingOne);
                }
            }
            else{
                continue;
            }
        }
        
        $no_answer = Customer::find()->where(['os_flag'=>1]);
       	//$no_answer->andWhere(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['priceminister']])
        $no_answer->andWhere(['in','seller_id',$allAuthorizeSellerIds]);
        $no_answer->asArray()->all();//为客户表提供查看是否有没回复信息
//         print_r($where);
//         print_r($letters);
//         exit();

        //获取不同平台的卖家账号
        $selleruserids = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(false, true, true);
        
        if(!empty($selleruserids['wish'])){
        	$tmp_wish_a = $selleruserids['wish'];
        	unset($selleruserids['wish']);
        	$selleruserids['wish'] = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($tmp_wish_a);
        }

        $tag_class_list = CustomerTagHelper::getTagColorMapping();
        $tags=Tag::find()->where(" tag_id in (select distinct tag_id from cs_ticket_message_tags where 1) ")->all();

        AppTrackerApiHelper::actionLog("Message", "show all msg"  );//显示所有消息、站内信
        return $this->render('show-letter',['letters'=>$letters,'pages'=>$pages,'accounts'=>$accounts,'save'=>$save,
        		'no_answer'=>$no_answer,'tag_class_list'=>$tag_class_list,'tags'=>$tags,'platform'=>$platform,'selleruserids'=>$selleruserids]);
    }
    /**
     +---------------------------------------------------------------------------------------------
     * 用户的相关信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2015/8/3
     +---------------------------------------------------------------------------------------------
     **/
    public function actionPeopleMessage(){
        if(!empty($_GET['platform_source'])&&!empty($_GET['customer_id'])&&!empty($_GET['seller_id'])){
            $message=Customer::find()->where(['platform_source'=>$_GET['platform_source'],'seller_id'=>$_GET['seller_id'],'customer_id'=>$_GET['customer_id']])->asArray()->one();
            if(!empty($message)){
                $sql="tag_id in (select tag_id from cs_customer_tags where customer_id = {$message['id']})";
                $people_flags=Tag::find()->andWhere($sql)->asArray()->all();
            }else{
                $people_flags=null;
            }
        }else{
            $message=null;
        }       
//         print_r($message);
//         exit();
        AppTrackerApiHelper::actionLog("Message", "show customer profile"  );//查看买家的详细信息
        return $this->renderPartial('people-message',['message'=>$message,'people_flags'=>$people_flags,]);
    }
    //标记为已处理
    public function actionTabSession(){
    	AppTrackerApiHelper::actionLog("Message", "mark handled"  );//标记已处理
        if(!empty($_REQUEST['platform_source'])&&!empty($_REQUEST['ticket_id'])){
            $result=MessageApiHelper::markSessionHandled($_REQUEST['platform_source'], $_REQUEST['ticket_id']);
            if($result['success']==true){
                return "标记成功";
            }else{
                return $result['error'];
            }
        }else{
            return "传递参数异常";
        }
    }
    //标签处理
    public function actionCustomerTag(){
        $tadata=array();
        $aa=array();
        $aa['tag_id']=1;
        $aa['tag_name']="重发";
        $aa['color']="red";
        $aa['classname']="egicon-flag-red";
        $bb['tag_id']=2;
        $bb['tag_name']="需要退款";
        $bb['color']="brown";
        $cc['tag_id']=3;
        $cc['tag_name']="包裹已退回";
        $cc['color']="blue";
        $cc['classname']="egicon-flag-blue";
        $tadata['all_tag'][0]=$aa;
        $tadata['all_tag'][1]=$bb;
        $tadata['all_tag'][2]=$cc;
        $tadata['all_select_tag_id']=array();

        AppTrackerApiHelper::actionLog("Message", "edit tag"  );//编辑客户标签
        exit(json_encode($tadata));
    }
    
//     /**
//      * 生成 添加标签的界面
//      */
//     public function actionGetCustomerTags(){
//     	$this->changeDBPuid();
//     	$tagList = [];
//     	$classList = [];
//     	if (!empty($_REQUEST['customer_id'])){
//     		$tagList = CustomerTagHelper::getALlTagDataByCustomerId($_REQUEST['customer_id']);
//     		$classList = CustomerTagHelper::getTagColorMapping();
//     	}
    	 
//     	return $this->renderPartial('_view_tags_info' , ['TagList'=>$tagList , 'classList'=>$classList]);
//     }//end of actionGetCustomerTags

    /**
     +----------------------------------------------------------
     * 异步 获取tag 数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2014/06/09				初始化
     +----------------------------------------------------------
     **/
    public function actionGetCustomerTagInfo(){
       // $this->changeDBPuid();
        $tagdata = ['all_tag'=>[] , 'all_select_tag_id'=>[]];
        if (!empty($_REQUEST['customer_id'])){
        	$cs_type=(empty($_REQUEST['cs_type']) || $_REQUEST['cs_type']=='false')?'':$_REQUEST['cs_type'];
        	switch ($cs_type){
        		case 'ticket':
        			$tagdata = CustomerTagHelper::getALlTagDataByTicketMessageId($_REQUEST['customer_id']);
        			break;
        		default:
        			$tagdata = CustomerTagHelper::getALlTagDataByCustomerId($_REQUEST['customer_id']);
        			break;
        	}	
        }
        
//         print_r($tagdata);
//         print_r(json_encode($tagdata));
//         exit();
        exit(json_encode($tagdata));
    }
    
    /**
     * 保存 标签
     */
    public function actionSaveTags(){	 
        if (!empty($_REQUEST['customer_id'])){
            $customer_id = $_REQUEST['customer_id'];
        }else{
            exit(json_encode(['success'=>false, 'message'=>'未知错误1']));
        }
        
        if (!empty($_REQUEST['tag_name'])){
            $tag_name = $_REQUEST['tag_name'];
        }else{
            exit(json_encode(['success'=>false, 'message'=>'未知错误2']));
        }
        
        if (!empty($_REQUEST['operation'])){
            $operation = $_REQUEST['operation'];
        }else{
            exit(json_encode(['success'=>false, 'message'=>'未知错误3']));
        }
        
        if (!empty($_REQUEST['color'])){
            $color = $_REQUEST['color'];
        }else{
            exit(json_encode(['success'=>false, 'message'=>'未知错误4']));
        }
        
        $cs_type='';
        if(!empty($_REQUEST['cs_type']) && $_REQUEST['cs_type']!=='false')
        	$cs_type=$_REQUEST['cs_type'];
        
        $result = CustomerTagHelper::saveOneCustomerTag($customer_id, $cs_type, $tag_name, $operation, $color);
    	exit(json_encode($result));
    }
    
    public function actionUpdateOneTrInfo(){
        if(!empty($_REQUEST['customer_id'])){
            $customers=Customer::find()->where(['id'=>$_REQUEST['customer_id']])->asArray()->all();
            foreach ($customers as $cusKey => $customer){
                $customers[$cusKey]['track_no'] = '';
            
                if($customer['last_order_id']!=""){
                    $trackingOne=Tracking::find()->select('track_no')->where(['order_id'=>$customer['last_order_id'],'seller_id'=>$customer['seller_id']])
                    ->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
                    if (count($trackingOne)!=0){
                        $customers[$cusKey]['track_no'] = $trackingOne['track_no'];
                    }
                    unset($trackingOne);
                }
            }
             foreach ($customers as $one){
                $customer_one=$one;
            }
            $flag_data=CustomerTagHelper::getALlTagDataByCustomerId($_REQUEST['customer_id']);//查找相关用户的便签
            $all_flag=$flag_data['all_tag'];                //所有已设置标签
            $selected_flag=$flag_data['all_select_tag_id'];//已选择的标签
            $customer_one['order_num']=$_REQUEST['num'];//订单总数
        }
        return $this->renderPartial('customer_tr_info',['customer'=>$customer_one,'all_flag'=>$all_flag,'selected_flag'=>$selected_flag,]);
    }
    
    public function actionUpdateOneTicketTrInfo(){
    	if(!empty($_REQUEST['ticket_id'])){
    		$where="";
    		$conn=\Yii::$app->subdb;
    		$queryTmp = new Query;
    		$queryTmp->select("t.*")
    			->from("cs_ticket_session t")
    			->leftJoin("cs_ticket_message a1", " a1.ticket_id = t.ticket_id ")
    			->where(['and'," a1.ticket_id =".$_REQUEST['ticket_id']." and t.ticket_id =".$_REQUEST['ticket_id']." and a1.ticket_id=t.ticket_id "]);
    	
    	$queryTmp->distinct();
    	
        $letter = $queryTmp->createCommand($conn)->queryOne();
        
        
        $accounts=TicketSession::find()->distinct('seller_id')->select('seller_id')->all();//平台帐号
        
        $letter['track_no'] = '';
        if($letter['related_type']=='P'||$letter['related_type']=='O'){
			if($letter['related_id']!=""){
				$trackingOne=Tracking::find()->select('track_no')
					->where(['order_id'=>$letter['related_id'],'seller_id'=>$letter['seller_id']])
                    ->orderBy(['update_time'=>SORT_DESC])
					->asArray()->one();
				if (count($trackingOne)!=0){
					 $letter['track_no'] = $trackingOne['track_no'];
				}
                unset($trackingOne);
            }
        }
        
        $flag_data=CustomerTagHelper::getALlTagDataByTicketMessageId($_REQUEST['ticket_id']);//查找相关用户的便签
		$all_flag=$flag_data['all_tag'];                //所有已设置标签
		$selected_flag=$flag_data['all_select_tag_id'];//已选择的标签
    	}
    	//AppTrackerApiHelper::actionLog("Message", "show all msg"  );//显示所有消息、站内信
    	return $this->renderPartial('ticket_message_tr_info',[
    			'letter'=>$letter,
    			'all_flag'=>$all_flag,
    			'selected_flag'=>$selected_flag,]
    	);
    }
    
    /**
     +----------------------------------------------------------
     * 重发消息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/09/24				初始化
     +----------------------------------------------------------
     **/
    public function actionReSentMessage(){
    	//$this->changeDBPuid();
        
    	if(!empty($_GET['seller_id'])&&!empty($_GET['platform_source']) &&  !empty($_GET['msg_id']) &&  !empty($_GET['ticket_id']) &&  !empty($_GET['buyer_id'])){
    		$selleruserid =$_GET['seller_id'];
    		$platform_source=$_GET['platform_source'];
    		$msg_id = $_GET['msg_id'];
    		$ticket_id = $_GET['ticket_id'];
    		$buyer_id =  $_GET['buyer_id'];
    		//重发消息
    		$result = MessageApiHelper::msgStatusFailToPending($selleruserid, $platform_source , 0 , '',$msg_id);
    		
    		//刷新status
    		MessageApiHelper::refreshMessageStatusInfo($ticket_id, $buyer_id);
    	}else{
    		$result = ['success'=>false , 'message'=>TranslateHelper::t('发生未知错误，请联系客服！')];
    	}
    	
    	exit(json_encode ( $result ));
    }
    
    
    /**
     +----------------------------------------------------------
     * 编辑后重发信息调用界面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj 	2015/09/24				初始化
     +----------------------------------------------------------
     **/
    public function actionEditResentMessage(){
        $message=array();
        if(!empty($_GET['seller_id'])&&!empty($_GET['platform_source'])){
            $message['ticket_id']=$_GET['ticket_id'];
            $message['msg_id']=$_GET['msg_id'];
            $message['msg']=$_GET['msg'];
            $message['platform_source']=$_GET['platform_source'];
            $message['seller_id']=$_GET['seller_id'];
            $message['ticket_id']=$_GET['ticket_id'];
            $message['buyer_id']=$_GET['buyer_id'];
            $message['nick_name']=$_GET['nick_name'];
        }
        return $this->renderPartial('resent_message',['message'=>$message,]);
    }
    
    /**
     +----------------------------------------------------------
     * 处理重新编辑后的信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/09/24				初始化
     +----------------------------------------------------------
     **/
    public function actionHandleEditResentMessage(){
    	if(!empty($_GET['seller_id'])&&!empty($_GET['platform_source']) &&  !empty($_GET['ticket_id'])  &&  !empty($_GET['contet']) && !empty($_GET['msg_id']) &&  !empty($_GET['buyer_id'])){
    		$selleruserid =$_GET['seller_id'];
    		$platform_source=$_GET['platform_source'];
    		$ticket_id = $_GET['ticket_id'];
    		$contents = $_GET['contet'];
    		$msg_id = $_GET['msg_id'];
    		$buyer_id =  $_GET['buyer_id'];
    		//先删除原来信息
    		$result = MessageApiHelper::msgNotSendDel($selleruserid, $platform_source, 0 ,'' ,$msg_id );
    		
    		
    		if (!empty($result['success'])){
    			// 增加编辑后信息
    			$params = [
    			'platform_source' => $platform_source,
    			'msgType'=>0,
    			'puid' => \Yii::$app->user->identity->getParentUid(),
    			'contents'=>$contents,
    			'ticket_id'=>$ticket_id,
    			];
    			$result = MessageApiHelper::sendMsgToPlatform($params);
    			
    			//刷新status
    			MessageApiHelper::refreshMessageStatusInfo($ticket_id, $buyer_id);
    		}else{
    			$result['message'] = $result['error'];
    		}
    	}else{
    		$result = ['success'=>false , 'message'=>TranslateHelper::t('发生未知错误，请联系客服！')];
    	}
    	
    	exit(json_encode ( $result ));
    }
    
    /**
     +----------------------------------------------------------
     * 重发 所有失败的站内信 action
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/09/24				初始化
     +----------------------------------------------------------
     **/
    public function actionResendAllFailureMessage(){
		if(!empty($_GET['seller_id'])&&!empty($_GET['platform_source'])  &&  !empty($_GET['ticket_id']) &&  !empty($_GET['buyer_id'])){
    		$selleruserid =$_GET['seller_id'];
    		$platform_source=$_GET['platform_source'];
    		$ticket_id = $_GET['ticket_id'];
    		$buyer_id =  $_GET['buyer_id'];
    		//重发所有消息
    		$result = MessageApiHelper::resendAllFailureMessage($selleruserid,$platform_source,0);
    		
    		//刷新status  $ticket_id, $buyer_id
    		MessageApiHelper::refreshMessageStatusInfo();
    		
    	}else{
    		$result = ['success'=>false , 'message'=>TranslateHelper::t('发生未知错误，请联系客服！')];
    	}
    	
    	exit(json_encode ( $result ));
	}//end of actionResendAllFailureMessage
	
	
	/**
	 +----------------------------------------------------------
	 * 撤销发送的   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/09/24				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCancelFailureMessage(){
        
	    if(!empty($_GET['seller_id'])&&!empty($_GET['platform_source']) &&  !empty($_GET['msg_id']) &&  !empty($_GET['ticket_id']) &&  !empty($_GET['buyer_id']) ){
			$selleruserid =$_GET['seller_id'];
			$platform_source=$_GET['platform_source'];
			$msg_id = $_GET['msg_id'];
			$ticket_id = $_GET['ticket_id'];
			$buyer_id =  $_GET['buyer_id'];
			
			//$result = MessageApiHelper::resendAllFailureMessage($selleruserid,$platform_source,0);
			$result = MessageApiHelper::msgNotSendDel($selleruserid, $platform_source, 0,'' , $msg_id);
			
			//刷新status 
			MessageApiHelper::refreshMessageStatusInfo( $ticket_id, $buyer_id);
		
		}else{
			$result = ['success'=>false , 'message'=>TranslateHelper::t('发生未知错误，请联系客服！')];
		}
		exit(json_encode ( $result ));
		
	}//end of actionCancelFailureMessage
	
	/**
	 +----------------------------------------------------------
	 * 获取重新发送的一条message   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj 	2015/09/24				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetOneInfo(){
	    if(!empty($_GET['ticket_id'])&&!empty($_GET['msg_id'])){
	        $one_info = TicketMessage::find()->where(['ticket_id'=>$_GET['ticket_id'],'msg_id'=>$_GET['msg_id']])->asArray()->one();
	    }
// 	    print_r($one_info);
	    $html='';
	    if(!empty($one_info)){
	    
	        $html="<div class='right-message'>".
	            "<div class='message-content'><p>{$one_info['content']}</p></div>".
	            "<div class='message-header'><div class='message-bottom'>回复&nbsp;&nbsp;</div><div class='message-bottom'>{$_GET['nick_name']}&nbsp;&nbsp;{$one_info['platform_time']}</div></div>".
	            "</div>";
	    }
	    return $html;
	    
	}
	public function actionMailTemplate(){
	    $where = " 1 ";
	    $puid = \Yii::$app->user->identity->getParentUid();
	    if(!empty($puid)){
	        $where .= "and puid in (0,{$puid})";
	    }else{
	        $where .= "and puid = 0";
	    }
        $where_detail = '1';
        if(!empty($_REQUEST['template_search'])){
            //去掉模糊查找的引号。以免SQL注入
            $search = str_replace("'","",$_REQUEST['template_search']);
            $search = str_replace('"',"",$_REQUEST['template_search']);
            $where .= " and id in (select template_id from cs_customer_msg_template_detail where subject 
like '%$search%' or content like '%$search%' )";
            $where_detail .= " and (subject like '%$search%' or content like '%$search%')";//详细信息的条件查询
        }
        
        if(!empty($_REQUEST['template_language'])){
            $where_detail .= " and ( lang = '{$_REQUEST['template_language']}')";
        }
        
        if(!empty($_REQUEST['template_type'])){
            $where .=" and ( type = '{$_REQUEST['template_type']}')";
        }
        
	    $query = CustomerMsgTemplate::find()->orderBy(['seq'=>SORT_ASC,]);
	    
	    $pagination = new Pagination([
	        'defaultPageSize' => 20,
	        'totalCount' => $query->andwhere($where)->count(),
	        'pageSizeLimit'=>[5,200],
	    ]);
	    $query->andwhere($where)->limit($pagination->limit);
	    $query->andwhere($where)->offset($pagination->offset);
	    
	    $data['data'] = $query->andwhere($where)->asArray()->all();
	    $data['pagination']=$pagination; 
	    //查找模版的详细内容
	    if(!empty($data['data'])){
	        foreach($data['data'] as $row ){
	            $idList[] =  $row['id'];
	        }
	        $template_details = CustomerMsgTemplateDetail::find()->andWhere(['template_id'=>$idList])->andWhere($where_detail)->asArray()->all();
	    }
// 	    echo $where."<br />";
// 	    echo $where_detail."<br />";
// 	    print_r($data['data']);
// 	    print_r($template_details);
// 	    exit();
        if(!empty($template_details)){
            foreach($template_details as $details){//映射
                $new_detail[$details['template_id']][] = $details;//同一模版的不同预言
            }
        }else{
            $new_detail="";
        }
        //每一个模版的语言缓存
        $language_tr = array();
        $language_addi_info=array();
        foreach ($data['data'] as &$data_info){//更新当前$data['data']的addi_info
            if(empty($data_info['addi_info'])){
                foreach ($new_detail[$data_info['id']] as $language_detail){
                    $language_tr[$language_detail['lang']] = StandardConst::$LANGUAGES_CODE_NAME_CN[$language_detail['lang']];
                }
                $language_addi_info['lang']=$language_tr;
                CustomerMsgTemplate::updateAll(['addi_info'=>json_encode($language_addi_info)],'id=:lang_id',array(':lang_id'=>$data_info['id']));
                $data_info['addi_info']=json_encode($language_addi_info); //刷新当前传递的addi_info
                $language_tr = array();
                $language_addi_info=array();
            }
        }
	    //language的列表处理
	    $language_array = array();
	    $language = ConfigHelper::getConfig("Message/template_language_statistics",'NO_CACHE');
	    if(empty($language)){
	        $language_list = CustomerMsgTemplateDetail::find()->distinct('lang')->select('lang')->asArray()->all();
	        foreach ($language_list as $language_all){
	            $language_array[$language_all['lang']] = StandardConst::$LANGUAGES_CODE_NAME_CN[$language_all['lang']];
	        }
	        ConfigHelper::setConfig("Message/template_language_statistics", json_encode($language_array));
	    }else{
	        $language_array=json_decode($language,true);
	    }
// 	    print_r($data['data']);
// 	    print_r($new_detail);
	    
	    return $this->render('mail-template',['data'=>$data,'template_detail'=>$new_detail,'language_list'=>$language_array,]);
	}
	
	public function actionUpdateTemplateTr(){
	    //切换语言
	    if(!empty($_GET['tr_num'])&&!empty($_GET['language'])){
	        $data = CustomerMsgTemplate::find()->where(['id'=>$_GET['tr_num']])->asArray()->one();
	        $template_details = CustomerMsgTemplateDetail::find()->where(['template_id'=>$_GET['tr_num'],'lang'=>$_GET['language'],])->asArray()->all();
	    }else{
	        return "";
	    }
	    //行刷新
	    if(!empty($_GET['tr_num'])&&!empty($_GET['update_one'])){
	        $data = CustomerMsgTemplate::find()->where(['id'=>$_GET['tr_num']])->asArray()->one();
	        $template_details = CustomerMsgTemplateDetail::find()->where(['template_id'=>$_GET['tr_num'],])->asArray()->all();
	    }
// 	    print_r($data);
// 	    print_r($template_details);
	    foreach($template_details as $details){
	        $new_detail[$details['template_id']][] = $details;//同一模版的不同预言
	    }
	    if(!empty($data)&&!empty($new_detail)){
	        return $this->renderPartial('mail_Template_tr',['data_tr'=>$data,'template_detail'=>$new_detail]);
	    }else{
	        return "";
	    }
	    
	}
	//新建模版
	public function actionNewTemplate(){
	    $language = ConfigHelper::getConfig("Message/template_language_statistics");
        return $this->renderAjax('new_template',['language'=>json_decode($language)]);
	}
	
	//修改模版
	public function actionEditTemplate(){
	    $language = ConfigHelper::getConfig("Message/template_language_statistics");
	    if(!empty($_GET['id'])&&!empty($_GET['language'])){
	        $one_record=CustomerMsgTemplateDetail::find()->where(['lang'=>$_GET['language'],'template_id'=>$_GET['id']])->asArray()->one();
	    }
	    return $this->renderAjax('new_template',['title'=>$_GET['title'],'one_record'=>$one_record,'language'=>json_decode($language),'template_id'=>$_GET['id'],'select_language'=>$_GET['language']]);
	}
	
	//预览模版
	public function actionPreviewTemplate(){
	    if(!empty($_GET['id'])&&!empty($_GET['language'])){
	        $one_record=CustomerMsgTemplateDetail::find()->where(['lang'=>$_GET['language'],'template_id'=>$_GET['id']])->asArray()->one();
	    }
	    if($one_record){
	        $result = MessageHelper::replaceTemplateData($one_record['subject'], $one_record['content'], "demodata01");
	    }
	    
        return $this->renderPartial('preview_template',['preview_result'=>$result]);
	}
	//同一模版其他语言的模版
	public function actionOtherLanguageTemplate(){
	    $language = ConfigHelper::getConfig("Message/template_language_statistics");
	    return $this->renderAjax('new_template',['title'=>$_GET['title'],'language'=>json_decode($language),'template_id'=>$_GET['id'],'isupdate'=>$_GET['isupdate']]);
	}
	//保存模版
	public function actionSaveTemplate(){
	    if($_GET['template_id']==0&&empty($_GET['letter_template_name']))
	    {
	        $result = ['success'=>false,'message'=>'新建模版名不能为空'];
	        exit(json_encode($result));
	    }
	    
	    if(empty($_GET['subject']))
	    {
	        $result = ['success'=>false,'message'=>'标题不能为空'];
	        exit(json_encode($result));
	    }
	    
	    if($_GET['template_id']!=0&&empty($_GET['letter_template_used'])&&empty($_GET['isupdate']))
	    {
	        $result = ['success'=>false,'message'=>'使用模版名不能为空'];
	        exit(json_encode($result));
	    }
	    
	    if(empty($_GET['letter_template']))
	    {
	        $result = ['success'=>false,'message'=>'内容不能为空'];
	        exit(json_encode($result));
	    }
	    //处理语言缓存
// 	    if($_GET['template_id']!=0){
// 	        $one_template = CustomerMsgTemplate::find()->where(['id'=>$_GET['template_id']])->one();
// 	        $addi_info = json_decode($one_template->addi_info ,true);
// 	        if(!in_array($_GET['letter_template_language'], $addi_info['lang'])){
// 	            $addi_info['lang'][$_GET['letter_template_language']]=StandardConst::$LANGUAGES_CODE_NAME_CN[$_GET['letter_template_language']];
// 	            $one_template->save();
// 	        }
// 	    }
	    $template = [
	        'id'=>$_GET['template_id'],							//模板ID ， 假如新建为0或者不需要设置， 修改需要填入
	        'type'=>"L",        								//模板类型 ， L为卖家模版 ， C为官方模版
	        'puid'=>\Yii::$app->user->identity->getParentUid(),	//puid
	        'seq'=>3,											//优先级
	        'detail_id'=>$_GET['template_detail_id'],			//tempalte detail id 假如新建为0或者不需要设置， 修改需要填入
	        //'template_name'=>$_GET['template_id']==0?$_GET['letter_template_name']:$_GET['letter_template_used'], // 模版名称
	        'lang'=>$_GET['letter_template_language'], 			//模版语言
	        'subject'=>$_GET['subject'], 						// 邮件标题
	        'content'=>$_GET['letter_template']
	    ];
	    if(empty($_GET['isupdate'])){
	        $template['template_name'] = $_GET['template_id']==0?$_GET['letter_template_name']:$_GET['letter_template_used'];
	    }
	    $result = MessageApiHelper::saveCustomerMessageTemplate($template);
	    //语言缓存
	    $tr_array=array();
	    $big_language=array();
	    if($_GET['template_id']!=0){
	        $tr_all_language = CustomerMsgTemplateDetail::find()->where(['template_id'=>$_GET['template_id']])->select('lang')->asArray()->all();
// 	        print_r($tr_all_language);exit();
	        foreach ($tr_all_language as $all_language ){
	            $tr_array[$all_language['lang']] = StandardConst::$LANGUAGES_CODE_NAME_CN[$all_language['lang']];
	        }
	        $big_language['lang']=$tr_array;
	        $one_language = CustomerMsgTemplate::updateAll(['addi_info'=>json_encode($big_language)],'id=:lang_id',array(':lang_id'=>$_GET['template_id']));
	        
	    }
	    exit(json_encode($result));
	}
	
	//删除模版
	public function actionDeleteTemplate(){
	   if(!empty($_GET['id'])){
	       $result = MessageApiHelper::deleteCustomerMessageTemplateData($_GET['id']);
	       exit(json_encode($result));
	   }else{
	       $result = ['success'=>false,'message'=>'数据传递失败'];
	       exit(json_encode($result));
	   }
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据选择的当前订单号，模版与语种， 生成站内信内容
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/06/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionFillTemplateData(){
		if (!empty($_REQUEST['template_id']) && !empty($_REQUEST['language']) ){
			 
			$DetailData = CustomerMsgTemplateDetail::find()->andWhere(['template_id'=>$_REQUEST['template_id'] , 'lang'=>$_REQUEST['language']])->asArray()->one();
			//检查 模版数据是否有存在
			if (empty($DetailData)){
				$result = ['success'=>false,'message'=>'找不到模版！'];
				exit(json_encode($result));
			}
			
			//有模版分有order id 与 无 order id
			if (!empty($_REQUEST['relate_id']) && !empty($_REQUEST['seller_id'])){
				//有订单的情况下， 先找出对应的物流信息， 然后替换对应的内容
				$OneTracking = Tracking::find()
				->andWhere(['seller_id'=>$_REQUEST['seller_id'],'order_id'=>$_REQUEST['relate_id']])
				->asArray()
				->one();
				
				$subject = $DetailData['subject'];
				$content = $DetailData['content'];
				//检查物流号是否存在， 存在则替换内容
				if (!empty($OneTracking['track_no'])){
					$newMsgData = MessageHelper::replaceTemplateData($subject, $content, $OneTracking['track_no']);
					$result = ['success'=>true,'message'=>'','subject'=>$newMsgData['subject'] , 'content'=>$newMsgData['template'] ,];
				}else{
					if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
						//test
						$newMsgData = MessageHelper::replaceTemplateData($subject, $content, 'demodata01');//test kh
						$result = ['success'=>true,'message'=>'','subject'=>$newMsgData['subject'] , 'content'=>$newMsgData['template'] ,];
					}else{
						//production
						/*kh20170401
						//由于trackno 为空 所以不传递track no 而使用 order id 作为参数查找出相关的订单数据
						$newMsgData = MessageHelper::replaceTemplateData($subject, $content, $_REQUEST['relate_id']);
						*/
						$newMsgData = MessageHelper::replaceTemplateData($subject, $content, $OneTracking['track_no']);
						$result = ['success'=>true,'message'=>'','subject'=>$subject , 'content'=>$content ,];
					}
				}
			}elseif (!empty($_REQUEST['ticket_id'])){
				// 与订单无关的 站内信 查询
				$subject = $DetailData['subject'];
				$content = $DetailData['content'];
				
				$newMsgData = MessageHelper::replaceTemplateDataByticketId($subject, $content, $_REQUEST['ticket_id']);
				$result = ['success'=>true,'message'=>'','subject'=>$newMsgData['subject'] , 'content'=>$newMsgData['template'] ,];
				
			}else{
				$subject = $DetailData['subject'];
				$content = $DetailData['content'];
				
				$result = ['success'=>true,'message'=>'','subject'=>$subject , 'content'=>$content ];
			}
		}else{
			$result = ['success'=>false,'message'=>'请选择模版！'];
		}
		exit(json_encode($result));
	}//end of actionFillTemplateData
	
	//发送消息窗口的语言切换
	public function actionDetailMessageLanguage(){
	    $option_html='';
	    if(!empty($_GET['template_id'])){
	        $template_details = CustomerMsgTemplateDetail::find()->where(['template_id'=>$_GET['template_id'],])->select('lang')->asArray()->all();
	    }
	    if(!empty($template_details)){
	        foreach ($template_details as $detail_language){
	            $lang_cn = StandardConst::$LANGUAGES_CODE_NAME_CN[$detail_language['lang']];
	            $option_html .= "<option value='{$detail_language['lang']}'>{$lang_cn}</option>";
	        }
	    }
	    if(!empty($_GET['template_id'])&&$_GET['template_id']== -1){
	        $option_html .= "<option value=-1'>选择语言</option>";
	    }
	    return $option_html;
// 	    $template_details = CustomerMsgTemplateDetail::find()->where(['template_id'=>'1',])->select('lang')->asArray()->all();
// 	    print_r($template_details);
	     
	}
	
	public function actionShowEbayDisputes(){
		$showsearch = 0;
		
// 		$where="";
// 		$conn=\Yii::$app->subdb;
// 		$queryTmp = new Query;
// 		$queryTmp->select("t.*")
// 			->from("cm_ebay_usercase t");
		 
// 		$queryTmp->orderBy(['t.lastmodified_date'=>SORT_DESC]);
		 
// 		$DataCount = $queryTmp->count("1", $conn);
		
// 		$pages = new Pagination(['totalCount' =>$DataCount, 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);//defaultPageSize默认页数,'params'=>$_REQUEST带参过滤
		
// 		$queryTmp->limit($pages->limit);
// 		$queryTmp->offset($pages->offset);
		
// 		$ebayDisputesList = $queryTmp->createCommand($conn)->queryAll();
        $user=\Yii::$app->user->identity;
        $puid = $user->getParentUid();

        $ebayUserArr = \eagle\models\SaasEbayUser::find()->where(['uid'=>$puid])->all();
		$data = CmEbayUsercase::find();
		$showsearch = 0;
		if (!empty($_REQUEST['caseid'])){
			$data->andWhere(['caseid'=>$_REQUEST['caseid']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['srn'])){
			$data->andWhere(['order_source_srn'=>$_REQUEST['srn']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['case_status'])){
			$data->andWhere(['status_value'=>$_REQUEST['case_status']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['selleruserid'])){
			$data->andWhere(['selleruserid'=>$_REQUEST['selleruserid']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['buyeruserid'])){
			$data->andWhere(['buyeruserid'=>$_REQUEST['buyeruserid']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['itemid'])){
			$data->andWhere(['itemid'=>$_REQUEST['itemid']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['case_type'])){
			$data->andWhere(['type'=>$_REQUEST['case_type']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['startdate'])||!empty($_REQUEST['enddate'])){
			//搜索订单日期
			switch ($_REQUEST['timetype']){
				case 'createtime':
					$tmp='created_date';
					break;
				case 'modifytime':
					$tmp='lastmodified_date';
					break;
				case 'respondtime':
					$tmp='respondbydate';
					break;
			}
			if (!empty($_REQUEST['startdate'])){
				$data->andWhere("$tmp >= :stime",[':stime'=>strtotime($_REQUEST['startdate'])]);
			}
			if (!empty($_REQUEST['enddate'])){
				$data->andWhere("$tmp <= :time",[':time'=>strtotime($_REQUEST['enddate'])+24*3599]);
			}
			$showsearch=1;
		}
		
		$allPlatformArr = MessageApiHelper::getPlatformAccountList();
		$data->andWhere(['in','selleruserid',$allPlatformArr['ebay']]);
		
		$data->orderBy('lastmodified_date DESC');
		$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
		$ebayDisputesList = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		$selleruserids = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
		
// 		AppTrackerApiHelper::actionLog("Message", "show all msg"  );//显示所有消息、站内信
		return $this->render('showEbayDisputes',['ebayDisputesList'=>$ebayDisputesList,'pages'=>$pages,'showsearch'=>$showsearch,'ebay_user'=>$ebayUserArr,'selleruserids'=>$selleruserids]);
	}
	
	public function actionEbayManualSync(){
		$startdate = $_POST['startdate'];
		$enddate = $_POST['enddate'];
        $uid = $_POST['ebay_user'];

		
//		$user=\Yii::$app->user->identity;
//		$puid = $user->getParentUid();
//
		$eu = \eagle\models\SaasEbayUser::find()->where(['selleruserid'=>$uid])->one();
//		if (count($ebayUserArr)==0){
//			echo json_encode(array('msg'=>'没有可以同步的账号'));exit();
//		}
		$str = '';
//		foreach ($ebayUserArr as $eu){
			$result = \common\api\ebayinterface\resolution\getusercases::getEbayUserCases($eu,strtotime($startdate),strtotime($enddate)+24*3600);

			if ($result == true && !is_array($result)){
				$str.=$eu->selleruserid."同步成功\r";
			}else{
				$str.=$eu->selleruserid."同步失败:".$result['error']['message'];
			}
//		}
		
		echo json_encode(array('msg'=>$str));
	}
	
	public function actionShowEbayDisputesDetails(){

		$ebayUserCaseEbpdetailone = \eagle\modules\message\models\CmEbayUsercaseEbpdetail::find()->where(['caseid'=>$_GET['caseid']])->one();
		$ebayUserCase = CmEbayUsercase::findOne(['caseid'=>$_GET['caseid']]);
		$result=[];
		//接口获取相应的操作
		if (is_null($ebayUserCaseEbpdetailone)){
			echo '<script>bootbox.alert("数据有误,请联系技术人员");return false;</script>';
		}else{
			//针对非CLOSED状态的纠纷做即时的更新
			$token=SaasEbayUser::findOne(['selleruserid'=>$ebayUserCase->selleruserid])->token;
			if ($ebayUserCase->status_value!='CLOSED'){
				$updatedetail= new getebpcasedetail();
				$updatedetail->eBayAuthToken=$token;
				$updateresult=$updatedetail->api($ebayUserCase->caseid, $ebayUserCase->type);
				if (!$updatedetail->responseIsFailure()){
					if (isset($updateresult['caseSummary']['status'])){
						foreach ($updateresult['caseSummary']['status'] as $key=>$val){
							$ebayUserCase->status_value=$val;
						}
						$ebayUserCase->save();
					}
				}
				$ebayUserCase->has_read=1;
				$ebayUserCase->save();
				$gao = new getactivityoptions();
				$gao->eBayAuthToken=$token;
				$result = $gao->api($ebayUserCase->caseid,$ebayUserCase->type);
			}
		}
		$countrys=EbayCountry::find()->select(['country','description'])->asArray()->all();
		$country=[];
		foreach ($countrys as $c){
			$country[$c['country']]=$c['description'];
		}
		$item=EbayItem::findOne(['itemid'=>$ebayUserCase->itemid]);
		
// 		AppTrackerApiHelper::actionLog("Message", "view message detail" );//查看消息详细内容
		return $this->renderAjax('ebayDisputesDetails',['ebayUserCaseEbpdetailone'=>$ebayUserCaseEbpdetailone,'ebayUserCase'=>$ebayUserCase,'result'=>$result,'country'=>$country,'item'=>$item]);
	}
	
	/**
	 * ajax处理ebp纠纷
	 * @author witsionjs
	 */
	public function actionAjaxEbpcase(){
		if (\Yii::$app->request->isPost){
			$eBayAuthToken=SaasEbayUser::findOne(['selleruserid'=>CmEbayUsercase::findOne(['caseid'=>$_POST['caseid']])->selleruserid])->token;
			switch ($_POST['doselect']){
				case 'offerOtherSolution':
					$oos=new offerothersolution();
					$oos->eBayAuthToken=$eBayAuthToken;
					$result=$oos->api($_POST['caseid'],$_POST['ttype'],$_POST['offerothersolutiontext']);
					break;
				case 'offerRefundUponReturn':
					$rbtr=new offerrefunduponreturn();
					$rbtr->eBayAuthToken=$eBayAuthToken;
					$result=$rbtr->api($_POST['caseid'],$_POST['ttype'],$_POST['requestbuyertoreturn'],
							$_POST['returncity'],$_POST['returncountry'],$_POST['returnname'],$_POST['returnpostalcode'],$_POST['returnstate'],$_POST['returnstreet1'],$_POST['returnstreet2']);
					break;
				case 'escalateToCustomerSupport':
					$etcs=new escalatetocustomersupport();
					$etcs->eBayAuthToken=$eBayAuthToken;
					if ($_POST['ttype']=='EBP_SNAD'){
						$reason=array('sellerSNADReason'=>$_POST['reason']);
					}else{
						$reason=array('sellerINRReason'=>$_POST['reason']);
					}
					$result=$etcs->api($_POST['caseid'],$_POST['ttype'],$_POST['reasontext'],$reason);
					break;
				case 'provideTrackingInfo':
					$pti=new providetrackinginfo();
					$pti->eBayAuthToken=$eBayAuthToken;
					$result=$pti->api($_POST['caseid'],$_POST['ttype'],$_POST['trackingtext'],$_POST['trackingcarrier'],$_POST['trackingnumber']);
					break;
				case 'provideShippingInfo':
					$psi=new provideshippinginfo();
					$psi->eBayAuthToken=$eBayAuthToken;
					$result=$psi->api($_POST['caseid'],$_POST['ttype'],$_POST['trackingtext2'],$_POST['trackingcarrier2'],base::dateTime(strtotime($_POST['shippeddate'])));
					break;
				case 'provideRefundInfo':
					$pri=new providerefundinfo();
					$pri->eBayAuthToken=$eBayAuthToken;
					$result=$pri->api($_POST['caseid'],$_POST['ttype'],$_POST['refundmessage']);
					break;
				case 'offerPartialRefund':
					$opr=new offerpartialrefund();
					$opr->eBayAuthToken=$eBayAuthToken;
					$result=$opr->api($_POST['caseid'],$_POST['ttype'],$_POST['partialrefundmessage'],$_POST['amount']);
					break;
				case 'issuePartialRefund':
					$opr=new issuepartialrefund();
					$opr->eBayAuthToken=$eBayAuthToken;
					$result=$opr->api($_POST['caseid'],$_POST['ttype'],$_POST['partialrefundmessage2'],$_POST['amount2']);
					break;
				case 'issueFullRefund':
					$ifr=new issuefullrefund();
					$ifr->eBayAuthToken=$eBayAuthToken;
					$result=$ifr->api($_POST['caseid'],$_POST['ttype'],$_POST['issuerefundmessage']);
					break;
				case 'appealToCustomerSupport':
					$acs=new appealtocustomersupport();
					$acs->eBayAuthToken=$eBayAuthToken;
					$result=$acs->api($_POST['caseid'],$_POST['ttype'],$_POST['appealreasontext'],$_POST['appealreason']);
					break;
			}
			if (isset($result['ack'])&&($result['ack']=='Success'||$result['ack']=='Warning')){
				if (isset($result['fullRefundStatus'])&&$result['fullRefundStatus']=='FAILED'){
					return Json::encode(['ack'=>'failure','msg'=>'退款操作失败,请检查paypal余额']);
				}
				return Json::encode(['ack'=>'success']);
			}else{
				if (isset($result["errorMessage"]["error"])){
					if (isset($result["errorMessage"]["error"]['message'])){
						$res['0'] = $result["errorMessage"]["error"];
					}else{
						$res = $result["errorMessage"]["error"];
					}
					$str = '';
					foreach ($res as $resv){
						$str.=$resv['message'];
					}
					return Json::encode(['ack'=>'failure','msg'=>$str]);
				}else{
					return Json::encode(['ack'=>'failure','msg'=>$result['error']['message']]);
				}
			}
		}
	}
	
	/**
	 * 订单添加备注,返回修改后的备注内容
	 * @author luzhiliang	2016-01-09
	 */
	public function actionAddOrderDesc(){
		AppTrackerApiHelper::actionLog("Message", "edit order desc");
		if(!isset($_REQUEST['order_id']) || !isset($_REQUEST['desc'])){
			$rtn = array (
    				'result' => false,
    				'message' => '修改失败，缺少订单id或添加的备注为空',
    				'desc'=>'',
    			);
		}else
			$rtn = OrderHelper::addOrderDesc('Message',$_REQUEST['order_id'],$_REQUEST['desc']);
		exit(json_encode ( $rtn ));
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 翻译所有message为英文和中文（暂时只支持cdiscount）
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/4/6
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionTranslateMessage(){
	    $return_array = [];
	    if(empty($_POST['translateIds'])){
	        return ResultHelper::getResult(400, '', "翻译数据有误");
	    }else{
	        $translateIds = $_POST['translateIds'];
	    }
	    $toLanguage = $_POST['toLanguage'];
	    $message_results = TicketMessage::find()->where(['msg_id'=>$translateIds])->all();
	    //查找结果
	    if(empty($message_results)){
	        return ResultHelper::getResult(400, '', "查找不到相关数据");
	    }else{
	        foreach ($message_results as $message_detail){
	            $original_message = preg_replace('/\r|\n/', '', $message_detail->content);
	            $translate_array = TranslateHelper::translate($original_message, 'fra', $toLanguage);//暂时支持cdiscount
	            //检查是否翻译成功
	            if(isset($translate_array['error_code'])){
	                return ResultHelper::getResult(400, '', "翻译失败,原因".$translate_array['error_msg']);
	            }else{
	                $translate_message = $translate_array['trans_result'][0]['dst'];
	            }
	            if($toLanguage == 'zh'){
	                $message_detail->Chineses_content = $translate_message;
	            }else if($toLanguage == 'en'){
	                $message_detail->English_content = $translate_message;
	            }
	            if($message_detail->save()){
	                $Language_array = [];
	                $Language_array['msg_id'] =  $message_detail->msg_id;
	                $Language_array['language'] = $toLanguage;
	                $Language_array['trans_content'] = $translate_message;
	                $return_array[] = $Language_array;
	            }else{
	                return ResultHelper::getResult(400, '', "翻译保存失败，请重试。");
	            }
	        }
	    }
	    return json_encode($return_array);
	}
	
	/**
	 +----------------------------------------------------------
	 * 一键延长买家收货时间的界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/01/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowExtendsBuyerAcceptGoodsTimeBox(){
	    if (!empty($_REQUEST['orderIdList'])){
	
	        if (is_array($_REQUEST['orderIdList'])){
	            $orderList  = OdOrder::find()->where(['order_source_order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
	            return $this->renderPartial('_ExtendsBuyerAcceptGoodsTimeBox.php' , ['orderList'=>$orderList] );
	        }else{
	            return $this->renderPartial('//errorview','E001 内部错误， 请联系客服！');
	        }
	    }else{
	        return $this->renderPartial('//errorview','没有选择订单！');
	    }
	}//end of actionShowExtendsBuyerAcceptGoodsTimeBox
	
	/**
	 +----------------------------------------------------------
	 * 一键延长买家收货时间
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/01/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExtendsBuyerAcceptGoodsTime(){
	    $ressult = ['success'=>true,'message'=>''];
	    if (!empty($_REQUEST['extenddataList'])){
	        foreach($_REQUEST['extenddataList'] as $row){
// 	            $tmpRT = AliexpressOrderHelper::ExtendsBuyerAcceptGoodsTime($row['order_id'], $row['extendday']);
	            $tmpRT = OrderApiHelper::extendAliexpressOrderBuyerAcceptGoodsTime($row['order_id'],$row['extendday']);
				
	            if (empty($tmpRT['success'])){
	                $errorMsg = "";
	                //延长失败！
	                $ressult['success'] = false;
	                	
	                $ressult['message'] .= $row['order_id'].' ';
	                	
	                if (!empty($tmpRT['memo']) ) $errorMsg .= $tmpRT['memo'].' ';
					if (!empty($tmpRT['message']) ) $errorMsg .= $tmpRT['message'].' ';
					
					if (empty($errorMsg)) $errorMsg = '网络异常，延长失败！';
					$ressult['message'] .= $errorMsg.'<br>';
	            }
	
	        }
	        exit(json_encode($ressult));
	    }else{
	        exit(json_encode(['success'=>false ,'message'=>TranslateHelper::t('找不到订单信息'), 'rt'=>$tmpRT]));
	    }
	}//end of actionExtendsBuyerAcceptGoodsTime
	
	public function actionUpateToHasRead(){
		$ticket_ids = empty($_REQUEST['ticket_ids'])?'':json_decode($_REQUEST['ticket_ids']);
		if(empty($ticket_ids))
			exit(json_encode(['succss'=>false,'message'=>'请选择未读信息']));
		
		if(is_string($ticket_ids)){
			$tmp_id = $ticket_ids;
			$ticket_ids = [];
			$ticket_ids[] = $tmp_id;
		}
		
		$result = ['success'=>true,'message'=>''];
		foreach ($ticket_ids as $ticket_id){
			try{
				$rtn = MessageApiHelper::msgUpateHasRead($ticket_id, null);//批量更新已读状态
				if(!$rtn['success']){
					$result['success'] = false;
					$result['message'] .= empty($rtn['error'])?$ticket_id.' UpateHasRead error;':$rtn['error'];
				}
			}catch (\Exception $e){
				$result['success'] = false;
				$result['message'] .= $ticket_id.' UpateHasRead catch Exception;';
			}
		}
		exit(json_encode($result));
	}
	public function actionSentMessageByMyslef(){
	    if(empty($_GET['message'])){
	        return;
	    }
	    if(!empty($_GET['ticket_id'])){
	        $other=TicketSession::find()->where(['ticket_id'=>$_GET['ticket_id'],])->one();
	    }
	    $message=array();
	    $message['sent_myself'] = 1;
	    $message['platform_source']=$other->platform_source;
	    $message['msgType']=$other->message_type;
	    $message['puid']=\Yii::$app->user->identity->getParentUid();
	    $message['contents']=$_GET['message'];
	    $message['ticket_id']=$other->ticket_id;
	    $message['orderId']=$other->related_id;
	    $message["item_id"]=$other->item_id;
	    $nickname=$other->seller_nickname;
	    $date=date("Y-m-d H:i:s",time());
	    $result=MessageApiHelper::sendMsgToPlatform($message);
	    //         print_r($result);
	    //         exit();
// 	    $html='';
// 	    if($result['success']==1){
	    
// 	        $html="<div class='right-message'>".
// 	            "<div class='message-content'><p>{$_GET['message']}</p></div>".
// 	            "<div class='message-header'><div class='message-bottom'>回复&nbsp;&nbsp;</div><div class='message-bottom'>{$nickname}&nbsp;&nbsp;{$date}</div></div>".
// 	            "</div>";
// 	    }
// 	    AppTrackerApiHelper::actionLog("Message", "send message"  );//发送消息
// 	    return $html;
	}
}