<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\OrderHelper;
use Qiniu\json_decode;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\models\SaasAmazonUser;
?>
<style>
.table td,.table th{
	text-align: left;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../leftmenu/_leftmenu',['counter'=>$counter]);?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('oms_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<!-- body内容开始 -->
	<div class="content-wrapper" >
		<form action="" method="post">
		<table class="table table-condensed table-bordered  table-striped"  style="font-size:12px;width:400px">
		<tr style="display:none"><th colspan="2"><b>订单异常检测</b></th></tr>
		<tr style="display:none">
			<td>sku是否存在</td>
			<td>
				<?php $check_sku=ConfigHelper::getConfig('order/check_sku');if (is_null($check_sku)){$check_sku=0;}?>
				<?=Html::radioList('check_sku',$check_sku,['0'=>'不检测','1'=>'检测'])?>
			</td>
		</tr>
		<tr style="display:none">
			<td>库存是否充足</td>
			<td>
				<?php $check_stock=ConfigHelper::getConfig('order/check_stock');if (is_null($check_stock)){$check_stock=0;}?>
				<?=Html::radioList('check_stock',$check_stock,['0'=>'不检测','1'=>'检测'])?>
			</td>
		</tr>
	<!-- 	<tr>
			<td>paypal账号</td>
			<td>
				<?php $check_paypal=ConfigHelper::getConfig('order/check_paypal');if (is_null($check_paypal)){$check_paypal=0;}?>
				<?=Html::radioList('check_paypal',$check_paypal,['0'=>'不检测','1'=>'检测'])?>
			</td>
		</tr> -->
		<tr style="display:none">
			<td>物流匹配</td>
			<td>
				<?php $check_wuliu=ConfigHelper::getConfig('order/check_wuliu');if (is_null($check_wuliu)){$check_wuliu=0;}?>
				<?=Html::radioList('check_wuliu',$check_wuliu,['0'=>'不检测','1'=>'检测'])?>
			</td>
		</tr>
		
		<tr>
			<th colspan="2">自动生成商品</th>
		</tr>
		<tr>
			<td>订单同步时自动生成商品</td>
			<td>
				<?php $sku_toproduct=ConfigHelper::getConfig('order/sku_toproduct');if (is_null($sku_toproduct)){$sku_toproduct=0;}?>
				<?=Html::radioList('sku_toproduct',$sku_toproduct,['0'=>'关闭','1'=>'开启'])?>
			</td>
		</tr>
		<?php if (empty($sku_toproduct)):?>
		<tr>
			<td>移入发货中时自动生成商品</td>
			<td>
				<?php $shipandcreateSKU=ConfigHelper::getConfig('order/shipandcreateSKU');if (is_null($shipandcreateSKU)){$shipandcreateSKU=0;}?>
				<?=Html::radioList('shipandcreateSKU',$shipandcreateSKU,['0'=>'关闭','1'=>'开启'])?>
			</td>
		</tr>
		<?php endif;?>
		</table>
		<?=Html::submitButton('提交',['class'=>'btn btn-success'])?>
		</form>
		<!-- 发票信息设置 -->
		<div style="margin-top:20px;">
			<h4>卖家发票信息：</h4>
			<a class="iv-btn btn-success" style="margin:8px 0;" onclick="$.modal(
				{url:'/configuration/elseconfig/add-or-view-invoice-info?act=add&id=0',method:'get',data:{}},
				'添加卖家发票信息'
				).done(function($modal){
					$modal.on('modal.action.resolve',function(){
						saveInvoiceInfo();
					});
				});">添加信息</a>
			<table class="table">
				<tr>
					<th width="40%">信息</th>
					<th width="50%">店铺</th>
					<th width="10%">操作</th>
				</tr>
				<?php 
				$uid = \Yii::$app->user->id;
				$invoiceInfos = OrderHelper::getSellerInvoiceInfos($uid);?>
				<?php if(!empty($invoiceInfos)):?>
				<?php foreach ($invoiceInfos as $info):?>
				<tr>
					<td style="border:1px solid #D6D2D2">
					公司名称：<?=$info['company']?><br>
					VAT：<?=$info['vat']?><br>
					税率(%)：<?=$info['tax_rate']?><br>
					公式：<?=(empty($info['tax_formula']) || $info['tax_formula']==1)?"不含税价格=总价/(1+增值税率)":"不含税价格=总价*(1-增值税率)"?><br>
					公司地址：<?=$info['address']?><br>
					联系电话：<?=$info['phone']?><br>
					E-mail地址：<?=$info['email']?><br>
					签名图片Url：<?=$info['autographurl']?><br>
					发票类型：<?=(!empty($info['type']) && $info['type']=='G')?'高青发票':'一般发票'?>
					</td>
					<?php $stores = $info['stores'];?>
					<td style="border:1px solid #D6D2D2">
						<table style="width:100%;">
						<?php if(!empty($stores)){ foreach ($stores as $platform=>$siteArr){?>
							<tr style="border:1px solid #D6D2D2">
								<td><?=$platform?>:</td>
								<td style="text-align: left;">
								<?php foreach ($siteArr as $site=>$storeArr){
									if($platform=='cdiscount'){
										echo implode(';', $storeArr)."<br>";
									}
									if($platform=='amazon'){
										echo "$site : ";
										$merchantId_name_mapping=[];
										$amzAccounts = SaasAmazonUser::find()->where(['uid'=>$uid])->asArray()->all();
										foreach ($amzAccounts as $amzAccount){
											if(in_array($amzAccount['merchant_id'],$storeArr))
												$merchantId_name_mapping[$amzAccount['merchant_id']] = $amzAccount['store_name'];
										}
										echo implode(';', $merchantId_name_mapping)."<br>";
									}
									if($platform=='priceminister'){
										echo implode(';', $storeArr)."<br>";
									}
									//@todo 后续新增平台
								}};?>
								</td>
							</tr>
						<?php } ?>
						</table>
					</td>
					<td style="border:1px solid #D6D2D2">
						<a class="" onclick="$.modal(
							{url:'/configuration/elseconfig/add-or-view-invoice-info?act=add&id=<?=$info['id'] ?>',method:'get',data:{}},
							'编辑卖家发票信息'
							).done(function($modal){
								$modal.on('modal.action.resolve',function(){
									saveInvoiceInfo();
								});
							});">编辑</a>
						<a href="javascript:void(0)" onclick="delInvoiceInfo(<?=$info['id'] ?>)">删除</a>
					</td>
				</tr>
				
				<?php endforeach;endif;?>
			</table>
		</div>
		<!-- 发票信息设置  end-->
		
		<div style="margin-top:20px;">
			<h4 style='display: inline;'>OMS 设置所有订单页是否显示特定操作功能：</h4>
