	<p class="title">物品细节</p>
  	<?php if (count($specifics)):?>
  	<div class="subdiv">
	<table>
	<?php foreach($specifics as $sp):?>
	<tr>
	<th><?php echo $sp->name?></th>
	<td>
	<?php echo $this->render('_specific_value',array('specific'=>$sp,'val'=>$val))?>
	</td>
	<td>
	<?php if (strlen(@$sp->relationship['ParentName'])):?>
	        请先设置<?=$sp->relationship['ParentName']?>
	<?php endif;?>
	<?php if (strlen(@$sp->relationship['ParentValue'])):?>
	        为<?=$sp->relationship['ParentValue']?><br>
	<?php endif;?>
	<?php if ($sp->minvalue >0):?>
	        此项为必填
	<?php endif;?>
	</td>
	</tr>
	<?php endforeach;?>
	</table>
	</div>
	<?php endif;?>