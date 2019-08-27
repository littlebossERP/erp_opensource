<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/20
 * Time: 下午2:39
 */

namespace console\controllers;


use common\helpers\Helper_Array;
use common\mongo\lljListing\LazadaPublishListingMG;
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use console\components\Controller;
use console\helpers\CommonHelper;
use eagle\models\comment\CmCommentRule;
use eagle\models\comment\CmCommentTemplate;
use eagle\modules\comment\dal_mongo\CommentConstances;
use eagle\modules\comment\dal_mongo\CommentManagerFactory;
use eagle\modules\comment\dal_mongo\CommentRule;
use eagle\modules\comment\dal_mongo\CommentTemplate;
use eagle\modules\listing\models\LazadaPublishListing;
use Yii;


class DataMigrateController extends Controller
{
    const LOG_ID = "console-controllers-DataMigrateController";
    public static $platform = ['aliexpress'];//需要转移的数据

    private function localLog($msg)
    {
        CommonHelper::log($msg, self::LOG_ID);
    }

    public function actionMysql2Mongo()
    {
        $this->doMysql2Mongo(CommentConstances::ALL);
    }

    public function actionSyncLazadaPublishListing($puid)
    {
        $this->doSyncLazadaPublishListing($puid);
    }

    private function doSyncLazadaPublishListing($puid)
    {
        $notProDataProperties = array('Brand', 'Description', 'Name', 'Price', 'PrimaryCategory', 'SellerSku', 'TaxClass', 'Categories', 'Condition',
            'CountryCity', 'ParentSku', 'ProductGroup', 'ProductId', 'Quantity', 'SaleEndDate', 'SalePrice', 'SaleStartDate', 'ShipmentType',
            'Status', 'Variation', 'BrowseNodes');
        if (true) {
            $lazadaPublishListings = LazadaPublishListing::find()->all();
            $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
            $parentId = "";
            self::localLog("lazadaPublishListing size is " . count($lazadaPublishListings));
            $lazadaPublishListingMGs = array();
            foreach ($lazadaPublishListings as $publishListing) {
                $storeInfo = json_decode($publishListing->store_info, true);
                $baseInfo = json_decode($publishListing->base_info, true);
                $imageInfo = json_decode($publishListing->image_info, true);
                $descriptionInfo = json_decode($publishListing->description_info, true);
                $shippingInfo = json_decode($publishListing->shipping_info, true);
                $warrantyInfo = json_decode($publishListing->warranty_info, true);
                $variantData = json_decode($publishListing->variant_info, true);
                Helper_Array::removeEmpty($storeInfo, true);
                Helper_Array::removeEmpty($baseInfo, true);
                Helper_Array::removeEmpty($imageInfo, true);
                if (!empty($descriptionInfo))
                    Helper_Array::removeEmpty($descriptionInfo, true);
                Helper_Array::removeEmpty($shippingInfo, true);
                Helper_Array::removeEmpty($warrantyInfo, true);
                Helper_Array::removeEmpty($variantData, true);
                $childSku = array();
                for ($i = 1; $i < count($variantData); $i++) {
                    $childSku[] = array("sku" => $variantData[$i]["SellerSku"], "state" => $publishListing->state, "status" => $publishListing->status);
                }
                $parentSku = "";
                for ($i = 0; $i < count($variantData); $i++) {
                    $oneVariant = $variantData[$i];
                    $lazadaPublishListingMG = new LazadaPublishListingMG();
                    $product['PrimaryCategory'] = $storeInfo['primaryCategory'];
                    $productData = array();
                    if (!empty($storeInfo['categories']))
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

                    if (!empty($descriptionInfo))
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
                    foreach ($oneVariant as $key => $value) {
                        $key = ucfirst($key);// 界面组织来的属性 首字母变成小写了，这里首字母要转成大写 传给proxy
                        if (!empty($value)) {
                            if (in_array($key, $notProDataProperties)) {
                                if ($key == 'SellerSku') {
                                    $value = htmlentities($value);
                                }

                                // seller后台 full api 看到ProductGroup这个属性，但是get attr 接口已经获取不到这个属性了
                                if ('ProductGroup' == $key) continue;
                                $product[$key] = $value;
                            } else {
                                $productData[$key] = $value;
                            }
                        }
                    }
                    if (!empty($baseInfo['Name']))
                        $productData['NameMs'] = $baseInfo['Name'];
                    if (!empty($descriptionInfo['Description']))
                        $productData['DescriptionMs'] = $descriptionInfo['Description'];
                    $product['ProductData'] = $productData;
                    $lazadaPublishListingMG->product = $product;
                    $lazadaPublishListingMG->uid = intval($puid);
                    $lazadaPublishListingMG->lazadaUid = $publishListing->lazada_uid;
                    $lazadaPublishListingMG->platform = $publishListing->platform;
                    $lazadaPublishListingMG->site = $publishListing->site;
                    $lazadaPublishListingMG->mainImage = $imageInfo['Product_photo_primary'];
                    if (!empty($imageInfo['Product_photo_others']))
                        $lazadaPublishListingMG->images = explode("@,@", $imageInfo['Product_photo_others']);
                    $lazadaPublishListingMG->mainImageThumbnail = $imageInfo['Product_photo_primary_thumbnail'];
                    if (!empty($imageInfo['Product_photo_others_thumbnail'])) {
                        $lazadaPublishListingMG->imagesThumbnail = explode("@,@", $imageInfo['Product_photo_others_thumbnail']);
                    }
                    $lazadaPublishListingMG->state = $publishListing->state;
                    $lazadaPublishListingMG->status = $publishListing->status;
                    $lazadaPublishListingMG->feedId = $publishListing->feed_id;
                    $lazadaPublishListingMG->feedInfo = $publishListing->feed_info;
                    $uploadedProduct = json_decode($publishListing->uploaded_product, true);
                    if ($i == 0) {
                        $lazadaPublishListingMG->uploadedProduct = $uploadedProduct;
                        $lazadaPublishListingMG->isParentSku = true;
                    } else {
                        if (count($uploadedProduct) > 0) {
                            $lazadaPublishListingMG->uploadedProduct = array($oneVariant["SellerSku"]);
                        }
                        $lazadaPublishListingMG->isParentSku = false;
//                        $lazadaPublishListingMG->parentId = $parentId;
                    }
                    $lazadaPublishListingMG->createTime = $publishListing->create_time;
                    $lazadaPublishListingMG->updateTime = $publishListing->update_time;
                    $lazadaPublishListingMG->sellerSku = $oneVariant["SellerSku"];

                    if ($i == 0) {
                        $lazadaPublishListingMG->childSku = $childSku;
                        $lazadaPublishListingManager->insert($lazadaPublishListingMG);
//                        $parentId = $lazadaPublishListingMG->_id;
                        $parentSku = $lazadaPublishListingMG->sellerSku;
                    } else {
                        $lazadaPublishListingMG->product["ParentSku"] = $parentSku;
                        $lazadaPublishListingMGs[] = $lazadaPublishListingMG;
                    }
                }
            }
            $lazadaPublishListingManager->batchInsert($lazadaPublishListingMGs);
        } else {
            self::localLog("change database failed ");
        }
    }

