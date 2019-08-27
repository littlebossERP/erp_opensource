<?php namespace eagle\modules\listing\service;


interface Product 
{
	// public $token;
	// public $site_id;
	// public $puid;

	function __construct($site_id);

	/**
	 * 从平台上获取所有范本信息
	 * @return [type] [description]
	 */
	function getProductsFromPlatform();

	/**
	 * 保存商品信息（同步用）
	 * @param  Array  $products [description]
	 * @return [type]           [description]
	 */
	function saveAllProducts(Array $products);


}