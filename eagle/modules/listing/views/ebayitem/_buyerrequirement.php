<?php 
use yii\helpers\Html;
?>
<p class="title">买家必需用paypal</p>
<div class="subdiv">
<?php echo Html::radioList('buyerrequirementdetails[LinkedPayPalAccount]',
		@$data['buyerrequirementdetails']['LinkedPayPalAccount'], array('true'=>'是','false'=>'否'))?>
</div>
<p class="title">政策违反</p>
<div class="subdiv">
	<table>
  	<tr><td>违 反 次 数</td><td> <?php echo Html::dropDownList('buyerrequirementdetails[MaximumBuyerPolicyViolations][Count]',
		@$data['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Count'],
		array('','4'=>4,'5'=>5,'6'=>6,'7'=>7))?></td></tr>
	<tr><td>评估时段</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumBuyerPolicyViolations][Period]',
		@$data['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Period'],
		array('','Days_30'=>'30天内','Days_180'=>'180天内'))?></td></tr></table>
</div>
	<p class="title">不付款订单</p>
<div class="subdiv">
	<table>
  	<tr><td>不付款次数</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumUnpaidItemStrikesInfo][Count]',
		@$data['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Count'],
		array('','2'=>2,'3'=>3,'4'=>4,'5'=>5))?></td></tr>
	<tr><td>评估时段</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumUnpaidItemStrikesInfo][Period]',
		@$data['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Period'],
		array('','Days_30'=>'30天内','Days_180'=>'180天内','Days_360'=>'360天内'))?></td></tr></table>
</div>
	<p class="title">10天内限制拍卖</p>
  	<?php echo Html::dropDownList('buyerrequirementdetails[MaximumItemRequirements][MaximumItemCount]',
		@$data['buyerrequirementdetails']['MaximumItemRequirements']['MaximumItemCount'],array('','2'=>2,'3'=>3,'4'=>4,'5'=>5),['class'=>'iv-input main-input'])?>次
