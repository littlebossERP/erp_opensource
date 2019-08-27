<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use yii\jui\JuiAsset;
use yii\web\UrlManager;
use yii\jui\DatePicker;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;


$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->title='订单列表';
$this->params['breadcrumbs'][] = $this->title;
?>	
<!-- <div role="tabpanel">
  <!-- 平台标签 
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" name="all" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">全部</a></li>
    <li role="presentation" name="wish" ><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">wish订单</a></li>
    <li role="presentation" name="ebay"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">ebay订单</a></li>
  </ul>
</div>-->
<div style="border-right: 1px solid;float: left">
	<!-- 左侧标签快捷区域 -->
	<div style=" height: 150px;">
	 [状态]<br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_NOPAY])?>">未付款（<?=$counter[OdOrder::STATUS_NOPAY]?>）</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_PAY])?>">已付款（<?=$counter[OdOrder::STATUS_PAY]?>）</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_WAITSEND])?>">待发货（<?=$counter[OdOrder::STATUS_WAITSEND]?>）</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_SHIPPED])?>">已发货</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_CANCEL])?>">已取消</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay','is_manual_order'=>'1'])?>">挂起（<?=$counter['guaqi']?>）</a><br>
	 <a class="label label-primary" href="<?=Url::to(['/order/order/listebay'])?>">全部（<?=$counter['all']?>）</a><br>
	</div>
	<br>
	<div style=" height: 200px;">
	 [异常]<br>
	 <a class="label label-warning" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_HASNOSHIPMETHOD])?>">未匹配到物流（<?=$counter[OdOrder::EXCEP_HASNOSHIPMETHOD]?>）</a><br>
	 <a class="label label-warning" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_PAYPALWRONG])?>">Paypal账号不符（<?=$counter[OdOrder::EXCEP_PAYPALWRONG]?>）</a><br>
	 <a class="label label-warning" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_SKUNOTMATCH])?>">sku不存在（<?=$counter[OdOrder::EXCEP_SKUNOTMATCH]?>）</a><br>
	 <a class="label label-warning" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_NOSTOCK])?>">库存不足（<?=$counter[OdOrder::EXCEP_NOSTOCK]?>）</a><br>
	 <a class="label label-warning" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_WAITMERGE])?>">待合并（<?=$counter[OdOrder::EXCEP_WAITMERGE]?>）</a><br>
	</div>
	<hr>
	<!-- 左侧快捷操作区域 -->
	<div style="margin: 10px;height:300px;">
	[操作]<br>
<!-- <button type="button" id="import" class="label label-success" data-toggle="modal" data-target="#myModal" >导入订单</button>
	<br> -->	
	 <button type="button" id="export" class="label label-success" onclick="window.open('<?=Url::to(['/order/excel/excel-model-list'])?>')">导出订单样式设置</button><br>
	 <button type="button" class="label label-success" data-toggle="modal" data-target="#myModal" >导入物流单号</button><br>
	 <button type="button" class="label label-success" onclick="window.open('<?=Url::to(['/order/order/usertab'])?>')">自定义标签设置</button><br>
	</div>
</div>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <form  enctype="multipart/form-data"?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">导入物流单号</h4>
      </div>
      <div class="modal-body">
        <input type="file" name="order_tracknum" id="order_tracknum" ><br>
        <a href="<?=Url::home()."template/ordertracknum_sample.xls"?>">范本下载</a>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
        <button type="button" class="btn btn-primary" id="save" onclick="importordertracknum()">提交</button>
      </div>
    </div>
  </div>
  </form>
</div>


<div style="padding:3px;float:right;width:1130px;">
	<div>
		<!-- 搜索区域 -->
		<form class="form-inline" id="form1" action="" method="post">
		<table class="" style="font-size:12px;">
		<tr>
