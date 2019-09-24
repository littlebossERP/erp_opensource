<?php

namespace eagle\modules\util\controllers;

use eagle\modules\util\helpers\ImageHelper;
use eagle\modules\util\models\S3;
use eagle\modules\util\models\UserImage;
use eagle\modules\util\models\GlobalImageInfo;
use eagle\modules\util\helpers\TranslateHelper;
use Qiniu;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use eagle\modules\util\helpers\TimeUtil;
use yii\data\Pagination;
use eagle\modules\util\helpers\ResultHelper;
use Qiniu\Storage\BucketManager;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use OSS\OssClient;
use OSS\Core\OssException;
use eagle\modules\util\models\UtImageClassification; 
use eagle\modules\payment\apihelpers\PaymentApiHelper;

class ImageController extends \eagle\components\Controller {
	public $enableCsrfValidation = false;
	
    public function actionIndex() {
    	
        return $this->render('index');
    }
    
    // util/image/upload
    public function actionUpload(){
        AppTrackerApiHelper::actionLog("image_lib", "/util/image/upload");
        set_time_limit(0);
        
        $puid = \Yii::$app->user->identity->getParentUid();
        
        $imgRPath = "/images/p/{$puid}/". date('Ymd') . '/';
        $imgTmpPath = \Yii::getAlias('@eagle/web') . $imgRPath;
        if(!file_exists($imgTmpPath)){
            mkdir($imgTmpPath, 0777, true);
        }
        
        //1.检查上传的图片信息
        if (!isset($_FILES["product_photo_file"]) || !is_uploaded_file($_FILES["product_photo_file"]["tmp_name"]))  {	//是否存在文件
            exit(json_encode(array('name' => null , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("图片不存在!"))));
        }
         
        $file = $_FILES["product_photo_file"];
        
         
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
        
        
        if($rtnMsg <> ''){
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => $rtnMsg)));
        }
         
        $name = $file["name"];
        $originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
        
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        if(move_uploaded_file ( $file["tmp_name"] , $imgTmpPath . $originName ) === false) {// 重命名上传图片
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统获取图片失败！'))));
        }
        
        $originSize = abs(filesize($imgTmpPath . $originName));
        
        list($ret, $thumbnailname) = ImageHelper::generateThumbnail($originName, $imgTmpPath);
        
        $thumbnailSize = abs(filesize($imgTmpPath . $thumbnailname));
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        \Yii::info("上传原图 $originName size=".round($originSize/1024)."K 到服务器  。 耗时".($timeMS2-$timeMS1)."（毫秒）");
         
        //4. 获取最终原图和缩略图的url
        $imageUrl = \Yii::$app->request->hostInfo.$imgRPath.$originName;
        $thumbnailUrl = \Yii::$app->request->hostInfo.$imgRPath.$thumbnailname;
         
        $im_size = @getimagesize($imageUrl);
        if(false === $im_size){
            $imgW = 0;
            $imgH = 0;
        }else{
            $imgW = $im_size[0];
            $imgH = $im_size[1];
        }
        
        $ImgKey = $imgRPath.$originName;
        //5. 记录相关信息到global和user的图片表
        $userImage = new UserImage();
        $userImage->origin_url = $imageUrl;
        $userImage->thumbnail_url = $thumbnailUrl;
        $userImage->origin_size = $originSize;
        $userImage->thumbnail_size = $thumbnailSize;
        $userImage->create_time = date("Y-m-d H:i:s");
        $userImage->service = 3;// service:0 amazon s3图片上传服务， 1 ： 七牛 ， 2 ：alioss，3 ： 本地
        $userImage->amazon_key = $ImgKey;
        $userImage->original_name = $name;
        $userImage->original_width = $imgW;
        $userImage->original_height = $imgH;
        if(!$userImage->save(false)){
            \Yii::error("$ImgKey UserImage save fail","file");
        }
         
        $globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
        if ($globalImageInfo === null){
            $globalImageInfo = new GlobalImageInfo;
            $globalImageInfo->puid = $puid;
            $globalImageInfo->image_number = 2;
            $globalImageInfo->total_size = $originSize + $thumbnailSize;
            if(!$globalImageInfo->save(false)){
                \Yii::error("$ImgKey : $puid create global image info record failed","file");
            }
             
        }else{
            $globalImageInfo->image_number = $globalImageInfo->image_number + 2;
            $globalImageInfo->total_size = $globalImageInfo->total_size + $originSize + $thumbnailSize;
            if(!$globalImageInfo->save(false)){
                \Yii::error("$ImgKey : $puid update global image info record failed","file");
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
        
        
    }
    
    // util/image/upload-image-url
    public function actionUploadImageUrl(){
        AppTrackerApiHelper::actionLog("image_lib", "/util/image/upload-image-url");
        set_time_limit(0);
        
        if (empty($_REQUEST["urls"]))  {
            return ResultHelper::getResult(400, "", TranslateHelper::t("请上传url"));
        }
         
        $puid = \Yii::$app->user->identity->getParentUid();
        list($ret,$message) = ImageHelper::uploadImageUrlToLocal($_REQUEST["urls"], $puid, \Yii::$app->request->hostInfo);
        if($ret){
            return ResultHelper::getResult(200, "", "图片url上传成功");
        }else{
            return ResultHelper::getResult(400, "", $message);
        }
    }
    
    // util/image/upload-to-qiniu
    public function actionUploadToQiniu(){
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
    	
    	$im_size = @getimagesize($imageUrl);
    	if(false === $im_size){
    	    $imgW = 0;
    	    $imgH = 0;
    	}else{
    	    $imgW = $im_size[0];
    	    $imgH = $im_size[1];
    	}
    	
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
    	 
    }
    
    // util/image/upload-image-url-to-qiniu
    public function actionUploadImageUrlToQiniu(){
    	AppTrackerApiHelper::actionLog("image_lib", "/util/image/upload-image-url");
    	set_time_limit(0);
    	if (empty($_REQUEST["urls"]))  {
    		return ResultHelper::getResult(400, "", TranslateHelper::t("请上传url"));
    	}
    	
    	
    	$puid=\Yii::$app->user->identity->getParentUid();
    	list($ret,$message) = ImageHelper::uploadImageUrlToImageLibrary($_REQUEST["urls"],$puid);
    	if($ret){
    		return ResultHelper::getResult(200, "", "图片url上传成功");
    	}else{
    		return ResultHelper::getResult(400, "", $message);
    	}
    }
    
    public function actionUploadToAmazonS3() {

    	$imgTmpPath=\Yii::getAlias('@app') . '/media/temp/';
    	//1.检查上传的图片信息
    	if (!isset($_FILES["product_photo_file"]) || !is_uploaded_file($_FILES["product_photo_file"]["tmp_name"]))  {	//是否存在文件   
    		exit(json_encode(array('name' => null , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("图片不存在!"))));
    	}
    
    	$file = $_FILES["product_photo_file"];
    	
    	if($file["error"] > 0 || $file["size"] <= 0) {
    		exit(json_encode(array('name' => null , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("图片上传出错，请稍候再试..."))));
    	}
    	
    	// 上传文件最大size
    	$customMaxSize = (!empty($_REQUEST['fileMaxSize']) && $_REQUEST['fileMaxSize'] < ImageHelper::$photoMaxSize)
    	?$_REQUEST['fileMaxSize']:ImageHelper::$photoMaxSize;
    	 
    	$rtnMsg = "";
    	
    	if(!array_key_exists($file["type"], ImageHelper::$photoMime)){
    		$rtnMsg .= TranslateHelper::t("%s :对不起，我们只支持上传 %s 格式的图片！" , $file["name"] , implode(",", array_keys(ImageHelper::$photoMime)));
    	}
    	
    	if( $file["size"] > $customMaxSize ) {
    		$rtnMsg .= TranslateHelper::t("%s :图片 %s K , 超出规定大小  %s K ， 请重新上传图片!" , $file["name"] , round($file["size"] / 1024) , ($customMaxSize / 1024 ));
    	}
    	
    	if($rtnMsg <> ''){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => $rtnMsg)));
    	}
    	
