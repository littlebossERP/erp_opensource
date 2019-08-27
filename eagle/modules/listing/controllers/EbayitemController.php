<?php

namespace eagle\modules\listing\controllers;

use eagle\modules\listing\models\EbayItem;
use yii\data\Pagination;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayLogItem;
use common\api\ebayinterface\relistitem;
use common\helpers\Helper_Siteinfo;
use common\api\ebayinterface\getitem;
use eagle\modules\listing\models\EbayItemDetail;
use common\api\ebayinterface\enditem;
use common\api\ebayinterface\shopping\getsingleitem;
use eagle\models\EbaySite;
use eagle\models\EbayCountry;
use common\helpers\Helper_Array;
use eagle\models\EbayCategoryfeature;
use common\api\ebayinterface\getcategoryfeatures;
use common\api\ebayinterface\base;
use eagle\models\EbaySpecific;
use eagle\models\EbayShippingservice;
use eagle\modules\listing\models\EbayMuban;
use eagle\models\EbayShippinglocation;
use common\api\ebayinterface\reviseitem;
use common\api\ebayinterface\UploadSiteHostedPictures;
use yii\helpers\Json;
use common\api\ebayinterface\reviseinventorystatus;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use common\api\ebayinterface\getsellerlist;
use common\api\ebayinterface\shopping\getmultipleitem;
use eagle\modules\listing\models\EbayItemVariationMap;
use common\api\ebayinterface\shopping\CurlExcpetion_Connection_Timeout;
use eagle\modules\listing\models\BaseFitmentmuban;
use eagle\models\EbayCategory;
use eagle\modules\listing\models\EbayPromotion;
use common\api\ebayinterface\setpromotionalsalelistings;
class EbayitemController extends \eagle\components\Controller
{
    
    public $enableCsrfValidation = false;
    
