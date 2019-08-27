<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/24
 * Time: 下午5:11
 */

namespace eagle\modules\listing\helpers;


use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use console\helpers\LogType;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV2;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use yii\data\Pagination;

class LLJHelper
{
    const LOG_ID = "eagle-modules-listing-helpers-LLJHelper";

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * 获取所有lazada,linio,jumia用户的api访问信息。 email,token,销售站点
     */
    public static function getAllLLJAccountInfoMap()
    {
        $lazadauserMap = array();

        $lazadaUsers = SaasLazadaUser::find()->all();
        foreach ($lazadaUsers as $lazadaUser) {
            $lazadauserMap[$lazadaUser->lazada_uid] = self::assembleConfig($lazadaUser);
        }

        return $lazadauserMap;
    }

    public static function getSpecificLazadaAccountInfo($lazadaUid)
    {
        $lazadaUser = SaasLazadaUser::findOne(["lazada_uid" => $lazadaUid]);
        if ($lazadaUser === null) return null;

        return self::assembleConfig($lazadaUser);
    }

    public static function getSpecificActiveLazadaAccountInfo($lazadaUid)
    {
        $lazadaUser = SaasLazadaUser::find()->where(['lazada_uid' => $lazadaUid])->andWhere('status <> 3')->one();// 不包括解绑账号
        return self::assembleConfig($lazadaUser);
    }

    /**
     * 获取所有lazada用户的api访问信息。 email,token,销售站点
     */
    public static function getBindAccountInfoMap()
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

    /**
     * 检查产品属性是否为空
     * @param $key
     * @param $value
     * @param $publishListing
     * @return bool
     */
    public static function checkPropertyIsEmpty($key, $value, $publishListing)
    {
        if (empty($value)) {
            self::changePublishListState($publishListing->id, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "$key cannot be empty.");
            return true;
        }
        return false;
    }

