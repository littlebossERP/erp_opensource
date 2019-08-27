<?php
namespace console\helpers;

use eagle\modules\platform\helpers\WishAccountsHelper;
use \Yii;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\models\QueueAliexpressGetorder;
use eagle\models\QueueAliexpressGetorder2;
use eagle\models\QueueAliexpressGetorder4;
use eagle\models\QueueAliexpressGetfinishorder;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasAliexpressUser;
use eagle\models\listing\AliexpressListing;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\models\CheckSync;
use eagle\models\QueueAliexpressPraise;
use eagle\models\QueueAliexpressPraiseInfo;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use console\helpers\AliexpressClearHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\AliexpressCategory;
use eagle\models\AliexpressListingDetail;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use common\helpers\Helper_Currency;
use eagle\modules\util\helpers\ExcelHelper;
use Qiniu\json_decode;
use eagle\models\ImportEnsogoListing;
use eagle\modules\listing\helpers\EnsogoStoreMoveHelper;
use eagle\modules\listing\models\EnsogoCategories;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\models\SaasEnsogoUser;
use eagle\models\QueueManualOrderSync;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SaasAliexpressAutosyncV2;
use eagle\models\QueueAliexpressGetorderV2;
/**
 +------------------------------------------------------------------------------
 * Aliexpress 数据同步类
 +------------------------------------------------------------------------------
 */
class AliexpressHelperAutoOrder {
	public static $cronJobId=0;
	private static $aliexpressGetOrderListVersion = null;
    private static $aliexpressAutoOrderListVerion= null;
	private static $version = null;


    protected static $active_users;

    protected static function isActiveUser($uid) {
		return true;
        // if(empty(self::$active_users)) {
            // self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
        // }

        // if(in_array($uid, self::$active_users)) {
            // return true;
        // }

        // return false;
    }

	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
	/**
	 * @param string $format. output time string format
	 * @param timestamp $timestamp
	 * @return America/Los_Angeles formatted time string
	 */
	static function getLaFormatTime($format , $timestamp){
		$dt = new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
		return $dt->format($format);
	}

