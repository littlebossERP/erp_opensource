<?php if($contentType == "basic_descriptions"): ?>
	<?php if(empty($showDemo)): ?>
		<?php
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
// if(!empty($allItem['tb_eb_CFI_style_master_desc_details'])){
// 	$descDetailsStyle .= "text-align: ".$allItem['tb_eb_CFI_style_master_desc_details'];
// }else{
// 	$descDetailsStyle .= "text-align: left;";
// }
		$descDetailsStyle .= "'";
		$descBoxAlign = "";
		if(!empty($dContent['d_content_align'])){
			$descBoxAlign = "ab".$dContent['d_content_align'];
		}

		$descFullWidth = 770;
		if(!empty($dContent['d_content_width'])){
			if('1/2' == $dContent['d_content_width']){
				$descBoxWidth = $descFullWidth * 0.5;
			}else if('1/3' == $dContent['d_content_width']){
				$descBoxWidth = $descFullWidth * 1 / 3;
			}else if('2/3' == $dContent['d_content_width']){
				$descBoxWidth = $descFullWidth * 2 / 3;
			}else if('1/4' == $dContent['d_content_width']){
				$descBoxWidth = $descFullWidth * 1 / 4;
			}else if('3/4' == $dContent['d_content_width']){
				$descBoxWidth = $descFullWidth * 3 / 4;
			}

		}else{
			$descBoxWidth = 385;
		}

//		var_dump($dContent);
//		die;
		?>
		<div id="d_pre<?php echo $dContent['d_content_displayorder'];?>" style="width: <?php echo $descBoxWidth;?>px; border: 0px solid rgb(124, 237, 4);" class="cbp">
			<div class="dbbox <?php echo $descBoxAlign;?>" style="width: <?php echo $descBoxWidth;?>px;">
				<div class="desc_details" <?php echo $descDetailsStyle;?>>
					<?php if($dContent['d_content_pos']=="Next_to_Product_Photo"){
						echo $itemInfo['itemdescription_listing'];
						}else{
						echo $itemInfo['description'];
					}
					;?>

				</div>
			</div>
		</div>
	<?php else :?>
		<div id="d_pre1" style="width: 385px; border: 0px solid rgb(124, 237, 4);" class="cbp">
			<div class="dbbox" style="width: 390px;">
				<div class="desc_details <?php echo (isset($d_content_align)?$d_content_align:"");?>">
					<ul>
						<li>Luxury <b>satin</b> gift bags</li>
						<li>High quality silky satin used</li>
						<li>Ideal for jewellery pouches or wedding favour bags</li>
						<li>Also available in more color choices, please see our chart
							below</li>
						<li>Other size available, detail shown in size chart below</li>
						<li>Size measured from outside, internal size would be smaller</li>
						<li>Drawstring area takes at least one inch to close</li>
					</ul>
					<br>
					<br>
					<strong>
						<p class="master_size_string">Size</p>
					</strong>
					<ul class="master_size_string2">
						<li>
							<div class="descdiv">Approx. 10.5 x 14 cm or 4 x 5.5 inch</div>
						</li>
						<li>
							<div class="descdiv">(with 1 inch gusseted bottom)</div>
						</li>
					</ul>
					<br>
					<strong>Package includes</strong>
					<li>200 Bags</li>
					<br>
					<table class="desc_details  m_desc_details">
						<tbody>
						<tr>
							<td style="vertical-align: top;"><strong>
									<p class="master_Desc_string">Model</p></strong>
							</td>
							<td style="vertical-align: top;">
								<p id="master_Desc_string2">&nbsp;&nbsp;sample product code</p>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif;?>


