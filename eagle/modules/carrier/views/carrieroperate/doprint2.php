<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use yii\helpers\Url;
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [
		'depends' => [
				'yii\web\JqueryAsset'
				]
		] );
?>
<div>
			<?php if($data['result']['error']):echo "<p class='text-center' style='margin-top:100px; font-size:16px;line-height:30px;'>物流运输服务：".$data['carrier_name'].'<br>物流单打印失败 错误原因:'.$data['result']['msg']."<br><input type='button' value='关闭页面' class='btn btn-warning' onclick='javascript:window.close();'/></p>"; ?>
			<?php else:
					  if(isset($data['result']['data']['errors'])){
					  	$str = '';
					  	foreach ($data['result']['data']['errors'] as $key => $value) {
					  		$str .= '订单：'.intval($key).'打印失败；原因：'.$value['msg'].'<br/>';
					  	}
					  	echo $str;
					  }
				  	  if($data['result']['data']['pdfUrl']):

			 ?>
			<iframe src="<?= $data['result']['data']['pdfUrl'] ?>" width="100%" height="1000" border="0"></iframe>
			<?php      endif;
				   endif; ?>
</div>