//     	echo $_FILES["upfile"]["tmp_name"];
    	$name = $file["name"]; 
    	$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );	    	
//     	move_uploaded_file ( $file["tmp_name"] , $imgTmpPath . $originName );
    	if(move_uploaded_file ( $file["tmp_name"] , $imgTmpPath . $originName ) === false) {
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统获取图片失败！'))));
    	}
    	$originSize = abs(filesize($imgTmpPath.$originName));
    	
    	
    	list($msec,$sec) = explode(' ', microtime()); 
    	$starttime = $sec + $msec;
    	
    	//2.生成缩略图
    	list($ret , $thumbnailName) = ImageHelper::generateThumbnail($originName, $imgTmpPath);
    	
    	list($msec,$sec) = explode(' ', microtime()); 
    	$endTime = $sec + $msec;
    	
    	\Yii::info($thumbnailName.":generate thumbnail cost time gct=".(($endTime-$starttime) * 1000)."ms" ,"file");
    	
    	if($ret  === false){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统生成缩略图失败！'))));
    	}
    	$thumbnailSize=abs(filesize($imgTmpPath.$thumbnailName));
    	
    	//3. 上传原图和缩略图到amazon s3服务器
    	$amazonPath=\Yii::$app->user->id."/". date('Ymd')."/";
    	$originAmazonFile= $amazonPath.$originName;
    	$thumbnailAmazonFile= $amazonPath.$thumbnailName;
    	//$bucketName='littleboss-img'; // USA
     	$bucketName = 'littleboss-image';//Singapore
    	$tryCount = 3; // 上传失败重试次数 
     	
    	list($msec,$sec) = explode(' ', microtime()); 
    	$starttime = $sec + $msec;
    	$oCounter = 1;
    	do{
    		$doRetry = false;
    		try{
    			if (!S3::putObject ( S3::inputFile ( $imgTmpPath.$originName ), $bucketName, $originAmazonFile, S3::ACL_PUBLIC_READ )) {
    				\Yii::error("$originAmazonFile upload fails","file");
    				$doRetry = true;
    			}
    			if($doRetry === false)
    				unlink ($imgTmpPath.$originName );
    		}catch(\Exception $e){
    			\Yii::error("$originAmazonFile upload fails. Error:".$e->getMessage(),"file");
    			$doRetry = true;
    		}
    	}while ($oCounter++ < $tryCount && $doRetry);
    	
    	if($doRetry){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统上传原图到amazon s3服务器失败！'))));
    	}
    	
    	$tCounter = 1;
    	do{
    		$doRetry = false;
	    	try{
	    		if (!S3::putObject ( S3::inputFile ( $imgTmpPath.$thumbnailName ), $bucketName, $thumbnailAmazonFile, S3::ACL_PUBLIC_READ )) {
	    			\Yii::error("$thumbnailAmazonFile upload fails","file");
	    			$doRetry = true;
	    		}    	
	    		if($doRetry === false)
	    			unlink ( $imgTmpPath.$thumbnailName );
	    	}catch(\Exception $e){
	    		\Yii::error("$thumbnailAmazonFile upload fails. Error:".$e->getMessage(),"file");
	    		$doRetry = true;
	    	}
    	}while ($tCounter++ < $tryCount && $doRetry);
    	
    	list($msec,$sec) = explode(' ', microtime()); 
    	$endTime = $sec + $msec;
    	\Yii::info("upload images cost time：  origin image name:".$originName." , size=".round($originSize/1024)."K , try upload counts：$oCounter  ； thumbnail: size=".round($thumbnailSize/1024)."K ，try upload counts：$tCounter ； to amazon s3 butcketName:$bucketName 。total upload cost time upt=".(($endTime-$starttime) * 1000)."ms" ,"file");

    	if($doRetry){
    		exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统上传缩略图到amazon s3服务器失败！'))));
    	}
    	
    	//4. 获取最终圆头和缩略图的url    	
    	$imageUrl="http://".$bucketName.".s3.amazonaws.com/".$originAmazonFile;
    	$thumbnailUrl="http://".$bucketName.".s3.amazonaws.com/".$thumbnailAmazonFile;
    	
    	//5. 记录相关信息到global和user的图片表
    	$userImage=new UserImage();
    	$userImage->origin_url=$imageUrl;
    	$userImage->thumbnail_url=$thumbnailUrl;
    	$userImage->origin_size=$originSize;
    	$userImage->thumbnail_size=$thumbnailSize;
    	$userImage->create_time=date("Y-m-d H:i:s");
    	$userImage->amazon_key=$originAmazonFile;
    	if(!$userImage->save(false)){
    		\Yii::error("$originName UserImage save fail","file");
    	}
	    	
    	$puid=\Yii::$app->user->identity->getParentUid();
    	$globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
    	if ($globalImageInfo===null){
    		$globalImageInfo=new GlobalImageInfo;
    		$globalImageInfo->puid=$puid;
    		$globalImageInfo->image_number=2;
    		$globalImageInfo->total_size=$originSize+$thumbnailSize;
    		if(!$globalImageInfo->save(false)){
    			\Yii::error("$originName : $puid create global image info record failed","file");
    		}
    		
    	}else{
    		$globalImageInfo->image_number=$globalImageInfo->image_number+2;
    		$globalImageInfo->total_size=$globalImageInfo->total_size+$originSize+$thumbnailSize;
    		if(!$globalImageInfo->save(false)){
    			\Yii::error("$originName : $puid update global image info record failed","file");
    		}
    	}
    	
    	exit(json_encode( array('name' => $file["name"], 'status' => true, 'data' => array('original' => $imageUrl , 'thumbnail' => $thumbnailUrl) , 'rtnMsg' => $rtnMsg)));
    }
    
    // /util/image/show-library  图片库list
    public function actionShowLibrary() {
    	//check模块权限
    	if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('image')){
    		return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有图片库模块的权限!']);
    	}
    	
    	AppTrackerApiHelper::actionLog("image_lib", "/util/image/show-library");
    	// 'service'=>1指 7牛图片  ， 0是使用amazon s3上传的图片，2是阿里云oss
    	$query = UserImage::find()->where(['service'=>ImageHelper::$SHOW_IMG_SERVICE]);
    	if(!empty($_REQUEST['name'])){
    		$query->andWhere(['like','original_name',$_REQUEST['name']]);
    	}

    	if(!empty($_REQUEST['classification'])){
    		$arr=[$_REQUEST['classification']];
    		$txt=UtImageClassification::getAllson($_REQUEST['classification']);
    		if(!empty($txt)){
    			$arr=(explode(",",$txt));
    			$arr=array_filter($arr);
    		}
    		$query->andWhere(['classification_id'=>$arr]);
    	}

    	$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>30 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
    	$images = $query->offset($pages->offset)->limit($pages->limit)->orderBy('create_time desc')->asArray()->all();
    	
    	// 使用global 获取使用情况，图片库上线前，清空已有global 信息。后面可能要再将amazon图片转移到七牛
    	$puid=\Yii::$app->user->identity->getParentUid();
