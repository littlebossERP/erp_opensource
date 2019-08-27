<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\OdOrder;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->title = TranslateHelper::t('待获取物流号');
//$this->params['breadcrumbs'][] = $this->title;
 ?>
<style>
	.hidetr td {
		overflow:hidden;
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
	
</style>
<style>
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
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
<?php echo $this->render('_leftmenu');?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<!-- 右侧table内容区域 -->
<div class="content-wrapper" >
<?php $doarr=[
		''=>'移动到',
		'0'=>'待上传到物流商',
		'1'=>'待交运',
		'2'=>'待获取物流号',
		'3'=>'待打印物流单',
		'6'=>'已完成',
		'4'=>'重新上传'
];
?>
<?php $divTagHtml = "";?>
<form action="" method="post" name='form1' id='form1'>
<button type="button" id="do-carrier" value='gettrackingno'  style="height: 28px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('批量获取物流号')?></button>
 &nbsp;
<?=Html::dropDownList('do','',$doarr,['class'=>'do eagle-form-control','onchange'=>"movestatus($(this).val());"]);?> &nbsp;
<?=Html::textInput('order_id',isset($search_data['order_id'])?$search_data['order_id']:'',['class'=>'eagle-form-control','placeholder'=>"订单号",'title'=>'订单号'])?>&nbsp;
<?=Html::textInput('customer_number',isset($search_data['customer_number'])?$search_data['customer_number']:'',['class'=>'eagle-form-control','placeholder'=>"客户参考号",'title'=>'客户参考号'])?>&nbsp;
<?=Html::hiddenInput('default_shipping_method_code',isset($search_data['default_shipping_method_code'])?$search_data['default_shipping_method_code']:'',['id'=>'default_shipping_method_code'])?>
<?=Html::hiddenInput('default_carrier_code',isset($search_data['default_carrier_code'])?$search_data['default_carrier_code']:'',['id'=>'default_carrier_code'])?>

<button type="submit" id="search" class="btn btn-primary btn-sm">搜索</button>
</form>
<form action="" method="post" name='a' id='a'>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover table-striped" style="font-size: 12px;">
		<tr class="list-firstTr">
			<th width="20px"><?=Html::checkbox('select_all','',['class'=>'select-all']);?></th>
			<th width="100px"><?=TranslateHelper::t('订单号')?><br></th>
			<th width="60px"><?=TranslateHelper::t('国家')?></th>
			<th class="text-nowrap" width="150px"><?=Html::dropDownList('default_carrier_code',isset($search_data['default_carrier_code'])?$search_data['default_carrier_code']:'',$carriers,['prompt'=>TranslateHelper::t('物流商'),'style'=>'width:65px;','class'=>'search']);?></th>
			<th class="text-nowrap" width="300px"><?=Html::dropDownList('default_shipping_method_code',isset($search_data['default_shipping_method_code'])?$search_data['default_shipping_method_code']:'',$services,['prompt'=>TranslateHelper::t('运输服务'),'style'=>'width:100px;','class'=>'search']);?></th>
			<th><?php echo TranslateHelper::t('提示信息');?></th>
			<th class="text-nowrap"><?=TranslateHelper::t('操作')?></th>
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
            <td><?=$data['consignee_country_code'] ?></td>
            <td><?=isset($carriers[$data['default_carrier_code']])?$carriers[$data['default_carrier_code']]:''; ?></td>
            <td><?=isset($services[$data['default_shipping_method_code']])?$services[$data['default_shipping_method_code']]:''; ?></td>
            <td>
            <font color="orange"><?php echo strlen($data['carrier_error'])?$data['carrier_error']:'未获取';?></font>
            <?php ?>
            </td>
            <td>
            <a href="<?=Url::to(['/carrier/carrieroperate/gettrackingno','order_id'=>$data['order_id']])?>"  target="_blank"><?php echo TranslateHelper::t('获取物流号');?></a>
            <?php if ($data['default_carrier_code']=='lb_epacket'){?>
            <a href="<?=Url::to(['/carrier/carrieroperate/recreate','order_id'=>$data['order_id']])?>"  target="_blank"><?php echo TranslateHelper::t('重新发货');?></a>
            <?php }?>
            </td>
		</tr>
        <?php endforeach;?>
        <?php endif; ?>
    </table>
</form>
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

	if(val == 4){
		bootbox.confirm("重新上传只会影响小老板系统上面的订单状态，不会修改物流商的物流订单状态，是否确定重新上传？", function (res) {
            if (res == true) {
            	$.showLoading();
        		$.post('<?=Url::to(['/carrier/default/movestep'])?>',{orderids:idstr,status:val},function(response){
        			$.hideLoading();
        			var result = JSON.parse(response);
        			if(result.Ack ==1){
        				bootbox.alert(result.msg);
        			}
        			window.location.reload();
        			$.showLoading();
        		});
            }
        });
	}else{
		$.showLoading();
		$.post('<?=Url::to(['/carrier/default/movestep'])?>',{orderids:idstr,status:val},function(response){
			$.hideLoading();
			var result = JSON.parse(response);
			if(result.Ack ==1){
				bootbox.alert(result.msg);
			}
			window.location.reload();
			$.showLoading();
		});
	}
	
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