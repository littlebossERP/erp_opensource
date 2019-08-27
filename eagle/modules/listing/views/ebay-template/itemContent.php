<?php 
error_reporting(E_ALL^E_NOTICE);
$d_content_type = array(
							"Basic Descriptions"=>"basic_descriptions",
							"Item Specifics"=>"item_specifics",
							"Action button"=>"action_button",
							"EagleGallery"=>"eagleGallery",
						);
$productImgs = array(
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",	
	\Yii::getAlias('@web') ."/images/ebay/template/product_photo_1.jpg",
);						
				
if(isset($_REQUEST['dsortable'])){
	$dsortable = array();
	$index = 0;
	foreach ($_REQUEST['dsortable'] as $value){
		$dsortable[floor($index/8)][$value['name']] = $value['value'];
		$index++;
	} 
	$reSortDContent = array();
	$contentTypeDContentMap = array();
	foreach ($dsortable as $dContent){
		$reSortDContent[$dContent['d_content_displayorder']] = $dContent;
		$contentTypeDContentMap[$d_content_type[$dContent['d_content_type_id']]][$dContent['d_content_displayorder']] = $dContent;
	}
	$displayDsortable = array();
	foreach ($reSortDContent as $dContent){
		$displayDsortable[strtolower($dContent['d_content_pos'])][$dContent['d_content_displayorder']] = $d_content_type[$dContent['d_content_type_id']];
	}
	
}
if(isset($_REQUEST['pos'])){
	if(isset($displayDsortable[strtolower($_REQUEST['pos'])])){
		foreach ($displayDsortable[strtolower($_REQUEST['pos'])] as $order=>$d_content_type){
			echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type][$order]));
		}
	}
	return ;
}
?>


<div id="desc_html">
<div id="above_product_Photo" class="desc_word ">
	<?php 
		if(isset($displayDsortable) && isset($displayDsortable['above_product_photo'])){
			foreach ($displayDsortable['above_product_photo'] as $d_content_type){
				echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
			}
		}
		// else{
		// 	$this->render('itemPartialHtml', array("initDemo"=>true,"contentType"=>"item_specifics"));
		// }
	?>
