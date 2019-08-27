<?php 
$menu_group = [
	'addressinfo'=>[
		'address'=>[],
		'barcode'=>[],
		'character'=>[],
		'address_mode2'=>[],
	],
	'orderinfo'=>[
		'character'=>[],
		'barcode'=>[],
	],
	'pickinginfo'=>[
		'skulist'=>[],
		'declarelist'=>[],
	],
	'productinfo'=>[
		'character'=>[],
		'barcode'=>[],
	],
	'element'=>[
		'line-x'=>[],
		'line-y'=>[],
		'customtext'=>[],
		'circletext'=>[],
		'onlineimage'=>[],
	],
	'imagelibrary'=>[
		'image'=>[]
	],


];

// 地址单
$address = array_merge([],$menu_group);
unset($address['pickinginfo']['declarelist']);

// 报关单
$declare = array_merge([],$menu_group);
unset($declare['pickinginfo']['skulist']);

// 配货单
$allocation = array_merge([],$menu_group);
unset($allocation['pickinginfo']['declarelist']);

// 发票
$invoice = array_merge([],$menu_group);
unset($invoice['pickinginfo']['declarelist']);

// 商品标签
$tag = array_merge([],$menu_group);
unset($tag['addressinfo']);
unset($tag['orderinfo']);
unset($tag['pickinginfo']);
unset($tag['imagelibrary']);


return [
	'地址单'=>$address,
	'报关单'=>$declare,
	'配货单'=>$allocation,
	'发票'=>$invoice,
	'商品标签'=>$tag,
];