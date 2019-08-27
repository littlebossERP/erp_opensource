<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\tracking\models\Tracking;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['country'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['country']),true)).";" , \yii\web\View::POS_READY);
}
$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.customCondition=".json_encode(@$counter['custom_condition']).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);

?>
<style>
.table td,.table th{
	text-align: center;
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
<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php echo $this->render('_leftmenu',['counter'=>$counter]);?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<div class="content-wrapper" >
<!---------------------------------------- oms 2.1 nav start  --------------------------------------------------->
<?php echo $order_nav_html;?>
<!---------------------------------------- oms 2.1 nav end  ------------------------------------------------------>
<div style="height:10px"></div>
<div>
<ul class="nav nav-pills"><!-- main-tab -->
  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['distribution_inventory_status'])&&$_REQUEST['distribution_inventory_status'] == OdOrder::DISTRIBUTION_INVENTORY_NO){echo 'btn-important';}?>" href="/delivery/order/listdistributioninventory?warehouse_id=<?=@$_REQUEST['warehouse_id']?>&delivery_status=0&distribution_inventory_status=2"><?=TranslateHelper::t('待分配').'('.$counter['distributionInventoryStatus'][OdOrder::DISTRIBUTION_INVENTORY_NO].')'?></a></li>
  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['distribution_inventory_status'])&&$_REQUEST['distribution_inventory_status'] == OdOrder::DISTRIBUTION_INVENTORY_OUTOFSTOCK){echo 'btn-important';}?>" href="/delivery/order/listdistributioninventory?warehouse_id=<?=@$_REQUEST['warehouse_id']?>&delivery_status=0&distribution_inventory_status=3"><?=TranslateHelper::t('缺货').'('.$counter['distributionInventoryStatus'][OdOrder::DISTRIBUTION_INVENTORY_OUTOFSTOCK].')'?></a></li>
  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['distribution_inventory_status'])&&$_REQUEST['distribution_inventory_status'] == OdOrder::DISTRIBUTION_INVENTORY_ALREADY){echo 'btn-important';}?>" href="/delivery/order/listdistributioninventory?warehouse_id=<?=@$_REQUEST['warehouse_id']?>&delivery_status=0&distribution_inventory_status=4" ><?=TranslateHelper::t('已分配').'('.$counter['distributionInventoryStatus'][OdOrder::DISTRIBUTION_INVENTORY_ALREADY].')'?></a></li>
</ul>
</div>