    public static function getApiInfo(){

        
        $api = new AliexpressInterface_Api ();
        $access_token = $api->getAccessToken ( 'cn1510671045' );//74479386066578
        $api->access_token = $access_token;
        //$res= $api->findOrderById(['orderId'=>'76804726209601']);
        $paramx = array(
            'page' => 1,
            'pageSize' => 50,
        );
        //先试试1秒的误差
        $start_time= 1468606369-60;
        $end_time= 1468606369+60;
        $orderStatus= 'WAIT_SELLER_SEND_GOODS';

        $paramx['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
        $paramx['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
        $paramx['orderStatus']= $orderStatus;

        $res= $api->findOrderListQuery( $paramx );
        return $res;

    }
    /**
     *获取ali推送的订单列表
     *
     *
     * @author akirametero
     */
    public static function getAliAutoOrder(){

        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressAutoOrderListVerion",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressAutoOrderListVerion)) {
            self::$aliexpressAutoOrderListVerion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressAutoOrderListVerion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressAutoOrderListVerion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s').' getAliAutoOrder script start '.self::$cronJobId.PHP_EOL;

        $connection=Yii::$app->db_queue;
        $connection_db_queue2 = Yii::$app->db_queue2;
        $db= Yii::$app->db;
        $now_time= time();
        $res= $connection->createCommand("SELECT * FROM queue_aliexpress_auto_order WHERE is_lock=0  ORDER BY ID ASC LIMIT 10")->query();
        //$res= $connection->createCommand("SELECT * FROM queue_aliexpress_auto_order WHERE is_lock=0 and sellerloginid='cn1511119925' ORDER BY ID ASC LIMIT 10")->query();
       // $res= $connection->createCommand("SELECT * FROM queue_aliexpress_auto_order WHERE order_id='80201726907065'  ORDER BY ID ASC LIMIT 10")->query();

        $result= $res->readAll();
        if( empty( $result ) ){
            return false;
        }
        foreach( $result as $vs ){
            //多线程预处理,锁定这条数据
            $id= $vs['id'];
            $updater= $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1 WHERE id={$id}")->execute();
            echo 'queue_aliexpress_auto_order锁定ID-',$id,PHP_EOL;
        }
        //foraech
        //前一个速卖通店铺ID,用来对比是否需要切换数据库,节约时间
        foreach( $result as $vs ){

            //$again_sellerloginid= $result[$k-1]['sellerloginid'];
            //当前的速卖通店铺id
            $id= $vs['id'];
            $sellerloginid= $vs['sellerloginid'];
            $order_status= $vs['order_status'];
            $order_id= $vs['order_id'];
            $order_change_time= $vs['order_change_time'];
            $msg_id= $vs['msg_id'];
            $order_type= $vs['order_type'];
            $gmtBorn= $vs['gmtBorn'];
            $ajax_message= $vs['ajax_message'];
            $status= $vs['status'];
            $push_status= $vs['push_status'];
            $error_message= $vs['error_message'];
            $create_time= $vs['create_time'];
            $update_time= $vs['update_time'];
            $last_time= $vs['last_time'];
            $next_time= $vs['next_time'];

            //根据sellerloginid去确定用户ID,切换数据用,通过saas_aliexpress_user获取用户uid,只针对启用的账户,没启用的就不去占用资源了
            $rs= $db->createCommand("SELECT uid,aliexpress_uid FROM saas_aliexpress_user WHERE sellerloginid ='{$sellerloginid}' AND is_active=1 ")->query();
            $rs_user= $rs->read();
            
 
            $rs_orderinfo= $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$order_id}' ORDER BY  id DESC")->query()->read();
            $order4eof= true;
            if( empty( $rs_orderinfo ) ){
                //队列1中不存在这个信息，给写入一个
                $order4eof= false;
                echo '队列4中不存在订单orderid-',$order_id,PHP_EOL;
                //continue;
            }else{
                echo 'order4存在数据';
                $insertid= $rs_orderinfo['id'];
            }


            //$rs_user['uid']== 1180;
            //判断这个订的在队列1表queue_aliexpress_getorder中是否存在,存在获取order_info
            if( $rs_user!==false  ){
                //切换数据库
                $datebase_eof= true;

                if( $datebase_eof===false ) {
                    //没有数据库
                    echo '用户数据库不存在uid-',$rs_user['uid'];
                    return false;
                }else{
                    //判断用户的授权信息
                    $api = new AliexpressInterface_Api ();
                    if (!AliexpressInterface_Auth::checkToken ( $sellerloginid )) {
                        $update= $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2 ,error_message='授权出问题了' WHERE id={$id}")->execute();
                        echo '授权出问题了',$sellerloginid;
                        continue;
                    }

                    $access_token = $api->getAccessToken ( $sellerloginid );
                    //获取访问token失败
                    if ($access_token === false){
                        $update= $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,error_message='店铺访问token失败' WHERE id={$id}")->execute();
                        echo '店铺sellerloginid-访问token失败-',$sellerloginid;
                        continue;
                    }
                    //$access_token= "b9df07eb-9ec2-4830-ba96-2acf6eda8268";
                    $api->access_token = $access_token;
                    
                    
                    //通过orderid 判断用户库的小老板订单表是否有这个订单
                    $rf_od= Yii::$app->subdb->createCommand("SELECT order_id,paid_time,fulfill_deadline,order_source_create_time,order_source_status FROM od_order_v2 WHERE order_source_order_id='{$order_id}' AND order_source ='aliexpress' ")->query()->read();
                    if( !empty( $rf_od ) ){

                        // 接口传入参数速卖通订单号
                        $param = ['orderId' => $order_id];
                        $res= $api->findOrderById ( $param );
                        /**
                        //订单状态不对
                        if( $order4eof===true ){
                            if( $res['orderStatus']!=$rs_orderinfo['order_status'] ){
                                $order4eof= false;
                                echo 'order4中的状态略旧也没有写入新的';
                            }
                        }**/
                        if( $order4eof===false ){
                            //order4 没有数据
                            $page = 1;
                            $pageSize = 50;
                            $start_time= self::getLaFormatTime("m/d/Y H:i:s", AliexpressInterface_Helper::transLaStrTimetoTimestamp($res['gmtCreate']));
                            $end_time= self::getLaFormatTime("m/d/Y H:i:s", AliexpressInterface_Helper::transLaStrTimetoTimestamp($res['gmtCreate']));
                            $param = ['page' => 1, 'pageSize' => 50, 'createDateStart' => $start_time, 'createDateEnd' => $end_time];
                            print_r ($param);
                            $result = $api->findOrderListSimpleQuery($param);
                            print_r ($result);
                            if( isset ($result['totalItem']) && $result ['totalItem']> 0) {
                                $aurs = SaasAliexpressAutosync::find()->where(['sellerloginid'=>$sellerloginid])->asArray()->one();
                                if( empty( $aurs ) ){
                                    break;
                                }
                                //print_r ($result ['orderList']);
                                foreach ( $result ['orderList'] as $one ) {
                                    $orderid = $one['orderId'];
                                    if( $orderid==$order_id ){
                                        //订单产生时间
                                        echo '开始写入order4';
                                        $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
                                        $order_info = $one;
                                        $QAG_four = new QueueAliexpressGetorder4();
                                        $QAG_four->uid = $rs_user['uid'];
                                        $QAG_four->sellerloginid = $sellerloginid;
                                        $QAG_four->aliexpress_uid = $aurs['aliexpress_uid'];
                                        $QAG_four->order_status = $one['orderStatus'];
                                        $QAG_four->orderid = $orderid;
                                        $QAG_four->order_info = json_encode($order_info);
                                        $QAG_four->gmtcreate = $gmtCreate;
                                        $boolfour = $QAG_four->save(false);
                                        $insertid= $QAG_four->id;
                                        echo 'insertid--'.$insertid;
                                        break;
                                    }else{
                                        echo 'orderid='.$orderid.'---one_orderid='.$one['orderId'];
                                    }
                                }
                            }
                        }
                        //
                        if( !isset( $insertid ) ){
                            echo 'no $insertid--'.$order_id;
                            continue;
                        }

                        //更新订单状态以及更新订单信息
                        $QAG_obj = QueueAliexpressGetorder4::findOne($insertid);
                        if(!$QAG_obj) {
                            echo 'QueueAliexpressGetorder4 no info -'.$order_id;
                            continue;
                        }
                        //判断小老板的订单表中,是否有剩余发货时间记录了
                        $vo= true;
                        if( $vo===true ){
                            //没有剩余发货时间,走接口,获取
                            $paramx = array(
                                'page' => 1,
                                'pageSize' => 50,
                            );
                            //先试试1秒的误差
                            $start_time= $rf_od['order_source_create_time'];
                            $end_time= $rf_od['order_source_create_time'];
                            $orderStatus= $rf_od['order_source_status'];

                            $paramx['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                            $paramx['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                            $paramx['orderStatus']= $orderStatus;

                            $api_time= time();//接口调用时间
                            $result2 = $api->findOrderListQuery($paramx);
                            //print_r ($paramx);
                            //print_r ($result2);
                            /**
                            if( $sellerloginid=='cn1518946269nbfs' ){
                                $mail = Yii::$app->mailer->compose();
                                $mail->setTo("akirametero@vip.qq.com");
                                $mail->setSubject('4664-同步日志--订单--'.$order_id.'----'.$orderStatus);
                                $mail->setTextBody(json_encode($result2));
                                $mail->send();
                            }
                            **/
                            if( isset( $result2['totalItem'] ) && isset( $result2['orderList'] ) ){
                                if( !empty( $result2['orderList'] ) ){
                                    $leftSendGoodDay= 0;
                                    $leftSendGoodHour= 0;
                                    $leftSendGoodMin= 0;
                                    //买家指定的物流服务
                                    $logisticsServiceName_arr= array();
                                    $memo_arr= array();
                                    foreach ( $result2 ['orderList'] as $ordervs ) {
                                        //循环找到对应的orderid的数组
                                        if( isset( $ordervs['productList'] ) ){
                                            \Yii::info("用户puid".$rs_user['uid'].json_encode($ordervs['productList']),"file");
                                            foreach( $ordervs['productList'] as $pl ){
                                                //客选物流
                                                if( isset( $pl['logisticsServiceName'] ) ){
                                                    $logisticsServiceName= $pl['logisticsServiceName'];
                                                    $productid= $pl['productId'];
                                                    $logisticsServiceName_arr["shipping_service"][$productid]= $logisticsServiceName;
                                                }
                                                //买家备注
                                                if( isset($pl['memo']) ){
                                                    $pmemo= str_replace("'","",$pl['memo']);
                                                    if( $pmemo=='' ){
                                                        $pmemo= '无';
                                                    }
                                                    $memo_arr[]= $pmemo;
                                                    //$logisticsServiceName_arr["user_message"][$productid] = $memo;
                                                }
                                            }
                                        }
                                        //判断是否存在剩余发货时间的3个属性
                                        if( isset( $ordervs['leftSendGoodDay'] ) ){
                                            //这个是天,换算成秒数
                                            $leftSendGoodDay= $ordervs['leftSendGoodDay']*86400;
                                        }else{
                                        }
                                        if( isset( $ordervs['leftSendGoodHour'] ) ){
                                            //这个是小时,换算成秒数
                                            $leftSendGoodHour= $ordervs['leftSendGoodHour']*3600;
                                        }else{
                                        }
                                        if( isset( $ordervs['leftSendGoodMin'] ) ){
                                            //这个是分组,换算成秒数
                                            $leftSendGoodMin= $ordervs['leftSendGoodMin']*60;
                                        }else{
                                        }
                                        //update 买家指定的物流服务商
                                        print_r ($logisticsServiceName_arr);
                                        if( !empty( $logisticsServiceName_arr ) ){
                                            list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $order_id, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
                                            if( $order_status===false ){
                                                echo $order_id.'可选物流更新失败--'.$msg.PHP_EOL;
                                            }
                                        }
                                        //处理最后发货时间,如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
                                        if( $leftSendGoodDay>0 || $leftSendGoodHour>0 || $leftSendGoodMin>0 ){
                                            //在接口调用时间上,加上秒数就是最后发货时间啦
                                            $fulfill_deadline= ceil($leftSendGoodDay+$leftSendGoodHour+$leftSendGoodMin+$api_time);
                                            //更新掉字段
                                            Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_id=".$rf_od['order_id'])->execute();
                                        }else{
                                        }

                                        $memo= '';
                                        if( !empty( $memo_arr ) ){
                                            $memo_eof= false;
                                            foreach( $memo_arr as $memo_vss ){
                                                if( $memo_vss!='无' ){
                                                    $memo_eof= true;
                                                    break;
                                                }
                                            }
                                            if( $memo_eof===true ){
                                                foreach( $memo_arr as $key=>$memo_vss ){
                                                    $count= $key+1;
                                                    $memo.= "商品{$count}:{$memo_vss};";
                                                }
                                            }
                                        }

                                        //处理买家备注
                                        //update

                                        if( $memo!='' ){
                                            Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_id=".$rf_od['order_id'])->execute();
                                            $sysTagRt = OrderTagHelper::setOrderSysTag($rf_od['order_id'], 'pay_memo');
                                            if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400'){
                                                echo '\n'.$rf_od['order_id'].' insert pay_memo failure :'.$sysTagRt['message'];
                                            }
                                        }
                                    }
                                }
                            }else{

                                echo '接口findOrderListQuery 没有货到到信息';
                            }
                        }else{
                            //有剩余发货时间,就不处理了
                        }


                        //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
                        if (isset($res['id']))  $res['id']=strval($res['id']);
                        //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
                        $res["sellerOperatorLoginId"]= $QAG_obj->sellerloginid;

                        $r = AliexpressInterface_Helper::saveAliexpressOrder ( $QAG_obj, $res );
                        if($r['success'] != 0 ) {
                            //save false,更新推送队列中的status=2
                            if( isset($r['message']) && isset( $r['success'] ) ){
                                $error= $r['success'].'--'.$r['message'];
                            }else{
                                $error= '订单更新失败';
                            }
                            $update_t= date("Y-m-d H:i:s");
                            $update= $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,error_message='{$error}',update_time='{$update_t}'  WHERE id={$id}")->execute();

                            //echo 'false-订单更新失败-用户ID-',$rs_user['uid'],'-订单orderid-',$order_id,'-订单状态status-',$order_status,'-速卖通sellerloginid-',$sellerloginid,PHP_EOL;
                        }else{
                            $update_t= date("Y-m-d H:i:s");
                            //save true,更新推送队列中的status=3
                            $update= $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}' WHERE id={$id}")->execute();
                            //echo 'true-订单更新成功-用户ID-',$rs_user['uid'],'-订单orderid-',$order_id,'-订单状态status-',$order_status,'-速卖通sellerloginid-',$sellerloginid,PHP_EOL;

                            //如果是完成状态的订单,删除队列4中的数据
                            if( $order_status=='FINISH' ){
                                $delete= $connection->createCommand("DELETE FROM queue_aliexpress_getorder4 WHERE orderid='{$order_id}' ")->execute();
                            }

                            //从orderv2表中获取


                        }

                    }else{
                        //没有找到订单数据,添加到已完成的订单队列中
                        echo '在小老板订单中没有找到orderid-',$order_id,PHP_EOL;
                        
                        //插入到db_queue2，漏单信息表，lrq20171121
                        try{
	                        $insertSql2= "INSERT INTO aliexpress_not_push_order (`sellerloginid`,`order_status`,`order_id`,`status`,`update_status`,`create_time`,`update_time`,`msg`)".
	                        	" VALUES ('$sellerloginid','$order_status','$order_id',0,0,'$create_time','$update_time','') ";
	                        $connection_db_queue2->createCommand( $insertSql2 )->execute();
                        }
                        catch(\Exception $ex){
                        	echo "update aliexpress_not_push_order err ".$ex->getMessage().", sql: ".$insertSql2;
                        }
                    }
                }
            }else{
                echo "速卖通店铺-{$sellerloginid}不存在或者未启用".PHP_EOL;
            }
        }
        //end foreach

        echo 'all-end-',date("H:i:s"),PHP_EOL;
        return true;
    }
    //end function

