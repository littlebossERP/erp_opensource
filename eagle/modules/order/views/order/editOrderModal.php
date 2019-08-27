<?php 
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper;

if ($order->order_status == OdOrder::STATUS_PAY){
	$input_attr = [];
	$select_attr = [];
}else{
	$input_attr = ['readonly'=>"readonly"];
	$select_attr = ['disabled'=>"disabled"];
}


?>
<style>
.div-warehouse-help{
	margin: 0px 0px 40px 0;
}

img.platform-logo {
    height: 20px;
<?php if($order->order_source == 'amazon'): ?>
	background-color: black;
<?php endif;?>
}

.display_row{
	line-height: 26px;
}

#tabs-consignee .row ,#tabs-shipmethod .row ,#tabs-billing-info .row , div#div_warehouse, div#div_ShippingServices{
	line-height: 30px;
}

#memo-info-edit textarea{
	width:100%
}

span.glyphicon.glyphicon-ok.text-success,span.glyphicon.glyphicon-remove.text-warn {
    margin-left: 20px;
}

.modal-box.inside-modal {
    width: 1100px;
}
<?php if ($order->order_source == 'ebay'):?>
div#tabs-shipmethod ,div#tabs-consignee ,div#tabs-memo ,div#tabs-declaration{
	
    height: 365px;
	overflow-y: auto;
}
<?php else:?>
div#tabs-shipmethod ,div#tabs-consignee ,div#tabs-memo ,div#tabs-declaration{
    height: 230px;
	overflow-y: auto;
}
<?php endif;?>
.panel-edit-order-head {
    margin: 5px 0px 15px 0;
}
#edt-order-tabs>ul>li>a{
	width:100%;
}
.ui-widget-header{
	color: #222222;
    font-weight: bold;
	background:white;
}

#edt-order-tabs{
	background-color: #f4f9fc;
}

#edt-order-tabs ul li.ui-tabs-active a{
	background-color: #f4f9fc;
}

#edt-order-tabs ul li a{
	background-color: #d9edf7;
}

.panel-edit-order-head>.edit-order-label{
	margin-left: 10px;
	
}

#table-buyer-info>tbody>tr>td>label{
	margin-right: 10px;
}

