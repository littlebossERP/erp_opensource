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
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\widgets\SizePager;
use eagle\models\SaasEnsogoUser;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);

?>
<style>
.table th{
	text-align: center;
}
.table td{
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
.sprite_pay_0,.sprite_pay_1,.sprite_pay_2,.sprite_pay_3,.sprite_shipped_0,.sprite_shipped_1,.sprite_check_1,.sprite_check_0
{
	display:block;
	background-image:url(/images/MyEbaySprite.png);
	overflow:hidden;
	float:left;
	width:20px;
	height:20px;
	text-indent:20px;
}
.sprite_pay_0
{
	background-position:0px -92px;
}
.sprite_pay_1
{
	background-position:-50px -92px;
}
.sprite_pay_2
{
	background-position:-95px -92px;
}
.sprite_pay_3
{
	background-position:-120px -92px;
}
.sprite_shipped_0
{
	background-position:0px -67px;
}
.sprite_shipped_1
{
	background-position:-50px -67px;
}
.sprite_check_1
{
	background-position:-100px -15px;
}
.sprite_check_0
{
	background-position:-77px -15px;
}

.exception_201,.exception_202,.exception_221,.exception_210,.exception_222,.exception_223,.exception_299,.exception_manual{
	display:block;
	background-image:url(/images/icon-yichang-eBay.png);
	overflow:hidden;
	float:left;
	width:30px;
	height:15px;
	text-indent:20px;
}
.exception_201{
	background-position:-3px -10px;
}
.exception_202{
	background-position:-26px -10px;
}
.exception_221{
	background-position:-55px -10px;
	width:50px;
}
.exception_210{
	background-position:-107px -10px;
}
.exception_222{
	background-position:-135px -10px;
}
.exception_223{
	background-position:-170px -10px;
}
.exception_299{
	background-position:-199px -10px;
}
.exception_manual{
	background-position:-230px -10px;
}

.input-group-btn > button{
  padding: 0px;
  height: 28px;
  width: 30px;
  border-radius: 0px;
  border: 1px solid #b9d6e8;
}

.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}