<!-- 			<div> -->
				<label><input type="radio" name="is_show_OtherOperation" onchange=onchangeOtherOperation(this) value="0" <?=($is_show_OtherOperation == false ? 'checked' : '') ?> > 否</label>
				<label><input type="radio" name="is_show_OtherOperation" onchange=onchangeOtherOperation(this) value="1" <?=($is_show_OtherOperation == true ? 'checked' : '') ?> > 是</label>
<!-- 			</div> -->
		</div>
		
		<div style="margin-top:20px;">
			<h4 style='display: inline;'>OMS 设置已付款状态是否不显示可用库存功能：</h4>
				<label><input type="radio" name="is_show_Available_stock" onchange=onchangeAvailablestock(this) value="0" <?=($is_show_AvailableStock == false ? 'checked' : '') ?> > 否</label>
				<label><input type="radio" name="is_show_Available_stock" onchange=onchangeAvailablestock(this) value="1" <?=($is_show_AvailableStock == true ? 'checked' : '') ?> > 是</label>
		</div>
	</div>
	<div class="create_or_edit_invoice_info_win"></div>
<!-- body内容结束 -->
</div>

<script>
$('#table_Stores_Info').hide();
function saveInvoiceInfo(){
	$.showLoading();
	$.ajax({
		type:"GET",
		url:"/configuration/elseconfig/save-invoice-info",
		data:$('#seller_invoice_info_form').serialize(),
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				$e = $.alert("保存成功，即将刷新页面！",'success');
				$e.then(function(){
					window.location.reload();
				});
			}else{
				$.alert("保存失败："+result.message,'danger');
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			$.alert("保存失败：后台传输有误",'danger');
		}
	});
}

function delInvoiceInfo(id){
	$e = $.confirmBox('<p class="text-danger">确认删除此卖家发票信息？</p>');
	$e.then(function(){
		$.showLoading();
		$.ajax({
			type:"GET",
			url:"/configuration/elseconfig/del-invoice-info?id="+id,
			dataType:'json',
			success: function (result) {
				$.hideLoading();
				if(result.success){
					$e = $.alert("删除成功，即将刷新页面！",'success');
					$e.then(function(){
						window.location.reload();
					});
				}else{
					$.alert("删除失败："+result.message,'danger');
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				$.alert("删除失败：后台传输有误",'danger');
			}
		});
	});
}

function isShowStores()
{
	if( $(':radio[name="invoice_type"]:checked').val() == "G")
	    $('#table_Stores_Info').hide();
	else
		$('#table_Stores_Info').show();
}

function onchangeOtherOperation(obj){
	is_show_OtherOperation = $(obj).val();

	$.showLoading();
	$.ajax({
		type:"GET",
		url:"/configuration/elseconfig/other-operation-order-set?is_show_OtherOperation="+is_show_OtherOperation,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				$e = $.alert("设置成功！",'success');
			}else{
				$.alert("设置失败："+result.message,'danger');
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			$.alert("后台传输有误",'danger');
		}
	});
}

function onchangeAvailablestock(obj){
	is_show_Availablestock = $(obj).val();

	$.showLoading();
	$.ajax({
		type:"GET",
		url:"/configuration/elseconfig/availablestock-set?is_show_Availablestock="+is_show_Availablestock,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				$e = $.alert("设置成功！",'success');
			}else{
				$.alert("设置失败："+result.message,'danger');
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			$.alert("后台传输有误",'danger');
		}
	});
}

</script>