.myj-table {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
}
.mTop5 {
    margin-top: 5px;
}
.myj-table tr th {
    text-align: center;
    font-size: 13px;
    padding: 5px;
    height: 40px;
    background-color: #eee;
    border-bottom: 1px solid #ccc;
}
.myj-table tr td {
    border: 1px solid #ccc;
    text-align: center;
    font-size: 13px;
    padding: 3px;
    word-wrap: break-word;
    word-break: break-all;
}
.modal-content table tr td {
    font-size: 13px;
}
.fBlue {
    color: #337ab7!important;
}
.col-xs-5 {
    width: 51%;
}
.p0 {
    padding: 0;
}
.form-group {
    margin-bottom: 5px;
	overflow: auto;
}
.form-horizontal .form-group {
    margin-right: -15px;
    margin-left: -15px;
}
.form-horizontal .control-label {
    padding-top: 7px;
    margin-bottom: 0;
    text-align: right;
}
.col-sm-2 {
    width: 22%;
	margin-top: 11px;
}
.col-sm-8 {
    width: 75%;
}
.form-control {
	font-size: 13px;
    display: block;
    width: 100%;
    height: 34px;
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
    -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
    transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
}
.borderRdio {
    border-radius: 4px;
}
.border3 {
    border: 1px solid #ccc;
}
.rootskubtn-pd{
	padding-left: 7px;
	padding-right: 7px;
}
.btn.btn-disabled {
    background-color: #ccc;
    color: #fff;
    border: 1px solid #ddd;
    cursor: default;
}
.btn-defaul{
	color: #333;
    text-shadow: none;
    background-color: #eee;
    border-color: #ccc;
}
.mBottom20{
    margin-bottom: 20px;
}
</style>




	<?=Html::input('hidden','orderid',$order->order_id,['id'=>'edit-modal-order-id'])?>
	
	<div class="panel-edit-order" >
		<div class="panel-edit-order-head">
		<?php if (!empty(\eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source])):?>
		<img alt="<?= $order->order_source?>" src="<?= \eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source]?>" class="platform-logo">
		<?php endif;?>
		<?=Html::label('订单号:',null,['class'=>'edit-order-label'])?><span><?=$order->order_id?></span>
		<?= OrderFrontHelper::displayEditOrderPlatformOrderIDInfo($order)?>
		<?=Html::label('平台状态：',null,['class'=>'edit-order-label'])?><span><div style="display: inline-block;"><?php OrderFrontHelper::displayOrderSourceStatus($order,$orderCheckOutList)?></div></span>
		<?=Html::label('小老板状态：',null,['class'=>'edit-order-label'])?><span><?= OdOrder::$status[$order->order_status]?></span>
		</div>
		
		
	<div class="panel-edit-order-body">
		<div class="order-edit-row">
			
			<table id="table-buyer-info" class="table table-bordered" style="font-size:12px;border:0px;margin-bottom: 5px;">
				<tr>
					<td style="border:1px solid #d9effc;text-align:left;">
					<?=Html::label('卖家账号:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?=(isset($selleruserids_new[$order->selleruserid]) ? $selleruserids_new[$order->selleruserid] : $order->selleruserid) ?></b>
					<p>
					<?php 
				     switch (true){
				     	case in_array($order->order_source , ['ebay']):
				     		if (!empty($paypal->transactionid)){
				     			echo Html::label('PP交易号:',null,['class'=>'edit-order-label'])." ".@$paypal->transactionid;
				     		}
				     	break;
				     	default:
				     	break;
				     }
				     
				     ?>
					</p>
					</td>
					<td style="border:1px solid #d9effc;text-align:left;">
						<?=Html::label('买家账号:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?=$order->source_buyer_user_id?></b><br/>
						<?=Html::label('买家姓名:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?=$order->consignee?></b><br/>
						<?=Html::label('买家邮箱:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;" data-key="consignee_email"><?=$order->consignee_email?></b> <span class="glyphicon glyphicon-edit" onclick="OrderCommon.showEditOrderEmialDialog('<?=$order->consignee_email?>');"></span><br/>
					
					</td>
					<td style="border:1px solid #d9effc;text-align:left;">
						<?php OrderFrontHelper::displayEditOrderAmountInfoHTML($order);?>
					</td>
				</tr>
			</table>
		</div>
		<!-- 
		<div class="order-edit-row">	
			<?=html::label('时间记录：',null,['class'=>'edit-order-title-label'])?>
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
				<tr>
					<td><?=Html::label('下单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('付款日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
					<td><?=Html::label('通知平台发货日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('打单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
					<td><?=Html::label('下单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('出库日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
				</tr>
			</table>
			
		</div>
		 -->
<div class="panel panel-info">
	  <div class="panel-heading" style="height: 40px;" ><span data-type="title">收货地址</span> 
	  <div id='div-tab-normal-button-list' class="pull-right"><button type="button" class="iv-btn btn-important" onclick="OrderCommon.editOrderBtnGroupSetting('edit')">编辑</button></div>
	  <div id='div-tab-edit-button-list' class="pull-right hidden">
	  	<button id="order-declaration-reset" type="button" class="iv-btn btn-reset hidden"  onclick="OrderCommon.editOrderdeclarationresset()">重置</button>
	  	<button type="button" class="iv-btn btn-success" onclick="OrderCommon.editOrderSaveInfo('<?=$order->order_id ?>')">保存</button>
	  	<button type="button" class="iv-btn btn-warn"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">取消</button>
	  </div>
	  <?php if($order->order_source=='cdiscount'){?>
	  <div id='billing-to-consignee-button' class="pull-right hidden"><button type="button" class="iv-btn btn-important" onclick="OrderCommon.copyBillingInfoToShippingInfo()">套用发票信息</button></div>
	  <?php } ?>
	  </div>
	  <div class="panel-body nopadding">

	  
	  <form id="frm_order_edit" action="" method="post">
	 
	  <div id="edt-order-tabs">
  <ul>
    <li data-div-id="consignee-info"><a href="#tabs-consignee"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">收货地址<span class="<?= $HealthCheckClassList['consignee']?>"></span></a></li>
    <li data-div-id="order-declaration"><a href="#tabs-declaration"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">报关信息<span class="<?= $HealthCheckClassList['declaration']?>"></span></a></li>
    <li data-div-id="warehouse-shipservice"><a href="#tabs-shipmethod"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">运输服务<span class="<?= $HealthCheckClassList['shipmethod']?>"></span></a></li>
    <!-- 
    <li data-div-id="declaration_info"><a href="#tabs-declaration" onclick="OrderCommon.editOrderBtnGroupSetting('view')">报关信息</a></li>
     -->
    <li data-div-id="memo-info"><a href="#tabs-memo"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">备注信息</a></li>
	<?php if($order->order_source=='cdiscount'):?>
	<li data-div-id="billing-info"><a href="#tabs-billing-info"  onclick="OrderCommon.editOrderBtnGroupSetting('view')">发票地址</a></li>
	<?php endif;?>
  </ul>
  <!-- 收货地址 -->
  <div id="tabs-consignee" style="padding-top: 0px;">
     <?php 
     switch (true){
     	case in_array($order->order_source , ['ebay']):
     		OrderFrontHelper::displayEditEbayOrderShippingAddressHtml($order ,$countryList, $paypal);
     	break;
     	default:
     		OrderFrontHelper::displayEditOrderShippingAddressHtml($order , $countryList );
     	break;
     }
     
     
     
     ?>
	
    
  </div> <!-- end of tabs-consignee  -->
  
    <!-- 报关 -->
  <div id="tabs-declaration">
  <div id="order-declaration-view" class="col-md-12"><?php OrderFrontHelper::displayViewOrderDeclarationInfo($order)?></div>
  <div id="order-declaration-edit" class="col-md-12 hidden"><?php OrderFrontHelper::displayEditOrderDeclarationInfo($order)?></div>

  </div>
  <!-- 报关 end-->
			
		    
		  <!-- 运输服务  -->
		  <div id="tabs-shipmethod">
		  	<div  id="warehouse-shipservice-view"  class="col-md-12">
				<div class="row">
					<?php
					$tmpColLabelName= "仓库";
					$tmpColName="default_warehouse_id";
					?>
					<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
					<div class="col-md-10"><?= html::label(@$warehouses[$order->$tmpColName],null,['class'=>'text-left', 'data-key'=>$tmpColName])?>
					</div>
				</div>
				<?php
				if($order->order_status==300){
				?>
				<div class="row">
					<label class="col-md-2 control-label p0">获取单号方式:</label>
					<div class="col-md-10 pTop6" id="updateTrackNumLabel"><?php echo $order->default_shipping_method_code=='manual_tracking_no'?'手动获取':'自动获取'; ?></div>
				</div>
				<?php 
				}
				?>
				<div class="row">
					<?php
					$tmpColLabelName= "运输服务";
					$tmpColName="default_shipping_method_code";
					
					?>
					<div class="col-md-2 nopadding text-nowrap"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
					<div class="col-md-10" id="showshippingmethod">
						<?php 
							if($order->default_shipping_method_code=='manual_tracking_no'){
								$ShippingCodeByPlatformArr=\eagle\models\OdOrderShipped::find()->select(['shipping_method_name','shipping_method_code'])->where(['order_id'=>$order->order_id])->orderBy(["created" => SORT_DESC])->asArray()->one();
								?>
								<label data-key="<?php echo $ShippingCodeByPlatformArr['shipping_method_code'];?>"><?php echo $ShippingCodeByPlatformArr['shipping_method_name'];?></label>
								<?php 
							}
							else{
								?>
								<?= html::label(@$shipmethodList[$order->$tmpColName],null,[ 'data-key'=>$order->$tmpColName])?>
								<?php
							}
						?>
					</div>
				</div>
				<?php
				if($order->order_status==300){
				?>
				<div class="row">
					<label class="col-md-2 control-label p0">跟踪号:</label>
					<div class="col-md-10 pTop6" id="trackNumberDiv"><?php echo $order->tracking_number; ?></div>
				</div>
				<div id="div_trackingNumberDiv_msg_sh" class="row displaycss"></div>
				<?php 
				}
				?>
				<div class="row">
					<?php
					$tmpColLabelName= "客选物流";
					
					?>
					<div class="col-md-2 nopadding text-nowrap"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
					<div class="col-md-10">
					<?php if (empty($customerShippingMethod)){
						echo "无";
					}else{
						$customerShippingMethodStr = "";
						foreach($customerShippingMethod as $row){
							if (!empty($tmpStr)) $tmpStr .=',';
							$customerShippingMethodStr .= $row;
						}//end of each $customerShippingMethod
						echo $customerShippingMethodStr;
					}?>
					</div>
				</div>
		 	</div>
		  	<div  id="warehouse-shipservice-edit"  class="col-md-12 hidden">
			  	<?php 
			  	$default_shipping_method_code=$order->default_shipping_method_code;
				if (! isset($default_shipping_method_code)) $default_shipping_method_code = '';
					OrderFrontHelper::getShippingServicesHtml($order->order_source, $warehouses, $shipmethodList , $default_shipping_method_code,$order);
				?>
				<div class="row">
					<?php
					$tmpColLabelName= "客选物流";
					
					?>
					<span class="text-nowrap" ><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right','style'=>'width:12%;text-align: left;'])?></span>
					<span class="">
					<?php if (empty($customerShippingMethod)){
						echo "无";
					}else{
						echo $customerShippingMethodStr;
					}?>
					</span>
				</div>
		 	 </div>
		    
		  </div><!-- 运输服务 结束 -->

  
		  <!-- 备注信息 -->
		  <div id="tabs-memo">
		  	<div id="memo-info-view"><?php echo nl2br($order->desc)?></div>
		    <div id="memo-info-edit" class="hidden"><textarea rows="7" name="desc"><?php echo $order->desc?></textarea></div>
		  </div><!-- 备注信息 -->
  		  
		  <!-- 发票地址信息 开始  -->
		  <?php if($order->order_source=='cdiscount'):?>
		  <div id="tabs-billing-info">
		  	<?php 
		  	OrderFrontHelper::displayEditOrderBillingAddressHtml($order, $countryList);
		    ?>
		  </div>
		  <?php endif; ?>
		  <!-- 发票地址信息 结束  -->
		</div>
	  </form>
	  </div>
	</div>	
	
		<div class="div-warehouse-help"><a href="http://www.littleboss.com/word_list_44_228.html" target="_blank"  class="pull-right" style="
	    margin-bottom: 5px;
	    margin-top: -10px;
	    text-decoration: underline;
	"> 海外仓商品配对指导</a></div>
		<div id="div-order-item-info"  class="order-edit-row">
			<?php OrderFrontHelper::displayEditOrderItemInfo($order, $existProductResult, $order_rootsku_product_image)?>
		</div>
	</div>
	</div>

<div class="modal-footer" style="padding-top: 0px;padding-bottom: 0px;">
	<a data-event="reject" class="iv-btn btn-info">关闭</a>
</div>

<div class="modal-body tab-content" id="dialog" style="display:none;">

				<div id="titleRelation" class="col-xs-12 mTop10 mBottom10 pLeft10s f14">是否确认修改报关信息？</div>
                <label class="mLeft30"><input type="radio" value="0" name="warningRemoveRelation" id="warningRemoveRelation1" checked> 仅对当前订单的SKU生效</label><br>
                <?php 
                	if($order->order_status==200 || $order->order_status==100 || ($order->order_status==300 && ($order->carrier_step==0 || $order->carrier_step==4)))
                		$hiddenview='';
                	else
                		$hiddenview='hidden';
                ?>
                <label class="mLeft30 <?php echo $hiddenview;?>"><input type="radio" value="1" name="warningRemoveRelation" id="warningRemoveRelation2"> 修改相同SKU的所有「已付款」订单</label><br>
                <label class="mLeft30 <?php echo $hiddenview;?>"><input type="radio" value="2" name="warningRemoveRelation" id="warningRemoveRelation3"> 修改相同SKU的所有「已付款」订单和以后的新订单</label>
               
</div>
<div class="modal-body tab-content" id="dialog2" style="display:none;">

				<div id="titlePairproduct" class="col-xs-12 mTop10 mBottom10 pLeft10s f14">确认要解除配对关系？</div>
                <label class="mLeft30"><input type="radio" value="0" name="warningPairproduct" id="warningPairproduct1" checked> 仅对当前订单的SKU生效</label><br>
                <label class="mLeft30"><input type="radio" value="1" name="warningPairproduct" id="warningPairproduct2"> 修改相同SKU的所有「已付款」订单</label><br>
                <label class="mLeft30"><input type="radio" value="2" name="warningPairproduct" id="warningPairproduct3"> 修改相同SKU的所有「已付款」订单和以后的订单</label>
               
</div>

<div style="position:absolute;top:35%;left:1109px;width:120px;text-align:center;" id="upOrDownDiv">
					<span data-toggle="tooltip" data-placement="top" id="upShow" data-html="true" title="" style="display:inline-block;width:100px;" data-original-title="<?php echo ($upOrDownDiv['cursor']==1||empty($upOrDownDiv['cursor']))?'已经是第一个了':''; ?>">
						<button type="button" id="upbtnForOrder" class="btn btn-default btn-lg mBottom20 <?php echo ($upOrDownDiv['cursor']==1 || empty($upOrDownDiv['cursor']))?'btn-disabled':''; ?>" onclick="<?php echo ($upOrDownDiv['cursor']==1||empty($upOrDownDiv['cursor']))?'':'OrderCommon.upOrDownOrder(\'up\',\''.$upOrDownDiv['up'].'\');'; ?>"><strong>上一个</strong></button>
					</span>
					<span data-toggle="tooltip" data-placement="top" id="downShow" data-html="true" title="" style="display:inline-block;width:110px;" data-original-title="<?php echo ($upOrDownDiv['cursor']==3||empty($upOrDownDiv['cursor']))?'已经是最后一个了':''; ?>">
						<button type="button" id="downbtnForOrder" class="btn btn-default btn-lg <?php echo ($upOrDownDiv['cursor']==3||empty($upOrDownDiv['cursor']))?'btn-disabled':''; ?>" onclick="<?php echo ($upOrDownDiv['cursor']==3||empty($upOrDownDiv['cursor']))?'':'OrderCommon.upOrDownOrder(\'down\',\''.$upOrDownDiv['down'].'\');'; ?>"><strong>下一个</strong></button>
					</span>
					<input type="hidden" id="upOrDownDivtxt" value='<?php echo $upOrDownDivtxt; ?>'>
</div>

<script>
  
  </script>
  <style>
  .ui-tabs-vertical { width: 100%; }
  .ui-tabs-vertical .ui-tabs-nav { padding: 0px; float: left; width: 15%; }
  .ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0; }
  .ui-tabs-vertical .ui-tabs-nav li a { display:block; }
  .ui-tabs-vertical .ui-tabs-nav li.ui-tabs-active { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; }
  .ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: right; width: 85%;}
  .nopadding{padding:0; }
  </style>
