<?php 
use yii\helpers\Html;
?>
<style>
.iv-myinput{
	border-color:#666666;
	border:1px solid #cdced0;
	height:26px;
	border-radius:3px;
}
</style>
<?php 
	/**
	 * @author fanjs
	 * 用于处理不同selectionmode的ebay specific选项页面
	 */
	$spzhal=$specific->value;
	$specificvalname=!isset($val[$specific->name])?'':$val[$specific->name];
	//单选必选
	if ($specific->selectionmode=='SelectionOnly' && $specific->minvalue>0){
		echo Html::dropDownList("specific[$specific->name]", $specificvalname, array_combine($spzhal,$spzhal),['class'=>'iv-input']);
	//单选未必填
	}elseif ($specific->selectionmode=='SelectionOnly' && $specific->maxvalue<2){
		echo Html::dropDownList("specific[$specific->name]", $specificvalname, array_combine($spzhal,$spzhal),['prompt'=>'','class'=>'iv-myinput']);
	//文本框
	}elseif ($specific->selectionmode == 'FreeText' && count($specific->value) ==0){
		echo Html::textInput("specific[$specific->name]",$specificvalname,['class'=>'iv-input']);		
	//combo 暂时只做成下拉选择
	}elseif ($specific->selectionmode == 'FreeText'){
?>
<input class="iv-input" name="specific[<?php echo $specific->name ?>]" list="<?php echo $specific->name ?>" value="<?php echo $specificvalname?>">
<datalist id="<?php echo $specific->name ?>">
<?php foreach ($spzhal as $s){?>
	<option value="<?=$s?>">
<?php }?>
</datalist>
<!-- echo CHtml::dropDownList("specific[$specific->name]", $specificvalname, array_combine($spzhal,$spzhal),array('class'=>'easyui-combobox','prompt'=>'')); -->
<?php 
		//多选框，可不选
	}elseif ($specific->maxvalue>1 && $specific->minvalue==0){
		echo Html::checkboxList("specific[$specific->name]", $specificvalname,array_combine($spzhal,$spzhal),['class'=>'iv-input']);
	}
