<?php

namespace eagle\modules\listing\controllers;

use Yii;
use eagle\modules\listing\models\EbayMuban;
use yii\data\Pagination;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayMubanDetail;
use common\helpers\Helper_Array;
use eagle\models\EbayCategory;
use eagle\models\EbayCategoryfeature;
use eagle\models\EbaySpecific;
use eagle\models\EbayShippingservice;
use eagle\models\EbaySite;
use eagle\models\EbayShippinglocation;
use eagle\models\EbayCountry;
use common\helpers\Helper_Siteinfo;
use eagle\modules\listing\models\Mytemplate;
use eagle\modules\listing\models\EbaySalesInformation;
use eagle\modules\listing\models\EbayCrossselling;
use yii\helpers\Json;
use eagle\models\EbayRegionLocation;
use common\api\ebayinterface\additem;
use common\helpers\Helper_Util;
use common\api\ebayinterface\EbayInterfaceException_Connection_Timeout;
use eagle\modules\listing\models\EbayItem;
use eagle\models\EbayAutoadditemset;
use app\models\Template;
use eagle\modules\listing\models\EbayCrosssellingItem;
use common\api\ebayinterface\uploadsitehostedpictures;
use yii\helpers\Html;
use eagle\modules\listing\helpers\MubanHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use common\api\ebayinterface\getsuggestedcategories;
use common\api\ebayinterface\base;
use eagle\modules\listing\models\EbayMubanProfile;
use yii\base\Exception;
use eagle\modules\listing\models\EbayAccountMap;
use common\api\ebayinterface\getitem;
use eagle\modules\ebay\apihelpers\EbayCommonInfoApiHelper;


