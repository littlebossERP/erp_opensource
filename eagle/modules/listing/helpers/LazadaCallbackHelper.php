<?php
namespace eagle\modules\listing\helpers;

use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use \Yii;
use \Exception;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelper;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\util\helpers\TimeUtil;

/**
 * +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行feed，或产品等接口等数据回填
 * +------------------------------------------------------------------------------
 */
class LazadaCallbackHelper
{

    const LOG_ID = "eagle-modules-listing-helpers-LazadaCallbackHelper";

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * 刊登feed 结果回填
     *
     * @param array $params
     * array(
     *        puid=>34,
     *        feedId=>17397a63-9375-4cd7-a8ac-fc468c53a920 ,
     *        totalRecords=>4,
     *        failReport=>array( "zhitian-CS8514102609A "=>"Field Brand with valuety",  "zhitian-CS8514102609B "=>"Field 433Brand with valuety",)
     * )
     */
    public static function productCreate($params = array())
    {
        if (!isset($params['puid'])) {
            self::localLog("productCreate 参数缺少puid", LogType::ERROR);
            return false;
        }

        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " productCreate 参数缺少feedId", LogType::ERROR);
            return false;
        }

        $nowTime = time();
        $publishListings = LazadaPublishListing::find()->where(['feed_id' => $params['feedId']])->all();
        
        foreach ($publishListings as $publishListing) {
            $uploadedProductSkus = array();
            $recordUpSkus = array();
            $variantData = json_decode($publishListing->variant_info, true);
            $hasError = false;
            $parentSku = "";
            $parentSkuIsUploadSuccess = true;
            $errorInfo = array();
            foreach ($variantData as $oneVariant) {
                // 由于并没有保存刊登产品的ParentSku而是以第一个产品为parent 所以这里要判断ParentSku是否刊登成功要找回ParentSku
                if ("" == $parentSku)
                    $parentSku = $oneVariant['SellerSku'];

                if (!empty($params['failReport']) && is_array($params['failReport']) && array_key_exists($oneVariant['SellerSku'], $params['failReport'])) {
                    $hasError = true;
                    if ($oneVariant['SellerSku'] == $parentSku) $parentSkuIsUploadSuccess = false;
                    if (!empty($errorInfo[$oneVariant['SellerSku']])) {
                        $errorInfo[$oneVariant['SellerSku']] = $errorInfo[$oneVariant['SellerSku']] . $params['failReport'][$oneVariant['SellerSku']];
                    } else {
                        $errorInfo[$oneVariant['SellerSku']] = $params['failReport'][$oneVariant['SellerSku']];
                    }
                } else if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {// dzt20160122 lazada api 原因导致全部失败，需重新操作
                    $hasError = true;
                    $errorInfo = array('平台接口问题导致操作失败，请重新发布此商品。');//一个$publishListing 只要记录一个结果就ok 
                } else {
                    $uploadedProductSkus[] = $oneVariant['SellerSku'];
                }
            }
            if($hasError){
                self::localLog("productCreate hasError :".print_r($errorInfo,true));
            }
            
            if (!empty($publishListing->uploaded_product)) {// 记录已经刊登到后台的sku
                $recordUpSkus = json_decode($publishListing->uploaded_product, true);
                foreach ($uploadedProductSkus as $uploadedProductSku) {
                    if (!in_array($uploadedProductSku, $recordUpSkus)) {// 去重复
                        $recordUpSkus[] = $uploadedProductSku;
                    }
                }
            } else if ($parentSkuIsUploadSuccess) {
                // 父产品刊登失败，其他产品不能也不会刊登成功，但feed report 只返回一个sku刊登失败导致其他产品被标为刊登成功，从而再次发布依然失败
                $recordUpSkus = $uploadedProductSkus;
            }

            if (!empty($uploadedProductSkus)) {// 如果没有就不要把 空数组的json 写入字段了，直接为空，这样删除刊登都比较方便，直接判断字段为空。
                $publishListing->uploaded_product = json_encode($recordUpSkus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            }

            if (!$publishListing->save()) {
                self::localLog('puid:' . $params['puid'] . " productCreate LazadaPublishListing状态写入已上传sku失败：" . print_r($publishListing->errors, true), LogType::ERROR);
                return false;
            }

            // 如果这个$publishListing 里面的sku 都不在$params['failReport']的sku里面，这个$publishListing就算刊登成功
            if ($hasError == false) {
                self::localLog("productCreate puid:" . $params['puid'] . " feedId:" . $params['feedId'] . " has no error change state:" . LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, LogType::ERROR);
                SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][0]);

                // 更新  图片上传的请求情况，由于变参产品只需提交一个请求就可以上传 所有产品的图片，所以这里是需要所有产品都 create 完成才能
                $userBgControl = UserBackgroundJobControll::find()->where(["puid" => $params['puid'], "job_name" => LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME])->one();
                if ($userBgControl === null) {
                    $userBgControl = new UserBackgroundJobControll;
                    $userBgControl->puid = $params['puid'];
                    $userBgControl->job_name = LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME;
                    $userBgControl->create_time = $nowTime;
                    $userBgControl->execution_request = 1;
                } else {
                    self::localLog("productCreate execution_request:".$userBgControl->execution_request." +1");
                    $userBgControl->execution_request = $userBgControl->execution_request + 1;
                }
                $userBgControl->update_time = $nowTime;
                $userBgControl->save(false);
                self::localLog("productCreate userBgControl saved");

            } else {
                self::localLog("productCreate puid:" . $params['puid'] . " feedId:" . $params['feedId'] . " has some error:" . implode(".", $errorInfo));
                SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][2], implode("<br />", $errorInfo));

            }
        }

        return true;
    }


    public static function productCreateV2($params = array())
    {
        self::localLog("productCreateV2 start");
        if (!isset($params['puid'])) {
            self::localLog("productCreate 参数缺少puid", LogType::ERROR);
            return false;
        }
        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " productCreate 参数缺少feedId", LogType::ERROR);
            return false;
        }

        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $cursor = $lazadaPublishListingManager->find(array("feedId" => $params['feedId']));
        foreach ($cursor as $lazadaPublishListing) {
            $sellerSku = $lazadaPublishListing['sellerSku'];
            if (!empty($params['failReport']) && is_array($params['failReport']) && array_key_exists($sellerSku, $params['failReport'])) {
                $errorInfo = $params['failReport'][$sellerSku];
            } else if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {// dzt20160122 lazada api 原因导致全部失败，需重新操作
                $errorInfo = array('平台接口问题导致操作失败，请重新发布此商品。');//一个$publishListing 只要记录一个结果就ok 
            }
            if (isset($errorInfo)) {
                self::localLog("productCreate puid:" . $params['puid'] . " feedId:" . $params['feedId'] . " has some error:" . $errorInfo);
                LLJHelper::changePublishListStateV2(array($lazadaPublishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][2], $errorInfo);
                if ($lazadaPublishListing["isParentSku"]) {
                    $childSkus = array();
                    foreach ($lazadaPublishListing["childSku"] as $childSku) {
                        $childSkus[] = $childSku['sku'];
                    }
                    $q = array("lazadaUid" => $lazadaPublishListing["lazadaUid"], "sellerSku" => array('$in' => $childSkus));
                    LLJHelper::changePublishListSateByCondition($q, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][8], "parent error==>" . $errorInfo);
                }
            } else {
                self::localLog("productCreate puid:" . $params['puid'] . " feedId:" . $params['feedId'] . " has no error change state:" . LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, LogType::ERROR);
                LLJHelper::changePublishListStateV2(array($lazadaPublishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][0]);
                //父产品上传成功后,刊登子产品,如果不是父产品,则不用上传图片步骤
                if ($lazadaPublishListing["isParentSku"]) {
                    LazadaLinioJumiaProductFeedHelperV2::productPublish(array($lazadaPublishListing['_id']->{'$id'}));
                } else {
                    continue;
                }
                // 更新  图片上传的请求情况，由于变参产品只需提交一个请求就可以上传 所有产品的图片，所以这里是需要所有产品都 create 完成才能
                $userBgControl = UserBackgroundJobControll::find()->where(["puid" => $params['puid'], "job_name" => LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME])->one();
                if ($userBgControl === null) {
                    $userBgControl = new UserBackgroundJobControll;
                    $userBgControl->puid = $params['puid'];
                    $userBgControl->job_name = LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME;
                    $userBgControl->create_time = time();
                    $userBgControl->execution_request = 1;
                } else {
                    self::localLog("productCreate execution_request +1");
                    $userBgControl->execution_request = $userBgControl->execution_request + 1;
                }
                $userBgControl->update_time = time();
                $userBgControl->save(false);
                self::localLog("productCreate userBgControl saved");
            }
        }
        return true;
    }
    
    /**
     * 刊登结果回填
     *
     * @param array $params
     * array(
     *        puid=>34,
     *        publishListingIds=>array(1,2,3),
     *        errors=>array(0=>array( [SellerSku] => hahaha , [Field] => __images__, [Message] => The image is invalid),...)
     * )
     */
    public static function productCreateV3($params = array()) {
        if (!isset($params['puid'])) {
            self::localLog("productCreate 参数缺少puid", LogType::ERROR);
            return false;
        }
    
        $nowTime = time();
        $publishListing = LazadaPublishListing::findOne($params['publishListingId']);
        
        $mapErrors = array();
        $allSkuErrMsg = "";
        // dzt20170207 lazada返回$params['errors']数组有时有 sku 有时没有sku ，不清楚会不会出现有夹杂着有些有sku 有些没有
        // 现在暂时对没有sku的报错直接判定为整个 产品刊登报错
        if(!empty($params['errors'])){
            if(is_array($params['errors'])){
                foreach ($params['errors'] as $error){
                    if(!empty($error['SellerSku'])){
                        if(empty($mapErrors[$error['SellerSku']]))
                            $mapErrors[$error['SellerSku']] = "[SellerSku] => ".$error['SellerSku']."[Field] => ".$error['Field'].", [Message] => ".$error['Message'].".";
                        else
                            $mapErrors[$error['SellerSku']] .= "[Field] => ".$error['Field'].", [Message] => ".$error['Message'].".";
                    }else{
                        $allSkuErrMsg .= "[Field] => ".$error['Field'].", [Message] => ".$error['Message'].".";
                    }
                }
            }else{
                $allSkuErrMsg = $params['errors'];
            }
            
        }
        
        $uploadedProductSkus = array();
        $recordUpSkus = array();
        $variantData = json_decode($publishListing->variant_info, true);
        $hasError = false;
        $errorInfo = array();
        if($allSkuErrMsg <> ''){
            $hasError = true;
            $errorInfo[] = $allSkuErrMsg;
        }else{
            foreach ($variantData as $oneVariant) {
                if (!empty($mapErrors) && array_key_exists($oneVariant['SellerSku'], $mapErrors)) {
                    $hasError = true;
                    if (!empty($errorInfo[$oneVariant['SellerSku']])) {
                        $errorInfo[$oneVariant['SellerSku']] = $errorInfo[$oneVariant['SellerSku']] . $mapErrors[$oneVariant['SellerSku']];
                    } else {
                        $errorInfo[$oneVariant['SellerSku']] = $mapErrors[$oneVariant['SellerSku']];
                    }
                } else {
                    $uploadedProductSkus[] = $oneVariant['SellerSku'];
                }
            }  
        }
        
        if (!empty($publishListing->uploaded_product)) {// 记录已经刊登到后台的sku
            $recordUpSkus = json_decode($publishListing->uploaded_product, true);
            foreach ($uploadedProductSkus as $uploadedProductSku) {
                if (!in_array($uploadedProductSku, $recordUpSkus)) {// 去重复
                    $recordUpSkus[] = $uploadedProductSku;
                }
            }
        } else {
            // 父产品刊登失败，其他产品不能也不会刊登成功，但feed report 只返回一个sku刊登失败导致其他产品被标为刊登成功，从而再次发布依然失败
            $recordUpSkus = $uploadedProductSkus;
        }

        // 如果没有就不要把 空数组的json 写入字段了，直接为空，这样删除刊登都比较方便，直接判断字段为空。
        // 由于新接口只支持一个个产品上传，所以一个sku出现报错，其他变参应该都不能上传成功
        if ($hasError == false && !empty($uploadedProductSkus)) {
            $publishListing->uploaded_product = json_encode($recordUpSkus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }

        if (!$publishListing->save()) {
            self::localLog('puid:' . $params['puid'] . " productCreateV3 LazadaPublishListing状态写入已上传sku失败：" . print_r($publishListing->errors, true), LogType::ERROR);
            return false;
        }

        // 如果这个$publishListing 里面的sku 都不在$params['failReport']的sku里面，这个$publishListing就算刊登成功
        if ($hasError == false) {
            self::localLog("productCreateV3 puid:" . $params['puid'] . ",publishListingId:" . $publishListing->id . " has no error.Change state:" . LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, LogType::INFO);
            SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][0]);

            // 更新  图片上传的请求情况，由于变参产品只需提交一个请求就可以上传 所有产品的图片，所以这里是需要所有产品都 create 完成才能
            $userBgControl = UserBackgroundJobControll::find()->where(["puid" => $params['puid'], "job_name" => LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME])->one();
            if ($userBgControl === null) {
                $userBgControl = new UserBackgroundJobControll;
                $userBgControl->puid = $params['puid'];
                $userBgControl->job_name = LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME;
                $userBgControl->create_time = $nowTime;
                $userBgControl->execution_request = 1;
            } else {
                $userBgControl->execution_request = $userBgControl->execution_request + 1;
                self::localLog("productCreateV3 execution_request +1,now:".$userBgControl->execution_request);
            }
            $userBgControl->update_time = $nowTime;
            $userBgControl->save(false);
            self::localLog("productCreateV3 userBgControl saved");

        } else {
            self::localLog("productCreateV3 puid:" . $params['puid'] . ",publishListingId:" . $publishListing->id . " has some error:" . implode(".", $errorInfo));
            SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][2], implode("<br />", $errorInfo));

        }
    
        return true;
    }

    /**
     * 图片upload feed 结果回填
     *
     * @param array $params
     * array(
     *        puid=>34,
     *        feedId=>17397a63-9375-4cd7-a8ac-fc468c53a920 ,
     *        totalRecords=>4,
     *        failReport=>array( "zhitian-CS8514102609A "=>"Field Brand with valuety",  "zhitian-CS8514102609B "=>"Field 433Brand with valuety",)
     * )
     *
     * @return  array(true,array(sellersku1,sku2....) )  虽然一个图片上的feed只包含1个sku，但是 这里返回该sku对应的所有的子商品sku 这里sellersku可能为""
     */
    public static function imageUpload($params = array())
    {
        if (!isset($params['puid'])) {
            self::localLog("imageUpload 参数缺少puid", LogType::ERROR);
            return array(false, "");
        }

        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " imageUpload 参数缺少feedId", LogType::ERROR);
            return array(false, "");
        }

        // 目前我们eagle控制图片是一个feed一个sku的图片上传，所以这里按道理 返回错误的话 只有一个sku
        // 但是这里要判断 无论sku是父的sku还是子的sku，错误都要记录到同一个LazadaPublishListing object里
        $selllerSku = array();
        $publishListing = LazadaPublishListing::findOne(['feed_id' => $params['feedId']]);

        // 产品已被删除 或 可能是改feed id的产品被其他feed id覆盖了
        if (empty($publishListing)) {
            self::localLog("imageUpload callback: 相应的LazadaPublishListing 已经被删除。" . print_r($params, true), LogType::ERROR);
            return array(true, "");
        }

        $variantData = json_decode($publishListing->variant_info, true);
        $hasError = false;

        foreach ($variantData as $oneVariant) {
            $selllerSku[] = $oneVariant['SellerSku'];

            //因图片 upload 只使用一个sku 去上传，其他sku的图片会同时修改，所以这里在for $variantData 里面设置错误信息不会重复设置。因为failReport sku 只有一个
            if (isset($params['failReport']) && is_array($params['failReport'])) {
                if (array_key_exists($oneVariant['SellerSku'], $params['failReport'])) {
                    $hasError = true;
                    LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                        LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][5], $params['failReport'][$oneVariant['SellerSku']]);
                }
            }
        }

        // dzt20160122
        if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {
            $hasError = true;
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][5], "平台接口问题导致操作失败，请重新发布此商品。");
        }

        if($hasError){
            self::localLog("imageUpload hasError ..");
        }
        if ($hasError == false) {
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED][0]);
            return array(true, $selllerSku);
        } else {
            return array(true, "");
        }

    }

    public static function imageUploadV2($params = array())
    {
        if (!isset($params['puid'])) {
            self::localLog("imageUpload 参数缺少puid", LogType::ERROR);
            return array(false, "");
        }

        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " imageUpload 参数缺少feedId", LogType::ERROR);
            return array(false, "");
        }
        //TODO 先查看imageUpload逻辑
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $lazadaPublishListing = $lazadaPublishListingManager->findOne(array("feedId" => $params['feedId']));
        if (empty($lazadaPublishListing)) {
            self::localLog("imageUpload callback: 相应的LazadaPublishListing 已经被删除。" . print_r($params, true), LogType::ERROR);
            return array(true, "");
        }
        $hasError = false;
        $sellerSku = $lazadaPublishListing['sellerSku'];
        if (isset($params['failReport']) && is_array($params['failReport'])) {
            if (array_key_exists($sellerSku, $params['failReport'])) {
                $hasError = true;
                LLJHelper::changePublishListStateV2(array($lazadaPublishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][5], $params['failReport'][$sellerSku]);
            }
        }
        if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {
            $hasError = true;
            LLJHelper::changePublishListStateV2(array($lazadaPublishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][5], "平台接口问题导致操作失败，请重新发布此商品。");
        }
        if ($hasError == false) {
            LLJHelper::changePublishListStateV2(array($lazadaPublishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED][0]);
            return array(true, $sellerSku);
        } else {
            return array(true, "");
        }
    }

    /**
     * 记录产品转为人工审核状态 .getProduct filter:pending 结果回填
     *
     * @param array $params
     * array(
     *    puid=>34,
     *    feedId=>17397a63-9375-4cd7-a8ac-fc468c53a920 ,
     *    skuPendingMap=>array("sku1"=>0,"sku2"=>1....)   //0---表示不是pending，1----是pending
     *    )
     */
    public static function markPendingProduct($params = array())
    {
        if (!isset($params['puid'])) {
            self::localLog("markPendingProduct 参数缺少puid", LogType::ERROR);
            return false;
        }

        // 需要feedId 以更好地get LazadaPublishListing object 
        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " markPendingProduct 参数缺少feedId", LogType::ERROR);
            return false;
        }

        // 由于这个feedId 是图片上传的feedId ，所以这里一般只包含一个Sku
        // @todo 有一个问题是， 变参产品的一个产品是 rejected or else的非成功状态， 而一个产品的状态是pending ，这里我们该如何表达
        // @todo 目前是有一个pending就会认为成功 ，再来一次没有pending就会改为不成功。就看变参产品触发callback markPendingProduct的先后 看谁覆盖谁。
        $publishListing = LazadaPublishListing::findOne(['feed_id' => $params['feedId']]);

        // 产品已被删除 或 可能是改feed id的产品被其他feed id覆盖了
        if (empty($publishListing)) {
            self::localLog("imageUpload callback: 相应的LazadaPublishListing 已经被删除。" . print_r($params, true));
            return array(true, "");
        }

        $pendingSku = array();
        $notPendingSku = array();

        $variantData = json_decode($publishListing->variant_info, true);
        foreach ($variantData as $oneVariant) {

            if (array_key_exists($oneVariant['SellerSku'], $params['skuPendingMap'])) {
                if (1 == $params['skuPendingMap'][$oneVariant['SellerSku']]) {
                    $pendingSku[] = $oneVariant['SellerSku'];
                } else {
                    $notPendingSku[] = $oneVariant['SellerSku'];
                }
            } else {// 按道理不应该进入这里
                self::localLog('markPendingProduct 接口参数包含 同一个image upload feed产品的所有变参sku , 但是sku:' . $oneVariant['SellerSku'] . "不在参数里面。");
            }
        }

        if (!empty($pendingSku)) {
            if (count($notPendingSku) == 0) {
                $info = "sku:" . implode(",", $pendingSku) . "等待人工审核中";
            } else {
                $info = "sku:" . implode(",", $pendingSku) . "等待人工审核中 , sku:" . implode(",", $notPendingSku) . "未进入人工审核，请进入卖家平台查看详细问题。";
            }
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0], $info);
        } else {
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][6], "产品无法转入人工审核状态，请进入卖家平台查看产品问题。");
        }

        return true;
    }

    public static function markPendingProductV2($params = array())
    {
        if (!isset($params['puid'])) {
            self::localLog("markPendingProduct 参数缺少puid", LogType::ERROR);
            return false;
        }

        // 需要feedId 以更好地get LazadaPublishListing object 
        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " markPendingProduct 参数缺少feedId", LogType::ERROR);
            return false;
        }
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $lazadaPublishListing = $lazadaPublishListingManager->findOne(array("feedId" => $params["feedId"]));
        if (empty($lazadaPublishListing)) {
            self::localLog("imageUpload callback: 相应的LazadaPublishListing 已经被删除。" . print_r($params, true));
            return array(true, "");
        }
        $pendingSku = array();
        $notPendingSku = array();
        foreach ($lazadaPublishListing["childSku"] as $childSku) {
            if (array_key_exists($childSku, $params['skuPendingMap'])) {
                if (1 == $params['skuPendingMap'][$childSku]) {
                    $pendingSku[] = $childSku;
                } else {
                    $notPendingSku[] = $childSku;
                }
            } else {// 按道理不应该进入这里
                self::localLog('markPendingProduct 接口参数包含 同一个image upload feed产品的所有变参sku , 但是sku:' . $childSku . "不在参数里面。");
            }
        }

        $pending = self::getProductsBySku($pendingSku, $lazadaPublishListing["lazadaUid"]);
        $notPending = self::getProductsBySku($notPendingSku, $lazadaPublishListing["lazadaUid"]);

        if (!empty($pending)) {
            LLJHelper::changePublishListStateV2($pending, LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0], "等待人工审核中。");
            if (!empty($notPending)) {
                LLJHelper::changePublishListStateV2($notPending, LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0], "未进入人工审核，请进入卖家平台查看详细问题。");
            }
        } else {
            LLJHelper::changePublishListStateV2($lazadaPublishListing, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][6], "产品无法转入人工审核状态，请进入卖家平台查看产品问题。");
        }
    }

    /**
     * 记录产品转为人工审核状态 .getQcStatus pending结果回填
     *
     * @param obj $publishListing
     * @param array $qcStatus
     */
    public static function markPendingProductV3($publishListing,$qcStatus)
    {
        if(empty($publishListing)){
            return array(false,'参数$publishListing 数据异常');
        }
        
        $qcInfoMap = array();
        $pendingSku = array();
        $notPendingInfo = '';
        foreach ($qcStatus as $qcStatu){
            if(isset($qcStatu['Status'])){//旧接口
                if($qcStatu['Status'] == 'pending'){
                    $pendingSku[] = $qcStatu['SellerSKU'];
                }else{
                    $notPendingInfo .=  "SellerSKU：".$qcStatu['SellerSKU'].",Status:".$qcStatu['Status'].",Reason:".$qcStatu['Reason'].";";
                }
            }else if(isset($qcStatu['status'])){//新接口
                if($qcStatu['status'] == 'pending'){
                    $pendingSku[] = $qcStatu['seller_sku'];
                }else{
                    $notPendingInfo .=  "SellerSKU：".$qcStatu['seller_sku'].",Status:".$qcStatu['status'];
                    if(!empty($qcStatu['reason'])){
                        $notPendingInfo .= ",Reason:".$qcStatu['reason'].";";
                    }
                    
                }
            }
            
        }
    
        if (empty($notPendingInfo)) {
            $info = "等待人工审核中 sku:" . implode(",", $pendingSku);
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0], $info);
        } else {
            if(empty($pendingSku)){
                $info = "产品未进入人工审核:" . $notPendingInfo;
            }else{
                $info = "等待人工审核中 sku:" . implode(",", $pendingSku) . " , 未进入人工审核:" . $notPendingInfo;
            }
            
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                    LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][9], $info);
        }
    
        return true;
    }
    
    private static function getProductsBySku($skus = array(), $lazadaUid)
    {
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $cursor = $lazadaPublishListingManager->find(array("lazadaUid" => $lazadaUid, "sellerSku" => array('$in' => $skus)));
        $rtn = array();
        foreach ($cursor as $doc) {
            $rtn[] = $doc;
        }
    }

    /**
     * 记录产品转为人工审核失败状态  getProduct filter:rejected 结果回填 @todo 发布完成后需要测试
     *
     * @param array $params
     * array(
     *     "puid"=>23,
     *  "needChangeDB"=>1,  // 1---需要change db，0不需要。默认为1
     *    “rejectedReport”=>array("sku1"=>"fail message 1","344-4545"=>"fail 2")   //至少有1个sku
     *)
     */
    public static function markRejectedProduct($params = array())
    {

        $status = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0];//待人工审核
        $publishListings = LazadaPublishListing::find()->where(['state' => LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE, 'status' => $status])->all();
        foreach ($publishListings as $publishListing) {
            $variantData = json_decode($publishListing->variant_info, true);
            foreach ($variantData as $oneVariant) {
                if (array_key_exists($oneVariant['SellerSku'], $params['rejectedReport'])) {
                    SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,
                        LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][7], $params['rejectedReport'][$oneVariant['SellerSku']], "人工审核失败");
                }
            }
        }

        return true;
    }

    /**
     * 记录产品转为线上状态  getProduct filter:live 结果回填 @todo 发布完成后需要测试
     *
     * @param array $params
     *    array(
     *          "puid"=>23,
     *           "needChangeDB"=>0,
     *        “skuList”=>array("sku1","sku2")   //至少有1个sku
     *    )
     */
    public static function markLiveProduct($params = array())
    {
        $status = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][0];//待人工审核
        $publishListings = LazadaPublishListing::find()->where(['state' => LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE, 'status' => $status])->all();
        foreach ($publishListings as $publishListing) {
            $variantData = json_decode($publishListing->variant_info, true);
            foreach ($variantData as $oneVariant) {
                if (in_array($oneVariant['SellerSku'], $params['skuList'])) {
                    SaasLazadaAutoSyncApiHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE,
                        LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE][1], "人工审核完成");
                }
            }
        }

        return true;
    }


    /**
     * 产品修改 feed 结果回填
     * 注意： 产品 修改中 状态去除在这里完成，但产品修改后的信息的更新需要等到 后台更新产品信息完成
     *
     * @param array $params
     * array(
     *        puid=>34,
     *        feedId=>17397a63-9375-4cd7-a8ac-fc468c53a920 ,
     *        totalRecords=>4,
     *        failReport=>array( "zhitian-CS8514102609A "=>"Field Brand with valuety",  "zhitian-CS8514102609B "=>"Field 433Brand with valuety",)
     * )
     * @param array $config
     */
    public static function productUpdate($params = array(), $config)
    {
        if (!isset($params['puid'])) {
            Yii::error("productUpdate 参数缺少puid", "file");
            return false;
        }

        if (!isset($params['feedId'])) {
            Yii::error('puid:' . $params['puid'] . " productUpdate 参数缺少feedId", "file");
            return false;
        }

 
        $nowTime = time();
        // dzt20160119  comment for 更新成功马上更新产品信息,多产品的情况下updateAll比较难使用
// 		if(empty($params['failReport'])){
// 			$update = array();
// 			// 修改状态 
// 			$update['error_message'] = "";
// 			$update['is_editing'] = 0;
// 			$affectRows = LazadaListing::updateAll($update,['feed_id'=>$params['feedId']]);
// 			if($affectRows <= 0){
// 				Yii::error('puid:'.$params['puid']." productUpdate LazadaListing状态更新失败","file");
// 				return false;
// 			}
// 			return true;
// 		}

        // dzt20160122 lazada api 原因导致全部失败，需重新操作
        if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {
            $update = array();
            // 修改状态
            $update['error_message'] = "平台接口问题导致操作失败，请重新发布此商品。";
            $update['is_editing'] = 0;
            $update['update_time'] = $nowTime;
            $affectRows = LazadaListing::updateAll($update, ['feed_id' => $params['feedId']]);
            if ($affectRows <= 0) {
                Yii::error('puid:' . $params['puid'] . " productUpdate LazadaListing状态更新失败", "file");
                return false;
            }
            return true;
        }

        $listings = LazadaListing::find()->where(['feed_id' => $params['feedId']])->all();
        foreach ($listings as $listing) {
            // 修改状态 
            $listing->is_editing = 0;// 注意： 这里feed执行成功，但产品信息并未更新完，即批量修改尽管已经成功，但是新的产品信息还没同步到eagle,但这里也解除 修改中 状态
            $listing->update_time = $nowTime;
            if (!empty($params['failReport']) && array_key_exists($listing->SellerSku, $params['failReport'])) {
                $listing->error_message = $params['failReport'][$listing->SellerSku];
            } else {
                $apiReqParams = array(
                    "SkuSellerList" => $listing->SellerSku,
                );
                \Yii::info("LazadaInterface_Helper::getProducts before reqParams:" . json_encode($apiReqParams), "file");
                $result = LazadaInterface_Helper::getProducts($config, $apiReqParams);
                // \Yii::info("LazadaInterface_Helper::getProducts result:" . json_encode($result, true), "file");
				\Yii::info("LazadaInterface_Helper::getProducts:".$listing->SellerSku." success", "file");
                if ($result["success"] === false or !isset($result["response"]) or !isset($result["response"]["products"])) {
                    // 获取产品失败
                    Yii::error("productUpdate getProducts fail. apiReqParams:" . json_encode($apiReqParams), "file");
                } else {
                    if (count($result["response"]["products"]) == 1) {// 保险起见
                        foreach ($result["response"]["products"] as $updatedPdInfo) {
                            if ($updatedPdInfo["SalePrice"] === "") $updatedPdInfo["SalePrice"] = 0;
                            if (empty($updatedPdInfo["Price"])) $listingInfo["Price"] = 0;

                            if (!empty($updatedPdInfo["SaleStartDate"])) {
                                $updatedPdInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($updatedPdInfo["SaleStartDate"]);
                            } else
                                $updatedPdInfo["SaleStartDate"] = 0;

                            if (!empty($updatedPdInfo["SaleEndDate"])) {
                                $updatedPdInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($updatedPdInfo["SaleEndDate"]);
                            } else
                                $updatedPdInfo["SaleEndDate"] = 0;

                            if (empty($updatedPdInfo["FulfillmentBySellable"]))
                                $updatedPdInfo["FulfillmentBySellable"] = 0;

                            if (empty($updatedPdInfo["FulfillmentByNonSellable"]))
                                $updatedPdInfo["FulfillmentByNonSellable"] = 0;

                            if (empty($updatedPdInfo["ReservedStock"]))
                                $updatedPdInfo["ReservedStock"] = 0;

                            if (empty($updatedPdInfo["RealTimeStock"]))
                                $updatedPdInfo["RealTimeStock"] = 0;
                            $listing->setAttributes($updatedPdInfo);
                        }
                    }
                }

                $listing->error_message = "";
            }

            if (!$listing->save()) {
                Yii::error("productUpdate listing->save fail:" . print_r($listing->errors, true), "file");
            }
        }

        return true;
    }


    public static function productUpdateV2($params = array(), $config)
    {
        self::localLog("productUpdateV2 start...");
        if (!isset($params['puid'])) {
            self::localLog("productUpdate 参数缺少puid", LogType::ERROR);
            return false;
        }

        if (!isset($params['feedId'])) {
            self::localLog('puid:' . $params['puid'] . " productUpdate 参数缺少feedId", LogType::ERROR);
            return false;
        }
        $lazadaListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
        // dzt20160122 lazada api 原因导致全部失败，需重新操作
        if (!empty($params['failReport']) && is_string($params['failReport']) && "lazada api error" == $params['failReport']) {
            $errorMsg = "平台接口问题导致操作失败，请重新发布此商品。";
            $isEditing = 0;
            return $lazadaListingManager->update(array("feedId" => $params['feedId']), array('$set' => array("errorMsg" => $errorMsg, "isEditing" => $isEditing, "updateTime" => time())), array("multiple" => true));
        }
        $cursor = $lazadaListingManager->find(array("feedId" => $params['feedId']));
        foreach ($cursor as $lazadaListing) {
            $isEditing = 0;
            $sellerSku = $lazadaListing['sellerSku'];
            if (!empty($params['failReport']) && array_key_exists($lazadaListing['SellerSku'], $params['failReport'])) {
                $lazadaListingManager->findAndModify(array("_id" => $lazadaListing["_id"]), array('$set' => array("errorMsg" => $params['failReport'][$sellerSku], "updateTime" => time(), "isEditing" => $isEditing)));
            } else {
                $apiReqParams = array(
                    "SkuSellerList" => $sellerSku,
                );
                self::localLog("productUpdateV2 start and reqParams:" . json_encode($apiReqParams));
                $result = LazadaInterface_Helper::getProducts($config, $apiReqParams);
                self::localLog("productUpdateV2 end and result:" . json_encode($result, true));
                if ($result["success"] === false or !isset($result["response"]) or !isset($result["response"]["products"])) {
                    // 获取产品失败
                    self::localLog("productUpdate getProducts fail. apiReqParams:" . json_encode($apiReqParams), LogType::ERROR);
                } else if (count($result["response"]["products"]) == 1) {
                    $updatedPdInfo = $result["response"]["products"][0];
                    $lazadaListingManager->findAndModify(
                        array("_id" => $lazadaListing["_id"]),
                        array('$set' => array("product" => $updatedPdInfo, "isEditing" => $isEditing)));
                }
            }
        }
        return true;
    }
}

?>