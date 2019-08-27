<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\carrier\helpers\CarrierHelper;
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [
		'depends' => [
				'yii\web\JqueryAsset'
				]
		] );
?>
<script>
	var operate_ids = '<?= $ids ?>';
</script>
<?php echo  CarrierHelper::jindutiao(CarrierHelper::returnAction([0,1,2,3,6]),$nums,CarrierHelper::returnAction($step),$ids);?>

<link href="/css/carrier/carrier.css" rel="stylesheet" type="text/css" />
<div style="" class="container-fluid">
<input type="button" class="btn btn-success" value="打印订单"  onclick="print_all()" style=" margin-bottom: 10px" />
<input type="button" class="btn btn-success" value="赛兔模式订单"  onclick="print_saitu_all()" style=" margin-bottom: 10px" />
	<div class="getOrderNo_title">
		<ul>
			<li style="width:100px"><input type="checkbox" id="ck_all" checked  /></li>
			<li>运输服务</li>
			<li>操作</li>
			<li>结果</li>
		</ul>
	</div>
<?php if($data):foreach($data['emslist'] as $k=>$v):?>
<div class="result" result="">
<div class="getOrderNo_order">
	<ul>
		<li style="width:100px">
			<input type="checkbox" class="ck" ids="<?php echo $v;?>" ems="<?php echo $k;?>" checked />
		</li>
		<li><?php echo $k;?></li>
		<li>
			<input type="button"  class="btn btn-primary btn-xs" value="预览并打印" onclick="print_one('<?php echo $v;?>','<?php echo $k;?>')"/>
			<input type="button"  class="btn btn-primary btn-xs" value="赛兔模式预览并打印" onclick="print_saitu_one('<?php echo $v;?>','<?php echo $k;?>')"/>
		</li>
		<li><input type="button"  class="btn btn-primary btn-xs" value="已打印" onclick="set_print('<?php echo $v;?>','<?php echo $k;?>',this)"/></li>
	</ul>
</div>
</div>
<?php endforeach;else:echo '该状态下没有订单';endif;?>
</div>
<!-- <div style="text-align: center;"><input type="button" class="btn btn-success btn-lg" value="打印订单" onclick="print_all()" /> </div> -->