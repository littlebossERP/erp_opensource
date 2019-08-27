<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\models\carrier\SysCarrierParam;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carrier.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->title = TranslateHelper::t('待上传');
?>
<style>
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
.exception_201,.exception_202,.exception_221,.exception_210,.exception_222,.exception_223,.exception_299{
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
	background-position:-200px -10px;
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
</style>	
<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php echo $this->render('_leftnew');?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<!-- 右侧table内容区域 -->
<div class="content-wrapper" >
<!---------------------------------------- oms 2.1 nav start  --------------------------------------------------->
<?php echo $order_nav_html;?>
<!---------------------------------------- oms 2.1 nav end  ------------------------------------------------------>

<?php $divTagHtml = "";?>
<div style="padding-top:5px;height:40px;">
	<button type="button" id="do-carrier" onclick="doaction('getorderno')"  style="height: 30px;width:100px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('上传')?></button>
	 &nbsp;
	 <button type="button" id="do-carrier"  onclick="edit_customs_info()"  style="height: 30px;width:130px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('修改报关信息')?></button>
	 &nbsp;
	<input  type="checkbox"  class="check"  id="deliverysoon" />上传完立即交运
 </div>
<table cellspacing="0" cellpadding="0" width="100%"  class="table table-hover table-striped" style="font-size: 12px;">
		<tr class="list-firstTr">
			<th width="1%"><?=Html::checkbox('select_all','',['class'=>'select-all','onclick'=>'selectall(this)']);?></th>
			<th width="4%"><?=TranslateHelper::t('单号')?><br></th>
			<th width="8%"><?=TranslateHelper::t('平台')?></th>
			<th width="10%"><?=TranslateHelper::t('帐号')?></th>
			<th width="12%"><?=TranslateHelper::t('平台订单号')?></th>
			<th width="4%"><?=TranslateHelper::t('收件国家')?></th>
			<th width="13%"><?=TranslateHelper::t('SKU x 数量')?></th>
			<th width="10%"><?=TranslateHelper::t('物流商')?></th>
			<th width="12%"><?=TranslateHelper::t('运输服务')?></th>
			<th width="10%"><?=TranslateHelper::t('客户参考号')?></th>
			<th width="10%"><?=TranslateHelper::t('上传结果');?></th>
			<th width="6%"><?=TranslateHelper::t('操作')?></th>
		</tr>
        <?php if(count($orders)>0): ?>
        <?php foreach($orders['data'] as $data):?>
        <tr>
        	<td><?=Html::checkbox('order_id[]','',['value'=>$data['order_id'],'class'=>'order-id']);?></td>
            <td>
            <?=$data['order_id'] ?><br>
            <?php if ($data['exception_status']>0&&$data['exception_status']!='201'):?>
						<div title="<?=OdOrder::$exceptionstatus[$data['exception_status']]?>" class="exception_<?=$data['exception_status']?>"></div>
					<?php endif;?>
					<?php if (strlen($data['user_message'])>0):?>
						<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
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
            <td><?=$data['order_source'] ?></td>
            <td><?=$data['selleruserid'] ?></td>
            <td><?=$data['order_source_order_id'] ?></td>
            <td><?=$data['consignee_country_code'] ?></td>
            <td>
            	<?php  if (count($data->items)):foreach ($data->items as $item):?>
					<?php if (isset($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
					<?php endif;?>
				<?php endforeach;endif;?>
            </td>
            <td><?=isset($carriers[$data['default_carrier_code']])?$carriers[$data['default_carrier_code']]:'未匹配到物流商'; ?></td>
            <td><?=isset($services[$data['default_shipping_method_code']])?$services[$data['default_shipping_method_code']]:'未匹配到运输服务'; ?></td>
         	<td>
         	<?=$data->customer_number?>
         	</td>
            <td>
	            <font color="orange"><?php echo strlen($data['carrier_error'])?$data['carrier_error']:'还未上传';?></font>
            </td>
            <td>
            	<input type="button" value="暂停" class="iv-btn btn-primary" onclick="suspend（'<?= $data['order_id']?>')" />
            </td>
		</tr>
		<tr class="orderInfo">
			<td></td>
			<td colspan="11" class="row">
				<form class="form-inline">
					<?php 
						//查询出物流商的参数
						$params = SysCarrierParam::find()->where(['carrier_code'=>$data['default_carrier_code']])->andWhere('type in (2,3)')->orderBy('sort asc')->all();
						//对查询到的参数进行分类
						$order_params = $item_params = [];
						foreach ($params as $v) {
							if($v->type == 2){
								$order_params[] = $v;
							}else{
								$item_params[] = $v;
							}
						}
						if($data['default_carrier_code'] == 'lb_alionlinedelivery'){
							echo $this->render('lbalionlinedelivery',['orderObj'=>$data,'services'=>$services,'order_params'=>$order_params,'item_params'=>$item_params]);
						}else{
// 							echo $this->render('default',['orderObj'=>$data,'services'=>$services,'order_params'=>$order_params,'item_params'=>$item_params]);
						}
					?>
				</form>
			</td>
		</tr>
        <?php endforeach;?>
        <?php endif; ?>
    </table>
<?php if($orders['pagination']):?>
	<div id="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['pagination'=>$orders['pagination'] , 'pageSizeOptions'=>array( 15 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%; text-align: right;">
	    	<?=\yii\widgets\LinkPager::widget(['pagination' => $orders['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
	<?php endif;?>
</div>
<?=$divTagHtml?>
<script>
 //全选
function selectall(obj){
		if($(obj).prop('checked')==true){
			$('.order-id').prop('checked',true);
		}else{
			$('.order-id').prop('checked',false);
		}	
}
//批量操作
function doaction(val){
    if($('.order-id:checked').length==0&&val!=''){
    	$.alert('请选择要操作的订单','info');
		return false;
    }
    switch(val){
    	case 'getorderno':
    		$event = $.confirmBox('确认将选中的订单上传至物流商？');
    		$event.then(function(){
    			var allRequests = [];
    			var n = 0;
    			var delivery = ($('#deliverysoon').prop('checked'))?1:0;
    			$.maskLayer(true);
    			$(".order-id:checked").each(function() {n++;		
    				var obj = this;
    				var $form = $(this).parent().parent().next(".orderInfo").find('form'),
    					$message = $form.closest('.orderInfo').prev().find('.message');
    				$message.html(" 执行中,请不要关闭页面！");
    				allRequests.push($.ajax({
    					url: global.baseUrl + "carrier/carrieroperate/get-data?delivery="+delivery,
    					data: $form.serialize(),
    					type: 'post',
    					success: function(response) {
    						var result = JSON.parse(response);
    						if (result.error) {
    							$message.html(result.msg);
    						} else {
    							$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
    							createSuccessDiv(obj);
    						}
    					},
    					error: function(XMLHttpRequest, textStatus) {
    						$message.html('网络不稳定.请求失败,请重试');
    					}
    				}));
    			});
    			$.when.apply($, allRequests).then(function() {
    				$.maskLayer(false);
    				if(n == 0){
    					bootbox.alert('请先选择需要上传的订单!');
    				}
    				else bootbox.alert('操作已全部完成,错误原因请查看处理结果!');
    			});
    		});
			break;
        default:
            break;
    }
}
//暂停发货
function suspend(orderid){
	var ids=[];
	ids.push(id);
	var event = $.confirmBox("暂停发货，订单状态将从待上传变成暂停发货状态");
	event.then(function(){
		  // 确定
			 $.maskLayer(true);
			 $.post('<?=Url::to(['/carrier/default/suspend'])?>',{order_id:ids,m:'carrier',a:'待上传->暂停发货'},function(result){
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
		},function(){
		  // 取消，关闭遮罩
			$.maskLayer(false);
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

//批量修改报关信息
function edit_customs_info(){
	$.maskLayer(true);
	$.get(global.baseUrl+'carrier/default/edit-customs-info',
	   function (data){
			$.maskLayer(false);
			var thisbox = bootbox.dialog({
				className: "myClass", 
				title : ("批量修改报关信息"),
			    message: data,
			    buttons:{
					Ok: {  
						label: Translator.t("保存"),  
						className: "btn-success",  
						callback: function () { 
							saveCustomsInfo( function(){
								$(thisbox).modal('hide');
							});
							return false;
						}
					}, 
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-transparent",  
						callback: function () {  
						}
					}, 
				}
			});	
	});
};

//批量修改报关信息保存
function saveCustomsInfo(callback){
	var checkOrderIds = '';
	var tmpCustomsName = '';
	var tmpCustomsEName = '';
	var tmpCustomsDeclaredValue = '';
	var tmpCustomsweight = '';
	
	var tmpCustomsextraid = '';
	
	if ($.trim($('#customsName').val()).length != 0 ) {
		tmpCustomsName = $.trim($('#customsName').val());
	}
	if ($.trim($('#customsEName').val()).length != 0 ) {
		tmpCustomsEName = $.trim($('#customsEName').val());
	}
	if ($.trim($('#customsDeclaredValue').val()).length != 0 ) {
		tmpCustomsDeclaredValue = $.trim($('#customsDeclaredValue').val());
	}
	if ($.trim($('#customsweight').val()).length != 0 ) {
		tmpCustomsweight = $.trim($('#customsweight').val());
	}
	
	if ($.trim($('#customsextra_id').val()).length != 0 ) {
		tmpCustomsextraid = $.trim($('#customsextra_id').val());
	}
	
	$(".ordedr-id").each(function(){
		if($(this).is(':checked')){
			checkOrderIds += $(this).parent().parent().find('td:eq(1)').html()+',';
			
			if(tmpCustomsName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_cn").parent().next().val(tmpCustomsName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='Name[]']").val(tmpCustomsName);
			}
			if(tmpCustomsEName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_en").parent().next().val(tmpCustomsEName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='EName[]']").val(tmpCustomsEName);
			}
			if(tmpCustomsDeclaredValue != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_declaredValue").parent().next().val(tmpCustomsDeclaredValue);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='DeclaredValue[]']").val(tmpCustomsDeclaredValue);
			}
			if(tmpCustomsweight != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_weight").parent().next().val(tmpCustomsweight);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='weight[]']").val(tmpCustomsweight);
			}
			
			if(tmpCustomsextraid != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline").find(".hasTip").next().val(tmpCustomsextraid);
			}
		}
	});
	
	if($("#chk_isEditToSku").is(':checked')){
		$.post(global.baseUrl + "carrier/default/edit-customs-info", {
			orders: checkOrderIds,customsName:tmpCustomsName,customsEName:tmpCustomsEName,customsDeclaredValue:tmpCustomsDeclaredValue,customsweight:tmpCustomsweight
		}, function(result) {
			bootbox.alert(result);
			location.reload();
		});
	}else{
		bootbox.alert({
			message:'批量修改成功',
			callback:function(){
				callback();
			}
		});
	}
}
</script>