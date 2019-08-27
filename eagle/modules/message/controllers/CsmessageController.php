<?php

namespace eagle\modules\message\controllers;

use Yii;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\message\helpers\MessageBGJHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\GoogleHelper;
use yii\base\Action;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\message\models\AutoRoles;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\models\MsgTemplate;


class CsmessageController extends \eagle\components\Controller{
	//public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man
 
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    
    public function actionSpeed(){
    
    	$rtn = array();
    	$now_str = date('Y-m-d H:i:s');
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$start_time = date('Y-m-d H:i:s');
    
    	$current_time=explode(" ",microtime());
    	$time1=round($current_time[0]*1000+$current_time[1]*1000);
    
    	$coreCriteria = "status='P'   " ;
    
    	//防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
    	//查询3小时内玩过的
    
    	$coreCriteria .= ' and priority =  5 ' ;
    	$pendingOne = Yii::$app->get('db')->createCommand(
    				"select * from message_api_queue force index (status_2) where $coreCriteria  limit 50")
    				->queryAll();
    
    	$sub_id1 ='';
    	$current_time=explode(" ",microtime());
    	$time2=round($current_time[0]*1000+$current_time[1]*1000);
    	$run_time  = $time2 - $time1; //这个得到的$time是以 ms 为单位的
     
    	$MainQueueP = Yii::$app->get('db')->createCommand("SELECT count(1)  FROM  `message_api_queue` WHERE  `status` =  'P'")->queryScalar();
     
    	$usuageTable="<br><br><br><table><tr><td>外部接口</td><td>时间段(小时)</td>
 						<td>调用次数</td><td>平均耗时(s)</td></tr>";
    	$allDetail = Yii::$app->get('db')->createCommand("select * from ut_ext_call_summary where (ext_call like 'CS%' or ext_call like 'MS%') and time_slot like '".substr($now_str,0,10)."%' order by ext_call,time_slot")->queryAll();
    	$lastExtCall='';
    	$Ext_Call_Chs['Tracking.17Track']= '17Track 查询接口';
    	$Ext_Call_Chs['Tracking.Ubi']= 'Ubi 查询接口';
    	$Ext_Call_Chs['Trk.MainQQuery']= '查询一个物流号';
    	$Ext_Call_Chs['Trk.MainQPickOne']= '主队列提取一个物流号';
    	$Ext_Call_Chs['MS.MainQPickOne']= '发信主队列提取一个物流号';
    	$Ext_Call_Chs['CS.MS.aliexpress']= '速卖通发信';
    	$Ext_Call_Chs['CS.MS.ebay']= 'eBay发信';
    	
    	
    	$subTotal=0;
    	foreach ($allDetail as $aDetail){
    		if ($lastExtCall <>  $aDetail['ext_call'] and $lastExtCall<>''){
    			//subTotal
    			$usuageTable .=  "<tr><td width='170px'> </td>
 				<td width='170px'>合计</td>
 				<td width='170px'>". number_format($subTotal)."</td>
 				<td width='170px'> </td></tr> ";
    			$usuageTable .= "</table><br><table>";
    			$subTotal = 0;
    		}
    
    		//如果是没有有效值，就skip这个时段好了
    		if (!empty($aDetail['total_count']))
    			$usuageTable .=  "<tr><td width='170px'>".$Ext_Call_Chs[$aDetail['ext_call']]."</td>
 				<td width='170px'>".$aDetail['time_slot']."(小时)</td>
 				<td width='170px'>".number_format($aDetail['total_count'])."</td>
 				<td width='170px'>". ($aDetail['average_time_ms']/1000) ."</td></tr>";
    
    		$lastExtCall =  $aDetail['ext_call'];
    		$subTotal += $aDetail['total_count'];
    	}
    
    	if ( $lastExtCall<>''){
    		//subTotal
    		$usuageTable .=  "<tr><td width='170px'> </td>
 				<td width='170px'>合计</td>
 				<td width='170px'>". number_format($subTotal)."</td>
 				<td width='170px'> </td></tr> ";
    
    		$subTotal = 0;
    	}
    
    	$usuageTable .= "</table>";
    
    
    	return "$start_time<br>查询MainQueue一条Pending后: 耗时 $run_time 毫秒 <br> <br> MainQueue Pending 有 $MainQueueP ".$usuageTable ;
    
    }
    
	public function actionYs(){
    	 
    	$rtn = array();
    	 
    	$start_time = date('Y-m-d H:i:s');

    	MessageBGJHelper::putToMessageQueueBuffer(['puid'=>1,'create_time'=>$start_time,
    			'subject'=>"I'm sending a parcel",'content'=>'Hi, have you receive it?track no is "RG32423423fCN"',
                'platform'=>'ebay','order_id'=>'123123123','seller_id'=>'SL11' ]);
    	MessageBGJHelper::putToMessageQueueBuffer(['puid'=>2,'create_time'=>$start_time,
    			'subject'=>"Amigo~~",'content'=>'Hola,Como estas? track no is "RG32423423fCN"',
    			'platform'=>'aliexpress','order_id'=>'122323-5434f-123','seller_id'=>'SL22']);
    	
    	MessageBGJHelper::postMessageApiQueueBufferToDb();
    	
    	$rtn = MessageBGJHelper::make17TrackMessageTail(1,'301239055611-1093723491020');
    	$end_time = date('Y-m-d H:i:s');
    	return "$start_time<br>Tracked and got  result:<br>".print_r($rtn,true) ."<br>$end_time" ;
    }
    