    private function doMysql2Mongo($mongoCollectionName = CommentConstances::COMMENT_RULE)
    {
        switch ($mongoCollectionName) {
            case CommentConstances::ALL:
                $this->mysql2Mongo4All();
                break;
            default:
                $this->localLog("no matched method for " . $mongoCollectionName);
        }
    }

    private function mysql2Mongo4All()
    {

        foreach (self::$platform as $platform) {    // 平台遍历
            CommonHelper::switchSubDb($platform, function ($puid) use ($platform) { // 切子库
                //批量迁移CommentRule
                $rules = CmCommentRule::find()->all();
                $mrules = array();
                $ruleIdObjIdMap = array();
                foreach ($rules as $rule) {
                    $mgrule = new CommentRule();
                    $mgrule->uid = $puid;
                    $mgrule->score = CommentConstances::DEFAULT_SCORE;
                    $mgrule->sellerIdList = explode(",", $rule->selleruseridlist);
                    $mgrule->content = $rule->content;
                    $mgrule->isCommentIssue = $rule->is_dispute;
                    $mgrule->countryList = explode(",", $rule->countrylist);
                    $mgrule->isUse = $rule->is_use;
                    $mgrule->platform = $rule->platform;
                    $mgrule->createTime = $rule->createtime;
                    $mgrule->updateTime = $rule->updatetime;
                    CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->insert($mgrule);
                    $ruleIdObjIdMap[$rule['id']] = $mgrule->_id->{'$id'};
                    $mrules[] = $mgrule;

                }
//                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->batchInsert($mrules);
                $this->localLog("successfully insert commentRule batch and size is " . count($mrules) . ", uid is " . $puid);


                //批量插入commentTemplate
                $tmpls = CmCommentTemplate::find()->all();
                $mtmpls = array();
                foreach ($tmpls as $tmpl) {
                    $mtmpl = new CommentTemplate();
                    $mtmpl->uid = $puid;
                    $mtmpl->content = $tmpl->content;
                    $mtmpl->createTime = $tmpl->createtime;
                    $mtmpl->isUse = $tmpl->is_use;
                    $mtmpl->platform = $tmpl->platform;
                    $mtmpls[] = $mtmpl;
                }
                $this->localLog("start insert commentTemplate batch and size is " . count($mtmpls) . ", uid is " . $puid);
                if (!empty($mtmpls)) {
                    CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->batchInsert($mtmpls);
                }
                $this->localLog("successfully insert commentTemplate batch and size is " . count($mtmpls) . ", uid is " . $puid);

            });
        }
    }
}

