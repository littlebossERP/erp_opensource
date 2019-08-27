<?php
namespace eagle\modules\listing\helpers;
use yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayMuban;
use eagle\modules\listing\models\EbayMubanDetail;
use eagle\modules\listing\models\EbayAutoTimerListing;
use eagle\models\SaasEbayVip;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\EbaySite;
use common\helpers\Helper_Array;
use common\helpers\Helper_Util;
use common\api\ebayinterface\additem;
/**
* 
*/
class EbayAutoTimerListingCtrlHelper{
    function __construct(){
    }
    /**
     * [getCreateDraft 获取范本list]
     * @author willage 2017-04-06T14:38:27+0800
     * @update willage 2017-04-06T14:38:27+0800
     */
    public static function getDraftItem($sellerArry,$params=NULL){
        /**
         * No.1-搜索提取item
         */
        $data=self::_searchDraftItem($params,$sellerArry);
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
                ->all();
        return [$models,$pages];
    }


    /**
     * [_searchDraftItem 范本筛选]
     * @author willage 2017-03-24T10:01:15+0800
     * @update willage 2017-03-24T10:01:15+0800
     */
    public static function _searchDraftItem($params,$sellerArry){
        /**
         * No.1-提取定时刊登记录
         */
        $queTiming=EbayAutoTimerListing::find()
            ->where(['selleruserid'=>$sellerArry])
            ->select('draft_id')
            ->asArray()
            ->all();
        $draftArry=ArrayHelper::getColumn($queTiming, 'draft_id');

        /**
         * No.2-固定筛选
         */
        $data= EbayMuban::find()
            ->where(['selleruserid'=>$sellerArry])//账号筛选(解绑不显示)
            ->andwhere(['not in', 'mubanid', $draftArry]);//剔除已设置
        \yii::info($data->createCommand()->getRawSql(),"file");
        \yii::info($data->count(),"file");

        /**
         * No.3-参数搜索
         */
        if (!empty($params['itemtitle'])){//标题搜索
            $data->andWhere('itemtitle like :t',[':t'=>'%'.$params['itemtitle'].'%']);
        }

        if (!empty($params['selleruserid'])){//sellerid搜索
            $data->andWhere(['selleruserid'=>$params['selleruserid']]);
        }
        if (!empty($params['paypal'])){
            $data->andWhere('paypal = :t',[':t'=>$params['paypal']]);
        }
        if (!empty($params['listingtype'])){//类型搜索
            if ($params['listingtype']=='FixedPriceItem') {
                $data->andWhere(['isvariation'=>0]);
            }else if ($params['listingtype']=='IsVariation') {
                $data->andWhere(['isvariation'=>1]);
            }else if ($params['listingtype']=='Chinese') {
                 $data->andWhere('listingtype = :t',[':t'=>'Chinese']);
            }
        }
        if ( isset($params['siteid']) && ($params['siteid']!='')){//站点搜索
            $data->andWhere(['siteid'=>$params['siteid']]);
        }
        if (!empty($params['sku'])){//sku搜索
            $data->andWhere('sku = :t',[':t'=>$params['sku']]);
        }
        if ( isset($params['outofstockcontrol']) && ($params['outofstockcontrol']!='')){//永久在线搜索
            $data->andWhere('outofstockcontrol = :t',[':t'=>$params['outofstockcontrol']]);
        }
        /**
         * 排序
         */
        $data->orderBy('mubanid desc');
        // if(isset($params['xu']) && strlen($params['xu'])){//排序
        //     if ($params['xu'] == 'price') $sortname = 'currentprice';
        //     if ($params['xu'] == 'quantity') $sortname = 'quantitysold';
        //     $data->orderBy($sortname.' '.$params['xusort']);
        // }
        //\yii::info($data,"file");
        return $data;
    }


