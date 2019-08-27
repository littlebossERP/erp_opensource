<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;


?>
<style>

</style>
<?php if($step=='generated'){ ?>
	<p>生成任务完成。</p>
	<?php if(!empty($generateResult)){
			$total_msg = '';
			$err_msg = '';
		  	$result_msg = '';
		  	$result_has_failed = '';
		  	//var_dump($generateResult);
		foreach ($generateResult as $i=>$result){
			if(!empty($result['message']) && !$result['success'])
				$err_msg .= '<br>'.$result['message'];
			elseif (!empty($result['message']) && $result['success'])
				$result_msg .= '<br>'.$result['message'];
			
			if(!empty($result['insert_result']['result']))
				$result_msg .= '<br>'.$result['insert_result']['result'];
			
			if(!empty($result['insert_result']['failed']))
				$result_has_failed = '<br>创建任务时发送错误，请联系客服(失败的订单会于失败列表生成记录)<br>';
			
			if(!empty($result['matchOrders']))
				$result_msg .= '模板规定的时间内，找到符合规则的订单'.count($result['matchOrders']).'单；<br>';
			
			if(!empty($result['unMatchOrdersMsg'])){
				$result_msg .= '模板规定的时间内，如下订单由于以下原因不符合规则：<br>';
				$unMatchCount = 0;
				foreach ($result['unMatchOrdersMsg'] as $od_id=>$unMatchOrdersMsg){
					$unMatchCount ++ ;
					if($unMatchCount<30)
						$result_msg .= $unMatchOrdersMsg.'<br>';
					else 
						break;
				}
				if($unMatchCount>=30)
					$result_msg .= '更多请查看任务生成日志。';
			}
		}
		if($err_msg!==''){
			$total_msg .= '有错误或失败，原因：'.$err_msg;
		}
		if($result_msg!==''){
			$total_msg .= '任务生成结果：<br>'.$result_has_failed . $result_msg;
		}
		
		echo $total_msg;
	} ?>
<?php }else{ ?>
	<p style="font-size: 16px;padding-bottom:10px;"><?=@$message;?></p>
	<p style="font-size: 16px;padding-bottom:20px;">是否确认继续生成？</p>
	<div style="text-align: center;">
		<button type="button" class="btn btn-primary" onclick="amazoncs.Template.generateMailQuestConfirmed(<?=@$templateIds?>)">确认</button>
	</div>
<?php } ?>


