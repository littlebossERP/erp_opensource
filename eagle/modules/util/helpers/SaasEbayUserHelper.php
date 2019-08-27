<?php
namespace eagle\modules\util\helpers;

use eagle\models\SaasEbayUser;
/**
 * ebay user 表的 业务模型
 * @author lxqun
 * 2014-1-15
 */
class SaasEbayUserHelper
{


	/**
	 * 查找一个ebay用户
	 * @param array,int,string $param
	 * @return $M
	 * @author lxqun
	 * @date 2014-2-13
	 */
	public static function getOne($param){
//	 if(is_string($param)){
// 		$M=SaasEbayUser::findOne(['selleruserid'=>$param]);
	    $M=SaasEbayUser::findOne(['selleruserid'=>$param,'item_status'=>1]);
//	    $M=SaasEbayUser::find()->where('selleruserid=\''.$param.'\'')->one();
//	 }
// 	 elseif(is_int($param)){
// 	      $M=SaasEbayUser::find()->where('ebay_uid='.$param)->one();
// 	 }
	 
	 return $M;
	}

}
