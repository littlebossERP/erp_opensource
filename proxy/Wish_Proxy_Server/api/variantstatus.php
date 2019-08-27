<?php 
// 上/下架商品

// param: token,  sku,enable:on/off

$status = $v3->request('enable')=='on'?'enable':'disable';

$rtn = $v3->callApi('varitation_'.$status,[
	'sku'=>$v3->request('sku')
]);

return $rtn;

