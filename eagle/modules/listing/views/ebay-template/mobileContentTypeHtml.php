
<?php if($contentType == "shop_name_banner"):?>

<?php 
$radio = 430 / 1080;

$shopNameStyle = "style='";
if(!empty($allItem['shop_name_text_size'])){
	$shopNameStyle .= "font-size: ". ($allItem['shop_name_text_size'] * $radio) ."px;";
}
if(!empty($allItem['shop_name_text_style'])){
	$shopNameStyle .= "font-family:".$allItem['shop_name_text_style'].";";
}
if(!empty($allItem['shop_name_text_color'])){
	if(stripos($allItem['shop_name_text_color'] , '#') === false )
		$allItem['shop_name_text_color'] = '#'.$allItem['shop_name_text_color'];
	
	$shopNameStyle .= "color:".$allItem['shop_name_text_color'].";";
}
if(!empty($allItem['shop_name_text_left'])){
	$shopNameStyle .= "left:".($allItem['shop_name_text_left'] * $radio) ."px;";
}
if(!empty($allItem['shop_name_text_top'])){
	$shopNameStyle .= "top:".($allItem['shop_name_text_top'] * $radio) ."px;";
}
$shopNameStyle .= "'";
?>
<div class='mpicbanner' >
<div class="overf" style="position: relative;">
	<center>
	
	<p class="shopnameaddon" <?php echo $shopNameStyle;?> >
		<?php 
		if(!empty($allItem['shop_name_text'])){
			echo $allItem['shop_name_text'];
		}
		?>
	</p>				
	<img width='100%' src="<?php echo $allItem['graphic_setting_Shop_Name_Banner']; ?>" style="display: inline-block;">
	<div width="100%" style="display: none; background-image: url(/images/ebay/template/webbar5.png); height: 10px;"></div>
	</center>
</div>
</div>	
<?php elseif ($contentType == "product_photo"):?>

<div id='rmpic' <?php if(empty($itemInfo['imagesUrlArr'])) echo 'style="display: none;"';?>>
<img id='rmpic_r' src='/images/ebay/template/arrow-right.png'>
<img id='rmpic_l' src='/images/ebay/template/arrow-left.png'>
<div class='mpicbox'>
<?php if(!empty($itemInfo['imagesUrlArr'])):?>
<table>
<tr>
<?php foreach ($itemInfo['imagesUrlArr'] as $index=>$prodLink):?>
<td>
<a id='mbp<?php echo $index;?>' href='<?php echo $prodLink;?>' >
<img border='0' onload="resizeImg(this);" src='<?php echo $prodLink;?>' class='img_border' name='mainimage'>
</a>
</td>
<?php endforeach;?>
</tr>
</table>
<?php endif;?>
</div>
</div>
<script type="text/javascript">
<?php 
if(isset($itemInfo) && !empty($itemInfo['imagesUrlArr'])){
	foreach ($itemInfo['imagesUrlArr'] as $index=>$prodLink){
		echo "popup('mbp".$index."');";
	}
}
?>
</script>

<?php elseif ($contentType == "description"):?>
<?php  
// escription font style 
$descDetailsStyle = "style='";
if(!empty($allItem['eb_tp_clr_Description_details'])){
	if(stripos($allItem['eb_tp_clr_Description_details'] , '#') === false )
		$allItem['eb_tp_clr_Description_details'] = '#'.$allItem['eb_tp_clr_Description_details'];
	$descDetailsStyle .= "color: ".$allItem['eb_tp_clr_Description_details'].";";
}else{
	$descDetailsStyle .= "color: #ffffff;";
}
if(!empty($allItem['eb_tp_font_Description'])){
	$descDetailsStyle .= "font-family: ".$allItem['eb_tp_font_Description'].";";
}else{
	$descDetailsStyle .= "font-family: Arial;";
}
if(!empty($allItem['tb_eb_CFI_style_master_desc_fontSize'])){
	$descDetailsStyle .= "font-size: ".$allItem['tb_eb_CFI_style_master_desc_fontSize']."px;";
}else{
	$descDetailsStyle .= "font-size: 12px;";
}
if(!empty($allItem['tb_eb_CFI_style_master_desc_details'])){
	$descDetailsStyle .= "text-align: ".$allItem['tb_eb_CFI_style_master_desc_details'];
}else{
	$descDetailsStyle .= "text-align: left;";
}	
$descDetailsStyle .= "'";


?>
<div class='desc_word' <?php echo $descDetailsStyle;?>>
<?php if(!empty($itemInfo['description'])):?>
<?php echo $itemInfo['description'];?>	
<?php endif;?>
</div>

<?php elseif ($contentType == "item_Specifics"):?>
<table id="itemid4" class="ItemSpecificstableALL desc_details"  style='width: 100%;display: none;'><tbody>
<tr>
<th colspan="2" style='border-top-left-radius: 10px;border-top-right-radius: 10px;'><strong>Item Specifics</strong></th>
</tr>
<tr><td style="">Contract </a></td><td style="">Without Contract</td></tr>
<tr><td style="">Brand </a></td><td style="">BlackBerry</td></tr>
<tr><td style="">Model </td><td style="">BlackBerry Q10</td></tr>
<tr><td style="">Carrier </td><td style="">Unlocked</td></tr>
<tr><td style="">Storage Capacity </td><td style="">16 GB</td></tr>
<tr><td style="">Color </td><td style="">White</td></tr>
<tr><td style="">Camera </td><td style="">8.0 MP</td></tr>
<tr><td style="">Operating System </td><td style="">BlackBerry 10</td></tr>
<tr><td style="">MPN </td><td style="">ebay_BlacberryQ10WhiteUnlocked</td></tr>
</tbody>
</table>
<?php elseif ($contentType == "poster"):?>
<div class='' style="display: none;">

