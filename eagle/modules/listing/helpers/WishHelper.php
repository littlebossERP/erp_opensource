<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */
namespace eagle\modules\listing\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasWishUser;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\platform\helpers\WishAccountsV2Helper;
use yii\helpers\StringHelper;
use yii\db\mssql\PDO;

use eagle\modules\manual_sync\models\Queue;

use eagle\modules\listing\helpers\ApiHelper;
use eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use eagle\modules\listing\service\wish\Product;

use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenLog;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishOrder;
use eagle\modules\listing\models\SyncProductApiQueue;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\models\WishOrderDetail;

/**
 * 
 +------------------------------------------------------------------------------
 * 刊登模块模板业务
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		fanjs
 +------------------------------------------------------------------------------
 */
class WishHelper {


    static $apiUrl = 'http://198.11.178.150/Wish_Proxy_Server/v3.php';

    public static $wish_status = [
        'posting' => 7,
        'online' => 8,
        'approved' => 8,
        'rejected' => 9,
        'pending' => 7
    ];

	public static function getAllWishFanBenStatus(){
		$status = [
			"editing"=> "编辑中", 
			"posting"=> '刊登中',
			"complete" => '刊登完成',
			"online"=>'在线刊登',
			"error"=>'刊登失败',
		];
		return $status;
	}

	//获取wish商品刊登状态
	public static function getWishFanBenStatus(){
		$status = array(
		"waiting" 	=> "待发布",
		"checking"	=> '审核中',
		"fail" 		=> '刊登失败',
		"online" 	=> '在线商品',
		"offline"	=> '下架商品'
		);
		return $status;
	}

	public static function getMenu(){
		return [
			'刊登管理'=>[
				'icon'=>'icon-shezhi',
				'items'=>[
					'待发布'=>[
						'url'=>'/listing/wish/wish-list',
						'tabbar'=>self::getTabbar([
							'type'=>2,
							'lb_status'=>1
						]),
					],
					'发布中'=>[
						'url'=>'/listing/wish/wish-list?type=3',
						'tabbar'=>self::getTabbar([
							'type'=>3
						]),
					],
					'刊登失败'=>[
						'url'=>'/listing/wish/wish-list?type=2&lb_status=4',
						'tabbar'=>self::getTabbar([
							'type'=>2,
							'lb_status'=>4
						]),
					],
				]
			],
			'商品列表'=>[
	            'icon'=>'icon-pingtairizhi',
	            'items'=>[
	                'Wish平台商品'=>[
	                    'url'=>'/listing/wish-online/wish-product-list',
	                ],
	            ]
	        ],
		];
	}

	/**
	 * tabbar数字计算
	 * @param  array  $where [description]
	 * @return [type]        [description]
	 */
	private static function getTabbar($where=[]){
		return WishFanben::find()->where($where)->count();
	}


	public static function getWishShippingMethodMapping(){
		$ShipMapping  = [
				'AUNAP'=>'AustraliaPost',
				'AUNIP'=>'AustraliaPost',
				'AURAP'=>'AustraliaPost',
				'AUREP'=>'AustraliaPost',
				'AURIP'=>'AustraliaPost',
				'AURLP'=>'AustraliaPost',
				'AURLT'=>'AustraliaPost',
				'AURPP'=>'AustraliaPost',
				'AURSP'=>'AustraliaPost',
				'au_post_eParcel'=>'AustraliaPost',
				'au_post_express'=>'AustraliaPost',
				'au_post_large_letter_no_registration'=>'AustraliaPost',
				'au_post_large_letter_with_registration'=>'AustraliaPost',
				'au_post_parcel_post_no_registration'=>'AustraliaPost',
				'au_post_parcel_post_with_registration'=>'AustraliaPost',
				'au_post_small_letter_no_registration'=>'AustraliaPost',
				'au_post_small_letter_with_registration'=>'AustraliaPost',
				'BEO'=>'Belpost',
				'BEP'=>'Belpost',
				'BGD'=>'Belpost',
				'CAF'=>'Chukou1',
				'CAN'=>'Chukou1',
				'CAP'=>'ChinaAirPost',
				'CAT'=>'Chukou1',
				'CEE'=>'Chukou1',
				'CEF'=>'Chukou1',
				'CEN'=>'Chukou1',
				'CET'=>'Chukou1',
				'CFE'=>'Chukou1',
				'CGN'=>'Chukou1',
				'CGT'=>'Chukou1',
				'CHINA EMS'=>'EMS',
				'CIE'=>'Chukou1',
				'CJP'=>'Chukou1',
				'CLS'=>'ChinaAirPost',
				'CLY'=>'ChinaAirPost',
				'CLZ'=>'ChinaAirPost',
				'CND'=>'DHL',
				'CNE'=>'Chukou1',
				'CNI'=>'ChinaAirPost',
				'CNPOST'=>'ChinaAirPost',
				'CRA'=>'ChinaAirPost',
				'CRB'=>'ChinaAirPost',
				'CRE'=>'RussianPost',
				'CRI'=>'ChinaAirPost',
				'CRN'=>'ChinaAirPost',
				'CRP'=>'ChinaAirPost',
				'CRS'=>'Chukou1',
				'CRU'=>'Chukou1',
				'CUE'=>'Chukou1',
				'CUN'=>'Chukou1',
				'CUT'=>'Chukou1',
				'DENDE'=>'DeutschePost',
				'DENDS'=>'DeutschePost',
				'DENID'=>'DeutschePost',
				'DERDS'=>'DeutschePost',
				'DERID'=>'DeutschePost',
				'DERIS'=>'DeutschePost',
				'DERIT'=>'DeutschePost',
				'DERLS'=>'DeutschePost',
				'DERLT'=>'DeutschePost',
				'DGM'=>'DeutschePost',
				'dgm_expedited_service'=>'Chukou1',
				'dgm_ground_service'=>'Chukou1',
				'DHL'=>'DHL',
				'domestic_parcel_tracked_1000148'=>'BPost',
				'domestic_parcel_tracked_1000149'=>'BPost',
				'dpd_domestic_normal_parcels_1000211'=>'DPDUK',
				'dpd_domestic_small_parcels_1000212'=>'DPDUK',
				'dpd_international_parcels_1000213'=>'DPDUK',
				'dsa_large_letter_untracked_service'=>'BPost',
				'dsa_small_letter_untracked_service'=>'BPost',
				'EMI'=>'EMS',
				'EMP'=>'EMS',
				'EMS'=>'EMS',
				'ESRIS'=>'EMS',
				'ESRLI'=>'Chukou1',
				'ESRLM'=>'Chukou1',
				'ESRLP'=>'Chukou1',
				'EUB'=>'ChinaAirPost',
				'EUF'=>'ChinaAirPost',
				'EUI'=>'ChinaAirPost',
				'EUU'=>'USPS',
				'FEDEX '=>'FedEx',
				'HBM'=>'HongKongPost',
				'HK DHL'=>'DHL',
				'HK EMS'=>'EMS',
				'HKE'=>'EMS',
				'HKFEDIE'=>'FedEx',
				'HKFEDIP'=>'FedEx',
				'HNXBGH'=>'ChinaAirPost',
				'HTM'=>'HongKongPost',
				'international_parcels_tracked'=>'BPostInternational',
				'international_parcels_untracked_flats'=>'BPostInternational',
				'international_parcels_untracked_letters'=>'BPostInternational',
				'international_parcels_untracked_Packets'=>'BPostInternational',
				'LARLP'=>'AsendiaUSA',
				'LARLS'=>'AsendiaUSA',
				'LARPP'=>'AsendiaUSA',
				'LARSS'=>'AsendiaUSA',
				'LYT GH'=>'PX4',
				'MEP'=>'Aramex',
				'MORLP'=>'RussianPost',
				'NJFRE'=>'AsendiaUSA',
				'NJNIU'=>'AsendiaUSA',
				'NJNLE'=>'AsendiaUSA',
				'NJNLL'=>'AsendiaUSA',
				'NJNUS'=>'AsendiaUSA',
				'NJRIS'=>'AsendiaUSA',
				'NJRLE'=>'AsendiaUSA',
				'NJRLP'=>'AsendiaUSA',
				'NJRLS'=>'AsendiaUSA',
				'NJRPP'=>'AsendiaUSA',
				'NJRSS'=>'AsendiaUSA',
				'NJRUS'=>'AsendiaUSA',
				'NLR'=>'PostNL',
				'notracked_europe_service_1000229'=>'DeutschePost',
				'notracked_large_letter_1000228'=>'DeutschePost',
				'notracked_large_letter_1000230'=>'BPost',
				'notracked_small_letter_1000231'=>'BPost',
				'notracked_small_letter_1000308'=>'DeutschePost',
				'RIRIS'=>'RussianPost',
				'royal_mail_1_class_tracked'=>'RoyalMail',
				'royal_mail_1_class_tracked_signed'=>'RoyalMail',
				'royal_mail_2_class_tracked'=>'RoyalMail',
				'royal_mail_2_class_tracked_signed'=>'RoyalMail',
				'SAP'=>'Chukou1',
				'SGO'=>'SingaporePost',
				'SGP'=>'SingaporePost',
				'SHANGH  IM'=>'ChinaAirPost',
				'Singapo SPack IMAIR'=>'SingaporePost',
				'TNT'=>'TNT',
				'TNT'=>'TNT',
				'toll_iepc'=>'TollGlobalExpress',
				'toll_priority'=>'TollPriority',
				'TONLE'=>'CanadaPost',
				'TORLE'=>'CanadaPost',
				'TORLP'=>'CanadaPost',
				'TORLS'=>'CanadaPost',
				'TORPE'=>'CanadaPost',
				'UEE'=>'AsendiaUSA',
				'UKNIR'=>'RoyalMail',
				'UKNR2'=>'RoyalMail',
				'UKNRM'=>'RoyalMail',
				'UKNRT'=>'RoyalMail',
				'UKPOD'=>'RoyalMail',
				'UKRIO'=>'RoyalMail',
				'UKRIP'=>'RoyalMail',
				'UKRIR'=>'RoyalMail',
				'UKRIS'=>'RoyalMail',
				'UKRLE'=>'RoyalMail',
				'UKRLF'=>'RoyalMail',
				'UKRLH'=>'RoyalMail',
				'UKRLO'=>'RoyalMail',
				'UKRLS'=>'RoyalMail',
				'UKRLT'=>'RoyalMail',
				'UKRNX'=>'RoyalMail',
				'UKRR2'=>'RoyalMail',
				'UKRRM'=>'RoyalMail',
				'UPS'=>'UPS',
				'UPS Export HK'=>'UPS',
				'ups_3_day_select_residential_service'=>'UPS',
				'ups_ground_service'=>'UPS',
				'ups_next_day_air_saver_service'=>'UPS',
				'ups_surepost_service'=>'UPS',
				'USFRE'=>'AsendiaUSA',
				'USNIU'=>'AsendiaUSA',
				'USNLE'=>'AsendiaUSA',
				'USNLL'=>'AsendiaUSA',
				'USNUS'=>'AsendiaUSA',
				'usps_first_class_mail_tracked_service'=>'USPS',
				'usps_priority_mail_parcels_tracked_service'=>'USPS',
				'USRIS'=>'AsendiaUSA',
				'USRLE'=>'AsendiaUSA',
				'USRLP'=>'AsendiaUSA',
				'USRLS'=>'AsendiaUSA',
				'USRPP'=>'AsendiaUSA',
				'USRUS'=>'AsendiaUSA',
				'yanwen_101'=>'Belpost',
				'yanwen_102'=>'Belpost',
				'yanwen_103'=>'DeutschePost',
				'yanwen_104'=>'DeutschePost',
				'yanwen_105'=>'ChinaAirPost',
				'yanwen_106'=>'SingaporePost',
				'yanwen_107'=>'HongKongPost',
				'yanwen_112'=>'ChinaAirPost',
				'yanwen_113'=>'ChinaAirPost',
				'yanwen_118'=>'EMS',
				'yanwen_12'=>'ChinaAirPost',
				'yanwen_120'=>'ChinaAirPost',
				'yanwen_121'=>'ChinaAirPost',
				'yanwen_122'=>'EMS',
				'yanwen_131'=>'RoyalMail',
				'yanwen_132'=>'RoyalMail',
				'yanwen_133'=>'RoyalMail',
				'yanwen_134'=>'RoyalMail',
				'yanwen_135'=>'RoyalMail',
				'yanwen_136'=>'RoyalMail',
				'yanwen_137'=>'RoyalMail',
				'yanwen_138'=>'RoyalMail',
				'yanwen_139'=>'FedEx',
				'yanwen_14'=>'SingaporePost',
				'yanwen_140'=>'Yanwen',
				'yanwen_141'=>'Yanwen',
				'yanwen_143'=>'ChinaAirPost',
				'yanwen_144'=>'PostNL',
				'yanwen_145'=>'FedEx',
				'yanwen_146'=>'SwissPost',
				'yanwen_147'=>'SwissPost',
				'yanwen_148'=>'SwissPost',
				'yanwen_149'=>'SwissPost',
				'yanwen_150'=>'SwissPost',
				'yanwen_151'=>'SwissPost',
				'yanwen_152'=>'SwedenPosten',
				'yanwen_153'=>'SwedenPosten',
				'yanwen_154'=>'ChinaAirPost',
				'yanwen_155'=>'ChinaAirPost',
				'yanwen_156'=>'ChinaAirPost',
				'yanwen_158'=>'ChinaAirPost',
				'yanwen_159'=>'Yanwen',
				'yanwen_160'=>'MalaysiaPost',
				'yanwen_161'=>'MalaysiaPost',
				'yanwen_163'=>'ChinaAirPost',
				'yanwen_164'=>'ChinaAirPost',
				'yanwen_165'=>'ChinaAirPost',
				'yanwen_166'=>'ChinaAirPost',
				'yanwen_167'=>'ChinaAirPost',
				'yanwen_168'=>'ChinaAirPost',
				'yanwen_169'=>'ChinaAirPost',
				'yanwen_170'=>'ChinaAirPost',
				'yanwen_171'=>'EMS',
				'yanwen_172'=>'ChinaAirPost',
				'yanwen_173'=>'EMS',
				'yanwen_174'=>'PostNL',
				'yanwen_175'=>'LithuaniaPost',
				'yanwen_176'=>'LithuaniaPost',
				'yanwen_177'=>'LithuaniaPost',
				'yanwen_178'=>'LithuaniaPost',
				'yanwen_179'=>'SwedenPosten',
				'yanwen_180'=>'SwedenPosten',
				'yanwen_24'=>'HongKongPost',
				'yanwen_25'=>'Yanwen',
				'yanwen_3'=>'EMS',
				'yanwen_30'=>'EMS',
				'yanwen_31'=>'YODEL',
				'yanwen_32'=>'YODEL',
				'yanwen_34'=>'Yanwen',
				'yanwen_36'=>'Yanwen',
				'yanwen_45'=>'DHL',
				'yanwen_5'=>'DHL',
				'yanwen_6'=>'UPS',
				'yanwen_7'=>'TNT',
		];
		return $ShipMapping;
	}//end of getWishShippingMethodMapping

