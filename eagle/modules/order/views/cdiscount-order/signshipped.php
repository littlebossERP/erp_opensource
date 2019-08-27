<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJs('$("select[name^=\'shipmethod[\']").change(
	function(){
		var method = $(this).val();
		var tmp = $(this).parents("tr").find("input[name^=\'trackurl[\']:eq(0)");
		var tmp_mothed_div = $(this).parents("tr").find(".othermethod_div:eq(0)");
		if(method!==\'Other\' && method!==\'\'){
			tmp.val(\'\');
			tmp.attr("readonly","readonly");
			tmp_mothed_div.css("display","none");
		}else{
			tmp.removeAttr("readonly","readonly");
			tmp_mothed_div.css("display","block");	
		}
});' , \yii\web\View::POS_READY);
?>
<br>
<form action="<?=Url::to(['/order/cdiscount-order/signshippedsubmit'])?>" method="post">
<?php if (count($orders)):foreach ($orders as $order):?>
<div class="alert alert-dismissible" role="alert"  style="border:1px solid #d9effc;">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <?php $odship=OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->orderBy('id DESC')->one();?>
  	<table class="table table-bordered" style="border:0px;">
  		<tr style="background-color: #f4f9fc;font-size:12px;">
  			<td colspan="4">
  				订单号:<b>
  					<?=$order->order_source_order_id ?></b>
  				小老板订单id:<b>
  					<?=$order->order_id ?>
  					<?=Html::input('hidden','order_id[]',$order->order_id)?></b>
  				<?php if (count($order->queueships)){ ?>
  				<?php if(empty($odship)) $odship=OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>1])->orderBy('updated  DESC')->one(); ?>
					<span style="background-color:#fff8d9"><font color="red">(Cdiscount一旦标记发货过一次后，不能再更改物流信息)</font>该订单在标记发货队列中已处理或未处理,如果不希望重复标记,您可以取消该订单已有的物流操作</span>
				 <?php } ?>
  		</td></tr>
  		<tr>
  			<td style="vertical-align: middle;width:15%">收件人: <?=$order->source_buyer_user_id?></td>
  			<td style="vertical-align: middle;width:15%">运单号
  			<?php if(!empty($odship->tracking_number)):?>
  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>30,'style'=>'width:auto;cursor:not-allowed;background-color:#eee;display:inline-block;','readonly'=>'readonly','class'=>'form-control'])?>
  			<?php else:?>
  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>30,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
  			<?php endif;?>
  			<!-- <span qtipkey=""></span> --></td>
  			<?php $track_url='';$method='';$othermethodDisplay='display:block';
  			if (isset($odship->shipping_method_code)){
				$method = $odship->shipping_method_code;
			}else{
				if (!empty($order->default_shipping_method_code)){
					$method=CarrierApiHelper::getServiceCode($order->default_shipping_method_code,$order->order_source);
					$track_url = CarrierApiHelper::getServiceUrl($order->default_shipping_method_code,$order->order_source);;
				}else{
					$method = $order->order_source_shipping_method;
				}
			}?>
			
  			<td style="vertical-align: middle;width:50%"><span>查询网址</span><?=Html::input('',"trackurl[$order->order_id]",!empty($odship->tracking_link)?$odship->tracking_link:$track_url,['size'=>80,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?><span qtipkey="cd_ship_method_url"></span></td>
  			<td style="vertical-align: middle;width:15%"><div>
	  			<span>物流方式</span>
				<?php   
				//兼容
				$tmp_methods = array_keys($cdiscountShippingMethod);
				foreach ($tmp_methods as $t_m){
					if(strtoupper($t_m) == strtoupper($method)){
						$method = $t_m;
						if(strtolower($method)!=='other')
							$othermethodDisplay = 'display:none';
						break;
					}
				}
				?>
				<?php //echo Html::input('text',"shipmethod[$order->order_id]",'',['placeholder'=>'最好填法文或英文运输服务名','style'=>'width:200px;']); ?>
				<?php echo Html::dropDownList("shipmethod[$order->order_id]",$method,$cdiscountShippingMethod,['prompt'=>'物流方式','style'=>'width:200px;display:inline-block;','id'=>'carrier_code','class'=>'form-control'])?>
			</div>
			<div class="othermethod_div" style="<?=$othermethodDisplay?>">
				<span>物流名称 </span><?=Html::input('',"othermethod[$order->order_id]",@$odship->shipping_method_name,['placeholder'=>'最好填法文或英文运输服务名','size'=>30,'style'=>'width:auto;display:inline-block','class'=>'form-control'])?>
			</div>
  			</td>
  		</tr>
  		<tr style="display: none">
  		<td colspan="4">发货留言<?=Html::input('',"message[$order->order_id]",'',['size'=>200,'display'=>'none'])?></td>
  		</tr>	
  	</table>
</div>
<?php endforeach;endif;?>
<center><?=Html::submitButton('标记发货',['class'=>'btn btn-success'])?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=Html::button('取消',['class'=>'btn','onclick'=>'window.close();'])?>
</center>
</form>