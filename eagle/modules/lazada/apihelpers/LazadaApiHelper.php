<?php
namespace eagle\modules\lazada\apihelpers;

use \Yii;
use eagle\models\SaasLazadaAutosync;
use eagle\models\QueueLazadaGetorder;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasLazadaUser;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\LazadaCategoryAttr;
use eagle\models\LazadaCategories;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\LazadaFeedList;
use eagle\modules\listing\helpers\LazadaCallbackHelper;
use eagle\modules\listing\helpers\LazadaFeedHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\listing\models\LazadaListingV2;
use common\api\lazadainterface\LazadaInterface_Helper_V2;
use eagle\models\SaasLazadaAutosyncV2;
use eagle\models\QueueLazadaGetorderV2;
use common\helpers\Helper_Array;


class LazadaApiHelper
{

    private static $COUNTRYCODE_NAME_MAP = array('co.id' => '印尼', 'id' => '印尼', 'my' => '马来西亚', 'ph' => '菲律宾', 'sg' => '新加坡', 'co.th' => '泰国', 'th' => '泰国', 'vn' => '越南');// lazada站点
    private static $NEW_COUNTRYCODE_NAME_MAP = array('id' => '印尼', 'my' => '马来西亚', 'ph' => '菲律宾', 'sg' => '新加坡', 'th' => '泰国', 'vn' => '越南', 'cb' => '跨境站点');
    public static $LINIO_COUNTRYCODE_NAME_MAP = array('ar'=>'阿根廷',
        'cl' => '智利', 'co' => '哥伦比亚','ec'=>'厄瓜多尔','mx' => '墨西哥', 'pa' => '巴拿马', 'pe' => '秘鲁',/*'ve'=>'委内瑞拉'*/);// linio站点
    public static $JUMIA_COUNTRYCODE_NAME_MAP = array('dz' => '安哥拉', 'cm' => '喀麦隆', 'eg' => '埃及', 'gh' => '加纳', 
            'ci' => '科特迪瓦', 'ke' => '肯尼亚', 'ma' => '摩洛哥', 'ng' => '尼日利亚', 'sn' => '塞内加尔', 'tz' => '坦桑尼亚', 
            'ug' => '乌干达', 'za' => '南非');// jumia站点
    
    public static $COUNTRYCODE_COUNTRYCode2_MAP = array('co.id' => 'ID', 'my' => 'MY', 'ph' => 'PH', 'sg' => 'SG', 'co.th' => 'TH', 'vn' => 'VN', 'cb' => 'CB');
    public static $COUNTRYCODE_NAME_MAP_CARRIER = array('ID' => '印尼', 'MY' => '马来西亚', 'PH' => '菲律宾', 'SG' => '新加坡', 'TH' => '泰国', 'VN' => '越南');

    public static $LAZADA_LISTING_CATEGORY_SELECTED_HISTORY_PATH = "lazada/category/history";
    public static $LINIO_LISTING_CATEGORY_SELECTED_HISTORY_PATH = "linio/category/history";
    public static $JUMIA_LISTING_CATEGORY_SELECTED_HISTORY_PATH = "jumia/category/history";

    // 刊登产品的状态
    const PUBLISH_LISTING_STATE_DRAFT = "draft";
    const PUBLISH_LISTING_STATE_PRODUCT_UPLOAD = "product_upload";
    const PUBLISH_LISTING_STATE_PRODUCT_UPLOADED = "product_uploaded";
    /**
     * 记录子产品的发布状态,先发布父产品,再发布子产品
     * @var WAIT_PARENT_UPLOAD
     */
    const PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD = "wait_parent_upload";

    const PUBLISH_LISTING_STATE_IMAGE_UPLOAD = "image_upload";
    const PUBLISH_LISTING_STATE_IMAGE_UPLOADED = "image_uploaded";
    const PUBLISH_LISTING_STATE_COMPLETE = "complete";
    const PUBLISH_LISTING_STATE_FAIL = "fail";

    // 刊登产品的状态描述   请注意：添加status 在后面添加，前面的已经在使用
    public static $PUBLISH_LISTING_STATES_STATUS_MAP = array(
        self::PUBLISH_LISTING_STATE_DRAFT => array('draft', 'processing'),
        self::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD => array('prodCrtFeedInQueue'),
        self::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED => array('prodCrtSuccess', 'imageUpProcessing'),
        self::PUBLISH_LISTING_STATE_WAIT_PARENT_UPLOAD => array('ready', 'processing'),
        self::PUBLISH_LISTING_STATE_IMAGE_UPLOAD => array('imgUploadFeedInQueue'),
        self::PUBLISH_LISTING_STATE_IMAGE_UPLOADED => array('imgUploadSuccess'),
        self::PUBLISH_LISTING_STATE_COMPLETE => array('prodReadyToManualReview', 'ManualReviewSuccess'),
        self::PUBLISH_LISTING_STATE_FAIL => array('eagleCheck', 'prodCrtApiError', 'prodCrtFeedRtnError', 'eagleImgUploadCheck', 'imgUploadApiError', 'imgUploadFeedRtnError', 'prodOtherErrors', 'prodManualReviewFail','parentFail','prodQCErrors'),
    );
    // 刊登产品的状态描述 中文解释
    public static $PUBLISH_FAIL_STATUS_NAME_MAP = array(
        'eagleCheck' => '创建产品错误',
        'prodCrtApiError' => '创建产品错误',
        'prodCrtFeedRtnError' => '创建产品错误',
        'eagleImgUploadCheck' => '图片上传错误',
        'imgUploadApiError' => '图片上传错误',
        'imgUploadFeedRtnError' => '图片上传错误',
        'prodOtherErrors' => '产品其他信息问题，请到卖家平台了解详情',
        'prodQCErrors' => '产品质检问题',
        'prodManualReviewFail' => '人工审核失败'
    );
    
    // 客选物流
    public static $LINIO_BUYER_SHIPPING_SERVICES = array(
        "SFC Service"=>"SFC Service",
        "Mail Americas"=>"Mail Americas",
    	"Skypostal"=>"Skypostal",
    );
    
    
    // lazada 图片上传在UserBackgroundJobControll 里面的job name
    const IMAGE_UPLOAD_BGJ_NAME = "lazada_image_upload";
    protected static $active_users;

    // 20161222 标记发货时候，允许标记发货的订单item状态
    public static $CAN_SHIP_ORDERITEM_STATUS = array("pending","ready_to_ship","processing","shipped","delivered");
    
    // 20161222 同步到这些状态时，订单item会被标记为不可处理发货
    // 20170204 Own Warehouse 不是原始订单状态，是订单原始的ShippingType，为配合FBL不发货，将Own Warehouse 写入item的原始状态
    public static $CANNOT_SHIP_ORDERITEM_STATUS = array("Own Warehouse","return_waiting_for_approval","return_shipped_by_customer","return_rejected","returned","failed","canceled");
	
    /**
     * 更新lazada,linio,jumia物流服务
     * @param string $puid
     * @param string $platform
     * @param string $type all:更新平台全部账号 , one:更新单个站点账号
     * @param string $platform_userid
     * @param string $lazada_site
     * @return Ambigous <\eagle\modules\util\helpers\result, string, multitype:unknown >
     */
    public static function updateShipmentProviders($puid = "", $platform = "lazada", $type = "all", $platform_userid = "", $lazada_site = "")
    {
        $msg = [];
        if ("all" == $type) {
            if (empty($puid)) {
                $puid = \Yii::$app->user->identity->getParentUid();
            }
            $lazadaUsers = SaasLazadaUser::find()->where(['puid' => $puid, "platform" => $platform])->andWhere('status <> 3')->all();
        } else {
            $lazadaUsers = SaasLazadaUser::find()->where(['platform_userid' => $platform_userid, 'lazada_site' => strtolower($lazada_site)])->andWhere('status <> 3')->all();
        }

        if (empty($lazadaUsers))
            return ResultHelper::getResult(400, "", "指定订单账号$platform_userid ($lazada_site)不存在");

        foreach ($lazadaUsers as $slu) {
            $Timestamp = new \DateTime();
            $Timestamp->setTimezone(new \DateTimeZone('UTC'));
            $config = array(
                "userId" => $slu->platform_userid,
                "apiKey" => $slu->token,
                "countryCode" => $slu->lazada_site,
            );
            
            if(!empty($slu->version)){//新账号
                $config['apiKey'] = $slu->access_token;//新授权，用新的token
                $ret = LazadaInterface_Helper_V2::getShipmentProviders($config, []);
            }else{//旧帐号
                $ret = LazadaInterface_Helper::getShipmentProviders($config, []);
            }
            

            $shipments = array();
            if ($ret["success"] == false) {
                $msg[] = $slu->platform_userid . " " . $slu->lazada_site . " get shipment providers fail.";
                yii::info("updateShipmentProviders:" . $slu->platform_userid . " " . $slu->lazada_site . " get shipment providers fail.ret:" . json_encode($ret), "file");
                continue;
            } else {
                $shipments = $ret['response']['shipments'];
            }
            $allShipments = array();
            if (!empty($shipments)) {
                if(!empty($slu->version)){//新账号
                    foreach ($shipments as $shipment) {
                        $allShipments[] = $shipment['name'];
                    }
                }else{//旧帐号
                    foreach ($shipments as $shipment) {
                        $allShipments[] = $shipment['Name'];
                    }
                }
            }
            $slu->shipment_providers = json_encode($allShipments);
            if (!$slu->save()) {
                $msg[] = $slu->platform_userid . " " . $slu->lazada_site . " save shipment providers fail.";
            }
        }
        if (empty($msg)) {
            return ResultHelper::getSuccess();
        }
        return ResultHelper::getFailed($msg);

    }

    /**
     * 返回可选客选物流 for 物流匹配
     */
    public static function getBuyerShippingServices($platform = 'lazada')
    {
        if ($platform == 'linio')
            return self::$LINIO_BUYER_SHIPPING_SERVICES;
        
        if ($platform == 'jumia')
            return array();
        
        if ($platform == 'lazada')
            return array();
    }
    
    /**
     * 返回lazada 可选的物流方式
     * @return array(array(shipping_code,shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
     *
     * shipping_code就是 通知平台的那个运输方式对应值
     * shipping_value就是给卖家看到的可选择的运输方式名称
     */
    public static function getShippingCodeNameMap()
    {// @todo 待确认获取自 https://lazada.formstack.com/forms/change_tn Revised Shipping Provider*
        $puid = \Yii::$app->user->identity->getParentUid();
        $lazadaUsers = SaasLazadaUser::find()->where(['puid' => $puid, "platform" => "lazada"])->andWhere('status <> 3')->asArray()->all();
        $shippingCodeNameMap = array();
        foreach ($lazadaUsers as $lazadaUser) {
            if (!empty($lazadaUser['shipment_providers'])) {
                $shipmentProviders = json_decode($lazadaUser['shipment_providers'], true);
                foreach ($shipmentProviders as $shipment) {
                    if (!isset($shippingCodeNameMap[$shipment]))
                        $shippingCodeNameMap[$shipment] = $shipment;
                }
            }
        }
        if (!empty($shippingCodeNameMap))
            return $shippingCodeNameMap;
        else
            return array(
                "AS-4PX-Express" => "AS-4PX-Express",
                "AS-4PX-Postal-China Post" => "AS-4PX-Postal-China Post",
                "AS-4PX-Postal-Singpost" => "AS-4PX-Postal-Singpost",
                "AS-4PX-Singpost" => "AS-4PX-Singpost",
                "AS-aramex" => "AS-aramex",
                "AS-Australia Post-EMS" => "AS-Australia Post-EMS",
                "AS-China-EMS" => "AS-China-EMS",
                "AS-china-post" => "AS-china-post",
                "AS-City-Link Express (HK)" => "AS-City-Link Express (HK)",
                "AS-citylinkexpress" => "AS-citylinkexpress",
                "AS-CJ-Asia" => "AS-CJ-Asia",
                "AS-dhl" => "AS-dhl",
                "AS-dhl-global-mail-asia" => "AS-dhl-global-mail-asia",
                "AS-directlink" => "AS-directlink",
                "AS-DPEX" => "AS-DPEX",
                "AS-Ecargo-Economy" => "AS-Ecargo-Economy",
                "AS-Ecargo-Premium" => "AS-Ecargo-Premium",
                "AS-EC-Firstclass" => "AS-EC-Firstclass",
                "AS-fedex" => "AS-fedex",
                "AS-flytexpress - postal - China Post" => "AS-flytexpress - postal - China Post",
                "AS-flytexpress - postal - Sweden Post" => "AS-flytexpress - postal - Sweden Post",
                "AS-Gdex" => "AS-Gdex",
                "AS-hong-kong-post" => "AS-hong-kong-post",
                "AS-jne" => "AS-jne",
                "AS-Kerry-logistics" => "AS-Kerry-logistics",
                "AS-korea-EMS" => "AS-korea-EMS",
                "AS-Korea-Post" => "AS-Korea-Post",
                "AS-LBC-Economy" => "AS-LBC-Economy",
                "AS-LBC-Express" => "AS-LBC-Express",
                "AS-LBC-JZ-express premium-JZ2" => "AS-LBC-JZ-express premium-JZ2",
                "AS-LBC-JZ-express-JZ" => "AS-LBC-JZ-express-JZ",
                "AS-LBC-Omena" => "AS-LBC-Omena",
                "AS-LBC-Parcel-Green" => "AS-LBC-Parcel-Green",
                "AS-LWE-Express" => "AS-LWE-Express",
                "AS-LWE-Express-API SC" => "AS-LWE-Express-API SC",
                "AS-LWE-Postal-MY Post" => "AS-LWE-Postal-MY Post",
                "AS-LWE-Postal-MY Post-API SC" => "AS-LWE-Postal-MY Post-API SC",
                "AS-malaysia-post" => "AS-malaysia-post",
                "AS-Malaysia-Post-Posdaftar" => "AS-Malaysia-Post-Posdaftar",
                "AS-MY Post-Postal-Sunyou" => "AS-MY Post-Postal-Sunyou",
                "AS-MY Post-Postal-ZHY" => "AS-MY Post-Postal-ZHY",
                "AS-mypostonline" => "AS-mypostonline",
                "AS-poczta-polska" => "AS-poczta-polska",
                "AS-postnl-international" => "AS-postnl-international",
                "AS-qsm" => "AS-qsm",
                "AS-Quantium-Solutions" => "AS-Quantium-Solutions",
                "AS-qxpress" => "AS-qxpress",
                "AS-Rong-Shindler-Express" => "AS-Rong-Shindler-Express",
                "AS-royal-mail" => "AS-royal-mail",
                "AS-SF-Express" => "AS-SF-Express",
                "AS-Singapore-Post" => "AS-Singapore-Post",
                "AS-singapore-speedpost" => "AS-singapore-speedpost",
                "AS-skynet" => "AS-skynet",
                "AS-SRE" => "AS-SRE",
                "AS-Sweden-Posten" => "AS-Sweden-Posten",
                "AS-Swiss-Post" => "AS-Swiss-Post",
                "AS-szdpex" => "AS-szdpex",
                "AS-Taiwan-Post" => "AS-Taiwan-Post",
                "AS-Ta-Q-Bin" => "AS-Ta-Q-Bin",
                "AS-taqbin-my" => "AS-taqbin-my",
                "AS-Taqbin-SG" => "AS-Taqbin-SG",
                "AS-tnt" => "AS-tnt",
                "AS-toll-global-express" => "AS-toll-global-express",
                "AS-UPS" => "AS-UPS",
                "AS-Xend" => "AS-Xend",
                "AS-Yanwen-Postal" => "AS-Yanwen-Postal",
                "AS-YSE" => "AS-YSE",
                "AS-flyexpress - express-TOLL" => "AS-flyexpress - express-TOLL",
                "AS-flytexpress - express-TNT" => "AS-flytexpress - express-TNT",
                "AS-flytexpress - express-DHL" => "AS-flytexpress - express-DHL",
            );
    }

