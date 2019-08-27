<?php 

$sideBarBoxTitleStyle = "style='";
if(!empty($allItem['title_bkgd_type'])){
	if($allItem['title_bkgd_type'] == 'Pattern'){
		$sideBarBoxTitleStyle .= "background-image: url(".$allItem['title_bkgd_pattern'].");";
	}else{
		if(stripos($allItem['infobox_bkgd_color'] , '#') === false )
			$allItem['title_bkgd_color'] = '#'.$allItem['title_bkgd_color'];
		$sideBarBoxTitleStyle .= "background-color:".$allItem['title_bkgd_color'].";";
	}
}

if(!empty($allItem['title_fontSize'])){
	$sideBarBoxTitleStyle .= "font-size: ". ($allItem['title_fontSize']) ."px;";
}
if(!empty($allItem['title_fontStyle'])){
	$sideBarBoxTitleStyle .= "font-family:".$allItem['title_fontStyle'].";";
}
if(!empty($allItem['title_fontColor'])){
	if(stripos($allItem['title_fontColor'] , '#') === false )
		$allItem['title_fontColor'] = '#'.$allItem['title_fontColor'];

	$sideBarBoxTitleStyle .= "color:".$allItem['title_fontColor'].";";
}


if(!empty($allItem['cat_bkgd_type'])){
	if($allItem['cat_bkgd_type'] == 'Pattern'){
		$sideBarBoxTitleStyle .= "background-image: url(".$allItem['cat_bkgd_pattern'].");";
	}else{
		if(stripos($allItem['infobox_bkgd_color'] , '#') === false )
			$allItem['cat_bkgd_color'] = '#'.$allItem['cat_bkgd_color'];
		$sideBarBoxTitleStyle .= "background-color:".$allItem['cat_bkgd_color'].";";
	}
}

$sideBarBoxTitleStyle .= "'";


$sideBarBoxStyle = "style='";
if(!empty($allItem['infobox_bkgd_type'])){
	if($allItem['infobox_bkgd_type'] == 'Pattern'){
		$sideBarBoxStyle .= "background-image: url(".$allItem['infobox_bkgd_pattern'].");";
	}else{
		if(stripos($allItem['infobox_bkgd_color'] , '#') === false )
			$allItem['infobox_bkgd_color'] = '#'.$allItem['infobox_bkgd_color'];
		$sideBarBoxStyle .= "background-color:".$allItem['infobox_bkgd_color'].";";
	}
}
$sideBarBoxStyle .= "'";

?>
<?php if("search" == strtolower($catInfo['content_type_id'])):?>

<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>> <?php echo $catInfo['content_text'];?> </div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<div id='Store-Search'>
<form name='search' method='get' action='http://stores.ebay.com//' target="_blank">
<ul style='padding: 0px;'>
	<li style='margin: 5px;'><input style='float: left; width: 96px;'
		type='text' id='Search-Query' name='_nkw' value=''> <input
		name='submit' type='submit' value='GO' class='catbutton'></li>
</ul>
</form>
</div>
</div>

<?php elseif ("Hot Item" == $catInfo['content_type_id']):?>
<style type='text/css'>
#theDIV2 {
	position: absolute;
	width: 180px; background #fff;
	color: #666;
}

</style>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div id='hotitem-content' class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<div>
<table class='hotitemli'>
	<div class='soout' id='soout2' name='soout2'></div>
	<?php if(!empty($itemInfo) && !empty($itemInfo['crossSellArr'])):?>
	<?php $index = 0;?>
	<?php foreach ( $itemInfo['crossSellArr'] as $crossItem ):?>
	<?php 
		$index++;
		if($index > 6)break;
	?>
	<tr onMouseOver='soout2(this);' onMouseout='sooutout2();'>
		<td><a target='_blank' href='<?php if(isset($crossItem['url'])) echo $crossItem['url'];?>'>
		<img
			src='<?php if(isset($crossItem['picture'])) echo $crossItem['picture'];?>'
			alt='2' width='50'> </a>
			
			
		</td>
		<td class="navitemc navitemitem hotitemtitle" style="color: rgb(127, 127, 127);">
			<table>
				<tbody>
					<tr>
						<td>
							<div class="navitemc navitemitem hotitemtitle" style="color: rgb(127, 127, 127);"><?php if(isset($crossItem['title'])) echo $crossItem['title'];?></div>
						</td>
					</tr>
					
					<tr>
						<td>
							<p class="navitemc" style="margin-top: 0px; margin-bottom: 0px;color:red;">
								<em><?php if(isset($crossItem['price']) && isset($crossItem['icon'])) echo $crossItem['icon']." ".$crossItem['price'];?></em>
							</p>
						</td>
					</tr> 
				</tbody>
			</table>
		</td>
	</tr>
	<?php endforeach;?>
	<?php endif;?>