    /**
     * 获取next_time这个字段,符合时间段要求的,会写会主表
     * @author akirametero
     */
    public static function getNextTimeList(){

        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/getNextTimeList",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressAutoOrderListVerion)) {
            self::$aliexpressAutoOrderListVerion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressAutoOrderListVerion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressAutoOrderListVerion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo 'begin';
        $return= false;
        $nowtime= time();
        $connection=Yii::$app->db_queue;
        //获取总数,分页处理,数据量可能过大
        $resf= $connection->createCommand("SELECT count(1) as num FROM queue_aliexpress_auto_order_handling WHERE next_time<={$nowtime}  ")->query()->readAll();
        $allnum= $resf[0]['num'];
        $allpage= ceil($allnum/100);
        echo 'allnum--'.$allnum;
        echo 'allpage--'.$allpage;

        for( $p=0;$p<$allpage;$p++ ){
            //双11控制预处理订单量
            $rs_asocount= $connection->createCommand("SELECT count(1) as num FROM queue_aliexpress_auto_order WHERE is_lock=0 ")->query()->readAll();
            $asc= $rs_asocount[0]['num'];
            if( $asc>1000 ){
                return $return;
            }

            $res= $connection->createCommand("SELECT * FROM queue_aliexpress_auto_order_handling WHERE next_time<={$nowtime}  ORDER by id ASC LIMIT 100 ")->query()->readAll();
            if( !empty( $res ) ){
                foreach( $res as $vs ){
                    $id= $vs['id'];
                    $sellerloginid= $vs['sellerloginid'];
                    $order_status= $vs['order_status'];
                    $order_id= $vs['order_id'];
                    $order_change_time= $vs['order_change_time'];
                    $msg_id= $vs['msg_id'];
                    $order_type= $vs['order_type'];
                    $gmtBorn= $vs['gmtBorn'];
                    $ajax_message= $vs['ajax_message'];
                    $status= $vs['status'];
                    $push_status= $vs['push_status'];
                    $error_message= $vs['error_message'];
                    $create_time= $vs['create_time'];
                    $update_time= $vs['update_time'];
                    $last_time= $vs['last_time'];
                    $next_time= $vs['next_time'];

                    $insertsql= "INSERT INTO queue_aliexpress_auto_order (`sellerloginid`,`order_status`,`order_id`,`order_change_time`,`msg_id`,`order_type`,`gmtBorn`,`ajax_message`,`status`,`push_status`,`error_message`,`create_time`,`update_time`,`last_time`,`next_time`) VALUES ('{$sellerloginid}','{$order_status}','{$order_id}','{$order_change_time}','{$msg_id}','{$order_type}','{$gmtBorn}','{$ajax_message}','{$status}','{$push_status}','{$error_message}','{$create_time}','{$update_time}','{$last_time}','{$next_time}' ) ";
                    echo $order_id,PHP_EOL;
                    $rv= $connection->createCommand( $insertsql )->execute();
                    //var_dump($rv);
                    $delsql= "DELETE FROM queue_aliexpress_auto_order_handling WHERE id={$id}";
                    $rb= $connection->createCommand( $delsql )->execute();
                    echo 'delete-handling-id-'.$id.PHP_EOL;
                    //var_dump($rb);
                }
            }else{
            }
            $return= true;
            //exit('aa');
        }

        echo 'end';
        return $return;
    }
    //end function


