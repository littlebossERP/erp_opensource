<?php namespace eagle\modules\comment\controllers;

use console\controllers\CommentHelperController as console;
use eagle\modules\comment\helpers\CommentHelper;
use eagle\modules\comment\models\CmCommentRule;
use eagle\modules\comment\models\CmCommentLog;
use eagle\modules\order\models\OdOrder;

class TestController extends \eagle\components\Controller
{

	// function __construct(){
	// 	// die;
	// }

	/**
	 * 测试规则匹配
	 * @return [type] [description]
	 */
	function actionMatchrules(){
		$rule = CmCommentRule::findOne(3);
		$orders = CommentHelper::matchOrdersFromRules($rule);
		var_dump($orders);
	}

	function actionList(){
		$log = OdOrder::find()
			->where(['is_comment_ignore'=>1])->all();
		foreach($log as $l){
			var_dump($l->order_source_order_id);
		}
	}

	function actionTestnonpay(){
		$sellerid = $_GET['seller'];
		$nonCmOrders = CommentHelper::aliexpressNonHaopingOrders($sellerid);
		var_dump($nonCmOrders);
	}


	function actionTestmodel(){
		$rule = CmCommentRule::find()
			->one()
			->fliter();

		var_dump($rule);
	}

	function actionTestapi(){
		// $order = ['68990726220274'];  69148826101765
		$order = [$_GET['order']];

		$res = CommentHelper::syncEvaluation($order,5,'We are pleased to know that you have received the goods smoothly and looking forward your lasting supports.');

		var_dump($res);
	}


	function actionNon(){
		$rs = CommentHelper::aliexpressNonHaopingOrders('cn1514685701hmkj');
		var_dump($rs);
	}

	function actionTestresult(){

		$rs = CommentHelper::aliexpressResult();
		var_dump($rs);
	}


}