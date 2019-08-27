<?php

namespace eagle\modules\lazada\apihelpers;

use common\models\DefaultInfoType;
use common\mongo\lljListing\LazadaCategoriesLeafs;
use common\mongo\lljListing\LazadaCategoriesSingleNodes;
use common\mongo\lljListing\LazadaCategoryAttrMG;
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use common\mongo\lljListing\UpdateState;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use \Yii;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\models\LazadaCategories;
use eagle\models\LazadaCategoryAttr;
use eagle\models\SaasLazadaAutosync;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\listing\helpers\LazadaFeedHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\LazadaBrand;
use eagle\modules\message\helpers\MessageBGJHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;

/**
 * +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行非订单类数据同步
 * +------------------------------------------------------------------------------
 */
class SaasLazadaAutoSyncApiHelperV2
{


    const LOG_ID = "eagle-modules-lazada-apihelpers-SaasLazadaAutoSyncApiHelperV2";

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }


    /**
     * 先判断在线商品是否修改中
     * @param  $listingId -- saas_lazada_autosync表的id
     * @return null或者$SAA_obj , null表示抢不到记录
     */
    private static function _lockLazadaListingRecord($listingId, $type)
    {
        $nowTime = time();
        $connection = Yii::$app->get("subdb");
        $command = $connection->createCommand("update lazada_listing set is_editing=$type,update_time=$nowTime where id=$listingId and is_editing=0 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
        // 抢到记录
        $listing = LazadaListing::findOne($listingId);
        return $listing;
    }

    private static function _lockLazadaListingRecordV2($listingId, $type)
    {
        $old = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING)->findAndModify(array("_id" => $listingId), array('$set' => array("isEditing" => $type)));
        if ($old["isEditing"] == $type) {
            return null;
        }
        return $old;
    }

    /**
     * 获取所有lazada用户的api访问信息。 email,token,销售站点
     */
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

    /**
     * 从proxy中获取目录树
     * @param array $config
     * @param boolean $loadCache
     */
    public static function getCategoryTree($config, $loadCache = true)
    {
        // 目录先记录在数据库中，后面再考虑可能搬到redis里面
        $categoriesJsonStrObj = LazadaCategories::findOne(['site' => $config['countryCode']]);
        if (empty($categoriesJsonStrObj) || $loadCache == false) {
            $response = LazadaInterface_Helper::getCategoryTree($config);
//            CommonHelper::insertRawDataToRawResponseData("getCategoryTree", $response);
            if ($response['success'] != true) {
                return array(false, $response['message']);
            }

            $categoryTree = $response['response']['categories'];
            $categories = array();
            foreach ($categoryTree as $category) {
                LazadaApiHelper::getCategoryInfo($category, 1, 0, $config['countryCode'], $categories);
            }

            $categoriesJsonStr = json_encode($categories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

            $nowTime = time();
            if (empty($categoriesJsonStrObj)) {
                $categoriesJsonStrObj = new LazadaCategories();
                $categoriesJsonStrObj->create_time = $nowTime;
            }

            $categoriesJsonStrObj->site = $config['countryCode'];
            $categoriesJsonStrObj->categories = $categoriesJsonStr;
            $categoriesJsonStrObj->update_time = $nowTime;
            if (!$categoriesJsonStrObj->save()) {
                \Yii::error("getCategoryTree error:" . print_r($categoriesJsonStrObj->errors, true), "file");
                return array(false, "获取目录失败");
            }
        } else {
            $categoriesJsonStr = $categoriesJsonStrObj->categories;
            $categories = json_decode($categoriesJsonStr, true);
        }

        if (empty($categories)) {
            return array(false, "获取目录失败");
        }

        return array(true, $categories);
    }

    /**
     * 更新数据库中的类目表:lazada_categories_leafs,lazada_categories_single_nodes
     * @param $config
     * @param $platform
     * @return array
     */
    public static function refreshCategoryCategoriesleafs($config, $platform)
    {

        self::localLog("refreshCategoryCategoriesleafs start call getCategoryTree..... ");
        $response = LazadaInterface_Helper::getCategoryTree($config);
        self::localLog("refreshCategoryCategoriesleafs end call getCategoryTree ");

        if ($response['success'] != true) {
            return array(false, $response['message']);
        }
        $categories = $response['response']['categories'];
        $count = 0;
        $now = time();
        foreach ($categories as $category) {
            $categoriesLeafs = array();
            $categoryNodes = array();
            self::localLog("refreshCategoryCategoriesleafs start dividCategories, seq " . $count++);
            self::dividCategories($category, 0, array(), $categoriesLeafs, $categoryNodes);
            self::localLog("refreshCategoryCategoriesleafs end dividCategories ");
            for ($i = 0; $i < count($categoriesLeafs); $i++) {
                $categoriesLeafs[$i]->site = $config['countryCode'];
                $categoriesLeafs[$i]->platform = $platform;
                $categoriesLeafs[$i]->updateTime = $now;
            }
            for ($i = 0; $i < count($categoryNodes); $i++) {
                $categoryNodes[$i]->site = $config['countryCode'];
                $categoryNodes[$i]->platform = $platform;
                $categoryNodes[$i]->updateTime = $now;
            }
            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_LEAFS)->batchInsert($categoriesLeafs);
            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_SINGLE_NODES)->batchInsert($categoryNodes);
        }
        LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_LEAFS)->remove(array("platform" => $platform, "site" => $config['countryCode'], "updateTime" => array('$lt' => $now)));
        LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_SINGLE_NODES)->remove(array("platform" => $platform, "site" => $config['countryCode'], "updateTime" => array('$lt' => $now)));
        return array(true, "更新成功");
    }

    public static function refreshAllCategories()
    {
        $sites = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO)
            ->find(array("infoType" => DefaultInfoType::LLJ_SITE));
        $taskNum = $sites->count();
        foreach ($sites as $site) {
            self::localLog("left task " . $taskNum);
            $taskNum--;
            $content = $site["content"];
            $config = array("userId" => $content["userId"], "apiKey" => $content["token"], "countryCode" => $content["site"]);
            list($success, $msg) = SaasLazadaAutoSyncApiHelperV2::refreshCategoryCategoriesleafs($config, $content["platform"]);
            if (!$success) {
                self::localLog($msg);
                $users = SaasLazadaUser::find()->where(array('lazada_site' => $content["site"], "platform" => $content["platform"], "status" => 1))->all();
                foreach ($users as $user) {
                    $config["userId"] = $user->platform_userid;
                    $config["apiKey"] = $user->token;
                    list($success, $msg) = SaasLazadaAutoSyncApiHelperV2::refreshCategoryCategoriesleafs($config, $content["platform"]);
                    if ($success) {
                        self::localLog("token " . $user->token . " userId " . $user->platform_userid);
                        LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO)
                            ->findAndModify(array("_id" => $site["_id"]), array('$set' => array("content.token" => $user->token, "content.userId" => $user->platform_userid)));
                        break;
                    }
                }
            }
        }
    }

    public static function refreshAllLeafAttributes()
    {

        while (true) {
            $site = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO)
                ->findAndModify(array("infoType" => DefaultInfoType::LLJ_SITE, "content.state" => array('$nin' => array("processing", "success"))), array('$set' => array("content.state" => "processing")));
            if (empty($site)) {
                self::localLog("no more task ... and returned");
                return;
            }
            $content = $site["content"];
            $lazadaLeafs = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORIES_LEAFS)->find(array("site" => $content["site"], "platform" => $content["platform"]));
            $leafNum = $lazadaLeafs->count();
            $config = array("userId" => $content["userId"], "apiKey" => $content["token"], "countryCode" => $content["site"]);
            $now = time();
            $lazadaCategoryAttrs = array();
            $batchCount = 0;
            foreach ($lazadaLeafs as $lazadaLeaf) {
                self::localLog("left leaf num is " . $leafNum);
                $leafNum--;
                $batchCount++;
                $response = LazadaInterface_Helper::getCategoryAttributes($config, array('PrimaryCategory' => $lazadaLeaf["categoryId"]));
                if ($response['success'] != true) {
                    $users = SaasLazadaUser::find()->where(array('lazada_site' => $content["site"], "platform" => $content["platform"], "status" => 1))->all();
                    foreach ($users as $user) {
                        $config["userId"] = $user->platform_userid;
                        $config["apiKey"] = $user->token;
                        $response = LazadaInterface_Helper::getCategoryAttributes($config, array('PrimaryCategory' => $lazadaLeaf["categoryId"]));
                        if ($response['success']) {
                            self::localLog("token " . $user->token . " userId " . $user->platform_userid);
                            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO)
                                ->findAndModify(array("_id" => $site["_id"]), array('$set' => array("content.token" => $user->token, "content.userId" => $user->platform_userid)));
                            break;
                        }
                    }
                }
                $lazadaCategoryAttr = new LazadaCategoryAttrMG();
                $lazadaCategoryAttr->attributes = $response['response']['attributes'];
                $lazadaCategoryAttr->categoryId = $lazadaLeaf["categoryId"];
                $lazadaCategoryAttr->updateTime = $now;
                $lazadaCategoryAttr->platform = $content["platform"];
                $lazadaCategoryAttr->site = $config["countryCode"];
                $lazadaCategoryAttrs[] = $lazadaCategoryAttr;
                if ($batchCount > 10) {
                    while (true) {
                        try {
                            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)->batchInsert($lazadaCategoryAttrs);
                            $batchCount = 0;
                            $lazadaCategoryAttrs = array();
                            break;
                        } catch (\MongoCursorException $e) {
                            self::localLog(json_encode($e->getTrace()));
                            sleep(100);
                            self::localLog("retry start...");
                        }
                    }

                }
            }

            while (true) {
                try {
                    LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)->batchInsert($lazadaCategoryAttrs);
                    break;
                } catch (\MongoCursorException $e) {
                    self::localLog(json_encode($e->getTrace()));
                    sleep(100);
                    self::localLog("retry start...");
                }
            }
            while (true) {
                try {
                    LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)->remove(array("site" => $content["site"], "platform" => $content["platform"], "updateTime" => array('$lt' => $now)));
                    break;
                } catch (\MongoCursorException $e) {
                    self::localLog(json_encode($e->getTrace()));
                    sleep(100);
                    self::localLog("retry start...");
                }
            }
            while (true) {
                try {
                    LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::DEFAULT_INFO)->update(array('_id' => $site['_id']), array('$set' => array("content.state" => "success")));
                    break;
                } catch (\MongoCursorException $e) {
                    self::localLog(json_encode($e->getTrace()));
                    sleep(100);
                    self::localLog("retry start...");
                }
            }
        }
    }

    public static function dividCategories($category, $level, $route, &$categoriesLeafs, &$categoryNodes)
    {
        $level++;
        $singleNode = new LazadaCategoriesSingleNodes();
        $singleNode->name = $category['Name'];
        $singleNode->categoryId = $category['CategoryId'];
        $singleNode->globalIdentifier = $category["GlobalIdentifier"];
        $singleNode->level = $level;
        if (!empty($route)) {
            $singleNode->parentCategoryId = $route[$level - 1]["categoryId"];
        }

        if (!empty($category['Children'])) {
            $route[$level] = array("name" => $category['Name'], "categoryId" => $category['CategoryId'], "globalIdentifier" => $category["GlobalIdentifier"]);
            $singleNode->isLeaf = false;
            $categoryNodes[] = $singleNode;

            if (!isset($category['Children']['Category']['Name'])) {
                foreach ($category['Children']['Category'] as $child) {
                    self::dividCategories($child, $level, $route, $categoriesLeafs, $categoryNodes);
                }
            } else {
                self::dividCategories($category['Children']['Category'], $level, $route, $categoriesLeafs, $categoryNodes);
            }

        } else {
            $lazadaCategoriesLeafs = new LazadaCategoriesLeafs();
            $lazadaCategoriesLeafs->route = $route;
            $lazadaCategoriesLeafs->categoryId = $category['CategoryId'];
            $lazadaCategoriesLeafs->name = $category['Name'];
            $lazadaCategoriesLeafs->globalIdentifier = $category["GlobalIdentifier"];
            $categoriesLeafs[] = $lazadaCategoriesLeafs;
            $singleNode->isLeaf = true;
            $categoryNodes[] = $singleNode;
        }
    }


    public static function getCategoryAttributes($config, $platform, $primaryCategory)
    {
        $lazadaCategoryAttr = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)
            ->findOne(array("categoryId" => $primaryCategory, "site" => $config["countryCode"], "platform" => $platform));
        if (empty($lazadaCategoryAttr)) {
            $now = time();
            self::localLog("refreshCategoryAttributes start call getCategoryAttributes..... ");
            $response = LazadaInterface_Helper::getCategoryAttributes($config, array('PrimaryCategory' => $primaryCategory));
            self::localLog("refreshCategoryAttributes end call getCategoryAttributes ");
            if ($response['success'] != true) {
                return array(false, $response['message']);
            }
            $lazadaCategoryAttr = new LazadaCategoryAttrMG();
            $lazadaCategoryAttr->attributes = $response['response']['attributes'];
            $lazadaCategoryAttr->categoryId = $primaryCategory;
            $lazadaCategoryAttr->updateTime = $now;
            $lazadaCategoryAttr->platform = $platform;
            $lazadaCategoryAttr->site = $config["countryCode"];
            LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_CATEGORY_ATTR)->insert($lazadaCategoryAttr);
        }
        return $lazadaCategoryAttr;
    }

    /**
     * 从proxy中获品牌 ， 从印尼和马来西亚的销售站点来看，品牌不是共用的。
     * @param array $config
     */
    public static function getBrands($config, $searchName = "", $searchMode = "like", $purge = false)
    {
        // 目录先记录在数据库中，后面再考虑可能搬到redis里面
        // 本来是想和目录树和目录属性一样，一个站点一个记录，记录所有品牌，但这次 由于sql太大 还是要拆分来完成了
        $brands = LazadaBrand::find()->where(['site' => $config['countryCode']])->asArray()->all();
        $filterBrands = array();
        if (empty($brands) || $purge) {
            $response = LazadaInterface_Helper::getBrands($config);
            if ($response['success'] != true) {
                return array(false, $response['message']);
            }

            if ($purge == true) {
                LazadaBrand::deleteAll(['site' => $config['countryCode']]);
            }

            $brands = $response['response']['brands'];
            foreach ($brands as &$brand) {
                $brand['site'] = $config['countryCode'];
            }
            try {
                SQLHelper::groupInsertToDb("lazada_brand", $brands, 'db');
                LazadaBrand::updateAll(['update_time' => time()], ['site' => $config['countryCode']]);
            } catch (\Exception $ex) {
                return array(false, "更新错误。" . $ex->getMessage());
                \Yii::error('getBrands groupInsertToDb config:' . print_r($config, true) . ' exception:' . print_r($ex, true), "file");
            }

        }

        foreach ($brands as $oneBrand) {
            if (!empty($searchName)) {
                if ("like" == $searchMode) {// 类似模糊搜索 结果最多20个
                    if (count($filterBrands) <= 20 && false !== stripos($oneBrand['Name'], $searchName)) {// 如果有搜索 限制搜索结果为20个
                        $filterBrands[] = $oneBrand['Name'];
                    }
                } else {// 完全匹配
                    if (strcmp($oneBrand['Name'], $searchName) == 0) {
                        $filterBrands[] = $oneBrand['Name'];
                        return array(true, $filterBrands);
                    }
                }

            } else {
                $filterBrands[] = $oneBrand['Name'];
            }
        }

        if (empty($filterBrands)) {
            return array(false, "获取品牌失败");
        }

        return array(true, $filterBrands);
    }



    // 更改刊登产品状态
    // @param $publishListingIds LazadaPublishListing id 或者 id数组
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
     * 批量修改产品库存
     *
     * @param array $products =array('productIds'=>array(111 ,222,333),'op'=>0, 'quantity'=>50 , 'condition_num'=>20);
     * // op 0,1分别代表 直接修改库存和按数量添加，默认直接修改 ; 3库存少于某值  增加多少 ,4库存少于某值  直接修改库存为某值
     * // condition_num op 为3或4是 的过滤条件
     */
    public static function batchUpdateQuantity($products)
    {
        if (empty($products['productIds'])) {
            return array(false, "请选择修改的产品");
        }

        if (!isset($products['quantity'])) { // 可以为0
            return array(false, "请填写修改库存的数量");
        }

        if (isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && !isset($products['condition_num'])) {
            return array(false, "请填写过滤库存的数量");
        }

        $requestNum = count($products['productIds']);
        $targetListings = self::getLazadaListingByIdStrs($products['productIds']);
        $nowTime = time();
        $updateProducts = array();
        $prodIds = array();
        foreach ($targetListings as $targetListing) {
            // 过滤产品状态，修改中不能进入修改
            // 进入修改中状态
            $targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'], LazadaAutoFetchListingHelper::EDITING_QUANTITY);
            if ($targetListingObj == null)// 产品已经修改中
                continue;
            $newOp = array();
            if (isset($products['op']) && ($products['op'] == 3 || $products['op'] == 4) && isset($products['condition_num'])) {
                $newOp[] = array('edit_time' => $nowTime, 'type' => 'quantity', 'value' => $products['quantity'], 'op' => $products['op'], 'condition_num' => $products['condition_num']);
            } else {
                $newOp[] = array('edit_time' => $nowTime, 'type' => 'quantity', 'value' => $products['quantity'], 'op' => $products['op']);
            }
            self::recordOperation($newOp, $targetListingObj['operationLog'], $targetListing["_id"]);
            $update = array();
            $update['SellerSku'] = htmlentities($targetListing['sellerSku']);
            if (!isset($products['op']) || 0 == $products['op']) {// 默认直接修改
                $update['Quantity'] = $products['quantity'];
            } else if (1 == $products['op']) {// 按数量添加
                $update['Quantity'] = $targetListing['product']['Quantity'] + $products['quantity'];
            } else if (3 == $products['op'] && $targetListing['product']['Quantity'] < $products['condition_num']) {// 少于某数量则添加
                $update['Quantity'] = $targetListing['product']['Quantity'] + $products['quantity'];
            } else if (4 == $products['op'] && $targetListing['product']['Quantity'] < $products['condition_num']) {// 少于某数量则直接修改
                $update['Quantity'] = $products['quantity'];
            }
            $updateProducts[$targetListing['lazadaUid']][] = $update;
            $prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
        }

        $excuteNume = self::doProductUpdate($updateProducts, $prodIds);

        return array(true, "提交了$requestNum 个，成功执行$excuteNume 个。");
    }

    private static function getLazadaListingByIdStrs($productIds)
    {
        $lazadaListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
        $objIds = array();
        foreach ($productIds as $objIdStr) {
            $objIds[] = new \MongoId($objIdStr);
        }
        return $lazadaListingManager->find(array('_id' => array('$in' => $objIds)));
    }

    /**
     * 批量修改产品产品价格
     * @param array $products =array('productIds'=>array(111 ,222,333), 'op'=>0,price'=>50 ); // op 0,1,2 分别代表 直接修改价格，按金额添加和按百分比添加
     */
    public static function batchUpdatePrice($products)
    {
        if (empty($products['productIds'])) {
            return array(false, "请选择修改的产品");
        }

        if (!isset($products['price'])) { // 可以为0
            return array(false, "请填写修改价格");
        }

        $requestNum = count($products['productIds']);
        $targetListings = self::getLazadaListingByIdStrs($products['productIds']);
        $updateProducts = array();
        $prodIds = array();
        foreach ($targetListings as $targetListing) {
            // 过滤产品状态，修改中不能进入修改
            // 进入修改中状态
            $targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'], LazadaAutoFetchListingHelper::EDITING_PRICE);
            if ($targetListingObj == null)// 产品已经修改中
                continue;

            // $operation_log 以json数组形式记录操作
            // operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
            $newOp = array('edit_time' => time(), 'type' => 'price', 'value' => $products['price'], 'op' => $products['op']);
            self::recordOperation($newOp, $targetListingObj['operationLog'], $targetListing['_id']);

            $update = array();
            $update['SellerSku'] = htmlentities($targetListing['sellerSku']);
            if (isset($products['op']) && 1 == $products['op']) {// 按金额添加
                $update['Price'] = $targetListing['product']['Price'] + $products['price'];
            } else if (isset($products['op']) && 2 == $products['op']) {// 按百分比添加
                $update['Price'] = $targetListing['product']['Price'] + round(($products['price'] / 100 * $targetListing['Price']), 2);
            } else {// 默认直接修改
                $update['Price'] = $products['price'];
            }

            $updateProducts[$targetListing['lazadaUid']][] = $update;
            $prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
        }
        $excuteNume = self::doProductUpdate($updateProducts, $prodIds);

        return array(true, "提交了$requestNum 个，成功执行$excuteNume 个。");
    }

    /**
     * 批量修改产品促销信息
     * @param array $products =array('productIds'=>array(111 ,222,333),'op'=>0,
     * 'salePrice'=>198,'saleStartDate'=>'2015-11-11','saleEndDate'=>'2015-12-12');// op 0,1,2 分别代表 直接修改促销价，按金额添加和按百分比添加
     */
    public static function batchUpdateSaleInfo($products)
    {
        if (empty($products['productIds'])) {
            return array(false, "请选择修改的产品");
        }

        if (!isset($products['salePrice'])) { // 可以为0
            return array(false, "请填写促销价格");
        }

        if (empty($products['saleStartDate'])) {
            return array(false, "请选择促销开始时间");
        }

        if (empty($products['saleEndDate'])) {
            return array(false, "请选择促销结束时间");
        }

        if (strtotime($products['saleStartDate']) > strtotime($products['saleEndDate'])) {
            return array(false, "促销结束时间必须晚于开始时间");
        }

        $requestNum = count($products['productIds']);
        $targetListings = self::getLazadaListingByIdStrs($products['productIds']);
        $updateProducts = array();
        $prodIds = array();
        foreach ($targetListings as $targetListing) {
            // 过滤产品状态，修改中不能进入修改
            // 进入修改中状态
            $targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'], LazadaAutoFetchListingHelper::EDITING_SALESINFO);
            if ($targetListingObj == null)// 产品已经修改中
                continue;

            $newOp = array('edit_time' => time(), 'type' => 'sale', 'value' => $products['saleStartDate'] . ',' . $products['saleEndDate'] . ',' . $products['salePrice'], 'op' => $products['op']);
            self::recordOperation($newOp, $targetListingObj["operationLog"], $targetListing['_id']);

            // 填写修改信息
            $update = array();
            $update['SellerSku'] = htmlentities($targetListing['sellerSku']);

            if (isset($products['op']) && 1 == $products['op']) {// 按金额添加
                $update['SalePrice'] = $targetListing['product']['SalePrice'] + $products['salePrice'];
            } else if (isset($products['op']) && 2 == $products['op']) {// 按百分比添加
                $update['SalePrice'] = $targetListing['product']['SalePrice'] + round(($products['salePrice'] / 100 * $targetListing['Price']), 2);
            } else {// 默认直接修改
                $update['SalePrice'] = $products['salePrice'];
            }

            // 检查价格和促销价格大小
            if ($update['SalePrice'] > $targetListingObj['product']['Price']) {
                LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING)->findAndModify(array("_id" => $targetListing["_id"]), array('$set' =>
                    array("isEditing" => 0, "errorMsg" => "Sale price must be lower than the standard price. Sale Price must be blank if standard price is blank")));
                continue;
            }

            // @todo 检查促销时间
            $update['SaleStartDate'] = $products['saleStartDate'];
            $update['SaleEndDate'] = $products['saleEndDate'];
            $update['Price'] = $targetListing['product']['Price'];// linio product update 促销信息要带价格 ， 但所有平台都提交price应该不影响

            $updateProducts[$targetListing['lazadaUid']][] = $update;
            $prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
        }
        $excuteNume = self::doProductUpdate($updateProducts, $prodIds);

        return array(true, "提交了$requestNum 个，成功执行$excuteNume 个。");
    }

    /**
     * 批量上架
     * @param array $products =array(111 ,222,333);
     */
    public static function batchPutOnLine($products)
    {
        if (empty($products)) {
            return array(false, "请选择修改的产品");
        }

        $requestNum = count($products);
        $targetListings = self::getLazadaListingByIdStrs($products);
        $updateProducts = array();
        $prodIds = array();
        foreach ($targetListings as $targetListing) {
            // 过滤产品状态，修改中不能进入修改
            // 进入修改中状态
            $targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'], LazadaAutoFetchListingHelper::PUTING_ON);
            if ($targetListingObj == null)// 产品已经修改中
                continue;
            $newOp = array('edit_time' => time(), 'type' => 'put', 'value' => 'on');
            self::recordOperation($newOp, $targetListingObj["operationLog"], $targetListing['_id']);

            // 填写修改信息
            $update = array();
            $update['Status'] = "active";
            $update['SellerSku'] = htmlentities($targetListing['sellerSku']);
            $updateProducts[$targetListing['lazadaUid']][] = $update;
            $prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
        }
        $excuteNume = self::doProductUpdate($updateProducts, $prodIds);

        return array(true, "提交了$requestNum 个，成功执行$excuteNume 个。");
    }

    /**
     * 批量下架
     * @param array $products =array(111 ,222,333);
     */
    public static function batchPutOffLine($products)
    {
        if (empty($products)) {
            return array(false, "请选择修改的产品");
        }

        $requestNum = count($products);

        $targetListings = self::getLazadaListingByIdStrs($products);
        $updateProducts = array();
        $prodIds = array();
        foreach ($targetListings as $targetListing) {
            // 过滤产品状态，修改中不能进入修改
            // 进入修改中状态
            $targetListingObj = self::_lockLazadaListingRecordV2($targetListing['_id'], LazadaAutoFetchListingHelper::PUTING_OFF);
            if ($targetListingObj == null)// 产品已经修改中
                continue;

            // $operation_log 以json数组形式记录操作
            // operation_log 为 text 最大 65535 的长度，所以当操作记录多的时候要注意，或者删除以前记录
            $newOp = array('edit_time' => time(), 'type' => 'put', 'value' => 'off');
            self::recordOperation($newOp, $targetListingObj["operationLog"], $targetListing["_id"]);
            // 填写修改信息
            $update = array();
            $update['Status'] = "inactive";
            $update['SellerSku'] = htmlentities($targetListing['sellerSku']);
            $updateProducts[$targetListing['lazadaUid']][] = $update;
            $prodIds[$targetListing['lazadaUid']][] = $targetListing['_id'];
        }

        $excuteNume = self::doProductUpdate($updateProducts, $prodIds);
        return array(true, "提交了$requestNum 个，成功执行$excuteNume 个。");
    }

    /**
     * @param $products
     * @param $nowTime
     * @param $oldOperationLog
     * @param $lazadaListingManager
     * @param $targetListing
     */
    private static function recordOperation($valArr, $oldOperationLog, $lazadaListingId)
    {
        $lazadaListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);

        $oldOperationLog[] = $valArr;

        $lazadaListingManager->findAndModify(array("_id" => $lazadaListingId), array('$set' => array("operationLog" => $oldOperationLog)));
    }

    /**
     * @param $updateProducts
     * @param $prodIds
     * @return int
     */
    private static function doProductUpdate($updateProducts, $prodIds)
    {
        $allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
        $excuteNume = 0;
        $lazadaListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
        foreach ($updateProducts as $lazadaUid => $sameAccountUpProds) {
            $tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
            $config = array(
                "userId" => $tempConfig["userId"],
                "apiKey" => $tempConfig["apiKey"],
                "countryCode" => $tempConfig["countryCode"],
            );
            $response = LazadaInterface_Helper::productUpdate($config, array('products' => $sameAccountUpProds));
            self::localLog("batchUpdatePrice productUpdate response:" . print_r($response, true));
            if ($response['success'] != true) {
                // 转换状态
                $lazadaListingManager->update(array("_id" => array('$in' => $prodIds[$lazadaUid])), array('$set' => array("isEditing" => 0, "errorMsg" => $response['message'])), array("multiple" => true));
                self::localLog("batchUpdatePrice productUpdate fail:" . $response['message'] . " puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazadaUid . " site:" . $tempConfig["countryCode"], LogType::ERROR);
            } else {
                $excuteNume += count($sameAccountUpProds);
                $feedId = $response['response']['body']['Head']['RequestId'];
                $lazadaListingManager->update(array("_id" => array('$in' => $prodIds[$lazadaUid])), array('$set' => array("feedId" => $feedId)), array("multiple" => true));
                $insertFeedResult = LazadaFeedHelper::insertFeed($tempConfig['puid'], $lazadaUid, $tempConfig["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_UPDATE);
                if ($insertFeedResult) {
                    self::localLog("batchUpdatePrice insertFeed " . LazadaFeedHelper::PRODUCT_UPDATE . " success. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazadaUid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId);
                } else {
                    self::localLog("batchUpdatePrice insertFeed " . LazadaFeedHelper::PRODUCT_UPDATE . " fail. puid:" . $tempConfig['puid'] . " lazada_uid:" . $lazadaUid . " site:" . $tempConfig["countryCode"] . " feedId" . $feedId, LogType::ERROR);
                }
            }
        }
        return $excuteNume;
    }

}

?>