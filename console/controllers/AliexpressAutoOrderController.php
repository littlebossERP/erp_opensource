<?php

namespace console\controllers;
use Yii;
use yii\console\Controller;
use eagle\models\QueueAliexpressGetorder;
use yii\base\Exception;
use eagle\models\SaasAliexpressUser;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\ConfigHelper;
use console\helpers\AliexpressHelperAutoOrder;
use eagle\modules\order\helpers\AliexpressOrderHelper;
use console\helpers\AliexpressHelper;
use eagle\modules\listing\helpers\AlipressApiHelper;
use eagle\modules\listing\helpers\AliexpressHelper as ListenAliexpressHelper;
/**
 * Aliexpress 后台脚本
 * @author million 88028624@qq.com
 * 2015-05-21
 */
class AliexpressAutoOrderController extends Controller {

	###################################################################################################################

    /**
     * 从queue_aliexpress_auto_order推送结果表中,处理几个状态的订单
     * @author akirametero
     */
    function actionGetFinishAliAutoOrder(){
        $startRunTime=time();
        do {
            $res= AliexpressHelperAutoOrder::getAliAutoOrder();
            if( $res===false ){
                sleep(10);
            }

        }while (time() < $startRunTime+3600);
    }
    ###################################################################################################################


    function actionTongBu( $uid,$sellid='',$t=30 ){


        AliexpressOrderHelper::getOrderListManualByUid($uid,$sellid,$t);

    }

    function actionTest(){
        $rsle= SaasAliexpressUser::find()->where(['is_active'=>1])->asArray()->all();
        foreach( $rsle as $v ){
            $sellid= $v['sellerloginid'];
            
            AliexpressOrderHelper::getOrderListManualByUid($v['uid'],$sellid,2);
        }

        //$result= AliexpressHelperAutoOrder::getApiInfo();
        //print_r ($result);exit;
    }


    ###################################################################################################################
    function actionGetHandlingOrder(){
        $startRunTime=time();
        do {
            $res= AliexpressHelperAutoOrder::getNextTimeList();
            if( $res===false ){
                sleep(60);
            }

        }while (time() < $startRunTime+3600);
    }


    ###################################################################################################################
    /**
     * 自动写入订单中的买家选择的快递数据
     * @author akirametero
     */
    function actionSetBuyerServiceName(){
            $res= AliexpressHelperAutoOrder::setBuyerServiceName();

    }

    //end function
    ###################################################################################################################
    function actionTe(){
        AliexpressHelper::getListing('onSelling','last_time',0,'N','cn1500245082');
        sleep(60);
        AliexpressHelper::getListing('onSelling','last_time',0,'N','cn1500245082');
        //AliexpressHelper::getListingDetail( '5560','cn1511972691' );
    }

    /**
     *
     * 同步类目信息
     */
    function actionUpdateCate(){
        AlipressApiHelper::updateCateInfo();
        ListenAliexpressHelper::TestCategory();
    }
    //end function

    function actionAutoTest(){
        $res= AliexpressHelperAutoOrder::getAliAutoOrder();
    }

    function actionDelQueue(){
        AliexpressHelperAutoOrder::delQueueTable();
    }


    /**
     * 每天重置同步失败的订单
     * akirametero
     */
    function actionRefreshAliexpressAutosync(){
        
        AliexpressHelperAutoOrder::RefreshAliexpressAutosync();
    }
    //end function
}
