<?php
use yii\helpers\Html;
?>
<div class="tracking-index col2-layout">
<?=$this->render('../_leftmenu');?>
<div class="content-wrapper" >
<table class="table table-condensed">
	<tr>
		<th>ItemID</th><th>操作人</th><th>reason</th><th>result</th><th>time</th><th>content</th><th>Message</th>
	</tr>
	<?php if (count($logs)):foreach ($logs as $log):?>
	<tr>
		<td><?=$log->itemid?></td>
		<td><?=$log->name?></td>
		<td><?=$log->reason?></td>
		<td><?=$log->result?></td>
		<td><?=date('Y-m-d H:i:s',$log->createtime)?></td>
		<td>
			<?php 
			if (!is_null($log->content)){
				$str='';
				foreach ($log->content as $k=>$t){  
		        	$str.=$k.':'.$t.'<br>'; 
		        }
		        echo $str; 
	        }
	        ?>
		</td>
		<td>
		<?php 
		if (!is_null($log->message)&&isset($log->message['Errors'])){
			$error=[];
			if (isset($log->message['Errors']['ShortMessage'])){
				$error['0']=$log->message['Errors'];
			}else{
				$error=$log->message['Errors'];
			}
			foreach ($error as $e){
				echo '<b>'.Html::encode($e['LongMessage']).'</b><br>';
			}
		}
		?>
		</td>
	</tr>
	<?php endforeach;endif;?>
</table>
</div>
</div>