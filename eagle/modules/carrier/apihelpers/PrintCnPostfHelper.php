<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\SysShippingMethod;
use eagle\models\CarrierTemplateHighcopy;
use eagle\models\SysCountry;
use eagle\modules\order\models\OdOrderShipped;
use Qiniu\json_decode;
use eagle\modules\carrier\helpers\CarrierPartitionNumberHelper;
use eagle\modules\carrier\apihelpers\PrintPdfHelper;

class PrintCnPostfHelper{
	
	//根据国家简码，选择对应面单
	public static function getCnPost($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
	    if($order->consignee_country_code == 'FR')
	    	self::getCnPost_FR($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
	    else if($order->consignee_country_code == 'DE')
	    	self::getCnPost_DE($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		else if($order->consignee_country_code == 'GB' || $order->consignee_country_code == 'UK')
		    self::getCnPost_GB_UK($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		else if($order->consignee_country_code == 'US')
		    self::getCnPost_US($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		else if($order->consignee_country_code == 'SE')
			self::getCnPost_SE($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		else
		    self::getCnPost_Orther($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
	}
	
	/**
	 * E邮宝，美国
	 * 
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_US($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
	    //******************字体   start********************
	    $font_msyh = 'msyh';      //微软雅黑
	    $font_Arial = 'Arial';    //Arial
	    $font_Arialbd = 'Arialbd';    //Arialbd
	    //******************字体   end**********************
	    
		//******************框架   start****************************
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件类型left边线条
		$pdf->Line(4+$tmpX, 4+$tmpY, 4+$tmpX, 15+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件类型top边线条
		$pdf->Line(4+$tmpX, 4+$tmpY, 16+$tmpX, 4+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件类型right边线条
		$pdf->Line(16+$tmpX, 4+$tmpY, 16+$tmpX, 15+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件类型bottom边线条
		$pdf->Line(4+$tmpX, 15+$tmpY, 16+$tmpX, 15+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//中国邮政下划线
		$pdf->Line(25+$tmpX, 11.3+$tmpY, 65+$tmpX, 11.3+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//分拣码left边线条
		$pdf->Line(72+$tmpX, 4+$tmpY, 72+$tmpX, 14+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//分拣码top边线条
		$pdf->Line(72+$tmpX, 4+$tmpY, 96+$tmpX, 4+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//分拣码right边线条
		$pdf->Line(96+$tmpX, 4+$tmpY, 96+$tmpX, 14+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//分拣码bottom边线条
		$pdf->Line(72+$tmpX, 14+$tmpY, 96+$tmpX, 14+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//最上区域bottom边线条
		$pdf->Line(2+$tmpX, 22+$tmpY, 98+$tmpX, 22+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//寄件信息right边线条
		$pdf->Line(57+$tmpX, 22+$tmpY, 57+$tmpX, 45+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//寄件信息bottom边线条
		$pdf->Line(2+$tmpX, 45+$tmpY, 98+$tmpX, 45+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件信息left边线条
		$pdf->Line(20+$tmpX, 45+$tmpY, 20+$tmpX, 69.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件信息bottom边线条
		$pdf->Line(2+$tmpX, 69.5+$tmpY, 98+$tmpX, 69.5+$tmpY, $style=array('width' => 1.05, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//******************框架   end****************************
			
		//******************内容   start****************************
		//输出 F 字样
		$pdf->SetFont($font_Arial, '', 32);
		$pdf->writeHTMLCell(32, 0, 6+$tmpX, 2+$tmpY, 'F', 0, 1, 0, true, '', true);
		//输出 from 字样
		$pdf->SetFont($font_Arial, '', 8);
		$pdf->writeHTMLCell(32, 0, 4+$tmpX, 18+$tmpY, 'from:', 0, 1, 0, true, '', true);
		//中国邮政logo
		$pdf->writeHTMLCell(0, 0, 30+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="27.8mm" height="6.5mm" >', 0, 1, 0, true, '', true);
		//美国邮政logo+epacket tm 字样
		$pdf->writeHTMLCell(0, 0, 25+$tmpX, 12+$tmpY, '<img src="/images/customprint/labelimg/americapost.jpg"  width="40mm" height="6mm" >', 0, 1, 0, true, '', true);
		//epacket tm 字样
		$pdf->SetFont($font_Arial, '', 10);
		$pdf->writeHTMLCell(32, 0, 37+$tmpX, 17.5+$tmpY, 'ePacket', 0, 1, 0, true, '', true);
		$pdf->SetFont($font_Arial, '', 7);
		$pdf->writeHTMLCell(32, 0, 51+$tmpX, 17.5+$tmpY, 'TM', 0, 1, 0, true, '', true);
		//Airmail Postage Paid China Post 字样
		$pdf->SetFont($font_Arialbd, '', 7.5);
		$pdf->writeHTMLCell(32, 0, 72+$tmpX, 4+$tmpY, 'Airmail', 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(32, 0, 72+$tmpX, 7+$tmpY, 'Postage Paid', 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(32, 0, 72+$tmpX, 10+$tmpY, 'China Post', 0, 1, 0, true, '', true);
		//获取收件人信息
		$receiverInfo = PrintPdfHelper::getReceiverInfo($order);
		//分拣码
		$RECEIVER_ZIPCODE = '';
		if(!empty($receiverInfo['RECEIVER_ZIPCODE']))
		    $RECEIVER_ZIPCODE = $receiverInfo['RECEIVER_ZIPCODE'];
		if(strlen($RECEIVER_ZIPCODE) > 5)
		{
		    //邮编大于5位时，截取前五位
		    $RECEIVER_ZIPCODE = substr($RECEIVER_ZIPCODE, 0, 5);
		}
		    
		$receiver_country_en_num = PrintPdfHelper::getEubSortingYardsByCountry($order->consignee_country_code, $RECEIVER_ZIPCODE);
		$pdf->SetFont($font_Arial, '', 14);
		$pdf->writeHTMLCell(32, 0, 78+$tmpX, 15+$tmpY, $receiver_country_en_num, 0, 1, 0, true, '', true);
		//获取寄件人信息
		$senderInfo = PrintPdfHelper::getSenderInfo($order);
		//寄件信息
		$senderAdd = $senderInfo['SENDER_NAME'];
		if(!empty($senderInfo['SENDER_COMPANY_NAME']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
		if(!empty($senderInfo['SENDER_ADDRESS']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
		$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE']).' '.$senderInfo['SENDER_ZIPCODE'];
		if(!empty($senderInfo['SENDER_COUNTRY_EN']))
			$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
		/*$phone = '';
		$mobile = '';
		if(!empty($senderInfo['SENDER_MOBILE']))
			$mobile = $senderInfo['SENDER_MOBILE'];
		if(!empty($senderInfo['SENDER_TELEPHONE']))
			$phone = $senderInfo['SENDER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($mobile != $phone && strlen($mobile.$phone) > 0)
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(!empty($mobile))
			$senderAdd .= '<br>PHONE: '.$mobile;*/
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(55, 0, 3+$tmpX, 22+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
		//条码的样式
		$style = array(
			'border'=>false, 
        	'padding'=>3, 
		    'align' => 'C',
		    'cellfitalign' => 'C',
        	'fgcolor'=>array(0,0,0), 
        	'bgcolor'=>false, 
        	'text'=>false, 
        	'font'=>'helvetica', 
        	'fontsize'=>8, 
        	'stretchtext'=>0,
		);
		//收件人邮编条形码
		$pdf->write1DBarcode('420'.$RECEIVER_ZIPCODE, 'C128', 57+$tmpX, 22+$tmpY, '40', 20, 0.4, $style, 'N');
		$pdf->SetFont($font_msyh, '', 8);
		$pdf->writeHTMLCell(50, 0, 68+$tmpX, 40+$tmpY, 'ZIP '.$RECEIVER_ZIPCODE, 0, 1, 0, true, '', true);
		
		//Customs information availableon attached CN22.USPS Personnel Scan barcode below for delivery information .
		$pdf->SetFont($font_Arialbd, '', 5);
		$pdf->writeHTMLCell(55, 0, 2+$tmpX, 40+$tmpY, 'Customs information availableon attached CN22.<br>USPS Personnel Scan barcode below for delivery information.', 0, 1, 0, true, '', true);
		
		//TO：
		$pdf->SetFont($font_Arialbd, '', 20);
		$pdf->writeHTMLCell(20, 0, 5+$tmpX, 52+$tmpY, 'TO:', 0, 1, 0, true, '', true);
		
		//收件人信息
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(75, 0, 21+$tmpX, 46+$tmpY, strtoupper($receiverInfo['RECEIVER_NAME']), 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(75, 0, 21+$tmpX, 50+$tmpY, strtoupper($receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
				'UNITED STATES OF AMERICA'), 0, 1, 0, true, '', true);
		//收件电话，自动缩小字体
		$phone = '';
		$mobile = '';
		if(!empty($receiverInfo['RECEIVER_MOBILE']))
			$mobile = $receiverInfo['RECEIVER_MOBILE'];
		if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
			$phone = $receiverInfo['RECEIVER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(strlen($mobile) > 35)
		{
			$pdf->SetFont($font_msyh, '', 6);
		}
		//电话图标
		$pdf->writeHTMLCell(0, 0, 21+$tmpX, 65.5+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
		//电话信息
		$pdf->writeHTMLCell(65, 0, 28+$tmpX, 66+$tmpY, $mobile, 0, 1, 0, true, '', true);
		//USPS TRACKING #
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(50, 0, 33+$tmpX, 71+$tmpY, 'USPS TRACKING #', 0, 1, 0, true, '', true);
		
		//获取相关跟踪号信息
		$trackingInfo = PrintPdfHelper::getTrackingInfo($order);
		//跟踪号条形码
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 5+$tmpX, 73+$tmpY, '90', 20, 0.6, $style, 'N');
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		
	    //是否加打信息
		if(!empty($otherParams['printAddVal'])){
			$isSKU = 0;
			foreach ($otherParams['printAddVal'] as $v){
				if($v == 'addSku'){
					$isSKU = 1;
				}
			}
		
			//显示SKU信息
			if($isSKU == 1){
				$skuStr = '';
				//获取订单详情列表信息
				$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
				foreach ($itemListDetailInfo['products'] as $product_key => $product)
				{
					$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
				}
				
				if($skuStr != ''){
				    if(strlen($skuStr)>75){
				        $skuStr = substr($skuStr, 0, 75);
				    }
					$pdf->SetFont($font_Arial, '', 8);
					$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
				}
			}
		}
	}
	
	/**
	 * E邮宝，英国
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_GB_UK($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
	    //******************字体   start********************
	    $font_msyh = 'msyh';      //微软雅黑
	    $font_Arial = 'Arial';    //Arial
	    $font_Arialbd = 'Arialbd';    //Arialbd
	    //******************字体   end**********************
	     
	    //******************框架   start****************************
	    //left边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //top边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //right边线条
	    $pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //bottom边线条
	    $pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //邮件类型left边线条
	    $pdf->Line(7+$tmpX, 9+$tmpY, 7+$tmpX, 20+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //邮件类型top边线条
	    $pdf->Line(7+$tmpX, 9+$tmpY, 25+$tmpX, 9+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //邮件类型right边线条
	    $pdf->Line(25+$tmpX, 9+$tmpY, 25+$tmpX, 20+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //邮件类型bottom边线条
	    $pdf->Line(7+$tmpX, 20+$tmpY, 25+$tmpX, 20+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	     
	    //中国邮政下划线
	    $pdf->Line(36+$tmpX, 14+$tmpY, 74+$tmpX, 14+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //最上区域bottom边线条
	    $pdf->Line(2+$tmpX, 27.5+$tmpY, 98+$tmpX, 27.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //寄件信息bottom边线条
	    $pdf->Line(2+$tmpX, 51+$tmpY, 98+$tmpX, 51+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //收件信息left边线条
	    $pdf->Line(18+$tmpX, 51+$tmpY, 18+$tmpX, 71+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //收件信息bottom边线条
	    $pdf->Line(2+$tmpX, 71+$tmpY, 98+$tmpX, 71+$tmpY, $style=array('width' => 1.05, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //******************框架   end****************************
	    
	    //******************内容   start****************************
	    //输出英国  字样
	    $pdf->SetFont($font_msyh, '', 20);
	    $pdf->writeHTMLCell(20, 0, 8+$tmpX, 10+$tmpY, '英国', 0, 1, 0, true, '', true);
	    //输出 from 字样
	    $pdf->SetFont($font_Arial, '', 12);
	    $pdf->writeHTMLCell(32, 0, 4+$tmpX, 23+$tmpY, 'Return to', 0, 1, 0, true, '', true);
	    //中国邮政logo
	    $pdf->writeHTMLCell(0, 0, 38+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="30mm" height="9mm" >', 0, 1, 0, true, '', true);
	    //英国邮政logo
	    $pdf->writeHTMLCell(0, 0, 45+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/royalmail.jpg"  width="18mm" height="11mm" >', 0, 1, 0, true, '', true);
	    //Prime Expres logo
	    $pdf->writeHTMLCell(0, 0, 75+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/eub-postexpres.jpg"  width="18mm" height="23mm" >', 0, 1, 0, true, '', true);
	    //获取寄件人信息
	    $senderInfo = PrintPdfHelper::getSenderInfo($order);
	    //寄件信息
	    $senderAdd = $senderInfo['SENDER_NAME'];
	    if(!empty($senderInfo['SENDER_COMPANY_NAME']))
	        $senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
	    if(!empty($senderInfo['SENDER_ADDRESS']))
	    	$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
	    $senderAdd .= '<br>'.$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'].' '.$senderInfo['SENDER_ZIPCODE'];
	    if(!empty($senderInfo['SENDER_COUNTRY_EN']))
	    	$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
	    $phone = '';
	    $mobile = '';
	    if(!empty($senderInfo['SENDER_MOBILE']))
	    	$mobile = $senderInfo['SENDER_MOBILE'];
	    if(!empty($senderInfo['SENDER_TELEPHONE']))
	    	$phone = $senderInfo['SENDER_TELEPHONE'];
	    if($mobile == '' && $phone != '')
	    {
	    	$mobile = $phone;
	    }
	    else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0)
	    {
	    	$mobile = $mobile.'/'.$phone;
	    }
	    if(!empty($mobile))
	    	$senderAdd .= '<br>PHONE: '.$mobile;
	    $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(90, 0, 2+$tmpX, 28+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
	    //获取发件人信息
	    $receiverInfo = PrintPdfHelper::getReceiverInfo($order);
	    //TO：
	    $pdf->SetFont($font_Arialbd, '', 20);
	    $pdf->writeHTMLCell(16, 0, 3+$tmpX, 57+$tmpY, 'TO:', 0, 1, 0, true, '', true);
	    //收件人信息
	    $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(75, 0, 19+$tmpX, 51+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(75, 0, 19+$tmpX, 54+$tmpY, $receiverInfo['RECEIVER_ADDRESS'].'<br>'.
	    		$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).'<br>'.
	    		'UNITED KINGDOM '.$receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
	    //收件电话，自动缩小字体
	    $phone = '';
	    $mobile = '';
	    if(!empty($receiverInfo['RECEIVER_MOBILE']))
	    	$mobile = $receiverInfo['RECEIVER_MOBILE'];
	    if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
	    	$phone = $receiverInfo['RECEIVER_TELEPHONE'];
	    if($mobile == '' && $phone != '')
	    {
	    	$mobile = $phone;
	    }
	    else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
	    {
	    	$mobile = $mobile.'/'.$phone;
	    }
	    if(strlen($mobile) > 35)
	    {
	    	$pdf->SetFont($font_msyh, '', 6);
	    }
	    //电话图标
	    $pdf->writeHTMLCell(0, 0, 19+$tmpX, 67+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
	    //电话信息
	    $pdf->writeHTMLCell(70, 0, 25+$tmpX, 67+$tmpY, $mobile, 0, 1, 0, true, '', true);
	    //Royel Mail Delivery Confirmation
	    $pdf->SetFont($font_Arialbd, '', 10);
	    $pdf->writeHTMLCell(80, 0, 22+$tmpX, 72+$tmpY, 'Royel Mail Delivery Confirmation', 0, 1, 0, true, '', true);
	    //投递需扫描图示
	    $pdf->writeHTMLCell(0, 0, 2+$tmpX, 78+$tmpY, '<img src="/images/customprint/labelimg/EIBFrscan-2.jpg"  width="16mm" height="12mm" >', 0, 1, 0, true, '', true);
	    //获取相关跟踪号信息
	    $trackingInfo = PrintPdfHelper::getTrackingInfo($order);
	    //跟踪号条形码
	    $pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 76+$tmpY, '60', 14, 0.6, $style, 'N');
	    $pdf->SetFont($font_Arialbd, '', 10);
	    $pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
	    //无需签收图示
	    $pdf->writeHTMLCell(0, 0, 80+$tmpX, 77+$tmpY, '<img src="/images/customprint/labelimg/no-signature.jpg"  width="16mm" height="13mm" >', 0, 1, 0, true, '', true);
	    
	    //是否加打信息
	    if(!empty($otherParams['printAddVal'])){
	    	$isSKU = 0;
	    	foreach ($otherParams['printAddVal'] as $v){
	    		if($v == 'addSku'){
	    			$isSKU = 1;
	    		}
	    	}
	    
	    	//显示SKU信息
	    	if($isSKU == 1){
	    		$skuStr = '';
	    		//获取订单详情列表信息
	    		$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
	    		foreach ($itemListDetailInfo['products'] as $product_key => $product)
	    		{
	    			$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
	    		}
	    
	    		if($skuStr != ''){
	    			if(strlen($skuStr)>7){
	    				$skuStr = substr($skuStr, 0, 75);
	    			}
	    			$pdf->SetFont($font_Arial, '', 8);
	    			$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
	    		}
	    	}
	    }
	    //******************内容  end****************************
	}
	
	/**
	 * E邮宝，德国
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_DE($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
	    //******************字体   start********************
	    $font_msyh = 'msyh';      //微软雅黑
	    $font_Arial = 'Arial';    //Arial
	    $font_Arialbd = 'Arialbd';    //Arialbd
	    //******************字体   end**********************
	     
	    //******************框架   start****************************
	    //left边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //top边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //right边线条
	    $pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //bottom边线条
	    $pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //黑框left边线条
	    $pdf->Line(11+$tmpX, 16+$tmpY, 11+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //黑框top边线条
	    $pdf->Line(11+$tmpX, 16+$tmpY, 20+$tmpX, 16+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //黑框right边线条
	    $pdf->Line(20+$tmpX, 16+$tmpY, 20+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //黑框bottom边线条
	    $pdf->Line(11+$tmpX, 23.5+$tmpY, 20+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	     
	    
	    //大黑框left边线条
	    $pdf->Line(4+$tmpX, 6+$tmpY, 4+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //大黑框top边线条
	    $pdf->Line(4+$tmpX, 6+$tmpY, 27.5+$tmpX, 6+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //大黑框right边线条
	    $pdf->Line(27.5+$tmpX, 6+$tmpY, 27.5+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //大黑框bottom边线条
	    $pdf->Line(4+$tmpX, 24.5+$tmpY, 27.5+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //ePacket下划线
	    $pdf->Line(32+$tmpX, 14+$tmpY, 78+$tmpX, 14+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //最上区域bottom边线条
	    $pdf->Line(2+$tmpX, 29+$tmpY, 98+$tmpX, 29+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //寄件信息right边线条
	    $pdf->Line(69+$tmpX, 29+$tmpY, 69+$tmpX, 49+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //寄件信息bottom边线条
	    $pdf->Line(2+$tmpX, 49+$tmpY, 98+$tmpX, 49+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //收件信息left边线条
	    $pdf->Line(23+$tmpX, 49+$tmpY, 23+$tmpX, 70+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //收件信息bottom边线条
	    $pdf->Line(2+$tmpX, 70+$tmpY, 98+$tmpX, 70+$tmpY, $style=array('width' => 1.05, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //******************框架   end****************************
	    	
	    //******************内容   start****************************
	    //输出 Prioritaire 字样
	    $pdf->SetFont($font_Arial, '', 12);
	    $pdf->writeHTMLCell(32, 0, 6+$tmpX, 9+$tmpY, 'Prioritaire', 0, 1, 0, true, '', true);
	    //输出 国家简码 字样
	    $pdf->SetFont($font_Arialbd, '', 14);
	    $pdf->writeHTMLCell(32, 0, 11+$tmpX, 17+$tmpY, $order->consignee_country_code, 0, 1, 0, true, '', true);
	    //ePacket 字样
	    $pdf->SetFont($font_Arialbd, '', 25);
	    $pdf->writeHTMLCell(50, 0, 37+$tmpX, 4+$tmpY, 'ePacket', 0, 1, 0, true, '', true);
	    //中国邮政logo
	    $pdf->writeHTMLCell(0, 0, 30+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="23mm" height="8.33mm" >', 0, 1, 0, true, '', true);
	    //Geo-logo
	    $pdf->writeHTMLCell(0, 0, 55+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/eubfr.jpg"  width="20mm" height="8.33mm" >', 0, 1, 0, true, '', true);
	    //Prime Expres logo
	    $pdf->writeHTMLCell(0, 0, 77+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/eub-postexpres.jpg"  width="18mm" height="24mm" >', 0, 1, 0, true, '', true);
	    //输出 DE: 字样
	    $pdf->SetFont($font_Arial, '', 8);
	    $pdf->writeHTMLCell(32, 0, 4+$tmpX, 25+$tmpY, 'DE:', 0, 1, 0, true, '', true);
	    //获取寄件人信息
	    $senderInfo = PrintPdfHelper::getSenderInfo($order);
	    //寄件信息
	    $senderAdd = $senderInfo['SENDER_NAME'];
	    if(!empty($senderInfo['SENDER_COMPANY_NAME']))
	    	$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
	    if(!empty($senderInfo['SENDER_ADDRESS']))
	    	$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
	    $senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE']).' '.$senderInfo['SENDER_ZIPCODE'];
	    if(!empty($senderInfo['SENDER_COUNTRY_EN']))
	    	$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
	    /*$phone = '';
	    $mobile = '';
	    if(!empty($senderInfo['SENDER_MOBILE']))
	    	$mobile = $senderInfo['SENDER_MOBILE'];
	    if(!empty($senderInfo['SENDER_TELEPHONE']))
	    	$phone = $senderInfo['SENDER_TELEPHONE'];
	    if($mobile == '' && $phone != '')
	    {
	    	$mobile = $phone;
	    }
	    else if($mobile != $phone && strlen($mobile.$phone) > 0)
	    {
	    	$mobile = $mobile.'/'.$phone;
	    }
	    if(!empty($mobile))
	    	$senderAdd .= '<br>PHONE: '.$mobile;*/
	    $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(68, 0, 2+$tmpX, 29+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
	    //获取收件人信息
	    $receiverInfo = PrintPdfHelper::getReceiverInfo($order);
	    //收件人邮编
	    $pdf->SetFont($font_Arial, '', 20);
	    $pdf->writeHTMLCell(28, 0, 70+$tmpX, 30+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, 'C', true);
	    //寄达国中文
	    $pdf->SetFont($font_msyh, '', 20);
	    $pdf->writeHTMLCell(28, 0, 70+$tmpX, 38+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'C', true);
	    //输出 A: 字样
	    $pdf->SetFont($font_Arialbd, '', 20);
	    $pdf->writeHTMLCell(30, 0, 8+$tmpX, 55+$tmpY, 'A:', 0, 1, 0, true, '', true);
	    //收件人信息
	    $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(70, 0, 24+$tmpX, 49+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(70, 0, 24+$tmpX, 52+$tmpY, $receiverInfo['RECEIVER_ADDRESS'].'<br>'.
	    		$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
	    		strtoupper($receiverInfo['RECEIVER_COUNTRY_EN']), 0, 1, 0, true, '', true);
	    //收件电话，自动缩小字体
	    $phone = '';
	    $mobile = '';
	    if(!empty($receiverInfo['RECEIVER_MOBILE']))
	        $mobile = $receiverInfo['RECEIVER_MOBILE'];
	    if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
	    	$phone = $receiverInfo['RECEIVER_TELEPHONE'];
	    if($mobile == '' && $phone != '')
	    {
	        $mobile = $phone;
	    }
	    else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
	    {
	    	$mobile = $mobile.'/'.$phone;
	    }
	    if(strlen($mobile) > 35)
	    {
	    	$pdf->SetFont($font_msyh, '', 6);
	    }
	    //电话图标
		$pdf->writeHTMLCell(60, 0, 24+$tmpX, 66+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
		//电话信息
		$pdf->writeHTMLCell(0, 0, 30+$tmpX, 66+$tmpY, $mobile, 0, 1, 0, true, '', true);
		//信箱投递图示
		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 60+$tmpY, '<img src="/images/customprint/labelimg/eubfremail.jpg"  width="12mm" height="7mm" >', 0, 1, 0, true, '', true);
	    //CONFIRMATION DE DISTRIBUTION
	    $pdf->SetFont($font_Arialbd, '', 10);
	    $pdf->writeHTMLCell(80, 0, 20+$tmpX, 71+$tmpY, 'CONFIRMATION DE DISTRIBUTION', 0, 1, 0, true, '', true);
	    //投递需扫描图示
	    $pdf->writeHTMLCell(0, 0, 2+$tmpX, 76+$tmpY, '<img src="/images/customprint/labelimg/EIBFrscan-2.jpg"  width="16mm" height="12mm" >', 0, 1, 0, true, '', true);
	    //获取相关跟踪号信息
	    $trackingInfo = PrintPdfHelper::getTrackingInfo($order);
	    //跟踪号条形码
	    $pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 76+$tmpY, '60', 14, 0.6, $style, 'N');
	    $pdf->SetFont($font_Arialbd, '', 10);
	    $pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
	    //无需签收图示
	    $pdf->writeHTMLCell(0, 0, 80+$tmpX, 75+$tmpY, '<img src="/images/customprint/labelimg/eubfrscanright.jpg"  width="16mm" height="13mm" >', 0, 1, 0, true, '', true);
	     
	    
	    //是否加打信息
	    if(!empty($otherParams['printAddVal'])){
	    	$isSKU = 0;
	    	foreach ($otherParams['printAddVal'] as $v){
	    		if($v == 'addSku'){
	    			$isSKU = 1;
	    		}
	    	}
	    
	    	//显示SKU信息
	    	if($isSKU == 1){
	    		$skuStr = '';
	    		//获取订单详情列表信息
	    		$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
	    		foreach ($itemListDetailInfo['products'] as $product_key => $product)
	    		{
	    			$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
	    		}
	    
	    		if($skuStr != ''){
	    			if(strlen($skuStr)>75){
	    				$skuStr = substr($skuStr, 0, 75);
	    			}
	    			$pdf->SetFont($font_Arial, '', 8);
	    			$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
	    		}
	    	}
	    }
	    //******************内容   end****************************
	}
	

	/**
	 * E邮宝，法国
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_FR($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
		//******************字体   start********************
		$font_msyh = 'msyh';      //微软雅黑
		$font_Arial = 'Arial';    //Arial
		$font_Arialbd = 'Arialbd';    //Arialbd
		//******************字体   end**********************
	
		//******************框架   start****************************
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//黑框left边线条
		$pdf->Line(11+$tmpX, 16+$tmpY, 11+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//黑框top边线条
		$pdf->Line(11+$tmpX, 16+$tmpY, 20+$tmpX, 16+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//黑框right边线条
		$pdf->Line(20+$tmpX, 16+$tmpY, 20+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//黑框bottom边线条
		$pdf->Line(11+$tmpX, 23.5+$tmpY, 20+$tmpX, 23.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		 
		//大黑框left边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 4+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//大黑框top边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 27.5+$tmpX, 6+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//大黑框right边线条
		$pdf->Line(27.5+$tmpX, 6+$tmpY, 27.5+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//大黑框bottom边线条
		$pdf->Line(4+$tmpX, 24.5+$tmpY, 27.5+$tmpX, 24.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//ePacket下划线
		$pdf->Line(32+$tmpX, 14+$tmpY, 78+$tmpX, 14+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//最上区域bottom边线条
		$pdf->Line(2+$tmpX, 29+$tmpY, 98+$tmpX, 29+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//寄件信息right边线条
		$pdf->Line(69+$tmpX, 29+$tmpY, 69+$tmpX, 49+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//寄件信息bottom边线条
		$pdf->Line(2+$tmpX, 49+$tmpY, 98+$tmpX, 49+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//收件信息left边线条
		$pdf->Line(23+$tmpX, 49+$tmpY, 23+$tmpX, 70+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件信息bottom边线条
		$pdf->Line(2+$tmpX, 70+$tmpY, 98+$tmpX, 70+$tmpY, $style=array('width' => 1.05, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//******************框架   end****************************
	
		//******************内容   start****************************
		//输出 Prioritaire 字样
		$pdf->SetFont($font_Arial, '', 12);
		$pdf->writeHTMLCell(32, 0, 6+$tmpX, 9+$tmpY, 'Prioritaire', 0, 1, 0, true, '', true);
		//输出 国家简码 字样
		$pdf->SetFont($font_Arialbd, '', 14);
		$pdf->writeHTMLCell(32, 0, 11+$tmpX, 17+$tmpY, $order->consignee_country_code, 0, 1, 0, true, '', true);
		//ePacket 字样
		$pdf->SetFont($font_Arialbd, '', 25);
		$pdf->writeHTMLCell(50, 0, 37+$tmpX, 4+$tmpY, 'ePacket', 0, 1, 0, true, '', true);
		//中国邮政logo
		$pdf->writeHTMLCell(0, 0, 30+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="23mm" height="8.33mm" >', 0, 1, 0, true, '', true);
		//Geo-logo
		$pdf->writeHTMLCell(0, 0, 55+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/la_poste.jpg"  width="20mm" height="8.33mm" >', 0, 1, 0, true, '', true);
		//Prime Expres logo
		$pdf->writeHTMLCell(0, 0, 77+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/eub-postexpres.jpg"  width="18mm" height="24mm" >', 0, 1, 0, true, '', true);
		//输出 DE: 字样
		$pdf->SetFont($font_Arial, '', 8);
		$pdf->writeHTMLCell(32, 0, 4+$tmpX, 25+$tmpY, 'DE:', 0, 1, 0, true, '', true);
		//获取寄件人信息
		$senderInfo = PrintPdfHelper::getSenderInfo($order);
		//寄件信息
		$senderAdd = $senderInfo['SENDER_NAME'];
		if(!empty($senderInfo['SENDER_COMPANY_NAME']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
		if(!empty($senderInfo['SENDER_ADDRESS']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
		$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE']).' '.$senderInfo['SENDER_ZIPCODE'];
		if(!empty($senderInfo['SENDER_COUNTRY_EN']))
			$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
		/*$phone = '';
		 $mobile = '';
		if(!empty($senderInfo['SENDER_MOBILE']))
			$mobile = $senderInfo['SENDER_MOBILE'];
		if(!empty($senderInfo['SENDER_TELEPHONE']))
			$phone = $senderInfo['SENDER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
		$mobile = $phone;
		}
		else if($mobile != $phone && strlen($mobile.$phone) > 0)
		{
		$mobile = $mobile.'/'.$phone;
		}
		if(!empty($mobile))
			$senderAdd .= '<br>PHONE: '.$mobile;*/
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(68, 0, 2+$tmpX, 29+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
		//获取收件人信息
		$receiverInfo = PrintPdfHelper::getReceiverInfo($order);
		//收件人邮编
		$pdf->SetFont($font_Arial, '', 20);
		$pdf->writeHTMLCell(28, 0, 70+$tmpX, 30+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, 'C', true);
		//寄达国中文
		$pdf->SetFont($font_msyh, '', 20);
		$pdf->writeHTMLCell(28, 0, 70+$tmpX, 38+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'C', true);
		//输出 A: 字样
		$pdf->SetFont($font_Arialbd, '', 20);
		$pdf->writeHTMLCell(30, 0, 8+$tmpX, 55+$tmpY, 'A:', 0, 1, 0, true, '', true);
		//收件人信息
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(70, 0, 24+$tmpX, 49+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(70, 0, 24+$tmpX, 52+$tmpY, $receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
				strtoupper($receiverInfo['RECEIVER_COUNTRY_EN']), 0, 1, 0, true, '', true);
		//收件电话，自动缩小字体
		$phone = '';
		$mobile = '';
		if(!empty($receiverInfo['RECEIVER_MOBILE']))
			$mobile = $receiverInfo['RECEIVER_MOBILE'];
		if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
			$phone = $receiverInfo['RECEIVER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(strlen($mobile) > 35)
		{
			$pdf->SetFont($font_msyh, '', 6);
		}
		//电话图标
		$pdf->writeHTMLCell(60, 0, 24+$tmpX, 66+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
		//电话信息
		$pdf->writeHTMLCell(0, 0, 30+$tmpX, 66+$tmpY, $mobile, 0, 1, 0, true, '', true);
		//信箱投递图示
		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 60+$tmpY, '<img src="/images/customprint/labelimg/eubfremail.jpg"  width="12mm" height="7mm" >', 0, 1, 0, true, '', true);
		//CONFIRMATION DE DISTRIBUTION
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(80, 0, 20+$tmpX, 71+$tmpY, 'CONFIRMATION DE DISTRIBUTION', 0, 1, 0, true, '', true);
		//投递需扫描图示
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 76+$tmpY, '<img src="/images/customprint/labelimg/EIBFrscan-2.jpg"  width="16mm" height="12mm" >', 0, 1, 0, true, '', true);
		//获取相关跟踪号信息
		$trackingInfo = PrintPdfHelper::getTrackingInfo($order);
		//跟踪号条形码
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 76+$tmpY, '60', 14, 0.6, $style, 'N');
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		//无需签收图示
		$pdf->writeHTMLCell(0, 0, 80+$tmpX, 75+$tmpY, '<img src="/images/customprint/labelimg/eubfrscanright.jpg"  width="16mm" height="13mm" >', 0, 1, 0, true, '', true);
	
		 
		//是否加打信息
		if(!empty($otherParams['printAddVal'])){
			$isSKU = 0;
			foreach ($otherParams['printAddVal'] as $v){
				if($v == 'addSku'){
					$isSKU = 1;
				}
			}
		  
			//显示SKU信息
			if($isSKU == 1){
				$skuStr = '';
				//获取订单详情列表信息
				$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
				foreach ($itemListDetailInfo['products'] as $product_key => $product)
				{
					$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
				}
				 
				if($skuStr != ''){
					if(strlen($skuStr)>75){
						$skuStr = substr($skuStr, 0, 75);
					}
					$pdf->SetFont($font_Arial, '', 8);
					$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
				}
			}
		}
		//******************内容   end****************************
	}
	
	/**
	 * E邮宝，瑞典
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_SE($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
		//******************字体   start********************
		$font_msyh = 'msyh';      //微软雅黑
		$font_Arial = 'Arial';    //Arial
		$font_Arialbd = 'Arialbd';    //Arialbd
		//******************字体   end**********************
	
		//******************框架   start****************************
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
		//收件国英文left边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 4+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文top边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 30+$tmpX, 6+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文right边线条
		$pdf->Line(30+$tmpX, 6+$tmpY, 30+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文bottom边线条
		$pdf->Line(4+$tmpX, 23+$tmpY, 30+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//收件国中文top边线条
		$pdf->Line(4+$tmpX, 17+$tmpY, 30+$tmpX, 17+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//有分拣码的收件国家，显示分拣码
		if(in_array($order->consignee_country_code, ['AU', 'RU', 'CA']))
		{
			//分拣码top边线条
			$pdf->Line(30+$tmpX, 17+$tmpY, 36+$tmpX, 17+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//分拣码right边线条
			$pdf->Line(36+$tmpX, 17+$tmpY, 36+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//分拣码bottom边线条
			$pdf->Line(30+$tmpX, 23+$tmpY, 36+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		}
	
		//ePacket下划线
		$pdf->Line(40+$tmpX, 14+$tmpY, 78+$tmpX, 14+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
		//最上区域bottom边线条
		$pdf->Line(2+$tmpX, 27.5+$tmpY, 98+$tmpX, 27.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
		//寄件信息bottom边线条
		$pdf->Line(2+$tmpX, 50+$tmpY, 98+$tmpX, 50+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
		//收件信息left边线条
		$pdf->Line(20+$tmpX, 50+$tmpY, 20+$tmpX, 71.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件信息bottom边线条
		$pdf->Line(2+$tmpX, 71.5+$tmpY, 98+$tmpX, 71.5+$tmpY, $style=array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//******************框架   end****************************
	
		//******************内容   start****************************
		//获取收件人信息
		$receiverInfo = PrintPdfHelper::getReceiverInfo($order);
		//收件国简码
		$pdf->SetFont($font_msyh, '', 32);
		$pdf->writeHTMLCell(26, 0, 5+$tmpX, 4+$tmpY, $order->consignee_country_code, 0, 1, 0, true, 'C', true);
		//收件国中文
		$pdf->SetFont($font_msyh, '', 10);
		$pdf->writeHTMLCell(26, 0, 5+$tmpX, 17+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'C', true);
		//有分拣码的收件国家，显示分拣码
		if(in_array($order->consignee_country_code, ['AU', 'RU', 'CA']))
		{
			$receiver_country_en_num = PrintPdfHelper::getEubSortingYardsByCountry($order->consignee_country_code, $receiverInfo['RECEIVER_ZIPCODE']);
			$pdf->SetFont($font_Arial, '', 18);
			$pdf->writeHTMLCell(6, 0, 30+$tmpX, 16+$tmpY, $receiver_country_en_num, 0, 1, 0, true, '', true);
		}
		//ePacket 字样
		$pdf->SetFont($font_Arialbd, '', 25);
		$pdf->writeHTMLCell(50, 0, 40+$tmpX, 4+$tmpY, 'ePacket', 0, 1, 0, true, '', true);
		//中国邮政logo
		$pdf->writeHTMLCell(0, 0, 42+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="30mm" height="10mm" >', 0, 1, 0, true, '', true);
		//from: 字样
		$pdf->SetFont($font_Arial, '', 8);
		$pdf->writeHTMLCell(32, 0, 4+$tmpX, 24+$tmpY, 'From:', 0, 1, 0, true, '', true);
		//获取寄件人信息
		$senderInfo = PrintPdfHelper::getSenderInfo($order);
		//寄件信息
		$senderAdd = $senderInfo['SENDER_NAME'];
		if(!empty($senderInfo['SENDER_COMPANY_NAME']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
		if(!empty($senderInfo['SENDER_ADDRESS']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
		$senderAdd .= '<br>'.$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'].' '.$senderInfo['SENDER_ZIPCODE'];
		if(!empty($senderInfo['SENDER_COUNTRY_EN']))
			$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
		$phone = '';
		$mobile = '';
		if(!empty($senderInfo['SENDER_MOBILE']))
			$mobile = $senderInfo['SENDER_MOBILE'];
		if(!empty($senderInfo['SENDER_TELEPHONE']))
			$phone = $senderInfo['SENDER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($mobile != $phone && $phone !='')
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(!empty($mobile))
			$senderAdd .= '<br>PHONE: '.$mobile;
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(75, 0, 3+$tmpX, 28+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
		//Prime Expres logo
		$pdf->writeHTMLCell(0, 0, 78+$tmpX, 28+$tmpY, '<img src="/images/customprint/labelimg/eub-postexpres.jpg"  width="16mm" height="20mm" >', 0, 1, 0, true, '', true);
		//TO: 字样
		$pdf->SetFont($font_Arial, '', 20);
		$pdf->writeHTMLCell(30, 0, 4+$tmpX, 56+$tmpY, 'TO:', 0, 1, 0, true, '', true);
		//收件人信息
		//类似阿拉伯，收件地址是阿拉伯文，不可用$font_msyh字体
		if($order->consignee_country_code == 'SA')
			$pdf->SetFont('dejavusans', '', 7.5);
		else
			$pdf->SetFont($font_msyh, '', 7.5);
		$pdf->writeHTMLCell(45, 0, 20+$tmpX, 50+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(45, 0, 20+$tmpX, 53.5+$tmpY, $receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
				strtoupper($receiverInfo['RECEIVER_COUNTRY_EN']), 0, 1, 0, true, '', true);
		//收件电话，自动缩小字体
		$phone = '';
		$mobile = '';
		if(!empty($receiverInfo['RECEIVER_MOBILE']))
			$mobile = $receiverInfo['RECEIVER_MOBILE'];
		if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
			$phone = $receiverInfo['RECEIVER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(strlen($mobile) > 35)
		{
			$pdf->SetFont($font_msyh, '', 6);
		}
		//电话图标
		$pdf->writeHTMLCell(0, 0, 20+$tmpX, 68+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
		//电话信息
		$pdf->writeHTMLCell(45, 0, 27+$tmpX, 68+$tmpY, $mobile, 0, 1, 0, true, '', true);
		//条码的样式
		$RECEIVER_ZIPCODE = '';
		if(!empty($receiverInfo['RECEIVER_ZIPCODE']))
			$RECEIVER_ZIPCODE = $receiverInfo['RECEIVER_ZIPCODE'];
		if(strlen($RECEIVER_ZIPCODE) > 5){
			//邮编大于5位时，截取前五位
			$RECEIVER_ZIPCODE = substr($RECEIVER_ZIPCODE, 0, 5);
		}
		$style = array(
				'border'=>false,
				'padding'=>3,
				'align' => 'C',
				'cellfitalign' => 'C',
				'fgcolor'=>array(0,0,0),
				'bgcolor'=>false,
				'text'=>false,
				'font'=>'helvetica',
				'fontsize'=>8,
				'stretchtext'=>0,
		);
		//收件人邮编条形码
		$pdf->write1DBarcode('420'.$RECEIVER_ZIPCODE, 'C128', 63+$tmpX, 52+$tmpY, '35', 18, 0.4, $style, 'N');
		$pdf->SetFont($font_msyh, '', 8);
		$pdf->writeHTMLCell(50, 0, 72+$tmpX, 67+$tmpY, 'ZIP '.$RECEIVER_ZIPCODE, 0, 1, 0, true, '', true);
		//DELIVERY CONFIRMATION
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(80, 0, 25+$tmpX, 72+$tmpY, 'DELIVERY CONFIRMATION', 0, 1, 0, true, '', true);
		//投递需扫描图示
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 76+$tmpY, '<img src="/images/customprint/labelimg/EIBFrscan-2.jpg"  width="16mm" height="12mm" >', 0, 1, 0, true, '', true);
		//获取相关跟踪号信息
		$trackingInfo = PrintPdfHelper::getTrackingInfo($order);
		//跟踪号条形码
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 76+$tmpY, '60', 14, 0.6, $style, 'N');
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		//无需签收图示
		$pdf->writeHTMLCell(0, 0, 80+$tmpX, 75+$tmpY, '<img src="/images/customprint/labelimg/no-signature.jpg"  width="15mm" height="15mm" >', 0, 1, 0, true, '', true);
	
			
		//是否加打信息
		if(!empty($otherParams['printAddVal'])){
			$isSKU = 0;
			foreach ($otherParams['printAddVal'] as $v){
				if($v == 'addSku'){
					$isSKU = 1;
				}
			}
	
			//显示SKU信息
			if($isSKU == 1){
				$skuStr = '';
				//获取订单详情列表信息
				$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
				foreach ($itemListDetailInfo['products'] as $product_key => $product)
				{
					$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
				}
	
				if($skuStr != ''){
					if(strlen($skuStr)>75){
						$skuStr = substr($skuStr, 0, 75);
					}
					$pdf->SetFont($font_Arial, '', 8);
					$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
				}
			}
		}
		//******************内容   end****************************
	}
	
	/**
	 * E邮宝，标准面单
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPost_Orther($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
		//******************字体   start********************
		$font_msyh = 'msyh';      //微软雅黑
		$font_Arial = 'Arial';    //Arial
		$font_Arialbd = 'Arialbd';    //Arialbd
		//******************字体   end**********************
	
		//******************框架   start****************************
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//收件国英文left边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 4+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文top边线条
		$pdf->Line(4+$tmpX, 6+$tmpY, 30+$tmpX, 6+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文right边线条
		$pdf->Line(30+$tmpX, 6+$tmpY, 30+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件国英文bottom边线条
		$pdf->Line(4+$tmpX, 23+$tmpY, 30+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件国中文top边线条
		$pdf->Line(4+$tmpX, 17+$tmpY, 30+$tmpX, 17+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//有分拣码的收件国家，显示分拣码
		if(in_array($order->consignee_country_code, ['AU', 'RU', 'CA']))
		{
    		//分拣码top边线条
    		$pdf->Line(30+$tmpX, 17+$tmpY, 36+$tmpX, 17+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    		//分拣码right边线条
    		$pdf->Line(36+$tmpX, 17+$tmpY, 36+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    		//分拣码bottom边线条
    		$pdf->Line(30+$tmpX, 23+$tmpY, 36+$tmpX, 23+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		}
		
		//ePacket下划线
		$pdf->Line(40+$tmpX, 14+$tmpY, 78+$tmpX, 14+$tmpY, $style=array('width' => 0.175, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//最上区域bottom边线条
		$pdf->Line(2+$tmpX, 27.5+$tmpY, 98+$tmpX, 27.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//寄件信息bottom边线条
		$pdf->Line(2+$tmpX, 50+$tmpY, 98+$tmpX, 50+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		 
		//收件信息left边线条
		$pdf->Line(20+$tmpX, 50+$tmpY, 20+$tmpX, 71.5+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//收件信息bottom边线条
		$pdf->Line(2+$tmpX, 71.5+$tmpY, 98+$tmpX, 71.5+$tmpY, $style=array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//******************框架   end****************************
	
		//******************内容   start****************************
		//获取收件人信息
		$receiverInfo = PrintPdfHelper::getReceiverInfo($order);
		//收件国简码
		$pdf->SetFont($font_msyh, '', 32);
		$pdf->writeHTMLCell(26, 0, 5+$tmpX, 4+$tmpY, $order->consignee_country_code, 0, 1, 0, true, 'C', true);
		//收件国中文
		$pdf->SetFont($font_msyh, '', 10);
		$pdf->writeHTMLCell(26, 0, 5+$tmpX, 17+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'C', true);
		//有分拣码的收件国家，显示分拣码
		if(in_array($order->consignee_country_code, ['AU', 'RU', 'CA']))
		{
			$receiver_country_en_num = PrintPdfHelper::getEubSortingYardsByCountry($order->consignee_country_code, $receiverInfo['RECEIVER_ZIPCODE']);
			$pdf->SetFont($font_Arial, '', 18);
			$pdf->writeHTMLCell(6, 0, 30+$tmpX, 16+$tmpY, $receiver_country_en_num, 0, 1, 0, true, '', true);
		}
		//ePacket 字样
		$pdf->SetFont($font_Arialbd, '', 25);
		$pdf->writeHTMLCell(50, 0, 40+$tmpX, 4+$tmpY, 'ePacket', 0, 1, 0, true, '', true);
		//中国邮政logo
		$pdf->writeHTMLCell(0, 0, 42+$tmpX, 15+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="30mm" height="10mm" >', 0, 1, 0, true, '', true);
		//from: 字样
		$pdf->SetFont($font_Arial, '', 8);
		$pdf->writeHTMLCell(32, 0, 4+$tmpX, 24+$tmpY, 'From:', 0, 1, 0, true, '', true);
		//获取寄件人信息
		$senderInfo = PrintPdfHelper::getSenderInfo($order);
		//寄件信息
		$senderAdd = $senderInfo['SENDER_NAME'];
		if(!empty($senderInfo['SENDER_COMPANY_NAME']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
		if(!empty($senderInfo['SENDER_ADDRESS']))
			$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
		$senderAdd .= '<br>'.$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'].' '.$senderInfo['SENDER_ZIPCODE'];
		if(!empty($senderInfo['SENDER_COUNTRY_EN']))
			$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
		$phone = '';
		 $mobile = '';
		if(!empty($senderInfo['SENDER_MOBILE']))
			$mobile = $senderInfo['SENDER_MOBILE'];
		if(!empty($senderInfo['SENDER_TELEPHONE']))
			$phone = $senderInfo['SENDER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
		    $mobile = $phone;
		}
		else if($mobile != $phone && $phone !='')
		{
		    $mobile = $mobile.'/'.$phone;
		}
		if(!empty($mobile))
			$senderAdd .= '<br>PHONE: '.$mobile;
		$pdf->SetFont($font_msyh, '', 7);
		$pdf->writeHTMLCell(75, 0, 3+$tmpX, 28+$tmpY, $senderAdd, 0, 1, 0, true, '', true);
		//Prime Expres logo
		$pdf->writeHTMLCell(0, 0, 78+$tmpX, 28+$tmpY, '<img src="/images/customprint/labelimg/eub-postexpres.jpg"  width="16mm" height="20mm" >', 0, 1, 0, true, '', true);
		//TO: 字样
		$pdf->SetFont($font_Arial, '', 20);
		$pdf->writeHTMLCell(30, 0, 4+$tmpX, 56+$tmpY, 'TO:', 0, 1, 0, true, '', true);
		//收件人信息
		//类似阿拉伯，收件地址是阿拉伯文，不可用$font_msyh字体
		if($order->consignee_country_code == 'SA')
		    $pdf->SetFont('dejavusans', '', 7.5);
		else
			$pdf->SetFont($font_msyh, '', 7.5);
		$pdf->writeHTMLCell(70, 0, 20+$tmpX, 50+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->writeHTMLCell(70, 0, 20+$tmpX, 53.5+$tmpY, $receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.(empty($receiverInfo['RECEIVER_PROVINCE']) ? $receiverInfo['RECEIVER_CITY'] : $receiverInfo['RECEIVER_PROVINCE']).' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
				strtoupper($receiverInfo['RECEIVER_COUNTRY_EN']), 0, 1, 0, true, '', true);
		//收件电话，自动缩小字体
		$phone = '';
		$mobile = '';
		if(!empty($receiverInfo['RECEIVER_MOBILE']))
			$mobile = $receiverInfo['RECEIVER_MOBILE'];
		if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
			$phone = $receiverInfo['RECEIVER_TELEPHONE'];
		if($mobile == '' && $phone != '')
		{
			$mobile = $phone;
		}
		else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 50)
		{
			$mobile = $mobile.'/'.$phone;
		}
		if(strlen($mobile) > 35)
		{
			$pdf->SetFont($font_msyh, '', 6);
		}
		//电话图标
		$pdf->writeHTMLCell(0, 0, 20+$tmpX, 68+$tmpY, '<img src="/images/customprint/labelimg/phone.jpg"  width="4mm" height="3mm" >', 0, 1, 0, true, '', true);
		//电话信息
		$pdf->writeHTMLCell(60, 0, 27+$tmpX, 68+$tmpY, $mobile, 0, 1, 0, true, '', true);
		//DELIVERY CONFIRMATION
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(80, 0, 25+$tmpX, 72+$tmpY, 'DELIVERY CONFIRMATION', 0, 1, 0, true, '', true);
		//投递需扫描图示
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 76+$tmpY, '<img src="/images/customprint/labelimg/EIBFrscan-2.jpg"  width="16mm" height="12mm" >', 0, 1, 0, true, '', true);
		//获取相关跟踪号信息
		$trackingInfo = PrintPdfHelper::getTrackingInfo($order);
		//跟踪号条形码
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 76+$tmpY, '60', 14, 0.6, $style, 'N');
		$pdf->SetFont($font_Arialbd, '', 10);
		$pdf->writeHTMLCell(50, 0, 35+$tmpX, 90+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		//无需签收图示
		$pdf->writeHTMLCell(0, 0, 80+$tmpX, 75+$tmpY, '<img src="/images/customprint/labelimg/no-signature.jpg"  width="15mm" height="15mm" >', 0, 1, 0, true, '', true);
	
		 
		//是否加打信息
		if(!empty($otherParams['printAddVal'])){
			$isSKU = 0;
			foreach ($otherParams['printAddVal'] as $v){
				if($v == 'addSku'){
					$isSKU = 1;
				}
			}
		
			//显示SKU信息
			if($isSKU == 1){
				$skuStr = '';
				//获取订单详情列表信息
				$itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
				foreach ($itemListDetailInfo['products'] as $product_key => $product)
				{
					$skuStr .= $product['SKU'].' * '.$product['QUANTITY'].';  ';
				}
				
				if($skuStr != ''){
				    if(strlen($skuStr)>75){
				        $skuStr = substr($skuStr, 0, 75);
				    }
					$pdf->SetFont($font_Arial, '', 8);
					$pdf->writeHTMLCell(90, 0, 2+$tmpX, 96+$tmpY, $skuStr, 0, 1, 0, true, '', true);
				}
			}
		}
		//******************内容   end****************************
	}
	
	/**
	 * 报关单
	 *
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getCnPostDeclare($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams)
	{
	    //******************字体   start********************
	    $font_msyh = 'msyh';      //微软雅黑
	    $font_Arial = 'Arial';    //Arial
	    $font_Arialbd = 'Arialbd';    //Arialbd
	    $font_cid0jp = 'times';    //阿拉伯字体
	    //******************字体   end**********************
	    
	    //******************框架   start****************************
	    //left边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //top边线条
	    $pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //right边线条
	    $pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //bottom边线条
	    $pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	     
	    //有国家简码、分拣码的收件国家
	    if(in_array($order->consignee_country_code, ['AU', 'RU', 'CA']))
	    {
    	    //上黑框left边线条
    	    $pdf->Line(32+$tmpX, 14+$tmpY, 32+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框top边线条
    	    $pdf->Line(32+$tmpX, 14+$tmpY, 40+$tmpX, 14+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框right边线条
    	    $pdf->Line(40+$tmpX, 14+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框bottom边线条
    	    $pdf->Line(32+$tmpX, 19+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    
    	    //下黑框left边线条
    	    $pdf->Line(32+$tmpX, 19+$tmpY, 32+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //下黑框top边线条
    	    $pdf->Line(32+$tmpX, 19+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //下黑框right边线条
    	    $pdf->Line(40+$tmpX, 19+$tmpY, 40+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //下黑框bottom边线条
    	    $pdf->Line(32+$tmpX, 24+$tmpY, 40+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    }
	    //美国只显示分拣码
	    else if(in_array($order->consignee_country_code, ['US']))
	    {
	    	//下黑框left边线条
	    	$pdf->Line(32+$tmpX, 19+$tmpY, 32+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    	//下黑框top边线条
	    	$pdf->Line(32+$tmpX, 19+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    	//下黑框right边线条
	    	$pdf->Line(40+$tmpX, 19+$tmpY, 40+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    	//下黑框bottom边线条
	    	$pdf->Line(32+$tmpX, 24+$tmpY, 40+$tmpX, 24+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    }
	    //英国没有收件国简码
	    else if(!in_array($order->consignee_country_code, ['GB', 'UK']))
        {
        	//上黑框left边线条
    	    $pdf->Line(32+$tmpX, 14+$tmpY, 32+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框top边线条
    	    $pdf->Line(32+$tmpX, 14+$tmpY, 40+$tmpX, 14+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框right边线条
    	    $pdf->Line(40+$tmpX, 14+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    	    //上黑框bottom边线条
    	    $pdf->Line(32+$tmpX, 19+$tmpY, 40+$tmpX, 19+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        }
        
	    //寄件人下划线
	    $pdf->Line(2+$tmpX, 48+$tmpY, 49+$tmpX, 48+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(2+$tmpX, 52+$tmpY, 49+$tmpX, 52+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //收件信息top边线条
	    $pdf->Line(49+$tmpX, 27+$tmpY, 98+$tmpX, 27+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //收件信息left边线条
	    $pdf->Line(49+$tmpX, 27+$tmpY, 49+$tmpX, 56+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    
	    //物品申报信息，横线
	    $pdf->Line(2+$tmpX, 56+$tmpY, 98+$tmpX, 56+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(2+$tmpX, 60+$tmpY, 98+$tmpX, 60+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(2+$tmpX, 77+$tmpY, 98+$tmpX, 77+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(2+$tmpX, 82+$tmpY, 98+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    //物品申报信息，竖线
	    $pdf->Line(8+$tmpX, 56+$tmpY, 8+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(14+$tmpX, 56+$tmpY, 14+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(62+$tmpX, 56+$tmpY, 62+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(70+$tmpX, 56+$tmpY, 70+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	    $pdf->Line(82+$tmpX, 56+$tmpY, 82+$tmpX, 82+$tmpY, $style=array('width' => 0.35, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	     
	    //******************框架   end****************************
	    
	    //******************内容   start****************************
	    //获取收件人信息
	    $receiverInfo = PrintPdfHelper::getReceiverInfo($order);
	    //中国邮政logo
	    $pdf->writeHTMLCell(0, 0, 4+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="27mm" height="9mm" >', 0, 1, 0, true, '', true);
	    //IMPORTANT ： The item/parcel may be opened offically.Please print in English.
	    $pdf->SetFont($font_Arial, '', 5.8);
	    $pdf->writeHTMLCell(30, 0, 2+$tmpX, 13+$tmpY, 'IMPORTANT:<br>The item/parcel may be<br>opened offically.<br>Please print in English.', 0, 1, 0, true, '', true);
	    //国家简码
	    //英国没有收件国简码
	    if(!in_array($order->consignee_country_code, ['GB', 'UK', 'US']))
	    {
    	    $pdf->SetFont($font_Arial, '', 12);
    	    $pdf->writeHTMLCell(10, 0, 32+$tmpX, 14+$tmpY, $order->consignee_country_code, 0, 1, 0, true, '', true);
	    }
	    //有分拣码的收件国家，显示分拣码
	    if(in_array($order->consignee_country_code, ['US', 'AU', 'RU', 'CA']))
	    {
    	    $receiver_country_en_num = PrintPdfHelper::getEubSortingYardsByCountry($order->consignee_country_code, $receiverInfo['RECEIVER_ZIPCODE']);
    	    $pdf->SetFont($font_Arial, '', 12);
    	    $pdf->writeHTMLCell(8, 0, 32+$tmpX, 19+$tmpY, $receiver_country_en_num, 0, 1, 0, true, 'C', true);
	    }
	    //获取相关跟踪号信息
	    $trackingInfo = PrintPdfHelper::getTrackingInfo($order);
	    //跟踪号条形码
	    $pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 45+$tmpX, 5+$tmpY, '50', 14, 0.6, $style, 'N');
	    $pdf->SetFont($font_Arial, '', 10);
	    $pdf->writeHTMLCell(45, 0, 60+$tmpX, 20+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
	    //获取寄件人信息
	    $senderInfo = PrintPdfHelper::getSenderInfo($order);
	    //寄件信息
	    $senderAdd = $senderInfo['SENDER_NAME'];
	    if(!empty($senderInfo['SENDER_COMPANY_NAME']))
	    	$senderAdd .= '<br>'.$senderInfo['SENDER_COMPANY_NAME'];
	    if(!empty($senderInfo['SENDER_ADDRESS']))
	    	$senderAdd .= '<br>'.$senderInfo['SENDER_ADDRESS'];
	    $senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE']).' '.$senderInfo['SENDER_ZIPCODE'];
	    /*
	    if(!empty($senderInfo['SENDER_COUNTRY_EN']))
	    	$senderAdd .= '<br>'.strtoupper($senderInfo['SENDER_COUNTRY_EN']);
	    */
	    
	    $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(47, 0, 2+$tmpX, 25+$tmpY, 'FROM:'.$senderAdd, 0, 1, 0, true, '', true);
	    //寄件电话，自动缩小字体
	    $sphone = '';
	    $smobile = '';
	    if(!empty($senderInfo['SENDER_TELEPHONE']))
	    	$sphone = $senderInfo['SENDER_TELEPHONE'];
	    if(!empty($senderInfo['SENDER_MOBILE']))
	    	$smobile = $senderInfo['SENDER_MOBILE'];
	    if($smobile == '' && $sphone != '')
	    {
	    	$smobile = $sphone;
	    }
	    else if($sphone != '' && $smobile != $sphone && strlen($smobile.$sphone) > 0 && strlen($smobile.$sphone) <= 30)
	    {
	    	$smobile = $smobile.'/'.$sphone;
	    }
	    if(strlen($smobile) > 22)
	    {
	    	$pdf->SetFont($font_msyh, '', 6);
	    }
	    $pdf->writeHTMLCell(45, 0, 2+$tmpX, 45+$tmpY, 'PHONE: '.$smobile, 0, 1, 0, true, '', true);
	    //收件人信息
	    $receiverAdd = 'SHIP TO: '.$receiverInfo['RECEIVER_NAME'].'<br>'.$receiverInfo['RECEIVER_ADDRESS'].'<br>'.
	    		$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' ';
	    //当州为空时，以城市代替
	    if(empty($receiverInfo['RECEIVER_PROVINCE']))
	        $receiverAdd .= $receiverInfo['RECEIVER_CITY'];
	    else
	        $receiverAdd .= $receiverInfo['RECEIVER_PROVINCE'];
	    //英国无论简码是什么，英文都默认为UNITED KINGDOM
	    if(in_array($order->consignee_country_code, ['GB', 'UK']))
	        $receiverAdd .= '<br>UNITED KINGDOM '.$receiverInfo['RECEIVER_ZIPCODE'];
	    else 
	    {
	        $receiverAdd .= ' '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>';
    	    if($order->consignee_country_code == 'US')
    	        $receiverAdd .= 'UNITED STATES OF AMERICA ';
    	    else
    	        $receiverAdd .= strtoupper($receiverInfo['RECEIVER_COUNTRY_EN']).' ';
	    }
	    //美国，收件信息，全部大写
	    if($order->consignee_country_code == 'US')
	        $receiverAdd = strtoupper($receiverAdd);
	    
	    //类似阿拉伯，收件地址是阿拉伯文，不可用$font_msyh字体
	    if($order->consignee_country_code == 'SA')
	        $pdf->SetFont('dejavusans', '', 7);
	    else 
	        $pdf->SetFont($font_msyh, '', 7);
	    $pdf->writeHTMLCell(50, 0, 49+$tmpX, 28+$tmpY, $receiverAdd, 0, 1, 0, true, '', true);
	    //收件电话，自动缩小字体
	    $phone = '';
	    $mobile = '';
	    if(!empty($receiverInfo['RECEIVER_MOBILE']))
	    	$mobile = $receiverInfo['RECEIVER_MOBILE'];
	    if(!empty($receiverInfo['RECEIVER_TELEPHONE']))
	    	$phone = $receiverInfo['RECEIVER_TELEPHONE'];
	    if($mobile == '' && $phone != '')
	    {
	    	$mobile = $phone;
	    }
	    else if($phone != '' && $mobile != $phone && strlen($mobile.$phone) > 0 && strlen($mobile.$phone) <= 30)
	    {
	    	$mobile = $mobile.'/'.$phone;
	    }
	    if(strlen($mobile) > 22)
	    {
	    	$pdf->SetFont($font_msyh, '', 6);
	    }
	    $pdf->writeHTMLCell(50, 0, 49+$tmpX, 53+$tmpY, 'PHONE: '.$mobile, 0, 1, 0, true, '', true);
	    //Fees(US $): 字样、Certificate No.字样
	    $pdf->SetFont($font_Arial, '', 7);
	    $pdf->writeHTMLCell(20, 0, 2+$tmpX, 49+$tmpY, 'Fees(US $):', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(20, 0, 2+$tmpX, 53+$tmpY, 'Certificate No.', 0, 1, 0, true, '', true);
	    //I certify the particulars given........
	    $pdf->SetFont($font_Arial, '', 5);
	    $pdf->writeHTMLCell(95, 0, 2+$tmpX, 82+$tmpY, 'I certify the particulars given in this customs declaration are correct .Thisitem does not contain
            any dangerous article,or atricles prohibited by legislation or by postal or customs regulations. I have met all applicable export filing requirements under the Foreign Trade Regulations.', 0, 1, 0, true, '', true);
	    //Sender’s Signature & Date Signed 字样
	    $pdf->SetFont($font_Arialbd, '', 7.5);
	    $pdf->writeHTMLCell(60, 0, 2+$tmpX, 91+$tmpY, 'Sender’s Signature & Date Signed', 0, 1, 0, true, '', true);
	    //CN22 字样
	    $pdf->SetFont($font_Arialbd, '', 15);
	    $pdf->writeHTMLCell(20, 0, 82+$tmpX, 89+$tmpY, 'CN22', 0, 1, 0, true, '', true);
	    //明细列名
	    $pdf->SetFont($font_Arial, '', 6.5);
	    $pdf->writeHTMLCell(20, 0, 2.5+$tmpX, 56.5+$tmpY, 'No', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(20, 0, 8.5+$tmpX, 56.5+$tmpY, 'Qty', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(40, 0, 25+$tmpX, 56.5+$tmpY, 'Description of Contents', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(20, 0, 63+$tmpX, 56.5+$tmpY, 'Kg.', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(20, 0, 70.5+$tmpX, 56.5+$tmpY, 'Val(US$)', 0, 1, 0, true, '', true);
	    $pdf->writeHTMLCell(20, 0, 82.5+$tmpX, 56.5+$tmpY, 'Goods Origi', 0, 1, 0, true, '', true);
	    //获取订单详情列表信息
	    $itemListDetailInfo = PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
	    //输出报关信息
	    $pdf->SetFont($font_msyh, '', 7);
	    //Total Groww Weight(Kg.): 字样
	    $pdf->writeHTMLCell(40, 0, 20+$tmpX, 78+$tmpY, 'Total Groww Weight(Kg.):', 0, 1, 0, true, '', true);
	    //合计
	    $pdf->writeHTMLCell(9, 0, 62+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, 'C', true);
	    $pdf->writeHTMLCell(12, 0, 70+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, 'R', true);
	    //明细信息
	    $amountQty = 0;   //合计数量
	    $p_Y = $tmpY;
	    $count = 5;
	    foreach ($itemListDetailInfo['products'] as $product_key => $product)
	    {	
	        //只显示5条
	        if($count > 0)
	        {
        		$pdf->writeHTMLCell(5, 0, 3+$tmpX, 60+$p_Y, $product['SKUAUTOID'], 0, 1, 0, true, 'C', true);
        		$pdf->writeHTMLCell(7, 0, 8+$tmpX, 60+$p_Y, $product['QUANTITY'], 0, 1, 0, true, 'C', true);
        		$pdf->writeHTMLCell(50, 0, 15+$tmpX, 60+$p_Y, $product['DECLARE_NAME_EN'].' '.$product['DECLARE_NAME_CN'], 0, 1, 0, true, '', true);
        		$pdf->writeHTMLCell(9, 0, 62+$tmpX, 60+$p_Y, $product['PROD_WEIGHT'], 0, 1, 0, true, 'C', true);
        		$pdf->writeHTMLCell(12, 0, 70+$tmpX, 60+$p_Y, $product['PRICE'], 0, 1, 0, true, 'R', true);
        		$pdf->writeHTMLCell(15, 0, 83+$tmpX, 60+$p_Y, 'CN', 0, 1, 0, true, 'C', true);
        		
        		//需要换行
        		if(strlen($product['DECLARE_NAME_EN'].' '.$product['DECLARE_NAME_CN']) > 50)
        		{
        		    $p_Y += 3;
        		    $count--;;
        		}
        		
        		$p_Y += 3;
        		$count--;
	        }
    		$amountQty += $product['QUANTITY'];
	    }
	    //合计数量
	    $pdf->writeHTMLCell(7, 0, 8+$tmpX, 78+$tmpY, $amountQty, 0, 1, 0, true, 'C', true);
	    
	    //******************内容   end****************************
	}
}