    /**
     * 获取并写入买家选择的快递
     * @author akirametero
     */
    public static function setBuyerServiceName(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/setBuyerServiceName",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressAutoOrderListVerion)) {
            self::$aliexpressAutoOrderListVerion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressAutoOrderListVerion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressAutoOrderListVerion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        $return= false;
        $db= Yii::$app->db;
        //获取所有用户
        $rs= $db->createCommand("SELECT uid,sellerloginid FROM saas_aliexpress_user WHERE is_active=1 ")->query();
        $rs_user= $rs->readAll();
        if( !empty( $rs_user ) ){
            foreach( $rs_user as $vs_user ){
                 
                $sellerloginid= $vs_user['sellerloginid'];
                $api = new AliexpressInterface_Api ();
                if (!AliexpressInterface_Auth::checkToken($sellerloginid)) {
                    echo '授权出问题了', $sellerloginid;
                    continue;
                }
                $access_token = $api->getAccessToken($sellerloginid);
                if ($access_token === false) {
                    echo '店铺sellerloginid-访问token失败-', $sellerloginid;
                    continue;
                }
                $api->access_token = $access_token;
                //获取用户所有订单addi_info is not null and
                $rd= Yii::$app->subdb->createCommand("SELECT order_source_order_id,order_id,order_source_status,order_source_create_time FROM od_order_v2 where ( addi_info not like '%shipping_service%' or addi_info is null ) AND  order_source ='aliexpress' and selleruserid ='{$sellerloginid}' AND ( order_source_status='WAIT_BUYER_ACCEPT_GOODS' OR order_source_status='FINISH' ) ")->query()->readAll();

                if( !empty( $rd ) ){
                    foreach( $rd as $vsd ){
                        $order_id= $vsd['order_source_order_id'];

                        //通过接口获取这个订单的信息
                        $paramx = array(
                            'page' => 1,
                            'pageSize' => 50,
                        );
                        //先试试1秒的误差
                        $start_time= $vsd['order_source_create_time'];
                        $end_time= $vsd['order_source_create_time'];
                        $orderStatus= $vsd['order_source_status'];

                        $paramx['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                        $paramx['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                        $paramx['orderStatus']= $orderStatus;

                        $api_time= time();//接口调用时间
                        $result2 = $api->findOrderListQuery($paramx);
                        if( isset( $result2['totalItem'] ) && isset( $result2['orderList'] ) ) {
                            if (!empty($result2['orderList'])) {
                                //买家指定的物流服务
                                $logisticsServiceName_arr = array();

                                foreach ($result2 ['orderList'] as $ordervs) {
                                    //循环找到对应的orderid的数组
                                    if ($ordervs['orderId'] == $order_id) {
                                        if (isset($ordervs['productList'])) {
                                            foreach ($ordervs['productList'] as $pl) {
                                                if (isset($pl['logisticsServiceName'])) {
                                                    $logisticsServiceName = $pl['logisticsServiceName'];
                                                    $productid = $pl['productId'];
                                                    $logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
                                                }


                                            }
                                        }

                                        break;
                                    } else {
                                    }
                                }
                                //end foreach
                                //update 买家指定的物流服务商
                                if (!empty($logisticsServiceName_arr)) {
                                    $return= true;
                                    list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $order_id, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
                                    if( $order_status===false ){
                                        echo $order_id.'可选物流更新失败--'.$msg.PHP_EOL;
                                    }
                                }
                            }
                        }

                    }
                }else{

                }

            }
            //end foreach
        }
        //end if
        return $return;

    }
    //end function

    /**
     * 自动检查auto表中的存货有多少
     * akirametero
     */
    public static function checkAutoOrderNum(){
        $connection= Yii::$app->db_queue;
        $checkSuccess= true;
        $sql= "SELECT COUNT(1) AS num FROM queue_aliexpress_auto_order WHERE is_lock=0 ";
        $res= $connection->createCommand($sql)->query()->readAll();
        $allnum= $res[0]['num'];
        $rtnMsg= 'queue_aliexpress_auto_order中存在未处理订单数据量:'.$allnum;
        return array($checkSuccess,$rtnMsg);
    }
    //end function
    

    /**
     * 定时清理 表
     * akirametero
     */
    public static function delQueueTable(){
        $connection= Yii::$app->db_queue;

        $arr= array(

            'queue_aliexpress_getorder4'=>'gmtcreate',
            'queue_aliexpress_getorder2'=>'create_time',
            'queue_aliexpress_auto_order'=>'create_time',

        );
        //有效时间一月内
        $wheredatetime= time()-86400*31*3;
        //循环查询第一条数据的对应日期值，否则符合设定
        foreach( $arr as $key=>$vs ){
            $rs= $connection->createCommand( "select id,{$vs} from {$key} where id<((select id from {$key} order by id asc limit 1)+100) order by id desc limit 1;" )->query()->read();
            if( !empty( $rs ) ){
                if( $key=="queue_aliexpress_auto_order" ){
                    $time= strtotime($rs[$vs]);
                }else{
                    $time= $rs[$vs];
                }
                if( $time<$wheredatetime ){
                    $del_id= $rs['id'];
                    $sql= "delete from {$key} where id<={$del_id}";
                    $exe= $connection->createCommand($sql)->execute();
                    echo $sql.PHP_EOL;
                    self::delQueueTable();
                }else{
                    continue;
                }
            }
        }
        echo 'ok';
    }
    //end function
    
    /**
     * 检查自动同步订单是否有误
     * akirametero
     */
    public static function checkSyncOrderInfo(){
    	$msg = "Warning: ".PHP_EOL;
    	$isCheckSuccess = true;
    	//正在同步订单的账号数
    	$sync_count = SaasAliexpressAutosyncV2::find()->where("status=1 and type='time'")->count();
    	//订单同步失败账号数
    	$sync_err_count = SaasAliexpressAutosyncV2::find()->where("times>0 and type='time'")->count();
    	//长时间处于订单同步账号数
    	$sync_long_time_count = SaasAliexpressAutosyncV2::find()->where("status=1 and type='time' and last_time<".(time() - 1800))->count();
    	//长时间不订单同步账号数
    	$not_sync_long_time_count = SaasAliexpressAutosyncV2::find()->where("times<10 and type='time' and next_time<".(time() - 3600))->count();
    	//积压需同步订单详情数
    	$get_order_count = QueueAliexpressGetorderV2::find()->count();
    	//正在同步订单详情数
    	$get_order_sync_count = QueueAliexpressGetorderV2::find()->where("status=1")->count();
    	//同步订单详情失败数
    	$get_order_err_count = QueueAliexpressGetorderV2::find()->where("times>0")->count();
    	//长时间处于同步订单详情数
    	$get_order_long_time_count = QueueAliexpressGetorderV2::find()->where("status=1 and update_time<".(time() - 3600))->count();
    	//长时间不同步订单详情数
    	$not_get_order_long_time_count = QueueAliexpressGetorderV2::find()->where("times<10 and update_time<".(time() - 7200))->count();
    	
    	if($sync_count > 5){
    		$msg .= '  当前有'.$sync_count.'个账号正在拉取，请留意拉取Job的情况;'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($sync_err_count > 0){
    		$msg .= '  当前有'.$sync_err_count.'个账号订单拉取失败,请检查失败原因;'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($sync_long_time_count > 0){
    		$msg .= '  当前有'.$sync_long_time_count.'个账号长时间处于同步中，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($not_sync_long_time_count > 0){
    		$msg .= '  当前有'.$not_sync_long_time_count.'个账号长时间不同步订单，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($get_order_count > 5000){
    		$msg .= '  当前有'.$get_order_count.'个订单拉取详情，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($get_order_sync_count > 10){
    		$msg .= '  当前有'.$get_order_sync_count.'个订单拉取详情，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($get_order_err_count > 0){
    		$msg .= '  当前有'.$get_order_err_count.'个订单拉取失败,请检查失败原因;'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($get_order_long_time_count > 0){
    		$msg .= '  当前有'.$get_order_long_time_count.'个订单长时间处于同步中，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	if($not_get_order_long_time_count > 0){
    		$msg .= '  当前有'.$not_get_order_long_time_count.'个订单长时间不同步，请留意拉取Job的情况；'. PHP_EOL;
    		$isCheckSuccess = false;
    	}
    	
    	return [$isCheckSuccess, $msg];
    }
    //end function
    
    /**
     * 每天重置同步失败的订单
     * akirametero
     */
    public static function RefreshAliexpressAutosync(){
        $time= time();
        $connection= $db= Yii::$app->db;
        $sql= "UPDATE saas_aliexpress_autosync_v2 SET `times`=0,`message`='',`next_time`={$time} WHERE `type`='time' AND `status`=3 AND  `times`=10 ";
        $connection->createCommand($sql)->execute();

        $sql= "UPDATE saas_aliexpress_autosync_v2 SET `times`=0,`message`='' WHERE `type`!='time' AND `status`=3 AND  `times`=10 ";
        $connection->createCommand($sql)->execute();
        
        return true;
    }
    //end function


}
