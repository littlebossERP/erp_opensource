<?php namespace eagle\modules\listing\models;

use common\helpers\ProxyBatchHelper;

class EnsogoProxyQuery
{
	public $query;

	function __construct($api,$size){
		$this->query = \Yii::$app->db->createCommand("SELECT * FROM sys_carrier")->query();
	}

	function each(){

		return new ProxyBatchHelper([$this,'fetchData']);
	}

	function fetchData(){
		return $this->query->read();
	}


}