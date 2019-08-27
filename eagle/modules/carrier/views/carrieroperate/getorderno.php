<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\models\SysCarrierParam;
use yii\helpers\Html;
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [ 
		'depends' => [ 
				'yii\web\JqueryAsset' 
		] 
] );
?>
<script>
// 匹配运输服务用
var operate_ids = '<?= $ids ?>';
</script>
<link href="/css/carrier/carrier.css" rel="stylesheet" type="text/css" />


<?php 
// 进度显示
echo  CarrierHelper::jindutiao(CarrierHelper::returnAction([0,1,2,3,6]),$nums,CarrierHelper::returnAction($step),$ids);
?>
<div class="container-fluid" id="getOrderNo_main">
	<div class="carrier-operate-action">
		<input type="button" value="上传至物流商" class="btn btn-success submit" />
		<input type="button" value="匹配运输服务" class="btn btn-primary match" />
		<input type="button" value="批量修改报关信息" class="btn btn-primary edit_customs_info" />
		 <i>点击订单信息展开详情</i>
	</div>

	<table class="table lb-grid table-striped table-bordered table-hover table-responsive">
		<tr class="lb-grid-toggle-all danger">
			<th class="lb-grid-select-all" data-selected="true">
				<input type="checkbox" />
			</th>
			<th>平台订单号</th>
			<th>国家</th>
			<th>物流商</th>
			<th>运输方式</th>
			<th style="width:30em;">处理结果</th>
		</tr>
		<?php 
		// 表单信息
		if($orderObjs):
		foreach($orderObjs as $k=>$orderObj):
			$shippingService = SysShippingService::find()->where(['id'=>$orderObj->default_shipping_method_code])->one();
			if ($shippingService !==null){
				$carrierName = $shippingService->carrier_name;
			}else{
				$carrierName = '';
			}
			//查询出物流商的参数
			$params = SysCarrierParam::find()->where(['carrier_code'=>$orderObj->default_carrier_code])->andWhere('type in (2,3)')->orderBy('sort asc')->all();
			//对查询到的参数进行分类
			$order_params = $item_params = [];
			foreach ($params as $v) {
				if($v->type == 2){
					$order_params[] = $v;
				}else{
					$item_params[] = $v;
				}
			}
		?>
		<tr class="orderTitle lb-grid-toggle info">
			<td class="lb-grid-select">
				<input type="checkbox" value="<?=$ids?>" />
			</td>
			<td><?=$orderObj->order_id ?></td>
			<td><?=$orderObj->consignee_country_code?></td>
			<td><?=$carrierName ?></td>
			<td><?=isset($services[$orderObj->default_shipping_method_code])?$services[$orderObj->default_shipping_method_code]:'';?></td>
			<td class="message" title="<?=$orderObj->carrier_error?$orderObj->carrier_error:''; ?>" text-overflow="3" text-overflow-trigger="mouseover">
				<!-- <a class="error_message"> -->
				<?php 
					$error = $orderObj->carrier_error?'上次上传错误信息：<br/>'.$orderObj->carrier_error:'';
					// if(mb_strlen($error,'utf-8') > 45){
					// 	$error = mb_substr($error,0,45,'utf-8').'...';
					// }
					echo $error;

				?>
			</td>
		</tr>
		<tr class="orderInfo">
			<td></td>
			<td colspan="5" class="row">
				<form class="form-inline">
				<?php 
					if($orderObj->default_carrier_code == 'lb_alionlinedelivery'){
						echo $this->render('lbalionlinedelivery',['orderObj'=>$orderObj,'services'=>$services,'order_params'=>$order_params,'item_params'=>$item_params]);
					}else{
						echo $this->render('default',['orderObj'=>$orderObj,'services'=>$services,'order_params'=>$order_params,'item_params'=>$item_params]);
					}
				?>
				</form>
			</td>
		</tr>
		<?php 
		endforeach;
		else:
			echo '该状态下没有订单或订单没有分配运输服务';
		endif;
		?>
	</table>

	<div class="carrier-operate-action">
		<input type="button" value="上传至物流商" class="btn btn-success submit" />
		<input type="button" value="匹配运输服务" class="btn btn-primary match" />
		<input type="button" value="批量修改报关信息" class="btn btn-primary edit_customs_info" />
	</div>

<div>