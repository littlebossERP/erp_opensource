<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\SysShippingMethod;
use eagle\models\CarrierTemplateHighcopy;
use eagle\models\OdOrderShipped;
use eagle\models\SysCountry;
use Qiniu\json_decode;
use eagle\modules\carrier\apihelpers\PrintPdfHelper;

class LGS_PrintPdfHelper{
		
	
/**
	 * @param string 
	 */
	//LEX-ID 快递（印尼）
	public static function getLGSLEXID($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//跟踪号
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(82, 0, 2+$tmpX, 2.5+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字ID4
		$pdf->SetFont('msyhbd', 'B', 12);
		$pdf->writeHTMLCell(10, 0, 85+$tmpX, 3+$tmpY, 'ID4', 0, 1, 0, true, '', true);

		//文字date
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(31, 0, 65+$tmpX, 20+$tmpY, 'Date:', 0, 1, 0, true, '', true);
		
		//字体类型（如helvetica(Helvetica)黑体，times (Times-Roman)罗马字体）、风格（B粗体，I斜体，underline下划线等）、字体大小
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		//分别为宽度，高 度，x坐标，y坐标，内容，是否右边框，与下一个单元格的相对为位置，是否填充背景色，是否重置高度，文本对齐方式，是否自动换行
		$pdf->writeHTMLCell(31, 0, 75+$tmpX, 20+$tmpY, $itemListDetailInfo['lists']['PRINT_TIME3'], 0, 1, 0, true, '', true);

		//文字From:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 20+$tmpY, 'From:', 0, 1, 0, true, '', true);
		
		//发件人名称
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(54, 0, 13+$tmpX, 20+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//发件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$address=$senderInfo['SENDER_ADDRESS'].(empty($senderInfo['SENDER_AREA'])?'':' '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_CITY'])?'':' '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_PROVINCE'])?'':' '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_COUNTRY_EN'])?'':' '.$senderInfo['SENDER_COUNTRY_EN']).(empty($senderInfo['SENDER_ZIPCODE'])?'':' '.$senderInfo['SENDER_ZIPCODE']);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 24+$tmpY, $address, 0, 1, 0, true, '', true);
				
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128C', 2+$tmpX, 4+$tmpY, '82', 16, 0.55, $style, 'N');
		
// 		$pdf->Line(2+$tmpX, 20.5+$tmpY, 98+$tmpX, 20.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 31+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//发件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 31+$tmpY, !empty($senderInfo['SENDER_TELEPHONE'])?$senderInfo['SENDER_TELEPHONE']:$senderInfo['SENDER_MOBILE'], 0, 1, 0, true, '', true);
		
		//发件人电话下面的线
		$pdf->Line(2+$tmpX, 35+$tmpY, 98+$tmpX, 35+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人名称
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 35+$tmpY, 'To:'.$receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':' '.$receiverInfo['RECEIVER_ZIPCODE']).(empty($receiverInfo['RECEIVER_EMAIL'])?'':' '.$receiverInfo['RECEIVER_EMAIL']).(empty($receiverInfo['RECEIVER_COMPANY'])?'':' '.$receiverInfo['RECEIVER_COMPANY']);
		if(strlen($address)>200)
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
		else if(strlen($address)>150)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 39+$tmpY, $address, 0, 1, 0, true, '', true);

		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 49+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 49+$tmpY, !empty($receiverInfo['RECEIVER_TELEPHONE'])?$receiverInfo['RECEIVER_TELEPHONE']:$receiverInfo['RECEIVER_MOBILE'], 0, 1, 0, true, '', true);
		
		//收件人电话下面的线
		$pdf->Line(2+$tmpX, 53+$tmpY, 98+$tmpX, 53+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Description
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(50, 0, 2+$tmpX, 53+$tmpY, 'Description:', 0, 1, 0, true, '', true);
		
		//文字#
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY, '#', 0, 1, 0, true, '', true);
		
		//文字Seller SKU
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY, 'Seller SKU', 0, 1, 0, true, '', true);
		
		//文字quantity
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(16, 0, 82+$tmpX, 57+$tmpY, 'quantity', 0, 1, 0, true, '', true);
		
		//商品信息
		//超过2个的只显示2个
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
// 			if($count>2)
// 				break;
			
			//序号
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $key+1, 0, 1, 0, true, '', true);
			
