<?php 

// TODO proxy dev account @XXX@ 
$client_id = '@XXX@ ';
$client_secret = '@XXX@ ';
$redirect_uri  = 'https://您的erp地址/platform/wish-accounts-v2/get-wish-authorization-code';

$rtn = $v3->callApi('RefreshAccessToken',[
	'grant_type' 		=>'refresh_token',
	'client_id' 		=>$client_id,
	'client_secret' 	=>$client_secret,
	'refresh_token' 	=>$v3->request('refresh_token')
]);
// 保存一下日志
$msg = date('Y-m-d H:i:s').PHP_EOL."request_token:".$v3->request('refresh_token').PHP_EOL.var_export($rtn,true).PHP_EOL.PHP_EOL;
error_log($msg,3,'/tmp/wish-refresh-token.log');

return $rtn;

