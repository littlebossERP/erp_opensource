<?php
namespace eagle\modules\listing\apihelpers;
use yii\helpers\Json;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
/**
 * @author witsionjs
 *其他模块调用刊登模块Item的api
 */
class EbayItemApiHelper{
	
	/**
	 * @param $seller 卖家的ebay账号
	 * @param $page 查询的页码,默认1
	 * @param $pagesize 每页的查询数据量，默认100
	 * @param $orderby 查询的排序字段:默认为刊登时间(starttime:开始时间,endtime:下架时间)
	 * @param $sort 排序方式升序，降序默认为降序(asc,desc)
	 * 
	 * @return [
	 * 		'ack'=>'failure';failure:请求失败;success:请求成功
	 * 		'errormessage'=>'';请求失败时返回的失败原因
	 * 		'data'=>成功情况下的数据
	 * 		'pagesize'=>每页的查询数据量
	 * 		'page'=>查询的页码
	 * 		'resultcount'=>结果集总数
	 * ]
	 * 
	 * 通过传入的ebay卖家账号获取该卖家账号所对应的item
	 * @author fanjs
	 */
	public static function getEbayItemBySellerID($seller,$page=1,$pagesize=100,$orderby='starttime',$sort='DESC'){
		//传入的ebay账号不能为空
		if (strlen($seller)==0||empty($seller)){
			return Json::encode(['ack'=>'failure','errormessage'=>'input seller is null']);
		}
		//传入的账号在db中无法查找时
		$user = SaasEbayUser::findOne(['selleruserid'=>$seller]);
		if (empty($user)||is_null($user)){
			return Json::encode(['ack'=>'failure','errormessage'=>'sellerID has not found']);
		}
		//进行data的检索，整理结果集总数
		$count = EbayItem::find()->where(['selleruserid'=>$seller,'listingstatus'=>'Active'])->count();
		$data = EbayItem::find()->where(['selleruserid'=>$seller,'listingstatus'=>'Active']);
		//如果有请求分页，处理分页逻辑
		$data = $data->orderBy([$orderby=>$sort])->offset(($page-1)*$pagesize)->limit($pagesize)->with('detail')->asArray()->all();
		//数据拼接返回
		$result = [
			'ack'=>'success',
			'pagesize'=>$pagesize,
			'page'=>$page,
			'resultcount'=>$count,
			'data'=>$data
		];
		return Json::encode($result);
	}


	public static function getEbayUsersList(){
		$connection = \Yii::$app->db;
		$rows = $connection->createCommand('SELECT ebay_uid,uid  FROM `saas_ebay_user` where item_status !=0')->queryAll();
		// $puidArr=array();
		// foreach($rows as $row){
		// 	$puidArr[]=$row["puid"];
		// }
		return $rows;
	}

	public static function getEbayActiveUsersList(){
		$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(5*24);
		$ebayUserArr=self::getEbayUsersList();
		$activeEbayUidArr=array();

		foreach ($ebayUserArr as $valArr) {
			if (in_array($valArr['uid'],$activeUsersPuidArr)){
				$activeEbayUidArr[]=$valArr['ebay_uid'];
			}
		}
		return $activeEbayUidArr;
	}


}//end class