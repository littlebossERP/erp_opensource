<?php 

use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
<label class="control-label col-lg-3" for="">物品状况<span class="requirefix">*</span></label>
<div class="col-lg-5">
<?php if(strlen($val['primarycategory'])==0): ?>
	<span style="color:red">请先选择刊登类目</span>
<?php elseif (@$condition['conditionenabled']=='Disabled'||(is_null(@$condition['conditionenabled'])&&is_null(@$condition['conditionvalues']))): ?>
	<span style="color:green">该类目无需设置物品属性</span>
<input type="hidden" name="conditionid" value="">
<?php else:?>
<?php 
//对于只有1个参数组的数组，进行整理
$_conditiontmp = [];
if (@isset($condition['conditionvalues']['Condition']['ID'])){
	$_conditiontmp = ['0'=>@$condition['conditionvalues']['Condition']];
}else{
	$_conditiontmp = @$condition['conditionvalues']['Condition'];
}
?>
<?php echo Html::dropDownList('conditionid', $val['conditionid'],Helper_Array::toHashmap($_conditiontmp, 'ID', 'DisplayName'),['class'=>'iv-input'])?>
<?php endif;?>
</div>
