<?php
namespace eagle\modules\collect\controllers;


use eagle\modules\collect\models\GoodscollectAll;
use yii\data\Pagination;
use eagle\modules\collect\models\GoodscollectEbay;
use eagle\modules\collect\models\GoodscollectEbayDetail;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use common\helpers\Helper_Array;
use eagle\models\EbayCategory;
use eagle\models\EbayCategoryfeature;
use eagle\models\EbaySpecific;
use eagle\models\EbayShippingservice;
use eagle\modules\listing\models\EbayMuban;
use eagle\models\EbaySite;
use eagle\modules\listing\models\EbayAccountMap;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayMubanProfile;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbayCountry;
use eagle\modules\listing\models\Mytemplate;
use eagle\modules\listing\models\EbayCrossselling;
use eagle\modules\listing\models\EbaySalesInformation;
use eagle\models\EbayShippinglocation;
use eagle\modules\listing\models\EbayMubanDetail;
use common\api\trans\transBAIDU;
use common\api\ebayinterface\additem;
use common\helpers\Helper_Util;
use common\api\ebayinterface\EbayInterfaceException_Connection_Timeout;
use yii\helpers\Url;
class CollectController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	/**
	 * 总的采集页面的列表
	 * @author fanjs
	 */
	public function actionIndex(){
		$data = GoodscollectAll::find();
		if (isset($_REQUEST['active_type']) && $_REQUEST['active_type'] == 'Y'){
			$data->andWhere('wish=1 || ebay=1 || lazada=1 || ensogo=1');
		}
		if (isset($_REQUEST['active_type']) && $_REQUEST['active_type'] == 'N'){
			$data->andWhere('wish=0 && ebay=0 && lazada=0 && ensogo=0');
		}
		//分页
		$pages = new Pagination(['totalCount' => $data->count(),
				'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50',
				'params'=>$_REQUEST,
				'pageSizeLimit'=>[20,50,100,200]]);
		$collects = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		$count = [
			'all'=>GoodscollectAll::find()->count(),
			'renling'=>GoodscollectAll::find()->where('wish = 1 or ebay = 1 or lazada = 1 or ensogo =1')->count(),
			'weirenling'=>GoodscollectAll::find()->where('wish != 1 and ebay != 1 and lazada != 1 and ensogo !=1')->count()
		];
		return $this->render('index',['collects'=>$collects,'pages'=>$pages,'count'=>$count]);
	}
	
	/**
	 * 删除采集数据
	 * @author fanjs
	 */
	public function actionDel(){
		if (\Yii::$app->request->isPost){
			$ids = strtoarr($_POST['ids']);
			if (count($ids)){
				if(GoodscollectAll::deleteAll(['id'=>$ids])){
					return 'success';
				}else{
					return '删除失败,请联系技术人员';
				}
			}else{
				return '传入的id有误,请联系技术人员';
			}
		}
	}
	
	/**
	 * 认领采集箱数据到ebay草稿箱
	 * @author fanjs
	 */
	public function actionRenlingebay(){
		if (\Yii::$app->request->isPost){
			$ids = strtoarr($_POST['ids']);
			if (count($ids)){
				$gcs = GoodscollectAll::findAll(['id'=>$ids]);
				foreach ($gcs as $gc){
					$ge = new GoodscollectEbay();
					$ge->itemtitle = $gc->title;
					$ge->mainimg = $gc->mainimg;
					$ge->startprice = $gc->price;
					$ge->createtime = time();
					if ($ge->save()){
						$ged = new GoodscollectEbayDetail();
						$ged->imgurl = $gc->img;
						$dc = json_decode($gc->description);
						$ged->itemdescription = $dc->description;
						$ged->mubanid = $ge->mubanid;
						if ($ged->save()){
							$gc->ebay = 1;
							$gc->save();
							continue;
						}else{
							GoodscollectEbay::deleteAll(['mubanid'=>$ged->mubanid]);
							continue;
						}
					}else{
						continue;
					}
				}
				return 'success';
			}else{
				return '传入的id有误,请联系技术人员';
			}
		}
	}
	
	/**
	 * ebay的采集草稿箱
	 * @author fanjs
	 */
	public function actionEbay(){
		$data = GoodscollectEbay::find();
		
		if (!empty($_REQUEST['listingtype'])){
			if ($_REQUEST['listingtype']=='Chinese'){
				$data->andWhere(['listingtype'=>'Chinese']);
			}else{
				$data->andWhere('listingtype != "Chinese"');
			}
		}
		if (!empty($_REQUEST['siteid'])){
			$data->andWhere(['siteid'=>$_REQUEST['siteid']]);
		}
		if (!empty($_REQUEST['search_key'])){
			switch ($_REQUEST['search_name']){
				case 'itemtitle':
					$data->andWhere(['itemtitle'=>$_REQUEST['search_key']]);
					break;
				case 'sku':
					$data->andWhere(['sku'=>$_REQUEST['search_key']]);
					break;
				default:break;
			}
		}
		$pages = new Pagination(['totalCount' => $data->count(),
				'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50',
				'params'=>$_REQUEST,
				'pageSizeLimit'=>[20,50,100,200]]);
		$collects = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		return $this->render('ebay',['collects'=>$collects,'pages'=>$pages]);
	}
	
	/**
	 * 删除草稿箱数据
	 * @author fanjs
	 */
	public function actionDelebay(){
		if (\Yii::$app->request->isPost){
			$ids = strtoarr($_POST['ids']);
			if (count($ids)){
				if(GoodscollectEbay::deleteAll(['mubanid'=>$ids])){
					GoodscollectEbayDetail::deleteAll(['mubanid'=>$ids]);
					return 'success';
				}else{
					return '删除失败,请联系技术人员';
				}
			}else{
				return '传入的id有误,请联系技术人员';
			}
		}
	}
	
	/**
	 * 编辑ebay草稿箱
	 * @author fanjs
	 */
	public function actionEbayedit(){
		AppTrackerApiHelper::actionLog('collect_ebay','/collect/collect/edit');
		//数据初始化
		if (isset($_REQUEST['mubanid'])){
			$data=GoodscollectEbay::findOne($_REQUEST['mubanid'])->attributes;
			$detail = GoodscollectEbayDetail::findOne($_REQUEST['mubanid']);
			if (!empty($detail)){
				$detaildata=$detail->attributes;
			}else{
				$detaildata = [];
			}
			$data=array_merge($data,$detaildata);
			$data = array_merge(EbayMuban::$default,array_filter($data));
		}
		
		if (\Yii::$app->request->isPost){
			$data=array_merge($data,$_POST);
			//处理post过来的图片数据
			//删除空值图片
// 			if (is_array($data['imgurl'])){
// 				foreach ($data['imgurl'] as $k=>$v){
// 					if (strlen($v)==0){
// 						unset($data['imgurl'][$k]);
// 					}
// 				}
// 			}
				
// 			//如果用户修改的时候清空了删除图片，将默认数据中的图片也删除
// 			if(!isset($_POST['imgurl'])){
// 				$data['imgurl'] = '';
// 			}
			//使用新的图片控件
			$_imgtmp = [];
			if (is_array($data['extra_images'])){
				if (isset($data['main_image']) && strlen($data['main_image'])){
					array_push($_imgtmp, $data['main_image']);
					$data['mainimg'] = $data['main_image'];
				}
				foreach ($data['extra_images'] as $img){
					if ($img != $data['main_image']){
						array_push($_imgtmp, $img);
					}
				}
			}
			$data['imgurl'] = $_imgtmp;
			
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
						unset($data['mubanid']);
						$gebay=GoodscollectEbay::findOne(@$_POST['mubanid']);
						$gebay->setAttributes($data,false);
						$gebay->isvariation=0;
						if(is_array($data['variation'])&&count($data['variation'])>0){
							$gebay->isvariation=1;
						}
						$gebay->uid=\Yii::$app->user->identity->getParentUid();
						if (isset($data['imgurl']['0'])){
							$gebay->mainimg=$data['imgurl']['0'];
						}
						$gebay->save();
						$mubandetail=GoodscollectEbayDetail::find()->where('mubanid = '.$gebay->mubanid)->one();
						if (empty($mubandetail)){
							$mubandetail = new GoodscollectEbayDetail();
						}
						$mubandetail->mubanid=$gebay->mubanid;
						$mubandetail->setAttributes($data,false);
						$mubandetail->save();
						$this->redirect(array('/collect/collect/ebay'));
						//echo '<script>window.close();</script>';
						//$this->render('index');
						break;
					case 'verify':
						$seu=SaasEbayUser::find()->where(['selleruserid' =>$data['selleruserid']])->one();
						if (empty($seu)){
							$_SESSION['result'] = [
								'Ack'=>'Failure',
								'Errors'=>[
									'ErrorCode'=>'-1',
									'SeverityCode'=>'-1',
									'ShortMessage'=>'Please choose your ebay account',
									'LongMessage'=>'Please choose your ebay account'
								]	
							];
							$this->redirect(array('/listing/ebaymuban/verifyresult'));
						}
						$eiai=new additem();
						if($_POST['act']=='verify'){
							$eiai->isVerify(true);
						}
						$eiai->siteID = $data['siteid'];
						$eiai->eBayAuthToken = $seu->token;
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
							
						$this->redirect(array('/listing/ebaymuban/verifyresult'));
						break;
					default:
						break;
				}
			}
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
		//获取当前登陆用户的ebay账号绑定信息
		$ebayselleruserid=SaasEbayUser::find()->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->identity->getParentUid(),':expiretime'=>time()])->asArray()->all();
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
		
		return $this->render('ebayedit',$carrydata);
	}
	
	/**
	 * 批量修改草稿数据
	 * @author fanjs
	 */
	public function actionEbaymutiedit(){
		$ids = strtoarr($_REQUEST['mubanid']);
		$mubans = GoodscollectEbay::findAll(['mubanid'=>$ids]);
		return $this->render('mutiedit',['mubans'=>$mubans]);
	}
	
	/**
	 * 批量修改操作界面
	 * @author fanjs
	 */
	public function actionMutieditoption(){
		return $this->renderPartial('mutieditoption',['attr'=>$_REQUEST['attr']]);
	}
	
	/**
	 * 批量修改后的保存
	 * @author fanjs
	 */
	public function actionMutieditsave(){
		if(\Yii::$app->request->isPost){
			$ge = GoodscollectEbay::findOne($_POST['id']);
			$ge->setAttributes([
				'sku'=>$_POST['sku'],
				'itemtitle'=>$_POST['title'],
				'startprice'=>$_POST['price'],
				'quantity'=>$_POST['quantity']
			]);
			if($ge->save()){
				return 'success';
			}else{
				return 'failure';
			}
		}
	}
	
	/**
	 * 删除采集数据
	 * @author fanjs
	 */
	public function actionMovetofanben(){
		if (\Yii::$app->request->isPost){
			$ids = strtoarr($_POST['ids']);
			if (count($ids)){
				foreach ($ids as $id){
					$gebay = GoodscollectEbay::findOne(['mubanid'=>$id]);
					$gebay_detail = GoodscollectEbayDetail::findOne(['mubanid'=>$id]);
					
					$muban = new EbayMuban();
					$muban_detail = new EbayMubanDetail();
					
					$_tmp = $gebay->attributes;
					unset($_tmp['mubanid']);
					$muban->setAttributes($_tmp);
					if ($muban->save()){
						$_tmp = $gebay_detail->attributes;
						$_tmp['mubanid'] = $muban->mubanid;
						$muban_detail->setAttributes($_tmp);
						if($muban_detail->save()){
							GoodscollectEbay::deleteAll(['mubanid'=>$id]);
							GoodscollectEbayDetail::deleteAll(['mubanid'=>$id]);
							return 'success';
						}else{
							EbayMuban::deleteAll(['mubanid'=>$muban->mubanid]);
							return '转入失败,请联系技术人员';
						}
					}else{
						return '转入失败,请联系技术人员';
					}
				}
			}else{
				return '传入的id有误,请联系技术人员';
			}
		}
	}
	
	/**
	 * 立即刊登一个草稿
	 * @author fanjs
	 */
	public function actionAdditemone(){
		if (\Yii::$app->request->isPost){
			$ids = strtoarr($_POST['ids']);
			$result = '';
			if (count($ids)){
				foreach ($ids as $id){
					$gebay = GoodscollectEbay::findOne(['mubanid'=>$id]);
					$gebay_detail = GoodscollectEbayDetail::findOne(['mubanid'=>$id]);

					$aapi = new additem();
					if (strlen($gebay->selleruserid)==0){
						$result.='草稿ID:'.$gebay->mubanid.'立即刊登失败'."<br>";
					}else{
						$r = $aapi->apiFromMuban ( $gebay, $gebay->uid, $gebay->selleruserid, $gebay->detail->storecategoryid, $gebay->detail->storecategory2id,null,'',1 );
						if (isset ( $r ['ItemID'] )) {
							$result.='草稿ID:'.$gebay->mubanid.'立即刊登成功,ItemID:'.$r ['ItemID']."\n";
						} else {
							if (isset($r['Errors']['LongMessage'])){
								$errors = ['0'=>$r['Errors']];
							}else{
								$errors = $r['Errors'];
							}
							$result.='草稿ID:'.$gebay->mubanid.'立即刊登失败';
							foreach ($errors as $error){
								if ($error['SeverityCode'] == 'Error'){
									$result.=':'.$error['LongMessage'];
								}
							}
							$result.="<br>";
						}
					}
				}
				return $result;
			}else{
				return '传入的id有误,请联系技术人员';
			}
		}
	}
	
	/**
	 * 当前controller的翻译处理
	 * @author fanjs
	 */
	public function actionTranslate(){
		if (\Yii::$app->request->isPost){
			$api = new transBAIDU();
			$result = $api->translate($_POST['str'], 'auto','en');
			return json_encode($result);
		}
	}
}

/**
 * 函数将传递过来的字符串整理成数组形式
 * @author fanjs
 */
function strtoarr($str){
	if (!is_null($str)){
		$_tmp = explode(',',$str);
		$_tmp = array_filter($_tmp);
		return $_tmp;
	}else{
		return [];
	}
}