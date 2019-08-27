<?php
namespace eagle\modules\listing\controllers;

use yii\web\Controller;
use eagle\modules\listing\helpers\EbayVisibleTemplateHelper;
use eagle\modules\listing\models\Mytemplate;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use common\api\ebayinterface\getstore;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\getsellerlist;
use common\api\ebayinterface\getmyebayselling;
use common\api\ebayinterface\setstore;
class EbayTemplateController extends Controller
{
	public $enableCsrfValidation = false;
	
    public function behaviors() {
        return [
         	'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }
	
    // 修改ebay可视化模板
	public function actionEdit(){
		AppTrackerApiHelper::actionLog('listing_ebay','/ebay-template/edit');
		// TODO action 添加 log 记录访问app
// 		AppTrackerApiHelper::log('');
		$puid = \Yii::$app->user->identity->getParentUid();


		$user = SaasEbayUser::find()->where(["uid"=>$puid])->asArray()->all();
		$userArr=array();
		foreach($user as $val){
			$userArr[]=$val['selleruserid'];

		}
		// \Yii::info("saas_ebay_user_keshi puid:$puid   user:".print_r($user,true),"file");
		// var_dump($user);
		// die;
		$templateInfo = array();
		$itemInfo = EbayVisibleTemplateHelper::getItemInfo();
		if(!empty($_REQUEST['template_id'])){
			$visualTemplate = Mytemplate::findOne(['id'=>$_REQUEST['template_id'],"type"=>"1"]);
			if(!empty($visualTemplate)){
				$templateInfo = json_decode($visualTemplate->content,true);
				$templateInfo['storenameArr'] = array($visualTemplate->account);
				// var_dump($templateInfo['puid']);
				// die;
			}else {
				exit("Can not find tempate:".$_REQUEST['template_id']);
			}
		}
		$newListItem = array();
		if(empty($templateInfo)){
			return $this->renderAjax('editTemplate', array("showDemo"=>true,"initDemo"=>true,"allItem"=>array() , "sortable"=>array() , "msortable"=>array() , "dsortable"=>array() , "editor"=>array() , "infodetclass"=>array() , "itemInfo"=>$itemInfo,"switchType"=>'layout_left',"productType"=>'product_layout_left',"storenameArr"=>$userArr,"newListItem"=>$newListItem));
		}else{
			$templateInfo['showDemo'] = true;
			$templateInfo['itemInfo'] = $itemInfo;
			$templateInfo['newListItem'] = $newListItem;
			// $templateInfo['switchType'] = "layout_left";
			return $this->renderAjax('editTemplate', $templateInfo);
		}
		
	}

	//页面风格
	public function actionStyle(){
		return $this->render('styleTemplate');
	}
	
	// ebay 可视化模板  获取某部分html
	public function actionGetPartialTemplate(){
		// TODO action 添加 log 记录访问app
// 		AppTrackerApiHelper::log('');
		
		if(empty($_GET['partial']))return;
		
		$allPatial = array('toolBar','toolBarDemo','description','menuBar','itemContent','sideBar',
		'policyView' ,'feedbackView' , 'mobileView' , 'catDetails' , 'picDetails' , 
		'textDetails','tool','itemDetails','flashDetails','cusDetails','youtubeDetails','actionButton','newlistitemDetails');
		
		// 获取样板item信息
		$itemInfo = EbayVisibleTemplateHelper::getItemInfo();

		if(in_array($_GET['partial'], $allPatial)){
			$templateInfo = array();
			if(!empty($_REQUEST['template_id'])){
				$visualTemplate = Mytemplate::findOne(['id'=>$_REQUEST['template_id'],"type"=>"1"]);
				if(!empty($visualTemplate)){
					$templateInfo = json_decode($visualTemplate->content,true);
				}
			}
			$newListItem = array();
			if(empty($templateInfo)){
				$productType = 'product_layout_left';
				$switchType = 'layout_left';
				$allItem = array();
				$sortable = array();
				$msortable = array();
				$dsortable = array();
				$editor = array();
				$infodetclass = array();
				if(!empty($_REQUEST['allItem'])){
					foreach ($_REQUEST['allItem'] as $value){
						$allItem[$value['name']] = $value['value'];
					} 
				}
				if(!empty($_REQUEST['productType'])){
					$sortable = $_REQUEST['productType'];
				}
				if(!empty($_REQUEST['switchType'])){
					$sortable = $_REQUEST['switchType'];
				}
				if(!empty($_REQUEST['sortable'])){
					$sortable = $_REQUEST['sortable'];
				}
				
				if(!empty($_REQUEST['msortable'])){
					$msortable = $_REQUEST['msortable'];
				}
	
				if(!empty($_REQUEST['dsortable'])){
					$dsortable = $_REQUEST['dsortable'];
				}
				
				if(!empty($_REQUEST['editor'])){
					$editor = $_REQUEST['editor'];
				}
				
				if(!empty($_REQUEST['infodetclass'])){
					foreach ($_REQUEST['infodetclass'] as $value){
						$infodetclass[$value['name']] = $value['value'];
					} 
				}
				if($_REQUEST['partial'] == "toolBar" && empty($_REQUEST['template_id'])){
					return $this->renderAjax("tool", array("allItem"=>$allItem , "sortable"=>$sortable , 
					"msortable"=>$msortable , "dsortable"=>$dsortable , "editor"=>$editor , "infodetclass"=>$infodetclass , "itemInfo"=>$itemInfo,'switchType'=>$switchType,'productType'=>$productType,"newListItem"=>$newListItem,));
				}else{
					return $this->renderAjax($_GET['partial'], array("showDemo"=>true,"initDemo"=>true,"allItem"=>$allItem , "sortable"=>$sortable , 
						"msortable"=>$msortable , "dsortable"=>$dsortable , "editor"=>$editor , "infodetclass"=>$infodetclass , "itemInfo"=>$itemInfo,'switchType'=>$switchType,'productType'=>$productType,"newListItem"=>$newListItem));
				}
				 
				// if($_REQUEST['partial'] == "toolBar" && empty($_REQUEST['template_id'])){
				// 	return $this->renderAjax("toolBarDemo", array("allItem"=>$allItem , "sortable"=>$sortable , 
				// 	"msortable"=>$msortable , "dsortable"=>$dsortable , "editor"=>$editor , "infodetclass"=>$infodetclass , "itemInfo"=>$itemInfo));
				// }else{
				// 	return $this->renderAjax($_GET['partial'], array("showDemo"=>true,"allItem"=>$allItem , "sortable"=>$sortable , 
				// 	"msortable"=>$msortable , "dsortable"=>$dsortable , "editor"=>$editor , "infodetclass"=>$infodetclass , "itemInfo"=>$itemInfo));
				// }
				
				
			}else{
				$allItem = array();
				foreach ($templateInfo['allItem'] as $value){
					$allItem[$value['name']] = $value['value'];
				}
				foreach ($templateInfo['infodetclass'] as $value){
					$infodetclass[$value['name']] = $value['value'];
				} 
				$templateInfo['allItem'] = $allItem;
				$templateInfo['infodetclass'] = $infodetclass;
				$templateInfo['itemInfo'] = $itemInfo;
				$templateInfo['newListItem'] = $newListItem;
				return $this->renderAjax($_GET['partial'], $templateInfo);
			}			
		}
	}

	// 新建/保存ebay 可视化模板
	public function actionSaveTemplate(){
		// TODO action 添加 log 记录访问app
// 		AppTrackerApiHelper::log('');
		 
		if(empty($_REQUEST['template_id']) || $_REQUEST['isSaveas'] == true){
			$visualTemplate = new Mytemplate();
			$visualTemplate->type = 1;
			$visualTemplate->account=$_REQUEST['name1'];
			if(!empty($_REQUEST['name'])){
				$visualTemplate->title = $_REQUEST['name'];
			}
		}else{//ProductAliases::model()->findall('sku=:sku',array(':sku'=>$sku));
			$visualTemplate = Mytemplate::findOne(['id'=>$_REQUEST['template_id'],"type"=>"1"]);
			if(empty($visualTemplate)){
				exit("Can not find template:".$_REQUEST['template_id']) ;
			}
		}
		$templateInfo = array(
			"productType"=>!empty($_REQUEST['productType'])?$_REQUEST['productType']:'product_layout_left',
			"switchType"=>!empty($_REQUEST['switchType'])?$_REQUEST['switchType']:'layout_left',
			"allItem"=>!empty($_REQUEST['allItem'])?$_REQUEST['allItem']:array(),
			"sortable"=>!empty($_REQUEST['sortable'])?$_REQUEST['sortable']:array(),
			"dsortable"=>!empty($_REQUEST['dsortable'])?$_REQUEST['dsortable']:array(),
			"msortable"=>!empty($_REQUEST['msortable'])?$_REQUEST['msortable']:array(),
			"editor"=>!empty($_REQUEST['editor'])?$_REQUEST['editor']:array(),
			"infodetclass"=>!empty($_REQUEST['infodetclass'])?$_REQUEST['infodetclass']:array(),
		);
		$visualTemplate->content = json_encode($templateInfo);
		if($visualTemplate->save(false)){
			echo  $visualTemplate->id;
		}else{
			echo "Fail to save model:".$_REQUEST['template_id'];
		}
	}
	
	// 删除ebay 可视化模板
	public function actionDelTemplate(){
		AppTrackerApiHelper::actionLog('listing_ebay','/ebay-template/deltemplate');
		// TODO action 添加 log 记录访问app
		// 		AppTrackerApiHelper::log('');
		
		if(!empty($_REQUEST['template_id'])){
			$visualTemplate = Mytemplate::findOne(['id'=>$_REQUEST['template_id'],"type"=>"1"]);
			if(empty($visualTemplate)){
				$returnMsg = "模板：".$_REQUEST['template_id']." 不存在:";
			}else {
				if($visualTemplate->delete()){
					$returnMsg = "模板：".$_REQUEST['template_id']." 已删除";
				}else{
					$returnMsg ="模板：".$_REQUEST['template_id']." 删除失败";
				}
			}
		}else if(!empty($_REQUEST['visualTemplateIdStr'])){
			Mytemplate::deleteAll(['id'=>explode(',',$_REQUEST['visualTemplateIdStr'])]);
			$returnMsg = '模板已删除';
		}
		
		exit(json_encode(array('message'=>$returnMsg)));
	}
	
// 	public function actionImageUpload(){
// 		//图片上传的位置，记得一定是ealge/attachment
// 		$photoMime = array ( 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
//         $root = YiiBase::getPathOfAlias('webroot');
//         $absUri = '/attachment/ebayTemplateImages/'.date("Ymd");
//         $folder = $root.$absUri;
//         // 留意权限问题
//         PhotoHelper::mkDirIfNotExist($folder);
        
//         $urlPath = HOST_INFO.Yii::app()->baseUrl.$absUri.'/';//Yii::app()->request->hostInfo.Yii::app()->baseUrl.$absUri;
//         $filePath = $folder.'/';
        
//         $files = $_FILES["file"];
//         $infoJson = array();
//         if (isset($files['name'])){
//         	$isSuc = false;
//         	$tmpFilePath = $files["tmp_name"];
        	
// 	        if ((($files["type"] == "image/gif")
// 	        || ($files["type"] == "image/jpeg")
// 	        || ($files["type"] == "image/png")
// 	        || ($files["type"] == "image/jpg")
// 	        || ($files["type"] == "image/pjpeg")) 
// 	        && $files["error"] <= 0 
// 	        && $files["size"] <= PhotoHelper::$photoMaxSize){
//                 $fileName = 'OR_' . base64_encode(time().rand(0, 1000)) . '.' . $photoMime[$files["type"]];
// 				$desFilePath = $filePath . $fileName;
// 				echo file_exists($desFilePath);
// 		        if(file_exists($desFilePath) || move_uploaded_file($tmpFilePath, $desFilePath)) {
// 		        	exit($urlPath.$fileName);
// 		        }
// 	        }
//         }
//         exit();
		
// 	}
	
	// 留给 js 获取 最终生成的html的入口 ， 方便测试用
	public function actionGetFinalTemplateView(){
		//allItem,sortable,msortable,dsortable,editor,infodetclass
		$switchType = 'layout_left';
		$productType = 'product_layout_left';
		$allItem = array();
		$sortable = array();
		$msortable = array();
		$dsortable = array();
		$editor = array();
		$infodetclass = array();
		$noFullInfo = false;
		if(!empty($_REQUEST['template_id'])){
			// read template info
			
		}else if(isset($_REQUEST['template_id']) && $_REQUEST['template_id'] == 0){
			
			if(!empty($_REQUEST['allItem'])){
				foreach ($_REQUEST['allItem'] as $value){
					$allItem[$value['name']] = $value['value'];
				} 
			}else {
				$noFullInfo = true;
			}

			if(!empty($_REQUEST['switchType'])){
				$sortable = $_REQUEST['prodcutType'];
			} else {
				$noFullInfo = true;
			}

			if(!empty($_REQUEST['switchType'])){
				$sortable = $_REQUEST['switchType'];
			} else {
				$noFullInfo = true;
			}
			
			if(!empty($_REQUEST['sortable'])){
				$sortable = $_REQUEST['sortable'];
			} else {
				$noFullInfo = true;
			}
			
			if(!empty($_REQUEST['msortable'])){
				$msortable = $_REQUEST['msortable'];
			} else {
				$noFullInfo = true;
			}

			if(!empty($_REQUEST['dsortable'])){
				$dsortable = $_REQUEST['dsortable'];
			} else {
				$noFullInfo = true;
			}
			
			if(!empty($_REQUEST['editor'])){
				$editor = $_REQUEST['editor'];
			} else {
				$noFullInfo = true;
			}
			
			if(!empty($_REQUEST['infodetclass'])){
				foreach ($_REQUEST['infodetclass'] as $value){
					$infodetclass[$value['name']] = $value['value'];
				} 
			} else {
				$noFullInfo = true;
			}
			
		}
//		else{
//			return ;
//		}
		if($noFullInfo){
			return "Template info lost.";//模板信息丢失
		}
		// 获取样板item信息
		$itemInfo = EbayVisibleTemplateHelper::getItemInfo();
		
		$layoutSetting = explode("_", $allItem['layout_style_name']);
		$sideBarPosition = strtolower($layoutSetting[0]);
		
		//目前仅支持sidebar 左侧排版
		$layoutSetting[0] = "left";
		
		if("right" == $sideBarPosition){
			$templateFile = "finalTemplate/finalRightSideTemplate";
		}elseif ("none" == $sideBarPosition){
			$templateFile = "finalTemplate/finalNoneSideTemplate";
		}else {// 默认左排版
			$templateFile = "finalTemplate/finalLeftSideTemplate";
			$layoutSetting[0] = "left";
			$allItem['layout_style_name'] = implode("_", $layoutSetting);
		}
		
		$fileRoot = \Yii::getAlias('@eagle/modules/listing/views/ebay-template').DIRECTORY_SEPARATOR;
		$fileExt = ".php";
		
		$templateInfo['sortable'] = $sortable;
		$templateInfo['msortable'] = $msortable;
		$templateInfo['dsortable'] = $dsortable;
		$templateInfo['editor'] = $editor;
		$templateInfo['switchType'] = $switchType;
		$templateInfo['productType'] = $productType;
		$templateInfo['fileRoot'] = $fileRoot;
		$templateInfo['fileExt'] = $fileExt;
		$templateInfo['allItem'] = $allItem;
		$templateInfo['infodetclass'] = $infodetclass;
		$templateInfo['itemInfo'] = $itemInfo;
		
		
		return $this->renderAjax($templateFile, $templateInfo);
	}
	
// 	public function actionCheckCategoryExistFromAllUser(){
// 		$sameNameCategories = array();
//  		$sql = "SELECT * FROM `user_base` where puid = 0";
//  		$command = Yii::app()->db->createCommand($sql);
// 		$rows = $command->queryAll();
// 		foreach ($rows as $row){
// 			$userIdArr[] = $row['uid'];
// 		}
		
// 		foreach ($userIdArr as $uid){
// 			$sql = 'SHOW DATABASES LIKE "user_'.$uid.'" ';
// 			$command = Yii::app()->subdb->createCommand($sql);
// 			$rows = $command->queryAll();
// 			if(!empty($rows)){
			

// 			    	$sql = "SELECT a.category_id, a.name 
// 							FROM  `pd_category` a,  `pd_category` b
// 							WHERE a.name = b.name
// 							AND a.category_id <> b.category_id ";
// 			    	$command = Yii::app()->subdb->createCommand($sql);
// 					$rows = $command->queryAll();
// 				    foreach ($rows as $row){
// 						$sameNameCategories[$uid][] = $row;
// 					}	    	

// 			}else {
// 				//异常情况
// 				Yii::log("uid:$uid database not exists \n","info","");
// 			}
			
// 		}
// 		echo "checkCategoryExistFromAllUser: \n".print_r($sameNameCategories,true);
//  		Yii::log("checkCategoryExistFromAllUser: \n".print_r($sameNameCategories,true),"info","");
 
// 	}

	public function actionNoReturn(){
// 		\Yii::info(['listing',__CLASS__,__FUNCTION__,'哈哈哈哈哈'] , 'edb\user');
		return ;
	}
	
	public function actionGetEbayStoreCat(){
		$uid = \Yii::$app->user->id;
		$selleruserid = 'testuser_hsseller1';
		$M = SaasEbayUser::find()->where("account_id=:a and selleruserid=:b",array(':a'=>$uid,':b'=>$selleruserid))->one();
		$token = $M->token;
		var_dump($token);
		if (!empty($token) && !empty($selleruserid)) {
// 			$ebayGetStoreApi = new getstore();
// 			$ebayGetStoreApi->eBayAuthToken = $token;
// 			$storeCatInfo = $ebayGetStoreApi->api($selleruserid);
// 			print_r($storeCatInfo) ;
// 			$heriCats = $storeCatInfo['Store']['CustomCategories']['CustomCategory'];
// 			var_dump($heriCats);

// 			define('CURRENT_TIMESTAMP', time() - 60 * 86400);
// 			define('ONEDAY', 86400);
// 			$ebayGetSellerListApi = new getsellerlist();
// 			$ebayGetSellerListApi->eBayAuthToken = $token;
// 			$sellerListInfo = $ebayGetSellerListApi->api(['EntriesPerPage'=>10,'PageNumber'=>1]);
// 			print_r($sellerListInfo);

			
			// 获取newest start time active listing,
			
			// getmyebayselling 同时应该也支持hot sell
// 			Quantity
// 			(in) Sort by quantity (ascending).
// 			QuantityAvailable
// 			(in) Sort by the number of items available (ascending).
// 			QuantityAvailableDescending
// 			(in) Sort items by the number there are available, in descending order.
// 			QuantityDescending
// 			(in) Sort by the quantity of items sold (descending).
// 			QuantityPurchased
// 			(in) Sort by the number of items purchased (ascending).
// 			QuantityPurchasedDescending
// 			(in) Sort items by the number that have been purchased, in descending order.
// 			QuantitySold
// 			(in) Sort by the number of items sold (ascending).
// 			QuantitySoldDescending
// 			(in) Sort by the number of items sold, in descending order.

// 			$gmesApi = new getmyebayselling();
// 			$gmesApi->eBayAuthToken = $token;
// 			$gmesApi->EntriesPerPage = 10;
// 			$gmesApi->PageNumber = 1;
// 			$gmesApi->sort = 'StartTimeDescending';
// // 			$gmesApi->sort = 'StartTime';
// 			$gmesInfo = $gmesApi->api(array('ActiveList'));
// 			print_r($gmesInfo);
			
			$setStoreApi = new setstore();
			$setStoreApi->resetConfig($M->DevAcccountID);
			$setStoreApi->eBayAuthToken = $token;
			$rtn = $setStoreApi->updateDescription(['CustomListingHeader'=>['DisplayType'=>'None']]);
// 			$rtn = $setStoreApi->updateDescription(['CustomListingHeader'=>['DisplayType'=>'FullAndLeftNavigationBar']]);
			print_r($rtn);
		}
		
		
		
	}
	
}


?>
