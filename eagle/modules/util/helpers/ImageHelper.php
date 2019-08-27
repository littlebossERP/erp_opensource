<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\models\SysLog;
use eagle\modules\util\models\GlobalLog;
use eagle\modules\util\models\S3;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Endroid\QrCode\QrCode;
use eagle\modules\util\models\GlobalImageInfo;
use eagle\modules\util\models\UserImage;
use OSS\OssClient;
use OSS\Core\OssException;


/**
 +------------------------------------------------------------------------------
 * log模块
 +------------------------------------------------------------------------------
 * @category	Image
 * @package		Helper
 * @subpackage  Exception
 * @author		YZQ
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class ImageHelper {
	/**
	 +---------------------------------------------------------------------------------------------
	 * 上传图片到amazon s3服务器
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $sourceFile		
	 +---------------------------------------------------------------------------------------------
	 * @return						
	 *	 
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	
	public static $photoMaxSize = 5242880 ; // 5M
	public static $baseLibrarySize = 524288000 ; //  209715200 200M  dzt20160615 改成 500M
// 	private static $photoType = array ('TN' => 150, /*'SM' => 400, 'LG' => 1000 */ );
	
	public static $photoMime = array ( 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
	
	// TODO imglib qiniu account @XXX@
	public static $qiniuAccessKey = '@XXX@';
	public static $qiniuSecretKey = '@XXX@';
	public static $qiniuDomain = '@XXX@.com/';//自己绑定的域名
	public static $qiniuDomainBackup = '@XXX@.clouddn.com/';//七牛分配的域名
	 
	// TODO imglib alioss account
	public static $ALI_OSS_ACCESS_ID = '@XXX@';
	public static $ALI_OSS_ACCESS_KEY = '@XXX@';
	public static $ALI_OSS_ENDPOINT = '@XXX@.aliyuncs.com';
	public static $ALI_OSS_TEST_BUCKET = '@XXX@';
	
	public static $SHOW_IMG_SERVICE = [1,2,3];//0:amazon s3 , 1:qiniu,2:alioss,3:local
	
	
	public static function getQiniuBackupLink($link){
	    return str_replace(self::$qiniuDomain, self::$qiniuDomainBackup, $link);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成缩略图
	 *
	 +---------------------------------------------------------------------------------------------
	 * @param $fileName 原图文件名称
	 * @param $path  原图路径
	 * @param $thumbMaxSide  长或宽的最大px  
	 +---------------------------------------------------------------------------------------------
	 * @return 缩略图名称
	 *
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function generateThumbnail($fileName,$path,$thumbMaxSide=150){
		
		$targetName = "thumb_".$thumbMaxSide."_".$fileName;
		$src_file = $path.$fileName;
		$des_file = $path.$targetName;
		
		$im_size = getimagesize($src_file);
		$srcW = $im_size[0];
		$srcH = $im_size[1];
		$src_type = $im_size[2];
		
		$source = self::createImageSource( $src_file, $im_size ["mime"]);
		
		// 缩放并保存图片
		$result = self::genReSizePhoto($source, $im_size[0], $im_size[1], $thumbMaxSide , $im_size ["mime"] , $des_file);
		return array($result , $targetName);	
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取图片resource
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param fileName		需要判断的路径字符串
	 * @param imgType		图片类型
	 +----------------------------------------------------------
	 * @return				图片resource
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	protected static function createImageSource($fileName, $imgType) {
		switch ($imgType) {
			case "image/jpeg" :
				$source = imagecreatefromjpeg ( $fileName ); //jpeg file
				break;
			case "image/jpg" :
				$source = imagecreatefromjpeg ( $fileName ); //jpeg file
				break;
			case "image/pjpeg" :
				$source = imagecreatefromjpeg ( $fileName ); //jpeg file
				break;
			case "image/gif" :
				$source = imagecreatefromgif ( $fileName ); //gif file
				break;
			case "image/png" :
				$source = imagecreatefrompng ( $fileName ); //png file
				break;
			default :
				$source = false;
				break;
		}
	
		return $source;
	}
	
	/**
	 +----------------------------------------------------------
	 * 缩放并保存图片
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片resource
	 * @param width			图片宽度
	 * @param height		图片高度
	 * @param reSize		缩放大小
	 * @param imgType		图片类型
	 * @param fileName		图片路径字符串
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function genReSizePhoto($source, $width, $height, $reSize , $imgType , $fileName) {
		if ($width > $reSize || $height > $reSize) {
			$newWidth = 0;
			$newHeight = 0;
			if ($width > $height) {
				$newWidth = $reSize;
				$newHeight = round($reSize * $height / $width);
			} else {
				$newWidth = round($reSize * $width / $height);
				$newHeight = $reSize;
			}
	
		}else{
			$newWidth = $width;
			$newHeight = $height;
		}
		$thumb = imagecreatetruecolor($newWidth, $newHeight);
		
		// 都是GD库为图片生成缩略图的函数
   		// 如果对缩略图的质量要求不高可以使用imagecopyresized()函数，imagecopyresize()所生成的图像比较粗糙，但是速度较快。
// 		imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
// 		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height); 
		
		switch ($imgType) {
			case "image/jpeg" :
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				$reusult = imagejpeg ( $thumb, $fileName , 100 ); //jpeg file
				break;
			case "image/jpg" :
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				$reusult = imagejpeg ( $thumb, $fileName , 100 ); //jpeg file
				break;
			case "image/pjpeg" :
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				$reusult = imagejpeg ( $thumb, $fileName , 100 ); //jpeg file
				break;
			case "image/gif" :
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				$reusult = imagegif ( $thumb, $fileName , 100 ); //gif file
				break;
			case "image/png" :
				imagealphablending($thumb, false);
				imagesavealpha($thumb,true);
				$transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
				imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				$reusult = imagepng($thumb,$fileName); //png file
				break;
			default :
				$reusult = false;
				break;
		}
	
		return $reusult;
	}
	
	public static function getLeftMenuArr(){
		return [
			'XX管理'=>[
				'icon'=>'icon-shezhi',
				'items'=>[
					'图片库'=>[
						'url'=>'/util/image/show-library',
					],
					// '发布成功'=>[
						// 'url'=>'/listing/'.$platform.'-listing/publish-success',
						// 'tabbar'=>84,
					// ],
				]
			],
		];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 七牛缓存PayPal Suppliers客户 产品url
	 +---------------------------------------------------------------------------------------------
	 * @param  $origin_url
	 * @param  $puid
	 +---------------------------------------------------------------------------------------------
	 * @return multitype:boolean string
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016-04-25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPaypalSuppliersListingImages($puid,$origin_url){
		
		$qiniuPath = "paypal-suppliers-cache/".$puid."/". date('Ymd')."/";
		
		// 获取图片key 上传到七牛的key
		$parseUrlInfo = parse_url($origin_url);
		$extension = strtolower(substr($parseUrlInfo['path'] , strripos($parseUrlInfo['path'],'.') + 1 )) ;
		// 即使同一条url 分3次上传我们都分别替用户上传
		$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $parseUrlInfo['path'] ), 0, 5 ) . '.' . $extension;
		$qiniuKey = $qiniuPath.$originName;
		
		list($ret,$imgTmpPath) = self::downLoadImages($originName,$origin_url);
		if($ret == false){
			return array(false,$imgTmpPath);
		}
		
		$accessKey = ImageHelper::$qiniuAccessKey;
		$secretKey = ImageHelper::$qiniuSecretKey;
		$qiniuDomain = ImageHelper::$qiniuDomain;
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'littleboss-image';
		$token = $auth->uploadToken($bucket);
		$uploadMgr = new UploadManager();
		
		try{
			$tryCount = 3;
			$oCounter = 0;
			$doRetry = false;
			do{
				$timeMS11 = TimeUtil::getCurrentTimestampMS();
				list($ret, $err) = $uploadMgr->putFile($token, $qiniuKey , $imgTmpPath);
				if ($err !== null) {
					$doRetry = true;
					\Yii::error("getPaypalSuppliersListingImages $qiniuKey upload fails. Error:".print_r($err,true),"file");
				}
				
				if($doRetry === false){
					unlink ($imgTmpPath);// 删除保存到本地的上传图片
					$timeMS12 = TimeUtil::getCurrentTimestampMS();
					\Yii::info("getPaypalSuppliersListingImages 上传url $qiniuKey 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS12-$timeMS11)."（毫秒）" , "file");
				}
			}while ($oCounter++ < $tryCount && $doRetry);
		}catch(\Exception $e){
    		\Yii::error("getPaypalSuppliersListingImages $qiniuKey upload fails. Exception:file:".$e->getFile().", line:".$e->getLine().", message:".$e->getMessage(),"file");
    		$doRetry = true;
    		return array( false , $e->getMessage());
    	}
    	
    	if($doRetry){
    		return array( false , "系统上传原图到七牛服务器失败。err:".print_r($err,true));
    	}
    	
		return array(true,"http://".$qiniuDomain.$qiniuKey);
	}
	
	/**
	 * 下载图片到本地上传 
	 * 由于paypal suppliers的图片可能是https的，直接通过七牛抓取或者直接通过自建站拉取都失败，所以这里先下载图片，再上传到七牛。
	 * 
	 * @param string $originName
	 * @param string $origin_url
	 * @return multitype:boolean string
	 */
	public static function downLoadImages($originName,$origin_url){
		$imgTmpPath = \Yii::getAlias("@eagle/media/temp/");
		$ch = curl_init ();
		curl_setopt($ch, CURLOPT_URL, $origin_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		$rawdata = curl_exec($ch);

		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if ($curl_errno > 0) { // network error
			curl_close($ch);
			return array(false,"cURL Error $curl_errno : $curl_error");
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		//echo $httpCode.$response."\n";
		if ($httpCode == '200' ){
			$fp = fopen($imgTmpPath.$originName,'w');
			fwrite($fp, $rawdata);
			fclose($fp);
			return array(true,$imgTmpPath.$originName);
		}else{ // network error
			return array(false,"Got error respond code $httpCode from $origin_url");
		}
	}
	
	/**
	 * 生成二维码图片并上传到七牛
	 *
	 * @param string $originName
	 * @param string $origin_url
	 * @return multitype:boolean string
	 */
	public static function genQrcode($content,$puid){
		$imgTmpPath = \Yii::getAlias("@eagle/media/temp/");
		$qiniuPath = "qr-code/".$puid."/". date('Ymd')."/";
		
		$extension = "png" ;
		// 即使同一条url 分3次上传我们都分别替用户上传
		$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $content ), 0, 5 ) . '.' . $extension;
		$qiniuKey = $qiniuPath.$originName;
		
		$qrCode = new QrCode();
		$qrCode
// 		->setText("ahahaha~")
		->setText($content)
		->setSize(200)
		->setPadding(10)
		->setErrorCorrection('high')
		->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
		->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
// 		->setLabel('My label')
		->setLabelFontSize(16)
// 		->render('hahaha.jpg');
		->render($imgTmpPath.$originName);
		
		
		$accessKey = ImageHelper::$qiniuAccessKey;
		$secretKey = ImageHelper::$qiniuSecretKey;
		$qiniuDomain = ImageHelper::$qiniuDomain;
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'littleboss-image';
		$token = $auth->uploadToken($bucket);
		$uploadMgr = new UploadManager();
		
		try{
			$tryCount = 3;
			$oCounter = 0;
			$doRetry = false;
			do{
				$timeMS11 = TimeUtil::getCurrentTimestampMS();
				list($ret, $err) = $uploadMgr->putFile($token, $qiniuKey , $imgTmpPath.$originName);
				if ($err !== null) {
					$doRetry = true;
					\Yii::error("getPaypalSuppliersListingImages $qiniuKey upload fails. Error:".print_r($err,true),"file");
				}
		
				if($doRetry === false){
					unlink ($imgTmpPath.$originName);// 删除保存到本地的上传图片
					$timeMS12 = TimeUtil::getCurrentTimestampMS();
					\Yii::info("getPaypalSuppliersListingImages 上传url $qiniuKey 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS12-$timeMS11)."（毫秒）" , "file");
				}
			}while ($oCounter++ < $tryCount && $doRetry);
		}catch(\Exception $e){
			\Yii::error("getPaypalSuppliersListingImages $qiniuKey upload fails. Exception:file:".$e->getFile().", line:".$e->getLine().", message:".$e->getMessage(),"file");
			$doRetry = true;
			return array( false , $e->getMessage());
		}
		 
		if($doRetry){
			return array( false , "系统上传原图到七牛服务器失败。err:".print_r($err,true));
		}
		 
		return array(true,"http://".$qiniuDomain.$qiniuKey);
	}
		
	/**
	 * 上传图片链接到本地
	 *
	 * @param string $imageUrls
	 * @param int $puid
	 * @return multitype:boolean string
	 */
	public static function uploadImageUrlToLocal($imageUrls, $puid, $imgHost=""){
	    if(empty($imageUrls))
	        return array(false,"请输入图片链接");
	    	
	    $toFetchUrls = array();
	    
	    $localPath = "/images/p/{$puid}/". date('Ymd') . '/';
	    $imgTmpPath = \Yii::getAlias('@eagle/web') . $localPath;
	    if(!file_exists($imgTmpPath)){
	        mkdir($imgTmpPath, 0777, true);
	    }
	    
	    	
	    // 1.检查url
	    foreach ($imageUrls as $upUrl){
	        $imageSize = @getimagesize($upUrl);
	        if(false == $imageSize){
	            return array(false,"无法获取url,或url：".$upUrl."不是图片。");
	        }
	
	        if(!in_array(strtolower(substr($upUrl,strrpos($upUrl,".")+1)), ImageHelper::$photoMime) || !array_key_exists($imageSize['mime'], ImageHelper::$photoMime)){
	            return array(false,TranslateHelper::t("%s :对不起，我们只支持上传 %s 格式的图片！" , $upUrl , implode(",", array_keys(ImageHelper::$photoMime))));
	        }
	
	        // 获取图片key 上传到七牛的key
	        $parseUrlInfo = parse_url($upUrl);
	        // 即使同一条url 分3次上传我们都分别替用户上传
	        $originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $parseUrlInfo['path'] ), 0, 5 ) . '.' . ImageHelper::$photoMime[$imageSize['mime']];
	        $imgKey = $localPath.$originName;
	
	        $toFetchUrls[$imgKey] = array();
	        $toFetchUrls[$imgKey]['url'] = $upUrl;
	        $toFetchUrls[$imgKey]['imgKey'] = $imgKey;
	        $toFetchUrls[$imgKey]['width'] = $imageSize[0];
	        $toFetchUrls[$imgKey]['height'] = $imageSize[1];
	        $toFetchUrls[$imgKey]['name'] = $parseUrlInfo['path'];
	        $toFetchUrls[$imgKey]['newName'] = $originName;
	
	    }
	    	
	    $rtnMsg = "";
	    	
	    if($rtnMsg <> ''){
	        return array(false,$rtnMsg);
	    }
	    	
	   
	    if(empty($toFetchUrls)){
	        return array(false,"没有合规格的图片url上传");
	    }
	    	
	    // 3.上传url
	    $uploadedUrl = array();
	    $timeMS1 = TimeUtil::getCurrentTimestampMS();
	    try{
	        foreach ($toFetchUrls as $imgKey=>$oneUrlInfo){
                $content = file_get_contents($oneUrlInfo['url']);
                file_put_contents($imgTmpPath . $oneUrlInfo['newName'], $content);
                $originSize = abs(filesize($imgTmpPath . $oneUrlInfo['newName']));
                list($ret, $thumbnailname) = ImageHelper::generateThumbnail($oneUrlInfo['newName'], $imgTmpPath);
                $thumbnailSize = abs(filesize($imgTmpPath . $thumbnailname));
                $oneUrlInfo['originSize'] = $originSize;
                $oneUrlInfo['thumbnailSize'] = $thumbnailSize;
                $oneUrlInfo['thumbnailname'] = $thumbnailname;
                $uploadedUrl[] = $oneUrlInfo;
	        }
	    }catch(\Exception $e){
	        \Yii::error("actionUploadImageUrl $imgKey upload fails. Error:".$e->getMessage(),"file");
	    }
	    	
	    $timeMS2 = TimeUtil::getCurrentTimestampMS();
	    \Yii::info("actionUploadImageUrl 上传".count($uploadedUrl)."个url 到服务器 。 耗时".($timeMS2-$timeMS1)."（毫秒）" , "file");
	
	    if(empty($uploadedUrl)){
	        return array(false,"没有图片url上传成功");
	    }
	
	    $returnUrl = array();
	    foreach ($uploadedUrl as $uploadedOneUrl){
	        //4. 获取最终原图和缩略图的url
	        $imageUrl = $imgHost.$uploadedOneUrl['imgKey'];
	        $thumbnailUrl = $imgHost.$localPath.$uploadedOneUrl['thumbnailname'];
	        $imgKey = $uploadedOneUrl['imgKey'];
	        
	        //5. 记录相关信息到global和user的图片表
	        $userImage = new UserImage();
	        $userImage->origin_url = $imageUrl;
	        $userImage->thumbnail_url = $thumbnailUrl;
	        $userImage->origin_size = $uploadedOneUrl['originSize'];
	        $userImage->thumbnail_size = $uploadedOneUrl['thumbnailSize'];
	        $userImage->create_time = date("Y-m-d H:i:s");
	        $userImage->service = 3;// service:0 amazon s3图片上传服务， 1 ： 七牛
	        $userImage->amazon_key = $uploadedOneUrl['imgKey'];
	        $userImage->original_name = $uploadedOneUrl['name'];
	        $userImage->original_width = $uploadedOneUrl['width'];
	        $userImage->original_height = $uploadedOneUrl['height'];
	        if(!$userImage->save(false)){
	            \Yii::error("$imgKey UserImage save fail","file");
	        }
	
	        $globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
	        if ($globalImageInfo === null){
	            $globalImageInfo = new GlobalImageInfo;
	            $globalImageInfo->puid = $puid;
	            $globalImageInfo->image_number = 2;
	            $globalImageInfo->total_size = $uploadedOneUrl['originSize'] + $uploadedOneUrl['thumbnailSize'];
	            if(!$globalImageInfo->save(false)){
	                \Yii::error("actionUploadImageUrl $imgKey : $puid create global image info record failed","file");
	            }
	
	        }else{
	            $globalImageInfo->image_number = $globalImageInfo->image_number + 2;
	            $globalImageInfo->total_size = $globalImageInfo->total_size + $uploadedOneUrl['originSize'] + $uploadedOneUrl['thumbnailSize'];
	            if(!$globalImageInfo->save(false)){
	                \Yii::error("actionUploadImageUrl $imgKey : $puid update global image info record failed","file");
	            }
	        }
	        	
	        $returnUrl[] = $imageUrl;
	    }
	
	    return array(true,$returnUrl);
	}
	
	/**
	 * 上传图片链接到图片库
	 *
	 * @param string $imageUrls
	 * @param int $puid
	 * @return multitype:boolean string
	 */
	public static function uploadImageUrlToImageLibrary($imageUrls,$puid){
		if(empty($imageUrls))
			return array(false,"请输入图片链接");
			
		$toFetchUrls = array();
		$qiniuPath = $puid."/". date('Ymd')."/";
		$totalAddSize = 0;
		 
		// 1.检查url
		foreach ($imageUrls as $upUrl){
			$imageSize = @getimagesize($upUrl);
			if(false == $imageSize){
				return array(false,"无法获取url,或url：".$upUrl."不是图片。");
			}
		
			if(!in_array(strtolower(substr($upUrl,strrpos($upUrl,".")+1)), ImageHelper::$photoMime) || !array_key_exists($imageSize['mime'], ImageHelper::$photoMime)){
				return array(false,TranslateHelper::t("%s :对不起，我们只支持上传 %s 格式的图片！" , $upUrl , implode(",", array_keys(ImageHelper::$photoMime))));
			}
		
			// 获取图片key 上传到七牛的key
			$parseUrlInfo = parse_url($upUrl);
			// 即使同一条url 分3次上传我们都分别替用户上传
			$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $parseUrlInfo['path'] ), 0, 5 ) . '.' . ImageHelper::$photoMime[$imageSize['mime']];
			$qiniuKey = $qiniuPath.$originName;
		
			$toFetchUrls[$qiniuKey] = array();
			$toFetchUrls[$qiniuKey]['url'] = $upUrl;
			$toFetchUrls[$qiniuKey]['qiniuKey'] = $qiniuKey;
			$toFetchUrls[$qiniuKey]['width'] = $imageSize[0];
			$toFetchUrls[$qiniuKey]['height'] = $imageSize[1];
			$toFetchUrls[$qiniuKey]['name'] = $parseUrlInfo['path'];
		
			// 获取图片大小
			$urlInfo = get_headers($upUrl,1);// getimagesize 应该能排除图片问题的情况，这里不作判断了
			$toFetchUrls[$qiniuKey]['origin_size'] = $urlInfo['Content-Length'];
			$totalAddSize += $urlInfo['Content-Length'];
		}
		 
		$rtnMsg = "";
		 
		// 2.检查图片库空间
// 		$usage = GlobalImageInfo::find()->where(['puid'=>$puid])->asArray()->one();
// 		if(empty($usage)){
// 			$usage['total_size'] = 0;
// 			$usage['library_size'] = ImageHelper::$baseLibrarySize;
// 		}
		$all_usage=UploadFileHelper::GlobalInfo($puid);
		$usage=array(
				"total_size"=>$all_usage['count_size'],
				"library_size"=>$all_usage['count_library_size'],
		);
		if(empty($usage)){
			$usage['total_size'] = 0;
			$usage['library_size'] = ImageHelper::$baseLibrarySize;
		}
		
		
		$newUsage = $usage['total_size'] + $totalAddSize;
		if( $newUsage > $usage['library_size']){
			$rtnMsg .= TranslateHelper::t("上传图片大小%sM ,上传图片后图片库使用 %sM , 超出规定大小  %sM ，请重新上传图片!" , round($totalAddSize / 1024 / 1024 , 2) , round($newUsage / 1024 / 1024 , 2) , round($usage['library_size'] / 1024 / 1024 , 2));
		}
		
		if($rtnMsg <> ''){
			return array(false,$rtnMsg);
		}
		 
		$accessKey = ImageHelper::$qiniuAccessKey;
		$secretKey = ImageHelper::$qiniuSecretKey;
		$qiniuDomain = ImageHelper::$qiniuDomain;
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'littleboss-image';
		$bucketMgr = new BucketManager($auth);
		 
		if(empty($toFetchUrls)){
			return array(false,"没有合规格的图片url上传");
		}
		 
		// 3.上传url
		$uploadedUrl = array();
		$timeMS1 = TimeUtil::getCurrentTimestampMS();
		try{
			foreach ($toFetchUrls as $qiniuKey=>$oneUrlInfo){
				$tryCount = 3;
				$oCounter = 0;
				$doRetry = false;
				do{
					$timeMS11 = TimeUtil::getCurrentTimestampMS();
					list($ret, $err) = $bucketMgr->fetch($oneUrlInfo['url'], $bucket , $oneUrlInfo['qiniuKey'] );
					if ($err !== null) {
						$doRetry = true;
						\Yii::error("actionUploadImageUrl $qiniuKey upload fails. Error:".print_r($err,true),"file");
					}
		
					if($doRetry === false){
						$timeMS12 = TimeUtil::getCurrentTimestampMS();
						\Yii::info("actionUploadImageUrl 上传url $qiniuKey size=".round($oneUrlInfo['origin_size']/1024)."K 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS12-$timeMS11)."（毫秒）" , "file");
						$uploadedUrl[] = $oneUrlInfo;
					}
				}while ($oCounter++ < $tryCount && $doRetry);
			}
		}catch(\Exception $e){
			\Yii::error("actionUploadImageUrl $qiniuKey upload fails. Error:".$e->getMessage(),"file");
		}
		 
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		\Yii::info("actionUploadImageUrl 上传".count($uploadedUrl)."个url 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS2-$timeMS1)."（毫秒）" , "file");
		
		if(empty($uploadedUrl)){
			return array(false,"没有图片url上传成功");
		}
		
		$returnUrl = array();
		foreach ($uploadedUrl as $uploadedOneUrl){
			//4. 获取最终原图和缩略图的url
			$imageUrl = "http://".$qiniuDomain.$uploadedOneUrl['qiniuKey'];
			// 以前的缩略图用 最大边150 这里改为160
			//     		$thumbnailUrl = "http://".$qiniuDomain.$uploadedOneUrl['qiniuKey']."?imageView2/2/w/160/h/160";// 宽最大160，高最大160 等比缩放，不裁剪
			$thumbnailUrl = "http://".$qiniuDomain.$uploadedOneUrl['qiniuKey']."?imageView2/1/w/160/h/160";// 长宽为160  等比缩放，居中裁剪
			$qiniuKey = $uploadedOneUrl['qiniuKey'];
			//5. 记录相关信息到global和user的图片表
			$userImage = new UserImage();
			$userImage->origin_url = $imageUrl;
			$userImage->thumbnail_url = $thumbnailUrl;
			$userImage->origin_size = $uploadedOneUrl['origin_size'];
			$userImage->thumbnail_size = 0;
			$userImage->create_time = date("Y-m-d H:i:s");
			$userImage->service = 1;// service:0 amazon s3图片上传服务， 1 ： 七牛
			$userImage->amazon_key = $uploadedOneUrl['qiniuKey'];
			$userImage->original_name = $uploadedOneUrl['name'];
			$userImage->original_width = $uploadedOneUrl['width'];
			$userImage->original_height = $uploadedOneUrl['height'];
			if(!$userImage->save(false)){
				\Yii::error("$qiniuKey UserImage save fail","file");
			}
		
			$globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
			if ($globalImageInfo === null){
				$globalImageInfo = new GlobalImageInfo;
				$globalImageInfo->puid = $puid;
				$globalImageInfo->image_number = 1;
				$globalImageInfo->total_size = $uploadedOneUrl['origin_size'];
				if(!$globalImageInfo->save(false)){
					\Yii::error("actionUploadImageUrl $qiniuKey : $puid create global image info record failed","file");
				}
		
			}else{
				$globalImageInfo->image_number = $globalImageInfo->image_number + 1;
				$globalImageInfo->total_size = $globalImageInfo->total_size + $uploadedOneUrl['origin_size'];
				if(!$globalImageInfo->save(false)){
					\Yii::error("actionUploadImageUrl $qiniuKey : $puid update global image info record failed","file");
				}
			}
			
			$returnUrl[] = $imageUrl;
		}

		return array(true,$returnUrl);
	}
	
	
	/**
	 * 删除七牛文件
	 * dzt 2017-03-24
	 */
	public static function deleteQiniuImages($images){
	    $deleteKeys = array();
	    $deleteNum = 0;
	    $deleteSize= 0;
	    $ids = array();
	    foreach ($images as $oneImage){
	        $deleteNum ++;
	        $deleteSize += $oneImage['origin_size'];
	        $deleteKeys[] = $oneImage["amazon_key"];
	        $ids[] = $oneImage["id"];
	    }
	    
	    if(!empty($deleteKeys)){
	        $accessKey = ImageHelper::$qiniuAccessKey;
	        $secretKey = ImageHelper::$qiniuSecretKey;
	        $qiniuDomain = ImageHelper::$qiniuDomain;
	        $auth = new Auth($accessKey, $secretKey);
	        $bucket = 'littleboss-image';
	        $uploadMgr = new BucketManager($auth);
	        $operations = $uploadMgr->buildBatchDelete($bucket, $deleteKeys);
	    
	        $timeMS1=TimeUtil::getCurrentTimestampMS();
	    
	        list($ret, $err) = $uploadMgr->batch($operations);
	        $timeMS2=TimeUtil::getCurrentTimestampMS();
	        if ($err !== null) {
	            \Yii::error("deleteQiniuImages: ".$err->message().",iamges:".json_encode($images),"file");
	            return array(true,$err->message());
	        }else{
	            self::_resetGlobalImageInfo($deleteNum,$deleteSize);
	            UserImage::deleteAll(['service'=>1,'id'=>$ids]);
	        }
	    }
	    
	    return array(true,'');
	}
	
	/**
	 * 上传本地图片文件到 阿里云oss for amazon 图片
	 * 不保存到图片库
	 * 
	 * @param $puid 
	 * @param $imagePath 图片绝对路径
	 * 
	 * @return array(bool , $imageUrl(图片阿里云url) | $errMsg(上传错误信息))
	 * dzt 2017-07-12
	 * 
	 */
	public static function uploadAmazonLocalFileToAliOss($puid,$imagePath){
	    $originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $imagePath ), 0, 5 ) . '.' . pathinfo ( $imagePath, PATHINFO_EXTENSION );
	    $ossAccessId = ImageHelper::$ALI_OSS_ACCESS_ID;
	    $ossAccessKey = ImageHelper::$ALI_OSS_ACCESS_KEY;
	    $ossEndpoint = ImageHelper::$ALI_OSS_ENDPOINT;
	    $bucket = ImageHelper::$ALI_OSS_TEST_BUCKET;
	    $ossDomain = $bucket.'.'.$ossEndpoint."/";
	    $ossPath = "amazonlisting/". date('Ymd')."/".$puid;// 阿里云oss保存路径
	    $ossFileName = $ossPath."/".$originName;// 上传到阿里云oss 这个值是唯一的，oss通过此值管理图片
	    
	    $ossClient = new OssClient($ossAccessId, $ossAccessKey, $ossEndpoint);
	    
	    $doRetry = false;
	    $oCounter = 0;
	    $tryCount = 3;// 上传失败重试次数
	    $timeMS1 = TimeUtil::getCurrentTimestampMS();
	    
	    $errMsg = '';
	    do{
	        try{
	            $ossClient->uploadFile($bucket, $ossFileName, $imagePath);// 上传本地图片文件
	            $doRetry = false;
	            unlink ($imagePath);// 删除保存到本地的上传图片
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
	        return array(false,TranslateHelper::t('系统上传原图到阿里云服务器失败:').$errMsg);
	    }
	    
	    $imageUrl = "http://".$ossDomain.$ossFileName;
	    return array(true,$imageUrl);
	}
	
	/**
	 * 上传本地图片文件到 七牛 for 0so1 客户意见反馈功能图片保存
	 * 不保存到图片库
	 *
	 * @param int $puid
	 * @param array $files // $_FILES 作为参数传入
	 *
	 * @return array(bool , $imageUrl(七牛图片url) | $errMsg(上传错误信息))
	 * dzt 2017-07-21
	 *
	 */
	public static function uploadLocalFileToQiniuForFeedback($puid,$file){
	    set_time_limit(0);
	    $imgTmpPath=\Yii::getAlias('@app') . '/media/temp/';
	    //1.检查上传的图片信息
	    if (!isset($files["product_photo_file"]) || !is_uploaded_file($files["product_photo_file"]["tmp_name"]))  {	//是否存在文件
	        return array(false, "图片不存在！");
	    }
	    	
	    $name = $file["name"];
		$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
		 
// 		if(move_uploaded_file ( $file["tmp_name"] , $imgTmpPath . $originName ) === false) {// 重命名上传图片
// 			return array(false, "系统获取图片失败！");
// 		}
		 
		$accessKey = ImageHelper::$qiniuAccessKey;
		$secretKey = ImageHelper::$qiniuSecretKey;
		$qiniuDomain = ImageHelper::$qiniuDomain;
		
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'littleboss-image';
		$token = $auth->uploadToken($bucket);
		$uploadMgr = new UploadManager();
		 
		$qiniuPath = "feedback/". date('Ymd')."/".$puid."/";// 七牛用户路径
		$qiniuKey = $qiniuPath.$originName;// 上传到七牛的图片key值，这个值是唯一的，七牛通过此值管理图片。
		$doRetry = false;
		$oCounter = 0;
		$tryCount = 3;// 上传失败重试次数
	    
		do{
		    try{
		        list($ret, $err) = $uploadMgr->put($token, $qiniuKey , $file["tmp_name"]);
		        if ($err !== null) {
		            $doRetry = true;
		            \Yii::error("$qiniuKey upload fails. Error:".print_r($err,true),"file");
		        }
// 		        if($doRetry === false)
// 		            unlink ($imgTmpPath . $originName );// 删除保存到本地的上传图片
		    }catch(\Exception $e){
		        \Yii::error("$qiniuKey upload fails. Error:".$e->getMessage(),"file");
		        $doRetry = true;
		    }
		} while ($oCounter++ < $tryCount && $doRetry);
			
		if($doRetry){
		    return array(false, '系统上传原图到七牛服务器失败:'.$err);
		}
		
	     
		$imageUrl = "http://".$qiniuDomain.$qiniuKey;
	    return array(true, $imageUrl);
	}
	
	/**
	 * 删除阿里云oss 文件
	 * dzt 2017-03-24
	 */
	public static function deleteAliImages($images){
	    $deleteKeys = array();
	    $deleteNum = 0;
	    $deleteSize= 0;
	    $errMsg = '';
	    $ids = array();
	    foreach ($images as $oneImage){
	        $deleteNum ++;
	        $deleteSize += $oneImage['origin_size'];
	        $deleteKeys[] = $oneImage["amazon_key"];
	        $ids[] = $oneImage["id"];
	    }
	     
	    if(!empty($deleteKeys)){
	        
	        $timeMS1=TimeUtil::getCurrentTimestampMS();
	        try{
	            $ossClient = new OssClient(self::$ALI_OSS_ACCESS_ID, self::$ALI_OSS_ACCESS_KEY, self::$ALI_OSS_ENDPOINT);
                $bucket = self::$ALI_OSS_TEST_BUCKET;
	            $ossClient->deleteObjects($bucket, $deleteKeys);
	            
	        } catch(OssException $e) {
	            \Yii::error("deleteAliImages fails. Error:".$e->getMessage().",iamges:".json_encode($images),"file");
                $errMsg .= $e->getMessage();
            }catch(\Exception $e){
                \Yii::error("deleteAliImages fails. Error:file:".
                        $e->getFile().",line:".$e->getLine().",message:".$e->getMessage().",iamges:".json_encode($images),"file");
                $errMsg .= $e->getMessage();
            }
            
	        $timeMS2=TimeUtil::getCurrentTimestampMS();
	        if (!empty($errMsg)) {
	            return array(false,$errMsg);
	        }else{
	            self::_resetGlobalImageInfo($deleteNum,$deleteSize);
	            UserImage::deleteAll(['service'=>2,'id'=>$ids]);
	        }
	    }
	    
	    return array(true,'');
	}
	
	/**
	 * 删除本地 文件
	 */
	public static function deleteLocalImages($images){
	    $deleteFiles = array();
	    $deleteNum = 0;
	    $deleteSize= 0;
	    $errMsg = '';
	    $ids = array();
	    foreach ($images as $oneImage){
	        $deleteNum += 2;
	        $deleteSize += $oneImage['origin_size'];
	        $deleteFiles[] = \Yii::getAlias('@eagle/web').$oneImage["amazon_key"];
	        
	        $deleteSize += $oneImage['thumbnail_size'];
	        $thumnailUrlPart = explode("/", $oneImage['thumbnail_url']);
	        $thumnailname = array_pop($thumnailUrlPart);
	        $keyPart = explode("/", $oneImage["amazon_key"]);
	        array_pop($keyPart);
	        array_push($keyPart, $thumnailname);
	        
	        $deleteFiles[] = \Yii::getAlias('@eagle/web').implode('/', $keyPart);
	        
	        $ids[] = $oneImage["id"];
	    }
	    
	    if(!empty($deleteFiles)){
	         
	        $timeMS1=TimeUtil::getCurrentTimestampMS();
	        foreach ($deleteFiles as $file)
	           @unlink ($file);
	    
	        $timeMS2=TimeUtil::getCurrentTimestampMS();
	        if (!empty($errMsg)) {
	            return array(false,$errMsg);
	        }else{
	            self::_resetGlobalImageInfo($deleteNum,$deleteSize);
	            UserImage::deleteAll(['service'=>3,'id'=>$ids]);
	        }
	    }
	     
	    return array(true,'');
	}
	
	
	/**
	 * 删除图片后 修改图片库GlobalImageInfo表数据
	 * dzt 2017-03-24
	 */
	private static function _resetGlobalImageInfo($deleteNum,$deleteSize){
	    $puid=\Yii::$app->user->identity->getParentUid();
	    $globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid])->one();
	    if ($globalImageInfo===null){
	        $globalImageInfo=new GlobalImageInfo;
	        $globalImageInfo->puid=$puid;
	        $globalImageInfo->image_number=0;
	        $globalImageInfo->total_size=0;
	        if(!$globalImageInfo->save(false)){
	            \Yii::error("actionDelete : $puid create global image info record failed","file");
	        }
	    }else{
	        if($globalImageInfo->image_number - $deleteNum < 0){
	            $globalImageInfo->image_number = 0;
	        }else{
	            $globalImageInfo->image_number = $globalImageInfo->image_number - $deleteNum;
	        }
	    
	        if($globalImageInfo->total_size - $deleteSize < 0){
	            $globalImageInfo->total_size = 0;
	        }else{
	            $globalImageInfo->total_size = $globalImageInfo->total_size - $deleteSize;
	        }
	    
	        if(!$globalImageInfo->save(false)){
	            \Yii::error("actionDelete : $puid update global image info record failed","file");
	        }
	    }
	}
	
	/**
	 * 删除类别后 修改图片类别为未分类
	 * lgw 2017-07-12
	 */
	public static function DeleteImagesClassifica($delnameone){
		if(!empty($delnameone)){
			//先删除子节点
			$UtImageClassification=\eagle\modules\util\models\UtImageClassification::find()->select(['ID'])->where(['parentID'=>$delnameone])->asArray()->all();
			if(!empty($UtImageClassification)){
				foreach ($UtImageClassification as $UtImageClassificationone){
					self::DeleteImagesClassifica($UtImageClassificationone['ID']);
				}
			}
	
			//删除父节点
			\eagle\modules\util\models\UtImageClassification::deleteAll(['ID'=>$delnameone]);
			UserImage::updateAll(['classification_id'=>1],['classification_id'=>$delnameone]);
			 
		}
	}
	
	/**
	 * 图片类别下拉html
	 * lgw 2017-07-12
	 */
	public static function ImagesClassificaDropDownList($list,$index){
		$html='';
		 
		if(!empty($list)){
			foreach ($list as $move_arr_keys=>$move_arrone){
				$html.='<li style="padding-left:'.(13*$index).'px;"><a data-val="'.$move_arr_keys.'" href="#" onclick="util.imageLibrary.imageMove(this)">|--'.$move_arrone['name'].'</a></li>';
				if(!empty($move_arrone['data'])){
					$html_son=self::ImagesClassificaDropDownList($move_arrone['data'],$index+1);
					$html.=$html_son;
				}
			}
		}
		 
		return $html;
	}
	
	/**
	 * 图片类别view html
	 * lgw 2017-07-12
	 */
	public static function ImagesClassificaHtml2($parentID,$classification){
		$data=array(
				'html'=>'',
				'data'=>array(),
				'count'=>0,
		);
	
		$count=0;
		$imagesClassifica=\eagle\modules\util\models\UtImageClassification::find()->where(["parentID"=>$parentID])->orderBy(["ID" => SORT_ASC])->asArray()->all();
		$imagesClassList=$imagesClassifica;
		foreach ($imagesClassifica as $keys=>$imagesClassificaone){
			$num=UserImage::find()->where(['service'=>[1,2]])->andwhere(['classification_id'=>$imagesClassificaone['ID']])->count();
			$imagesClassList[$keys]['num']=$num;
			$count+=$num;
		}
	
		$html='';
		$droplist=array();
		if(!empty($imagesClassifica)){
			$html.='<ul data-cid="0" style="display: block;">';
			foreach ($imagesClassList as $keys=>$imagesClassListone){
				if(!empty($classification) && $classification==$imagesClassListone['ID'])
					$clickbgcss='bgColor';
				else
					$clickbgcss='';
				 
				$html.='<li groupid="'.$imagesClassListone['ID'].'" groupname="'.$imagesClassListone['name'].'"><div class="outDiv '.$clickbgcss.'">';
				$droplist[$imagesClassListone['ID']]['name']=$imagesClassListone['name'];
				$son_html=self::ImagesClassificaHtml2($imagesClassListone['ID'],$classification);
	
				if(!empty($son_html['html'])){
					$html.='<span class="gly glyphicon pull-left glyphicon-triangle-bottom" data-isleaf="open"></span>';
				}
				 
				if(!empty($son_html['count']))
					$num=$imagesClassListone['num']+$son_html['count'];
				else
					$num=$imagesClassListone['num'];
				 
				$html.='<div class="pull-left"><label><span class="chooseTreeName" onclick="null" data-groupid="'.$imagesClassListone['ID'].'">'.$imagesClassListone['name'].'<span class="num">'.(empty($num)?'':'('.$num.')').'</span></span><span class=""></span></label></div>';
	
				$html.='</div>';
				 
				$html.=$son_html['html'];
				$droplist[$imagesClassListone['ID']]['data']=$son_html['data'];
				$count+=$son_html['count'];
				$html.='</li>';
				 
			}
			$html.='</ul>';
		}
		 
		$data['html']=$html;
		$data['data']=$droplist;
		$data['count']=$count;
		return $data;
	}
	
	/**
	 * 图片类别设置 html
	 * lgw 2017-07-12
	 */
	public static function ImagesClassificaHtml($parentID,$level){
		$imagesClassifica=\eagle\modules\util\models\UtImageClassification::find()->where(["parentID"=>$parentID])->orderBy(["ID" => SORT_ASC])->asArray()->all();
		$data=array(
				'html'=>'',
				'title'=>'',
		);
		if(!empty($imagesClassifica)){
			$html='';
			$html.='<ul id="categoryTreeB_'.$level.'_ul_'.$parentID.'" class="level'.$level.'" style="display:block;margin-left:10px;margin-top:0px;">';
			foreach ($imagesClassifica as $keys=>$imagesClassificaone){
				$html_second=self::ImagesClassificaHtml($imagesClassificaone['ID'],$level+1);
				$html.='<li id="categoryTreeB_'.$imagesClassificaone['ID'].'" class="level'.$level.'" tabindex="'.$keys.'" >';
				if(!empty($html_second['html'])){
					$html.='<span id="categoryTreeB_'.$imagesClassificaone['ID'].'_switch" title="'.$html_second['title'].'" class="gly1 glyphicon glyphicon-triangle-bottom pull-left" data-isleaf="open"></span>';
				}
				 
				$html.='<a id="categoryTreeB_'.$imagesClassificaone['ID'].'_a" class="level'.$level.'" target="_blank" style="">
    						<span id="categoryTreeB_'.$imagesClassificaone['ID'].'_span"  style="">'.$imagesClassificaone['name'].'</span>';
				if($level<3)
					$html.='<span class="button add glyphicon glyphicon-plus displays" id="addBtn_categoryTreeB_'.$imagesClassificaone['ID'].'" title="添加分类" ></span>';
					
				if($imagesClassificaone['ID']!=1 || $imagesClassificaone['name']!='未分类'){
					$html.='<span class="button edit glyphicon glyphicon-edit displays" id="editBtn_categoryTreeB_'.$imagesClassificaone['ID'].'" title="更改分类名" ></span>';
					$html.='<span class="button remove glyphicon glyphicon-remove displays" id="removeBtn_categoryTreeB_'.$imagesClassificaone['ID'].'" title="删除分类" ></span>';
				}
	
				$html.='</a>';
	
				$html.=$html_second['html'];
	
				$html.='</li>';
			}
			$html.='</ul>';
			$data['html']=$html;
			$data['title']='categoryTreeB_'.$level.'_ul_'.$parentID;
		}
		 
		 
		return $data;
	}
	
	/**
	 * 更新图片库容量
	 * lgw 2017-08-14
	 * $library_size 更新的容量数 单位:M
	 * $puid puid
	 */
	public static function _resetGlobalImageInfo_library_size($library_size,$puid=null,$condition=null){
		$result=array(
				"code"=>0,
				"message"=>'',
		);
		
		$library_size_new=1024*1024*$library_size;
		if(empty($library_size_new)){
			$result['code']=1;
			$result['message']='设置的容量数为空或格式不对';
			return $result;
		}
		
// 		$puid=\Yii::$app->user->identity->getParentUid();
		$globalImageInfo=GlobalImageInfo::find();
		if(!empty($condition))
			$globalImageInfo=$globalImageInfo->where($condition);
		
		if(!empty($puid)){
			if(!empty($puid['puid'])){
				$puiddata=$puid['puid'];
				foreach ($puiddata as $puiddataone){
					$globalImageInfo_puid=$globalImageInfo->where(["puid"=>$puiddataone])->one();
					if ($globalImageInfo_puid === null){
						$globalImageInfo_puid = new GlobalImageInfo;
						$globalImageInfo_puid->puid = $puiddataone;
						$globalImageInfo_puid->image_number = 0;
						$globalImageInfo_puid->total_size = 0;
						$globalImageInfo_puid->library_size = $library_size_new;
						if(!$globalImageInfo_puid->save(false)){
							\Yii::error("resetlibrary_size : ".$puid." create global image info record failed","file");
							$result['code']=1;
							$result['message'].=$puid." create global image info record failed<br/>";
						}
					}
					else{
						$globalImageInfo_puid->library_size=$library_size_new;
							
						if(!$globalImageInfo_puid->save(false)){
							\Yii::error("resetlibrary_size : ".$puiddataone." update global image info record failed","file");
							$result['code']=1;
							$result['message'].=$puiddataone.'update global image info record failed<br/>';
						}
					}
				}
			}
		}
		else{
			$globalImageInfo_all=$globalImageInfo->all();
			if(empty($globalImageInfo_all)){
				$result['code']=1;
				$result['message']='找不到数据';
				return $result;
			}
				
			foreach ($globalImageInfo_all as $globalImageInfoone){
				$globalImageInfoone->library_size=$library_size_new;
					
				if(!$globalImageInfoone->save(false)){
					\Yii::error("resetlibrary_size : ".$globalImageInfoone->puid." update global image info record failed","file");
					$result['code']=1;
					$result['message'].=$globalImageInfoone->puid.'update global image info record failed<br/>';
				}
			}
		}
		
		return $result;		
	}
	
	
	/**
	 * get图片库数据
	 * lgw 2017-08-14
	 * $condition 条件
	 * $column 查找的字段
	 */
	public static function getGlobalImageInfo($condition=null,$column=null){
		$result=array(
				"code"=>0,
				"message"=>'',
				"data"=>'',
		);
	
// 		$puid=\Yii::$app->user->identity->getParentUid();
// 		$globalImageInfo=GlobalImageInfo::find()->where(["puid"=>$puid]);
		$globalImageInfo=GlobalImageInfo::find();
		if(!empty($condition))
			$globalImageInfo=$globalImageInfo->where($condition);
		
		if($column==null)
			$globalImageInfo=$globalImageInfo->asArray()->all();
		else
			$globalImageInfo=$globalImageInfo->select($column)->asArray()->all();

		if ($globalImageInfo===null){
			$result["code"]=1;
			$result["message"]="查找不到数据";
		}
		else{
			$result["data"]=$globalImageInfo;
		}
		
		return $result;
	
	}
	
}