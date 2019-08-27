<?php 
// 获取商品信息
$rtn = $v3->callApi('product',[
	'start'=>$v3->request('start'),
	'limit'=>$v3->request('limit'),
	'since'=>$v3->request('since'),
	'show_rejected'=>'true'
]);

return $rtn;

