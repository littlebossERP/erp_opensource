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
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use common\mongo\lljListing\RawResponseDate;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\models\LazadaFeedList;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\models\UserBackgroundJobControll;
use MongoId;
use Yii;

/**
 * Class LazadaProductFeedHelper
 * lazada,linio,jumia 产品刊登整个流程
 * 1、编辑好的产品发布
 * 2、循环检测产品是否刊登成功
 * 3、如果刊登成功则上传图片
 * 
 * 基本废弃，只有productPublish有被调用，但也是在被废弃的流程里面调用
 * @package eagle\modules\listing\helpers
 * @author vizewang
 * @version 1.0
 * @since 2.0
 */
class LazadaLinioJumiaProductFeedHelperV2
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

        if (empty($publishListingIds)) {
            return array(false, "没有需要发布的产品id");
        }
        $objIds = array();
        foreach ($publishListingIds as $publishListingId) {
            $objIds[] = new MongoId($publishListingId);
        }
        //TODO正式上线取消注释
//        try {
        // 准备合格的产品信息
        list($products, $variationCount, $passedNum) = self::prepareProductData($objIds);
        $failedIds = self::sendRequest($products);
        $success = $passedNum - count($failedIds);
        self::localLog("提交了 " . $variationCount . " 个变体，" . $success . "执行成功 。");
        return array(true, "提交了 " . $variationCount . " 个变体，" . $success . "执行成功 。");
