<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\carrier\SysCarrierParam;
use eagle\models\OdOrderShipped;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\order\models\OdOrder;
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/orderSearch.js", [
		'depends' => [
		'yii\web\JqueryAsset'
		]
		] );
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['consignee_country_code'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['consignee_country_code']),true)).";" , \yii\web\View::POS_READY);
}
$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.customCondition=".json_encode(@$custom_condition).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($OrderTagHelper::getTagColorMapping()).";" , \yii\web\View::POS_READY);
$orderStatus21 = OdOrder::getOrderStatus('oms21'); // oms 2.1 的订单状态
?>

<?php echo $this->render('_leftmenu');?>

<style>
.btn_tag_qtip a {
  margin-right: 5px;
}
.div_select_tag>.input-group , .div_new_tag>.input-group{
  float: left;
  width: 32%;
  vertical-align: middle;
  padding-right: 10px;
  padding-left: 10px;
  margin-bottom: 10px;
}

.div_select_tag{
	display: inline-block;
	border-bottom: 1px dotted #d4dde4;
	margin-bottom: 10px;
}
.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}
.div_add_tag{
	width: 600px;
}
</style>
<div class="content-wrapper" >
<!-- --------------------------------------------搜索 begin--------------------------------------------------------------- -->
	<div>
		<!-- 搜索区域 -->
		<form class="form-inline" id="form1" name="form1" action="" method="post">
		<div style="margin:30px 0px 0px 0px">
		<!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
		<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'卖家账号'])?>
		<!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
			<div class="input-group iv-input">
		        <?php $sel = [
					'order_id'=>'小老板订单号',
		        	'order_source_order_id'=>'平台订单号',
					'sku'=>'SKU',
					'order_source_itemid'=>'平台物品号',
					'customer_number'=>'物流号',
					'buyeid'=>'买家账号',
		        	'consignee'=>'买家姓名',
					'email'=>'买家Email',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num'])?>
		      	
		    </div>
		    <!----------------------------------------------------------- 模糊搜索 ----------------------------------------------------------->
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    
		    
		    <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
		    <?=Html::submitButton('筛选',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
	    	<?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
	    	<!----------------------------------------------------------- 高级搜索 ----------------------------------------------------------->
	    	<a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>	 
	    	<!----------------------------------------------------------- 常用筛选 ----------------------------------------------------------->
	    	<?php
	    	if (!empty($custom_condition)){
	    		$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($custom_condition));
	    	}else{
	    		$sel_custom_condition =['0'=>'加载常用筛选'];
	    	}
	    	
	    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
	    	<?=Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
	    	<div style="height:30px"></div>
	    	<!----------------------------------------------------------- 保持高级搜索展开 ----------------------------------------------------------->
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
	    	<!----------------------------------------------------------- 国家----------------------------------------------------------->
			<div class="input-group"  name="div-select-nation"  data-role-id="0"  style='margin:0px'>
				<?=Html::textInput('consignee_country_code',@$_REQUEST['consignee_country_code'],['class'=>'iv-input','placeholder'=>'请选择国家'])?>
			</div>
			<!----------------------------------------------------------- 小老板订单状态----------------------------------------------------------->
			<?=Html::dropDownList('order_status',@$_REQUEST['order_status'],$orderStatus21,['class'=>'iv-input','prompt'=>'小老板订单状态','id'=>'order_status'])?>
			<!----------------------------------------------------------- 仓库 ----------------------------------------------------------->
			<?=Html::dropDownList('default_warehouse_id',@$_REQUEST['default_warehouse_id'],$warehouse,['class'=>'iv-input','prompt'=>'仓库','id'=>'default_warehouse_id'])?>
			<!----------------------------------------------------------- 销售平台 ----------------------------------------------------------->
			<?=Html::dropDownList('order_source',@$_REQUEST['order_source'],$OdOrder::$orderSource,['class'=>'iv-input','prompt'=>'销售平台','id'=>'order_source'])?>
			<!----------------------------------------------------------- 物流商 ----------------------------------------------------------->
			<?php 
			echo Html::dropDownList('default_carrier_code',@$_REQUEST['default_carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code'])
			?>
			<!----------------------------------------------------------- 运输服务----------------------------------------------------------->
			<?=Html::dropDownList('default_shipping_method_code',@$_REQUEST['default_shipping_method_code'],$services,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod'])?>
			<!----------------------------------------------------------- 自定义标签 ----------------------------------------------------------->
			<?=Html::dropDownList('custom_tag',@$_REQUEST['custom_tag'],$all_tag_list,['class'=>'iv-input'])?>
			<!----------------------------------------------------------- 重新发货类型 ----------------------------------------------------------->
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=$OdOrder::$reorderType;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input'])?>
			<!----------------------------------------------------------- 评价 ----------------------------------------------------------->
			<?=Html::dropDownList('order_evaluation',@$_REQUEST['order_evaluation'],$OdOrder::$orderEvaluation,['class'=>'iv-input','prompt'=>'评价','id'=>'order_evaluation'])?>
			<!----------------------------------------------------------- tracker状态 ----------------------------------------------------------->
			<?php $TrackerStatusList = [''=>'tracker状态'];
			 	$tmpTrackerStatusList = Tracking::getChineseStatus('',true);
				$TrackerStatusList+= $tmpTrackerStatusList?>
			 <?=Html::dropDownList('tracker_status',@$_REQUEST['tracker_status'],$TrackerStatusList,['class'=>'iv-input','style'=>'width:110px;margin:0px'])?>
			<!----------------------------------------------------------- 日期搜索----------------------------------------------------------->
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'iv-input'])?>
        	<?=Html::input('date','date_from',@$_REQUEST['date_from'],['class'=>'iv-input'])?>
        	至
			<?=Html::input('date','date_to',@$_REQUEST['date_to'],['class'=>'iv-input'])?>
			<?=Html::dropDownList('ordersorttype',@$_REQUEST['ordersorttype'],['desc'=>'降序','asc'=>'升序'],['class'=>'iv-input','id'=>'ordersorttype'])?>
			<!----------------------------------------------------------- 排序 ----------------------------------------------------------->	
			<?=Html::dropDownList('customsort',@$_REQUEST['customsort'],[''=>'排序','order_id asc'=>'流水号升序','order_id desc '=>'流水号降序','grand_total asc '=>'金额升序','grand_total desc'=>'金额降序'],['class'=>'iv-input'])?>
			<!----------------------------------------------------------- 商品数量 ----------------------------------------------------------->
			商品数量：	
			<?=Html::dropDownList('item_qty_compare_operators',@$_REQUEST['item_qty_compare_operators'],['>'=>'大于','='=>'等于','<'=>'小于'],['class'=>'iv-input'])?>
			<?=Html::input('text','item_qty',@$_REQUEST['item_qty'],['class'=>'iv-input'])?>
			<br>
			<div style="height:30px"></div>
			<!----------------------------------------------------------- 系统标签 ----------------------------------------------------------->
			<strong style="font-weight: bold;font-size:14px;">系统标签：</strong>
			<?php 
			echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			<?php 
			echo Html::CheckboxList('order_systags',@$_REQUEST['order_systags'],$OrderTagHelper::$OrderSysTagMapping);
			?>
			<div style="height:20px"></div>
			 </div> 
			 <?php //=Html::hiddenInput('trackstatus',@$_REQUEST['trackstatus'],['id'=>'trackstatus'])?>	
				
	    </div>
		</form>
	</div>