    /**
     * 返回linio 可选的物流方式
     * @return array(array(shipping_code,shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
     *
     * shipping_code就是 通知平台的那个运输方式对应值
     * shipping_value就是给卖家看到的可选择的运输方式名称
     */
    public static function getLinioShippingCodeNameMap()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        $lazadaUsers = SaasLazadaUser::find()->where(['puid' => $puid, "platform" => "linio"])->andWhere('status <> 3')->asArray()->all();
        $shippingCodeNameMap = array();
        foreach ($lazadaUsers as $lazadaUser) {
            if (!empty($lazadaUser['shipment_providers'])) {
                $shipmentProviders = json_decode($lazadaUser['shipment_providers'], true);
                foreach ($shipmentProviders as $shipment) {
                    if (!isset($shippingCodeNameMap[$shipment]))
                        $shippingCodeNameMap[$shipment] = $shipment;
                }
            }
        }

        return $shippingCodeNameMap;
    }

    /**
     * 返回jumia 可选的物流方式
     * @return array(array(shipping_code,shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
     *
     * shipping_code就是 通知平台的那个运输方式对应值
     * shipping_value就是给卖家看到的可选择的运输方式名称
     */
    public static function getJumiaShippingCodeNameMap()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        $lazadaUsers = SaasLazadaUser::find()->where(['puid' => $puid, "platform" => "jumia"])->andWhere('status <> 3')->asArray()->all();
        $shippingCodeNameMap = array();
        foreach ($lazadaUsers as $lazadaUser) {
            if (!empty($lazadaUser['shipment_providers'])) {
                $shipmentProviders = json_decode($lazadaUser['shipment_providers'], true);
                foreach ($shipmentProviders as $shipment) {
                    if (!isset($shippingCodeNameMap[$shipment]))
                        $shippingCodeNameMap[$shipment] = $shipment;
                }
            }
        }

        return $shippingCodeNameMap;
    }

    /**
     * 返回lazada默认的物流方式shipping_code
     * shipping_code就是 通知平台的那个运输方式对应值
     *
     */
    public static function getDefaultShippingCode()
    {
        return "Other";
    }

    /**
     * 返回lazada 所有站点CountryCode和站点中文的mapping
     *
     */
    public static function getLazadaCountryCodeSiteMapping($platform = 'lazada')
    {
        if ($platform == 'linio')
            return self::$LINIO_COUNTRYCODE_NAME_MAP;

        if ($platform == 'jumia')
            return self::$JUMIA_COUNTRYCODE_NAME_MAP;

        if ($platform == 'lazada')
            return self::$NEW_COUNTRYCODE_NAME_MAP;

    }

    /**
     * 返回lazada 所有站点CountryCode和站点中文的mapping
     *
     */
    public static function getSelectedCategoryHistoryPath($platform = 'lazada')
    {
        if ($platform == 'linio')
            return self::$LINIO_LISTING_CATEGORY_SELECTED_HISTORY_PATH;

        if ($platform == 'jumia')
            return self::$JUMIA_LISTING_CATEGORY_SELECTED_HISTORY_PATH;

        if ($platform == 'lazada')
            return self::$LAZADA_LISTING_CATEGORY_SELECTED_HISTORY_PATH;

    }

    /**
     * 启动/停用Lazada后台任务
     * @author dzt 2015-08-18
     */
    static function SwitchLazadaCronjob($status, $lazada_uid)
    {
        try {
            //如果用户设置账号不启用,则关闭lazada的相关同步订单功能 , 开关同步功能不修改时间 ， update time 除外
            if (1 == $status) {
                $asyncAffectRows = SaasLazadaAutosync::updateAll(array('is_active' => $status, 'status' => 0, 'error_times' => 0, 'update_time' => time()), 'lazada_uid=:lazada_uid ', array(':lazada_uid' => $lazada_uid));
                yii::info("SaasDhgateAutosync::updateAll $status,$lazada_uid .affect rows:" . $asyncAffectRows, "file");

                $queueAffectRows = QueueLazadaGetorder::updateAll(['is_active' => $status, 'status' => 0, 'error_times' => 0, 'update_time' => time()], ['lazada_uid' => $lazada_uid]);
                yii::info("QueueDhgateGetorder::updateAll is_active:$status,lazada_uid:$lazada_uid .affect rows:" . $queueAffectRows, "file");

            } else {
                $asyncAffectRows = SaasLazadaAutosync::updateAll(array('is_active' => $status, 'update_time' => time()), 'lazada_uid=:p', array(':p' => $lazada_uid));
                yii::info("SaasDhgateAutosync::updateAll $status,$lazada_uid .affect rows:" . $asyncAffectRows, "file");

                $queueAffectRows = QueueLazadaGetorder::updateAll(['is_active' => $status, 'update_time' => time()], ['lazada_uid' => $lazada_uid]);
                yii::info("QueueDhgateGetorder::updateAll $status,$lazada_uid .affect rows:" . $queueAffectRows, "file");
            }
        } catch (\Exception $ex) {
            yii::info("启动/停用 lazada后台任务 Exception:" . print_r($ex, true), "file");
            return array("success" => false, "message" => $ex->getMessage());
        }

        return array("success" => true, "message" => '');

    }
    
    /**
     * 新授权 启动/停用Lazada后台任务
     * @author lwj 2017-4-17
     */
    static function SwitchLazadaCronjobV2($status, $lazada_uid)
    {
        try {
            //如果用户设置账号不启用,则关闭lazada的相关同步订单功能 , 开关同步功能不修改时间 ， update time 除外
            if (1 == $status) {
                $asyncAffectRows = SaasLazadaAutosyncV2::updateAll(array('is_active' => $status, 'status' => 0, 'error_times' => 0, 'update_time' => time()), 'lazada_uid=:lazada_uid ', array(':lazada_uid' => $lazada_uid));
                yii::info("SaasLazadaAutosyncV2::updateAll $status,$lazada_uid .affect rows:" . $asyncAffectRows, "file");
    
                $queueAffectRows = QueueLazadaGetorderV2::updateAll(['is_active' => $status, 'status' => 0, 'error_times' => 0, 'update_time' => time()], ['lazada_uid' => $lazada_uid]);
                yii::info("QueueLazadaGetorderV2::updateAll is_active:$status,lazada_uid:$lazada_uid .affect rows:" . $queueAffectRows, "file");
    
            } else {
                $asyncAffectRows = SaasLazadaAutosyncV2::updateAll(array('is_active' => $status, 'update_time' => time()), 'lazada_uid=:p', array(':p' => $lazada_uid));
                yii::info("SaasLazadaAutosyncV2::updateAll $status,$lazada_uid .affect rows:" . $asyncAffectRows, "file");
    
                $queueAffectRows = QueueLazadaGetorderV2::updateAll(['is_active' => $status, 'update_time' => time()], ['lazada_uid' => $lazada_uid]);
                yii::info("QueueLazadaGetorderV2::updateAll $status,$lazada_uid .affect rows:" . $queueAffectRows, "file");
            }
        } catch (\Exception $ex) {
            yii::info("启动/停用 lazada后台任务 V2 Exception:" . print_r($ex, true), "file");
            return array("success" => false, "message" => $ex->getMessage());
        }
    
        return array("success" => true, "message" => '');
    
    }

    /**
     * 绑定Lazada账号
     * @author dzt 2015-08-20
     */
    public static function createLazadaAccount($params)
    {
    	if ('lazada' == $params['platform'] && empty($params["store_name"])) {
    		return array(false, TranslateHelper::t("请输入店铺名"));
    	}
    	
        if (empty($params["platform_userid"]) or empty($params["token"])) {
            return array(false, TranslateHelper::t("API账号邮箱和token都不能为空"));
        }

        if (empty($params["lazada_site"])) {
            return array(false, TranslateHelper::t("请选择站点"));
        }

        $puid = \Yii::$app->user->identity->getParentUid();
        
        
        // 1.检查Lazada信息的合法性(platform_userid：客户email 在某个站点下是否被占用)
        $filteData = SaasLazadaUser::find()->where(array('platform_userid' => $params['platform_userid'], 'lazada_site' => $params['lazada_site']))->andWhere('status<>3')->one();
        if ($filteData !== null) {
            if ('linio' == $params['platform']) {
                return array(false, TranslateHelper::t("站点:" . self::$LINIO_COUNTRYCODE_NAME_MAP[$params['lazada_site']] . " 的API账号邮箱已存在，不能重复绑定账号到同一个站点!"));
            } else if ('jumia' == $params['platform']) {
                return array(false, TranslateHelper::t("站点:" . self::$JUMIA_COUNTRYCODE_NAME_MAP[$params['lazada_site']] . " 的API账号邮箱已存在，不能重复绑定账号到同一个站点!"));
            } else {
                return array(false, TranslateHelper::t("站点:" . self::$COUNTRYCODE_NAME_MAP[$params['lazada_site']] . " 的API账号邮箱已存在，不能重复绑定账号到同一个站点!"));
            }
        }

        // 2. 检查lazada_site 是否能连接
        $config = array(
            'userId' => $params["platform_userid"],
            'apiKey' => $params["token"],
            'countryCode' => $params["lazada_site"]
        );
        list($isConnected, $shipments) = self::testLazadaAccount($config);
        if (!$isConnected) {
            if ('linio' == $params['platform']) {
                return array(false, TranslateHelper::t("Linio api连接测试失败。" . self::$LINIO_COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
            } else if ('jumia' == $params['platform']) {
                return array(false, TranslateHelper::t("Jumia api连接测试失败。" . self::$JUMIA_COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
            } else {
                return array(false, TranslateHelper::t("Lazada api连接测试失败。" . self::$COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
            }
        }

        // 3. 保存Lazada信息到db
        $saasId = \Yii::$app->user->identity->getParentUid();
        if ($saasId == 0) {
            //用户没登陆导致????
            return array(false, "请退出小老板并重新登录，再进行绑定!");
        }

        $user = SaasLazadaUser::find()->where(array('platform_userid' => $params['platform_userid'], 'lazada_site' => $params['lazada_site']))->one();
        if (empty($user)) {
            $user = new SaasLazadaUser();
            $user->create_time = time();
            $user->platform_userid = $params['platform_userid'];
        }

        $nowTime = time();
        $user->store_name = $params['store_name'];
        $user->token = $params['token'];
        $user->lazada_site = $params['lazada_site'];
        $user->status = $params['status'];
        $user->puid = $puid;
        $user->update_time = $nowTime;
        $user->platform = $params['platform'];

        // 添加运输方式
        $allShipments = array();
        if (!empty($shipments)) {
            foreach ($shipments as $shipment) {
                $allShipments[] = $shipment['Name'];
            }
        }
        $user->shipment_providers = json_encode($allShipments);
        
        //linio : if oms_use_product_image?
        $addi_info = empty($user->addi_info)?[]:json_decode($user->addi_info,true);
        if(empty($addi_info)) $addi_info=[];
        if(empty($params['oms_use_product_image']))
        	$addi_info['oms_use_product_image'] = false;
        else
        	$addi_info['oms_use_product_image'] = true;
        $user->addi_info = json_encode($addi_info);
        
        if ($user->save()) {

            // 绑定成功写入autosync表 添加同步订单job  同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单,4----获取listing。
            $types = array(1, 2, 3, 4, 5);// dzt20151225 lazada刊登上线，3个平台同时加type 4 // dzt20160503 type 5 漏单查找
            foreach ($types as $type) {
                $SLA_obj = SaasLazadaAutosync::find()->where('lazada_uid=:lazada_uid and type=:type', array(':lazada_uid' => $user->lazada_uid, ':type' => $type))->one();
                if (isset($SLA_obj)) {//已经有数据，只要更新
                    $SLA_obj->puid = $puid;// dzt20160308 重新绑定puid可能换了，这里要重新修改。 puid 2501 改绑lazada_uid 125,126到2501,但这里没有修改puid
                    $SLA_obj->is_active = $user->status;
                    $SLA_obj->status = 0;
                    $SLA_obj->error_times = 0;
                    $SLA_obj->update_time = $nowTime;
                    $SLA_obj->last_binding_time = $nowTime;
                    $SLA_obj->save();
                } else {//新数据，插入一行数据
                    $SLA_obj = new SaasLazadaAutosync();
                    $SLA_obj->puid = $puid;
                    $SLA_obj->lazada_uid = $user->lazada_uid;
                    $SLA_obj->platform = $user->platform;
                    $SLA_obj->site = $user->lazada_site;
                    $SLA_obj->is_active = $user->status;// 是否启用
                    $SLA_obj->status = 0; // 同步状态
                    $SLA_obj->type = $type;// 同步job类型
                    $SLA_obj->error_times = 0;
                    $SLA_obj->start_time = 0;// 同步时间段开始时间
                    $SLA_obj->end_time = 0;// 同步时间段结束时间
                    $SLA_obj->last_finish_time = 0;

                    if ($type <> 1) $SLA_obj->next_execution_time = $nowTime + 1800;
                    else $SLA_obj->next_execution_time = 0;

                    $SLA_obj->message = '';
                    $SLA_obj->binding_time = $nowTime;
                    $SLA_obj->last_binding_time = $nowTime;//最近一次账号的绑定时间,暂时是memo作用
                    $SLA_obj->create_time = $nowTime;
                    $SLA_obj->update_time = $nowTime;
                    if (!$SLA_obj->save()) yii::error("lazada autosync create error:" . print_r($SLA_obj->getErrors(), true), "file");
                }
            }

            // 开启/关闭 已有同步job
            $queueAffectRows = QueueLazadaGetorder::updateAll(['is_active' => $user->status, 'update_time' => $nowTime], ['lazada_uid' => $user->lazada_uid]);
            yii::info("QueueLazadaGetorder::updateAll " . $user->status . "," . $user->lazada_uid . " .affect rows:" . $queueAffectRows, "file");

            if ($user->platform == "lazada"){
                $countryCode2 = LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$user->lazada_site];
            }else{
                $countryCode2 = strtoupper($user->lazada_site);
            }
            
            //绑定成功调用
            PlatformAccountApi::callbackAfterRegisterAccount($params['platform'], $puid, ['selleruserid'=>$user->platform_userid, 'order_source_site_id'=>$countryCode2]);
            return array(true, "");
        } else {
            $message = '';
            foreach ($user->errors as $k => $anError) {
                $message .= "Update Lazada user " . ($message == "" ? "" : "<br>") . $k . " error:" . $anError[0];
            }
            return array(false, $message);
        }
    }

    /**
     * 修改Lazada账号
     * @author dzt 2015-08-20
     */
    static function updateLazadaAccount($params)
    {
        $User_obj = SaasLazadaUser::find()->where(array('lazada_uid' => $params["lazada_uid"]))->andWhere('status<>3')->one();
        if ($User_obj == null) return array(false, TranslateHelper::t("该账户不存在"));

        if ($params['platform'] != 'lazada' && empty($params["token"])) {
            return array(false, TranslateHelper::t("token都不能为空"));
        }
        
        if ('lazada' == $params['platform'] && empty($params["store_name"])) {
        	return array(false, TranslateHelper::t("请输入店铺名"));
        }

        // 检查lazada_site 是否能连接
        $shipments = array();
        if(strtolower($User_obj->platform) != 'lazada'){// lazada目前不能手动修改token 不用测试这个了 
            $config = array(
                'userId' => $User_obj->platform_userid,
                'apiKey' => $params["token"],
                'countryCode' => $User_obj->lazada_site,
            );
            list($isConnected, $shipments) = self::testLazadaAccount($config);
            if (!$isConnected) {
                if ('linio' == $params['platform']) {
	                return array(false, TranslateHelper::t("Linio api连接测试失败。" . self::$LINIO_COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
                } else if ('jumia' == $params['platform']) {
	                return array(false, TranslateHelper::t("Jumia api连接测试失败。" . self::$JUMIA_COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
                } else {
	                return array(false, TranslateHelper::t("Lazada api连接测试失败。" . self::$COUNTRYCODE_NAME_MAP[$params["lazada_site"]] . "站连接不上，请检查输入api信息!"));
                }
            }
        }
        

        // 保存Lazada账号的变化信息
        if('lazada' != $params['platform']){
            $User_obj->token = $params["token"];
            $User_obj->status = $params["status"];
        }
        
        $User_obj->store_name = $params['store_name'];
        $User_obj->update_time = time();

        // 更新运输方式
        if (!empty($shipments)) {
            $allShipments = array();
            foreach ($shipments as $shipment) {
                $allShipments[] = $shipment['Name'];
            }
            $User_obj->shipment_providers = json_encode($allShipments);
        } else {
            $existingShipments = json_decode($User_obj->shipment_providers, true);
            $addShipments = array();
            foreach ($shipments as $shipment) {
                if (!in_array($shipment['Name'], $existingShipments)) {
                    $addShipments[] = $shipment['Name'];
                }
            }
            if (!empty($addShipments))
                $User_obj->shipment_providers = json_encode(array_merge($existingShipments, $shipment));
        }
		
        //linio : if oms_use_product_image?
        $addi_info = empty($User_obj->addi_info)?[]:json_decode($User_obj->addi_info,true);
        if(empty($addi_info)) $addi_info=[];
        if(empty($params['oms_use_product_image']))
        	$addi_info['oms_use_product_image'] = false;
        else
        	$addi_info['oms_use_product_image'] = true;
        $User_obj->addi_info = json_encode($addi_info);
        
        if (!$User_obj->save()) {
            $message = '';
            foreach ($User_obj->errors as $k => $anError) {
                $message .= "Update Lazada user " . ($message == "" ? "" : "<br>") . $k . " error:" . $anError[0];
            }
            return array(false, $message);
        }
        
        $rtn = PlatformAccountApi::resetSyncSetting($params['platform'], $User_obj->lazada_uid, $User_obj->status, $User_obj->puid);
        return array($rtn['success'], $rtn['message']);
    }
    
    /**
     * 通过获取 客户的运输方式 来测试用户想要绑定的lazada的api信息是否ok
     * 顺便把运输方式保存，以供以后标记发货使用。
     * @param $config =array('userId'=>??,'apiKey'=>??,'countryCode'=>??);
     * @return true or false
     **/
    public static function testLazadaAccount($config)
    {
        $Timestamp = new \DateTime();
        $Timestamp->setTimezone(new \DateTimeZone('UTC'));
        $apiParams = array(
            "UpdatedAfter" => $Timestamp->format(\DateTime::ISO8601),
        );

        $ret = LazadaInterface_Helper::getShipmentProviders($config, $apiParams);

        if ($ret["success"] == false) {
            return array($ret["success"], array());
        } else {
            return array($ret["success"], $ret['response']['shipments']);
        }
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * lazada linio jumia 订单同步情况 数据
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param $account_key                各个平台账号表主键（必需） 就是用获取对应账号信息的
     * @param $uid                        uid use_base 的id
     * @param $platform                   主要针对新的lazada接口
     * +---------------------------------------------------------------------------------------------
     * @return array (  'result'=> array(is_active 是否启用 , last_time上次同步时间,message 信息 ,status 同步执行状态) 同步表的最新数据
     *    //其中同步执行状态 为以下值'0'=>'等待同步','1'=>'已经有同步队列为他同步中','2'=>'同步成功','3'=>'同步失败','4'=>'同步完成',
     *                    'message'=>执行详细结果
     *                    'success'=> true 成功 false 失败    )
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt        2015/12/02                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    public static function getLastOrderSyncDetail($account_key, $uid = 0,$platform)
    {
        //get active uid
        if (empty($uid)) {
            $userInfo = \Yii::$app->user->identity;
            if ($userInfo['puid'] == 0) {
                $uid = $userInfo['uid'];
            } else {
                $uid = $userInfo['puid'];
            }
        }

        //同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
        // 当时因为 拉取新订单和 更新订单 使用lazada不同的字段过滤，所以分开了两个type
        // 这里2和3都是 即时更新订单的相关Job
        if($platform == 'lazada'){
            $ret = SaasLazadaUser::find()->where(['lazada_uid' => $account_key])->asArray()->one();
            if(!empty($ret['version'])){//新授权
                $newOrder = SaasLazadaAutosyncV2::find()->where(['lazada_uid' => $account_key, 'type' => 2])->asArray()->one();
                $updateOrder = SaasLazadaAutosyncV2::find()->where(['lazada_uid' => $account_key, 'type' => 3])->asArray()->one();
            }else{
                $newOrder = SaasLazadaAutosync::find()->where(['lazada_uid' => $account_key, 'type' => 2])->asArray()->one();
                $updateOrder = SaasLazadaAutosync::find()->where(['lazada_uid' => $account_key, 'type' => 3])->asArray()->one();
            }
        }else{
            $newOrder = SaasLazadaAutosync::find()->where(['lazada_uid' => $account_key, 'type' => 2])->asArray()->one();
            $updateOrder = SaasLazadaAutosync::find()->where(['lazada_uid' => $account_key, 'type' => 3])->asArray()->one();
        }
        
        $SLAutosync = array();
        if ($newOrder['status'] == 3 && $updateOrder['status'] == 3 && $newOrder['error_times'] >= 10) {// 取错误状态的
            $SLAutosync = $newOrder;
        } else if ($newOrder['status'] != 3 && $updateOrder['status'] == 3 && $updateOrder['error_times'] >= 10) {
            $SLAutosync = $updateOrder;
        } else {
            if ($newOrder['end_time'] >= $updateOrder['end_time']) {// 取时间最新的
                $SLAutosync = $newOrder;
            } else {
                $SLAutosync = $updateOrder;
            }
        }

        if (empty($SLAutosync)) {
            return ['success' => false, 'message' => '没有同步信息', 'result' => []];
        } else {
            $result['is_active'] = $SLAutosync['is_active'];
            $result['last_time'] = $SLAutosync['end_time'];
            $result['next_time'] = $SLAutosync['next_execution_time'];// dzt20161123 自己加的next_time 不用再笼统的算半小时后
            $result['message'] = $SLAutosync['message'];
            $result['status'] = $SLAutosync['status'];// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题  刚好大致一样
            return ['success' => true, 'message' => '', 'result' => $result];
        }
    }// end of getLastOrderSyncDetail
    
    // 防止部分客户太多帐号绑定超时
    public static function getLastOrderSyncDetailV2($uid = 0,$platform)
    {
        //get active uid
        if (empty($uid)) {
            $userInfo = \Yii::$app->user->identity;
            if ($userInfo['puid'] == 0) {
                $uid = $userInfo['uid'];
            } else {
                $uid = $userInfo['puid'];
            }
        }
    
        //同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
        // 当时因为 拉取新订单和 更新订单 使用lazada不同的字段过滤，所以分开了两个type
        // 这里2和3都是 即时更新订单的相关Job
//         $newOrder = [];
//         $updateOrder = [];
//         $lazadaUser = SaasLazadaUser::find()->where(['puid' => $uid,'platform'=>$platform])->asArray()->all();
//         if(!empty($lazadaUser)){
//             foreach ($lazadaUser as $ret){
//                 if(!empty($ret['version'])){//新授权
//                     $newOrder[] = SaasLazadaAutosyncV2::find()->where(['lazada_uid' => $ret['lazada_uid'], 'type' => 2])->asArray()->one();
//                     $updateOrder[] = SaasLazadaAutosyncV2::find()->where(['lazada_uid' => $ret['lazada_uid'], 'type' => 3])->asArray()->one();
//                 }else{
//                     $newOrder[] = SaasLazadaAutosync::find()->where(['lazada_uid' => $ret['lazada_uid'], 'type' => 2])->asArray()->one();
//                     $updateOrder[] = SaasLazadaAutosync::find()->where(['lazada_uid' => $ret['lazada_uid'], 'type' => 3])->asArray()->one();
//                 }
//             }
//         }
        $newOrder1 = SaasLazadaAutosync::find()->where(['puid' => $uid, 'type' => 2 ,'is_active'=>1] )->orderBy("lazada_uid, type")->asArray()->all();
        $updateOrder1 = SaasLazadaAutosync::find()->where(['puid' => $uid, 'type' => 3 ,'is_active'=>1])->orderBy("lazada_uid, type")->asArray()->all();
        
        $newOrder2 = SaasLazadaAutosyncV2::find()->where(['puid' => $uid, 'type' => 2 ,'is_active'=>1])->orderBy("lazada_uid, type")->asArray()->all();
        $updateOrder2 = SaasLazadaAutosyncV2::find()->where(['puid' => $uid, 'type' => 3 ,'is_active'=>1])->orderBy("lazada_uid, type")->asArray()->all();
        
        $newOrder = array_merge($newOrder1,$newOrder2);
        $updateOrder = array_merge($updateOrder1,$updateOrder2);
    
        $SLAutosync = array();
        foreach ($newOrder as $key => $val){
            if ($newOrder[$key]['status'] == 3 && $updateOrder[$key]['status'] == 3 && $newOrder[$key]['error_times'] >= 10) {// 取错误状态的
                $SLAutosync[] = $newOrder[$key];
            } else if ($newOrder[$key]['status'] != 3 && $updateOrder[$key]['status'] == 3 && $updateOrder[$key]['error_times'] >= 10) {
                $SLAutosync[] = $updateOrder[$key];
            } else {
                if ($newOrder[$key]['end_time'] >= $updateOrder[$key]['end_time']) {// 取时间最新的
                    $SLAutosync[] = $newOrder[$key];
                } else {
                    $SLAutosync[] = $updateOrder[$key];
                }
            } 
        }
        
    
        if (empty($SLAutosync)) {
            return ['success' => false, 'message' => '没有同步信息', 'result' => []];
        } else {
            $allResult = [];//汇总所有的数据
            foreach ($SLAutosync as $val){
                $result = [];
                $result['is_active'] = $val['is_active'];
                $result['last_time'] = $val['end_time'];
                $result['next_time'] = $val['next_execution_time'];// dzt20161123 自己加的next_time 不用再笼统的算半小时后
                $result['message'] = $val['message'];
                $result['status'] = $val['status'];// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题  刚好大致一样
                $allResult[$val['lazada_uid']] = $result;
            }
            return ['success' => true, 'message' => '', 'result' => $allResult];
        }
    }

    /**
     * 重新组织通过proxy返回的目录树
     * @param array $category 传入的目录树结构
     * @param int $level 第几层目录
     * @param int $parentId 父目录id
     * @param string $site 站点
     * @param array $rtnCategory 返回的目录数组，一条记录一个目录
     */
    public static function getCategoryInfo($category, $level, $parentId, $site, &$rtnCategory)
    {
        if (!empty($category['Children'])) {
            $isLeaf = false;
        } else {
            $isLeaf = true;
        }

        $rtnCategory[$category['CategoryId']] = array('categoryId' => $category['CategoryId'], 'categoryName' => $category['Name'], 'isLeaf' => $isLeaf, 'level' => $level, 'parentCategoryId' => $parentId, 'site' => $site);

        if (!$isLeaf) {
            $level++;
            // lazada 只有一个结果时候结构不同。。
            if (isset($category['Children']['Category']['CategoryId'])) {
                $category['Children']['Category'] = array($category['Children']['Category']);
            }
            foreach ($category['Children']['Category'] as $child) {
                self::getCategoryInfo($child, $level, $category['CategoryId'], $site, $rtnCategory);
            }
        }
    }
    
    /**
     * 重新组织通过proxy返回的目录树
     * @param array $category 传入的目录树结构
     * @param int $level 第几层目录
     * @param int $parentId 父目录id
     * @param string $site 站点
     * @param array $rtnCategory 返回的目录数组，一条记录一个目录
     */
    public static function getCategoryInfoV3($category, $level, $parentId, $site, &$rtnCategory)
    {
        if (!empty($category['children'])) {
            $isLeaf = false;
        } else {
            $isLeaf = true;
        }
    
        $rtnCategory[$category['categoryId']] = array('categoryId' => $category['categoryId'], 'categoryName' => $category['name'], 'isLeaf' => $isLeaf, 'level' => $level, 'parentCategoryId' => $parentId, 'site' => $site);
    
        if (!$isLeaf) {
            $level++;
            foreach ($category['children'] as $child) {
                self::getCategoryInfoV3($child, $level, $category['categoryId'], $site, $rtnCategory);
            }
        }
    }
    
    /**
     * 新API接口
     * 重新组织通过proxy返回的目录树
     * @param array $category 传入的目录树结构
     * @param int $level 第几层目录
     * @param int $parentId 父目录id
     * @param string $site 站点
     * @param array $rtnCategory 返回的目录数组，一条记录一个目录
     */
    public static function getCategoryInfoV4($category, $level, $parentId, $site, &$rtnCategory)
    {
        if (!empty($category['children'])) {
            $isLeaf = false;
        } else {
            $isLeaf = true;
        }
    
        $rtnCategory[$category['category_id']] = array('categoryId' => $category['category_id'], 'categoryName' => $category['name'], 'isLeaf' => $isLeaf, 'level' => $level, 'parentCategoryId' => $parentId, 'site' => $site);
    
        if (!$isLeaf) {
            $level++;
            foreach ($category['children'] as $child) {
                self::getCategoryInfoV4($child, $level, $category['category_id'], $site, $rtnCategory);
            }
        }
    }

    /**
     * 通过primaryCategory id 来获取所有 category 的id
     *
     * @param int $primaryCategory
     * @param int $lazada_uid
     */
    public static function getAllCatIdsByPrimaryCategory($primaryCategory = null, $lazada_uid = null)
    {
        if (!empty($primaryCategory) && !empty($lazada_uid)) {
            $lazadaUser = SaasLazadaUser::find()->where(['lazada_uid' => $lazada_uid])->one();
            if (!empty($lazadaUser)) {
                if(!empty($lazadaUser->version)){//新授权
                    $config = array(
                        "userId" => $lazadaUser->platform_userid,
                        "apiKey" => $lazadaUser->access_token,
                        "countryCode" => $lazadaUser->lazada_site
                    );
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryTree($config);
                }else{
                    $config = array(
                        "userId" => $lazadaUser->platform_userid,
                        "apiKey" => $lazadaUser->token,
                        "countryCode" => $lazadaUser->lazada_site
                    );
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
                }
                if ($ret == true) {
                    $categoryIds = array();
                    self::getAllCatIdsByPriCatIdCategory($primaryCategory, $categories, $categoryIds);
                    return array(true, $categoryIds);
                } else {
                    yii::error("getAllCatIdsByPrimaryCategory 获取目录树失败:" . $categories, "file");
                }
            } else {
                yii::error("getAllCatIdsByPrimaryCategory lazada账号lazada_uid:" . $lazada_uid . "不存在", "file");
            }
        }

        return array(false, "");
    }

    /**
     * 通过Category id和目录数组获取 所有父 category 的id
     *
     * @param int $catId
     * @param array $categories 从数据库获取的目录数组
     * @param array $rtnCategoryIds 返回 category 的id结果
     */
    public static function getAllCatIdsByPriCatIdCategory($catId, $categories, &$rtnCategoryIds)
    {
        if (!in_array($catId, $rtnCategoryIds)) {
            $rtnCategoryIds[] = $catId;
        }

        if (isset($categories[$catId])) {// $categories 数组带$catId index时 
            if (!in_array($categories[$catId]['parentCategoryId'], $rtnCategoryIds) && $categories[$catId]['parentCategoryId'] != 0)
                self::getAllCatIdsByPriCatIdCategory($categories[$catId]['parentCategoryId'], $categories, $rtnCategoryIds);
        } else {
            foreach ($categories as $category) {
                if ($category['parentCategoryId'] == $catId && $category['parentCategoryId'] != 0) {
                    self::getAllCatIdsByPriCatIdCategory($category['parentCategoryId'], $categories, $rtnCategoryIds);
                }
            }
        }
    }

    // lazada 刊登左侧目录
    public static function getLeftMenuArr($platform = 'lazada')
    {
        $guidenceUrl = "";
        if ('lazada' == $platform) {
//             $guidenceUrl = 'http://www.littleboss.com/announce_info_30.html';
            // $guidenceUrl = 'http://www.littleboss.com/word_list_182_183.html';
            $guidenceUrl =  \eagle\modules\util\helpers\SysBaseInfoHelper::getHelpdocumentUrl("word_list_182_183.html");
            
            
        } else if ('linio' == $platform) {
//             $guidenceUrl = 'http://www.littleboss.com/announce_info_31.html';
//             $guidenceUrl = 'http://www.littleboss.com/word_list_178_179.html';
            $guidenceUrl =  \eagle\modules\util\helpers\SysBaseInfoHelper::getHelpdocumentUrl("word_list_178_179.html");
        }

        $menu = [
            '刊登管理' => [
                'icon' => 'icon-shezhi',
                'items' => [
                    '待发布' => [
                        'url' => '/listing/' . $platform . '-listing/publish',
                    ],
                    '发布中' => [
                        'url' => '/listing/' . $platform . '-listing/publishing',
                    ],
                    '发布失败' => [
                        'url' => '/listing/' . $platform . '-listing/publish-fail',
                    ],
                    '发布成功'=>[
                        'url' => '/listing/' . $platform . '-listing/publish-success',
                    ],
                ]
            ],
            '商品列表' => [
                'icon' => 'icon-liebiao',
                'items' => [
                    '在线商品' => [
                        'url' => '/listing/' . $platform . '/online-product'
                    ],
                    '下架商品' => [
                        'url' => '/listing/' . $platform . '/off-shelf-product',
                    ]
                ],
            ],
        ];
        
        if('lazada' == $platform){
            unset($menu['商品列表']['items']['下架商品']);
        }else{
//             unset($menu['刊登管理']['items']['发布成功']);
        }

        if('jumia' == $platform){
            $items = ['导入任务管理'=>['url'=>'/listing/' . $platform . '/import-listing-job-list']];
            $menu['刊登管理']['items'] = array_merge($items, $menu['刊登管理']['items']);
        } 
        
        
        if (!empty($guidenceUrl)) {
            $menu['教程'] = ['icon' => 'icon-stroe', 'url' => $guidenceUrl, 'target' => '_blank'];
        }

        return $menu;
    }

    public static function getLeftMenuArrV2($platform = 'lazada')
    {
        $guidenceUrl = "";
        if ('lazada' == $platform) {
            $guidenceUrl = 'http://www.littleboss.com/announce_info_30.html';
        } else if ('linio' == $platform) {
            $guidenceUrl = 'http://www.littleboss.com/announce_info_31.html';
        }

        $menu = [
            '刊登管理' => [
                'icon' => 'icon-shezhi',
                'items' => [
                    '待发布' => [
                        'url' => '/listing/' . $platform . '-listing-v2/publish',
                    ],
                    '发布中' => [
                        'url' => '/listing/' . $platform . '-listing-v2/publishing',
                    ],
                    '发布失败' => [
                        'url' => '/listing/' . $platform . '-listing-v2/publish-fail',
                    ],
                ]
            ],
            '商品列表' => [
                'icon' => 'icon-liebiao',
                'items' => [
                    '在线商品' => [
                        'url' => '/listing/' . $platform . '-v2/online-product'
                    ],
                    '下架商品' => [
                        'url' => '/listing/' . $platform . '-v2/off-shelf-product',
                    ]
                ],
            ],
        ];

        if (!empty($guidenceUrl)) {
            $menu['教程'] = ['icon' => 'icon-stroe', 'url' => $guidenceUrl, 'target' => '_blank'];
        }

        return $menu;
    }

    // lazada LGS 上传订单 == lazada 标记LGS发货
    public static function shipLazadaLgsOrder(\eagle\modules\order\models\OdOrder $order)
    {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping();
        $code2CodeMap = array_flip(self::$COUNTRYCODE_COUNTRYCode2_MAP);
        if (empty($code2CodeMap[$order->order_source_site_id]))
            return array(false, "站点" . $order->order_source_site_id . "不是 lazada的站点。");

        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $code2CodeMap[$order->order_source_site_id],'status'=>1]);
        
        // dzt20190426 cb支持
        if (empty($SLU)) {
            $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => 'cb' ,'status'=>1]);
			if(!empty($SLU)){
                $lazadaSites = json_decode($SLU->country_user_info, true);
                $lazadaSitesMap = Helper_Array::toHashmap($lazadaSites, 'country');
                if(empty($lazadaSitesMap[strtolower($order->order_source_site_id)]))
                    $SLU = null;
            }
        }
        
        if (empty($SLU)) {
            if($order->order_source_site_id == 'ID' || $order->order_source_site_id == 'TH'){//由于新接口，国家代码变为两位，需要兼容旧接口，
                $newMap = ['ID'=>'id','TH'=>'th'];
                $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $newMap[$order->order_source_site_id],'status'=>1]);
                if(empty($SLU)){
                    return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
                }
            }else{
                return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
            }
           
        }
        
        // dzt20161208 puid 602客户再次提出 All order items must have status Pending or Ready To Ship问题
        // dzt20161222 订单item已经添加平台状态，可以不再根据这个了
//         $addiInfo = json_decode($order->addi_info,true);
//         $itemStatus = array();
//         if(!empty($addiInfo) && !empty($addiInfo['lgs_related']) && !empty($addiInfo['lgs_related']['itemStatus'])){
//             $itemStatus = $addiInfo['lgs_related']['itemStatus'];
//         }

        $OrderItemIds = array();
        $ignoreItems = array();
        foreach ($order->items as $item) {
            if(!empty($item->platform_status) 
                    && !in_array($item->platform_status, LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){// dzt20161208 不标记不适合标记发货的item
                $ignoreItems[] = $item->order_source_order_item_id.'=>'.$item->platform_status;
            }else{
                $OrderItemIds[] = $item->order_source_order_item_id;
            }
        }

        // dzt20161208
        if(empty($OrderItemIds)){// 或者没有要上传产品的不报错直接当成已经上传成功？
            return array(false, "订单".$order->order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems));
        }
        
        $config = array(
            "userId" => $SLU->platform_userid,
            "apiKey" => $SLU->token,
            "countryCode" => strtolower($order->order_source_site_id),
        );

        // 获取运输方式shipping_method_code
        $method = "";
        if (strlen($order->default_shipping_method_code) > 0) {
            $service = SysShippingService::findOne($order->default_shipping_method_code);
            $method = $service->shipping_method_code;

            if ("LGS-TIKI-ID" == $method) {// dzt20160606 shipping code问题
                $method = "LGS-Tiki-ID";
            }
        }

        $appParams = array(
            'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
            'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
            'ShippingProvider' => $method,
            'TrackingNumber' => "", // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
        );

        if(!empty($ignoreItems)){
		    \Yii::info("shipLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ignore items:".implode(',', $ignoreItems) , "file");
		}
        \Yii::info("shipLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ready to proxy.config:".json_encode($config).",appParams:".json_encode($appParams), "file");
        if(!empty($SLU->version)){//新接口发货
            $config['apiKey'] = $SLU->access_token;//新授权，用新的token
            $result = LazadaInterface_Helper_V2::shipOrder($config,$appParams);
        }else{//旧接口发货
            $result = LazadaInterface_Helper::shipOrder($config, $appParams);
        }
        \Yii::info("shipLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",return from proxy.result:".json_encode($result), "file");

        $delivery_time = time();
        if ($result ['success'] && $result['response']['success'] == true) { // 成功
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "C";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
            
            return array(true, "");
        } else {
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "F";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
            
            return array(false, $result['message']);
        }
    }// end of LGS shipLazadaLgsOrder


    // lazada LGS 上传订单后 获取lazada 生成的订单 package id 和 TrackingNumber
    public static function getPackageInfo(\eagle\modules\order\models\OdOrder $order)
    {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping();
        $code2CodeMap = array_flip(self::$COUNTRYCODE_COUNTRYCode2_MAP);
        if (empty($code2CodeMap[$order->order_source_site_id]))
            return array(false, "站点" . $order->order_source_site_id . "不是 lazada的站点。");

        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $code2CodeMap[$order->order_source_site_id] ,'status'=>1]);

        // dzt20190426 cb支持
        if (empty($SLU)) {
            $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => 'cb' ,'status'=>1]);
			if(!empty($SLU)){
                $lazadaSites = json_decode($SLU->country_user_info, true);
                $lazadaSitesMap = Helper_Array::toHashmap($lazadaSites, 'country');
                if(empty($lazadaSitesMap[strtolower($order->order_source_site_id)]))
                    $SLU = null;
            }
        }
        
        if (empty($SLU)) {
            if($order->order_source_site_id == 'ID' || $order->order_source_site_id == 'TH'){//由于新接口，国家代码变为两位，需要兼容旧接口，
                $newMap = ['ID'=>'id','TH'=>'th'];
                $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $newMap[$order->order_source_site_id] ,'status'=>1]);
                if(empty($SLU)){
                    return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
                }
            }else{
                return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
            }
        }

        
        $config = array(
            "userId" => $SLU->platform_userid,
            "apiKey" => $SLU->token,
            "countryCode" => strtolower($order->order_source_site_id),
        );
        
        
        $addiInfo = json_decode($order->addi_info,true);
        if(empty($addiInfo) || empty($addiInfo['lgs_related']) || empty($addiInfo['lgs_related']['OrderId']))
            return array(false, "订单：" . $order->order_source_site_id . " 原始信息丢失。");
        
        $apiParams = array(
            "OrderId" => $addiInfo['lgs_related']['OrderId']
        );

        
        //新授权
        if(!empty($SLU->version)){
            $config['apiKey'] = $SLU->access_token;
            $itemsResult = LazadaInterface_Helper_V2::getOrderItems($config, $apiParams);
        }else{
            $itemsResult = LazadaInterface_Helper::getOrderItems($config, $apiParams);
        }
        
        \Yii::info("(".__FUNCTION__."),apiParams:" . json_encode($apiParams).",config:" . json_encode($config), "file");
        \Yii::info("(".__FUNCTION__."),itemsResult:" . json_encode($itemsResult), "file");
        
        if ($itemsResult ['success'] && $itemsResult['response']['success'] == true) { // 成功
            if(isset($itemsResult["response"]["items"][$addiInfo['lgs_related']['OrderId']])){//兼容旧接口
                $itemsArr = $itemsResult["response"]["items"][$addiInfo['lgs_related']['OrderId']];
            }else{
                $itemsArr = $itemsResult["response"]["items"][$order->order_source_order_id];
            }
            
	
            // dzt20161021 如果一个item发货，一个item取消，这里获取的item有可能没有发货信息
            $hasShippingInfo = false;
            $TrackingCode = '';
            $PackageId = '';
            if(!empty($SLU->version)){//兼容旧接口
                foreach ($itemsArr as $item){
                    if($item["tracking_code"]<>""){
                        $hasShippingInfo = true;
                        $TrackingCode = $item["tracking_code"];
                        $PackageId = $item["package_id"];
                        $ShipmentProvider = $item["shipment_provider"];
                        break;
                    }
                }
            }else{
                foreach ($itemsArr as $item){
                    if($item["TrackingCode"]<>""){
                        $hasShippingInfo = true;
                        $TrackingCode = $item["TrackingCode"];
                        $PackageId = $item["PackageId"];
                        $ShipmentProvider = $item["ShipmentProvider"];
                        break;
                    }
                }
            }
            
            if($hasShippingInfo){
            	return array(true, array('TrackingNumber' => $TrackingCode, 'PackageId' => $PackageId ,'ShipmentProvider' => $ShipmentProvider));
            }else{
            	return array(false, 'Order has not any shipping info.');
            }
        } else {
            return array(false, $itemsResult['message']);
        }
    }// end of LGS getPackageInfo

    /**
     * +---------------------------------------------------------------------------------------------
     * lazada LGS 上传订单 == lazada 获取跟踪号
     * 
     * 订单多了个新步骤SetStatusToPackedByMarketplace，api doc说明在SetStatusToReadyToShip之前必须要调用这个
     * 尽管到今天，我们没有调用Set PM直接Set RTS 好像一直没有事情发生，但其他文档和lazada的开发人员都让我们加上这步了。
     * 
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param \eagle\modules\order\models\OdOrder $order        订单object
     * +---------------------------------------------------------------------------------------------
     * @return array(boolean,string)
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt     2017/03/17              初始化
     * +---------------------------------------------------------------------------------------------
     **/
    // 
    public static function packLazadaLgsOrder(\eagle\modules\order\models\OdOrder $order)
    {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping();
        $code2CodeMap = array_flip(self::$COUNTRYCODE_COUNTRYCode2_MAP);
        if (empty($code2CodeMap[$order->order_source_site_id]))
            return array(false, "站点" . $order->order_source_site_id . "不是 lazada的站点。");
        
        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $code2CodeMap[$order->order_source_site_id],'status'=>1]);
        
        // dzt20190426 cb支持
        if (empty($SLU)) {
            $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => 'cb' ,'status'=>1]);
            if(!empty($SLU)){
                $lazadaSites = json_decode($SLU->country_user_info, true);
                $lazadaSitesMap = Helper_Array::toHashmap($lazadaSites, 'country');
                if(empty($lazadaSitesMap[strtolower($order->order_source_site_id)]))
                    $SLU = null;
            }
        }
        
        if (empty($SLU)) {
            if($order->order_source_site_id == 'ID' || $order->order_source_site_id == 'TH'){//由于新接口，国家代码变为两位，需要兼容旧接口，
                $newMap = ['ID'=>'id','TH'=>'th'];
                $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $newMap[$order->order_source_site_id],'status'=>1]);
                if(empty($SLU)){
                    return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
                }
            }else{
                return array(false, $order->selleruserid . "站点:" . self::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在");
            }
        }
        
        $OrderItemIds = array();
        $ignoreItems = array();
        foreach ($order->items as $item) {
            if(!empty($item->platform_status)
                    && !in_array($item->platform_status, LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){// dzt20161208 不标记不适合标记发货的item
                $ignoreItems[] = $item->order_source_order_item_id.'=>'.$item->platform_status;
            }else{
                $OrderItemIds[] = $item->order_source_order_item_id;
            }
        }
        
        $config = array(
                "userId" => $SLU->platform_userid,
                "apiKey" => $SLU->token,
                "countryCode" => strtolower($order->order_source_site_id),
        );
        
        // 获取运输方式shipping_method_code
        $method = "";
        if (strlen($order->default_shipping_method_code) > 0) {
            $service = SysShippingService::findOne($order->default_shipping_method_code);
            $method = $service->shipping_method_code;
            if ("LGS-TIKI-ID" == $method) {// dzt20160606 shipping code问题
                $method = "LGS-Tiki-ID";
            }
        }
        
        $appParams = array(
                'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
                'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
                'ShippingProvider' => $method,
        );
        
        if(!empty($ignoreItems)){
            \Yii::info("packLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ignore items:".implode(',', $ignoreItems) , "file");
        }
        \Yii::info("packLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ready to proxy.config:".json_encode($config).",appParams:".json_encode($appParams), "file");
        if(!empty($SLU->version)){//新帐号
            $config['apiKey'] = $SLU->access_token;//新帐号，用新的token
            $result = LazadaInterface_Helper_V2::packedByMarketplace($config, $appParams);
        }else{//旧帐号
            $result = LazadaInterface_Helper::packedByMarketplace($config, $appParams);
        }
        \Yii::info("packLazadaLgsOrder puid:".$SLU->puid.",orderid:".$order->order_id.",return from proxy.result:".json_encode($result), "file");
        
        if ($result ['success'] && $result['response']['success'] == true) { // 成功
            $itemsArr = $result["response"]["items"];
            $hasShippingInfo = false;
            $TrackingCode = '';
            $PackageId = '';
            if(!empty($SLU->version)){//新帐号
                foreach ($itemsArr as $item){
                    if($item["tracking_number"]<>""){
                        $hasShippingInfo = true;
                        $TrackingCode = $item["tracking_number"];
                        $PackageId = $item["package_id"];
                        break;
                    }
                }
            }else{//旧帐号
                foreach ($itemsArr as $item){
                    if($item["TrackingNumber"]<>""){
                        $hasShippingInfo = true;
                        $TrackingCode = $item["TrackingNumber"];
                        $PackageId = $item["PackageId"];
                        break;
                    }
                }
            }
            
            if($hasShippingInfo){
                return array(true, array('TrackingNumber' => $TrackingCode, 'PackageId' => $PackageId));
            }else{
                return array(false, 'Order has not any shipping info.');
            }
        } else {
            return array(false, $result['message']);
        }
    }
    
    // 更新数据库缓存的lazada ,linio,jumia目录树
    // 由于目录树返回内容比较多，所以更新失败不删除，以免客户自动拉取太慢。
    public static function refreshCategoryTree()
    {
        $trees = LazadaCategories::find()->asArray()->all(); 
        \Yii::info("refreshCategoryTree There are " . count($trees) . " trees to update.", "file");
        foreach ($trees as $tree) {
            if(!array_key_exists($tree['site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){//旧接口不再处理lazada的国家
                // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
                $lazadaUserQuery = SaasLazadaUser::find()->where(['lazada_site' => $tree['site']]);
                $lazadaUser = $lazadaUserQuery->orderBy('create_time desc')->one();
                \Yii::info("refreshCategoryTree site:" . $tree['site'], "file");
                if (!empty($lazadaUser)) {
                    $config = array(
                            "userId" => $lazadaUser->platform_userid,
                            "apiKey" => $lazadaUser->token,
                            "countryCode" => $lazadaUser->lazada_site
                    );
                
                    \Yii::info("refreshCategoryTree getCategoryTree site:" . $tree['site'] . ",config:" . json_encode($config), "file");
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config, false);
                    if ($ret == false) {
                        \Yii::info("refreshCategoryTree site:" . $tree['site'] . " " . $categories, "file");
                    } else {
                        \Yii::info("refreshCategoryTree site:" . $tree['site'] . " update success.", "file");
                    }
                }
            }
        }
    }
    
    // 更新数据库缓存的lazada目录树
    // 由于目录树返回内容比较多，所以更新失败不删除，以免客户自动拉取太慢。
    public static function refreshCategoryTreeV2()
    {
        $trees = LazadaCategories::find()->asArray()->all(); 
        \Yii::info("refreshCategoryTree There are " . count($trees) . " trees to update.", "file");
        foreach ($trees as $tree) {
            if(array_key_exists($tree['site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){//新接口只更新lazada的国家
                // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
                $lazadaUserQuery = SaasLazadaUser::find()->where(['lazada_site' => $tree['site'],'version'=>"v2"]);
                $lazadaUser = $lazadaUserQuery->orderBy('create_time desc')->one();
                \Yii::info("refreshCategoryTree site:" . $tree['site'], "file");
                if (!empty($lazadaUser)) {
                    $config = array(
                        "userId" => $lazadaUser->platform_userid,
                        "apiKey" => $lazadaUser->access_token,
                        "countryCode" => $lazadaUser->lazada_site
                    );
                
                    \Yii::info("refreshCategoryTreeV2 getCategoryTree site:" . $tree['site'] . ",config:" . json_encode($config), "file");
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryTree($config, false);
                    if ($ret == false) {
                        \Yii::info("refreshCategoryTreeV2 site:" . $tree['site'] . " " . $categories, "file");
                    } else {
                        \Yii::info("refreshCategoryTreeV2 site:" . $tree['site'] . " update success.", "file");
                    }
                }
            }
        }
    }

    // 刷新数据库缓存的lazada ,linio,jumia目录
    // 如果更新失败就删除
    public static function refreshCategoryAttrs()
    {
        $catAttrs = LazadaCategoryAttr::find()
        ->asArray()->all();
        
        \Yii::info("refreshCategoryAttrs There are " . count($catAttrs) . " categorires to update.", "file");
        foreach ($catAttrs as $catAttr) {
            if(!array_key_exists($catAttr['site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){
                // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
                $lazadaUser = SaasLazadaUser::find()->where(['lazada_site' => $catAttr['site']])->orderBy('create_time desc')->one();
                if (!empty($lazadaUser)) {
                    $config = array(
                            "userId" => $lazadaUser->platform_userid,
                            "apiKey" => $lazadaUser->token,
                            "countryCode" => $lazadaUser->lazada_site
                    );
                
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelper::getCategoryAttributes($config, $catAttr['categoryid'], false);
                    if ($ret == false) {
                        \Yii::info("refreshCategoryAttrs $categories", "file");
                        // 站点目录变化，会导致某些目录无法再更新成功要删除
                        LazadaCategoryAttr::deleteAll(['id' => $catAttr['id']]);
                    }
                }
            }
        }
    }
    
    // 刷新数据库缓存的lazada目录
    // 如果更新失败就删除
    public static function refreshCategoryAttrsV2()
    {
        $catAttrs = LazadaCategoryAttr::find()
        ->asArray()->all();
    
        \Yii::info("refreshCategoryAttrs There are " . count($catAttrs) . " categorires to update.", "file");
        foreach ($catAttrs as $catAttr) {
            if(array_key_exists($catAttr['site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){
                \Yii::info("refreshCategoryAttrsV2 site:" . $catAttr['site'] . " categoryid:" . $catAttr['categoryid'], "file");
                
                // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
                $lazadaUser = SaasLazadaUser::find()->where(['lazada_site' => $catAttr['site'],'version'=>"v2"])->orderBy('create_time desc')->one();
                if (!empty($lazadaUser)) {
                    $config = array(
                        "userId" => $lazadaUser->platform_userid,
                        "apiKey" => $lazadaUser->access_token,
                        "countryCode" => $lazadaUser->lazada_site
                    );
                
                    list($ret, $categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryAttributes($config, $catAttr['categoryid'], false);
                    if ($ret == false) {
                        \Yii::info("refreshCategoryAttrsV2 $categories", "file");
                        // 站点目录变化，会导致某些目录无法再更新成功要删除
                        LazadaCategoryAttr::deleteAll(['id' => $catAttr['id']]);
                    }
                }
            }
        }
    }

    // 更新数据库缓存的lazada ,linio,jumia品牌
    // 由于站点品牌返回内容比较多，所以更新失败不删除，以免客户自动拉取太慢。
    public static function refreshBrands()
    {
        $toUpBrands = array();
        // 好像是为了确保站点有账号可以拉取信息 不用LazadaBrand而是这样列出来的，不过好像没啥用。后面可以改回LazadaBrand foreach 更新
        $userSites = SaasLazadaUser::find()->where(['status' => 1])->groupBy('lazada_site')->asArray()->all();
        foreach ($userSites as $userSite) {
            if(!array_key_exists($userSite['lazada_site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){//只更新linio jumia的站点
                $toUpBrands[] = array('site' => $userSite['lazada_site']);
            }
        }


        \Yii::info("refreshBrands There are " . count($toUpBrands) . " sites' brands to update.", "file");
// 		echo "There are ".count($toUpBrands)." sites' brands to update.\n";
        foreach ($toUpBrands as $brand) {
            \Yii::info("refreshBrands site:" . $brand['site'], "file");
            // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
            $lazadaUser = SaasLazadaUser::find()->where(['lazada_site' => $brand['site']])->orderBy('create_time desc')->one();
            if (!empty($lazadaUser)) {
                $config = array(
                    "userId" => $lazadaUser->platform_userid,
                    "apiKey" => $lazadaUser->token,
                    "countryCode" => $lazadaUser->lazada_site
                );

                list($ret, $categories) = SaasLazadaAutoSyncApiHelper::getBrands($config, "", "", true);
                if ($ret == false) {
                    \Yii::info("refreshBrands site:" . $brand['site'] . " " . $categories, "file");
                } else {
                    \Yii::info("refreshBrands site:" . $brand['site'] . " update success.", "file");
                }
            }
        }
    }
    
    // 更新数据库缓存的lazada品牌
    // 由于站点品牌返回内容比较多，所以更新失败不删除，以免客户自动拉取太慢。
    public static function refreshBrandsV2()
    {
        $toUpBrands = array();
        $userSites = SaasLazadaUser::find()->where(['status' => 1])->groupBy('lazada_site')->asArray()->all();
        foreach ($userSites as $userSite) {
            if(array_key_exists($userSite['lazada_site'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())){//只更新Lazada的站点
                $toUpBrands[] = array('site' => $userSite['lazada_site']);
            }
        }
    
        \Yii::info("refreshBrands There are " . count($toUpBrands) . " sites' brands to update.", "file");
        // 		echo "There are ".count($toUpBrands)." sites' brands to update.\n";
        foreach ($toUpBrands as $brand) {
            \Yii::info("refreshBrands site:" . $brand['site'], "file");
            // @todo 最近绑定的账号可能还不可靠，后面可以改为最近更新过信息的账号。
            $lazadaUser = SaasLazadaUser::find()->where(['lazada_site' => $brand['site'],'version'=>"v2"])->orderBy('create_time desc')->one();
            if (!empty($lazadaUser)) {
                $config = array(
                    "userId" => $lazadaUser->platform_userid,
                    "apiKey" => $lazadaUser->access_token,
                    "countryCode" => $lazadaUser->lazada_site
                );
    
                list($ret, $categories) = SaasLazadaAutoSyncApiHelperV4::getBrands($config, "", "", true);
                if ($ret == false) {
                    \Yii::info("refreshBrandsV2 site:" . $brand['site'] . " " . $categories, "file");
                    // 					echo " ".$categories.PHP_EOL;
                } else {
                    \Yii::info("refreshBrandsV2 site:" . $brand['site'] . " update success.", "file");
                    // 					echo " update success.".PHP_EOL;
                }
            }
        }
    }

// 开启/关闭 lazada,linio,jumia在线商品同步
    public static function switchProductSync($lazadaUid, $turnOn = true)
    {
        $SLA = SaasLazadaAutosync::findOne(['lazada_uid' => $lazadaUid, 'type' => 4]);
        if (empty($SLA))
            return array(false, "商品同步记录不存在");

        if ($turnOn) {
            $SLA->is_active = 1;
        } else {
            $SLA->is_active = 0;
        }

        if (!$SLA->save()) {
            \Yii::error("switchProductSync SLA->save() false:" . json_encode($SLA->errors), "file");
            return array(false, implode(';', $SLA->errors));
        } else {
            return array(true, "");
        }
    }

    // 开启/关闭 lazada,linio,jumia订单同步
    public static function switchOrderSync($lazadaUid, $turnOn = true)
    {
        if ($turnOn) {
            $isActive = 1;
        } else {
            $isActive = 0;
        }

        $nums = SaasLazadaAutosync::updateAll(['is_active' => $isActive], ['lazada_uid' => $lazadaUid, 'type' => [1, 2, 3, 5]]);
        if (empty($nums)) {
            \Yii::error("switchOrderSync SaasLazadaAutosync::updateAll false. is_active:$isActive ,lazada_uid:$lazadaUid ", "file");
            return array(false, "修改失败");
        } else {

            return array(true, "");
        }
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * lazada linio jumia 在线商品同步情况 数据
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param $account_key                各个平台账号表主键（必需） 就是用获取对应账号信息的
     * @param $uid                        uid use_base 的id
     * +---------------------------------------------------------------------------------------------
     * @return array (  'result'=> array(is_active 是否启用 , last_time上次同步时间,message 信息 ,status 同步执行状态) 同步表的最新数据
     *    //其中同步执行状态 为以下值'0'=>'等待同步','1'=>'已经有同步队列为他同步中','2'=>'同步成功','3'=>'同步失败','4'=>'同步完成',
     *                    'message'=>执行详细结果
     *                    'success'=> true 成功 false 失败    )
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt        2016/03/03                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    public static function getLastProductSyncDetail($account_key, $uid)
    {
        //get active uid
        if (empty($uid)) {
            $userInfo = \Yii::$app->user->identity;
            if ($userInfo['puid'] == 0) {
                $uid = $userInfo['uid'];
            } else {
                $uid = $userInfo['puid'];
            }
        }

        $autoSyncProdInfo = SaasLazadaAutosync::find()->where(['lazada_uid' => $account_key, 'type' => 4])->asArray()->one();

        if (empty($autoSyncProdInfo)) {
            return ['success' => false, 'message' => '没有同步信息', 'result' => []];
        } else {
            $result['is_active'] = $autoSyncProdInfo['is_active'];
            $result['last_time'] = $autoSyncProdInfo['update_time'];
            $result['message'] = $autoSyncProdInfo['message'];
            $result['status'] = $autoSyncProdInfo['status'];// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题  刚好大致一样
            return ['success' => true, 'message' => '', 'result' => $result];
        }
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * lazada linio jumia 在线商品同步情况 数据
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param $account_key                各个平台账号表主键（必需） 就是用获取对应账号信息的
     * @param $uid                        uid use_base 的id
     * +---------------------------------------------------------------------------------------------
     * @return array (  'result'=> array(is_active 是否启用 , last_time上次同步时间,message 信息 ,status 同步执行状态) 同步表的最新数据
     *    //其中同步执行状态 为以下值'0'=>'等待同步','1'=>'已经有同步队列为他同步中','2'=>'同步成功','3'=>'同步失败','4'=>'同步完成',
     *                    'message'=>执行详细结果
     *                    'success'=> true 成功 false 失败    )
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt        2016/03/03                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    public static function getLastProductSyncDetailV2($account_key, $uid)
    {
        //get active uid
        if (empty($uid)) {
            $userInfo = \Yii::$app->user->identity;
            if ($userInfo['puid'] == 0) {
                $uid = $userInfo['uid'];
            } else {
                $uid = $userInfo['puid'];
            }
        }
    
        $autoSyncProdInfo = SaasLazadaAutosyncV2::find()->where(['lazada_uid' => $account_key, 'type' => 4])->asArray()->one();
    
        if (empty($autoSyncProdInfo)) {
            return ['success' => false, 'message' => '没有同步信息', 'result' => []];
        } else {
            $result['is_active'] = $autoSyncProdInfo['is_active'];
            $result['last_time'] = $autoSyncProdInfo['update_time'];
            $result['message'] = $autoSyncProdInfo['message'];
            $result['status'] = $autoSyncProdInfo['status'];// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题  刚好大致一样
            return ['success' => true, 'message' => '', 'result' => $result];
        }
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * 获取Lazada订单付款方式。LGS 打印要用到付款方式
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param \eagle\modules\order\models\OdOrder $order
    +---------------------------------------------------------------------------------------------
     * @return multitype:boolean string  付款方式
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt        2016/05/10                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    public static function getPaymentMethod(\eagle\modules\order\models\OdOrder $order)
    {
    	$addiInfo = json_decode($order->addi_info,true);
    	if(empty($addiInfo) || empty($addiInfo['lgs_related']) || empty($addiInfo['lgs_related']['PaymentMethod']))
    		return array(false, "订单：" . $order->order_source_site_id . " 买家付款信息丢失。");
    	

        return array(true, $addiInfo['lgs_related']['PaymentMethod']);
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * 获取Lazada店铺名称。LGS 打印高仿面单要用到Lazada店铺名称
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param string $userId email账号
     * @param string $countryCode 站点
     * +---------------------------------------------------------------------------------------------
     * @return multitype:boolean string            付款方式
     * +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        dzt        2016/05/11                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    public static function getStoreName($userId, $countryCode)
    {
        $tempCode = array_flip(self::$COUNTRYCODE_COUNTRYCode2_MAP);
        if (!empty($tempCode[$countryCode])) {
            $countryCode2 = $tempCode[$countryCode];
        } else {
            $countryCode2 = strtolower($countryCode);
        }

        $SLA = SaasLazadaUser::findOne(["platform_userid" => $userId, "lazada_site" => $countryCode2]);
        if (empty($SLA)) {
            if($countryCode == 'ID' || $countryCode == 'TH'){//由于新接口，国家代码变为两位，需要兼容旧接口，
                $newMap = ['ID'=>'id','TH'=>'th'];
                $SLA = SaasLazadaUser::findOne(['platform_userid' => $userId, 'lazada_site' => $newMap[$countryCode]]);
                if(empty($SLA)){
                    return array(false, "订单对应的账号$countryCode：$userId 不存在");
                }
            }else{
                return array(false, "订单对应的账号$countryCode：$userId 不存在");
            }
            
        }

        return array(true, $SLA->store_name);
    }

    /**
     * 监控表-saas_aliexpress_autosync 中的status(1,2,3)订单状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     */
    public static function getOrderSysErrorListForTwoHours()
    {
        return LazadaApiHelper::getAutoSynErrorList(1, [1, 2, 3], time() - 7200);
    }

    /**
     * 监控表-saas_aliexpress_autosync 中的status(5)订单状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     */
    public static function getOrderSysErrorListForSixHours()
    {
        return LazadaApiHelper::getAutoSynErrorList(1, [5], time() - 21600);
    }


    /**
     * 监控表-saas_aliexpress_autosync 中的status=4状态(产品拉取),超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     */
    public static function getListingSysErrorListForTwoHours()
    {
        return LazadaApiHelper::getAutoSynErrorList(1, [4], time() - 7200);
    }

    /**
     * 监控表-queue_lazada_getorder 中的status拉取状态,超出设置时间还是1的情况,就需要发邮件报警了
     * last_time在当前检测时间的两小时后
     */
    public static function getItemSysErrorListForTwoHours()
    {
        return LazadaApiHelper::getItemSysErrorList(1, time() - 7200);
    }

    /**
     * 获取saas_lazada_autosync中的错误数据
     *
     */
    public static function getAutoSynErrorList($status, $types = array(), $last_time)
    {
        $connection = Yii::$app->db;
        $res = $connection->createCommand("
					SELECT sla.*,slu.platform_userid,FROM_UNIXTIME(sla.update_time) as lt,FROM_UNIXTIME(sla.next_execution_time) as nt 
					FROM saas_lazada_autosync sla
					LEFT JOIN saas_lazada_user slu ON slu.lazada_uid = sla.lazada_uid
					WHERE 
					" . (!empty($types) ? " sla.`type` in (" . implode(',', $types) . ")" : "") . " 
        			AND sla.is_active=1 
        			AND  (( sla.`status`='{$status}'  AND sla.update_time <'{$last_time}' ) 
					or ( sla.error_times>=5 and sla.message not like '%ErrorCode:7%' )) 
					ORDER BY sla.update_time DESC  
				")->query();
        $result = $res->readAll();
        $msg = "============注意：只有活跃用户才reset 同步状态以及error_times============" . PHP_EOL . PHP_EOL;
        if (!empty($result)) {
            foreach ($result as $vs) {
                ////检查是否活跃用户,在邮件主体中标记出来吧
                $mt = self::isActiveUser($vs['puid']) === false ? '非活跃用户' : '活跃用户';
                //如果是活跃用户,就先把status,改成0
                if ($mt == '活跃用户') {
                    $id = $vs['id'];
                    $update = $connection->createCommand("UPDATE saas_lazada_autosync SET `status`=0,`error_times`=0 WHERE id='{$id}'")->execute();
                }

                $errorMsg = empty($vs['message']) ? "" : $vs['message'];
                $msg .= $mt . '--PUID:' . $vs['puid'] . '--lazada_uid:' . $vs['lazada_uid'] . ',最后更新时间--' . $vs['lt'] . ',Lazada登录账户--' . $vs['platform_userid'] . ',站点--' . $vs['site'] . 
                ',同步type--' . $vs['type'] . ',同步status--' . $vs['status'] . ',错误次数--' . $vs['error_times'] . ",错误信息--$errorMsg,autosyncId--" . $vs['id'] . PHP_EOL . PHP_EOL;
            }
            echo $msg;
            $sendto_email = ['1241423221@qq.com', '395628249@qq.com', '156038530@qq.com', '805411301@qq.com'];//
            $subject = 'Lazada订单数据拉取错误:type-' . implode(',', $types);
            $body = $msg;
// 			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $body);

            return array(false, $msg);
        } else {
            echo "{$status}没有type=" . implode(',', $types) . "的异常数据";
            yii::info("{$status}没有type=" . implode(',', $types) . "的异常数据", "file");
            return array(true, "{$status}没有type=" . implode(',', $types) . "的异常数据");
        }

    }
    
    /**
     * 监控表-lazada_feed_list 中的process_status 为0个数，以及is_running长时间是1的情况,就需要发邮件报警了
     */
    public static function getFeedErrorList() {
    	$unCheckedFeeds = LazadaFeedList::find()->where(['process_status'=>[0,7]])->andWhere('next_execution_time<'.time())->all();
    	$unCheckedCount = count($unCheckedFeeds);
    	$isCheckSuccess = true;
    	$msg = '';
    	if ($unCheckedCount > 100) {
    		$isCheckSuccess = false;
    		$msg .= 'Warning 当前有'.$unCheckedCount.'个Feed未检查，请留意检查Job的情况'. PHP_EOL;
    	} else {
    		$msg .= "lazada_feed_list Feeds 拉取正常 当前有$unCheckedCount 个Feed待拉取". PHP_EOL;
    		echo "lazada_feed_list Feeds 拉取正常 当前有$unCheckedCount 个Feed待拉取\n";
    		yii::info("lazada_feed_list Feeds 拉取正常 当前有$unCheckedCount 个Feed待拉取", "file");
    	}
    	
    	if (!empty($unCheckedFeeds)) {
    		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
    		foreach ($unCheckedFeeds as $vs) {
    			if($vs->error_times >=10 || ($vs->is_running ==1 && $vs->update_time<(time()-3600))){
    				if($isCheckSuccess)$isCheckSuccess = false;
    				if($vs->error_times > 0){
    					$msg .= 'Feed:' . $vs->Feed . '--Status:' . $vs->Status . '--Action:' . $vs->Action . '--' . '--PUID:' . $vs['puid'] . '--错误次数:' . $vs->error_times . '--错误内容:' . $vs->message . ',最后更新时间-- ' . date('Y-m-d H:i:s',$vs->update_time) . ',Lazada登录账户-- ' . $vs->lazada_saas_user_id . PHP_EOL;
    				}else{
    					$msg .= 'Feed:' . $vs->Feed . '--Status:' . $vs->Status . '--Action:' . $vs->Action . '--' . '--PUID:' . $vs['puid'] . ',is_running=1,最后更新时间-- ' . date('Y-m-d H:i:s',$vs->update_time) . ',Lazada登录账户-- ' . $vs->lazada_saas_user_id . PHP_EOL;
    				}
    				$msg .= "<br />";
    				
    				if(false === strpos($vs->message, "ErrorCode:12")){// 其他失败重试
    					$vs->error_times = 0;
    					$vs->is_running = 0;
    				}else{// ErrorMessage:E012: Invalid Feed ID 处理 异常feed让客户重新发布
    					$config=$allLazadaAccountsInfoMap[$vs->lazada_saas_user_id];
    					$feedId=$vs->Feed;
    					$reqParams=array("puid"=>$vs->puid,"feedId"=>$vs->Feed,"totalRecords"=>$vs->TotalRecords);
    					$reqParams["failReport"]= "lazada api error";
    					if ($vs->type==LazadaFeedHelper::PRODUCT_CREATE){
    						\Yii::info("LazadaCallbackHelper::productCreate before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
    						$ret=LazadaCallbackHelper::productCreate($reqParams);
    						\Yii::info("LazadaCallbackHelper::productCreate after feedId:$feedId ret:".print_r($ret,true),"file");
    					}else if($vs->type==LazadaFeedHelper::PRODUCT_UPDATE){
    						// 回调修改listing 修改状态信息
    						\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
    						$ret=LazadaCallbackHelper::productUpdate($reqParams,$config);// dzt20160119 带上config 参数立即获取产品信息
    						\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$feedId ret:".print_r($ret,true),"file");
    					}else if ($vs->type==LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD) {
    						\Yii::info("LazadaCallbackHelper::imageUpload reqParams:".print_r($reqParams,true),"file");
    						list($ret,$sellerSkus)=LazadaCallbackHelper::imageUpload($reqParams);
    						\Yii::info("LazadaCallbackHelper::imageUpload sellerSku:".print_r($sellerSkus,true),"file");
    					}
    					
    					$vs->process_status = 8;// 异常feed让客户重新发布
    				}
    				$vs->save();
    			}
    		}
    	} else {
    		$msg .= "lazada_feed_list没有的异常数据";
    		echo "lazada_feed_list没有的异常数据\n";
    		yii::info("lazada_feed_list没有的异常数据", "file");
    	}
		echo $msg;
    	return array($isCheckSuccess,$msg);
    	
    }
    

    //end function
    protected static function isActiveUser($uid)
    {

		return true;
        // if (empty(self::$active_users)) {
            // self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
        // }

        // if (in_array($uid, self::$active_users)) {
            // return true;
        // }

        // return false;
    }

	/**
	 * 获取queue_lazada_getorder中的错误数据 @todo error_times 检查
	 *
	 * @author zwd
	 */
	public static function getItemSysErrorList($status, $last_time)
	{
		$connection = Yii::$app->db;
		$res = $connection->createCommand("
				SELECT que.id,que.puid,que.status,que.message,que.error_times,que.orderid,saas_lazada_user.platform_userid,FROM_UNIXTIME(que.update_time) as lt
				FROM queue_lazada_getorder_v2 que
				LEFT JOIN saas_lazada_user ON saas_lazada_user.lazada_uid = que.lazada_uid
				WHERE ((que.`status`='{$status}' AND que.update_time <'{$last_time}') 
				or (que.`status`='4')) or (que.error_times>=10 )
				AND que.is_active=1
				ORDER BY que.update_time DESC
				")->query();
		$result = $res->readAll();
		$msg = "";
		$isCheckSuccess = true;
		
		$undoCount = QueueLazadaGetorder::find()
		->where('(status=0 or status =3) and error_times<10 and is_active=1')->count();
		if ($undoCount > 1000) {
			$isCheckSuccess = false;
			$msg .= 'Warning 当前有'.$undoCount.'个订单待拉取，请留意拉取Job的情况'. PHP_EOL;
		} else {
			$msg .= "queue_lazada_getorder_v2 Items 拉取正常 当前有$undoCount 个订单item待拉取". PHP_EOL;
			echo "queue_lazada_getorder_v2 Items 拉取正常 当前有$undoCount 个订单item待拉取";
			yii::info("queue_lazada_getorder_v2 Items 拉取正常 当前有$undoCount 个订单item待拉取", "file");
		}
		
		if (!empty($result)) {
			$isCheckSuccess = false;
			foreach ($result as $vs) {
				//检查是否活跃用户,在邮件主体中标记出来吧
				$mt = self::isActiveUser($vs['puid']) === false ? '非活跃用户' : '活跃用户';
				if($vs['status'] != 4){
					$id = $vs['id'];
					$update = $connection->createCommand("UPDATE queue_lazada_getorder_v2 SET `status`=0,`error_times`=0 WHERE id='{$id}'")->execute();
				}
	
				$msg .= $mt . 'ItemError--Orderid:' . $vs['orderid'] . '--status:' . $vs['status'] . '--' . '--PUID:' . $vs['puid'] . '--错误次数:' . $vs['error_times'] . '--错误内容:' . $vs['message'] . ',最后更新时间-- ' . $vs['lt'] . ',Lazada登录账户-- ' . $vs['platform_userid'] . PHP_EOL;
				$msg .= "<br />";
			}
// 			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $body);

		} else {
			$msg .= "status--{$status}没有的异常数据";
			echo "status--{$status}没有的异常数据";
			yii::info("status--{$status}没有的异常数据", "file");
		}
		echo $msg;
		return array($isCheckSuccess,$msg);

	}
    //end function
    
    /**
     * 发送邮件
     * @param string $sendto_email 接收邮箱
     * @param string $subject 标题
     * @param string $body 主体
     * @param array $email 用来发送的邮箱(随机在其中选择一个)
     *  array(
     *    array("email"=>"xxx@163.com","password" =>"xxx" ,"host"=>"smtp.163.com"),
     * array("email"=>"xxx@qq.com","password" =>"xxx" ,"host"=>"smtp.qq.com"),
     *  )
     * @return boolean
     */
    public static function sendEmail($sendto_email, $subject, $body, $email = array())
    {
		// TODO add send mail info
        $emailsArr = array(
            array("email" => "xxx@163.com", "password" => "xxx", "host" => "smtp.163.com"),
            array("email" => "xxx@qq.com", "password" => "xxx", "host" => "smtp.qq.com"),

        );
        if (!empty($email)) {
            $emailsArr = $email;
        }
        $emailNum = count($emailsArr);

        $emailIndex = rand(1, 1000) % $emailNum;

        $littlebossEmail = $emailsArr[$emailIndex]["email"];
        $littlebossEmailPW = $emailsArr[$emailIndex]["password"];
        $emailHost = $emailsArr[$emailIndex]["host"];

        $mail = new \PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->IsSMTP();                            // 经smtp发送
        $mail->Host = $emailHost;           // SMTP 服务器
        $mail->SMTPAuth = true;                     // 打开SMTP 认证
        $mail->Username = $littlebossEmail;    // 用户名
        $mail->Password = $littlebossEmailPW;// 密码
        $mail->From = $littlebossEmail;            // 发信人
        $mail->FromName = "小老板 ERP";        // 发信人别名
        if (is_array($sendto_email)) {
            foreach ($sendto_email as $oneMail) {
                $mail->AddAddress($oneMail);
            }
        } else {
            $mail->AddAddress($sendto_email); // 收信人
        }
        $mail->WordWrap = 50;
// 		$mail->IsHTML(true);                            // 以html方式发送
// 		$mail->AltBody  =  "请使用HTML方式查看邮件。";
        // 邮件主题
// 		$mail->Subject = $subject;//'littleboss verify code: '.$authnum;
        //如果标题有中文，用下面这行
        $mail->Subject = "=?utf-8?B?" . base64_encode($subject) . "?=";
        //邮件内容
        $mail->Body = $body;

        $sendResult = $mail->send();
        return $sendResult;
    }
    
    /**
     * 获取所有lazada用户的api访问信息。 email,token,销售站点
     */
    private static function getAllLazadaAccountInfoMap(){
    	$lazadauserMap=array();
    
    	$lazadaUsers=SaasLazadaUser::find()->all();
    	foreach($lazadaUsers as $lazadaUser){
    		$lazadauserMap[$lazadaUser->lazada_uid]=array(
    				"userId"=>$lazadaUser->platform_userid,
    				"apiKey"=>$lazadaUser->token,
    				"countryCode"=>$lazadaUser->lazada_site
    		);
    	}
    
    	return $lazadauserMap;
    
    }
    
    /**
     * 用户绑定的该平台账号的异常账号。异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败。
     * for dashbord 获取所有账号相关的报错信息
     * 
     */
    public static function getUserAccountRelatedErrorInfo($uid,$platform="lazada"){
    	$accountUnActive = [];//未开启同步的账号
    	$tokenExpired = [];//授权过期的账号
    	$order_retrieve_errors = [];//获取订单失败
    	$initial_order_failed = [];//首次绑定时，初始化获取订单失败
    	$listing_failed = [];//刊登失败
    	
    	$SLUs = SaasLazadaUser::find()->where(['puid'=>$uid,'platform'=>$platform])->andWhere('status<>3')->asArray()->all();
    	if(empty($SLUs))
    		return [];
    	
    	$nowTime = time();
    	foreach ($SLUs as $SLU){
    		if(empty($SLU['status'])){
    			$accountUnActive[] = $SLU;
    			continue;
    		}
    		
    		if(!empty($SLU['version'])&&$platform == 'lazada'){//新授权
    		    $authPromblems = 0;
    		    if($SLU['token_timeout'] <$nowTime )
    		        $authPromblems = 1;
    		    
    		}else{
    		    $authPromblems = SaasLazadaAutosync::find()->where(['lazada_uid'=>$SLU['lazada_uid'],'status'=>3])->andWhere('error_times >= 5')->andWhere('type != 4')->andWhere(['or like','message',['ErrorCode:7','ErrorCode:9']])->count();//拉产品可能超时
    		}
    		if($authPromblems > 0){
    			$tokenExpired[] = $SLU;
    			continue;
    		}
    		
    		// type 2,3 获取新订单，获取更新订单
    		if(!empty($SLU['version'])&&$platform == 'lazada'){//新授权
    		    $getOrderProblems = SaasLazadaAutosyncV2::find()->where(['lazada_uid'=>$SLU['lazada_uid'],'type'=>[2,3],'status'=>3])->andWhere('error_times >= 5')->count();
    		}else{
    		    $getOrderProblems = SaasLazadaAutosync::find()->where(['lazada_uid'=>$SLU['lazada_uid'],'type'=>[2,3],'status'=>3])->andWhere('error_times >= 5')->count();
    		}
    		if($getOrderProblems > 0){
    			$order_retrieve_errors[] = $SLU;
    			continue;
    		}
    		
    		//  type 1 获取旧订单 ，约等于初始化获取订单失败
    		if(!empty($SLU['version'])&&$platform == 'lazada'){//新授权
    		    $getOldOrderProblems = SaasLazadaAutosyncV2::find()->where(['lazada_uid'=>$SLU['lazada_uid'],'type'=>1,'status'=>3])->andWhere('error_times >= 5')->count();
    		}else{
    		    $getOldOrderProblems = SaasLazadaAutosync::find()->where(['lazada_uid'=>$SLU['lazada_uid'],'type'=>1,'status'=>3])->andWhere('error_times >= 5')->count();
    		}
    		if($getOrderProblems > 0){
    			$initial_order_failed[] = $SLU;
    			continue;
    		}
    		
    	}
    	
    	$problems=[
    	'unActive'=>$accountUnActive,
    	'token_expired'=>$tokenExpired,
    	'initial_failed'=>$initial_order_failed,
    	'order_retrieve_failed'=>$order_retrieve_errors,
//     	'listing_failed'=>$listing_failed,
    	];
    	return $problems;
    }
    
    // 设置lazada，linio，jumia用户订单拉取起始时间点 
    public static function setGetOrderEndTime($uids,$time,$platform="lazada"){
    	if(empty($uids))
    		return '';
    	
    	if (!is_numeric($time) ){
    		return '';
    	}
    	
    	$uidStr = '';
    	if(!is_array($uids)){
    		if( (string)$uids=='-1'){
    			$uidStr .= " 1 ";
    		}else {
    			$uidStr .= "`puid`=$uids";
    		}
    	}else{
    		$uidStr .= "`puid` in (" .implode(',', $uids). ")";
    	}
    	
    	$sql = "UPDATE `saas_lazada_autosync` SET `end_time`='$time' WHERE `lazada_uid` in ( select `lazada_uid` from `saas_lazada_user` where `platform`='$platform' and '$uidStr' )";
    	return $sql;
    }
    
    // 设置lazada用户订单拉取起始时间点
    public static function setLazadaGetOrderEndTime($uids,$time){
    	return self::setGetOrderEndTime($uids, $time , "lazada");
    }
    
    // 设置linio用户订单拉取起始时间点
    public static function setLinioGetOrderEndTime($uids,$time){
    	return self::setGetOrderEndTime($uids, $time , "linio");
    }
    
    // 设置jumia用户订单拉取起始时间点 
    public static function setJumiaGetOrderEndTime($uids,$time){
    	return self::setGetOrderEndTime($uids, $time , "jumia");
    }
    
    // 对存在特定字符，进行htmlentities 转换
    // lazada,linio 创建产品各个输入值一旦出现& 就会报Invalid Request Format ，但htmlentities之前，最好判断一下有没有 & 这类字符，不要盲目转换
    // 如果出现 字符串 包括了 "Señoras" 和 "&" 则没办法判断了 ，这是时候应该提示客户不能这样填
    // dzt20161107 使用htmlspecialchars 代替htmlentities 处理& ，不会导致 “°” , “Señoras” 之类的也被翻译出&出来
    public static function transformFeedString($str){
    	if(
    	        strpos($str, "&") !== false || 
    	        strpos($str, "<") !== false || 
    	        strpos($str, ">") !== false
	        ){
    		$str = htmlspecialchars($str);
    	}
    	return $str;
    }
    
    // jumia 特殊物流即时 上传订单 == jumia 标记发货
    public static function shipJumiaOrder(\eagle\modules\order\models\OdOrder $order) {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping("jumia");
        if (empty($codeNameMap[strtolower($order->order_source_site_id)]))
            return array(false, "站点" . $order->order_source_site_id . "不是 jumia的站点。");
    
        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => strtolower($order->order_source_site_id)]);
        if (empty($SLU)) {
            return array(false, $order->selleruserid . "站点:" . $codeNameMap[strtolower($order->order_source_site_id)] . " 账号不存在");
        }
    
        $OrderItemIds = array();
        $ignoreItems = array();
        foreach ($order->items as $item) {
            if(!empty($item->platform_status)
                    && !in_array($item->platform_status, LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){// dzt20161208 不标记不适合标记发货的item
                $ignoreItems[] = $item->order_source_order_item_id.'=>'.$item->platform_status;
            }else{
                $OrderItemIds[] = $item->order_source_order_item_id;
            }
        }
    
        // dzt20161208
        if(empty($OrderItemIds)){// 或者没有要上传产品的不报错直接当成已经上传成功？
            return array(false, "订单".$order->order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems));
        }
    
        $config = array(
                "userId" => $SLU->platform_userid,
                "apiKey" => $SLU->token,
                "countryCode" => $SLU->lazada_site
        );
    
        // 获取运输方式shipping_method_code
        $method = "";
        if (strlen($order->default_shipping_method_code) > 0) {
            $service = SysShippingService::findOne($order->default_shipping_method_code);
            $method = $service->shipping_method_code;
        }
    
        $appParams = array (
				'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
				'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
				'ShippingProvider' => $method, //
				'TrackingNumber' => "", // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
		);
		
		// 调用接口获取订单列表
		if(!empty($ignoreItems)){
		    \Yii::info("shipJumiaOrder uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id.",ignore items：".implode(',', $ignoreItems) , "file");
		}
    
        \Yii::info("shipJumiaOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ready to proxy.config:".json_encode($config).",appParams:".json_encode($appParams), "file");
        $result = LazadaInterface_Helper::shipOrder($config, $appParams);
        \Yii::info("shipJumiaOrder puid:".$SLU->puid.",orderid:".$order->order_id.",return from proxy.result:".json_encode($result), "file");
    
        $delivery_time = time();
        if ($result ['success'] && $result['response']['success'] == true) { // 成功
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "C";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
    
            return array(true, "");
        } else {
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "F";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
    
            return array(false, $result['message']);
        }
    }// end of LGS shipLazadaLgsOrder
    
    // jumia 上传订单后 获取生成的订单 package id 和 TrackingNumber
    public static function getJumiaPackageInfo(\eagle\modules\order\models\OdOrder $order)
    {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping("jumia");
        if (empty($codeNameMap[strtolower($order->order_source_site_id)]))
            return array(false, "站点" . $order->order_source_site_id . "不是 jumia的站点。");
    
        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => strtolower($order->order_source_site_id)]);
        if (empty($SLU)) {
            return array(false, $order->selleruserid . "站点:" . $codeNameMap[strtolower($order->order_source_site_id)] . " 账号不存在");
        }
    
        $config = array(
                "userId" => $SLU->platform_userid,
                "apiKey" => $SLU->token,
                "countryCode" => $SLU->lazada_site
        );
    
        $addiInfo = json_decode($order->addi_info,true);
        if(empty($addiInfo) || empty($addiInfo['lgs_related']) || empty($addiInfo['lgs_related']['OrderId']))
            return array(false, "订单：" . $order->order_source_site_id . " 原始信息丢失。");
    
        $apiParams = array(
                "OrderId" => $addiInfo['lgs_related']['OrderId']
        );
    
        \Yii::info("(".__FUNCTION__."),apiParams:" . json_encode($apiParams).",config:" . json_encode($config), "file");
        $itemsResult = LazadaInterface_Helper::getOrderItems($config, $apiParams);
        \Yii::info("(".__FUNCTION__."),itemsResult:" . json_encode($itemsResult), "file");
    
        if ($itemsResult ['success'] && $itemsResult['response']['success'] == true) { // 成功
            $itemsArr = $itemsResult["response"]["items"][$addiInfo['lgs_related']['OrderId']];
    
            // dzt20161021 如果一个item发货，一个item取消，这里获取的item有可能没有发货信息
            $hasShippingInfo = false;
            $TrackingCode = '';
            $PackageId = '';
            foreach ($itemsArr as $item){
                if($item["TrackingCode"]<>""){
                    $hasShippingInfo = true;
                    $TrackingCode = $item["TrackingCode"];
                    $PackageId = $item["PackageId"];
                    $ShipmentProvider = $item["ShipmentProvider"];
                    break;
                }
            }
            if($hasShippingInfo){
                return array(true, array('TrackingNumber' => $TrackingCode, 'PackageId' => $PackageId ,'ShipmentProvider' => $ShipmentProvider));
            }else{
                return array(false, 'Order has not any shipping info.');
            }
        } else {
            return array(false, $itemsResult['message']);
        }
    }// end of LGS getPackageInfo
    
    
    /*
     * 检查linio账号订 单图片是否使用配对SKU的商品库主图
    * @author	lzhl	2017-07-14
    */
    public static function checkIfLinioUserOmsUseProductImage($puid,$seller_id,$site_id){
    	$account = SaasLazadaUser::find()->where(['puid'=>$puid,'platform_userid'=>$seller_id,'lazada_site'=>$site_id,'platform'=>'linio'])->one();
    	if(empty($account))
    		return false;
    	$addi_info = empty($account->addi_info)?[]:json_decode($account->addi_info,true);
    	if(empty($addi_info['oms_use_product_image']))
    		return false;
    	else
    		return true;
    }
    
    /**
     * 清理创建时间为3个月前的listing 没有区分账号
     * 由于更新产品是先删后再插入，所以3个月前的产品是没有修改更新过的
     * @author	dzt	2017-11-01
     */
    public static function clearLazadaListingBeforeThreeMonth(){
        
        $clearTargets = SaasLazadaUser::find()->select('puid')->groupBy('puid')->asArray()->all();
        $clearTimeLimit = time() - 86400 * 30; 
        \Yii::info("clearLazadaListingBeforeThreeMonth targets:".count($clearTargets).",clearTimeLimit:".date("Y-m-d H:i:s", $clearTimeLimit),"file");
        
        foreach ($clearTargets as $clearTarget){
            $puid = $clearTarget['puid'];
            $deleteNum = LazadaListingV2::deleteAll('create_time<'.$clearTimeLimit);
            if(!empty($deleteNum))
                \Yii::info("clearLazadaListingBeforeThreeMonth puid:$puid ,clear listings:$deleteNum","file");
        }
    }
    
    // linio 上传订单后 获取生成的订单 package id 和 TrackingNumber
    public static function getLinioPackageInfo(\eagle\modules\order\models\OdOrder $order)
    {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping("linio");
        if (empty($codeNameMap[strtolower($order->order_source_site_id)]))
            return array(false, "站点" . $order->order_source_site_id . "不是 linio的站点。");
    
        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => strtolower($order->order_source_site_id)]);
        if (empty($SLU)) {
            return array(false, $order->selleruserid . "站点:" . $codeNameMap[strtolower($order->order_source_site_id)] . " 账号不存在");
        }
    
        $config = array(
            "userId" => $SLU->platform_userid,
            "apiKey" => $SLU->token,
            "countryCode" => $SLU->lazada_site
        );
    
        $addiInfo = json_decode($order->addi_info,true);
        if(empty($addiInfo) || empty($addiInfo['lgs_related']) || empty($addiInfo['lgs_related']['OrderId']))
            return array(false, "订单：" . $order->order_source_site_id . " 原始信息丢失。");
    
        $apiParams = array(
            "OrderId" => $addiInfo['lgs_related']['OrderId']
        );
    
        \Yii::info("(".__FUNCTION__."),apiParams:" . json_encode($apiParams).",config:" . json_encode($config), "file");
        $itemsResult = LazadaInterface_Helper::getOrderItems($config, $apiParams);
        \Yii::info("(".__FUNCTION__."),itemsResult:" . json_encode($itemsResult), "file");
    
        if ($itemsResult ['success'] && $itemsResult['response']['success'] == true) { // 成功
            $itemsArr = $itemsResult["response"]["items"][$addiInfo['lgs_related']['OrderId']];
    
            // dzt20161021 如果一个item发货，一个item取消，这里获取的item有可能没有发货信息
            $hasShippingInfo = false;
            $TrackingCode = '';
            $PackageId = '';
            foreach ($itemsArr as $item){
                if($item["TrackingCode"]<>""){
                    $hasShippingInfo = true;
                    $TrackingCode = $item["TrackingCode"];
                    $PackageId = $item["PackageId"];
                    $ShipmentProvider = $item["ShipmentProvider"];
                    break;
                }
            }
            if($hasShippingInfo){
                return array(true, array('TrackingNumber' => $TrackingCode, 'PackageId' => $PackageId ,'ShipmentProvider' => $ShipmentProvider));
            }else{
                return array(false, 'Order has not any shipping info.');
            }
        } else {
            return array(false, $itemsResult['message']);
        }
    }// end of LGS getPackageInfo
    
    // linio 特殊物流即时 上传订单 == linio 标记发货
    public static function shipLinioOrder(\eagle\modules\order\models\OdOrder $order) {
        $codeNameMap = self::getLazadaCountryCodeSiteMapping("linio");
        if (empty($codeNameMap[strtolower($order->order_source_site_id)]))
            return array(false, "站点" . $order->order_source_site_id . "不是 linio的站点。");
    
        $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => strtolower($order->order_source_site_id)]);
        if (empty($SLU)) {
            return array(false, $order->selleruserid . "站点:" . $codeNameMap[strtolower($order->order_source_site_id)] . " 账号不存在");
        }
    
        $OrderItemIds = array();
        $ignoreItems = array();
        foreach ($order->items as $item) {
            if(!empty($item->platform_status)
                && !in_array($item->platform_status, LazadaApiHelper::$CAN_SHIP_ORDERITEM_STATUS)){// dzt20161208 不标记不适合标记发货的item
                $ignoreItems[] = $item->order_source_order_item_id.'=>'.$item->platform_status;
            }else{
                $OrderItemIds[] = $item->order_source_order_item_id;
            }
        }
    
        // dzt20161208
        if(empty($OrderItemIds)){// 或者没有要上传产品的不报错直接当成已经上传成功？
            return array(false, "订单".$order->order_id." 没有可发货的订单item。忽略的item及其状态为：".implode(',', $ignoreItems));
        }
    
        $config = array(
            "userId" => $SLU->platform_userid,
            "apiKey" => $SLU->token,
            "countryCode" => $SLU->lazada_site
        );
    
        // 获取运输方式shipping_method_code
        $method = "";
        if (strlen($order->default_shipping_method_code) > 0) {
            $service = SysShippingService::findOne($order->default_shipping_method_code);
            $method = $service->shipping_method_code;
        }
    
        $appParams = array (
            'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
            'DeliveryType' => "dropship", // 目前不清楚其他类型的 DeliveryType，先hardcode 为dropship。  DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
            'ShippingProvider' => $method, //
            'TrackingNumber' => "", // 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
        );
    
        // 调用接口获取订单列表
        if(!empty($ignoreItems)){
            \Yii::info("shipLinioOrder uid:".$queueSyncShippedObj->uid.",order:".$queueSyncShippedObj->order_source_order_id.",ignore items：".implode(',', $ignoreItems) , "file");
        }
    
        \Yii::info("shipLinioOrder puid:".$SLU->puid.",orderid:".$order->order_id.",ready to proxy.config:".json_encode($config).",appParams:".json_encode($appParams), "file");
        $result = LazadaInterface_Helper::shipOrder($config, $appParams);
        \Yii::info("shipLinioOrder puid:".$SLU->puid.",orderid:".$order->order_id.",return from proxy.result:".json_encode($result), "file");
    
        $delivery_time = time();
        if ($result ['success'] && $result['response']['success'] == true) { // 成功
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "C";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
    
            return array(true, "");
        } else {
            // dzt20170609 添加 更新订单 虚拟发货 状态 start
            $syncShippedStatus = "F";
            $syncRT = OrderApiHelper::setOrderSyncShippedStatus($order->order_id, $syncShippedStatus, $delivery_time);
    
            return array(false, $result['message']);
        }
    }// end of LGS shipLazadaLgsOrder
    
}//end of class
?>