<!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
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
					'tracknum'=>'物流号',
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
	    	if (!empty($counter['custom_condition'])){
	    		$sel_custom_condition = array_merge(['加载常用删选'] , array_keys($counter['custom_condition']));
	    	}else{
	    		$sel_custom_condition =['0'=>'加载常用删选'];
	    	}
	    	
	    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
	    	<?=Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
	    	<div style="height:30px"></div>
	    	<!----------------------------------------------------------- 保持高级搜索展开 ----------------------------------------------------------->
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
	    	<!----------------------------------------------------------- 国家----------------------------------------------------------->
			<div class="input-group"  name="div-select-nation"  data-role-id="0"  style='margin:0px'>
				<?=Html::textInput('country',@$_REQUEST['country'],['class'=>'iv-input','placeholder'=>'请选择国家'])?>
			</div>
			<!----------------------------------------------------------- 销售平台 ----------------------------------------------------------->
			<?=Html::dropDownList('order_source',@$_REQUEST['order_source'],OdOrder::$orderSource,['class'=>'iv-input','prompt'=>'销售平台','id'=>'order_source'])?>
			<!----------------------------------------------------------- 物流商 ----------------------------------------------------------->
			<?php 
			// 物流商
			$carriersProviderList = CarrierOpenHelper::getOpenCarrierArr('2');
			
			echo Html::dropDownList('carrier_code',@$_REQUEST['carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code'])
			?>
			<!----------------------------------------------------------- 运输服务----------------------------------------------------------->
			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod'])?>
			<!----------------------------------------------------------- 自定义标签 ----------------------------------------------------------->
			<?=Html::dropDownList('sel_tag',@$_REQUEST['sel_tag'],$all_tag_list,['class'=>'iv-input'])?>
			<!----------------------------------------------------------- 重新发货类型 ----------------------------------------------------------->
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=Odorder::$reorderType ;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input'])?>
			<!----------------------------------------------------------- 评价 ----------------------------------------------------------->
			<?=Html::dropDownList('order_evaluation',@$_REQUEST['order_evaluation'],OdOrder::$orderEvaluation,['class'=>'iv-input','prompt'=>'评价','id'=>'order_evaluation'])?>
			<!----------------------------------------------------------- 订单数量 ----------------------------------------------------------->
			<?=Html::dropDownList('quantity_type',@$_REQUEST['quantity_type'],['1'=>'单商品订单','>1'=>'多商品订单'],['prompt'=>'订单数量','class'=>'iv-input'])?>
			<!----------------------------------------------------------- 日期搜索----------------------------------------------------------->
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'iv-input'])?>
        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'iv-input'])?>
        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'iv-input'])?>
			<?=Html::dropDownList('ordersorttype',@$_REQUEST['ordersorttype'],['desc'=>'降序','asc'=>'升序'],['class'=>'iv-input','id'=>'ordersorttype'])?>
			<!----------------------------------------------------------- 排序 ----------------------------------------------------------->	
			<?=Html::dropDownList('customsort',@$_REQUEST['customsort'],[''=>'排序','order_id asc'=>'流水号升序','order_id desc '=>'流水号降序','grand_total asc '=>'金额升序','grand_total desc'=>'金额降序'],['class'=>'iv-input'])?>
			<br>
			<div style="height:30px"></div>
			<!----------------------------------------------------------- 系统标签 ----------------------------------------------------------->
			<strong style="font-weight: bold;font-size:14px;">系统标签：</strong>
			<?php 
			echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			<?php 
			echo Html::CheckboxList('order_systags',@$_REQUEST['order_systags'],OrderTagHelper::$OrderSysTagMapping);
			?>
			<div style="height:20px"></div>
			 </div> 
			 <?php //=Html::hiddenInput('trackstatus',@$_REQUEST['trackstatus'],['id'=>'trackstatus'])?>	
				
	    </div>
		</form>
	</div>