</div>
<div class="subtitle"><?php echo $itemInfo['title'];?></div>
<div id="desc" name="desc" >
	<?php if(isset($productType)){ $data =  $productType;}else{$data = 'product_layout_left';} ?>
	<div name="productType" id="product_layout_left" class="shutdown <?php if($data == 'product_layout_left'){echo "active";}?>"  >
		<div>
		<div>
			<div id="Bwidht" class="product_photo_need_hide " style="width: 410px;">
				<?php if(empty($productImgs)):?>
					<div id="big_pic">
						<div class="bigimage">
							<div id="gallery"><a href="<?php echo \Yii::getAlias('@web');?>/images/ebay/template/product_photo_1.jpg"
												 id="linkId"> <img alt="" border="0" id="sample_Bigimg"
																   width="400PX"
																   src="<?php echo \Yii::getAlias('@web'); ?>/images/ebay/template/product_photo_1.jpg"
																   class="img_border" name="mainimage">
									<div id="Zoom-Icon"></div>
								</a></div>
						</div>
					</div>
					<div id="smail_pic_box" style="float: left;font-size: 0;">
						<div class="smail_pic smail_pic0"><a class="mousedown" id="MD0"
															 onmousedown="changeImages('<?php
															 echo \Yii::getAlias('@web');
															 ?>/images/ebay/template/product_photo_1.jpg')">
								<img alt="" class="sm0" width="70px"
									 src="<?php
									 echo \Yii::getAlias('@web');
									 ?>/images/ebay/template/product_photo_1.jpg">
							</a></div>
						<div class="smail_pic smail_pic1"><a class="mousedown" id="MD1"
															 onmousedown="changeImages('<?php
															 echo \Yii::getAlias('@web');
															 ?>/images/ebay/template/product_photo_1.jpg')">
								<img alt="" class="sm1" width="70px"
									 src="<?php
									 echo \Yii::getAlias('@web');
									 ?>/images/ebay/template/product_photo_1.jpg">
							</a></div>
						<div class="smail_pic smail_pic2"><a class="mousedown" id="MD2"
															 onmousedown="changeImages('<?php
															 echo \Yii::getAlias('@web');
															 ?>/images/ebay/template/product_photo_1.jpg')">
								<img alt="" class="sm2" width="70px"
									 src="<?php
									 echo \Yii::getAlias('@web');
									 ?>/images/ebay/template/product_photo_1.jpg">
							</a></div>
						<div class="smail_pic smail_pic3"><a class="mousedown" id="MD3"
															 onmousedown="changeImages('<?php
															 echo \Yii::getAlias('@web');
															 ?>/images/ebay/template/product_photo_1.jpg')">
								<img alt="" class="sm3" width="70px"
									 src="<?php
									 echo \Yii::getAlias('@web');
									 ?>/images/ebay/template/product_photo_1.jpg">
							</a></div>
						<div class="smail_pic smail_pic4"><a class="mousedown" id="MD4"
															 onmousedown="changeImages('<?php
															 echo \Yii::getAlias('@web');
															 ?>/images/ebay/template/product_photo_1.jpg')">
								<img alt="" class="sm4" width="70px"
									 src="<?php
									 echo \Yii::getAlias('@web');
									 ?>/images/ebay/template/product_photo_1.jpg">
							</a></div>
					</div>
				<?php else :?>

					<div id="big_pic">
						<div class="bigimage">
							<div id="gallery"><a href="<?php echo $productImgs[0]; ?>" id="linkId">
									<img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$productImgs[0]; ?>" class="img_border" name="mainimage">
									<div id="Zoom-Icon"></div>
								</a></div>
						</div>
					</div>
					<div id="smail_pic_box" style="float: left;">
						<?php foreach ($productImgs as $index=>$source):?>

							<div class="smail_pic smail_pic<?php echo $index;?>"><a class="mousedown" id="MD<?php echo $index;?>"
																					onmousedown="changeImages('<?php echo $source;?>')">
									<img alt="" class="sm<?php echo $index;?>" width="70px" src="<?php echo $source;?>">
								</a>
							</div>
						<?php endforeach;?>
					</div>
				<?php endif;?>
			</div>
			<td style="vertical-align: top;">
				<div class="desc_word" id="h_desc">
					<div id="Next_to_Product_Photo" class="desc_word "><br>
						<?php
						if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
							foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
								echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
							}
						}else {
							echo $this->render('itemPartialHtml', array("showDemo"=>$showDemo,"itemInfo"=>$itemInfo,"contentType"=>"basic_descriptions"));
						}
						?>
					</div>
				</div>
			</td>
		</div>
		</div>
	</div>
	<div name="productType" id="product_layout_right" class="product_layout_right shutdown <?php if($data == 'product_layout_right'){echo "active";}?>" >
	<div>
		<div>
		<div id="Bwidht" class="product_photo_need_hide clearfix">
		<?php if(empty($productImgs)):?>
			<div id="big_pic">
			<div class="bigimage">
			<div id="gallery"><a href="<?php echo \Yii::getAlias('@web');?>/images/ebay/template/product_photo_1.jpg"
			id="linkId"> <img alt="" border="0" id="sample_Bigimg"
			width="400PX"
			src="<?php echo \Yii::getAlias('@web'); ?>/images/ebay/template/product_photo_1.jpg"
			class="img_border" name="mainimage">
		<div id="Zoom-Icon"></div>
		</a></div>
		</div>
		</div>
		<div id="smail_pic_box" style="float: left;font-size: 0;">
		<div class="smail_pic smail_pic0"><a class="mousedown" id="MD0"
			onmousedown="changeImages('<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg')">
		<img alt="" class="sm0" width="70px"
			src="<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg">
		</a></div>
		<div class="smail_pic smail_pic1"><a class="mousedown" id="MD1"
			onmousedown="changeImages('<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg')">
		<img alt="" class="sm1" width="70px"
			src="<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg">
		</a></div>
		<div class="smail_pic smail_pic2"><a class="mousedown" id="MD2"
			onmousedown="changeImages('<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg')">
		<img alt="" class="sm2" width="70px"
			src="<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg">
		</a></div>
		<div class="smail_pic smail_pic3"><a class="mousedown" id="MD3"
			onmousedown="changeImages('<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg')">
		<img alt="" class="sm3" width="70px"
			src="<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg">
		</a></div>
		<div class="smail_pic smail_pic4"><a class="mousedown" id="MD4"
			onmousedown="changeImages('<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg')">
		<img alt="" class="sm4" width="70px"
			src="<?php
			echo \Yii::getAlias('@web');
			?>/images/ebay/template/product_photo_1.jpg">
		</a></div>
		</div>
		<?php else :?>
			
			<div id="big_pic">
				<div class="bigimage">
				<div id="gallery"><a href="<?php echo $productImgs[0]; ?>" id="linkId">
				 <img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$productImgs[0]; ?>" class="img_border" name="mainimage">
			<div id="Zoom-Icon"></div>
			</a></div>
			</div>
			</div>
			<div id="smail_pic_box" >
			<?php foreach ($productImgs as $index=>$source):?>
			
			<div class="smail_pic smail_pic<?php echo $index;?>"><a class="mousedown" id="MD<?php echo $index;?>"
				onmousedown="changeImages('<?php echo $source;?>')">
			<img alt="" class="sm<?php echo $index;?>" width="70px" src="<?php echo $source;?>">
			</a>
			</div>
			<?php endforeach;?>
			</div>
		<?php endif;?>
		</div>
		<div style="vertical-align: top;">

		</div>
		</div>
		<div class="desc_word" id="h_desc" >
			<div id="Next_to_Product_Photo" class="desc_word product_layout_right <?php if($data == 'product_layout_right'){echo "active";}?>" ><br>
				<?php
				if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
					foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
						echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
					}
				}else {
					echo $this->render('itemPartialHtml', array("showDemo"=>$showDemo,"itemInfo"=>$itemInfo,"contentType"=>"basic_descriptions"));
				}
				?>
			</div>
		</div>
	</div>