.div-input-group>.input-group>input{
	height: 28px;
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

.div_new_tag {
  display: inline-block;
}

.span-click-btn{
	cursor: pointer;
}

.btn_tag_qtip a {
  margin-right: 5px;
}

.div_add_tag{
	width: 600px;
}
/*商品的数量如果大于1给予的span样式*/
.multiitem{
	padding:0 4px 0 4px;
	background:rgb(255,153,0);
	border-radius:8px;
	color:#fff;
}
.checkbox{
	display: block;
    float: left;
 	margin-top: 2px;
}
</style>	
<div class="tracking-index col2-layout">
<?=$this->render('_leftmenu',['counter'=>$counter]);?>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <form  enctype="multipart/form-data">
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

<!-- Modal 即时发送站内信的modal-->
<div class="modal fade" id="myMessage" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <form  enctype="multipart/form-data">
  <div class="modal-dialog">
    <div class="modal-content">
      
    </div>
  </div>
  </form>
</div>
<!-- 手动同步订单的modal -->
<div class="modal fade" id="syncorderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<div class="content-wrapper" >
	<div>
		<!-- 搜索区域 -->
		<form class="form-inline" id="form1" name="form1" action="" method="post">
		<div style="margin:5px">
		<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$ensogoUsersDropdownList,['class'=>'form-control input-sm','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'卖家账号'])?>
			<?php 
				$search=[
					'APPROVED'=> 'APPROVED' , 
					'SHIPPED'=>'SHIPPED' , 
					'REFUNDED'=>'REFUNDED' , 
					'REQUIRE_REVIEW'=>'REQUIRE_REVIEW',
				];
			?>
			<div class="input-group">
		        <?php $sel = [
					'order_id'=>'小老板订单号',
		        	'ensogo_orderid'=>'ensogo订单号',
					'sku'=>'SKU',
					'tracknum'=>'物流号',
					'buyeid'=>'买家名字',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'form-control input-sm','style'=>'width:120px;margin:0px'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'form-control input-sm','id'=>'num','style'=>'width:120px'])?>
		    </div>
		    <?=Html::submitButton('搜索',['class'=>"btn-xs",'id'=>'search'])?>
	    	<?=Html::button('重置',['class'=>"btn-xs",'onclick'=>"javascript:cleform();"])?>
	    	<a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>	 
	    	<br>
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
	    	<?=Html::dropDownList('fuhe',@$_REQUEST['fuhe'],$search,['class'=>'form-control input-sm','prompt'=>'状态条件','id'=>'fuhe'])?>
			<?=Html::dropDownList('country',@$_REQUEST['country'],$countryArr,['class'=>'form-control input-sm','prompt'=>'国家','id'=>'country'])?>
			<?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap()?>
			<?=Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'form-control input-sm','prompt'=>'仓库'])?>
			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'form-control input-sm','prompt'=>'运输服务','id'=>'shipmethod'])?>
			<br>
			<div class="input-group">
	        	<?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'form-control input-sm','style'=>'width:90px;margin:0px'])?>
	        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'form-control','style'=>'width:130px'])?>
	        </div>	
	        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'form-control','style'=>'width:130px;margin:0px'])?>
			<div class="input-group">
				<?=Html::dropDownList('ordersort',@$_REQUEST['ordersort'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'form-control input-sm','style'=>'width:90px;margin:0px'])?>
				<?=Html::dropDownList('ordersorttype',@$_REQUEST['ordersorttype'],['desc'=>'降序','asc'=>'升序'],['class'=>'form-control input-sm','id'=>'ordersorttype','style'=>'width:60px;margin:0px'])?>
			</div>
			 </div> 
			 <?=Html::hiddenInput('trackstatus',@$_REQUEST['trackstatus'],['id'=>'trackstatus'])?>	
	    </div>
		</form>
	</div>
	<br>
	<div style="">
		<form name="a" id="a" action="" method="post">
		<?php $doarr=[
			''=>'批量操作',
			'checkorder'=>'检查订单',
			'signshipped'=>'ensogo标记发货',
			'mergeorder'=>'合并已付款订单',
			'changeshipmethod'=>'更改运输服务',
		];
		/* if (isset($_REQUEST['order_status'])&&$_REQUEST['order_status']>='300'){
			unset($doarr['signshipped']);
		} */
		?>
		<div class="col-md-2">
		<?=Html::dropDownList('do','',$doarr,['onchange'=>"doaction($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		<?php if (isset($_REQUEST['order_status'])&&$_REQUEST['order_status']<'300'):?>
 		<div class="col-md-2" style="">
		<?php $doCarrier=[
			''=>'发货处理',
			'getorderno'=>'提交物流',
			'signwaitsend'=>'提交发货',
//			'dodispatch'=>'交运订单',
// 			'gettrackingno'=>'获取物流号',
// 			'doprint'=>'打印物流单',
// 			'cancelorderno'=>'取消订单',
	];
	?>
		<?=Html::dropDownList('do','',$doCarrier,['class'=>'form-control input-sm do-carrier do']);?>
		</div> 
		<?php endif;?>
		<div class="col-md-2">
		<?php 
			$movearr = [''=>'移动到']+OdOrder::$status;
			unset($movearr[100]);
// 			unset($movearr[300]);
 			unset($movearr[400]);
		?>
			<?=Html::dropDownList('do','',$movearr,['onchange'=>"movestatus($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		<div class="col-md-2">
			<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		
		<div class="col-md-2">
			<?php //echo Html::button('手动同步订单',['class'=>'btn btn-primary','onclick'=>'dosyncorder();']) ?>
		</div>
		
		<?php $divTagHtml = "";
			$div_event_html = "";
		?>
		<br>
			<table class="table table-condensed table-bordered" style="font-size:12px;">
			<tr>
				<th width="1%" style="text-align:left;vertical-align:middle;">
				<span><span class="glyphicon glyphicon-minus checkbox" onclick="spreadorder(this);"></span></span><input id="ck_all" class="ck_0" type="checkbox">
				</th>
				<th width="4%"><b>平台订单号</b></th>
				<th width="12%"><b>商品SKU</b></th>
				<th width="10%"><b>总价</b></th>
				<th width="10%"><b>付款日期</b></th>
				<th><b>站点</b></th>
				<th width="6%">
				<?=Html::dropDownList('country',@$_REQUEST['country'],$countryArr,['prompt'=>'收件国家','style'=>'width:100px','onchange'=>"dosearch('country',$(this).val());"])?>
				</th>
				<th width="6%">
				<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['prompt'=>'运输服务','style'=>'width:100px','onchange'=>"dosearch('shipmethod',$(this).val());"])?>
				</th>
				<th width="8%"><b>Ensogo状态</b></th>
				<th width="10%">
					<b>平台状态</b>
				</th>
				<th width="13%"><b>物流状态</b></th>
				<th ><b>操作</b></th>
			</tr>
			<?php $carriers=CarrierApiHelper::getShippingServices(false)?>
			<?php if (count($models)):foreach ($models as $order):?>
			<?php 
				$SAUser = SaasEnsogoUser::findOne(['store_name'=>$order->selleruserid]);
			?>
			<tr style="background-color: #f4f9fc">
				<td><span><span class="orderspread glyphicon glyphicon-minus checkbox" onclick="spreadorder(this,'<?=$order->order_id?>');"></span></span><label><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>"></label>
				</td>
				<td style="text-align: left;">
					<?=$order->order_id?><br>
					<?php if ($order->seller_commenttype=='Positive'):?>
						<span style='background:green;'><a style="color: white" title="<?=$order->seller_commenttext?>">好评</a></span><br>
					<?php elseif($order->seller_commenttype=='Neutral'):?>
						<span style='background:yellow;'><a title="<?=$order->seller_commenttext?>">中评</a></span><br>
					<?php elseif($order->seller_commenttype=='Negative'):?>
						<span style='background:red;'><a title="<?=$order->seller_commenttext?>">差评</a></span><br>
					<?php endif;?>
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<div title="<?=OdOrder::$exceptionstatus[$order->exception_status]?>" class="exception_<?=$order->exception_status?>"></div>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
					<?php endif;?>
					<?php if ($order->is_manual_order==1):?>
						<span title="挂起" class="exception_manual"></span>
					<?php endif;?>
					<?php 
				$divTagHtml .= '<div id="div_tag_'.$order->order_id.'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
				$TagStr = OrderFrontHelper::generateTagIconHtmlByOrderId($order);
				//$TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($order->order_id);
				
				if (!empty($TagStr)){
					$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$order->order_id."' >$TagStr</span>";
				}
				echo $TagStr;
				?>
				</td>
				<td>
					<?php if (count($order->items)):foreach ($order->items as $item):?>
					<?php if (isset($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
					<?php endif;?>
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
					<?=$order->order_source_site_id?>
				</td>
				<td>
					<?php 
					$site_country = strtoupper($order->consignee_country);
					if(empty($site_country))	$site_country = strtoupper($order->order_source_site_id);
					?>
					<label title=""><?=isset($sysCountry[$site_country])?$sysCountry[$site_country].'('.$site_country.')':$site_country ?></label>
				</td>
				<td>
					<?php if (strlen($order->default_shipping_method_code)){?>[<?=$carriers[$order->default_shipping_method_code]?>]<?php }?>
				</td>
				<td>
					<!-- 付款状态图标 -->
					<!-- 
					<?php if ($order->pay_status==0):?>
					
					<?php elseif ($order->pay_status==1):?>
					<div title="已付款" class="sprite_pay_1"></div>
					<?php elseif ($order->pay_status==2):?>
					<div title="支付中" class="sprite_pay_2"></div>
					<?php elseif ($order->pay_status==3):?>
					<div title="已退款" class="sprite_pay_3"></div>
					<?php endif;?>
					 -->
					<!-- 发货图标 -->
					<!-- 					<?php if ($order->shipping_status==1):?>
					<div title="已发货" class="sprite_shipped_1"></div>
					<?php else:?>
					<div title="未发货" class="sprite_shipped_0"></div>
					<?php endif;?>
					 -->
					<b>
					 <?= empty($order->order_source_status)?"":$order->order_source_status?>
					 </b>
				</td>
				<td>
					<b><?=OdOrder::$status[$order->order_status]?></b>
				</td>
				<td>
					<?php if ($order->order_status=='300'):?>
					<?php echo CarrierHelper::$carrier_step[$order->carrier_step].'<br>';?>
					<?php endif;?>
					<?php if (count($order->trackinfos)):foreach ($order->trackinfos as $ot):?>
						<?php 
						$class = 'text-info';
						if ($ot->status==1){
							$class = 'text-success';
						}elseif ($ot->status==0){
							$class = 'text-warning';
						}elseif($ot->status==2){
							$class = 'text-danger';
						}
						?>
						<!--  <a href="<?=$ot->tracking_link?>" title="<?=$ot->shipping_method_name?>" target="_blank" ><font class="<?php echo $class?>"><?=$ot->tracking_number?></font></a>-->
						<?php if (strlen($ot->tracking_number)):
							$track_info = TrackingApiHelper::getTrackingInfo($ot->tracking_number);
						?>
						<?php if($ot->errors=='等待平台处理结果'):?>
							<b>等待平台处理结果</b><br>
						<?php elseif ($track_info['success']==TRUE):?>
							<b><?=$track_info['data']['status']?></b><br>
						<?php endif;?>
						<a href="javascript:void(0);" onclick="OmsViewTracker(this)" title="<?=$ot->shipping_method_name?>" target="_blank" ><span class="order-info"><font class="<?php echo $class?>"><?=$ot->tracking_number?></font></span></a><br>
						<?php 
						//组织显示物流明细的东东
						$div_event_html .= "<div id='div_more_info_".$ot->tracking_number."' class='div_more_tracking_info div_space_toggle'>";
						
							
						$all_events_str = "";
							
						$all_events_rt = TrackingHelper::generateTrackingEventHTML([$ot->tracking_number]);
						if (!empty($all_events_rt[$ot->tracking_number])){
							$all_events_str = $all_events_rt[$ot->tracking_number];
						}
							
						$div_event_html .=  $all_events_str;
						
						$div_event_html .= "</div>";
						?>
						<?php endif;?>
					<?php endforeach;endif;?>
				</td>
				<td>
					<a href="<?=Url::to(['/order/ensogo-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="egicon-edit" title="编辑订单"></span></a>
					<?php $doarr_one=[
						''=>'操作',
						'checkorder'=>'检查订单',
						'signshipped'=>'Ensogo标记发货',
						'changemanual'=>'挂起/取消挂起',
						'history'=>'修改历史',
					];
					/* if ($order->order_status>='300'){
						unset($doarr_one['signshipped']);
					} */
					if ($order->order_status<='200'){
 						$doarr_one+=[
 							'getorderno'=>'提交物流',
 							'signwaitsend'=>'提交发货',
 						];
					}
					?>
					<?=Html::dropDownList('do','',$doarr_one,['onchange'=>"doactionone($(this).val(),'".$order->order_id."');",'class'=>'form-control input-sm do','style'=>'width:70px;']);?>
					
				</td>
			</tr>
				<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
				<tr  class="xiangqing <?=$order->order_id?>">
					<td style="border:1px solid #d9effc;"><img src="<?=$item->photo_primary?>" width="60px" height="60px"></td>
					<td colspan="2" style="border:1px solid #d9effc;text-align:justify;">
						SKU:<b><?=$item->sku?></b><br>
						<?=$item->product_name?><br>
					</td>
					<td  style="border:1px solid #d9effc">
						<?php if (!empty($item->product_attributes)){
							$tmpProdAttr = json_decode($item->product_attributes,true);
							foreach($tmpProdAttr as $_tmpAttrKey =>$_tmpAttrVal){
								echo $_tmpAttrKey." : <b>".$_tmpAttrVal."</b><br>";
							}
						}?>
						<?='数量 : '.$item->quantity?>
					</td>
					
					<?php if ($key=='0'):?>
					<td rowspan="<?=count($order->items)?>" style="border:1px solid #d9effc">
						<font color="#8b8b8b">仓库:</font><br>
						<b><?php if ($order->default_warehouse_id>0&&count($warehouses)){echo $warehouses[$order->default_warehouse_id];}?></b>
					</td>
					<td colspan="2" rowspan="<?=count($order->items)?>" style="border:1px solid #d9effc">
						<font color="#8b8b8b">ensogo店铺名 / 买家名</font><br>
						<b><?= $SAUser===null ? "" : $SAUser->store_name;?> / <?=$order->consignee?></b>
					</td>
					<?php endif;?>
					<td colspan="2" style="border:1px solid #d9effc;   text-align: left;  ">
						<?php if (!empty($order->order_source_order_id)):?>
						<font color="#8b8b8b">ensogo<?= TranslateHelper::t('订单号')?>:</font><b><?=$item->order_source_order_id ?></b><br>
						<?php endif;?>
						<font color="#8b8b8b">下单日期:</font><b><?=$order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):''?></b>
					</td>
					<?php if ($key=='0'):?>
					<td colspan="2"  rowspan="<?=count($order->items)?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
						<font color="#8b8b8b">买家留言:</font><br><b><?=$order->user_message?></b>
					</td>
					<td  rowspan="<?=count($order->items)?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
					<span><font color="red"><?=$order->desc?></font></span>
						<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font style="white-space: nowrap;" color="00bb9b">备注</font></a>
					</td>
					<?php endif;?>
				</tr>	
				<?php endforeach;endif;?>
			<?php endforeach;endif;?>
			</table>
			<div class="btn-group" >
			<?=LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
			</div>
			<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
		</form>
	</div>

<div style="clear: both;"></div>
</div></div>
<div style="display: none;">
<?=$divTagHtml?>
<?=$div_event_html?>
<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,CarrierApiHelper::getShippingServices())?></div>
</div>


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
				location.reload();
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
		case 'signwaitsend':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/signwaitsend'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signpayed':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
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
		case 'changeshipmethod':
			var thisOrderList =[];
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				
				thisOrderList.push($(this).val());
				if (idstr != '') idstr+=',';
				idstr+=$(this).val();
			});

			if (idstr ==''){
				bootbox.alert('只能修改已付款状态的运输服务');
				return;
			}

			var html  = '以下未发货订单已经被选中<br>'+idstr +'<br><br>请选择批量使用物流运输方式 ：<select name="change_shipping_method_code">'+$('select[name=demo_shipping_method_code]').html()+'</select>';
			bootbox.dialog({
				title: Translator.t("订单详细"),
				className: "order_info", 
				message: html,
				buttons:{
					Ok: {  
						label: Translator.t("确定"),  
						className: "btn-primary",  
						callback: function () { 
							return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
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
			
			break;
		default:
			return false;
			break;
	}
}
//单独操作
function doactionone(val,orderid){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	if(orderid == ""){ bootbox.alert("订单号错误");return false;}
	switch(val){
		case 'checkorder':
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signshipped':
			window.open("<?=Url::to(['/order/ensogo-order/signshipped'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				window.open("<?=Url::to(['/order/order/deleteorder'])?>"+"?order_id="+orderid,'_blank')
			}
			break;
		case 'signpayed':
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'givefeedback':
			window.open("<?=Url::to(['/order/order/feedback'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'dispute':
			window.open("<?=Url::to(['/order/order/dispute'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'mergeorder':
			window.open("<?=Url::to(['/order/order/mergeorder'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'history':
			window.open("<?=Url::to(['/order/logshow/list'])?>"+"?orderid="+orderid,'_blank');
			break;
		case 'getorderno':
			OrderCommon.setShipmentMethod(orderid);
			break;
		case 'signwaitsend':
			OrderCommon.shipOrder(orderid);
			break;
		case 'changemanual':
			$.post('<?=Url::to(['/order/order/changemanual'])?>',{orderid:orderid},function(result){
				if(result == 'success'){
					bootbox.alert('操作已成功');
					location.reload();
				}else{
					bootbox.alert(result);
				}
			});
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
			var str;
			str=$(obj).html();
			if(str.indexOf('取消挂起')>=0){
				$(obj).html('<span class="glyphicon glyphicon-open toggleMenuL" title="挂起"><\/span>');			
			}else{
				$(obj).html('<span class="glyphicon glyphicon-save toggleMenuL" title="取消挂起"><\/span>');	
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

function dosearch(name,val){
	$('#'+name).val(val);
	document.form1.submit();
}
//添加备注函数结束

function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
	$('select[name=keys]').val('order_id');
	$('select[name=timetype]').val('soldtime');
	$('select[name=ordersort]').val('soldtime');
	$('select[name=ordersorttype]').val('desc');
}

//即时发送站内信
function sendmessage(orderid){
	var Url='<?=Url::to(['/order/order/sendmessage'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {orderid : orderid},
		url: Url,
        success:function(response) {
        	$('#myMessage .modal-content').html(response);
        	$('#myMessage').modal('show');
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
			$(obj).parent().html('<span class="glyphicon glyphicon-plus checkbox" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus checkbox');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).parent().html('<span class="glyphicon glyphicon-minus checkbox" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus checkbox');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus checkbox');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus checkbox');
			return false;
		}
	}
}

//手动同步订单
function dosyncorder(){
	var Url=global.baseUrl +'order/ensogo-order/syncmt';
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {},
		url: Url,
        success:function(response) {
        	$('#syncorderModal .modal-content').html(response);
        	$('#syncorderModal').modal('show');
        }
    });
}

function changeShipMethod(orderIDList , shipmethod){
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/order/order/changeshipmethod', 
			data: {orderIDList : orderIDList , shipmethod : shipmethod },
			success: function (result) {
				//bootbox.alert(result.message);
				if (result.success == false) 
					bootbox.alert(result.message);
				else{
					bootbox.alert({message:Translator.t("修改成功！") , callback: function() {  
		                window.location.reload(); 
		            },  
		            });
				}
				return false;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
	return false;
}

function OmsViewTracker(obj){
	var s_trackingNo = $(obj).has('.text-success');
	if(typeof(s_trackingNo)!=='undefined' && s_trackingNo.length>0){
		var qtip = $(obj).find(".order-info").data('hasqtip');
		if(typeof(qtip)=='undefined')
			return false;
		var opened = $("#qtip-"+qtip).css("display");
		if(opened=='block')
			return true;
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/oms-view-tracker', 
			data: {invoker: 'Ensogo-Oms'},
			success: function (result) {
				return true;
			},
			error :function () {
				return false;
			}
		});	
	}
}
</script>