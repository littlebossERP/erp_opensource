<?php
namespace eagle\modules\listing\helpers;
use common\helpers\Helper_Array;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use eagle\models\SaasEbayVip;
use eagle\modules\listing\models\EbayAutoInventory;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayItemVariationMap;
use yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
/**
* 
*/
class EbayAutoInventoryCtrlHelper{
	function __construct(){
	}

    /**
     * [getCreateItem 获取item列表]
     * @author willage 2017-03-24T10:04:31+0800
     * @update willage 2017-03-24T10:04:31+0800
     */
    public static function getCreateItem($sellerArry,$params=NULL){
        /**
         * No.1-搜索提取item
         */
        list($data,$queIvyVars)=self::_searchCreateItem($params,$sellerArry);
        //配置page
        // $pageSize=isset($params['per-page'])?$params['per-page']:'50';
        // $pages = new Pagination([
        //     'defaultPageSize' => 20,
        //     'pageSize' => $pageSize,
        //     'totalCount' => $data->count(),
        //     'pageSizeLimit'=>[5,100],//每页显示条数范围
        //     // 'params'=>$params,
        //     ]);
        // //获取item数组
        // $models = $data->offset($pages->offset)
        //         ->limit($pages->limit)
        //         ->asArray()
        //         ->all();
        /**
         * No.2-提取item detail
         */
        // $itemArry=ArrayHelper::getColumn($models, 'itemid');

        /**
         * No.2-配置page
         */
        $pageSize=isset($params['per-page'])?$params['per-page']:'50';
        $pages = new Pagination([
            'defaultPageSize' => 20,
            'pageSize' => $pageSize,
            'totalCount' => $data->count(),
            'pageSizeLimit'=>[5,100],//每页显示条数范围
            // 'params'=>$params,
            ]);

        $models = $data->offset($pages->offset)
                ->limit($pages->limit)
                ->asArray()
                ->all();
        /**
         * No.3-提取含有多属性的item detail
         */
        $itemArry=ArrayHelper::getColumn($models, 'itemid');
        // $isvar=ArrayHelper::getColumn($models, 'isvariation');
        $details=self::_searchCreateDetail($sellerArry,$itemArry,$queIvyVars);

        return [$models,$details,$pages];
    }
    /**
     * [_searchCreateItem item筛选]
     * @author willage 2017-03-24T10:01:15+0800
     * @update willage 2017-03-24T10:01:15+0800
     */
    public static function _searchCreateItem($params,$sellerArry){
        /**
         * No.1-提取非多属性补货item
         */
        $queIvy=EbayAutoInventory::find()
            ->where(['selleruserid'=>$sellerArry])
            ->andwhere('var_specifics IS NULL')
            ->select('itemid')
            ->asArray()
            ->all();
        $queIvyVar=self::_removedSavedInventory($sellerArry);
        /**
         * No.2-固定筛选
         */
        $data= EbayItem::find()
            ->where(['selleruserid'=>$sellerArry])//账号筛选(解绑不显示)
            ->andwhere(['listingstatus'=>'Active'])//在线产品
            ->andwhere('listingtype != "Chinese"')//非拍卖产品
            ->andwhere(['not in','itemid',$queIvy])//剔除非多属性,已设置补货item
            ->andwhere(['not in','itemid',$queIvyVar]);
        /**
         * No.3-参数搜索
         */
        if (!empty($params['itemtitle'])){//标题搜索
            $data->andWhere('itemtitle like :t',[':t'=>'%'.$params['itemtitle'].'%']);
        }
        if (!empty($params['itemid'])){//itemid搜索
            $data->andWhere(['itemid'=>$params['itemid']]);
        }
        if (!empty($params['selleruserid'])){//sellerid搜索
            $data->andWhere(['selleruserid'=>$params['selleruserid']]);
        }
        if (!empty($params['listingtype'])){//类型搜索
            if ($params['listingtype']=='FixedPriceItem') {
                $data->andWhere(['isvariation'=>0]);
            }else if ($params['listingtype']=='IsVariation') {
                $data->andWhere(['isvariation'=>1]);
            }
        }
        if (!empty($params['site'])){//站点搜索
            $data->andWhere(['site'=>$params['site']]);
        }
        if (!empty($params['sku'])){//sku搜索
            $itemid = EbayItemVariationMap::find()->where(['like','sku',$params['sku']])->select('itemid')->asArray()->all();
            $itemid = Helper_Array::getCols($itemid, 'itemid');
            $data->andWhere(['itemid'=>$itemid]);
        }
        if (isset($params['hassold'])&&$params['hassold']!=''){//已售出搜索
            if ($params['hassold'] == '0'){
                $data->andWhere('quantitysold=0');
            }else{
                $data->andWhere('quantitysold>0');
            }
        }
        if (isset($params['outofstockcontrol'])&&$params['outofstockcontrol']!=''){//永久在线搜索
            if ($params['outofstockcontrol'] == '0'){
                $data->andWhere(['outofstockcontrol'=>0]);
            }else{
                $data->andWhere(['outofstockcontrol'=>1]);
            }
        }
        /**
         * 排序
         */
        $data->orderBy('id DESC');
        // if(isset($params['xu']) && strlen($params['xu'])){//排序
        //     if ($params['xu'] == 'price') $sortname = 'currentprice';
        //     if ($params['xu'] == 'quantity') $sortname = 'quantitysold';
        //     $data->orderBy($sortname.' '.$params['xusort']);
        // }
        return [$data,$queIvyVar];
    }

