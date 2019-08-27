<?php namespace eagle\modules\listing\helpers;

use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\helpers\WishHelper;

class TestWish 
{
	public $product;

	function __construct(){
		// $this->product = WishFanben::findOne($id);
	}

	// 发布，预保存
	function save(){
		$this->product->name = 'hohohoho';
		return $this->product->save();
	}

	function push(){
		return $this->product->push();
	}

	function create(){
		$product = WishHelper::WishFanbenSave($this->getPost());

		return $product;
	}

	function getOnline($id){
		$product = WishFanben::findOne($id);
		return $product->getOnlineData();
	}

	function test(){


		$p = WishFanben::find()->select(['status'])->column();

		var_dump($p);

		// column
	}

	function getPost(){
		$post = [
		    'name' => '禅亚塔',
		    'tags' => '111',
		    'site_id' => 1,
		    'description' => '321312',
		    'parent_sku' => 'test-0602-0002',
		    'shipping' => 1,
		    'shipping_time' => '5-10',
		    'price' => 1,
		    'inventory' => 1,
		    'msrp' => 0,
		    'brand' => '',
		    'upc' => '',
		    'type' => 2,
		    'fanben_id' => 0,
		    'opt_method' => '',
		    'landing_page_url' => '',
		    'size' => 'Man',
		    'main_image' => 'http://image.littleboss.com/1/20160602/20160602173911-812d813.jpg?imageView2/1/w/210/,h/210',
		    'extra_image_1' => '',
		    'extra_image_2' => '',
		    'extra_image_3' => '',
		    'extra_image_4' => '',
		    'extra_image_5' => '',
		    'extra_image_6' => '',
		    'extra_image_7' => '',
		    'extra_image_8' => '',
		    'extra_image_9' => '',
		    'extra_image_10' => '',
		    'variance' => 
		        [
		                [
		                    'price' => 1,
		                    'inventory' => 1,
		                    'sku' => 'test-0602-0002',
		                    'shipping' => 1,
		                    'enable' => 'Y'
		                ]

		        ]
		];
		return $post;
	}


}