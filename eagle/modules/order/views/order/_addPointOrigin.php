<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>

<div>
	<?php /**
	<!-- 批量设置div start -->
	<div id="div_batch_setting"  style="<?= (count($orderList)>1)?"margin-bottom: 5px;":"display:none"?>";>
		<?=Html::label(TranslateHelper::t('备注'))?>
		<?=Html::textarea('batch_memo','',['id'=>'batch_memo','rows'=>'2','cols'=>'65','style'=>'margin:2px' , 'placeholder'=>TranslateHelper::t('在此输入备注信息，点击右边的“应用到所有”，即可批量把备注复制到勾选的订单中。用户可以提交前对每个订单备注再做修改。')])?>
		<?=HTML::button(TranslateHelper::t('应用到所有'),['class'=>"iv-btn btn-xs btn-default",'onclick'=>"OrderCommon.batch_set_memo()"]) ?>
	</div>
	<!-- 批量设置div end -->
	**/?>


	<!-- 订单的备列举 start -->
	<table class="table">
		<thead>
			<tr>
				<th width="25%"><?= TranslateHelper::t('来源平台单号')?></th>
				<th width="25%"><?= TranslateHelper::t('买家账号')?></th>
				<th width="50%"><?= TranslateHelper::t('发货地')?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($orderList as $anOrder){
			$addInfo = json_decode($anOrder['addi_info'],true);
			if( isset( $addInfo['order_point_origin'] ) && $addInfo['order_point_origin']!='' ){
				$now_select_country= $addInfo['order_point_origin'];
			}else{
				$now_select_country= 'CN';
			}

		?>
		<tr>
			<td width="25%" style="word-break: break-all;"><?php echo $anOrder['order_source_order_id']?></td>
			<td width="25%" style="word-break: break-all;"><?= $anOrder['selleruserid']?></td>
			<td width="50%" style="word-break: break-all;"><?=html::dropDownList('select_country',$now_select_country ,$countryList ,['style'=>'width:190px','data-order-id'=>$anOrder['order_id']])?></td>
		</tr>
		
		<?php
		}
		?>
		
		</tbody>
	</table>
	<!-- 订单的备列举 end -->
</div>

