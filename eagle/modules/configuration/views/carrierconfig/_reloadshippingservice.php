<?php
use yii\helpers\Html;
?>

	<?php foreach ($Shipping as $k=>$ship){?>
		<tr data='<?= $ship['id']?>'>
			<td style='width: 50px;text-align:center;'><?= Html::checkbox('selectShip['.$ship['service_name'].']',false,['class'=>'selectShip','value'=>$ship['id']])?></td>
			<td><?= @$ship['service_name'].'('.@$ship['shipping_method_code'].')' ?></td>
			<?php if($type != 'custom'){?><td><?= @$ship['shipping_method_name']?></td><?php }?>
			<td><?= @$ship['is_close'] == 1 ? "<font color='red'>是</font>" : '否' ?></td>
			<td><?= @$ship['is_used'] == 1 ? '开启' : '关闭' ?></td>
			<td><?= @$ship['accountNickname']?></td>
			<td><?= @$ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+$serviceUser[$k]['rule'],['onchange'=>'selectRuless(this,'.$ship['id'].')','class'=>'iv-input']) : ''?></td>
			<?php if($type != 'custom'){?>
				<td>
					<?php if($ship['is_copy']){?>
						<a class="btn btn-xs" onclick="delServiceNow(this)">删除</a>
					<?php }?>
					
					<?php 
						if($ship['is_used']){
							echo "<a class='btn btn-xs' onclick=openEditServiceModel(this,'copy')>复制</a>";
     						echo "<a class='btn btn-xs' onclick=openEditServiceModel(this,'edit')>编辑</a>";
							echo "<a class='btn btn-xs' onclick=openOrCloseShipping(this,'close','api')>关闭</a>";
						}else
     						echo "<a class='btn btn-xs' onclick=openOrCloseShipping(this,'open','api')>开启</a>";
		     		?>
				</td>
			<?php }else{?>
				<td style="padding:8px 0;">
					<a class="btn btn-xs" onclick="$.openModal('/configuration/carrierconfig/address',{id:<?= $ad['id']?>,codes:'<?= @$codes_key?>'},'编辑地址信息','get')">编辑</a>
					<a class="btn btn-xs" onclick="openDelAddressModal(<?= $ad['id']?>)">删除</a>
					<?php if($ad['is_default']){
							echo '<a class="btn btn-xs def_address" data="'.$ad['id'].'" style="color:#FF9900;">默认地址</a>';
						}
						else
					     	echo '<a class="btn btn-xs" data="'.$ad['id'].'" onclick="setDefaultAddress('.$ad['id'].',this)">设为默认</a>'
					?>
				</td>
			<?php }?>
		</tr>
	<?php }?>