			//sku
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['SKU'], 0, 1, 0, true, '', true);
			
			//数量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(16, 0, 83+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['QUANTITY'], 0, 1, 0, true, 'C', true);
			
			$count++;
		}
		
		//文字Metode Pembayaran
		$arr=json_decode($order['addi_info']);
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(41, 0, 2+$tmpX, 74.5+$tmpY, 'Metode Pembayaran:', 0, 1, 0, true, '', true);
		
		//文字Payment method
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(56, 0, 41+$tmpX, 74.5+$tmpY, empty($arr->lgs_related->PaymentMethod)?'':$arr->lgs_related->PaymentMethod, 0, 1, 0, true, '', true);
		
		//包装号上面的线
		$pdf->Line(2+$tmpX, 78.5+$tmpY, 98+$tmpX, 78.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Package number:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 78.5+$tmpY, 'Package number:',0, 1, 0, true, '', true);
		
		//包装id
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(59, 0, 34+$tmpX, 78.5+$tmpY, $trackingInfo['PackageId'],0, 1, 0, true, '', true);
		
		//包装号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['PackageId'], 'c128', 2+$tmpX, 81+$tmpY, '96', 14.9, 0.3, $style, 'N');
		
		//包装号(条码下面)
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 95+$tmpY, $trackingInfo['PackageId'], 0, 1, 0, true, 'C', true);
	}

	//TIKI-ID 快递（印尼）
	public static function getLGSTikiID($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//跟踪号
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(82, 0, 2+$tmpX, 2.5+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字ID3
		$pdf->SetFont('msyhbd', 'B', 12);
		$pdf->writeHTMLCell(10, 0, 85+$tmpX, 3+$tmpY, 'ID3', 0, 1, 0, true, '', true);

		//文字date
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(31, 0, 65+$tmpX, 20+$tmpY, 'Date:', 0, 1, 0, true, '', true);
		
		//字体类型（如helvetica(Helvetica)黑体，times (Times-Roman)罗马字体）、风格（B粗体，I斜体，underline下划线等）、字体大小
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		//分别为宽度，高 度，x坐标，y坐标，内容，是否右边框，与下一个单元格的相对为位置，是否填充背景色，是否重置高度，文本对齐方式，是否自动换行
		$pdf->writeHTMLCell(31, 0, 75+$tmpX, 20+$tmpY, $itemListDetailInfo['lists']['PRINT_TIME3'], 0, 1, 0, true, '', true);

		//文字From:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 20+$tmpY, 'From:', 0, 1, 0, true, '', true);
		
		//发件人名称
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(54, 0, 13+$tmpX, 20+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//发件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$address=$senderInfo['SENDER_ADDRESS'].(empty($senderInfo['SENDER_AREA'])?'':' '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_CITY'])?'':' '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_PROVINCE'])?'':' '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_COUNTRY_EN'])?'':' '.$senderInfo['SENDER_COUNTRY_EN']).(empty($senderInfo['SENDER_ZIPCODE'])?'':' '.$senderInfo['SENDER_ZIPCODE']);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 24+$tmpY, $address, 0, 1, 0, true, '', true);
				
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' =>0
		);
		
		if(is_int($trackingInfo['tracking_number'])){
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128C', 2+$tmpX, 4+$tmpY, '82', 16, 0.55, $style, 'N');
		}else{
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 2+$tmpX, 4+$tmpY, '82', 16, 0.55, $style, 'N');
		}
		
// 		$pdf->Line(2+$tmpX, 20.5+$tmpY, 98+$tmpX, 20.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 31+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//发件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 31+$tmpY, !empty($senderInfo['SENDER_TELEPHONE'])?$senderInfo['SENDER_TELEPHONE']:$senderInfo['SENDER_MOBILE'], 0, 1, 0, true, '', true);
		
		//发件人电话下面的线
		$pdf->Line(2+$tmpX, 35+$tmpY, 98+$tmpX, 35+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人名称
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 35+$tmpY, 'To:'.$receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':' '.$receiverInfo['RECEIVER_ZIPCODE']).(empty($receiverInfo['RECEIVER_EMAIL'])?'':' '.$receiverInfo['RECEIVER_EMAIL']).(empty($receiverInfo['RECEIVER_COMPANY'])?'':' '.$receiverInfo['RECEIVER_COMPANY']);
		if(strlen($address)>200)
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
		else if(strlen($address)>150)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 39+$tmpY, $address, 0, 1, 0, true, '', true);

		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 49+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 49+$tmpY, !empty($receiverInfo['RECEIVER_TELEPHONE'])?$receiverInfo['RECEIVER_TELEPHONE']:$receiverInfo['RECEIVER_MOBILE'], 0, 1, 0, true, '', true);
		
		//收件人电话下面的线
		$pdf->Line(2+$tmpX, 53+$tmpY, 98+$tmpX, 53+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Description
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(50, 0, 2+$tmpX, 53+$tmpY, 'Description:', 0, 1, 0, true, '', true);
		
		//文字#
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY, '#', 0, 1, 0, true, '', true);
		
		//文字Seller SKU
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY, 'Seller SKU', 0, 1, 0, true, '', true);
		
		//文字quantity
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(16, 0, 82+$tmpX, 57+$tmpY, 'quantity', 0, 1, 0, true, '', true);
		
		//商品信息
		//超过2个的只显示2个
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
// 			if($count>2)
// 				break;
			
			//序号
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $key+1, 0, 1, 0, true, '', true);
			
			//sku
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['SKU'], 0, 1, 0, true, '', true);
			
			//数量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(16, 0, 83+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['QUANTITY'], 0, 1, 0, true, 'C', true);
			
			$count++;
		}
		
		//文字Metode Pembayaran
		$arr=json_decode($order['addi_info']);
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(41, 0, 2+$tmpX, 74.5+$tmpY, 'Metode Pembayaran:', 0, 1, 0, true, '', true);
		
		//文字Payment method
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(56, 0, 41+$tmpX, 74.5+$tmpY, empty($arr->lgs_related->PaymentMethod)?'':$arr->lgs_related->PaymentMethod, 0, 1, 0, true, '', true);
		
		//包装号上面的线
		$pdf->Line(2+$tmpX, 78.5+$tmpY, 98+$tmpX, 78.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Package number:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 78.5+$tmpY, 'Package number:',0, 1, 0, true, '', true);
		
		//包装id
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(59, 0, 34+$tmpX, 78.5+$tmpY, $trackingInfo['PackageId'],0, 1, 0, true, '', true);
		
		//包装号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['PackageId'], 'c128', 2+$tmpX, 81+$tmpY, '96', 14.9, 0.3, $style, 'N');
		
		//包装号(条码下面)
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 95+$tmpY, $trackingInfo['PackageId'], 0, 1, 0, true, 'C', true);
		
