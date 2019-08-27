<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OrderRelation;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

// amazon 支持下拉及手写
$this->registerJs("$('[data-platform=amazon]').combobox({removeIfInvalid:false,allNull:true});" , \yii\web\View::POS_READY);

$this->registerJs('$("select[data-platform=cdiscount][name^=\'shipmethod[\']").change(
	function(){
		var method = $(this).val();
		var tmp = $(this).parents("tr").find("input[name^=\'trackurl[\']:eq(0)");
		var cd_othermethod = $(this).parents("tr").find(".cd_othermethod");
		if(method!==\'Other\' && method!==\'\'){
			tmp.val(\'\');
			tmp.attr("readonly","readonly");
			cd_othermethod.css("display","none");
		}else{
			tmp.removeAttr("readonly","readonly");
			cd_othermethod.css("display","block");
		}
});' , \yii\web\View::POS_READY);
?>

<style>
.ui-autocomplete {
	z-index: 2000 !important;
	overflow-y: scroll;
	max-height: 400px;
}

.ui-combobox-input{
	width: 253px;
	border: 1px solid #ABADB3;
	color: #333333;
	border: 1px solid #cdced0;
    background-color: #FFFFFF;
    padding: 3px 5px;
    position: relative;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    display: inline-block;
    vertical-align: middle;
}

.ui-combobox-toggle{
	border: 1px solid #ABADB3;
	color: #333333;
	border: 1px solid #cdced0;
    background-color: #FFFFFF;
    padding: 3px 5px;
    position: relative;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    display: inline-block;
    vertical-align: middle;
}
</style>

<br>
<form action="<?=Url::to(['/order/order/signshippedsubmit'])?>" method="post">
<?php if (count($orders)):foreach ($orders as $order):?>
<div class="alert alert-dismissible" role="alert"  style="border:1px solid #d9effc;">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <?php 
  $odship=OdOrderShipped::find()->where(['order_id'=>$order->order_id])->orderBy('id DESC')->one();
  if ($odship==null){// dzt20160825 如果合并订单的原始订单没有发货信息，即读取合并订单发货信息
  	$smOrderRel = OrderRelation::findOne(['father_orderid'=>$order->order_id , 'type'=>'merge' ]);
	if(!empty($smOrderRel)){
		$odship=OdOrderShipped::find()->where(['order_id'=>$smOrderRel->son_orderid])->orderBy('id DESC')->one();
	}
  }

  if ($odship==null){
  	$odship = new OdOrderShipped();
  }
  ?>
  	<table class="table table-bordered" style="border:0px;">
  		<tr style="background-color: #f4f9fc;font-size:12px;">
  			<td colspan="5">
  				小老板订单号:<b><?=$order->order_id?><?=Html::input('hidden','order_id[]',$order->order_id)?></b>&nbsp;
  				<?php if ($order->order_source == 'ebay'):?>
  					<?= ucfirst($order->order_source)?> SRN:<b><?=$order->order_source_srn?></b>
  				<?php else:?>
  					<?= ucfirst($order->order_source)?>订单号:<b><?=$order->order_source_order_id?></b>
  				<?php endif;?>
  				
  				<?php if (count($order->queueships)):?>
					<span style="background-color:#fff8d9"><font color="red">!</font>该订单在标记发货队列中已处理或未处理,如果不希望重复标记,您可以取消该订单</span>
				 <?php endif;?>
				 
				 
  		</td></tr>
  		<tr>
  			<td style="line-height: 40px;">收件人<?=$order->source_buyer_user_id?></td>
  			<td>
  				运单号
	  			<?php if(in_array($order->order_source, ['cdiscount']) && !empty($odship->tracking_number)):?>
	  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>30,'style'=>'width:auto;cursor:not-allowed;background-color:#eee;display:inline-block;','readonly'=>'readonly','class'=>'form-control'])?>
	  			<?php else:?>
	  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>30,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
	  			<?php endif;?>
  			</td>
  			<td>
  				查询网址<?=Html::input('',"trackurl[$order->order_id]",strlen($odship->tracking_link)?$odship->tracking_link:'',['size'=>80,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
  			</td>
  			
  			<td>
  			<?php if(in_array($order->order_source, ['aliexpress','dhgate'])): ?>
  			<?=Html::dropDownList("signtype[$order->order_id]",$odship->signtype,array('all'=>'全部发货','part'=>'部分发货'),['prompt'=>'标记类型','style'=>'width:150px;','id'=>'carrier_code','class'=>'eagle-form-control'])?>
			<?php endif;?>
			</td>
			
  			<td>运输服务
  			<?php 
  			$othermethodDisplay='display:block';
  			if (strlen($odship->shipping_method_code)>0){
				$method = $odship->shipping_method_code;
			}else{
				if (!empty($order->default_shipping_method_code)){
					$method=CarrierApiHelper::getServiceCode($order->default_shipping_method_code,$order->order_source);
				}else{
					$method = CarrierApiHelper::getDefaultServiceCode($order->order_source);;
				}
			}
			
			if (in_array($order->order_source, ['ebay']) || empty($allShipcodeMapping[$order->order_source])){
				echo Html::input('',"shipmethod[".$order->order_id."]",$method,['size'=>25]);
			}else{
				//兼容
				if(in_array($order->order_source, ['cdiscount'])){
					$tmp_methods = array_keys($allShipcodeMapping[$order->order_source]);
					foreach ($tmp_methods as $t_m){
						if(strtoupper($t_m) == strtoupper($method)){
							$method = $t_m;
							if(strtolower($method)!=='other')
								$othermethodDisplay = 'display:none';
							break;
						}
					}
				}
				
				// 手写method 支持选择
				if(in_array($order->order_source, ['amazon']) && !isset($allShipcodeMapping[$order->order_source][$method])){
					$allShipcodeMapping[$order->order_source][$method] = $method;
				}
				echo Html::dropDownList("shipmethod[".$order->order_id."]",$method,$allShipcodeMapping[$order->order_source],['data-platform'=>$order->order_source , 'prompt'=>'物流方式','style'=>'width:200px;display:inline-block;','id'=>'carrier_code','class'=>'form-control']);
			}
			?>
			<?php if(in_array($order->order_source, ['cdiscount'])):?>
			<br><span class="cd_othermethod" style="<?=$othermethodDisplay ?>">物流名称 <?=Html::input('',"othermethod[$order->order_id]",@$odship->shipping_method_name,['placeholder'=>'最好填法文或英文运输服务名','size'=>30,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?></span>
			<?php endif;?>
  			</td>
  		</tr>
  		<tr>
	  		<td colspan="5">
	  			<?php if(in_array($order->order_source, ['ebay','aliexpress','wish','dhgate'])):?>
	  			发货留言<?=Html::input('',"message[$order->order_id]",'',['size'=>200])?>
	  			<?php endif;?>
	  		</td>	
  		</tr>
  	</table>
</div>
<?php endforeach;endif;?>
<center><?=Html::submitButton('标记发货',['class'=>'btn btn-success'])?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=Html::button('取消',['class'=>'btn','onclick'=>'window.close();'])?>
</center>
</form>

<br/><br/>
<!-- 展示该订单的标记历史及结果 -->
<table class="table table-condensed" style="font-size:12px">
<tr>
	<th>订单号</th>
	<th>运单号</th>
	<th>客户参考号</th>
	<th>物流商</th>
	<th>结果</th>
	<th>来源</th>
	<th>错误</th>
	<th>创建时间</th>
	<th>最后标记时间</th>
</tr>
<?php if (count($logs)):foreach ($logs as $log):?>
<tr>
	<td><?=$log->order_id?></td>
	<td><?=$log->tracking_number?></td>
	<td><?=$log->customer_number?></td>
	<td><?=$log->shipping_method_code?></td>
	<td>
		<?php if($log->result == 'false'){?>
			失败
		<?php }elseif($log->result == 'true'){?>
			成功
		<?php }?>
	</td>
	<td><?=$log->addtype?></td>
	<td><?=$log->errors?></td>
	<td><?=date('Y-m-d H:i:s',$log->created)?></td>
	<td><?=date('Y-m-d H:i:s',$log->lasttime)?></td>
</tr>
<?php endforeach;endif;?>
</table>