//        } catch (\Exception $e) {
//            self::localLog("productPublish Exception" . print_r($e->getMessage(), true), LogType::ERROR);
//            return array(false, $e->getMessage());
//        }
    }


    public static function manualProductPublish($publishListingIds = array(), $uid)
    {
//        self::productPublishTest($publishListingIds, $uid);
        $objId = array();
        foreach ($publishListingIds as $publishId) {
            $objId[] = new MongoId($publishId);
        }
        self::productPublish($objId);
    }

    private static function getAllLazadaAccountInfoMap()
    {
        $lazadauserMap = array();

        $lazadaUsers = SaasLazadaUser::find()->where('status<>3')->all();
        foreach ($lazadaUsers as $lazadaUser) {
            $lazadauserMap[$lazadaUser->lazada_uid] = array(
                "userId" => $lazadaUser->platform_userid,
                "apiKey" => $lazadaUser->token,
                "platform" => $lazadaUser->platform,
                "countryCode" => $lazadaUser->lazada_site,
                "puid" => $lazadaUser->puid,
            );
        }

        return $lazadauserMap;
    }

    public static function productPublishTest($publishListingIds, $uid)
    {
        try {
            $sendSuccess = 0;
            if (!empty($publishListingIds)) {
                $allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
                $products = array('create' => array(), 'update' => array());// 根据账号分组的 待上传产品信息数组
                $prodIds = array('create' => array(), 'update' => array());// 根据账号分组的 待上传产品 的 刊登产品Id
                $ret = true;
                foreach ($publishListingIds as $publishListingId) {
                    // 也许是网络问题，所以这里失败也允许 进入发布
//                    $connection = Yii::$app->subdb;
//                    $command = $connection->createCommand("update lazada_publish_listing set status='" . LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1] . "' where id=" . $publishListingId . " and ( state='" . LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT . "' or state='" . LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL . "' ) ");
//                    $record = $command->execute();
//                    if ($record <= 0)//抢不到---如果是多进程的话，有抢不到的情况
//                        continue;
//                    self::localLog(json_encode($record));
                    // 抢到记录
                    $publishListing = LazadaPublishListing::findOne($publishListingId);
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

                    $rawRespDataManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RAW_RESPONSE_DATA);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "storeInfo";
                    $rawRespData->response = $storeInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "baseInfo";
                    $rawRespData->response = $baseInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "imageInfo";
                    $rawRespData->response = $imageInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "descriptionInfo";
                    $rawRespData->response = $descriptionInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "shippingInfo";
                    $rawRespData->response = $shippingInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "warrantyInfo";
                    $rawRespData->response = $warrantyInfo;
                    $rawRespDataManager->insert($rawRespData);
                    $rawRespData = new RawResponseDate();
                    $rawRespData->api = "variantData";
                    $rawRespData->response = $variantData;
                    $rawRespDataManager->insert($rawRespData);
                    // 1.必要属性检查
                    if (self::_checkPropertyIsEmpty('PrimaryCategory', $storeInfo['primaryCategory'], $publishListing)) continue;
                    if (self::_checkPropertyIsEmpty('Brand', $baseInfo['Brand'], $publishListing)) continue;
                    if (self::_checkPropertyIsEmpty('Name', $baseInfo['Name'], $publishListing)) continue;
                    // 接口上看 其他两个平台都必填，lazada不是必填，但lazada卖家后台显示必填，想审核通过也是必填
                    if (self::_checkPropertyIsEmpty('Description', $descriptionInfo['Description'], $publishListing)) continue;

                    // 平台必填区别
                    if ('lazada' == $publishListing->platform) {
                        if (self::_checkPropertyIsEmpty('PackageContent', $descriptionInfo['PackageContent'], $publishListing)) continue;

                        if (self::_checkPropertyIsEmpty('PackageLength', $shippingInfo['PackageLength'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageWidth', $shippingInfo['PackageWidth'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageHeight', $shippingInfo['PackageHeight'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageWeight', $shippingInfo['PackageWeight'], $publishListing)) continue;
                        // sg my ShortDescription貌似必填
                        if ($publishListing->site == "my") {// my才强制
                            // dzt20160520 前端去掉 让客户填写 'NameMs','DescriptionMs'，直接再这里从 'Name','Description' 复制
                            $baseInfo['NameMs'] = $baseInfo['Name'];
                            $descriptionInfo['DescriptionMs'] = $descriptionInfo['Description'];

                            // dzt20160226 马来西亚也去掉必填
// 							if(self::_checkPropertyIsEmpty('MaxDeliveryTime',$shippingInfo['MaxDeliveryTime'],$publishListing)) continue;
// 							if(self::_checkPropertyIsEmpty('MinDeliveryTime',$shippingInfo['MinDeliveryTime'],$publishListing)) continue;

                            if (self::_checkPropertyIsEmpty('TaxClass', $baseInfo['TaxClass'], $publishListing)) continue;
                        }
                    } else if ('linio' == $publishListing->platform) {
// 						if(self::_checkPropertyIsEmpty('DeliveryTimeSupplier',$baseInfo['DeliveryTimeSupplier'],$publishListing)) continue;// dzt20151222 comment： 墨西哥要求必填，但 哥伦比亚和智利的目录没有这个属性

                        if (self::_checkPropertyIsEmpty('PackageLength', $shippingInfo['PackageLength'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageWidth', $shippingInfo['PackageWidth'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageHeight', $shippingInfo['PackageHeight'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('PackageWeight', $shippingInfo['PackageWeight'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('TaxClass', $baseInfo['TaxClass'], $publishListing)) continue;
                    } else if ('jumia' == $publishListing->platform) {
                        if (self::_checkPropertyIsEmpty('ShortDescription', $descriptionInfo['ShortDescription'], $publishListing)) continue;
                        if (self::_checkPropertyIsEmpty('ProductWeight', $shippingInfo['ProductWeight'], $publishListing)) continue;
                    }

                    // 但是我们为了后面不再查错，所以这里强制要求先上传图片
                    if (empty($imageInfo['Product_photo_primary']) && empty($imageInfo['Product_photo_others'])) {
                        self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Images cannot be empty.");
                        continue;
                    }

                    $hasVariantProblem = false;
                    $parentSku = "";
                    $existingUpSkus = json_decode($publishListing->uploaded_product, true); // 已上传到Lazada后台的产品 sku

                    foreach ($variantData as $oneVariant) {
                        // dzt20151216 在jumia 后台操作保存过，可以不填，之前的两个平台也可以不填，但是getAttr接口看到这个字段是必填。
// 						$hasVariantProblem = self::_checkPropertyIsEmpty('Variation',$oneVariant['Variation'],$publishListing);
// 						if($hasVariantProblem) break;

                        // 接口上看 其他两个平台都不是必填，linio是必填，但这里我们set成必填
                        $hasVariantProblem = self::_checkPropertyIsEmpty('SellerSku', $oneVariant['SellerSku'], $publishListing);
                        if ($hasVariantProblem) break;

                        // dzt20151113 调试productCreate Price又不是必填的了。 但是我们为了后面不再查错，所以这里强制要求填
                        $hasVariantProblem = self::_checkPropertyIsEmpty('Price', $oneVariant['Price'], $publishListing);
                        if ($hasVariantProblem) break;

                        // 接口上看 其他所有平台都不是必填，但这里我们set成必填
                        $hasVariantProblem = self::_checkPropertyIsEmpty('Quantity', $oneVariant['Quantity'], $publishListing);
                        if ($hasVariantProblem) break;

                        // 检查 Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank

                        if (isset($oneVariant['SalePrice']) && $oneVariant['SalePrice'] > $oneVariant['Price']) {
                            self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Sale price must be lower than the standard price.");
                            $hasVariantProblem = true;
                            if ($hasVariantProblem) break;
                        }

                        // 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在,否则 linio 报Internal Application Error错
                        if (!empty($oneVariant['SalePrice']) || !empty($oneVariant['SaleStartDate']) || !empty($oneVariant['SaleEndDate'])) {
                            if (empty($oneVariant['SalePrice'])) {
                                self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SalePrice is empty.Please complete the sales information.");
                                $hasVariantProblem = true;
                                if ($hasVariantProblem) break;
                            }

                            if (empty($oneVariant['SaleStartDate'])) {
                                self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SaleStartDate is empty.Please complete the sales information.");
                                $hasVariantProblem = true;
                                if ($hasVariantProblem) break;
                            }

                            if (empty($oneVariant['SaleEndDate'])) {
                                $hasVariantProblem = self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "SaleEndDate is empty.Please complete the sales information.");
                                $hasVariantProblem = true;
                                if ($hasVariantProblem) break;
                            }
                        }

                        // 平台必填区别
                        if ('linio' == $publishListing->platform) {
                            $hasVariantProblem = self::_checkPropertyIsEmpty('ProductId', $oneVariant['ProductId'], $publishListing);
                        }
                        if ($hasVariantProblem) break;

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
                            self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Field SellerSku with value '" . $parentSku . "' has a problem: You already have another product with the SKU '" . $parentSku . "'");
                            continue;
                        }
                    }

                    // 2.组合产品数据内容
                    $product = array();
                    $productData = array();
                    // 查阅了几个马来西亚的主目录 full call api 发现都是21 个属性under product tag 差异主要在ProductData tag里的内容
                    // 查阅墨西哥的主目录 full call api发现都是21 个属性under product tag
// 					<Brand></Brand>
// 					<Description></Description>
// 					<Name></Name>
// 					<Price>61.95</Price>
// 					<PrimaryCategory></PrimaryCategory>
// 					<SellerSku></SellerSku>
// 					<TaxClass></TaxClass>
// 					<Categories>5, 94, 1075</Categories>
// 					<Condition>New, Refurbish</Condition>
// 					<CountryCity></CountryCity>
// 					<ParentSku>ABC-1000-202</ParentSku>
// 					<ProductGroup></ProductGroup>
// 					<ProductId>978-3-16-148410-0</ProductId>
// 					<Quantity>50</Quantity>
// 					<SaleEndDate>2000-05-21</SaleEndDate>
// 					<SalePrice>233.45</SalePrice>
// 					<SaleStartDate>2013-05-21</SaleStartDate>
// 					<ShipmentType></ShipmentType>
// 					<Status></Status>
// 					<Variation>Earphone fluffy blue</Variation>

// 					<BrowseNodes></BrowseNodes>// jumia 的, linio，lazada 没有
// 					<ProductData></ProductData>
                    $notProDataProperties = array('Brand', 'Description', 'Name', 'Price', 'PrimaryCategory', 'SellerSku', 'TaxClass', 'Categories', 'Condition',
                        'CountryCity', 'ParentSku', 'ProductGroup', 'ProductId', 'Quantity', 'SaleEndDate', 'SalePrice', 'SaleStartDate', 'ShipmentType',
                        'Status', 'Variation', 'BrowseNodes');

                    $product['PrimaryCategory'] = $storeInfo['primaryCategory'];
                    if (!empty($storeInfo['categories']) && 'lazada' <> $publishListing->platform)// dzt20151225 lazada后台没得填这选项，去掉
                        $product['Categories'] = implode(",", $storeInfo['categories']);

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

                    foreach ($descriptionInfo as $key => $value) {
                        if (!empty($value)) {
                            if (in_array($key, $notProDataProperties)) {
                                $product[$key] = "<![CDATA[" . $value . "]]>";
                            } else {
                                $productData[$key] = "<![CDATA[" . $value . "]]>";
                            }
                        }
                    }

                    foreach ($shippingInfo as $key => $value) {
                        if (!empty($value)) {
                            if (in_array($key, $notProDataProperties)) {
                                $product[$key] = $value;
                            } else {
                                $productData[$key] = $value;
                            }
                        }
                    }

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
                }
                // 3.发送更新请求
                foreach ($products['update'] as $lazada_uid => $sameAccountUpProds) {
                    if (!empty($sameAccountUpProds)) {
                        $tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
                        $config = array(
                            "userId" => $tempConfig["userId"],
                            "apiKey" => $tempConfig["apiKey"],
                            "countryCode" => $tempConfig["countryCode"],
                        );

                        // 这里更新的是产品的所有信息，不像在线修改只更新部分信息
                        Yii::info("productPublish before updateFeed config:" . json_encode($config) . PHP_EOL . " products:" . json_encode($sameAccountUpProds), "file");
                        $rawRespDataManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RAW_RESPONSE_DATA);
                        $rawRespData = new RawResponseDate();
                        $rawRespData->api = "updateProduct";
                        $data = array("feed" => $sameAccountUpProds);
                        $response = LazadaInterface_Helper::productUpdate($config, array('products' => $sameAccountUpProds));
                        $data["resp"] = $response;
                        $rawRespData->response = $data;
                        $rawRespDataManager->insert($rawRespData);
//                        Yii::info("productPublish updateFeed response:".print_r($response,true),"file");
//                        if($response['success'] != true){
//                            if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
//                                $response['message'] = $response['message'].'<br>请重新发布商品';
//                            }
//                            self::changePublishListState($prodIds['update'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
//                        }else{
//                            $sendSuccess += count($sameAccountUpProds);
//                            $feedId = $response['response']['body']['Head']['RequestId'];
//                            LazadaPublishListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds['update'][$lazada_uid]]); // 记录feed id
//                            // 转换状态
//                            self::changePublishListState($prodIds['update'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
//                            $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
//                            if($insertFeedResult){
//                                Yii::info("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
//                            }else{
//                                Yii::error("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_UPDATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
//                            }
//                        }
                    }
                }

                // 4.发送创建请求
                foreach ($products['create'] as $lazada_uid => $sameAccountCrtProds) {
                    if (!empty($sameAccountCrtProds)) {
                        $tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
                        $config = array(
                            "userId" => $tempConfig["userId"],
                            "apiKey" => $tempConfig["apiKey"],
                            "countryCode" => $tempConfig["countryCode"],
                        );
                        $rawRespDataManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RAW_RESPONSE_DATA);
                        $rawRespData = new RawResponseDate();
                        $rawRespData->api = "createProduct";
                        $data = array("feed" => $sameAccountCrtProds);
                        $response = LazadaInterface_Helper::productCreate($config, array('products' => $sameAccountCrtProds));
                        $data["resp"] = $response;
                        $rawRespData->response = $data;
                        $rawRespDataManager->insert($rawRespData);
//                        Yii::info("productPublish before createFeed config:".json_encode($config).PHP_EOL." products:".json_encode($sameAccountCrtProds),"file");
//                        $response = LazadaInterface_Helper::productCreate($config,array('products'=>$sameAccountCrtProds));
//                        Yii::info("productPublish createFeed response:".print_r($response,true),"file");
//                        if($response['success'] != true){
//                            if(stripos($response['message'],'Internal Application Error') !== false){// dzt20160310 对平台错误 提示客户重试减少客服量
//                                $response['message'] = $response['message'].'<br>请重新发布商品';
//                            }
//                            self::changePublishListState($prodIds['create'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1],$response['message']);
//                        }else{
//                            $sendSuccess += count($sameAccountCrtProds);
//                            $feedId = $response['response']['body']['Head']['RequestId'];
//                            LazadaPublishListing::updateAll(['feed_id'=>$feedId],['id'=>$prodIds['create'][$lazada_uid]]); // 记录feed id
//                            // 转换状态
//                            self::changePublishListState($prodIds['create'][$lazada_uid],LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
//                            $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
//                            if($insertFeedResult){
//                                Yii::info("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_CREATE." success. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
//                            }else{
//                                Yii::error("productPublish insertFeed ".LazadaFeedHelper::PRODUCT_CREATE." fail. puid:".$tempConfig['puid']." lazada_uid:".$lazada_uid." site:".$tempConfig["countryCode"]." feedId".$feedId,"file");
//                            }
//                        }
                    }
                }

            }

            return array(true, "提交了 " . count($publishListingIds) . " 个，成功执行 " . $sendSuccess . " 个。");
        } catch (\Exception $e) {
            Yii::error("productPublish Exception" . print_r($e, true), "file");
            self::changePublishListState($publishListingId, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], $e->getMessage());
            return array(false, $e->getMessage());
        }

    }

    private static function _checkPropertyIsEmpty($key, $value, $publishListing)
    {
        if (empty($value)) {
            self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "$key cannot be empty.");
            return true;
        }
        return false;
    }

    public static function changePublishListState($publishListingIds, $state, $status, $Info = "")
    {
        if (is_array($publishListingIds)) {
            $update = array();
            $update['state'] = $state;
            $update['status'] = $status;
            $update['feed_info'] = $Info;
            $recode = LazadaPublishListing::updateAll($update, ['id' => $publishListingIds]);
            Yii::info("update $recode recode for " . implode(",", $publishListingIds), "file");
        } else {
            $publishListing = LazadaPublishListing::findOne($publishListingIds);
            $publishListing->state = $state;
            $publishListing->status = $status;
            $publishListing->feed_info = $Info;
            if (!$publishListing->save()) {
                Yii::error('lazada_uid:' . lazada_uid . ' publishListingId:' . $publishListing->id . '  $$publishListing->save() 保存失败:' . print_r($publishListing->errors, true), "file");
            }
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

    public static function manualCheckFeedStatus($lazadaFeedListId)
    {
        $lazadaFeedList = LazadaFeedList::findOne($lazadaFeedListId);
        $lazadaUser = SaasLazadaUser::findOne($lazadaFeedList->lazada_saas_user_id);
        $config = array("userId" => $lazadaUser->platform_userid,
            "apiKey" => $lazadaUser->token,
            "countryCode" => $lazadaUser->lazada_site);
        self::doCheckFeedStatus($config, $lazadaFeedList);
    }

    private static function doCheckFeedStatus($config, $lazadaFeedList)
    {
        $reqParams = array("FeedID" => $lazadaFeedList->Feed);
        $feedDetail = self::getFeedStatus($config, $reqParams);
//        CommonHelper::insertRawDataToRawResponseData("feedstatus", $feedDetail);

        $nowTime = time();
        if (!self::feedstatusResponseErrorDigest($feedDetail, $lazadaFeedList, $nowTime))
            return false;
        self::feedstatusParse($feedDetail, $lazadaFeedList, $config, $nowTime);
        return true;
    }


    /**
     * 产品图片上传
     * 原始文件eagle.modules.lazada.apihelpers.SaasLazadaAutoSyncApiHelper.ImageUpload
     */
    public static function ImageUpload()
    {
        //由于 feed check 和image upload 由不同的 Background job执行，status 不能共同修改，所以新增了execution_request 来让feed check 通知图片上传要触发任务执行
        $recommendJobObjs = UserBackgroundJobControll::find()
            ->where('is_active = "Y" AND status <>1 AND job_name="' . LazadaApiHelper::IMAGE_UPLOAD_BGJ_NAME . '" AND error_count<5 AND execution_request>0')
            ->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
        if (!empty($recommendJobObjs)) {
            self::doImageUpload($recommendJobObjs);
        }
    }


    public static function testProductCreate($state, $status, $sku, $childNum = 0, $uid = 297, $lazadaUid = 1, $name = "test")
    {
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $parent = $lazadaPublishListingManager->findOne(array("state" => "raw", "isParentSku" => true));
        unset($parent['_id']);
        $parent['uid'] = $uid;
        $parent['lazadaUid'] = $lazadaUid;
        $childSku = array();
        for ($i = 0; $i < $childNum; $i++) {
            $childSku[] = array("sku" => $sku . ($i + 1), "state" => $state, "status" => $status);
        }
        $parent["childSku"] = $childSku;
        $parent["sellerSku"] = $sku;
        $parent["product"]["SellerSku"] = $sku;
        $parent["state"] = $state;
        $parent["status"] = $status;
        $parent["product"]["Name"] = $name;
        $parent["product"]["ProductId"] = $sku;
        unset($parent["parentId"]);
        $lazadaPublishListingManager->insert($parent);
        $child = array();
        $parentSku = $parent["sellerSku"];
        for ($i = 0; $i < $childNum; $i++) {
            $child[$i] = $parent;
            $child[$i]["isParentSku"] = false;
            $child[$i]["sellerSku"] = $childSku[$i]["sku"];
            $child[$i]["product"]["SellerSku"] = $childSku[$i]["sku"];
            $child[$i]["product"]["ParentSku"] = $parentSku;
            $child[$i]["product"]["ProductId"] = $childSku[$i]["sku"];
            $child[$i]["product"]["Variation"]=$i+2;
            unset($child[$i]["childSku"]);
            unset($child[$i]["_id"]);
        }
        $lazadaPublishListingManager->batchInsert($child);
    }

    public static function manualImageUpload($userBJC)
    {
        $recommendJobObjs = array(UserBackgroundJobControll::findOne($userBJC));
        self::doImageUpload($recommendJobObjs);
    }

    private static function doImageUpload($recommendJobObjs)
    {
        $nowTime = time();
        self::localLog("ImageUpload start... time:$nowTime");
        $allLazadaAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
//        try {
        foreach ($recommendJobObjs as $recommendJobObj) {
            //1. 先判断是否可以正常抢到该记录
            if (!self::imageUploadLock($recommendJobObj)) {
                self::localLog("can not get lock, skipped");
                continue; //抢不到
            }
            $puid = $recommendJobObj->puid;
            // 重新去掉 fail 的 确实有fail的情况不适合自动重新上传图片
            $cursor = $lazadaPublishListingManager->find(array("uid" => $puid, "state" => LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, "isParentSku" => true));

            self::localLog("ImageUpload puid:$puid count " . $cursor->count());
            foreach ($cursor as $imageUploadTarget) {
                $imageUploadTarget['status'] = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][1];
                $q = array('_id'=>$imageUploadTarget['_id'],'status' => array('$ne' => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][1]));
                $old = $lazadaPublishListingManager
                    ->findAndModify(
                        $q,
                        array('$set' => array("status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED][1]))
                    );
                if (empty($old)) {
                    continue;
                }
                self::localLog("ImageUpload LazadaPublishListing id:" . $imageUploadTarget['_id']->{'$id'});


                $imageUrl = array();
                if (!empty($imageUploadTarget['mainImage'])) {
                    $imageUrl[] = $imageUploadTarget['mainImage'];
                }
                if (!empty($imageUploadTarget['images'])) {
                    $image = $imageUploadTarget['images'];
                    if (is_array($image)) {
                        $imageUrl = array_merge($imageUrl, $image);
                    } else {
                        $imageUrl[] = $image;
                    }
                }
                if (empty($imageUrl)) {
                    LLJHelper::changePublishListStateV2(array($imageUploadTarget), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3], "Images cannot be empty.");
                    continue;
                }
                $tempConfig = $allLazadaAccountsInfoMap[$imageUploadTarget["lazadaUid"]];
                $config = array(
                    "userId" => $tempConfig["userId"],
                    "apiKey" => $tempConfig["apiKey"],
                    "countryCode" => $tempConfig["countryCode"],
                );
                $uploads = array();
                $uploads['SellerSku'] = $imageUploadTarget['sellerSku'];
                $uploads['Images'] = $imageUrl;


                self::localLog("ImageUpload start productImage uploads:" . print_r($uploads, true));
                $response = LazadaInterface_Helper::productImage($config, $uploads);
                self::localLog("ImageUpload end productImage response:" . print_r($response, true));


                if ($response['success'] != true) {
                    // 目前总结到 state_code 为28 的为 curl timeout 可以接受重试
                    if (isset($response['state_code']) && (28 == $response['state_code']
                            || (isset($response['response']['state_code']) && 28 == $response['response']['state_code']))
                    ) {// 不改变state 只记下error message
                        LLJHelper::changePublishListStateV2(array($imageUploadTarget), LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4], $response['message']);
                    } else {// 其他错误 mark state 为fail 不再重试
                        if (stripos($response['message'], 'Internal Application Error') !== false) {// dzt20160310 对平台错误 提示客户重试减少客服量
                            $response['message'] = $response['message'] . '<br>请重新发布商品';
                        }
                        LLJHelper::changePublishListStateV2(array($imageUploadTarget), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][4], $response['message']);

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

                    $lazadaPublishListingManager->findAndModify(array("_id" => $imageUploadTarget['_id']), array('$set' => array("feedId" => $feedId)));
                    // 转换状态
                    LLJHelper::changePublishListStateV2(array($imageUploadTarget), LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD][0]);
                    self::localLog("productCreate response:" . print_r($response['response'], true));
                    $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $imageUploadTarget['lazadaUid'], $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD);
                    if ($insertFeedResult) {
                        self::localLog("ImageUpload insertFeed " . LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD . " success. puid:" . $tempConfig['puid'] . " lazada_uid:" . $imageUploadTarget['lazadaUid'] . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    } else {
                        self::localLog("ImageUpload insertFeed " . LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD . " fail. puid:" . $tempConfig['puid'] . " lazada_uid:" . $imageUploadTarget['lazadaUid'] . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    }
                }
            }// end of !empty($imageUploadTargets)

            $recommendJobObj->status = 2;
            $recommendJobObj->error_count = 0;
            $recommendJobObj->error_message = "";
            $recommendJobObj->last_finish_time = $nowTime;
            $recommendJobObj->update_time = $nowTime;
            $recommendJobObj->next_execution_time = $nowTime + 24 * 3600;//24个小时后重试
            $recommendJobObj->save(false);
        }// end of foreach $recommendJobObjs
//        } catch (\Exception $e) {
//            self::localLog("ImageUpload Exception" . print_r($e->getMessage(), true), LogType::ERROR);
//            if (!empty($recommendJobObj))
//                self::handleBgJobError($recommendJobObj, $e->getMessage());
//            if (!empty($imageUploadTarget))
//                LLJHelper::changePublishListStateV2($imageUploadTarget['_id'], LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][3], $e->getMessage());
//        }

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
//        self::localLog("getFeedStatus reqParams is " . json_encode($reqParams));
        $getFeedDetailRlt = LazadaProxyConnectHelper::call_lazada_api($reqParams);
//        self::localLog("getFeedStatus end ===>result:" . print_r($getFeedDetailRlt, true));

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
        $feedId = $lazadaFeedList->Feed;
        foreach ($feedDetail["response"]["feeds"] as $singleFeedDetail) {
            $feedStatus = $singleFeedDetail["Status"];
            self::localLog("feedstatusParse start feedId:$feedId feedStatus:$feedStatus");
            //4.2 状态为 排队中
            if ($feedStatus == ProductFeedStatus::QUEUED) {
                self::parseQueued($lazadaFeedList, $nowTime, $feedStatus, $singleFeedDetail["Status"]);
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
            $errorMessage = "no response feeds subelement,==>raw msg " . $feedDetail["response"]["message"];
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
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $oldLazadaPublishListing = $lazadaPublishListingManager->findAndModify(array("_id" => $publishListingId, 'state' => array('$in' => array(LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL))), array('$set' => array("status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1])));
        if (!empty($oldLazadaPublishListing) && $oldLazadaPublishListing["status"] != LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]) {
            return array(true, $oldLazadaPublishListing);
        } else {
            return array(false, null);
        }
    }

    /**
     * 获取父产品所对应的未处理中的子产品
     * @param $parent
     * @return array
     */
    private static function childPublishListingLock($parent)
    {
        $childSkus = $parent["childSku"];
        $skuListing = array();
        foreach ($childSkus as $childSku) {
            $oldLazadaPublishListing = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING)
                ->findAndModify(array("uid" => $parent["uid"], "lazadaUid" => $parent["lazadaUid"], "sellerSku" => $childSku, 'state' => array('$in' => array(LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL))), array('$set' => array("status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1])));
            if (!empty($oldLazadaPublishListing) && $oldLazadaPublishListing["status"] != LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]) {
                $skuListing[] = $oldLazadaPublishListing;
            }
        }
        return $skuListing;
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
        $recommendJobObj->status = 1;
        $affectRows = $recommendJobObj->update(false);
        if ($affectRows <= 0) {
            return false;
        }
        return true;
    }


    /**
     * 预检查发布的铲平是否有问题,如果有则返回false,否则返回true
     * 检查所有categroyAttrubite字段isMandatory为1的产品字段
     * @param $publishListingId
     * @return mixed
     */
    private static function publishListingInfoCheck($publishListing)
    {

        $productInfo = array_merge($publishListing["product"], $publishListing["product"]["ProductData"]);
        // 获取categoryAttribute
        $categoryAttr = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)
            ->findOne(array("platform" => $publishListing["platform"], "site" => $publishListing["site"], "categoryId" => $publishListing["product"]["PrimaryCategory"]));
        //如果强制要求,则检查是否为空,如果为空则返回错误
        if (empty($categoryAttr)) {
            LLJHelper::changePublishListStateV2(array($publishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "category attribute mismatching");
            return false;
        }

        foreach ($categoryAttr["attributes"] as $attribute) {
            if ($attribute["isMandatory"] == 1) {
                if (LLJHelper::checkPropertyIsEmptyV2($attribute["FeedName"], $productInfo, $publishListing))
                    return false;
            }
        }

        if ($productInfo['SalePrice'] >= $productInfo['Price']) {
            LLJHelper::changePublishListStateV2(array($publishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Sale price must be lower than the standard price.");
            return false;
        }
        // 平台必填区别
        if (empty($publishListing['mainImage']) && empty($publishListing['images'])) {
            LLJHelper::changePublishListStateV2(array($publishListing), LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "Images cannot be empty.");
            return false;
        }
        return true;
    }


    /**
     * @param $products
     * @return array 返回发送失败的产品信息
     */
    private
    static function sendRequest($products)
    {
        $allLazadaAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
        $failedIds = array();
        foreach ($products as $lazada_uid => $allProducts) {
            $tempConfig = $allLazadaAccountsInfoMap[$lazada_uid];
            $config = array(
                "userId" => $tempConfig["userId"],
                "apiKey" => $tempConfig["apiKey"],
                "countryCode" => $tempConfig["countryCode"],
            );
            foreach ($allProducts as $action => $productArr) {
                $feeds = array();
                $publishLazadaListingIds = array();
                foreach ($productArr as $productDetail) {
                    $feeds[] = $productDetail["product"];
                    $publishLazadaListingIds[] = $productDetail["_id"];
                }
                self::localLog("sendRequest $action start  config:" . json_encode($config) . PHP_EOL . " products:" . json_encode($feeds));
                if ($action == "update") {
                    $response = LazadaInterface_Helper::productUpdate($config, array('products' => $feeds));
                } else if ($action == "create") {
                    $response = LazadaInterface_Helper::productCreate($config, array('products' => $feeds));
                }
                if ($response['success'] != true) {
                    if (stripos($response['message'], 'Internal Application Error') !== false) {// dzt20160310 对平台错误 提示客户重试减少客服量
                        $response['message'] = $response['message'] . '<br>请重新发布商品';
                    }
                    LLJHelper::changePublishListStateV2($productArr, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][1], $response['message']);
                    $failedIds = array_merge($failedIds, $publishLazadaListingIds);
                } else {
                    $feedId = $response['response']['body']['Head']['RequestId'];

                    $manager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
                    $manager->update(array("_id" => array('$in' => $publishLazadaListingIds)), array('$set' => array("feedId" => $feedId)), array("multiple" => true));
                    // 转换状态
                    LLJHelper::changePublishListStateV2($productArr, LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD][0]);
                    $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazada_uid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_CREATE);
                    if ($insertFeedResult) {
                        self::localLog("productPublish insertFeed " . LazadaFeedHelper::PRODUCT_CREATE . " success. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    } else {
                        self::localLog("productPublish insertFeed " . LazadaFeedHelper::PRODUCT_CREATE . " fail. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazada_uid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                    }
                }
            }

        }
        return $failedIds;
    }

    /**
     * @param $publishListingIds
     * @return array
     */
    private static function prepareProductData($publishListingIds)
    {
        $variationCount = 0;
        $products = array();//产品相关的数据,'lazadaUid'=>array('create'=>array(),'update'=>array())
        $passedNum = 0;
        $readyForCreateStates = array(LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD);
        foreach ($publishListingIds as $publishListingId) {
            $publishListings = LLJHelper::getAllReadyForPublishProducts($publishListingId);
            if (empty($publishListings)) {
                continue;
            }
            if (isset($products[$publishListings[0]['lazadaUid']]) && !isset($products[$publishListings[0]['lazadaUid']]["create"])) {
                $products[$publishListings[0]['lazadaUid']] = array('update' => array(), 'create' => array());
            }
            $variationCount += count($publishListings);
            foreach ($publishListings as $publishListing) {
                $isPass = self::publishListingInfoCheck($publishListing);
                if ($isPass) {
                    $passedNum++;
                    if (in_array($publishListing["state"], $readyForCreateStates)) {
                        $products[$publishListing['lazadaUid']]['create'][] = $publishListing;
                    } else {
                        $products[$publishListing['lazadaUid']]['update'][] = $publishListing;
                    }
                }
            }
        }
        return array($products, $variationCount, $passedNum);
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
            self::createAndUpdateStatusCallback($lazadaFeedList, $singleFeedDetail, $config);
            return;
        }
        if ($lazadaFeedList->type == self::PRODUCT_IMAGE_UPLOAD) {
            //(1)图片upload已经完成（不一定成功）
            self::imageuploadStatusCallback($lazadaFeedList, $singleFeedDetail, $config, $nowTime);
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
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $singleFeedDetail['Feed']);//"totalRecords" => $lazadaFeedList->TotalRecords

        $reqParams["failReport"] = self::parseSingleFeedDetailError($singleFeedDetail);


        if ($lazadaFeedList->type == self::PRODUCT_CREATE) {
            self::localLog("createAndUpdateStatusCallback start feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productCreateV2($reqParams);
            self::localLog("createAndUpdateStatusCallback end feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else { //$SAA_obj->type==self::PRODUCT_UPDATE
            // 回调修改listing 修改状态信息
            self::localLog("createAndUpdateStatusCallback start feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productUpdateV2($reqParams, $config);// dzt20160119 带上config 参数立即获取产品信息
            self::localLog("createAndUpdateStatusCallback end feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        }


        if ($ret === true) {
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_CHECKED_CALLED;
            self::localLog("createAndUpdateStatusCallback feedId:$lazadaFeedList->Feed STATUS_CHECKED_CALLED");
            $lazadaFeedList->save(false);
        } else {
            $lazadaFeedList->message = "LazadaCallbackHelper::productCreate";
            $lazadaFeedList->error_times = $lazadaFeedList->error_times + 1;
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_FAIL;
            $lazadaFeedList->save(false);
            self::localLog("createAndUpdateStatusCallback feedId:$lazadaFeedList->Feed STATUS_FAIL checkAllFeedStatus  productCreate  save");
        }
    }

    private static function parseSingleFeedDetailError($singleFeedDetail)
    {
        if ($singleFeedDetail["FailedRecords"] > 0) {
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
            return $reqErrors;
        }
    }

//TODO update
    private static function imageuploadStatusCallback($lazadaFeedList, $singleFeedDetail, $config, $nowTime)
    {
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "totalRecords" => $lazadaFeedList->TotalRecords);
        $reqParams["failReport"] = self::parseSingleFeedDetailError($singleFeedDetail);
        //(2)图片upload成功的话，回调函数调用
        self::localLog("imageuploadStatusCallback start reqParams:" . print_r($reqParams, true));
        list($ret, $sellerSkus) = LazadaCallbackHelper::imageUploadV2($reqParams);
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
            $ret = LazadaCallbackHelper::markPendingProductV2($reqParams);
            self::localLog("after LazadaInterface_Helper::markPendingProduct ret:" . print_r($ret, true));
        } else {
            $lazadaFeedList->process_status = ProductListingProcessStatus::STATUS_PENDING;
            self::localLog("feedId:$lazadaFeedList->Feed STATUS_PENDING");
            $lazadaFeedList->save(false);
            $reqParams["isPending"] = 1;
            LazadaCallbackHelper::markPendingProductV2($reqParams);
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
        self::localLog("parseError feedId:$lazadaFeedList->Feed STATUS_CHECKED");
        $lazadaFeedList->save(false);
        self::localLog("parseError feedId:$lazadaFeedList->Feed self::STATUS_CHECKED save");

        $lazadaFeedList->is_running = 0;
        $reqParams = array("puid" => $lazadaFeedList->puid, "feedId" => $lazadaFeedList->Feed, "totalRecords" => $lazadaFeedList->TotalRecords);
        $reqParams["failReport"] = "lazada api error";

        if ($lazadaFeedList->type == self::PRODUCT_CREATE) {
            self::localLog("LazadaCallbackHelper::productCreate before feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productCreateV2($reqParams);
            self::localLog("LazadaCallbackHelper::productCreate after feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else if ($lazadaFeedList->type == self::PRODUCT_UPDATE) {
            // 回调修改listing 修改状态信息
            self::localLog("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$lazadaFeedList->Feed reqParams:" . print_r($reqParams, true));
            $ret = LazadaCallbackHelper::productUpdateV2($reqParams, $config);// dzt20160119 带上config 参数立即获取产品信息
            self::localLog("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$lazadaFeedList->Feed ret:" . print_r($ret, true));
        } else if ($lazadaFeedList->type == self::PRODUCT_IMAGE_UPLOAD) {
            self::localLog("LazadaCallbackHelper::imageUpload reqParams:" . print_r($reqParams, true));
            list($ret, $sellerSkus) = LazadaCallbackHelper::imageUploadV2($reqParams);
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
        $recommendJobObj->error_message = $errorMessage;
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
}