</table>
</div>
</div>
<script type='text/javascript'>
function soout2(e)
{
// return false;
markup = e.innerHTML; 
document.getElementById('soout2').innerHTML = markup;
document.getElementById('soout2').style.display='fixed';
document.getElementById('soout2').style.top=event.clientY-100+'px';
document.getElementById('soout2').style.left=event.clientX+100+'px';
// document.getElementById('gallery').style.position='static';
document.getElementById('hotitem-content').style.zIndex = 9;
document.getElementById('soout2').style.display='block';
}
function sooutout2()
{
// return false;
document.getElementById('soout2').style.display='none';
// document.getElementById('gallery').style.position='relative';
document.getElementById('hotitem-content').style.zIndex = '';
}

</script>
<?php elseif ("Picture" == $catInfo['content_type_id']):?>
<?php 
$infobox_content_json_arr = array();
$infobox_content = array();
if(!empty($catInfo['infobox_content'])){
	$infobox_content_json_arr = json_decode($catInfo['infobox_content'],true);
	$infobox_content = explode("::", $infobox_content_json_arr[0]);
	$infobox_content = array_filter($infobox_content);
}
?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<center>
<div class='navpic'>
<p href='#'>
<?php if(!empty($infobox_content)):?>
<?php foreach ($infobox_content as $picSrc):?>
<img src='<?php echo $picSrc ;?>'
	alt='content_src_url' width='170'>
<?php endforeach;?>	
<?php endif;?>	
</p>
</div>
</center>
</div>
<?php elseif ("New List Item" == $catInfo['content_type_id']):?>

<style type='text/css'>
#theDIV4 {
	position: absolute;
	width: 180px; background #fff;
	color: #666;
}

#itemlayout {
	position: relative;
	width: 180px;
	height: 180px;
	overflow: hidden;
}

.w180 {
	width: 180px;
	float: left;
}
</style>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<div id='itemlayout'>
<div id='theDIV4'>
<div class='w180'>
	<?php if(!empty($newListItem)):?>
	<a target='_blank' href='http://www.ebay.de/itm/<?php echo $newListItem['itemid']; ?>'>
		<img src='<?php echo $newListItem['mainimg']; ?>'
			 alt='New' width='179'>
	</a>
	<?php endif;?>
</div>
</div>
</div>
</div>
<script type='text/javascript'>
// NS6 = (document.getElementById&&!document.all);
// IE = (document.all);
// NS = (navigator.appName=='Netscape'&&navigator.appVersion.charAt(0)=='4');
// moving4 = setTimeout('null',1);
// var divobj4, speed = 20;
// var stime = 4000;
// var nowno = 0;
// if(!window.startaction4){
// var startaction4;
// var action;
// } 
// var int4=self.setInterval(function(){ movediv4('theDIV4',-180);},stime);
// function movediv4(id,len) {
// divobj4 = document.getElementById(id).style;
// if(isNaN(parseInt(divobj4.left))) divobj4.left = '0px';
// elen = parseInt(divobj4.left)+len;
// var nowscroll = parseFloat(document.getElementById('theDIV4').title); 
// var maxscroll = Math.floor(document.getElementById('theDIV4').offsetWidth / 180);