<!-- 			<th>平台</th>
			<td>
			<?=Html::dropDownList('platform','',['ebay'=>'eBay','amazon'=>'Amazon','aliexpress'=>'Aliexpress','wish'=>'Wish'],['class'=>'form-control input-sm','prompt'=>'','id'=>'pt'])?>
			</td> -->
			<th>仓库</th>
			<td>
			<?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap()?>
			<?=Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'form-control input-sm','prompt'=>''])?>
			</td>
			<th>物流方式</th>
			<td>
			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'form-control input-sm','prompt'=>''])?>
			</td>
			<th>状态条件</th>
			<td>
<!-- 				<select id="fuhe" class="form-control input-sm" name="fuhe" >
				  <option>复合条件</option>
				  <option>已付款</option>
				  <option>未付款</option>
				  <option>付款处理中</option>
				  <option>部分付款</option>
				  <option>已打印发货单</option>
				  <option>已填写物流跟踪单号</option>
				  <option>E邮宝未交运订单</option>
				  <option>没有跟踪好订单</option>
				  <option>已标记发货</option>
				  <option>已支付但未标记发货</option>
				  <option>已标记退款</option>
				  <option>订单已取消</option>
				  <option>可有有requestsTotal</option>
				  <option>重新发货</option>
				  <option>重新发货的新订单</option>
				</select> -->
				<?php 
				$search=[
					'haspayed'=>'平台已付款',
					'hasnotpayed'=>'平台未付款',
					'pending'=>'付款处理中',
					'hassend'=>'平台已发货',
					'payednotsend'=>'已付款未发货',
					'hasmessage'=>'有平台留言',
					'hasinvoice'=>'已发送账单',
				];
			?>
			<?=Html::dropDownList('fuhe',@$_REQUEST['fuhe'],$search,['class'=>'form-control input-sm','prompt'=>''])?>
			</td>
		</tr>			
		<tr>
			<!-- <th>订单类型</th>	
			<td>
			<?=Html::dropDownList('goodstype','',['1'=>'单件商品','2'=>'单商品多数量','3'=>'多商品'],['class'=>'form-control input-sm','prompt'=>''])?>
			</td> -->
			<th>
				<?php $sel = [
					'order_id'=>'小老板订单号',
					'ebay_orderid'=>'eBay交易号',
					'sku'=>'SKU',
					'srn'=>'SRN',
					//'itemid'=>'ItemID',
					'tracknum'=>'物流号',
					'buyeid'=>'买家账号',
					'email'=>'买家Email',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'form-control input-sm'])?>
<!--				<select  class="form-control input-sm" style="width: 100px" id="keys" name="keys" >
				  <option>xlb订单号</option>
				  <option>ebay交易号</option>
 			  	  <option>amazon订单号</option>
				  <option>速卖通订单号</option>
				  <option>Wish订单号</option> 
				  <option>Paypal交易号</option>
				  <option>Sku</option>
				  <option>Itemid</option>
				  <option>物流号</option>
				  <option>买家ebayID</option>
				  <option>买家email</option>
				</select>-->
			</th>
			<td>
				<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'form-control input-sm','id'=>'num'])?>
			</td>
			<th>卖家账号</th>
			<td>
				<?=Html::textInput('selleruserid',@$_REQUEST['selleruserid'],['class'=>'form-control input-sm','id'=>'selleruserid'])?>
			</td>
			<th>目的地</th>
			<td> 
				<?=Html::textInput('country',@$_REQUEST['country'],['class'=>'form-control input-sm','id'=>'country'])?>
			</td>
		</tr>
		<tr>
			<th>
				<?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'form-control input-sm'])?>
			</th>
			<td>
				<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'form-control'])?>至
				<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'form-control'])?>
			</td>
