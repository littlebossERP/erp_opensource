<?php 
$resortCat =array();
if(isset($sortable)){
	$groupSortable = array();
	$index = 0;
	foreach ($sortable as $value){
		if("content_type_id" == $value['name'])
			$index++;
		$groupSortable[$index][$value['name']] = $value['value'];
	}
	foreach ($groupSortable as $catInfo){
		$resortCat[$catInfo['content_displayorder']] = $catInfo;
	}
}

// side bar info box title style
$titleStyle = "style='";
if(!empty($infodetclass['title_fontSize'])){
	$titleStyle .= "font-size: ".$infodetclass['title_fontSize'].";";
}
if(!empty($infodetclass['title_fontStyle'])){
	$titleStyle .= "font-family:".$infodetclass['title_fontStyle'].";";
}
if(!empty($infodetclass['title_fontColor'])){
	if(strripos($infodetclass['title_fontColor'],"#") === false )
		$infodetclass['title_fontColor'] = "#".$infodetclass['title_fontColor'];
	$titleStyle .= "color: ".$infodetclass['title_fontColor'].";";
}
if(!empty($infodetclass['title_bkgd_type'])){
	if($infodetclass['title_bkgd_type'] == 'Pattern'){
		$titleStyle .= "background-image: url('".$infodetclass['title_bkgd_pattern']."')";
	}else{
		if(strripos($infodetclass['title_bkgd_color'],"#") === false )
			$infodetclass['title_bkgd_color'] = "#".$infodetclass['title_bkgd_color'];
		$titleStyle .= "background-color: ".$infodetclass['title_bkgd_color'].";";
	}
	
}

if(!empty($infodetclass['cat_bkgd_type'])){
	if($infodetclass['cat_bkgd_type'] == 'Pattern'){
		$titleStyle .= "background-image: url('".$infodetclass['cat_bkgd_pattern']."')";
	}else{
		if(strripos($infodetclass['cat_bkgd_color'],"#") === false )
			$infodetclass['cat_bkgd_color'] = "#".$infodetclass['cat_bkgd_color'];
		$titleStyle .= "background-color: ".$infodetclass['cat_bkgd_color'].";";
	}
}

// side bar info box text style
$textStyle = "style='";
if(!empty($infodetclass['text_fontsize'])){
	$textStyle .= "font-size: ".$infodetclass['text_fontsize'].";";
}
if(!empty($infodetclass['text_fontstyle'])){
	$textStyle .= "font-family:".$infodetclass['text_fontstyle'].";";
}
if(!empty($infodetclass['text_fontcolor'])){
	if(strripos($infodetclass['text_fontcolor'],"#")  === false )
		$infodetclass['title_fontColor'] = "#".$infodetclass['text_fontcolor'];
	$textStyle .= "color: ".$infodetclass['text_fontcolor'].";";
}
if(!empty($infodetclass['text_overcolor'])){
	if(strripos($infodetclass['text_fontcolor'],"#")  === false )
		$infodetclass['title_fontColor'] = "#".$infodetclass['text_fontcolor'];
	// text_overcolor 没效果，大概是mouse over或者点击后的link的字体颜色
}
if(!empty($infodetclass['infobox_bkgd_type'])){
	if($infodetclass['infobox_bkgd_type'] == 'Pattern'){
		$textStyle .= "background-image: url('".$infodetclass['infobox_bkgd_pattern']."')";
	}else{
		if(strripos($infodetclass['infobox_bkgd_color'],"#") === false )
			$infodetclass['title_bkgd_color'] = "#".$infodetclass['infobox_bkgd_color'];
		$textStyle .= "background-color: ".$infodetclass['title_bkgd_color'].";";
	}
}

$textStyle = "'";

// $itemInfo : new , hot , etc
?>
<?php if(!empty($resortCat) || !empty($finalHtml)):?>
	<?php foreach ($resortCat as $catInfo):?>
		<div id='pre<?php echo $catInfo['content_displayorder'];?>' class='pbp'>
			<?php if(empty($finalHtml)):?>
			<?php echo $this->render('sideBarPartialHtml', array('allItem'=>$allItem,"catInfo"=>$catInfo , "itemInfo"=>$itemInfo,"newListItem"=>$newListItem));?>
			<?php else:?>
			<?php echo $this->renderFile($fileRoot.'sideBarPartialHtml'.$fileExt, array('allItem'=>$allItem,"catInfo"=>$catInfo , "itemInfo"=>$itemInfo,"newListItem"=>$newListItem));?>
			<?php endif;?>
		</div>
	<?php endforeach;?>
	
<?php endif;?>