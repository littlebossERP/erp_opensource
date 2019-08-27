<?php

namespace eagle\modules\order\helpers;

use \Yii;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\manual_sync\models\Queue;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;


class LazadaOrderHelper{
	public static $amzapiVer = '2_2';

	static public function getCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
		if (isset($OpList['givefeedback'])) {
			unset($OpList['givefeedback']);//去掉“给买家好评”
		}
		$temp = [ 
// 		        'signshipped'=>'虚拟发货(标记发货)'
		];
		//把“虚拟发货”放到第一位
		self::array_insert($OpList,1,$temp);
		//var_dump($OpList);
		switch ($code){
			case OdOrder::STATUS_PAY:
				break;
			case OdOrder::STATUS_WAITSEND:
				$OpList+=['invoiced'=>'发票'];
				break;
			case OdOrder::STATUS_SHIPPED:
				if (isset($OpList['signcomplete'])) {
					unset($OpList['signcomplete']);//去掉“已出库订单补发”
				}
				if (isset($OpList['checkorder'])) {
					unset($OpList['checkorder']);//去掉“检测订单”
				}
				$OpList+=['invoiced'=>'发票'];
				
				break;

		}
		if ($type =='b') {
			switch ($code) {
				case OdOrder::STATUS_PAY:
// 					if (isset($OpList['checkorder'])) {
// 						unset($OpList['checkorder']);//去掉“检测订单”
// 					}
					break;
				case OdOrder::STATUS_SHIPPED:
					break;
				default:
					$OpList += [ 'checkorder'=>'检测订单'];
					break;
			}
			
			$OpList += ['updateImage' => '更新图片缓存'];
		}
		if ($type =='s'){
			$OpList += ['invoiced' => '发票'];
			$OpList += ['updateImage' => '更新图片缓存'];
			$OpList += ['updateShipping' => '更新平台物流服务'];
		}
		
		$tmp_is_show = true;
		if($code == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		if($tmp_is_show == false){
			unset($OpList['checkorder']);
		}
		
		//var_dump($OpList);
		return $OpList;
	}//end getAmazonCurrentOperationList

	/**
	 * [array_insert 插入到数组指定位置]
	 * @Author   willage
	 * @DateTime 2016-07-15T19:04:36+0800
	 * @param    [type]                   &$array       [description]
	 * @param    [type]                   $position     [description]
	 * @param    [type]                   $insert_array [description]
	 * @return   [type]                                 [description]
	 */
    static public function array_insert (&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }
    
    
    /**
     * 根据OMS过滤出来的账号email 查找对应email的账号的 store name
     * 
     * dzt20170522 
     */
    public static function getAccountStoreNameMapByEmail($emails){
        $slus = SaasLazadaUser::find()
        ->where(['puid'=>\Yii::$app->user->identity->getParentUid()])
        ->select(['platform_userid','lazada_site','store_name'])
        ->andwhere(['platform_userid'=>$emails])
        ->andwhere(['platform'=>'lazada'])
        ->andWhere("status <> 3")
        ->asArray()->all();
        
        $lazadaUsersDropdownList = array();
        $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
        
        foreach ($slus as $lazadaUser){// placeholder '_XX_' in case email contains '_'
            if(isset(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$lazadaUser['lazada_site']])){
                $serchKey =  $lazadaUser['platform_userid']."_@@_".LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$lazadaUser['lazada_site']];
            }else{
                $newArray = ['id'=>'ID','th'=>'TH'];
                $serchKey =  $lazadaUser['platform_userid']."_@@_".$newArray[$lazadaUser['lazada_site']];
            }
            $searchLabel = empty($lazadaUser['store_name'])?$lazadaUser['platform_userid']:$lazadaUser['store_name'];
            $searchLabel .= "(".$siteIdNameMap[$lazadaUser['lazada_site']].")"; // 发现许多客户的相同邮箱的不同站点的store_name一样。。。
            $lazadaUsersDropdownList[$serchKey] = $searchLabel;
        }
        
        empty($lazadaUsersDropdownList)?$lazadaUsersDropdownList = $emails:"";
        
        return $lazadaUsersDropdownList;
    }
    

}//end class


?>