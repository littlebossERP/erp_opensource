<?php

namespace eagle\modules\tracking\controllers;

use Yii;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\DataStaticHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use yii\base\Action;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasWishUser;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper;
use eagle\modules\message\helpers\MessageBGJHelper;
use yii\base\Exception;
use eagle\modules\tracking\helpers\TrackingTagHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\order\models\WishOrder;
use eagle\modules\order\helpers\WishOrderHelper;
use eagle\models\SaasDhgateUser;
use eagle\modules\util\helpers;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use yii\data\Sort;
use eagle\modules\message\models\MsgTemplate;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\tracking\helpers\TrackingHelperTest;
use eagle\modules\tracking\helpers\TrackingQueueHelper;
use eagle\modules\tracking\models\Tag;
use eagle\modules\message\models\Message;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\permission\helpers\UserHelper;

/**
 * TrackingController implements the CRUD actions for Tracking model.
 */
class TrackingController extends \eagle\components\Controller{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

	// 格式：array('aa'=>'bb')
	public $TOKENS = array();
	 
	public function setThisSessionForPuid(){
		if (!isset($_GET['token']) or empty($_GET['token']) or
			 (empty($_GET['name']) and empty($_GET['puid']))
		   )
			return 0;
		
		$whoCalling ='';
		foreach ($this->TOKENS as $who=>$hisToken){
			if ($_GET['token'] == $hisToken)
				$whoCalling = $who;
		}
		
		if ($whoCalling <>'' ){
			if (!empty($_GET['puid'])){
				$_SESSION['puid'] = $_GET['puid'];
				$_SESSION['name'] = Yii::$app->get('db')->createCommand("select user_name from user_database where did=".$_GET['puid'])->queryScalar();				
			}elseif(!empty($_GET['name'])){
				$_SESSION['name'] = $_GET['name'];
				$_SESSION['puid'] = Yii::$app->get('db')->createCommand("select did from user_database where user_name ='".trim($_GET['name'])."'")->queryScalar();
			}
			
			$message = "BackDoor：内部 $whoCalling 登入 并尝试设置获取  ".$_SESSION['puid']."-".$_SESSION['name']." 的信息";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
		}
	}

    public function is_ip($gonten){
        $ip = explode(".",$gonten);
        for($i=0;$i<count($ip);$i++)
        {
            if($ip[$i]>255){
                return (0);
            }
        }
        return ereg("^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$",$gonten);
    }
  
 
    //called by $this->get_ip();
	public function get_ip(){
		$realip="";
        //判断服务器是否允许$_SERVER
        if(isset($_SERVER)){    
            if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            }elseif(isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            }else{
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        }else{
            //不允许就使用getenv获取  
            if(getenv("HTTP_X_FORWARDED_FOR")){
                  $realip = getenv( "HTTP_X_FORWARDED_FOR");
            }elseif(getenv("HTTP_CLIENT_IP")) {
                  $realip = getenv("HTTP_CLIENT_IP");
            }else{
                  $realip = getenv("REMOTE_ADDR");
            }
        }
    
        return $realip;
    }  
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 植入后门时候使用，查看是否有session中指定了puid，如果是，使用该puid 的db
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function changeDBPuid(){
		$this->setThisSessionForPuid();
		if (!isset($_SESSION['puid']) or empty($_SESSION['puid'])  
		)
			return false;
		
		$puid = $_SESSION['puid'];
		 
		\Yii::$app->user->identity['puid'] = $puid;
		return true;
	}
	