    /**
     * 在线item的列表
     * @author fanjs
     */
    public function actionList()
    {
        AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/list');
        //只显示有权限的账号，lrq20170828
        $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
        $selleruserids = array();
        foreach($account_data as $key => $val){
        	$selleruserids[] = $key;
        }
        //add willage 2016/11/16
        $ebayselleruserid = SaasEbayUser::find()
                                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                                ->andwhere('listing_status = 1')
                                ->andwhere('listing_expiration_time > '.time())
                                ->andwhere(['selleruserid' => $selleruserids])
                                ->select('selleruserid')
                                ->asArray()
                                ->all();

        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->andwhere(['selleruserid' => $selleruserids])
                        ->asArray()
                        ->all();
        $data= EbayItem::find()->where(['listingstatus'=>'Active']);
        //不显示 解绑的账号的订单
        $data->andWhere(['selleruserid'=>$ebayselleruserid]);

        if (!empty($_REQUEST['itemtitle'])){
            $data->andWhere('itemtitle like :t',[':t'=>'%'.$_REQUEST['itemtitle'].'%']);
        }
        if (!empty($_REQUEST['itemid'])){
            $data->andWhere(['itemid'=>$_REQUEST['itemid']]);
        }
        if (!empty($_REQUEST['listingtype'])){
            if ($_REQUEST['listingtype']=='Chinese'){
                $data->andWhere(['listingtype'=>'Chinese']);
            }else{
                $data->andWhere('listingtype != "Chinese"');
            }
        }
        if (!empty($_REQUEST['selleruserid'])){
            $data->andWhere(['selleruserid'=>$_REQUEST['selleruserid']]);
        }
        if (!empty($_REQUEST['site'])){
            $data->andWhere(['site'=>$_REQUEST['site']]);
        }
        if (!empty($_REQUEST['sku'])){
            $itemid = EbayItemVariationMap::find()->where(['like','sku',$_REQUEST['sku']])->select('itemid')->asArray()->all();
            $itemid = Helper_Array::getCols($itemid, 'itemid');
            $data->andWhere(['itemid'=>$itemid]);
        }
        if (isset($_REQUEST['hassold'])&&$_REQUEST['hassold']!=''){
            if ($_REQUEST['hassold'] == '0'){
                $data->andWhere('quantitysold=0');
            }else{
                $data->andWhere('quantitysold>0');
            }
        }
        if (isset($_REQUEST['outofstockcontrol'])&&$_REQUEST['outofstockcontrol']!=''){
            if ($_REQUEST['outofstockcontrol'] == '0'){
                $data->andWhere(['outofstockcontrol'=>0]);
            }else{
                $data->andWhere(['outofstockcontrol'=>1]);
            }
        }
        if(isset($_REQUEST['xu']) && strlen($_REQUEST['xu'])){
            if ($_REQUEST['xu'] == 'price') $sortname = 'currentprice';
            if ($_REQUEST['xu'] == 'quantity') $sortname = 'quantitysold';
            $data->orderBy($sortname.' '.$_REQUEST['xusort']);
        }

        $pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
        $items = $data->offset($pages->offset)
        ->limit($pages->limit)
        ->all();
        // $ebayselleruserid = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
        return $this->render('list',['items'=>$items,'pages'=>$pages,'ebayselleruserid'=>$ebayselleruserid,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * 下架item的列表
     * @author fanjs
     */
    public function actionListend()
    {
        AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/listend');
        //只显示有权限的账号，lrq20170828
        $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
        $selleruserids = array();
        foreach($account_data as $key => $val){
        	$selleruserids[] = $key;
        }
        $ebayselleruserid = SaasEbayUser::find()
                                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                                ->andwhere('listing_status = 1')
                                ->andwhere('listing_expiration_time > '.time())
                                ->andwhere(['selleruserid' => $selleruserids])
                                ->select('selleruserid')
                                ->asArray()
                                ->all();
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->andwhere(['selleruserid' => $selleruserids])
                        ->asArray()
                        ->all();
        $data= EbayItem::find()->where('listingstatus != "Active"');
        //add willage 2016/11/16

        //不显示 解绑的账号的订单
        $data->andWhere(['selleruserid'=>$ebayselleruserid]);

        if (!empty($_REQUEST['itemtitle'])){
            $data->andWhere('itemtitle like :t',[':t'=>'%'.$_REQUEST['itemtitle'].'%']);
        }
        if (!empty($_REQUEST['itemid'])){
            $data->andWhere(['itemid'=>$_REQUEST['itemid']]);
        }
        if (!empty($_REQUEST['listingtype'])){
            if ($_REQUEST['listingtype']=='Chinese'){
                $data->andWhere(['listingtype'=>'Chinese']);
            }else{
                $data->andWhere('listingtype != "Chinese"');
            }
        }
        if (!empty($_REQUEST['selleruserid'])){
            $data->andWhere(['selleruserid'=>$_REQUEST['selleruserid']]);
        }
        if (!empty($_REQUEST['site'])){
            $data->andWhere(['site'=>$_REQUEST['site']]);
        }
        if (!empty($_REQUEST['sku'])){
            $data->andWhere(['sku'=>$_REQUEST['sku']]);
        }
        if (!empty($_REQUEST['hassold'])&&strlen($_REQUEST['hassold'])){
            if ($_REQUEST['hassold'] == '0'){
                $data->andWhere(['quantitysold'=>0]);
            }else{
                $data->andWhere('quantitysold>0');
            }
        }
        if (!empty($_REQUEST['outofstockcontrol'])&&strlen($_REQUEST['outofstockcontrol'])){
            if ($_REQUEST['outofstockcontrol'] == '0'){
                $data->andWhere(['outofstockcontrol'=>0]);
            }else{
                $data->andWhere(['outofstockcontrol'=>1]);
            }
        }
        $pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
        $items = $data->offset($pages->offset)
                    ->limit($pages->limit)
                    ->all();
        // $ebayselleruserid = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
        return $this->render('listend',['items'=>$items,'pages'=>$pages,'ebayselleruserid'=>$ebayselleruserid,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * 修改在线的item
     * @author fanjs
     */
    public function actionUpdate(){
        AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/update');
        $itemid = $_GET['itemid'];
        $item_obj=EbayItem::findOne(['itemid' =>$itemid]);
        return $this->render('update',['item'=>$item_obj],false,true);
    }
    
    /**
     * 修改在线的item 第二步
     * @author fanjs
     */
    public function actionUpdate2(){
        $itemid = $_POST['itemid'];
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        //更新Item因为同步Item部分数据没有同步
        $seu = SaasEbayUser::findOne(['selleruserid'=>$item_obj->selleruserid]);
        $getItemAPI=new getitem();
        $getItemAPI->resetConfig($seu->DevAcccountID);
        $getItemAPI->eBayAuthToken=$seu->token;
        $r=$getItemAPI->api($item_obj->itemid);
        if (!$getItemAPI->responseIsFailure()){
            $getItemAPI->save($r);
        }
        $item_detail_obj = EbayItemDetail::findOne(['itemid'=>$itemid]);
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        $item = $item_obj->getAttributes();
        $item_detail = $item_detail_obj->getAttributes();
        $data = array_merge($item,$item_detail);
        if ($data['site'] == 'CustomCode'){
            //getitem部分的item的site为customcode，临时处理
            $site_map = [
                'GBP'=>'UK',
                'USD'=>'US',
                'EUR'=>'Germany',
                'CAD'=>'Canada',
                'AUD'=>'Australia',
            ];
            $data['site'] = $site_map[$data['currency']];
        }
        if ($data['listingtype'] == 'CustomCode'){
            $data['listingtype'] = 'FixedPriceItem';
        }
        $siteid = EbaySite::findOne(['site'=>$data['site']])->siteid;
        $data['siteid']=$siteid;
        $data=array_merge($data,$_POST);
        $setitemvalues = $_POST['setitemvalues'];
        if (!is_array($setitemvalues)){
            $setitemvalues = explode(',', $setitemvalues);
        }
        $carrydata=array(
                'locationarr'=>Helper_Array::toHashmap(EbayCountry::findBySql("select * from ebay_country order by description asc")->asArray()->all(),'country','description'),
                'data'=>$data,
                'setitemvalues'=>$setitemvalues,
        );
        if (strlen($data['primarycategory'])){
            $ecf = EbayCategoryfeature::findOne(['siteid'=>$data['siteid'],'categoryid'=>$data['primarycategory']]);
            $product = [];
            if (!is_null($ecf)){
                $product = [
                    'isbnenabled'=>$ecf->isbnenabled,
                    'upcenabled'=>$ecf->upcenabled,
                    'eanenabled'=>$ecf->eanenabled,
                ];
            }
            $carrydata['product']=$product;
        }
        #######################################################################################
        if(in_array('conditionid',$setitemvalues)){
            $condition=array();
            if (strlen($data['primarycategory'])){
                $ecf = EbayCategoryfeature::findOne(['siteid'=>$data['siteid'],'categoryid'=>$data['primarycategory']]);
                          if (!is_null($ecf)){
                             $condition=array(
                            'conditionenabled'=>$ecf->conditionenabled,
                            'conditionvalues'=>$ecf->conditionvalues,
                    );
                    if (is_null($ecf->conditionenabled)){
                        $api=new getcategoryfeatures();
                        $api->siteID=$data['siteid'];
                        $ue = SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER]);
                        $api->resetConfig($ue->DevAcccountID);
                        $api->eBayAuthToken=$ue->token;
                        $r=$api->requestAll($data['primarycategory']);
                        if ($r['Ack']=='Success') {
                            $condition=array(
                                    'conditionenabled'=>@$r['Category']['ConditionEnabled'],
                                    'conditionvalues'=>@$r['Category']['ConditionValues'],
                            );
                        }
                    }
                }else{
                    $api=new getcategoryfeatures();
                    $api->siteID=$data['siteid'];
                    $ue = SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER]);
                    $api->resetConfig($ue->DevAcccountID);
                    $api->eBayAuthToken=$ue->token;
                    $r=$api->requestAll($data['primarycategory']);
                    if ($r['Ack']=='Success') {
                        $condition=array(
                                'conditionenabled'=>@$r['Category']['ConditionEnabled'],
                                'conditionvalues'=>@$r['Category']['ConditionValues'],
                        );
                    }
                }
            }
            $carrydata['condition']=$condition;
        }
        #######################################################################################
        if(in_array('itemspecifics',$setitemvalues)){
        //$specifics=array();
            $specifics = EbaySpecific::findAll(['siteid'=>$data['siteid'],'categoryid'=>$data['primarycategory']]);
            $carrydata['specifics']=$specifics;
        }
        
        ################################################################################
            if(in_array('shippingdetails',$setitemvalues)){
            /**
            * 物流数据的构建
                * $shippingservice:境内物流
                 * $ishippingservice:境外物流
                 */
                    if ($data['siteid'] == 100) {
                    $sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = 0';
            } else {
                $sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = '.$data['siteid'];
            }
                                $shippingservice=EbayShippingservice::findBySql($sql)->asArray()->all();
                                $shippingservice=EbayMuban::dealshippingservice($shippingservice);
                                $ishippingservice = array ();
                                foreach ( $shippingservice as $k => $v ) {
                                if ($v ['internationalservice'] == 'true') {
                                $ishippingservice [] = $v;
                                unset ( $shippingservice [$k] );
                                }
                                }
                                $shippingserviceall=array(
                                        'shippingservice'=>Helper_Array::toHashmap($shippingservice,'shippingservice','description'),
                                                'ishippingservice'=>Helper_Array::toHashmap($ishippingservice,'shippingservice','description'),
                                                'shiplocation'=>Helper_Array::toHashmap(EbayShippinglocation::findBySql('select * from ebay_shippinglocation where siteid = '
                                                        .$data['siteid'].' AND shippinglocation != \'None\'')->asArray()->all(),'shippinglocation','description'),
                                );
            $carrydata['shippingserviceall']=$shippingserviceall;
        }
        
        ################################################################################
        $selectsite = EbaySite::findOne(['siteid'=>$data['siteid']]);
        $feature_array=array();
        if ($selectsite->listing_feature['BoldTitle']=='Enabled'){
        $feature_array['BoldTitle']='BoldTitle';
        }
        if ($selectsite->listing_feature['Border']=='Enabled'){
            $feature_array['Border']='Border';
        }
        if ($selectsite->listing_feature['Highlight']=='Enabled'){
        $feature_array['Highlight']='Highlight';
        }
        if ($selectsite->listing_feature['HomePageFeatured']=='Enabled'){
            $feature_array['HomePageFeatured']='HomePageFeatured';
        }
        if ($selectsite->listing_feature['FeaturedFirst']=='Enabled'){
            $feature_array['FeaturedFirst']='FeaturedFirst';
        }
        if ($selectsite->listing_feature['FeaturedPlus']=='Enabled'){
            $feature_array['FeaturedPlus']='FeaturedPlus';
        }
        if ($selectsite->listing_feature['ProPack']=='Enabled'){
            $feature_array['ProPack']='ProPack';
        }
                ################################################################################
        $carrydata['feature_array']=$feature_array;
        if (strlen($selectsite->buyer_requirement['LinkedPayPalAccount'])){
            $carrydata['buyerrequirementenable']=$selectsite->buyer_requirement['LinkedPayPalAccount'];
        }
        if(is_array($selectsite->return_policy)){
            $carrydata['returnpolicy']=$selectsite->return_policy;
        }
        if(is_array($selectsite->payment_option)){
            $carrydata['paymentoption']=Helper_Array::toHashmap($selectsite->payment_option, 'PaymentOption','Description');
        }
        if(is_array($selectsite->tax_jurisdiction)){
            $carrydata['salestaxstate']=Helper_Array::toHashmap($selectsite->tax_jurisdiction,'JurisdictionID','JurisdictionName');
        }
        if(is_array($selectsite->dispatchtimemax)){
            $carrydata['dispatchtimemax']=Helper_Array::toHashmap($selectsite->dispatchtimemax,'DispatchTimeMax','Description');
        }
        return $this->render('update3',$carrydata,false,true);
    }

    /**
     * 修改在线刊登的接口调用
     * @author fanjs
     */
    function actionUpdate3(){
        \yii::info("==========","file");
        \yii::info($_POST,"file");
        $itemid=$_POST['itemid'];
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        $item_detail_obj = EbayItemDetail::findOne(['itemid'=>$itemid]);
        $item_detail = $item_detail_obj->getAttributes();
        $seu = SaasEbayUser::findOne(['selleruserid'=>$item_obj->selleruserid]);
        $siteid = EbaySite::findOne(['site'=>$item_obj->site])->siteid;
        $EIRI = new reviseitem();
        $EIRI->resetConfig($seu->listing_devAccountID);
        $EIRI->siteID=$siteid;
        $EIRI->eBayAuthToken=$seu->listing_token;
        $itemValues = $_POST;
        if (!empty($itemValues['shippingdetails']['ShippingServiceOptions'])) {
            foreach ($itemValues['shippingdetails']['ShippingServiceOptions'] as $key => $val) {
                if (isset($val['ShippingServiceAdditionalCost'])) {
                    if (strlen($val['ShippingServiceAdditionalCost'])==0) {
                        $itemValues['shippingdetails']['ShippingServiceOptions'][$key]['ShippingServiceAdditionalCost']='0.00';
                    }else{
                        \yii::info(print_r($val,false),"file");
                    }
                }
            }
            # code...
        }
        //多属性
        if (isset($_POST['nvl_name']))
        {
            $variations = $item_detail['variation'];
            if (isset($variations['Variation']['StartPrice'])){
                $variations['Variation']=array($variations['Variation']);
            }
            $variation_table=array();
            $variation_nvl=array();
            foreach ($_POST['quantity'] as $i => $q){
                if (strlen($q)==0 && strlen($_POST['startprice'][$i])==0){
                    continue;
                }
                $row=array(
                        'Quantity'=>$q,
                        'SKU'=>$_POST['variationsku'][$i],
                        'StartPrice'=>$_POST['startprice'][$i]
                );
                if (isset($_POST['v_productid_name'])){
                    $row['VariationProductListingDetails'][$_POST['v_productid_name']] = $_POST['v_productid_val'][$i];
                }
                foreach ($_POST['nvl_name'] as $nvl_name){
                    @$row['vvv'][$nvl_name]=$_POST[base64_encode($nvl_name)][$i];
                    $variation_nvl[$nvl_name][$_POST[base64_encode($nvl_name)][$i]]=$_POST[base64_encode($nvl_name)][$i];
                }
                $variation_table[]=$row;
            }
             
            $quantity_arr=$_POST['quantity'];
            $sku_arr=$_POST['variationsku'];
        
            $name=$_POST['nvl_name'];
            foreach ($variations['Variation'] as &$vnode){
                $nvl=$vnode['VariationSpecifics']['NameValueList'];
                if (isset($nvl['Name'])){
                    $nvl=array($nvl);
                }
                foreach ($variation_table as $row_i =>$row){
                    $match=true;
                    foreach ($nvl as $nvlnode){
                        if ($row['vvv'][$nvlnode['Name']]!=$nvlnode['Value']){
                            $match=false;
                        }
                    }
                    if ($match==true){
                        $vnode['Quantity']=$row['Quantity'];
                        $vnode['StartPrice']=$row['StartPrice'];
                        $vnode['SKU']=$row['SKU'];
                        unset($variation_table[$row_i]);
                        break;
                    }
                }
            }
            //还有多余项
            if (count($variation_table)){
                foreach ($variation_table as $row){
                    $_nvl=array();
                    foreach ($_POST['nvl_name'] as $nvl_name){
                        $_nvl_node=array();
                        $_nvl_node['Name']=$nvl_name;
                        $_nvl_node['Value']=$row['vvv'][$nvl_name];
                        $_nvl[]=$_nvl_node;
                    }
                    $variations['Variation'][]=array(
                            'Quantity'=>$row['Quantity'],
                            'SKU'=>$row['SKU'],
                            'StartPrice'=>$row['StartPrice'],
                            'VariationSpecifics'=>array('NameValueList'=>$_nvl),
                    );
                }
            }
            if (isset($variations['VariationSpecificsSet']['NameValueList']['Name'])){
                $variations['VariationSpecificsSet']['NameValueList']=array($variations['VariationSpecificsSet']['NameValueList']);
            }
            foreach ($variation_nvl as $_name => $_values){
                foreach ($variations['VariationSpecificsSet']['NameValueList'] as $i=>$old_nvl){
                    if ($old_nvl['Name'] == $_name){
                        if(!is_array($old_nvl['Value'])&&strlen($old_nvl['Value'])){
                            $old_nvl['Value']=array($old_nvl['Value']);
                        }
                        $variations['VariationSpecificsSet']['NameValueList'][$i]['Value']=array_unique(array_merge($_values,$old_nvl['Value']));
                    }
                }
            }
            unset($_POST['quantity']);
            unset($_POST['startprice']);
            if (isset($_POST['picture']) && count($_POST['picture'])){
                Helper_Array::removeEmpty($_POST['picture']);
            }
            if (count($_POST['picture']) && $_POST['VariationSpecificName']){
                //检查是否需要上传图片
                $uploading=false;
                $imgurl=$item_detail['imgurl'][0];
                if (strpos($imgurl, 'ebayimg.com')!==false){
                    $uploading=true;
                }
                $variations['Pictures']=array('VariationSpecificName'=>$_POST['VariationSpecificName']);
                foreach ($_POST['picture'] as $key => $everyurl){
                    foreach ($everyurl as $url) {
                        if ($uploading&&strpos($url, 'ebayimg.com')===false){
                            $pictureManager=new UploadSiteHostedPictures();
                            $pictureManager->resetConfig($seu->listing_devAccountID);
                            $pictureManager->siteID=$siteid;
                            $pictureManager->eBayAuthToken=$seu->listing_token;
                            $url=$pictureManager->upload($url);
                            $url=$url['SiteHostedPictureDetails']['FullURL'];
                        }
        
                        $variations['Pictures']['VariationSpecificPictureSet'][]=array(
                                'PictureURL'=>$url,
                                'VariationSpecificValue'=>$key
                        );
                    }
                }
            }
            $itemValues['variation']=$variations;
        }
        $r=$EIRI->apiCore($itemid,$itemValues);
        return $this->render('_result',array('result'=>$r));
    }
    
    /**
     * 修改在线的Item的一口价及数量的操作页面
     */
    public function actionRevise(){
        AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/revise');
        if(strlen($_REQUEST['keys'])){
            $keys=explode(',',$_REQUEST['keys']);
            $keys = array_filter($keys);
            $items = EbayItem::findAll(['itemid'=>$keys]);
            return $this->render('revise',['items'=>$items]);
        }
    } 
    
    /**
     * 批量修改在线Item的ajax处理页面
     * @author fanjs 20150731
     */
    public function actionAjaxrevise(){
        if (\Yii::$app->request->isPost){
            session_write_close();
            $item = EbayItem::findOne(['itemid'=>$_POST['itemid']]);
            if (empty($item)) {
                exit(array('Ack'=>'Failure','show'=>'Item不存在'));
            }
            $eu = SaasEbayUser::findOne(['selleruserid'=>$item->selleruserid]);
            $api = new reviseinventorystatus();
            $api->resetConfig($eu->listing_devAccountID);
            $values=array();
            //sku仅针对有多属性的进行上传
            if (strlen($_POST['sku'])&&$item->isvariation){
                $values['sku']=$_POST['sku'];
            }
            $values['quantity']=$_POST['quantity'];
            $values['startprice']=$_POST['startprice'];
    
            $api->siteID = EbaySite::findOne(['site'=>$item->site])->siteid;
            $r=$api->apiforrevise($item->itemid, $values, $eu->listing_token);
            //log
            $reviseitem = new EbayLogItem();
            $reviseitem->name=\Yii::$app->user->identity->getFullName();
            $reviseitem->reason="手动修改";
            $reviseitem->itemid=$item->itemid;
            $reviseitem->content=$values;
            $reviseitem->result=$r['Ack'];
            $result['Ack']=$r['Ack'];
            if (!$api->responseIsFailure()){
                //将itemvalues中的数量部分在存储前加上售出的数量  auther@fanjs
                if(strlen($values['quantity'])>0){
                    $item->quantity=$values['quantity']+$item->quantitysold;
                    //                  $valuesr['quantity']=$values['quantity']+$item->quantitysold;
                }
                if (strlen($values['startprice'])){
                    $item->currentprice=$values['startprice'];
                    $item->startprice=$values['startprice'];
                }
                //              $item->setAttributes($valuesr);
                $item->save();
                $message='修改成功';
                $result['show']='修改成功';
    
                $detail = EbayItemDetail::findOne($item->itemid);
                    
                //多属性数据保存
                if (count($detail->variation) && $values['sku']){
                    $va=$detail->variation;
                    $variation=$va['Variation'];
                    if (isset($variation['SKU'])){
                        $variation=array($variation);
                    }
                    foreach ($variation as &$row){
                        if ($row['SKU']==$values['sku']){
                            if (strlen($values['quantity'])){
                                $row['Quantity']=$values['quantity']+@$row['SellingStatus']['QuantitySold'];
                            }
                            if ($values['startprice']){
                                $row['StartPrice']=$values['startprice'];
                            }
                            if (isset($values['buyitnowprice'])){
                                $row['StartPrice']=$values['buyitnowprice'];
                            }
                        }
                    }
                    $va['Variation']=$variation;
                    $detail->variation=$va;
                    $detail->save();
                }
                    
                    
            }elseif ($api->responseIsFailure()){
                $message='修改失败';
                if(isset($r['Errors'])){
                    if(isset($r['Errors'][0])){
                        $result['show']=$r['Errors'][0]['LongMessage'];
                    }else{
                        $result['show']=$r['Errors']['LongMessage'];
                    }
                }
            }
            //log
            $reviseitem->message=$message;
            $reviseitem->save();
            exit(Json::encode($result));
        }
    }

    /**
     * 同步在线item的最新数据
     * @author fanjs
     */
    public function actionSync(){
        if(\Yii::$app->request->isPost){
            AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/sync');
            if (isset($_POST['itemid'])){
                $itemid=$_POST['itemid'];
                $item = EbayItem::findOne(['itemid'=>$itemid]);
                $eBayuser = SaasEbayUser::findOne(['selleruserid'=>$item->selleruserid]);
                 
                //api请求
                $api = new getsingleitem();
                $result = $api->apiItem($itemid);
                if (!$api->responseIsFail) {
                    $api->save ( $result,$eBayuser,[] );
                    return 'success';
                }else{
                    if (isset($result['Errors']['LongMessage'])){
                        $result['Errors']['0']['LongMessage']=$result['Errors']['LongMessage'];
                    }
                    return $result['Errors']['0']['LongMessage'];
                }
            }
        }
    }
    
    /**
     * 提前下架在线item
     * @author fanjs
     */
    public function actionClose(){
        if(\Yii::$app->request->isPost){
            AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/close');
            return $this->renderPartial('close',['itemid'=>$_POST['itemid']]);
        }
    }
    
    /**
     * 提前下架在线item
     * @author fanjs
     */
    public function actionAjaxClose(){
        if(\Yii::$app->request->isPost){
            if (isset($_POST['itemid'])&&isset($_POST['reason'])){
                $itemid=$_POST['itemid'];
                $itemid = explode(',',$itemid);
                $itemids = array_filter($itemid);
                $rst = '';
                foreach ($itemids as $itemid){
                    $item = EbayItem::findOne(['itemid'=>$itemid]);
                    $eBayuser = $item->selleruserid;
                    $token = SaasEbayUser::findOne(['selleruserid'=>$eBayuser])->listing_token;
                    $devID=SaasEbayUser::findOne(['selleruserid'=>$eBayuser])->listing_devAccountID;
                    //api请求
                    $api = new enditem();
                    $api->resetConfig($devID);
                    $api->eBayAuthToken = $token;
                    $result = $api->api($itemid,$_POST['reason']);
                    if ($api->responseIsSuccess()){
                        $item->listingstatus='Ended';
                        $item->save();
                    }else{
                        if (isset($result['Errors']['LongMessage'])){
                            $result['Errors']['0']['LongMessage']=$result['Errors']['LongMessage'];
                        }
                        $rst .= $itemid.':'.$result['Errors']['0']['LongMessage'].'<br>';
                    }
                }
                if ($rst == ''){
                    $rst = 'success';
                }
                return $rst;
            }
        }
    }
    
    /**
     * 在线item的操作记录
     * @author fanjs
     */
    public function actionHistory(){
        AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/history');
        if (isset($_GET['itemid'])){
            $data = EbayLogItem::find()->where(['itemid'=>$_GET['itemid']]);
        }else{
            $data = EbayLogItem::find()->where(['itemid'=>'-1']);
        }
        $pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
        $logs = $data->offset($pages->offset)
        ->limit($pages->limit)
        ->all();
        return $this->render('history',['logs'=>$logs,'pages'=>$pages]);
    }
    
    /**
     * 重上下架的刊登
     * @author fanjs
     */
    public function actionRelist(){
        if(\Yii::$app->request->isPost){
            AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/relist');
            
            $itemid=$_POST['itemid'];
            $itemid = explode(',',$itemid);
            $itemids = array_filter($itemid);
            $rst = '';
            foreach ($itemids as $itemid){
            
                $item = EbayItem::find()->where(['itemid'=>$itemid])->one();
                if (empty($item)){
                    $rst .= $itemid.':刊登不存在<br>';continue;
                }
                $user=SaasEbayUser::find()->where(['selleruserid'=>$item->selleruserid])->one();
                $api = new relistitem();
                $api->resetConfig($user->listing_devAccountID);
                $sitearr=Helper_Siteinfo::getEbaySiteIdList();
                foreach ($sitearr as $k=>$v){
                    if($v['en']==$item->site){
                        $api->siteID=$v['no'];
                        break;
                    }
                }
                $r=$api->apirelist($item->itemid, $user->listing_token);
                $reviseitem = new EbayLogItem();
                $reviseitem->name=\Yii::$app->user->identity->getUsername();
                $reviseitem->reason="重新刊登";
                $reviseitem->itemid=$item->itemid;
                $reviseitem->result=$r['Ack'];
                $result['Ack']=$r['Ack'];
                if ($result['Ack']=="Success"){
                    //newitem
                    $getitem_api = new getitem();
                    $getitem_api->resetConfig($user->DevAcccountID);
                    $getitem_api->eBayAuthToken = $user->token;
                    $newitem = $getitem_api->api ( $r['ItemID'] );
                    if (! $getitem_api->responseIsFailure ()) {
                        \Yii::info( 'get success' );
                        // 保存 同步状态
                        $getitem_api->save ( $newitem );
                    }else{
                        $log = new EbayLogItem();
                        \Yii::log ( 'get failed' );
                        $log->setAttributes ( array (
                                'uid' => $user->uid,
                                'selleruserid' => $user->selleruserid,
                                'itemid' => $r['ItemID'],
                        ) );
                        $log->message="getitem failed";
                        $log->save(false);
                    };
                    $rst .= $itemid.':success<br>';
                }else{
                    if(isset($r['Errors'])){
                        if(isset($r['Errors'][0])){
                            $str=$r['Errors'][0]['LongMessage'];
                        }else{
                            $str=$r['Errors']['LongMessage'];
                        }
                    }
                    $rst .= $itemid.':'.$str.'<br>';
                }
                //log
                $reviseitem->message=$r;
                $reviseitem->save(false);
            }
                return $rst;
        }
    }
    
    /**
     * 手动同步ebay账号的在线item
     * @author fanjs
     */
    public function actionMtsync(){
    	//只显示有权限的账号，lrq20170828
    	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
    	$selleruserids = array();
    	foreach($account_data as $key => $val){
    		$selleruserids[] = $key;
    	}
        // $ebayselleruserid = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
        $ebayselleruserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 1')
                        ->andwhere('listing_expiration_time > '.time())
                        ->andwhere(['selleruserid' => $selleruserids])
                        ->all();
        return $this->renderPartial('mtsync',['ebayselleruserid'=>$ebayselleruserid]);
    }
    
    /**
     * ajax获取账号的在线item数量
     * @author fanjs
     */
    public function actionAjaxgetitemcount(){
        if(\Yii::$app->request->isPost){
            $eu = SaasEbayUser::findOne(['selleruserid'=>$_POST['sellerid']]);
            if (empty($eu))return Json::encode(['ack'=>'failure','msg'=>'找不到相应的eBay账号']);
           
            $api = new getsellerlist();
            $api->resetConfig($eu->DevAcccountID);
            $api->eBayAuthToken = $eu->token;
            
            $start = base::dateTime(time() - 20 * 24 * 3600 );
            $end = base::dateTime ( time () + 50 * 24 * 3600 );
            $api->_before_request_xmlarray ['OutputSelector'] = array (
                    'PaginationResult',
                    'ItemArray.Item.Quantity',
                    'ItemArray.Item.Variations.Variation',
                    'ItemArray.Item.SKU',
                    'ItemArray.Item.SellingStatus',
                    'ItemArray.Item.ItemID',
                    'ItemArray.Item.StartPrice',
                    'ItemArray.Item.Storefront.StoreCategoryID',
                    'ItemArray.Item.Storefront.StoreCategory2ID',
                    'ItemArray.Item.Location',
                    'ItemArray.Item.ListingDetails.EndTime'
            );
            $currentPage = isset($_POST['currentpage'])?$_POST['currentpage']:'1';
            $pagination = array (
                    'EntriesPerPage' => 50,
                    'PageNumber' => $currentPage
            );
            $r = $api->api ( $pagination, 'ReturnAll', $start, $end );
            if ($api->responseIsFailure ()) {
                if ($r ['Errors'] ['ErrorCode'] == 340) {
                    return Json::encode(['ack'=>'warning','msg'=>'全部处理完毕']);
                }
                return Json::encode(['ack'=>'warning','msg'=>$r['Errors']['LongMessage']]);
            }else{
                return Json::encode(['ack'=>'success','data'=>['TotalNumberOfPages'=>$r['PaginationResult']['TotalNumberOfPages'],'TotalNumberOfEntries'=>$r['PaginationResult']['TotalNumberOfEntries']]]);
            }
        }
    }
    
    /**
     * ajax同步在线的item
     * @param sellerid同步的账号
     * @param currentpage 同步的页码
     */
    public function actionAjaxgetitem(){
        if(\Yii::$app->request->isPost){
            set_time_limit(0);
            $eu = SaasEbayUser::findOne(['selleruserid'=>$_POST['sellerid']]);
            if (empty($eu))return Json::encode(['ack'=>'failure','msg'=>'找不到相应的eBay账号']);
        
            $api = new getsellerlist();
            $api->resetConfig($eu->DevAcccountID);
            $api->eBayAuthToken = $eu->token;

            $start = base::dateTime(time() - 20 * 24 * 3600 );
            $end = base::dateTime ( time () + 50 * 24 * 3600 );
            $api->_before_request_xmlarray ['OutputSelector'] = array (
                    'PaginationResult',
//                  'ItemArray.Item.Quantity',
//                  'ItemArray.Item.Variations.Variation',
//                  'ItemArray.Item.SKU',
//                  'ItemArray.Item.SellingStatus',
                    'ItemArray.Item.ItemID',
                    'ItemArray.Item.Site',
//                  'ItemArray.Item.StartPrice',
//                  'ItemArray.Item.Storefront.StoreCategoryID',
//                  'ItemArray.Item.Storefront.StoreCategory2ID',
//                  'ItemArray.Item.Location',
//                  'ItemArray.Item.ListingDetails.EndTime'
            );
            $currentPage = isset($_POST['currentpage'])?$_POST['currentpage']:'1';
            $pagination = array (
                    'EntriesPerPage' => 50,
                    'PageNumber' => $currentPage
            );
            $r = $api->api ( $pagination, 'ReturnAll', $start, $end );

            if ($api->responseIsFailure ()) {
                if ($r ['Errors'] ['ErrorCode'] == 340) {
                    return Json::encode(['ack'=>'warning','msg'=>'全部处理完毕']);
                }
                return Json::encode(['ack'=>'warning','msg'=>$r['Errors']['LongMessage']]);
            }else{
                //存储item
                if (isset($r['ItemArray']['Item'])){
                    if (isset($r['ItemArray']['Item']['ItemID'])){
                        $_tmp = $r['ItemArray']['Item'];
                        unset($r['ItemArray']);
                        $r['ItemArray']['Item']['0'] = $_tmp;
                    }
                    $itemArr = $r ['ItemArray'] ['Item'];
                    
                    $_sitemap = Helper_Array::toHashmap($itemArr, 'ItemID','Site');
                    $getitem_api = new getmultipleitem();
                    $_tmparr = [];
                    $_Arrcount = count($itemArr);
                    foreach ( $itemArr as $row ) {
                        $_Arrcount--;
                        $_itemid = $row ['ItemID'];
                        array_push($_tmparr, $row ['ItemID']);
                        if (count($_tmparr)==10 ||$_Arrcount == 0){
                            $str = implode(',',$_tmparr);
                            try {
                                $_r = $getitem_api->apiItem($str);
                                if ($getitem_api->responseIsFail) {
                                    return Json::encode(['ack'=>'failure','msg'=>'eBay multitem failed']);
                                }
                            }catch (CurlExcpetion_Connection_Timeout $e){
                                return Json::encode(['ack'=>'failure','msg'=>'eBay接口通讯超时']);
                            }
                            // 保存 同步状态
                            $getitem_api->save ( $_r,$eu,$_sitemap );
                            $_tmparr = [];
                        }
                    }
                    EbayItem::updateAll(['listingstatus'=>'Ended'],'endtime < '.(time()-1800).' and listingstatus = "Active"');
                }else{
                    return Json::encode(['ack'=>'failure','msg'=>'接口未返回Item数据']);
                }
                return Json::encode(['ack'=>'success']);
            }
        }
    }
    
    /**
     * 重上item时可以设置部分字段的值
     * @author fanjs
     */
    public function actionRelistmodify(){
        $itemid = $_GET['itemid'];
        $item_obj=EbayItem::findOne(['itemid' =>$itemid]);
        return $this->render('relistmodify',['item'=>$item_obj],false,true);
    }
    
    /**
     * 重上item时可修改属性 第二步
     * @author fanjs
     */
    public function actionRelistmodify2(){
        $itemid = $_POST['itemid'];
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        $item_detail_obj = EbayItemDetail::findOne(['itemid'=>$itemid]);
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        $item = $item_obj->getAttributes();
        $item_detail = $item_detail_obj->getAttributes();
        $data = array_merge($item,$item_detail);
        if ($data['site'] == 'CustomCode'){
            //getitem部分的item的site为customcode，临时处理
            $site_map = [
                'GBP'=>'UK',
                'USD'=>'US',
                'EUR'=>'Germany',
                'CAD'=>'Canada',
                'AUD'=>'Australia',
            ];
            $data['site'] = $site_map[$data['currency']];
        }
        if ($data['listingtype'] == 'CustomCode'){
            $data['listingtype'] = 'FixedPriceItem';
        }
        $siteid = EbaySite::findOne(['site'=>$data['site']])->siteid;
        $data['siteid']=$siteid;
        $data=array_merge($data,$_POST);
        $setitemvalues = $_POST['setitemvalues'];
        if (!is_array($setitemvalues)){
            $setitemvalues = explode(',', $setitemvalues);
        }
        $carrydata=array(
            'locationarr'=>Helper_Array::toHashmap(EbayCountry::findBySql("select * from ebay_country order by country asc")->asArray()->all(),'country','description'),
            'data'=>$data,
            'setitemvalues'=>$setitemvalues,
        );
        #######################################################################################
        if(in_array('conditionid',$setitemvalues)){
            $condition=array();
            if (strlen($data['primarycategory'])){
                $ecf = EbayCategoryfeature::findOne(['siteid'=>$data['siteid'],'categoryid'=>$data['primarycategory']]);
                if (!is_null($ecf)){
                    $condition=array(
                        'conditionenabled'=>$ecf->conditionenabled,
                        'conditionvalues'=>$ecf->conditionvalues,
                    );
                    if (is_null($ecf->conditionenabled)){
                        $api=new getcategoryfeatures();
                        $api->siteID=$data['siteid'];
                        $ue = SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER]);
                        $api->resetConfig($ue->DevAcccountID);
                        $api->eBayAuthToken=$ue->token;
                        $r=$api->requestAll($data['primarycategory']);
                        if ($r['Ack']=='Success') {
                            $condition=array(
                                'conditionenabled'=>@$r['Category']['ConditionEnabled'],
                                'conditionvalues'=>@$r['Category']['ConditionValues'],
                            );
                        }
                    }
                }else{
                    $api=new getcategoryfeatures();
                    $api->siteID=$data['siteid'];
                    $ue = SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER]);
                    $api->resetConfig($ue->DevAcccountID);
                    $api->eBayAuthToken=$ue->token;
                    $r=$api->requestAll($data['primarycategory']);
                    if ($r['Ack']=='Success') {
                        $condition=array(
                            'conditionenabled'=>@$r['Category']['ConditionEnabled'],
                            'conditionvalues'=>@$r['Category']['ConditionValues'],
                        );
                    }
                }
            }
            $carrydata['condition']=$condition;
             
        }
        #######################################################################################
        if(in_array('itemspecifics',$setitemvalues)){
        //$specifics=array();
            $specifics = EbaySpecific::findAll(['siteid'=>$data['siteid'],'categoryid'=>$data['primarycategory']]);
            $carrydata['specifics']=$specifics;
        }
         
        ################################################################################
        if(in_array('shippingdetails',$setitemvalues)){
            /**
            * 物流数据的构建
            * $shippingservice:境内物流
            * $ishippingservice:境外物流
            */
            if ($data['siteid'] == 100) {
                $sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = 0';
            } else {
                $sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = '.$data['siteid'];
            }
            $shippingservice=EbayShippingservice::findBySql($sql)->asArray()->all();
            $shippingservice=EbayMuban::dealshippingservice($shippingservice);
            $ishippingservice = array ();
            foreach ( $shippingservice as $k => $v ) {
                if ($v ['internationalservice'] == 'true') {
                    $ishippingservice [] = $v;
                    unset ( $shippingservice [$k] );
                }
            }
            $shippingserviceall=array(
                'shippingservice'=>Helper_Array::toHashmap($shippingservice,'shippingservice','description'),
                'ishippingservice'=>Helper_Array::toHashmap($ishippingservice,'shippingservice','description'),
                'shiplocation'=>Helper_Array::toHashmap(EbayShippinglocation::findBySql('select * from ebay_shippinglocation where siteid = '
                    .$data['siteid'].' AND shippinglocation != \'None\'')->asArray()->all(),'shippinglocation','description'),
            );
            $carrydata['shippingserviceall']=$shippingserviceall;
        }
                         
        ################################################################################
        $selectsite = EbaySite::findOne(['siteid'=>$data['siteid']]);
        $feature_array=array();
        if ($selectsite->listing_feature['BoldTitle']=='Enabled'){
            $feature_array['BoldTitle']='BoldTitle';
        }
        if ($selectsite->listing_feature['Border']=='Enabled'){
                $feature_array['Border']='Border';
        }
        if ($selectsite->listing_feature['Highlight']=='Enabled'){
            $feature_array['Highlight']='Highlight';
        }
        if ($selectsite->listing_feature['HomePageFeatured']=='Enabled'){
            $feature_array['HomePageFeatured']='HomePageFeatured';
        }
        if ($selectsite->listing_feature['FeaturedFirst']=='Enabled'){
            $feature_array['FeaturedFirst']='FeaturedFirst';
        }
        if ($selectsite->listing_feature['FeaturedPlus']=='Enabled'){
            $feature_array['FeaturedPlus']='FeaturedPlus';
        }
        if ($selectsite->listing_feature['ProPack']=='Enabled'){
            $feature_array['ProPack']='ProPack';
        }
        ################################################################################
        $carrydata['feature_array']=$feature_array;
        if (strlen($selectsite->buyer_requirement['LinkedPayPalAccount'])){
            $carrydata['buyerrequirementenable']=$selectsite->buyer_requirement['LinkedPayPalAccount'];
        }
        if(is_array($selectsite->return_policy)){
            $carrydata['returnpolicy']=$selectsite->return_policy;
        }
        if(is_array($selectsite->payment_option)){
            $carrydata['paymentoption']=Helper_Array::toHashmap($selectsite->payment_option, 'PaymentOption','Description');
        }
        if(is_array($selectsite->tax_jurisdiction)){
            $carrydata['salestaxstate']=Helper_Array::toHashmap($selectsite->tax_jurisdiction,'JurisdictionID','JurisdictionName');
        }
        if(is_array($selectsite->dispatchtimemax)){
            $carrydata['dispatchtimemax']=Helper_Array::toHashmap($selectsite->dispatchtimemax,'DispatchTimeMax','Description');
        }
        return $this->render('relistmodify2',$carrydata,false,true);
    }
        
    /**
    * 重上item时的接口调用
    * @author fanjs
    */
    function actionRelistmodify3(){
        $itemid=$_POST['itemid'];
        $item_obj = EbayItem::findOne(['itemid'=>$itemid]);
        $item_detail_obj = EbayItemDetail::findOne(['itemid'=>$itemid]);
        $item_detail = $item_detail_obj->getAttributes();
        $seu = SaasEbayUser::findOne(['selleruserid'=>$item_obj->selleruserid]);
        $siteid = EbaySite::findOne(['site'=>$item_obj->site])->siteid;
        $EIRI = new relistitem();
        $EIRI->resetConfig($seu->listing_devAccountID);
        if ($item_obj->listingtype != 'Chinese'){
            $EIRI->verb = 'RelistFixedPriceItem';
        }
        $EIRI->siteID=$siteid;
        $EIRI->eBayAuthToken=$seu->listing_token;
        $itemValues = $_POST;
            //多属性
        if (isset($_POST['nvl_name'])){
            $variations = $item_detail['variation'];
            if (isset($variations['Variation']['StartPrice'])){
                $variations['Variation']=array($variations['Variation']);
            }
            $variation_table=array();
            $variation_nvl=array();
            foreach ($_POST['quantity'] as $i => $q){
                if (strlen($q)==0 && strlen($_POST['startprice'][$i])==0){
                    continue;
                }
                $row=array(
                    'Quantity'=>$q,
                    'SKU'=>$_POST['variationsku'][$i],
                    'StartPrice'=>$_POST['startprice'][$i]
                );
                foreach ($_POST['nvl_name'] as $nvl_name){
                    @$row['vvv'][$nvl_name]=$_POST[base64_encode($nvl_name)][$i];
                    $variation_nvl[$nvl_name][$_POST[base64_encode($nvl_name)][$i]]=$_POST[base64_encode($nvl_name)][$i];
                }
                $variation_table[]=$row;
            }
                 
            $quantity_arr=$_POST['quantity'];
            $sku_arr=$_POST['variationsku'];
                 
            $name=$_POST['nvl_name'];
            foreach ($variations['Variation'] as &$vnode){
                $nvl=$vnode['VariationSpecifics']['NameValueList'];
                if (isset($nvl['Name'])){
                    $nvl=array($nvl);
                }
                foreach ($variation_table as $row_i =>$row){
                    $match=true;
                    foreach ($nvl as $nvlnode){
                        if ($row['vvv'][$nvlnode['Name']]!=$nvlnode['Value']){
                            $match=false;
                        }
                    }
                    if ($match==true){
                        $vnode['Quantity']=$row['Quantity'];
                        $vnode['StartPrice']=$row['StartPrice'];
                        $vnode['SKU']=$row['SKU'];
                        unset($variation_table[$row_i]);
                        break;
                    }
                }
            }
            //还有多余项
            if (count($variation_table)){
                foreach ($variation_table as $row){
                    $_nvl=array();
                    foreach ($_POST['nvl_name'] as $nvl_name){
                        $_nvl_node=array();
                        $_nvl_node['Name']=$nvl_name;
                        $_nvl_node['Value']=$row['vvv'][$nvl_name];
                        $_nvl[]=$_nvl_node;
                    }
                    $variations['Variation'][]=array(
                        'Quantity'=>$row['Quantity'],
                        'SKU'=>$row['SKU'],
                        'StartPrice'=>$row['StartPrice'],
                        'VariationSpecifics'=>array('NameValueList'=>$_nvl),
                    );
                }
            }
            if (isset($variations['VariationSpecificsSet']['NameValueList']['Name'])){
                $variations['VariationSpecificsSet']['NameValueList']=array($variations['VariationSpecificsSet']['NameValueList']);
            }
            foreach ($variation_nvl as $_name => $_values){
                foreach ($variations['VariationSpecificsSet']['NameValueList'] as $i=>$old_nvl){
                    if ($old_nvl['Name'] == $_name){
                        if(!is_array($old_nvl['Value'])&&strlen($old_nvl['Value'])){
                            $old_nvl['Value']=array($old_nvl['Value']);
                        }
                        $variations['VariationSpecificsSet']['NameValueList'][$i]['Value']=array_unique(array_merge($_values,$old_nvl['Value']));
                    }
                }
            }
            unset($_POST['quantity']);
            unset($_POST['startprice']);
            Helper_Array::removeEmpty($_POST['picture']);
            if (count($_POST['picture']) && $_POST['VariationSpecificName']){
                //检查是否需要上传图片
                $uploading=false;
                $imgurl=$item_detail['imgurl'][0];
                if (strpos($imgurl, 'ebayimg.com')!==false){
                    $uploading=true;
                }
                $variations['Pictures']=array('VariationSpecificName'=>$_POST['VariationSpecificName']);
                foreach ($_POST['picture'] as $key => $everyurl){
                    foreach ($everyurl as $url) {
                        if ($uploading&&strpos($url, 'ebayimg.com')===false){
                            $pictureManager=new UploadSiteHostedPictures();
                            $pictureManager->resetConfig($seu->listing_devAccountID);
                            $pictureManager->siteID=$siteid;
                            $pictureManager->eBayAuthToken=$seu->listing_token;
                            $url=$pictureManager->upload($url);
                            $url=$url['SiteHostedPictureDetails']['FullURL'];
                        }
                             
                        $variations['Pictures']['VariationSpecificPictureSet'][]=array(
                            'PictureURL'=>$url,
                            'VariationSpecificValue'=>$key
                        );
                    }
                }
            }
            $itemValues['variation']=$variations;
        }
        $r=$EIRI->apiCore($itemid,$itemValues);
        return $this->render('relistresult',array('result'=>$r));
    }
    
    /**
     * 补库存在线item
     * @author fanjs
     */
    public function actionBukucunset(){
        if(\Yii::$app->request->isPost){
//          AppTrackerApiHelper::actionLog('listing_ebay','/ebayitem/close');
            $item = EbayItem::findOne(['itemid'=>$_POST['itemid']]);
            return $this->renderPartial('bukucunset',['item'=>$item]);
        }
    }
    
    /**
     * 补库存设置入库
     * @author fanjs
     */
    public function actionAjaxBukucunset(){
        if(\Yii::$app->request->isPost){
            if (isset($_POST['itemid'])){
                $itemids = explode(',', $_POST['itemid']);
                $itemids = array_filter($itemids);
    //          $itemid=$_POST['itemid'];
                $msg = '';
                foreach ($itemids as $itemid){
                    $item = EbayItem::findOne(['itemid'=>$itemid]);
                    //拍卖不支持补库存
                    if($item->listingtype == 'Chinese'){
                        $msg .=$itemid.':拍卖商品不支持补库存<br>';
                    }
                    $item->bukucun = $_POST['bukucun'];
                    $item->less = $_POST['less'];
                    $item->bu = $_POST['bu'];
                    if ($item->save()){
                        $msg .= $itemid.':success<br>';
                    }else{
                        $msg .=$itemid.':存储失败<br>';
                    }
                }
                return $msg;
            }
        }else{
            return '错误的请求';
        }
    }
    
    /**
     * 批量设置补库存
     * @author fanjs
     */
    public function actionMltibukucunset(){
        if(\Yii::$app->request->isPost){
            return $this->renderPartial('mltibukucunset',['itemids'=>$_POST['itemids']]);
        }
    }
    
    /**
     * 给item添加汽配信息
     * @author fanjs
     */
    public function actionAddfitment(){
        $fitmentmuban = BaseFitmentmuban::find()->select(['id','name'])->all();
        $itemid = $_REQUEST['itemid'];
        return $this->renderPartial('addfitment',['fitmentmuban'=>$fitmentmuban,'itemid'=>$itemid]);
    }
    
    /**
     * 给item添加汽配信息的ebay接口api调用
     * @author fanjs
     */
    public function actionAjaxaddfitment(){
        if(\Yii::$app->request->isPost){
            $item = EbayItem::findOne(['itemid'=>$_POST['itemid']]);
            $ec = EbayCategory::findOne(['categoryid'=>$item->detail->primarycategory,'siteid'=>EbaySite::findOne(['site'=>$item->site])->siteid]);
            $fitment = BaseFitmentmuban::findOne(['id'=>$_POST['mubanid']])->itemcompatibilitylist;
            if (!is_null($ec) && $ec->iscompatibility==1){
                $eu = SaasEbayUser::findOne(['selleruserid'=>$item->selleruserid]);
                if(is_null($eu)){
                    return Json::encode(['ack'=>'failure','msg'=>'系统找不到对应的eBay账号授权,请确认已授权相应账号']);
                }
                $ri = new reviseitem();
                $ri->resetConfig($eu->listing_devAccountID);
                $ri->eBayAuthToken=$eu->listing_token;
                $result=$ri->revisecompatibility($item->itemid,$fitment);
                if ($result['Ack']=='Success'||$result['Ack']=='Warning'){
//                  $item->ibayset->hasfitment=1;
//                  $item->save();
                    //记录操作记录
                    EbayLogItem::Addlog('', \Yii::$app->user->identity->username, '添加汽配信息', $item->itemid, 'ItemId '.$item->itemid.'手动添加汽配信息', $result);
                    return Json::encode(['ack'=>'success']);
                }else{
                    return Json::encode(['ack'=>'failure','msg'=>$result['Errors']['LongMessage']]);
                }
            }else{
                return Json::encode(['ack'=>'failure','msg'=>'该item不支持汽配信息或系统找不到对应类目']);
            }
        }
    }
    
    /**
     * 批量修改在线Item之前选择操作字段
     * @author fanjs
     */
    public function actionMltichangeall(){
        return $this->renderPartial('mltichangeall',['itemid'=>$_REQUEST['itemid']]);
    }
    
    /**
     * 处理批量修改Item的实际数据
     * @author fanjs
     */
    public function actionMltichangealldata(){
        $itemids = explode(',', $_POST['itemid']);
        $itemids = array_filter($itemids);
        $itemids_str = implode(',', $itemids);
        $items = EbayItem::findAll(['itemid'=>$itemids]);
        $item_demo = EbayItem::findOne(['itemid'=>$itemids['1']]);
        $_sitetmp = $_categorytmp = $_listingtype = 1;
        foreach ($items as $one){
            if ($one->site != $item_demo->site){
                $_sitetmp +=1;
            }
            if ($one->detail->primarycategory != $item_demo->detail->primarycategory){
                $_categorytmp +=1;
            }
            if ($one->listingtype != $item_demo->listingtype){
                $_listingtype +=1;
            }
        }
        $qubie = ['sitetmp'=>$_sitetmp,'categorytmp'=>$_categorytmp,'listingtype'=>$_listingtype];
        $selectsite=EbaySite::findOne(['site'=>$item_demo->site]);
        if(is_array($selectsite->tax_jurisdiction)){
            $salestaxstate=Helper_Array::toHashmap($selectsite->tax_jurisdiction,'JurisdictionID','JurisdictionName');
        }else{
            $salestaxstate=null;
        }
        if(is_array($selectsite->dispatchtimemax)){
            $dispatchtimemax=Helper_Array::toHashmap($selectsite->dispatchtimemax,'DispatchTimeMax','Description');
        }else{
            $dispatchtimemax=null;
        }
        if(is_array($selectsite->payment_option)){
            $paymentoption=Helper_Array::toHashmap($selectsite->payment_option, 'PaymentOption','Description');
        }else{
            $paymentoption=null;
        }
        if(is_array($selectsite->return_policy)){
            $returnpolicy=$selectsite->return_policy;
        }else{
            $returnpolicy=null;
        }
        return $this->render('mltichangealldata',['itemid'=>$itemids_str,'setitems'=>$_POST['setitemvalues'],'demo'=>$item_demo,'qubie'=>$qubie,
                'locationarr'=>Helper_Array::toHashmap(Helper_Array::toArray(EbayCountry::findBySql('select * from ebay_country order by country asc')->all()),'country','description'),
                'salestaxstate'=>$salestaxstate,
                'dispatchtimemax'=>$dispatchtimemax,
                'paymentoption'=>$paymentoption,
                'return_policy'=>$returnpolicy
        ]);
    }
    
    /**
     * 处理批量修改Item的结果
     * @author fanjs
     */
    public function actionMltichangeresult(){
        if(\Yii::$app->request->isPost){
            $setitems = explode(',',$_POST['setitems']);
            $itemsid = explode(',',$_POST['itemid']);
            $items = EbayItem::findAll(['itemid'=>$itemsid]);
            
            //处理修改字段的数据
            $checkvalue = [];
            if (isset($_POST['itemtitle']) && strlen($_POST['itemtitle'])){
                $checkvalue['itemtitle'] = $_POST['itemtitle'];
            }
            if (isset($_POST['itemtitle2']) && strlen($_POST['itemtitle2'])){
                $checkvalue['subtitle'] = $_POST['itemtitle2'];
            }
            if (isset($_POST['primarycategory']) && strlen($_POST['primarycategory'])){
                $checkvalue['primarycategory'] = $_POST['primarycategory'];
            }
            if (isset($_POST['country']) && strlen($_POST['country'])){
                $checkvalue['country'] = $_POST['country'];
            }
            if (isset($_POST['location']) && strlen($_POST['location'])){
                $checkvalue['location'] = $_POST['location'];
            }
            if (isset($_POST['postalcode']) && strlen($_POST['postalcode'])){
                $checkvalue['postalcode'] = $_POST['postalcode'];
            }
            if (isset($_POST['listingduration']) && strlen($_POST['listingduration'])){
                $checkvalue['listingduration'] = $_POST['listingduration'];
            }
            if (isset($_POST['autopay']) && strlen($_POST['autopay'])){
                $checkvalue['autopay'] = $_POST['autopay'];
            }
            if (!isset($_POST['autopay']) && in_array('autopay', $setitems)){
                $checkvalue['autopay'] = false;
            }
            $checkvalue['shippingdetails'] = [];
            if (isset($_POST['dispatchtime']) && strlen($_POST['dispatchtime'])){
                $checkvalue['dispatchtime'] = $_POST['dispatchtime'];
            }
            if (isset($_POST['paymentmethods']) && is_array($_POST['paymentmethods'])){
                $checkvalue['paymentmethods'] = $_POST['paymentmethods'];
            }
            
            $api_results = [];
            foreach ($items as $item){
                $detail=$item->detail->shippingdetails;
                $checkvalue['shippingdetails'] = $detail;
                if (isset($detail['ShippingServiceOptions']) && isset($_POST['ShippingServiceCost']) && strlen($_POST['ShippingServiceCost'])>0 && isset($_POST['ShippingServiceAdditionalCost']) && strlen($_POST['ShippingServiceAdditionalCost'])>0){
                    if (isset($detail['ShippingServiceOptions']['ShippingService'])){
                        $_tmp = $detail['ShippingServiceOptions'];
                        $_tmp['ShippingServiceCost'] = $_POST['ShippingServiceCost'];
                        $_tmp['ShippingServiceAdditionalCost'] = $_POST['ShippingServiceAdditionalCost'];
                    }else{
                        $i = 0;
                        foreach ($detail['ShippingServiceOptions'] as $k=>$v){
                            if ($i == 0){
                                $v['ShippingServiceCost'] = $_POST['ShippingServiceCost'];
                                $v['ShippingServiceAdditionalCost'] = $_POST['ShippingServiceAdditionalCost'];
                            }
                            $detail['ShippingServiceOptions'][$k] = $v;
                            $i++;
                        }
                        $_tmp = $detail['ShippingServiceOptions'];
                    }
                    $checkvalue['shippingdetails'] = array_merge($checkvalue['shippingdetails'],['ShippingServiceOptions'=>$_tmp]);
                }
                if (isset($detail['InternationalShippingServiceOption']) && isset($_POST['inShippingServiceCost']) && strlen($_POST['inShippingServiceCost']) && isset($_POST['inShippingServiceAdditionalCost']) && strlen($_POST['inShippingServiceAdditionalCost'])){
                    if (isset($detail['InternationalShippingServiceOption']['ShippingService'])){
                        $_tmp = $detail['InternationalShippingServiceOption'];
                        $_tmp['ShippingServiceCost'] = $_POST['inShippingServiceCost'];
                        $_tmp['ShippingServiceAdditionalCost'] = $_POST['inShippingServiceAdditionalCost'];
                    }else{
                        $i = 0;
                        foreach ($detail['InternationalShippingServiceOption'] as $k=>$v){
                            if ($i == 0){
                                $v['ShippingServiceCost'] = $_POST['inShippingServiceCost'];
                                $v['ShippingServiceAdditionalCost'] = $_POST['inShippingServiceAdditionalCost'];
                            }
                            $detail['InternationalShippingServiceOption'][$k] = $v;
                            $i++;
                        }
                        $_tmp = $detail['InternationalShippingServiceOption'];
                    }
                    $checkvalue['shippingdetails'] = array_merge($checkvalue['shippingdetails'],['InternationalShippingServiceOption'=>$_tmp]);
                }
                if (isset($_POST['PaymentInstructions'])){
                    $checkvalue['shippingdetails'] = array_merge($checkvalue['shippingdetails'],['PaymentInstructions'=>$_POST['PaymentInstructions']]);
                }
                if (isset($_POST['shippingdetails']) && isset($_POST['shippingdetails']['ExcludeShipToLocation']) && is_array($_POST['shippingdetails']['ExcludeShipToLocation'])){
                    $checkvalue['shippingdetails'] += ['ExcludeShipToLocation'=>$_POST['shippingdetails']['ExcludeShipToLocation']];
                }
                if (isset($_POST['return_policy'])){
                    $checkvalue['return_policy'] = $_POST['return_policy'];
                }
                if (count($checkvalue['shippingdetails']) == 0){
                    unset($checkvalue['shippingdetails']);
                }
                $api = new reviseitem();
                $seller = SaasEbayUser::findOne(['selleruserid'=>$item->selleruserid]);
                if (empty($seller)){
                    $api_results[$item->itemid] = ['ack'=>'failure','msg'=>'can not find the token in database;'];
                    continue;
                }
                $api->resetConfig($seller->listing_devAccountID);
                $api->eBayAuthToken = $seller->listing_token;
                $result = $api->apiCore($item->itemid, $checkvalue);
                if ($result['Ack'] == 'Success' || $result['Ack'] == 'Warning'){
                    $api_results[$item->itemid] = ['ack'=>'success'];
                }
                if ($result['Ack'] == 'Failure'){
                    $msg = '';
                    $allmsg = [];
                    if (isset($result['Errors']['LongMessage'])){
                        $allmsg['0'] = $result['Errors'];
                    }else{
                        $allmsg = $result['Errors'];
                    }
                    foreach ($allmsg as $a){
                        $msg.=$a['LongMessage'];
                    }
                    $api_results[$item->itemid] = ['ack'=>'failure','msg'=>$msg];
                }
            }
            return $this->render('mltichangeresult',['result'=>$api_results]);
        }
    }
    
    /**
     * 添加促销规则
     * @author fanjs
     */
    public function actionAddpromotion(){
        $itemids = explode(',', $_POST['itemid']);
        $itemids = array_filter($itemids);
        $item_demo = EbayItem::findOne(['itemid'=>$itemids['1']]);
        $proms = EbayPromotion::find()->where(['selleruserid'=>$item_demo->selleruserid])->select(['id','promotionalsalename'])->asArray()->all();
        if (count($proms)){
            $proms_arr = Helper_Array::toHashmap($proms,'id','promotionalsalename');
        }else{
            $proms_arr = [];
        }
        $todoitem = implode(',',$itemids);
        return $this->renderPartial('addpromotion',['proms'=>$proms_arr,'itemids'=>$todoitem]);
    }
    
    /**
     * 添加促销规则接口调用
     * @author fanjs
     */
    public function actionAjaxaddpromotion(){
        if (\Yii::$app->request->isPost){
            $itemids = explode(',', $_POST['itemids']);
            $prom = EbayPromotion::findOne($_POST['promid']);
            $selleruserid = SaasEbayUser::findOne(['selleruserid'=>$prom->selleruserid]);
            $api = new setpromotionalsalelistings();
            $api->resetConfig($selleruserid->listing_devAccountID);
            $api->eBayAuthToken = $selleruserid->listing_token;
            $result = $api->api('Add',$prom->promotionalsaleid,$itemids);
            if ($api->responseIsFailure()){
                return $result['Errors']['LongMessage'];
            }else{
                return 'success';
            }
        }
    }
    
    /**
     * 添加促销规则,验证提交的itemid是否有问题
     * @author fanjs
     */
    public function actionAddpromotionverify(){
        $itemids = explode(',', $_POST['itemid']);
        $itemids = array_filter($itemids);
        $items = EbayItem::findAll(['itemid'=>$itemids]);
        $item_demo = EbayItem::findOne(['itemid'=>$itemids['1']]);
        foreach ($items as $one){
            if ($one->selleruserid != $item_demo->selleruserid){
                return '请选择同一卖家的刊登进行处理';
            }
        }
        return 'success';
    }
    
    /**
     * 批量删除下架的item
     * @author fanjs
     */
    public function actionMltidel(){
        $itemids = explode(',', $_POST['itemid']);
        $itemids = array_filter($itemids);
        $items = EbayItem::findAll(['itemid'=>$itemids]);
        EbayItem::deleteAll(['itemid'=>$itemids]);
        return 'success';
    }
    
    public function actionTest(){
//      $error= '';$sku = 'SKESFIDCPO0181A';
//      $qfs = EbayitemHelper::getQuantityForSale('151298416843',1, '123',$sku,$error,true);
//      echo $qfs;

        $ei = EbayItem::findOne(['itemid'=>'110155811391']);var_dump($ei);
    }
}
