<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
$uid = Yii::$app->user->identity->getParentUid ();
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [ 
		'depends' => [ 
				'yii\web\JqueryAsset' 
		] 
] );
$doarr = [ 
		'1' => '提交订单',
		'2' => '确认订单',
		'3' => '获取物流号',
		'4' => '打印物流单',
		'5' => '取消订单',
		'6' => '重新发货' 
];
$dispatch_list = [
	'lb_4px'=>1,
	'lb_SF'=>1,
	'lb_tiesanjiaoOverSea'=>1
]
?>
<style>
.in_padding input {
	margin-bottom: 5px;
}
</style>
<script>
	var submitOrderUrl = '<?=Url::to("get-data") ?>';
</script>
<!-- table -->
<input type="button" value="开始执行" id="savebutton" class="btn btn-primary">
<table class="table table-bordered">
	<tr>
		<th colspan="2"><?php if($operate_code==1): ?>以下信息请确认(*为必填)<?php else:echo $doarr[$operate_code];endif; ?></th>
	</tr>
    <?php foreach($ids as $k=>$id): ?>
    <tr class="t" value="<?=$id ?>">
		<td style="width: 80%; padding: 0">
			<!-- 如果用户是提交订单 则显示表单列表 -->
            <?php if($operate_code == 1): ?>
            <form>
            <table class="table table-bordered" style="table-layout: fixed; margin: 0; font-size: 12px">
					<tr class="success" style="height: 45px">
						<td>订单号:<?= $data[$k]['odorderitem'][0]['order_id'] ?>&nbsp;&nbsp;<b>(<?=$data[$k]['carrier']?>)</b></td>
						<td>订单付款类型: <select name="paymentCode">
								<option value="P">预付</option>
								<option value="C">到付</option>
						</select>
						</td>
						<td>备注:<input type="text" name="orderNote">
						</td>
						<td>
						<?php if(isset($dispatch_list[$data[$k]['carrier_code']])): ?>
							直接交运:
							<select name="is_Dispatch">
									<option value="0">否</option>
									<option value="1">是</option>
							</select>
						<?php endif; ?>
						</td>
					</tr>
            	<?php foreach($data[$k]['odorderitem'] as $key => $v): ?>
            	<tr>
						<td colspan="4">商品名:<b><?=$v['product_name'] ?></b></td>
				</tr>
				<tr>
					<td colspan="4" style="padding:0">
						<table class="table table-hover" style="margin:0">
							<tr>
								<td>数量:</td>
								<td>
									<input type="text"  name="DeclarePieces[]" value="<?=@$v['quantity']-@$v['sent_quantity'] ?>" style="width: 50px">
									<select	name="DeclareUnitCode[]">
										<option value="PCE">件</option>
										<option value="MT">米</option>
										<option value="DZN">打</option>
										<option value="KG">公斤</option>
									</select>
								</td>
								<td><b>商品英文报关名称*:</b></td>
								<td><input type="text" name="EName[]" value="<?=@$v['product']['declaration_en'] ?>"></td>
							</tr>
							<tr>
								<td><b>重量(g)*:</b></td>
								<td><input type="text"  name="weight[]" style="width: 50px" value="<?=@$v['product']['prod_weight'] ?>"></td>
								<td><b>商品中文报关名称*:</b></td>
								<td><input type="text" name="Name[]" value="<?=@$v['product']['declaration_ch'] ?>"></td>
							</tr>
							<tr>
								<td><b>报关价格*:</b></td>
								<td>
									<input type="text"  name="DeclaredValue[]" style="width:50px;" value="<?=@$v['product']['declaration_value'] ?>">
									<select name="currency[]" value="<?=@$v['product']['declaration_value_currency'] ?>">
										<option value="USD" <?php if(@$v['product']['declaration_value_currency']=='USD')echo 'selected' ?>>美元($)</option>
										<option value="EUR" <?php if(@$v['product']['declaration_value_currency']=='EUR')echo 'selected' ?>>欧元(€)</option>
										<option value="GBP" <?php if(@$v['product']['declaration_value_currency']=='GBP')echo 'selected' ?>>英镑(￡)</option>
										<option value="CNY" <?php if(@$v['product']['declaration_value_currency']=='CNY')echo 'selected' ?>>人民币(￥)</option>
										<option value="AUD" <?php if(@$v['product']['declaration_value_currency']=='AUD')echo 'selected' ?>>澳元($)</option>
									</select>
								</td>
								<?php if($data[$k]['carrier_code'] == 'lb_ali'): ?>
									<td>是否包含锂电池:</td>
									<td>
										<select name="isContainsBattery[<?=$key ?>]">
											<option value="0">否</option>
											<option value="1">是</option>
										</select>
									</td>
								<?php else: ?>
									<td>配货备注:</td>
									<td><input type="text" name="DeclareNote[<?=$key ?>]" value="<?=@$v['product']['comment'] ?>"></td>
								<?php endif; ?>
							</tr>
							<tr>
								<td>海关编码:</td>
								<td>
									<input type="text" name="Hscode[]">
								</td>
								<td>包装规格</td>
								<td>
									长<input type='text' name="length[]" style="width:40px" value="<?=@$v['product']['prod_length'] ?>">
									宽<input type='text' name="width[]" style="width:40px" value="<?=@$v['product']['prod_width'] ?>">
									高<input type='text' name="height[]" style="width:40px" value="<?=@$v['product']['prod_height'] ?>">
								</td>
							</tr>
						</table>			
					</td>
				</tr>
        	    <?php endforeach; ?>
			</table>
			<input type="hidden" name="operate_code" value="<?=$operate_code  ?>">
			</form>
        <?php else: ?>
        <!-- 订单相关其他操作 数据的展示 -->
			<form>
				<table cellspacing="0" cellpadding="0" width="100%"
					class="table table-bordered" style="table-layout: fixed; margin: 0">
					<tr style="height: 45px" class="success">
						<td>订单号:<?=$id ?></td>
					</tr>
					<tr>
						<td>物流商客户号:<?=$uid.$id ?></td>
					</tr>
				</table>
				<input type="hidden" name="operate_code"
					value="<?=$operate_code  ?>">
			</form>
        <?php endif; ?>
        </td>
		<td style="padding: 0">
			<table class="table" style="table-layout: fixed;">
				<tr class="success" style="height: 45px">
					<td>处理结果: <input type="button" name="delid" value="移除该订单">
					</td>
				</tr>
				<tr>
					<td class='result'></td>
				</tr>
			</table>
		</td>
	</tr>
    <?php endforeach; ?>
</table>
<div id="putre"></div>
