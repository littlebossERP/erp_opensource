<?php 
use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
<?php if(isset($return_policy['ReturnsAccepted'])):?>

<label class="control-label col-lg-3" for="returngoods" >退货<span class="requirefix">*</span></label>
<div class="col-lg-8">
<div class="whole-onebox">
	<?php echo Html::dropDownList('return_policy[ReturnsAcceptedOption]', @$data['return_policy']['ReturnsAcceptedOption'],Helper_Array::toHashmap($return_policy['ReturnsAccepted'],'ReturnsAcceptedOption','Description'),array('onchange'=>'return_policy_trigger(this)','class'=>'iv-input'))?>
		<div class="subdiv">
		<table>
		<?php if (isset($return_policy['Refund'])):?>
		<tr class="return_accepted_only">
			<th>退货方式</th>
		    <td>
		    <?php echo Html::dropDownList('return_policy[RefundOption]', @$data['return_policy']['RefundOption'],
		    		Helper_Array::toHashmap($return_policy['Refund'], 'RefundOption', 'Description'))?>
		    </td>
		</tr>
		<?php endif;?>
		<?php if (isset($return_policy['ReturnsWithin'])):?>
		<tr class="return_accepted_only">
			<th>接受退货天数</th>
		    <td>
		    <?php echo Html::dropDownList('return_policy[ReturnsWithinOption]', @$data['return_policy']['ReturnsWithinOption'],
		    		Helper_Array::toHashmap($return_policy['ReturnsWithin'], 'ReturnsWithinOption', 'Description'))?>
		    </td>
		</tr>
		<?php endif;?>
		<?php if (isset($return_policy['ShippingCostPaidBy'])):?>
		<tr class="return_accepted_only">
		    <th>退费承担</th>
		    <td>
		    <?php echo Html::dropDownList('return_policy[ShippingCostPaidByOption]', @$data['return_policy']['ShippingCostPaidByOption'],
		    		Helper_Array::toHashmap($return_policy['ShippingCostPaidBy'], 'ShippingCostPaidByOption', 'Description'))?>
		    </td>
		</tr>
		<?php endif;?>
		<?php if (isset($return_policy['RestockingFeeValue'])):?>
		<tr class="return_accepted_only">
		    <th>RestockingFeeValue</th>
		    <td>
		    <?php echo Html::dropDownList('return_policy[RestockingFeeValue]', @$data['return_policy']['RestockingFeeValue'],
		    		Helper_Array::toHashmap($return_policy['RestockingFeeValue'], 'RestockingFeeValueOption', 'Description'))?>
		    </td>
		    </td>
		</tr>
		<?php endif;?>
		<?php if ($return_policy['Description']==true):?>
		<tr class="return_accepted_only">
		    <th>退货说明</th>
		    <td>
		    <?php echo Html::textarea('return_policy[Description]',@$data['return_policy']['Description'],array('rows'=>5,'cols'=>60))?>
		    </td>
		</tr>
		<?php endif;?>
		</table>
		</div>
	<?php endif;?>
</div>
</div>
<script type="text/javascript">
        function return_policy_trigger(obj){
            if($(obj).val()=='ReturnsAccepted'){
                $('.return_accepted_only').show();
            }else{
                $('.return_accepted_only').hide();
            }
        }
</script>