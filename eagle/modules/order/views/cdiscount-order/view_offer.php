<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$shipping_code=[
	'Untracked',
	'Tracked',
	'Registered',
];
?>
<style>
.table h3{
	font-weight: bold;
    font-size: 14px;
    padding-top: 10px;
    font-family: "SimSun";
    line-height: 29px;
    color: #374655;
    padding-left: 15px;
}
</style>
<div>
	<?php if(empty($offer) && !empty($errMsg)): ?>
		<p><?=$errMsg ?></p>
	<?php else:?>
	<table style="width:100%;" class="table">
		<tr style="width:100%;">
			<th style="text-align: left;">商品描述</th>
		</tr>
		<tr style="width:100%;">
			<td style="text-align: left;">
				<?php if(!empty($offer['description'])):?>
				<p style="width:100%;background-color:#E1E6EA;"><?=$offer['description'] ?></p>
				<?php else:?>
				<p style="color:red;">无</p>
				<?php endif;?>
			</td>
		</tr>
		<tr style="width:100%;">
			<th style="text-align: left;">商品备注</th>
		</tr>
		<tr style="width:100%;">
			<td style="text-align: left;">
				<?php if(!empty($offer['comments'])):?>
				<p style="width:100%;"><?=$offer['comments'] ?></p>
				<?php else:?>
				<p style="color:red;">无</p>
				<?php endif;?>
			</td>
		</tr>
		<tr style="width:100%;">
			<th style="text-align: left;">商品图片</th>
		</tr>
		<tr style="width:100%;">
			<td style="text-align: left;">
			<div style="width:100%;">
				<?php if(!empty($offer['img'])){
					$photos = json_decode($offer['img'],true);
					foreach ($photos as $p){
						echo "<img src='".$p."' style='float:left;padding:5px;width:100px;height:100px;' >";
					}
				}else{?>
					<p style="color:red;">无</p>
				<?php }?>
			</div>
			</td>
		</tr>
		<tr style="width:100%;">
			<th style="text-align: left;">递送方式</th>
		</tr>
		<tr style="width:100%;">
			<td style="text-align: left;">
				<table class="table">
					<tr style="border: 1px solid;">
						<th style="text-align:center;padding:5px;">递送方式</th>
						<th style="text-align:center;padding:5px;">时效（天）</th>
						<th style="text-align:center;padding:5px;">运费</th>
						<th style="text-align:center;padding:5px;">额外收费</th>
					</tr>
					<?php if(!empty($offer['shipping_information_list'])){
						$shipping_info = json_decode($offer['shipping_information_list'],true);
						if(!empty($shipping_info['ShippingInformation'])){
							foreach ($shipping_info['ShippingInformation'] as $i=>$info){
								echo "<tr style='border: 1px solid;'>";
								echo "<td style='text-align:center;padding:5px;'>".(empty($info['DeliveryMode']['Code'])?'':$info['DeliveryMode']['Code']." :").(empty($info['DeliveryMode']['Name'])?$shipping_code[$i]:$info['DeliveryMode']['Name'])."</td>";
								echo "<td style='text-align:center;padding:5px;'>".$info['MinLeadTime'].'-'.$info['MaxLeadTime']."</td>";
								echo "<td style='text-align:center;padding:5px;'>".$info['ShippingCharges']."</td>";
								echo "<td style='text-align:center;padding:5px;'>".$info['AdditionalShippingCharges']."</td>";
								echo "<tr>";
							}	
						}
					}?>
				</table>
			</td>
		</tr>
	
	</table>
	<?php endif;?>
</div>