<!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->

<div class="form-group" >
	<ul class="nav nav-pills"><!-- btn-tab -->
		<li role="presentation" ><input type="button" class ="iv-btn btn-important" onclick="moveToUpload()" value="重新导出" /></li>
		<li role="presentation" ><input type="button" class ="iv-btn btn-important" onclick="changeServerToUpload()" value="更改运输服务" /></li>
		<li role="presentation" ><?= html::dropDownList('excelCarriers',@$_REQUEST['default_carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','onchange'=>'changeExcelCarrier($(this).val())'])?></li>
		<li role="presentation" ><input type="button" class="iv-btn btn-important" onclick="exportExcel('<?= @$_REQUEST['default_carrier_code']?>')" value="导出" /></li>
		<li role="presentation" ><?= Html::dropdownlist('shipmethod',@$_REQUEST['default_shipping_method_code'],[''=>'请先选择运输服务再打印']+$services,['class'=>'iv-input','style'=>'width:260px','onchange'=>'changeShippingService(this)'])?></li>
		<li role="presentation" ><input type="button" class="iv-btn <?= (isset($printMode['is_custom_print']) && !empty($printMode['is_custom_print']))?'btn-important':'disabled'?>" onclick="<?= (isset($printMode['is_custom_print']) && !empty($printMode['is_custom_print']))?"doprint('custom')":''?>" value="自定义标签打印" /></li>
		<li role="presentation" >
		<input type="button" data-toggle="modal" data-target="#myModal" class ="iv-btn btn-important" onclick="" value="导入跟踪号" />
		</li>
	</ul>
