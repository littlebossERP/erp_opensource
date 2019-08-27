<?php
use yii\helpers\Html;
use common\helpers\Helper_Array;

$this->title='发送eBay账单';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if (isset($error)&&count($error)):?>
<div class="alert alert-danger" role="alert">
<?php foreach ($error as $e):?>
<?=$e?><br>
<?php endforeach;?>
</div>
<?php elseif(isset($result)):?>
<div class="alert alert-success" role="alert">
<?=$result?><br>
</div>
<?php else:?>
<?php if (strlen($order->user_message)):?>
<div class="alert alert-danger" role="alert">
买家请求留言:<?=$order->user_message?>
</div>
<?php endif;?>
<form  method="post">
<input type="hidden" name="order_id" value="<?=$order->order_id?>">
<input type="hidden" name="isinternational" value="<?=$isinternational?>">
<input type="hidden" name="siteid" value="<?=$siteid?>">
<div class="alert alert-info" role="alert">
订单：<?=$order->order_id?>
</div>
<div class="alert alert-warning" role="alert">
	<table >
	  <tr>
	    <th width="200">发送卖家邮箱</th>
	    <td>
	    	<div class="col-sm-4">
	    	<input type="checkbox" checked="checked" name="EmailCopyToSeller" value="1">
	    	</div>	
	    </td>
	  </tr>
	  <tr>
	  	<th>物流选项</th>
	  	<td>
	  		<div class="col-sm-10">
	  		<?=Html::dropDownList('ShippingService',$transaction->shippingserviceselected['ShippingService'],Helper_Array::toHashmap($shippingservices,'shippingservice','description'),['class'=>'form-control input-sm','prompt'=>''])?>
			</div>	
	  	</td>
	  </tr>
	  <tr>
	  	<th>首件</th>
	  	<td>
	  		<div class="col-sm-4">
	  		<?=Html::input('','ShippingServiceCost',@$transaction->shippingserviceselected['ShippingServiceCost'],['class'=>'form-control input-sm'])?>
	  		<?=$transaction->currency ?>
	  		</div>
		</td>
		</tr>
		<tr>
		<th>续件</th>
		<td>
			<div class="col-sm-4">
			<?=Html::input('','ShippingServiceAdditionalCost',@$transaction->shippingserviceselected['ShippingServiceAdditionalCost'],['class'=>'form-control input-sm'])?>
	  		<?=$transaction->currency ?>
	  		</div>
	  	</td>
	  </tr>
	  <tr>
	  	<th>留言</th>
	  	<td>
	  		<div class="col-sm-10">
	  		<textarea rows="5" cols="80" name="CheckoutInstructions" class="form-control input-sm"></textarea>
	  		</div>
	  	</td>
	  </tr>
	  <tr>
	  	<td colspan="2">
	  		<input type="submit" value="发送">
	  	</td>
	  </tr>
	</table>
</div>
</form>
<?php endif;?>