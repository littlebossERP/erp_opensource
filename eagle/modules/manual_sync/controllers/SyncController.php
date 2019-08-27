<?php namespace eagle\modules\manual_sync\controllers;

use eagle\modules\manual_sync\models\Queue;

class SyncController extends \eagle\components\Controller 
{


	protected function request($name){
		if(isset($_REQUEST[$name]) && $_REQUEST[$name]){
	        return $_REQUEST[$name];
		}else{
			return NULL;
		}
	}

    function actionGetProgress(){
        $data = Queue::getProgress($this->request('type'),$this->request('site_id'));
        if(isset($data['queue']['_data'])){
            $data['data'] = json_decode($data['queue']['_data']);
        }
        return $this->renderJson($data);
    }

    function actionGetProgressByUser(){
        $data = Queue::getProgress($this->request('type'),\Yii::$app->user->id);
        if(isset($data['queue']['_data'])){
            $data['data'] = json_decode($data['queue']['_data']);
        }
        return $this->renderJson($data);
    }

    function actionGetQueue(){
        try{
            $queue = Queue::add($this->request('type'),$this->request('site_id'));
            $result = [
                'success'=>true,
                'queue_id'=>$queue->id,
                'status'=>$queue->status,
                'progress'=>$queue->progress
            ];
        }catch(\Exception $e){
            $result = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'code'=>$e->getCode()
            ];
        }
        return $this->renderJson($result);
    }

    // 根据用户uid创建同步队列
    function actionGetQueueByUser(){

        try{
            $queue = Queue::add($this->request('type'),\Yii::$app->user->id);
            $queue->data([
                'shop'=>$this->request('shop')
            ]);
            $queue->save(true);
            $result = [
                'success'=>true,
                'queue_id'=>$queue->id,
                'status'=>$queue->status,
                'progress'=>$queue->progress
            ];
        }catch(\Exception $e){
            $result = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'code'=>$e->getCode()
            ];
        }
        return $this->renderJson($result);
    }

    function test(){
        $queue = Queue::get('wish:product',656);
        var_dump($queue);
        $queue->save(true);
        var_dump($queue);
    }


}
