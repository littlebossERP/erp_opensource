<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\apihelpers\ApiHelper;
use eagle\models\OdOrderShipped;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carrier.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->title = TranslateHelper::t('已完成');
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
<?php $divTagHtml = "";?>
<!---------------------------------------- oms 2.1 nav end  ------------------------------------------------------>
	<div style="padding-top:5px;height:40px;">
		<button type="button" id="do-carrier" onclick="doaction('completecarrier')"  style="height: 30px;width:100px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('确认发货完成')?></button>
		 &nbsp;
		 <button type="button" id="do-carrier"  onclick="doaction('reupload')"  style="height: 30px;width:100px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('重新上传')?></button>
		 &nbsp;
		 <button type="button" id="do-carrier"  onclick="doaction('gettrackingno')"  style="height: 30px;width:100px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('获取跟踪号')?></button>
		 &nbsp;
		  <button type="button" id="do-carrier"  onclick="doaction('doprint')"  style="height: 30px;width:100px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('打印物流单')?></button>
		 &nbsp;
	 </div>
	  <form action="" method="post" name='a' id='a'>
<table cellspacing="0" cellpadding="0" width="100%"  class="table table-hover table-striped" style="font-size: 12px;">
		<tr class="list-firstTr">
			<th width="1%"><?=Html::checkbox('select_all','',['class'=>'select-all','onclick'=>'selectall(this)']);?></th>
			<th width="4%"><?=TranslateHelper::t('单号')?><br></th>
			<th width="12%"><?=TranslateHelper::t('平台')?></th>
			<th width="10%"><?=TranslateHelper::t('帐号')?></th>
			<th width="15%"><?=TranslateHelper::t('平台订单号')?></th>
			<th width="4%"><?=TranslateHelper::t('收件国家')?></th>
			<th width="13%"><?=TranslateHelper::t('SKU x 数量')?></th>
			<th width="10%"><?=TranslateHelper::t('物流商/物流单号')?></th>
			<th width="15%"><?=TranslateHelper::t('运输服务/跟踪号')?></th>
			<th width="10%"><?=TranslateHelper::t('客户参考号')?></th>
			<th width="6%"><?=TranslateHelper::t('操作')?></th>
		</tr>
        <?php if(count($orders)>0): ?>
        <?php foreach($orders['data'] as $data):?>
        <tr data="<?=$data['order_id'] ?>" ems="<?=key($orders['ems'])?>">
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
            <td><?=isset($carriers[$data['default_carrier_code']])?$carriers[$data['default_carrier_code']]:'未匹配到物流商'; ?>
            		<?=isset($data['customer_number'])?'<br>'.$data['customer_number']:''?>
            </td>
            <td><?=isset($services[$data['default_shipping_method_code']])?$services[$data['default_shipping_method_code']]:'未匹配到运输服务'; ?>
            		<?php $shipped = OdOrderShipped::find()->where(['order_id'=>$data['order_id'] ])->andwhere( " ifnull(tracking_number, '') <> '' " )->orderby('id desc')->one();
            		$trackNum = empty($shipped)?'':'<br>'.$shipped->tracking_number;
            		echo $trackNum;?>
            </td>
         	<td>
         		<?=$data->customer_number?>
         	</td>
            <td>
            	<!--  <input type="button" value="取消物流操作" class="iv-btn btn-primary" onclick="carriercancel()" url="<?=Url::to(['/carrier/default/carriercancel','order_id'=>$data['order_id']])?>"/>-->
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
</div>
<script>
//取消物流操作
function carriercancel(){
	$.showLoading();
	var url = $(this).attr('url');
	$.get(url,function (data){
			$.hideLoading();
			var retinfo = eval("(" + data +")");
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		}
	);
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
</script>