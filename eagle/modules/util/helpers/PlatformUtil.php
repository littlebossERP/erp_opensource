<?php

namespace eagle\modules\util\helpers;


/**
 +------------------------------------------------------------------------------
 * 各种销售平台的一些常量
 +------------------------------------------------------------------------------
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class PlatformUtil {
	
	/**
	 * 获取对应平台站点的logo本地文件
	 * 本地文件保存于 eagle/web/images/platform_logo
	 * 文件名格式为： 平台名+站点国家代码+.jpg/png/gif 。若没有分站点，则只需：平台名+.jpg/png/gif
	 * @return url , or '' if not existing
	 */	
	public static function getLogo($platform,$site){
		$basepath = \Yii::getAlias('@webroot');
		$baseUrl = \Yii::$app->request->getHostInfo();
		//$baseUrl='http://eagle2.com';
		
		if(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.jpg'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'_'.$site.'.jpg';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.png'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'_'.$site.'.png';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.gif'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'_'.$site.'.gif';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.jpg'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'.jpg';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.png'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'.png';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.gif'))
			$Logo_Url = $basepath.'/images/platform_logo/'.$platform.'.gif';
		else
			$Logo_Url = '';
		
		/*图片url地址
		if(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.jpg'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'_'.$site.'.jpg';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.png'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'_'.$site.'.png';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'_'.$site.'.gif'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'_'.$site.'.gif';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.jpg'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'.jpg';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.png'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'.png';
		elseif(file_exists($basepath.'/images/platform_logo/'.$platform.'.gif'))
			$Logo_Url = $baseUrl.'/images/platform_logo/'.$platform.'.gif';
		else 
			$Logo_Url = '';
		*/
		//exit($Logo_Url);
		return $Logo_Url;
	}	

	/**
	 * 平台相关站点信息
	 * @todo 需要不断完善
	 */
	public static function getPlatformSiteInfo($platform,$site)
	{
		$platform = strtolower($platform);
		$site = strtolower($site);
		$allInfo = [
			'amazon'=>[
				'ca'=>['lang'=>'EN','name'=>'Amazon Canada'],
				'cn'=>['lang'=>'CN','name'=>'亚马逊'],
				'de'=>['lang'=>'DE','name'=>'Amazon Germany'],
				'es'=>['lang'=>'ES','name'=>'Amazon Spain'],
				'fr'=>['lang'=>'FR','name'=>'Amazon France'],
				'it'=>['lang'=>'IT','name'=>'Amazon Italy'],
				'jp'=>['lang'=>'JP','name'=>'Amazon Japan'],
				'us'=>['lang'=>'EN','name'=>'Amazon U.S.A(America)'],
				'uk'=>['lang'=>'EN','name'=>'Amazon United Kingdom'],
				'au'=>['lang'=>'EN','name'=>'Amazon Australia'],
				'br'=>['lang'=>'PT','name'=>'Amazon Australia'],
				'in'=>['lang'=>'EN','name'=>'Amazon India'],//amazon的India网站使用的是英语
				'mx'=>['lang'=>'ES','name'=>'Amazon Mexico'],
				'nl'=>['lang'=>'NL','name'=>'Amazon Netherlands'],
				],
			'cdiscount'=>[
				'fr'=>['lang'=>'FR','name'=>'Cdiscount'],
				],
			'linio'=>[
				'mx'=>['lang'=>'ES','name'=>'Linio'],
			],
		
		];
		
		if(isset($allInfo[$platform][$site]))
			return $allInfo[$platform][$site];
		else 
			return [];
	}
	
	
	/*
	 * 用于打印发票的部分翻译
	* @param	$arrayind	string		需要翻译的项
	* @param	$lang		string		目标语言
	* @return	string
	*
	* @todo
	* 开始只支持 英文，法文，德文 三种语言。日后出现新语言需求要不断更新
	*/
	public static function getLangStr($arrayind,$lang)
	{
		$LangStr['PaymentAddress']['EN']='Payment Address';
		$LangStr['PaymentAddress']['FR']='VENDU À';
		$LangStr['PaymentAddress']['DE']='Payment-Adresse';
		$LangStr['PaymentAddress']['ES']='Dirección de Pago';
	
		$LangStr['ShippingAddress']['EN']='Shipping Address';
		$LangStr['ShippingAddress']['FR']='LIVRÉ À';
		$LangStr['ShippingAddress']['DE']='Liefer-Adresse';
		$LangStr['ShippingAddress']['ES']='Dirección de envío';
	
		$LangStr['sku']['EN']='Product Reference No.';
		$LangStr['sku']['FR']='Réf.';
		$LangStr['sku']['DE']='Produkt-Nr';
		$LangStr['sku']['ES']='Referencia Producto No.';
	
		$LangStr['productname']['EN']='Product Name';
		$LangStr['productname']['FR']='Produits';
		$LangStr['productname']['DE']='Name des Produkts';
		$LangStr['productname']['ES']='Nombre de producto';
	
		$LangStr['qty']['EN']='Quantity';
		$LangStr['qty']['FR']='Qté';
		$LangStr['qty']['DE']='Menge';
		$LangStr['qty']['ES']='Cantidad';
	
		$LangStr['postcode']['EN']='Post Code';
		$LangStr['postcode']['FR']='Code postal';
		$LangStr['postcode']['DE']='Postleitzahl';
		$LangStr['postcode']['ES']='Código postal';
	
		$LangStr['telephone']['EN']='Telephone No';
		$LangStr['telephone']['FR']='Téléphone';
		$LangStr['telephone']['DE']='Telefon Nr.';
		$LangStr['telephone']['ES']='Teléfono';
	
		$LangStr['subtotal']['EN']='Sub-total';
		$LangStr['subtotal']['FR']='Sous-total';
		$LangStr['subtotal']['DE']='Zwischensumme';
		$LangStr['subtotal']['ES']='Total parcial';
	
		$LangStr['packinglist']['EN']='Packing List';
		$LangStr['packinglist']['FR']='Liste de colisage';
		$LangStr['packinglist']['DE']='Packliste';
		$LangStr['packinglist']['ES']='Lista de viaje';
	
		$LangStr['orderno']['EN']='Order No.';
		$LangStr['orderno']['FR']='Facture n°';
		$LangStr['orderno']['DE']='Bestell-Nr';
		$LangStr['orderno']['ES']='Orden No.';
	
		$LangStr['orderdate']['EN']='Date of Order';
		$LangStr['orderdate']['FR']='Date de commande';
		$LangStr['orderdate']['DE']='Datum der Bestellung';
		$LangStr['orderdate']['ES']='Fecha de orden';
	
		$LangStr['invoice']['EN']='Commercial Invoice';
		$LangStr['invoice']['FR']='Facture Commerciale';
		$LangStr['invoice']['DE']='Handelsrechnung';
		$LangStr['invoice']['ES']='Factura';
	
		$LangStr['price']['EN']='Price';
		$LangStr['price']['FR']='Prix';
		$LangStr['price']['DE']='Preis';
		$LangStr['price']['ES']='Precio';
	
		$LangStr['shippingamt']['EN']='Shipping Amount';
		$LangStr['shippingamt']['FR']='Frais de port';
		$LangStr['shippingamt']['DE']='Liefer-Betrag';
		$LangStr['shippingamt']['ES']='Monto del envío';
	
		$LangStr['discountamt']['EN']='Discount Amount';
		$LangStr['discountamt']['FR']='Montant de la remise';
		$LangStr['discountamt']['DE']='Skontobetrag';
		$LangStr['discountamt']['ES']='Importe de descuento';
	
		$LangStr['grandtotal']['EN']='Grand Total';
		$LangStr['grandtotal']['FR']='Montant global';
		$LangStr['grandtotal']['DE']='Gesamtsumme';
		$LangStr['grandtotal']['ES']='Gran total';
			
		$LangStr['nettotal']['EN']='Net Total';
		$LangStr['nettotal']['FR']='Total net';
		$LangStr['nettotal']['DE']='Netto insgesamt';
		$LangStr['nettotal']['ES']='Total neto';
		
		$LangStr['VAT']['EN']='VAT';
		$LangStr['VAT']['FR']='TVA';
		$LangStr['VAT']['DE']='MWSt.';
		$LangStr['VAT']['ES']='IVA';
		
		if(isset($LangStr[$arrayind][$lang]))
			return $LangStr[$arrayind][$lang];
		else
			return $arrayind;
	
	}
	
	/*
	 * 金额转换成带货币符号的格式
	* @param	$currency	string		货币代码
	* @param	$price		number		金额
	* @return	string
	*
	* @todo
	* 开始只支持 美元，欧元，英镑，人民币 4种货币。要持续更新
	*/
	public static function formatCurrencyPrice($currency,$price)
	{
		$dj = number_format($price,2,'.',',');
		if ($currency == 'USD')
			$dj = "$ ".number_format($price,2,'.',',')."";
	
		if ($currency == 'EUR')
			$dj = "".number_format($price,2,'.',',')." €";
			
		if ($currency == 'CNY')
			$dj = "￥ ".number_format($price,2,'.',',')."";
	
		if ($currency == 'GBP')
			$dj = "£ ".number_format($price,2,'.',',')."";
	
		return $dj;
	}
}

?>