<?php elseif($contentType == "action_button"):?>
	<style type="text/css">
		.bn3 {}
		#act3{}
		#bit3{}
		.price3{display:none}
		.aabl3 {BORDER: #555 1px solid;width: 100%;-moz-box-shadow: 0px 10px 14px -7px #276873;-webkit-box-shadow: 0px 10px 14px -7px #276873;
			box-shadow: 0px 10px 14px -7px #276873;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;display:inline-block;cursor:pointer;font-weight:bold;text-decoration:none;
			text-shadow:0px 1px 0px #3d768a;text-align:center;FONT-SIZE: 18PX;color: #ffffff;font-family: Arial;background-repeat:repeat-x;background-image:url("http://soldeazy.com/pub/45pxh/45p1.jpg");
			padding: 10px 0px 10px 0px;}
		.buy3 {BORDER: #555 1px solid;width: 100%;-moz-box-shadow: 0px 10px 14px -7px #276873;-webkit-box-shadow: 0px 10px 14px -7px #276873;
			box-shadow: 0px 10px 14px -7px #276873;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;display:inline-block;cursor:pointer;font-weight:bold;text-decoration:none;
			text-shadow:0px 1px 0px #3d768a;text-align:center;background-repeat:repeat-x;background-image:url("http://soldeazy.com/pub/45pxh/45p1.jpg");FONT-SIZE: 18PX;color: #ffffff;font-family: Arial;
			padding: 10px 0px 10px 0px;}
		.fpabl3 {BORDER: #555 1px solid;width: 100%;-moz-box-shadow: 0px 10px 14px -7px #276873;-webkit-box-shadow: 0px 10px 14px -7px #276873;
			box-shadow: 0px 10px 14px -7px #276873;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;display:inline-block;cursor:pointer;font-weight:bold;
			text-decoration:none;text-shadow:0px 1px 0px #3d768a;text-align:center;background-repeat:repeat-x;background-image:url("http://soldeazy.com/pub/45pxh/45p3.jpg");FONT-SIZE: 18PX;color: #ffffff;
			font-family: Arial;padding: 10px 0px 10px 0px;}
		.price3{font-size: 35PX;font-family: Arial;color: #ff0000;}
		.tfl3{}
	</style>

	<?php if(!empty($initDemo) && $initDemo == true):?>
		<div id="d_pre3" class="cbp">
			<div class="dbbox abcenter needpadding" style="width: 780px;border: 1px none ;">
				<table style="display: inline-table;width:100%;margin-left:auto;margin-right:auto;text-align:left;" class="">
					<tbody>
					<tr>
						<td align="center" colspan="3" class="price3">
							<div>USD 29.99</div>
						</td>
					</tr>

					<tr class="bn3">
						<td colspan="2" style="width: 200px; font-size: 12px; margin-right: auto; margin-left: 0px; text-align: left; color: rgb(76, 76, 76); font-family: Arial;" class="desc_details tfl3">Don't want to wait...</td>
						<td colspan="3">
							<a class="fpabl3">Direct Buy</a>
							<a class="buy3">Buy Now</a>
						</td>
						<td colspan="3">
							<a class="aabl3">Place Bid</a>
							<a class="aabl3">Add to cart</a>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
	<?php else :?>
		<div id="d_pre<?php echo $dContent['d_content_displayorder'];?>" class="cbp">
			<div class="dbbox abcenter needpadding" style="width: 780px;border: 1px none ;">
				<table style="display: inline-table;width:100%;margin-left:auto;margin-right:auto;text-align:left;" class="">
					<tbody>
					<tr>
						<td align="center" colspan="3" class="price3">
							<div>USD 29.99</div>
						</td>
					</tr>

					<tr class="bn3">
						<td colspan="2" style="width: 200px; font-size: 12px; margin-right: auto; margin-left: 0px; text-align: left; color: rgb(76, 76, 76); font-family: Arial;" class="desc_details tfl3">Don't want to wait...</td>
						<td colspan="3">
							<a class="fpabl3">Direct Buy</a>
							<a class="buy3">Buy Now</a>
						</td>
						<td colspan="3">
							<a class="aabl3">Place Bid</a>
							<a class="aabl3">Add to cart</a>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif;?>

<?php elseif($contentType == "eagleGallery"):?>
	<?php if(!empty($initDemo) && $initDemo == true):?>
		<div id="d_pre4" class="cbp">
			<div class="outer">
				<div class="inner">
					<!-- ==========EAGLEGALLERY========== -->
					<div id="layoutshow">
						<style>
							#eaglegallery-label{font-style:italic;height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;vertical-align:top}
							#eaglegallery-price{height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;padding-right:20px;vertical-align:top}
							#eaglegallery-remaining{height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;padding-left:20px;vertical-align:top}
							#eaglegallery-wrap div,#eaglegallery-wrap table,#eaglegallery-wrap tr,#eaglegallery-wrap th,#eaglegallery-wrap td{padding:0}
							#eaglegallery-wrap div table tr td a{cursor: pointer;text-decoration: none;}
							#seo{display:none}
							.bin_img{display:table-cell;vertical-align:middle}
							.eazy-credit{margin-top:60px;position:relative;text-align:center}
							.SH_title_bar{box-shadow:1px 1px 9px #999;border-radius:5px 5px 0 0;padding:1px 1px}
							.tbl_cell_class{margin-bottom:10px}
							a.eazy-credit{font-family:Verdana;font-size:9px;text-decoration:none}
							a.eazy-credit:hover{text-decoration:underline}
							a.listingTitle{font-family:Verdana;font-size:8px;text-decoration:none;line-height:9px;display:block;-webkit-text-size-adjust:none;}
							a.listingTitle:hover{text-decoration:underline}
							@media screen and (-webkit-min-device-pixel-ratio:0) {
								a.listingTitle{line-height:11px;}
							}
						</style>
						<div id="eaglegallery-wrap" style="width: 600px; border-radius: 6px; display: block; background: rgb(170, 160, 160);">

						</div>
					</div>
				</div>
			</div>
		</div>
	<?php else:?>
		<div id="d_pre<?php echo $dContent['d_content_displayorder'];?>" class="cbp">
			<div class="outer">
				<div class="inner">
					<!-- ==========EAGLEGALLERY========== -->
					<div id="layoutshow">
						<style>
							#eaglegallery-label{font-style:italic;height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;vertical-align:top}
							#eaglegallery-price{height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;padding-right:20px;vertical-align:top}
							#eaglegallery-remaining{height:20px;padding:0;font-family:sans-serif;font-size:10px;font-weight:normal;padding-left:20px;vertical-align:top}
							#eaglegallery-wrap div,#eaglegallery-wrap table,#eaglegallery-wrap tr,#eaglegallery-wrap th,#eaglegallery-wrap th{padding:0}
							#eaglegallery-wrap div table tr td a{cursor: pointer;text-decoration: none;}
							#seo{display:none}
							.bin_img{display:table-cell;vertical-align:middle}
							.eazy-credit{margin-top:60px;position:relative;text-align:center}
							.SH_title_bar{box-shadow:1px 1px 9px #999;border-radius:5px 5px 0 0;padding:1px 1px}
							.tbl_cell_class{margin-bottom:10px}
							a.eazy-credit{font-family:Verdana;font-size:9px;text-decoration:none}
							a.eazy-credit:hover{text-decoration:underline}
							a.listingTitle{font-family:Verdana;font-size:8px;text-decoration:none;line-height:9px;display:block;-webkit-text-size-adjust:none;}
							a.listingTitle:hover{text-decoration:underline}
							@media screen and (-webkit-min-device-pixel-ratio:0) {
								a.listingTitle{line-height:11px;}
							}
						</style>
						<div id="eaglegallery-wrap" style="width: 600px; border-radius: 6px; display: block; background: rgb(170, 160, 160);">

						</div>
					</div>
				</div>
			</div>
		</div>

	<?php endif;?>
	<script type="text/javascript">

		var ListingNum=new Array();
		var ListingTitle=new Array();
		var ListingView=new Array();
		var ListingGallery=new Array();
		var ListingType=new Array();
		var ListingCurrentPrice=new Array();
		var ListingBIN=new Array();
		var ListingTimeLeft=new Array();
		var ListingFullTitle=new Array();
		var Listingcurrency=new Array();
		var Listingformat=new Array();

		<?php if(!empty($itemInfo) && !empty($itemInfo['crossSellArr'])):?>
		<?php $index = 0;?>
		<?php foreach ( $itemInfo['crossSellArr'] as $crossItem ):?>

		ListingNum[<?= $index?>] = '<?php if(isset($crossItem['picture'])) echo $crossItem['picture'];?>';
		ListingFullTitle[<?= $index?>] = '<?php if(isset($crossItem['title'])) echo $crossItem['title'];?>';
		ListingTitle[<?= $index?>] = '<?php if(isset($crossItem['title'])) echo $crossItem['title'];?>';
		ListingType[<?= $index?>] = 'StoresFixedPrice';
		ListingCurrentPrice[<?= $index?>] = '';
		ListingBIN[<?= $index?>] = '<?php if(isset($crossItem['price'])) echo $crossItem['price'];?>';
		ListingTimeLeft[<?= $index?>] = '';
		ListingView[<?= $index?>] = '<?php if(isset($crossItem['url'])) echo $crossItem['url'];?>';
		Listingcurrency[<?= $index?>] = '<?php if(isset($crossItem['icon'])) echo $crossItem['icon'];?>';
		Listingformat[<?= $index?>] = 'BIN';
		<?php $index++;?>

		<?php endforeach;?>
		<?php endif;?>

		var font_color = "#333333";
		var showcase_Backgroud_set = "color";
		var showcase_Backgroud = "#aaa0a0";
		var showcase_Backgroud_img = "";
		var showcase_Frame_Color = "#333333";
		var Hover_Photo_Color = "#c0c0c0";
		var Price_Font = "#bf0000";
		var showcase_title_bar = "none";
		var height = "245";
		var if_height = "190";
		var showcase_img_height = "solid";
		var ShowcaseFrame = "solid";
		var ShowcasePhoto_Frame = "solid";
		var showcase_bottom_color = "#c0c0c0";
		var message="";
		var string_color="#ffffff";
		var title_position="center";
		var message2="It's EagleGallery! Come to my shop for more.";
		var designid="9";
		var showcase_set = "fr";
		// 	var setting_data ="promotion";
		setting_data ="item";
		var setting_title_bar ="";
		var setting_Item ="";
		var setting_Price ="";
		var setting_Frame ="";
		var setting_Photo_Frame ="";
		var check_id = "1692";
		setting_Item_Time_Interval = 5000;
		setting_Promotion_Time_Interval = 8000;

		function findp(text){
			if(text.search("#") > -1){
				return text;
			}else{
				return '#'+text;
			}
		}

		function buildEagleGallery() {
			document.getElementById('eaglegallery-wrap').style.display='none';
			document.getElementById('eaglegallery-wrap').innerHTML = "";
			var q="'";
			var addother = '';
			xCount=0;
			if (showcase_Backgroud != ''){
				document.getElementById('eaglegallery-wrap').style.background = findp(showcase_Backgroud);
			}else{
				document.getElementById('eaglegallery-wrap').style.backgroundImage = 'url('+showcase_Backgroud_img+')';
			}
			var ScrollImages=new Array();
			for (var i=0; i<=ListingNum.length; i++){
				//item//
				if (setting_data == 'item'){
					if (ListingNum[i]){
						xCount++;
						var img_bin = '';
						//Update for Smart Social - will only run on Smart Social results page
						if (typeof FSSresult !== 'undefined') {
							ListingView[i] = FCS_link_pt1+"_crossell"+conv_trackingid+""+FCS_link_pt2+""+ListingNum[i]+""+FCS_link_pt3;
						}
						if(Listingformat[i] === 'BIN'){
							img_bin = '<img src="http://soldeazy.com/pub/Fix_price.png" width="15px" border="0" />';
						}else{
							img_bin = '<img src="http://soldeazy.com/pub/Auction.png" width="15px"  border="0" />';
						}

						ScrollImages[xCount] = '<div onmouseover="highlightIt('+xCount+','+q+q+','+q+ListingNum[i]+q+');" onmouseout="noHighLight('+xCount+');" id="tbl_cell_'+xCount+'" name="tbl_cell_'+xCount+'" class="slideshowObjectStatic"><table cellspacing="0px cellpadding="0px" style="border:3;font-family: arial;font-size:14px;"><tr id="itemhere" style="vertical-align:top;"><td></td></tr><tr style=" height: 142px; "><td align="center" colspan="3">'+
							'<a class="" style="width:140px;height:140px;" href="' + ListingView[i] + '" target="_blank">'+
							'<img class="showcaseimg" onload="" id="picid'+i+'" style="max-width: 140px;" src="'+ListingNum[i]+'" border="0" '+
							' title="" alt="'+ListingNum[i]+'" style="border-radius: 10px;" /></a></td></tr>'+
							'<tr id="listingTitle" class="listTitle" style="vertical-align:top;'+setting_Item+'"><td style="width:1px;"></td><td align="center"><a style="height:13px;overflow:hidden;margin-top:2px;color:'+findp(font_color)+';text-overflow: ellipsis;white-space: nowrap;width: 140px;" class="listingTitle" href="' + ListingView[i] + '" target="_blank" title = "'+ ListingTitle[i] +'">' + ListingTitle[i] + '</a></td><td style="width:1px;"></td></tr>' +
							'<tr id="listingPrice" class="listPrice" style="vertical-align:top;'+setting_Price+'"><td style="width:1px;"></td><td align="center" valign="center"><a style="margin-top:2px;font-size:9px;-webkit-text-size-adjust:none;text-decoration:none;color:'+findp(Price_Font)+';"  href="' + ListingView[i] + '" target="_blank" font-size="9px">'/*+img_bin+' '*/+Listingcurrency[i]+' '+parseFloat(ListingBIN[i]).toFixed(2)+'</a></td><td style="width:1px;"></td></tr>' +
							'<tr id="" class="listPrice" style="vertical-align:top;"><td style="width:1px;"></td><td align="center" valign="center"><a style="margin-top:2px;-webkit-text-size-adjust:none;text-decoration:none;color:'+findp(font_color)+';"  href="' + ListingView[i] + '" target="_blank" font-size="12px"><div class="listingPrice" style="margin-top:2px;font-size:9px;-webkit-text-size-adjust:none;text-decoration:none;"> '+ListingTimeLeft[i]+' </div></a></td><td style="width:1px;"></td></tr>' +
							'</table></div>';
					}

					//item end//
					addothercss='.slideshowObjectStatic{width: 150px;height: 220px;text-align: center;overflow: hidden;display:inline;float:left;}'
					addothercss+='.slideshowObject{ width: 150px; height: 220px;position: absolute; text-align: center; overflow: hidden;}';
					addothercss+='.slideshowContainer{width: 750px;height: 220px;position: relative;overflow: hidden;margin: auto;}';
					if(setting_Item != '' && setting_Price!= ''){
						addothercss+='.slideshowContainer{height: 143px;}';
					}else if(setting_Item != ''){
						addothercss+='.slideshowContainer{height: 162px;}';
					}else if(setting_Price != ''){
						addothercss+='.slideshowContainer{height: 192px;}';
					}
					addother = '';
					if(showcase_title_bar != 'none'){
						addother +='<div class="SH_title_bar" id="SH_title_bar" style="height: 25px;background-image: url('+showcase_title_bar+');font-weight:bold;vertical-align: middle;color:'+findp(string_color)+'" align="center">';
						addother += '<div id="eazy_message" title="" style="padding-top:5px;padding-left: 11px;padding-right: 11px;font-size:14px">'+message2+'</div></div>';
					}

				}else{
					//promo//
					if (ListingNum[i]){
						xCount++;
						var img_bin = '';

						//Update for Smart Social - will only run on Smart Social results page
						if (typeof FSSresult !== 'undefined') {
							ListingView[i] = FCS_link_pt1+"_crossell"+conv_trackingid+""+FCS_link_pt2+""+ListingNum[i]+""+FCS_link_pt3;
						}
						ScrollImages[xCount]='<div class="slideshowObjectStatic">'+
							'<a class="" href="' + ListingView[i] + '" target="_blank">'+
							'<img id="picid'+i+'" src="'+ListingNum[i]+'" alt="'+ListingTitle[i]+'" border="0" '+
							' title="" /></a>'+
							'</div>';
					}
					//promo end //
					addothercss ='.slideshowObjectStatic{height: 200px;text-align: center;overflow: hidden;display:inline;float:left;}';
					addothercss+='.slideshowObject{ height: 200px;position: absolute; text-align: center; overflow: hidden;}';
					addothercss+='.slideshowContainer{width: 780px;height: 200px;position: relative;overflow: hidden;margin: auto;}';
					addothercss+='#eaglegallery-wrap{background: transparent !important;}';
				}

			}

			var xstrip=ScrollImages.join("");
			//css//


			xHTML ='<style>';
			xHTML+='#eaglegallery-wrap{overflow: hidden;width: 780px !important;border-radius:10px;border:2px '+ShowcaseFrame+' '+findp(showcase_Frame_Color)+'}';
			xHTML+=addothercss;
			xHTML+='</style>';
			//css end//
			xHTML+= addother;
			xHTML+='<div class="slideshowContainerStatic">'+xstrip+'</div>';
			xHTML+='<div id="showbox" style="width:780px;position:absolute;display:none"><center><div id="boxstyle" style="border-radius: 10px;background-color:rgba(56, 44, 44, 0.91);padding: 10px;width: 305px;"><table cellspacing="0" cellpadding="0" border="0" width="100%">';
			xHTML+='<tr><td id="eaglegallery-img" align="center" align="center" width=""><img id="showboximg" width="300px" src="http://i.ebayimg.sandbox.ebay.com/00/s/NzQyWDk5MA==/$(KGrHqVHJC0FFJcwBq5nBSevKB!3d!~~60_1.JPG?set_id=8800005007"></td></tr>';
			xHTML+='<tr><td id="eaglegallery-label" style="overflow:hidden;font-size:15px;color:'+findp(string_color)+'" align="center" ></td></tr>';
			xHTML+='<tr><td id="eaglegallery-price" style="padding-right:10px;color:'+findp(Price_Font)+'" align="center" ></td></tr>';
			xHTML+='</table></div></center></div>';
			document.getElementById('eaglegallery-wrap').innerHTML=xHTML;
			document.getElementById('eaglegallery-wrap').style.display='block';
			start();
		}
		// JUST COMMENT THIS OUT TO SEE IT WITHOUT JAVASCRIPT!
		var objTimer = null;
		var letTimer = null;
		if (setting_data == 'item'){
			var theLeft = 0;
		}else{
			var theLeft = 0;
		}
		var iii = 0;
		function posIt() {
// 		document.getElementById('eaglegallery-wrap').style.display='block';
			iii++;
			theLeft -= 10;
			var objs = document.getElementsByClassName('slideshowObject');
			if(objs.length > 5){
				if (theLeft <= (-150*objs.length)) {
					theLeft = 0;
				}
				for (var i = 0; i < objs.length; i++) {
					var obj = objs[i];
					var leftPos = (theLeft + obj.idxVal*150);
					if (leftPos < -150)
						leftPos += (150*objs.length);
					obj.style.left = leftPos + 'px';
				}
				if(iii < 15 ){
					objTimer = setTimeout(posIt, 20);
				}else{
					iii =0 ;
				}
			}else{
				theLeft = 0;
				if (theLeft <= (-150*objs.length)) {
					theLeft = 0;
				}
				for (var i = 0; i < objs.length; i++) {
					var obj = objs[i];
					var leftPos = (theLeft + obj.idxVal*150);
					if (leftPos < -150)
						leftPos += (150*objs.length);
					obj.style.left = leftPos + 'px';
				}
				if(iii < 15 ){
					objTimer = setTimeout(posIt, 20);
				}else{
					iii =0 ;
				}
			}
		}
		function posIt2() {
// 		document.getElementById('eaglegallery-wrap').style.display='block';
			iii++;
			theLeft -= 10;
			var objs = document.getElementsByClassName('slideshowObject');
			if (theLeft <= (-780*objs.length)) {
				theLeft = 0;
			}
			for (var i = 0; i < objs.length; i++) {
				var obj = objs[i];
				var leftPos = (theLeft + obj.idxVal*780);
				if (leftPos < -780)
					leftPos += (780*objs.length);
				obj.style.left = leftPos + 'px';
			}
			if(iii < 78 ){
				objTimer = setTimeout(posIt2, 10);
			}else{
				iii =0 ;
			}

		}

		buildEagleGallery();

		function letdo() {
			if (setting_data == 'item'){
				posIt();
			}else{
				posIt2();
			}

			if (setting_data == 'item'){
				if(xCount > 5)
					letTimer = setTimeout(letdo, setting_Item_Time_Interval);
			}else{
				letTimer = setTimeout(letdo, setting_Promotion_Time_Interval);
			}

		}
		function start() {
			document.getElementsByClassName('slideshowContainerStatic')[0].className = 'slideshowContainer';
			var objs = document.getElementsByClassName('slideshowObjectStatic');
			var len = objs.length;
			for (var i = 0; i < len; i++) {
				var obj = objs[0];
				obj.className = 'slideshowObject';
				obj.id = 'slideshowItem' + i;
				obj.idxVal = i;
				obj.addEventListener('mouseover', function() {
					clearTimeout(letTimer);
					clearTimeout(objTimer);
				}, true);
				obj.addEventListener('mouseout', function() {
					letTimer = setTimeout(letdo, 2000);
				}, true);
			}

			// 发生多次请求导致多次载入js ， 再导致setTimeout被调用了多次，所以gallery 跳转的时间间隔有问题
			// 通过 dom 设置属性来避免？ (因为dom 是共享的)
			var wrapperClassList = document.getElementById('subbody').classList;
			var issetTimer = false;
			for(var j = 0 ; j < wrapperClassList.length ; j++ ){
				if(wrapperClassList[j] == 'running-gallery'){
					issetTimer = true;
				}
			}

			if(!issetTimer){
				document.getElementById('subbody').className += ' running-gallery';
				letdo();
			}

		}
		function highlightIt(num,label,imgh){
			return false;
			tblElement = "picid"+num;
			document.getElementById('showboximg').src = imgh;
			document.getElementById('showbox').style.display='block';
			window.clearTimeout(window.startaction);
			var imgs=document.getElementsByName(tblElement);
			for (var x=0; x<=imgs.length; x++){
				if(imgs[x]) {
					//imgs[x].style.width='120px';
					//imgs[x].style.height='120px';
				}
			}
			//document.getElementById("eaglegallery-remaining").innerHTML= ListingTimeLeft[num];
			document.getElementById("eaglegallery-label").innerHTML=ListingFullTitle[num];
			if(ListingBIN[num]!="") {
				document.getElementById("eaglegallery-price").innerHTML="<div style='font-weight:bold;position:relative;'>"+Listingcurrency[num]+' '+parseFloat(ListingBIN[num]).toFixed(2)+"</div>";
			} else {
				document.getElementById("eaglegallery-price").innerHTML="<div style='font-weight:bold;position:relative;'>"+Listingcurrency[num]+' '+parseFloat(ListingBIN[num]).toFixed(2)+"</div>";
			}
		};

		function noHighLight(num) {
			if (xCount>=1) {
				document.getElementById('showbox').style.display='none';
			}
			//document.getElementById("eaglegallery-remaining").innerHTML="";

			tblElement = "tbl_cell_"+num;
			tblElement = "tbl_cell_"+num;
			var imgs=document.getElementsByName(tblElement);
			for (var x=0; x<=imgs.length; x++){
				if(imgs[x]) {
					imgs[x].style.border="";
					imgs[x].style.background = '';
				}
			}
		}

	</script>
<?php endif;?>

