<?php if(empty($_REQUEST['template_id']) || !isset($_REQUEST['allItem'])):?>

<div class="margin">
<div id="policy_bot_text" name="policy_bot_text" class="editor">
<p><span style="color: rgb(128, 128, 128)">[ Policy bottom: This is
the contents of your policies.&nbsp;You can add a banner for this
policy as header and add text descriptions here. The text
descriptions can be different Font Size, Font Color, Style and even
graphics and icons are also accepted. ]</span></p>
<p>&nbsp;</p>
<p>&nbsp;</p>

</div>
</div>
<?php else: ?>
<div class="margin">
<div id="policy_bot_text" name="policy_bot_text" class="editor">
<?php 
	$allItem = array();
	foreach ($_REQUEST['allItem'] as $value){
		$allItem[$value['name']] = $value['value'];
	} 
?>
 <?php echo $allItem['sh_ch_info_Policybot'];?>
</div>
</div>
<?php endif;?>