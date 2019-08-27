<?php
namespace eagle\modules\util\helpers;
use yii;
class BarcodeHelper{
	const IMAGE_TYPE_PNG=1;
	const IMAGE_TYPE_JPG=2;
	const IMAGE_TYPE_GIF=3;
	
	static function generate($codetype,$thickness,$text='',$imagetype,$savefilename='',$fontname='Arial.ttf',$fontsize=12,$resolution=1){
		// 引用class文件夹对应的类
		$class_dir=dirname(__FILE__). '/../../../components/barcodegen/';
		require_once($class_dir.'class/BCGFontFile.php');
		require_once($class_dir.'class/BCGColor.php');
		require_once($class_dir.'class/BCGDrawing.php');
		if (!file_exists($class_dir . 'class/BCG' . $codetype . '.barcode.php')){
			throw new \Exception('编码不存在');
		}
		// 条形码的编码格式
		require_once($class_dir . 'class/BCG' . $codetype . '.barcode.php');
		// 加载字体大小
		if($fontname !== '0' && $fontname !== '-1' && intval($fontsize) >= 1){
			$font = new \BCGFontFile($class_dir.'font/'.$fontname, intval($fontsize));
		} else {
			$font = 0;//不需要显示条码下面的字符
		}
		//颜色条形码
		$color_black = new \BCGColor(0, 0, 0);
		$color_white = new \BCGColor(255, 255, 255);
		$codebar = 'BCG'.$codetype;
		$code_generated = new $codebar();
		$code_generated->setThickness($thickness);// 条形码的厚度
		$code_generated->setScale($resolution);
		$code_generated->setBackgroundColor($color_white);// 空白间隙颜色
		$code_generated->setForegroundColor($color_black);// 条形码颜色
		$code_generated->setFont($font);// 条形码需要的数据内容
		$code_generated->parse(utf8_decode($text));// 条形码需要的数据内容
		$drawing = new \BCGDrawing('', $color_white);
		$drawing->setDPI(300);
		$drawing->setBarcode($code_generated);
		$drawing->draw();
		if ($savefilename){
			$drawing->setFilename($savefilename);
		}else {
			if($imagetype === self::IMAGE_TYPE_PNG) {
				header('Content-Type: image/png');
			} elseif($imagetype === self::IMAGE_TYPE_JPG) {
				header('Content-Type: image/jpeg');
			} elseif($imagetype === self::IMAGE_TYPE_GIF) {
				header('Content-Type: image/gif');
			}
		}

		$drawing->finish($imagetype);
	}
}