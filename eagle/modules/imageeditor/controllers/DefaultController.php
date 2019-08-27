<?php

namespace eagle\modules\imageeditor\controllers;

use yii\web\Controller;
use eagle\modules\util\helpers\ImageHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\models\GlobalImageInfo;
use eagle\modules\util\helpers\TimeUtil;
use Qiniu;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use eagle\modules\util\models\UserImage;

class DefaultController extends Controller
{
	public $enableCsrfValidation = false;
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * 美图秀秀在线编辑图片
     * @return Ambigous <string, string>
     * @author fanjs
     */
    public function actionEdit(){
    	$puid=\Yii::$app->user->identity->getParentUid();
    	list($ret,$msg) = ImageHelper::uploadImageUrlToImageLibrary([$_REQUEST['url']],$puid);
    	if($ret){
    		$newurl = $msg['0'];
    	}else{
    		$newurl = $_REQUEST['url'];
    	}
    	
    	return $this->renderPartial('edit',['imgurl'=>$_REQUEST['url'],'newurl'=>$newurl]);
    }
    
    /**
     * 美图秀秀在线商品编辑后回传的url
     * @author fanjs
     */
    public function actionUploadFromMeitu(){
    	AppTrackerApiHelper::actionLog("image_lib", "/util/image/upload");
    	set_time_limit(0);
    	$imgTmpPath=\Yii::getAlias('@app') . '/media/temp/';
    	//1.检查上传的图片信息
    	if (!isset($_FILES["product_photo_file"]) || !is_uploaded_file($_FILES["product_photo_file"]["tmp_name"]))  {	//是否存在文件
    		exit(json_encode(array('name' => null , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("图片不存在!"))));
    	}
    	 
    	$file = $_FILES["product_photo_file"];
    	$puid=\Yii::$app->user->identity->getParentUid();
    	 
    	if($file["error"] > 0 || $file["size"] <= 0) {
    		exit(json_encode(array('name' => null , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("图片上传出错，请稍候再试..."))));
    	}
    	
    	// 上传文件最大size
    	$customMaxSize = (!empty($_REQUEST['fileMaxSize']) && $_REQUEST['fileMaxSize'] < ImageHelper::$photoMaxSize)
    	?$_REQUEST['fileMaxSize']:ImageHelper::$photoMaxSize;
    	 
    	$rtnMsg = "";
    	if(!in_array(strtolower(substr($file["name"],strrpos($file["name"],".")+1)), ImageHelper::$photoMime) && !array_key_exists($file["type"], ImageHelper::$photoMime)){
    		$rtnMsg .= TranslateHelper::t("%s :对不起，我们只支持上传 %s 格式的图片！" , $file["name"] , implode(",", array_keys(ImageHelper::$photoMime)));
    	}
    	 
    	if( $file["size"] > $customMaxSize ) {
    		$rtnMsg .= TranslateHelper::t("%s :图片 %s K , 超出规定大小  %s K ， 请重新上传图片!" , $file["name"] , round($file["size"] / 1024) , ($customMaxSize / 1024 ));
    	}
    	
    	// 检查图片库空间
//     	$usage = GlobalImageInfo::find()->where(['puid'=>$puid])->asArray()->one();
//     	if(empty($usage)){
//     		$usage['total_size'] = 0;
//     		$usage['library_size'] = ImageHelper::$baseLibrarySize;
//     	}
    	$all_usage=\eagle\modules\util\helpers\UploadFileHelper::GlobalInfo();
    	$usage=array(
    			"total_size"=>$all_usage['count_size'],
    			"library_size"=>$all_usage['count_library_size'],
    	);
    	if(empty($usage)){
    		$usage['total_size'] = 0;
    		$usage['library_size'] = ImageHelper::$baseLibrarySize;
    	}
    	
    	$newUsage = $usage['total_size'] + $file["size"];
    	if( $newUsage > $usage['library_size']){
    		$rtnMsg .= TranslateHelper::t("上传图片大小%sM ,上传图片后图片库使用 %sM , 超出规定大小  %sM ，请重新上传图片!" , round($file["size"] / 1024 / 1024 , 2) , round($newUsage / 1024 / 1024 , 2) , round($usage['library_size'] / 1024 / 1024 , 2));
    	}
    	 
    	if($rtnMsg <> ''){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => $rtnMsg)));
    	}
    	 
    	$name = $file["name"];
    	$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
    	 
    	if(move_uploaded_file ( $file["tmp_name"] , $imgTmpPath . $originName ) === false) {// 重命名上传图片
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统获取图片失败！'))));
    	}
    	 
    	$accessKey = ImageHelper::$qiniuAccessKey;
    	$secretKey = ImageHelper::$qiniuSecretKey;
    	$qiniuDomain = ImageHelper::$qiniuDomain;
    	
    	$auth = new Auth($accessKey, $secretKey);
    	$bucket = 'littleboss-image';
    	$token = $auth->uploadToken($bucket);
    	$uploadMgr = new UploadManager();
    	 
    	$qiniuPath = \Yii::$app->user->id."/". date('Ymd')."/";// 七牛用户路径
    	$qiniuKey = $qiniuPath.$originName;// 上传到七牛的图片key值，这个值是唯一的，七牛通过此值管理图片。
    	$doRetry = false;
    	$oCounter = 0;
    	$tryCount = 3;// 上传失败重试次数
    	$timeMS1 = TimeUtil::getCurrentTimestampMS();
    	$originSize = abs(filesize($imgTmpPath . $originName));
    	do{
    		try{
    			list($ret, $err) = $uploadMgr->putFile($token, $qiniuKey , $imgTmpPath . $originName);
    			if ($err !== null) {
    				$doRetry = true;
    				\Yii::error("$qiniuKey upload fails. Error:".print_r($err,true),"file");
    			}
    			if($doRetry === false)
    				unlink ($imgTmpPath . $originName );// 删除保存到本地的上传图片
    		}catch(\Exception $e){
    			\Yii::error("$qiniuKey upload fails. Error:".$e->getMessage(),"file");
    			$doRetry = true;
    		}
    	} while ($oCounter++ < $tryCount && $doRetry);
    	 
    	if($doRetry){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统上传原图到七牛服务器失败:').$err)));
    	}
    	 
    	$timeMS2 = TimeUtil::getCurrentTimestampMS();
    	\Yii::info("上传原图 $qiniuKey size=".round($originSize/1024)."K 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS2-$timeMS1)."（毫秒）");
    	 
    	//4. 获取最终原图和缩略图的url
    	$imageUrl = "http://".$qiniuDomain.$qiniuKey;
    	// 以前的缩略图用 最大边150 这里改为210
    	//     	$thumbnailUrl = "http://".$qiniuDomain.$qiniuKey."?imageView2/2/w/160/h/160";// 宽最大210，高最大210 等比缩放，不裁剪
    	$thumbnailUrl = "http://".$qiniuDomain.$qiniuKey."?imageView2/1/w/210/h/210";// 长宽为210  等比缩放，居中裁剪
    	 
    	$im_size = getimagesize($imageUrl);
    	$imgW = $im_size[0];
    	$imgH = $im_size[1];
    	 
    	//5. 记录相关信息到global和user的图片表
    	$userImage = new UserImage();
    	$userImage->origin_url = $imageUrl;
    	$userImage->thumbnail_url = $thumbnailUrl;
    	$userImage->origin_size = $originSize;
    	$userImage->thumbnail_size = 0;
    	$userImage->create_time = date("Y-m-d H:i:s");
    	$userImage->service = 1;// service:0 amazon s3图片上传服务， 1 ： 七牛
    	$userImage->amazon_key = $qiniuKey;
    	$userImage->original_name = $name;
    	$userImage->original_width = $imgW;
    	$userImage->original_height = $imgH;
    	if(!$userImage->save(false)){
    		\Yii::error("$qiniuKey UserImage save fail","file");
    	}
    	 
    	$globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
    	if ($globalImageInfo === null){
    		$globalImageInfo = new GlobalImageInfo;
    		$globalImageInfo->puid = $puid;
    		$globalImageInfo->image_number = 1;
    		$globalImageInfo->total_size = $originSize;
    		if(!$globalImageInfo->save(false)){
    			\Yii::error("$qiniuKey : $puid create global image info record failed","file");
    		}
    		 
    	}else{
    		$globalImageInfo->image_number = $globalImageInfo->image_number + 1;
    		$globalImageInfo->total_size = $globalImageInfo->total_size + $originSize;
    		if(!$globalImageInfo->save(false)){
    			\Yii::error("$qiniuKey : $puid update global image info record failed","file");
    		}
    	}
    	if(!empty($_GET['dir']) && $_GET['dir']==='image'){        // kindeditor专用
    		exit(json_encode([
    				'error'=>0,
    				'url'=>$imageUrl
    				]));
    	}else{
    		exit(json_encode( array('name' => $file["name"], 'status' => true, 'data' => array('original' => $imageUrl , 'thumbnail' => $thumbnailUrl) , 'rtnMsg' => $rtnMsg)));
    	}
    	
//     	if (!$_FILES['Filedata']) {
//     		die ( 'Image data not detected!' );
//     	}
//     	if ($_FILES['Filedata']['error'] > 0) {
//     		switch ($_FILES ['Filedata'] ['error']) {
//     			case 1 :
//     				$error_log = 'The file is bigger than this PHP installation allows';
//     				break;
//     			case 2 :
//     				$error_log = 'The file is bigger than this form allows';
//     				break;
//     			case 3 :
//     				$error_log = 'Only part of the file was uploaded';
//     				break;
//     			case 4 :
//     				$error_log = 'No file was uploaded';
//     				break;
//     			default :
//     				break;
//     		}
//     		die ( 'upload error:' . $error_log );
//     	} else {
//     		$img_data = $_FILES['Filedata']['tmp_name'];
//     		$size = getimagesize($img_data);
//     		$file_type = $size['mime'];
//     		if (!in_array($file_type, array('image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/gif'))) {
//     			$error_log = 'only allow jpg,png,gif';
//     			die ( 'upload error:' . $error_log );
//     		}
//     		switch($file_type) {
//     			case 'image/jpg' :
//     			case 'image/jpeg' :
//     			case 'image/pjpeg' :
//     				$extension = 'jpg';
//     				break;
//     			case 'image/png' :
//     				$extension = 'png';
//     				break;
//     			case 'image/gif' :
//     				$extension = 'gif';
//     				break;
//     		}
//     	}
//     	if (!is_file($img_data)) {
//     		die ( 'Image upload error!' );
//     	}
//     	//图片保存路径,默认保存在该代码所在目录(可根据实际需求修改保存路径)
//     	$save_path = dirname( __FILE__ );
//     	$uinqid = uniqid();
//     	$filename = $save_path . '/' . $uinqid . '.' . $extension;
//     	$result = move_uploaded_file( $img_data, $filename );
//     	if ( ! $result || ! is_file( $filename ) ) {
//     		die ( 'Image upload error!' );
//     	}
//     	echo 'Image data save successed,file:' . $filename;
    }
}
