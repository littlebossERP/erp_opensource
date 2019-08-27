<?php 
error_reporting(E_ALL^E_NOTICE);
$m_content_type = array(
							"Shop Name banner"=>"shop_name_banner",
							"Hori. Product Photo"=>"product_photo",
							"Description"=>"description",
							"Item Specifics"=>"item_Specifics",
							"Poster"=>"poster",
							"Policy"=>"policy"
						);

$msortableMap = array();
if(!empty($_REQUEST['msortable'])){
	$index = 0;
	foreach ($_REQUEST['msortable'] as $value){
		if($value['name'] == 'm_content_type_id')
			$index++;
		$msortableMap[$index][$value['name']] = $value['value'];
	} 
	$m_content_sort = array();
	foreach ($msortableMap as $value){
		$m_content_sort[$value['m_content_displayorder']] = $m_content_type[$value['m_content_type_id']];
	}
}

if(isset($_REQUEST['allItem'])){
	$allItem = array();
	foreach ($_REQUEST['allItem'] as $value){
		$allItem[$value['name']] = $value['value'];
	} 
}
?>
<?php if(!empty($m_content_sort)):?>
<?php foreach ($m_content_sort as $index=>$contentType):?>
<div id='m_pre<?php echo $index?>' class='mbp'>
	<?php echo $this->render('mobileContentTypeHtml', array("showDemo"=>$showDemo,"allItem"=>$allItem,"contentType"=>$contentType,"itemInfo"=>$itemInfo)); ?>
</div>
<?php endforeach;?>
<?php endif;?>		