// if(nowscroll < maxscroll){
// moveit4(len,elen); 
// window.clearInterval(int4);
// int4=self.setInterval(function(){ movediv4('theDIV4',-180);},stime);
// }else{
// window.clearInterval(int4);
// document.getElementById('theDIV4').style.left='0px';
// int4=self.setInterval(function(){ movediv4('theDIV4',-180);},stime);
// document.getElementById('theDIV4').title = 1;
// }
// }
// function moveit4(len,elen) {
// clearInterval(window.startaction4);
// if(((len>0)&&((NS6||NS)&&parseInt(divobj4.left)<elen)||(IE&&divobj4.pixelLeft<elen))
// || ((len<0)&&((NS6||NS)&&parseInt(divobj4.left)>elen)||(IE&&divobj4.pixelLeft>elen))) {
// clearTimeout(moving4);
// moving4 = setTimeout('moveit4('+len+','+elen+')',speed);
// num=(len>0)?10:-10;
// if (IE)
// divobj4.pixelLeft += num;
// else if (NS6)
// divobj4.left = parseInt(divobj4.left)+num+'px';
// else if (NS)
// divobj4.left = parseInt(divobj4.left)+num;
// } else {
// clearTimeout(moving4);
// moving4=setTimeout('null',1);
// var nowscroll = parseFloat(document.getElementById('theDIV4').title);
// document.getElementById('theDIV4').title = nowscroll + 1;

// }
// }
</script>
<?php elseif ("Text.Link" == $catInfo['content_type_id']):?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<?php if(!empty($catInfo['infobox_content'])):?>
<?php foreach (json_decode($catInfo['infobox_content'],TRUE) as $infobox_content):?>
<?php list($contentText,$contentLink) = explode("::", $infobox_content);?>
<?php if(!empty($contentText)):?>
<div class='Cat_gbox'><a class='gbox' target=''
	href='<?php echo $contentLink;?>'><?php echo $contentText?></a></div>
<?php endif;?>
<?php endforeach;?>
<?php endif;?>
</div>

<?php elseif ("Store Cat." == $catInfo['content_type_id']):?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<?php if(!empty($catInfo['infobox_content'])):?>
<?php foreach (json_decode($catInfo['infobox_content'],TRUE) as $infobox_content):?>
<?php list($contentText,$contentLink) = explode("::", $infobox_content);?>
<?php if(!empty($contentText)):?>
<div class='Cat_List'><a class='gbox' target=''
	href='<?php echo $contentLink;?>'><?php echo $contentText?></a></div>
<?php endif;?>
<?php endforeach;?>
<?php endif;?>
</div>

<?php elseif ("Flash" == $catInfo['content_type_id']):?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<?php if(!empty($catInfo['infobox_content'])):?>
<?php foreach (json_decode($catInfo['infobox_content'],TRUE) as $infobox_content):?>
<?php list($contentText,$contentLink) = explode("::", $infobox_content);?>
<?php if(!empty($contentText)):?>
<div class='Cat_gbox'><a class='gbox' target=''
	href='<?php echo $contentLink;?>'><?php echo $contentText?></a></div>
<?php endif;?>
<?php endforeach;?>
<?php endif;?>
</div>

<?php elseif ("Custom Item" == $catInfo['content_type_id']):?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
	<?php if(!empty($itemInfo) && !empty($itemInfo['crossSellArr'])):?>
		<?php foreach ( $itemInfo['crossSellArr'] as $crossItem ):?>
			<div class='Cat_gbox'>
			<a class='gbox' target='' href='<?php if(isset($crossItem['url'])) echo $crossItem['url']; ?>'><?php if(isset($crossItem['title'])){echo $crossItem['title'];} ?></a>
			</div>
		<?php endforeach?>
	<?php endif;?>
</div>

<?php elseif ("Youtube" == $catInfo['content_type_id']):?>
<div class='gboxout' <?php echo $sideBarBoxTitleStyle;?>><?php echo $catInfo['content_text'];?></div>
<div class='layoutborder' <?php echo $sideBarBoxStyle;?>>
<?php if(!empty($catInfo['infobox_content'])):?>
<?php foreach (json_decode($catInfo['infobox_content'],TRUE) as $infobox_content):?>
<?php list($contentText,$contentLink) = explode("::", $infobox_content);?>
<?php if(!empty($contentText)):?>
<div>
	<object width="180" height="111">
		<param name="movie" value="<?php echo $contentText;?>">
		<param name="allowScriptAccess" value="always">
		<embed src="<?php echo $contentText;?>" type="application/x-shockwave-flash" allowscriptaccess="always" width="180" height="111">
	</object>
</div>
<?php endif;?>
<?php endforeach;?>
<?php endif;?>
</div>
<?php endif;?>







