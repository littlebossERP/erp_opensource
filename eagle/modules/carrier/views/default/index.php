<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/tracking_tag.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css", ['depends'=>["eagle\assets\AppAsset"]]);
$this->title = TranslateHelper::t('可操作订单');
//$this->params['breadcrumbs'][] = $this->title;
 ?>
<style>
.hidetr td {
	overflow: hidden;
}

.input-group-btn>button {
	padding: 0px;
	height: 28px;
	width: 30px;
	border-radius: 0px;
	border: 1px solid #b9d6e8;
}

.div-input-group {
	width: 150px;
	display: inline-block;
	vertical-align: middle;
	margin-top: 1px;
}

.div-input-group>.input-group>input {
	height: 28px;
}

.div_select_tag>.input-group,.div_new_tag>.input-group {
	float: left;
	width: 32%;
	vertical-align: middle;
	padding-right: 10px;
	padding-left: 10px;
	margin-bottom: 10px;
}
</style>
<style>
.table td,.table th {
	text-align: center;
}

table {
	font-size: 12px;
}

.table>tbody>tr:nth-of-type(even) {
	background-color: #f4f9fc
}

.table>tbody td {
	color: #637c99;
}

.table>tbody a {
	color: #337ab7;
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
<style>
.sprite_pay_0,.sprite_pay_1,.sprite_pay_2,.sprite_pay_3,.sprite_shipped_0,.sprite_shipped_1,.sprite_check_1,.sprite_check_0
	{
	display: block;
	background-image: url(/images/MyEbaySprite.png);
	overflow: hidden;
	float: left;
	width: 20px;
	height: 20px;
	text-indent: 20px;
}

.sprite_pay_0 {
	background-position: 0px -92px;
}

.sprite_pay_1 {
	background-position: -50px -92px;
}

.sprite_pay_2 {
	background-position: -95px -92px;
}

.sprite_pay_3 {
	background-position: -120px -92px;
}

.sprite_shipped_0 {
	background-position: 0px -67px;
}

.sprite_shipped_1 {
	background-position: -50px -67px;
}

.sprite_check_1 {
	background-position: -100px -15px;
}

.sprite_check_0 {
	background-position: -77px -15px;
}

.exception_201,.exception_202,.exception_221,.exception_210,.exception_222,.exception_223,.exception_299
	{
	display: block;
	background-image: url(/images/icon-yichang-eBay.png);
	overflow: hidden;
	float: left;
	width: 30px;
	height: 15px;
	text-indent: 20px;
}

.exception_201 {
	background-position: -3px -10px;
}

.exception_202 {
	background-position: -26px -10px;
}

.exception_221 {
	background-position: -55px -10px;
	width: 50px;
}

.exception_210 {
	background-position: -107px -10px;
}

.exception_222 {
	background-position: -135px -10px;
}

.exception_223 {
	background-position: -170px -10px;
}

.exception_299 {
	background-position: -200px -10px;
}

.input-group-btn>button {
	padding: 0px;
	height: 28px;
	width: 30px;
	border-radius: 0px;
	border: 1px solid #b9d6e8;
}

.div-input-group {
	width: 150px;
	display: inline-block;
	vertical-align: middle;
	margin-top: 1px;
}

.div-input-group>.input-group>input {
	height: 28px;
}

.div_select_tag>.input-group,.div_new_tag>.input-group {
	float: left;
	width: 32%;
	vertical-align: middle;
	padding-right: 10px;
	padding-left: 10px;
	margin-bottom: 10px;
}

.div_select_tag {
	display: inline-block;
	border-bottom: 1px dotted #d4dde4;
	margin-bottom: 10px;
}

.div_new_tag {
	display: inline-block;
}

.span-click-btn {
	cursor: pointer;
}

.btn_tag_qtip a {
	margin-right: 5px;
}

.div_add_tag {
	width: 600px;
}
</style>
<div class="tracking-index col2-layout">
<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php echo $this->render('_leftmenu');?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<!-- 右侧table内容区域 -->
	<div class="content-wrapper">
<?php $doarr=[
		''=>'移动到',
		OdOrder::CARRIER_WAITING_UPLOAD => '待上传到物流商',
		OdOrder::CARRIER_WAITING_DELIVERY=>'待交运',
		OdOrder::CARRIER_WAITING_GETCODE=>'待获取物流号',
		OdOrder::CARRIER_WAITING_PRINT=>'待打印物流单',
		OdOrder::CARRIER_FINISHED=>'已完成',
];
?>
<?php $doCarrier=[
		''=>'物流操作',
		'getorderno'=>'上传订单到物流商',
		'dodispatch'=>'交运订单',
		'gettrackingno'=>'获取物流号',
		'doprint'=>'打印物流单',
];
?>
<?php $divTagHtml = "";?>
<?php $divOrderInfoHtml = '<div class="sr-only">';?>
<form action="" method="post" name='form1' id='form1'>
<?=Html::dropDownList('do','',$doCarrier,['class'=>'do-carrier2 eagle-form-control']);?> &nbsp;
<?=Html::dropDownList('do','',$doarr,['class'=>'do eagle-form-control','onchange'=>"movestatus($(this).val());"]);?> &nbsp;
<?=Html::textInput('order_id',isset($search_data['order_id'])?$search_data['order_id']:'',['class'=>'eagle-form-control','placeholder'=>"订单号",'title'=>'订单号'])?>&nbsp;
<?=Html::textInput('customer_number',isset($search_data['customer_number'])?$search_data['customer_number']:'',['class'=>'eagle-form-control','placeholder'=>"客户参考号",'title'=>'客户参考号'])?>&nbsp;
<?=Html::hiddenInput('default_shipping_method_code',isset($search_data['default_shipping_method_code'])?$search_data['default_shipping_method_code']:'',['id'=>'default_shipping_method_code'])?>
<?=Html::hiddenInput('default_carrier_code',isset($search_data['default_carrier_code'])?$search_data['default_carrier_code']:'',['id'=>'default_carrier_code'])?>
<?=Html::hiddenInput('carrier_step',isset($search_data['carrier_step'])?$search_data['carrier_step']:'',['id'=>'carrier_step'])?>
<?=Html::hiddenInput('carrier_type',isset($search_data['carrier_type'])?$search_data['carrier_type']:'',['id'=>'carrier_type'])?>

<button type="submit" id="search" class="btn btn-primary btn-sm">搜索</button>
		</form>
		<form action="" method="post" name='a' id='a'>
			<table cellspacing="0" cellpadding="0" width="100%"
				class="table table-hover table-striped" style="font-size: 12px;">
				<tr class="list-firstTr">
					<th width="20px"><?=Html::checkbox('select_all','',['class'=>'select-all']);?></th>
					<th width="100px"><?=TranslateHelper::t('订单号')?><br></th>
					<th width="60px"><?=TranslateHelper::t('国家')?></th>
					<th width="140px"><?=TranslateHelper::t('SKU')?></th>
					<th class="text-nowrap"><?=Html::dropDownList('default_carrier_code',isset($search_data['default_carrier_code'])?$search_data['default_carrier_code']:'',$carriers,['prompt'=>TranslateHelper::t('物流商'),'style'=>'width:65px;','class'=>'search']);?></th>
					<th class="text-nowrap"><?=Html::dropDownList('default_shipping_method_code',isset($search_data['default_shipping_method_code'])?$search_data['default_shipping_method_code']:'',$services,['prompt'=>TranslateHelper::t('运输服务'),'style'=>'width:75px;','class'=>'search']);?></th>
					<th><?=Html::dropDownList('carrier_step',isset($search_data['carrier_step'])?$search_data['carrier_step']:'',CarrierHelper::$carrier_step,['prompt'=>TranslateHelper::t('物流操作状态'),'style'=>'width:100px;','class'=>'search']);?></th>
					<th class="text-nowrap"><?=TranslateHelper::t('操作')?></th>
				</tr>
        <?php if(count($orders)>0): ?>
        <?php foreach($orders['data'] as $data):?>
        <tr>
					<td><?=Html::checkbox('order_id[]','',['value'=>$data['order_id'],'class'=>'order-id']);?></td>
					<td>
		            <span class="order-info"><?=$data['order_id'] ?></span>
		            <?php $divOrderInfoHtml.='<table id = "div_more_info_'.$data['order_id'].'" style="text-align:left;width:100%"><tbody>';?>
		            <?php foreach ($data->items as $one){?>
		            <?php $divOrderInfoHtml.='<tr><td style="border:1px solid #d9effc;"><img alt="" src="'.$one->photo_primary.'" width="60px" height="60px"></td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc">SKU:'.$one->sku.'<br>'.$one->product_name.'</td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc;width:60px;" class="text-nowrap">'.$one->quantity.'</td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc;width:60px;" class="text-nowrap">'.$one->product_attributes.'</td></tr>'?>
		            <?php }?>
		            <?php $divOrderInfoHtml.='</tbody></table>'?>
		            <br>
            <?php if ($data['exception_status']>0&&$data['exception_status']!='201'):?>
						<div
							title="<?=OdOrder::$exceptionstatus[$data['exception_status']]?>"
							class="exception_<?=$data['exception_status']?>"></div>
					<?php endif;?>
					<?php if (strlen($data['user_message'])>0):?>
						<div
							title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>"
							class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
					<?php endif;?>
            <?php
            $divTagHtml .= '<div id="div_tag_'.$data['order_id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
            $TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($data['order_id']);
            if (!empty($TagStr)){
            	$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$data['order_id']."' >$TagStr</span>";
            }
            echo $TagStr;
            ?>
            </td>
					<td><?=$data['consignee_country_code'] ?></td>
					<td>
            <?php if (count($data->items)):foreach ($data->items as $item):?>
					<?php if (isset($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
					<?php endif;?>
					<?php endforeach;endif;?>
            </td>
            <td><?=isset($carriers[$data['default_carrier_code']])?$carriers[$data['default_carrier_code']]:'未匹配到物流商'; ?></td>
					<td>
            <?=isset($services[$data['default_shipping_method_code']])?$services[$data['default_shipping_method_code']]:'未匹配到运输服务'; ?><br>
            <?=$data['customer_number'] ?></td>
					
					<td>
            <?=CarrierHelper::$carrier_step[$data['carrier_step']] ?><br>
						<font color="orange"><?php echo $data['carrier_error']?></font>
            <?php ?>
            </td>
			<td>
			<?php if ($data['carrier_step'] == 0 ||$data['carrier_step'] == 4 ){?>
             <a href="<?=Url::to(['/carrier/carrieroperate/getorderno','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('上传');?></a>
            <?php }elseif ($data['carrier_step'] == 1){?>
	            <a href="<?=Url::to(['/carrier/carrieroperate/dodispatch','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('交运');?></a> 
	            <a href="<?=Url::to(['/carrier/carrieroperate/cancelorderno','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('取消');?></a>
            <?php }elseif ($data['carrier_step'] == 2){?>
	            <a href="<?=Url::to(['/carrier/carrieroperate/gettrackingno','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('获取物流号');?></a>
            <?php }elseif ($data['carrier_step'] == 3){?>
	            <a href="<?=Url::to(['/carrier/carrieroperate/doprint2','orders'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('打印物流单');?></a> 
	            <a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/default/completeprint','order_id'=>$data['order_id']])?>" class="completeprint"><?php echo TranslateHelper::t('确认已打印');?></a>
            <?php }elseif ($data['carrier_step'] == 5){?>
            	<a href="<?=Url::to(['/carrier/carrieroperate/dodispatch','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('交运');?></a>
            <?php }elseif ($data['carrier_step'] == 6){?>
            	<a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/default/completecarrier','order_id'=>$data['order_id']])?>" class="completecarrier"><?php echo TranslateHelper::t('确认发货完成');?></a>
				<?php if (strlen($data['customer_number'])==0){?>
	            <a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/default/carriercancel','order_id'=>$data['order_id']])?>" class="carriercancel"><?php echo TranslateHelper::t('取消物流操作');?></a>
	            <?php }?>
	            <?php if ($data['default_carrier_code']=='lb_epacket'){?>
	            <a href="<?=Url::to(['/carrier/carrieroperate/recreate','order_id'=>$data['order_id']])?>" target="_blank"><?php echo TranslateHelper::t('重新发货');?></a>
	            <?php }?>
            <?php }?>
            </td>
				</tr>
        <?php endforeach;?>
        <?php endif; ?>
    </table>
		</form>
	<?php if($orders['pagination']):?>
	<div id="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['pagination'=>$orders['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%; text-align: right;">
	    	<?=\yii\widgets\LinkPager::widget(['pagination' => $orders['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
		</div>
	<?php endif;?>
</div>
</div>
<?=$divTagHtml?>
<?=$divOrderInfoHtml.'</div>'?>
<script type="text/javascript">
//移动物流操作状态
function movestatus(val){
	if(val==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.order-id:checked').length==0&&val!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.showLoading();
	$.post('<?=Url::to(['/carrier/default/movestep'])?>',{orderids:idstr,status:val},function(response){
		$.hideLoading();
		var result = JSON.parse(response);
		if(result.Ack ==1){
			bootbox.alert(result.msg);
		}
		window.location.reload(true)
	});
}
//确认发货完成
function completecarrier(){
	if($('.order-id:checked').length==0){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.showLoading();
	$.post('<?=Url::to(['/carrier/default/completecarrier'])?>',{orderids:idstr},function(response){
		$.hideLoading();
		var result = JSON.parse(response);
		if(result.code =='fail'){
			bootbox.alert({title:Translator.t('错误提示') , message:result.message  });	
			return false;
		}else{
			bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
				window.location.reload();
				$.showLoading();
			}});
		}
	});
}

//物流取消
function carriercancel(){
	if($('.order-id:checked').length==0){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.showLoading();
	$.post('<?=Url::to(['/carrier/default/completecarrier'])?>',{orderids:idstr},function(response){
		$.hideLoading();
		var result = JSON.parse(response);
		if(result.code =='fail'){
			bootbox.alert({title:Translator.t('错误提示') , message:result.message  });	
			return false;
		}else{
			bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
				window.location.reload();
				$.showLoading();
			}});
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
</script>
<!-- 
<div class="Carrier-default-index">
    <h1><?= $this->context->action->uniqueId ?></h1>
    <p>
        This is the view content for action "<?= $this->context->action->id ?>".
        The action belongs to the controller "<?= get_class($this->context) ?>"
        in the "<?= $this->context->module->id ?>" module.
    </p>
    <p>
        You may customize this page by editing the following file:<br>
        <code><?= __FILE__ ?></code>
    </p>
</div>
-->