    /**
     * [_eraseVariation description]
     * @author willage 2017-03-24T10:00:51+0800
     * @update willage 2017-03-24T10:00:51+0800
     */
    public static function _searchCreateDetail($sellerArry,$itemArry,$queIvyVars){
        /**
         * No.1-获取inventory多属性记录
         */
        $queIvy=EbayAutoInventory::find()
            ->where(['selleruserid'=>$sellerArry])
            ->andwhere(['not in','itemid',$queIvyVars])
            ->andwhere('var_specifics IS NOT NULL')
            ->andwhere('status!=2')
            ->select('itemid,var_specifics')
            ->asArray()
            ->all();

        /**
         * No.2-获取detail(多属性)
         */
        $details=EbayItemDetail::find()
            ->where(['itemid'=>$itemArry])
            ->andwhere('variation IS NOT NULL')
            ->select('itemid,variation')
            ->asArray()
            ->all();

        /**
         * No.3-剔除已存在的记录
         */
        foreach ($details as $key=>$val) {//解析数据
            $tmp=unserialize($val['variation']);
            if (!isset($val['variation'][0])) {
                $details[$key]['variation']=array($tmp['Variation']);
            }else{
                $details[$key]['variation'] = $tmp['Variation'];
            }
        }
        foreach ($queIvy as $qkey => $qval) {//剔除数据
            $checked=false;
            foreach ($details as $dkey =>$dval) {
                if ($qval['itemid']==$dval['itemid']) {
                    foreach ($dval['variation'] as $key => $value) {
                        $tmp=json_encode($value['VariationSpecifics']['NameValueList']);
                        if (strcmp($qval['var_specifics'],$tmp)==0) {//匹配到该多属性产品
                            ArrayHelper::remove($details[$dkey]['variation'], $key);
                            $checked=true;
                            break;
                        }
                    }
                    if ($checked) {
                        break;
                    }
                }
            }
        }

        return $details;
    }

    public static function _removedSavedInventory($sellerArry){
        /*
         * No.1-提取多属性的Inventory记录
         */
        $queIvy=EbayAutoInventory::find()
            ->where(['selleruserid'=>$sellerArry])
            ->andwhere('var_specifics IS NOT NULL')
            ->andwhere('status!=2')
            ->select('itemid,var_specifics')
            ->orderBy('itemid DESC')
            ->asArray()
            ->all();
        $itemArry=ArrayHelper::getColumn($queIvy, 'itemid');
        $details=EbayItemDetail::find()
            ->where(['itemid'=>$itemArry])
            ->andwhere('variation IS NOT NULL')
            ->select('itemid,variation')
            ->orderBy('itemid DESC')
            ->asArray()
            ->all();
        // $result = ArrayHelper::map($queIvy, 'itemid','var_specifics');
        // \yii::info($itemArry,"file");
        // \yii::info($details,"file");

        // \yii::info($result,"file");
        $itemIdArry = array();
        foreach ($details as $dkey => $dval) {//按itemdetail遍历
            \yii::info($dval,"file");
            $tmp=unserialize($dval['variation']);
            $dval['variation'] = $tmp['Variation'];
            $checkCnt=0;
            if (!isset($dval['variation'][0])) {
                $dval['variation'] = array($dval['variation']);
            }
            foreach ($queIvy as $qkey => $qval) {
                if ($qval['itemid']==$dval['itemid']) {
                    $checkCnt++;
                }
            }
            // foreach ($dval['variation'] as $vkey => $vval) {//按variation遍历
            //     foreach ($queIvy as $qkey => $qval) {//匹配遍历inventory
            //         if ($qval['itemid']==$dval['itemid']) {
            //             $tmp=json_encode($vval['VariationSpecifics']['NameValueList']);
            //             if (strcmp($qval['var_specifics'],$tmp)==0) {
            //                 $checkCnt++;
            //             }
            //         }
            //     }
            // }

            if(count($dval['variation'])<=$checkCnt){
                \yii::info("count:".count($dval['variation']),"file");
                $itemIdArry[]=$dval['itemid'];
                // $itemIdArry[]=$dval['itemid'];
            }
        }


        \yii::info($itemIdArry,"file");
        // $queIvy=ArrayHelper::merge($queIvy,$itemIdArry);
        // \yii::info($queIvy,"file");
        return $itemIdArry;
    }

