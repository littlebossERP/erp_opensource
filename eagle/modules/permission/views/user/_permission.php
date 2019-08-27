<?php 
use eagle\modules\order\models\OdOrder;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<style>
.dropdown-controller{
	width: 13px;
    height: 13px;
    display: inline-block;
    cursor: pointer;
	font-weight: bolder;
    color: green;
}
.account-dropdown-div{
	overflow:auto;
}
</style>
<tr style="font-size: 16px"><td style="font-weight: bold;" colspan="<?=$colspan ?>">授权可用的模块 （请在要显示的模块前打勾）</td></tr>
<?php $index = 0;foreach ($modules as $key=>$moduleName){?>
<?php $key = strtolower($key);?>
<?php if($index == 0 || $index % $colspan == 0){?>
<tr>
<?php } ?>
	<td <?php if($key=='catalog') { $index++; echo 'colspan="2"';} ?>>
		<input id="module-<?=$key?>" type="checkbox" name="modules[]" 
		<?php if(!empty($permission)){
			$str = '';
			if(empty($permission['version']) || $permission['version'] < 1.2){
				if(in_array($key, ['image'])){
					$str = "checked";
				}
			}
			if(empty($permission['version']) || $permission['version'] < 1.3){
				if(in_array($key, ['cdiscount'])){
					$str = "checked";
				}
			}
			
			if(!empty($permission['modules']) && in_array($key, $permission['modules'])){
				$str = "checked";
			}
			echo $str;
		}?> 
		value="<?=$key?>" style="margin-top:2px;">
	<?php if(in_array($key, ['inventory', 'catalog', 'delivery'])){?> 
		<LABEL for="module-<?=$key?>"><?=$moduleName?>（</LABEL>
		<input id="module-<?=$key?>-edit" type="checkbox" name="modules[]" 
			<?php 
				if(!empty($permission) && !empty($permission['modules'])){
					if($key == 'catalog' && empty($permission['version']) && in_array($key, $permission['modules']))
						echo "checked";
					else if($key == 'delivery' && (empty($permission['version']) || $permission['version'] < 1.3) && in_array($key, $permission['modules']))
						echo "checked";
					else if(in_array($key.'_edit', $permission['modules']))
						echo "checked";
				}
			?> 
			value="<?=$key?>_edit" style="margin-right:0px; margin-left:0px;">
		<LABEL for="module-<?=$key?>-edit"><?= $key == 'delivery' ? '打包' : '编辑'?> </LABEL>
		<?php if($key == 'catalog'){
			$types = [
				'export' => '导出', 
				'delete' => '删除',
			];
			foreach($types as $type => $type_name){
		?>
			<input id="<?= $key.'_'.$type ?>" type="checkbox" name="module_chli[]" 
			<?php 
				if(!empty($permission)){
					if((empty($permission['version']) || $permission['version'] < 1.4) && in_array($key, $permission['modules']))
						echo "checked";
					else if(!empty($permission['module_chli']) && in_array($key.'_'.$type, $permission['module_chli']))
						echo "checked";
				}
			?> 
			value="<?= $key.'_'.$type ?>" style="margin-right:0px; margin-left:0px;">
			<LABEL for="<?= $key.'_'.$type ?>"><?= $type_name ?> </LABEL>
		<?php }}?>
		<LABEL>）</LABEL>
	<?php }else{?>
		<LABEL for="module-<?=$key?>"><?=$moduleName?></LABEL>
	<?php }?>
	</td>
	
<?php if($index % $colspan == ($colspan-1)){?>
</tr>
<?php }?>
<?php $index++;?>
<?php }?>
<?php if($index % $colspan != 0){?>
</tr>
<?php }?>

<tr style="font-size: 16px"><td style="font-weight: bold;" colspan="<?=$colspan ?>">授权可用的设置 （请在要显示的设置前打勾）</td></tr>
<tr>
<?php foreach ($setting_modules as $key => $moduleName){
	$key = strtolower($key);?>
	<td>
		<input id="module-<?=$key?>" type="checkbox" name="setting_modules[]" <?= (!empty($permission) && (!isset($permission['setting_modules']) || in_array($key, $permission['setting_modules'])))?"checked":""?> value="<?=$key?>" style="margin-top:2px;">
		<LABEL for="module-<?=$key?>"><?=$moduleName?></LABEL>
	</td>
<?php }?>
</tr>

<?php $platform_source = OdOrder::$orderSource;?>

