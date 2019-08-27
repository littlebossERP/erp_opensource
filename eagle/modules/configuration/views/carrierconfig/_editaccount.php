<?php 

use yii\helpers\Html;
use yii\helpers\Url;


?>
<style>
	.drop{
		width:250px;
	}
	.modal_input{
		width:250px;
		height:25px;
		line-height:25px;
	}
	.myline{
		height:25px;
		line-height:1.5;
		margin:8px 0;
	}
	.modal-body{
		width:600px;
		min-height:300px;
	}
	.accountbodys{
		min-height:240px;
	}
</style>
<form id="accountEditFORM" class="col-xs-12 accountbodys">
	<?php $account_data = $account['response']['data'];
			$param = $account_data['authParams'];
	?>
	<?php 
		echo Html::hiddenInput('carrier_code',$carrier_code);
		echo Html::hiddenInput('id',$account_data['id']);
	?>
	<p class="myline row"><span class="col-xs-5 text-right"><b style="color: red">*</b> <label>账号别名(自定义)：</label></span><span><?= Html::input('text','accountNickname', $account_data['accountNickname'],['class'=>'modal_input required iv-input','placeholder'=>'自定义名称,为了方便多账号管理']) ?></span><span qtipkey="carrier_account_explain"></span></p>
	<?php foreach ($param as $k=>$p){
		$req = $p['carrier_is_required'];
		$req_class = ($req)?'required':'';
		$type = $p['carrier_display_type'];
		if($p['carrier_is_encrypt']) $type = 'password';
		$name = $p['carrier_param_name'];
		$list = $p['carrier_param_value'];
		$val = $p['param_value'];

		if(($p['is_hidden']!=1 || !empty($account_data['id'])) && !in_array($carrier_code,array("lb_chukouyi")) ){
		?>
		<p class="myline col-xs-12"><span class="col-xs-5 text-right">
			<?php if($req){?><b style="color: red">*</b><?php }?>
			 <?= $name?>：</span><span>
			 <?php 
			 	if($type == 'text'){
			 		echo Html::input($type,'carrier_params['.$k.']',$val,['class'=>'modal_input iv-input '.$req_class]);
			 		
			 		//start 认证参数解释
			 		foreach($qtipKeyArr as $qtipKey) {//判断是否存在相应解释在数据库
			 			if($qtipKey['tip_key']==($carrier_code.'-'.$k)){
			 				$existData = '<span id="'.$carrier_code.'-'.$k.'" qtipkey="'.$carrier_code.'-'.$k.'"></span>';
			 				break;
			 			}
			 			else{
			 				$noneData = '<span id="'.$carrier_code.'-'.$k.'" qtipkey="none_carrierParameter_explain"></span>';
			 				continue;
			 			}
			 		}
			 		if(!empty($existData)){
			 			echo $existData;
			 			$existData = null;
			 		}
			 		else{
			 			echo empty($noneData)?'':$noneData;
			 		}
			 		//end 认证参数解释
			 	}
			 	else if($type == 'dropdownlist')
			 		echo Html::dropDownList('carrier_params['.$k.']',$val,$list,['class'=>''.$req_class,'prompt'=>'请下拉选择']); //$name
			  ?>
			 </span></p>
	<?php }
	}
	/*
	if($accountcount>1 || empty($accountcount) || $account_data['id']===0){
	?>
	<div class="myline col-xs-12"><p class="col-xs-offset-5"><?= Html::checkbox('is_default',$account_data['isDefault'],['class'=>'','label'=>'默认物流账号','id'=>'isDefault']) ?></p></div>
	<?php }*/?>
	
	<?php
		if(!empty($account_data['help_url'])){
	?>
		<div class="myline col-xs-12"><p class="col-xs-offset-10"><a target="_blank" href='<?=$account_data['help_url'] ?>'>查看授权帮助</a></p></div>
	<?php
		}
	?>
	
</form>
<div class="modal-footer col-xs-12">
	<button type="button" class="btn btn-primary" onclick="saveAccount('<?= $carrier_code?>','<?= $account_data['id'] ?>')">保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>

<!--认证参数Qtip解释-->
<script>
    $.initQtip();
</script>