    public static function checkPropertyIsEmptyV2($key, $arr, $publishListing)
    {
        if (empty($arr[$key])) {
            self::changePublishListStateV2($publishListing, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL][0], "$key cannot be empty.");
            return true;
        }
        return false;
    }

    /**
     * 更改刊登产品状态
     * @param $publishListingIds
     * @param $state
     * @param $status
     * @param string $Info
     */
    public static function changePublishListState($publishListingIds, $state, $status, $Info = "")
    {
        if (is_array($publishListingIds)) {
            $update = array();
            $update['state'] = $state;
            $update['status'] = $status;
            $update['feed_info'] = $Info;
            $recode = LazadaPublishListing::updateAll($update, ['id' => $publishListingIds]);
            self::localLog("update $recode recode for " . implode(",", $publishListingIds));
        } else {
            $publishListing = LazadaPublishListing::findOne($publishListingIds);
            $publishListing->state = $state;
            $publishListing->status = $status;
            $publishListing->feed_info = $Info;
            if (!$publishListing->save()) {
                self::localLog('lazada_uid:' . lazada_uid . ' publishListingId:' . $publishListing->id . '  $$publishListing->save() 保存失败:' . print_r($publishListing->errors, true), LogType::ERROR);
            }
        }
    }

    /**
     * 修改产品状态
     * @param $publishListings lazada_publish_listing 表中的一条记录,主要为了获取产品是否为父产品及 _id
     * @param $state
     * @param $status
     * @param string $Info
     */
    public static function changePublishListStateV2($publishListings, $state, $status, $Info = "")
    {
        if (!is_array($publishListings)) {
            $publishListings = array($publishListings);
        }
        $objIds = array();
        $childs = array();
        foreach ($publishListings as $publishListing) {
            if (!$publishListing["isParentSku"]) {
                $childs[] = $publishListing;
            }
            $objIds[] = $publishListing["_id"];
        }
        $manager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        if (!empty($objIds)) {
            $manager->update(array("_id" => array('$in' => $objIds)), array('$set' => array("state" => $state, "status" => $status, "feedInfo" => $Info)), array("multiple" => true));
        }
        foreach ($childs as $child) {
            $manager->update(
                array("lazadaUid" => $child["lazadaUid"], "sellerSku" => $child["product"]["ParentSku"], "childSku.sku" => $child["sellerSku"]),
                array('$set' => array('childSku.$.state' => $state, 'childSku.$.status' => $status))
            );
        }
    }


    public static function changePublishListSateByCondition($q, $state, $status, $info = "")
    {
        $manager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $updatedCursor = $manager->find($q);
        $manager->update($q, array('$set' => array("state" => $state, "status" => $status, "feedInfo" => $info)), array("multiple" => true));
        foreach ($updatedCursor as $updated) {
            if (!$updated["isParentSku"])
                $manager->update(
                    array("lazadaUid" => $updated["lazadaUid"], "sellerSku" => $updated["product"]["ParentSku"], "childSku.sku" => $updated["sellerSku"]),
                    array('$set' => array('childSku.$.state' => $state, 'childSku.$.status' => $status))
                );
        }
    }

    /**
     * @param $puid
     * @param $platform
     * @return array
     * data=array(id,title,shop_name,create_time,image,status,
     * variation=>array(sku,quantity,price)
     * )
     */
    public static function retrieveLazadaPublishListing($puid, $platform, $state, $status = null,$lazadaUsersDropdownList)
    {
        $data = [];
        $puid = intval($puid);
        $q = array("uid" => $puid);
        if (!empty($_REQUEST['shop_name'])) {
            $q["lazadaUid"] = intval($_REQUEST['shop_name']);
        }
        $q["platform"] = $platform;
        $q["isParentSku"] = true;
        $q['$and'][] = array('$or' => array(array("state" => array('$in' => $state)), array('childSku.state' => array('$in' => $state))));
        if (!empty($status)) {
            $q['$and'][] = array('$or' => array(array("status" => array('$in' => $status)), array('childSku.status' => array('$in' => $status))));
        }
        self::assembleResearch($q);
        $cursor = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING)->find($q);
        $pages = new Pagination(['totalCount' => $cursor->count(), 'defaultPageSize' => 20, 'pageSizeLimit' => [5, 200], 'params' => $_REQUEST]);
        $lazadaPublishLists = $cursor->sort(['createTime' => -1])->skip($pages->offset)->limit($pages->limit);
        $variantQ = array("uid" => $puid, "platform" => $platform, "state" => array('$in' => $state));
        foreach ($lazadaPublishLists as $lazadaPublishList) {
            $childSkus = array();
            $variation = array();
            $feedInfo = "";
            foreach ($lazadaPublishList["childSku"] as $child) {
                if (in_array($child["state"], $state)) {
                    if (!empty($status) && !in_array($child["status"], $status)) {
                        continue;
                    }
                    $childSkus[] = $child["sku"];
                }
            }

            if (in_array($lazadaPublishList["state"], $state)) {

                if (!(!empty($status) && !in_array($lazadaPublishList["status"], $status))) {
                    if (isset($lazadaPublishList["product"]["Quantity"])) {
                        $variation[] = array("sku" => $lazadaPublishList["sellerSku"], "quantity" => $lazadaPublishList["product"]["Quantity"], "price" => $lazadaPublishList["product"]["Price"]);
                    } else {
                        $variation[] = array("sku" => $lazadaPublishList["sellerSku"], "quantity" => 0, "price" => $lazadaPublishList["product"]["Price"]);
                    }
                    if (isset($lazadaPublishList['feedInfo'])){
                        $subInfo = trim($lazadaPublishList['feedInfo']);
                        if (!empty($subInfo)) {
                            $feedInfo = $lazadaPublishList["sellerSku"] . "=>" . $subInfo . '</br>';
                        }
                    }
                }
            }

            $variantQ["sellerSku"] = array('$in' => $childSkus);
            $public_array = [];
            $variantCursor = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING)->find($variantQ);
            foreach ($variantCursor as $variant) {
                if (isset($lazadaPublishList["product"]["Quantity"])) {
                    $variation[] = array("sku" => $variant["sellerSku"], "quantity" => $variant["product"]["Quantity"], "price" => $variant["product"]["Price"]);
                } else {
                    $variation[] = array("sku" => $variant["sellerSku"], "quantity" => 0, "price" => $variant["product"]["Price"]);
                }
                if(isset($variant['feedInfo'])){
                    $subInfo = trim($variant['feedInfo']);
                    if (!empty($subInfo)) {
                        $feedInfo = $feedInfo . $variant["sellerSku"] . "=>" . $variant["feedInfo"] . '</br>';
                    }
                }
            }
            $public_array['id'] = $lazadaPublishList['_id']->{'$id'};
            $public_array['title'] = $lazadaPublishList["product"]["Name"];
            $public_array['variation'] = $variation;
            $public_array['feed_info'] = $feedInfo;
            $public_array['shop_name'] = isset($lazadaUsersDropdownList[$lazadaPublishList['lazadaUid']]) ? $lazadaUsersDropdownList[$lazadaPublishList['lazadaUid']] : "";// 解绑没有
            $public_array['create_time'] = date("Y-m-d H:i:s", $lazadaPublishList['createTime']);
            $public_array['image'] = isset($lazadaPublishList['mainImageThumbnail']) ? (!empty($lazadaPublishList['mainImageThumbnail']) ? $lazadaPublishList['mainImageThumbnail'] : $lazadaPublishList['mainImage']) : $lazadaPublishList['mainImage'];
            $public_array['status'] = $lazadaPublishList['status'];
            $data[] = $public_array;
        };
        return array($data, $pages);
    }

    /**
     * @param $puid
     * @param $lazadaIds
     * @param $status
     * @return array
     * onlineProduct=array(LazadaListing产品信息,childSku=>array(LazadaListing子产品信息))
     */
    public static function retrieveLazadaListing($puid, $lazadaIds, $status = "active")
    {
        $q = array("uid" => $puid, "lazadaUid" => array('$in' => $lazadaIds));
        if (!empty($_REQUEST['shop_name'])) {
            $q["lazadaUid"] = intval($_REQUEST['shop_name']);
        }
        $q["product.Status"] = $status;
        $q["isParent"] = true;
        $search_status = "";
        self::assembleResearch($q);
        if (isset($_REQUEST['sub_status']) && $_REQUEST['sub_status'] <> '') {
            $q["subStatus"] = new \MongoRegex('/' . preg_quote($_REQUEST['sub_status']) . '/i');
        }
        $cursor = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING)->find($q);
        $pages = new Pagination(['totalCount' => $cursor->count(), 'defaultPageSize' => 20, 'pageSizeLimit' => [5, 200], 'params' => $_REQUEST]);
        $lazadaListings = $cursor->sort(['createTime' => -1])->skip($pages->offset)->limit($pages->limit);;//以parent_sku为一条记录
        $onlineProduct = array();
        foreach ($lazadaListings as $lazadaListing) {
            $sellerSku = $lazadaListing["sellerSku"];
            $onlineProduct[$sellerSku] = $lazadaListing;
            if (count($lazadaListing["childSku"]) > 0) {
                $childCursor = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING)
                    ->find(array("lazadaUid" => $lazadaListing["lazadaUid"], "sellerSku" => array('$in' => $lazadaListing["childSku"])));
                $onlineProduct[$sellerSku]["childSku"] = array();
                foreach ($childCursor as $childLazadaListing) {
                    $onlineProduct[$sellerSku]["product"]["Quantity"] += $childLazadaListing["product"]["Quantity"];
                    $onlineProduct[$sellerSku]["childSku"][] = $childLazadaListing;
                }
            }
        }
        return array($search_status, $onlineProduct, $pages);
    }

    /**
     * @param $q
     * @return mixed
     */
    private static function assembleResearch(&$q)
    {
        if (!empty($_REQUEST['condition']) && !empty($_REQUEST['condition_search'])) {
            $_REQUEST['condition_search'] = trim($_REQUEST['condition_search']);
            if ($_REQUEST['condition'] == 'title') {
                $searchStr = $_REQUEST['condition_search'];
                $q["product.Name"] = new \MongoRegex('/' . preg_quote($searchStr) . '/i');
            }
            if ($_REQUEST['condition'] == 'sku') {
                $searchStr = $_REQUEST['condition_search'];
                $q["sellerSku"] = new \MongoRegex('/' . preg_quote($searchStr) . '/i');
            }
        }
    }

    public static function fuzzyQueryCategories($search, $site, $platform)
    {
        $q = array("name" => new \MongoRegex('/' . preg_quote($search) . '/i'), "site" => $site, "platform" => $platform, "updateTime" => array('$lt' => (time() - 5 * 60 * 1000)));
        $categories = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_LEAFS)
            ->find($q);
        if ($categories->count() == 0) {
            unset($q["updateTime"]);
            $categories = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_LEAFS)
                ->find($q);
        }
        $rtn = array();
        foreach ($categories as $category) {
            $route = $category["route"];
            $route[count($route) + 1] = array("name" => $category["name"], "categoryId" => $category["categoryId"], "globalIdentifier" => $category["globalIdentifier"]);
            $rtn[] = $route;
        }
        return $rtn;
    }

    public static function saveProducts($lazadaUid, $platform, $products, $puid, $op)
    {
        $lazadaUser = SaasLazadaUser::findOne(['lazada_uid' => $lazadaUid]);
        if (empty($lazadaUser)) {
            return ResultHelper::getResult(400, '', "账号不存在");
        }
        $editStatus = array(
            LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0],
            LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL]
        );
        $now = time();
        $lazadaReadyPublishParent = array();
        $lazadaReadyPublishChild = array();
        $lazadaReadyPublishParentHasChild = array();
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        foreach ($products as $productInfo) {
            $mainImage = $productInfo['mainImage'];
            $images = $productInfo['images'];
            if (empty($mainImage) && !empty($images)) {
                $mainImage = $images[0];
            }
            $mainImageThumbnail = $productInfo['mainImageThumbnail'];
            $imagesThumbnail = $productInfo['imagesThumbnail'];
            if (empty($mainImageThumbnail) && !empty($imagesThumbnail)) {
                $mainImageThumbnail = $imagesThumbnail[0];
            }
            if (!empty($productInfo['id'])) {
                $lazadaPublishListing = $lazadaPublishListingManager->findOne(array('_id' => new MongoId($productInfo['id'])));
                if (empty($lazadaPublishListing)) {
                    return ResultHelper::getResult(400, '', "产品不存在！");
                } else if (!in_array($lazadaPublishListing->status, $editStatus)) {
                    return ResultHelper::getResult(400, '', "产品正在处理中，不允许编辑和保存！");
                } else {
                    $lazadaPublishListingManager->remove(array('_id' => $lazadaPublishListing['_id']));
                    unset($lazadaPublishListing["_id"]);
                }

            } else {
                $lazadaPublishListing = array();
                $isNew = true;
                $lazadaPublishListing['creatTime'] = $now;
                $lazadaPublishListing['updateTime'] = $now;
            }
            $lazadaPublishListing['uid'] = $puid;
            $lazadaPublishListing['lazadaUid'] = $lazadaUid;
            $lazadaPublishListing['platform'] = $platform;
            $lazadaPublishListing['site'] = $lazadaUser->lazada_site;
            $lazadaPublishListing['product'] = $productInfo['product'];
            $lazadaPublishListing['mainImage'] = $mainImage;
            $lazadaPublishListing['images'] = $images;
            $lazadaPublishListing['mainImageThumbnail'] = $mainImageThumbnail;
            $lazadaPublishListing['imagesThumbnail'] = $imagesThumbnail;
            $lazadaPublishListing['state'] = LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT;
            $lazadaPublishListing['status'] = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0];
            $lazadaPublishListing['sellerSku'] = $productInfo["product"]["SellerSku"];
            if ($productInfo["isParentSku"] === false) {
                $lazadaPublishListing['isParentSku'] = false;
                $lazadaReadyPublishChild[] = $lazadaPublishListing;
            } else {
                $lazadaPublishListing['isParentSku'] = true;
                if (!empty($productInfo['childSku'])) {
                    $lazadaPublishListing['childSku'] = $productInfo['childSku'];
                    $lazadaReadyPublishParentHasChild[] = $lazadaPublishListing;
                } else {
                    $lazadaReadyPublishParent[] = $lazadaPublishListing;
                }
            }

            if ($isNew) {// 保存历史选择目录
                $path = LazadaApiHelper::getSelectedCategoryHistoryPath($platform);
                $path = $path . $lazadaUid;
                $historyCatsStr = ConfigHelper::getConfig($path);
                $historyCats = array();
                if (empty($historyCatsStr)) {
                    $historyCats[] = $productInfo['primaryCategory'];
                } else {
                    $historyCats = json_decode($historyCatsStr, true); // 不能为空
                    if (!in_array($productInfo['primaryCategory'], $historyCats)) {
                        $historyCats[] = $productInfo['primaryCategory'];
                    }
                }
                ConfigHelper::setConfig($path, json_encode($historyCats));// config 记录字段大小有限，所以这里只记录目录id，不记录目录信息
            }
        }

        if (!empty($lazadaReadyPublishParent)) {
            $lazadaPublishListingManager->batchInsert($lazadaReadyPublishParent);
        }
        if (!empty($lazadaReadyPublishParentHasChild)) {
            $lazadaPublishListingManager->batchInsert($lazadaReadyPublishParentHasChild);
            $sellerSkuObjIdMap = array();
            foreach ($lazadaReadyPublishParentHasChild as $parent) {
                foreach ($parent['childSku'] as $childSku) {
                    $sellerSkuObjIdMap[$childSku] = $parent['_id'];
                }
            }
            for ($i = 0; $i < count($lazadaReadyPublishChild); $i++) {
                $lazadaReadyPublishChild[$i]['parentId'] = $sellerSkuObjIdMap[$lazadaReadyPublishChild[$i]['sellerSku']];
            }
            $lazadaPublishListingManager->batchInsert($lazadaReadyPublishChild);
        }

        // 发布产品
        if (isset($op) && $op == 2) {
            $parentObjIds = array();
            foreach ($lazadaReadyPublishParent as $parent) {
                $parentObjIds[] = $parent['_id']->{'$id'};
            }
            foreach ($lazadaReadyPublishParentHasChild as $parent) {
                $parentObjIds[] = $parent['_id']->{'$id'};
            }
            foreach ($lazadaReadyPublishChild as $child) {
                $parentObjIds[] = $parent['_id']->{'$id'};
            }
            list($ret, $message) = LazadaLinioJumiaProductFeedHelperV2::productPublish($parentObjIds);
            if ($ret == false) {
                return ResultHelper::getResult(400, '', $message);
            } else {
                return ResultHelper::getResult(200, '', "产品保存并提交发布成功，发布结果请留意产品后续提示。");
            }
        }

        return ResultHelper::getResult(200, '', "刊登产品保存成功。");
    }

    /**
     * 根据变体信息生成lazada_publish_listing表中的多条数据,
     *
     * 不包含站点信息,即site和lazadaUid字段
     *
     * @param $productInfo
     * @return array
     */
    public static function divideVariation($productInfo, $puid, $platform)
    {
        $dividedProducts = array();
        $lazadaPublishListings = array();
        if (!isset($productInfo["Variations"])) {
            $dividedProducts[] = $productInfo["product"];
        } else {
            foreach ($productInfo["Variations"] as $variation) {
                $variationProduct = $productInfo["product"];
                foreach ($variation as $key => $value) {
                    $variationProduct[$key] = $value;
                }
                $dividedProducts[] = $variationProduct;
            }
        }
        $childSku = array();
        for ($i = 1; $i < count($dividedProducts); $i++) {
            $dividedProducts[$i]["ParentSku"] = $dividedProducts[0]["SellerSku"];
            $childSku[] = array("sku" => $dividedProducts[$i]["SellerSku"],
                "state" => LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, "status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0]);
        }


        $mainImage = $productInfo['mainImage'];
        $images = $productInfo['images'];
        if (empty($mainImage) && !empty($images)) {
            $mainImage = $images[0];
        }
        $mainImageThumbnail = $productInfo['mainImageThumbnail'];
        $imagesThumbnail = $productInfo['imagesThumbnail'];
        if (empty($mainImageThumbnail) && !empty($imagesThumbnail)) {
            $mainImageThumbnail = $imagesThumbnail[0];
        }
        $now = time();
        for ($i = 0; $i < count($dividedProducts); $i++) {
            $lazadaPublishListing=array();
            $lazadaPublishListing['uid'] = intval($puid);
            $lazadaPublishListing['platform'] = $platform;
            $lazadaPublishListing['product'] = $dividedProducts[$i];
            $lazadaPublishListing['mainImage'] = $mainImage;
            $lazadaPublishListing['images'] = $images;
            $lazadaPublishListing['mainImageThumbnail'] = $mainImageThumbnail;
            $lazadaPublishListing['imagesThumbnail'] = $imagesThumbnail;
            $lazadaPublishListing['state'] = LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT;
            $lazadaPublishListing['status'] = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0];
            $lazadaPublishListing['sellerSku'] = $dividedProducts[$i]["SellerSku"];
            if ($i == 0) {
                $lazadaPublishListing['childSku'] = $childSku;
                $lazadaPublishListing['isParentSku'] = true;
            }else{
                $lazadaPublishListing['isParentSku'] = false;
            }
            $lazadaPublishListing['createTime'] = $now;
            $lazadaPublishListing['updateTime'] = $now;
            $lazadaPublishListings[] = $lazadaPublishListing;
        }

        return $lazadaPublishListings;
    }

    private static function checkSkus($lazadaUid,$variations){
        $sellerSkus=array();
        foreach ($variations as $variation){
            $sellerSkus[]=$variation["SellerSku"];
        }
        $rlts=LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING)->find(array("lazadaUid"=>$lazadaUid,"sellerSku"=>array('$in'=>$sellerSkus)),array("sellerSku"=>true));
        $exist=array();
        foreach ($rlts as $rlt){
            $exist[]=$rlt["sellerSku"];
        }
        return $exist;
    }

    public static function oneSiteProductSave($oneSiteInfo, $puid, $platform)
    {
        $lazadaUid = $oneSiteInfo["lazadaUid"];
        $products = $oneSiteInfo["products"];
        $rawLazadaListingProducts = array();
        $oldObjs = array();
        foreach ($products as $product) {
            $exist=self::checkSkus($lazadaUid, $product["Variations"]);
            if(!empty($exist)){
                $msg="sku重复:".implode("|",$exist );
                return array(false,$msg);
            }
            $rawLazadaListingProducts = array_merge($rawLazadaListingProducts, self::divideVariation($product, $puid, $platform));
            if (!empty($product["id"])) {
                $oldObjs[] = new MongoId($product["id"]);
            }
        }
        $lazadaUser = SaasLazadaUser::findOne($lazadaUid);
        if (empty($lazadaUser)) {
            return array(false, "账号不存在");
        }
        for ($i = 0; $i < count($rawLazadaListingProducts); $i++) {
            $rawLazadaListingProducts[$i]["site"] = $lazadaUser->lazada_site;
            $rawLazadaListingProducts[$i]["lazadaUid"] = intval($lazadaUid);
        }
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $lazadaPublishListingManager->batchInsert($rawLazadaListingProducts);

        $parentsPrimaryCats = array();
        $parentsObjIds = array();
        for ($i = 0; $i < count($rawLazadaListingProducts); $i++) {
            if ($rawLazadaListingProducts[$i]["isParentSku"]) {
                $parentsObjIds[] = $rawLazadaListingProducts[$i]["_id"]->{'$id'};
                $parentsPrimaryCats[] = $rawLazadaListingProducts[$i]["product"]["PrimaryCategory"];
            }
        }

        if (!empty($oldObjs)) {
            foreach ($oldObjs as $oldObj) {
                LLJHelper::deleteProductIncludeChilds($oldObj);
            }
        } else {
            $path = LazadaApiHelper::getSelectedCategoryHistoryPath($platform);
            $path = $path . $lazadaUid;
            $historyCatsStr = ConfigHelper::getConfig($path);
            $historyCats = array();
            if (empty($historyCatsStr)) {
                $historyCats[] = $parentsPrimaryCats[0];
            } else {
                $historyCats = json_decode($historyCatsStr, true); // 不能为空
            }
            for ($i = 1; $i < count($parentsPrimaryCats); $i++) {
                if (!in_array($parentsPrimaryCats[$i], $historyCats)) {
                    $historyCats[] = $parentsPrimaryCats[$i];
                }
            }
            ConfigHelper::setConfig($path, json_encode($historyCats));// config 记录字段大小有限，所以这里只记录目录id，不记录目录信息
        }

        return array(true, $parentsObjIds);


    }

    /**
     * 如果产品有子产品,则子产品一起删除
     * @param $parentObjId
     */
    public static function deleteProductIncludeChilds($parentObjId)
    {
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $parent = $lazadaPublishListingManager->findOne(array('_id' => $parentObjId));

        if ($parent["isParentSku"] == true) {
            $skus = $parent["childSku"];
        } else {
            $skus = array();
        }
        $skus[] = $parent["sellerSku"];
        return $lazadaPublishListingManager->remove(array("uid" => $parent["uid"], "lazadaUid" => $parent["lazadaUid"], "sellerSku" => array('$in' => $skus)));
    }

    /**
     * @param $lazadaUser
     * @return array
     */
    public static function assembleConfig($lazadaUser)
    {
        if(!empty($lazadaUser->version)){//新授权
            $returnInfo = array(
                "userId" => $lazadaUser->platform_userid,
//                 "apiKey" => $lazadaUser->token,
                "apiKey" => $lazadaUser->access_token,
                "countryCode" => $lazadaUser->lazada_site,
                "version"=>"v2",
            );
        }else{
            $returnInfo = array(
                "userId" => $lazadaUser->platform_userid,
                "apiKey" => $lazadaUser->token,
                "countryCode" => $lazadaUser->lazada_site,
                "version"=>"",
            );
        }
        
        return $returnInfo;
    }

    /**
     * 根据传入的id,返回子产品,父产品状态为draft,fail,子产品状态为parent_uploaded。
     * 并更改相应产品状态为processing
     * @param $objId 父产品sku
     */
    public static function getAllReadyForPublishProducts($objId)
    {
        $readyProducts = array();
        $lazadaPublishListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_PUBLISH_LISTING);
        $oldLazadaPublishListing = $lazadaPublishListingManager
            ->findAndModify(
                array("_id" => $objId, 'state' => array('$in' => array(LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL)), "status" => array('$ne' => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1])),
                array('$set' => array("status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]))
            );
        if (!empty($oldLazadaPublishListing) && $oldLazadaPublishListing["status"] != LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]) {
            $readyProducts[] = $oldLazadaPublishListing;
        } else if (empty($oldLazadaPublishListing)) {
            $oldLazadaPublishListing = $lazadaPublishListingManager->findOne(array("_id" => $objId));
        }
        //如果父产品不需要重新发布,则表示父产品已经发布成功,子产品可以发布
        $readyStates = array(LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT, LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL, LazadaApiHelper::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD);
        if (empty($readyProducts)) {
            foreach ($oldLazadaPublishListing["childSku"] as $childSku) {
                if (in_array($childSku["state"], $readyStates)) {
                    $oldChild = $lazadaPublishListingManager
                        ->findAndModify(
                            array("lazadaUid" => $oldLazadaPublishListing["lazadaUid"], "sellerSku" => $childSku["sku"], "status" => array('$ne' => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1])),
                            array('$set' => array("status" => LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][1]))
                        );
                    if (!empty($oldChild)) {
                        $readyProducts[] = $oldChild;
                    }
                }
            }
        } else {
            $childSkus = array();
            foreach ($oldLazadaPublishListing["childSku"] as $childSku) {
                $childSkus[] = $childSku["sku"];
            }
            $subQ = array("lazadaUid" => $oldLazadaPublishListing["lazadaUid"], "sellerSku" => array('$in' => $childSkus));
            $lazadaPublishListingManager
                ->update(
                    array("lazadaUid" => $oldLazadaPublishListing["lazadaUid"], "sellerSku" => array('$in' => $childSkus)),
                    array('$set' => array('state' => LazadaApiHelper::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD)),
                    array("multiple" => true)
                );

            self::changePublishListSateByCondition($subQ, LazadaApiHelper::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD, LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD][0], "");
        }

        return $readyProducts;
    }

    public static function TranferToNormalData($data){
        var_dump($data['child']);die;
        $result = [];
        $result['variations'] = [];
        $variance = ['Variation','SellerSku','SalePrice','Price','SaleStartDate','SaleEndDate','Quantity','Color','ProductId'];
        foreach($data['parent'] as $key => $val){
            if($key == 'product'){
                foreach($data['parent']['product'] as $k => $v){
                    if(in_array($k, $variance)){
                        $result['variations'][0][$k] = $data['parent']['product'][$k];    
                    }else if($k == 'ProductData'){
                       $result = array_merge($result,$data['parent']['product']['ProductData']);
                    }else{
                       $result[$k] = $data['parent']['product'][$k];
                    }
                }
            }else{
                $result[$key] = $data['parent'][$key];
            }
        }
        if(!empty($data['child'])){
            foreach($data['child'] as $key => $val){
                array_push($result['variations'],[
                    'Variation'=> $data['child'][$key]['product']['Variation'],
                    'SellerSku'=> $data['child'][$key]['product']['SellerSku'],
                    'SalePrice'=> $data['child'][$key]['product']['SalePrice'],
                    'Price' => $data['child'][$key]['product']['SaleStartDate'],
                    'SaleStartDate'=> $data['child'][$key]['product']['SaleStartDate'],
                    'SaleEndDate' => $data['child'][$key]['product']['SaleEndDate'],
                    'Quantity' => $data['child'][$key]['product']['Quantity'],
                    'Color' => $data['child'][$key]['product']['Color'],
                    'ProductId' => $data['child'][$key]['product']['ProductId']
                ]);
            }
        }
        return $result;
    }
}