<!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
<!-- --------------------------------------------列表  begin--------------------------------------------------------------- -->
	<div>
		<form name="a" id="a" action="" method="post">
		<div class="pull-left" style="height: 40px;">
		<?php 
		if (isset($_REQUEST['delivery_status']) && $_REQUEST['delivery_status'] =='0' && $_REQUEST['distribution_inventory_status'] =='2'){
			echo Html::button('分配库存',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('distributionInventory');"]);echo "&nbsp;";
			echo Html::button('暂停发货',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('suspenddelivery');"]);
		}elseif (isset($_REQUEST['delivery_status']) && $_REQUEST['delivery_status'] =='0' && $_REQUEST['distribution_inventory_status'] =='3'){
			echo Html::button(TranslateHelper::t('分配库存'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('distributionInventory');"]);echo "&nbsp;";
			echo Html::button(TranslateHelper::t('暂停发货'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('suspenddelivery');"]);echo "&nbsp;";
			echo Html::button(TranslateHelper::t('标记缺货'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('outofstock');"]);echo "&nbsp;";
			echo Html::button(TranslateHelper::t('库存处理'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('stockManage');"]);
		}elseif (isset($_REQUEST['delivery_status']) && $_REQUEST['delivery_status'] =='0' && $_REQUEST['distribution_inventory_status'] =='4'){
			echo Html::button(TranslateHelper::t('物流商下单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signtoplaceanorder');"]);echo "&nbsp;";
			echo Html::button(TranslateHelper::t('暂停发货'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('suspenddelivery');"]);
		
		}
		?>
		</div>
		<br>
			<table class="table table-condensed table-bordered" style="font-size:12px;">
			<tr>
				<th width="1%">
				<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox" check-all="e1" />
				</th>
				<th width="4%"><b>小老板单号</b></th>
				<th width="12%"><b>商品SKU</b></th>
				<th width="10%"><b>总价</b></th>
				<th width="18%"><b>下单日期/付款日期</b></th>
				<th width="6%"><b>运输服务</b></th>
				<th width="6%"><b>收件国家</b></th>
				<th width="10%"><b>平台</b></th>
				<th width="10%"><b>平台状态</b></th>
			</tr>
			<?php $carriers=CarrierApiHelper::getShippingServices(false); ?>
			<?php if (count($models)):foreach ($models as $order):?>
			<tr style="background-color: #f4f9fc">
				<td><span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,'<?=$order->order_id?>');"></span><input type="checkbox" name="order_id[]" class="ck"  value="<?=$order->order_id?>" data-check="e1" />
				</td>
				<td>
					<?=$order->order_id?><br>
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<div title="<?=OdOrder::$exceptionstatus[$order->exception_status]?>" class="exception_<?=$order->exception_status?>"></div>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
					<?php endif;?>
				</td>
				<td>
					<?php if (count($order->items)):foreach ($order->items as $item):?>
					<?php if (isset($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;<b>X<span <?php if ($item->quantity>1){echo 'class="multiitem"';}?>><?=$item->quantity?></span></b><br>
					<?php endif;?>
					<?php endforeach;endif;?>
				</td>
				<td>
					<?=$order->grand_total?>&nbsp;<?=$order->currency?>
				</td>
				<td>
				<?=$order->order_source_create_time>0?date('y/m/d H:i:s',$order->order_source_create_time)."</br>":''?>
			    <?=$order->paid_time>0?date('y/m/d H:i:s',$order->paid_time):''?>
					
				</td>
				<td>
					<?php if (strlen($order->default_shipping_method_code)){?>[<?=@$carriers[$order->default_shipping_method_code]?>]<?php }?>
				</td>
				<td>
					<label title="<?=$order->consignee_country?>"><?=$order->consignee_country_code?></label>
				</td>
				<td>
				<?php echo OdOrder::$orderSource[$order->order_source];?>
				</td>
				<td>
					<?php if (isset(OdOrder::$aliexpressStatus[$order->order_source_status])){echo OdOrder::$aliexpressStatus[$order->order_source_status];}else{echo $order->order_source_status;};?>
				</td>
				
			</tr>
				<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
				<tr class="xiangqing <?=$order->order_id?>">
					<td style="border:1px solid #d9effc;"><img src="<?=$item->photo_primary?>" width="60px" height="60px"></td>
					<td colspan="2" style="border:1px solid #d9effc;text-align:justify;">
					<font color="#ff9900">平台订单号:<b><?=$item->order_source_order_id?></b></font><br>
					SKU:<b><?=$item->sku?></b><br>
					<?= (empty($item->product_url) ? $item->product_name : '<a href="'.$item->product_url.'" target="_blank">'.$item->product_name.'</a>') ?>
					</td>
					<td  style="border:1px solid #d9effc">
						<?=$item->quantity?>
					</td>
					<?php if ($key=='0'):?>
					<td style="border:1px solid #d9effc">
						<b>详细配货商品</b>
					</td>
					<td rowspan="<?=count($order->items)?>" style="border:1px solid #d9effc;text-align:left;" class="text-nowrap">
						<font color="#8b8b8b">卖家账号:</font>
						<b><?=$order->selleruserid?></b><br>
						<font color="#8b8b8b">买家姓名:</font>
						<b><?=$order->consignee?></b><br>
						<font color="#8b8b8b">买家账号:</font>
						<b><?=$order->source_buyer_user_id?></b><br>
						<font color="#8b8b8b">买家邮箱:</font>
						<b><?=$order->consignee_email?></b>
					</td>
					<td colspan="2" rowspan="<?=count($order->items)?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc;text-align:left;">
						<font color="#8b8b8b">付款备注:</font><br><b class="text-warning"><?=$order->user_message?></b>
					</td>
					<td rowspan="<?=count($order->items)?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
					<span><font color="red"><?=$order->desc?></font></span>
						<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font color="00bb9b">备注</font></a>
					</td>
					<?php endif;?>
				</tr>	
				<?php endforeach;endif;?>
			<?php endforeach;endif;?>
			</table>
		</form>
		<?php if($pages):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 , 500 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
			</div>
			</div>
		<?php endif;?>
	</div>
<div style="clear: both;"></div>
<!-- --------------------------------------------列表  begin--------------------------------------------------------------- -->	
	
	
	
</div>
<script>

//批量操作
function doaction(val){
	//如果没有选择订单，返回；
	if(val==""){
		$.alertBox('<p class="text-warn">请选择您的操作！</p>');
		return false;
    }
    if($('.ck:checked').length==0&&val!=''){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	switch(val){
		case 'distributionInventory'://分配库存
			var idstr = [];
			$('input[name^="order_id"]:checked').each(function(){
				idstr.push($(this).val());
			});
			//遮罩
			 $.maskLayer(true);
			 $.post('<?=Url::to(['/delivery/order/distributioninventory'])?>',{orderids:idstr},function(result){
				 var r = $.parseJSON(result);
				 if(r.result){
					 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
				 }else{
					 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
				 } 
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
				 
			});
			break;
		case 'suspenddelivery'://暂停发货
			var idstr = [];
			$('input[name^="order_id"]:checked').each(function(){
				idstr.push($(this).val());
			});
			//遮罩
			 $.maskLayer(true);
			 $.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr,m:'delivery',a:'分配库存->暂停发货'},function(result){
				 var event = $.confirmBox('<p class="text-text">'+result+'</p>');
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
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			//遮罩
			 $.maskLayer(true);
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:idstr,m:'delivery',a:'分配库存->标记缺货'},function(result){
				var event = $.confirmBox('<p class="text-text">'+result+'</p>');
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
				
			});
			break;
		case 'signtoplaceanorder'://物流商下单
			var idstr = [];
			$('input[name^="order_id"]:checked').each(function(){
				idstr.push($(this).val());
			});
			//遮罩
			 $.maskLayer(true);
			 $.post('<?=Url::to(['/delivery/order/signtoplaceanorder'])?>',{orders:idstr,m:'delivery',a:'分配库存->物流商下单'},function(result){
				 var event = $.confirmBox('<p class="text-text">'+result+'</p>');
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
				 
			});
			break;
		case 'stockManage':
			var thisOrderList =[];
			
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			
			OrderCommon.showStockManageBox(thisOrderList);
			break;
		default:
			return false;
			break;
	}
}
//重置
function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
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

//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		return false;
	}
	
}

//展开，收缩订单商品
function spreadorder(obj,id){
	if(typeof(id)=='undefined'){
		//未传参数进入，全部展开或收缩
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.xiangqing').hide();
			$(obj).parent().html('<span class="glyphicon glyphicon-plus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).parent().html('<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}
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
	var configPath = 'delivery/order';
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/carrier/carrierprocess/append-custom-condition?custom_name='+filter_name+'&configPath='+configPath, 
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

//备注
function updatedesc(orderid,obj){
	var desc=$(obj).prev();
    var oiid=$(obj).attr('oiid');
	var html="<textarea name='desc' style='width:200xp;height:60px'>"+desc.text()+"</textarea><input type='button' onclick='ajaxdesc(this)' value='修改' oiid='"+oiid+"'>";	
    desc.html(html);
    $(obj).toggle();
}
function ajaxdesc(obj){
	 var obj=$(obj);
	 var desc=$(obj).prev().val();
	 var oiid=$(obj).attr('oiid');
	  $.post('<?=Url::to(['/order/aliexpressorder/ajaxdesc'])?>',{desc:desc,oiid:oiid},function(r){
		  retArray=$.parseJSON(r);
		  if(retArray['result']){
		      obj.parent().next().toggle();
		      var html="<font color='red'>"+desc+"</font> <span id='showresult' style='background:yellow;'>"+retArray['message']+"</span>"
		      obj.parent().html(html);
		      setTimeout("showresult()",3000);
		  }else{
		      alert(retArray['message']);
		  }
	  })
}
function showresult(){
  $('#showresult').remove();
}
</script>