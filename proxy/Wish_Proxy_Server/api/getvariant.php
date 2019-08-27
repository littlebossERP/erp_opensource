<?php 
// 获取变种信息
$rtn = $v3->callApi('getVariant',[
	'sku'=>$v3->request('sku')
]);

return $rtn;