<!-- 			<th>
				记录数
			</th>
			<td>
				<?=Html::dropDownList('orderlimit','',['50'=>'每页50条','100'=>'每页100条','200'=>'每页200条'],['class'=>'form-control input-sm','id'=>'orderlimit'])?>
			</td> -->
			<th>排序</th>
			<td>
				<?=Html::dropDownList('ordersort',@$_REQUEST['ordersort'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'form-control input-sm'])?>
				<?=Html::dropDownList('ordersorttype',@$_REQUEST['ordersorttype'],['desc'=>'降序','asc'=>'升序'],['class'=>'form-control input-sm','id'=>'ordersorttype'])?>
			</td>
			<td></td>
			<td>
				<?=Html::submitButton('搜索',['class'=>"btn btn-default btn-sm",'id'=>'search'])?>
				<?=Html::button('重置',['class'=>"btn btn-default btn-sm",'id'=>'clear','type'=>'reset'])?>
			</td>
		</tr>
		</table>
		</form>
	</div>
	<br>
	<div style="">
		<form name="a" id="a" action="" method="post">
		<div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" >全选
		</div>
		&nbsp;&nbsp;
		<?php $doarr=[
			''=>'批量操作',
			'checkorder'=>'检查订单',
			'signshipped'=>'平台标记发货',
			'signpayed'=>'标记为已付款',
			'deleteorder'=>'删除订单',
			'mergeorder'=>'合并已付款订单',
			'givefeedback'=>'评价',
			'dispute'=>'催款取消eBay订单'
		];
		?>
		<div class="col-md-2">
		<?=Html::dropDownList('do','',$doarr,['onchange'=>"doaction($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		
		<div class="col-md-2">
			<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		
		<div class="col-md-2">
		<?php 
			$movearr = [''=>'移动到']+OdOrder::$status;
		?>
			<?=Html::dropDownList('do','',$movearr,['onchange'=>"movestatus($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		
 		<div class="col-md-2">
		<?php $doCarrier=[
			''=>'物流操作',
			'getorderno'=>'上传订单到物流系统',
			'dodispatch'=>'交运订单',
			'gettrackingno'=>'获取物流号',
			'doprint'=>'打印物流单',
			'cancelorderno'=>'取消订单',
	];
	?>
		<?=Html::dropDownList('do','',$doCarrier,['class'=>'form-control input-sm do-carrier do']);?>
		</div> 
		
		<div style="padding:3px;" >
			<table class="table table-condensed table-bordered" style="font-size:12px;">
			<tr>
				<th width="1%"></th>
				<th width="4%"><b>小老板订单号</b></th>
				<th width="12%"><b>商品SKU</b></th>
				<th width="10%"><b>总价</b></th>
				<th width="18%"><b>付款日期</b></th>
				<th width="6%"><b>国家</b></th>
				<th width="6%"><b>物流</b></th>
				<th width="7%"><b>订单状态</b></th>
				<th width="15%"><b>发货状态</b></th>
				<th width="15%"><b>物流状态</b></th>
				<th width="10%"><b>操作</b></th>
			</tr>
			<?php if (count($models)):foreach ($models as $order):?>
			<tr>
				<td><label><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>"></label><br>
					<?=Html::dropDownList('usertab',$order->order_manual_id,['添加标签']+$usertabs,['class'=>"btn btn-primary btn-xs",'onchange'=>"setusertab($order->order_id,this)"])?>
				</td>
				<td>
					<?=$order->order_id?><br>
					<?php if ($order->seller_commenttype=='Positive'):?>
						<span style='background:green;'><a style="color: white" title="<?=$order->seller_commenttext?>">好评</a></span><br>
					<?php elseif($order->seller_commenttype=='Neutral'):?>
						<span style='background:yellow;'><a title="<?=$order->seller_commenttext?>">中评</a></span><br>
					<?php elseif($order->seller_commenttype=='Negative'):?>
						<span style='background:red;'><a title="<?=$order->seller_commenttext?>">差评</a></span><br>
					<?php endif;?>
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[$order->exception_status]?></a><br>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?></a><br>
					<?php endif;?>
				</td>
				<td>
					<?php if (count($order->items)):foreach ($order->items as $item):?>
					<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
					<?php endforeach;endif;?>
				</td>
				<td>
					<?=$order->grand_total?>&nbsp;<?=$order->currency?>
				</td>
				<td>
					<?=$order->paid_time>0?date('Y-m-d H:i:s',$order->paid_time):''?>
					<?php 
					if (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND  , OdOrder::STATUS_SHIPPING])){
						$tmpTimeLeft =  ((!empty($order->fulfill_deadline))?'<br><span id="timeleft_'.$order->order_id.'" class="fulfill_timeleft" data-order-id="'.$order->order_id.'" data-time="'.($order->fulfill_deadline-time()).'"></span>':"");
						echo $tmpTimeLeft;
					}
					?>
				</td>
				<td>
					<label title="<?=$order->consignee_country?>"><?=$order->consignee_country_code?></label>
				</td>
				<td>
					<?php if (strlen($order->default_shipping_method_code)){?>[<?=$carriers[$order->default_shipping_method_code]?>]<?php }?>
				</td>
				<td>
					<b><?=OdOrder::$paystatus[$order->pay_status];?></b>
				</td>
				<td>
					<b><?=$order->shipping_status=='1'?'已发货':'未发货';?></b>
				</td>
				<td>
					<?=$order->printtime>0?date('Y-m-d H:i:s',$order->printtime):''?>/<br><?=$order->delivery_time>0?date('Y-m-d H:i:s',$order->delivery_time):''?>
				</td>
				<td>
					<?=Html::button('编辑',['class'=>"btn btn-primary btn-xs",'onclick'=>"window.open('".Url::to(['/order/order/edit','orderid'=>$order->order_id])."')"])?>
					<?php if ($order->is_manual_order==1):?>
					<?=Html::button('取消挂起',['class'=>"btn btn-primary btn-xs",'onclick'=>"javascript:changemanual($order->order_id,this)"])?>
					<?php else:?>
					<?=Html::button('挂起',['class'=>"btn btn-primary btn-xs",'onclick'=>"javascript:changemanual($order->order_id,this)"])?>
					<?php endif;?>
					<?php if ($order->order_source=='ebay'&&$order->order_status==100):?>
					<?=Html::button('发送账单',['title'=>'发送eBay账单','class'=>"btn btn-primary btn-xs",'onclick'=>"window.open('".Url::to(['/order/order/sendinvoice','orderid'=>$order->order_id])."')"])?>
					<?php endif;?>
					<?=Html::dropDownList('usertab',$order->order_manual_id,['添加标签']+$usertabs,['class'=>"btn btn-primary btn-xs",'onchange'=>"setusertab($order->order_id,this)"])?><br>
					<a title="修改历史" href="<?=Url::to(['/order/logshow/list','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-file"></span></a>
				</td>
			</tr>
				<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
				<tr>
					<td><img></td>
					<td colspan="2">
						SKU:<b><?=$item->sku?></b><br>
						<?=$item->product_name?><br>
					</td>
					<td>
						<?=$item->quantity?>
					</td>
					<?php if ($key=='0'):?>
					<td rowspan="<?=count($order->items)?>">
						仓库:<br><?php if ($order->default_warehouse_id>0&&count($warehouses)){echo $warehouses[$order->default_warehouse_id];}?>
					</td>
					<td rowspan="<?=count($order->items)?>">
						用户名/邮箱:<br>
						<?=$order->source_buyer_user_id?><br><?=$order->consignee_email?>
					</td>
					<td rowspan="<?=count($order->items)?>">
						SRN:<?=$item->order_source_srn?><br>
						下单日期:<?=$order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):''?>
					</td>
					<td colspan="2"  rowspan="<?=count($order->items)?>"  width="200" style="word-break:break-all;word-wrap:break-word;">
						买家留言:<br><?=$order->user_message?>
					</td>
					<?php endif;?>
					<td colspan="2"   width="150" style="word-break:break-all;word-wrap:break-word;">
					<span><font color="red"><?=$item->desc?></font></span>
						<?=Html::button('备注',['onclick'=>"updatedesc($item->order_item_id,this)",'oiid'=>"$item->order_item_id"])?>
					</td>
				</tr>	
				<?php endforeach;endif;?>
			<?php endforeach;endif;?>
			</table>
			<table class="table table-condensed table-bordered" style="font-size:12px;">
			<tr>
				<th width="1%"></th>
				<th width="4%">Xlb订单号<br>SRN</th>
				<th width="12%">状态</th>
				<th width="10%">卖家账号<br>买家账号(Email)</th>
				<th width="18%">收件信息</th>
				<th width="6%">费用</th>
				<th width="6%">仓库(SKU)</th>
				<th width="7%">物流方式(物流号)</th>
				<th width="15%">售出时间/<br>付款时间</th>
				<th width="15%">打单日期/<br>发货日期</th>
				<th width="10%">订单总价</th>
			</tr>
			<?php if (count($models)):foreach ($models as $order):?>
	 		<tr>
				<td><label><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>"></label></td>
				<td>
					<?=$order->order_id?>
					<!-- 订单单独操作  -->
					<br>
					<?=Html::button('编辑',['class'=>"btn btn-primary btn-xs",'onclick'=>"window.open('".Url::to(['/order/order/edit','orderid'=>$order->order_id])."')"])?>
					<?php if ($order->is_manual_order==1):?>
					<?=Html::button('取消挂起',['class'=>"btn btn-primary btn-xs",'onclick'=>"javascript:changemanual($order->order_id,this)"])?>
					<?php else:?>
					<?=Html::button('挂起',['class'=>"btn btn-primary btn-xs",'onclick'=>"javascript:changemanual($order->order_id,this)"])?>
					<?php endif;?>
					<?php if ($order->order_source=='ebay'&&$order->order_status==100):?>
					<?=Html::button('发送账单',['title'=>'发送eBay账单','class'=>"btn btn-primary btn-xs",'onclick'=>"window.open('".Url::to(['/order/order/sendinvoice','orderid'=>$order->order_id])."')"])?>
					<?php endif;?>
					<?=Html::dropDownList('usertab',$order->order_manual_id,['添加标签']+$usertabs,['class'=>"btn btn-primary btn-xs",'onchange'=>"setusertab($order->order_id,this)"])?><br>
					<a title="修改历史" href="<?=Url::to(['/order/logshow/list','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-file"></span></a>
				</td>
				<td>
					<?=OdOrder::$paystatus[$order->pay_status];?><br>
					<?=$order->shipping_status=='1'?'已发货':'未发货';?><br>
					<?php if ($order->seller_commenttype=='Positive'):?>
						<span style='background:green;'><a style="color: white" title="<?=$order->seller_commenttext?>">好评</a></span><br>
					<?php elseif($order->seller_commenttype=='Neutral'):?>
						<span style='background:yellow;'><a title="<?=$order->seller_commenttext?>">中评</a></span><br>
					<?php elseif($order->seller_commenttype=='Negative'):?>
						<span style='background:red;'><a title="<?=$order->seller_commenttext?>">差评</a></span><br>
					<?php endif;?>
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[$order->exception_status]?></a><br>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?></a><br>
					<?php endif;?>
						<a class="label label-primary"><?=OdOrder::$status[$order->order_status]?></a><br>
				</td>
				<td><?=$order->selleruserid?><br><?=$order->source_buyer_user_id?>(<?=$order->consignee_email?>)</td>
				<td>
					<address>
					  <strong><?=$order->consignee_country?>(<?=$order->consignee_country_code?>)</strong><br>
					  <?=$order->consignee?><br>
					  <?=$order->consignee_address_line1?><br>
					  <?=$order->consignee_city?>&nbsp;&nbsp;<?=$order->consignee_postal_code?><br>
					  <abbr title="Phone">P:</abbr><?=$order->consignee_phone?>
					</address>
				</td>
				<td><?=$order->grand_total?></td>
				<td><?php if ($order->default_warehouse_id>0&&count($warehouses)){echo $warehouses[$order->default_warehouse_id];}?></td>
				<td><?php if (strlen($order->default_shipping_method_code)){?>[<?=$carriers[$order->default_shipping_method_code]?>]<?php }?><br>
					<!-- 订单的物流信息 -->
					<?php if (count($order->trackinfos)):foreach ($order->trackinfos as $ot):?>
						<b><?=$ot->shipping_method_name?>:</b><a href="<?=$ot->tracking_link?>"><?=$ot->tracking_number?></a>
						<?php if ($ot->status==1):?>
							<font color="green">(√)</font>
						<?php else:?>
							<font color="red">(X)</font>
						<?php endif;?>
						<br>
					<?php endforeach;endif;?>
				</td>
				<td><?=$order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):''?>/<br><?=$order->paid_time>0?date('Y-m-d H:i:s',$order->paid_time):''?></td>
				<td><?=$order->printtime>0?date('Y-m-d H:i:s',$order->printtime):''?>/<br><?=$order->delivery_time>0?date('Y-m-d H:i:s',$order->delivery_time):''?></td>
				<td><?=$order->grand_total?></td>
			</tr>
			<?php if (count($order->items)):foreach($order->items as $key=>$item):?>
			<tr>
				<td colspan="2">
					<?=$item->order_source_srn?>
				</td>
				<td colspan="3">
					<?=$item->ordered_quantity?>*<?=$item->product_name?>
				</td>
				<td>运费:<?=$item->shipping_price?></td>
				<td style="word-wrap:break-word;"><?=$item->sku?></td>
				<?php if ($key=='0'):?>
				<td rowspan="<?=count($order->items)?>" style="word-wrap:break-word;">
					留言:<?=$order->user_message?>
				</td>
				<?php endif;?>
				<td colspan="2">
				<span><font color="red"><?=$item->desc?></font></span>
					<?=Html::button('备注',['onclick'=>"updatedesc($item->order_item_id,this)",'oiid'=>"$item->order_item_id"])?>
				</td>
				<td><?=$item->price?></td>
			</tr>
			<?php endforeach;endif;?>
			<?php endforeach;endif;?>
			</table>
			<?php
			
			 echo LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
		</div>
		</form>
	</div>
