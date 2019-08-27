<?php
use yii\helpers\Html;
?>

<style>
	.myline{
		height:25px;
		line-height:1.5;
		margin:8px 0;
	}


/* .modal-body{ */
/* /*  	width:250px;  */ */
/* 	height:120px; */
/* } */

/* .iv-modal .modal-content{ */
/*  	min-height:150px; */
/*  	max-height:350px; */
/*  	height:230px;  */
/* } */

/* .modal-footer{ */
/* 	border-top:none; */
/* } */

/* .modal_input{ */
/* 	width:250px; */
/* } */

/* .row{ */
/* 	margin-top:10px; */
/* } */
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
		width:550px;
		min-height:300px;
	}
	.accountbodys{
		min-height:240px;
	}
</style>


<form id='carrier_account_from' class="col-xs-12 accountbodys">
<?=Html::hiddenInput('id',$id) ?>
<?=Html::hiddenInput('third_party_code',$third_party_code) ?>
<?=Html::hiddenInput('carrier_code',$carrier_code) ?>
<?=Html::hiddenInput('warehouse_id',$warehouse_id) ?>

<div class="myline row"><span class="col-xs-4 text-right"><b style="color: red">*</b> <label>账号简称：</label></span><span><?= Html::input('text','name', @$carrier_account['response']['data']['accountNickname'],['class'=>'modal_input required iv-input','placeholder'=>'自定义名称,为了方便多账号管理']) ?></span></span><span qtipkey="carrier_account_explain"></span></div>

<?php foreach (@$carrier_account['response']['data']['authParams'] as $k=>$p){
		$req = $p['carrier_is_required'];
		$req_class = ($req)?'required':'';
		$type = $p['carrier_display_type'];
		if($p['carrier_is_encrypt']) $type = 'password';
		$name = $p['carrier_param_name'];
		$list = $p['carrier_param_value'];
		$val = $p['param_value'];
		if(($p['is_hidden']!=1 || !empty($carrier_account['response']['data']['id'])) && !in_array($carrier_code,array("lb_chukouyiOversea"))){
		?>
		<div class="myline row"><span class="col-xs-4 text-right">
			<?php if($req){?><b style="color: red">*</b><?php }?>
			 <label><?= $name?>：</label></span><span>
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
			 		echo Html::dropDownList('carrier_params['.$k.']',$val,$list,['class'=>'modal_input iv-input '.$req_class])
			  ?>
			 </span></span></div>
	<?php }        
		}/*
	if($account>1||empty($account)){
	?>

<div class="myline row">
<p class="col-xs-offset-4"><?= Html::checkbox('default',@$carrier_account['response']['data']['isDefault'],['class'=>'','label'=>'默认物流账号','id'=>'isDefault']) ?></p>
</div>
<?php }*/?>
</form>


<div class="modal-footer  col-xs-12">
	<button type="button" class="btn btn-primary" onclick="oversea_carrier_save()">保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>