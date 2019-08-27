<?php
namespace eagle\modules\carrier\openapi;

use eagle\components\OpenApi;
use eagle\modules\carrier\apihelpers\ApiHelper;

/**
 * 物流基础信息接口
 *
 * author: rice
 * date: 2015-08-17
 * version: 0.1
 */
class Base extends OpenApi {

    /*
     * 获取系统物流商信息
     *
     * author: rice
     * date: 2015-08-17
     * version: 0.1
     *
     * 输入参数: $v string 可选 默认值: 0.1 版本号
     *
     * 返回值(JSON):
     * 错误码：无
     *
     * data =>managedb库sys_carrier表
     *      key: 主键carrier_code
     *      value:carrier_name字段
        {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "lb_4px": "递四方",
                    "lb_4pxOversea": "递四方(海外仓)",
                    "lb_birdsysOversea": "飞鸟国际(海外仓)",
                    "lb_chukouyi": "出口易",
                    "lb_chukouyiOversea": "出口易(海外仓)",
                    "lb_CNE": "CNE",
                    "lb_epacket": "亚太平台 CNPOST",
                    "lb_IEUB": "国际E邮宝",
                    "lb_SF": "顺丰",
                    "lb_tiesanjiaoOverSea": "铁三角(海外仓)",
                    "lb_winit": "万邑通ISP",
                    "lb_winitOversea": "万邑通(海外仓)",
                    "lb_yanwen": "燕文",
                    "lb_yilong": "颐龙"
                }
            }
        }
     */
    public function getCarriers($v = '0.1') {
        $result = ApiHelper::getCarriers();
        echo $this->output($result);
        exit;
    }


    /*
     * 获取用户的运输服务信息
     *
     * author: rice
     * date: 2015-08-17
     * version: 0.1
     *
     * 输入参数: $v string 可选 默认值: 0.1 版本号
     *
     * 返回值(JSON):
     * 错误码：无
     *
     * data =>user库sys_shipping_service表
     *      key: 主键id
     *      value:service_name字段
        {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "1": "递四方-4px测试账号-DHL出口",
                    "2": "递四方",
                    "3": "递四方-4px测试账号-4PX专线ARMX",
                    "4": "递四方-4px测试账号-4PX联邮通挂号",
                    "6": "递四方-4px测试账号-新加坡小包挂号",
                    "47": "出口易-ck1测试账号-中邮大包",
                    "55": "出口易-ck1测试账号-上海本地中邮挂号",
                }
            }
        }
     */
    public function getShippingServices($v = '0.1', $all = false) {
        $result = ApiHelper::getShippingServices($all);
        echo $this->output($result);
        exit;
    }


    /*
     * 获取用户的物流账号信息
     *
     * author: rice
     * date: 2015-08-17
     * version: 0.1
     *
     * 输入参数: $v string 可选 默认值: 0.1 版本号
     *
     * 返回值(JSON):
     * 错误码：无
     *
     * data =>user库sys_carrier_account表
     *      key: 主键id
     *      value:carrier_name字段
        {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "1": "4px测试账号",
                    "2": "ck1测试账号",
                    "3": "4px海外仓测试账号",
                    "4": "yw测试账号",
                    "5": "yl测试账号",
                    "6": "CNE测试账号",
                }
            }
        }
     */
    public function getCarrierAccounts($v = '0.1') {
        $result = ApiHelper::getCarrierAccounts();
        echo $this->output($result);
        exit;
    }


    /*
     * 获取海外仓库信息，账号及仓库信息
     *
     * author: rice
     * date: 2015-08-17
     * version: 0.1
     *
     * 输入参数: $v string 可选 默认值: 0.1 版本号
     *
     * 返回值(JSON):
     * 错误码：无
     *
     * data =>web/docs/海外仓配置文件
     *      key: user库sys_carrier_account表主键id
        {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "3": {
                        "account_name": "4px海外仓测试账号",
                        "warehouse": {
                            "USLA": "美国洛杉矶仓",
                            "DEWH": "德国仓",
                            "AUSY": "澳洲仓",
                            "UKLH": "英国仓"
                        }
                    },
                    "8": {
                        "account_name": "飞鸟测试账号",
                        "warehouse": {
                            "1": "UK1 英国一站",
                            "10": "AU 澳洲站",
                            "11": "CN 深圳站",
                            "12": "DE 德国站",
                            "14": "GI 直布罗陀站"
                        }
                    },
                    "9": {
                        "account_name": "铁三角测试账号",
                        "warehouse": {
                            "YORK": "东部PA仓",
                            "GUANGZHOU": "铁三角广州仓",
                            "ZZ": "郑州仓",
                            "SHANGHAI": "上海仓"
                        }
                    }
                }
            }
        }
     */
    public function getWerehouseAccounts($v = '0.1') {
        $result = ApiHelper::getWerehouseAccounts();
        echo $this->output($result);
        exit;
    }
}