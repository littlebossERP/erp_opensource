<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\catalog\helpers;
use eagle\modules\catalog\models\Photo;
use yii;
use yii\data\Pagination;
/**
 +------------------------------------------------------------------------------
 * 图片处理辅佐类
 +------------------------------------------------------------------------------
 * @category	Catalog
 * @package		Helper/product
 * @subpackage  Exception
 * @author		ouss <songshun.ou@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class PhotoHelper{
	
	public static $photoMaxSize = 5242880; // 5M
	
	private static $photoType = array ('TN' => 150, 'SM' => 400, 'LG' => 1000 );
	
	private static $photoMime = array ( 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
	
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
	 * 判断保存图片的路径是否存在，不存在则创建
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param dir			需要判断的路径字符串
	 +----------------------------------------------------------
	 * @return				路径是否存在
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function mkDirIfNotExist($dir) {
		if (! file_exists ( $dir )) {
			if (! mkdir ( $dir, 0777, true )) {
				throw new Exception ( 'create folder fail' );
			} else {
				return true;
			}
		}
		return false;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取图片resource
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片resource
	 * @param imgType		图片类型
	 * @param fileName		图片路径字符串
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function saveImageBySource($source, $imgType, $fileName) {
		switch ($imgType) {
			case "image/jpeg" :
				$reusult = imagejpeg ( $source, $fileName ); //jpeg file
				break;
			case "image/jpg" :
				$reusult = imagejpeg ( $source, $fileName ); //jpeg file
				break;
			case "image/pjpeg" :
				$reusult = imagejpeg ( $source, $fileName ); //jpeg file
				break;
			case "image/gif" :
				$reusult = imagegif ( $source, $fileName ); //gif file
				break;
			case "image/png" :
				$reusult = imagepng ( $source, $fileName ); //png file
				break;
			default :
				$reusult = false;
				break;
		}
	
		return $reusult;
	}
	
	/**
	 +----------------------------------------------------------
	 * 缩放图片
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片resource
	 * @param width			图片宽度
	 * @param height		图片高度
	 * @param reSize		缩放大小
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function reSizePhoto($source, $width, $height, $reSize) {
		if ($width > $reSize || $height > $reSize) {
			$newWidth = 0;
			$newHeight = 0;
			if ($width > $height) {
				$newWidth = $reSize;
				$newHeight = $reSize * $height / $width;
			} else {
				$newWidth = $reSize * $width / $height;
				$newHeight = $reSize;
			}
	
			$thumb = imagecreatetruecolor($newWidth, $newHeight);
	
			imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
	
			if(function_exists("imagecopyresampled")) {
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			} else {
				imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			}
	
			return $thumb;
		}
	
		return $source;
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存原图到数据库，并生成各种尺寸图片
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片名称
	 * @param path			图片保存路径
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function saveOriginalImg($tmpFilePath, $filePath, $urlPath, $priority, $fileMime, $sku = 'null') {
		$fileName = base64_encode(time().rand(0, 1000)) . '.' . self::$photoMime[$fileMime];
		$desFilePath = $filePath . 'OR_' . $fileName;
	
		if(file_exists($desFilePath) || move_uploaded_file($tmpFilePath, $desFilePath)) {
			$originaImg = new Photo();
			$originaImg->sku = $sku;
			$originaImg->priority = $priority;
			$originaImg->photo_scale = 'OR';
			$originaImg->file_name = 'OR_' . $fileName;
			$originaImg->photo_url = $urlPath. 'OR_' .$fileName;
			$originaImg->save();
			$result = self::createImgByOriginal($fileName, $filePath, $urlPath, $priority, $sku);
				
			$result['ids'][] = $originaImg->id;
			return $result;
		}
		return FALSE;
	}
	
	/**
	 +----------------------------------------------------------
	 * 生成各种尺寸的图片,并保存到数据库
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片名称
	 * @param path			图片保存路径
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function createImgByOriginal($fileName, $filePath, $urlPath, $priority, $sku) {
		$size = getimagesize ( $filePath. 'OR_' .$fileName );
		$source = self::createImageSource( $filePath. 'OR_' .$fileName, $size ["mime"]);
		$result = array('ids' => array(), 'tnUrl' => '');
		foreach (self::$photoType as $keySize => $value) {
			$photo = new Photo();
			$photo->sku = $sku;
			$photo->priority = $priority;
			$photo->photo_scale = $keySize;
			$photo->file_name = 'OR_' .$fileName;
			$photo->photo_url = $urlPath.'OR_' .$fileName;
			$thumb = self::reSizePhoto($source, $size[0], $size[1], $value);
			$tmpName = $keySize . '_' .$fileName;
			if (self::saveImageBySource($thumb, $size ["mime"], $filePath.$tmpName)) {
				$photo->file_name = $tmpName;
				$photo->photo_url= $urlPath.$tmpName;
			}
			$photo->save();
			$result['ids'][] = $photo->id;
			if ($keySize == 'TN') {
				$result['tnUrl'] = $photo->photo_url;
			}
		}
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取图片数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sku		图片所属产品SKU
	 * @param scale		图片SIZE缩放模式
	 * 					TN = 缩略图 （默认）  , width = 150px
	 *                  SM = Small Photo , width =  400px
	 *                  LG = Large Photo  , width = 800px
	 *                  OG = Original
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/03/28				初始化
	 +----------------------------------------------------------
	 **/
	public static function getPhotosBySku($sku, $scale = 'TN') {
		/*
		$criteria = new CDbCriteria();
		$criteria->compare('sku', $sku);
		$criteria->compare('photo_scale', $scale);
		$criteria->order = "priority asc";
		$criteria->select = 'photo_url';
		*/
		$photos = Photo::findAll(['sku'=>$sku , 'photo_scale'=>$scale]);
	
		$photoUrlArray = array();
		foreach ($photos as $item) {
			$photoUrlArray[] = $item->photo_url;
		}
		return $photoUrlArray;
	}
	
	public static function savePhotoByUrl($sku, $primary, $others, $scale = 'OR') {
		
		Photo::deleteAll(['sku'=> $sku]);
		if ($primary != '') {
			$photo = new Photo();
			$photo->sku = $sku;
			$photo->priority = 0;
			$photo->photo_scale = $scale;
			$photo->photo_url = $primary;
			$photo->file_name = '';
			$photo->save();
		}
		$startIx = 1;
		foreach ($others as $item) {
			$photo = new Photo();
			$photo->sku = $sku;
			$photo->priority = $startIx;
			$photo->photo_scale = $scale;
			$photo->photo_url = $item;
			$photo->file_name = '';
			$photo->save();
			$startIx++;
		}
	}
	/**
	 * 重置产品PhotoPrimary，对其他图片不作删除处理，但对priority顺序作处理。
	 * @param  $sku
	 * @param  $primary
	 */
	public static function resetPhotoPrimary($sku, $primary, $scale = 'OR') {
		if ($primary=='') return;	
		$query = Photo::find()->where(['sku'=>$sku])->orderBy("priority ASC")->asArray()->all();
		$photos = $query;
		$primary_is_new = true;//primary是否已在sku关联的图片中
		$primary_now_priority = 0;//如果primary已经在关联图片中，它原本的priority
		foreach ($photos as $priority=>$photo){
			if($primary==$photo['photo_url']){
				$primary_is_new = false;
				$primary_now_priority = $priority;
				unset($photos[$priority]);
				break;
			}
		}
		Photo::deleteAll(['sku'=> $sku]);//删除所有图片
		if($primary_is_new){
			//当primary是新图片时，设置priority 0 ，即primary
			$photo = new Photo();
			$photo->sku = $sku;
			$photo->priority = 0;
			$photo->photo_scale = $scale;
			$photo->photo_url = $primary;
			$photo->file_name = '';
			$photo->save();
			//重新记录旧图片
			$index = 1;
			foreach ($photos as $p){
				$photo = new Photo();
				$photo->sku = $sku;
				$photo->priority = $index;
				$photo->photo_scale = $scale;
				$photo->photo_url = $p['photo_url'];
				$photo->file_name = '';
				$photo->save();
				$index ++;
			}
		}else{
			//当primary是旧图片时
			$photo = new Photo();
			$photo->sku = $sku;
			$photo->priority = 0;
			$photo->photo_scale = $scale;
			$photo->photo_url = $primary;
			$photo->file_name = '';
			$photo->save();
			//重新记录primary以外的旧图片，
			foreach ($photos as $p){
				$photo = new Photo();
				$photo->sku = $sku;
				//原priority在primary前面的，priority+1
				if($p['priority'] < $primary_now_priority)
					$index=$p['priority']+1;
				else 
					$index = $p['priority'];
				$photo->priority = $index;
				$photo->photo_scale = $scale;
				$photo->photo_url = $p['photo_url'];
				$photo->file_name = '';
				$photo->save();
			}
		}
		

	}
}//end of PhotoHelper