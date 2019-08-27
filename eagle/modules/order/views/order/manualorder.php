<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;

// js 文件引入
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/manualOrder.js?v=".'1.03', ['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

// 执行js初始化
$this->registerJs("manualOrder.init();" , \yii\web\View::POS_READY);
?>
<style>
.ui-autocomplete{
	z-index:9999;
	z-index: 2000 !important;
	overflow-y: scroll;
	max-height: 320px;
}
</style>

<div class="" style="width:1200px;border:0px solid #ddd;margin:20px auto;padding:15px;">
<!-- href="/order/order/import-manual-order-modal" target="_modal"  -->
<a href="/template/手工订单 导入 模板.xls" class="pull-right" style="  margin: 8px 5px 0 20px;" >模板下载</a>
<a id="importManualOrder" class="iv-btn btn-important pull-right" title="导入订单" >导入订单</a>
 
  <h1 style="line-height: 35px;font-size: 25px;">新建手工订单 </h1>


<form id="manual-order-form"  class="form-horizontal" action="/order/order/saveManualOrder" method="post">
<?php 
echo Html::hiddenInput('order_source',$_REQUEST['platform']);

?>

	<div class="panel panel-default">
	<!------------------------------------------------ 订单信息 --------------------------------------------------------->
		<div class="panel-heading"><?=TranslateHelper::t('订单信息')?></div>
		<div class="panel-body">
		<!--------------------------------------------- 第一行 --------------------------------------------------------->
			<div class="row form-group">
				<?php
				$tmpColLabelName= "订单号";
				$tmpColName="order_source_order_id";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				
				<?php
				$tmpColLabelName= "买家邮箱";
				$tmpColName="consignee_email";
				$tmpRequire= "非必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
			</div>
			
			<div class="row form-group">
				<?php
				
				$tmpColLabelName= (isset(OdOrder::$orderSource[$_REQUEST['platform']])) ?OdOrder::$orderSource[$_REQUEST['platform']]: $_REQUEST['platform']."店铺";
				$tmpColName="selleruserid";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],$seller_array,['class'=>'iv-input']); //($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				
				<?php
				$tmpColLabelName= "币种";
				$tmpColName="currency";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				//echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],['USD'=>'USD','GBP'=>'GBP','EUR'=>'EUR','AUD'=>'AUD','CNY'=>'CNY','JPY'=>'JPY','CAD'=>'CAD'],['class'=>'iv-input']);
				?>
				</div>
				
				
			</div>
			
			<?php if (!empty($sites)):?>
			<div class="row form-group">
				<?php
				$tmpColLabelName= "站点";
				$tmpColName="order_source_site_id";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],$sites,['class'=>'iv-input']); //($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
			</div>
			<?php endif;?>
		
		</div>
	</div>

	<!------------------------------------------------ 发货地址 --------------------------------------------------------->
	<div class="panel panel-default">
		<div class="panel-heading"><?=TranslateHelper::t('发货地址')?></div>
		<div class="panel-body">
		<!--------------------------------------------- 第一行 --------------------------------------------------------->
			<div class="row form-group">
				
				<?php
				$tmpColLabelName= "买家姓名";
				$tmpColName="consignee";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				<?php
				$tmpColLabelName= "国家";
				$tmpColName="consignee_country_code";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				//echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
// 				echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],$country,['class'=>'iv-input']);
				
				echo Html::dropDownList($tmpColName, '', $country,['prompt'=>'请选择国家','class'=>'']);
				?>
				</div>
				
				
			</div>
			
		<!--------------------------------------------- 第二行 --------------------------------------------------------->
			<div class="row form-group">
				<?php
				$tmpColLabelName= "地址行1";
				$tmpColName="consignee_address_line1";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				<?php
				$tmpColLabelName= "邮编";
				$tmpColName="consignee_postal_code";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
			</div>
			
		<!--------------------------------------------- 第三行 --------------------------------------------------------->
			<div class="row form-group">
				<?php
				$tmpColLabelName= "行2";
				$tmpColName="consignee_address_line2";
				$tmpRequire= "非必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				<?php
				$tmpColLabelName= "电话";
				$tmpColName="consignee_phone";
				$tmpRequire= "非必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
			</div>
			
			<!--------------------------------------------- 第四行 --------------------------------------------------------->
			<div class="row form-group">
				<?php
				$tmpColLabelName= "城市";
				$tmpColName="consignee_city";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
				<?php
				$tmpColLabelName= "手机";
				$tmpColName="consignee_mobile";
				$tmpRequire= "非必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
				
			</div>
			
			<!--------------------------------------------- 第五行 --------------------------------------------------------->
			<div class="row form-group">
				
				
				<?php
				$tmpColLabelName= "省/州";
				$tmpColName="consignee_province";
				$tmpRequire= "必填";
				$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
				echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['class'=>'col-sm-1 control-label']);
				?>
				<div class="col-sm-5">
				<?php 
				echo Html::textInput($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
				?>
				</div>
			</div>
		</div><!-- end of  panel-body  -->
	</div>
	
	<!------------------------------------------------ 商品信息 --------------------------------------------------------->
	<div class="panel panel-default">
		<div class="panel-heading"><?=TranslateHelper::t('商品信息 ')?>
		<a class="cursor_pointer pull-right" onclick="manualOrder.addManualOrderItem(this)"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>增加一行</a>
		<a id="bathAddProduct" class="iv-btn btn-important pull-right" title="导入订单" style="margin: -7px 20px 0px 0px;" onclick="manualOrder.selectProd(this, 'all')">批量增加商品</a>
		</div>
		<div class="panel-body">
			<table id="order_item_info" class="table">
				<thead>
					<tr>
						<th width="30%"><span>*</span>商品SKU</th>
						
						<th><span>*</span>数量</th>
						<th><span>*</span>订单单价</th>
						<th>操作</th>
					</tr>
				</thead>
				
			</table>
		</div><!-- end of  panel-body  -->
	
	</div>
	
	<!------------------------------------------------ 订单备注 --------------------------------------------------------->
	<div class="panel panel-default">
		<div class="panel-heading"><?=TranslateHelper::t('订单备注')?></div>
		<div class="panel-body">
		<textarea class="form-control" rows="5" name="desc" maxlength="500"></textarea>
		</div><!-- end of  panel-body  -->
	
	</div>

	
	<?php 
	echo Html::label(TranslateHelper::t("订单商品金额"));
	?>
	<span id="div_order_subtotal" style="min-width: 100px;display: inline-block;">0</span>
	<?php 
	echo Html::label(TranslateHelper::t("运费"));
	echo Html::textInput('shipping_cost',0 ,['class'=>'iv-input']);
	echo Html::button(TranslateHelper::t('关闭'),['class'=>'iv-btn btn-default pull-right','onclick'=>'window.close();']);
	echo Html::button(TranslateHelper::t('保存'),['class'=>'iv-btn btn-success pull-right','onclick'=>'manualOrder.SaveManualOrder()']);
	
	?>
</form>

</div>