    public function actionVersionUp(){
    	$versioned = array('Message/sendQueueVersion' );
    	$versions_Now = '';
    	foreach ($versioned as $appName){
    		$last_version = ConfigHelper::getGlobalConfig($appName,'NO_CACHE');
    		if (empty($last_version))
    			$last_version = 0;
    
    		$last_version ++;
    		ConfigHelper::setGlobalConfig($appName, $last_version);
    		$versions_Now .= "<br> $appName , $last_version ";
    	}
    	return     	   $versions_Now;
    }
    
    /**
     +----------------------------------------------------------
     * 设置匹配无规则页面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/07/14				初始化
     +----------------------------------------------------------
     **/
    public function actionRoleSetting(){
    	$result = MessageHelper::getlistRoleSetting();
    	return $this->renderPartial('role_setting', $result);
    }//end of actionRoleSetting
    
    /**
     +----------------------------------------------------------
     * 删除 匹配规则
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/07/14				初始化
     +----------------------------------------------------------
     **/
    public function actionDeleteRole(){
    	$result = [];
    	if (!empty($_REQUEST['role_id'])){
    		//role id is not empty 
    		
    		if (is_numeric($_REQUEST['role_id'])){
    			
    			$record = MessageHelper::deleteMessageRole($_REQUEST['role_id']);;
    			
    			if (!empty($record)){
    				$result = ['success'=>true , 'message'=>"成功删除".$record."条规则"];
    				$result['data'] = MessageHelper::getlistRoleSetting();
    			}else{
    				$result = ['success'=>false , 'message'=>"该规则已经删除!"];
    			}
    		}else{
    			//role id 无效
    			$result = ['success'=>false , 'message'=>"选中无效的规则!"];
    		}
    	}else{
    		//没有role id
    		$result = ['success'=>false , 'message'=>"没有选中规则!"];
    	}
    	exit(json_encode($result));
    }//end of actionDeleteRole
    
    /**
     +----------------------------------------------------------
     * 规则提升一个优先级
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/07/14				初始化
     +----------------------------------------------------------
     **/
    public function actionSetRoleUp() {
    	$result = [];
    	if (!empty($_REQUEST['role_id'])){
    		//role id is not empty 
    		
    		if (is_numeric($_REQUEST['role_id'])){
    			$record = MessageHelper::setRoleUp($_REQUEST['role_id']);
    			$result = ['success'=>true , 'message'=>"操作成功"];
    			$result['data'] = MessageHelper::getlistRoleSetting();
    		
    		}else{
    			//role id 无效
    			$result = ['success'=>false , 'message'=>"选中无效的规则!"];
    		}
    	}else{
    		//没有role id
    		$result = ['success'=>false , 'message'=>"没有选中规则!"];
    	}
    	exit(json_encode($result));
    }//end of actionSetRoleUp
    
    /**
     +----------------------------------------------------------
     * 保存规则
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2015/07/14				初始化
     +----------------------------------------------------------
     **/
    public function actionSaveRole(){
    	
    	if (!empty($_REQUEST['role_id'])){
    		$data['id'] = $_REQUEST['role_id'];
    	}
    	
    	if (!empty($_REQUEST['template_id'])){
    		$data['template_id'] = $_REQUEST['template_id'];
    	}
    	
    	if (!empty($_REQUEST['layout_id'])){
    		$data['layout_id'] = $_REQUEST['layout_id'];
    	}
    	
    	if (!empty($_REQUEST['role_name'])){
    		$data['name'] = $_REQUEST['role_name'];
    	}
    	
    	if (!empty($_REQUEST['priority'])){
    		$data['priority'] = $_REQUEST['priority'];
    	}
    	
    	if (!empty($_REQUEST['platform_account'])){
    		$data['platform'] = [];
    		$data['accounts'] = [];
    		foreach($_REQUEST['platform_account'] as $plarform_account =>$isActive){
    			//前台返回的是string 而不是boolean
    			if ($isActive !='false'){
    				//如果 是有效的 平台 和账号则放进
    				$tmpRow = explode(':', $plarform_account);
    				if (! in_array($tmpRow[0] ,$data['platform'] )){
    					$data['platform'][] = $tmpRow[0];
    				}
    				
    				$data['accounts'][$tmpRow[0]][] = $tmpRow[1];
    			}
    		}
    		
    		if (!empty($data['platform'] )) $data['platform']  = json_encode($data['platform'] );
    		if (!empty($data['accounts'] )) $data['accounts']  = json_encode($data['accounts'] );
    	}
    	
    	if (!empty($_REQUEST['nation'])){
    		$data['nations'] = [];
    		foreach($_REQUEST['nation'] as $code =>$isActive){
    			//前台返回的是string 而不是boolean
    			if ($isActive !='false'){
    				//如果 是有效的 国家 则放进
    				$data['nations'][] = $code;
    			}
    		}
    		if (!empty($data['nations'] )) $data['nations']  = json_encode($data['nations'] );
    	}
    	
    	
    	
    	if (!empty($_REQUEST['ship_status'])){
    		$data['status'] = [];
    		foreach($_REQUEST['ship_status'] as $status =>$isActive){
    			//前台返回的是string 而不是boolean
    			if ($isActive !='false'){
    				//如果 是有效的 国家 则放进
    				$data['status'][] = $status;
    			}
    		}
    		if (!empty($data['status'] )) $data['status']  = json_encode($data['status'] );
    	}
    	
    	$result = MessageHelper::saveRole($data);
    	
    	$result['data'] = MessageHelper::getlistRoleSetting();
    	exit(json_encode($result));
    }//end of actionSaveRole
    
    
    
}