</div>
	<?php $divTagHtml = "";?>
<table class="table table-condensed table-bordered" style="font-size:12px;table-layout: fixed;word-break: break-all;">
			<tr>
				<th width="2%">
				<input type="checkbox" check-all="e1" />
				</th>
				<th width="10%"><b>小老板单号</b></th>
				<th width="4%"><b>平台</b></th>
				<th width="8%"><b>账号</b></th>
				<th width="8%"><b>平台订单号</b></th>
				<th width="4%"><b>国家</b></th>
				<th width="12%"><b>SKU x 数量</b></th>
				<th width="10%"><b>物流商/物流单号</b></th>
				<th width="10%"><b>仓库</b></th>
				<th width="10%"><b>运输服务/跟踪号</b></th>
				<th width="10%"><b>客户参考号</b></th>
			</tr>
			<?php if (count($orders)):foreach ($orders as $order):?>
			<tr style="background-color: #f4f9fc" data="<?= $order->order_id?>" deliveryid="<?= $order->delivery_id?>">
				<td>
				<input type="checkbox" name="order_id[]" class="ck"  value="<?=$order->order_id?>" data-check="e1" />
				</td>
				<td>
					<?= TranslateHelper::t($order->order_id)?><br>
					<?php if ($order['exception_status']>0&&$order['exception_status']!='201'):?>
								<div title="<?=$OdOrder::$exceptionstatus[$order['exception_status']]?>" class="exception_<?=$order['exception_status']?>"></div>
							<?php endif;?>
							<?php if (strlen($order['user_message'])>0):?>
								<div title="<?=$OdOrder::$exceptionstatus[$OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=$OdOrder::EXCEP_HASMESSAGE?>"></div>
							<?php endif;?>
		            <?php 
		            $divTagHtml .= '<div id="div_tag_'.$order['order_id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
		            $TagStr = $OrderTagHelper::generateTagIconHtmlByOrderId($order['order_id']);
		            if (!empty($TagStr)){
		            	$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$order['order_id']."' >$TagStr</span>";
		            }
		            echo $TagStr;
		            ?>
				</td>
				<td><?= TranslateHelper::t($OdOrder::$orderSource[$order->order_source])?></td>
				<td><?= TranslateHelper::t($order->selleruserid)?></td>
				<td><?= TranslateHelper::t($order->order_source_order_id)?></td>
				<td>
					<label title="<?=$order->consignee_country?>"><?=$order->consignee_country_code?></label>
				</td>
				<td>
					<?php if (count($order->items)):foreach ($order->items as $item):?>
					<?php if (isset($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
					<?php endif;?>
					<?php endforeach;endif;?>
				</td>
				<td>
					<?=isset($carriers[$order->default_carrier_code])?$carriers[$order->default_carrier_code]:'未匹配到物流商'; ?>
					<?php echo $order['customer_number'];?>
				</td>
				<td>
					<?= @$warehouse[@$order->default_warehouse_id]?>
				</td>
				<td>
				<?=isset($allshippingservices[$order->default_shipping_method_code])?$allshippingservices[$order->default_shipping_method_code]:'未匹配到运输服务'; ?><br>
            	<?php echo $order['customer_number'];?>
            	<span class="message"></span>
				</td>
				<td>
					<?php echo $order['customer_number'];?>
					<?php //TranslateHelper::t($order->order_source_order_id)?>
				</td>
			</tr>
			<?php endforeach;endif;?>
			</table>
	
		<?php if($pagination):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 , 500 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
			</div>
			</div>
		<?php endif;?>
	
	</div>
<?=$divTagHtml?>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <form  enctype="multipart/form-data"?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">导入跟踪号</h4>
      </div>
      <div class="modal-body">
        <input type="file" name="order_customer_number" id="order_customer_number" ><br>
        <a href="<?=Url::home()."template/ordercustomer_number_sample.xls"?>">范本下载</a>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
        <button type="button" class="btn btn-primary" id="save" onclick="importordercustomer_number()">提交</button>
      </div>
    </div>
  </div>
  </form>
</div>
<script>
//单次操作
function doactionForOneOrder(val,orderid){
	//如果没有选择订单，返回；
	if(val==""){
		$.alert('请选择您的操作','info');
		return false;
    }
	if(orderid==""){
		$.alert('无选择订单','info');
		return false;
    }
	var idstr = [];
	idstr.push(orderid);
	switch(val){
		case 'suspenddelivery'://暂停发货
			//遮罩
			 $.maskLayer(true);
			 $.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr,m:'delivery',a:'物流商下单->暂停发货'},function(result){
				 //var r = $.parseJSON(result);
				 var event = $.alert(result,'success');
				 //var event = $.confirmBox(r.message);
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
			});
			break;
		case 'outofstock'://标记缺货
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:idstr,m:'delivery',a:'物流商下单->标记缺货'},function(result){
				//var r = $.parseJSON(result);
				 var event = $.alert(result,'success');
				 //var event = $.confirmBox(r.message);
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
				
			});
			break;
		default:
			return false;
			break;
	}
}
//添加自定义标签
function setusertab(orderid,tabobj){
	var tabid = $(tabobj).val();
	$.post('<?=Url::to(['/order/aliexpressorder/setusertab'])?>',{orderid:orderid,tabid:tabid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
		}else{
			bootbox.alert(result);
		}
	});
}
//保存常用筛选
function showCustomConditionDialog(){
	var html = '<label>'+Translator.t('筛选条件名称')+'</label><?=Html::textInput('filter_name',@$_REQUEST['filter_name'],['class'=>'iv-input','id'=>'filter_name'])?>';
	var modalbox = bootbox.dialog({
		title: Translator.t("保存为常用筛选条件"),
		className: "", 
		message: html,
		buttons:{
			Ok: {  
				label: Translator.t("保存"),  
				className: "btn-primary",  
				callback: function () { 
					if ($('#filter_name').val() == "" ){
						bootbox.alert(Translator.t('请输入筛选条件名称!'));
						return false;
					}

					saveCustomCondition(modalbox , $('#filter_name').val() );
					return false;
					//result = ListTracking.AppendRemark(track_no , $('#filter_name').val());
				}
			}, 
			Cancel: {  
				label: Translator.t("返回"),  
				className: "btn-default",  
				callback: function () {  
				}
			}, 
		}
	});	
}
//保存自定义标签
function saveCustomCondition(modalbox , filter_name){
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/carrier/carrierprocess/append-custom-condition?custom_name='+filter_name, 
			data: $('#form1').serialize(),
			success: function (result) {
				if (result.success == false){
					bootbox.alert(result.message);	
					return false
				}
				modalbox.modal('hide');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
}
//上传物流单号
function importordercustomer_number(){
	if($("#order_customer_number").val()){
		$.ajaxFileUpload({  
			 url:'<?=Url::to(['/order/aliexpressorder/importordercustomer_number'])?>',
		     fileElementId:'order_customer_number',
		     type:'post',
		     dataType:'json',
		     success: function (result){
			     if(result.ack=='failure'){
					bootbox.alert(result.message);
				 }else{
					bootbox.alert('操作已成功');
				 }
		     },  
			 error: function ( xhr , status , messages ){
				 bootbox.alert(messages);
		     }  
		});  
	}else{
		bootbox.alert("请添加文件");
	}
}
</script>