    public function behaviors()
    {
        return [
         	'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }
  
    /**
     * Lists all Tracking models.
     * @return mixed
     */
    public function actionIndex(){
    	$this->changeDBPuid();
    	$puid1 = \Yii::$app->subdb->getCurrentPuid();
    
    	AppTrackerApiHelper::actionLog("Tracker", "进入录入物流单号:"   );
    
    	$dataProvider = new ActiveDataProvider([
    			'query' => Tracking::find(),
    			]);
    
    	return $this->render('manual_import_tracking', [
    			'dataProvider' => $dataProvider,
    			]);
    }
    
    public function actionYs(){
    	global $CACHE ;
    	$CACHE['JOBID'] = 'ystest';
    	
    	$this->changeDBPuid();
    	$rtn = array();
    	$aTracking = new Tracking();
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$start_time = date('Y-m-d H:i:s');
    	$now_str = date('Y-m-d H:i:s');
    	
    	$a[] = 'apple';
    	$a[] = 'bananer';
    	$a[] = 'cats';
    	$a[] = 'dogs';
    	
    	$b[]= 'red';
    	$b[]= 'yellow';
    	$b[]= 'white ';
    	$b[]= 'coffe ';
    	
    	$rtn = [];
 
    	$rtn['a1'] = TrackingAgentHelper::encodeOrderNumber(1);
    	$rtn['a2'] = TrackingAgentHelper::encodeOrderNumber(2);
    	$rtn['a10'] = TrackingAgentHelper::encodeOrderNumber(10);
    	$rtn['a100'] = TrackingAgentHelper::encodeOrderNumber(100);
    	$rtn['a345'] = TrackingAgentHelper::encodeOrderNumber(345);
    	$rtn['a50000'] = TrackingAgentHelper::encodeOrderNumber(50000);
    	$rtn['a150000'] = TrackingAgentHelper::encodeOrderNumber(150000);
    	$rtn['a950000'] = TrackingAgentHelper::encodeOrderNumber(950000);
	 
    	return  "<br> puid $puid status is <br>This trial: start time: $start_time<br> :<br> a= ".print_r($rtn,true) ."<br>  <br>b=  " .print_r($rtn,true);
    }
   
    public function actionYs3(){
    	global $CACHE ;
    	$this->changeDBPuid();
    	$start_time = date('Y-m-d H:i:s');
		echo "start service runnning for action CdOfferTerminaterCommitHP at $start_time";
	
		$rtn = \eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper::CheckNormalProducts(\Yii::$app->subdb->getCurrentPuid());
	
		$start_time = date('Y-m-d H:i:s');
				echo "<br>end service runnning for action CdOfferTerminaterCommitHP at $start_time";
    }
    			    
    

    public function actionSpeed(){
    		
    	$rtn = array();
    	$now_str = date('Y-m-d H:i:s');
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$start_time = date('Y-m-d H:i:s');
    
    	$current_time=explode(" ",microtime());
    	$time1=round($current_time[0]*1000+$current_time[1]*1000);
    
    	$coreCriteria = "status='P' and id % 15 = 5 " ;
    		
    	//防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
    
    	//查询3小时内玩过的
    	 
		$coreCriteria .= ' and priority =  5 ' ;
		$pendingOne = Yii::$app->get('db_queue')->createCommand(
				"select * from tracker_api_queue force index (status_2) where $coreCriteria order by priority, run_time,id asc limit 6")
				->queryAll();

    	$sub_id1 ='';
    	$current_time=explode(" ",microtime());
    	$time2=round($current_time[0]*1000+$current_time[1]*1000);
    	$run_time  = $time2 - $time1; //这个得到的$time是以 ms 为单位的
    
    	$pendingSubOne = TrackerApiSubQueue::find()
    	->andWhere( ($sub_id1=='')?"sub_queue_status='P' ":" sub_id=$sub_id1" )
    	->one();
    
    	$current_time=explode(" ",microtime());
    	$time3=round($current_time[0]*1000+$current_time[1]*1000);
    	$run_time2  = $time3 - $time2; //这个得到的$time是以 ms 为单位的
    
    	$MainQueueS = Yii::$app->get('db_queue')->createCommand("SELECT count(1)  FROM  `tracker_api_queue` WHERE  `status` =  'S'")->queryScalar();
    	$MainQueueP = Yii::$app->get('db_queue')->createCommand("SELECT count(1)  FROM  `tracker_api_queue` WHERE  `status` =  'P'")->queryScalar();
    
    	$SubQueueS = Yii::$app->get('db_queue')->createCommand("SELECT count(1)  FROM  `tracker_api_sub_queue` WHERE  `sub_queue_status` =  'S'")->queryScalar();
    	$SubQueueP = Yii::$app->get('db_queue')->createCommand("SELECT count(1)  FROM  `tracker_api_sub_queue` WHERE  `sub_queue_status` =  'P'")->queryScalar();
    
    	$usuageTable="<br><br><br><table><tr><td>外部接口</td><td>时间段(小时)</td>
 						<td>调用次数</td><td>平均耗时(s)</td></tr>";
    	$allDetail = Yii::$app->get('db')->createCommand("select * from ut_ext_call_summary where ext_call like 'Tr%' and time_slot like '".substr($now_str,0,10)."%' order by ext_call,time_slot")->queryAll();
    	$lastExtCall='';
    	$Ext_Call_Chs['Tracking.17Track']= '17Track 查询接口';
    	$Ext_Call_Chs['Tracking.Ubi']= 'Ubi 查询接口';
    	$Ext_Call_Chs['Trk.MainQQuery']= '查询一个物流号';
    	$Ext_Call_Chs['Trk.MainQPickOne']= '主队列提取一个物流号';
    	$Ext_Call_Chs['CS.MS.wish']= 'CS.MS.wish';

    	$subTotal=0;
    	foreach ($allDetail as $aDetail) 
    		$allDetailKeyed[$aDetail['ext_call']][$aDetail['time_slot']] = $aDetail;
    	
    	foreach ($allDetail as $aDetail){
    		if ($lastExtCall <>  $aDetail['ext_call'] and $lastExtCall<>'' 
    				and strpos($aDetail['ext_call'], "Up") === false  and strpos($aDetail['ext_call'], "Down") === false){
    			//subTotal
    			$usuageTable .=  "<tr><td width='170px'> </td>
 				<td width='170px'>合计</td>
 				<td width='170px'>". number_format($subTotal)."</td>
 				<td width='170px'> </td></tr> ";
    			$usuageTable .= "</table><br><table>";
    			$subTotal = 0;
    		}
    		
    		
    		
    		//如果是没有有效值，就skip这个时段好了
    		if (!empty($aDetail['total_count']) and strpos($aDetail['ext_call'], "Down") === false ){
    			$itsDownName = str_replace("Up",'Down',$aDetail['ext_call']);
    			$color = '';
    			if ($itsDownName <>$aDetail['ext_call'] and  isset($allDetailKeyed[$itsDownName][$aDetail['time_slot']])){
    				$itsDownCount = $allDetailKeyed[$itsDownName][$aDetail['time_slot']]['total_count'];
    				if ($itsDownCount <> $aDetail['total_count'])
    					$color='red';
    			}else{
    				$itsDownCount='';
    			}
    				 
    			$usuageTable .=  "<tr><td width='170px'>".(isset($Ext_Call_Chs[$aDetail['ext_call']])?$Ext_Call_Chs[$aDetail['ext_call']]:$aDetail['ext_call']).
    			"</td>
 				<td width='170px'>".$aDetail['time_slot']."(小时)</td>
 				<td width='170px' ".(empty($color)?"":' style="color:'.$color.'"').">".number_format($aDetail['total_count']).($itsDownCount==''?'': (" / " . $itsDownCount))."</td>
 				<td width='170px'>". ($aDetail['average_time_ms']/1000) ."</td></tr>";
    		}
    		
    		$lastExtCall =  $aDetail['ext_call'];
    		$subTotal += $aDetail['total_count'];
    	}
    
    	if ( $lastExtCall<>'' and strpos($aDetail['ext_call'], "Up") === false  and strpos($aDetail['ext_call'], "Down") === false){
    		//subTotal
    		$usuageTable .=  "<tr><td width='170px'> </td>
 				<td width='170px'>合计</td>
 				<td width='170px'>". number_format($subTotal)."</td>
 				<td width='170px'> </td></tr> ";
    
    		$subTotal = 0;
    	}
    
    	$usuageTable .= "</table>";
    
    
    	$html =  "$start_time<br>查询MainQueue一条Pending后: 耗时 $run_time 毫秒 <br>查询SubQueue一条Pending后: 耗时 $run_time2 毫秒 <br><br> MainQueue Pending 有 $MainQueueP, 正在做Scoring的有 $MainQueueS <br>SubQueue Pending 有 $SubQueueP, 正在做Scoring的有 $SubQueueS".$usuageTable ;
    //********************************************************************************
    	//Phase 2: below is for message send monitor
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
    			"select * from message_api_queue   where $coreCriteria  limit 50")
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
    	$Ext_Call_Chs['MS.MainQPickOne']= '发信主队列提取50个';
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
    	
    	$casesDiv = '<style>.case_tb th,.case_tb td{border:1px solid}</style><div style="width:500px;"><table class="case_tb" cellspacing="0" style="position:fixed;right:0px;top:0px;"><tr><th colspan="5">待处理case</th></tr><tr><th>uid</th><th>track_no</th><th>carrier_type</th><th>url</th><th>desc</th></tr>';
    	$query = "SELECT * FROM `tracker_cases` WHERE `status`='P' ORDER BY `id` ASC";
    	$command = Yii::$app->db->createCommand($query);
    	$caseRecords = $command->queryAll();
    	foreach ($caseRecords as $case){
    		$casesDiv .= '<tr><td>'.$case['uid'].'</td><td>'.$case['track_no'].'</td><td>'.(empty(CarrierTypeOfTrackNumber::$expressCode[$case['carrier_type']])?'未知':CarrierTypeOfTrackNumber::$expressCode[$case['carrier_type']]).'</td><td>'.$case['customer_url'].'</td><td>'.$case['desc'].'</td></tr>';
    	}
    	$casesDiv .= '</table></div>';
    	return $html . "<br>$start_time<br>查询MainQueue一条Pending后: 耗时 $run_time 毫秒 <br> <br> MainQueue Pending 有 $MainQueueP ".$usuageTable.$casesDiv ;
    	
    	
    }
    
    public function actionVersionUp(){
    	$versioned = array('Tracking/mainQueueVersion','Tracking/subQueueVersion',
    			'Tracking/postBufferIntoTrackQueueVersion','Message/sendQueueVersion'
    			,'Message/aliexpressOrdermsgmsgGetListVersion'
    			,'Message/aliexpressMsgmsgGetListVersion' ,'Message/dhgateMsgmsgGetListVersion'
                ,'Message/ebayMsgmsgGetListVersion' ,'Message/wishMsgmsgGetListVersion'
    			,'htmlcatcher/HtmlCatchDataQueueVersion','CDOT/QueueVersion'	
    	          );
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
     * Displays a single Tracking model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id){
    	$this->changeDBPuid();
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Tracking model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {	$this->changeDBPuid();
        $model = new Tracking();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Tracking model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {	$this->changeDBPuid();
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

	/**
	 +---------------------------------------------------------------------------------------------
	 * 展示绑定平台的情况，或者是添加，修改绑定某个store
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     Get['platform']	要显示的平台名
	 * @action    Get['action']	        展示或者修改或者是添加
	 +---------------------------------------------------------------------------------------------
	 * @return						
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/3/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public function actionBindPlatformStore()
    {	 /* 以下逻辑已经被移动到 action platform_account_binding
    	
    	$allUserList = array();
    	
    	// 获取ebay user list
    	$ebayUserList = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
    	$allUserList["ebayUserList"] = $ebayUserList;
    	
    	
    	// 获取速卖通 user list
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	
    	$users = SaasAliexpressUser::find()->where('uid ='.$uid)
    	->orderBy('refresh_token_timeout desc')
    	->asArray()
    	->all();
    	
    	$aliexpressUserList = array();
    	foreach ($users as $user){
    		$user['refresh_token_timeout'] = $user['refresh_token_timeout'] > 0?date('Y-m-d',$user['refresh_token_timeout']):'未授权';
    		$aliexpressUserList[] = $user;
    	}
    	$allUserList['aliexpressUserList'] = $aliexpressUserList;
    	
    	
    	return $this->render('bind_platform_store' , $allUserList);
    */
    }

    /**
     +---------------------------------------------------------------------------------------------
     * 展示Ebay平台订单的情况
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/3/24				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionEbayOrderList(){
    	$ebayAccountNum = EbayAccountsApiHelper::countBindingAccounts();
    	if($ebayAccountNum <= 0 ){
    		return $this->render('no_account_bind' , ['platform'=>'ebay']);
    	}
	    	
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 展示Aliexpress平台订单的情况
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/3/24				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionAliexpressOrderList(){
    	$aliexpressAccountNum = AliexpressAccountsApiHelper::countBindingAccounts();
    	if($aliexpressAccountNum <= 0 ){
    		return $this->render('no_account_bind' , ['platform'=>'aliexpress']);
    	}
    	
    }
    
    
    /**
     * 查询 物流信息
     */
    public function actionQuery_tracking_process(){
    	global $CACHE;
    	$puid1 = \Yii::$app->subdb->getCurrentPuid();
    	\Yii::info("actionQuery_tracking_process uid:".$puid1."params:".print_r($_REQUEST,true),'file');
    	$this->changeDBPuid();
    	//force update the top menu statistics
    	ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
    	ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
    	
    	$tracklist = [];
    	if (! empty($_POST['trackinglist'])){
    		//手工录入 入口 
    		
    		if (empty($_POST['trackinglist_type'])){
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('查询出现错误, 请联系技术人员修正问题');
    			exit(json_encode($result));
    		}else{
    			if (strtolower($_POST['trackinglist_type']) == 'json' ){
    				$tracklist = json_decode($_POST['trackinglist'],true);
    			}else{
    				//中文逗号替换成 英文逗号
    				$tmp_strackinglist = trim(str_ireplace('，', ',', $_POST['trackinglist']));
    				//空格, 回车都替换成英文逗号
    				$tmp_strackinglist = trim(str_ireplace(array("\r\n", "\r", "\n"), ',', $_POST['trackinglist']));
    				//根据 英文逗号 将 字符串拆分
    				$tmp_tracklist = explode(',', $tmp_strackinglist);
    				// 过滤 值 等于空的 数组
    				$tmp_tracklist = array_filter($tmp_tracklist);
    				foreach($tmp_tracklist as $tmp_trackno){
    					$tracklist[] = ['0' => $tmp_trackno];
    				}
    			}
    		}

			$per_import_limit = 40;
    		if (count($tracklist)-1>$per_import_limit){
    			$result ['success'] = false;
    			$result ['message'] = TranslateHelper::t('本次查询数据多于'.$per_import_limit.' 条记录,如有需要查询多于'.$per_import_limit.'条记录,请使用excel导入查询!');
    			exit(json_encode($result));
    		}
    		/**/
	    	//每天最多上传100单
// 			$suffix = date('Ymd');
    		 
    			
    		$limt_count = TrackingHelper::getTrackerUsedQuota($puid1);
			$max_import_limit = TrackingHelper::getTrackerQuota($puid1);
			
			/* 屏蔽 之前的vip用户
			if (!empty(TrackingHelper::$vip_tracker_excel_import_limit[$puid1])){
				//指定 的客户 可以享受vip 服务
				$max_import_limit = TrackingHelper::$vip_tracker_excel_import_limit[$puid1];
			}else{
				//每天最多上传100单
				//$max_import_limit = TrackingHelper::$tracker_import_limit;
				$max_import_limit = TrackingHelper::getTrackerQuota($puid1);
			}
			*/
    		if ( $limt_count + count($tracklist) -1 > $max_import_limit ){
				if (TrackingHelper::$tracker_guest_import_limit == $max_import_limit){
					$tips = "如想增加物流跟踪助手查询限额到".TrackingHelper::$tracker_import_limit.",请绑定平台账号。";
				}else{
					$tips = '';
				}
				
				$result['success'] = false;
				if ($limt_count == 0 ){
					$result['message'] = TranslateHelper::t("每天手动录入与excel上传总数上限".$max_import_limit."单! "." 本次导入记录超出上限！".$tips);
				}else{
					$result['message'] = TranslateHelper::t("每天手动录入与excel上传总数上限".$max_import_limit."单,当前已经录入了".$limt_count."条物流! "." 本次导入".count($tracklist)."条记录失败！".$tips);
				}
				
				//return $result;
				exit(json_encode($result));
			}
			
			
			
    		if (empty($source)) $source = 'M';
    		 
    		$puid1 = \Yii::$app->subdb->getCurrentPuid();
    		AppTrackerApiHelper::actionLog("Tracker", "录入查询物流号",["param1"=>count($tracklist) ]);

    		//智能识别录入格式
    		$result = TrackingHelper::checkManualImportFormat($tracklist);
    		 
    		if ($result['success']){
    			if (!empty($result['ImportDataFieldMapping'])){
    				//检查 track no 是否重复
    		
    				if (isset($result['ImportDataFieldMapping']['track_no'])){
    					$checkTrackNoArr = [];
    					foreach($tracklist as $onetrack):
    					//不存在track no 跳过
    					if (! isset($onetrack[$result['ImportDataFieldMapping']['track_no']] )) continue;
    					//存在返回格式有问题 , 不存在则保存记录
    					if (array_key_exists($onetrack[$result['ImportDataFieldMapping']['track_no']] , $checkTrackNoArr)){
    						$add_rt['success'] = false;
    						$add_rt['message'] = TranslateHelper::t('物流单号:'.$onetrack[$result['ImportDataFieldMapping']['track_no']].'重复出现,可能 是无法自动识别格式或者录入数据有问题,请使用excel导入');
    						exit(json_encode($add_rt));
    					}else {
    						$checkTrackNoArr[$onetrack[$result['ImportDataFieldMapping']['track_no']]] = 1;
    					}
    						
    					endforeach;
    				}else{
    					$add_rt['success'] = false;
    					$add_rt['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
    					exit(json_encode($add_rt));
    				}
    				 
    				//Write all trackings to db
    				foreach($tracklist as $onetrack):
    				$data = [];
    				//根据智能排序重组格式
    				foreach($result['ImportDataFieldMapping'] as $key=>$value){
    					if (isset($onetrack[$value]))
    						$data[$key] = trim($onetrack[$value]);
    				}
    				//不存在track no 跳过
    				if (! isset($data['track_no'])) continue;
    					
    				$data['batch_no'] = 'M'.date('Ymd');
    				//吧要new 的Tracking 放到缓存里面
    				$add_rt = TrackingHelper::addTracking($data,$source);    				
    				if (! $add_rt['success']){
    					$errorList[] = ['message'=>$data['track_no'].":".$add_rt['message']];
    				}else{//success
    					$tracknoList[] = $data['track_no'];
    				}    					
    				endforeach;
					
					//批量提交到数据库
    				TrackingHelper::postTrackingBufferToDb();
    				
    				if (count($tracknoList)<30){
    					//Request API for 17Track for this puid pending tracking
    					foreach ($tracknoList as $track_no){
    						TrackingHelper::generateOneRequestForTracking($track_no, Tracking::$IS_USER_REQUIRE_UPDATE); //true means waiting online
    					}
    					//吧Api Request Buffer 的批量insert 到db
    					TrackingHelper::postTrackingApiQueueBufferToDb();
    				}else{//when 上传的记录大于 30 条，使用buffer 异步机制，有异步job自动捡起来做的
    					TrackingHelper::putIntoTrackQueueBuffer($tracknoList, Tracking::$IS_USER_REQUIRE_UPDATE);
    				}
    				
    				//记录当前查询的次数
    				//ConfigHelper::setConfig("Tracking/trackerImportLimit_".$suffix , $limt_count + count($tracklist));
//     				TrackingHelper::setTrackerTempDataToRedis("trackerImportLimit_".$suffix , $limt_count + count($tracklist)-1);//不能处理并发问题， 改为新接口
    				$suffix = $CACHE['TrackerSuffix'.$puid1];
    				//等到真的进去队列查询了，才耗费quota吧
    				//TrackingHelper::addTrackerQuotaToRedis("trackerImportLimit_".$suffix ,  count($tracklist));//20170912 使用redisadd 的新接口
    				
    				$afterAddRt = [];
    				$afterAddRt['success'] = true;
    				 
    				//成功更新纪录的找出tracking no
    				if (!empty($tracknoList)){
    					$TrackInfoList = Tracking::find()->where(['track_no'=>$tracknoList])->all();
    				}
    				 
    				if (! empty($errorList)){
    					$afterAddRt['success'] = false;
    					$afterAddRt['errorlist'] = $errorList;
    				}
    				$model = new Tracking();

    				if (! empty($TrackInfoList)){
    					$afterAddRt['TrackInfoList'] = $TrackInfoList;
    		
    					$afterAddRt['TbHtml'] = $this->renderPartial('_list_manual_import_tracking_record.php', [
    							'trackingList' => $TrackInfoList,
    							'model'=>$model,
    							]);
    		
    				}
    				
    				exit(json_encode($afterAddRt));
    			}else{
    				$add_rt['success'] = false;
    				$add_rt['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
    			}
    			exit(json_encode($add_rt));
    		
    		}else{
    			exit(json_encode($result));
    		
    		}
    		
    		
    	}elseif(!empty($_POST['batch_no'])){
    		
    		
    		$afterAddRt['success'] = true;
    		//excel 导入后 , 显示数据
    		//找出top 100 tracking no
    		//$TrackInfoList = Tracking::find()->where(['batch_no'=>$_POST['batch_no']])->all();
    		$sql = "select * from lt_tracking where batch_no ='".$_POST['batch_no']."' limit 100 ";
    		$TrackInfoList = Tracking::findBySql($sql)->all();
    		$model = new Tracking();
    		if (! empty($TrackInfoList)){
    			$afterAddRt['TrackInfoList'] = $TrackInfoList;
    		
    			$afterAddRt['TbHtml'] = $this->renderPartial('_list_manual_import_tracking_record.php', [
    					'trackingList' => $TrackInfoList,
    					'model'=>$model,
    					]);
    		
    		}
    		exit(json_encode($afterAddRt));
    	}
    	
    }//end of actionQuery_tracking_process
    
    
    
    
    
    /**
     * 后台 自动更新物流信息
     */
    public function actionBackground_update_tracking_info(){
    	$this->changeDBPuid();
		 
    	try {
    		if (! empty($_POST['track_no_list'])){
    			// 找出 物流记录
    			$track_no_list = json_decode($_POST['track_no_list'],true);
    			
    			if (!empty($_POST['lang']))
    				$lang = json_decode($_POST['lang'],true) ;
    			else 
    				$lang = [];
    			$result['success'] = true;
    			$result['message'] = '';
    			$result['TbHtml'] = TrackingHelper::generateTrackingEventHTML($track_no_list,$lang,true);
    			//todo trhrml
    			$result['TrHtml'] = TrackingHelper::generateTrackingInfoHTML($track_no_list,$lang);
    		}else{
    			$result['success'] = false;
    			$result['message'] =TranslateHelper::t('找不到物流记录') ;
    			$result['TbHtml'] = '';
    			$result['TrHtml'] = '';
    			$result['ProgressHtml'] = '';
    			
    		}
    		exit(json_encode($result));
    	} catch (Exception $e) {
    		$result['success'] = false;
    		$result['message'] = $e->getMessage();
    		$result['TbHtml'] = [];
    		
    		exit(json_encode($result));
    	}
    }//end of actionBackground_update_tracking_info
    
    public function actionViewDetailTrackingEvent(){
    	$track_no=$_GET['track_no'];
    	AppTrackerApiHelper::actionLog("Tracker", "view tracking detail" ,['paramstr1'=>$track_no]);//查看相关物流详情
    }
    
    /**
     * 后台 查询记录中的 物流信息 
     */
    public function actionBackground_update_list_tracking_info(){
    	$this->changeDBPuid();
		 
    	try {
    		if (! empty($_POST['track_no_list'])){
    			// 找出 物流记录
    			$track_no_list = json_decode($_POST['track_no_list'],true);
    			 
    			if (!empty($_POST['lang']))
    				$lang = json_decode($_POST['lang'],true) ;
    			else
    				$lang = [];
    			$result['success'] = true;
    			$result['message'] = '';
    			$result['TbHtml'] = TrackingHelper::generateTrackingEventHTML($track_no_list,$lang,true);
    			
    			$TrackList = Tracking::findAll(['track_no'=>$track_no_list]);
    			
    			
    			$isPlatform = ! empty($_POST['platform']);
    			foreach($TrackList as $oneTracking){
    				$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
    				$oneTracking['state']  = Tracking::getChineseState($oneTracking['state'] );
    				
    				$row = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking , $isPlatform);
    				$TrHTML[$oneTracking['id']] = $row[$oneTracking['track_no']];
    			}
    			
    			//todo trhrml
    			$result['TrHtml'] = $TrHTML;
    			//$result['TrHtml'] = TrackingHelper::generateTrackingInfoHTML($track_no_list,$lang);
    		}else{
    			$result['success'] = false;
    			$result['message'] =TranslateHelper::t('找不到物流记录') ;
    			$result['TbHtml'] = '';
    			$result['TrHtml'] = '';
    			$result['ProgressHtml'] = '';
    			 
    		}
    		exit(json_encode($result));
    	} catch (Exception $e) {
    		$result['success'] = false;
    		$result['message'] = $e->getMessage();
    		$result['TbHtml'] = [];
    	
    		exit(json_encode($result));
    	}
    }//end of actionBackground_update_list_tracking_info
    
    /**
     * 自动邮件提醒设置 界面
     */
    public function actionEmailAlertSetting(){
    	$this->changeDBPuid();
    	//获取config 信息
    	$path = "tracking/EmailAlertSetting";
    	$config =json_decode( ConfigHelper::getConfig($path),true);
    	
    	//调用 view
    	return $this->render('email_alert_setting' , ['config'=>$config]);
    }//end of actionEmailAlertSetting
    
    /**
     * 查询物流更新界面  : 立即更新 按键
     */
    public function actionGenerate_request_for_tracking(){
    	$this->changeDBPuid();
    	$result = [];
		 
    	if (!empty($_POST['track_no'])){
    		//请求17track 并更新物流信息
    		$result = TrackingHelper::generateOneRequestForTracking($_POST['track_no'],true);
    		
    		//吧Api Request Buffer 的批量insert 到db
    		TrackingHelper::postTrackingApiQueueBufferToDb();
    		
    		AppTrackerApiHelper::actionLog("Tracker", "请求立即更新物流号", ['paramstr1'=>$_POST['track_no'] ] );
    		
    		if(!empty($_POST['lang']))
    			$lang[$_POST['track_no']] = $_POST['lang'];
    		else 
    			$lang = [];
    		
    		if (!empty($result['success'] )) {
				// 更新物流信息成功
				$track_no_list [] = $_POST ['track_no'];
				$result ['TbHtml'] = TrackingHelper::generateTrackingEventHTML ( $track_no_list ,$lang,true);
				
				$oneTracking = \eagle\models\tracking\Tracking::findOne(['track_no'=>$track_no_list]);
				
				
				$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
				$oneTracking['state']  = Tracking::getChineseState($oneTracking['state']);
				$isPlatform = (! empty($_POST['platform']));
				$result ['TrHtml'] = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking,$isPlatform);
			} // end of $result['success'] empty or not
			
			if (! empty($result['message'])) $result['message'] = TranslateHelper::t($result['message']);
			
		}
		exit ( json_encode ( $result ) );
	} // end of actionGenerate_request_for_tracking
	
	
	/**
	 * 批量立即跟新
	 */
	public function actionBatch_generate_request(){
		$this->changeDBPuid();
		$result = [];
		if (!empty($_POST['tracking_no_list'])){
			$tracking_no_list = json_decode($_POST['tracking_no_list'],true);
			foreach ($tracking_no_list as $track_no){
				//请求17track 并更新物流信息
				$result = TrackingHelper::generateOneRequestForTracking($track_no,true);
			}
			//吧Api Request Buffer 的批量insert 到db
			TrackingHelper::postTrackingApiQueueBufferToDb();
		
			AppTrackerApiHelper::actionLog("Tracker", "请求立即更新物流号", ['paramstr1'=>$_POST['tracking_no_list'] ] );
			if (! empty($result['message'])) $result['message'] = TranslateHelper::t($result['message']);
				
		}
		exit ( json_encode ( $result ) );
	}
	/**
	 * 查询物流更新 界面 和 平台订单物流跟踪 页面
	 */
	public function actionListTracking(){
		$this->changeDBPuid();
		//
		global $CACHE;
		if (empty($CACHE['IgnoreToCheck_ShipType'])){
			$config = ConfigHelper::getConfig('IgnoreToCheck_ShipType','NO_CACHE');
			if(!empty($config))
				$CACHE['IgnoreToCheck_ShipType'] = json_decode($config,true);
			else 
				$CACHE['IgnoreToCheck_ShipType'] = [];
		}
		
		// params 赋值
		if (isset ( $_GET ['txt_search'] )) {
			$keyword = $_GET ['txt_search'];
		} else {
			$keyword = "";
		}
		
		if (isset ( $_GET ['startdate'] )) {
			$date_from = $_GET ['startdate'];
		} else {
			$date_from = '';
		}
		
		if (isset ( $_GET ['enddate'] )) {
			$date_to = $_GET ['enddate'];
		} else {
			$date_to = '';
		}
 
		$Tracking = new Tracking ();
		// 包裹类型
		if (! empty ( $_GET ['parcel_classification'] )) {
			if ($_GET ['parcel_classification'] == 'all_parcel') {
				//$params['source']  = 'M,E';
			} else {
				$params = Tracking::getTrackingConditionByClassification ( $_GET ['parcel_classification'] );
				//$params ['source'] = ['M','E'];
				// 由于 getListDataByCondition 不支持2维数组 , 先做转换
				if (is_array ( $params )) {
					foreach ( $params as $key => $value ) {
						if (is_array ( $value )) {
							$params [$key] = implode ( ',', $value );
						}
					}
				}
			}
		} else {
			//$params['source']  = 'M,E';
		}
		
		if (!empty($_GET['select_parcel_classification'])){
			$params = Tracking::getTrackingConditionByClassification ( $_GET ['select_parcel_classification'] );
			// 由于 getListDataByCondition 不支持2维数组 , 先做转换
			if (is_array ( $params )) {
				foreach ( $params as $key => $value ) {
					if (is_array ( $value )) {
						$params [$key] = implode ( ',', $value );
					}
				}
			}	
		}
		
		//停留时间 		支持区间搜索	2017-10-31 lzhl
		if(!empty($_GET['stay_days'])){
			if(is_numeric($_GET['stay_days'])){
				$params['stay_days'][]=['operator'=>'=','days'=>(int)$_GET['stay_days']];
			}else{
				$dayArr = explode(';', $_GET['stay_days']);
				foreach ($dayArr as $arr){
					$arr = trim($arr);
					if(preg_match('/^(\<|\>)?(\=)?[0-9]+$/',$arr)){
						$operator = preg_replace('/[0-9]+/', '', $arr);
 						$days = preg_replace('/[^0-9]*/', '', $arr);
						if(!in_array($operator,['>','<','=','>=','<=']) || !is_numeric($days)){
							//不符合格式
						}else{
							$params['stay_days'][]=['operator'=>$operator,'days'=>(int)$days];
						}
					}else{
						//不符合格式
					}
				}
			}
		}
		//停留时间	没做区间搜索前
// 		if (!empty($_GET['stay_days']) &&  (int)$_GET['stay_days'] >0 ){
// 			$params['stay_days'] = (int)$_GET['stay_days'];
// 		}
		
		//总运输天数 		支持区间搜索	2017-12-01 lzhl
		if(!empty($_GET['total_days'])){
			if(is_numeric($_GET['total_days'])){
				$params['total_days'][]=['operator'=>'=','days'=>(int)$_GET['total_days']];
			}else{
				$dayArr = explode(';', $_GET['total_days']);
				foreach ($dayArr as $arr){
					$arr = trim($arr);
					if(preg_match('/^(\<|\>)?(\=)?[0-9]+$/',$arr)){
						$operator = preg_replace('/[0-9]+/', '', $arr);
						$days = preg_replace('/[^0-9]*/', '', $arr);
						if(!in_array($operator,['>','<','=','>=','<=']) || !is_numeric($days)){
							//不符合格式
						}else{
							$params['total_days'][]=['operator'=>$operator,'days'=>(int)$days];
						}
					}else{
						//不符合格式
					}
				}
			}
		}
		
		if(!empty($_GET ['to_nations'] )){
			$params['to_nation'] =strtoupper( $_GET ['to_nations']);
		}
		
		if(!empty($_GET ['is_handled'] )){
			$params['mark_handled'] =strtoupper( $_GET ['is_handled']);
		}
		
		
		if(!empty($_GET ['is_remark'] )){
			$params['hasComment'] = strtoupper($_GET ['is_remark']);
		}
		
		if (!empty($_GET['is_has_tag'])){
			if (is_numeric($_GET ['is_has_tag']))
				$params['tagid'] = $_GET ['is_has_tag'];
		}
		
		
		// 平台类型
		if (! empty ( $_GET ['platform'] )) {
			// platform 只要小写
			if (strtolower($_GET ['platform']) != 'all'){
				$params ['platform'] = strtolower($_GET ['platform']);
			}
			//$params ['source'] = 'O';
		}
		
		//Step ebay，如果是查看ebay的订单，check 是否已经绑定了ebay账号，如果没有，进入提示绑定页面
		if (!empty($params ['platform']) and $params ['platform'] =='ebay'){
			$ebayAccountNum = EbayAccountsApiHelper::countBindingAccounts();
			if($ebayAccountNum <= 0  ){
				return $this->render('no_account_bind' , ['platform'=>'ebay']);
			}
		}
		
		//Step 速卖通，如果是查看速卖通的订单，check 是否已经绑定了速卖通账号，如果没有，进入提示绑定页面
		if (!empty($params ['platform']) and $params ['platform'] =='aliexpress'){
			$aliexpressAccountNum = AliexpressAccountsApiHelper::countBindingAccounts();
			 
    		if($aliexpressAccountNum <= 0 ){
    			return $this->render('no_account_bind' , ['platform'=>'aliexpress']);
    		}
		}
		
		if (!empty($_GET['ship_by'])){
			$params['ship_by'] = $_GET['ship_by'];
		}
		
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		//权限筛选
		if(empty($_GET['sellerid'])){
			if(!$isParent){
				$allAuthorizeSellerIds = [];
				$allAuthorizePlatformAccountsArr = UserHelper::getUserAllAuthorizePlatformAccountsArr();
				
				if(empty($allAuthorizePlatformAccountsArr)){
				
				}else{
					foreach ($allAuthorizePlatformAccountsArr as $platform=>$accounts){
						if(!empty($accounts)){
							foreach ($accounts as $key=>$name)
								$allAuthorizeSellerIds[] = $key;
						}
					}
				}
				if(empty($allAuthorizeSellerIds))
					exit("您没有有效的平台账号管理权限，无法浏览此页面。");
				else
					$params ['seller_id'] = implode(',',$allAuthorizeSellerIds);
			}
		}
		else{
			$tmpRow = explode("@", $_GET['sellerid']);
			
			//$params ['platform'] = $tmpRow[0];
			//$params ['seller_id'] = $tmpRow[1];
			$params ['seller_id'] = $_GET['sellerid'];
		}
			
		if (! empty ( $_GET ['sort'] )) {
			$sort = $_GET ['sort'];
			if ('-' == substr ( $sort, 0, 1 )) {
				$sort = substr ( $sort, 1 );
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		
		if (! isset ( $sort ))
			$sort = ''; //use default in helper business logic
		if (! isset ( $order ))
			$order = '';//use default in helper business logic
		
		if (! empty ( $_GET ['per-page'] )) {
			if (is_numeric ( $_GET ['per-page'] )) {
				$pageSize = $_GET ['per-page'];
			} else {
				$pageSize = 50;
			}
		} else {
			$pageSize = 50;
		}
				
		$puid1 = \Yii::$app->subdb->getCurrentPuid();

		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Controller tracking params 2:".print_r($params,true)],"edb\global");
    	//调用 查询方法
		$params['deleted'] = 'N';
		
		if (!empty($_GET['is_send'])){
			$params['is_send'] = strtoupper($_GET ['is_send']);
		}
		
		
		//客服提醒只对oms来源的物流进行统计
		if (!empty($_GET['pos'])){
			$params ['source'] = 'O';
			$params['pos'] = $_GET['pos'];
			//已签收请求好评 状态有两个
			if ($_GET['pos'] == 'RGE'){
				if (!empty($params['status'])) $params['status'] .= ',platform_confirmed';
			}
		}
		
    	$TrackingData = TrackingHelper::getListDataByCondition($keyword,$params,$date_from,$date_to,$sort, $order , $pageSize );
    	 
    	//khcomment20150606 $TrackingData['header_total'] = $track_statistics[$statistics_key];
    	 
    	$puid1 = \Yii::$app->subdb->getCurrentPuid();
    	 
    	AppTrackerApiHelper::actionLog("Tracker", "查看全部物流记录,平台:".(empty($_GET ['platform'])?"":$_GET ['platform']) , ['paramstr1'=> (isset($_GET ['parcel_classification'])?$_GET ['parcel_classification']:"") ]  );
    	
    	
    	// 是否显示 新绑定 账号的进度 条
    	$IsShowProgressBar = false;
    	// 只有在 全部订单  , ebay 订单 , 速卖通订单 中列出全部订单时候 才出绑定 进度条
    	if (!empty($_REQUEST['platform']) && (! isset($_REQUEST['parcel_classification']))){
    		//echo "<br> platform : ".$_REQUEST['platform'];
    		$platform = (strtolower($_REQUEST['platform']) == 'all')? "": strtolower($_REQUEST['platform']) ;
    		$progressData = TrackingHelper::progressOfTrackingDataInit($platform);
    		
    		$IsShowProgressBar = $progressData['success'];
    	}
    	
    	//获取用户常用的物流方式
    	$using_carriers = TrackingHelper::getTrackerTempDataFromRedis("using_carriers" ); //ConfigHelper::getConfig("Tracking/using_carriers");
    	
    	if (!empty($using_carriers))
    		$using_carriers = json_decode($using_carriers,true);
    	
    	//获取用户账号信息
    	$account_data = TrackingHelper::getAccountFilterData('all');
    	
    	//获取 所有 tag 标签
    	$AllTagData = TrackingTagHelper::getTagByTagID();
    	
    	
    	$tag_class_list = TrackingTagHelper::getTagColorMapping();
    	//获取国家数据
    	$country_list = [];
    	/* 
    	$setConNations = ['CN','US'];
    	ConfigHelper::setConfig("Tracking/to_nations", json_encode($setConNations));
    	*/
    	//$tmp_country_list = ConfigHelper::getConfig("Tracking/to_nations" );
		$tmp_country_list = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
    	if (!empty($tmp_country_list)){
    		if (is_string($tmp_country_list)){
    			$country_code_lsit =  json_decode($tmp_country_list);
    		}elseif(is_array($tmp_country_list)){
    			$country_code_lsit = $tmp_country_list;
    		}else{
    			$country_code_lsit = [];
    		}
    		
    		foreach($country_code_lsit as $code){
    			$label = TrackingHelper::autoSetCountriesNameMapping($code);
    			$country_list [$code] = $label;
    		}
    	}
    	
    	
    	//var_dump($account_data);
    	//return ;
    	
    	//调用 view
    	return $this->render('list_tracking', [
    			'TrackingData' => $TrackingData,
    			'IsShowProgressBar'=> $IsShowProgressBar,
    			'using_carriers'=> $using_carriers, 
    			'account_data'=> $account_data,
    			'AllTagData'=>$AllTagData,
    			'country_list'=>$country_list,
    			'tag_class_list'=>$tag_class_list,
    			]);
    }//end of actionListPlatformTracking
    
    /**
     * excel 导入 tracking 数据
     */
    public function actionImportTrackingByExcel(){
    	$this->changeDBPuid();
    	try {
    		// excel 导入
    		if (! empty($_FILES['input_import_excel_file'])){
    			$result = TrackingHelper::importTrackingDataByExcel($_FILES['input_import_excel_file']);
    		}
    	} catch (\Exception $e) {
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t($e->getMessage());
    	}
    	
    	if ($result ['success']){
    		//成功导入 tracking 才写日志 和清空cache 
    		$puid1 = \Yii::$app->subdb->getCurrentPuid();
    		
    		AppTrackerApiHelper::actionLog("Tracker", "Import Tracking By Excel",["param1"=>empty($result ['count'])?0:$result ['count'] ]);
    		//Request API for 17Track for this puid pending tracking
    		//comment below because it may work for too many records and exceeds 30 secs script time limitation
    		//TrackingHelper::requestTrackingForUid(0,true); //true for online call
    		//force update the top menu statistics
    		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
    		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
    	}
    	
    	
    	
    	exit(json_encode($result));
    }//end of actionImportTrackingByExcel
    
    /**
     * 标记已处理
     */
    public function actionMarkTrackingHandled(){
    	$this->changeDBPuid();
    	if (!empty($_POST['tracking_no_list'])){
    		// decode array 
    		$tracking_no_array = json_decode($_POST['tracking_no_list']);
    		$result = TrackingHelper::markTrackingHandled($tracking_no_array);
    		AppTrackerApiHelper::actionLog("Tracker", "标记物流号已处理"  );
    		
    		$allTracking = Tracking::find()->where(['id'=>$tracking_no_array])->asArray()->all();
    		if (!empty($allTracking)){
    			foreach ($allTracking as $oneTracking){
	    			$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
	    			$oneTracking['state']  = Tracking::getChineseState($oneTracking['state'] );
	    			$row = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking );
	    			$result['TrHtml'][$oneTracking['id']] = $row[$oneTracking['track_no']];
    			}
    		}
    	}
    	//force update the top menu statistics
    	ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
    	ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
    	exit(json_encode($result));
    }//end of actionMarkTrackingHandled
	
	/**
     * 查询记录页面的导出excel post 方式实现
     */
	public function actionExportTrackingExcelAutosubmit(){
		$status = !empty($_REQUEST['status'])?$_REQUEST['status']:'';
		//这种方法最多 是255左右 ， 假如超过后者会覆盖前者
		header("Content-Type: text/html; charset=utf8"); //保证为utf8 不乱码
		echo "<body><form id='formid' method='post' action='/tracking/tracking/export-tracking-excel'>";
		echo empty($status)?'':"<input type='hidden' name='status' value='$status'>";
		echo "</form></body>";
		echo "
		<script language='javascript'>
		var checkList = window.opener.document.getElementsByName('chk_tracking_record');
		var track_id_list = [];
		for ( var i =0 ; i<checkList.length;i++){
			if (checkList[i].checked){
				
				var tmpInput = document.createElement(\"input\"); 
				tmpInput.type = 'hidden'; 
				tmpInput.name = 'track_id_list[]'; 
				tmpInput.value = checkList[i].getAttribute('data-track-id'); 
				document.getElementById('formid').appendChild(tmpInput); 
				track_id_list.push(checkList[i].value);
			}
			
		}
		
		document.getElementById('formid').submit();
		
		</script>
		";
		exit();
		
	}//end of actionExportTrackingExcelAutosubmit 
    
    /**
     * 查询记录页面的导出excel 
     */
    public function actionExportTrackingExcel(){
    	$this->changeDBPuid();
    	//找出 结果
    	
    	
		// params 赋值
    if (isset ( $_GET ['txt_search'] )) {
			$keyword = $_GET ['txt_search'];
		} else {
			$keyword = "";
		}
		
		if (isset ( $_GET ['startdate'] )) {
			$date_from = $_GET ['startdate'];
		} else {
			$date_from = '';
		}
		
		if (isset ( $_GET ['enddate'] )) {
			$date_to = $_GET ['enddate'];
		} else {
			$date_to = '';
		}
 
		$Tracking = new Tracking ();
		// 包裹类型
		if (! empty ( $_GET ['parcel_classification'] )) {
			if ($_GET ['parcel_classification'] == 'all_parcel') {
				//$params['source']  = 'M,E';
			} else {
				$params = Tracking::getTrackingConditionByClassification ( $_GET ['parcel_classification'] );
				//$params ['source'] = ['M','E'];
				// 由于 getListDataByCondition 不支持2维数组 , 先做转换
				if (is_array ( $params )) {
					foreach ( $params as $key => $value ) {
						if (is_array ( $value )) {
							$params [$key] = implode ( ',', $value );
						}
					}
				}
			}
		} else {
			//$params['source']  = 'M,E';
		}
		
		if (!empty($_GET['select_parcel_classification'])){
			$params = Tracking::getTrackingConditionByClassification ( $_GET ['select_parcel_classification'] );
			// 由于 getListDataByCondition 不支持2维数组 , 先做转换
			if (is_array ( $params )) {
				foreach ( $params as $key => $value ) {
					if (is_array ( $value )) {
						$params [$key] = implode ( ',', $value );
					}
				}
			}
			
		}
		
		if(!empty($_GET ['to_nations'] )){
			$params['to_nation'] =strtoupper( $_GET ['to_nations']);
		}
		
		if(!empty($_GET ['is_handled'] )){
			$params['mark_handled'] =strtoupper( $_GET ['is_handled']);
		}
		
		if(!empty($_GET ['is_remark'] )){
			$params['hasComment'] = strtoupper($_GET ['is_remark']);
		}
		
		if (!empty($_GET['is_has_tag'])){
			if (is_numeric($_GET ['is_has_tag']))
				$params['tagid'] = $_GET ['is_has_tag'];
		}
		
		
		// 平台类型
		if (! empty ( $_GET ['platform'] )) {
			// platform 只要小写
			if (strtolower($_GET ['platform']) != 'all'){
				$params ['platform'] = strtolower($_GET ['platform']);
			}
			//$params ['source'] = 'O';
		}
		
		if (!empty($_GET['ship_by'])){
			$params['ship_by'] = $_GET['ship_by'];
		}
		
		if (!empty($_GET['sellerid'])){
			$tmpRow = explode("@", $_GET['sellerid']);
			
			//$params ['platform'] = $tmpRow[0];
			//$params ['seller_id'] = $tmpRow[1];
			$params ['seller_id'] = $_GET['sellerid'];
		}
		
		if(!empty($_GET['pos']))
			$params ['pos'] = $_GET['pos'];
			
		if (! empty ( $_GET ['sort'] )) {
			$sort = $_GET ['sort'];
			if ('-' == substr ( $sort, 0, 1 )) {
				$sort = substr ( $sort, 1 );
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		
		if (! isset ( $sort ))
			$sort = ''; //use default in helper business logic
		if (! isset ( $order ))
			$order = '';//use default in helper business logic
				
		$puid1 = \Yii::$app->subdb->getCurrentPuid();

		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Controller tracking params 2:".print_r($params,true)],"edb\global");
    	//调用 查询方法
		$params['deleted'] = 'N';
		
		if (!empty($_GET['is_send'])){
			$params['is_send'] = strtoupper($_GET ['is_send']);
		}
    	
    	// 设置一个足够大的值 
    	//$pageSize = 9999999;
    	
    	//调用 查询方法
    	//$TrackingData = TrackingHelper::getListDataByCondition($keyword,$params,$date_from,$date_to,$sort, $order , $pageSize );
		$field_label_list = TrackingHelper::$EXPORT_EXCEL_FIELD_LABEL;
		
		//kh20160218 start 导出选中的物流号
    	if (!empty($_REQUEST['track_no_list'])){
    		$tmp_track_no_list = [];
    		foreach($_REQUEST['track_no_list'] as $track_no_base64encode){
    			$tmp_track_no_list[] = base64_decode($track_no_base64encode);
    		}
    			
    		if (!empty($tmp_track_no_list)){
    			$params['export_track_no_list'] = $tmp_track_no_list;
    		}
    	}
    	//lzhl20161010 start 导出选中的物流号by id
    	if (!empty($_REQUEST['track_id_list'])){
    		$tmp_track_id_list = [];
    		foreach ($_REQUEST['track_id_list'] as $id_str){
    			$tmp_track_id_list[] = (int)$id_str;
    		}
    		$params['export_track_id_list'] = $tmp_track_id_list;
    	}
		if( $puid1=='18870' ){
			unset($params['export_track_no_list']);
			unset($params['export_track_id_list']);
			$_REQUEST['per-page']= 1000000000;
		}
    	
    	
    	if(!empty($_REQUEST['status']) && $_REQUEST['status']=='no_info')
    		$params['status'] = 'no_info,checking';
    	
    	//kh20160218 end   导出选中的物流号
    	$TrackingData = TrackingHelper::getListDataByConditionByPagination($keyword,$params,$date_from,$date_to,$sort, $order , $field_label_list);
    	AppTrackerApiHelper::actionLog("Tracker", "Excel导出物流号"  );
    	 
    	$castStrArr['order_id'] = 'str';
    	$castStrArr['track_no'] = 'str';
    	ExcelHelper::exportToCsv($TrackingData,[],null,$castStrArr); 
		unset($TrackingData);
    	unset($data_array);
    }//end of actionExportTrackingExcel
    
    
    /**
     * 自动邮件提醒设置 界面
     */
    public function actionSaveEmailAlertSetting(){
    	$this->changeDBPuid();
    	try {
    		//初始化返回结果
    		$result['success'] = true;
    		$result['message'] = '';
    		$path = "tracking/EmailAlertSetting";
    		// 从form 中获取数据
    		$value = json_encode($_POST);
    		//调用 config 的保存设置接口
    		$exe_result = ConfigHelper::setConfig($path, $value);
    		
    		if ($exe_result){
    			$result['success'] = true;
    			$result['message'] = TranslateHelper::t('保存成功!');;
    		}else{
    			$result['success'] = false;
    			$result['message'] = TranslateHelper::t('未知错误!');
    		}
    	} catch (Exception $e) {
    		//异常处理
    		$result['success'] = false;
    		$result['message'] = TranslateHelper::t($e->getMessage());
    	}
    	//返回 执行结果
    	exit(json_encode($result));
    }//end of actionSaveEmailAlertSetting
    
    /**
     * 录入页面的导出excel
     */
    public function actionExport_manual_excel(){
    	$this->changeDBPuid();
    	if (!empty($_GET['track_no_list'])){
    		$track_no_list = json_decode($_GET['track_no_list'],true);
    		
    		//params 赋值
    		$keyword = "";
    		$date_from = '';
    		$date_to = '';
    		$sort = '';
    		$order = '';
    		$params ['track_no']= implode(',',$track_no_list) ;
    		
    		// 设置一个足够大的值
    		$pageSize = 9999999;
    		 
    		//调用 查询方法
    		$TrackingData = TrackingHelper::getListDataByCondition($keyword,$params,$date_from,$date_to,$sort, $order , $pageSize );
    		
    		$field_label_list = TrackingHelper::$EXPORT_EXCEL_FIELD_LABEL;
    		// 导出 excel 的 header
    		$data_array [] = $field_label_list;
    		foreach($TrackingData['data'] as &$oneTracking):
    		//$EXPORT_EXCEL_FIELD_LABEL 为需要导出的field  , array_flip后得出需要导出的field name
    		foreach(array_flip($field_label_list) as $field_name){
    			//循环所有需要导出的field 并将值 放入临时变量 row 中
    			//BUG FIX -- liang 2016-05-6 start
    			if($field_name == 'last_event'){
    				$row['last_event']='';
    				if(!empty($oneTracking['all_event'])){
    					$all_event = json_decode($oneTracking['all_event'],true);
    					if(!empty($all_event)){
    						$last_event = $all_event[0];
    						$last_event_when = empty($last_event['when'])?'':$last_event['when'];
    						$last_event_where = empty($last_event['where'])?'':base64_decode($last_event['where']);
    						$last_event_what = empty($last_event['what'])?'':base64_decode($last_event['what']);
    						$row['last_event']=$last_event_when.'  '.(empty($last_event_where)?'':$last_event_where.' - ').$last_event_what;
    					}
    				}
    				continue;
    			}
    			if($field_name == 'tags'){
    				$row['tags']='N/A';
    				$tag_data = TrackingTagHelper::getTrackingTagsByTrackId($oneTracking['id']);
    				$tag_ids = [];
    				foreach ($tag_data as $tracking_tag){
    					$tag_ids[] = $tracking_tag['tag_id'];
    				}
    				$TagList = Tag::find()->where(['tag_id'=>$tag_ids])->asArray()->all();
    				foreach ($TagList as $tag){
    					if(empty($row['tags']) || $row['tags']=='N/A')
    						$row['tags'] = $tag['tag_name'];
    					else
    						$row['tags'] = $row['tags'].','.$tag['tag_name'];
    				}
    				continue;
    			}
    			if($field_name == 'remark'){
    				$row['remark']='';
    				if(!empty($oneTracking['remark'])){
    					$remarks = json_decode($oneTracking['remark']);
    					if(!empty($remarks)){
    						foreach ($remarks as $r){
    							$row['remark'].= (empty($r->who)?'未知':$r->who).'于'.(empty($r->when)?' 年 月  日':$r->when).'添加了备注：'.(empty($r->what)?'':$r->what).';';
    						}
    					}
    				}
    				continue;
    			}
    			//BUG FIX -- liang 2016-05-6 end
    			$row[$field_name] = $oneTracking[$field_name];
    		}
    		// 获取一个tracking 完成后放进  $data_array 中
    		$data_array [] = $row;
    		// 释放内存
    		$oneTracking=[];
    		unset($oneTracking);
    		endforeach;
    		unset($TrackingData);
    		ExcelHelper::exportToExcel($data_array);
    		unset($data_array);
    		
    	}
    }//end of actionExport_manual_excel
    
    /**
     * 渠道统计分析
     */
    public function actionDelivery_statistical_analysis(){
    	$this->changeDBPuid();
    	$addi_params ['countries'] = [];
    	
    	//获取国家数据
    	$country_list = [];
    	/* 
    	$setConNations = ['PE','US','UK' , 'CA' , 'SG' , 'RO'];
    	ConfigHelper::setConfig("Tracking/to_nations", json_encode($setConNations));
    	*/
    	//$tmp_country_list = ConfigHelper::getConfig("Tracking/to_nations" );
		$tmp_country_list = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
    	if (!empty($tmp_country_list)){
    		if (is_string($tmp_country_list)){
    			$country_code_lsit =  json_decode($tmp_country_list);
    		}elseif(is_array($tmp_country_list)){
    			$country_code_lsit = $tmp_country_list;
    		}else{
				$country_code_lsit = [ ];
			}
			
			foreach ( $country_code_lsit as $code ) {
				$label = TrackingHelper::autoSetCountriesNameMapping ( $code );
				$country_list [$code] = $label;
			}
			
			$addi_params ['countries'] = $country_list;
		}
		
		try {
			
			if (isset ( $_GET ['startdate'] )) {
				$date_from = $_GET ['startdate'];
			} else {
				$date_from = '';
			}
			
			if (isset ( $_GET ['enddate'] )) {
				$date_to = $_GET ['enddate'];
			} else {
				$date_to = '';
			}
			
			if (! empty ( $_GET ['to_nations'] )) {
				$to_nation = $_GET ['to_nations'];
			} else {
				$to_nation = '';
			}
			
			// 调用 查询方法
			$analysisData = TrackingHelper::getDeliveryStatisticalAnalysis ( $date_from, $date_to, $to_nation );
			
			// 调用 view
			return $this->render ( 'delivery_statistical_analysis', [ 
					'analysisData' => $analysisData,
					'addi_params' => $addi_params 
			] );
		} catch ( Exception $e ) {
			return $this->render ( 'delivery_statistical_analysis', [ 
					'addi_params' => $addi_params 
			] );
		}
	} // end of actionPlatform_statistical_analysis
	
	/**
	 * 渠道统计分析 : 导出明细
	 */
	public function actionExport_delivery_statistical_analysis_detail() {
		$this->changeDBPuid ();
		$params = array();
		if (isset ( $_GET ['startdate'])){
    		$date_from = $_GET['startdate'];
    	}else{
    		$date_from = '';
    	}
    		 
		if (isset($_GET['enddate'])){
			$date_to = $_GET['enddate'];
		}else{
			$date_to = '';
		}
		//params 赋值
		if (!empty($_GET['to_nations'])){
			$params ['to_nation']= $_GET['to_nations'];
		}
		
		if (!empty($_GET['ship_by'])){
			$params ['ship_by']= $_GET['ship_by'];
		}
		
		$keyword = "";
		$sort = '';
		$order = '';
		
		/*
		//性能 调试 log
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		*/
		$field_label_list = TrackingHelper::$EXPORT_EXCEL_FIELD_LABEL;
		//调用 查询方法
		//$TrackingData = TrackingHelper::getListDataByCondition($keyword,$params,$date_from,$date_to,$sort, $order , $pageSize );
		$TrackingData = TrackingHelper::getListDataByConditionNoPagination($keyword,$params,$date_from,$date_to,$sort, $order , $field_label_list );
		
		/*
		//性能 调试 log
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		$current_time_cost = $logTimeMS2-$logTimeMS1;
		$current_memory_cost = $logMemoryMS2-$logMemoryMS1;
		echo " \n  <br>".__FUNCTION__.' step 1  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
		\Yii::info((__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
		*/
		
		$castStrArr['order_id'] = 'str';
		$castStrArr['track_no'] = 'str';
		ExcelHelper::exportToCsv($TrackingData,[],null,$castStrArr);
		unset($TrackingData);
		unset($data_array);
		
		/*
    	//性能 调试 log
    	$logTimeMS3=TimeUtil::getCurrentTimestampMS();
    	$logMemoryMS3 = (memory_get_usage()/1024/1024);
    	$current_time_cost = $logTimeMS3-$logTimeMS2;
    	$current_memory_cost = $logMemoryMS3-$logMemoryMS2;
    	echo " \n  <br>".__FUNCTION__.' step 2  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
    	\Yii::info((__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
    	*/
    }//end of actionExport_delivery_statistical_analysis_detail
    
    /**
     * 平台 绑定 页面
     */
    public function actionPlatform_account_binding(){
    	$this->changeDBPuid();
    	$allUserList = array();
    	
    	// 获取ebay user list
    	$ebayUserList = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
    	$allUserList["ebayUserList"] = $ebayUserList;
    	
    	
    	// 获取速卖通 user list
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	
    	$users = SaasAliexpressUser::find()->where('uid ='.$uid)
    	->orderBy('refresh_token_timeout desc')
    	->asArray()
    	->all();
    	
    	$aliexpressUserList = array();
    	foreach ($users as $user){
    		$user['refresh_token_timeout'] = $user['refresh_token_timeout'] > 0?date('Y-m-d',$user['refresh_token_timeout']):'未授权';
    		$aliexpressUserList[] = $user;
    	}
    	$allUserList['aliexpressUserList'] = $aliexpressUserList;
    	
    	// 获取wish user list
    	$WishUserData = SaasWishUser::find()->where(["uid" => $uid])
    	->orderBy("last_order_success_retrieve_time desc")
    	->asArray()
    	->all();
    	
    	$allUserList['WishUserList'] = $WishUserData;
    	
    	// 获取wish user list
    	$dhgateUserData = SaasDhgateUser::find()->where(["uid" => $uid])->andWhere('is_active <> 3')
    	->orderBy("refresh_token_timeout desc")
    	->asArray()
    	->all();
    	
    	$dhgateUserList = array();
    	foreach ($dhgateUserData as $UserData){
    		$UserData['refresh_token_timeout'] = $UserData['refresh_token_timeout'] > 0?date('Y-m-d',$UserData['refresh_token_timeout']):'未授权';
    		$dhgateUserList[] = $UserData;
    	}
    	
    	$allUserList['dhgateUserList'] = $dhgateUserList;

    	// 获取Lazada user list
    	$LazadaUserList = array();
    	$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
    	$lazadaUserData = SaasLazadaUser::find()->where(["puid" => $uid])->andWhere('status <> 3')
    	->asArray()
    	->all();
    	 
    	foreach ($lazadaUserData as $lazadaUser){
    		$lazadaUser['lazada_site'] = $lazadaSite[$lazadaUser['lazada_site']];
    		$LazadaUserList[] = $lazadaUser;
    	}
    	$allUserList['LazadaUserList'] = $LazadaUserList;
    	
    	return $this->render('bind_platform_store' , $allUserList);
    }//end of actionPlatform_account_binding
    
    /**
     * 翻译
     */
    public function actionTranslate_content(){
    	$this->changeDBPuid();
		 
    	try {
			if (! empty ( $_POST ['track_no'] )) {
				// 找出 物流记录
				$track_no_list = [$_POST ['track_no']];
				
				if (! empty ( $_POST ['lang'] ))
					$lang[$_POST ['track_no']] = $_POST ['lang'];
				else
					$lang[$_POST ['track_no']]  = '';
				
				$result ['success'] = true;
				$result ['message'] = '';
				$result ['TbHtml'] = TrackingHelper::generateTrackingEventHTML ( $track_no_list, $lang, true);
				// todo trhrml
				// $result['TrHtml'] = TrackingHelper::generateTrackingInfoHTML($track_no_list);
				$result ['TrHtml'] = [ ];
			} else {
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t ( '找不到物流记录' );
				$result ['TbHtml'] = [ ];
				$result ['TrHtml'] = [ ];
			}
			exit ( json_encode ( $result ) );
		} catch ( Exception $e ) 

		{
			$result ['success'] = false;
			$result ['message'] = $e->getMessage ();
			$result ['TbHtml'] = [ ];
			
			exit ( json_encode ( $result ) );
		}
    }//end of actionTranslate_content
    
    /**
     * 订单基本信息
     */
    public function actionGet_order_info(){
    	$this->changeDBPuid();
    	if (!empty($_GET['track_no'])){
    		$rtn = TrackingHelper::getOrderDetailFromOMSByTrackNo($_GET['track_no']);
    		//test kh $rtn = TrackingHelper::getOrderDetailFromOMSByTrackNo('RI323812986CN');
    		AppTrackerApiHelper::actionLog("Tracker", "展开物流号订单详情",['paramstr1'=>$_GET['track_no'] ] );
    		$rtn['order']['track_no'] = $_GET['track_no'];
    		return $this->renderAjax('_view_order_info', ['orderData'=>$rtn['order']]);
    	}
    }//end of actionGet_order_info
    
    
    /**
     * 查询某天的Track成功失败情况以及客户的健康分析并导出excel
     */
    public function actionExportTrackingReportExcel(){
    	$this->changeDBPuid();
    	//params 赋值
    	if (isset($_GET['startdate'])){
    		$date_from = $_GET['startdate'];
    	}else{
    		$date_from = '';
    	}
    	 
    	if (isset($_GET['enddate'])){
    		$date_to = $_GET['enddate'];
    	}else{
    		$date_to = '';
    	}
    	
    	$data_array = TrackingHelper::showTrackingReportFor($date_from,$date_to);
    	
    	ExcelHelper::exportToExcel($data_array);
    	
    	unset($data_array);
    }//end of actionExportTrackingExcel
    
    /**
     * 删除tracking 记录
     */
    public function actionDeleteTracking(){
    	$this->changeDBPuid();
    	$result = [];
    	$track_nos = array();
    	if (!empty($_POST['is_decode'])) 
    		$isdecode =true;
    	else 
    		$isdecode = false;
    	if (isset($_POST['track_no'])){
    		if (is_array($_POST['track_no'])){
    			foreach ($_POST['track_no'] as $aTrackNo)
    				$track_nos[$aTrackNo] = $aTrackNo;
    		}else{
    			$track_nos[$_POST['track_no']] = $_POST['track_no'];
    		}
    		foreach ($track_nos as $track_no){
    			if ($isdecode) $track_no = base64_decode($track_no);
    			$result = TrackingHelper::deleteTracking($track_no);
    			AppTrackerApiHelper::actionLog("Tracker", "删除物流号",['paramstr1'=>$track_no] );
    		}
    	}else{
    		$result = [
    			'success'=>false , 
    			'message'=>TranslateHelper::t('请选择有效的物流单号!'),
    		];
    	}
    	
    	exit ( json_encode ( $result ) );
    }//end of actiondeleteTracking
    /**
     * 生成 添加标签的界面
     */
    public function actionGetTrackingTags(){
    	$this->changeDBPuid();
    	$tagList = [];
    	$classList = [];
    	if (!empty($_REQUEST['tracking_id'])){
    		$tagList = TrackingTagHelper::getALlTagDataByTrackId($_REQUEST['tracking_id']);
    		$classList = TrackingTagHelper::getTagColorMapping();
    	}
    	
    	return $this->renderPartial('_view_tags_info' , ['TagList'=>$tagList , 'classList'=>$classList]);
    }//end of actionGetTrackingTags
    
    /**
     * 保存 标签 
     */
    public function actionSaveTags(){
    	
    	if (!empty($_REQUEST['tracking_id']) ){
    		if (!empty($_REQUEST['tracking_id'])){
    			$tracking_id = $_REQUEST['tracking_id'];
    		}
    		
    		if (!empty($_REQUEST['TagIdList'])){
    			$TrackingTagIdList = $_REQUEST['TagIdList'];
    		}else{
    			$TrackingTagIdList = [];
    		}
    		
    		if (!empty($_REQUEST['IsEdit'])){
    			$isEditTag = true;
    		}else{
    			$isEditTag = false;
    		}
    		
    		if (!empty($_REQUEST['TagData'])){
    			$TagData = $_REQUEST['TagData'];
    		}else{
    			$TagData = [];
    		}
    		
    		$result = TrackingTagHelper::saveTagAndTrackingTags($tracking_id, $TrackingTagIdList,$isEditTag  ,$TagData);
    	}else{
    		$result = ['success' => false, 'message'=>'保存失败'];
    	}
    	
    	AppTrackerApiHelper::actionLog("Tracker", "添加/修改标签" );  
    	 
    	exit(json_encode($result));
    }
    /**
     * 生成 添加备注的界面
     */
    public function actionGetTrackingRemark(){
    	$this->changeDBPuid();
    	$remark = [];
    	$orderids=[];
    	if (isset($_GET['track_no'])){
    		$track_no = $_GET['track_no'];
    		$models = Tracking::findAll(['track_no'=>$_GET['track_no']]);
    		foreach($models as $model){
    			$orderids[$model->order_id] = $model->order_id;
    			if (empty($remark))
    			$remark = json_decode($model->remark,true);
    		}
    	}else{
    		$track_no = '';
    		$remark = [];
    	}
    	
    	return $this->renderPartial('_view_remark_info' , ['RemarkList'=>$remark,'orderids'=>$orderids , 'track_no' =>$track_no]);
    }//end of actionGetTrackingRemark
    
    /**
     * 新增 备注
     */
    public function actionAppendRemark(){
    	$this->changeDBPuid();
    	if (isset($_POST['track_no'])){
    		$track_no = $_POST['track_no'];
    	}else{
    		$track_no = "";
    	}
    	
    	if (isset($_POST['remark'])){
    		$remark = $_POST['remark'];
    	}else{
    		$remark = "";
    	}
    	
    	
    	if (empty($track_no)){
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t('请选择物流单号');
    		exit ( json_encode ( $result ) );
    	}
    	
    	if (empty($remark)){
    		$result ['success'] = false;
    		$result ['message'] = TranslateHelper::t('请填写备注');
    		exit ( json_encode ( $result ) );
    	}
    	
    	$result = TrackingHelper::appendTrackingRemark($track_no, $remark);
    	AppTrackerApiHelper::actionLog("Tracker", "添加备注",["paramstr1"=>$track_no ]);
    	$model = Tracking::findOne(['track_no'=>$track_no]);
    	$result['sectionHtml'] = TrackingHelper::generateRemarkHTML($model->remark);
    	
    	$oneTracking = $model;
    	if (!empty($oneTracking)){
    		$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
    		$oneTracking['state']  = Tracking::getChineseState($oneTracking['state'] );
    		$row = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking );
    		$result['TrHtml'][$oneTracking['id']] = $row[$oneTracking['track_no']];
    	}
    	exit ( json_encode ( $result ) );
    	
    }//end of actionAddRemark
    
    /**
     * 获取新绑定账号的初始化进度
     */
	public function actionGetNewAccountInitProcess(){
		$this->changeDBPuid();
		$data = [];
		$result = [];
		if (! empty( $_REQUEST['platform'] )){
			$platform = (strtolower($_REQUEST['platform']) == 'all')? "": strtolower($_REQUEST['platform']) ;
			//获取 是否有新账号绑定 
			$progressData = TrackingHelper::progressOfTrackingDataInit($platform);
			
			if ($progressData['success']){
				// ture 为需要显示 新账户进度条的logic 
				
				//定义 各种 state 对应 的css 
				$state_mapping_css = [
					'complete' => 'success' , 
					'normal' => 'primary' , 
					'exception' => 'danger' ,
				];
				
				// 统计总数
				$totalCount = 0;
				
				//当前已经查询完的总数
				$currentCount = 0;
				foreach($progressData['state_distribution'] as $row){
					$totalCount += $row['cc'];
				}
				
				//生成 前台js 进度条的格式 
				/* 前台 js 需要 的格式  , 这里是测试 的 demo 数据
				 * type 进度条的class 关键字   , width 是进度条的百分比 , count 是进度条的数字部分
				$data[] =  ['type'=>'success' , 'width'=>'15' ,'count'=>20];
				$data[] =  ['type'=>'danger' , 'width'=>'25' ,'count'=>10];
				$data[] =  ['type'=>'primary' , 'width'=>'25' ,'count'=>35];
				
				$result = ResultHelper::getSuccess($data,2);
				$result['barMessage'] = '20/34';
				*/
				foreach($progressData['state_distribution'] as $row){
					
					//initial 的情况下跳过
					if (empty($state_mapping_css[$row['state']])) continue;
					
					//计算 进度条的宽度
					if (!empty($totalCount)){
						$tmp_width = $row['cc'] / $totalCount *100;
					}else{
						$tmp_width = 0;
					}
					
					$currentCount += $row['cc'];
					
					//生成 前台js 进度条的格式 
					$data[] = ['type'=>$state_mapping_css[$row['state']] , 'width'=>$tmp_width ,'count'=>$row['cc']];
				}
				
				
				$result = ResultHelper::getSuccess($data,2);
				$result['barMessage'] = $currentCount."/".$totalCount;
				$result['visible'] = true;
			}else{
				// false 为 不显示 进度 条
				$result = ResultHelper::getSuccess($data,2);
				$result['visible'] = false;
			}
			
		}else{
			//参数缺失
			$result = ResultHelper::getMissingParameter($data);
		}
		
		exit(json_encode ( $result ));
	}//end of actionGetNewAccountInitProcess
	
	
	/**
	 +----------------------------------------------------------
	 * 发送 站内信
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSendMessage(){
		$this->changeDBPuid();
		try {
			$trackNoList = [];
			$template = [];
			$addi_params = [];
			$template_addi_info = [];
			$isUpdate = true;
			if (!empty($_REQUEST['letter_template_name']))
				$template ['name']  =  $_REQUEST['letter_template_name'];
			
			
			if (!empty($_REQUEST['letter_template']))
				$template ['body']  =  $_REQUEST['letter_template'];
			else
				$template ['body']  =  '';
			
			if (!empty($_REQUEST['template_id'])){
				if ( $_REQUEST['template_id'] > 0 )
				$template ['id']  =  $_REQUEST['template_id'];
			}
			
			if (!empty($_REQUEST['track_no_list'])){
				$trackNoList = explode(',', $_REQUEST['track_no_list']);
			}
			if (!empty($_REQUEST['subject'])){
				//$addi_params['subject'] = $_REQUEST['subject'];
				$template ['subject']  =  $_REQUEST['subject'];
			}
			
			if (!empty($_REQUEST['template_layout'])){
				$template_addi_info['layout'] = $_REQUEST['template_layout'];
				$LayOutId =$_REQUEST['template_layout'];
			}else{
				$LayOutId =0 ;
			}
			
			if (!empty($_REQUEST['recom_prod_count'])){
				$template_addi_info['recom_prod_count'] = $_REQUEST['recom_prod_count'];
				$ReComProdCount=$_REQUEST['recom_prod_count'];
			}else{
				$ReComProdCount=0;
			}
			
			if (!empty($_REQUEST['recom_prod_group'])){
				$template_addi_info['recom_prod_group'] = $_REQUEST['recom_prod_group'];
				$ReComGroup=$_REQUEST['recom_prod_group'];
			}else{
				$ReComGroup=0;
			}
			
			if (!empty($_REQUEST['path'])){
				$addi_params['path'] = $_REQUEST['path'];
			}
			
			if (!empty($_REQUEST['op_method'])){
				$addi_params['op_method'] = $_REQUEST['op_method'];
			}
			
			if (!empty($_REQUEST['msg_id'])){
				$addi_params['msg_id'] = $_REQUEST['msg_id'];
			}
			
			if (!empty($template_addi_info)){
				$template['addi_info'] = json_encode($template_addi_info) ;
			}
			
			//发信类型：启运/到达待取/异常退回/已签收求好评
			if(!empty($_REQUEST['pos'])){
				if($_REQUEST['pos']=='RSHP')
					$status = 'shipping';
				if($_REQUEST['pos']=='RPF')
					$status = 'arrived_pending_fetch';
				if($_REQUEST['pos']=='RRJ')
					$status = 'rejected';
				if($_REQUEST['pos']=='RGE')
					$status = 'received';
				if($_REQUEST['pos']=='DF')
					$status = 'delivery_failed';
			}else
				$status='';
			
			if (!empty($_REQUEST['isUpdate'])){
				
				if (strtoupper($_REQUEST['isUpdate'])!='T'){
					if (strtoupper($_REQUEST['isUpdate'])=='M')
						$isUpdate = false;
					
					if (strtoupper($_REQUEST['isUpdate'])=='B')
						$isUpdate = true;
					
					$result = MessageHelper::sendStationMessage($template , $trackNoList , $addi_params , $isUpdate ,$status);
					
					
					AppTrackerApiHelper::actionLog("Tracker", "批量发站内信",['paramstr1'=>implode(",", $trackNoList)] );
					
					//保存 数量 和layout id 到lt_tracking
					TrackingHelper::setMessageConfig($trackNoList, $LayOutId, $ReComProdCount, $ReComGroup);
					
				}else{
					$result = MessageHelper::saveMessageTemplate($template);
					AppTrackerApiHelper::actionLog("Tracker", "保存模板",['paramstr1'=>implode(",", $trackNoList)] );
				}
			}
			
			ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
			ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		} catch (Exception $e) {
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		
		exit(json_encode ( $result ));
	}//end of actionSendMessage
	
	/**
	 +----------------------------------------------------------
	 * 根据规则发送 站内信
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/07/22				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSendMessageByRole(){
		$addi_params = [];
		
		//发信类型：启运/到达待取/异常退回/已签收求好评
		if(!empty($_REQUEST['pos'])){
			if($_REQUEST['pos']=='RSHP')
				$status = 'shipping';
			if($_REQUEST['pos']=='RPF')
				$status = 'arrived_pending_fetch';
			if($_REQUEST['pos']=='DF')
				$status = 'delivery_failed';
			if($_REQUEST['pos']=='RRJ')
				$status = 'rejected';
			if($_REQUEST['pos']=='RGE')
				$status = 'received';
		}else
			$status='';
		
		if (!empty($_REQUEST['track_no_mapping_role'])){
			
			foreach($_REQUEST['track_no_mapping_role'] as $tempalate_id=>$trackNoList){
				$query = MsgTemplate::find();
				$template = $query->andWhere(['id'=>$tempalate_id])->asArray()->one();
				$isUpdate = false;
				$result = MessageHelper::sendStationMessage($template , $trackNoList , $addi_params , $isUpdate, $status);
				
				$addi_info = json_decode($template['addi_info'],true);
				
					
				$LayOutId = empty($addi_info['layout_id'])?1:$addi_info['layout_id'];
				$ReComProdCount = empty($addi_info['recom_prod_count'])?8:$addi_info['recom_prod_count'];
				$ReComProdGroup = empty($addi_info['recom_prod_group'])?0:$addi_info['recom_prod_group'];
				//保存 数量 和layout id 到lt_tracking
				TrackingHelper::setMessageConfig($trackNoList, $LayOutId, $ReComProdCount, $ReComProdGroup);
				
			}//end of each send by template 
			
			AppTrackerApiHelper::actionLog("Tracker", "规则匹配发信",['paramstr1'=>json_encode($_REQUEST['track_no_mapping_role'])] );
		}
		
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		
		exit(json_encode ( $result ));
		
	}//end of actionSendMessageByRole
	
	/**
	 +----------------------------------------------------------
	 * 显示 站内信的 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionStationLetter(){
		$this->changeDBPuid();
		$data = [];
		
		if (!empty($_REQUEST['is_decode']))
			$isdecode =true;
		else
			$isdecode = false;
		
		
		// 设置 track no 
		if (! empty($_REQUEST['track_no'])){
			if ($isdecode){
				$TracknoList = explode(',', $_REQUEST['track_no']);
				
				foreach($TracknoList as &$trackno){
					$trackno = base64_decode($trackno);
				}
				
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = implode(',', $TracknoList);
			}else{
				$TracknoList = $_REQUEST['track_no'];
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = $_REQUEST['track_no'];
			}
			
		}
		
		
		if (empty($data['track_no'])){
			exit("empty");
		}
		
		//get all template data
		$data['listTemplate'] = MessageHelper::listAllTemplate();
		//get default template id
		$data['defaultTemplate']  = TrackingHelper::getDefaultTemplate($TracknoList);
		
		AppTrackerApiHelper::actionLog("Tracker", "进入批量发信"  );
		//$data['showtitle'] = true;
		$data['hideSendMessageBtn'] = true;
		/*
		return $this->render('station_letter', [ 'data'=>$data
				]);
		*/
		return $this->renderPartial('station_letter_detail', [ 'data'=>$data
				]);
	}//end of actionStation_letter
	
	/**
	 +----------------------------------------------------------
	 * 显示 单个 物流号 站内信与发送记录的 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionStationLetterDetail(){
		$this->changeDBPuid();
		$data = [];
		
		if (!empty($_REQUEST['is_decode']))
			$isdecode =true;
		else
			$isdecode = false;
		
		
		// 设置 track no
		if (! empty($_REQUEST['track_no'])){
			/**/
			if ($isdecode){
				$TracknoList = explode(',', $_REQUEST['track_no']);
		
				foreach($TracknoList as &$trackno){
					$trackno = base64_decode($trackno);
				}
				
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = implode(',', $TracknoList);
			}else{
				$TracknoList = $_REQUEST['track_no'];
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = implode(',', $TracknoList);
			}
		}
		
		if (empty($data['track_no'])){
			exit("empty");
		}
		
		// 设置 order id
		if (! empty($_REQUEST['order_id'])){
			$order_id = $_REQUEST['order_id'];
			//get message history
			$data['history'] = MessageHelper::getMessageDataByOrderId($order_id);
			$data['order_id'] = $order_id;
		}
		
		if (!empty($_REQUEST['show_method'])){
			$data['show_method'] = $_REQUEST['show_method'];
		}
		
		//get all template data
		$data['listTemplate'] = MessageHelper::listAllTemplate();
		$data['listTemplateAddinfo'] = [];
		foreach($data['listTemplate']  as $oneTemplate){
			if (!empty($oneTemplate['addi_info'])){
				$data['listTemplateAddinfo'][$oneTemplate['id']]  = json_decode($oneTemplate['addi_info'],true);
			}else{
				$data['listTemplateAddinfo'][$oneTemplate['id']] = ['layout'=>1 , 'recom_prod_count'=>4];
			}
			$data['listTemplateAddinfo'][$oneTemplate['id']]['name'] = $oneTemplate['name'];
			
		}
		//get default template id
		//$data['defaultTemplate']  = TrackingHelper::getDefaultTemplate($TracknoList);
		
		$data['defaultTemplate']  = ['template_id'=>-2];//-2 = 自动匹配
		
		$data['hideSendMessageBtn'] = true;
		
		//get message history
		
		AppTrackerApiHelper::actionLog("Tracker", "进入单个发信"  );
		
		
		$matchRT = TrackingHelper::matchMessageRole($TracknoList);
		$data['matchRoleTracking'] = $matchRT['matchRoleTracking'];
		$data['unMatchRoleTracking'] = $matchRT['unMatchRoleTracking'] ;
		$data['isSameSeller'] = empty($matchRT['isSameSeller'])?false:$matchRT['isSameSeller'];
		return $this->renderPartial('station_letter_detail', [ 'data'=>$data
				]);
	}//end of actionStationLetterDetail
	
	/**
	 +----------------------------------------------------------
	 * 获取当前物流号匹配规则的数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/08/03				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRefreshMatchRole(){
		$this->changeDBPuid();
		$data = [];
		
		if (!empty($_REQUEST['is_decode']))
			$isdecode =true;
		else
			$isdecode = false;
		// 设置 track no
		if (! empty($_REQUEST['track_no'])){
			/**/
			if ($isdecode){
				$TracknoList = explode(',', $_REQUEST['track_no']);
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = implode(',', $TracknoList);
			}else{
				$TracknoList = $_REQUEST['track_no'];
				$TracknoList = TrackingHelper::getActiveTrackNo($TracknoList);
				$data['track_no'] = implode(',', $TracknoList);
				
			}
		}
		
		if (empty($data['track_no'])){
			exit("empty");
		}
		
		$matchRT = TrackingHelper::matchMessageRole($TracknoList);
		$data['match_data'] = $matchRT['matchRoleTracking'];
		$data['unmatch_data'] = $matchRT['unMatchRoleTracking'] ;
		exit(json_encode($data));
	}//end of actionrefreshMatchRole
	
	/**
	 +----------------------------------------------------------
	 * 添加和修改模版 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/07/24				初始化
	 +----------------------------------------------------------
	 **/
	public function actionManageTemplate(){
		
		//get all template data
		$data['listTemplate'] = MessageHelper::listAllTemplate();
		$data['listTemplateAddinfo'] = [];
		foreach($data['listTemplate']  as $oneTemplate){
			if (!empty($oneTemplate['addi_info'])){
				$data['listTemplateAddinfo'][$oneTemplate['id']]  = json_decode($oneTemplate['addi_info'],true);
		
			}else{
				//default addi info 
				$data['listTemplateAddinfo'][$oneTemplate['id']] = ['layout'=>1 , 'recom_prod_count'=>4];
			}
				
		}
		
		//get default template id
		if (isset($_REQUEST['template_id']))
			$data['defaultTemplate']  = ['template_id'=>$_REQUEST['template_id']];
		else 
			$data['defaultTemplate']  = ['template_id'=>-1];
		
		return $this->renderPartial('station_letter_detail', [ 'data'=>$data
				]);
	}//end of actionManageTemplate
	
	/**
	 +----------------------------------------------------------
	 * 删除模版
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/07/24				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDeleteTemplate(){
		if(!isset($_REQUEST['template_id'])){
			exit( json_encode(array('success'=>false,'message'=>TranslateHelper::t('请指定要删除的模板!'))) );
		}
		
		$template_id = $_REQUEST['template_id'];
		$template_id=explode(',', $template_id);
		
		$result=TrackingHelper::deleteMsgTemplate($template_id);
		exit( json_encode($result) );
	}//end of actionDeleteTemplate
	
	/**
	 +----------------------------------------------------------
	 * 显示 单个 物流号 发送记录的 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionStationLetterHistory(){
		$this->changeDBPuid();
		$data = [];
	
		if (!empty($_REQUEST['is_decode']))
			$isdecode =true;
		else
			$isdecode = false;
	
	
		// 设置 order id 
		if (! empty($_REQUEST['order_id'])){
			/*
				if ($isdecode){
			$TracknoList = explode(',', $_REQUEST['track_no']);
	
			foreach($TracknoList as &$trackno){
			$trackno = base64_decode($trackno);
			}
	
			$data['track_no'] = implode(',', $TracknoList);
			}else{
			$TracknoList = $_REQUEST['track_no'];
			$data['track_no'] = $_REQUEST['track_no'];
			}
			*/
			$order_id = $_REQUEST['order_id'];
			//get message history
			$data['history'] = MessageHelper::getMessageDataByOrderId($order_id);
			$data['order_id'] = $order_id;
		}
	