    /**
     * [creatRecord 新记录创建]
     * @author willage 2017-03-24T10:00:14+0800
     * @update willage 2017-03-24T10:00:14+0800
     */
    public static function creatRecord($params,$puid)
    {
        /**
         * No.1-数量限制检查
         */
        if (!self::_limitCheckV2($params['seller_id'],$puid,count($params['inventory']))) {
            return [false,"自动补库超限"];
        }

        $itemid=$params['item_id'];
        $varSpecArr=$params['varisation'];
        $invArr=$params['inventory'];
        $aInv=EbayAutoInventory::find()
                        ->where(['itemid'=>$itemid])
                        ->andwhere(['var_specifics'=>$varSpecArr])
                        // ->createCommand()->getRawSql();
                        ->all();
        if (!empty($aInv)) {
            $log=EbayAutoInventory::find()
                ->where(['itemid'=>$itemid])
                ->andwhere(['var_specifics'=>$varSpecArr])
                ->createCommand()->getRawSql();
            \yii::info("creatRecord SQL:".$log,"file");
            return [false,"该item或该item属性自动补库已被创建"];
        }
        $array=SaasEbayUser::find()->where(['selleruserid'=>$params['seller_id']])->select('selleruserid,ebay_uid')->asArray()->all();
        $sellerEuidArry = ArrayHelper::map($array, 'selleruserid', 'ebay_uid');
        \yii::info($sellerEuidArry,"file");
        /**
         * No.2-item-variation检查
         */
        //find item
        $item = EbayItem::findOne(['itemid'=>$itemid]);
        if (empty($item)) {
            // \yii::info($aInv,"file");
            return [false,"该 item 不存在"];
        }
        //find variation
        $variation=EbayItemDetail::find()->where(['itemid'=>$itemid])->select('variation')->asArray()->one();
        if (!empty($variation['variation'])) {
            $variation=unserialize($variation['variation']);
            $variation=$variation['Variation'];
            if (!isset($variation[0])) {
                $variation = array($variation);
            }
            $quantityArr=array();
            $skuArr=array();
            foreach ($varSpecArr as $key => $value) {
                $check=false;
                foreach ($variation as $vkey => $vval) {
                    $tmp=json_encode($vval['VariationSpecifics']['NameValueList']);
                    if (strcmp($value,$tmp)==0) {//匹配到该多属性产品
                        $check=true;
                        if (isset($vval['SellingStatus']['QuantitySold'])) {
                            $quantityArr[]=$vval['Quantity']-$vval['SellingStatus']['QuantitySold'];
                        }else{
                            $quantityArr[]=$vval['Quantity'];
                        }
                        if (isset($vval['SKU'])) {
                            $skuArr[]=$vval['SKU'];
                        }else{
                            $skuArr[]=NULL;
                        }
                        break;
                    }
                }
                if (!$check) {
                    return [false,"该属性不存在：".$value];
                }
            }
        }
        /**
         * No.3-记录保存
         */
        $batchInsertArr=array();
        if (!$item->isvariation) {
            $batchInsertArr[]=array(
                'selleruserid' => $item->selleruserid,
                'draft_id' => NULL,
                'itemid' => $itemid,
                'sku' => $item->sku,
                'item_type'=>0,
                'var_specifics' => NULL,
                'online_quantity' => $item->quantity-$item->quantitysold,
                'status' => 1,
                'status_process' => 0,
                'type' => 'always',
                'less_than_equal_to' => NULL,
                'inventory' => $invArr[0],
                'success_cnt' => 0,
                'ebay_uid' => $sellerEuidArry[$item->selleruserid],
                'puid'=>$puid,
                'created' => time(),
                'updated' => time(),
            );
        }else{
            foreach ($varSpecArr as $key => $val) {
                $batchInsertArr[]=array(
                    'selleruserid' => $item->selleruserid,
                    'draft_id' => NULL,
                    'itemid' => $itemid,
                    'sku' => @$skuArr[$key],
                    'item_type'=>is_null($skuArr[$key])?1:0,
                    'var_specifics' => $val,
                    'online_quantity' => $quantityArr[$key],
                    'status' => 1,
                    'status_process' => 0,
                    'type' => 'always',
                    'less_than_equal_to' => NULL,
                    'inventory' => $invArr[$key],
                    'success_cnt' => 0,
                    'ebay_uid' => $sellerEuidArry[$item->selleruserid],
                    'puid'=>$puid,
                    'created' => time(),
                    'updated' => time(),
                );
            }
        }



        \yii::info($batchInsertArr,"file");
        //批量插入(注意数据组织时,$columnArr和$batchInsertArr对应)
        $dbase=\Yii::$app->db;
        $columnArr=array(
            'selleruserid',
            'draft_id',
            'itemid',
            'sku',
            'item_type',
            'var_specifics',
            'online_quantity',
            'status',
            'status_process',
            'type',
            'less_than_equal_to',
            'inventory',
            'success_cnt',
            'ebay_uid',
            'puid',
            'created',
            'updated',
        );
        $dbase->createCommand()->batchInsert("ebay_auto_inventory", $columnArr, $batchInsertArr)->execute();
        /**
         * No.4-SaasEbayAutosyncstatus设置
         */
        if (!self::_switchSaasStatus($params['seller_id'])) {
            return [false,"设置错误"];
        }

        return [true,"创建成功"];
    }
    /**
     * [_switchSaasStatus description]
     * @author willage 2017-03-31T09:17:49+0800
     * @update willage 2017-03-31T09:17:49+0800
     * 凡是EbayAutoInventory对应sellerid有记录(开启),
     * 则SaasEbayAutosyncstatus保持next_execute_time为当前时间,
     * 否则SaasEbayAutosyncstatus保持next_execute_time=NULL,
     */
    public static function _switchSaasStatus($sellerid){
        $isOpen=EbayAutoInventory::find()
            ->where(['selleruserid'=>$sellerid])
            ->andwhere(['status'=>1])
            ->count();

        $autoSyncS=SaasEbayAutosyncstatus::find()
                        ->where(['selleruserid'=>$sellerid])
                        ->andwhere(['in','type',[9,10]])
                        ->all();
        if (empty($autoSyncS)) {
            \yii::info("_switchSaasStatus error","file");
            return false;
        }

        foreach ($autoSyncS as $key => $val) {
            if ($isOpen) {//有记录,开启
                if(is_null($val->next_execute_time)){//如果之前已经开启,则不变
                    $val->next_execute_time=time();
                    $val->updated=time();
                    $val->save(false);
                }
            }else{//无记录,关闭
                if(!is_null($val->next_execute_time)){//如果之前已经是NULL,则不变
                    $val->next_execute_time=NULL;
                    $val->updated=time();
                    $val->save(false);
                }
            }
        }
        return true;
    }
    /**
     * [authorCheck 检查店铺的权限]
     * @author willage 2017-03-23T16:48:16+0800
     * @update willage 2017-03-23T16:48:16+0800
     */
    public static function _authorCheck($sellerid,$puid){
        $vip=0;
        $isVip=SaasEbayVip::find()
                ->where(['selleruserid'=>$sellerid])
                ->andwhere(['vip_type'=>'inventory'])
                ->andwhere(['vip_status'=>1])
                ->count();
        if (!$isVip) {//非VIP
            $vip=0;
            return $vip;
        }
        return $vip;
    }
    /**
     * [_limitCheck 检测店铺的数量限制]
     * @author willage 2017-03-23T18:07:19+0800
     * @update willage 2017-03-23T18:07:19+0800
     */
    public static function _limitCheck($sellerid,$puid,$appCnt){
        return true;

    }

    /**
     * [_limitCheckV2 使用公共模板做前置检查]
     * @author willage 2017-08-14T14:02:22+0800
     * @update willage 2017-08-14T14:02:22+0800
     */
    public static function _limitCheckV2($sellerid,$puid,$appCnt){
        return true;
    }
    /**
     * [_limitExceededHandle 超限处理]
     * @author willage 2017-08-14T15:38:59+0800
     * @update willage 2017-08-14T15:38:59+0800
     */
    public static function _limitExceededHandle($sellerid,$puid,$cnt){
        $exceeds=EbayAutoInventory::find()
                ->where(['puid'=>$puid])
                ->andwhere(['status'=>1])
                ->limit($cnt)
                ->orderBy('id DESC');
        foreach ($exceeds->each() as $value) {
            $value->status=0;
            $value->save(false);
            self::_switchSaasStatus($sellerid);
            \yii::info("pause invent id : ".$value->id,"file");
        }
        \yii::info($exceeds->createCommand()->getRawSql(),"file");
        \yii::info($exceeds->count(),"file");
    }


}//end class
?>