<?php 
// 获取商品信息
$rtn = $v3->callApi('getProduct',[
	'parent_sku'=>$v3->request('sku')
]);

return $rtn;