		//AppTrackerApiHelper::actionLog("Tracker", "进入单个发信"  );
		return $this->renderPartial('_message_history',['data'=>$data]);
	}//end of actionStationLetterHistory
	
	/**
	 +----------------------------------------------------------
	 * 站内信的 获取 预览数据的 action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionPreviewMessage(){
		$this->changeDBPuid();
		$result = [];
		if (!empty($_GET['track_no_list'])){
			$track_no_list = explode(',', $_GET['track_no_list']);
			$puid = \Yii::$app->subdb->getCurrentPuid();
			AppApiHelper::turnOnUserFunction($puid, 'tracker_recommend'); 
			if (is_array($track_no_list)){
				foreach($track_no_list as $track_no){
					$subject = $_GET['subject'];
					$template = $_GET['template'];
					
					if (stripos( $_GET['template'],'买家查看包裹追踪及商品推荐链接')){
						$result [$track_no] ['recom_prod'] = 'Y';
					}else{
						$result [$track_no] ['recom_prod'] = 'N';
					}
					
					$result [$track_no] = MessageHelper::replaceTemplateData($subject, $template, $track_no); 
					if (!empty($result [$track_no] ['template']))
					$result [$track_no] ['template'] = nl2br($result [$track_no] ['template']); 
					$aTracking = Tracking::find()->andWhere(['track_no'=>$track_no])->asArray()->One();
					$result [$track_no] ['tail'] = MessageBGJHelper::make17TrackMessageTail($puid,'',$aTracking);
					if (!empty($aTracking['id']))
						$result [$track_no]['recom_prod_preview_link'] = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $aTracking['id']) ;
					else 
						$result [$track_no]['recom_prod_preview_link'] = '';
				}
			}
		}
		exit(json_encode ( $result ));
	}//end of actionPreviewMessage
	
	/**
	 +----------------------------------------------------------
	 * 重发 失败的站内信 action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionResendAllFailureMessage(){
		$this->changeDBPuid();
		MessageHelper::resendAllFailureMessage();
		$result = ['success'=>true , 'message'=>TranslateHelper::t('已经重发')];
		exit(json_encode ( $result ));
	}//end of actionResendAllFailureMessage
	
	
	/**
	 +----------------------------------------------------------
	 * 异步保存单个 标签
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/06/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveOneTag(){
		$this->changeDBPuid();
		if (!empty($_REQUEST['tracking_id'])){
			$tracking_id = $_REQUEST['tracking_id'];
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
		
		$result = TrackingTagHelper::saveOneTrackingTag($tracking_id, $tag_name, $operation, $color);
		exit(json_encode($result));
	}//end of actionSaveOneTag
	
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
	public function actionGetOneTagInfo(){
		$this->changeDBPuid();
		$tagdata = [];
		if (!empty($_REQUEST['track_id'])){
			$tagdata = TrackingTagHelper::getALlTagDataByTrackId($_REQUEST['track_id']);
		}
		exit(json_encode($tagdata));
	}
	
	/**
	 +----------------------------------------------------------
	 * 异步更新查询记录页面的单行
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/06/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUpdateTrackTrInfo(){
		$this->changeDBPuid();
		if (!empty($_REQUEST['track_id'])){
			
			$oneTracking = Tracking::findOne(['id'=>$_REQUEST['track_id']]);
			if (!empty($oneTracking)){
				$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
				$oneTracking['state']  = Tracking::getChineseState($oneTracking['state'] );
				$row = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking );
				$TrHTML['TrHtml'][$oneTracking['track_no']] = $row[$oneTracking['track_no']];
				exit(json_encode($TrHTML));
			}
		}
	}//end of actionUpdateTrackTrInfo
	
	/*
	 * 发信模板设置 页面
	 */
	public function actionMail_template_setting(){
		$this->changeDBPuid();
		
		if(isset($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}else{
			$sort = 'id';
			$order = 'asc';
		}
		$sortConfig = new Sort(['attributes' => ['id','name','subject']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		
		$templateData =TrackingHelper::getMsgTemplate($sort, $order) ;
		
		
		//get all template data
		$data['listTemplate'] = $templateData['data'];
		$data['listTemplateAddinfo'] = [];
		foreach($data['listTemplate']  as $oneTemplate){
			if (!empty($oneTemplate['addi_info'])){
				$data['listTemplateAddinfo'][$oneTemplate['id']]  = json_decode($oneTemplate['addi_info'],true);
			}else{
				$data['listTemplateAddinfo'][$oneTemplate['id']] = ['layout'=>1 , 'recom_prod_count'=>4];
			}
			$data['listTemplateAddinfo'][$oneTemplate['id']]['name'] = $oneTemplate['name'];
				
		}
		
		return $this->render('mail_template_setting',[
					'templateData'=>$templateData,
					'sortConfig'=>$sortConfig,
					'data'=>$data,
				]);
		
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 批量更新 无法 交运 物流信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionBatchUpdateUnshipParcel(){
		$this->changeDBPuid();
		TrackingHelper::batchUpdateUnshipParcel();
		AppTrackerApiHelper::actionLog("Tracker", "批量更新无法交运物流" );
		exit(json_encode(['success'=>true,'message'=>'更新全部无法交运物流请求已经接受!请20分钟后查看更新']));
	}//end of actionBatchUpdateUnshipParcel
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置 指定 的物流信息的 推荐商品 页面的布局  和 推荐商品数量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSetProductRecommendSetting(){
		$this->changeDBPuid();
		if (! empty($_REQUEST['track_no'])){
			if (! empty($_REQUEST['layout_id']))	
				$layout_id = $_REQUEST['layout_id'];
			else
				$layout_id = 1;
			
			if (!empty($_REQUEST['product_count']))
				$product_count  = $_REQUEST['product_count'];
			else 
				$product_count = 8;
			
			if (!empty($_REQUEST['recom_prod_group']))
				$recom_prod_group  = $_REQUEST['recom_prod_group'];
			else
				$recom_prod_group = 0;
			
			$TrackNoList[] = $_REQUEST['track_no'];
			TrackingHelper::setMessageConfig($TrackNoList,$layout_id,$product_count,$recom_prod_group);
		}
		
		
	}//end of actionSetProductRecommendSetting
	
	
	/**
	 * 打开用户使用的dash-board
	 * @param	int		$autoShow	调用类型：0:手动展示，1:自动展示
	 * @return	mixed
	 */
	public function actionUserDashBoard($autoShow=1){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($uid))
			exit('请先登录!');
		$chartData = TrackingHelper::getTrackerChartDataByUid($uid,10);
		$advertData = TrackingHelper::getAdvertDataByUid($uid,2);
	
		$autoShow = (int)$autoShow;
		if(!empty($autoShow)){//自动展示时，如果用户不手动收起dashboard，则认为用户未有任何操作，下次打开oms时无论是何时，依旧展示
			
		}else{//用户手动展示dashboard，则认为用户已经浏览了dashboard，下次展示为4小时后
			$set_redis = RedisHelper::RedisSet('Tracker_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
		}

		$chart['series'] = [];
		$series = [];
		return $this->renderAjax('_dash_board',[
				'chartData'=>$chartData,
				'advertData'=>$advertData,
				]);
	}
	public function actionHideDashBoard(){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($uid))
			return false;
		$set_redis = RedisHelper::RedisSet('Tracker_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
		return $set_redis;
	}
	
	/**
	 * 用户上传物流查询问题界面
	 */
	public function actionReportNoInfo($id){
		if(empty($id))
			exit('没有运单号!');
		$uid = \Yii::$app->subdb->getCurrentPuid();;
		if (empty($uid))
			exit('请先登录!');
		
		$trackModel = Tracking::findOne($id);
		if(!empty($trackModel)){
			$query = "SELECT * FROM `tracker_cases` WHERE `uid`=$uid and `track_no`='".$trackModel->track_no."' ";
			$command = Yii::$app->db->createCommand($query);
			$record = $command->queryOne();
			
			if(!empty($record)){
				$case = $record;
				if($record['status']=='C')
					$act = 'view';
				else 
					$act = 'edit';
				
			}else{
				$act = 'add';
				$case = [];
			}
		
			return $this->renderAjax('_report_case',[
					'model'=>$trackModel,
					'case'=>$case,
					'act'=>$act,
				]);
		}else 
			exit('运单信息缺失');
	}
	
	/**
	 * 用户上传物流查询问题-save
	 */
	public function actionSaveCustomerReport(){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($uid))
			exit('请先登录!');
		if(!empty($_POST)){
			$rtn = TrackingHelper::saveCase($uid,$_POST);
		}
		
		
		exit(json_encode($rtn));
	}
	
	public function actionIgnoreTrackingNo(){
		AppTrackerApiHelper::actionLog("Tracker", "忽略查询不到的物流" );
		if(!empty($_GET)){
			$track_id = [];
			if(is_array($_GET['id']))
				$track_id = $_GET['id'];
			elseif(is_string($_GET['id']))
				$track_id = explode(',', $_GET['id']);
				
			$rtn = TrackingHelper::ignoreTrackerNo($track_id);
			//force update the top menu statistics
			TrackingHelper::setTrackerTempDataToRedis("left_menu_statistics", json_encode(array()));
			TrackingHelper::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));
		}
		exit(json_encode($rtn));
	}

	
	public function actionRefreshOneTracking($id){

		if (!empty($id)){
			$this->changeDBPuid();
			$oneTracking = Tracking::findOne($id);
			if (!empty($oneTracking)){
				$oneTracking['status']  = Tracking::getChineseStatus($oneTracking['status'] );
				$oneTracking['state']  = Tracking::getChineseState($oneTracking['state'] );
				$row = TrackingHelper::generateQueryTrackingInfoHTML($oneTracking );
				$result['TrHtml'][$oneTracking['id']] = $row[$oneTracking['track_no']];
			}
		}
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		exit(json_encode($result));
	}//end of actionRefreshOneTracking
	
	
	public function actionShow17TrackTrackingInfo($num){
		if(empty($num) || trim($num)=='')
			exit('运单号缺失');
		return $this->renderAjax('_17_track_iframe',[
				'num'=>$num,
			]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 批量更新物流信息为已完成
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/07/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionMarkTrackingCompleted(){
		$this->changeDBPuid();
    	if (!empty($_POST['tracking_no_list'])){
    		// decode array 
    		$tracking_no_array = json_decode($_POST['tracking_no_list']);
			
			
    		$result = TrackingHelper::markTrackingCompleted($tracking_no_array);
    		AppTrackerApiHelper::actionLog("Tracker", "标记物流号已完成"  );
    		
    		
    	}
    	//force update the top menu statistics
    	ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
    	ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
    	exit(json_encode($result));
	}//end of function actionMarkTrackingCompleted
	
	/**
	 +----------------------------------------------------------
	 * 批量更新物流信息为运输途中
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/07/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionMarkTrackingShipping(){
		$this->changeDBPuid();
    	if (!empty($_POST['tracking_no_list'])){
    		// decode array 
    		$tracking_no_array = json_decode($_POST['tracking_no_list']);
			
			
    		$result = TrackingHelper::markTrackingShipping($tracking_no_array);
    		AppTrackerApiHelper::actionLog("Tracker", "标记物流号已完成"  );
    		
    		
    	}
    	//force update the top menu statistics
    	ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
    	ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
    	exit(json_encode($result));
	}//end of function actionMarkTrackingCompleted
	

	/**
	 +----------------------------------------------------------
	 * 批量忽略查询
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl 	2016/11/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionMarkTrackingIgnore(){
		$this->changeDBPuid();
		if (!empty($_POST['tracking_no_list'])){
			// decode array
			$tracking_no_array = json_decode($_POST['tracking_no_list']);
				
			$result = TrackingHelper::markTrackingIgnore($tracking_no_array);
			AppTrackerApiHelper::actionLog("Tracker", "忽略查询物流号"  );
		}
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		exit(json_encode($result));
	}//end of function actionMarkTrackingIgnore

	/**
	 +----------------------------------------------------------
	 * 标记为已发送某种自助提醒
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl 	2016/11/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionMarkTrackingIsSent(){
		$this->changeDBPuid();
		if (!empty($_POST['tracking_no_list']) && !empty($_REQUEST['pos']) ){
			// decode array
			$tracking_no_array = json_decode($_POST['tracking_no_list']);
			
			if($_REQUEST['pos']=='RSHP')
				$status = 'shipping';
			if($_REQUEST['pos']=='RPF')
				$status = 'arrived_pending_fetch';
			if($_REQUEST['pos']=='DF')
				$status = 'delivery_failed';
			if($_REQUEST['pos']=='RRJ')
				$status = 'rejected';
			if($_REQUEST['pos']=='RGE')
				$status = 'received';
			
			$result = TrackingHelper::markTrackingIsSent($tracking_no_array,$status);
			AppTrackerApiHelper::actionLog("Tracker", "标记为".$status."已发送"  );
		}
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		exit(json_encode($result));
	}//end of function actionMarkTrackingIsSent
	
	
	public function actionTest(){
		
		/*
		//性能 调试 log
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		*/
			
		$query = Tracking::find();
		$data = $query->limit($_REQUEST['limmit'])->asArray()->all();
		echo 'number : '.count($data);
		/*
		//性能 调试 log
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		$current_time_cost = $logTimeMS2-$logTimeMS1;
		$current_memory_cost = $logMemoryMS2-$logMemoryMS1;
		echo " \n  <br>".__FUNCTION__.' step 1  T=('.($current_time_cost).') and M='.($current_memory_cost).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M '.'<br>'; //test kh
		\Yii::info((__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ","file");
		*/
		
		return;
		
		TrackingHelper::copyTrackingFromOmsShippedComplete();
		
		TrackingHelper::requestTrackingForUid();
		
		TrackingHelper::postBufferIntoTrackQueue();
		return;
		$track_no = 'LVS1484350000053453';
		
		$tmpTracking = Tracking::find()->andWhere(['track_no'=>$track_no])->asArray()->One();
		echo json_encode($tmpTracking);
		return;
		$rr = TrackingHelper::getOrderDetailFromOMSByTrackNo($track_no);
		var_dump($rr);
		return ;
		if (!empty($_REQUEST['limit'])){
			$limit = $_REQUEST['limit'];
		}else{
			$limit = 5000;
		}
		
		$offset = 0;
		$condition=" 1 ";
		$query = Tracking::find();
		$connection = Yii::$app->subdb;
		echo "model asArray : <br>";
		for( $i=0;$i<5;$i++){
			
			$data ['condition'] = $condition;
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				//echo __FUNCTION__.' step 1  :'.(memory_get_usage()/1024/1024). 'M<br>'; //test kh
			}
			/* */
			 $data['data'] = $query
			//->andWhere($condition,$bindVals)
			->offset($offset)
			->limit($limit)
			//->orderBy(" $sort $order  , id $order ")
			->asArray()
			->all();
			
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
			
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				echo __FUNCTION__.' step 2  T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($data['data']).'<br>'; //test kh
				\Yii::info("get lt_tracking data  total=".count($data['data']).",t2_1=".($logTimeMS2-$logTimeMS1).
						",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
				
				unset($data);
			}
			
			
		}//end of for model as array
		
		echo "/********************************************************/<br>";
		echo "command : <br>";
		for( $i=0;$i<5;$i++){
				
			$data ['condition'] = $condition;
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				//echo __FUNCTION__.' step 1  :'.(memory_get_usage()/1024/1024). 'M<br>'; //test kh
			}
			
			$sql = "select * from lt_tracking  where  ".$condition." limit ".$limit." offset ".$offset." ";
			$command = $connection->createCommand($sql);
			//$command->bindValues($bindVals);
			
			$data['data'] = $command->queryAll();
				
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
				
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				echo __FUNCTION__.' step 2  T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($data['data']).'<br>'; //test kh
				\Yii::info("get lt_tracking data  total=".count($data['data']).",t2_1=".($logTimeMS2-$logTimeMS1).
						",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
		
				unset($data);
			}
		}//end of for model as array
		
	}//end of actionTest
	//删除tracking 发送失败的信息
	public function actionCancelFailureMessage(){
	    if(empty($_POST['message_id'])){
	        return json_encode(array('success'=>false,'data'=>'','error'=>'数据有误，撤销失败'));
	    }else{
	        $result = Message::findOne($_POST['message_id']);
	        if(empty($result)){
	            return json_encode(array('success'=>false,'data'=>'','error'=>'没有找到相关数据，撤销失败'));
	        }else{
	            if($result->delete()){
	                if(!empty($_POST['order_id'])){
	                    $check_result = Message::find()->where(['order_id'=>$_POST['order_id'],'status'=>'F'])->asArray()->all();
	                    if(count($check_result) == 0){
	                        $tracking_record = Tracking::find()->where(['order_id'=>$_POST['order_id'],'track_no'=>$_POST['track_no']])->one();
	                        if(!empty($tracking_record)){
	                            $tracking_record->msg_sent_error = 'N';
	                            $tracking_record->save(false);
	                        }
	                    }
	                }
	                return json_encode(array('success'=>true,'data'=>'','error'=>''));
	            }else{
	                return json_encode(array('success'=>false,'data'=>'','error'=>'撤销失败'));
	            }
	        }
	    }
	}
	    
	/**
	 * 设置某个运输服务方式为自动忽略查询
	 * @return array()
	 */
	public function actionIgnoreShipType(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$ship_by = empty($_REQUEST['ship_by'])?'':trim($_REQUEST['ship_by']);
		if(empty($ship_by))
			return json_encode(array('success'=>false,'message'=>'设置失败，物流服务方式不能为空'));
		$ship_by = base64_decode($ship_by);
		$config = ConfigHelper::getConfig('IgnoreToCheck_ShipType','NO_CACHE');
		if(!empty($config))
			$config = json_decode($config,true);
		else
			$config = [];
		
		if(!in_array($ship_by, $config))
			$config[] = $ship_by;
		try{
			$rtn = ConfigHelper::setConfig('IgnoreToCheck_ShipType', json_encode($config));
			if($rtn)
				$rtn = TrackingHelper::setUserIgnoredCheckCarriers($puid,$config);
			else 
				return json_encode(array('success'=>false,'message'=>'设置失败:保存新设置时出错'));
			
			if(!$rtn['success'])
				return json_encode(array('success'=>false,'message'=>$rtn['message']));
		}catch (\Exception $e){
			return json_encode(array('success'=>false,'message'=>print_r($e->getMessage(),true) ));
		}
		
		global $CACHE;
		$CACHE['IgnoreToCheck_ShipType'] = $config;
		
		return json_encode(array('success'=>true,'message'=>''));
	}
	
	/**
	 * 取消某个运输服务方式的自动忽略查询设定
	 * @return array()
	 */
	public function actionReActiveShipType(){
		$ship_by = empty($_REQUEST['ship_by'])?'':trim($_REQUEST['ship_by']);
		if(empty($ship_by))
			return json_encode(array('success'=>false,'message'=>'设置失败，物流服务方式不能为空'));
		$ship_by = base64_decode($ship_by);
		$config = ConfigHelper::getConfig('IgnoreToCheck_ShipType','NO_CACHE');
		if(!empty($config))
			$config = json_decode($config,true);
		else
			return json_encode(array('success'=>true,'message'=>''));//未有任何设置,则返回成功
		
		$new_config = [];
		foreach ($config as $c){
			if($c!==$ship_by)
				$new_config[] = $ship_by;
		}
		
		try{
			$rtn = ConfigHelper::setConfig('IgnoreToCheck_ShipType', json_encode($new_config));
			if(!$rtn)
				return json_encode(array('success'=>false,'message'=>'设置失败:保存新设置时出错'));
			
			$puid = \Yii::$app->user->identity->getParentUid();
			$rtn = TrackingHelper::setUserIgnoredCheckCarriers($puid, $new_config);
			if(!$rtn['success'])
				return json_encode(array('success'=>false,'message'=>$rtn['message']));
				
		}catch (\Exception $e){
			return json_encode(array('success'=>false,'message'=>print_r($e->getMessage(),true) ));
		}
		
		global $CACHE;
		$CACHE['IgnoreToCheck_ShipType'] = $config;
		
		return json_encode(array('success'=>true,'message'=>''));
	}
	
	/**
	 * 手动设置查询使用的物流类型 view
	 * lzhl		2017-01-10
	 */
	public function actionSetCarrierTypeWin(){
		//$puid = \Yii::$app->subdb->getCurrentPuid();
		if(empty($_REQUEST['track_ids'])){
			exit('请勾选物流号再进行操作');
		}
		if(!empty($_REQUEST['act']))
			$act=trim($_REQUEST['act']);
		else 
			$act = 'singal';
		
		$track_ids = explode(',', $_REQUEST['track_ids']);
		Helper_Array::removeEmpty($track_ids);
		
		$trackings = Tracking::findAll($track_ids);
		
		if(empty($trackings)){
			exit('没有有效的物流号');
		}
		return $this->renderAjax('_set_carrier_type',[
				'trackings'=>$trackings,
				'act'=>$act,
			]);
	}
	
	public function actionSaveTrackingCarrierType(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$classification = "Tracker_AppTempData";
		$key = "userLastSetCarrierTypeTime";
		//获取上次操作时间
		$lastSetCarrierTypeTime = RedisHelper::RedisGet($classification,"user_$puid".".".$key);
		if(!empty($lastSetCarrierTypeTime) && time()-(int)$lastSetCarrierTypeTime < 5){
			exit(json_encode(['success'=>false,'message'=>'操作过于频繁，请稍后再试']));
		}
		
		if(empty($_REQUEST['carrier_type']))
			exit(json_encode(['success'=>false,'message'=>'没有选的物流号']));
		
		$shipBy_carrierType_mapping = [];//物流商服务 和 carrier_type的mapping
		$refresh_track_no_list = [];//需要立即查询的物流号id
		
		foreach ($_REQUEST['carrier_type'] as $track_id=>$carrier_type){
			$tracking = Tracking::findOne($track_id);
			if(empty($tracking))
				continue;
			
			$sameNumberTracks = Tracking::find()->where(['track_no'=>$tracking->track_no])->all();
			foreach ($sameNumberTracks as $st){
				$st->carrier_type = $carrier_type;
				//更新track_no的carrier_type 和将操作记录到addi_info
				$st->carrier_type = $carrier_type;
				
				$addi_json = $st->addi_info;
				if(!empty($addi_json)){
					$addi = json_decode($addi_json,true);
				}
				if(empty($addi))
					$addi = [];
				$addi['set_carrier_type'] = $carrier_type;
				$addi['set_carrier_type_time'] = TimeUtil::getNow();
				
				$st->addi_info = json_encode($addi);
				
				if(!$st->save(false)){
					exit(json_encode(['success'=>false,'message'=>$st->track_no.'信息更新失败，E001']));
				}
			}
			
			//修改carrier_type成功，则将track id 和 mapping关系存入数组
			$refresh_track_no_list[$tracking->track_no] = $carrier_type;
			if(empty($shipBy_carrierType_mapping[$tracking->ship_by]) || !in_array($carrier_type, $shipBy_carrierType_mapping[$tracking->ship_by]))
				$shipBy_carrierType_mapping[$tracking->ship_by][] = $carrier_type;
				
		}
		//组织写入redis的mapping数据
		$redisData = [];
		foreach ($shipBy_carrierType_mapping as $ship_by=>$carrier_type_mapping){
			//mapping关系多于1个，则不能指定，跳过。
			$carrier_type_mapping = array_unique($carrier_type_mapping);
			if(empty($carrier_type_mapping) || count($carrier_type_mapping)>1)
				continue;
			if(count($carrier_type_mapping)==1)
				$redisData[$ship_by] = $carrier_type_mapping[0];
		}
		//写入redis
		if(!empty($redisData)){
			TrackingHelper::addUserShipByAndCarrierTypeMappingToRedis($redisData);
		}
		
		$rtn = ['success'=>true,'message'=>''];

		//$refresh track 信息
		foreach ($refresh_track_no_list as $track_no=>$carrier_type ){
			$refresh_result = TrackingHelper::generateOneRequestForTracking($track_no,true,'',['setCarrierType'=>true,'CarrierType'=>$carrier_type]);
			if(empty($refresh_result['success']) && !empty($refresh_result['message'])){
				$rtn['success'] = false;
				$rtn['message'] .= $refresh_result['message'];
			}
		}
		TrackingHelper::postTrackingApiQueueBufferToDb();
		
		//记录上次操作完成时间
		RedisHelper::RedisSet($classification,"user_$puid".".".$key,time());
		exit(json_encode($rtn));
	}
	
	public function actionTestL(){
		$ship_by = @$_REQUEST['ship_by'];
		$carrier_type = @$_REQUEST['carrier_type'];
		$rtn = TrackingHelper::addGlobalShipByAndCarrierTypeMappingToRedis([$ship_by=>$carrier_type]);
		//TrackingHelper::getGlobalShipByAndCarrierTypeMappingFromRedis();
	}
	
	/*
	 * @author lzhl	2017-09-14
	 */
	public function actionGetIgnoreCarriersWin(){
		//获取用户常用的物流方式
		$using_carriers = TrackingHelper::getTrackerTempDataFromRedis("using_carriers" ); //ConfigHelper::getConfig("Tracking/using_carriers");
		 
		if (!empty($using_carriers))
			$using_carriers = json_decode($using_carriers,true);
		else 
			$using_carriers = [];
		if(!empty($using_carriers)){
			$userIgnoredCheckCarriers = TrackingHelper::getUserIgnoredCheckCarriers();
			if(!empty($userIgnoredCheckCarriers['success']))
				$userIgnoredCheckCarriers = $userIgnoredCheckCarriers['data'];
			else 
				$userIgnoredCheckCarriers = [];
			
			$html=
				'<form id="set-ignore-carriers">
					<ul>';
			foreach($using_carriers as $key=>$val){
				if(empty($key)) continue;
				$html .= '<li>
							<label for="carrier_'.$key.'">'
								.$val.
								'<input type="checkbox" name="carriers[]" value="'.$key.'" id="carrier_'.$key.'" '.((in_array($key, $userIgnoredCheckCarriers))?"checked":'').'>
							</label>
						</li>';
			}
			$html.='</ul>
			</form>';
			//$html .= '<button type="button" class="btn btn-success" onclick="ListTracking.SetIgnoreCarriers()">保存</button>';
		}else{
			$html = '暂无可用物流商';
		}
		
		return $html;
	}
	
	/*
	 * @author lzhl	2017-09-14
	*/
	public function actionSetIgnoreCarriers(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$carriers = empty($_REQUEST['carriers'])?[]:$_REQUEST['carriers'];
// 		var_dump($carriers);
		
		try{
			$rtn = ConfigHelper::setConfig('IgnoreToCheck_ShipType', json_encode($carriers));
			if(!$rtn)
				exit (json_encode(array('success'=>false,'message'=>'设置失败:保存新设置时出错')));
		}catch (\Exception $e){
			exit (json_encode(array('success'=>false,'message'=>print_r($e->getMessage(),true) )));
		}
		
		$rtn = TrackingHelper::setUserIgnoredCheckCarriers($puid, $carriers);
		exit(json_encode($rtn));
	}
	
	public function actionQuotaInsufficeientReSearch(){
		$this->changeDBPuid();
		$rtn['success'] = true;
		$rtn['message'] = '';
		if (!empty($_POST['tracking_no_list'])){
			$tracking_no_array = json_decode($_POST['tracking_no_list']);
			
			$errMsg = '';
			foreach ($tracking_no_array as $track_no){
				$rtn = TrackingHelper::generateOneRequestForTracking($track_no, Tracking::$IS_USER_REQUIRE_UPDATE); //true means waiting online
				if(!$rtn['success']){
					$errMsg .= $rtn['message']."<br>";
					$rtn['success'] = false;
				}
			}
			$result['message'] = $errMsg;
		}else{
			$rtn['success'] = false;
			$result['message'] = '请选择物流号！';
		}
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		exit(json_encode($result));
	}
	
	public function actionCheckPlatformIsSetGetDaysAgoOrderTrackNo(){
		$rtn = ['show'=>false,'days'=>-1,'html'=>''];
		$puid = \Yii::$app->subdb->getCurrentPuid();
		if(empty($puid))
			return json_encode(['show'=>false,'days'=>-1,'html'=>'']);
		
		$platform = @$_REQUEST['platform'];
		if(empty($platform))
			return  json_encode(['show'=>false,'days'=>-1,'html'=>'']);
		
		$platform = strtolower($platform);
		$key = 'PlatformGetOrderTrackNoDays';
		$setting = RedisHelper::RedisGet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key);
		if(!empty($setting))
			$setting = json_decode($setting,true);
		else 
			$setting = [];
		if(isset($setting[$platform])){
			$days = (int)$setting[$platform];
			return json_encode(['show'=>false,'days'=>$days,'html'=>'']);
		}
		
		$days = 7;
		$rtn['days'] = 7;//默认
		$rtn['show'] = true;
		
		$html = '<div>';
		$html.= '<p class="alert" style="text-align:left;line-height:2;">如果您之前从未设置过小老板物流跟踪助手在账号新绑定时，往前同步'.$platform.'平台多少天的订单物流跟踪号，那么您现在需要设置一次，避免同步太多旧单而消耗物流跟踪助手查询配额。</p>';
		$html.= '<p class="alert alert-success">新绑定的'.$platform.'账号，物流跟踪助手为其同步<input type="number" id="getHowManyDaysAgo" name="getDaysAge" value="'.$days.'" style="width:50px">天前的订单物流号</p>';
		$html.= '<p class="alert" style="text-align:left;line-height:2;">设置后，日后所有同平台的新绑定账号都使用同一设定。<br>您可以到物流跟踪助手页面修改该设定。</p>';
		$html.= '</div>';
		$rtn['html'] = $html;
		return json_encode($rtn);
	}
	
	public function actionSetGetDaysAgoOrderTrackNo(){
		$platform = @$_REQUEST['platform'];
		if(empty($platform))
			return false;
		
		if(!isset($_REQUEST['days']))
			return false;
		if($_REQUEST['days']=='')
			$days=7;
		else 
			$days = (int)$_REQUEST['days'];
		if($days<0)
			$days=0;
		
		$rtn = TrackingHelper::setPlatformGetHowLongAgoOrderTrackNo($platform,$days);
		return $rtn;
	}
	
	public function actionGetOdTracknoDaysSet(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$key = 'PlatformGetOrderTrackNoDays';
		$setting = RedisHelper::RedisGet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key);
		if(!empty($setting))
			$setting = json_decode($setting,true);
		else
			$setting = [];
		
		$request = empty($_REQUEST['setting'])?[]:$_REQUEST['setting'];
		if(!empty($request) && is_array($request)){
			foreach ($request as $platform=>$days){
				$days = (int)$days;
				if($days<0)
					$days=0;
				$setting[$platform] = $days;
			}
		}
		
		$rtn = RedisHelper::RedisSet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key,json_encode($setting));
		
		return $this->render('get_od_trackno_days_set',[
				'setting'=>$setting,
				'platforms'=>['ebay'=>'eBay','aliexpress'=>'速卖通','wish'=>'Wish','dhgate'=>'敦煌','lazada'=>'Lazada','jumia'=>'Jumia','linio'=>'Linio'],
			]);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查看额度信息
	 * @author		lzhl	2017/09/--		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionViewQuota(){
		AppTrackerApiHelper::actionLog("Tracker", "查看配额信息");
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$used_count = TrackingHelper::getTrackerUsedQuota($puid);
		$max_import_limit = TrackingHelper::getTrackerQuota($puid);
		
		return $this->renderAjax('_view_quota',[
				'used_count'=>$used_count,
				'max_import_limit'=>$max_import_limit,
				]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 将忽略的重新查询
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl 	2017/09/28				初始化
	 +----------------------------------------------------------
	 **/
	public function actionIgnoredReSearch(){
		$this->changeDBPuid();
		if (!empty($_POST['tracking_no_list'])){
			// decode array
			$tracking_no_array = json_decode($_POST['tracking_no_list']);
	
			$result = TrackingHelper::ignoredTrackingReSearch($tracking_no_array);
			AppTrackerApiHelper::actionLog("Tracker", "将忽略设为重新查询"  );
		}
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		exit(json_encode($result));
	}//end of function actionMarkTrackingIgnore
	
	public function actionTestApi(){
// 		$platform = 'dhgate';
// 		$day = \eagle\modules\tracking\helpers\TrackingHelper::getPlatformGetHowLongAgoOrderTrackNo($platform);
// 		var_dump($day);
		
		$ignor = \eagle\modules\tracking\helpers\TrackingHelper::getUserIgnoredCheckCarriers();
		var_dump($ignor);
		exit();
	}
	
	public function actionTranslateEvents(){
		$track_no = @$_REQUEST['track_no'];
		$track_id = @$_REQUEST['track_id'];
		$to_lang = @$_REQUEST['to_lang'];
		$translateRtn = \eagle\modules\tracking\helpers\TrackingHelper::translateTrackingEvent($track_no, $to_lang);
		if($translateRtn['success']){
			exit(json_encode(['success'=>true,'message'=>'','html'=>@$translateRtn['eventHtml']]));
		}else{
			exit(json_encode(['success'=>false,'message'=>@$translateRtn['message'],'html'=>@$translateRtn['eventHtml']]));
		}
	}
	
	public function actionTestTran(){
		$track_no = 'RX402226405DE';
		$html = \eagle\modules\tracking\helpers\TrackingHelper::translateTrackingEvent($track_no, $to_lang='zh');
		return $html;
		exit($html);
	}
}