//     	$usage = GlobalImageInfo::find()->where(['puid'=>$puid])->asArray()->one();
//     	if(empty($usage)){
//     		$usage = array();
//     		$usage['total_size'] = 0;
//     		$usage['image_number'] = 0;
//     		$usage['library_size'] = ImageHelper::$baseLibrarySize;
//     	}
    	$usage=\eagle\modules\util\helpers\UploadFileHelper::GlobalInfo();

    	//获取图片分类
		$classification=empty($_REQUEST['classification'])?'':$_REQUEST['classification'];
		$imagesClassifica=ImageHelper::ImagesClassificaHtml2(0,$classification);
		$imagesClassificaDropDownList=ImageHelper::ImagesClassificaDropDownList($imagesClassifica['data'],0);
		$imagesClassifica_data=array(
				'imagesClassifica'=>$imagesClassifica,
				'imagesClassificaDropDownList'=>$imagesClassificaDropDownList,
		);
		$permissions = [];
    	return $this->render('list',['images'=>$images,'usage'=>$usage,'pages'=>$pages,'imagesClassifica_data'=>$imagesClassifica_data,'permissions'=>$permissions]);
    }
    
    // /util/image/delete  
    public function actionDelete() {
    	AppTrackerApiHelper::actionLog("image_lib", "/util/image/delete");
    	if(empty($_POST['ids'])){
    		return ResultHelper::getResult(400, '', "请选择图片");
    	}
    	
    	$ids = explode(',', $_POST['ids']);
    	
    	
    	$qiniuImages = UserImage::find()->where(['service'=>1,'id'=>$ids])->asArray()->all();
    	if(!empty($qiniuImages)){
    	    list($ret, $msg) = ImageHelper::deleteQiniuImages($qiniuImages);
    	    if(!$ret)
    	       return ResultHelper::getResult(400, '', "删除失败：".$msg);
    	    
    	}
    	
    	$aliImages = UserImage::find()->where(['service'=>2,'id'=>$ids])->asArray()->all();
    	if(!empty($aliImages)){
    	    list($ret, $msg) = ImageHelper::deleteAliImages($aliImages);
    	    if(!$ret)
    	        return ResultHelper::getResult(400, '', "部分图片删除失败：".$msg);
    	}
    	
    	$localImages = UserImage::find()->where(['service'=>3,'id'=>$ids])->asArray()->all();
    	if(!empty($localImages)){
    	    list($ret, $msg) = ImageHelper::deleteLocalImages($localImages);
    	    if(!$ret)
    	        return ResultHelper::getResult(400, '', "部分图片删除失败：".$msg);
    	}
    	
    	return ResultHelper::getResult(200, "", "删除成功");
    }

    /**
     * 从图片库选择图片
     * @author hqf
     * @version 2016-05-17
     * @return [type] [description]
     */
    function actionSelectImageLib(){ 
    	//获取图片分类
		$imagesClassifica=ImageHelper::ImagesClassificaHtml2(0,'');
    	
        return $this->renderAuto('select-image-lib',[
            'menu'=>[
                'menu'=>[
                    '图片库'=>[
                        'icon'=>'icon-pingtairizhi',
                        'items'=>[
                            '从图片库选择'=>[
                                'url'=>'/util/image/select-image-lib',
                            ],
                        ]
                    ]
                ],
                'active'=>'从图片库选择',
            ],
        	'imagesClassifica'=>$imagesClassifica
        ]);
    }

    function actionGetLibInfo(){
        $imageInfo = GlobalImageInfo::find()->where([
            'puid'=>\Yii::$app->user->identity->getParentUid()
        ])->one();
        return $this->renderJson([
            'totalSize'=>$imageInfo->library_size, // ImageHelper::$baseLibrarySize,
            'usedSize'=>$imageInfo->total_size,
            'count'=>$imageInfo->image_number
        ]);
    }

    /**
     * [actionSelectImageLibList description]
     * @return [type] [description]
     */
    function actionSelectImageLibList(){
        // 'service'=>1指 7牛图片  ， 0是使用amazon s3上传的图片
        $Image = UserImage::find()->where(['service'=>ImageHelper::$SHOW_IMG_SERVICE]);
        if(isset($_POST['id'])){
            // 删除图片
            $_POST['ids'] = $_POST['id'];
            $this->actionDelete();
            // $Image->where([
            //     'id'=>$_POST['id']
            // ])->one()->delete();
            return $this->renderJson([
                'success'=>true,
                'code'=>200,
                'message'=>''
            ]);
        }else{
        	try{
	        	if(!empty($_GET['classification'])){
	        		$arr=[$_GET['classification']];
	        		$txt=UtImageClassification::getAllson($_GET['classification']);
	        		if(!empty($txt)){
	        			$arr=(explode(",",$txt));
	        			$arr=array_filter($arr);
	        		}
	        		$Image->andWhere(['classification_id'=>$arr]);
	        	}
	
	            // get
	            $start = isset($_GET['start'])?$_GET['start']:1;
	            $limit = isset($_GET['limit'])?$_GET['limit']:1;
	            $count = $Image->count();
	            $Image->offset($start)->limit($limit);
	            $html = '';
	            foreach($Image->each(1) as $image){
	                $html .= $this->renderPartial('select-image-lib-item',[
	                    'name'=>basename($image->origin_url),
	                    'id'=>$image->id,
	                    'origin_url'=>$image->origin_url,
	                    'thumbnail_url'=>$image->thumbnail_url,
	                    'size'=>$image->origin_size
	                ],true);
	            }
	            return $this->renderJson([
	                'html'=>$html,
	                'total'=>$count
	            ]);
        	}catch (\Exception $err){
        		print_r($err->getMessage());
        	}
        }
    }

    // dzt20170324 针对云站用户刊登上传图片用阿里云 oss 香港bucket
    // util/image/upload-to-ali-oss
    public function actionUploadToAliOss() {
    
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
//         $usage = GlobalImageInfo::find()->where(['puid'=>$puid])->asArray()->one();
//         if(empty($usage)){
//             $usage['total_size'] = 0;
//             $usage['library_size'] = ImageHelper::$baseLibrarySize;
//         }
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

        $ossAccessId = ImageHelper::$ALI_OSS_ACCESS_ID;
        $ossAccessKey = ImageHelper::$ALI_OSS_ACCESS_KEY;
        $ossEndpoint = ImageHelper::$ALI_OSS_ENDPOINT;
        $bucket = ImageHelper::$ALI_OSS_TEST_BUCKET;
        $ossDomain = $bucket.'.'.$ossEndpoint."/";
        $ossPath = \Yii::$app->user->id."/". date('Ymd');// 阿里云oss保存路径
        $ossFileName = $ossPath."/".$originName;// 上传到阿里云oss 这个值是唯一的，oss通过此值管理图片
        
        $ossClient = new OssClient($ossAccessId, $ossAccessKey, $ossEndpoint);
        
        $doRetry = false;
        $oCounter = 0;
        $tryCount = 3;// 上传失败重试次数
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $originSize = abs(filesize($imgTmpPath . $originName));
        $errMsg = '';
        do{
            try{
//                 $file = 'http://xxx.com/22657ca.jpg';
//                 $content = file_get_contents($file);
//                 $ossClient->createObjectDir($bucket, $ossPath);// 1/20170216/20170216175332-22657ca.jpg 这种文件名会自动创建目录
//                 $ossClient->putObject($bucket, $ossFileName, $content);// 上传在内存里面的图片，file_get_contents 的返回之类的

                $ossClient->uploadFile($bucket, $ossFileName, $imgTmpPath . $originName);// 上传本地图片文件
                $doRetry = false;
                unlink ($imgTmpPath . $originName );// 删除保存到本地的上传图片
            }catch(OssException $e){
                \Yii::error("$ossFileName upload fails. Error:".$e->getMessage(),"file");
                $doRetry = true;
                $errMsg .= $e->getMessage();
            }catch(\Exception $e){
    			\Yii::error("$ossFileName upload fails. Error:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(),"file");
    			$doRetry = true;
    			$errMsg .= $e->getMessage();
    		}
        } while ($oCounter++ < $tryCount && $doRetry);
         
        if($doRetry){
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统上传原图到阿里云服务器失败:').$errMsg)));
        }
         
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        \Yii::info("上传原图 $ossFileName size=".round($originSize/1024)."K 到阿里云服务器  butcketName:$bucket 。 耗时".($timeMS2-$timeMS1)."（毫秒）","file");
         
        //4. 获取最终原图和缩略图的url
        $imageUrl = "http://".$ossDomain.$ossFileName;
        $thumbnailUrl = "http://".$ossDomain.$ossFileName."?x-oss-process=image/resize,m_fill,h_210,w_210";// 长宽为210  等比缩放，居中裁剪
//         $thumbnailUrl = "http://".$ossDomain.$ossFileName."?x-oss-process=image/resize,m_lfit,h_210,w_210";// 宽最大210，高最大210 等比缩放，不裁剪

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
        $userImage->service = 2;// service:0 amazon s3图片上传服务， 1 ： 七牛，2 阿里云oss
        $userImage->amazon_key = $ossFileName;
        $userImage->original_name = $name;
        $userImage->original_width = $imgW;
        $userImage->original_height = $imgH;
        if(!$userImage->save(false)){
            \Yii::error("$ossFileName UserImage save fail","file");
        }
         
        $globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
        if ($globalImageInfo === null){
            $globalImageInfo = new GlobalImageInfo;
            $globalImageInfo->puid = $puid;
            $globalImageInfo->image_number = 1;
            $globalImageInfo->total_size = $originSize;
            if(!$globalImageInfo->save(false)){
                \Yii::error("$ossFileName : $puid create global image info record failed","file");
            }
             
        }else{
            $globalImageInfo->image_number = $globalImageInfo->image_number + 1;
            $globalImageInfo->total_size = $globalImageInfo->total_size + $originSize;
            if(!$globalImageInfo->save(false)){
                \Yii::error("$ossFileName : $puid update global image info record failed","file");
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
        
    }
    
    /**
     * 图片类别设置
     * lgw 2017-07-12
     */
    public function actionImagesClassifica(){
    	//获取图片分类    	
    	$html=ImageHelper::ImagesClassificaHtml(0, 1);
    	
    	return $this->render('image-classification',['html'=>$html['html']]);
    }
    
    /**
     * 图片类别设置保存
     * lgw 2017-07-12
     */
    public function actionImagesClassificaSave(){
    	$newname = empty($_POST['newname'])?'':$_POST['newname'];
    	$editname = empty($_POST['editname'])?'':$_POST['editname'];
    	$delname = empty($_POST['delname'])?'':$_POST['delname'];
    	$newnewname = empty($_POST['newnewname'])?'':$_POST['newnewname'];
    	$newnewname2 = empty($_POST['newnewname2'])?'':$_POST['newnewname2'];
    	$arr_dongtai = empty($_POST['arr_dongtai'])?'':$_POST['arr_dongtai'];

    	$code='true';
    	$msg='';
    	$save_after_new=array();

    	if(!empty($newname)){
    		foreach ($newname as $keys=>$newnameone){
    			if(!empty($newnameone)){
	    			foreach ($newnameone as $keys2=>$newnameoneone){
		    			try{
		    				if(!empty($newnameoneone)){
				    			$UtImageClassification=new UtImageClassification();
				    			$UtImageClassification->name=$newnameoneone;
				    			$UtImageClassification->parentID=$keys;
				    			$UtImageClassification->save();
	
				    			$save_after_new[$keys2]=$UtImageClassification->getDb()->getLastInsertID();
		    				}
		    			}
		    			catch (\Exception $err){
		    				$code=1;
		    				$msg.=$err->getMessage();
		    			}
	    			}
    			}
    		}
    	}

    	if(!empty($editname)){
    		foreach ($editname as $keys=>$editnameone){
    			try{
    				if(!empty($editnameone)){
	    				$UtImageClassification=UtImageClassification::find()->where(['ID'=>$keys])->all();
	    				foreach ($UtImageClassification as &$UtImageClassificationone){
	    					$UtImageClassificationone->name=$editnameone;
	    					$UtImageClassificationone->save();
	    				}
    				}
    			}
    			catch (\Exception $err){
    				$code='false';
    				$msg.=$err->getMessage();
    			}
    		}
    	}
    	
    	if(!empty($delname)){
    		$delname_arr=explode(";",$delname);
    		foreach ($delname_arr as $delnameone){
    			try{
    				ImageHelper::DeleteImagesClassifica($delnameone);
    			}
    			catch (\Exception $err){
    				$code=1;
    				$msg.=$err->getMessage();
    			}
    		}
    	}

    	//子保存
    	$save_after_new_dongtai=[];
    	if(!empty($arr_dongtai)){
    		$arr_dongtai=array_filter($arr_dongtai);
    		foreach ($arr_dongtai as $keys=>$arr_dongtaione){
    			//分级,2级还是3级....
    			if(!empty($arr_dongtaione)){
    				$arr_dongtaione=array_filter($arr_dongtaione);
    				foreach ($arr_dongtaione as $keys2=>$arr_dongtaioneone){
    					//各级数组查找上级id
    					$arr_dongtaioneone==array_filter($arr_dongtaioneone);
    					
    					if(!empty($save_after_new[$keys2])){
    						$newid=$save_after_new[$keys2];
    					}
    					else {
    						$newid=$save_after_new_dongtai[$keys2];
    					}
    					
    					if(!empty($arr_dongtaioneone)){
    						foreach ($arr_dongtaioneone as $keys3=>$arr_dongtaioneoneone){
    							//保存
    							try{
    								$UtImageClassification=new UtImageClassification();
    								$UtImageClassification->name=$arr_dongtaioneoneone;
    								$UtImageClassification->parentID=$newid;
    								$UtImageClassification->save();
    								 
    								$save_after_new_dongtai[$keys3]=$UtImageClassification->getDb()->getLastInsertID();
    							}
    							catch (\Exception $err){
    								$code=1;
    								$msg.=$err->getMessage();
    							}
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	return json_encode(['code'=>$code,'message'=>$msg]);
    	
    }
    
    /**
     * 移动图片类别
     * lgw 2017-07-12
     */
    public function actionImagesClassificaMove(){
    	$ids = empty($_POST['ids'])?array():$_POST['ids'];
    	$c_id = empty($_POST['c_id'])?0:$_POST['c_id'];

    	$code='true';
    	$msg='';
    	    	
    	try{
    		UserImage::updateAll(['classification_id'=>$c_id],['id'=>$ids]);
    	}
    	catch (\Exception $err){
    		$code=1;
    		$msg.=$err->getMessage();
    	}
    	
    	return json_encode(['code'=>$code,'message'=>$msg]);
    }
    

}
