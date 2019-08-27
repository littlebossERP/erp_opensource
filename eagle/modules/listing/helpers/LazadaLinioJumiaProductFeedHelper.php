<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/24
 * Time: 下午4:58
 */

namespace eagle\modules\listing\helpers;

use common\api\lazadainterface\LazadaInterface_Helper;
use common\api\lazadainterface\LazadaProxyConnectHelper;
use common\helpers\Helper_Array;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\models\LazadaFeedList;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\models\UserBackgroundJobControll;

/**
 * Class LazadaProductFeedHelper
 * lazada,linio,jumia 产品刊登整个流程
 * 1、编辑好的产品发布
 * 2、循环检测产品是否刊登成功
 * 3、如果刊登成功则上传图片
 * @package eagle\modules\listing\helpers
 * @author vizewang
 * @version 1.0
 * @since 2.0
 */
class LazadaLinioJumiaProductFeedHelper
{
    const LOG_ID = "eagle-modules-listing-helpers-LazadaLinioJumiaProductFeedHelper";
    const PRODUCT_CREATE = "product_create";
    const PRODUCT_UPDATE = "product_update";
    const PRODUCT_IMAGE_UPLOAD = "product_image_upload";

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * 产品发布
     * @param array $publishListingIds 待发布产品的id
     * 原始代码SaasLazadaAutoSyncApiHelper
     */
    public static function productPublish($publishListingIds = array())
    {

        if (!empty($publishListingIds)) {
            return array(false, "没有需要发布的产品id");
        }
        try {
            $sendSuccess = 0;
            // 准备合格的产品信息
            list($products, $prodIds, $publishListingId) = self::prepareProductData($publishListingIds);
            $allLazadaAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
            // 发送更新请求
            $sendSuccess += self::sendUpdateRequest($products, $allLazadaAccountsInfoMap, $prodIds, $sendSuccess);

            // 发送创建请求
            $sendSuccess += self::sendCreateRequest($products, $allLazadaAccountsInfoMap, $prodIds, $sendSuccess);

            return array(true, "提交了 " . count($publishListingIds) . " 个，成功执行 " . $sendSuccess . " 个。");
        } catch (\Exception $e) {
            self::localLog("productPublish Exception" . print_r($e, true), LogType::ERROR);
            LLJHelper::changePublishListState($publishListingId, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], $e->getMessage());
            return array(false, $e->getMessage());
        }

    }


    /**
     *  后台触发---检查所有由小老板提交的但没有完成的feed的status。
     * 原始类LazadaFeedHelper
     */
    public static function checkAllFeedStatus()
    {

        $connection = \Yii::$app->db;

        //2. 查看是否有需要检查的feed
        $hasGotRecord = false;
        $nowTime = time();
        // 同步状态 process_status :  0--初始状态，1---已检查, 待调用回调函数，2--已检查等,已调用回调函数，3---已经进入人工待审核队列，7----运行有异常，需要后续重试
        //TODO 去除为了测试的设置
        $sqlStr = 'select `id` from  `lazada_feed_list`  ' .
            ' where  is_running=0 and error_times<10 and (process_status=' . ProductListingProcessStatus::STATUS_INITIAL . ' or process_status=' . ProductListingProcessStatus::STATUS_FAIL . ') AND next_execution_time<' . $nowTime;
        self::localLog("sql:$sqlStr");
        $command = $connection->createCommand($sqlStr);

        $allLazadaAccountsInfoMap = LLJHelper::getAllLLJAccountInfoMap();
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            echo "checkAllFeedStatus dataReader->read() id:" . $row['id'] . "\n";
            // 先判断是否真的抢到待处理账号
            $lazadaFeedList = self::checkAllFeedStatusLock($row['id']);
            if ($lazadaFeedList === null) {
                self::localLog("can not get lock, skipped");
                continue; //抢不到---如果是多进程的话，有抢不到的情况
            }
            $config = $allLazadaAccountsInfoMap[$lazadaFeedList->lazada_saas_user_id];
            self::doCheckFeedStatus($config, $lazadaFeedList);
        }
    }

    private static function doCheckFeedStatus($config, $lazadaFeedList)
    {
        $reqParams = array("FeedID" => $lazadaFeedList->Feed);
        $feedDetail = self::getFeedStatus($config, $reqParams);
        //TODO 当稳定之后需要删除的log

        //4. 根据返回结果做处理
        //4.1 异常处理
        $nowTime = time();
        if (!self::feedstatusResponseErrorDigest($feedDetail, $lazadaFeedList, $nowTime))
            return false;
        self::feedstatusParse($feedDetail, $lazadaFeedList, $config, $nowTime);
        return true;
    }

    public static function checkAllFeedStatusByFeedListId($lazadaFeedListId)
    {
        $lazadaFeedList = LazadaFeedList::findOne($lazadaFeedListId);
        $lazadaUser = SaasLazadaUser::findOne($lazadaFeedList->lazada_saas_user_id);
//        var_dump($lazadaUser);
        $config = array(
            "userId" => $lazadaUser->platform_userid,
            "apiKey" => $lazadaUser->token,
            "countryCode" => $lazadaUser->lazada_site
        );
        self::doCheckFeedStatus($config, $lazadaFeedList);
    }


    /**
     * 产品图片上传
     * 原始文件eagle.modules.lazada.apihelpers.SaasLazadaAutoSyncApiHelper.ImageUpload
     */
    public static function ImageUpload($platforms)
    {


        //由于 feed check 和image upload 由不同的 Background job执行，status 不能共同修改，所以新增了execution_request 来让feed check 通知图片上传要触发任务执行
        $recommendJobObjs = UserBackgroundJobControll::find()
            ->where('is_active = "Y" AND status <>1 AND job_name="' . LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME . '" AND error_count<5 AND execution_request>0')
            ->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
        if (!empty($recommendJobObjs)) {
            self::doImageUpload($recommendJobObjs,$platforms);
        }
    }
    
    private static function doImageUpload($recommendJobObjs,$platforms)
    {
        $nowTime = time();
        self::localLog("ImageUpload start... time:$nowTime");
        $allLazadaAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
        try {
            foreach ($recommendJobObjs as $recommendJobObj) {
                //1. 先判断是否可以正常抢到该记录
                if (!self::imageUploadLock($recommendJobObj))
                    continue; //抢不到

                $recommendJobObj = UserBackgroundJobControll::findOne($recommendJobObj->id);
                $puid = $recommendJobObj->puid;

                // 重新去掉 fail 的 确实有fail的情况不适合自动重新上传图片
                $imageUploadTargets = LazadaPublishListing::find()->where("state='" . LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED . "' ")->andWhere(['platform'=>$platforms])->all();
                if (!empty($imageUploadTargets)) {
                    self::localLog("ImageUpload puid:$puid count" . count($imageUploadTargets));
                    foreach ($imageUploadTargets as $imageUploadTarget) {
                        $imageUploadTarget->status = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][1];
                        if ($imageUploadTarget->update() == false)//抢不到---如果是多进程的话，有抢不到的情况
                            continue;

                        self::localLog("ImageUpload LazadaPublishListing id:" . $imageUploadTarget->id);

                        $imageInfo = json_decode($imageUploadTarget->image_info, true);
                        $variantData = json_decode($imageUploadTarget->variant_info, true);

                        // 如果产品进入这里 ， 则需要检查productPublish 里面检查图片的逻辑或者 查看详细状态，是否有被编辑过的痕迹。
                        if (empty($imageInfo['Product_photo_primary']) && empty($imageInfo['Product_photo_others'])) {
                            LLJHelper::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3], "Images cannot be empty.");
                            continue;
                        }

                        $tempConfig = $allLazadaAccountsInfoMap[$imageUploadTarget->lazada_uid];
                        $config = array(
                            "userId" => $tempConfig["userId"],
                            "apiKey" => $tempConfig["apiKey"],
                            "countryCode" => $tempConfig["countryCode"],
                        );

                        $uploads = array();
                        $uploads['SellerSku'] = LazadaApiHelper::transformFeedString($variantData[0]['SellerSku']);
                        $uploads['Images'] = array();
                        if (!empty($imageInfo['Product_photo_primary'])) {
                            $uploads['Images'][] = $imageInfo['Product_photo_primary'];
                        }

                        if (!empty($imageInfo['Product_photo_others'])) {
                            $others = explode("@,@", $imageInfo['Product_photo_others']);
                            $uploads['Images'] = array_merge($uploads['Images'], $others);
                            $uploads['Images'] = array_filter($uploads['Images']);
                        }

                        self::localLog("ImageUpload before productImage uploads:" . print_r($uploads, true));
                        $response = LazadaInterface_Helper::productImage($config, $uploads);
                        self::localLog("ImageUpload after productImage response:" . print_r($response, true));


                        if ($response['success'] != true) {
                            // 目前总结到 state_code 为28 的为 curl timeout 可以接受重试
                            if (isset($response['state_code']) && (28 == $response['state_code']
                                    || (isset($response['response']['state_code']) && 28 == $response['response']['state_code']))
                            ) {// 不改变state 只记下error message
                                LLJHelper::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4], $response['message']);
                            } else {// 其他错误 mark state 为fail 不再重试
                                if (stripos($response['message'], 'Internal Application Error') !== false) {// dzt20160310 对平台错误 提示客户重试减少客服量
                                    $response['message'] = $response['message'] . '<br>请重新发布商品';
                                }
                                LLJHelper::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4], $response['message']);

                                // 非网络原因都要减，不能重试
                                if ($recommendJobObj->execution_request >= 1) {
                                    $recommendJobObj->execution_request = $recommendJobObj->execution_request - 1;
                                    $recommendJobObj->save(false);
                                }
                            }
                        } else {
                            // 但要尽量确保这个不会有负数，确保当有 可以执行图片上传的obj时不会因为这里而导致执行不了
                            if ($recommendJobObj->execution_request >= 1) {
                                $recommendJobObj->execution_request = $recommendJobObj->execution_request - 1;
                                $recommendJobObj->save(false);
                            }

                            $feedId = $response['response']['body']['Head']['RequestId'];
                            LazadaPublishListing::updateAll(['feed_id' => $feedId], ['id' => $imageUploadTarget->id]); // 记录feed id
                            // 转换状态
                            LLJHelper::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD][0]);
                            self::localLog("productCreate response:" . print_r($response['response'], true));
                            $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $imageUploadTarget->lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD);
                            if ($insertFeedResult) {
                                self::localLog("ImageUpload insertFeed " . LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD . " success. puid:" . $tempConfig['puid'] . " lazada_uid:" . $imageUploadTarget->lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                            } else {
                                self::localLog("ImageUpload insertFeed " . LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD . " fail. puid:" . $tempConfig['puid'] . " lazada_uid:" . $imageUploadTarget->lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                            }
                        }
                    }// end of foreach $imageUploadTargets
                }// end of !empty($imageUploadTargets)

                $recommendJobObj->status = 2;
                $recommendJobObj->error_count = 0;
                $recommendJobObj->error_message = "";
                $recommendJobObj->last_finish_time = $nowTime;
                $recommendJobObj->update_time = $nowTime;
                $recommendJobObj->next_execution_time = $nowTime + 24 * 3600;//24个小时后重试
                $recommendJobObj->save(false);
            }// end of foreach $recommendJobObjs
        } catch (\Exception $e) {
            self::localLog("ImageUpload Exception" . print_r($e, true), LogType::ERROR);
            if (!empty($recommendJobObj))
                self::handleBgJobError($recommendJobObj, $e->getMessage());
            if (!empty($imageUploadTarget))
                LLJHelper::changePublishListState($imageUploadTarget->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3], $e->getMessage());
        }

    }

    /**
     * 获取指定feedid的执行结果
     * @param unknown $config
     * @param unknown $reqParams
     * array("FeedID"=>"234234-345345-335")
     * @return string
     */
    public
    static function getFeedStatus($config, $reqParams = array())
    {

        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getFeedDetail",
            "reqParams" => json_encode($reqParams)

        );
        self::localLog("getFeedStatus reqParams is " . json_encode($reqParams));
        $getFeedDetailRlt = LazadaProxyConnectHelper::call_lazada_api($reqParams);
        self::localLog("getFeedStatus end ===>result:" . print_r($getFeedDetailRlt, true));

        return $getFeedDetailRlt;
    }

    /**
     * 对返回结果的不同状态进行处理
     * @param $feedDetail
     * @param $lazadaFeedList
     * @param $config
     * @param $nowTime
     */
    private
    static function feedstatusParse($feedDetail, $lazadaFeedList, $config, $nowTime)
    {
        $singleFeedDetail = $feedDetail["response"]["feeds"][0];
        $feedStatus = $singleFeedDetail["Status"];
        $feedId = $lazadaFeedList->Feed;
        self::localLog("feedstatusParse start feedId:$feedId feedStatus:$feedStatus");
        //4.2 状态为 排队中 
        // dzt20171023 添加PROCESSING 状态同样处理
        if ($feedStatus == ProductFeedStatus::QUEUED || $feedStatus == ProductFeedStatus::PROCESSING) {
            self::parseQueued($lazadaFeedList, $nowTime, $singleFeedDetail);
            return;
        }
        
        //4.3 状态为已完成
        if ($feedStatus == ProductFeedStatus::FINISHED) {
            self::parseFinished($lazadaFeedList, $nowTime, $singleFeedDetail, $config);
            return;
        }
        //4.4 lazada方面的api 失败: 可能是lazada方面的处理问题，导致产品创建失败，需重新刊登
        if ($feedStatus == "Error") {
            self::parseError($lazadaFeedList, $nowTime, $singleFeedDetail, $config);
            return;
        }
        self::parseOthers($lazadaFeedList, $nowTime, $singleFeedDetail);
    }

    /**
     * 返回值的错误解析
     * @param $feedDetail
     * @param $lazadaFeedList
     * @param $nowTime
     * @return bool 如果有不可继续执行的错误,返回false,否则返回true
     */
    private
    static function feedstatusResponseErrorDigest($feedDetail, $lazadaFeedList, $nowTime)
    {
        $errorMessage = "";
        if ($feedDetail["success"] === false) {
            $errorMessage = $feedDetail["message"];
        } else if (!isset($feedDetail["response"]["feeds"])) {
            $errorMessage = "no response feeds";
        } else if (count($feedDetail["response"]["feeds"]) <= 0) {
            $errorMessage = "no response feeds subelement";
        }
        if ($errorMessage <> "") {
            $lazadaFeedList->next_execution_time = $nowTime + self::getCheckIntervalByType($lazadaFeedList->type);
            $lazadaFeedList->update_time = $nowTime;
            $lazadaFeedList->message = $errorMessage;
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->is_running = 0;
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_FAIL;
            $lazadaFeedList->save(false);
            self::localLog("feedstatusResponseErrorDigest end feedId:$lazadaFeedList->Feed STATUS_FAIL save $errorMessage");
            return false;
        }
        return true;
    }


    /**
     * 检查多进程时,publishListing是否已经在执行,如果已经在执行则返回false,否则修改状态,返回true
     * @param $publishListingId
     * @return bool
     */
    private
    static function publishListingLock($publishListingId)
    {
        $connection = Yii::$app->subdb;
        $command = $connection->createCommand("update lazada_publish_listing set status='" . LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1] . "' where id=" . $publishListingId . " and ( state='" . LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT . "' or state='" . LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL . "' ) ");
        $record = $command->execute();
        if ($record > 0) {
            return true;
        }
        return false;
    }

    /**
     * 先判断是否真的抢到待处理账号
     * @param $rowId
     * @return null或者$SAA_obj , null表示抢不到记录
     */
    private
    static function checkAllFeedStatusLock($rowId)
    {
        $connection = \Yii::$app->db;
        $command = $connection->createCommand("update lazada_feed_list set is_running=1 where is_running=0 and id=" . $rowId);
        $affectRows = $command->execute();
        if ($affectRows <= 0)
            return null; //抢不到---如果是多进程的话，有抢不到的情况
        // 抢到记录
        $lazadaFeedList = LazadaFeedList::findOne($rowId);
        return $lazadaFeedList;
    }

    /**
     * 判断是否有进程在执行相同的任务,如果有,则返回false,否则返回true
     * @param $recommendJobObj
     * @return bool
     */
    private
    static function imageUploadLock($recommendJobObj)
    {
//         $recommendJobObj->status = 1;
//         $affectRows = $recommendJobObj->update(false);

        $affectRows = UserBackgroundJobControll::updateAll(['status'=>1],['id'=>$recommendJobObj->id, 'status'=>[0, 3]]);
        if ($affectRows <= 0) {
            return false;
        }
        return true;
    }

    /**
     * 查看Variant 信息是否完整
     * @param $oneVariant
     * @param $publishListing
     * @return bool
     */
    private
    static function checkVariant($oneVariant, $publishListing)
    {
        // dzt20151216 在jumia 后台操作保存过，可以不填，之前的两个平台也可以不填，但是getAttr接口看到这个字段是必填。
// 						$hasVariantProblem = self::_checkPropertyIsEmpty('Variation',$oneVariant['Variation'],$publishListing);
// 						if($hasVariantProblem) break;

        // 接口上看 其他两个平台都不是必填，linio是必填，但这里我们set成必填
        if (LLJHelper::checkPropertyIsEmpty('SellerSku', $oneVariant['SellerSku'], $publishListing))
            return false;

        // dzt20151113 调试productCreate Price又不是必填的了。 但是我们为了后面不再查错，所以这里强制要求填
        if (LLJHelper::checkPropertyIsEmpty('Price', $oneVariant['Price'], $publishListing))
            return false;

        // 接口上看 其他所有平台都不是必填，但这里我们set成必填
        if (LLJHelper::checkPropertyIsEmpty('Quantity', $oneVariant['Quantity'], $publishListing))
            return false;

        // 检查 Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank

        if (isset($oneVariant['SalePrice']) && $oneVariant['SalePrice'] > $oneVariant['Price']) {
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Sale price must be lower than the standard price.");
            return false;
        }

        // 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在,否则 linio 报Internal Application Error错
        if (!empty($oneVariant['SalePrice']) || !empty($oneVariant['SaleStartDate']) || !empty($oneVariant['SaleEndDate'])) {
            if (empty($oneVariant['SalePrice'])) {
                LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SalePrice is empty.Please complete the sales information.");
                return false;
            }

            if (empty($oneVariant['SaleStartDate'])) {
                LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SaleStartDate is empty.Please complete the sales information.");
                return false;
            }

            if (empty($oneVariant['SaleEndDate'])) {
                LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SaleEndDate is empty.Please complete the sales information.");
                return false;
            }
        }

        // 平台必填区别
        if ('linio' == $publishListing->platform) {
            if (LLJHelper::checkPropertyIsEmpty('ProductId', $oneVariant['ProductId'], $publishListing)) {
                return false;
            };
        }
        return true;
    }

    /**
     * 预检查发布的铲平是否有问题,如果有则返回false,否则返回true
     * @param $publishListingId
     * @return mixed
     */
    private
    static function publishListingInfoCheck($publishListing)
    {
        $storeInfo = json_decode($publishListing->store_info, true);
        $baseInfo = json_decode($publishListing->base_info, true);
        $imageInfo = json_decode($publishListing->image_info, true);
        $descriptionInfo = json_decode($publishListing->description_info, true);
        $shippingInfo = json_decode($publishListing->shipping_info, true);
        $warrantyInfo = json_decode($publishListing->warranty_info, true);
        $variantData = json_decode($publishListing->variant_info, true);

        // 清空空值的key,以及trim 所有value
        Helper_Array::removeEmpty($storeInfo, true);
        Helper_Array::removeEmpty($baseInfo, true);
        Helper_Array::removeEmpty($imageInfo, true);
        Helper_Array::removeEmpty($descriptionInfo, true);
        Helper_Array::removeEmpty($shippingInfo, true);
        Helper_Array::removeEmpty($warrantyInfo, true);
        Helper_Array::removeEmpty($variantData, true);

        // 1.必要属性检查
        if (LLJHelper::checkPropertyIsEmpty('PrimaryCategory', $storeInfo['primaryCategory'], $publishListing))
            return false;
        if (LLJHelper::checkPropertyIsEmpty('Brand', $baseInfo['Brand'], $publishListing))
            return false;
        if (LLJHelper::checkPropertyIsEmpty('Name', $baseInfo['Name'], $publishListing))
            return false;
        // 接口上看 其他两个平台都必填，lazada不是必填，但lazada卖家后台显示必填，想审核通过也是必填
        if (LLJHelper::checkPropertyIsEmpty('Description', $descriptionInfo['Description'], $publishListing))
            return false;

        // 平台必填区别
        if ('lazada' == $publishListing->platform) {
            if (LLJHelper::checkPropertyIsEmpty('PackageContent', $descriptionInfo['PackageContent'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageLength', $shippingInfo['PackageLength'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageWidth', $shippingInfo['PackageWidth'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageHeight', $shippingInfo['PackageHeight'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageWeight', $shippingInfo['PackageWeight'], $publishListing))
                return false;
            if ($publishListing->site == "my") {// my才强制
                // dzt20160520 前端去掉 让客户填写 'NameMs','DescriptionMs'，直接再这里从 'Name','Description' 复制
                $baseInfo['NameMs'] = $baseInfo['Name'];
                $descriptionInfo['DescriptionMs'] = $descriptionInfo['Description'];

                // dzt20160226 马来西亚也去掉必填
// 							if(self::_checkPropertyIsEmpty('MaxDeliveryTime',$shippingInfo['MaxDeliveryTime'],$publishListing)) continue;
// 							if(self::_checkPropertyIsEmpty('MinDeliveryTime',$shippingInfo['MinDeliveryTime'],$publishListing)) continue;

                if (LLJHelper::checkPropertyIsEmpty('TaxClass', $baseInfo['TaxClass'], $publishListing))
                    return false;
            }
        } else if ('linio' == $publishListing->platform) {
            if (LLJHelper::checkPropertyIsEmpty('PackageLength', $shippingInfo['PackageLength'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageWidth', $shippingInfo['PackageWidth'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageHeight', $shippingInfo['PackageHeight'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('PackageWeight', $shippingInfo['PackageWeight'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('TaxClass', $baseInfo['TaxClass'], $publishListing))
                return false;
        } else if ('jumia' == $publishListing->platform) {
            if (LLJHelper::checkPropertyIsEmpty('ShortDescription', $descriptionInfo['ShortDescription'], $publishListing))
                return false;
            if (LLJHelper::checkPropertyIsEmpty('ProductWeight', $shippingInfo['ProductWeight'], $publishListing))
                return false;
        }
        if (empty($imageInfo['Product_photo_primary']) && empty($imageInfo['Product_photo_others'])) {
            LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Images cannot be empty.");
            return false;
        }
        return array(true, $storeInfo, $baseInfo, $imageInfo, $descriptionInfo, $shippingInfo, $warrantyInfo, $variantData);
    }

    /**
     * 组合产品数据内容
     * @param $publishListing
     * @param $storeInfo
     * @param $baseInfo
     * @param $descriptionInfo
     * @param $shippingInfo
     * @param $warrantyInfo
     * @param $variantData
     * @param $parentSku
     * @return mixed 组合后的产品
     */
    private
    static function assembleProduct(&$products, &$prodIds, $publishListing, $storeInfo, $baseInfo, $descriptionInfo, $shippingInfo, $warrantyInfo, $variantData, $parentSku, $existingUpSkus)
    {
        $product = array();
        $productData = array();
        $notProDataProperties = array('Brand', 'Description', 'Name', 'Price', 'PrimaryCategory', 'SellerSku', 'TaxClass', 'Categories', 'Condition',
            'CountryCity', 'ParentSku', 'ProductGroup', 'ProductId', 'Quantity', 'SaleEndDate', 'SalePrice', 'SaleStartDate', 'ShipmentType',
            'Status', 'Variation', 'BrowseNodes');

        $product['PrimaryCategory'] = $storeInfo['primaryCategory'];
        if (!empty($storeInfo['categories']) && 'lazada' <> $publishListing->platform)// dzt20151225 lazada后台没得填这选项，去掉
            $product['Categories'] = implode(",", $storeInfo['categories']);

        self::retriveBaseInfo($publishListing, $baseInfo, $notProDataProperties, $product, $productData);

        self::retriveDescriptionInfo($descriptionInfo, $notProDataProperties, $product, $productData);

        self::retriveShippingInfo($shippingInfo, $notProDataProperties, $product, $productData);

        self::retriveWarrantyInfo($publishListing, $warrantyInfo, $notProDataProperties, $product, $productData);

        $product['ProductData'] = $productData;// 以下是产品变参信息，先合并已经定义的信息

        // 初始化准备上提交产品下标，以便用于变参属性写入
        if (empty($products['create'][$publishListing->lazada_uid])) {
            $initProdCrtIndex = 0;
            $products['create'][$publishListing->lazada_uid] = array();
        } else {
            $initProdCrtIndex = count($products['create'][$publishListing->lazada_uid]);
        }

        if (empty($products['update'][$publishListing->lazada_uid])) {
            $initProdUpIndex = 0;
            $products['update'][$publishListing->lazada_uid] = array();
        } else {
            $initProdUpIndex = count($products['update'][$publishListing->lazada_uid]);
        }

        foreach ($variantData as $oneVariant) {
            // 分开 调用create 接口和 update接口产品。如添加变参产品时，父产品只修改变参值，但是子产品需要创建。又或者是批量发布时候包含不同状态产品。
            if (!empty($existingUpSkus) && in_array($oneVariant['SellerSku'], $existingUpSkus)) {
                $type = "update";
                $initProductIndex = $initProdUpIndex++;
            } else {
                $type = "create";
                $initProductIndex = $initProdCrtIndex++;
            }

            $products[$type][$publishListing->lazada_uid][$initProductIndex] = $product;// 如果是变参产品 复制已经定义的变参产品信息

            if (!empty($parentSku))
                $products[$type][$publishListing->lazada_uid][$initProductIndex]['ParentSku'] = $parentSku;

            foreach ($oneVariant as $key => $value) {
                $key = ucfirst($key);// 界面组织来的属性 首字母变成小写了，这里首字母要转成大写 传给proxy
                if (!empty($value)) {
                    if (in_array($key, $notProDataProperties)) {
                        if ($key == 'SellerSku') {
                            $value = htmlentities($value);
                        }

                        // seller后台 full api 看到ProductGroup这个属性，但是get attr 接口已经获取不到这个属性了
                        if ('ProductGroup' == $key) continue;
                        $products[$type][$publishListing->lazada_uid][$initProductIndex][$key] = $value;
                    } else {
                        $products[$type][$publishListing->lazada_uid][$initProductIndex]['ProductData'][$key] = $value;
                    }
                }
            }

            if (!isset($publishListingId, $prodIds[$type][$publishListing->lazada_uid]) || !in_array($publishListingId, $prodIds[$type][$publishListing->lazada_uid]))
                $prodIds[$type][$publishListing->lazada_uid][] = $publishListingId;
        }
        return $products;
    }

    /**
     * @param $publishListing
     * @param $baseInfo
     * @param $notProDataProperties
     * @param $product
     * @param $productData
     */
    private
    static function retriveBaseInfo($publishListing, $baseInfo, $notProDataProperties, &$product, &$productData)
    {
        foreach ($baseInfo as $key => $value) {
            if (!empty($value)) {
                //dzt20151203 linio 由于get attr api 返回该属性为 input 输入，但接口只接受 boolean ，改为checkbox 形式后 ， checkbox的value设置也不是boolean值，所以界面填了YES，这里要装换。
                if ('linio' == $publishListing->platform && $key == 'EligibleFreeShipping' && strtolower($value) == 'yes') {
                    $value = true;
                }

                if ('jumia' == $publishListing->platform && $key == 'BrowseNodes') {// @todo 貌似linio 也是这样的， 待测试
                    $browserNodes = array();
                    foreach ($value as $groupNodes) {
                        $browserNodes[] = $groupNodes[0];
                    }
                    $value = implode(',', $browserNodes);
                }

                if (in_array($key, $notProDataProperties)) {
                    $product[$key] = $value;
                } else {
                    $productData[$key] = $value;
                }
            }
        }
    }

    /**
     * @param $descriptionInfo
     * @param $notProDataProperties
     * @param $product
     * @param $productData
     */
    private
    static function retriveDescriptionInfo($descriptionInfo, $notProDataProperties, &$product, &$productData)
    {
        foreach ($descriptionInfo as $key => $value) {
            if (!empty($value)) {
                if (in_array($key, $notProDataProperties)) {
                    $product[$key] = "<![CDATA[" . $value . "]]>";
                } else {
                    $productData[$key] = "<![CDATA[" . $value . "]]>";
                }
            }
        }
    }

    /**
     * @param $shippingInfo
     * @param $notProDataProperties
     * @param $product
     * @param $productData
     * @return array
     */
    private
    static function retriveShippingInfo($shippingInfo, $notProDataProperties, &$product, &$productData)
    {
        foreach ($shippingInfo as $key => $value) {
            if (!empty($value)) {
                if (in_array($key, $notProDataProperties)) {
                    $product[$key] = $value;
                } else {
                    $productData[$key] = $value;
                }
            }
        }
    }

    /**
     * @param $publishListing
     * @param $warrantyInfo
     * @param $notProDataProperties
     * @param $product
     * @param $productData
     * @return array
     */
    private
    static function retriveWarrantyInfo($publishListing, $warrantyInfo, $notProDataProperties, &$product, &$productData)
    {
        foreach ($warrantyInfo as $key => $value) {
            if (!empty($value)) {
                if ('linio' == $publishListing->platform && 'ProductWarranty' == $key) {
                    $value = "<![CDATA[" . $value . "]]>";
                }

                if (in_array($key, $notProDataProperties)) {
                    $product[$key] = $value;
                } else {
                    $productData[$key] = $value;
                }
            }
        }
    }

    /**
     * 发送更新请求
     * @param $products
     * @param $allLazadaAccountsInfoMap
     * @param $prodIds
     * @param $sendSuccess
     * @return mixed
     */
    private
    static function sendUpdateRequest($products, $allLazadaAccountsInfoMap, $prodIds)
    {
        return self::sendRequest($products['update'], $prodIds['update'], $allLazadaAccountsInfoMap, "update");
    }

    /**
     * 发送创建请求
     * @param $products
     * @param $allLazadaAccountsInfoMap
     * @param $prodIds
     * @param $sendSuccess
     * @return int
     */
    private
    static function sendCreateRequest($products, $allLazadaAccountsInfoMap, $prodIds)
    {
        return self::sendRequest($products['create'], $prodIds['create'], $allLazadaAccountsInfoMap, "create");
    }

    /**
     * 发送请求
     * @param $product
     * @param $prodId
     * @param $allLazadaAccountsInfoMap
     * @param $requestType create||update
     * @return int 发送请求数量
     */
    private
    static function sendRequest($product, $prodId, $allLazadaAccountsInfoMap, $requestType)
    {
        $sendSuccess = 0;
        foreach ($product as $lazada_uid => $sameAccountProds) {
            if (!empty($sameAccountProds)) {
                $tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
                $config = array(
                    "userId" => $tempConfig["userId"],
                    "apiKey" => $tempConfig["apiKey"],
                    "countryCode" => $tempConfig["countryCode"],
                );

                self::localLog("productPublish before createFeed config:" . json_encode($config) . PHP_EOL . " products:" . json_encode($sameAccountProds));
                if ($requestType == "create") {
                    $response = LazadaInterface_Helper::productCreate($config, array('products' => $sameAccountProds));
                } else if ($requestType == "update") {
                    $response = LazadaInterface_Helper::productUpdate($config, array('products' => $sameAccountProds));
                }
                self::localLog("productPublish createFeed response:" . print_r($response, true));
                if ($response['success'] != true) {
                    if (stripos($response['message'], 'Internal Application Error') !== false) {// dzt20160310 对平台错误 提示客户重试减少客服量
                        $response['message'] = $response['message'] . '<br>请重新发布商品';
                    }
                    self::changePublishListState($prodId[$lazada_uid], LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1], $response['message']);
                } else {
                    $sendSuccess += count($sameAccountProds);
                    $feedId = $response['response']['body']['Head']['RequestId'];
                    LazadaPublishListing::updateAll(['feed_id' => $feedId], ['id' => $prodId[$lazada_uid]]); // 记录feed id
                    // 转换状态
                    self::changePublishListState($prodId[$lazada_uid], LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
                    $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
                    if ($insertFeedResult) {
                        self::localLog("productPublish insertFeed " . LazadaFeedHelper::PRODUCT_CREATE . " success. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    } else {
                        self::localLog("productPublish insertFeed " . LazadaFeedHelper::PRODUCT_CREATE . " fail. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    }
                }
            }
        }
        return $sendSuccess;
    }

    /**
     * @param $publishListingIds
     * @return array
     */
    private
    static function prepareProductData($publishListingIds)
    {
        $products = array('create' => array(), 'update' => array());// 根据账号分组的 待上传产品信息数组
        $prodIds = array('create' => array(), 'update' => array());// 根据账号分组的 待上传产品 的 刊登产品Id
        foreach ($publishListingIds as $publishListingId) {
            if (!self::publishListingLock($publishListingId))//抢不到---如果是多进程的话，有抢不到的情况
                continue;
            $publishListing = LazadaPublishListing::findOne($publishListingId);
            list($isPass, $storeInfo, $baseInfo, $imageInfo, $descriptionInfo, $shippingInfo, $warrantyInfo, $variantData) = self::publishListingInfoCheck($publishListing);
            if (!$isPass)
                continue;
            $hasVariantProblem = false;
            $parentSku = "";
            $existingUpSkus = json_decode($publishListing->uploaded_product, true); // 已上传到Lazada后台的产品 sku

            foreach ($variantData as $oneVariant) {
                if (!self::checkVariant($oneVariant, $publishListing)) {
                    $hasVariantProblem = true;
                    break;
                }
                if (count($variantData) > 1 && empty($parentSku)) {
                    $parentSku = $oneVariant['SellerSku'];
                }
            }

            if ($hasVariantProblem) continue;

            // dzt20160413 有在线产品 A ，准备刊登产品 父产品和A的sku 一样，另外的子产品不存在重复
            // 这样刊登的时候会出现，父产品刊登失败提示,sku已存在，但另外的子产品就加到已存在产品的子产品里面
            // 所以这里暂时先加一个检查父产品是否再我们的在线商品里面。如果有就停止这个产品的刊登。
            // @todo 后面应该改成先让父产品刊登，后面再一起提交子产品。这样才稳妥一点
            if (!empty($parentSku) && !empty($existingUpSkus) && !in_array($parentSku, $existingUpSkus)) {
                $isExist = LazadaListing::findOne(['SellerSku' => $parentSku, 'lazada_uid_id' => $publishListing->lazada_uid]);
                if (!empty($isExist)) {
                    LLJHelper::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Field SellerSku with value '" . $parentSku . "' has a problem: You already have another product with the SKU '" . $parentSku . "'");
                    continue;
                }
            }

            // 2.组合产品数据内容
            self::assembleProduct($products, $prodIds, $publishListing, $storeInfo, $baseInfo, $descriptionInfo, $shippingInfo, $warrantyInfo, $variantData, $parentSku, $prodIds, $existingUpSkus);
        }
        return array($products, $prodIds, $publishListingId);
    }

    /**
     * @param $lazadaFeedList
     * @param $nowTime
     * @param $statusRet
     * @param $feedInfoRet
     */
    private
    static function parseQueued($lazadaFeedList, $nowTime, $singleFeedDetail)
    {
        if ($lazadaFeedList->Status == "") {
            //第一次拉取结果
            self::setSAAOBJPart1($lazadaFeedList, $singleFeedDetail);
        }

        $lazadaFeedList->next_execution_time = $nowTime + self::getCheckIntervalByType($lazadaFeedList->type);
        $lazadaFeedList->update_time = $nowTime;
        $lazadaFeedList->is_running = 0;
        $lazadaFeedList->save(false);
    }

    private
    static function parseFinished($lazadaFeedList, $nowTime, $singleFeedDetail, $config)
    {
        self::localLog("parseFinished start...");
        $lazadaFeedList->TotalRecords = $singleFeedDetail["TotalRecords"];
        $lazadaFeedList->ProcessedRecords = $singleFeedDetail["ProcessedRecords"];
        $lazadaFeedList->FailedRecords = $singleFeedDetail["FailedRecords"];
        $feedId = $lazadaFeedList->Feed;
        if ($lazadaFeedList->FailedRecords > 0) {
            $lazadaFeedList->FeedErrors = json_encode($singleFeedDetail["FeedErrors"]);
        }
        if ($lazadaFeedList->Status != "Finished") {
            //第一次拉取结果
            self::setSAAOBJPart1($lazadaFeedList, $singleFeedDetail);
        }
        $lazadaFeedList->update_time = $nowTime;
        $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED;
        $lazadaFeedList->save(false);
        //TODO call api---只有finish的状态才调用类型相对应的回调函数。这里会根据不同的type调用对应的回调函数
        $lazadaFeedList->is_running = 0;
        if ($lazadaFeedList->type == self::PRODUCT_CREATE or $lazadaFeedList->type == self::PRODUCT_UPDATE) {
            self::createAndUpdateStatusCallback($lazadaFeedList, $singleFeedDetail, $config, $feedId);
            return;
        }
        if ($lazadaFeedList->type == self::PRODUCT_IMAGE_UPLOAD) {
            //(1)图片upload已经完成（不一定成功）
            self::imageuploadStatusCallback($lazadaFeedList, $singleFeedDetail, $config, $nowTime);
            return;
        }
        
        // 处理excel 导入结果信息，另外在backgroup job再捡起这些结果处理
        if (in_array($lazadaFeedList->type, [LazadaFeedHelper::PRODUCT_CREATE2, LazadaFeedHelper::PRODUCT_UPDATE2, 
                LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD2, LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2, LazadaFeedHelper::PRODUCT_DELETE2])) {
            $lazadaFeedList->save(false);
            \Yii::info(__FUNCTION__.",feedId:$feedId,type:".$lazadaFeedList->type." Finished check success.","file");
            return;
        }
        
    }

    /**
     * @param $lazadaFeedList
     * @param $statusRet
     * @param $singleFeedDetail
     */
    private
    static function setSAAOBJPart1(&$lazadaFeedList, $singleFeedDetail)
    {
        $lazadaFeedList->Status = $singleFeedDetail["Status"];
        //2015-08-26T22:18:52+0800 时间转成 时间戳
        $lazadaFeedList->CreationDate = TimeUtil::getTimestampFromISO8601($singleFeedDetail["CreationDate"]);
        $lazadaFeedList->UpdatedDate = TimeUtil::getTimestampFromISO8601($singleFeedDetail["UpdatedDate"]);
        $lazadaFeedList->Source = $singleFeedDetail["Source"];
        $lazadaFeedList->Action = $singleFeedDetail["Action"];
    }

    /**
     * @param $lazadaFeedList
     * @param $singleFeedDetail
     * @param $config
     * @param $feedId
     */
    private
    static function createAndUpdateStatusCallback($lazadaFeedList, $singleFeedDetail, $config)
    {
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "totalRecords" => $lazadaFeedList->TotalRecords);
        if ($lazadaFeedList->FailedRecords > 0) {
            $reqErrors = array();
            if (isset($singleFeedDetail["FeedErrors"]["Error"]["SellerSku"])) {
                $errorsInfo = array($singleFeedDetail["FeedErrors"]["Error"]);
            } else {
                $errorsInfo = $singleFeedDetail["FeedErrors"]["Error"];
            }
            foreach ($errorsInfo as $feedErrorInfo) {
                if (!empty($reqErrors[$feedErrorInfo["SellerSku"]])) {
                    $reqErrors[$feedErrorInfo["SellerSku"]] = $reqErrors[$feedErrorInfo["SellerSku"]] . "<br />" . $feedErrorInfo["Message"];
                } else {
                    $reqErrors[$feedErrorInfo["SellerSku"]] = $feedErrorInfo["Message"];
                }
            }
            $reqParams["failReport"] = $reqErrors;
        }

        if ($lazadaFeedList->type == self::PRODUCT_CREATE) {
            self::localLog("createAndUpdateStatusCallback start feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productCreate($reqParams);
            self::localLog("createAndUpdateStatusCallback end feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else { //$SAA_obj->type==self::PRODUCT_UPDATE
            // 回调修改listing 修改状态信息
            self::localLog("createAndUpdateStatusCallback start feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productUpdate($reqParams, $config);// dzt20160119 带上config 参数立即获取产品信息
            self::localLog("createAndUpdateStatusCallback end feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        }


        if ($ret === true) {
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED_CALLED;
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_CHECKED_CALLED");
            $lazadaFeedList->save(false);
        } else {
            $lazadaFeedList->message = "LazadaCallbackHelper::productCreate";
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_FAIL;
            $lazadaFeedList->save(false);
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_FAIL checkAllFeedStatus  productCreate  save");
        }
    }

    private
    static function imageuploadStatusCallback($lazadaFeedList, $feedInfoRet, $config, $nowTime)
    {
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "totalRecords" => $lazadaFeedList->TotalRecords);

        if ($lazadaFeedList->FailedRecords > 0) {
            $reqErrors = array();

            if (isset($feedInfoRet["FeedErrors"]["Error"]["SellerSku"])) {// 只有一个feed时，返回结构可能不一样
                $errorsInfo = array($feedInfoRet["FeedErrors"]["Error"]);
            } else {
                $errorsInfo = $feedInfoRet["FeedErrors"]["Error"];
            }

            foreach ($errorsInfo as $feedErrorInfo) {
                $reqErrors[$feedErrorInfo["SellerSku"]] = $feedErrorInfo["Message"];
            }
            $reqParams["failReport"] = $reqErrors;
        }

        //(2)图片upload成功的话，回调函数调用
        self::localLog("imageuploadStatusCallback start reqParams:" . print_r($reqParams, true));
        list($ret, $sellerSkus) = LazadaCallbackHelper::imageUpload($reqParams);
        self::localLog("imageuploadStatusCallback end sellerSku:" . print_r($sellerSkus, true));
        if ($ret === false) {  //LazadaCallbackHelper::productImageUpload 失败
            //$SAA_obj->process_status=2;
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->message = "LazadaCallbackHelper::imageUpload fails";
            $lazadaFeedList->save(false);
            return;
        }
        $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED_CALLED;
        self::localLog("feedId:$lazadaFeedList->Feed STATUS_CHECKED_CALLED");
        $lazadaFeedList->save(false);

        if (empty($sellerSkus)) {//TODO 图片上传失败，不用检查是否进入人工审核,逻辑还要加强
            self::localLog("feedId:$lazadaFeedList->Feed image upload fails");
            return;
        }

        //(3)回调函数成功，检查是否已经人工审核状态
        $reqParams = array(
            "SkuSellerList" => implode(",", $sellerSkus),
            "Filter" => "pending"
        );

        self::localLog("LazadaInterface_Helper::getProducts before reqParams:" . print_r($reqParams, true));
        $result = LazadaInterface_Helper::getProducts($config, $reqParams);
        self::localLog("LazadaInterface_Helper::getProducts result:" . print_r($result, true));
        if ($result["success"] === false or !isset($result["response"]) or !isset($result["response"]["products"])) {
            //error
            $lazadaFeedList->next_execution_time = $nowTime + 5 * 60;
            $lazadaFeedList->update_time = $nowTime;
            $lazadaFeedList->message = $result["message"];
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->is_running = 0;
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_FAIL;
            $lazadaFeedList->save(false);

            self::localLog("Error after imageupload LazadaInterface_Helper::getProducts sku:" . implode(",", $sellerSkus) . ".  synId:" . $lazadaFeedList->id . " puid:" . $lazadaFeedList->puid . " ErrorMessage:" . $lazadaFeedList->message);
        }

        $products = $result["response"]["products"];
        //(4)进入人工审核状态，回调函数调用  skuPendingMap=>array("sku1"=>0,"sku2"=>1....)   //0---表示不是pending，1----是pending
        $skuPendingMap = array();
        foreach ($sellerSkus as $oneSku) {
            $skuPendingMap[$oneSku] = 0;
        }
        if (!empty($products)) {
            foreach ($products as $pendingProd) {
                if (in_array($pendingProd['SellerSku'], $sellerSkus)) {
                    $skuPendingMap[$pendingProd['SellerSku']] = 1;
                }
            }
        }
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "skuPendingMap" => $skuPendingMap);
        if (count($products) == 0) {
            self::localLog("LazadaInterface_Helper::markPendingProduct before reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::markPendingProduct($reqParams);
            self::localLog("after LazadaInterface_Helper::markPendingProduct ret:" . print_r($ret, true));
        } else {
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_PENDING;
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_PENDING");
            $lazadaFeedList->save(false);
            $reqParams["isPending"] = 1;
            LazadaCallbackHelper::markPendingProduct($reqParams);
        }
    }

    /**
     * @param $lazadaFeedList
     * @param $config
     * @param $nowTime
     * @param $singleFeedDetail
     * @param $statusRet
     * @param $feedId
     */
    private
    static function parseError($lazadaFeedList, $nowTime, $singleFeedDetail, $config)
    {
        $lazadaFeedList->TotalRecords = $singleFeedDetail["TotalRecords"];
        $lazadaFeedList->ProcessedRecords = $singleFeedDetail["ProcessedRecords"];
        $lazadaFeedList->FailedRecords = $singleFeedDetail["FailedRecords"];
        if ($lazadaFeedList->FailedRecords > 0) {
            $lazadaFeedList->FeedErrors = json_encode($singleFeedDetail["FeedErrors"]);
        }
        if ($lazadaFeedList->Status != "Error") {
            //第一次拉取结果
            self::setSAAOBJPart1($lazadaFeedList, $singleFeedDetail);
        }
        $lazadaFeedList->update_time = $nowTime;
        $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED;
        self::localLog("feedId:$lazadaFeedList->Feed STATUS_CHECKED");
//        var_dump($SAA_obj);
        $lazadaFeedList->save(false);
        self::localLog("checkAllFeedStatus feedId:$lazadaFeedList->Feed self::STATUS_CHECKED save");

        $lazadaFeedList->is_running = 0;
        
        // 导入的不需处理 直接保存即可。
        if (in_array($lazadaFeedList->type, [LazadaFeedHelper::PRODUCT_CREATE2, LazadaFeedHelper::PRODUCT_UPDATE2, 
                LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD2, LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2, LazadaFeedHelper::PRODUCT_DELETE2])) {
            $lazadaFeedList->save(false);
            \Yii::info(__FUNCTION__.",feedId:".$lazadaFeedList->Feed.",type:".$lazadaFeedList->type." Error check success.","file");
            continue;
        }
        
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "totalRecords" => $lazadaFeedList->TotalRecords);
        $reqParams["failReport"] = "lazada api error";

        if ($lazadaFeedList->type == self::PRODUCT_CREATE) {
            self::localLog("LazadaCallbackHelper::productCreate before feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productCreate($reqParams);
            self::localLog("LazadaCallbackHelper::productCreate after feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else if ($lazadaFeedList->type == self::PRODUCT_UPDATE) {
            // 回调修改listing 修改状态信息
            self::localLog("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productUpdate($reqParams, $config);// dzt20160119 带上config 参数立即获取产品信息
            self::localLog("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else if ($lazadaFeedList->type == self::PRODUCT_IMAGE_UPLOAD) {
            self::localLog("LazadaCallbackHelper::imageUpload reqParams:" . print_r($reqParams, true));
            list($ret, $sellerSkus) = LazadaCallbackHelper::imageUpload($reqParams);
            self::localLog("LazadaCallbackHelper::imageUpload sellerSku:" . print_r($sellerSkus, true));
        }

        if ($ret === true) {
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED_CALLED;
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_CHECKED_CALLED Error");
            $lazadaFeedList->save(false);
        } else {
            $lazadaFeedList->message = "mark all product error fail.";
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->next_execution_time = $nowTime + self::getCheckIntervalByType($lazadaFeedList->type);
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_FAIL;
            $lazadaFeedList->save(false);
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_FAIL checkAllFeedStatus Error save");
        }
    }

    /**
     * @param $lazadaFeedList
     * @param $nowTime
     * @param $feedInfoRet
     */
    private
    static function parseOthers($lazadaFeedList, $nowTime, $feedInfoRet)
    {
        $lazadaFeedList->TotalRecords = $feedInfoRet["TotalRecords"];
        $lazadaFeedList->ProcessedRecords = $feedInfoRet["ProcessedRecords"];
        $lazadaFeedList->FailedRecords = $feedInfoRet["FailedRecords"];
        if ($lazadaFeedList->FailedRecords > 0) {
            $lazadaFeedList->FeedErrors = json_encode($feedInfoRet["FeedErrors"]);
        }
        $lazadaFeedList->is_running = 0;
        $lazadaFeedList->next_execution_time = $nowTime + self::getCheckIntervalByType($lazadaFeedList->type);
        $lazadaFeedList->update_time = $nowTime;
        $lazadaFeedList->process_status = 0;
        $lazadaFeedList->save(false);
    }

// 复制自TrackingRecommendProductHelper ，next_execution_time 目前不判断，但保留设置
    private
    static function handleBgJobError($recommendJobObj, $errorMessage)
    {
        $nowTime = time();
        $recommendJobObj->status = 3;
        $recommendJobObj->error_count = $recommendJobObj->error_count + 1;
        
        // excel导入处理
        if(self::$job_name == $recommendJobObj->job_name){
            if($recommendJobObj->error_count >= 2){// 停止重试
                $recommendJobObj->status = 2;
                $recommendJobObj->is_active = "N";
            }else{
                $errorMessage .= "<br/>30分钟后系统自动重试导入。";
            }
        }
         
        $recommendJobObj->error_message .= $errorMessage;
        $recommendJobObj->last_finish_time = $nowTime;
        $recommendJobObj->update_time = $nowTime;
        $recommendJobObj->next_execution_time = $nowTime + 30 * 60;//半个小时后重试
        $recommendJobObj->save(false);
        return true;
    }

    /**
     * 根据不同的type返回检查的时间间隔。 秒为单位
     */
    public
    static function getCheckIntervalByType($type)
    {
        if ($type == self::PRODUCT_CREATE) {
            return 1 * 60;
        } else if ($type == self::PRODUCT_UPDATE) {// dzt20160108 调整修改后第一次检查feed 执行情况间隔时间 从20分钟到5分钟
            return 5 * 60;
        } else if ($type == self::PRODUCT_IMAGE_UPLOAD) {
            return 5 * 60;
        }
        return 30 * 60;

    }
    
    

    public static $job_name = "jumia_listing_from_excel";
    public static $custom_name_arr = array('import'=>"导入刊登产品", 'updateInfo'=>"导入更新产品信息", 
            'updateImage'=>"导入更新产品图片", 'delete'=>"导入删除产品");
    public static $status_name_map = array(0=>"等待执行", 1=>"执行中", 2=>"执行完成", 3=>"失败重试", 
            4=>"执行中，上传请求提交完成", 5=>"暂停提交，等待重新切换", 6=>"任务终止");
    
    // 后台任务检查feed
    public static $check_feed_job_name = "jumia_listing_import_feed_check";
    
    
    // 这些属性是xml的第一层属性，其他不在这里的属性都需要被包含在ProductData属性里面
    public static $notProDataProperties = array('Brand', 'Description', 'Name', 'Price', 'PrimaryCategory', 'SellerSku', 'TaxClass', 'Categories', 'Condition',
            'CountryCity', 'ParentSku', 'ProductGroup', 'ProductId', 'Quantity', 'SaleEndDate', 'SalePrice', 'SaleStartDate', 'ShipmentType',
            'Status', 'Variation', 'BrowseNodes');
    
    // 可以传HTML的字段 需要处理
    public static $markupProperties = array(
            'CareLabel', 'ManufacturerTxt', 'PackageContent', 'ShortDescription', 'Description', 'ProductWarranty', 'WarrantyAddress', 
    );
    
    // excel保存图片的字段
    public static $imageCols = array('MainImage', 'Image2', 'Image3', 'Image4', 'Image5', 'Image6', 'Image7', 'Image8');
    
    // excel导入更新产品的获取的字段
    public static $updateInfoCols = array('SellerSku', 'Price', 'SalePrice', 'SaleStartDate', 'SaleEndDate', 'Name', 'Quantity', 'Description');
    public static $updateImgCols = array('SellerSku', 'MainImage', 'Image2', 'Image3', 'Image4', 'Image5', 'Image6', 'Image7', 'Image8');
    public static $delCols = array('SellerSku');
    
    
    // 以防出异常丢失任务执行情况
    public static $successNum = 0;
    public static $rtnMessage = "";
    
    // 接口触发间隔
    // 间隔10秒 ng两个店铺同时上传100个商品，两个都一个渠道80几才出现限制，一个到99才出现限制。
    public static $sleepInterval = 10;
    
    /**
     *  后台触发----- 处理后台导入任务
    */
    public static function handleImportJob(){
        $job_name = self::$job_name;
    
        $nowTime = time();
        $checkRunningJobs = UserBackgroundJobControll::find()->where("job_name='{$job_name}' and (status=4 or status=1)")->all();
        foreach ($checkRunningJobs as $runningJob){
            echo __FUNCTION__." puid:".$runningJob->puid.",job id:".$runningJob->id." status:".$runningJob->status." is still running.--".date("Ymd H:i:s", $nowTime).PHP_EOL;
        
            // 防止回写callback不能正常修改结束状态 这里判断再修改
            if($runningJob->status == 4 || ($runningJob->status == 1 && $runningJob->custom_name<>'import')){
                // 检查子任务重置父任务状态
                $lastCount = UserBackgroundJobControll::find()->where(['job_name'=>self::$check_feed_job_name, 'execution_request'=>$runningJob->id])->count();
                if($lastCount == 0){
                    $runningJob->is_active = "N";
                    $runningJob->status = 2;
                    $runningJob->save(false);
                    echo __FUNCTION__." puid:".$runningJob->puid.",job id:".$runningJob->id." site is done running reset status.--".date("Ymd H:i:s", $nowTime).PHP_EOL;
                    continue;
                }else{// 有子任务在跑继续执行
                    continue;
                }
            }
            
            // 12小时还在跑的就关了
            $twelveHoursAgo = time() - 12 * 3600;
            if($runningJob->last_begin_run_time < $twelveHoursAgo){
                $runningJob->error_message .= "未知原因运行超过12小时，强制终止";
                $runningJob->is_active = "N";
                $runningJob->status = 3;
                $runningJob->error_count = 5;
                $runningJob->save(false);
                
                echo __FUNCTION__." puid:".$runningJob->puid.",job id:".$runningJob->id." terminate job.--".date("Ymd H:i:s", $nowTime).PHP_EOL;
            }
        }
        
        //status： 0 未处理 ，1处理中 ，2完成 ，3失败，4提交完成，等子任务执行，5间断执行
        $jobObjs = UserBackgroundJobControll::find()
        ->where('is_active="Y" AND status in (0,3,5) AND job_name="' . $job_name . '" AND error_count<5')
        ->andWhere('next_execution_time<'.$nowTime)
        ->orderBy('next_execution_time')
//         ->limit(5)// 不加limit了 有些等待执行的站点进不来
        ->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
        
        echo __FUNCTION__." start --".date("Ymd H:i:s", $nowTime).PHP_EOL;
        $allAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
        if (!empty($jobObjs)) {
            foreach ($jobObjs as $jobObj) {
                $nowTime = time();
                $puid = $jobObj->puid;
                $additionalInfo = json_decode($jobObj->additional_info, true);
                self::$successNum = 0;
                self::$rtnMessage = "";
                if(empty($allAccountsInfoMap[$additionalInfo['lazada_uid']])){
                    $jobObj->error_count = 4;// 不重试
                    self::handleBgJobError($jobObj, "账号不存在");
                    continue;
                }
                
                $tempConfig = $allAccountsInfoMap[$additionalInfo['lazada_uid']];
                $tempConfig['lazada_uid'] = $additionalInfo['lazada_uid'];
                // 开启多进程后，相同站点不重复运行避免平台调用限制
                $skip = false;
                
                $runningJobs = UserBackgroundJobControll::find()->where(['job_name'=>$job_name,'status'=>[1,4]])->all();
                foreach ($runningJobs as $runningJob){
                    echo __FUNCTION__." puid:".$runningJob->puid.",job id:".$runningJob->id." is still running.--".date("Ymd H:i:s", $nowTime).PHP_EOL;
                    $tmpAddInfo = json_decode($runningJob->additional_info, true);
                    if(empty($allAccountsInfoMap[$tmpAddInfo['lazada_uid']])){
                        continue;
                    }
                    
                    // 已有站点在运行
                    if($tempConfig['countryCode'] == $allAccountsInfoMap[$tmpAddInfo['lazada_uid']]['countryCode']){
                        $skip = true;
                    }
                    
                    // 测试改为店铺级别限制看是否有限制接口
                    // 基本确定是站点基本的限制，ng两个店铺上传100个商品，默认一秒间隔的情况，一个早一分钟触发，去到第69个产品出现限制，第二个店铺则第8个就开始被限制了
//                     if($tmpAddInfo['lazada_uid'] == $tempConfig['lazada_uid']){
//                         $skip = true;
//                     }
                }
                
                if($skip){
                    $attrs = array();
                    $attrs['last_begin_run_time'] = $nowTime;
                    // 不修改next_execution_time了 否则有些任务 asc排序一直不稳定 
                    // $attrs['next_execution_time'] = $nowTime + 30 * 60;// 半小时后重试
                    $affectRows = UserBackgroundJobControll::updateAll($attrs,['id'=>$jobObj->id, 'status'=>[0, 3, 5]]);
                    echo __FUNCTION__." puid:$puid,job id:".$jobObj->id." skip, same site is running:".$tempConfig['countryCode']."--".date("Ymd H:i:s", $nowTime).PHP_EOL;
                    continue;
                }
                
                $attrs = array();
                $attrs['status'] = 1;
                $attrs['last_begin_run_time'] = $nowTime;
                $affectRows = UserBackgroundJobControll::updateAll($attrs,['id'=>$jobObj->id, 'status'=>[0, 3, 5]]);
                $jobObj->refresh();
             
                if (empty($affectRows)){//抢不到---如果是多进程的话，有抢不到的情况
                    echo __FUNCTION__." puid:$puid,job id:".$jobObj->id." is running--".date("Ymd H:i:s", $nowTime).PHP_EOL;
                    continue;
                }
                    
                
                $file = array();
                $file['name'] = $additionalInfo['fileName'];
                $file['tmp_name'] = $additionalInfo['filePath'];
				echo "memory usage1:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
                
                // 从excel读出数据
				$extension = strtolower(substr($file['name'] , strripos($file['name'],'.') + 1 )) ;
				if(empty($file['name'])){
				    return TranslateHelper::t("文件上传失败,请按'F5'刷新页面再操作导入产品。");
				}
				
				
				if(strtolower(trim($extension)) == 'csv'){
				    $delimiters = ["\t", ";", ","];// 只处理这3种分隔符
				    $delimiter = "\t";// 默认分隔符
				    
				    $productsData = [];
				    $handle = fopen($file['tmp_name'],"r");
				    
				    foreach ($delimiters as $tryDelimiter){
				        $data = fgetcsv($handle,100000, $tryDelimiter);
				        if(count($data)>1){
				            $delimiter = $tryDelimiter;
				            rewind($handle);
				            break;
				        }
				        
				        rewind($handle);
				    }
				    
				    $i = 0;
				    while($data = fgetcsv($handle,100000, $delimiter)) {
				        if(!empty($data) && (count($data) > 1 || !empty($data[0]))){// 排除空行
				            $productsData[++$i] = $data;
				        }else{
				            echo "num:$i empty row.".PHP_EOL;
				        }
				    }
				    fclose($handle);
				    
				}else{
				    $productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($file, array(), false);
				}
                
                echo "memory usage2:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
                // \yii::info(json_encode($productsData), "file");
                try {
                    $totalNum = count($productsData) - 1;
                    if(empty($additionalInfo['totalNum'])){
                        $additionalInfo['totalNum'] = $totalNum;
                    }
                    
                    if(empty($additionalInfo['failNum']))
                        $additionalInfo['failNum'] = 0;
                    
                    // 主任务execution_request 用于记录执行offset
                    if($jobObj->execution_request == 0){
                        $jobObj->execution_request = 1;// 产品数组从下标2开始为第一个产品
                    }
                    
                    $jobObj->additional_info = json_encode($additionalInfo);
                    $jobObj->save(false);
                    
                    // 检查字段名是否合法
                    $columnMap = $productsData[1];
                    Helper_Array::removeEmpty($columnMap);
                    echo "columns:".print_r($columnMap, true).PHP_EOL;
                    $errColumnMsg = "";
                    foreach ($columnMap as $col=>$columnName){
                        $columnName = trim($columnName);
                        if(!preg_match('/^[a-zA-Z2-8]+$/', $columnName)){
                            if(!is_numeric($col)){
                                $errColumnMsg .= "{$col}列，字段名：'{$columnName}' 不合法<br>";
                            }else{
                                $errColumnMsg .= "字段名：'{$columnName}' 不合法<br>";
                            }
                        }
                    }
                    if(!empty($errColumnMsg))
                        throw new \Exception($errColumnMsg);
                    
                    
                    if($jobObj->custom_name == "import"){// 处理新建产品导入
                        
                        // 20000+产品内存炸了，切割数组
//                         $successNum = 0;
//                         $message = "";
//                         unset($productsData[1]);// 去除字段行  
//                         $productsDataGroup = array_chunk($productsData, 1000, true);
//                         unset($productsData);
//                         echo __FUNCTION__." before _processImportProductCreate total ".count($productsDataGroup)." group, memory usage:".round(memory_get_usage()/1024/1024, 2)."M.".PHP_EOL;
//                         foreach ($productsDataGroup as $index=>$oneGroupData){
//                             $oneGroupData[1] = $columnMap;
//                             echo __FUNCTION__." before _processImportProductCreate group index:$index,puid:$puid,job id:".$jobObj->id.PHP_EOL;
//                             list($oneSuccessNum, $oneMessage) = self::_processImportProductCreate($tempConfig, $oneGroupData, $jobObj);
//                             echo __FUNCTION__." after _processImportProductCreate group index:$index,puid:$puid,job id:".$jobObj->id.",successNum:$oneSuccessNum, message:$oneMessage".PHP_EOL;
//                             $successNum += $oneSuccessNum;
//                             $message .= $oneMessage;
//                             echo "memory usage group index:$index,:".round(memory_get_usage()/1024/1024, 2)."M.".PHP_EOL;
//                         }
//                         echo __FUNCTION__." done _processImportProductCreate memory usage:".round(memory_get_usage()/1024/1024, 2)."M. puid:$puid,job id:".$jobObj->id.",successNum:$successNum, message:$message".PHP_EOL;
                        
                        // 产品太多还可能会导致脚本运行过程而被kill
                        // array_slice 的offseta是根据数组的个数顺序来的，并不是根据数组的下标来处理的
                        // 所以后面execution_request 是加上数组count就可以，而不是等于最后一个元素的下标。
                        $productsDataGroup = array_slice($productsData, $jobObj->execution_request, 1000, true);
                        $groupNum = count($productsDataGroup);
                        unset($productsData);
                        $productsDataGroup[1] = $columnMap;
                        echo __FUNCTION__." before _processImportProductCreate group count:{$groupNum} , memory usage:".round(memory_get_usage()/1024/1024, 2)."M.".PHP_EOL;
                        list($successNum, $message) = self::_processImportProductCreate($tempConfig, $productsDataGroup, $jobObj);
                        echo __FUNCTION__." done _processImportProductCreate memory usage:".round(memory_get_usage()/1024/1024, 2)."M. puid:$puid,job id:".$jobObj->id.",successNum:$successNum, message:$message".PHP_EOL;

                        $jobObj->refresh();// 可能已经有子任务执行完，需要更新
                        $additionalInfo = json_decode($jobObj->additional_info, true);
                        // "成功提交".$successNum."个SKU，失败".(count($productsData)-$successNum)."个。".
                        if(!empty($additionalInfo['failNum'] )){
                            $additionalInfo['failNum'] = $additionalInfo['failNum'] + $groupNum - $successNum;
                        }else{
                            $additionalInfo['failNum'] = $groupNum - $successNum;
                        }
                        
                        // 这里先写入报错信息，等后续子任务执行完才修改主任务状态
                        $jobObj->error_message = $message.$jobObj->error_message;
                        $jobObj->additional_info = json_encode($additionalInfo);
                    
                        $jobObj->execution_request += $groupNum;
                    }elseif($jobObj->custom_name == "updateInfo"){// 处理产品更新导入
                        
                        echo __FUNCTION__." before _processImportProductUpdate puid:$puid,job id:".$jobObj->id.PHP_EOL;
                        list($successNum, $message) = self::_processImportProductUpdate($tempConfig, $productsData, $jobObj);
                        echo __FUNCTION__." after _processImportProductUpdate puid:$puid,job id:".$jobObj->id.",successNum:$successNum, message:$message".PHP_EOL;
                        
                        $jobObj->refresh();// 可能已经有子任务执行完，需要更新
                        $additionalInfo = json_decode($jobObj->additional_info, true);
                        if(!empty($additionalInfo['failNum'] )){
                            $additionalInfo['failNum'] = $additionalInfo['failNum'] + $totalNum - $successNum;
                        }else{
                            $additionalInfo['failNum'] = $totalNum - $successNum;
                        }
                        
                        // 这里先写入报错信息，等后续子任务执行完才修改主任务状态
                        $jobObj->error_message = $message.$jobObj->error_message;
                        $jobObj->additional_info = json_encode($additionalInfo);
                        
                        $jobObj->execution_request += $totalNum;
                    }elseif($jobObj->custom_name == "updateImage"){// 处理产品图片修改
                    
                        echo __FUNCTION__." before _processImportProductUpdateImage puid:$puid,job id:".$jobObj->id.PHP_EOL;
                        list($successNum, $message) = self::_processImportProductUpdateImage($tempConfig, $productsData, $jobObj);
                        echo __FUNCTION__." after _processImportProductUpdateImage puid:$puid,job id:".$jobObj->id.",successNum:$successNum, message:$message".PHP_EOL;
                        
                        $jobObj->refresh();// 可能已经有子任务执行完，需要更新
                        $additionalInfo = json_decode($jobObj->additional_info, true);
                        if(!empty($additionalInfo['failNum'] )){
                            $additionalInfo['failNum'] = $additionalInfo['failNum'] + $totalNum - $successNum;
                        }else{
                            $additionalInfo['failNum'] = $totalNum - $successNum;
                        }
                        
                        // 这里先写入报错信息，等后续子任务执行完才修改主任务状态
                        $jobObj->error_message = $message.$jobObj->error_message;
                        $jobObj->additional_info = json_encode($additionalInfo);
                        
                        $jobObj->execution_request += $totalNum;
                    }elseif($jobObj->custom_name == "delete"){// 处理产品删除导入
                        echo __FUNCTION__." before _processImportProductDelete puid:$puid,job id:".$jobObj->id.PHP_EOL;
                        list($successNum, $message) = self::_processImportProductDelete($tempConfig, $productsData, $jobObj);
                        echo __FUNCTION__." after _processImportProductDelete puid:$puid,job id:".$jobObj->id.",successNum:$successNum, message:$message".PHP_EOL;
                        
                        $jobObj->refresh();// 可能已经有子任务执行完，需要更新
                        $additionalInfo = json_decode($jobObj->additional_info, true);
                        if(!empty($additionalInfo['failNum'] )){
                            $additionalInfo['failNum'] = $additionalInfo['failNum'] + $totalNum - $successNum;
                        }else{
                            $additionalInfo['failNum'] = $totalNum - $successNum;
                        }
                        // 这里先写入报错信息，等后续子任务执行完才修改主任务状态
                        $jobObj->error_message = $message.$jobObj->error_message;
                        $jobObj->additional_info = json_encode($additionalInfo);
                        
                        $jobObj->execution_request += $totalNum;
                    }
                    
                    
                } catch (\Exception $e) {
                    echo "handleImportJob Exception:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage().PHP_EOL;
                    \Yii::error("handleImportJob Exception:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(), "file");
                    if (!empty($jobObj)){
                        $jobObj->refresh();
                        $additionalInfo = json_decode($jobObj->additional_info, true);
                        $additionalInfo['failNum'] = $additionalInfo['totalNum'] - self::$successNum;
                        $jobObj->additional_info = json_encode($additionalInfo);
                        $jobObj->error_count = 4; // 这里Exception 不重试 
                        self::handleBgJobError($jobObj, self::$rtnMessage."<br>".$e->getMessage());
                    }
                    
                    continue;
                }
                
                $nowTime = time();
                // 导入刊登才有分批计算
                if($jobObj->custom_name == "import" && !empty($jobObj->execution_request) && ($jobObj->execution_request - 1) < $totalNum){
                    $jobObj->status = 5;
                    $jobObj->next_execution_time = $nowTime + 10 * 60;
                }else{
                    $jobObj->status = 4;
                }
                
                $jobObj->last_finish_time = $nowTime;
                $jobObj->error_count = 0;
                $jobObj->update_time = $nowTime;
                $jobObj->save(false);
                
            }
        }
    }
    
    // 由于feed表不好加数据，所以feed处理里面不好加后续处理逻辑，所以另外添加feed检查后台Job 处理feed结果后续操作
    private static function _addBackgroundJob($puid, $feedType, $addInfo){
        $nowTime = time();
        
        $userBgControl = new UserBackgroundJobControll();
        $userBgControl->puid = $puid;
        $userBgControl->job_name = self::$check_feed_job_name;
        $userBgControl->create_time = $nowTime;
        $userBgControl->custom_name = $feedType;
         
        $userBgControl->status = 0;// 0 未处理 ，1处理中 ，2完成 ，3失败
        $userBgControl->error_count = 0;
        $userBgControl->is_active = "Y";// 运行完之后，删除
        // 记录feedId 来源BackgroundJob id用于放回结果，提交的product sku等部分信息
        $userBgControl->additional_info = json_encode($addInfo);
        
        // 通过source job加减execution_request 来确认source任务是否完成风险依然很大
        // 更新值的时候 不能字段加一减一那样更新，而是覆盖值，这样导致正在减的，遇上正在加的则会互相覆盖结果。
        // 修改execution_request 值在这里的内容为子任务对应的父任务id
        $userBgControl->execution_request = $addInfo['sourceJobId'];
        
        // feed执行 2分钟后再执行
        $nextExecutionTime = $nowTime + LazadaFeedHelper::getCheckIntervalByType($feedType) + 2*60;
        $userBgControl->next_execution_time = $nextExecutionTime;
         
        $userBgControl->update_time = $nowTime;
        if(!$userBgControl->save(false)){
            return false;
        }else{
            return $userBgControl->id;
        }
    }
    
    // excel导入，处理刊登新产品
    // $productsData 从excel获取的数组
    private static function _processImportProductCreate($tempConfig, $productsData, $jobObj){
        // 获取第一行column，获取字段
        $columnMap = $productsData[1];
        Helper_Array::removeEmpty($columnMap);
        $productGroup = [];
        $skuRowIndexMap = [];
        $skuImagesMap = [];
        
        $initMsg = "";
        foreach ($productsData as $rowIndex=>$rowData){ //匹配字段及其数据重组数组
            Helper_Array::removeEmpty($rowData);
            if($rowIndex == 1){
                continue;
            }
            
            $mapRow = [];
            $images = [];
            foreach ($rowData as $col=>$val){
                if(!empty($columnMap[$col])){
                    
                    $columnName = $columnMap[$col];
                    // 处理图片字段
                    if(in_array($columnName, self::$imageCols)){
                        if($columnName == 'MainImage'){
                            array_unshift($images, $val);
                        }else{
                            $images[] = $val;
                        }
                        
                        continue;// 提交产品不用提交图片字段
                    }
                    
                    if(in_array($columnName, self::$markupProperties)){
                        $val = "<![CDATA[" . $val . "]]>";
                    }else{
                        $val = is_string($val)?LazadaApiHelper::transformFeedString($val):$val;
                    }
                       
                    if($columnName == 'JumiaLocal' && strtolower($val) == 'yes'){
                        $val = true;
                    }
                    
                    
                    if(in_array($columnName, self::$notProDataProperties)){
                        $mapRow[$columnName] = $val;
                    }else{
                        $mapRow['ProductData'][$columnName] = $val;
                    }
                }
            }
        
            if(empty($mapRow['SellerSku'])){
                $newErrMsg = "SellerSku不存在<br />";
                $rowMsg = "第{$rowIndex}行，";
                if(strpos($initMsg, $newErrMsg) !== false){
                    $initMsg = str_replace($newErrMsg, $rowMsg.$newErrMsg, $initMsg);
                }else{
                    $initMsg .= $rowMsg.$newErrMsg;
                }
                echo PHP_EOL."row:{$rowIndex}, SellerSku not exist.";
                continue;
            }
            
//             Helper_Array::removeEmpty($images);
//             $mapRow['Images'] = implode(',', $images);
                    
            // 没有ParentSku 的单个重复
            if(empty($mapRow['ParentSku']) && !empty($productGroup[$mapRow['SellerSku']])){
                $newErrMsg = "产品sku重复<br />";
                $rowMsg = "第{$rowIndex}行，";// 后续可以再添加上被重复会提交的那行的信息，但这样报错信息可能比较多。
                if(strpos($initMsg, $newErrMsg) !== false){
                    $initMsg = str_replace($newErrMsg, $rowMsg.$newErrMsg, $initMsg);
                }else{
                    $initMsg .= $rowMsg.$newErrMsg;
                }
                echo PHP_EOL."row:{$rowIndex},SellerSku:".$mapRow['SellerSku']." repeat from row:".$skuRowIndexMap[$mapRow['SellerSku']];
                continue;
            }
            
            $groupTag = empty($mapRow['ParentSku'])?$mapRow['SellerSku']:$mapRow['ParentSku'];
            if(empty($productGroup[$groupTag])){
                $productGroup[$groupTag] = [];
            }
        
            $skuRowIndexMap[$mapRow['SellerSku']] = $rowIndex;
            $skuImagesMap[$mapRow['SellerSku']] = $images;
            $productGroup[$groupTag][] = $mapRow;
            
        }
        
        if(!empty($initMsg)){
            $initMsg = "<br>".$initMsg;
        }
//         \yii::info(json_encode($productGroup), "file");
        
        // 测试没有子产品的多个产品能否一起发布
        // 测试成功 可以合在一起发布
        $regroup = [];
        $colectArr = [];
        $gi = 1;
        $skuParentSkuMap = [];// for 变参图片上传重复
        foreach ($productGroup as $parentSku=>$groupData){
            if(count($groupData) == 1){
                $colectArr = array_merge($colectArr, $groupData);
               
            }else{
                // 另一个发现变参数量不多 导致一次上传数量太少，太慢
                // $regroup[$parentSku] = $groupData;
                
                
                // 将parentSku的产品抽出来排第一个
                $tempGroup = [];
                foreach ($groupData as $prodData){
                    $skuParentSkuMap[$prodData['SellerSku']] = $parentSku;
                    if($parentSku == $prodData['SellerSku'])
                        $colectArr[] = $prodData;
                    else
                        $tempGroup[] = $prodData;
                }
                
                $colectArr = array_merge($colectArr, $tempGroup);
                 
            }
            
            // 两种组合到一起看看
            if(count($colectArr) >= 20){// 一次最多上传到接口的产品个数
                $regroup["regroup".$gi] = $colectArr;
                $gi++;
                $colectArr = [];
            }
        }
        
        if(!empty($colectArr)){
            $regroup["regroup".$gi] = $colectArr;
            $colectArr = [];
        }
        
        $config = array(
            "userId" => $tempConfig["userId"],
            "apiKey" => $tempConfig["apiKey"],
            "countryCode" => $tempConfig["countryCode"],
        );
        
        $message = $initMsg."<br />";
        $successNum = 0;
        $successTryCount = 0;
        $doCount = 0;
//         foreach ($productGroup as $parentSku=>$groupData){
        foreach ($regroup as $parentSku=>$groupData){// 测试没有子产品的多个产品能否一起发布
            // 循环10次停一下，大概就是20个一批就30秒一次，这样10次就可以上传200个产品5分钟，平均下来就是15分钟900s上传200个产品
            // 如果改成20次，就是20分钟1200s上传400个产品，微笑要求改成只歇2分钟，但之前试过 一旦出现429歇5分钟一样不行，所以只能增加成一次跑20批10分钟试试看
            if($successTryCount == 10){
                $sleep = 600;
                echo PHP_EOL."{$successTryCount} success, alternative sleep {$sleep}s ,now do:{$doCount},success:{$successNum}".PHP_EOL;
                $successTryCount = 0;// 重置
                if(self::$sleepInterval > 60){// 每次间隔时间太长的，多次成功后可以减一下
                    self::$sleepInterval -= 10;
                }
            }else{
                $sleep = self::$sleepInterval + count($groupData);
                echo PHP_EOL."sleep ".$sleep."s".PHP_EOL;
            }
            
            sleep($sleep);// 请求太快jumia返回 httpcode 429 too many request问题
            
            $doCount += count($groupData);
            
            $rowMsg = "";
            $toUpProducts = [];
//             if(count($groupData) == 1){
//                 if(!empty($skuRowIndexMap[$groupData[0]['SellerSku']])){
//                     $rowMsg .= "第".$skuRowIndexMap[$groupData[0]['SellerSku']]."行，";
//                 }
            // 测试没有子产品的多个产品能否一起发布
            if(strpos($parentSku, "regroup") !== false){
            }else{
            }
            
            foreach ($groupData as $prodData){
                if(!empty($skuRowIndexMap[$prodData['SellerSku']])){
                    $rowMsg .= "第".$skuRowIndexMap[$prodData['SellerSku']]."行，";
                }
            }
            
            $toUpProducts = $groupData;
            
            echo __FUNCTION__." before LazadaInterface_Helper::productCreate --".date("Ymd H:i:s", time())."-- products:".json_encode($toUpProducts).PHP_EOL;
            $response = LazadaInterface_Helper::productCreate($config, array('products' => $toUpProducts));
            echo __FUNCTION__." after LazadaInterface_Helper::productCreate --".date("Ymd H:i:s", time())."-- response:".json_encode($response).PHP_EOL;
            
            if($response['success'] != true){
                // 429 too many request问题
                if(isset($response['response']) && isset($response['response']['state_code'])
                        && 429 == $response['response']['state_code']){
                    echo PHP_EOL."429 too many request sleep 600s".PHP_EOL;
                    if(self::$sleepInterval < 100)
                        self::$sleepInterval += 10; // 每次报错递增间隔10秒
                    
                    sleep(600);
                }
                    
                // 重复的报错信息
                $newErrMsg = "产品刊登失败：".$response['message']."<br />";
                if(strpos($message, $newErrMsg) !== false){
                    $message = str_replace($newErrMsg, $rowMsg.$newErrMsg, $message);
                }else{
                    $message .= $rowMsg.$newErrMsg;
                }
                
                self::$rtnMessage = $message;
            }else{
                $successTryCount++;
                $feedId = $response['response']['body']['Head']['RequestId'];
                
                $addFeedRet = LazadaFeedHelper::insertFeed($tempConfig['puid'], $tempConfig['lazada_uid'], $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE2);
                if($addFeedRet){
                    echo __FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_CREATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId ;
                    \Yii::info(__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_CREATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= $rowMsg."刊登请求提交成功后， add feed failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_CREATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId ;
                    \Yii::error(__FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_CREATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
                
                // 保留信息到后续上传图片等操作。
                $toUpImages = [];
                $toUpSkus = [];
                $toUpProdIndexs = [];
                foreach ($toUpProducts as $prodInfo){
                    $toUpSkus[] = $prodInfo['SellerSku'];
                    if(isset($skuParentSkuMap[$prodInfo['SellerSku']])){// 变参子产品只上传父产品图片
                        // 出现客户有定义父sku字段，但父sku的记录不存在，所以父sku没有图片，这时候用自己列的图片代替，上传的sku则为父sku，避免重复
                        if(empty($skuImagesMap[$skuParentSkuMap[$prodInfo['SellerSku']]])){
                            $toUpImages[$skuParentSkuMap[$prodInfo['SellerSku']]] = $skuImagesMap[$prodInfo['SellerSku']];
                        }else{
                            $toUpImages[$skuParentSkuMap[$prodInfo['SellerSku']]] = $skuImagesMap[$skuParentSkuMap[$prodInfo['SellerSku']]];
                        }
                    }else{
                        $toUpImages[$prodInfo['SellerSku']] = $skuImagesMap[$prodInfo['SellerSku']];
                    }
                    
                    $toUpProdIndexs[$prodInfo['SellerSku']] = $skuRowIndexMap[$prodInfo['SellerSku']];
                }
                
                $addInfo = [
                    'feedId'=>$feedId, 
                    'sourceJobId'=>$jobObj->id,
                    'images'=>$toUpImages,
                    'skus'=>$toUpSkus,
                    'prodIndexs'=>$toUpProdIndexs,
                ];
                
                $newJobId = self::_addBackgroundJob($tempConfig['puid'], LazadaFeedHelper::PRODUCT_CREATE2, $addInfo);
                if($newJobId !== false){
                    
                    $jobObj->refresh();// 更新类的值，尽量确保execution_request 更新了
                    $sourceAddInfo = json_decode($jobObj->additional_info, true);
                    if(empty($sourceAddInfo['subjobId']))
                        $sourceAddInfo['subjobId'] = [];
                    $sourceAddInfo['subjobId'][$newJobId] = "pending";
                    
                    $jobObj->additional_info = json_encode($sourceAddInfo);
                    // $jobObj->execution_request ++;// 记录子任务数，不再记录子任务数，而是改为子任务对应的父任务id
                    $jobObj->save(false);
                    echo __FUNCTION__." _addBackgroundJob ".LazadaFeedHelper::PRODUCT_CREATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::info(__FUNCTION__." _addBackgroundJob ".LazadaFeedHelper::PRODUCT_CREATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= $rowMsg."刊登feed插入成功后， add BackgroundJob failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_CREATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_CREATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
                
                $successNum += count($toUpProducts);
                self::$successNum = $successNum;
            }
        }
        
        return array($successNum, $message);
    }
    
    
    // excel导入，处理更新产品信息
    // $productsData 从excel获取的数组
    private static function _processImportProductUpdate($tempConfig, $productsData, $jobObj){
        // 获取第一行column，获取字段
        $columnMap = $productsData[1];
        
        $skuRowIndexMap = [];
        $prodColMapData = [];
        foreach ($productsData as $rowIndex=>$rowData){ //匹配字段及其数据重组数组
                if($rowIndex == 1){
                    continue;
                }
            
            $mapRow = [];
            $images = [];
            foreach ($rowData as $col=>$val){
                if(!empty($columnMap[$col])){
        
                    $columnName = $columnMap[$col];
                    if(in_array($columnName, self::$markupProperties)){
                        $val = "<![CDATA[" . $val . "]]>";
                    }else{
                        $val = is_string($val)?LazadaApiHelper::transformFeedString($val):$val;
                    }
                    
                    
                    if(in_array($columnName, self::$updateInfoCols)){
                        if(in_array($columnName, self::$notProDataProperties)){
                            $mapRow[$columnName] = $val;
                        }else{
                            $mapRow['ProductData'][$columnName] = $val;
                        }
                    }
                }
            }
        
            $skuRowIndexMap[$mapRow['SellerSku']] = $rowIndex;
            
            // SellerSku 用于查找，不能修改
            $prodColMapData[] = $mapRow;
        }
        
        $config = array(
            "userId" => $tempConfig["userId"],
            "apiKey" => $tempConfig["apiKey"],
            "countryCode" => $tempConfig["countryCode"],
        );
        
        $message = "";
        $successNum = 0;
        $successTryCount = 0;
        
        //由于更新信息可以组合发布，这里分割数组再发布
        $prodGroup = array_chunk($prodColMapData, 50);
        foreach ($prodGroup as $toUpProducts){
            if($successTryCount == 10){// 循环10次停一下
                $sleep = 600;
                echo PHP_EOL."{$successTryCount} success, alternative sleep ".$sleep."s".PHP_EOL;
                $successTryCount = 0;// 重置
                if(self::$sleepInterval > 60){// 每次间隔时间太长的，多次成功后可以减一下
                    self::$sleepInterval -= 10;
                }
            }else{
                $sleep = self::$sleepInterval + count($toUpProducts);
                echo PHP_EOL."sleep ".$sleep."s".PHP_EOL;
            }
            
            sleep($sleep);// 请求太快jumia返回 httpcode 429 too many request问题
            
            $response = LazadaInterface_Helper::productUpdate($config,array('products'=>$toUpProducts));
            echo __FUNCTION__." after LazadaInterface_Helper::productUpdate response:".json_encode($response).PHP_EOL;
            
            if($response['success'] != true){
                // 429 too many request问题
                if(isset($response['response']) && isset($response['response']['state_code'])
                        && 429 == $response['response']['state_code']){
                    echo PHP_EOL."429 too many request sleep 600s".PHP_EOL;
                    if(self::$sleepInterval < 100)
                        self::$sleepInterval += 10;
                    
                    sleep(600);
                }
                
                // 因为这里分组操作，不是一个个产品一个任务提交，所以出现问题这里就不进行简单重试。直接报错。
                // 转换状态
                $failRows = [];
                foreach ($toUpProducts as $prodInfo){
                    $failRows[] = $skuRowIndexMap[$prodInfo['SellerSku']];
                }
                
                $message .= "提交的第".implode(",", $failRows)."行，产品提交修改失败：".$response['message']."<br />";
                self::$rtnMessage = $message;
            }else{
                $successTryCount++;
                $feedId = $response['response']['body']['Head']['RequestId'];
            
                $addFeedRet = LazadaFeedHelper::insertFeed($tempConfig['puid'], $tempConfig['lazada_uid'], $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE2);
                if($addFeedRet){
                    \Yii::info(__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add feed failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
            
                // 保留信息到后续检查feed操作。
                $toUpSkus = [];
                $toUpProdIndexs = [];
                foreach ($toUpProducts as $prodInfo){
                    $toUpSkus[] = $prodInfo['SellerSku'];
                    $toUpProdIndexs[$prodInfo['SellerSku']] = $skuRowIndexMap[$prodInfo['SellerSku']];
                }
                
                $addInfo = [
                        'feedId'=>$feedId,
                        'sourceJobId'=>$jobObj->id,
                        'skus'=>$toUpSkus,
                        'prodIndexs'=>$toUpProdIndexs,
                ];
                
                $newJobId = self::_addBackgroundJob($tempConfig['puid'], LazadaFeedHelper::PRODUCT_UPDATE2, $addInfo);
                if($newJobId !== false){
                    $jobObj->refresh();// 更新类的值，尽量确保execution_request 更新了
                    $sourceAddInfo = json_decode($jobObj->additional_info, true);
                    if(empty($sourceAddInfo['subjobId']))
                        $sourceAddInfo['subjobId'] = [];
                    $sourceAddInfo['subjobId'][$newJobId] = "pending";
                    
                    $jobObj->additional_info = json_encode($sourceAddInfo);
                    // $jobObj->execution_request ++;// 记录子任务数，不再记录子任务数，而是改为子任务对应的父任务id
                    $jobObj->save(false);
                
                    \Yii::info(__FUNCTION__." _addBackgroundJob execution_request:".$jobObj->execution_request.LazadaFeedHelper::PRODUCT_UPDATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add BackgroundJob failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
                
                $successNum += count($toUpProducts);
                self::$successNum = $successNum;
            }
        }
        
        return array($successNum, $message);
    }
    
    
    // excel导入，处理更新产品图片
    // $productsData 从excel获取的数组
    private static function _processImportProductUpdateImage($tempConfig, $productsData, $jobObj){
        // 获取第一行column，获取字段
        $columnMap = $productsData[1];
        
        $skuRowIndexMap = [];
        $skuImagesMap = [];
        foreach ($productsData as $rowIndex=>$rowData){ //匹配字段及其数据重组数组
            if($rowIndex == 1){
                continue;
            }
            
            $mapRow = [];
            $images = [];
            foreach ($rowData as $col=>$val){
                if(!empty($columnMap[$col])){
        
                    $columnName = $columnMap[$col];
                    // 处理图片字段
                    if(in_array($columnName, self::$imageCols)){
                        if($columnName == 'MainImage'){
                            array_unshift($images, $val);
                        }else{
                            $images[] = $val;
                        }
                    }
                    
                    
                    if(in_array($columnName, self::$markupProperties)){
                        $val = "<![CDATA[" . $val . "]]>";
                    }else{
                        $val = is_string($val)?LazadaApiHelper::transformFeedString($val):$val;
                    }
                     
                    if(in_array($columnName, self::$updateImgCols)){
                        if(in_array($columnName, self::$notProDataProperties)){
                            $mapRow[$columnName] = $val;
                        }else{
                            $mapRow['ProductData'][$columnName] = $val;
                        } 
                    }
                }
            }
        
            $skuRowIndexMap[$mapRow['SellerSku']] = $rowIndex;
        
            // SellerSku 用于查找，不能修改
            // 这里没有parentSku区分同一子产品的图片来避免重复上传，所以全部都上传
            // 图片修改是覆盖
            Helper_Array::removeEmpty($images);
            $skuImagesMap[$mapRow['SellerSku']] = $images;
        }
        
        
        $config = array(
            "userId" => $tempConfig["userId"],
            "apiKey" => $tempConfig["apiKey"],
            "countryCode" => $tempConfig["countryCode"],
        );
        
        $message = "";
        $successNum = 0;
        $successTryCount = 0;
        
        // 由于更新图片可以批量修改，这里分割数组再发布
        $prodGroup = array_chunk($skuImagesMap, 20, true);
        foreach ($prodGroup as $groupData){
            if($successTryCount == 10){// 循环10次停一下
                $sleep = 600;
                echo PHP_EOL."{$successTryCount} success, alternative sleep ".$sleep."s".PHP_EOL;
                $successTryCount = 0;// 重置
                if(self::$sleepInterval > 60){// 每次间隔时间太长的，多次成功后可以减一下
                    self::$sleepInterval -= 10;
                }
            }else{
                $sleep = self::$sleepInterval + count($groupData);
                echo PHP_EOL."sleep ".$sleep."s".PHP_EOL;
            }
            
            sleep($sleep);// 请求太快jumia返回 httpcode 429 too many request问题
            
            $toUpProducts = [];
            $toUpSkus = [];
            $toUpProdIndexs = [];
            foreach ($groupData as $sku=>$toUpImages){
                $uploads = [];
                $uploads['SellerSku'] = $sku;
                $uploads['Images'] = $toUpImages;
                $toUpProducts[] = $uploads;
                
                $toUpSkus[] = $sku;
                $toUpProdIndexs[$sku] = $skuRowIndexMap[$sku];
            }
            
            echo PHP_EOL.__FUNCTION__." before LazadaInterface_Helper::productsImage uploads:".json_encode($toUpProducts).PHP_EOL;
            $response = LazadaInterface_Helper::productsImage($config, array('products' => $toUpProducts));
            echo __FUNCTION__." after LazadaInterface_Helper::productsImage response:".json_encode($response).PHP_EOL;
            
            if($response['success'] != true){
                // 429 too many request问题
                if(isset($response['response']) && isset($response['response']['state_code'])
                        && 429 == $response['response']['state_code']){
                    echo PHP_EOL."429 too many request sleep 600s".PHP_EOL;
                    if(self::$sleepInterval < 100)
                        self::$sleepInterval += 10;
                    
                    sleep(600);
                }
                
                // 转换状态
                $failRows = [];
                foreach ($toUpSkus as $sku){
                    $failRows[] = $skuRowIndexMap[$sku];
                }
            
                $message .= "提交的第".implode(",", $failRows)."行，产品提交修改失败：".$response['message']."<br />";
                self::$rtnMessage = $message;
            }else{
                $successTryCount++;
                $feedId = $response['response']['body']['Head']['RequestId'];
            
                $addFeedRet = LazadaFeedHelper::insertFeed($tempConfig['puid'], $tempConfig['lazada_uid'], $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2);
                if($addFeedRet){
                    \Yii::info(__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add feed failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
            
                // 保留信息到后续检查feed操作。
                $addInfo = [
                        'feedId'=>$feedId,
                        'sourceJobId'=>$jobObj->id,
                        'skus'=>$toUpSkus,
                        'prodIndexs'=>$toUpProdIndexs,
                ];
            
                $newJobId = self::_addBackgroundJob($tempConfig['puid'], LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2, $addInfo);
                if($newJobId !== false){
                    $jobObj->refresh();// 更新类的值，尽量确保execution_request 更新了
                    $sourceAddInfo = json_decode($jobObj->additional_info, true);
                    if(empty($sourceAddInfo['subjobId']))
                        $sourceAddInfo['subjobId'] = [];
                    $sourceAddInfo['subjobId'][$newJobId] = "pending";
            
                    $jobObj->additional_info = json_encode($sourceAddInfo);
                    // $jobObj->execution_request ++;// 记录子任务数，不再记录子任务数，而是改为子任务对应的父任务id
                    $jobObj->save(false);
            
                    \Yii::info(__FUNCTION__." _addBackgroundJob ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add BackgroundJob failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_IMAGE_UPDATE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
            
                $successNum += count($toUpProducts);
                self::$successNum = $successNum;
            }
            
        }
        
        return array($successNum, $message);
    }
    
    // excel导入，删除产品
    // $productsData 从excel获取的数组
    private static function _processImportProductDelete($tempConfig, $productsData, $jobObj){
        // 获取第一行column，获取字段
        $columnMap = $productsData[1];
        
        $skuRowIndexMap = [];
        $toUpSkus = [];
        foreach ($productsData as $rowIndex=>$rowData){ //匹配字段及其数据重组数组
            if($rowIndex == 1){
                continue;
            }
            
            $mapRow = [];
            $images = [];
            foreach ($rowData as $col=>$val){
                if(!empty($columnMap[$col])){
        
                    $columnName = $columnMap[$col];
                    if(in_array($columnName, self::$markupProperties)){
                        $val = "<![CDATA[" . $val . "]]>";
                    }else{
                        $val = is_string($val)?LazadaApiHelper::transformFeedString($val):$val;
                    }
                     
                    if(in_array($columnName, self::$delCols)){
                        if(in_array($columnName, self::$notProDataProperties)){
                            $mapRow[$columnName] = $val;
                        }else{
                            $mapRow['ProductData'][$columnName] = $val;
                        }
                    }
                }
            }
        
            $skuRowIndexMap[$mapRow['SellerSku']] = $rowIndex;
            $toUpSkus[] = $mapRow['SellerSku'];
        }
        
        $config = array(
                "userId" => $tempConfig["userId"],
                "apiKey" => $tempConfig["apiKey"],
                "countryCode" => $tempConfig["countryCode"],
        );
        
        $message = "";
        $successNum = 0;
        $successTryCount = 0;
        
        // 由于删除产品可以批量操作，这里分割数组再删除
        $prodGroup = array_chunk($toUpSkus, 50, true);
        foreach ($prodGroup as $groupData){
            if($successTryCount == 10){// 循环10次停一下
                $sleep = 600;
                echo PHP_EOL."{$successTryCount} success, alternative sleep ".$sleep."s".PHP_EOL;
                $successTryCount = 0;// 重置
                if(self::$sleepInterval > 60){// 每次间隔时间太长的，多次成功后可以减一下
                    self::$sleepInterval -= 10;
                }
            }else{
                $sleep = self::$sleepInterval + 20;// count($groupData) delete 50个sku 一次 等待60秒感觉太长
                echo PHP_EOL."sleep ".$sleep."s".PHP_EOL;
            }
            
            sleep($sleep);// 请求太快jumia返回 httpcode 429 too many request问题
            
            $response = LazadaInterface_Helper::productDelete($config,array('SellerSkus'=>$groupData));
            echo __FUNCTION__." after LazadaInterface_Helper::productDelete response:".json_encode($response).PHP_EOL;
            
            if($response['success'] != true){
                // 429 too many request问题
                if(isset($response['response']) && isset($response['response']['state_code'])
                        && 429 == $response['response']['state_code']){
                    echo PHP_EOL."429 too many request sleep 600s".PHP_EOL;
                    if(self::$sleepInterval < 100)
                        self::$sleepInterval += 10;
                    
                    sleep(600);
                }
            
                // 因为这里分组操作，不是一个个产品一个任务提交，所以出现问题这里就不进行简单重试。直接报错。
                // 转换状态
                $failRows = [];
                foreach ($groupData as $sku){
                    $failRows[] = $skuRowIndexMap[$sku];
                }
            
                $message .= "提交的第".implode(",", $failRows)."行，产品提交修改失败：".$response['message']."<br />";
                self::$rtnMessage = $message;
            }else{
                $successTryCount++;
                $feedId = $response['response']['body']['Head']['RequestId'];
            
                $addFeedRet = LazadaFeedHelper::insertFeed($tempConfig['puid'], $tempConfig['lazada_uid'], $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_DELETE2);
                if($addFeedRet){
                    echo __FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_DELETE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::info(__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_DELETE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add feed failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_DELETE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  insertFeed ".LazadaFeedHelper::PRODUCT_DELETE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
            
                // 保留信息到后续检查feed操作。
                $toUpProdIndexs = [];
                foreach ($groupData as $sku){
                    $toUpProdIndexs[$sku] = $skuRowIndexMap[$sku];
                }
            
                $addInfo = [
                        'feedId'=>$feedId,
                        'sourceJobId'=>$jobObj->id,
                        'skus'=>$groupData,
                        'prodIndexs'=>$toUpProdIndexs,
                ];
            
                $newJobId = self::_addBackgroundJob($tempConfig['puid'], LazadaFeedHelper::PRODUCT_DELETE2, $addInfo);
                if($newJobId !== false){
                    $jobObj->refresh();// 更新类的值，尽量确保execution_request 更新了
                    $sourceAddInfo = json_decode($jobObj->additional_info, true);
                    if(empty($sourceAddInfo['subjobId']))
                        $sourceAddInfo['subjobId'] = [];
                    $sourceAddInfo['subjobId'][$newJobId] = "pending";
            
                    $jobObj->additional_info = json_encode($sourceAddInfo);
                    // $jobObj->execution_request ++;// 记录子任务数，不再记录子任务数，而是改为子任务对应的父任务id
                    $jobObj->save(false);
                    
                    echo __FUNCTION__." _addBackgroundJob ".LazadaFeedHelper::PRODUCT_DELETE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::info(__FUNCTION__." _addBackgroundJob ".LazadaFeedHelper::PRODUCT_DELETE2." success. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                }else{
                    $message .= "parentSku:$parentSku add BackgroundJob failed.feedId:".$feedId."<br />";
                    echo __FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_DELETE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId.PHP_EOL;
                    \Yii::error(__FUNCTION__."  _addBackgroundJob ".LazadaFeedHelper::PRODUCT_DELETE2." fail. puid:".$tempConfig['puid']." lazada_uid:".$tempConfig['lazada_uid']." site:".$tempConfig["countryCode"]." feedId:".$feedId,"file");
                    self::$rtnMessage = $message;
                    continue;
                }
            
                $successNum += count($groupData);
                self::$successNum = $successNum;
            }
        }
        
        return array($successNum, $message);
    }
    
    
    
    
    
    
    
    
    
}