<?php $checkAllPlatform = (!empty($permission) && !empty($permission['platforms']) && (in_array("all", $permission['platforms']) || in_array('all',array_keys($permission['platforms']))) )?true:false;?>
<tr style="font-size: 16px"><td style="font-weight: bold;" colspan="<?=$colspan ?>">授权可用的平台&账号  <span class=""> <input id="platform-all" type="checkbox" name="platforms[]" <?=$checkAllPlatform?"checked":""?> value="all" style="margin-top:2px;margin-left:20px;"><LABEL for="platform-all" >全部</LABEL></span><span qtipkey="platform_has_full_permission"></span></td></tr>
<?php $index = 0;foreach ($platformList as $platform){?>
<?php $platform = strtolower($platform);?>

<tr>

	<td colspan="4" style="<?=( !empty($platformAccountList[$platform]) || !empty($permission['platforms']) && (in_array($platform, $permission['platforms']) || array_key_exists($platform,$permission['platforms'])) )?'':"display:none;" ?>">
		<div style="position: relative;">
			<h5 style="background-color:#f4f9fc;font-weight: bold;padding-bottom:5px;" >
				<!-- 平台是否有任何权限 start -->
				<input type="checkbox" name="platforms[]" style="display:none" data-platform="<?=$platform?>" 
					<?php $platform_has_account_permission = false;
						if($checkAllPlatform) $platform_has_account_permission = true;
						if(!empty($permission['platforms'])) {
							if( in_array($platform, $permission['platforms']) || array_key_exists($platform,$permission['platforms']) )
								$platform_has_account_permission = true;
						}
						echo ($platform_has_account_permission)?"checked":"";
					?> 
					value="<?=$platform?>" style="margin-top:2px;"
				>
				<!-- 平台是否有任何权限 end -->
				<!-- 平台是否有所有权限 start -->
				<?php $platform_has_full_permission = false;
					if(!empty($permission['platforms']) && in_array($platform, $permission['platforms']))
						$platform_has_full_permission = true;
					if(!empty($permission['platforms'][$platform]) && in_array('all', $permission['platforms'][$platform]))
						$platform_has_full_permission = true;
				?>
				<input type="checkbox" id="platform-<?=$platform?>" class="platform_select_all" name="<?=$platform?>[]" data-platform="<?=$platform?>" 
					<?php 
						echo ($platform_has_full_permission)?"checked":"";
					?> 
					value="all" style="margin-top:2px;"
				>
				
				<!-- 平台是否有所有权限 end -->
				
				<!-- <span id="<?=$platform?>-dropdown-btn" class="glyphicon glyphicon-menu-down dropdown-controller" onclick="dropDownSwitch(this)" data-platform="<?=$platform?>" title="展开账号列表"></span>  -->
				<LABEL for="platform-<?=$platform?>"><?=(!empty($platform_source[$platform]))?$platform_source[$platform]:$platform ?></LABEL>
				<div id="<?=$platform?>_full_permission_tip" style="display:<?=$platform_has_full_permission?'inline-block':'none';?>"><span>(全权限)</span><span qtipkey="platform_has_full_permission"></span></div>
			</h5>
			<div id="<?=$platform?>-accont-list-div" class="account-dropdown-div" data-platform="<?=$platform?>" style="">
				<?php if(!empty($platformAccountList[$platform])){ 
					
					$permission_accounts = empty($permission['platforms'][$platform])?[]:$permission['platforms'][$platform];
					if( !empty($permission['platforms']) && (empty($permission_accounts) && in_array($platform,$permission['platforms'])) ){
						$permission_accounts = array_keys($platformAccountList[$platform]);
					}
					foreach ($platformAccountList[$platform] as $account_key=>$store_name){
						if(in_array($account_key,$permission_accounts) || $platform_has_full_permission)
							$account_permitted = true;
						else 
							$account_permitted = false;
				?>
				<div style="float:left;clear:both;display:inline-flex;margin-left:20px;">
					<input type="checkbox" name="<?=$platform?>[]" class="single_select" id="<?=$platform.'-'.$account_key?>"
						value="<?=$account_key?>" <?=($account_permitted)?"checked":''?> 
						onclick="checkPlactformHasSelect(this,'<?=$platform?>')" 
						style="float:left;"
					>
					<label for="<?=$platform.'-'.$account_key?>" style="float:left;"><?=$store_name?></label>
				</div>
				<?php } } ?>
			</div>
			
		</div>
	</td>
	

</tr>

<?php $index++;?>
<?php }?>
<?php if($index % $colspan != 0){?>
</tr>
<?php }?>






<tr style=""><td style="font-weight: bold;" colspan="<?=$colspan ?>">授权其他功能</td></tr>
<?php $index = 0;foreach ($others as $key=>$name):?>
<?php $key = strtolower($key);?>
<?php if($index == 0 || $index % $colspan == 0):?>
<tr style="">
<?php endif;?>
	<td><input id="others-<?=$key?>" type="checkbox" name="others[]" <?= (!empty($permission) && !empty($permission['others']) && in_array($key, $permission['others']))?"checked":""?> value="<?=$key?>" style="margin-top:2px;"> <LABEL for="others-<?=$key?>"><?= $name ?></LABEL></td>
	
<?php if($index % $colspan == ($colspan-1)):?>
</tr>
<?php endif;?>
<?php $index++;?>
<?php endforeach;?>
<?php if($index % $colspan != 0):?>
</tr>
<?php endif;?>


<script>
/*
$(function(){
	$("input[name='platforms[]']").change(function(){
		var checked = $(this).prop("checked");
		$("input[name='"+$(this).val()+"[]']").each(function(){
			if(checked)
				$(this).prop("checked","checked");
			else
				$(this).removeAttr("checked");
		});
	});
});
*/
function dropDownSwitch(obj){
	//debugger;
	var platform = $(obj).data("platform");
	if($(obj).hasClass("glyphicon-menu-down")){
		$(".account-dropdown-div").each(function(){
			$(this).hide();
			$("#"+$(this).data("platform")+"-dropdown-btn").removeClass("glyphicon-menu-up").addClass("glyphicon-menu-down");
		});
		$(obj).removeClass("glyphicon-menu-down");
		$(obj).addClass("glyphicon-menu-up");
		$("#"+platform+"-accont-list-div").show();
		$(obj).attr("title","收起账号列表");
	}else{
		$(obj).removeClass("glyphicon-menu-up");
		$(obj).addClass("glyphicon-menu-down");
		$("#"+platform+"-accont-list-div").hide();
		$(obj).attr("title","展开账号列表");
	}
}

function checkPlactformHasSelect(obj,platform){
	//debugger;
	if($(obj).prop("checked")!==true){
		$(".platform_select_all[data-platform='"+platform+"']").removeAttr("checked");
	}
	var has = $("input[name='"+platform+"[]']:checked").length;
	if(has > 0){
		$("input[name='platforms[]'][data-platform='"+platform+"']").prop("checked",true);
	}else{
		$("input[name='platforms[]'][data-platform='"+platform+"']").prop("checked",false);
	}
}
</script>