<p id='mp1'>
<img border='0' width="430"  src='<?php echo Yii::app ()->baseUrl ;?>/images/ebay/template/Poster_Sample1.jpg'  name='mainimage'>
</p>
<br><br>
<p id='mp2'  >
<img border='0' width="430"  src='<?php echo Yii::app ()->baseUrl ;?>/images/ebay/template/Poster_Sample2.jpg' name='mainimage'>
</p>
<br>

</div>
<style>
.mobpovh{background-color:#b2b2b2}
</style>

<?php elseif ($contentType == "policy"):?>
<?php 
// policy tab font , background 
$tabHeaderiClass = "";
$tabActiveHeaderClass = "";
if(!empty($allItem['eb_tp_tab_Header_selected'])){
	if(stripos($allItem['eb_tp_tab_Header_selected'] , '#') === false )
		$allItem['eb_tp_tab_Header_selected'] = '#'.$allItem['eb_tp_tab_Header_selected'];
	$tabActiveHeaderClass .= "background-color: ".$allItem['eb_tp_tab_Header_selected'].";";
}else{
	$tabActiveHeaderClass .= "background-color: #7f7f7f;";
}


if(!empty($allItem['eb_tp_tab_Header_color'])){
	if(stripos($allItem['eb_tp_tab_Header_color'] , '#') === false )
		$allItem['eb_tp_tab_Header_color'] = '#'.$allItem['eb_tp_tab_Header_color'];
	$tabHeaderiClass .= "background-color: ".$allItem['eb_tp_tab_Header_color'].";";
}else{
	$tabHeaderiClass .= "background-color: #b2b2b2;";
}

if(!empty($allItem['eb_tp_tab_Header_font'])){
	if(stripos($allItem['eb_tp_tab_Header_font'] , '#') === false )
		$allItem['eb_tp_tab_Header_font'] = '#'.$allItem['eb_tp_tab_Header_font'];
	$tabActiveHeaderClass .= "color: ".$allItem['eb_tp_tab_Header_font'].";";
	$tabHeaderiClass .= "color: ".$allItem['eb_tp_tab_Header_font'].";";
}else{
	$tabActiveHeaderClass .= "color: #ffffff;";
	$tabHeaderiClass .= "color: #ffffff;";
}
if(!empty($allItem['eb_tp_tab_Font_style'])){
	$tabActiveHeaderClass .= "font-family: ".$allItem['eb_tp_tab_Font_style'].";";
	$tabHeaderiClass .= "font-family: ".$allItem['eb_tp_tab_Font_style'].";";
}else{
	$tabActiveHeaderClass .= "font-family: Arial;";
	$tabHeaderiClass .= "font-family: Arial;";
}
if(!empty($allItem['eb_tp_tab_Font_size'])){
	$tabActiveHeaderClass .= "font-size: ".$allItem['eb_tp_tab_Font_size']."px;";
	$tabHeaderiClass .= "font-size: ".$allItem['eb_tp_tab_Font_size']."px;";
}else{
	$tabActiveHeaderClass .= "font-size: 12px;";
	$tabHeaderiClass .= "font-size: 12px;";
}

$tabHeaderiClass .= "cursor: pointer;";
$tabActiveHeaderClass .= "cursor: pointer;";


// policy background : no image
$policyBackStyle = "style='";

if(!empty($allItem['tb_eb_CFI_style_policy_BP'])){
	if($allItem['tb_eb_CFI_style_policy_BP'] == 'Pattern'){
//		$policyBackStyle .= "background-image: url('".$allItem['eb_tp_policy_Pattern']."')";
	}else{
		if(!strripos($allItem['eb_tp_clr_infobox_background'],"#"))
			$allItem['eb_tp_clr_infobox_background'] = "#".$allItem['eb_tp_clr_infobox_background'];
		$policyBackStyle .= "background-color: ".$allItem['eb_tp_clr_infobox_background'].";";
	}
	
}
$policyBackStyle .= "'";

?>
<style>
.mobpovh{<?php echo $tabHeaderiClass;?>}
.mobpovah{
	<?php echo $tabActiveHeaderClass;?>
	border-top-left-radius: 15px;
	padding: 10px;
	margin-top: 5px;
}
</style>
<?php $policyNum = 0;?>
<?php for( $i = 1; $i <= 5; $i++):?>
	<?php if(!empty($allItem['sh_ch_info_Policy'.$i.'_header']) && !empty($allItem['sh_ch_info_Policy'.$i])):?>
	<?php $policyNum++;?>
	<div class='mobpovh' onclick="showme('cp<?php echo $policyNum;?>',this);">
		<?php echo $allItem['sh_ch_info_Policy'.$i.'_header'];?>
	</div>
	<div id='cp<?php echo $policyNum;?>' class='mobpovinfo cathide' <?php echo $policyBackStyle;?>>
		<?php echo $allItem['sh_ch_info_Policy'.$i];?>
	</div>
	<?php endif;?>
<?php endfor;?>


<?php endif;?>