	public static function getWishShipingMethodByMethodCode($methodCode){
		$mapping = self::getWishShippingMethodMapping();
		if (empty($mapping[$methodCode])){
			return ['success'=>false , 'message'=>'none of mapping by '.$methodCode , 'shipping_method'=>''];
		}else{
			return ['success'=>true , 'message'=>'', 'shipping_method'=>$mapping[$methodCode]];
		}
	}//end of getWishShipingMethodByMethodCode


	public static function getAllWishFanBenStatusComboBox(){
		$statuses = self::getAllWishFanBenStatus();
		$comboBox = [];
		foreach ($statuses as $status_code => $status_name){
			$aStatus = [];
			$aStatus['status_code'] = $status_code;
			$aStatus['status_name'] = $status_name;
			$comboBox [] = $aStatus;
		}
		return $comboBox;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get Wish Fan Ben Detai lData
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id						Wish Fan Ben record id
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			array of sql query all with all info
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getFanBenDetailData($id){
	
		$WishFanbenModel = WishFanben::find()->andWhere("id=:id",[":id"=>$id])->one();
		if ($WishFanbenModel<>null) {
			//TODO: HUB provides a method to get user name by user id
			$WishFanbenModel->capture_user_id = "yansen";
			$WishFanbenModelArr = $WishFanbenModel->getAttributes();
			$WishFanbenModelArr['variance'] = WishFanbenVariance::find()->andWhere("fanben_id=$id")->all();
		}else
			$WishFanbenModelArr = [];
		return $WishFanbenModelArr;
	}

/**
* @param $str
* @return {"success":true,"message":"","proxyResponse":{"msg":"","code":0,"data":{"tags":[{"tag":"Women's Fashion"},{"tag":"Sunglasses"},{"tag":"Men's Fashion"},{"tag":"Sport"},{"tag":"Swimsuit"},{"tag":"Swimming"},{"tag":"Swimwear"},{"tag":"sexy"},{"tag":"silver"},{"tag":"Shirt"}]}}}
 */
    public static function getTagInfo($str){
        $url = 'https://www.merchant.wish.com/api/contest-tag/search';
        try {
            $rtn['success'] = true;
            $rtn['message'] = '';

            $handle = curl_init($url);
            curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($handle, CURLOPT_TIMEOUT, 3);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(["q"=>$str] ) );

            /* Get the HTML or whatever is linked in $url. */
            $response = curl_exec($handle);
            $curl_errno = curl_errno($handle);
            $curl_error = curl_error($handle);
            if ($curl_errno > 0) { // network error
                $rtn['message']="cURL Error $curl_errno : $curl_error";
                $rtn['success'] = false ;
                $rtn['proxyResponse'] = "";
                curl_close($handle);
                return $rtn;
            }
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($httpCode <> '200' ){ //retry now
                $response = curl_exec($handle);
                $curl_errno = curl_errno($handle);
                $curl_error = curl_error($handle);
                if ($curl_errno > 0) { // network error
                    $rtn['message']="cURL Error $curl_errno : $curl_error";
                    $rtn['success'] = false ;
                    $rtn['proxyResponse'] = "";
                    curl_close($handle);
                    return $rtn;
                }
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            }

            if ($httpCode == '200' ){

                $rtn['proxyResponse'] = json_decode($response , true);

                if ($rtn['proxyResponse']==null){
                    $rtn['message'] = "content return from proxy is not in json format, content:".$response;
                    $rtn['success'] = false ;
                }else{
                    $rtn['message'] = "";
                }

            }else{ // network error
                $rtn['message'] = "Failed, Got error respond code $httpCode ";
                $rtn['success'] = false ;
                $rtn['proxyResponse'] = "";
            }

            curl_close($handle);

        } catch (Exception $e) {
            $rtn['success'] = false;  //跟proxy之间的网站是否ok
            $rtn['message'] = $e->getMessage();
            echo "WishProxyConnectHelper exception for ".$rtn['message']."\n";
            curl_close($handle);
        }

        return $rtn;

    }
	 

	/**
	 +----------------------------------------------------------
	 * 获取wish范本列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		condition，例如 array(keyword=>'good',date_from=>'',date_to='')
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				Wish范本数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	**/
	public static function getFanbenListData( $params=[],  $sort='' , $order='' , $pageSize = 30) {
		
		
		
		
		if(empty($sort)){
			$sort = 'create_time';
			$order = 'desc';
		}
		
		$filterStr = ' 1 ';
		if(!empty($params)) {
			foreach($params as $k => $v) {
				//take off the ' and "
				$v = str_replace("'",'',$v);
				$v = str_replace('"','',$v);
				
			// 	if ($k=='keyword'){
			// 		$filterStr .= " and (name like '%$v%' or parent_sku like '%$v%' or tags like '%$v%' or  upc like '%$v%' ".
			// 		" or exists(select fanben_id from wish_fanben_variance v  where v.fanben_id = wish_fanben.id and v.sku like '%$v%') )";
			// 	}elseif($k=='date_from') {
			// 		$filterStr .= " and create_time >= '$v'";
			// 	}elseif($k=='date_to') {
			// 		$filterStr .= " and create_time <= '$v'";
			// 	}else{
			// 		$filterStr .= " and $k = '$v'";
			// 	}
			// }

				if($k == 'keyword'){
					$filterStr .= "and (name  like '%$v%' or parent_sku like '%$v%')";
				}else{
					if($k == 'parent_sku' || $k == 'name'){
						$filterStr .= " and $k like '%$v%'";	
					}else{
						$filterStr .= " and $k = '$v'";
					}
				}
			}
		}
		// var_dump($filterStr);	
		
		$data ['condition'] = $filterStr;
		$query = WishFanben::find();
		$pagination = new Pagination([
			'defaultPageSize' => 100,
			'pageSize' => $pageSize,
			'totalCount' => $query->where($filterStr)->count(),
			'pageSizeLimit' =>[5,20,100,200], //每页显示条数范围
			'params'=>$_REQUEST,
		]);
	
		$data['pagination'] = $pagination;
		$data['data'] = $query
		->andWhere($filterStr)
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy("$sort $order")
		->asArray()
		->all();
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		
		// 调试sql
		/*
		 $tmpCommand = $query->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
		*/
		//Load 每个范本的variance
		foreach ($data['data'] as &$aFanben){
			$aFanben['variance_data'] = WishFanbenVariance::find()
			->andWhere([ 'fanben_id'=>$aFanben['id'] ])
			->orderBy(" sku asc ")
			->asArray()
			->all();;
		}
		 
		return $data;
 
	}
	
