<?php 
use yii\helpers\Html;
use yii\helpers\Url;
/*
// $account_data = $account['response']['data'];
// $param = $account_data['authParams'];
 ?>
// <?php foreach ($param as $k=>$p){
// 	$req = $p['carrier_is_required'];
// 	$req_class = ($req)?'required':'';
// 	$type = $p['carrier_display_type'];
// 	if($p['carrier_is_encrypt']) $type = 'password';
// ?>
<!-- 	<tr> -->
<!-- 		<td> -->
		 <label><?= (($req == true) ? '<b style="color: red">*</b>' : '' ).$p['carrier_param_name']?>：</label>
<!-- 		 </td> -->
<!-- 		 <td> -->
		 <?php 
// 		 	if($type == 'text'){
// 				echo Html::input($type,'carrier_params['.$k.']',$p['param_value'],['class'=>'modal_input iv-input '.$req_class]);
// 				echo '<span id="'.$carrier_code.'-'.$k.'" qtipkey="'.$carrier_code.'-'.$k.'"></span>';
// 			}
// 		 	else if($type == 'dropdownlist')
// 		 		echo Html::dropDownList('carrier_params['.$k.']',$p['param_value'],$p['carrier_param_value'],['class'=>'iv-input '.$req_class,'prompt'=>''])
// 		  ?>
<!-- 		 </td> -->
<!-- 	</tr> -->
// <?php }?>
*/
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
<form id="<?php echo $oversea==1?'oversea_account_form':'sys_account_form'; ?>" class="col-xs-12 accountbodys">
	<?php $account_data = $account['response']['data'];
			$param = $account_data['authParams'];
	?>
	<input type="hidden" name="<?php echo $oversea==1?'notWarehouseDropDownid':'notCarrierDropDownid'; ?>" value="<?php echo $carrier_code; ?>">
	<?php 
		echo Html::hiddenInput('id',$account_data['id']);
		
		if($oversea==1)
			echo Html::hiddenInput('hidwarehouse',$hidwarehouse);
		else if($oversea==-1){
			echo Html::hiddenInput('customtype','');
	?>
	<p class="myline row"><span class="col-xs-5 text-right"><b style="color: red">*</b> <label>自定义类型</label></span><span><?= Html::dropDownList('customselect','',['1'=>'Excel对接','0'=>'号码池分配'],['class'=>'iv-input']); ?></span><span qtipkey="carrier_account_explain"></span></p>
	<?php }?>
	<p class="myline row"><span class="col-xs-5 text-right"><b style="color: red">*</b> <label><?php 
			if($oversea==-1){
				?>物流商</label></span><span><?= Html::input('text','carrier_name', $account_data['accountNickname'],['class'=>'modal_input required iv-input','name'=>'accountNickname','placeholder'=>'自定义名称,为了方便多账号管理']) ?></span><span qtipkey="carrier_account_explain"></span></p>
			<?php 
			}else if($oversea==1){
				?>账号别名</label></span><span><?= Html::input('text','nickname', $account_data['accountNickname'],['class'=>'modal_input required iv-input','name'=>'accountNickname','placeholder'=>'自定义名称,为了方便多账号管理']) ?></span><span qtipkey="carrier_account_explain"></span></p>
			<?php }
			else{
				?>账号别名</label></span><span><?= Html::input('text','accountNickname', $account_data['accountNickname'],['class'=>'modal_input required iv-input','name'=>'accountNickname','placeholder'=>'自定义名称,为了方便多账号管理']) ?></span><span qtipkey="carrier_account_explain"></span></p>
			<?php }
			if($oversea==1){
				echo $tmpHtml;
			}
			else{
			
			
	 foreach ($param as $k=>$p){
		$req = $p['carrier_is_required'];
		$req_class = ($req)?'required':'';
		$type = $p['carrier_display_type'];
		if($p['carrier_is_encrypt']) $type = 'password';
		$name = $p['carrier_param_name'];
		$list = $p['carrier_param_value'];
		$val = $p['param_value'];

		if($p['is_hidden']!=1){
		?>
		<p class="myline col-xs-12"><span class="col-xs-5 text-right">
			<?php if($req){?><b style="color: red">*</b><?php }?>
			 <?= $name?>：</span><span>
			 <?php 
			 	if($type == 'text'){
			 		echo Html::input($type,'carrier_params['.$k.']',$val,['class'=>'modal_input iv-input '.$req_class,'name'=>'carrier_params['.$k.']']);
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
	<?php }}
	}
	/*
	if($type==0){
	?>
	<!-- <div name="isDefault" class="myline col-xs-12"><p class="col-xs-offset-5"><?= Html::checkbox('is_default',$account_data['isDefault'],['class'=>'','label'=>'默认物流账号','id'=>'isDefault','name'=>'isDefault']) ?></p></div>-->
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
<?php  
	if($oversea==-1)
		echo '<button type="button" class="btn btn-primary" onclick="self_carrier_save_new()">保存</button>';
	else if($oversea==1)
		echo '<button type="button" class="btn btn-primary" onclick="oversea_account_save(0)">保存</button>';
	else
		echo '<button type="submit" class="btn btn-primary" onclick="createAndOpenAccount()">保存</button>';
?>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>

<!--认证参数Qtip解释-->
<script>
    $.initQtip();
</script>