// 		print_r($order);die;
	}

	//MY 快递（马来）
	public static function getLGSPoslaju($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距

		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//头部图片
		$pdf->writeHTMLCell(40, 0, 2+$tmpX, 5+$tmpY, '<img src="/images/customprint/labelimg/lzd_AS-Poslaju_03.png"  width="100" >', 0, 1, 0, true, '', true);
		
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'c128', 40+$tmpX, 6+$tmpY, '58', 17, 0.4, $style, 'N');
		
		//运输服务
		$pdf->SetFont('msyhbd', 'B', 12);
		$pdf->writeHTMLCell(28, 0, 70+$tmpX, 3+$tmpY, $shippingService['shipping_method_code'], 0, 1, 0, true, '', true);

		//文字From
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 20+$tmpY, 'From:', 0, 1, 0, true, '', true);
		
		//发件人名称
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(65, 0, 14+$tmpX, 20+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//发件人地址
		$pdf->SetFont('helvetica', '', 8);
		$address=$senderInfo['SENDER_ADDRESS'].(empty($senderInfo['SENDER_AREA'])?'':'<br>'.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_CITY'])?'':' '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_PROVINCE'])?'':' '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_COUNTRY_EN'])?'':'<br>'.$senderInfo['SENDER_COUNTRY_EN']).(empty($senderInfo['SENDER_ZIPCODE'])?'':'<br>'.$senderInfo['SENDER_ZIPCODE']).(empty($senderInfo['SENDER_COMPANY_NAME'])?'':'<br>'.$senderInfo['SENDER_COMPANY_NAME']).'<br><span style="font-weight:Bold;">MALAYSIA</span> Tel:'.$senderInfo['SENDER_MOBILE'];
		$pdf->writeHTMLCell(66, 0, 2+$tmpX, 24+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//文字POS LAJU ACC # 8800400431
		$pdf->SetFont('msyhbd', 'B', 11);
		$pdf->writeHTMLCell(36, 0, 62+$tmpX, 24+$tmpY, 'POS LAJU ACC # 8800400431', 0, 1, 0, true, 'C', true);
				
		//收件人名称
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 46+$tmpY, 'To:'.$receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件地址
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$address=$receiverInfo['RECEIVER_ADDRESS'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':' '.$receiverInfo['RECEIVER_ZIPCODE']).(empty($receiverInfo['RECEIVER_COMPANY'])?'':' '.$receiverInfo['RECEIVER_COMPANY']);
		$pdf->writeHTMLCell(75, 0, 2+$tmpX, 50+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//文字tel
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(70, 0, 2+$tmpX, 66.5+$tmpY, 'Tel:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(69, 0, 10+$tmpX, 66.5+$tmpY, $receiverInfo['RECEIVER_MOBILE'].' / '.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
			
		//文字MY
		$pdf->SetFont('msyhbd', '', 18);
		$pdf->writeHTMLCell(13, 0, 80+$tmpX, 57+$tmpY, 'MY', 1, 1, 0, true, '', true);
		
		//文字Item Information
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(30, 0, 2+$tmpX, 70+$tmpY, 'Item Information', 0, 1, 0, true, '', true);
		
		//文字Transaction Ref
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(28, 0, 33+$tmpX, 70+$tmpY, 'Transaction Ref:', 0, 1, 0, true, '', true);
		
		//平台订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(38, 0, 62+$tmpX, 70+$tmpY, $order['order_source_order_id'], 0, 1, 0, true, '', true);

		//文字Date
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(11, 0, 2+$tmpX, 73+$tmpY, 'Date:', 0, 1, 0, true, '', true);
		
		//订单创建日期
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(22, 0, 11+$tmpX, 73+$tmpY, $itemListDetailInfo['lists']['PRINT_TIME3'], 0, 1, 0, true, '', true);
		
		//文字Product
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(66, 0, 33+$tmpX, 73+$tmpY, 'Product:COURIER CHARGES-DOMESTIC', 0, 1, 0, true, '', true);
		
		//总重量
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(30, 0, 2+$tmpX, 76+$tmpY, 'Weight:'.$itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//文字Type: MERCHANDISE
		$pdf->SetFont('msyhbd', 'B', 9);
		$pdf->writeHTMLCell(50, 0, 33+$tmpX, 76+$tmpY, 'Type: MERCHANDISE', 0, 1, 0, true, '', true);
		
		//底部大段文字Please use the number above 。。。。
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 83+$tmpY, 'Please use the number above to track the shipment status through Customer Service Center (Posline) 1-300-300-300 or Pos Malaysia web at www.pos.com.my Note: Liability of PosLaju for any delay, damage or lost be limited to and subject to the terms and conditions as stated behind the consignment note (PL1A)', 0, 1, 0, true, '', true);
// 				print_r($itemListDetailInfo);die;
	}

	//TH 快递（泰国）
	public static function getLGSTH1($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		//跟踪号条码的样式
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//平台订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(30, 0, 1.5+$tmpX, 5+$tmpY, 'Order No:<br>'.$order['order_source_order_id'], 0, 1, 0, true, 'C', true);
		
		//订单号下边的线
		$pdf->Line(2+$tmpX, 19+$tmpY, 98+$tmpX, 19+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//订单号右边的线
		$pdf->Line(30+$tmpX, 2+$tmpY, 30+$tmpX, 19+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字EMS Tracking No
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(58, 0, 30+$tmpX, 2+$tmpY, 'EMS Tracking No:', 0, 1, 0, true, 'C', true);

		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',   
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'c128', 30+$tmpX, 4+$tmpY, '58', 12.5, 0.3, $style, 'N');
		
		//文字跟踪号
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(58, 0, 30+$tmpX, 15+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字TH1
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(10, 0, 88+$tmpX, 8+$tmpY, 'TH1', 0, 1, 0, true, '', true);
		
		//跟踪号右面的线
		$pdf->Line(88+$tmpX, 2+$tmpY, 88+$tmpX, 19+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//文字Package No:
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 19+$tmpY, 'Package No:', 0, 1, 0, true, 'C', true);
		
		//包装号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['PackageId'], 'c128', 2+$tmpX, 22+$tmpY, '96', 15, 0.3, $style, 'N');
		
		//文字包装号
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 36+$tmpY, $trackingInfo['PackageId'],0, 1, 0, true, 'C', true);
		
		//包装号下面的线
		$pdf->Line(2+$tmpX, 40+$tmpY, 98+$tmpX, 40+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//店铺名右边的线
		$pdf->Line(30+$tmpX, 40+$tmpY, 30+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字店铺名
		$pdf->SetFont('angsau', '', 14);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 40+$tmpY, 'ชื่อบริษัท:', 0, 1, 0, true, '', true);
		
		//店铺名
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 46+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//文字
		$pdf->SetFont('angsau', '', 13);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 53+$tmpY, 'กรณีนำจ่ายไม่ได้กรุณาส่งคืน ศป.', 0, 1, 0, true, '', true);
		
		//文字EMS 10020
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 63+$tmpY, 'EMS 10020', 0, 1, 0, true, '', true);
		
		//lazada图片
		$pdf->writeHTMLCell(28, 12, 2+$tmpX, 66.5+$tmpY, '<img src="/images/customprint/labelimg/LGS TH logo_02.jpg"  width="100" >', 0, 1, 0, true, '', true);

		//文字LAZADA.CO.TH
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 79+$tmpY, 'LAZADA.CO.TH', 0, 1, 0, true, 'C', true);
		
		//文字Cross-Border Item
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 84+$tmpY, 'Cross-Border Item', 0, 1, 0, true, 'C', true);
		
		//地址信息下面的线
		$pdf->Line(2+$tmpX, 92+$tmpY, 98+$tmpX, 92+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字
		$pdf->SetFont('angsau', '', 12);
		$pdf->writeHTMLCell(28, 0, 2+$tmpX, 92.5+$tmpY, 'ไม่เก็บเงินค่าสินค้า', 0, 1, 0, true, 'C', true);
		
		//邮编
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(67.5, 0, 30+$tmpX, 93+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, 'C', true);
		
		//文字
		$pdf->SetFont('angsau', '', 13);
		$pdf->writeHTMLCell(10, 0, 30+$tmpX, 40+$tmpY, 'ผู้รับ:', 0, 1, 0, true, '', true);
		
		//收件人名称
		$pdf->SetFont('angsau', '', 13);
		$pdf->writeHTMLCell(58, 0, 39+$tmpX, 40+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//文字
		$pdf->SetFont('angsau', '', 13);
		$pdf->writeHTMLCell(40, 0, 30+$tmpX, 47+$tmpY, 'ที่อยู่ผู้รับ:', 0, 1, 0, true, '', true);
		
		//收件人地址
		$pdf->SetFont('angsau', '', 13);
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':'<br>'.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':'<br>'.$receiverInfo['RECEIVER_ZIPCODE']).(empty($receiverInfo['RECEIVER_COMPANY'])?'':'<br>'.$receiverInfo['RECEIVER_COMPANY']);
		$pdf->writeHTMLCell(67.5, 0, 30+$tmpX, 51+$tmpY, $address, 0, 1, 0, true, '', true);

		//文字tel
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(9, 0, 30+$tmpX, 87+$tmpY, 'Tel:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(67, 0, 37+$tmpX, 87+$tmpY, !empty($receiverInfo['RECEIVER_TELEPHONE'])?$receiverInfo['RECEIVER_TELEPHONE']:$receiverInfo['RECEIVER_MOBILE'], 0, 1, 0, true, '', true);
	}

	//PH 快递（菲律宾）
	public static function getLGSPH1($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//顶部左方图片
		$pdf->writeHTMLCell(18,18, 2+$tmpX, 5+$tmpY, '<img src="/images/customprint/labelimg/LGS PH logo.png"  width="100" >', 0, 1, 0, true, '', true);
				
		//跟踪号
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(62, 0, 21+$tmpX, 2.5+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字Parcel<br>Green
		$pdf->SetFont('msyhbd', 'B', 12);
		$pdf->writeHTMLCell(15, 0, 83+$tmpX, 5+$tmpY, 'Parcel<br>Green', 0, 1, 0, true, '', true);
		
		//文字date
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(31, 0, 65+$tmpX, 20+$tmpY, 'Date:', 0, 1, 0, true, '', true);
		
		//字体类型（如helvetica(Helvetica)黑体，times (Times-Roman)罗马字体）、风格（B粗体，I斜体，underline下划线等）、字体大小
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		//分别为宽度，高 度，x坐标，y坐标，内容，是否右边框，与下一个单元格的相对为位置，是否填充背景色，是否重置高度，文本对齐方式，是否自动换行
		$pdf->writeHTMLCell(31, 0, 75+$tmpX, 20+$tmpY, $itemListDetailInfo['lists']['PRINT_TIME3'], 0, 1, 0, true, '', true);

		//文字From:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 20+$tmpY, 'From:', 0, 1, 0, true, '', true);
		
		//发件人名称
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(54, 0, 13+$tmpX, 20+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//发件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$address=$senderInfo['SENDER_ADDRESS'].(empty($senderInfo['SENDER_AREA'])?'':' '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_CITY'])?'':' '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_PROVINCE'])?'':' '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_COUNTRY_EN'])?'':' '.$senderInfo['SENDER_COUNTRY_EN']).(empty($senderInfo['SENDER_ZIPCODE'])?'':' '.$senderInfo['SENDER_ZIPCODE']);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 24+$tmpY, $address, 0, 1, 0, true, '', true);
				
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128C', 21+$tmpX, 4+$tmpY, '61', 16.5, 0.4, $style, 'N');
		
// 		$pdf->Line(2+$tmpX, 20.5+$tmpY, 98+$tmpX, 20.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 31+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//发件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 31+$tmpY, !empty($senderInfo['SENDER_TELEPHONE'])?$senderInfo['SENDER_TELEPHONE']:$senderInfo['SENDER_MOBILE'], 0, 1, 0, true, '', true);
		
		//发件人电话下面的线
		$pdf->Line(2+$tmpX, 35+$tmpY, 98+$tmpX, 35+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人名称
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 35+$tmpY, 'To:'.$receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].'<br>'.(empty($receiverInfo['RECEIVER_AREA'])?'':''.$receiverInfo['RECEIVER_AREA'].' ').(empty($receiverInfo['RECEIVER_CITY'])?'':''.$receiverInfo['RECEIVER_CITY'].' ').(empty($receiverInfo['RECEIVER_PROVINCE'])?'':''.$receiverInfo['RECEIVER_PROVINCE']).'<br>'.(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':''.$receiverInfo['RECEIVER_COUNTRY_EN'].' ').(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':''.$receiverInfo['RECEIVER_ZIPCODE'].' ').(empty($receiverInfo['RECEIVER_EMAIL'])?'':''.$receiverInfo['RECEIVER_EMAIL'].' ').(empty($receiverInfo['RECEIVER_COMPANY'])?'':''.$receiverInfo['RECEIVER_COMPANY'].' ');
		if(strlen($address)>200)
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
		else if(strlen($address)>150)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 38+$tmpY, $address, 0, 1, 0, true, '', true);

		//文字Phone Number
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 49+$tmpY, 'Phone Number:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 31+$tmpX, 49+$tmpY, !empty($receiverInfo['RECEIVER_TELEPHONE'])?$receiverInfo['RECEIVER_TELEPHONE']:$receiverInfo['RECEIVER_MOBILE'], 0, 1, 0, true, '', true);
		
		//收件人电话下面的线
		$pdf->Line(2+$tmpX, 53+$tmpY, 98+$tmpX, 53+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Description
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(50, 0, 2+$tmpX, 53+$tmpY, 'Description:', 0, 1, 0, true, '', true);
		
		//文字#
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY, '#', 0, 1, 0, true, '', true);
		
		//文字Seller SKU
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY, 'Seller SKU', 0, 1, 0, true, '', true);
		
		//文字quantity
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(16, 0, 82+$tmpX, 57+$tmpY, 'quantity', 0, 1, 0, true, '', true);
		
		//商品信息
		//超过2个的只显示2个
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
// 			if($count>2)
// 				break;
			
			//序号
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(6, 0, 2+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $key+1, 0, 1, 0, true, '', true);
			
			//sku
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(75, 0, 7+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['SKU'], 0, 1, 0, true, '', true);
			
			//数量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(16, 0, 83+$tmpX, 57+$tmpY+3.5*($key+1)+2.5*$key, $vitems['QUANTITY'], 0, 1, 0, true, 'C', true);
			
			$count++;
		}
		
		//文字Metode Pembayaran
		$arr=json_decode($order['addi_info']);
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(41, 0, 2+$tmpX, 74.5+$tmpY, 'Metode Pembayaran:', 0, 1, 0, true, '', true);
		
		//文字Payment method
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(56, 0, 41+$tmpX, 74.5+$tmpY, empty($arr->lgs_related->PaymentMethod)?'':$arr->lgs_related->PaymentMethod, 0, 1, 0, true, '', true);
		
		//包装号上面的线
		$pdf->Line(2+$tmpX, 78.5+$tmpY, 98+$tmpX, 78.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字Package number:
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 78.5+$tmpY, 'Package number:',0, 1, 0, true, '', true);
		
		//包装id
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(59, 0, 34+$tmpX, 78.5+$tmpY, $trackingInfo['PackageId'],0, 1, 0, true, '', true);
		
		//包装号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['PackageId'], 'c128', 2+$tmpX, 81+$tmpY, '96', 18, 0.3, $style, 'N');
				
// 		print_r($itemListDetailInfo);die;
	}

	//SG3 快递 (新加坡)
	public static function getLGSSG3($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
// 		$pdf->setCellHeightRatio(1); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//顶部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(56, 0, 2+$tmpX, 3+$tmpY, 'If undelivered, please return to <br>20 TohGuan Road<br>#08-00,CJ Korea Express Building,<br>Singapore 608839', 0, 1, 0, true, '', true);

		//文字SG3
		$pdf->SetFont('msyhbd', 'B', 10.5);
		$pdf->writeHTMLCell(10, 0, 88+$tmpX, 2.5+$tmpY, 'SG3', 0, 1, 0, true, '', true);
		
		//sg3下面的图片
		$pdf->writeHTMLCell(40,0, 58+$tmpX, 7+$tmpY, '<img src="/images/customprint/labelimg/permit_sga.png" >', 0, 1, 0, true, '', true);

		//文字PP
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(8, 0, 59+$tmpX, 13+$tmpY, 'P P', 0, 1, 0, true, '', true);
		
		//文字4321
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(13, 0, 66+$tmpX, 13.5+$tmpY, '4 3 2 1', 0, 1, 0, true, '', true);
		
		//文字Registered Mail
		$pdf->SetFont('msyhbd', 'B', 12);
		$pdf->writeHTMLCell(38, 0, 59+$tmpX, 27+$tmpY, 'Registered Mail', 1, 1, 0, true, 'C', true);
		
		//文字Deliver To:
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(57, 0, 2+$tmpX, 23+$tmpY, 'Deliver To: ', 0, 1, 0, true, '', true);
		
		//收件人名称
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		if(strlen($receiverInfo['RECEIVER_NAME'])>20)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(57, 0, 21+$tmpX, 23+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$tmp=(empty($receiverInfo['RECEIVER_CITY'])?'-<br>':'-<br>'.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':'-'.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':'-'.$receiverInfo['RECEIVER_ZIPCODE']);
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].substr($tmp, 1).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'<br>':'<br>'.$receiverInfo['RECEIVER_ZIPCODE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' , '.$receiverInfo['RECEIVER_COUNTRY_EN']);
		$pdf->writeHTMLCell(54, 0, 2+$tmpX, 29+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//收件人邮编
		$pdf->SetFont($otherParams['pdfFont'], '', 13);
		$pdf->writeHTMLCell(38, 0, 59+$tmpX, 37+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//平台来源单号
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 70+$tmpY, $order['customer_number'], 0, 1, 0, true, '', true);
		
		//文字RX
		$pdf->SetFont($otherParams['pdfFont'], '', 16);
		$pdf->writeHTMLCell(10, 0, 4+$tmpX, 83+$tmpY, 'RX', 0, 1, 0, true, '', true);
		
		//跟踪号
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(83, 0, 15+$tmpX, 77+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false,//array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'c39', 15+$tmpX, 80+$tmpY, '83', 14.5, 0.6, $style, 'N');
		
		//文字跟踪号
		$pdf->SetFont('msyhbd', 'B', 10);
		$pdf->writeHTMLCell(83, 0, 15+$tmpX, 93+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
// 		print_r($order);die;
	}

	//LGS-FM40/LGS-FM41/LGS-FM01
	public static function getLGSFM($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		// 		$pdf->setCellHeightRatio(1); //字体行距
		if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='TH'){
			$Params=$otherParams['pdfFont'];
			$Params_buyer='angsau';
		}
		else if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='VN'){
			$Params=$otherParams['pdfFont'];
			$Params_buyer='calibri';
		}
		else{
			$Params=$otherParams['pdfFont'];
			$Params_buyer=$otherParams['pdfFont'];
		}
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//图片
		$pdf->writeHTMLCell(15,0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/LGS Lazada logo.png" >', 0, 1, 0, true, '', true);
		
		//图片下边线条
		$pdf->Line(2+$tmpX, 8.5+$tmpY, 98+$tmpX, 8.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		
		//文字:Lazada Firstmile Tracking Number
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 8+$tmpY, 'Lazada Firstmile Tracking Number', 0, 1, 0, true, 'C', true);
		
		//跟踪号条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false,//array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 2+$tmpX, 11+$tmpY, '96', 15, 0.6, $style, 'N');
		
		//跟踪号
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 24+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//跟踪号下边线条
		$pdf->Line(2+$tmpX, 28+$tmpY, 98+$tmpX, 28+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人名称To:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(8, 0, 2+$tmpX, 28+$tmpY, 'To:', 0, 1, 0, true, '', true);
		
		//收件人名称:
		if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='TH')
			$size=13;
		else 
			$size=9;
		$pdf->SetFont($Params_buyer, '', $size);
		$pdf->writeHTMLCell(85, 0, 10+$tmpX, 28+$tmpY, $receiverInfo['RECEIVER_NAME'].' - '.$receiverInfo['RECEIVER_COUNTRY_EN'], 0, 1, 0, true, '', true);

		//运输服务service:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(16, 0, 2+$tmpX, 32+$tmpY, 'Service:', 0, 1, 0, true, '', true);
		
		
		$lazada_Firstmile = 'Lazada Firstmile Fulfilment';
		if($shippingService->shipping_method_code=='LGS-FM01'){
			$lazada_Firstmile = 'Cainiao Hansel-Free Global Delivery';
		}
		
		//运输服务
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(70, 0, 18+$tmpX, 32+$tmpY, $lazada_Firstmile, 0, 1, 0, true, '', true);   //GainiaoHansel-Free Global Delivery
		
		//seller name:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(24, 0, 2+$tmpX, 38+$tmpY, 'Seller Name：', 0, 1, 0, true, '', true);
		
		//卖家店铺名:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(70, 0, 26+$tmpX, 38+$tmpY, $itemListDetailInfo['lists']['SHOP_LGS_NAME'], 0, 1, 0, true, '', true);
		
		//seller address:
		if(strlen($senderInfo['SENDER_ADDRESS'])>130)
			$size=6;
		else if(strlen($senderInfo['SENDER_ADDRESS'])>100)
			$size=7;
		else
			$size=9;
		$pdf->SetFont($Params, '', $size);
		$pdf->writeHTMLCell(94, 0, 2+$tmpX, 42+$tmpY, '<span style="font-size:9px;">Seller Address：</span>'.$senderInfo['SENDER_ADDRESS'], 0, 1, 0, true, '', true);
		
		//seller contact:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(27, 0, 2+$tmpX, 50+$tmpY, 'Seller Contact：', 0, 1, 0, true, '', true);
		
		//卖家联系方式
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(68, 0, 29+$tmpX, 50+$tmpY, (empty($senderInfo['SENDER_MOBILE'])?$senderInfo['SENDER_TELEPHONE']:$senderInfo['SENDER_MOBILE']), 0, 1, 0, true, '', true);

		//地址信息下边线条
		$pdf->Line(2+$tmpX, 54.5+$tmpY, 98+$tmpX, 54.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//产品说明:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(23, 0, 2+$tmpX, 54.5+$tmpY, '产品说明：', 0, 1, 0, true, '', true);
		
		//Item name:
		$pdf->SetFont('msyhbd', '', 11);
		$pdf->writeHTMLCell(27, 0, 22+$tmpX, 56+$tmpY, 'Item name：', 0, 1, 0, true, '', true);
		
		//Item sku seller:
		$pdf->SetFont('msyhbd', '', 11);
		$pdf->writeHTMLCell(35, 0, 60+$tmpX, 56+$tmpY, 'Item sku seller：', 0, 1, 0, true, '', true);
		
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>2)
				break;
			
			$jiange=6.5*$key;
				
			//Item name
			if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='TH'){
				$pdf->setCellHeightRatio(0.9);
				if(strlen($vitems['PRODUCT_TITLE'])>150)
					$size=9.5;
				else
					$size=12;
			}
			else{
				if(strlen($vitems['PRODUCT_TITLE'])>70)
					$size=5.5;
				else
					$size=8;
			}
			$pdf->SetFont($Params_buyer, '', $size);
			$pdf->writeHTMLCell(57, 0, 2+$tmpX, 60+$tmpY+$jiange, $vitems['PRODUCT_TITLE'], 0, 1, 0, true, 'C', true);
			$pdf->setCellHeightRatio(1.2);
			//sku
			$pdf->SetFont($Params, '', 8);
			$pdf->writeHTMLCell(35, 0, 60+$tmpX, 60+$tmpY+$jiange, $vitems['SKU'], 0, 1, 0, true, 'C', true);
				
			$count++;
		}
		
		$tmp_high = 0;
		if($shippingService->shipping_method_code=='LGS-FM01'){
			$tmp_high = 3;
		}
		
		//首公里仓库:
		$pdf->SetFont($Params, '', 8);
		$pdf->writeHTMLCell(27, 0, 2+$tmpX, 75+$tmpY-$tmp_high, '首公里仓库：', 0, 1, 0, true, '', true);
		
		//首公里仓库地址:
		$sglck='';
		if($shippingService->shipping_method_code=='LGS-FM40')
			$sglck='深圳市 宝安区 福永塘尾高新开发区福园一路德的工业园B栋1A区';
		else if($shippingService->shipping_method_code=='LGS-FM01')
			$sglck='广东省深圳市宝安区深圳国际机场 机场四道国内航空货站 201-221 四方邮局  518128';
		else //if($shippingService->carrier_code=='LGS-FM41')
			$sglck='浙江省义乌市龙海路811号申通电商大厦二楼 时丰运通物流';
		$pdf->SetFont($Params, '', 7.5);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 78.5+$tmpY-$tmp_high, $sglck, 0, 1, 0, true, '', true);
		
		//商品信息下边线条
		$pdf->Line(2+$tmpX, 82.5+$tmpY, 98+$tmpX,82.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//渠道编码:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(60, 0, 2+$tmpX, 84+$tmpY, '渠道编码', 0, 1, 0, true, 'R', true);
		
		//分拣编码:
		$fjbm='';
		if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='MY')
			$fjbm='LZDMY';
		else if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='SG')
			$fjbm='LZDSG';
		else if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='PH')
			$fjbm='LZDPH';
		else if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='TH')
			$fjbm='LZDTH';
		else if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='VN')
			$fjbm='LZDVN';
		else //if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='ID')
			$fjbm='LZDID';
		
		if($shippingService->shipping_method_code=='LGS-FM01'){
			$fjbm .= '01';
		}
		
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(34, 0, 63+$tmpX, 84+$tmpY, $fjbm, 0, 1, 0, true, 'C', true);
		
		//分拣编码下边线条
		$pdf->Line(2+$tmpX, 90.5+$tmpY, 98+$tmpX,90.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//分拣编码竖边线条
		$pdf->Line(62+$tmpX, 82.5+$tmpY, 62+$tmpX, 90.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//Lazada Package Number:
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 90+$tmpY, 'Lazada Package Number', 0, 1, 0, true, 'C', true);
		
		//包装码
		$pdf->SetFont($Params, '', 9);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 94+$tmpY, $trackingInfo['PackageId'], 0, 1, 0, true, 'C', true);
	}
	
	//Jumia Seko
	public static function getJumiaSeko($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		// 		$pdf->setCellHeightRatio(1); //字体行距
		
		if(!empty($order->tracking_number)){
			$trackingInfo['tracking_number'] = $order->tracking_number;
		}
		
// 		if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='TH'){
// 			$Params=$otherParams['pdfFont'];
// 			$Params_buyer='angsau';
// 		}

		$Params=$otherParams['pdfFont'];
		$Params_buyer=$otherParams['pdfFont'];
		
// 		echo $Params;
		$pdf->SetFont('msyhbd', '', 27);
		if(strtoupper($receiverInfo['RECEIVER_COUNTRY_EN_AB'])=='KE'){
			$puid = \Yii::$app->user->identity->getParentUid();
			
			if($puid == 13672){
				$pdf->writeHTMLCell(96, 0, 2+$tmpX, 2+$tmpY, 'Kenya Choice', 0, 1, 0, true, 'C', true);
			}else{
				$pdf->writeHTMLCell(96, 0, 2+$tmpX, 2+$tmpY, 'Kenya-Seko', 0, 1, 0, true, 'C', true);
			}
		}else{
			if(strlen($receiverInfo['RECEIVER_COUNTRY_EN']) > 15){
				$pdf->SetFont('msyhbd', '', 20);
			}
			$pdf->writeHTMLCell(96, 0, 2+$tmpX, 2+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN'], 0, 1, 0, true, 'C', true);
		}
		
// 		print_r($receiverInfo);
		$face_a_b = '';
		if(!empty($shippingService['carrier_params']['face_a_b']))
		{
			$face_a_b = $shippingService['carrier_params']['face_a_b'];
		}

		if(!empty($face_a_b)){
			$pdf->SetFont('msyh', '', 20);
			$pdf->writeHTMLCell(96, 0, 2+$tmpX, 5+$tmpY, $face_a_b, 0, 1, 0, true, 'L', true);
		}

		$pdf->SetFont('msyhbd', '', 11);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 18+$tmpY, 'Parcel Number', 0, 1, 0, true, 'L', true);
		
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false,//array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		
		// CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
		$pdf->SetFont('arialbd', '', 9);
		if(strlen($trackingInfo['tracking_number']) == 16){
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C39', '', '', '81.5', 16.5, 0.9, $style, 'N');
			$pdf->writeHTMLCell(96, 0, 28+$tmpX, 40+$tmpY, '*'.$trackingInfo['tracking_number'].'*', 0, 1, 0, true, 'L', true);
		}else if(strlen($trackingInfo['tracking_number']) == 17){
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C39', '', '', '85.7', 16.5, 0.9, $style, 'N');
			$pdf->writeHTMLCell(96, 0, 28+$tmpX, 40+$tmpY, '*'.$trackingInfo['tracking_number'].'*', 0, 1, 0, true, 'L', true);
		}else if(strlen($trackingInfo['tracking_number']) == 18){
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C39', '', '', '89.9', 16.5, 0.9, $style, 'N');
			$pdf->writeHTMLCell(96, 0, 28+$tmpX, 40+$tmpY, '*'.$trackingInfo['tracking_number'].'*', 0, 1, 0, true, 'L', true);
		}else{
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C39', 5+$tmpX, 23+$tmpY, '94.2', 29.4, 0.6, $style, 'N');
			$pdf->writeHTMLCell(96, 0, 33+$tmpX, 51+$tmpY, '*'.$trackingInfo['tracking_number'].'*', 0, 1, 0, true, 'L', true);
		}
		
		$pdf->SetFont('msyhbd', '', 11);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 60+$tmpY, 'Order Number', 0, 1, 0, true, 'L', true);
		
		unset($style);
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => 'C',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false,//array(255,255,255),
				'text' => false,
				'font' => 'helvetica',
				'fontsize' => 10,
				'stretchtext' =>0
		);
		
		$pdf->write1DBarcode($order->order_source_order_id, 'C39', 28+$tmpX, 65+$tmpY, '51.5', 29.4, 0.6, $style, 'N');
		
		$pdf->SetFont('arialbd', '', 9);
		$pdf->writeHTMLCell(96, 0, 43+$tmpX, 93+$tmpY, '*'.$order->order_source_order_id.'*', 0, 1, 0, true, 'L', true);
	}
}