	/**
	 * 订单信息列表
	 */
	static public function getListDataByCondition( $sort , $order )
	{	$query = WishFanben::find();
	
		// Pagination 插件自动获取url 上page和per-page（Pagination默认参数，可以修改）这两个name的参数
		// 然后获得$pagination->limit 即 $pageSizet 和 $pagination->offse 即 $page 校正过后的值。
		// 为配合分页功能，请尽量不要自己定义 “每页多少行()” 和 “第几页” 这两参数。
		// 如果硬是自己定义了,就在Pagination初始化时覆盖'pageParam'和'pageSizeParam'为你使用的参数也可。否则分页功能生成的链接会出现异常。
		$pagination = new Pagination([
				'defaultPageSize' => 30,
				'totalCount' => $query->count(),
				//'pageParam'=>'page',
				//'pageSizeParam'=>'pageSize',
				]);
	
		$data['pagination'] = $pagination;
	
		if(empty($sort)){
			$sort = 'create_time';
			$order = 'desc';
		}
	
		$data['data'] = $query
		// 		->orderBy('pc_purchase.')
		//->joinWith(['ordPay' => function ($query) {}])
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
	/*
		$statusIdLabelMap = self::getStatusIdLabelMap();
		$data['purchaseStatusMap'] = $statusIdLabelMap;
		foreach ($data['data'] as $key => $val) {
			$data['data'][$key]['status']=$statusIdLabelMap[$val['status']];
		}
		*/
			return $data;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Wish Fan Ben records
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 *                                  data[variance] = array( array('sku'=>'mx4white','inventory'=100),
	 *                                                          array('sku'=>'mx4black','inventory'=50),
	 *                                                        )
	 *                                  当从listing界面修改数量等variance信息，提交上来的时候，data里面只需要有 fanben_id,
	 *                                  其他name之类不需要。
	 *                                  data[variance]是改fanben下面的variance信息，这个必须有的。
	 * @param varianceOnly              default False. update both variance and product info
	 *                                  When it is true, update only variance.                                 
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * 						['fanben_id'] = Wish Fan Ben Id created/mofified
	 * This is to insert Wish Fan Ben headers and also the details
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveFanBen($data,$varianceOnly = false){
		try {
			$rtn['message']="";
			$rtn['success'] = true;
			$now_str = GetControlData::getNowDateTime_str();
			$varianceEditMethod = 'all';
		
			//step 1, insert record into Wish Fan Ben table if not existing
			//		  or load the Wish Fan Ben if existing
			$FanBenModel = null;
			if (isset($data['fanben_id']) and trim($data['fanben_id'])<>"" and $data['fanben_id']<>0 ){
				$FanBenModel = WishFanben::find( )->where(['id'=>$data['fanben_id'] ] )->one();
			}
			$sku = $data['parent_sku'];
			//create one if not existing
			if ($FanBenModel == null){
				$FanBenModel=new WishFanben();
				//check Wish Fan Ben name is exist or not,if exist then return false
	
				$fanbenExist = WishFanben::find()->andWhere(['parent_sku'=>$data['parent_sku'],'site_id'=> $data['site_id'] ])->count();
				if ($fanbenExist>0) {
					$rtn['success'] = false;
					$rtn['message'] = "该范本 Parent SKU:".$data['parent_sku']." 已存在，请勿重复！<br>";				
				}
					
				$FanBenModel->create_time = $now_str;
				$created = true;
			}else{
				$created = false;
				//when is changing an existing Wish Fan Ben, do not change the name of it
				//otherwise, it may mislead users
				//unset($data['name']);
			}
			
			//step 1.5 ,start to validate all variance, whether the sku is not eixsting for other fanben id on this site
			$connection = Yii::$app->subdb;
			
			if (isset($data['variance']) ){
				$inputSku = [];
				$inputColorSize = [];
				if (is_string($data['variance']))
					$data['variance'] = json_decode($data['variance'], true);
				foreach ($data['variance'] as $anVariance){
					$sql = "select count(*) as cc from wish_fanben fb, wish_fanben_variance va ".
								"where fb.id=va.fanben_id and site_id= ".$data['site_id'].
								" and sku='".$anVariance['sku']."' and va.fanben_id <> ".$data['fanben_id'];
					//step 1.5.1 check if this variance sku is duplicated in this form.
					if (! isset($inputSku[ $anVariance['sku'] ])){
						$inputSku[ $anVariance['sku'] ] = 1;
					}else{
						$rtn['success'] = false;
						$rtn['message'] = "变体SKU:".$anVariance['sku']." 重复，请重新填写.<br>";
						continue;
					}
					
					//step 1.5.2 check if this variance having the same color-size with other variance.
					if (isset($anVariance['color']) and isset($anVariance['size'])){
						$colorsize = $anVariance['color']."-".$anVariance['size'];
						if (! isset($inputSku[ $colorsize ])){
							$inputSku[ $colorsize ] = 1;
						}else{
							$rtn['success'] = false;
							$rtn['message'] = "子产品 SKU:".$anVariance['sku']." 的颜色以及Size组合已经在其他子产品出现，请不要重复.<br>";
							continue;
						}
					}//end if there is color size posted for variance info
					
					$command = $connection->createCommand($sql);
					
					$existingCount = $command->queryScalar();
					if ($existingCount>0) {
						$rtn['success'] = false;
						$rtn['message'] = "SKU:".$anVariance['sku']." 已存在，请重新填写.<br>";
					}
				}//end of each variance
			}
		
			if ( ! $rtn['success'] )
				return $rtn;
			
			
			if (isset($data['fanben_id']))
				unset($data['fanben_id']);
		
			$FanBenModel->attributes = $data; //put the $data field values into aWish Fan Ben
			$FanBenModel->capture_user_id = Yii::$app->user->id;
			$FanBenModel->update_time = $now_str;
		
			if (!$varianceOnly){
				if ( $FanBenModel->save() ){//save successfull
					$rtn['success']=true;
					OperationLogHelper::log('wish_fanben',$FanBenModel->id,($created?"创建":"修改")."Wish范本成功");
				}else{
					$rtn['success']=false;
					$rtn['message'] .= "保存范本数据失败.";
					foreach ($FanBenModel->errors as $k => $anError){
						$rtn['message'] .= "E_WFB0101 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}//end of save failed
			}//end of when Not for variance only, also save the product info
			
			if ( ! $rtn['success'] )
				return $rtn;
			
			//start to save the variance
			if (isset($data['variance']) ){
				$activeVariane = [];
                $is_enable = 1;
				foreach ($data['variance'] as $anVariance){
					$FanBenVarianceModel = null;
					$activeVariane[] = $anVariance['sku'];
					
					if (isset($anVariance['sku']) and trim($anVariance['sku'])<>""   ){
						$FanBenVarianceModel =  WishFanbenVariance::find()->andWhere(["fanben_id"=>$FanBenModel->id,"sku"=>$anVariance['sku'] ])->one();
						$VarianceCreated=false;
					}
					//create one if not existing
					if ($FanBenVarianceModel == null){
						$VarianceCreated = true;
						$FanBenVarianceModel = new WishFanbenVariance();
						$FanBenVarianceModel->fanben_id = $FanBenModel->id;
					}

                    if($anVariance['enable'] == 'N'){
                        $is_enable = 2;
                    }

					$FanBenVarianceModel->setAttributes( $anVariance);
					$FanBenVarianceModel->parent_sku = $sku;
					if ( $FanBenVarianceModel->save() ){//save successfull
						$rtn['success']=true;
						//	ENUM('purchase','stock_change','product','finance','Wish Fan Ben','supplier')
						OperationLogHelper::log('wish_fanben',$FanBenModel->id."-".$anVariance['sku'],($VarianceCreated?"创建":"修改")."Wish范本变参产品成功");	
					}else{
						$rtn['success']=false;
						$rtn['message'] .= "保存范本变参内容数据失败.";
						foreach ($FanBenVarianceModel->errors as $k => $anError){
							$rtn['message'] .= "E_WFB101 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
						}
					}//end of save failed					
				}//end of each variance

                if($is_enable == 1){
                    $fanben_variance = WishFanbenVariance::find()->where(['enable' => 'N','fanben_id'=>$FanBenModel->id]);
                    if($fanben_variance->count() > 0){
                        $is_enable = 2;
                    }
                } else {
                    $fanben_variance = WishFanbenVariance::find()->where(['enable' => 'Y','fanben_id'=>$FanBenModel->id]);
                    if($fanben_variance->count() == 0){
                        $is_enable = 3;
                    }
                }
                //更新FANBEN 变种商品状态
                $FanBenModel->is_enable = $is_enable;
                $FanBenModel->save();


				if (!empty($data['opt_method']) )
					$varianceEditMethod = $data['opt_method'];
					
				//check  whether delete variance
				if ($varianceEditMethod == 'all'){
					$FanbenVarinceDB = WishFanbenVariance::find()->where(["fanben_id"=>$FanBenModel->id])->all();
					foreach($FanbenVarinceDB as $tmpVarinace){
						if (! in_array($tmpVarinace['sku'], $activeVariane)){
							$tmpVarinace->delete();
						}
					}
				}
			}

			
			$rtn['fanben_id'] = $FanBenModel->id;
		
			//SysLogHelper::SysLog_Create("product",__CLASS__, __FUNCTION__,"","try to modify a Wish Fan Ben info", "trace");
	
			//OK now try to call a job to auto do appendAdd Item to Queue		
			if ($rtn['success']==true){
				//self::postFanBen($FanBenModel->id);
			}
			
			return $rtn;
		} catch (Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			return $rtn;
		}	
	}

    public static function pushFanBen($fanben_id){
        return self::postFanBen($fanben_id);
    }

	static function postFanBen($fanben_id){
		$rtn = [];
		$FanBenModel = WishFanben::findOne( $fanben_id );
		//if ($FanBenModel->status == 'posting') //由于 在线商品的存在所以不再使用posting的 为同步的标准 
		{
			//	when save and post, add this item to queue
			if ($FanBenModel->wish_product_id == '') //do create an item with variance
				$rtn = self::appendAddItemToQueue( $fanben_id );
			else //do update item , change variance and add variance
				$rtn = self::appendChangeItemToQueue($fanben_id);
		}
		return $rtn;
	}
	
	/**
	 * 把范本append到wish刊登队列的后边
	 * 这个function目的是对完全没有upload过的产品create 出来，所以会提交 product 以及所有variance
	 * parmameter 1:$FanBenModel
	 * 
	 * **/
	static function appendAddItemToQueue($fanben_id){
		$rtn['message']="";
		$rtn['success'] = true;
		$now_str = GetControlData::getNowDateTime_str();
		
		$user = Yii::$app->user->identity->getUsername();
		$uid = Yii::$app->user->id;
		//If failed to get valid uid, prompt error
		if ($uid=='0' or $uid =='' ){
			$rtn['success']=false;
			$rtn['message'] .= "E_Wish Fan Ben_00 UID got invalid uid:".$uid;
			return $rtn;
		}
			
//step 1: Load the fanben detail by the uid and fanben id
		$WishFanBenModel = WishFanben::findOne($fanben_id);
		//if failed to load the fanben detail, prompt error
		if ($WishFanBenModel == null){
			//异常情况
			$message = "Step 1 Failed to Load Fanben Detail for $fanben_id (uid=$uid)";
			Yii::error(['wish_fanben',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $message;						
			return $rtn;
		}

		//put the Fan Ben snapshot to posting data as a copy.
		$postData = $WishFanBenModel->getAttributes();

//Step 1.2: create one new For Father product + 1st variance
		$inQueueItem = new WishApiQueue();
		//even use the previous pending one, modify the create time to be now
		$inQueueItem->create_time = $now_str;
		$inQueueItem->update_time = $now_str;
		if (! is_string($fanben_id))
			$inQueueItem->fanben_order_id = (string)$fanben_id ;
		else 
			$inQueueItem->fanben_order_id = $fanben_id ;
		$inQueueItem->uid = $uid;
		//put more details to $inQueueItem
 		if (! is_string($WishFanBenModel->site_id))
			$inQueueItem->site_id = (string)$WishFanBenModel->site_id;
		else 
			$inQueueItem->site_id = $WishFanBenModel->site_id;
		$inQueueItem->action_type = 'product';
		 
//Step 1.5, add 1st variance to the main product
        $postData['sku'] = $postData['parent_sku'];
		$inQueueItem->params = json_encode($postData);
		$WishFanBenVarianceModels = WishFanbenVariance::find()->where(["fanben_id"=> $fanben_id])->all();
		if ($WishFanBenVarianceModels == null){
			$rtn['success']=false;
			$rtn['message'] .= "EWFB15：无法读取到该范本的Variance，刊登失败";
			return $rtn;
		}else{
			$varianceData = $WishFanBenVarianceModels[0];
		}
		 
		// unset($varianceData['id']);
		// unset($varianceData['fanben_id']);
			
		//merge the product data with the 1st variance data	
		//$postData = array_merge($postData, $varianceData->attributes);
		
//Step 1.6, remove pending reqeust for the same fanben id
		self::cancelWishAPIQueue($inQueueItem->fanben_order_id , "因为范本被修改，以最后的修改为准，本次修改变成中间值，放弃");
		
		//$inQueueItem->params = json_encode($postData);
		
		if ( $inQueueItem->save() ){//save successfull 
			$WishFanBenModel->status = 'posting';
			if ($WishFanBenModel->save()){
				OperationLogHelper::log('wish_fanben',$fanben_id,"Wish 范本刊登 $fanben_id 已经提交到队列");
			}else{
				$rtn['success']=false;
				foreach ($inQueueItem->errors as $k => $anError){
					$rtn['message'] .= "EWFB013 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}else{
			$rtn['success']=false;
			foreach ($inQueueItem->errors as $k => $anError){
				$rtn['message'] .= "EWFB014 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
		
//step 2, add variance reqeust for the 2nd and more 		
		for ($ind = 0; $ind < count($WishFanBenVarianceModels) and $rtn['success'] ; $ind++ ){
			$WishFanBenVarianceModel = $WishFanBenVarianceModels[$ind];
			
			self::generateAPIQueueForVariance($WishFanBenModel,$WishFanBenVarianceModel,$rtn);
			
		}//end of each variance from 2nd
		
		return $rtn;
	 
	}

	
	/**
	 * 吧Variance的model作为参数写到api 队列，要求进行create 或者update
	 * parmameter 1: 范本的Model
	 * Parameter 2：     范本其中一个Variance 的model
	 *
	 * **/
	
	static function generateAPIQueueForVariance(&$WishFanBenModel,&$WishFanBenVarianceModel,&$rtn){
		$inQueueItem = new WishApiQueue();
		$fanben_id = (string)$WishFanBenModel->id;
		//even use the previous pending one, modify the create time to be now
		$inQueueItem->create_time = GetControlData::getNowDateTime_str();;
		$inQueueItem->update_time = GetControlData::getNowDateTime_str();;
		$inQueueItem->fanben_order_id = $fanben_id ;
		$user = Yii::$app->user->identity->getUsername();
		$inQueueItem->uid = Yii::$app->user->id;
		//put more details to $inQueueItem
		$inQueueItem->site_id =  (string)$WishFanBenModel->site_id;
		$inQueueItem->action_type = 'product_var_chg';
		$inQueueItem->params = json_encode($WishFanBenVarianceModel->attributes);

		if ( $inQueueItem->save() ){//save successfull
			$WishFanBenVarianceModel->sync_status = 'in_queue';
			if ($WishFanBenVarianceModel->save()){
				OperationLogHelper::log('wish_fanben',$fanben_id ,"Wish 范本刊登 $fanben_id ".$WishFanBenVarianceModel->parent_sku."-".$WishFanBenVarianceModel->sku."已经提交到队列");
			}else{
				$rtn['success']=false;
				foreach ($WishFanBenVarianceModel->errors as $k => $anError){
					$rtn['message'] .= "EWFBVS11:". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}else{
			$rtn['success']=false;
			foreach ($inQueueItem->errors as $k => $anError){
				$rtn['message'] .= "EWFBVS12:". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
	}	 
	
	/**
	 * 取消wish刊登队列的范本
	 * parmameter 1:
	 *
	 * **/
	static function cancelWishAPIQueue($fanben_id, $remark=''){
		$rtn['message']="";
		$rtn['success']=true;
		$user = Yii::$app->user->identity->getUsername();
		$canceled = false;
		$uid = Yii::$app->user->id;
		
		$WishFanBenModel = WishFanben::findOne($fanben_id);
		
		$criteria = " 1 ";
		$criteria.= (' and uid = '.$uid);
		$criteria.= (" and status = 'pending'");
		$criteria.= (" and site_id = ".$WishFanBenModel->site_id);
		$criteria.= (" and action_type in ('product','product_var_chg','product_create','product_update')" );
		$criteria.= (" and fanben_order_id =  '$fanben_id'");
	
		$inQueueItems = WishApiQueue::find()->where($criteria)->all();
		//if  existing, update it to canceled		
		if (count($inQueueItems) > 0){
			foreach ($inQueueItems as $inQueueItem){						 
				$inQueueItem->update_time = GetControlData::getNowDateTime_str();
				$inQueueItem->message= $remark;
				$inQueueItem->status = 'canceled';				
				if ( $inQueueItem->save() ){//save successfull
					$rtn['success']=true;				
					$canceled = true;
					OperationLogHelper::log('wish_fanben',$fanben_id,"Wish 范本刊登 $fanben_id 已经取消");
				}else{
					//save queue item failed
					$rtn['success']=false;
					foreach ($inQueueItem->errors as $k => $anError){
						$rtn['message'] .= "EWFB.103 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}				
			}//end of each in Queue Item
		}//end of found pending in queue item request
		return $rtn;	
	}
 
	/**
	 * 把范本append到wish刊登队列的后边
	 * parmameter 1:
	 *
	 * **/
	static function appendChangeItemToQueue($fanben_id){
		$rtn['message']="";
		$rtn['success'] = true;
		$now_str = GetControlData::getNowDateTime_str();
		
		$user = Yii::$app->user->identity->getUsername();
		$uid = Yii::$app->user->id;
		//If failed to get valid uid, prompt error
		if ($uid=='0' or $uid =='' ){
			$rtn['success']=false;
			$rtn['message'] .= "E_Wish Fan Ben_00 UID got invalid uid:".$uid;
			return $rtn;
		}
			
//step 1: Load the fanben detail by the uid and fanben id
		$WishFanBenModel = WishFanben::findOne($fanben_id);
		//if failed to load the fanben detail, prompt error
		if ($WishFanBenModel == null){
			//异常情况			
			$message = "Step 1 Failed to Load Fanben Model for $fanben_id (uid=$uid)";
			Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $message;						
			return $rtn;
		}

		//put the Fan Ben snapshot to posting data as a copy.
		$postData = $WishFanBenModel->attributes;

//Step 1.2: create one new For change Father product, due to API for update product does NOT work, this is skipped 
		$inQueueItem = new WishApiQueue();
		//even use the previous pending one, modify the create time to be now
		$inQueueItem->create_time = $now_str;
		$inQueueItem->update_time = $now_str;
		$inQueueItem->fanben_order_id = $fanben_id ;
		$inQueueItem->uid = $uid;
		//put more details to $inQueueItem
		$inQueueItem->site_id = $WishFanBenModel->site_id;
		$inQueueItem->action_type = 'product';
		
//Step 1.5, add 1st variance to the main product
		$inQueueItem->params = json_encode($postData);
		$WishFanBenVarianceModels = WishFanbenVariance::find()->where(["fanben_id"=>$fanben_id])->all();
		if ($WishFanBenVarianceModels == null){
			$rtn['success']=false;
			$rtn['message'] .= "EWFB15：无法读取到该范本的Variance，刊登失败";
			return $rtn;
		}else{
			$varianceData = $WishFanBenVarianceModels[0];
		}		
		
//Step 1.6, remove pending reqeust for the same fanben id
		self::cancelWishAPIQueue($inQueueItem->fanben_order_id , "因为范本被修改，以最后的修改为准，本次修改变成中间值，放弃");

//Step 1.7, add the reqeust for father product
/*
		$inQueueItem->params = json_encode($postData);		
		if ( $inQueueItem->save() ){//save successfull 
			$WishFanBenModel->status = 'posting';
			if ($WishFanBenModel->save()){
				OperationLogHelper::log('wish_fanben',$fanben_id,"Wish 范本刊登 $fanben_id 已经提交到队列");
			}else{
				$rtn['success']=false;
				foreach ($inQueueItem->errors as $k => $anError){
					$rtn['message'] .= "E_Wish Fan Ben_13 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}else{
			$rtn['success']=false;
			foreach ($inQueueItem->errors as $k => $anError){
				$rtn['message'] .= "E_Wish Fan Ben_14 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
	*/	
//step 2, add variance reqeust for the 2nd and more 		
		for ($ind = 0; $ind < count($WishFanBenVarianceModels) and $rtn['success'] ; $ind++ ){
			$WishFanBenVarianceModel = $WishFanBenVarianceModels[$ind];
			
			self::generateAPIQueueForVariance($WishFanBenModel,$WishFanBenVarianceModel,$rtn);
			
		}//end of each variance from 2nd
		
		return $rtn;
	
		}
	
	public static function listUserWishAccounts(){
		$saasId = Yii::$app->user->identity->getParentUid();		
		$data = SaasWishUser::find()->where(['uid'=>$saasId])->all();
		$WishUserInfoList = [];
		foreach($data as $WishUser){
			$WishUserInfo=$WishUser->attributes;
			$WishUserInfo['create_time'] = gmdate('Y-m-d H:i:s', $WishUserInfo['create_time']+8*3600);
			$WishUserInfo['update_time'] = gmdate('Y-m-d H:i:s', $WishUserInfo['update_time']+8*3600);
			$WishUserInfoList[]=$WishUserInfo;
		}
		return $WishUserInfoList;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * CronJob 执行Wish API 队列的任务，不包括order ship，刊登post
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param  
	 +----------------------------------------------------------
	 * @return				array('message'=>"",'success'=true)
	 * 						当return 的message =n/a 的时候，外层的job 会理解为没有request
	 *                      排队，然后job 会sleep 10 秒钟，然后继续看有没有req 排队                 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq	   2014/11/25				初始化
	 +----------------------------------------------------------
	 **/
	public static function cronQueueHandleFanben(){
		$rtn['message']="";
		$rtn['success'] = true;
		
		//process_status --- canceled,pending,error,failed,complete
		$SAAS_api_requests = WishApiQueue::find()
								->andWhere("status = 'pending' and action_type in ( 'product' , 'product_var_chg')")
								->limit(30)
								->orderBy('timerid  asc')
								->all();

		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Online',"Step 0 starting Wish cronQueueHandleFanben,Queue Depth:".count($SAAS_api_requests)],"edb\global");
		if( ! empty($SAAS_api_requests) and count($SAAS_api_requests) > 0){
			foreach($SAAS_api_requests as $SAAS_api_request){
				//purge the error msg written before
				$SAAS_api_request->message = '';
				
				$uid = $SAAS_api_request->uid;
				$fanben_order_id = $SAAS_api_request->fanben_order_id;
				$SAAS_api_request->update_time = GetControlData::getNowDateTime_str();
				$request_action = $SAAS_api_request->action_type;
				//step 1: Load the fanben detail by the snapshot
				$params = json_decode($SAAS_api_request->params, true); 
				
				//step 2: Load the Wish access info, token, etc
				$WishShopModel = SaasWishUser::findOne($SAAS_api_request->site_id);
				if ($WishShopModel == null){
					$str = ['wish',__CLASS__,__FUNCTION__,"Step 1.5 Failed to Load wish token for ".$SAAS_api_request->site_id];
					\Yii::info(var_export($str,true),"file");
					//异常情况
					$SAAS_api_request->status= "failed";
					$SAAS_api_request->message="Failed to Load wish token for ".$SAAS_api_request->site_id;
					$SAAS_api_request->save();
					continue;
				}
				 

				//step 3: Load the FanBen in User X db
				$ret=true;				 
				if ($ret===false){
					continue;
				}else{
						$WishFanBenModel = WishFanben::findOne($fanben_order_id);
					
					if ($WishFanBenModel == null){
						//异常情况
					
						$str = ['wish',__CLASS__,__FUNCTION__,"Failed to Load Fanben ID $fanben_order_id for Uid: $uid "];
						\Yii::info(var_export($str,true),"file");
					//	异常情况
						$SAAS_api_request->status= "failed";
						$SAAS_api_request->message="Failed to Load Fanben ID $fanben_order_id for Uid: $uid " ;
						$SAAS_api_request->save();
						continue;
					}
				}//end of switch db for userx successfully
		
				//Step 4: call Proxy to do the request
				$rtn =self::_CreateUpdateProductUsingProxy($WishShopModel->token,$params);
				
				//1) If proxy do not return any success flag
				//2) If proxy returns success = false
				//3) if no having success flag from proxy
				//4) if got success = false from proxy
				
				$variance_sku = $params['sku'];
				
				//如果错误，返回的error message 写入到 wish_fanben.error_message,
				//以及 wish_fanben_variance. 的sync_status=’failed’ 
				if (!isset($rtn['success']) or 
					$rtn['success'] === false or 
					!isset($rtn['proxyResponse']['success']) or 
					( isset($rtn['proxyResponse']['success']) and $rtn['proxyResponse']['success'] === false) ){
					
					$SAAS_api_request->status= "failed";

					if (isset($rtn['proxyResponse']['success']) and $rtn['proxyResponse']['success'] === false )
						$SAAS_api_request->message = $rtn['proxyResponse']['message'];
					else 
						$SAAS_api_request->message = "Failed to call Proxy to crt/upd product:".$rtn['message'];
					
					$SAAS_api_request->save();
					
					//Also put the error message to FanBen Detail
					//When this api is for fanben, erase the error message before. 
					//When it is for variance, do not erase the error message before, just append
					if ($request_action == 'product'){
						$WishFanBenModel->error_message = '';
					}
					
					$WishFanBenModel->status='error';
					$WishFanBenModel->error_message  .= $SAAS_api_request->message;
					$WishFanBenModel->update_time = GetControlData::getNowDateTime_str();
					$WishFanBenModel->save();
					
					//Also put the error info to fanben variance
					$WishFanbenVarianceModel = WishFanbenVariance::find()
												->andWhere(["fanben_id"=>$fanben_order_id ,
															"parent_sku"=>$WishFanBenModel->parent_sku,
														    "sku"=>$variance_sku ])
												->one();
					if ($WishFanbenVarianceModel <> null){
						$WishFanbenVarianceModel->sync_status = "failed";
						$WishFanbenVarianceModel->save();
					}
					
					continue;
				}
				////////////////////////////////////////////////
				//以下就是成功执行了 API，如果没有错误，update 该variance 的sync_status=’complete’ , 
				///////////////////////////////////////////////
				//wish_fanben.error_message
				//Step 5, update the fanben set the wish_product_id as returned value				
				if (isset($rtn['proxyResponse']['wishReturn']['id']) and $rtn['proxyResponse']['wishReturn']['id']<>''){
					//		successfully load the fanben in that user db
					$WishFanBenModel->wish_product_id  = $rtn['proxyResponse']['wishReturn']['id'];					
				}//end of got response with wish product id
				
				//Step 5.5.1, update the fanben variance set the variance_product_id as returned value
				//this for adding variance api
				//Load the fanben Variance Model first
				$WishFanbenVarianceModel = WishFanbenVariance::find()
						->andWhere(["fanben_id"=>$fanben_order_id,
									"parent_sku"=>$WishFanBenModel->parent_sku,
									"sku"=>$variance_sku ])
						->one();
				
				if (isset($rtn['proxyResponse']['wishReturn']['variance_product_id']) and $rtn['proxyResponse']['wishReturn']['variance_product_id']<>'' ){
					//		successfully load the fanben in that user db					
					if ($WishFanbenVarianceModel <> null){
						$WishFanbenVarianceModel->variance_product_id  = $rtn['proxyResponse']['wishReturn']['variance_product_id'];			
						$WishFanbenVarianceModel->sync_status = "complete";
						$WishFanbenVarianceModel->save();
					}
				}//end of got response with wish product id

				//Step 5.5.2, update the fanben variance set the variance_product_id as returned value
				//this for creating variance by the way of product creation api				
				if (isset($rtn['proxyResponse']['wishReturn']['variants'][0]['id']) and $rtn['proxyResponse']['wishReturn']['variants'][0]['id']<>'' ){
					//		successfully load the fanben in that user db					
					if ($WishFanbenVarianceModel <> null){
						$WishFanbenVarianceModel->variance_product_id  = $rtn['proxyResponse']['wishReturn']['variants'][0]['id'];
						$WishFanbenVarianceModel->sync_status = "complete";
						$WishFanbenVarianceModel->save();
					}
				}//end of got response with wish product id
				
				
				//Step 6 ,Update the API reqeust as done complete.
				$SAAS_api_request->status= 'complete';
				$SAAS_api_request->update_time = GetControlData::getNowDateTime_str();;
				$SAAS_api_request->save();
				
				
				/*Step 7:因为每个执行api都是异步的现成，不知道其他variance是否有错误，所以每次执行完毕，都check 所有variance，以及父产品，看是否
A：所有 sync_status=’complete’ 。如果是，update 该variance的fanben id 的wish_fanben.status =’complete’, return
B: 如果有 sync_status=in_queue, update 该variance的fanben id 的wish_fanben.status =posting, return
C: 如果有 sync_status=failed, update 该variance的fanben id 的wish_fanben.status =failed, return 
				*/
				//A：所有 sync_status=’complete’ 。如果是，update 该variance的fanben id 的wish_fanben.status =’complete’, return
				$WishFanbenVarianceModels = WishFanbenVariance::find()->andWhere("fanben_id=$fanben_order_id  and sync_status <>'complete'");				
				if ($WishFanbenVarianceModels == null or count($WishFanbenVarianceModels) == 0 ){
					$WishFanBenModel->error_message = "";
					$WishFanBenModel->status = "complete";
				}else{				
					//B: 如果有 sync_status=in_queue, update 该variance的fanben id 的wish_fanben.status =posting, return					
					$WishFanbenVarianceModels = WishFanbenVariance::find()->andWhere("fanben_id=$fanben_order_id  and sync_status ='in_queue'");
					if ($WishFanbenVarianceModels <> null and count($WishFanbenVarianceModels) > 0){						
						$WishFanBenModel->status = "posting";
					}else{
						//C: 如果有 sync_status=failed, update 该variance的fanben id 的wish_fanben.status =failed, return
						$WishFanbenVarianceModels = WishFanbenVariance::find()->andWhere("fanben_id=$fanben_order_id  and sync_status ='failed'");						
						if ($WishFanbenVarianceModels <> null and count($WishFanbenVarianceModels) > 0){
							$WishFanBenModel->status = "failed";
						}// end if case C
					} //end if case B			
				}//end if case A	
				
				$WishFanBenModel->update_time = GetControlData::getNowDateTime_str();
				$WishFanBenModel->save();
				//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Handle API req done ".$SAAS_api_request->message],"edb\global");
			}//end of each SAA api request
			
		}else{//if nothing in the queue, idle
			$rtn['message']="n/a";
		}
		
		return $rtn;
	}
	
	 

	/**
	 * 提交create product 到远程Wish proxy, 如果该product unique id 已经存在，update it
	 * yzq 2014-11-25
	 */
	private static function _CreateUpdateProductUsingProxy($wish_token,$ProdDataArr){
		$timeout=120; //s
		
		\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Step 1 start to call proxy for crt/upd prod,token $wish_token" ],"edb\global");
		$reqParams['token'] = $wish_token;
		//the proxy will auto do update if the wish product id is existing
		//$retInfo=WishProxyConnectHelper::call_WISH_api("CreateProduct",$reqParams, ["data"=>$ProdDataArr] );
		$retInfo = WishProxyConnectKandengHelper::call_WISH_api("CreateProduct",$reqParams, ["data"=>$ProdDataArr] );
        \Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"Step 2 complete calling proxy for crt/upd prod,token $wish_token" ],"edb\global");

		//check the return info
		return $retInfo;
	
	}


    /**
     * 手动同步商品 根据日期修改
     * @param $site_id
     * @return mixed
     */
    public static function syscnProductInfo($wish_account,$start=1){

        if(  $wish_account['last_manual_product_time'] == '0000-00-00 00:00:00' ){
            $since = date('Y-m-d',strtotime($wish_account['last_product_success_retrieve_time']));
        } else {
            $since = date('Y-m-d',strtotime($wish_account['last_manual_product_time']));
        }

        $limit = 50;

        $data = [
            "since" => $since,
            "start" => ($start - 1) * $limit,
            "limit" => $limit
        ];

        $retInfo = NULL;
        //调用接口
        $retInfo = WishProxyConnectKandengHelper::call_WISH_api("getProductsByPagination",["token"=>$wish_account['token']],["data"=>$data]);

        self::saveLog('syscnProductInfo',['site_id'=>$wish_account['site_id'],'data'=>$data],$retInfo);

        $returnInfo = [
            'code' => 200,
            'message' => '手动同步商品成功！',
            'total' => 0
        ];

        if($retInfo['success'] === true){
            \Yii::$app->db->createCommand()->update('saas_wish_user',['last_manual_product_time'=>date('Y-m-d H:i:s')]," site_id = {$wish_account['site_id']}")->execute();
            $return_data = $retInfo['proxyResponse']['wishReturn']['data'];
            if(empty($return_data)){
                $returnInfo['code'] = 201;
                $returnInfo['message'] = '没有商品信息需要更新！';
            } else {
                $returnInfo['total'] = count($return_data);
                self::saveProductInfo($return_data,$wish_account['site_id']);
            }
        } else {
            $returnInfo['code'] = 202;
            $returnInfo['message'] = $retInfo['message'];
        }
        unset($return_data);
        unset($retInfo);

        return $returnInfo;

    }

    /**
     * 即时发布修改商品信息
     * @param $wish_account
     * @param $fanben_info
     * @return array
     */
    public static function  pushUpdateProduct($wish_account,$fanben_info){
        if(empty($wish_account) || $fanben_info == NULL){
            return ['code' => 201 , 'message' => '信息不完整,请重新提交'];
        }

        $data = [
            'wish_product_id' => $fanben_info->wish_product_id,
            'name' => $fanben_info->name,
            'description' => $fanben_info->description,
            'tags' => $fanben_info->tags,
            'landing_page_url'=>$fanben_info->landing_page_url,
            'brand' => $fanben_info->brand,
            'upc' => $fanben_info->upc,
            'main_image' => $fanben_info->main_image,
            'extra_images' => self::getExtraImages($fanben_info)
        ];


        Yii::info(__FUNCTION__.": 批量修改商品标题开始".var_export($data,true)."\r\n","file");
        //调用接口
        $retInfo = WishProxyConnectKandengHelper::call_WISH_api("UpdateProduct",["token"=>$wish_account['token']],["data"=>$data]);
        //记录日志
        self::saveLog(__FUNCTION__,['site_id'=>$wish_account['site_id'],'data'=>$data],$retInfo);

        Yii::info(__FUNCTION__.": 批量修改商品标题结束".var_export($retInfo,true)."\r\n","file");

        if($retInfo['success'] === true){
            $retInfo = ['code'=>200,'message'=>'商品更新成功'];
        } else {
            $retInfo = ['code'=>202,'message'=>'商品更新失败'];
        }

        return $retInfo;
    }

    /**
     * 变更商品在线状态
     * @param $wish_account array 用户WISH账号信息
     * @param $status boolean true|false 变更在线状态 true上架 false下架
     * @param $wish_product_id string WISH在线商品ID
     * @return array
     */
    public static function changeProductStatus($wish_account,$status,$parent_sku){

        if(empty($wish_account)){
            return ['code' => 201 , 'message' => '信息不完整,请重新提交'];
        }

        $data = [
            'parent_sku' => $parent_sku,
            'enable' => $status
        ];
        Yii::info(__FUNCTION__.": 变更商品在线状态开始".var_export($data,true)."\r\n","file");

        //调用接口
        $retInfo = WishProxyConnectKandengHelper::call_WISH_api("changeProductStatus",["token"=>$wish_account['token']],["data"=>$data]);

        self::saveLog(__FUNCTION__,['site_id'=>$wish_account['site_id'],'data'=>$data],$retInfo);

        Yii::info(__FUNCTION__.": 变更商品在线状态结束".var_export($retInfo,true)."\r\n","file");

        if($retInfo['success'] === true){
            $retInfo = ['code'=>200,'message'=>'商品更新状态成功'];
        } else {
            $retInfo = ['code'=>202,'message'=>'商品更新状态失败'];
        }

        return $retInfo;

    }

    public static function pushUpdateProductVariationInfo($wish_account,$variation){
        if(empty($wish_account) || $variation == NULL){
            return ['code' => 201 , 'message' => '信息不完整,请重新提交'];
        }

        $variation_addinfo = json_decode($variation->addinfo,true);
        $data = [
            'sku' => $variation->sku,
            'inventory' => $variation->inventory,
            'price' => $variation->price,
            'shipping' => $variation->shipping,
            'enabled' => $variation->enable == 'Y' ? 'True' : 'False',
            'size' => $variation->size,
            'color' => $variation->color,
            'msrp' => $variation_addinfo['msrp'],
            'shipping_time' => $variation_addinfo['shipping_time'],
            'main_image' => $variation->image_url,
            'variance_product_id' => $variation->variance_product_id

        ];
        Yii::info(__FUNCTION__.": 变更变种商品更新开始".var_export($data,true)."\r\n","file");

        //调用接口
        $retInfo = WishProxyConnectKandengHelper::call_WISH_api("UpdateProductVariation",["token"=>$wish_account['token']],["data"=>$data]);

        self::saveLog(__FUNCTION__,['site_id'=>$wish_account['site_id'],'data'=>$data],$retInfo);

        Yii::info(__FUNCTION__.": 变更变种商品更新结束".var_export($retInfo,true)."\r\n","file");

        if($retInfo['success'] === true){
            $retInfo = ['code'=>200,'message'=>'变更变种商品更新成功'];
        } else {
            $retInfo = ['code'=>202,'message'=>'变更变种商品更新失败'];
        }

        return $retInfo;
    }

    /**
     * 变更变种商品在线状态
     * @param $wish_account
     * @param $status
     * @param $variation_sku
     * @return array
     */
    public static function changeProductVariationStatus($wish_account,$status,$variation_sku){
        if(empty($wish_account)){
            return ['code' => 201 , 'message' => '信息不完整,请重新提交'];
        }

        $data = [
            'sku' => $variation_sku,
            'enable' => $status == 1 ? false : true
        ];
        Yii::info(__FUNCTION__.": 变更变种商品在线状态开始".var_export($data,true)."\r\n","file");

        //调用接口
        $retInfo = WishProxyConnectKandengHelper::call_WISH_api("changeProductVaritationStatus",["token"=>$wish_account['token']],["data"=>$data]);

        self::saveLog(__FUNCTION__,['site_id'=>$wish_account['site_id'],'data'=>$data],$retInfo);

        Yii::info(__FUNCTION__.": 变更变种商品在线状态结束".var_export($retInfo,true)."\r\n","file");

        if($retInfo['success'] === true){
            $retInfo = ['code'=>200,'message'=>'变种商品更新状态成功'];
        } else {
            $retInfo = ['code'=>202,'message'=>'变种商品更新状态失败'];
        }

        return $retInfo;
    }
    /**
     * 保存商品信息
     * @param $products
     * @param $site_id
     * @return array 【code,message】
     */
    public static function saveProductInfo($products,$site_id){
        $db = \Yii::$app->subdb;
        $puid = \Yii::$app->user->identity->getParentUid();
        foreach($products as $key => $product){
            $product = $product['Product'];
            //多图，用“|”分割
            $extra_images = empty($product['extra_images']) ? '' : explode('|',$product['extra_images']);
            //商品是否已经存在
            $product_info = self::getProductInfoByWishProductId($product['id']);
            if(!empty($product_info)){
                $fanben_id = self::updateFanBenInfo($db,$product_info,$puid,$product,$extra_images);
            } else {
                $fanben_id = self::insertFanBenInfo($db,$site_id,$puid,$product,$extra_images);
            }

            self::saveProductVariantInfo($db,$fanben_id,$product['parent_sku'],$product['variants']);

        }
        unset($products);
        return true;
    }

    /**
     * 更新商品变种信息
     * @param $fan_ben_id
     * @param $variants
     */
    public static function saveProductVariantInfo($db,$fanben_id,$product_sku,$variants){
        $is_enable = 1;//变种商品是否存在下架商品 1不存在 2存在
        $number = 0;
        $total = count($variants);//变种商品总数
        foreach($variants as $key => $variant){
            $variant = $variant['Variant'];
            $fan_ben_variant = self::getFanbenVariantInfoByVariantId($variant['id']);
            if(!empty($fan_ben_variant)){
                $fanben_id = $fan_ben_variant['fanben_id'];
                self::updateFanbenvarianceInfo($db,$fan_ben_variant,$variant);
            } else {
                self::insertFanbenvarianceInfo($db,$fanben_id,$product_sku,$variant);
            }

            if($variant['enabled'] == 'False'){
                $number++;
                $is_enable = 2;
            }
        }
        //变种商品全部下架
        if($number == $total){
            $is_enable = 3;
        }
        //同步商品，变种商品是否存在下架商品信息
        self::changeProductEnable($is_enable,$fanben_id,$db);
        unset($variants);
        return true;
    }

    public static function getProductInfoByWishProductId($id){
        $sql = " SELECT * FROM wish_fanben WHERE wish_product_id = '{$id}'";
        $row = \Yii::$app->subdb->createCommand($sql)->query()->read();
        return $row === false ? [] : $row;
    }

    public static function getFanbenVariantInfoByVariantId($id){
        $sql = " SELECT * FROM wish_fanben_variance WHERE variance_product_id = '{$id}'";
        $row = \Yii::$app->subdb->createCommand($sql)->query()->read();
        return $row === false ? [] : $row;
    }

    /**
     * 更新FANBEN 信息
     * @param $db
     * @param $fanben_info
     * @param $puid
     * @param $product
     * @param $extra_images
     * @return mixed
     */
    public static function updateFanBenInfo($db,$fanben_info,$puid,$product,$extra_images){
        $params = [
            'update_time' => date('Y-m-d H:i:s'),
            'capture_user_id' => $puid,
            'tags' => self::getTags($product['tags']),
            'variance_count' => count($product['variants']),
            'parent_sku' => $product['parent_sku'],
            'description' => $product['description'],
            'name' => $product['name'],
            'status' => $product['review_status'],
            'number_saves' => $product['number_saves'],
            'number_sold' => $product['number_sold'],
            'upc' => isset($product['upc'])?$product['upc']:'',
            'main_image' => $product['main_image']
        ];


        if($fanben_info['type'] == 1 ){
            $params['lb_status'] = isset(self::$wish_status[$product['review_status']]) ? self::$wish_status[$product['review_status']] : 6;
        } else {
            //小老板发布刊登的商品,如果状态通过就变更为在线商品
            if($product['review_status'] == 'online' || $product['review_status'] == 'approved'){
                $params['lb_status'] = 3;
            } else if($product['review_status'] == 'rejected'){
                $params['lb_status'] = 4;
            }
        }

        $msg = "syscWish : ".__CLASS__.__FUNCTION__."Online".var_export($params,true);
        Yii::info($msg,"file");

        if(!empty($extra_images)){
           foreach($extra_images as $key => $url){
               $number = $key +1 ;
               $params["extra_image_{$number}"] = $url;
           }
        }

        $command = $db->createCommand()->update('wish_fanben',$params," id = {$fanben_info['id']}");
        $command->execute();

        Yii::info(__FUNCTION__.":".$command->getRawSql(),"file");

        unset($command);
        return $fanben_info['id'];
    }
    /**
     * 保存新增FANBEN 记录
     * @param $db
     * @param $site_id
     * @param $puid
     * @param $product
     * @return mixed
     */
    public static function insertFanBenInfo($db,$site_id,$puid,$product,$extra_images){
        $params = [
            'type','lb_status','site_id','wish_product_id',
            'create_time','update_time','price','inventory',
            'msrp','shipping','shipping_time',
            'brand','description','capture_user_id',
            'status','parent_sku','variance_count','name',
            'tags','main_image','upc','number_saves','number_sold'
        ];
        if(!empty($extra_images)){
	        $extra = ['extra_image_1',
            'extra_image_2','extra_image_3','extra_image_4','extra_image_5',
            'extra_image_6','extra_image_7','extra_image_8','extra_image_9',
	            'extra_image_10'
        ];
        	 $params = array_merge($params,$extra);
        }

        $msg = "syscWish : ".__CLASS__.__FUNCTION__."Online".var_export($params,true);
        Yii::info($msg,"file");

        foreach($params as $val){
            $value[] = ":{$val}";
        }

        $sql = " INSERT INTO wish_fanben (`".join('`,`',$params)."`) VALUES (".join(',',$value).")";

        $lb_status = isset(self::$wish_status[$product['review_status']]) ? self::$wish_status[$product['review_status']] : 6;

        $command = $db->createCommand($sql);
        $command->bindValue(":type",1,PDO::PARAM_INT);//1在线商品
        $command->bindValue(":lb_status",$lb_status,PDO::PARAM_INT); //在线同步商品
        $command->bindValue(":number_saves",$product['number_saves'],PDO::PARAM_INT); //在线商品被收藏数
        $command->bindValue(":number_sold",$product['number_sold'],PDO::PARAM_INT);//在线商品销售数
        $command->bindValue(":site_id",$site_id,PDO::PARAM_INT);//小老板账户ID
        $command->bindValue(":wish_product_id",$product['id'],PDO::PARAM_STR);//WISH商品ID
        $command->bindValue(":capture_user_id",$puid,PDO::PARAM_INT);//执行人ID
        $command->bindValue(":create_time",date('Y-m-d H:i:s'),PDO::PARAM_STR);//创建时间

        //第一次获取商品单价、运费、库存 来源是第一个变种
        $variants = $product['variants'][0]['Variant'];
        $command->bindValue(":price",$variants['price'],PDO::PARAM_STR);//单价
        $command->bindValue(":inventory",$variants['inventory'],PDO::PARAM_STR);//指导价格
        $command->bindValue(":msrp",$variants['msrp'],PDO::PARAM_STR);//运费
        $command->bindValue(":shipping",$variants['shipping']);
        $command->bindValue(":shipping_time",$variants['shipping_time'],PDO::PARAM_STR);//快递时间

        $command->bindValue(":parent_sku",$product['parent_sku'],PDO::PARAM_STR);//父类SKU
        $command->bindValue(":variance_count",count($product['variants']),PDO::PARAM_INT);//变种商品数量
        $command->bindValue(":name",$product['name'],PDO::PARAM_STR);//商品名称
        $command->bindValue(":update_time",date('Y-m-d H:i:s'),PDO::PARAM_STR);//更新时间
        $command->bindValue(":description",$product['description'],PDO::PARAM_STR);//描述
        $command->bindValue(":status",$product['review_status'],PDO::PARAM_STR);//WISH在线状态
        $command->bindValue(":tags", self::getTags($product['tags']),PDO::PARAM_STR);//商品标签
        $command->bindValue(":upc",isset($product['upc'])?$product['upc']:'',PDO::PARAM_STR);//UPC
        $command->bindValue(":main_image",$product['main_image'],PDO::PARAM_STR);//商品主图
        $command->bindValue(":brand",isset($product['brand'])?$product['brand']:'');
        //商品图片
        if(!empty($extra_images)){
            $command->bindValue(":extra_image_1",isset($extra_images[0]) ? $extra_images[0] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_2",isset($extra_images[1]) ? $extra_images[1] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_3",isset($extra_images[2]) ? $extra_images[2] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_4",isset($extra_images[3]) ? $extra_images[3] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_5",isset($extra_images[4]) ? $extra_images[4] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_6",isset($extra_images[5]) ? $extra_images[5] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_7",isset($extra_images[6]) ? $extra_images[6] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_8",isset($extra_images[7]) ? $extra_images[7] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_9",isset($extra_images[8]) ? $extra_images[8] : '',PDO::PARAM_STR);
            $command->bindValue(":extra_image_10",isset($extra_images[9]) ? $extra_images[9] : '',PDO::PARAM_STR);
        }
        $command->execute();
        $id = $command->lastInsertId();
        Yii::info(__FUNCTION__.":".$command->getRawSql(),"file");
        unset($command);
        return $id;
    }

    public static function insertFanbenvarianceInfo($db,$fanben_id,$product_sku,$variant){
        $params = [
            'fanben_id','parent_sku','sku','color',
            'size','price','shipping','inventory',
            'addinfo','enable','variance_product_id','image_url'
        ];

        $msg = "syscWish : ".__CLASS__.__FUNCTION__."Online".var_export($params,true);
        Yii::info($msg,"file");

        foreach($params as $val){
            $value[] = ":{$val}";
        }

        $sql = " INSERT INTO wish_fanben_variance (`".join('`,`',$params)."`) VALUES (".join(',',$value).")";

        $command = $db->createCommand($sql);
        $command->bindValue(":fanben_id",$fanben_id,PDO::PARAM_INT);//1在线商品
        $command->bindValue(":parent_sku",$product_sku,PDO::PARAM_STR);
        $command->bindValue(":sku",$variant['sku'],PDO::PARAM_STR);
        $command->bindValue(":color",isset($variant['color'])?$variant['color']:'',PDO::PARAM_STR);
        $command->bindValue(":size",isset($variant['size'])?$variant['size']:'',PDO::PARAM_STR);
        $command->bindValue(":price",$variant['price'],PDO::PARAM_STR);
        $command->bindValue(":shipping",$variant['shipping'],PDO::PARAM_STR);
        $command->bindValue(":inventory",$variant['inventory'],PDO::PARAM_INT);//1在线商品
        $command->bindValue(":addinfo",json_encode($variant),PDO::PARAM_STR);
        $command->bindValue(":enable",$variant['enabled']=='True'?'Y':'N',PDO::PARAM_STR);
        $command->bindValue(":variance_product_id",$variant['id'],PDO::PARAM_STR);
        $command->bindValue(":image_url",$variant['all_images'],PDO::PARAM_STR);
        $command->execute();

        Yii::info(__FUNCTION__.":".$command->getRawSqL(),"file");
        unset($command);
    }

    public static function updateFanbenvarianceInfo($db,$fan_ben_variant,$variant){

        $params = [
            'parent_sku' => $fan_ben_variant['parent_sku'],
            'sku' => $variant['sku'],
            'color' => isset($variant['color'])?$variant['color']:'',
            'size' => isset($variant['size'])?$variant['size']:'',
            'price' => $variant['price'],
            'shipping' => $variant['shipping'],
            'inventory' => $variant['inventory'],
            'addinfo' => json_encode($variant),
            'enable' => $variant['enabled'] == 'True'?'Y':'N',
            'image_url' => $variant['all_images']
        ];

        $msg = "syscWish : ".__CLASS__.__FUNCTION__."Online".var_export($params,true);
        Yii::info($msg,"file");

        // if(!empty($extra_images)){
        //     foreach($extra_images as $key => $url){

        //               $params["extra_image_{$key}"] = $url;
        //     }
        // }

        $command = $db->createCommand()->update('wish_fanben_variance',$params," id = {$fan_ben_variant['id']}");
        $command->execute();

        Yii::info(__FUNCTION__.":".$command->getRawSql(),"file");

        unset($command);
    }

    /**
     * 同步商品时，查看是否有下架变种商品，存在则更新商品信息
     * @param $fanben_id
     * @param $db
     */
    private static function changeProductEnable($is_enable,$fanben_id,$db){
        $command = $db->createCommand()->update("wish_fanben",['is_enable'=>$is_enable]," id = {$fanben_id}");
        $command->execute();
        Yii::info(__FUNCTION__.":".$command->getRawSql(),"file");
    }
    /**
     * 获取标签信息
     * @param $tags
     * @return string
     */
    public static function getTags($tags){
        $return_tag = [];
        foreach($tags as $key => $data){
            $return_tag[] = $data['Tag']['id'];
        }
        return join(',',$return_tag);
    }
    /**
     *  记录日志信息
     * @param $action 操作类型、操作ACTION
     * @param $info 接口调用数据
     * @param $retInfo 接口返回数据
     */
    public static function saveLog($action,$info,$retInfo){
        $obj = new WishFanbenLog();
        $obj->wish_fanben_action = $action;
        $obj->wish_fanben_info = json_encode($info);
        $obj->wish_fanben_return_info = json_encode($retInfo);
        $obj->wish_fanben_status = 1;
        $obj->create_time = date('Y-m-d H:i:s');
        $obj->save();
        unset($obj);
    }

    	/*
   	*删除范本信息
   	*/

   	public static function delFanben($id){
   		$FanBenModel = WishFanben::findOne($id);
   		$variance  = WishFanbenVariance::deleteAll('parent_sku = :parent_sku',[':parent_sku'=>$FanBenModel['parent_sku']]);
   		$FanBenModel->delete();
   		$data = WishFanben::findOne($id);
   		if(!isset($data)){
   			$data['success'] = true;
   		}else{
   			$data['success'] = false;
   			$data['message'] = '删除范本信息失败';
   		}
   		return $data;
   	}

    public static function getExtraImages($data){
        $joinedImages = '';
        for ($i=1; $i<=10; $i++){
            if ($data['extra_image_'.$i] <>'')
                $joinedImages .= ($joinedImages==''?'':"|").$data['extra_image_'.$i] ;
        }
        return $joinedImages;
    }

    /*
    *同步所有绑定wish平台店铺的商品信息
    */
    public static function autoSyncFanbenInfo(){
    	$puid =  \Yii::$app->subdb->getCurrentPuid();
		$uid = $puid ==0 ? \Yii::$app->user->id :$puid;
		$result = SaasWishFanbenSyncHelper::addSyncProductQueue($puid);
    }

    /**
     * 获取可用WISH平台账号信息
     * @return array
     */
    public static function getWishAccountList(){
        $puid = Yii::$app->user->identity->getParentUid();
        $wish_account = WishAccountsApiHelper::ListAccounts($puid);

        $return = [];
        foreach($wish_account as $key => $account){
            if($account['is_active'] == 1){
                $return[$account['site_id']] = $account;
            }
        }
        return $return;
    }

    /**
    * 删除解绑店铺的同步商品信息
    */
    public static function delSyncFanbenInfo($site_id){
    	if(!empty($site_id)){
	    	// $model = WishFanBen::deleteAll(['site_id'=>$site_id]);
	    	$model =WishFanBen::find()->select(['id'])->where(['site_id'=>$site_id])->asArray()->all();
	    	foreach($model as $val){
	    		$delvariance = WishFanbenVariance::deleteAll(['fanben_id'=>$val]);	
	    		$delfanben =  WishFanben::findOne($val);
	    		$delfanben->delete();
	    	}
    	}
    }

    /*
    * 用户发布商品后同步wish平台的商品信息，保证商品信息状态一致
    */
    public static function SyncFanbenStatus($site_id){
    	//检查WISH账号是否正确
        $wish_account = WishAccountsApiHelper::RetrieveAccountBySiteID($site_id);	
        $puid = Yii::$app->user->identity->getParentUid();
        //检查队列信息是否存在
        $command = \Yii::$app->db->createCommand("select * from sync_product_api_queue where platform = 'wish'  and puid = '".$puid."' and seller_id='".$wish_account['account']['store_name']."' and status in ('P','S')");
        $queueList = $command->queryAll();
        if(count($queueList) <=0){
        	//获取所有有效的wish 账号
			$_model = new SyncProductApiQueue();
			$now = TimeUtil::getNow();
			$addi_info = '用户puid='.$puid.' site_id='.$site_id.' 正在同步商品信息';
			$data = [
				'status'=>'P',
				'puid'=>$puid,
				'priority' => 5,
				'seller_id' => $wish_account['account']['store_name'],
				'create_time' => $now,
				'update_time' => $now,
				'platform' => 'wish',
				'run_time' => 0,
				'addi_info' => $addi_info,
			];
			// var_dump($wish_account['account']['store_name']);
			$_model->attributes = $data;
			if($_model->save()){
				$Connection = \Yii::$app->db;	
				$Connection->createCommand()->update('saas_wish_user',['last_product_retrieve_time'=> $now],'site_id='.$site_id)->execute();

			};
        }
    }

    public static function CheckAccountBindingOrNot($site_id){
    	$return = WishAccountsApiHelper::checkAccountBindingOrNot($site_id);
    	return $return;
    }



    //////////////  hqf 2016-4-6
    

    /**
     * 获取access_token
     * @author huaqingfeng
     * @return string
     */
    private static function getToken($site_id){
        // 根据site_id查token
        if(!$user = SaasWishUser::find()->where([
            'site_id'=>$site_id
        ])->one()){
            throw new \Exception("site_id [{$site_id}] is not exists", 500);
        }
        if ($user->expiry_time  < date('Y-m-d H:i:s',strtotime('+2 days'))){
        	// time's  up , then refresh access token
        	if (!$user->refresh_token){
        		throw new \Exception("没有绑定成功， 请点击重新绑定(refresh token is empty!)", 404);
    		}
    		// 刷新token
    		
    		if(!$user->save()){
    			throw new \Exception("刷新token失败，请联系管理员", 500);
    		}
        }
        return $user->token;
    }

    public static function callApi($site_id,$name,$params=[],$data=[]){
        $api = new ApiHelper(self::$apiUrl);
        $api->params(array_merge([
            'action'=>$name,
            'token'=>self::getToken($site_id),
            'debug'=>1
        ],$params));
        $api->data($data);
        $result = $api->call();
        if(!$result['success']){
            throw new \Exception($result['message'], $result['code']);
        }elseif(!$result['response']['success']){
            throw new \Exception($result['response']['message'], 400);
        }
        return $result['response']['wishReturn']['data'];
    }

    /**
     * 保存商品及变体
     *
     * @author huaqingfeng
     * @version 2016-05-03 正确保存了商品的上下架状态
     * @param array  $data    [description]
     * @return \eagle\modules\listing\models\WishFanben
     */
    public static function WishFanbenSave($data = []){
    	$columns = [
            'parent_sku' 			=> true,
    		'name' 					=> true,
            'tags' 					=> true,
            'site_id' 				=> true,
            'description' 			=> true,
            'shipping' 				=> [true,false], 		// 新增必填，更新必填
            'shipping_time' 		=> [true,false],
            'price' 				=> [true,false],
            'inventory' 			=> [true,false],
            'msrp' 					=> false,
            'brand' 				=> false,
            'upc' 					=> false,
            'landing_page_url' 		=> false,
            // 'size' 				=> false,
            'main_image' 			=> true,
            'addinfo' 				=> false,
            'extra_image_1' 		=> false,
            'extra_image_2' 		=> false,
            'extra_image_3' 		=> false,
            'extra_image_4' 		=> false,
            'extra_image_5' 		=> false,
            'extra_image_6' 		=> false,
            'extra_image_7' 		=> false,
            'extra_image_8' 		=> false,
            'extra_image_9' 		=> false,
            'extra_image_10' 		=> false
        ];
    	// 判断新增还是修改
    	if(!isset($data['fanben_id']) || !$data['fanben_id'] || !$product = WishFanBen::find()->where([
    		'id'=>$data['fanben_id']
    	])->one()){
    		$product = new WishFanBen();
	    	$product->type = 2;
	    	$product->status = 'pending';
	    	$product->create_time = date('Y-m-d H:i:s');
	    	$product->is_enable = 1;
    	}
    	try{
	    	$uid = \Yii::$app->user->id;
    	}catch(\Exception $e){
    		$uid = 0;
    	}
    	$product->capture_user_id = $uid;
    	foreach($columns as $key=>$required){
    		if(is_array($required)){
    			$required = $product->isNewRecord ? $required[0]:$required[1];
    		}
    		if($required && !isset($data[$key])){
    			throw new \Exception("缺少必填项:".$key, 400);
    		}
    		if(isset($data[$key])){
	    		$product->$key = $data[$key];
    		}
    	}
    	// 保存变体
    	if(!count($data['variance'])){
    		$data['variance'] = [
    			[
    				'color' => $data['color'],
                    'size' => $data['size'],
                    'sku' => $data['parent_sku'],
                    'price' => $data['price'],
                    'inventory' => $data['inventory'],
                    'shipping' => $data['shipping'],
                    // 'enable' => $data['enable'] //商品启用状态（Y=上架，N=下架）
    			]
    		];
    	}
    	$product->variance_count = count($data['variance']);
    	if(!$product->save()){
    		$product->attributes = $product->getOldAttributes();
    		$product->error_message = json_encode($product->getErrors(),JSON_UNESCAPED_UNICODE);
    		throw new \Exception('商品保存失败:'.json_encode($product->getErrors(),JSON_UNESCAPED_UNICODE), 500);
    	}
    	WishFanbenVariance::saveByProduct($product,$data['variance']); 	// 必须先保存product,得到自增id
    	$product->enable();
    	$product->save();
    	return $product;
    }


    /**
     * 队列同步商品 (run in console)
     * @author huaqingfeng
     * @version 2016-07-20
     */
    public static function manualSyncCallback($queue){

    	$shops = [$queue->site_id];
    	if($queue->data('shop')){
    		$shops = $queue->data('shop');
    	}
    	foreach($shops as $site_id){
	    	// 抓取平台上的商品
	    	$user = SaasWishUser::find()->where([
	    		'site_id'=>$site_id
	    	])->one();
			$product = new Product($site_id);
			$allProducts = $product->getProductsFromPlatform();
			// 保存
			$result = $product->saveAllProducts($allProducts,function($result,$product)use($queue){
				// 进度增加
				$queue->addProgress();
				// $queue->setLog('products',$product->parent_sku);
				echo PHP_EOL.$product->parent_sku.' success'; 
			},$site_id);
			// 修改last_time
			$user->last_product_success_retrieve_time = date('Y-m-d H:i:s');
			$user->save();
    	}
    	return true;
    }

    /**
     * 发布商品
     * @param  [type] $queue [description]
     * @author huaqingfeng
     * @version  2016-04-08
     */
    public static function wishPushSyncCallback($queue){
    	$site_id = $queue->data('site_id');
    	$user = SaasWishUser::find()->where([
    		'site_id'=>$site_id
    	])->one();
    	// 遍历商品
		$error = [];
		foreach($queue->data('products') as $product_id){
			if($product = WishFanben::find()->where([
				'id'=>$product_id
			])->one()){
				// 发布商品
				try{
					$result = $product->push();
					$error[$product->parent_sku] = 'success';
				}catch(\Exception $e){
					$product->error_message = $e->getMessage();
					$product->save();
					$error[$product->parent_sku] = $e->getMessage();
				}
			}else{
				echo 'no prodcut:'.$product_id.PHP_EOL;
			}
		}
		if(count($error)){
			$queue->data(['error'=>$error]);
		}
		return true;
    }

    /**
     * 批量将商品添加到发布队列
     * @author huaqingfeng
     * @version  2016-04-08
     * @param integer $site_id
     * @param Array $products 商品fanben_id集合
     */
    public static function addProductsPushQueue($site_id,Array $products = []){
    	$type = 'wish:push';
    	$queue = Queue::add($type,$site_id.'-'.join('_',$products),[
    		'products'=>$products,
    		'site_id'=>$site_id
    	]);
    	// 批量更新状态为 发布中 type = 3
    	WishFanben::updateAll([
    		'type'=>3
    	],[
    		'IN','id',$products
    	]);
    	return $queue;
    }


	/**
     * 自动同步任务用
     * @author huaqingfeng
     * @version  2016-04-12
     * @return [type] [description]
     */
    public static function manualSyncGetAccountsByInterval($getUid){
    	$intervals = [72,168];
    	$delay = [7200,16800];
		$accounts = [];
    	foreach($intervals as $idx=>$itv){
    		$key = $delay[$idx];
    		$accounts[$key] = [];
    		$uid = $getUid($itv);
    		// 查询所有绑定的wish店铺
    		$users = SaasWishUser::find()->where([
    			'is_active'=>1
    		])->andWhere([
    			'IN','uid',$uid
    		]);
    		foreach($users->each() as $user){
    			$accounts[$key][] = $user->site_id;
    		}
    	}
    	return $accounts;
    }



}
