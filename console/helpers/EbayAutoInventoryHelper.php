<?php
namespace console\helpers;

use yii\helpers\ArrayHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayAutoInventory;
use common\api\ebayinterface\shopping\getmultipleitem;
use common\api\ebayinterface\reviseinventorystatus;
use common\api\ebayinterface\revisefixedpriceitem;
/**
 *----------------------------------------------------------------
 *<------------
 *|           |
 *|   3/4<----|
 *|     |     |
 *0---->----->1------>2(2:检查完成并且需要执行补货操作)
 *     |              |
 *     <----20<------10<---
 *                   |    |
 *                  30--->
 *----------------------------------------------------------------
 *   //状态机
 *   const CHECK_PENDING=0;
 *   const CHECK_RUNNING=1;
 *   const CHECK_EXECEPT=3;
 *   const CHECK_NOITEM=4;
 *   const INV_PENDING=2;
 *   const INV_RUNNING=10;
 *   const INV_FINISH=20;
 *   const INV_EXECEPT=30;
 *----------------------------------------------------------------
 */
class EbayAutoInventoryHelper{
    /**
     * [getEbayInventory 获取inventory库存]
     * @author willage 2017-03-14T10:43:16+0800
     * @editor willage 2017-03-14T10:43:16+0800
     * @param  [type]  $sellerId                [description]
     * @param  [type]  $process_type            [description]
     * @return [type]                           [description]
     */
    public static function getEbayInventory($sellerId,$process_type){
        switch ($process_type) {
            case 'check':
                $recondS=EbayAutoInventory::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$sellerId])
                    ->andwhere('status_process IN (0,3,4,20)')
                    ->orderBy('updated asc')
                    ->all();
                break;
            case 'inventory':
                $recondS=EbayAutoInventory::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$sellerId])
                    ->andwhere('status_process IN (2,30)')
                    ->andwhere('item_type=0')
                    ->andwhere('online_quantity!=0')
                    ->limit(4)//ReviseInventoryStatus api最多一次4件商品
                    ->orderBy('updated asc')
                    ->all();
                break;
            case 'varnosku':
                $recondS=EbayAutoInventory::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$sellerId])
                    ->andwhere('status_process IN (2,30)')
                    ->andwhere('item_type=1')
                    ->andwhere('online_quantity!=0')
                    ->orderBy('updated asc')
                    ->all();
                break;
            default:
                $recondS=NULL;
                break;
        }
        return $recondS;
    }
    /**
     * [reviseInventory 一般商品补库]
     * @author willage 2017-03-14T11:32:36+0800
     * @editor willage 2017-03-14T11:32:36+0800
     * @param  [type]  $invRecords              [description]
     * @return [type]                           [description]
     */
    public static function reviseInventory($invRecords)
    {
        /**
         * No.1-组织数据
         */
        $invArry = ArrayHelper::toArray($invRecords);
        $arrData=array();
        $sellerId=ArrayHelper::getValue($invArry, '0.selleruserid');
        $eu = SaasEbayUser::findOne(['selleruserid'=>$sellerId]);
        if (empty($eu)) {//获取saas user
            echo "No Saas user \n";
            return EbayAutoInventory::CODE_ERR_DB;
        }
        foreach ($invArry as $ikey => $ivalue) {
            $arrData[]=array(
                'ItemID'=>$ivalue['itemid'],
                'Quantity'=>$ivalue['inventory'],//Quantity可以直接是修改值,最终返回Quantity=修改值+sold
                'SKU'=>$ivalue['sku'],
            );
        }
        // echo print_r($invArry,false)."\n";
        echo print_r($arrData,false)."\n";
        /**
         * No.2-批量补库存
         */
        $api = new reviseinventorystatus();
        $api->resetConfig($eu->DevAcccountID);
        $api->eBayAuthToken = $eu->listing_token;
        $result = $api->batchApi($arrData);
        if (empty($result)||($api->responseIsFailure())|| !isset($result['InventoryStatus'])) {
            echo 'Failure : '.print_r($result,false)."\n";
            self::handleExecept($invRecords,EbayAutoInventory::CHECK_PENDING);
            return EbayAutoInventory::CODE_ERR_API;
        }
        /**
         * No.3-保存状态
         */
        try{
            foreach ($invRecords as $ikey => $ival) {
                $is_hasitemId=false;
                if (!isset($result['InventoryStatus'][0])) {
                    $result['InventoryStatus']=array(
                        $result['InventoryStatus']
                    );
                }
                foreach ($result['InventoryStatus'] as $rkey => $rval) {
                    $rItemId=$rval['ItemID'];
                    $rQuantity=$rval['Quantity'];
                    if ($ival->itemid==$rItemId) {
                        echo "--Inventory item:".$ival->itemid."-".$ival->sku."-".$ival->var_specifics." Q=".$ival->inventory."\n";
                        $is_hasitemId =true;
                        $ival->updated=time();
                        $ival->status_process=EbayAutoInventory::INV_FINISH;
                        //这里Quantity=修改的 + sold,不能用来表示在线数量
                        $ival->online_quantity=$ival->inventory;
                        $ival->success_cnt++;
                        $ival->save(false);
                        break;
                    }
                }
                if (!$is_hasitemId) {//响应没有该itemid,标记为EXECEPT
                    echo "--Inventory fail:".$ival->itemid."-".$ival->var_specifics."\n";
                    $ival->updated=time();
                    $ival->err_cnt++;
                    $ival->status_process=EbayAutoInventory::INV_EXECEPT;
                    $ival->save(false);
                }
            }
        }catch(Exception $e){
            echo $e->getMessage()."\n";
            return EbayAutoInventory::CODE_ERR_DB;
        }
        return EbayAutoInventory::CODE_SUCCESS;

    }
    /**
     * [reviseNoskuVarInventory no-sku多属性商品补库]
     * @author willage 2017-03-13T15:15:34+0800
     * @editor willage 2017-03-13T15:15:34+0800
     * @param  [type]  $invRecords              [description]
     * @return [type]                           [description]
     */
    public static function reviseNoskuVarInventory($invRecords){
        /**
         * No.1-组织数据
         */
        $invArry = ArrayHelper::toArray($invRecords);
        $sellerId=ArrayHelper::getValue($invArry, '0.selleruserid');
        $failCnt=0;
        $eu = SaasEbayUser::findOne(['selleruserid'=>$sellerId]);
        if (empty($eu)) {
            echo "No Saas user \n";
            return EbayAutoInventory::CODE_ERR_DB;
        }
        foreach ($invRecords as $ikey => $ivalue) {
            try{
                $param = array (
                    'Variations' => array (
                        'Variation' => array (
                            'Quantity' => $ivalue->inventory,
                            'VariationSpecifics'=>array(
                                'NameValueList' =>json_decode($ivalue->var_specifics,true),
                            )
                        )
                    )
                );
                print_r($param,false);
        /**
         * No.2-修改库存
         */
                $api = new revisefixedpriceitem();
                $api->resetConfig($eu->listing_devAccountID);
                $api->eBayAuthToken = $eu->listing_token;
                $result=$api->apiCore($ivalue->itemid,$param);
        /**
         * No.3-结果处理
         */
                if (empty($result)||$api->responseIsFailure()) {
                    print_r($result,false);
                    echo "--Failure Inventory no-sku-var:".$ivalue->itemid."-".$ivalue->var_specifics."\n";
                    $failCnt++;
                    $ivalue->updated=time();
                    $ivalue->status_process=EbayAutoInventory::INV_EXECEPT;
                    $ivalue->err_cnt++;
                    $ivalue->save(false);
                }else{
                    echo "--Success Inventory no-sku-var:".$ivalue->itemid."-".$ivalue->var_specifics." Q=".$ivalue->inventory."\n";
                    $ivalue->updated=time();
                    $ivalue->status_process=EbayAutoInventory::INV_FINISH;
                    //这里Quantity=修改的 + sold,不能用来表示在线数量
                    $ivalue->online_quantity=$ivalue->inventory;
                    $ivalue->success_cnt++;
                    $ivalue->save(false);
                }
            }catch(Exception $e){
                echo $e->getMessage()."\n";
                return EbayAutoInventory::CODE_ERR_DB;
            }
        }
        if ($failCnt) {
            return EbayAutoInventory::CODE_ERR_API;
        }else{
            return EbayAutoInventory::CODE_SUCCESS;
        }
    }
    /**
     * [checkQuantity 执行检查在线数量]
     * @author willage 2017-03-03T15:39:37+0800
     * @editor willage 2017-03-03T15:39:37+0800
     * @param  [type]  $invRecords              [description]
     * @return [type]                           [description]
     */
    public static function checkQuantity($invRecords)
    {
        $invItemCnt=0;
        /**
         * No.1-组织数据
         */
        $invArry = ArrayHelper::toArray($invRecords);
        $arrItemID=ArrayHelper::getColumn($invArry, 'itemid');
        print_r($arrItemID,false);
        /**
         * No.2-获取批量在线数量
         */
        $getAPI = new getmultipleitem();
        $getAPI->includeSelector='Details,Variations';
        $result=$getAPI->apiItem($arrItemID);
        if ((empty($result))||($getAPI->responseIsFail==true)) {
            echo "--CheckQuantity ERR_API\n";
            self::handleExecept($invRecords,EbayAutoInventory::CHECK_EXECEPT);
            return [EbayAutoInventory::CODE_ERR_API,$invItemCnt];
        }
        /**
         * No.3-保存inventory queue
         */
        try{
            foreach ($invRecords as $ikey => $ival) {
                $is_hasitem=false;
                $is_hasinv=false;
                foreach ($result as $rkey => $rvalue) {
                    //更新在线数量
                    list($is_hasitem,$is_hasinv) = self::handleCheckQuantity($ival,$rvalue);
                    if ($is_hasitem) {//匹配到item,就跑别的record
                        break;
                    }
                }
                if ($is_hasinv) {
                    $invItemCnt++;
                }
                if (!$is_hasitem) {//没有该item信息(或者没有该多属性信息)
                    echo "--Check no item:".$ival->itemid."-".$ival->var_specifics."\n";
                    $ival->updated=time();
                    $ival->status=EbayAutoInventory::STATUS_SUSPEND;
                    $ival->status_process=EbayAutoInventory::CHECK_NOITEM;
                    $ival->save(false);
                }
            }
        }catch(Exception $e){
            echo $e->getMessage()."\n";
            return [EbayAutoInventory::CODE_ERR_DB,$invItemCnt];
        }
        return [EbayAutoInventory::CODE_SUCCESS,$invItemCnt];
    }
    /**
     * [handleExecept 异常处理]
     * @author willage 2017-03-03T15:39:30+0800
     * @editor willage 2017-03-03T15:39:30+0800
     * TODO：根据result处理异常
     */
    public static function handleExecept($invObjs,$process){
        foreach ($invObjs as $ikey => $ivalue) {
            $ivalue->updated=time();
            $ivalue->status_process=$process;
            $ivalue->save(false);
        }
    }
    /**
     * [handleCheckQuantity description]
     * @author willage 2017-07-19T11:34:04+0800
     * @update willage 2017-07-19T11:34:04+0800
     * 由于返回数据，
     * 有时候是：[{"Name":"Style","Value":["Plum Blossom"]}]
     * 有时候是(这种的情况概率少些)：{"Name":"Style","Value":"Plum Blossom"}
     * 所以要匹配多种格式
     */
    public static function _handleNameValueListFormat($rc){
        $rcArr=json_decode($rc,true);
        if (isset($rcArr["Name"])) {
            $formatRc=[["Name"=>$rcArr["Name"],"Value"=>[$rcArr["Value"]]]];
            return json_encode($formatRc);
        }
        return $rc;

    }
    /**
     * [handleCheckQuantity 处理在线数量保存]
     * @author willage 2017-03-10T18:37:14+0800
     * @editor willage 2017-03-10T18:37:14+0800
     * @param  [type]  $invObj                  [description]
     * @param  [type]  $ritem                   [description]
     * @return [type]                           [description]
     */
    public static function handleCheckQuantity($invObj,$ritem){
        /**
         * No.1-匹配itemID,多属性要匹配-NameValueList
         */
        $is_hasitem=false;
        $is_hasInv=false;
        if ($invObj->itemid==$ritem['ItemID']) {//是否有该itemid
            if ($invObj->var_specifics==NULL) {//普通商品
                $rOnlineQuantity=$ritem['Quantity']-$ritem['QuantitySold'];
            }else{//various商品
                if (isset($ritem['Variations'])&&isset($ritem['Variations']['Variation'])) {
                    $varTmps=$ritem['Variations']['Variation'];
                    foreach ($varTmps as $vkey => $valTmp) {
                        $tmp=json_encode($valTmp['VariationSpecifics']['NameValueList']);

                        if (strcmp($invObj->var_specifics,$tmp)==0) {//匹配到该多属性产品
                            $rOnlineQuantity=$valTmp['Quantity']-$valTmp['SellingStatus']['QuantitySold'];
                            break;
                        }else{//换种格式再匹配
                            $newFormat=self::_handleNameValueListFormat($invObj->var_specifics);
                            echo "rt:".$tmp."\n";
                            echo "rc:".$invObj->var_specifics."\n";
                            echo "new:".$newFormat."\n";
                            if (strcmp($newFormat,$tmp)==0) {
                                $rOnlineQuantity=$valTmp['Quantity']-$valTmp['SellingStatus']['QuantitySold'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        /**
         * No.2-保存在线数量,并更新状态机
         */
        if (isset($rOnlineQuantity)) {//是否匹配到
            echo "--Check item:".$invObj->itemid."-".$invObj->var_specifics." online=".$rOnlineQuantity."\n";
            $is_hasitem=true;
            if ($rOnlineQuantity < $invObj->inventory) {//在线数量少于要补数量
                $is_hasInv=true;
                $invObj->updated=time();
                $invObj->status_process=EbayAutoInventory::INV_PENDING;
                $invObj->online_quantity=$rOnlineQuantity;
                $invObj->save(false);
            }else{
                $invObj->updated=time();
                $invObj->status_process=EbayAutoInventory::CHECK_PENDING;
                $invObj->online_quantity=$rOnlineQuantity;
                $invObj->save(false);
            }
        }
        return [$is_hasitem,$is_hasInv];
    }
}//end class