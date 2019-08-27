<?php if(empty($_REQUEST['allItem'])):?>
<div id="tabs">
<ul>
	<li id="tabHeader_1" class="tabHeaderi">
	<p id="tabHeader1">Policy1 header</p>
	</li>
	<li id="tabHeader_2" class="tabHeaderi">
	<p id="tabHeader2">Policy2 header</p>
	</li>
	<li id="tabHeader_3" class="tabHeaderi">
	<p id="tabHeader3">Policy3 header</p>
	</li>
	<li id="tabHeader_4" class="tabHeaderi">
	<p id="tabHeader4">Policy4 header</p>
	</li>
	<li id="tabHeader_5" class="tabHeaderi">
	<p id="tabHeader5">Policy5 header</p>
	</li>
</ul>
</div>
<div id="tabscontent">
<div class="tabpage" id="tabpage_1">
<div id="policy_box1_text" class="ckinline editor">
<p><span style="color: #808080">[ Policy 1: This is the contents of
your policies.&nbsp;You can add a banner for this policy as header
and add text descriptions here. The text descriptions can be
different Font Size, Font Color, Style and even graphics and icons
are also accepted. ]</span></p>
<hr>
<p>&nbsp;</p>
</div>
</div>
<div class="tabpage" id="tabpage_2">
<div id="policy_box2_text" class="ckinline editor">
<p><span style="color: rgb(128, 128, 128)">[ Policy 2: This is the
contents of your policies.&nbsp;You can add a banner for this policy
as header and add text descriptions here. The text descriptions can
be different Font Size, Font Color, Style and even graphics and icons
are also accepted. ]</span></p>
<hr>
<p>&nbsp;</p>

</div>
</div>
<div class="tabpage" id="tabpage_3">
<div id="policy_box3_text" class="ckinline editor">
<p><span style="color: rgb(128, 128, 128)">[ Policy 3: This is the
contents of your policies.&nbsp;You can add a banner for this policy
as header and add text descriptions here. The text descriptions can
be different Font Size, Font Color, Style and even graphics and icons
are also accepted. ]</span></p>
<hr>
<p>&nbsp;</p>

</div>
</div>
<div class="tabpage" id="tabpage_4">
<div id="policy_box4_text" class="ckinline editor">
<p><span style="color: rgb(128, 128, 128)">[ Policy 4: This is the
contents of your policies.&nbsp;You can add a banner for this policy
as header and add text descriptions here. The text descriptions can
be different Font Size, Font Color, Style and even graphics and icons
are also accepted. ]</span></p>
<hr>
<p>&nbsp;</p>

</div>
</div>
<div class="tabpage" id="tabpage_5">
<div id="policy_box5_text" class="ckinline editor">
<p><span style="color: rgb(128, 128, 128)">[ Policy 5: This is the
contents of your policies.&nbsp;You can add a banner for this policy
as header and add text descriptions here. The text descriptions can
be different Font Size, Font Color, Style and even graphics and icons
are also accepted. ]</span></p>
<hr>
<p>&nbsp;</p>

</div>
</div>
</div>


<?php else:?>
<?php 
	$allItem = array();
	foreach ($_REQUEST['allItem'] as $value){
		$allItem[$value['name']] = $value['value'];
	}
?>
<div id="tabs">
<ul>
<?php for( $i = 1; $i <= 5; $i++):?>
	<li id="tabHeader_<?php echo $i;?>" class="tabHeaderi">
	<p id="tabHeader<?php echo $i;?>">
		<?php echo $allItem['sh_ch_info_Policy'.$i.'_header'];?>
	</p>
	</li>
<?php endfor;?>
</ul>
</div>
<div id="tabscontent">

<?php for( $i = 1; $i <= 5; $i++):?>

<div class="tabpage" id="tabpage_<?php echo $i;?>">
	<div id="policy_box<?php echo $i;?>_text" class="ckinline editor">
		<?php echo isset($allItem['sh_ch_info_Policy'.$i])?$allItem['sh_ch_info_Policy'.$i]:"";?>
	</div>
</div>

<?php endfor;?>

<?php endif;?>