class EbaymubanController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * ebay刊登范本列表
	 * @author fanjs
	 */
    public function actionList()
    {
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/list');
        $ebayselleruserid = SaasEbayUser::find()
                                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                                ->andwhere('listing_status = 1')
                                ->andwhere('listing_expiration_time > '.time())
                                ->select('selleruserid')
                                ->asArray()
                                ->all();
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->asArray()
                        ->all();
        $data= EbayMuban::find();
        //不显示 解绑的账号的订单
        $data->andWhere(['selleruserid'=>$ebayselleruserid]);
    	if (!empty($_REQUEST['itemtitle'])){
    		$data->andWhere('itemtitle like :t',[':t'=>'%'.$_REQUEST['itemtitle'].'%']);
    	}
    	if (!empty($_REQUEST['sku'])){
    		$data->andWhere('sku = :t',[':t'=>$_REQUEST['sku']]);
    	}
    	if (!empty($_REQUEST['desc'])){
    		$data->andWhere('desc = :t',[':t'=>$_REQUEST['desc']]);
    	}
    	if (!empty($_REQUEST['siteid'])){
    		$data->andWhere('siteid = :t',[':t'=>$_REQUEST['siteid']]);
    	}
    	if (!empty($_REQUEST['listingtype'])){
    		if ($_REQUEST['listingtype']=='Chinese'){
    			$data->andWhere('listingtype = :t',[':t'=>'Chinese']);
    		}else{
    			$data->andWhere('listingtype != :t',[':t'=>'Chinese']);
    		}
    	}
    	if (!empty($_REQUEST['selleruserid'])){
    		$data->andWhere('selleruserid = :t',[':t'=>$_REQUEST['selleruserid']]);
    	}
    	if (!empty($_REQUEST['paypal'])){
    		$data->andWhere('paypal = :t',[':t'=>$_REQUEST['paypal']]);
    	}
    	if (!empty($_REQUEST['isvariation'])){
    		$data->andWhere('isvariation = :t',[':t'=>$_REQUEST['isvariation']]);
    	}
    	if (!empty($_REQUEST['outofstockcontrol'])){
    		$data->andWhere('outofstockcontrol = :t',[':t'=>$_REQUEST['outofstockcontrol']]);
    	}
    	$data->orderBy('mubanid desc');
    	$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
    	$mubans = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
    	//只显示有权限的账号，lrq20170828
    	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
    	$selleruserids = array();
    	foreach($account_data as $key => $val){
    		$selleruserids[] = $key;
    	}
    	$ebayselleruserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 1')
                        ->andwhere(['selleruserid' => $selleruserids])
                        ->andwhere('listing_expiration_time > '.time())
                        ->all();
        return $this->render('list',['mubans'=>$mubans,'pages'=>$pages,'ebayselleruserid'=>$ebayselleruserid,'ebaydisableuserid'=>$ebaydisableuserid]);
    }

    /**
     * 编辑eBay刊登范本的页面
     * @author fanjs
     */
    function actionEdit(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/edit');
    	//数据初始化
    	if (isset($_REQUEST['mubanid'])){
			$data=EbayMuban::findOne($_REQUEST['mubanid'])->attributes;
			$detail = EbayMubanDetail::findOne($_REQUEST['mubanid']);
			if (!empty($detail)){
				$detaildata=$detail->attributes;
			}else{
				$detaildata = [];
			}
			$data=array_merge($data,$detaildata);
			$data = array_merge(EbayMuban::$default,$data);
		}else{
			$data=EbayMuban::$default;
		}

		//数据初始化:从item生成
		if (!\Yii::$app->request->isPost && isset($_REQUEST['itemid']) && strlen($_REQUEST['itemid'])){
			$item = EbayItem::find()->where(['itemid'=>$_REQUEST['itemid']])->with('detail')->one();
			
			if (!empty($item)){
				//生成范本时抓取item的最新数据
				$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$item->selleruserid]);
				if (!empty($ebayuser)){
					$api = new getitem();
					$api->resetConfig($ebayuser->DevAcccountID);
					$api->eBayAuthToken = $ebayuser->token;
					$result = $api->api($item->itemid);
					if (!$api->responseIsFailure() && isset($result['ItemID'])){
						if($api->save($result)){
							$item = EbayItem::find()->where(['itemid'=>$_REQUEST['itemid']])->with('detail')->one();
						}
					}
				}
				
				$data = MubanHelper::_update_muban_by_item ( $item, $data );
			}
		}
		if (\Yii::$app->request->isPost){
			$data=array_merge($data,$_POST);
			//处理post过来的图片数据
			if (is_array($data['imgurl'])){
				foreach ($data['imgurl'] as $k=>$v){
					if (strlen($v)==0){
						unset($data['imgurl'][$k]);
					}
				}
			}
			
			//保存新的variation
			if(isset($_REQUEST['isMuti']) && $_REQUEST['isMuti'] == 1 && strlen($_POST['act']) && ($_POST['act'] == 'save' || $_POST['act'] == 'savenew')){
// 			    print_r($_REQUEST);exit();
			    $variation = array (
			        'assoc_pic_key' => $_REQUEST['assoc_pic_key'],
			        'assoc_pic_count' => 0,
			        'Variation' => array (),
			        'Pictures' => array (),
			        'VariationSpecificsSet' => array ()
			    );
			    $nvl = array ();
			    foreach ( $_REQUEST['v_quantity'] as $i => $q ) {
			        if (strlen ( $q ) == 0 || strlen ( $_POST ['price'] [$i] ) == 0) {
			            continue;
			        }
			        $row = array (
			            'SKU' => trim($_POST ['v_sku'] [$i]),
			            'Quantity' => trim($q),
			            'StartPrice' => trim($_POST ['price'] [$i]),
			            'VariationProductListingDetails'=>[trim($_POST ['v_productid_name'])=>trim($_POST ['v_productid_val'] [$i])],
			            'VariationSpecifics' => array ()
			        );
			    
			        foreach ( $_REQUEST['nvl_name'] as $name ) {
			            $name=trim($name);
			            $row ['VariationSpecifics'] ['NameValueList'] [] = array (
			                'Name' => $name,
			                'Value' => trim($_POST [self::nameStringDecode ( $name )] [$i])
			            );
			    
			            $nvl [$name] [$_POST [self::nameStringDecode ( $name )] [$i]] = trim($_POST [self::nameStringDecode ( $name )] [$i]);
			        }
			        $variation ['Variation'] [] = $row;
			    
			        $imgArray = array();//一个属性多图片
			        if(isset($_REQUEST['img'] [$i]) && count($_REQUEST['img'][$i]) > 0){
			            for($k = 0;$k < count($_REQUEST['img'][$i]);$k++){
			                if(strlen($_REQUEST['img'][$i][$k])){
			                    $imgArray[] = trim($_REQUEST['img'][$i][$k]);
			                    $variation ['assoc_pic_count'] ++;
			                }
			            }
			        }
			        if (count($imgArray)) {
			            $variation ['Pictures'] [] = array (
			                'VariationSpecificPictureSet' => array (
			                    'PictureURL' =>$imgArray
			                ),
			                'Value' => trim($_POST [self::nameStringDecode ( $_POST ['assoc_pic_key'] )] [$i])
			            );
			        } else {
			            $variation ['Pictures'] [] = array (
			                'VariationSpecificPictureSet' => array (
			                    'PictureURL' => $imgArray
			                ),
			                'Value' => 'none_skip'
			            );
			        }
			    }
			    foreach ( $nvl as $name => $values ) {
			        @$variation ['VariationSpecificsSet'] ['NameValueList'] [] = array (
			            'Name' => $name,
			            'Value' => $values
			        );
			    }
// 			    $json =json_encode($variation);
// 			    $data['variation'] = $json;
			    $data['variation'] = $variation;
			}else if(isset($_REQUEST['isMuti']) && $_REQUEST['isMuti'] == 0 && strlen($_POST['act']) && ($_POST['act'] == 'save' || $_POST['act'] == 'savenew')){
			    $data['variation'] = array();
			}
			
			//如果用户修改的时候清空了删除图片，将默认数据中的图片也删除
			if(!isset($_POST['imgurl'])){
				$data['imgurl'] = '';
			}
			//多属性的值进行反序列化处理
			if (!empty($data['variation'])&&!is_array($data['variation'])){
				$data['variation']=Helper_Array::objecttoarray(json_decode($data['variation']));
			}
			
			$category=EbayCategory::find()->where('categoryid = :c and siteid = :s and leaf=1',[':c'=>$data['primarycategory'],':s'=>$data['siteid']])->one();
			if (empty($category)||$category->variationenabled!=1||$data['listingtype']=='Chinese'){
				$data['variation']='';
			}
			//处理post过来的物流设置信息，清空shippingservice为空的
			if (isset($data['shippingdetails']['ShippingServiceOptions'])){
				foreach ($data['shippingdetails']['ShippingServiceOptions'] as $k=>$v){
					if (strlen($v['ShippingService'])==0){
						unset($data['shippingdetails']['ShippingServiceOptions'][$k]);
					}
				}
			}
			if (isset($data['shippingdetails']['InternationalShippingServiceOption'])){
				foreach ($data['shippingdetails']['InternationalShippingServiceOption'] as $k=>$v){
					if (strlen($v['ShippingService'])==0){
						unset($data['shippingdetails']['InternationalShippingServiceOption'][$k]);
					}
				}
			}
			
			//处理用户是通过下方的预览保存等按钮过来的操作
			if (strlen($_POST['act'])){
				switch ($_POST['act']){
					//点击的保存按钮
					case 'save':
					case 'savenew':
						unset($data['mubanid']);
						if (strlen(@$_POST['mubanid'])&&$_POST['act']!='savenew'){
							$muban=EbayMuban::findOne(@$_POST['mubanid']);
						}else{
							$muban=new EbayMuban();
						}
						$muban->setAttributes($data,false);
						$muban->isvariation=0;
						if(is_array($data['variation'])&&count($data['variation'])>0){
							$muban->isvariation=1;
						}
						$muban->uid=Yii::$app->user->identity->getParentUid();
						if (isset($data['imgurl']['0'])){
							$muban->mainimg=$data['imgurl']['0'];
						}
						$muban->save();
						$mubandetail=EbayMubanDetail::find()->where('mubanid = '.$muban->mubanid)->one();
						if (empty($mubandetail)){
							$mubandetail = new EbayMubanDetail();
						}
						$mubandetail->mubanid=$muban->mubanid;
						$mubandetail->setAttributes($data,false);
						$mubandetail->save();
						$this->redirect(array('ebaymuban/list'));
						//echo '<script>window.close();</script>';
						//$this->render('index');
						break;
					case 'verify':
					case 'additem':
						$seu=SaasEbayUser::find()->where(['selleruserid' =>$data['selleruserid']])->one();
						if (empty($seu)){
							
						}
						$eiai=new additem();
						$eiai->resetConfig($seu->listing_devAccountID);
						if($_POST['act']=='verify'){
							$eiai->isVerify(true);
						}
						$eiai->siteID = $data['siteid'];
						$eiai->eBayAuthToken = $seu->listing_token;
						$eiai->setValues ( $data );
						if (isset($data['uuid'])){
							$uuid=$data['uuid'];
						}else {
							$uuid=$data['uuid']=Helper_Util::getLongUuid();
						}
							
						try {
							$_SESSION['result'] = $eiai->api ( $data, \Yii::$app->user->identity->getParentUid(), 0, $seu->selleruserid ,$uuid);
						}catch (EbayInterfaceException_Connection_Timeout $ex){
							throw new EbayInterfaceException_Connection_Timeout('连接eBay服务器超时');
						}
						if (!$eiai->responseIsFailure()){
							unset($data['uuid']);
						}
							
						$this->redirect(array('ebaymuban/verifyresult'));
						break;
					default:
						break;
				}
			}
		}
		// print_r($data['variation']);
		// var_dump($data['variation']);
		\yii::info(print_r($data['variation'],1),"file");
		if(is_null($data['siteid'])){
			$data['siteid']=0;
		}
		if(is_null($data['listingduration'])){
			$data['listingduration']='Days_3';
		}
		if(is_null($data['listingtype'])){
			$data['listingtype']='FixedPriceItem';
		}
		
		$condition=array();
		$product = [];
		$specifics=array();
		if (strlen($data['primarycategory'])){
			$ecf=EbayCategoryfeature::find()->where('siteid=:siteid and categoryid=:categoryid',array(':siteid'=>$data['siteid'],':categoryid'=>$data['primarycategory']))->one();
			if (!is_null($ecf)){
				$condition=array(
						'conditionenabled'=>$ecf->conditionenabled,
						'conditionvalues'=>$ecf->conditionvalues,
				);
				$product = [
					'isbnenabled'=>$ecf->isbnenabled,
					'upcenabled'=>$ecf->upcenabled,
					'eanenabled'=>$ecf->eanenabled,
				];
			}
			$specifics=EbaySpecific::find()->where('siteid=:siteid and categoryid=:categoryid',array(':siteid'=>$data['siteid'],':categoryid'=>$data['primarycategory']))->all();
		}
		/**
		 * 物流数据的构建
		 * $shippingservice:境内物流
		 * $ishippingservice:境外物流
		 */
		if ($data['siteid'] == 100) {
			$sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = 0';
		} else {
			$sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = '.$data['siteid'];
		}
		$shippingservice=Helper_Array::toArray(EbayShippingservice::findBySql($sql)->all());
		$shippingservice=EbayMuban::dealshippingservice($shippingservice);
		$ishippingservice = array ();
		foreach ( $shippingservice as $k => $v ) {
			if ($v ['internationalservice'] == 'true') {
				$ishippingservice [] = $v;
				unset ( $shippingservice [$k] );
			}
		}
		$shippingserviceall=array(
			'shippingservice'=>Helper_Array::toHashmap($shippingservice, 'shippingservice','description'),
			'ishippingservice'=>Helper_Array::toHashmap($ishippingservice,'shippingservice','description'),
			'shiplocation'=>Helper_Array::toHashmap(Helper_Array::toArray(EbayShippinglocation::findBySql(
					'select * from ebay_shippinglocation where siteid = '.$data['siteid'].' AND shippinglocation != \'None\'')->all()),'shippinglocation','description'),
			);
		$selectsite=EbaySite::findOne('siteid = :siteid',[':siteid'=>$data['siteid']]);
		$feature_array=array();
		if ($selectsite->listing_feature['BoldTitle']=='Enabled'){
			$feature_array['BoldTitle']='BoldTitle';
		}
		if ($selectsite->listing_feature['Border']=='Enabled'){
			$feature_array['Border']='Border';
		}
		if ($selectsite->listing_feature['Highlight']=='Enabled'){
			$feature_array['Highlight']='Highlight';
		}
		if ($selectsite->listing_feature['HomePageFeatured']=='Enabled'){
			$feature_array['HomePageFeatured']='HomePageFeatured';
		}
		if ($selectsite->listing_feature['FeaturedFirst']=='Enabled'){
			$feature_array['FeaturedFirst']='FeaturedFirst';
		}
		if ($selectsite->listing_feature['FeaturedPlus']=='Enabled'){
			$feature_array['FeaturedPlus']='FeaturedPlus';
		}
		if ($selectsite->listing_feature['ProPack']=='Enabled'){
			$feature_array['ProPack']='ProPack';
		}
		
		//处理下拉的paypal账号显示
		if(strlen($data['selleruserid'])){
			$paypals = EbayAccountMap::find()->where(['selleruserid'=>$data['selleruserid']])->all();
		}else{
			$paypals = EbayAccountMap::find()->all();
		}
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		//获取当前登陆用户的ebay账号绑定信息
		$ebayselleruserid=SaasEbayUser::find()
			->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->identity->getParentUid(),':expiretime'=>time()])
			->andwhere(['selleruserid' => $selleruserids])
			->asArray()
			->all();
		$carrydata=array(
			'ebayselleruserid'=>Helper_Array::toHashmap($ebayselleruserid, 'selleruserid', 'selleruserid'),
			'feature_array'=>$feature_array,
			'locationarr'=>Helper_Array::toHashmap(Helper_Array::toArray(EbayCountry::findBySql('select * from ebay_country order by description asc')->all()),'country','description'),
			'shippingserviceall'=>$shippingserviceall,
			'sitearr'=>Helper_Array::toHashmap(Helper_Siteinfo::getEbaySiteIdList(), 'no', 'zh'),
			'listingtypearr'=>['Chinese'=>'拍卖','FixedPriceItem'=>'一口价'],
			'condition'=>$condition,
			'product'=>$product,
			'specifics'=>$specifics,
			'mytemplates'=>Helper_Array::toHashmap(Mytemplate::find()->asArray()->all(),'id','title'),
			'basicinfos'=>Helper_Array::toHashmap(EbaySalesInformation::find()->asArray()->all(),'id','name'),
			'crosssellings'=>Helper_Array::toHashmap(EbayCrossselling::find()->asArray()->all(),'crosssellingid','title'),	
			'paypals'=>$paypals,
			'data'=>$data
			);
		if (isset($selectsite->buyer_requirement['LinkedPayPalAccount']) && strlen($selectsite->buyer_requirement['LinkedPayPalAccount'])){
			$carrydata['buyerrequirementenable']=$selectsite->buyer_requirement['LinkedPayPalAccount'];
		}
		if(is_array($selectsite->return_policy)){
			$carrydata['returnpolicy']=$selectsite->return_policy;
		}
		if(is_array($selectsite->payment_option)){
			$carrydata['paymentoption']=Helper_Array::toHashmap($selectsite->payment_option, 'PaymentOption','Description');
		}
		if(is_array($selectsite->tax_jurisdiction)){
			$carrydata['salestaxstate']=Helper_Array::toHashmap($selectsite->tax_jurisdiction,'JurisdictionID','JurisdictionName');
		}
		if(is_array($selectsite->dispatchtimemax)){
			$carrydata['dispatchtimemax']=Helper_Array::toHashmap($selectsite->dispatchtimemax,'DispatchTimeMax','Description');
		}
		
		//整理模块范本
		$pfiles=EbayMubanProfile::find()->asArray()->all();
		$_new_pfiles=array();
		if (count($pfiles)){
			foreach ($pfiles as  $_file){
				$_new_pfiles[$_file['type']][]=$_file;
			}
		}
		if (!isset($_new_pfiles['shippingset'])){
			$_new_pfiles['shippingset'] = [];
		}
		if (!isset($_new_pfiles['returnpolicy'])){
			$_new_pfiles['returnpolicy'] = [];
		}
		if (!isset($_new_pfiles['buyerrequire'])){
			$_new_pfiles['buyerrequire'] = [];
		}
		if (!isset($_new_pfiles['plusmodule'])){
			$_new_pfiles['plusmodule'] = [];
		}
		if (!isset($_new_pfiles['account'])){
			$_new_pfiles['account'] = [];
		}
		$carrydata['profile'] = $_new_pfiles;

		
    	return $this->render('edit',$carrydata);
    }
    
    /**
     * 选择刊登的eBay类目
     * @author fanjs
     */
    function actionSelectebaycategory(){
    	if (Yii::$app->request->isPost) {
    		$elementid=@$_REQUEST['elementid'];
    		echo <<<EOF
<script language="javascript">
  var es=window.opener.document.getElementById('{$elementid}');
  es.value="{$_POST['primaryCategory']}";
  window.opener.document.a.target='';
  window.opener.document.a.submit();
  window.close();
</script>
EOF;
    		die ();
    	}
    	return $this->render('selectebaycategory',['elementid'=>@$_REQUEST['elementid'],'siteid'=>$_REQUEST['siteid']]);
    }
    
    /**
     * 选择刊登的eBay类目
     * @author fanjs
     */
    function actionSelectebaycategoryforrevise(){
    	if (Yii::$app->request->isPost) {
    		$elementid=@$_REQUEST['elementid'];
    		echo <<<EOF
<script language="javascript">
  var es=window.opener.document.getElementById('{$elementid}');
  es.value="{$_POST['primaryCategory']}";
  window.close();
</script>
EOF;
    		die ();
    	}
    	return $this->render('selectebaycategory',['elementid'=>@$_REQUEST['elementid'],'siteid'=>$_REQUEST['siteid']]);
    }
    
    /**ajax获取eBay的具体类目
     * @author fanjs
   	 */
    function actionGetEbayCats(){
    	//从数据库中取
    	$sql='select * from ebay_category where siteid = '.$_REQUEST['siteid'].' AND level = '.$_REQUEST['level'];
    	if($_REQUEST['level']>1){
    		$sql.=' AND pid = '.$_REQUEST['pid'];
    	}
    	$items = EbayCategory::findBySql($sql)->all();
    	Helper_Array::toArray($items);
    	if (count ( $items )) {
    		$r = $items;
    	}
    	exit ( Json::encode( $r ) );
    }
    
    /**
     * 预览刊登范本
     * @author fanjs
     */
    function actionPreview(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/preview');
    	if(Yii::$app->request->isPost)
    	{
    		$siteid = $_POST ['siteid'];
    		//处理post过来的物流设置信息，清空shippingservice为空的
    		foreach ($_POST ['shippingdetails']['ShippingServiceOptions'] as $k=>$v){
    			if (strlen($v['ShippingService'])==0){
    				unset($_POST ['shippingdetails']['ShippingServiceOptions'][$k]);
    			}
    		}
    		foreach ($_POST ['shippingdetails']['InternationalShippingServiceOption'] as $k=>$v){
    			if (strlen($v['ShippingService'])==0){
    				unset($_POST ['shippingdetails']['InternationalShippingServiceOption'][$k]);
    			}
    		}
    		if(!empty($_POST ['shippingdetails'] ['InternationalShippingServiceOption'])){
    			$excludeshiptoLocation = @$_POST ['shippingdetails'] ['ExcludeShipToLocation'];
    			$internationalshippingserviceoption = @$_POST ['shippingdetails'] ['InternationalShippingServiceOption'];
    		}
    	}
    	else
    	{
    		$mubanid = trim($_REQUEST['mubanid']);
    		$ebay_muban = EbayMuban::findOne($mubanid);
    	
    		$siteid = $ebay_muban->siteid;
    	
    		$excludeshiptoLocation = $ebay_muban->detail->shippingdetails;
    		$excludeshiptoLocation = $excludeshiptoLocation['ExcludeShipToLocation'];
    	
    		$internationalshippingserviceoption = $ebay_muban->detail->shippingdetails;
    		$internationalshippingserviceoption = $internationalshippingserviceoption['InternationalShippingServiceOption'];
    	}
    		
    		
    	// 物流
    	if ($siteid == 100) {
    		$sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = 0';
    	} else {
    		$sql='select * from ebay_shippingservice where validforsellingflow = \'true\' AND siteid = '.$siteid;
    	}
    	$shippingservice=Helper_Array::toArray(EbayShippingservice::findBySql($sql)->all());
    	$shippingservice=EbayMuban::dealshippingservice($shippingservice);
    		
    	$ishippingservice = array ();
    	foreach ( $shippingservice as $k => $v ) {
    		if ($v ['internationalservice'] == 'true') {
    			$ishippingservice [] = $v;
    			unset ( $shippingservice [$k] );
    		}
    	}
    	$shippingserviceall=array(
    			'shippingservice'=>Helper_Array::toHashmap($shippingservice,'shippingservice','description'),
    			'ishippingservice'=>Helper_Array::toHashmap($ishippingservice,'shippingservice','description'),
    			'shiplocation'=>Helper_Array::toHashmap(Helper_Array::toArray(EbayShippinglocation::findBySql('select * from ebay_shippinglocation where siteid = '
    							.$siteid.' AND shippinglocation != \'None\'')->all()),'shippinglocation','description'),
    	);
    		
    	// 国家,平台,货币
    	$es = EbaySite::findOne($siteid);
    		
    	// 详细运费处理开始---------------
    	if (!empty( $_POST['shippingdetails']['InternationalShippingServiceOption'])) {
    		// 地理地区匹配
    		// 需要的国家
    		$nationarray = array ();
    		// 需要的区域
    		$areaarray = array ();
    		// 视图需要显示的区域和国家
    		$viewshipsto = array ();
    	
    		// （可运至国家表=>屏蔽国家表） 的区域数组对应关系 。 'Africa'
    		// 、'Oceania'这两个在屏蔽国家表里有，在可运至国家表中还没有。
    		$shippingareaarray = array (
    				'Americas' => array (
    						'Central America and Caribbean',
    						'North America',
    						'South America'
    				),
    				'Asia' => array (
    						'Asia',
    						'Southeast Asia',
    						'Middle East'
    				),
    				'EuropeanUnion' => 'EuropeanUnion',
    				'Worldwide' => 'Worldwide',
    				'Europe' => 'Europe',
    				'Africa' => 'Africa',
    				'Oceania' => 'Oceania'
    		);
    		// 选定平台中Ebay_Shippinglocation表中所有值 排除区域和屏蔽国家
    		$nationshipping = EbayShippinglocation::find()->andWhere("siteid = :site and shippinglocation <> 'None'",[':site'=>$siteid]);
    		//if (! empty ( $_POST ['shippingdetails'] ['ExcludeShipToLocation'] )) {
    		if (!empty( $excludeshiptoLocation )) {
    			//$nationshipping = $nationshipping->where ( 'shippinglocation NOT in (?)', $_POST ['shippingdetails'] ['ExcludeShipToLocation'] );
    			$nationshipping = $nationshipping->andWhere ( 'shippinglocation NOT in (:s)',[':s'=>implode(',', $excludeshiptoLocation)] );
    		}
    		$nationshipping=$nationshipping->all();
    		//$nationshipping = $nationshipping->setColumns ( 'shippinglocation,description' )
    		// 把$nationshipping把数组转换为键值的形式
    		$nationshippingkv = array ();
    		foreach ( $nationshipping as $v ) {
    			$nationshippingkv [$v->shippinglocation] = $v->description;
    		}
    	
    		//foreach ( $_POST ['shippingdetails'] ['InternationalShippingServiceOption'] as $k => $v ) {
    		foreach ( $internationalshippingserviceoption as $k => $v ) {
    			if (empty ( $v ['ShipToLocation'] ) || empty ( $v ['ShippingService'] ))
    				continue;
    			$shiptolocation = $v ['ShipToLocation'];
    			foreach ( $shiptolocation as $shipto ) {
    				// 判断是否是区域
    				if (array_key_exists ( $shipto, $shippingareaarray )) {
    					if (! (in_array ( $shipto, $viewshipsto ) || in_array ( 'Worldwide', $viewshipsto ))) {
    						$viewshipsto [] = $nationshippingkv [$shipto];
    					}
    					if (is_array ( $shippingareaarray [$shipto] )) {
    						foreach ( $shippingareaarray [$shipto] as $area ) {
    							if (empty ( $areaarray [$area] )) {
    								$areaarray [$area] = array (
    										$k
    								);
    							} else {
    								$areaarray [$area] [] = $k;
    							}
    						}
    					} else {
    						$area = $shippingareaarray [$shipto];
    						if (empty ( $areaarray [$area] )) {
    							$areaarray [$area] = array (
    									$k
    							);
    						} else {
    							$areaarray [$area] [] = $k;
    						}
    					}
    				} else {
    					// 不去保存none的和屏蔽国家里的
    					//if ($shipto == 'None' || (! empty ( $_POST ['shippingdetails'] ['ExcludeShipToLocation'] ) && in_array ( $shipto, $_POST ['shippingdetails'] ['ExcludeShipToLocation'] ))) {
    					if ($shipto == 'None' || (! empty ( $excludeshiptoLocation ) && in_array ( $shipto, $excludeshiptoLocation ))) {
    						continue;
    					}
    					if (empty ( $nationarray [$shipto] )) {
    						$nationarray [$shipto] = array (
    								$k
    						);
    					} else {
    						$nationarray [$shipto] [] = $k;
    					}
    				}
    			}
    		}
    	
    		$shippingarea = array (
    				'areaarray' => $areaarray,
    				'nationarray' => $nationarray
    		);
    	
    		// 区域里包含的国家
    		$areanation = array ();
    	
    		// 查询时需要排除（“区域”）、屏蔽的国家
    		$areaall = array_merge ( $shippingareaarray ['Americas'], $shippingareaarray ['Asia'], array (
    				$shippingareaarray ['EuropeanUnion'],
    				$shippingareaarray ['Europe'],
    				$shippingareaarray ['Africa'],
    				$shippingareaarray ['Oceania']
    		), (empty ( $excludeshiptoLocation ) ? array () : $excludeshiptoLocation) );
    		$areanation = EbayRegionLocation::find();
    		$areanation->andWhere('location not in (:s)',[':s'=>implode(',',$areaall)]);
    		if (array_key_exists ( 'Worldwide', $areaarray )) {
    			$areanation->andWhere('region not in ("Additional Locations","Domestic Location")');
    		} else {
    			$areanation->andWhere('region not in (:r)',[':r'=>implode(',',array_keys ( $areaarray ))]);
    		}
    		$areanation = $areanation->select(['location','description'])->asArray()->all();
    	
    		// 把$areanation(区域里包含的国家)数组转换为键值的形式
    		$areanationkv = array ();
    		foreach ( $areanation as $v ) {
    			$areanationkv [$v ['location']] = $v ['description'];
    		}
    	
    		// 不在选定区域里的国家
    		$unareanation = array_diff_key ( $nationarray, $areanationkv );
    		if (in_array ( 'Worldwide', $viewshipsto )) {
    			$viewshipsto = 'Worldwide';
    		} else {
    			foreach ( $unareanation as $k => $v ) {
    				$viewshipsto [] = $nationshippingkv [$k];
    			}
    		}
    	
    		// 需要的国家简写与国家名对应数组
    		$nationstokv = array ();
    	
    		foreach ( $nationarray as $k => $v ) {
    			$nationstokv [$k] = $nationshippingkv [$k];
    		}
    		foreach ( $areanationkv as $k => $v ) {
    			if (array_key_exists ( $k, $nationstokv )) {
    				continue;
    			}
    			if (array_key_exists ( $k, $nationshippingkv )) {
    				$nationstokv [$k] = $nationshippingkv [$k];
    			} else {
    				$nationstokv [$k] = $v;
    			}
    		}
    		asort ( $nationstokv );
    	
    		// 详细运费处理结束---------------
    	}
    		
    	// 针对不同的刊登方式显示不同的日期
    	$listdate_all = array (
    			'Days_1' => '1',
    			'Days_3' => '3',
    			'Days_5' => '5',
    			'Days_7' => '7',
    			'Days_10' => '10',
    			'Days_30' => '30',
    			'GTC' => 'GTC'
    	);
    	$carrydata=array(
    			'shippingserviceall'=>$shippingserviceall,
    			'sitearr'=>Helper_Array::toHashmap(Helper_Siteinfo::getEbaySiteIdList(), 'no', 'zh'),
    			'shippingarea'=>@$shippingarea,
    			'viewshipsto'=>@$viewshipsto,
    			'viewnation'=>@$nationstokv,
    			'listdate_all'=>$listdate_all,
    			'select_site'=>$es,
    			'locationarr'=>Helper_Array::toHashmap(EbayCountry::findBySql('select * from ebay_country order by country asc')->all(),'country','description'),
    			'data'=>$_POST,
    	);
    	return $this->renderPartial('preview',$carrydata);
    }
    
    /**
     * 检测ebay范本的结果页面
     * @author fanjs
     */
    function actionVerifyresult(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/verifyresult');
    	return $this->render('_result',array('result'=>$_SESSION['result']));
    }
    
    /**
     * 批量检测eBay范本
     * @author fanjs
     */
    function actionListselectverify(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/listselectverify');
    	$ids = explode(',',$_REQUEST['mubanid']);
    	$list = EbayMuban::findAll(['mubanid'=>$ids]);
    	return $this->render('listselectverify',array('list'=>$list));
    }
    
    /**
     * 批量检测的ajax处理
     * @author fanjs
     */
    function actionAjaxverify(){
    	$data=EbayMuban::findOne($_POST['mubanid'])->attributes;
    	$detaildata=EbayMubanDetail::findOne($_POST['mubanid'])->attributes;
    	$data=array_merge($data,$detaildata);
    	$values=additem::valueMergeWithDefault($data);
    	$siteid=$values['siteid'];
    	$es=EbaySite::findOne(['siteid'=>$siteid]);
    	$values['site']=$es->site;
    	$values['paypal']=trim($values['paypal']);
    	$seu=SaasEbayUser::findOne(['selleruserid'=>$values['selleruserid']]);
    	$eiai=new additem();
    	if($_POST['act']=='verify'){
    		$eiai->isVerify(true);
    	}
    	if (isset($values['uuid'])){
    		$uuid=$values['uuid'];
    	}else {
    		$uuid=$values['uuid']=Helper_Util::getLongUuid();
    	}
    	$eiai->siteID = $siteid;
    	$eiai->resetConfig($seu->listing_devAccountID);
    	$eiai->eBayAuthToken = $seu->listing_token;
    	$eiai->setValues ( $values );
    	
    	$uid = \Yii::$app->user->identity->getParentUid() == 0 ? \Yii::$app->user->id : \Yii::$app->user->identity->getParentUid();
    	$result = $eiai->api ( $data, $uid, 0, $seu->selleruserid ,$uuid);
    	if(isset($result['Errors'])){
    		$errors = array();
    		if(!isset($result['Errors'][0])){
    			$errors[0]=$result['Errors'];
    		}else{
    			$errors=$result['Errors'];
    		}
    	}
    	$show = '';
    	if(isset($result['Fees'])){
    		$fee=Helper_Array::toHashmap($result['Fees']['Fee'],'Name','Fee');
    		foreach ($fee as $k=>$f){
    			if ($f>0){
    				$show.=$k.':'.$f.' '.$result['ebayfee']['currency'].'<br>';
    			}
    		}
    	}
    	if(isset($result['Errors'])){
    		foreach ($errors as $error){
    			$show.=$error['ShortMessage'].'<br>';
//    			$show.=str_replace('>','&gt;',str_replace('<','&lt;',$error['LongMessage'])).'<br>';
    			$show.=Html::encode($error['LongMessage']).'<br>';
    		}
    	}
    	$result['show']=$show;\Yii::info(print_r($result,true));
    	exit(Json::encode($result));
    }
    
    /**
     * 批量刊登
     * @author million 20140821
     */
    function actionListselectadd(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/listselectadd');
    	$ids = explode(',',$_REQUEST['mubanid']);
    	$list = EbayMuban::findAll(['mubanid'=>$ids]);
    	return $this->render('listselectadd',array('list'=>$list));
    }
    
    /**
     * 检测重复刊登
     * @author fanjs
     */
    function actionCheckrepeatmuban(){
    	if (Yii::$app->request->isPost){
    		$repeat='';
    		$item=EbayItem::find()->where('itemtitle = :title and selleruserid = :sellerid and listingstatus = \'Active\'',array(':title'=>$_POST['itemtitle'],':sellerid'=>$_POST['selleruserid']))->count();
    		if ($item>0){
    			$repeat.=' itemtitle';
    		}
    		if (isset($_POST['sku'])&&strlen($_POST['sku'])){
    			$item=EbayItem::find()->where('sku = :sku and selleruserid = :sellerid and listingstatus = \'Active\'',array(':sku'=>$_POST['sku'],':sellerid'=>$_POST['selleruserid']))->count();
    			if ($item>0){
    				$repeat.=' sku';
    			}
    		}print_r($repeat);
    		return $repeat;
    	}
    }
    
    /**
     * 删除范本
     * @author fanjs
     */
    function actionDelete(){
    	if (Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/delete');
    		try {
    			$ids = explode(',',$_POST['mubanid']);
    			$ids = array_filter($ids);
    			EbayMuban::deleteAll(['mubanid'=>$ids]);
    			EbayMubanDetail::deleteAll(['mubanid'=>$ids]);
    		}catch (\Exception $ex){
    			return $ex->getMessage();
    		}
    		return 'success';
    	}
    }
    
    /**
     * 范本的刊登记录
     * @author fanjs
     */
    function actionHistory(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/history');
    	$items=EbayItem::find()->where(['mubanid'=>$_REQUEST['mubanid']])->orderBy('createtime DESC')->all();
    	return $this->render('history',['items'=>$items]);
    }
    
    /**
     * 范本添加定时刊登
     * @author fanjs
     */
    function actionAdditemset(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/additemset');
    	$mids=@$_REQUEST['mubanid'];
    	if (isset($_REQUEST['timerid'])&&strlen($_REQUEST['timerid'])>0&&strpos($_REQUEST['timerid'],',')===false){
    		$eas=EbayAutoadditemset::findOne(['timerid'=>$_REQUEST['timerid']]);
    		$mids=$eas->mubanid;
    		if (empty($eas)){
    			$this->render('//errorview',['title'=>'编辑定时刊登','error'=>'数据有误']);
    		}
    	}else{
    		$eas=new EbayAutoadditemset();
    	}
    	$ems=EbayMuban::findBySql('select * from ebay_muban where mubanid in ('.$mids.')')->all();
    	if(Yii::$app->request->isPost){
    		$eas->gmt=$_POST['gmt'];
    		//激活时区
    		$eas->timeZoneActive ();
    		//必要设置
    		$eas->day_start_date =date('Ymd',strtotime($_POST['day_start_date']));
    		$eas->day_start_time =$_POST['day_start_time_hour'].$_POST['day_start_time_minute'];
    		$eas->day_start_date2 = $eas->day_start_date;
    		$eas->uid=\Yii::$app->user->identity->getParentUid();
    		//清空最后运行时间
    		$eas->last_runtime= null;
    		$error = [];
    		if (date ( "YmdHi" ) > $eas->day_start_date . $eas->day_start_time) {
    			$error ['day_start_date'] = '开始时间小于当前时间';
    		}
    		//重新设置时区
    		$eas->timeZoneRestore ();
    		if (count($error)==0){
    			//保存
    			if (!$eas->isNewRecord){
    				$eas->itemtitle=trim($_POST['itemtitle'][$eas->mubanid]);
//    				$eas->selleruserid=$eas->ebay_muban->selleruserid;
    				$eas->save();
    				echo '<script> window.opener.location.reload();window.close();</script>';
    			}else {	//批量添加
    				$whatDay=$_POST['whatday'];
    				if (count($whatDay)==0){
    					$whatDay=array(7,1,2,3,4,5,6);
    				}
    				//先计算第一个模板的时间
    				$base=$eas->day_start_date.' '.$eas->day_start_time.'00';
    	
    				$totalday=ceil($_POST['loop']/$_POST['loop_perday']);
    	
    				$time_arr=array();
    				$whatdayCount=0;
    	
    				for ($a=0;$a<$totalday;$a++){
    					do {
    						$new_time=strtotime($base)+$a*86400*$_POST['loop_per']+$whatdayCount*86400;
    	
    						if (!in_array(date('N',$new_time), $whatDay)){
    							$whatdayCount++;
    							continue;
    						}
    						for ($b=0;$b<$_POST['loop_perday'];$b++){
    							//相同模板间隔时间
    							$new_time2=$new_time+$b*60*$_POST['time_split_samesku_unit']*$_POST['time_split_samesku'];
    							if (in_array(date('N',$new_time2),$whatDay)){
    								$time_arr[]=$new_time2;
    							}
    						}
    						break;
    					}while (true);
    				}
    				foreach ($ems as $_mcount=> $m){
    					foreach ($time_arr as $runtime){
    						$aset=new EbayAutoadditemset();
    						$val=$eas->attributes;
    						Helper_Array::removeEmpty($val);
    						$aset->setAttributes($val);
    						$new_time=$runtime+$_mcount*60*$_POST['time_split_unit']*$_POST['time_split'];
    						if (!in_array(date('N',$new_time),$whatDay)){
    							//判断累加是否会超出星期范围
    							continue;
    						}
    						$aset->day_start_date=date('Ymd',$new_time);
    						$aset->day_start_time=date('Hi',$new_time);
    						$aset->mubanid=$m->mubanid;
    						$aset->selleruserid=$m->selleruserid;
    						$aset->itemtitle=trim($_POST['itemtitle'][$m->mubanid]);
    						$aset->save();
    					}
    				}
    				echo '<script>alert(\'操作已成功\');window.close();</script>';
    			}
    		}else{
    			echo '<script>alert("操作失败,设置时间有误");</script>';
    		}
    	}
    	return $this->render('additemset',array('mubans'=>$ems,'eas'=>$eas));
    }
    
    /**
     * eBay刊登的风格模板管理
     * @author fanjs
     */
    public function actionTemplatelist(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/templatelist');
        // $ebayselleruserid = SaasEbayUser::find()
        //                         ->where('uid = '.\Yii::$app->user->identity->getParentUid())
        //                         ->andwhere('listing_status = 1')
        //                         ->andwhere('listing_expiration_time > '.time())
        //                         ->select('selleruserid')
        //                         ->asArray()
        //                         ->all();
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->asArray()
                        ->all();
        $data = Mytemplate::find();
    	if (\Yii::$app->request->isPost){
    		if (isset($_POST['title'])&&strlen($_POST['title'])){
    			$data->andWhere(['title'=>$_POST['title']]);
    		}
    		if (isset($_POST['mubantype'])&&strlen($_POST['mubantype'])){
    			$data->andWhere(['type'=>$_POST['mubantype']]);
    		}
    	}
    	$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
    	$lists = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
    	return $this->render('templatelist',['list'=>$lists,'pages'=>$pages,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * eBay刊登风格模板的编辑
     * @author fanjs
     */
    public function actionTemplateedit(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/templateedit');
    	if (\Yii::$app->request->isPost){
    		if (isset($_POST['tid'])&&$_POST['tid']>0){
    			$mytemplate = Mytemplate::findOne($_POST['tid']);
    		}else{
    			$mytemplate=new Mytemplate();
    		}
    		$mytemplate->title=$_POST['title'];
    		$mytemplate->pic=$_POST['pic'];
    		$mytemplate->content=$_POST['content'];
    		$mytemplate->type='0';
    		$mytemplate->save(false);
    		echo '<script>window.opener.location.reload();alert("SUCCESS");window.close();</script>';
    	}else{
	    	if (isset($_REQUEST['tid'])){
	    		$mt=Mytemplate::findOne($_REQUEST['tid']);
	    	}else{
	    		$mt=new Mytemplate();
	    	}
	     	$publictemplate=Template::find()->all();
	    	return $this->render('templateedit',['mt'=>$mt,'publictemplates'=>$publictemplate]);
    	}
    }
    
    /**
     * eBay刊登风格模板的删除
     * @author fanjs
     */
    public function actionTemplatedelete(){
    	if(\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/templatedelete');
    		try {
    			$ids = explode(',',$_POST['id']);
    			$ids = array_filter($ids);
    			if (Mytemplate::deleteAll(['id'=>$ids])){
    				return 'success';
    			}
    			return 'failure';
    		} catch (\Exception $e) {
    			return $e->getMessage();
    		}
    	}
    }
    
    /**
     * eBay刊登风格编辑时选择基本模板
     * @author fanjs
     */
    public function actionSelecttemplatedata(){
    	if(\Yii::$app->request->isPost){
    		$t=Template::findOne($_POST['tid']);
    		if($_POST['type']=='pic'){
    			exit($t->pic);
    		}
    		if($_POST['type']=='content'){
    			exit($t->content);
    		}
    	}
    }
    
    /**
     * eBay销售信息范本列表
     * @author fanjs
     */
    public function actionSalesinfolist(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/salesinfolist');
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->asArray()
                        ->all();
    	$data = EbaySalesInformation::find();
    	$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
    	$lists = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
    	return $this->render('salesinfolist',['list'=>$lists,'pages'=>$pages,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * 销售信息范本的编辑
     * @author fanjs
     */
    public function actionSalesinfoedit(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/salesinfoedit');
    	if (\Yii::$app->request->isPost){
    		if (isset($_POST['sid'])&&$_POST['sid']>0){
    			$esi = EbaySalesInformation::findOne($_POST['sid']);
    		}else{
    			$esi=new EbaySalesInformation();
    		}
    		if ($esi->isNewRecord){
    			$esi->created = time();
    			$esi->updated = time();
    		}else{
    			$esi->updated = time();
    		}
    		$esi->uid=\Yii::$app->user->identity->getParentUid();
    		$esi->name=$_POST['name'];
    		$esi->payment=$_POST['payment'];
    		$esi->delivery_details=$_POST['deliverydetails'];
    		$esi->terms_of_sales=$_POST['termsofsales'];
    		$esi->about_us=$_POST['aboutus'];
    		$esi->contact_us=$_POST['contactus'];
    		$esi->save();
    		echo '<script>alert("操作已成功");window.opener.location.reload();window.close();</script>';
    	}
    	if (isset($_REQUEST['tid'])){
    		$si=EbaySalesInformation::findOne($_REQUEST['tid']);
    	}else{
    		$si=new EbaySalesInformation();
    	}
    	return $this->render('salesinfoedit',['si'=>$si]);
    }
    
    /**
     * 删除销售信息范本
     * @author fanjs
     */
    public function actionSalesinfodelete(){
    	if(\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/salesinfodelete');
    		try {
    			if (EbaySalesInformation::deleteAll(['id'=>$_POST['id']])){
    				return 'success';
    			}
    			return 'failure';
    		} catch (\Exception $e) {
    			return $e->getMessage();
    		}
    	}
    }
    
    /**
     * eBay交叉销售信息列表
     * @author fanjs
     */
    public function actionCrosslist(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/crosslist');
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->asArray()
                        ->all();
    	$data = EbayCrossselling::find();
    	$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
    	$lists = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
    	return $this->render('crosslist',['list'=>$lists,'pages'=>$pages,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * 交叉销售编辑
     * @author fanjs
     */
    public function actionCrossedit(){
    	AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/crossedit');
    	if(\Yii::$app->request->isPost){
    		if (isset($_POST['crosssellingid'])&& count($_POST['crosssellingid'])>0){
    			$crossselling=EbayCrossselling::findOne($_POST['crosssellingid']);
    		}else{
    			$crossselling=new EbayCrossselling();
    		}
    		$crossselling->title=$_POST['title'];
    		$crossselling->selleruserid=$_POST['selleruserid'];
	   		$crossselling->save();
    		EbayCrosssellingItem::deleteAll(['crosssellingid'=>$crossselling->crosssellingid]);
    		if (isset($_POST['crossItem'])&&count($_POST['crossItem'])>0){
    			for ($i=0;$i<count($_POST['crossItem']['picture']);$i++){
    				$crossItem=new EbayCrosssellingItem();
    				$crossItem->crosssellingid=$crossselling->crosssellingid;
    				$crossItem->sort=$_POST['crossItem']['sort'][$i];
    				$crossItem->data=array(
    						'title'=>$_POST['crossItem']['title'][$i],
    						'price'=>$_POST['crossItem']['price'][$i],
    						'picture'=>$_POST['crossItem']['picture'][$i],
    						'url'=>$_POST['crossItem']['url'][$i],
    						'icon'=>$_POST['crossItem']['icon'][$i],
    				);
    				if(!empty($_POST['crossItem']['url'][$i])){
    					$str='<a href="'.$_POST['crossItem']['url'][$i].'" target="_blank"><img src="'.$_POST['crossItem']['picture'][$i].'"   /></a>';
    				}else{
    					$str='<a href="#" target="_blank"><img src="'.$_POST['crossItem']['picture'][$i].'"  /></a>';
    				}
    				$crossItem->html=$str;
    				$crossItem->save();
    			}
    		}
    		echo '<script>alert("操作已成功");window.opener.location.reload();window.close();</script>';
    	}
    	if (isset($_REQUEST['cid'])){
    		$ec=EbayCrossselling::findOne($_REQUEST['cid']);
    	}else{
    		$ec=new EbayCrossselling();
    	}
    	$selleruserids=SaasEbayUser::findAll(['uid'=>\Yii::$app->user->identity->getParentUid()]);
    	return $this->render('crossedit',['selleruserids'=>$selleruserids,'ec'=>$ec]);
    }
    
    /**
     * 交叉销售删除
     * @author fanjs
     */
    public function actionCrossdelete(){
    	if(\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog('listing_ebay','/ebaymuban/crossdelete');
    		try {
    			if (EbayCrossselling::deleteAll(['crosssellingid'=>$_POST['id']])){
    				EbayCrosssellingItem::deleteAll(['crosssellingid'=>$_POST['id']]);
    				return 'success';
    			}
    			return 'failure';
    		} catch (\Exception $e) {
    			return $e->getMessage();
    		}
    	}
    }
    
    /**
     * 处理模板中的多属性编辑
     */
    function actionVariation(){
    	$product = [];
    	if (isset($_REQUEST['primarycategory']) && isset($_REQUEST['siteid'])){
    		$ecf=EbayCategoryfeature::find()->where('siteid=:siteid and categoryid=:categoryid',array(':siteid'=>$_REQUEST['siteid'],':categoryid'=>$_REQUEST['primarycategory']))->one();
    		if (!is_null($ecf)){
    			$product = [
    				'isbnenabled'=>$ecf->isbnenabled,
    				'upcenabled'=>$ecf->upcenabled,
    				'eanenabled'=>$ecf->eanenabled,
    			];
    		}
    	}
//     	return $this->render('variation',['product'=>$product],false,true);
        $data1 = [
            'code'=>200,
            'message'=>'',
            'data'=>$product,
        ];
        $data = json_encode($data1);
        exit($data);
    }
    //保存变量信息
    public function actionSaveVariation(){
        if (\Yii::$app->request->isPost) {
            $variation = array (
            				'assoc_pic_key' => $_REQUEST['assoc_pic_key'],
            				'assoc_pic_count' => 0,
            				'Variation' => array (),
            				'Pictures' => array (),
            				'VariationSpecificsSet' => array ()
            );
            $nvl = array ();
            foreach ( $_REQUEST['quantity' ] as $i => $q ) {
                if (strlen ( $q ) == 0 || strlen ( $_POST ['price'] [$i] ) == 0) {
                    continue;
                }
                $row = array (
                    'SKU' => trim($_POST ['sku'] [$i]),
                    'Quantity' => trim($q),
                    'StartPrice' => trim($_POST ['price'] [$i]),
                    'VariationProductListingDetails'=>[trim($_POST ['v_productid_name'])=>trim($_POST ['v_productid_val'] [$i])],
                    'VariationSpecifics' => array ()
                );
        
                foreach ( $_REQUEST['nvl_name'] as $name ) {
                    $name=trim($name);
                    $row ['VariationSpecifics'] ['NameValueList'] [] = array (
                        'Name' => $name,
                        'Value' => trim($_POST [self::nameStringDecode ( $name )] [$i])
                    );
        
                    $nvl [$name] [$_POST [self::nameStringDecode ( $name )] [$i]] = trim($_POST [self::nameStringDecode ( $name )] [$i]);
                }
                $variation ['Variation'] [] = $row;
                
                $imgArray = array();//一个属性多图片
                if(isset($_REQUEST['img'] [$i]) && count($_REQUEST['img'][$i]) > 0){
                    for($k = 0;$k < count($_REQUEST['img'][$i]);$k++){
                        if(strlen($_REQUEST['img'][$i][$k])){
                            $imgArray[] = trim($_REQUEST['img'][$i][$k]);
                            $variation ['assoc_pic_count'] ++;
                        }
                    }
                }
                if (count($imgArray)) {
                    $variation ['Pictures'] [] = array (
                        'VariationSpecificPictureSet' => array (
                            'PictureURL' =>$imgArray
                        ),
                        'Value' => trim($_POST [self::nameStringDecode ( $_POST ['assoc_pic_key'] )] [$i])
                    );
//                     $variation ['assoc_pic_count'] ++;
                } else {
                    $variation ['Pictures'] [] = array (
                        'VariationSpecificPictureSet' => array (
                            'PictureURL' => $imgArray
                        ),
                        'Value' => 'none_skip'
                    );
                }
//                 if (strlen ( $_POST ['img'] [$i] )) {
//                     $variation ['Pictures'] [] = array (
//                         'VariationSpecificPictureSet' => array (
//                             'PictureURL' =>trim( $_POST ['img'] [$i])
//                         ),
//                         'Value' => trim($_POST [self::nameStringDecode ( $_POST ['assoc_pic_key'] )] [$i])
//                     );
//                     $variation ['assoc_pic_count'] ++;
//                 } else {
//                     $variation ['Pictures'] [] = array (
//                         'VariationSpecificPictureSet' => array (
//                             'PictureURL' => trim($_POST ['img'] [$i])
//                         ),
//                         'Value' => 'none_skip'
//                     );
//                 }
            }
            foreach ( $nvl as $name => $values ) {
                @$variation ['VariationSpecificsSet'] ['NameValueList'] [] = array (
                    'Name' => $name,
                    'Value' => $values
                );
            }
//             print_r($_REQUEST);
            print_r($variation);
            //$json =addcslashes(json_encode ( $variation ),"'");
            //$json =mysql_escape_string(json_encode ( $variation ));
            $json =json_encode ( $variation );
            return $json;exit();
            
            $str = "<script type=\"text/javascript\">
            window.opener.document.getElementById('variation').value='{$json}';
            window.opener.renderVariation('{$json}');
            window.close();
            </script>";
            exit ( $str );
        }
    }
    
    /**
     * 上传图片到ebay
     * @author fanjs
     **/
    public function actionUploadimg()
    {
    	$upload	=	new uploadsitehostedpictures();
    	$eu = SaasEbayUser::findOne(['selleruserid'=>$_POST['selleruserid']]);
    	if (empty($eu)){
    		$infoJson = array('name' =>"Failure", 'status' => false, 'data' => "无法找到相应账号");
    		exit(json_encode($infoJson));
    	}
    	$upload->resetConfig($eu->listing_devAccountID);
    	$upload->eBayAuthToken=$eu->listing_token;
    
    	$url	=	$_POST['img'];
    	if(strpos($url,'?'))
    	{
    		$url = explode('?',$url);
    		$url = $url[0];
    	}
    	$url=explode('.',$url);
    	$url=end($url);//取到最后的值;
    		
    	$arr=array("jpg","png","gif","bmp","JPG","PNG","GIF","BMP","jpeg");
    	if(in_array($url,$arr))
    	{
    		$res=$upload->upload($_POST["img"]);
    		if ($res['Ack']!="Failure"){
    			$infoJson = array('name' => $res["Ack"], 'status' => true, 'data' => $res['SiteHostedPictureDetails']['FullURL']);
    		}else {
    			$infoJson = array('name' =>$res["Ack"], 'status' => false, 'data' => $res['Errors']['LongMessage']);
    		}
    	}else{
    		$infoJson = array('name' =>"Failure", 'status' => false, 'data' => "传入url不是正确图片路径");
    	}
    
    	//		问题：路径不对时，无法解决报错问题
    	//		$files = getimagesize($_POST['img']);
    	//		$infoJson = array();
    	//		$tmpFilePath = $files["tmp_name"];
    
    	exit(json_encode($infoJson));
    }
    
    /**
     * 刊登范本页面，用户搜索刊登类目
     * @author fanjs 2015.09.21
     */
    public function actionSearchcategory(){
    	if (\Yii::$app->request->isPost){
    		$eu=SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER]);
    		$api = new getsuggestedcategories();
    		$api->siteID = $_POST['siteid'];
    		$api->resetConfig($eu->DevAcccountID);
    		$api->eBayAuthToken = $eu->token;
    		$res = $api->api($_POST['query']);
    		return Json::encode($res);
    	}
    	return $this->renderPartial('searchcategory',['siteid'=>$_GET['siteid'],'typ'=>$_GET['typ']]);
    }
    
    /**
     * 刊登范本页面即时保存模块范本
     * @author fanjs
     */
    public function actionProfilesave(){
    	if (\Yii::$app->request->isPost){
    		$save_name = $_POST['save_name'];
    		$type = $_POST['type'];
    		$data = $_POST;
    		unset ( $data ['save_name'] );
    		unset ( $data ['type'] );
    		if (! $_POST['save_name'] || !$_POST['type']) {
    			exit(Json::encode(['ack'=>'failure','msg'=>'非法操作'])); 
    		}
    		$emp = EbayMubanProfile::findOne(['savename'=>$save_name,'type'=>$type]);
    		if (!empty($emp)) {
    			exit(Json::encode(['ack'=>'failure','msg'=>'范本名重复']));
    		}else{
    			$emp = new EbayMubanProfile();
    			$emp->created = time();
    		}
    		$emp->setAttributes([
    			'savename'=>$save_name,
    			'type'=>$type,
//    			'detail'=>$data,
    			'updated'=>time()
    		]);
    		$emp->detail = $data;
    		if ($emp->save ()){
    			exit(Json::encode(['ack'=>'success','id'=>$emp->id]));
    		}else{
    			exit(Json::encode(['ack'=>'failure','msg'=>$emp->getErrors()]));
    		}
    	}
    }
    
    /**
     * 刊登范本页面删除模块范本
     * @author fanjs
     */
    public function actionProfiledel(){
    	if (\Yii::$app->request->isPost){
    		try {
    			EbayMubanProfile::deleteAll(['id'=>$_POST['id']]);
    		}catch (Exception $e){
    			exit(Json::encode(['ack'=>'failure','msg'=>$e->getMessage()]));
    		}
    		exit(Json::encode(['ack'=>'success']));
    	}
    }
    /**
     * 刊登范本页面加载模块范本
     * @author fanjs
     */
    public function actionProfileload(){
    		$detail = EbayMubanProfile::findOne($_GET['id'])->detail;
    		exit(Json::encode($detail));
    }
    /**
     * 用于处理js中的特殊字符转换
     * @author fanjs
     */
    static function nameStringDecode($name) {
    	return str_replace ( array (
    			'"',
    			"'",
    			' ',
    			'&',
    			':',
    			'(',
    			')',
    			'/',
    			'*'
    	), array (
    			'#dquote#',
    			'#squote#',
    			'#space#',
    			'#and#',
    			'#colon#',
    			"#leftbrackets#",
    			"#rightbrackets#",
    			"#xie#",
    			"#mi#"
    	), $name );
    }
	//可视化模板描述细节编辑
	public function actionListingbasic(){
		$display = 'none';
		$id = $_REQUEST['id'];
		$result = Mytemplate::findOne(['id'=>$id,'type'=>1]);
		if(isset($result)){
			$type =  stripos($result['content'],'"productType":"product_layout_left"');
			if($type>0){
				$display = 'block';
			}
		}
		return $display;
	}
	
	public function actionGetAttribute(){
	    if(isset($_REQUEST['primarycategory'])&&isset($_REQUEST['siteid'])){
	        $result = EbaySpecific::find()->where(['categoryid'=>$_REQUEST['primarycategory'],'siteid'=>$_REQUEST['siteid']])->asArray()->all();
	        if(!empty($result)){
	            $detail = array('code'=>200,'message'=>'','data'=>$result);
	            return json_encode($detail);
	        }else{
	            return json_encode(array('code'=>220,'message'=>'没有相关数据','data'=>''));
	        }
	    }else{
	       return json_encode(array('code'=>400,'message'=>'参数有误','data'=>''));
	    }
	}
	
	public function actionGetDetailAttribute(){
	    if(isset($_REQUEST['selectName'])&&isset($_REQUEST['siteid'])&&isset($_REQUEST['primaryid'])){
	        $result = EbaySpecific::find()->where(['name'=>$_REQUEST['selectName'],'categoryid'=>$_REQUEST['primaryid'],'siteid'=>$_REQUEST['siteid'],])->asArray()->one();
	        if(!empty($result)&&!empty($result['value'])){
	            $detail = array('code'=>200,'message'=>'','data'=>unserialize($result['value']));
	            return json_encode($detail);
	        }else{
	            return json_encode(array('code'=>220,'message'=>'没有相关数据','data'=>''));
	        }
	    }else{
	        return json_encode(array('code'=>400,'message'=>'参数有误','data'=>''));
	    }
	}
}