    public static function varifyDraftItem($draft_id){
        /**
         * [范本数据提取]
         */
        \yii::info('范本数据提取',"file");
        $data=EbayMuban::findOne($draft_id)->attributes;
        $detaildata=EbayMubanDetail::findOne($draft_id)->attributes;
        $data=array_merge($data,$detaildata);
        $values=additem::valueMergeWithDefault($data);
        $siteid=$values['siteid'];
        $es=EbaySite::findOne(['siteid'=>$siteid]);
        $values['site']=$es->site;
        $values['paypal']=trim($values['paypal']);
        $seu=SaasEbayUser::findOne(['selleruserid'=>$values['selleruserid']]);
        /**
         * [刊登检测数据准备]
         */
        \yii::info('刊登检测数据准备',"file");
        $eiai=new additem();
        $eiai->isVerify(true);
        if (isset($values['uuid'])){
            $uuid=$values['uuid'];
        }else {
            $uuid=$values['uuid']=Helper_Util::getLongUuid();
        }
        $eiai->siteID = $siteid;
        $eiai->resetConfig($seu->listing_devAccountID);
        $eiai->eBayAuthToken = $seu->listing_token;
        $eiai->setValues ( $values );

        $uid = \Yii::$app->user->identity->getParentUid() == 0 ? \Yii::$app->user->id : \Yii::$app->user->identity->getParentUid();
        $result = $eiai->api ( $data, $uid, 0, $seu->selleruserid ,$uuid);
        /**
         * [刊登检测结果处理]
         */
        \yii::info('刊登检测结果处理',"file");
        if(isset($result['Errors'])){
            $errors = array();
            if(!isset($result['Errors'][0])){
                $errors[0]=$result['Errors'];
            }else{
                $errors=$result['Errors'];
            }
        }
        $show = '';
        if(isset($result['Fees'])){
            $fee=Helper_Array::toHashmap($result['Fees']['Fee'],'Name','Fee');
            foreach ($fee as $k=>$f){
                if ($f>0){
                    $show.=$k.':'.$f.' '.$result['ebayfee']['currency'].'<br>';
                }
            }
        }
        if(isset($result['Errors'])){
            foreach ($errors as $error){
                $show.=$error['ShortMessage'].'<br>';
                $show.=Html::encode($error['LongMessage']).'<br>';
            }
        }
        $result['show']=$show;
        \Yii::info($result,"file");
        return $result;
        // exit(Json::encode($result));
    }

    public static function createRecord($params,$puid,$verfy=NULL){
        /**
         * [创建EbayAutoTimerListing记录]
         */
        $user=SaasEbayUser::find()->where(['selleruserid'=>$params['EbayAutoTimerListing']['selleruserid']])->one();
        \yii::info($user,"file");
        $record=new EbayAutoTimerListing();
        // $record->id
        $record->draft_id=$params['EbayAutoTimerListing']['draft_id'];
        $record->selleruserid=$params['EbayAutoTimerListing']['selleruserid'];
        // $record->itemid
        // $record->itemtitle=
        $record->status=$params['EbayAutoTimerListing']['status'];
        $record->status_process=0;
        // $record->runtime
        $record->set_gmt=$params['EbayAutoTimerListing']['set_gmt'];
        $record->set_date=$params['EbayAutoTimerListing']['set_date'];
        $record->set_hour=$params['EbayAutoTimerListing']['set_hour'];
        $record->set_min=$params['EbayAutoTimerListing']['set_min'];
        $record->verify_result=is_null($verfy)?$verfy:json_encode($verfy);
        $record->err_cnt=0;
        $record->ebay_uid=$user->ebay_uid;
        $record->puid=$puid;
        $record->created=time();
        $record->updated=time();
        $record->save(false);
        /**
         * [开启SaasEbayAutosyncstatus任务状态(next_execute_time设置)]
         */
        $autoSync=SaasEbayAutosyncstatus::find()
                            ->where(['selleruserid'=>$params['EbayAutoTimerListing']['selleruserid']])
                            ->andwhere(['type'=>11])
                            ->andwhere(['next_execute_time'=>NULL])
                            ->one();
        if (!empty($autoSync)) {//如果之前已经开启,则不变
            $autoSync->next_execute_time=time();
            $autoSync->updated=time();
            $autoSync->save(false);
        }

    }

    /**
     * [updateRecord description]
     * @author willage 2017-04-12T17:17:52+0800
     * @update willage 2017-04-12T17:17:52+0800
     */
    public static function updateRecord($params,$puid,$model){
        /**
         * [参数检查、数量检查]
         */
        list($ret,$mesg)=EbayCommonFrontHelper::paramsCheck($params,'timer_listing',$params['EbayAutoTimerListing']['selleruserid'],$puid,1);
        if (!$ret) {
           return [201,$mesg];
        }
        /**
         * [记录修改]
         */
        $model->status=$params['EbayAutoTimerListing']['status'];
        $model->set_gmt=$params['EbayAutoTimerListing']['set_gmt'];
        $model->set_date=$params['EbayAutoTimerListing']['set_date'];
        $model->set_hour=$params['EbayAutoTimerListing']['set_hour'];
        $model->set_min=$params['EbayAutoTimerListing']['set_min'];
        $model->updated=time();
        $model->save(false);
        /**
         * [saas任务设置修改]
         */
        EbayCommonFrontHelper::_switchSaasStatus($params['EbayAutoTimerListing']['selleruserid'],"timer_listing");

        return [200,"修改成功 !"];

    }
}//end class