</div>

<div style="clear: both;"></div>
<script>
//批量操作
function doaction(val){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
    if($('.ck:checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的订单");return false;
    }
	switch(val){
		case 'checkorder':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
			});
			break;
		case 'signshipped':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/signshipped'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				document.a.target="_blank";
    			document.a.action="<?=Url::to(['/order/order/deleteorder'])?>";
    			document.a.submit();
    			document.a.action="";
			}
			break;
		case 'signpayed':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
			});
			break;
		case 'givefeedback':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/feedback'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'dispute':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/dispute'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'mergeorder':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/mergeorder'])?>";
			document.a.submit();
			document.a.action="";
			break;
		default:
			return false;
			break;
	}
}
//导出订单
function exportorder(type){
	if(type==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&type!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	window.open('<?=Url::to(['/order/excel/export-excel'])?>'+'?orderids='+idstr+'&excelmodelid='+type);
}

//移动订单状态到其他状态
function movestatus(val){
	if(val==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&val!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post('<?=Url::to(['/order/order/movestatus'])?>',{orderids:idstr,status:val},function(result){
		bootbox.alert('操作已成功');
	});
}
//上传物流单号
function importordertracknum(){
	if($("#order_tracknum").val()){
		$.ajaxFileUpload({  
			 url:'<?=Url::to(['/order/order/importordertracknum'])?>',
		     fileElementId:'order_tracknum',
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
//修改订单的挂起状态
function changemanual(orderid,obj){
	$.post('<?=Url::to(['/order/order/changemanual'])?>',{orderid:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			if($(obj).text()=='挂起'){
				$(obj).text('取消挂起');
			}else{
				$(obj).text('挂起');
			}
		}else{
			bootbox.alert(result);
		}
	});
}

//添加自定义标签
function setusertab(orderid,tabobj){
	var tabid = $(tabobj).val();
	$.post('<?=Url::to(['/order/order/setusertab'])?>',{orderid:orderid,tabid:tabid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
		}else{
			bootbox.alert(result);
		}
	});
}

//添加备注
function updatedesc(itemid,obj){
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
	  $.post('<?=Url::to(['/order/order/ajaxdesc'])?>',{desc:desc,oiid:oiid},function(r){
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
//添加备注函数结束
</script>