</div>
	<div name="productType" id="product_layout_center" class="product_layout_center shutdown <?php if($data == 'product_layout_center'){echo "active";}?>" >
	<div id="Bwidht" class="product_photo_need_hide clearfix">
		<?php if(empty($productImgs)):?>
			<div id="big_pic">
				<div class="bigimage">
					<div id="gallery"><div> <img alt="" border="0" id="sample_Bigimg"
														   width="400PX"
														   src="<?php echo \Yii::getAlias('@web'); ?>/images/ebay/template/product_photo_1.jpg"
														   class="img_border" name="mainimage">
							</div></div>
				</div>
			</div>
			<div id="smail_pic_box" style="float: left;font-size: 0;">
				<div class="smail_pic smail_pic0"><a class="mousedown" id="MD0"
													 onmousedown="changeImages('<?php
													 echo \Yii::getAlias('@web');
													 ?>/images/ebay/template/product_photo_1.jpg')">
						<img alt="" class="sm0" width="70px"
							 src="<?php
							 echo \Yii::getAlias('@web');
							 ?>/images/ebay/template/product_photo_1.jpg">
					</a></div>
				<div class="smail_pic smail_pic1"><a class="mousedown" id="MD1"
													 onmousedown="changeImages('<?php
													 echo \Yii::getAlias('@web');
													 ?>/images/ebay/template/product_photo_1.jpg')">
						<img alt="" class="sm1" width="70px"
							 src="<?php
							 echo \Yii::getAlias('@web');
							 ?>/images/ebay/template/product_photo_1.jpg">
					</a></div>
				<div class="smail_pic smail_pic2"><a class="mousedown" id="MD2"
													 onmousedown="changeImages('<?php
													 echo \Yii::getAlias('@web');
													 ?>/images/ebay/template/product_photo_1.jpg')">
						<img alt="" class="sm2" width="70px"
							 src="<?php
							 echo \Yii::getAlias('@web');
							 ?>/images/ebay/template/product_photo_1.jpg">
					</a></div>
				<div class="smail_pic smail_pic3"><a class="mousedown" id="MD3"
													 onmousedown="changeImages('<?php
													 echo \Yii::getAlias('@web');
													 ?>/images/ebay/template/product_photo_1.jpg')">
						<img alt="" class="sm3" width="70px"
							 src="<?php
							 echo \Yii::getAlias('@web');
							 ?>/images/ebay/template/product_photo_1.jpg">
					</a></div>
				<div class="smail_pic smail_pic4"><a class="mousedown" id="MD4"
													 onmousedown="changeImages('<?php
													 echo \Yii::getAlias('@web');
													 ?>/images/ebay/template/product_photo_1.jpg')">
						<img alt="" class="sm4" width="70px"
							 src="<?php
							 echo \Yii::getAlias('@web');
							 ?>/images/ebay/template/product_photo_1.jpg">
					</a></div>
			</div>
		<?php else :?>

			<div id="big_pic">
				<div class="bigimage">
					<div id="gallery">
							<img alt="" border="0" id="sample_Bigimg" width="400PX" src="<?php echo$productImgs[0]; ?>" class="img_border" name="mainimage">
					</div>
				</div>
			</div>

			<div id="smail_pic_box" style="float: left;">
						<?php foreach ($productImgs as $index=>$source):?>

							<div class="smail_pic smail_pic<?php echo $index;?>"><a class="mousedown" id="MD<?php echo $index;?>"
																					onmousedown="changeImages('<?php echo $source;?>')">
									<img alt="" class="sm<?php echo $index;?>" width="70px" src="<?php echo $source;?>">
								</a>
							</div>
						<?php endforeach;?>
					</div>

		<?php endif;?>
	</div>
	<div class="desc_word" id="h_desc" >
		<div id="Next_to_Product_Photo" class="desc_word product_layout_right <?php if($data == 'product_layout_center'){echo "active";}?>" ><br>
			<?php
			if(isset($displayDsortable) && isset($displayDsortable['next_to_product_photo'])){
				foreach ($displayDsortable['next_to_product_photo'] as $d_content_type){
					echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
				}
			}else {
				echo $this->render('itemPartialHtml', array("showDemo"=>$showDemo,"itemInfo"=>$itemInfo,"contentType"=>"basic_descriptions"));
			}
			?>
		</div>
	</div>
	<div >
		<?php if(empty($productImgs)):?>
			<?php foreach ($productImgs as $index=>$source):?>
				<div class="bigimage" style="padding-top: 10px;">
						<img alt="" class="sm<?php echo $index;?>" width="400px" src="<?php echo $source;?>">
				</div>
			<?php endforeach;?>
		<?php endif;?>
	</div>
