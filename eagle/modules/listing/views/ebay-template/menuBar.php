
<?php 
if(empty($allItem)){
	$allItem = array();
	foreach ($_REQUEST['allItem'] as $value){
		$allItem[$value['name']] = $value['value'];
	} 
}
$menubarWidth = 0;
if(!empty($allItem['tb_shop_master_Setting_menu_DT'])){
	$menubarWidth += 152 + 1;
}
if(!empty($allItem['tb_shop_master_Setting_menu_PM'])){
	$menubarWidth += 152 + 1;
}
if(!empty($allItem['tb_shop_master_Setting_menu_SP'])){
	$menubarWidth += 152 + 1;
}
if(!empty($allItem['tb_shop_master_Setting_menu_RP'])){
	$menubarWidth += 152 + 1;
}
 if(!empty($allItem['tb_shop_master_Setting_menu_CU'])){
 	$menubarWidth += 152 + 1;
 }

$menuFontStyle = "float: left; width: 152px; position: relative;";
if(!empty($allItem['tb_eb_CFI_style_master_menu_font_size'])){
	$menuFontStyle .= "font-size: ".$allItem['tb_eb_CFI_style_master_menu_font_size']."px;";
}
if(!empty($allItem['eb_tp_font_style_menurow'])){
	$menuFontStyle .= "font-family:".$allItem['eb_tp_font_style_menurow'].";";
}
$menuRightBorderColor = "#ffffff";
if(!empty($allItem['eb_tp_clr_Font_Color'])){
	if(stripos($allItem['eb_tp_clr_Font_Color'] , '#') === false )
		$allItem['eb_tp_clr_Font_Color'] = '#'.$allItem['eb_tp_clr_Font_Color'];
	
	$menuFontStyle .= "color:".$allItem['eb_tp_clr_Font_Color'].";";
	$menuRightBorderColor = $allItem['eb_tp_clr_Font_Color'];
}

$menuRightBorderStyle = "";
if(isset($allItem['tb_eb_CFI_style_master_menu_separator']) && "yes" == strtolower($allItem['tb_eb_CFI_style_master_menu_separator']) ){
	$menuRightBorderStyle .= "border-right-color: ".$menuRightBorderColor.";";
	$menuRightBorderStyle .= "border-right-width: 1px;";
	$menuRightBorderStyle .= "border-right-style: solid;";
}else{
	$menuRightBorderStyle .= "border-right-color: ".$menuRightBorderColor.";";
	$menuRightBorderStyle .= "border-right-width: 0px;";
	$menuRightBorderStyle .= "border-right-style: solid;";
}

?>
<div id="menubar" style="width: <?php echo $menubarWidth;?>px; height: 25px;">

<?php if(!empty($allItem['tb_shop_master_Setting_menu_DT'])):?>
<div class="menurow menuright"  style="<?php echo $menuRightBorderStyle;?>"; >
<a href="<?php echo !empty($allItem['tb_shop_master_Setting_menu_DT_t'])?$allItem['tb_shop_master_Setting_menu_DT_t']:"#"; ?>"
	style="<?php echo $menuFontStyle;?>text-decoration: none;">
<?php echo $allItem['tb_shop_master_Setting_menu_DT'];?> </a></div>
<?php endif;?>

<?php if(!empty($allItem['tb_shop_master_Setting_menu_PM'])):?>
<div class="menurow menuright"
	style="<?php echo $menuRightBorderStyle;?>">
<a href="<?php echo !empty($allItem['tb_shop_master_Setting_menu_PM_t'])?$allItem['tb_shop_master_Setting_menu_PM_t']:"#"; ?>"
	style="<?php echo $menuFontStyle;?>text-decoration: none;">
<?php echo $allItem['tb_shop_master_Setting_menu_PM'];?> </a></div>
<?php endif;?>

<?php if(!empty($allItem['tb_shop_master_Setting_menu_SP'])):?>
<div class="menurow menuright"
	style="<?php echo $menuRightBorderStyle;?>">
<a href="<?php echo !empty($allItem['tb_shop_master_Setting_menu_SP_t'])?$allItem['tb_shop_master_Setting_menu_SP_t']:"#"; ?>"
	style="<?php echo $menuFontStyle;?>text-decoration: none;">
<?php echo $allItem['tb_shop_master_Setting_menu_SP'];?> </a></div>
<?php endif;?>

<?php if(!empty($allItem['tb_shop_master_Setting_menu_RP'])):?>
<div class="menurow menuright" style="<?php echo $menuRightBorderStyle;?>">
<a href="<?php echo !empty($allItem['tb_shop_master_Setting_menu_RP_t'])?$allItem['tb_shop_master_Setting_menu_RP_t']:"#"; ?>"
	style="<?php echo $menuFontStyle;?>text-decoration: none;">
<?php echo $allItem['tb_shop_master_Setting_menu_RP'];?> </a></div>
<?php endif;?>

<?php if(!empty($allItem['tb_shop_master_Setting_menu_CU'])):?>
<div class="menurow" >
<a href="<?php echo !empty($allItem['tb_shop_master_Setting_menu_CU_t'])?$allItem['tb_shop_master_Setting_menu_CU_t']:"#"; ?>"
	style="<?php echo $menuFontStyle;?>text-decoration: none;">
<?php echo $allItem['tb_shop_master_Setting_menu_CU'];?> </a></div>
<?php endif;?>

</div>
