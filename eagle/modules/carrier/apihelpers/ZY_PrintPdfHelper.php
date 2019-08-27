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

class ZY_PrintPdfHelper{
	//中国邮政国际小包
	public static function getZYGJXB($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
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
		
		//中国邮政图片
		$pdf->writeHTMLCell(25,0, 3+$tmpX, 5+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg" >', 0, 1, 0, true, '', true);
		
		//文字航空 Small Packet BY Air
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(25, 0, 37+$tmpX, 3+$tmpY, '航空<br>Small Packet<br>BY Air', 0, 1, 0, true, 'C', true);
		
		//收件人国家
		$pdf->SetFont('msyhbd', '', 13);
		$pdf->writeHTMLCell(10, 0, 80+$tmpX, 6+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 0, 1, 0, true, 'C', true);
		
		//图片右边的线
		$pdf->Line(30+$tmpX, 2+$tmpY, 30+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件国家左边的线
		$pdf->Line(70+$tmpX, 2+$tmpY, 70+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//图片下面的线
		$pdf->Line(2+$tmpX, 15+$tmpY, 98+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//文字协议客户
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(17, 0, 2+$tmpX, 16+$tmpY, '协议客户:', 0, 1, 0, true, '', true);
		
		//发件人公司名
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(78, 0, 18+$tmpX, 16+$tmpY, (empty($senderInfo['SENDER_COMPANY_NAME'])?$senderInfo['SENDER_NAME']:$senderInfo['SENDER_COMPANY_NAME']), 0, 1, 0, true, '', true);
		
		//文字FROM
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(13, 0, 2+$tmpX, 20+$tmpY, 'FROM:', 0, 1, 0, true, '', true);
		
		//发件人地址
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$address=$senderInfo['SENDER_ADDRESS'].(empty($senderInfo['SENDER_AREA'])?'':' '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_CITY'])?'':' '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_PROVINCE'])?'':' '.$senderInfo['SENDER_PROVINCE']);
		$pdf->writeHTMLCell(84, 0, 13+$tmpX, 20+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//发件人地址下面的线
		$pdf->Line(2+$tmpX, 30+$tmpY, 98+$tmpX, 30+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字order
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(13, 0, 2+$tmpX, 31+$tmpY, 'Order:', 0, 1, 0, true, '', true);

		//订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(84, 0, 13+$tmpX, 31+$tmpY, $order['order_source_order_id'], 0, 1, 0, true, '', true);
		
		//订单号下面的线
		$pdf->Line(2+$tmpX, 35+$tmpY, 98+$tmpX, 35+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字To
		$pdf->SetFont('msyhbd', '', 16);
		$pdf->writeHTMLCell(12, 0, 2+$tmpX, 36+$tmpY, 'TO:', 0, 1, 0, true, '', true);
		
		//收件人
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(80, 0, 14+$tmpX, 36+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		
		//收件人地址
		$address=(empty($receiverInfo['RECEIVER_COMPANY'])?'':$receiverInfo['RECEIVER_COMPANY'].';').$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_CN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_CN']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']);
		if(strlen($address)>200)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(80, 0, 14+$tmpX, 40+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//文字Zip
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(8, 0, 2+$tmpX, 56+$tmpY, 'Zip', 0, 1, 0, true, '', true);
		
		//收件人邮编
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 10+$tmpX, 56+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//文字Tel
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(7, 0, 41+$tmpX, 56+$tmpY, 'Tel', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(50, 0, 48+$tmpX, 56+$tmpY, $receiverInfo['RECEIVER_TELEPHONE'].' / '.$receiverInfo['RECEIVER_MOBILE'], 0, 1, 0, true, '', true);
		
		//收件人信息下面的线
		$pdf->Line(2+$tmpX, 60+$tmpY, 98+$tmpX, 60+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(16, 0, 2+$tmpX, 61+$tmpY, '退件单位:', 0, 1, 0, true, '', true);
		
		//退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(81, 0, 16+$tmpX, 61+$tmpY, $senderInfo['SENDER_RETURNGOODS'], 0, 1, 0, true, '', true);
		
		//退件单位下面的线
		$pdf->Line(2+$tmpX, 65+$tmpY, 98+$tmpX, 65+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字R
		$pdf->SetFont('msyhbd', '', 40);
		$pdf->writeHTMLCell(16, 0, 4+$tmpX, 72+$tmpY, 'R', 0, 1, 0, true, '', true);
				
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 22+$tmpX, 68+$tmpY, '75', 16.5, 0.4, $style, 'N');
		
		//跟踪号
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(75, 0, 22+$tmpX, 83+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
	}
	
	//中国邮政国际小包(报关单)
	public static function getZYGJXB_BG($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1); //字体行距
		
		$msyhbd='msyhbd';
	
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//文字报关单 CUSTOMS DECLARATION
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(50, 0, 2+$tmpX, 2.5+$tmpY, '报关单 CUSTOMS DECLARATION', 0, 1, 0, true, '', true);
		
		//文字(可径行开拆 May be opened officially)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(55, 0, 2+$tmpX, 5.5+$tmpY, '(可径行开拆 May be opened officially)', 0, 1, 0, true, '', true);
		
		//文字CN22
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(10, 0, 85+$tmpX, 2.5+$tmpY, 'CN22', 0, 1, 0, true, '', true);
		
		//收件人邮编
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(19, 0, 79+$tmpX, 5.5+$tmpY, '邮 '.$receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
	
		//头部信息下面的虚线
		$pdf->Line(2+$tmpX, 9+$tmpY, 98+$tmpX, 9+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(0, 0, 0)));
		
		//文字中国邮政
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(14, 0, 2+$tmpX, 9.5+$tmpY, '中国邮政', 0, 1, 0, true, '', true);
		
		//文字CHINA POST
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(20, 0, 2+$tmpX, 12.5+$tmpY, 'CHINA POST', 0, 1, 0, true, '', true);
		
		//文字请先阅读背面的注意事项
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(35, 0, 62+$tmpX, 9.5+$tmpY, '请先阅读背面的注意事项', 0, 1, 0, true, '', true);
		
		//文字See instruction on the back
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(40, 0, 58+$tmpX, 12.5+$tmpY, 'See instruction on the back', 0, 1, 0, true, '', true);
		
		//中国邮政下面的线
		$pdf->Line(2+$tmpX, 16+$tmpY, 98+$tmpX, 16+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//复选框Gift
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(6, 0, 5+$tmpX, 15.5+$tmpY,'□', 0, 1, 0, true, '', true);
		
		//复选框礼品 Gift
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(30, 0, 9+$tmpX, 17+$tmpY,'礼品 Gift', 0, 1, 0, true, '', true);
		
		//复选框礼品 Gift默认勾
		$pdf->SetFont($otherParams['pdfFont'], '', 12);
		$pdf->writeHTMLCell(5, 0, 5.5+$tmpX, 16+$tmpY,'√', 0, 1, 0, true, '', true);
		
		//复选框Commercial sample
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(6, 0, 40+$tmpX, 15.5+$tmpY,'□', 0, 1, 0, true, '', true);
		
		//复选框商品货样 Commercial sample
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(50, 0, 44+$tmpX, 17+$tmpY,'商品货样 Commercial sample', 0, 1, 0, true, '', true);
		
		//复选框Documents
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(6, 0, 5+$tmpX, 19.5+$tmpY,'□', 0, 1, 0, true, '', true);
		
		//复选框文件 Documents
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(30, 0, 9+$tmpX, 21+$tmpY,'文件 Documents', 0, 1, 0, true, '', true);
		
		//复选框Other
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(6, 0, 40+$tmpX, 19.5+$tmpY,'□', 0, 1, 0, true, '', true);
		
		//复选框其他 Other
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(30, 0, 44+$tmpX, 21+$tmpY,'其他 Other', 0, 1, 0, true, '', true);
		
		//复选框下面的线
		$pdf->Line(2+$tmpX, 25+$tmpY, 98+$tmpX, 25+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字内件详细名称和数量.....
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(61, 0, 2+$tmpX, 25.5+$tmpY, '内件详细名称和数量<br>Grunt and details description of contents', 0, 1, 0, true, '', true);
		
		//文字总量(千克).....
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(18, 0, 63+$tmpX, 25.5+$tmpY, '总量(千克)<br>W(kg)', 0, 1, 0, true, 'C', true);
		
		//文字价值.....
		$pdf->SetFont($msyhbd, '', 8);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 25.5+$tmpY, '价值<br>V(USD)', 0, 1, 0, true, 'C', true);
		
		//明细头部下面的线
		$pdf->Line(2+$tmpX, 32+$tmpY, 98+$tmpX, 32+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量左边线条
		$pdf->Line(63+$tmpX, 25+$tmpY, 63+$tmpX, 32+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量右边线条
		$pdf->Line(81+$tmpX, 25+$tmpY, 81+$tmpX, 32+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息
		//超过10个的只显示10个
		$count=1;
		$jiange=3.7;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
// 			if($count>10)
// 				break;
			if(strlen($vitems['DECLARE_NAME_CN'])>80)
				$jiange=8;
			else if(strlen($vitems['DECLARE_NAME_CN'])>40)
				$jiange=5;
			//英文报关名*数量
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(61, 0, 2+$tmpX, 33+$tmpY+$jiange*$key, $vitems['DECLARE_NAME_EN'].' *'.$vitems['QUANTITY'], 0, 1, 0, true, '', true);
			
			//sku
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(18, 0, 63+$tmpX, 33+$tmpY+$jiange*$key, $vitems['WEIGHT'], 0, 1, 0, true, 'C', true);
			
			//价值
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(16, 0, 81+$tmpX, 33+$tmpY+$jiange*$key, $vitems['AMOUNT_PRICE'], 0, 1, 0, true, 'C', true);
			
			$count++;
		}
		//明细下面的线
		$pdf->Line(2+$tmpX, 70+$tmpY, 98+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//总重量左边线条
		$pdf->Line(63+$tmpX, 70+$tmpY, 63+$tmpX, 83+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//总重量右边线条
		$pdf->Line(81+$tmpX, 70+$tmpY, 81+$tmpX, 83+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字协调系统税则号列和货物原产...
		$pdf->SetFont($msyhbd, '', 7);
		$pdf->writeHTMLCell(61, 0, 2+$tmpX, 70.5+$tmpY, '协调系统税则号列和货物原产地(只对商品邮件填写)', 0, 1, 0, true, '', true);
		
		//文字总重量...
		$pdf->SetFont($msyhbd, '', 7);
		$pdf->writeHTMLCell(18, 0, 63+$tmpX, 70.5+$tmpY, '总重量<br>Total<br>Weight', 0, 1, 0, true, 'C', true);
		
		//文字总价值...
		$pdf->SetFont($msyhbd, '', 7);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 70.5+$tmpY, '总价值<br>Total<br>val', 0, 1, 0, true, 'C', true);
		
		//文字For commerical items only!...
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(61, 0, 2+$tmpX, 73.5+$tmpY, 'For commerical items only! HS straff number and country of origin of goods', 0, 1, 0, true, '', true);
		
		//总重量下面的线
		$pdf->Line(2+$tmpX, 79+$tmpY, 98+$tmpX, 79+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字CN
		$pdf->SetFont($msyhbd, '', 7);
		$pdf->writeHTMLCell(61, 0, 2+$tmpX, 79.5+$tmpY, 'CN', 0, 1, 0, true, 'C', true);
		
		//总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(18, 0, 63+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, 'C', true);
		
		//总价值
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, 'C', true);
		
		//商品信息下面的线
		$pdf->Line(2+$tmpX, 83+$tmpY, 98+$tmpX, 83+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字我保证上述申报准确无误......
		$pdf->SetFont($msyhbd, '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 84.5+$tmpY, '我保证上述申报准确无误，本函件内未装寄法律或邮政和海关规章禁止寄递的任何危险物品。', 0, 1, 0, true, '', true);
		
		//底部大段文字 
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 87+$tmpY, 'I,the undersigned,whose name and address are given on the item,certify that the particulars given in this declaration are correct and that this item does not contain any dangerous article or acticles prohibited by legislation or by postal or customs regulations.', 0, 1, 0, true, '', true);
		
		//文字Date and sender's signature:
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 94+$tmpY, 'Date and sender\'s signature:', 0, 1, 0, true, '', true);
		
	}

	//中国邮政挂号小包
	public static function getZYGHXB($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距

		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//中国邮政图片
		$pdf->writeHTMLCell(35, 0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/chinapost-4.jpg"  width="100" >', 0, 1, 0, true, '', true);
		
		//文字:Small Packet BY AIR
		$pdf->SetFont($otherParams['pdfFont'], '', 5.5);
		$pdf->writeHTMLCell(30, 0, 3+$tmpX, 13+$tmpY, 'Small Packet BY AIR', 0, 1, 0, true, 'C', true);
		
		//文字:R
		$pdf->SetFont('msyhbd', '', 24);
		$pdf->writeHTMLCell(10, 0, 35+$tmpX, 5+$tmpY, 'R', 0, 1, 0, true, 'C', true);
		
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 46+$tmpX, 5+$tmpY, '52', 16, 0.46, $style, 'N');
		
		//文字:跟踪号
// 		$pdf->SetFont('msyhbd', '', 10);
// 		$pdf->writeHTMLCell(52, 0, 46+$tmpX, 15+$tmpY, $trackingInfo['tracking_number'], 1, 1, 0, true, 'C', true);
		
		//文字:国家简码
		$NumCode=PrintApiHelper::getNumCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		if(empty($NumCode)){
			$pdf->SetFont($otherParams['pdfFont'], '', 14);
			$pdf->writeHTMLCell(10, 4.5, 14+$tmpX, 16.5+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'].$NumCode, 1, 1, 0, true, 'C', true);
		}
		else{
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(11, 4.5, 14+$tmpX, 16.5+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'].$NumCode, 1, 1, 0, true, 'C', true);
		}
		
		//收件人地址上面线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 98+$tmpX, 20+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人地址左边线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 40+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//发件人地址信息
		$address=$senderInfo['SENDER_NAME'].(empty($senderInfo['SENDER_ADDRESS'])?'':'&nbsp;&nbsp;'.$senderInfo['SENDER_ADDRESS']).(empty($senderInfo['SENDER_PROVINCE'])?'':', '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_CITY'])?'':', '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_AREA'])?'':', '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_COUNTRY_EN_AB'])?'':', '.$senderInfo['SENDER_COUNTRY_EN_AB']).(empty($senderInfo['SENDER_ZIPCODE'])?'':', '.$senderInfo['SENDER_ZIPCODE']);
		if(strlen($address)>153)
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
		else
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 24+$tmpY, '<span style="font-size:6">From:</span>'.$address, 0, 1, 0, true, '', true);
		
		//文字:Ship To:
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(12, 0, 40+$tmpX, 21+$tmpY, 'Ship To:', 0, 1, 0, true, '', true);
		
		//收件人
		$pdf->SetFont('msyhbd', '', 7);
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTMLCell(46, 0, 52+$tmpX, 21+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//收件人地址信息
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN_AB'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN_AB']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':' '.$receiverInfo['RECEIVER_ZIPCODE']);
		if(strlen($address)>150)
			$pdf->SetFont('msyhbd', '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont('msyhbd', '', 8);
		else if(strlen($address)>50)
			$pdf->SetFont('msyhbd', '', 10);
		else
			$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(58, 0, 40+$tmpX, 25+$tmpY, $address, 0, 1, 0, true, '', true);

		//文字:phone(收件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 47+$tmpY, 'Phone:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(23, 0, 48+$tmpX, 45+$tmpY, $receiverInfo['RECEIVER_MOBILE'].'<br/>'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//国家+拣码
		$qh=PrintPdfHelper::getWishGhAreaCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(27, 0, 71+$tmpX, 46+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'].'&nbsp;&nbsp;'.'<span style="font-size:12;">'.$qh.'</span>', 0, 1, 0, true, 'C', true);

		//文字:phone(发件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(9, 0, 2+$tmpX, 41.5+$tmpY, 'Phone:', 0, 1, 0, true, '', true);
		
		//发件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(30, 0, 10+$tmpX, 40+$tmpY, $senderInfo['SENDER_MOBILE'].'<br/>'.$senderInfo['SENDER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//发件人电话下面线条
		$pdf->Line(2+$tmpX, 46+$tmpY, 40+$tmpX, 46+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:中邮咸宁仓
		$pdf->SetFont('msyhbd', '', 12);
// 		$cangku=explode('--',$shippingService['shipping_method_name']);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 46.5+$tmpY, (isset($shippingService['carrier_params']['ZYCangku'])?$shippingService['carrier_params']['ZYCangku']:''), 0, 1, 0, true, '', true);
		
		//商品信息上面线条
		$pdf->Line(2+$tmpX, 52+$tmpY, 98+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:Description of Contents
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(63, 0, 2+$tmpX, 52+$tmpY, 'Description of Contents', 0, 1, 0, true, 'C', true);
		
		//文字:g
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 53.5+$tmpY, 'kg.', 0, 1, 0, true, 'C', true);
		
		//文字:Val(US $)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 53.5+$tmpY, 'Vals(USD $)', 0, 1, 0, true, 'C', true);
		
		//重量左边的线条
		$pdf->Line(65+$tmpX, 52+$tmpY, 65+$tmpX, 84+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量右边的线条
		$pdf->Line(80+$tmpX, 52+$tmpY, 80+$tmpX, 84+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item内容列上面的线条
		$pdf->Line(2+$tmpX, 59+$tmpY, 98+$tmpX, 59+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息
		//超过3个只显示3个
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>4){
				break;
			}
			//商品名
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(63, 0, 2+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['DECLARE_NAME_EN'].' *'.$vitems['QUANTITY'], 0, 1, 0, true, '', true);
		
			//重量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(15, 0, 65+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['WEIGHT'], 0, 1, 0, true, '', true);
		
			//金额
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(18, 0, 80+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
			$count++;
		}
		//文字:Total Gross Weight(kg)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(63, 0, 2+$tmpX, 79.5+$tmpY, 'Total Gross Weight(Kg)', 0, 1, 0, true, '', true);
		
		//商品总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//商品总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
		//文字Total Gross Weight(g)上面的线条
		$pdf->Line(2+$tmpX, 78+$tmpY, 98+$tmpX, 78+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息下面的线条
		$pdf->Line(2+$tmpX, 84+$tmpY, 98+$tmpX, 84+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:底部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 85+$tmpY, 'I certify that the particulars given in this declaration are correct and this item does not contain any dangerous articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
		
		//文字:Sender's signiture& Data Signed:
		$pdf->SetFont('msyhbd', '', 6);
		$pdf->writeHTMLCell(42, 0, 2+$tmpX, 94.5+$tmpY, 'Sender\'s signiture& Data Signed:', 0, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(12, 0, 65+$tmpX, 92+$tmpY, 'CN22', 0, 1, 0, true, '', true);
	}

	//中国邮政平常小包 +
	public static function getZYPCXB($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//中国邮政图片
		$pdf->writeHTMLCell(35, 0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/chinapost-4.jpg"  width="100" >', 0, 1, 0, true, '', true);
		
		//文字:Small Packet BY AIR
		$pdf->SetFont($otherParams['pdfFont'], '', 5.5);
		$pdf->writeHTMLCell(30, 0, 3+$tmpX, 13+$tmpY, 'Small Packet BY AIR', 0, 1, 0, true, 'C', true);
		
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 38+$tmpX, 3+$tmpY, '60', 16, 0.46, $style, 'N');
		
		//文字:跟踪号
// 				$pdf->SetFont('msyhbd', '', 10);
// 				$pdf->writeHTMLCell(60, 0, 38+$tmpX, 15+$tmpY, $trackingInfo['tracking_number'], 1, 1, 0, true, 'C', true);
		//文字:untracked 平小包
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(58, 0, 40+$tmpX, 16+$tmpY, 'untracked 平小包', 0, 1, 0, true, '', true);
		
		//文字:国家简码
		$NumCode=PrintApiHelper::getNumCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		if(empty($NumCode)){
			$pdf->SetFont($otherParams['pdfFont'], '', 14);
			$pdf->writeHTMLCell(10, 4.5, 14+$tmpX, 16.5+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'].$NumCode, 1, 1, 0, true, 'C', true);
		}
		else{
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(11, 4.5, 14+$tmpX, 16.5+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'].$NumCode, 1, 1, 0, true, 'C', true);
		}
		
		//收件人地址上面线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 98+$tmpX, 20+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人地址左边线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 40+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//发件人地址信息
		$address=$senderInfo['SENDER_NAME'].(empty($senderInfo['SENDER_ADDRESS'])?'':'&nbsp;&nbsp;'.$senderInfo['SENDER_ADDRESS']).(empty($senderInfo['SENDER_PROVINCE'])?'':', '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_CITY'])?'':', '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_AREA'])?'':', '.$senderInfo['SENDER_AREA']).(empty($senderInfo['SENDER_COUNTRY_EN_AB'])?'':', '.$senderInfo['SENDER_COUNTRY_EN_AB']).(empty($senderInfo['SENDER_ZIPCODE'])?'':', '.$senderInfo['SENDER_ZIPCODE']);
		if(strlen($address)>153)
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
		else
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 24+$tmpY, '<span style="font-size:6">From:</span>'.$address, 0, 1, 0, true, '', true);
		
		//文字:Ship To:
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(12, 0, 40+$tmpX, 21+$tmpY, 'Ship To:', 0, 1, 0, true, '', true);
		
		//收件人
		$pdf->SetFont('msyhbd', '', 7);
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTMLCell(46, 0, 52+$tmpX, 21+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//收件人地址信息
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN_AB'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN_AB']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']).(empty($receiverInfo['RECEIVER_ZIPCODE'])?'':' '.$receiverInfo['RECEIVER_ZIPCODE']);
		if(strlen($address)>150)
			$pdf->SetFont('msyhbd', '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont('msyhbd', '', 8);
		else if(strlen($address)>50)
			$pdf->SetFont('msyhbd', '', 10);
		else
			$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(58, 0, 40+$tmpX, 25+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//文字:phone(收件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 47+$tmpY, 'Phone:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(23, 0, 48+$tmpX, 45+$tmpY, $receiverInfo['RECEIVER_MOBILE'].'<br/>'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//国家+拣码
		$qh=PrintPdfHelper::getWishPyAreaCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(27, 0, 71+$tmpX, 46+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'].'&nbsp;&nbsp;'.'<span style="font-size:12;">'.$qh.'</span>', 0, 1, 0, true, 'C', true);
		
		//文字:phone(发件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(9, 0, 2+$tmpX, 41.5+$tmpY, 'Phone:', 0, 1, 0, true, '', true);
		
		//发件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(30, 0, 10+$tmpX, 40+$tmpY, $senderInfo['SENDER_MOBILE'].'<br/>'.$senderInfo['SENDER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//发件人电话下面线条
		$pdf->Line(2+$tmpX, 46+$tmpY, 40+$tmpX, 46+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:中邮咸宁仓
		$pdf->SetFont('msyhbd', '', 12);
// 		$cangku=explode('--',$shippingService['shipping_method_name']);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 46.5+$tmpY, (isset($shippingService['carrier_params']['ZYCangku'])?$shippingService['carrier_params']['ZYCangku']:''), 0, 1, 0, true, '', true);
		
		//商品信息上面线条
		$pdf->Line(2+$tmpX, 52+$tmpY, 98+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:Description of Contents
		$pdf->SetFont($otherParams['pdfFont'], '', 14);
		$pdf->writeHTMLCell(63, 0, 2+$tmpX, 52+$tmpY, 'Description of Contents', 0, 1, 0, true, 'C', true);
		
		//文字:g
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 53.5+$tmpY, 'kg.', 0, 1, 0, true, 'C', true);
		
		//文字:Val(US $)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 53.5+$tmpY, 'Vals(USD $)', 0, 1, 0, true, 'C', true);
		
		//重量左边的线条
		$pdf->Line(65+$tmpX, 52+$tmpY, 65+$tmpX, 84+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量右边的线条
		$pdf->Line(80+$tmpX, 52+$tmpY, 80+$tmpX, 84+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item内容列上面的线条
		$pdf->Line(2+$tmpX, 59+$tmpY, 98+$tmpX, 59+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息
		//超过3个只显示3个
		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>4){
				break;
			}
			//商品名
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(63, 0, 2+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['DECLARE_NAME_EN'].' *'.$vitems['QUANTITY'], 0, 1, 0, true, '', true);
		
			//重量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(15, 0, 65+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['WEIGHT'], 0, 1, 0, true, '', true);
		
			//金额
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(18, 0, 80+$tmpX, 59.5+$tmpY+4.4*$key, $vitems['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
			$count++;
		}
		//文字:Total Gross Weight(kg)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(63, 0, 2+$tmpX, 79.5+$tmpY, 'Total Gross Weight(Kg)', 0, 1, 0, true, '', true);
		
		//商品总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//商品总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 79.5+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
		//文字Total Gross Weight(g)上面的线条
		$pdf->Line(2+$tmpX, 78+$tmpY, 98+$tmpX, 78+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息下面的线条
		$pdf->Line(2+$tmpX, 84+$tmpY, 98+$tmpX, 84+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:底部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 85+$tmpY, 'I certify that the particulars given in this declaration are correct and this item does not contain any dangerous articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
		
		//文字:Sender's signiture& Data Signed:
		$pdf->SetFont('msyhbd', '', 6);
		$pdf->writeHTMLCell(42, 0, 2+$tmpX, 94.5+$tmpY, 'Sender\'s signiture& Data Signed:', 0, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(12, 0, 65+$tmpX, 92+$tmpY, 'CN22', 0, 1, 0, true, '', true);
	}

	//中邮一体化挂号(含退件单位)
	public static function getZYGHYTH($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//中国邮政图片
		$pdf->writeHTMLCell(35, 0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="100" >', 0, 1, 0, true, '', true);
		
		//文字:Small Packet BY AIR
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(30, 0, 5+$tmpX, 12+$tmpY, 'Small Packet BY AIR', 0, 1, 0, true, 'C', true);
		
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 35+$tmpX, 2+$tmpY, '63', 19, 0.46, $style, 'N');
		
		//文字:跟踪号
// 				$pdf->SetFont('msyhbd', '', 10);
// 				$pdf->writeHTMLCell(63, 0, 35+$tmpX, 15+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字:国家简码
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(8, 4.5, 10+$tmpX, 16+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 1, 1, 0, true, 'C', true);
		
		$NumCode=PrintApiHelper::getNumCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		if(!empty($NumCode)){
			//文字:国家分拣码
			$pdf->SetFont('msyhbd', '', 10);
			$pdf->writeHTMLCell(7, 4.5, 18+$tmpX, 16+$tmpY, $NumCode, 1, 1, 0, true, 'C', true);
		}
				
		//收件人地址上面线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 98+$tmpX, 20+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人地址左边线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 40+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//发件人地址信息
		$address=$senderInfo['SENDER_NAME'].(empty($senderInfo['SENDER_ADDRESS'])?'':'&nbsp;&nbsp;'.$senderInfo['SENDER_ADDRESS']).(empty($senderInfo['SENDER_PROVINCE'])?'':', '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_CITY'])?'':', '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_AREA'])?'':', '.$senderInfo['SENDER_AREA']).'<br><span style="font-size:7">Zip:'.$senderInfo['SENDER_ZIPCODE'].'</span><br><span style="font-size:7">TEL:'.$senderInfo['SENDER_TELEPHONE'].'/'.$senderInfo['SENDER_MOBILE'].'</span>';
		if(strlen($address)>153)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 24+$tmpY, '<span style="font-size:7">From:</span>'.$address, 0, 1, 0, true, '', true);
		
		//文字:Ship To:
		$pdf->SetFont('msyhbd', '', 8);
		$pdf->writeHTMLCell(14, 0, 40+$tmpX, 21+$tmpY, 'Ship To:', 0, 1, 0, true, '', true);
		
		//收件人
		$pdf->SetFont('msyhbd', '', 8);
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTMLCell(46, 0, 53+$tmpX, 21+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//收件人地址信息
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']);
		if(strlen($address)>150)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		else if(strlen($address)>50)
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
		else
			$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(58, 0, 40+$tmpX, 25+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//国家英文
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(35, 0, 40+$tmpX, 41+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN'], 0, 1, 0, true, '', true);
		
		//文字:Zip
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 44.5+$tmpY, 'Zip:', 0, 1, 0, true, '', true);
		
		//收件人邮编
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(35, 0, 46+$tmpX, 44.5+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//文字:phone(收件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 48+$tmpY, 'TEL:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(50, 0, 46+$tmpX, 48.2+$tmpY, $receiverInfo['RECEIVER_MOBILE'].'/'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//国家+拣码
		$qh=PrintPdfHelper::getWishGhAreaCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(27, 0, 73+$tmpX, 42+$tmpY, $qh.'&nbsp;'.$receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'R', true);
				
		//发件人电话下面线条
		$pdf->Line(2+$tmpX, 47+$tmpY, 40+$tmpX, 47+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:自编号
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(12, 0, 2+$tmpX, 48+$tmpY, '自编号:', 0, 1, 0, true, '', true);
		
		//自编号
		if(strlen($order->order_source_order_id)>22)
			$size=5;
		else if(strlen($order->order_source_order_id)>16)
			$size=6;
		else
			$size=8;
		$pdf->SetFont($otherParams['pdfFont'], '', $size);
		$pdf->writeHTMLCell(29, 0, 11+$tmpX, 48+$tmpY, $order->order_source_order_id, 0, 1, 0, true, '', true);   
		
		//文字:退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(14, 0, 2+$tmpX, 52+$tmpY, '退件单位:', 0, 1, 0, true, '', true);
		
		//退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(81, 0, 16+$tmpX, 52+$tmpY, $senderInfo['SENDER_RETURNGOODS'], 0, 1, 0, true, '', true);
		
		//退件单位下面的线
		$pdf->Line(2+$tmpX, 56+$tmpY, 98+$tmpX, 56+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息上面线条
		$pdf->Line(2+$tmpX, 52+$tmpY, 98+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//文字:No
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(8, 0, 2+$tmpX, 56+$tmpY, 'No', 0, 1, 0, true, '', true);
		
		//文字:Qty
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(9, 0, 10+$tmpX, 56+$tmpY, 'Qty', 0, 1, 0, true, '', true);
		
		//文字:Description of Contents
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(46, 0, 19+$tmpX, 56+$tmpY, 'Description of Contents', 0, 1, 0, true, '', true);
		
		//文字:kg
		$pdf->SetFont($otherParams['pdfFont'], '',10);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 56+$tmpY, 'Kg.', 0, 1, 0, true, '', true);
		
		//文字:Val(US $)
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 56+$tmpY, 'Val(US $)', 0, 1, 0, true, '', true);
		
		//No右边的线条
		$pdf->Line(10+$tmpX, 56+$tmpY, 10+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//数量右边的线条
		$pdf->Line(19+$tmpX, 56+$tmpY, 19+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量左边的线条
		$pdf->Line(65+$tmpX, 56+$tmpY, 65+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量右边的线条
		$pdf->Line(80+$tmpX, 56+$tmpY, 80+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item内容列上面的线条
		$pdf->Line(2+$tmpX, 61+$tmpY, 98+$tmpX, 61+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息
		//超过3个只显示3个
		$count=1;
		$totalqty=0;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>4){
				break;
			}
			//No
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(8, 0, 2+$tmpX, 61+$tmpY+4*$key, $vitems['SKUAUTOID'], 0, 1, 0, true, '', true);
			
			//数量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(9, 0, 10+$tmpX, 61+$tmpY+4*$key, $vitems['QUANTITY'], 0, 1, 0, true, '', true);
			
			//商品名
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(46, 0, 19+$tmpX, 61+$tmpY+4*$key, $vitems['DECLARE_NAME_EN'], 0, 1, 0, true, '', true);
		
			//重量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(15, 0, 65+$tmpX, 61+$tmpY+4*$key, $vitems['WEIGHT'], 0, 1, 0, true, '', true);
		
			//金额
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(18, 0, 80+$tmpX, 61+$tmpY+4*$key, $vitems['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
			$totalqty+=$vitems['QUANTITY'];
			$count++;
		}
		//商品总数量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(9, 0, 10+$tmpX, 78+$tmpY, $totalqty, 0, 1, 0, true, '', true);
		
		//文字:Total Gross Weight(kg)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(46, 0, 19+$tmpX, 78+$tmpY, 'Total Gross Weight(Kg.):', 0, 1, 0, true, '', true);
		
		//商品总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//商品总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
		//文字Total Gross Weight(g)上面的线条
		$pdf->Line(2+$tmpX, 78+$tmpY, 98+$tmpX, 78+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息下面的线条
		$pdf->Line(2+$tmpX, 82+$tmpY, 98+$tmpX, 82+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:底部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 83+$tmpY, '我保证上诉申报准确无误,本函件内未装寄法律或邮政和海关规章禁止寄递的任何危险物品。I,the undersigned, certify that the particulars given in this declaration are correct and this item does not contain any dangerous articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
		
		//文字:Sender's signiture& Data Signed:
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(44, 0, 2+$tmpX, 93+$tmpY, 'Sender\'s signiture& Data Signed:', 0, 1, 0, true, '', true);
		
		//签名
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(27, 0, 42+$tmpX, 93+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);

		//文字:已检视
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(13, 0, 70+$tmpX, 92+$tmpY, '已检视', 1, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(12, 0, 85+$tmpX, 92+$tmpY, 'CN22', 0, 1, 0, true, '', true);
	}
	
	//中邮一体化平邮(含退件单位)
	public static function getZYPYYTH($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//中国邮政图片
		$pdf->writeHTMLCell(35, 0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/chinapost-1.jpg"  width="100" >', 0, 1, 0, true, '', true);
		
		//文字:Small Packet BY AIR
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(30, 0, 5+$tmpX, 12+$tmpY, 'Small Packet BY AIR', 0, 1, 0, true, 'C', true);
		
		//文字:no tracking
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(13, 0, 36+$tmpX, 8+$tmpY, 'No<br>Tracking', 0, 1, 0, true, 'C', true);
		
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 50+$tmpX, 2+$tmpY, '47', 19, 0.5, $style, 'N');
		
		//文字:跟踪号
// 						$pdf->SetFont('msyhbd', '', 10);
// 						$pdf->writeHTMLCell(47, 0, 50+$tmpX, 15+$tmpY, $trackingInfo['tracking_number'], 1, 1, 0, true, 'C', true);
		
		//文字:国家简码
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(8, 4.5, 10+$tmpX, 16+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 1, 1, 0, true, 'C', true);
		
		$NumCode=PrintApiHelper::getNumCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		if(!empty($NumCode)){
			//文字:国家分拣码
			$pdf->SetFont('msyhbd', '', 10);
			$pdf->writeHTMLCell(7, 4.5, 18+$tmpX, 16+$tmpY, $NumCode, 1, 1, 0, true, 'C', true);
		}
		
		//收件人地址上面线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 98+$tmpX, 20+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//收件人地址左边线条
		$pdf->Line(40+$tmpX, 20+$tmpY, 40+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//发件人地址信息
		$address=$senderInfo['SENDER_NAME'].(empty($senderInfo['SENDER_ADDRESS'])?'':'&nbsp;&nbsp;'.$senderInfo['SENDER_ADDRESS']).(empty($senderInfo['SENDER_PROVINCE'])?'':', '.$senderInfo['SENDER_PROVINCE']).(empty($senderInfo['SENDER_CITY'])?'':', '.$senderInfo['SENDER_CITY']).(empty($senderInfo['SENDER_AREA'])?'':', '.$senderInfo['SENDER_AREA']).'<br><span style="font-size:7">Zip:'.$senderInfo['SENDER_ZIPCODE'].'</span><br><span style="font-size:7">TEL:'.$senderInfo['SENDER_TELEPHONE'].'/'.$senderInfo['SENDER_MOBILE'].'</span>';
		if(strlen($address)>153)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(37, 0, 2+$tmpX, 24+$tmpY, '<span style="font-size:7">From:</span>'.$address, 0, 1, 0, true, '', true);
		
		//文字:Ship To:
		$pdf->SetFont('msyhbd', '', 8);
		$pdf->writeHTMLCell(14, 0, 40+$tmpX, 21+$tmpY, 'Ship To:', 0, 1, 0, true, '', true);
		
		//收件人
		$pdf->SetFont('msyhbd', '', 8);
		$pdf->setCellHeightRatio(1);
		$pdf->writeHTMLCell(46, 0, 53+$tmpX, 21+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//收件人地址信息
		$address=$receiverInfo['RECEIVER_ADDRESS_MODE2'].(empty($receiverInfo['RECEIVER_AREA'])?'':' '.$receiverInfo['RECEIVER_AREA']).(empty($receiverInfo['RECEIVER_CITY'])?'':' '.$receiverInfo['RECEIVER_CITY']).(empty($receiverInfo['RECEIVER_PROVINCE'])?'':' '.$receiverInfo['RECEIVER_PROVINCE']).(empty($receiverInfo['RECEIVER_COUNTRY_EN'])?'':' '.$receiverInfo['RECEIVER_COUNTRY_EN']);
		if(strlen($address)>150)
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
		else if(strlen($address)>100)
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
		else if(strlen($address)>50)
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
		else
			$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(58, 0, 40+$tmpX, 25+$tmpY, $address, 0, 1, 0, true, '', true);
		
		//国家英文
		$pdf->SetFont('msyhbd', '', 9);
		$pdf->writeHTMLCell(35, 0, 40+$tmpX, 41+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN'], 0, 1, 0, true, '', true);
		
		//文字:Zip
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 44.5+$tmpY, 'Zip:', 0, 1, 0, true, '', true);
		
		//收件人邮编
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(35, 0, 46+$tmpX, 44.5+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//文字:phone(收件人)
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(9, 0, 40+$tmpX, 48+$tmpY, 'TEL:', 0, 1, 0, true, '', true);
		
		//收件人电话
		$pdf->setCellHeightRatio(1.1);
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(50, 0, 46+$tmpX, 48.2+$tmpY, $receiverInfo['RECEIVER_MOBILE'].'/'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
		$pdf->setCellHeightRatio(1.2);
		
		//国家+拣码
		$qh=PrintPdfHelper::getWishGhAreaCode($receiverInfo['RECEIVER_COUNTRY_EN_AB']);
		$pdf->SetFont('msyhbd', '', 12);
		$pdf->writeHTMLCell(27, 0, 73+$tmpX, 42+$tmpY, $qh.'&nbsp;'.$receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, 'R', true);
		
		//发件人电话下面线条
		$pdf->Line(2+$tmpX, 47+$tmpY, 40+$tmpX, 47+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:自编号
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(12, 0, 2+$tmpX, 48+$tmpY, '自编号:', 0, 1, 0, true, '', true);
		
		//自编号
		if(strlen($order->order_source_order_id)>22)
			$size=5;
		else if(strlen($order->order_source_order_id)>16)
			$size=6;
		else
			$size=8;
		$pdf->SetFont($otherParams['pdfFont'], '', $size);
		$pdf->writeHTMLCell(29, 0, 11+$tmpX, 48+$tmpY, $order->order_source_order_id, 0, 1, 0, true, '', true);
		
		//文字:退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(14, 0, 2+$tmpX, 52+$tmpY, '退件单位:', 0, 1, 0, true, '', true);
		
		//退件单位
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(81, 0, 16+$tmpX, 52+$tmpY, $senderInfo['SENDER_RETURNGOODS'], 0, 1, 0, true, '', true);
		
		//退件单位下面的线
		$pdf->Line(2+$tmpX, 56+$tmpY, 98+$tmpX, 56+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息上面线条
		$pdf->Line(2+$tmpX, 52+$tmpY, 98+$tmpX, 52+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:No
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(8, 0, 2+$tmpX, 56+$tmpY, 'No', 0, 1, 0, true, '', true);
		
		//文字:Qty
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(9, 0, 10+$tmpX, 56+$tmpY, 'Qty', 0, 1, 0, true, '', true);
		
		//文字:Description of Contents
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(46, 0, 19+$tmpX, 56+$tmpY, 'Description of Contents', 0, 1, 0, true, '', true);
		
		//文字:kg
		$pdf->SetFont($otherParams['pdfFont'], '',10);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 56+$tmpY, 'Kg.', 0, 1, 0, true, '', true);
		
		//文字:Val(US $)
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 56+$tmpY, 'Val(US $)', 0, 1, 0, true, '', true);
		
		//No右边的线条
		$pdf->Line(10+$tmpX, 56+$tmpY, 10+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//数量右边的线条
		$pdf->Line(19+$tmpX, 56+$tmpY, 19+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量左边的线条
		$pdf->Line(65+$tmpX, 56+$tmpY, 65+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//重量右边的线条
		$pdf->Line(80+$tmpX, 56+$tmpY, 80+$tmpX, 82+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item内容列上面的线条
		$pdf->Line(2+$tmpX, 61+$tmpY, 98+$tmpX, 61+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息
		//超过3个只显示3个
		$count=1;
		$totalqty=0;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>4){
				break;
			}
			//No
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(8, 0, 2+$tmpX, 61+$tmpY+4*$key, $vitems['SKUAUTOID'], 0, 1, 0, true, '', true);
				
			//数量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(9, 0, 10+$tmpX, 61+$tmpY+4*$key, $vitems['QUANTITY'], 0, 1, 0, true, '', true);
				
			//商品名
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(46, 0, 19+$tmpX, 61+$tmpY+4*$key, $vitems['DECLARE_NAME_EN'], 0, 1, 0, true, '', true);
		
			//重量
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(15, 0, 65+$tmpX, 61+$tmpY+4*$key, $vitems['WEIGHT'], 0, 1, 0, true, '', true);
		
			//金额
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(18, 0, 80+$tmpX, 61+$tmpY+4*$key, $vitems['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
			$totalqty+=$vitems['QUANTITY'];
			$count++;
		}
		//商品总数量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(9, 0, 10+$tmpX, 78+$tmpY, $totalqty, 0, 1, 0, true, '', true);
		
		//文字:Total Gross Weight(kg)
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(46, 0, 19+$tmpX, 78+$tmpY, 'Total Gross Weight(Kg.):', 0, 1, 0, true, '', true);
		
		//商品总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(15, 0, 65+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//商品总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		$pdf->writeHTMLCell(18, 0, 80+$tmpX, 78+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
		//文字Total Gross Weight(g)上面的线条
		$pdf->Line(2+$tmpX, 78+$tmpY, 98+$tmpX, 78+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//商品信息下面的线条
		$pdf->Line(2+$tmpX, 82+$tmpY, 98+$tmpX, 82+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:底部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 6);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 83+$tmpY, '我保证上诉申报准确无误,本函件内未装寄法律或邮政和海关规章禁止寄递的任何危险物品。I,the undersigned, certify that the particulars given in this declaration are correct and this item does not contain any dangerous articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
		
		//文字:Sender's signiture& Data Signed:
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(44, 0, 2+$tmpX, 93+$tmpY, 'Sender\'s signiture& Data Signed:', 0, 1, 0, true, '', true);
		
		//签名
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		$pdf->writeHTMLCell(27, 0, 42+$tmpX, 93+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//文字:已检视
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(13, 0, 70+$tmpX, 92+$tmpY, '已检视', 1, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont('msyhbd', '', 10);
		$pdf->writeHTMLCell(12, 0, 85+$tmpX, 92+$tmpY, 'CN22', 0, 1, 0, true, '', true);
	}

	//俄通收
	public static function getETONGSHOU($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:№ Заказа ИМ:
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(25, 0, 3+$tmpX, 5+$tmpY, '№ Заказа ИМ:', 0, 1, 0, true, 'L', true);
				
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
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 30+$tmpX, 2+$tmpY, '54', 15, 0.5, $style, 'N');
		
		//文字:跟踪号
		$pdf->SetFont($otherParams['pdfFont'], '',9);
		$pdf->writeHTMLCell(54, 0, 30+$tmpX, 16+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, 'C', true);
		
		//文字:R ets
		$pdf->SetFont($otherParams['pdfFont'], '',10);
		$pdf->writeHTMLCell(11, 0, 86+$tmpX, 5+$tmpY, 'R ets', 0, 1, 0, true, 'C', true);
		
		//文字:город:
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(23, 0, 5+$tmpX, 22+$tmpY, 'город:', 0, 1, 0, true, 'L', true);
		
		//收件人城市
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(60, 0, 30+$tmpX, 22+$tmpY, $receiverInfo['RECEIVER_CITY'], 0, 1, 0, true, 'L', true);
		
		//文字:Адрес:
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(23, 0, 5+$tmpX, 28+$tmpY, 'Адрес:', 0, 1, 0, true, 'L', true);
		
		//收件人地址
		$tmpConsigneeProvince = $order->consignee_province;
		if(empty($order->consignee_province)){
			$tmpConsigneeProvince = $order->consignee_city;
		}
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(66, 0, 30+$tmpX, 28+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN'].'&nbsp;'.$tmpConsigneeProvince.'&nbsp;'.$receiverInfo['RECEIVER_CITY'].'&nbsp;'.$receiverInfo['RECEIVER_DETAILED_ADDRESS'], 0, 1, 0, true, 'L', true);
		
		//文字:Тел:
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(23, 0, 5+$tmpX, 45+$tmpY, 'Тел:', 0, 1, 0, true, 'L', true);
		
		//收件人电话
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(60, 0, 30+$tmpX, 45+$tmpY, $receiverInfo['RECEIVER_MOBILE'].'&nbsp;/&nbsp;'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, 'L', true);
		
		//文字:Ф.И.О.
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(23, 0, 5+$tmpX, 52+$tmpY, 'Ф.И.О.', 0, 1, 0, true, 'L', true);
		
		//收件人姓名
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(60, 0, 30+$tmpX, 52+$tmpY, $receiverInfo['RECEIVER_NAME'], 0, 1, 0, true, 'L', true);
		
		//包裹重量left边线条
		$pdf->Line(5+$tmpX, 60+$tmpY, 5+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//包裹重量top边线条
		$pdf->Line(5+$tmpX, 60+$tmpY, 93+$tmpX, 60+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//包裹重量right边线条
		$pdf->Line(93+$tmpX, 60+$tmpY, 93+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//包裹重量bottom边线条
		$pdf->Line(5+$tmpX, 70+$tmpY, 93+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:сумм Н/П
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 5+$tmpX, 63+$tmpY, 'сумм Н/П', 0, 1, 0, true, 'L', true);
		
		//文字:Р.
		$pdf->SetFont($otherParams['pdfFont'], '', 12);
		$pdf->writeHTMLCell(7, 0, 28+$tmpX, 62.5+$tmpY, 'Р.', 0, 1, 0, true, 'L', true);
		
		//重量left边线条
		$pdf->Line(35+$tmpX, 60+$tmpY, 35+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//重量right边线条
		$pdf->Line(82+$tmpX, 60+$tmpY, 82+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:KG
		$pdf->SetFont($otherParams['pdfFont'], '', 12);
		$pdf->writeHTMLCell(10, 0, 65+$tmpX, 62.5+$tmpY, 'KG', 0, 1, 0, true, 'L', true);
		
		//总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 11);
		$pdf->writeHTMLCell(30, 0, 35+$tmpX, 63+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, 'C', true);
		
		//图片
		$pdf->writeHTMLCell(13,0, 81+$tmpX, 60.5+$tmpY, '<img src="/images/customprint/labelimg/etongshou.jpg" >', 0, 1, 0, true, '', true);

		//客户代码
		$pdf->SetFont($otherParams['pdfFont'], '', 10);
		$pdf->writeHTMLCell(85, 0, 8+$tmpX, 90+$tmpY, (isset($trackingInfo['return_no']['channelNumber'])?'【'.$trackingInfo['return_no']['channelNumber'].'】':'').'&nbsp; Ref No: '.$order->customer_number, 0, 1, 0, true, 'L', true);
	}

	//俄通收报关单
	public static function getETONGSHOU_BG($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount,$otherParams){
		$trackingInfo=PrintPdfHelper::getTrackingInfo($order);
		$senderInfo=PrintPdfHelper::getSenderInfo($order);
		$receiverInfo=PrintPdfHelper::getReceiverInfo($order);
		$itemListDetailInfo=PrintPdfHelper::getItemListDetailInfo($order, $shippingService);
		$pdf->setCellHeightRatio(1.2); //字体行距
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 98+$tmpY, 98+$tmpX, 98+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:Category of item
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(25, 0, 3+$tmpX, 5+$tmpY, 'Category of item', 0, 1, 0, true, 'L', true);
		
		//邮件种类左1线条
		$pdf->Line(30+$tmpX, 2+$tmpY, 30+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		//邮件种类左2线条
		$pdf->Line(35+$tmpX, 2+$tmpY, 35+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类左3线条
		$pdf->Line(60+$tmpX, 2+$tmpY, 60+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类左4线条
		$pdf->Line(65+$tmpX, 2+$tmpY, 65+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类中间线条
		$pdf->Line(30+$tmpX, 8.5+$tmpY, 98+$tmpX, 8.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类下线条
		$pdf->Line(2+$tmpX, 15+$tmpY, 98+$tmpX, 15+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		$checkedx=29.6+$tmpX;
		$checkedy=1.5+$tmpY;
		if(isset($trackingInfo['return_no']['mctCode'])){
			if($trackingInfo['return_no']['mctCode']==2){
				$checkedx=29.6+$tmpX;
				$checkedy=8+$tmpY;
			}
			else if($trackingInfo['return_no']['mctCode']==3){
				$checkedx=59.6+$tmpX;
				$checkedy=1.5+$tmpY;
			}
			else if($trackingInfo['return_no']['mctCode']==4){
				$checkedx=59.6+$tmpX;
				$checkedy=8+$tmpY;
			}
		}
		
		//复选框1
		$pdf->SetFont($otherParams['pdfFont'], '', 15);
		$pdf->writeHTMLCell(7, 0, 29.6+$tmpX, 1.5+$tmpY, '□', 0, 1, 0, true, 'L', true);
	
		//文字: √
		$pdf->SetFont($otherParams['pdfFont'], '', 15);
		$pdf->writeHTMLCell(7, 0, $checkedx, $checkedy, '√', 0, 1, 0, true, 'L', true);
		
		//文字:Gift
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 35+$tmpX, 3.5+$tmpY, 'Gift', 0, 1, 0, true, 'L', true);
		
		//复选框2
		$pdf->SetFont($otherParams['pdfFont'], '', 15);
		$pdf->writeHTMLCell(7, 0, 59.8+$tmpX, 1.5+$tmpY, '□', 0, 1, 0, true, 'L', true);
		
		//文字: Commercial sample
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(33, 0, 65+$tmpX, 3.5+$tmpY, ' Commercial sample', 0, 1, 0, true, 'L', true);
		
		//复选框3
		$pdf->SetFont($otherParams['pdfFont'], '', 15);
		$pdf->writeHTMLCell(7, 0, 29.6+$tmpX, 8+$tmpY, '□', 0, 1, 0, true, 'L', true);
		
		//文字:Document
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 35+$tmpX, 10+$tmpY, 'Document', 0, 1, 0, true, 'L', true);
		
		//复选框4
		$pdf->SetFont($otherParams['pdfFont'], '', 15);
		$pdf->writeHTMLCell(7, 0, 59.8+$tmpX, 8+$tmpY, '□', 0, 1, 0, true, 'L', true);
		
		//文字: Other
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(33, 0, 65+$tmpX, 10+$tmpY, ' Other', 0, 1, 0, true, 'L', true);
		
		//item信息上面的线
		$pdf->Line(2+$tmpX, 17+$tmpY, 98+$tmpX, 17+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//标题线条
		$pdf->Line(2+$tmpX, 26+$tmpY, 98+$tmpX, 26+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字: Quantity and detailed description of contents
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(40, 0, 2+$tmpX, 17.5+$tmpY, 'Quantity and detailed description of contents', 0, 1, 0, true, 'L', true);
		
		//重量左边的线
		$pdf->Line(58+$tmpX, 17+$tmpY, 58+$tmpX, 74+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字: Weight
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 59+$tmpX, 17.5+$tmpY, 'Weight<br/>(kg)', 0, 1, 0, true, 'L', true);
		
		//重量右边的线
		$pdf->Line(80+$tmpX, 17+$tmpY, 80+$tmpX, 74+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字: Value
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 19+$tmpY, 'Value', 0, 1, 0, true, 'L', true);
		
		//item里面线条1
		$pdf->Line(2+$tmpX, 30+$tmpY, 98+$tmpX, 30+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条2
		$pdf->Line(2+$tmpX, 34+$tmpY, 98+$tmpX, 34+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条3
		$pdf->Line(2+$tmpX, 38+$tmpY, 98+$tmpX, 38+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条4
		$pdf->Line(2+$tmpX, 42+$tmpY, 98+$tmpX, 42+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条5
		$pdf->Line(2+$tmpX, 46+$tmpY, 98+$tmpX, 46+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条6
		$pdf->Line(2+$tmpX, 50+$tmpY, 98+$tmpX, 50+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//item里面线条7
		$pdf->Line(2+$tmpX, 54+$tmpY, 98+$tmpX, 54+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		$count=1;
		foreach ($itemListDetailInfo['products'] as $key=>$vitems){
			if($count>8)
				break;
				
			$jiange=4*$key;

			//内件详细名称
			$pdf->SetFont($otherParams['pdfFont'], '', 9);
			$pdf->writeHTMLCell(90, 0, 2+$tmpX, 26+$tmpY+$jiange,$vitems['DECLARE_NAME_EN'], 0, 1, 0, true, 'L', true);
			
			//内件重量
			$pdf->SetFont($otherParams['pdfFont'], '', 9);
			$pdf->writeHTMLCell(20, 0, 59+$tmpX, 26+$tmpY+$jiange, $vitems['WEIGHT'], 0, 1, 0, true, 'L', true);

			//内件申报价值
			$pdf->SetFont($otherParams['pdfFont'], '', 9);
			$pdf->writeHTMLCell(16, 0, 81+$tmpX, 26+$tmpY+$jiange, sprintf("%.1f",$vitems['AMOUNT_PRICE']), 0, 1, 0, true, 'L', true);
				
			$count++;
		}
		
		//item信息下面的线
		$pdf->Line(2+$tmpX, 58+$tmpY, 98+$tmpX, 58+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字: HS tarifff number and country of origin of goods(For commercial items only)
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(55, 0, 2+$tmpX, 58+$tmpY, 'HS tarifff number and country of origin of goods(For commercial items only)', 0, 1, 0, true, 'L', true);
		
		//文字: Total Weight(kg)
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 59+$tmpX, 60+$tmpY, 'Total Weight(kg)', 0, 1, 0, true, 'L', true);
		
		//文字: Total Value
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 60+$tmpY, 'Total Value', 0, 1, 0, true, 'L', true);
		
		//总数量上面的线
		$pdf->Line(2+$tmpX, 70+$tmpY, 98+$tmpX, 70+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(20, 0, 59+$tmpX, 70+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, 'L', true);
		
		//总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(16, 0, 81+$tmpX, 70+$tmpY,sprintf("%.1f",$itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE']), 0, 1, 0, true, 'L', true);
		
		//底部文字上面的线
		$pdf->Line(2+$tmpX, 74+$tmpY, 98+$tmpX, 74+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//底部文字
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(96, 0, 2+$tmpX, 74+$tmpY, 'I,the undersigned,whose name and address are given on the itme,certify that particulars given in this declaration are correct and that this item dose not contain any dangerous article or artices prohibited by legislation or by postal or customs regulations Date and sender`s signature<br/>Sender\'s signature_________', 0, 1, 0, true, 'L', true);
		
	}
}
?>