</div>
	<div id="Below_All_Product_Photo" class="desc_word ">
		<?php
		if(isset($displayDsortable) && isset($displayDsortable['below_all_product_photo'])){
			foreach ($displayDsortable['below_all_product_photo'] as $d_content_type){
				echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
			}
		}else{
			echo $this->render('itemPartialHtml', array("showDemo"=>$showDemo,"initDemo"=>$initDemo,"itemInfo"=>$itemInfo,"contentType"=>"item_specifics"));
		}
		?>

	</div>
</div>


</div>

<div id="poster_html">
<div class="poster_pic">
<div class="disno">
<center><img border="0" id="sample_poster_img"
	src="/images/ebay/template/poster_banner_sample_grey_1.jpg"></center>
<center><img border="0" id="sample_poster_img2"
	src="/images/ebay/template/poster_banner_sample_grey_1.jpg"></center>
</div>
<br>
<div id="Below_All_Product_Posters" class="desc_word ">
<?php 
if(isset($displayDsortable) && isset($displayDsortable['below_all_product_posters'])){
	foreach ($displayDsortable['below_all_product_posters'] as $d_content_type){
		echo $this->render('itemPartialHtml', array("itemInfo"=>$itemInfo,"allItem"=>$allItem ,"contentType"=>$d_content_type , "dContent"=>$contentTypeDContentMap[$d_content_type]));
	}
}else{
	echo $this->render('itemPartialHtml', array("showDemo"=>$showDemo,"itemInfo"=>$itemInfo,"contentType"=>"item_specifics"));
}
?>
</div>
</div>
</div>
<br>