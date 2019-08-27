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
<?=$this->render('/order/_leftmenu',['counter'=>$counter]);?>
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
			<td>SKU自动生成商品</td>
			<td>
				<?php $sku_toproduct=ConfigHelper::getConfig('order/sku_toproduct');if (is_null($sku_toproduct)){$sku_toproduct=0;}?>
				<?=Html::radioList('sku_toproduct',$sku_toproduct,['0'=>'关闭','1'=>'开启'])?>
			</td>
		</tr>
		
		</table>
		<?=Html::submitButton('提交',['class'=>'btn btn-success'])?>
		</form>
		<!-- 发票信息设置 -->
		<div style="margin-top:20px;">
			<h4>卖家发票信息：</h4>
			<button type="button" class="btn btn-xs btn-success" onclick="editOrViewInvoiceInfo('add',0)" style="margin: 5px 0px;">添加信息</button>
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
					公司地址：<?=$info['address']?><br>
					联系电话：<?=$info['phone']?><br>
					E-mail地址：<?=$info['email']?>
					</td>
					<?php $stores = $info['stores'];?>
					<td style="border:1px solid #D6D2D2">
						<table style="width:100%;">
						<?php foreach ($stores as $platform=>$siteArr){?>
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
									//@todo 后续新增平台
								};?>
								</td>
							</tr>
						<?php } ?>
						</table>
					</td>
					<td style="border:1px solid #D6D2D2">
						<a href="javascript:void(0)" onclick="editOrViewInvoiceInfo('edit',<?=$info['id'] ?>)">编辑</a>
						<a href="javascript:void(0)" onclick="delInvoiceInfo(<?=$info['id'] ?>)">删除</a>
					</td>
				</tr>
				
				<?php endforeach;endif;?>
			</table>
		</div>
		<!-- 发票信息设置  end-->
	</div>
	<div class="create_or_edit_invoice_info_win"></div>
<!-- body内容结束 -->
</div>

<script>
function editOrViewInvoiceInfo(act,id){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/config/add-or-view-invoice-info',
		data:{act:act,id:id},
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "create_or_edit_invoice_info_win",
				title: "添加卖家发票信息",
				message: result,
				buttons:{
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
					OK: {  
						label: Translator.t("保存"),  
						className: "btn-primary",  
						callback: function () {
							saveInvoiceInfo();
						}  
					}, 
				}
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert("打开新建发票信息窗口失败。");
			return false;
		}
	});
}
function saveInvoiceInfo(){
	$.showLoading();
	$.ajax({
		type:"GET",
		url:"/order/config/save-invoice-info",
		data:$('#seller_invoice_info_form').serialize(),
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				bootbox.alert("保存成功,即将刷新页面！");
				window.location.reload();
			}else{
				bootbox.alert("保存失败："+result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert("保存失败：后台传输有误");
		}
	});
}

function delInvoiceInfo(id){
	$.showLoading();
	$.ajax({
		type:"GET",
		url:"/order/config/del-invoice-info?id="+id,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				bootbox.alert("删除成功，即将刷新页面！");
				window.location.reload();
			}else{
				bootbox.alert("删除失败："+result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert("删除失败：后台传输有误");
		}
	});
}
</script>