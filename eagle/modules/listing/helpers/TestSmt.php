<?php namespace eagle\modules\listing\helpers;

use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\manual_sync\models\Queue;
use eagle\modules\listing\models\AliexpressListing;
use eagle\modules\listing\models\AliexpressCategory;

class TestSmt 
{

	function batchPush($ids,$site_id){
		if($queue = Queue::get('smt:productpush',$site_id)){
			$queue->fail(true);
		}
		$queue = Queue::add('smt:productpush',$site_id,[
			'products'=>explode(',',$ids)
		]);
		return $queue;
	}


	function sync($site_id){
		$queue = new Queue('smt:push',$site_id);
		$queue->save(true);
		return AlipressApiHelper::syncAlipressProtuctDetail($queue);
	}

	function get($id){
		return AliexpressListing::findOne($id)->attributes;
	}

	function brands($cateid){
		return AliexpressCategory::findOne($cateid)->brands;
	}

	function getGroups($id){


		$query = AliexpressListing::find()->with([
			'detail'=>function($q){
				// $q->with('groups');
			}
			// 'detail'
		])->limit(5);


		foreach($query->each(1) as $product){
			var_dump($product->detail->groups[0]->attributes);
		}
	}

	function save(){
		$p = AliexpressListing::findOne(1);
		// $p->product_status = 5;
		// $p->subject = 'editing';
		// $p->save();
		return $p->push();
	}

	function addQueue(){
		$queue = Queue::add('smt:product',1);
		$queue->data([
		    'shop'=>['cn1001531090','111']
		]);
		return $queue->save(true);
	}

	function syncFreight($site_id){
		return AlipressApiHelper::tongbuFreightTemplate($site_id);
	}

	function group($site_id){

		// return AlipressApiHelper::getGroupInfoRow( $site_id,'260887723' );
		return AlipressApiHelper::tongbuProductGroup( $site_id );
	}


	function test(){

		return \Yii::getAlias('@modules');
	}

} ?>