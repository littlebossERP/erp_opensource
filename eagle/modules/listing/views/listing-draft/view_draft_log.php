<style type="text/css">
.view-draft-log{
	min-width:300px;
	padding:10px;
	text-align: left;
}
.view-draft-log li{
	line-height: 2;
}
.view-draft-log span{
	margin-right:10px;
	background-color: #ededed;
	padding:5px;
}

</style>
<ul class="view-draft-log">
<?php

$platformName = [
	'ebay'=>'eBay',
	'aliexpress'=>'速卖通',
	'wish'=>'Wish'
];

$hasOne = false;
foreach($data as $platform=>$item):
	if($item):
		$hasOne = true;

?>
<li>
	<label><?= $platformName[$platform] ?>: </label>
	<?php foreach($item as $shopName):
	echo "<span>{$shopName}</span>";
	endforeach; ?>
</li>
<?php
	endif;
endforeach;

if(!$hasOne){
	echo '<li>无</li>';
}
?>
</ul>