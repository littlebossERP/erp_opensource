<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;

$this->title = TranslateHelper::t("ebay可视化模板");

//设置全局js变量
$this->registerJs('var global = (function() { return this || (1,eval)("(this)"); }());global.baseUrl = "'.\Yii::getAlias('@web').'/";', \yii\web\View::POS_HEAD);

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery-1.8.3.min.js' , ['position' => \yii\web\View::POS_HEAD ] );
// 下面用到 $.fn.tabs ,这里需要的是 jquery-ui的,而不是easyUI的,
// 所以jquery-ui在easyUI后引入
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery.easyui.min.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery-ui-1.9.2.custom.min.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery-ui-1.9.2.custom.css' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jpicker/jpicker-1.1.6.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jpicker/jpicker-1.1.6.min.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jpicker/jpicker.css' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery-impromptu.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/zoo.css');
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/dropzone.js' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/dropzone.css');
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery.fancybox.pack.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/jquery.fancybox.css');
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/ckeditor/ckeditor.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/editEbayTemFunc.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/listing.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/project/listing/ebayVisualTemplate/templateCss.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/carrier/bootstrap.min.css' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/carrier/customprint/bootstrap.min.js' , ['position' => \yii\web\View::POS_HEAD ] );
$this->registerCssFile(\Yii::getAlias('@web')."/css/iconfont.css");
// $this->registerCssFile(\Yii::getAlias('@web')."/css/styleSheets/style.css");
$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['position' => \yii\web\View::POS_HEAD ]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['position' => \yii\web\View::POS_HEAD ]);
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/public.js' );



$tempalteId = !empty($_REQUEST['template_id'])?$_REQUEST['template_id']:0;

foreach ($allItem as $value){
	$allItem[$value['name']] = $value['value'];
} 

if(!empty($_REQUEST['style_id'])){
	$layoutSetting = explode('_', $_REQUEST['style_id']);
}else{
	
}
for( $i = 0; $i < 3; $i++ ){
	if($i == 0 ){
		if(isset($layoutSetting[$i])){
			$sideBarPosition = strtolower($layoutSetting[$i]);
		}else{ 
			$sideBarPosition = 'left';
		}
	}
	if($i == 1 ){
		if(isset($layoutSetting[$i])){
			$productPhotoDisplayPattern = strtolower($layoutSetting[$i]);
		}else{ 
			$productPhotoDisplayPattern = 'ls';
		}
	}
	if($i == 2 ){
		if(isset($layoutSetting[$i])){
			$policySectionStyle = strtolower($layoutSetting[$i]);
		}else{ 
			$policySectionStyle = 'tab';
		}
	}
}

$layoutStyleName = (!empty($sideBarPosition)? $sideBarPosition : ''). (!empty($productPhotoDisplayPattern)? '_'.$productPhotoDisplayPattern : '').(!empty($policySectionStyle)?  '_'.$policySectionStyle : '');

if(!empty($_REQUEST['theme'])){
	$theme = strtolower($_REQUEST['theme']);
}else{
	$theme = 'default';
}

if($theme == 'default'){
	$shop_name_text_color = "4c4c4c";
	$shop_name_sub_text_color = "999999";
	$title_bkgd_color = "cccccc";
	
	$eb_tp_clr_Mainbody_border = "666666";
	$eb_tp_clr_infobox_background = "ffffff";
	$eb_tp_clr_infobox_border = "ff5656";
	
	$eb_tp_tab_Header_selected = "";
	$eb_tp_tab_Header_color = "";
	$eb_tp_tab_Header_font = "";
}elseif ($theme == 'blue'){
	$shop_name_text_color = "005fbf";
	$shop_name_sub_text_color = "333333";
	$title_bkgd_color = "005fbf";
	
	$eb_tp_clr_Mainbody_border = "aad4ff";
	
	$eb_tp_clr_infobox_background = "d7f0f4";
	$eb_tp_clr_infobox_border = "aad4ff";
	
	$eb_tp_tab_Header_selected = "005fbf";
	$eb_tp_tab_Header_color = "aad4ff";
	$eb_tp_tab_Header_font = "ffffff";
	
}elseif ($theme == 'red'){
	$shop_name_text_color = "bf0000";
	$shop_name_sub_text_color = "000000";
	$title_bkgd_color = "bf0000";
	
	$eb_tp_clr_Mainbody_border = "aad4ff";
	
	$eb_tp_clr_infobox_background = "ffffff";
	$eb_tp_clr_infobox_border = "ff5656";
	
	$eb_tp_tab_Header_selected = "005fbf";
	$eb_tp_tab_Header_color = "aad4ff";
	$eb_tp_tab_Header_font = "ffffff";
}

?>
<style>
html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}
ol, ul {list-style: none;}
#showhide {
	background-image: url("<?= \Yii::getAlias('@web') ?>/images/ebay/template/engineering_.png");
	background-size: 20px 20px;
	height: 20px;
	width: 20px;
	cursor: pointer;
	position: fixed;
	top: 51px;
}

#id_layout_select {
	margin-bottom: 3px;
	background-image:
		url("<?= \Yii::getAlias('@web') ?>/images/ebay/template/icon_change_layout_01.png");
	background-size: 36px 28px;
	width: 36px;
	height: 28px;
	cursor: pointer;
	border: 0;
	display: inline-block;
}

p#moveheader {
	border: 1px solid #aaaaaa;
	background-color: #cccccc;
	margin: 0;
}

#body1 table td {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: normal;
	height: auto;
	line-height: 10px;
}

.ui-dialog-titlebar , .ui-state-default .ui-icon{
  display: none;
}
.ui-tooltip {
	color: rgb(34, 34, 34);
	display: block;
	font-family: Verdana, Arial, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

.btn_del {
	border: 0 !important;
	margin-right: 0 !important;
	background: transparent !important;
	background-image: url("<?= \Yii::getAlias('@web') ?>/images/ebay/template/delete-26.png") !important;
	background-size: 16px 16px !important;
	width: 16px;
	height: 16px;
	cursor: pointer;
	top: 4px;
	position: relative;
	border: 0;
	display: inline-block;
}
#policy_up .dz-default.dz-message, #test_upimg .dz-default.dz-message ,
#Body_up .dz-default.dz-message,#Menubar_up .dz-default.dz-message,
#mobile_up .dz-default.dz-message,#cat_up .dz-default.dz-message{
	/*background-image: url(<?= \Yii::getAlias('@web') ?>/images/ebay/template/click_here_01_214.png);*/
	width: 215px;
	height: 52px;
	margin-left: -105px;
	margin-top: -24.5px;
}

.dropzone {
	/*background: transparent!important;*/
	/*border: 1px dashed!important;*/
	min-height: 10px;
}

#form_upload.dropzone .dz-default.dz-message {
	/*background-image: url(<?= \Yii::getAlias('@web') ?>/images/ebay/template/click_here_01_214.png);*/
	width: 260px;
	-webkit-background-size: 200px 50px;
	height: 46px;
	margin-left: 10px;
	margin-top: -24.5px;
	min-height: 0px;
}
.jqibox , .jqifade{
	z-index: 15 !important;
}
.jPicker .Preview{
	background-image: none;
	
}
.jPicker{
	z-index: 99999 !important;
	margin-left: -10;
}

</style>
<div id="showhide"></div>
<form id="allItem">
<div>
	<select style="width:100px;height: 20px;" id="userAccount" name="userAccount">
		<option value="all" selected>所有</option>
		<?php if(count($storenameArr)>=1){ 
			foreach ($storenameArr as  $val) {
			echo "<option value='".$val."'>".$val."</option>";
		}}
		
		?>
		
	</select>
</div>
<div id="bigb">
<div id="body1">
<p id="moveheader" class="ui-widget-header"
	style="height: 18px; top: 0px; position: relative; cursor: move;"></p>
<div id="tool"></div>
<table style="width: 100%;display: none;">
	<tbody>
		<tr>
			<td style="display: -moz-popup;*display:none;display:none\9;"></td>
			<td style="width: 48px; vertical-align: middle;">
			<div style="text-align: center;"><a name="tip241"
				class="layout_select tip" id="id_layout_select"></a>
			<a name="tip241" class="" style="padding:5px 0px;">Change Layout</a></div>
			</td>
		</tr>
	</tbody>
</table>

<input name="layout_style_name" class="disno" id="layout_style_name"
	value="<?php echo $layoutStyleName;?>">
<div class="tip241 tooltips tipdiv" style="color: #663300;z-index: 2;">
<!-- <div id="layout_box">
<table style="color: #663300;">
	<tbody>
		<tr>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/Layout_basic_left.png"> <br>
			<input type="radio" name="RlayoutH" value="left" <?php if($sideBarPosition == "left")echo 'checked=""';?>>Left</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/Layout_basic_right.png"> <br>
			<input type="radio" name="RlayoutH" value="right" <?php if($sideBarPosition == "right")echo 'checked=""';?> >Right</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/Layout_basic_none.png"> <br>
			<input type="radio" name="RlayoutH" value="none"  <?php if($sideBarPosition == "none")echo 'checked=""';?> >None</div>
			</label></div>
			</td>
		</tr>
		<tr style="display: none;"><?php //item 显示布局待开发?>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_small.png"> <br>
			<input type="radio" name="RlayoutM" value="LS" checked="">Below Small

			</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_small_right.png"> <br>
			<input type="radio" name="RlayoutM" value="LP">Right Small</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_medium_right.png"> <br>
			<input type="radio" name="RlayoutM" value="XP">Right Big</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_medium.png"> <br>
			<input type="radio" name="RlayoutM" value="LM">Middle</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_large.png"> <br>
			<input type="radio" name="RlayoutM" value="MM">Big</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_large_n.png"> <br>
			<input type="radio" name="RlayoutM" value="NM">New Big</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_photo_left_right.png"> <br>
			<input type="radio" name="RlayoutM" value="PT">Left Right</div>
			</label></div>
			</td>
		</tr>
		<tr style="display: none;"><?php //policy 显示布局待开发?>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_policy_tab.png"> <br>
			<input type="radio" name="RlayoutB" value="tab" <?php if($policySectionStyle == "tab")echo 'checked=""'?>>Tab</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_policy_block.png"> <br>
			<input type="radio" name="RlayoutB" value="block"  <?php if($policySectionStyle == "block")echo 'checked=""'?>>Block</div>
			</label></div>
			</td>
			<td>
			<div class="layout_box_div_css" style="clear: both;"><label>
			<div style="margin: 0px auto; text-align: center;"><img width="100"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/layout_policy_fullwidth.png"> <br>
			<input type="radio" name="RlayoutB" value="extended"  <?php if($policySectionStyle == "extended")echo 'checked=""'?>>Extended</div>
			</label></div>
			</td>
		</tr>
		<tr>
			<td style=""><input id="gochanget" type="button" name="Go"
				class="btn_01" value="Confirm"> <input type="button" name="Go"
				class="goCancel btn_01" value="Cancel"></td>
		</tr>
	</tbody>
</table>
</div> -->
</div>
</div>
</div>
</form>




<div id="outhtml">


<style type="text/css">
#mmtable {
	width: 850px;
}

#mmtable .cimg {
	text-align: center;
}

.cimg {
	
	
	text-align: center;
}

#mmtable .mmtabletext {
	margin-left: 30px;
	line-height: 1;
}

#pttable tr td {
	width: 50%;
	vertical-align: middle;
}

#pttable tr td div {
	margin: 0px 30px 0px 30px;
}

.poster_pic {
	max-width: 800px;
}

.ItemSpecificstableALL tr td:first-child,.AttributestableALL tr td:first-child
	{
	min-width: 110px;
}

.ItemSpecificstableALL tr td:last-child,.AttributestableALL tr td:last-child
	{
	min-width: 110px;
}

#policy_box1_text,#policy_box2_text,#policy_box3_text,#policy_box4_text,#policy_box5_text,#policy_bot_text
	{
	overflow: hidden;
}

.hotitemtitle {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	width: 100px;
	height: 15px;
}

.hotitemli {
	margin: auto;
	margin-top: 2px;
	font-size: 12px
}

.hotitemli div,.hotitemli p {
	font-size: 12px
}

.hotitemli td {
	vertical-align: middle;
	padding: 2PX;
}

#smail_pic_box {
	margin-left: 4px;
}

.abright {
	display: inline-block;
	float: right;
	text-align: left;
	margin: 2px;
}

.ableft {
	display: inline-block;
	float: left;
	text-align: left;
	margin: 2px;
}

.abcenter {
	display: block;
	width: 100%;
	margin: auto;
	text-align: center;
	margin: 2px;
}

.dbbox {
	margin-bottom: 10px;
	border: 1px solid transparent;
	display: inline-block;
	overflow: hidden;
	vertical-align: top;
	border-radius: 10px;
	transition: box-shadow .25s, min-height .35s;
	-moz-transition: box-shadow .25s, min-height .35s;
	-webkit-transition: box-shadow .25s, min-height .35s;
	transition-delay: box-shadow .75s;
	-moz-transition-delay: box-shadow .75s;
	-webkit-transition-delay: box-shadow;
}

.dbbox:hover{
	border: 1px solid #f0f0f0;
	border-radius: 10px;
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	z-index: 5;
}


.cbp {
	display: inline-block;
	vertical-align: top;
	margin-right: 5px;
}

#Below_All_Product_Photo,#Below_All_Product_Posters,#above_product_Photo,.MM_h_desc,#desc_html,#feedback_html,#poster_html,#policy_html,#policy_html
	{
	width: 800px;
	margin: auto;
	word-break: break-word;
}

.mm5 {
	margin: 3px
}

.needpadding {
	padding-top: 5px;
	padding-bottom: 5px;
}

/*#menu {*/
	/*padding-left: 3px;*/
/*}*/

#right1080 {
	max-width: 880px;
}

#Attributestable {
	margin-top: 6px;
}

#Attributestable td:first-child {
	text-align: left;
}

#Attributestable td {
	padding: 3px;
	border: px;
}

#ItemSpecificstable {
	margin-top: 6px;
}

#ItemSpecificstable td:first-child {
	text-align: left;
}

#ItemSpecificstable td {
	padding: 3px;
	border: px;
}

.navitemc {
	margin: 3px;
}

.mcenter {
	width: 100%;
	margin: auto;
}

.outer {
	width: 100%;
	text-align: center;
}

.inner {
	display: inline-block;
}

#logo {
	clear: both;
	text-align: center;
}

.navitemitem {
	color: 
}

.top3px {
	padding-top: 2px;
}

#smail_pic_box {
	
}

#layout_type {
	width: 100%;
}

.m_p {
	max-width: 786px;
}

#mobilebox .m_p {
	width: 100%;
	max-width: 786px;
}

.mobpovinfo img {
	display: none;
}

#mobilefooter,#mobilebox {
	display: none
}

#mobilebox {
	width: 0
}

#promotion_html {
	
}

#sample2_graphic_setting_Shop_Name_Banner {
	position: relative;
}

.shopsubnameaddon {
	position: absolute;
	font-family: ;
	font-size: px;
	color: ;
	top: px;
	left: px;
}

.shopnameaddon {
	position: absolute;
	font-family: Impact !important;
	font-size: 75px;
	color: #4c4c4c;
	top: 10px;
	left: 25px;
}

.mpicbanner .shopnameaddon {
	font-size: 37.5px;
}

.settop {
	height: 35px;
}

.topsuname {
	font-weight: bold;
}

.catbutton {
	cursor: pointer;
	margin: 3px;
	border-radius: 2px;
	BORDER: #555 1px solid;
	text-shadow: 0 1px 1px rgba(0, 0, 0, .3);
	box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
	FONT-SIZE: px;
	min-width: 4em;
	height: 20px;
	COLOR: ;
	background-color: ;
	font-family: ;
}

.catbutton:hover {
	background-color: ;
}

.layoutborder {
	background-color: ;
	top: -3px;
	margin-bottom: -3px;
	position: relative;
	z-index: 0;
	border-radius: 5px;
	text-align: left;
}

.layoutborder {
	border: px;
}

.tdescexttd {
	text-align: inherit !important;
}

.gboxout {
	background-color: ;
	font-family: ;
	font-size: px;
	color: ;
	position: relative;
	z-index: 1;
	padding-left: 10px;
	padding-top: 2px;
	padding-bottom: 2px;
	border-top-right-radius: 5px;
	border-top-left-radius: 5px;
}

.gbox {
	font-family: ;
	font-size: px;
	color: ;
}

.Cattop ul li:hover a {
	color: ;
	text-decoration: none;
}

.widthset850 {
	max-width: 850px
}

.widthset160 {
	max-width: 160px
}

#floata {
	float: right;
}

#c_box {
	clear: both;
}

.soout {
	display: none;
	position: fixed;
	width: 180px;
	height: 320px;
	background: rgba(255, 255, 255, 0.9);
	padding: 10px;
	border-radius: 10px;
	border: 1px solid #9c9c9c;
}

.soout img {
	height: 180px !important;
	width: 180px !important;
	border: 1px solid #9c9c9c;
}

.soout .hotitemtitle {
	overflow: visible !important;
	width: 170px !important;
	white-space: normal;
	height: inherit;
	max-height: 110px;
}

#subbody {
	border-radius: 10px;
	box-shadow: 4px 4px 3px rgba(158, 158, 158, 0.5);
	line-height: 1.5;
	overflow: hidden;
	background-color: #ffffff;
	border: 6px solid #999999;

}

#subbody #big_pic div,#subbody #smail_pic_box div,.imgdiv,.product_photo_need_hide,.product_photo_need_hide div
	{
	line-height: 0;
}

#smail_pic_box img {
	margin: 2px;
}

#mobilebox {
	background-color: #ffffff;
	<!--border: 5px solid #999999;-->
	padding: 5px 5px 5px 5px;
}

.overf {
	overflow: hidden;
	position: relative;
}

#menubar {
	height: 25px;
}

.catnbwidthset {
	max-width: 800px;
}

.menurow {
	height: 100%;
	float: left;
	width: 152px;
	position: relative;
	font-size: 12px;
	color: #f7f7f6;
	font-family: Arial;
}

.menurow a {
	position: relative;
	top: 20%;
}

.menurow a:link,.menurow a:visited,.menurow a:hover {
	font-family: Arial;
	color: #f7f7f6;
	text-decoration: none;
}

.menuright {
	border-right: #f7f7f6 1px solid;
}

#menudisplay {
	position: relative;
	height: 25px;
	width: 850px;
	/*
	top: -5px;
	left: -2px;
	*/
	background-color: rgb(51, 51, 51);
}

.overf {
	overflow: hidden;
}

#big_smail_pic {
	clear: both;
	padding-left: 10px;
	padding-top: 10px
}

.navpic {
	padding-top: 2px;
	padding-bottom: 2px;
}

#feedback_html {
	text-align: left;
	border-radius: 10px;
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
}

.mobpovinfo {
	background-color: ;
}

.policy_box {
	background-color: ;
	border-radius: 10px;
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	border: 6px none #ffffff;
}

#poster_html {
	text-align: center;
	clear: both;
	width: 800px;
}

#policy_box1 {
	text-align: left;
	margin-top: 5px;
}

#policy_box2 {
	text-align: left;
	margin-top: 5px;
}

#policy_box3 {
	text-align: left;
	margin-top: 5px;
}

#policy_box4 {
	text-align: left;
	margin-top: 5px;
}

#policy_box5 {
	text-align: left;
	margin-top: 5px;
}

.poster_pic {
	padding-top: 3px;
}

#rmpic {
	position: relative;
}

#rmpic_l {
	position: absolute;
	left: 0px;
	top: 50%;
	filter: alpha(opacity = 40);
	opacity: 0.4;
	z-index: 9999;
	display: none;
}

#rmpic_r {
	position: absolute;
	right: 0px;
	top: 50%;
	filter: alpha(opacity = 40);
	opacity: 0.4;
	z-index: 9999;
	display: none;
}

.poster_pic img {
	max-width: 786px;
}


.descdiv {
	margin-right: auto;
	margin: 6px 0px 0px 0px;
	padding: 0px;
	font-size: 12PX;
	font-family: Arial;
	color: #999999;
}

.descdiv ul {
	padding: initial;
}

#linkId {
	display: inline-block;
}

#tabscontent {
	text-align: left;
}

#desc_html #desc {
	margin-top: 30PX;
}

.desc_box {
	padding-left: 5px !important;
}

.margin {
	margin-top: 5px;
	overflow: hidden;
}

.mousedown {
	cursor: pointer;
}

.toptwopx {
	padding-top: 4px;
}

.m_pic_r {
	max-width: 1100px;
}

.subtitle {
	line-height: 1.5;
	padding-top: 30px;
	padding: 10px;
	text-align: center;
	color: #999999;
	font-family: Impact;
	font-size: 20PX;
}

#mobile_subtitle {
	text-align: center;
	color: #999999;
	font-family: Impact;
	font-size: 20PX;
}

.tabpage {
	padding: 2px 2px 2px 2px;
	border: 6px;
	border-color: #ffffff;
	border-style: none;
	border-bottom-left-radius: 10px;
	border-bottom-right-radius: 10px;
}

#tabscontent {
	margin-bottom: 10px
}

.w180 {
	font-size: 8px;
}

#smail_pic_box {
	text-align: left;
}

#gallery {
	box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
}

.smail_pic {
	box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
	display: inline-block;
	border: 1px solid #9c9c9c;
}

.smail_pic:hover {
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
}

#policy_html {
	width: 800px;
	border-radius: 10px;
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	word-wrap: break-word;
}

.pbp {
	margin-bottom: 3px;
	border-radius: 10px;
	border: 1px solid transparent;
	vertical-align: top;
	border-radius: 10px;
	width: 180px;
	overflow: hidden;
}

.pbp:hover {
	
}

#tabscontent {
	clear: both;
}

#tabs>ul {
	margin: 0;
	padding: 0;
	font: 1em;
	list-style: none;
}

#tabs>ul>li {
	min-height: 12px;
	box-shadow: 6px 6px 5px rgba(0, 0, 0, 0.1);
	margin: 0 2px 0 0;
	padding: 7px 10px;
	display: block;
	float: left;
	color: #ffffff;
	-moz-user-select: none;
	user-select: none;
	border-top-left-radius: 4px;
	border-top-right-radius: 4px;
	border-bottom-right-radius: 0px;
	border-bottom-left-radius: 0px;
	background: #989898; /* old browsers */
	width: 138px;
	font-size: 12PX;
	font-family: Arial;
}

.hotitemli>tbody>tr {
	border-bottom: 1px dashed #E5E5E5;
}

.hotitemli>tbody>tr:last-child {
	border-bottom: 0px dashed #E5E5E5;
}

.hotitemli>tr {
	border-bottom: 1px dashed #E5E5E5;
}

.hotitemli>tr:last-child {
	border-bottom: 0px dashed #E5E5E5;
}

#tabs>ul>li:hover {
	cursor: pointer;
}

.mobpovh {
	cursor: pointer;
	background-color: #989898;
	border-top-left-radius: 15px;
	font-size: 18px;
	padding: 10px;
	margin-top: 5px;
	color: #ffffff;
}

.mobpovinfo {
	padding: 5px;
	overflow: hidden;
}

.imgapx2 {
	
}

.user_edit ul,.user_edit dl {
	*margin-right: 0px;
	padding: 0 40px;
	list-style: square;
}

.user_edit ol {
	*margin-right: 0px;
	padding: 0 40px;
	list-style: decimal;
}

.user_edit table,.user_edit td,.user_edit tr {
	border: double;
}

#Store-Search ul {
	list-style: none;
}

#tabs>ul>li.tabActiveHeader {
	background: #42443; /* old browsers */
	color: #ffffff;
	cursor: pointer;
	width: 138px;
}

.showbtn {
	display: none;
}

#Zoom-Icon {
	position: absolute;
	bottom: 0px;
	padding: 0;
	background-image: url(<?php echo \Yii::getAlias('@web') ;?>/images/ebay/template/zoom.gif);
	background-repeat: no-repeat;
	height: 25px;
	width: 25px;
	font-size: 0.75em;
	color: #A6A6A6;
	z-index: 2;
	cursor: pointer;
	right: 0px;
}

#gallery {
	position: relative;
}

.mpicbox {
	width: 100%;
	-webkit-overflow-scrolling: touch;
	overflow-y: hidden;
	overflow-x: scroll;
	margin-top: 1px;
}

.transparent {
	opacity: .2;
	-moz-opacity: 0.2
}

.nonOpaque {
	opacity: 1;
	-moz-opacity: 1
}

#zDIV_slideShow a {
	color: #000;
	background-color: #fff
}

#zDIV_slideShow {
	top: 200px !important;
	display: none;
	position: fixed;
	left: 0;
	width: 100%;
	height: 100%;
	background-position: 50% 50%;
	background-repeat: no-repeat;
	text-align: center;
	margin: 0;
	z-index: 10 /* IE Mac */
}

#zA_close {
	background: transparent !important
}

#zA_close img {
	border: 0 !important;
	position: absolute;
}

#zIMG {
	padding: 3px;
	background: #fff;
	border: 2px solid #000
}

.Cat_gbox {
	padding: 5px;
}

strong {
	font-weight: 900;
}

.ebaybtn {
	text-decoration: none;
	bottom: 2px;
	right: 2px;
	position: fixed;
	background: #808A0E;
	display: inline-block;
	text-align: center;
	padding: 10px 5%;
	color: #fff;
	text-decoration: none;
	font-weight: bold;
	line-height: 1;
	font-family: 'Helvetica Neue', 'Helvetica', Arial, Verdana, sans-serif;
	cursor: pointer;
	border: none;
	outline: none;
	-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	text-shadow: 0 -1px 1px rgba(0, 0, 0, 0.28);
	border: 1px solid #808A0E;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	border-radius: 4px;
	font-size: 18px;
	z-index: 5;
}

.ebaybtnbuy {
	background: #3525F3;
	right: 120px;
}

.showbtn {
	text-decoration: none;
	bottom: 2px;
	left: 2px;
	position: fixed;
	background: red;
	display: inline-block;
	text-align: center;
	padding: 11px 5%;
	color: #fff;
	text-decoration: none;
	font-weight: bold;
	line-height: 1;
	font-family: 'Helvetica Neue', 'Helvetica', Arial, Verdana, sans-serif;
	cursor: pointer;
	border: none;
	outline: none;
	-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5);
	text-shadow: 0 -1px 1px rgba(0, 0, 0, 0.28);
	border: 1px solid red;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	border-radius: 4px;
	font-size: 18px;
	z-index: 5;
}

#mobilefooter {
	bottom: 0;
	left: 0px;
	position: fixed;
	background-color: rgba(0, 0, 0, 0.3);
	width: 100%;
	height: 35px;
	z-index: 4;
	padding: 5px
}

#mobilefooter a {
	
}

.catline {
	cursor: pointer;
}

.cathide {
	display: none;
}

#popupbox {
	display: none;
	position: fixed;
	background: none;
	width: 100%;
	height: 100%;
	z-index: 999;
	overflow: hidden;
	top: 0px;
	left: 0px;
}

#oimg {
	position: absolute;
	z-index: -1;
	top: 0;
	left: 0;
	background: #000000;
	width: 100%;
	height: 100%;
	filter: alpha(opacity = 50);
	opacity: .5;
}

#ct2:hover #oimg {
	opacity: 1;
}

#ct2 {
	position: relative;
	display: inline-block;
}

#popup_img {
	max-width: 1000px;
	max-height: 1000px;
}

#popup_c {
	position: absolute;
	right: 0px;
	top: 0px;
	cursor: pointer;
}

#Bwidht {
	vertical-align: top;
}

.mpicbanner .shopsubnameaddon {
	left: 2px;
}

.mpicbanner .shopsubnameaddon {
	top: inherit;
}

.mpicbanner .shopsubnameaddon {
	bottom: 8px;
}

.mpicbanner .shopnameaddon {
	top: 15px;
}

.mpicbanner .shopnameaddon {
	left: 2px;
}
.product_layout_left{
	width: 400px;
}
.product_layout_right{
	width:800px;
}
</style>
<div id="popupbox">
<div id="oimg"></div>
<center>
<div id="ct2"><a id="popup_img_a" href="" target="_blank"><img
	id="popup_img" src=""></a> <a id="popup_c"><img class="popup_c_img"
	src="<?php echo \Yii::getAlias('@web') ;?>/images/ebay/template/close_button.png"></a></div>
</center>
</div>


<style>
.gbox {
	text-decoration: none;
	color:#aad4ff;
}

.gboxout {
	padding: 5px 5px 5px 5px;
	clear: both;
}

.Cat-List {
	position: relative;
	padding: 3px;
	background-color: ;;
	width: 170px;
}

.Cat-List:hover {
	background-color: ;;
}

.Cat-List a:hover {
	color: 
}

.Cattop {
	list-style: none;
}

.lv1a {
	font-family: ;
	font-size: px;
	color: ;
	width: 160px;
	text-decoration: none;
}

.lv2a {
	font-family: ;
	font-size: px;
	color: ;
	width: 160px;
	font-weight: normal;
	text-decoration: none;
}

#menudisplay {
	width: 1080px
}

.widthset850 {
	max-width: 1080px
}

#mmtable {
	width: 887px !important;
}
</style>

<style type="text/css" media="screen">
#Store-Search {
	
}

#promotion_html {
	margin: 0 auto;
	padding-top: 3px
}

#big_pic {
	float: left;
}

#logo {
	clear: both;
	text-align: center;
}

.m_desc_details {
	width: 100%;
}

#desc_header {
	font-size: 20px;
}

#Bwidht {
	/*width: 410px;*/
}

#policy_left {
	float: left;
	width: 400PX;
}

#policy_right {
	float: left;
	width: 90PX;
}

.policy_box {
	border-radius: 10px;
}

#feedback_img {
	text-align: center;
}

.margin {
	margin: 5px 5px 5px 5px;
}

#subbody {
	width: 1080px;
	padding-bottom: 40px;
	margin-bottom: 40px;
}


.menu{
	display: none;
}
.layout_left .menu{
	display: block;
	width:180px;
	vertical-align: top;
	float: left;
	margin-right: 50px;
	height: 100%;
}
.layout_left .main{
	float: left;
}
.layout_right .menu{
	display: block;
	width:180px;
	vertical-align: top;
	float: right;
	margin-right: 50px;
	height: 100%;
}
.layout_right .main{
	float: left;
}

.modal-content{
	width:800px;
	
}

.modal-content{
	width:800px;
	
}

.product_layout_right .desc_word{
	text-align: center;
	padding-top: 15px;
}
.product_layout_center{
	width:400px;
	left:0px;
	right:0px;
	top:0px;
	bottom:0px;
	margin-left: auto;
	margin-right: auto;
}
.shutdown{
	display: none;
}
.active{
	display: block;
}
</style>
<!-- <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      	<div class="row">
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio" id="radio" value="option" checked="checked"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio" id="radio" value="option"></p>
		      </div>
		    </div>
		  </div>
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio" id="radio" value="option"></p>
		      </div>
		    </div>
		  </div>
		</div>
		 <div class="row">
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1" checked="checked"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption">
		        <p><input type="radio" name="radio1" id="radio1" value="option1"></p>
		      </div>
		    </div>
		  </div>
		</div>
    
      <div class="row">
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption"
		        <p><input type="radio" name="radio2" id="radio" value="option" checked="checked"></p>
		      </div>
		    </div>
		  </div>
		   <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption"
		        <p><input type="radio" name="radio2" id="radio" value="option"></p>
		      </div>
		    </div>
		  </div>
		  <div class="col-sm-6 col-md-2">
		    <div class="thumbnail">
		      <img src="/images/ebay/template/layout_normal_highlighed_small.png" >
		      <div class="caption"
		        <p><input type="radio" name="radio2" id="radio" value="option"></p>
		      </div>
		    </div>
		  </div>
	  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary">确定</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
      </div>
    </div>
  </div>
</div> -->

<div id="mobilephone">
<div id="mobilebox">
<div class="desc_word">
<div class="desc_details m_desc_details desc_box"
	style="width: 100%; padding: 0;">
<div class="subtitle" style="font-size: 20px;">200 Burgundy Silky Satin
Wedding Favor Gift Bag 10x14cm</div>
<ul>
	<li>Luxury <b>satin</b> gift bags</li>
	<li>High quality silky satin used</li>
	<li>Ideal for jewellery pouches or wedding favour bags</li>
	<li>Also available in more color choices, please see our chart below</li>
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
</div>
</div>
<br>
</div>
</div>
<div id="subbody" name="bo1" class="C_S_A3">
<div id="header_html">
<center>
<div class="overf" id="sample2_graphic_setting_Shop_Name_Banner">
<center>
<p class="shopnameaddon"></p>
<p class="shopsubnameaddon"></p>
<img class="sampleimg widthset850"
	id="sample_graphic_setting_Shop_Name_Banner"
	src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/graphic_setting_Shop_Name_Banner_sample_grey.png"></center>
</div>
</center>
<center>
<table>
	<tbody>
		<tr>
			<td>
			<center>
			<div id="menudisplay"><img border="0"
				src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/menu_bar_sample_grey_1080.jpg"></div>
			</center>
			</td>
		</tr>
	</tbody>
</table>
</center>
</div>
<div  id="layout_type" class="<?php if(isset($switchType)){echo $switchType ;}else{echo 'layout_left';}?>">
	<div>
		<div>
			<?php if($sideBarPosition == "left"):?>
			<div class="menu">
				<?php echo $this->render('sideBar',  array("sideBarInit"=>true,"itemInfo"=>$itemInfo,"newListItem"=>$newListItem)); ?>
			</div>
			<div class="main">
				<?php // promotion_html 是notice banner 内容 ?>
				<div id="promotion_html">
				<div class="overf" id="sample2_graphic_setting_Notice_Banner">
				<center><img class="sampleimg catnbwidthset"
					id="sample_graphic_setting_Notice_Banner"
					src="<?= \Yii::getAlias('@web') ?>/images/ebay/template/graphic_setting_Notice_Banner_sample_grey.png"></center>
				</div>
				</div>
				<?php echo $this->renderAjax('itemContent', array("showDemo"=>true,"initDemo"=>true,"itemInfo"=>$itemInfo,"productType"=>$productType)); ?>
				<div id="policy_html">
					<?php echo $this->renderAjax('policyView', array());?>
				</div>
				<div id="feedback_html">
					<?php echo $this->renderAjax('feedbackView', array());?>
				</div>
				</div>
			</div>
			<?php endif;?>
		</div>
	</div>
</div>
</div>
<div id="logo">
</div>
<div id="mobilefooter"><a href="#" class="showbtn" id="showbtnd"
	onclick="showdesktop();">Desktop</a> <a href="#" id="showbtnm"
	class="showbtn" onclick="showmobile();">Mobile</a> <a id="btn_buy"
	class="ebaybtn ebaybtnbuy" title="" style="display: none;">Buy</a> <a
	id="btn_bin" class="ebaybtn" title="" style="display: none;">Watch</a>
</div>
</div>

<style>
#gpw {
  color: #666666;
  position: relative;
  top: 10px;
}

</style>
<div id="Loading" class="Loadingdialog ui-dialog-content ui-widget-content" style="display: none; width: auto; min-height: 0px; height: 61px;" scrolltop="0" scrollleft="0">
  <img style=" vertical-align: middle;" src="/images/loading.gif" alt=""><a id="gpw">Loading , please wait..</a>
</div>

<script>

var wasLoaded=0;
function onload(){
	if(!wasLoaded)window.parent.CKEDITOR.tools.callFunction(790,window);
	wasLoaded=1;

}
document.addEventListener("DOMContentLoaded", onload, false );

function ready_test() {
	$(".CorP").each(function () {
		if($(this).val()=='Color'){
			$('#'+$(this).attr('name')+'_c').show();
			$('#'+$(this).attr('name')+'_p').hide();
		}else{
			$('#'+$(this).attr('name')+'_p').show();
			$('#'+$(this).attr('name')+'_c').hide();
		}
	});
	$(".mouse").each(function () {
		if($(this).val()=='Color'){
			$("#cat_bkgd_type_c1").show();
			$("#cat_bkgd_type1_p1").hide();
		}else{
			$("#cat_bkgd_type1_p1").show();
			$("#cat_bkgd_type_c1").hide();
		}
	});
	if($('#infobox_bkgd_type').val() == 'Color' ){
		$('.layoutborder').css("background-image", "url('')");
		$('.layoutborder').css({'background-color': findcolor($('#infobox_bkgd_color').val())});
	}else{
		$('.layoutborder').css("background-image", "url(" + $('#infobox_bkgd_pattern').val() + ")");
	}
	$('#mobilebox').hide("fast");
	$('#mobilephone').hide("fast");		
	$('.shopnameaddon').html($('#shop_name_text').val());
	$('.shopsubnameaddon').html($('#shop_name_sub_text').val());
	$('#policy_bot_text').attr('contenteditable','true');
	$('#policy_box1_text').attr('contenteditable','true');
	$('#policy_box2_text').attr('contenteditable','true');
	$('#policy_box3_text').attr('contenteditable','true');
	$('#policy_box4_text').attr('contenteditable','true');
	$('#policy_box5_text').attr('contenteditable','true');
	if ( <?php echo $tempalteId;?> == 0) {
		
		policy(false);
		ckinline('policy_bot_text');
		
		var findc = $('#layout_style_name').val().indexOf("_t");
		if (findc > -1) {
			ready_tab();				
		} 
		if ($('#tabpage_2') != '') {
			ready_height();
		}
		
		$("#linkId").attr("href", "<?php echo \Yii::getAlias('@web') ;?>/images/ebay/template/sample/1.jpg");
		// if($(window).width() <= 1600 ){
		// 	$('#subbody').css('float', "right");
		// }else{
		// 	$('#subbody').css('float', "none");
		// }
		if (window.innerHeight)
			winHeight = window.innerHeight;
		else if ((document.body) && (document.body.clientHeight))
			winHeight = document.body.clientHeight;
		// $('.P_height').css('height', winHeight - 320);
		infochange();
		//window.clearInterval(aint);


		$('.policy_box').css('border', $('#eb_tp_clr_infobox_border_size').val() + "px " + $('#eb_tp_clr_infobox_border_style').val() + " " + findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
		$(".Loadingdialog").dialog("close");
		// $( ".D_Pre_info" ).click();
	} else {
		$('.showall').click();// template_id 等于0 不读入样品item信息
		policy(true);
		ckinline('policy_bot_text');
		infochange();
		$(".Loadingdialog").dialog("close");
		// ii++;
		// if(ii === 10){
		//location.reload();
		// }

	//aint=self.setInterval(function(){ ready_test();},2000);
	//window.startaction = setTimeout('ready_test()', 1500);
	}
}
</script>
<script type="text/javascript">			
var ajaxnum = 0;
	$( document ).ajaxComplete(function( event, xhr, settings ) {
		if ( settings.url.indexOf("get-partial-template?partial=policyView") > 0 ) {
			setTimeout( function(){
				if ($('#tabpage_2').css('display') != 'none') {
					$('#tabpage_2').hide("fast");
					$('#tabpage_3').hide("fast");
					$('#tabpage_4').hide("fast");
					$('#tabpage_5').hide("fast");
				}
			}, 1000);	
		}
		 if ( ajaxnum > 8) {
			//last update		
			
			if(''!=''){
				$( "#tool_tab" ).tabs({selected: ''});
				ajaxnum = -9999;
			}
			if($('#Title_ONNOFF').val() == 'ON'){
					$( "#TitleONNOFF" ).show("fast");
					$( ".subtitle" ).show("fast");
				}else{
					$( "#TitleONNOFF" ).hide("fast");
					$( ".subtitle" ).hide("fast");
				}
				
				if($('#product_photo_ONNOFF').val() == 'ON'){
					$( ".product_photo_need_hide" ).show("fast",function(){
						$(this).css({
							overflow:'initial'
						})
					});
				}else{
					$( ".product_photo_need_hide" ).hide("fast",function(){
						$(this).css({
							overflow:'initial'
						})
					});
				}			
		}
		infochange();
		startchange(false);
		shop_name();
		ajaxnum++;
	});

	$(window).resize(function() {
		// $('.P_height').css('height', $(window).height()-320);
		
		if($(window).width() <= 1600 ){
			$('#subbody').css('float', "right");
			$('#outhtml').css('width', "inherit");
			
			
		}else{
			$('#subbody').css('float', "none");
			$('#outhtml').css('width', "1080px");
		}
	});
	CKEDITOR.on('instanceCreated', function (event) {
		var editor = event.editor,
		element = editor.element;
	});
$(function(){
	$("#Loading").dialog({
		position: { my: "center top", at: "center bottom", of: $('#sample_graphic_setting_Shop_Name_Banner') },
		width: 'auto',
		height: 61,
		modal: true
	});
	
	var statesdemo = {
		state0: {
			title: 'Overall Theme Style',
			html:'<center><br><br><table class="in350"><tbody><tr><td align="center"><table><tbody><tr><td align="center"><label><input type="radio" name="new_sam" value="Default" checked="">Default</label></td><td align="center"></td></tr></tbody></table></td><td align="center"><table><tbody><tr><td align="center"><label><input type="radio" name="new_sam" value="Red">Red</label></td><td align="center"></td></tr></tbody></table></td><td align="center"><table><tbody><tr><td align="center"><label><input type="radio" name="new_sam" value="Blue">Blue</label></td><td align="center"></td></tr></tbody></table></td></tr></tbody></table><br>* You can change everything later</center>',
			buttons: {  'Next': 1 },
			focus: 1,
			submit:function(e,v,m,f){ 
				e.preventDefault();
				$.prompt.goToState('state1');
						
			}
		},
//		state1: {
//			title: 'Side Bar Position Setting',
//			html:'<center><br><br><table><tr><td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_left.png" alt="create info"  width="100"><br><input type="radio" name="new_nav" value="left" checked>Left</label></td>'+  
//				'<td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_right.png" alt="create info" width="100"><br><input type="radio" name="new_nav" value="right">Right</label></td>'+
//				'<td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_none.png" alt="create info"  width="100"><br><input type="radio" name="new_nav" value="none">None</label></td></tr></table></center>',
//			buttons: {Back: -1, Next: 1 },
//			
//			//focus: "input[name='fname']",
//			submit:function(e,v,m,f){ 
//				if(v==1) $.prompt.goToState('state2')
//				if(v==-1) $.prompt.goToState('state0');	
//				e.preventDefault();
//			}
//		},
//		state2: {
//			title: 'Product Photo Display Pattern',
//	html:'<center>You can choose how the Product Photos show in the HTML Description of a listing.<br><br><table><tr><td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_small.png" alt="create info"  width="120"><br><input type="radio" name="new_Photo" value="_LS" checked><br>Below Small</label></td>'+ 
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_medium.png" alt="create info" width="120"><br><input type="radio" name="new_Photo" value="_LM"><br>Middle</label></td>'+ 
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_small_right.png" alt="create info" width="120"><br><input type="radio" name="new_Photo" value="_LP"><br>Right Small</label></td>'+
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_medium_right.png" alt="create info" width="120"><br><input type="radio" name="new_Photo" value="_XP"><br>Right Big</label></td></tr><tr>'+
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_large.png" alt="create info"  width="120"><br><input type="radio" name="new_Photo" value="_MM"><br>Big</label></td>'+
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_large_n.png" alt="create info"  width="120"><br><input type="radio" name="new_Photo" value="_NM"><br>New Big</label></td>'+
//				'<td align="center" style=" vertical-align: top; "><label class=""><img src="/images/ebay/template/layout_photo_left_right.png" alt="create info"  width="120"><br><input type="radio" name="new_Photo" value="_PT"><br>Left Right</label></td>'+
//				'</tr></table></center>',
//
//			buttons: { Back: -1, Next: 1 },
//			//focus: ":input:first",
//			submit:function(e,v,m,f){ 
//
//				if(v==1) $.prompt.goToState('state3')
//				if(v==-1) $.prompt.goToState('state1');
//				e.preventDefault();
//			}
//		},
//		state3: {
//			title: 'Footer Policy Section Style',
//			html:'<center><br><br><table><tr><td align="center"><label class="diff3"><img src="/images/ebay/template/layout_policy_tab.png" alt="create info"  width="100"><br><input type="radio" name="new_policy" value="_Tab" checked>Tab</label></td>'+  
//				'<td align="center"><label class="diff3"><img src="/images/ebay/template/layout_policy_block.png" alt="create info" width="100"><br><input type="radio" name="new_policy" value="_Block">Block</label></td>'+
//				'<td align="center"><label class="diff3"><img src="/images/ebay/template/layout_policy_fullwidth.png" alt="create info"  width="100"><br><input type="radio" name="new_policy" value="_Extended">Extended</label></td></tr></table></center>',
//			buttons: { Back: -1, Done: 1 },
//			submit:function(e,v,m,f){ 	
//				if(v==1){
//				if(f.new_sam == 'Default'){
//					window.location = "<?php echo \Yii::getAlias('@web'); ?>/listing/ebay-template/edit?template_id=0&style_id="+f.new_nav+f.new_Photo+f.new_policy;
//				}else{
//					window.location = "<?php echo \Yii::getAlias('@web'); ?>/listing/ebay-tlisting/ebay-templateemplate_id=0&style_id="+f.new_nav+f.new_Photo+f.new_policy+"&theme="+encodeURIComponent(f.new_sam);}
//				}
//				e.preventDefault();
//				if(v==1) $.prompt.close();
//				if(v==-1) $.prompt.goToState('state2');
//			}
//		},
		state1: {
			title: 'Side Bar Position Setting',
			html:'<center><br><br><table><tr><td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_left.png" alt="create info"  width="100"><br><input type="radio" name="new_nav" value="left" checked>Left</label></td>'+  
				'<td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_right.png" alt="create info" width="100"><br><input type="radio" name="new_nav" value="right">Right</label></td>'+
				'<td align="center"><label class="diff3"><img src="/images/ebay/template/Layout_basic_none.png" alt="create info"  width="100"><br><input type="radio" name="new_nav" value="none">None</label></td></tr></table></center>',
				buttons: { Back: -1, Done: 1 },
				submit:function(e,v,m,f){ 	
					if(v==1){
						if(f.new_sam == 'Default'){
							window.location = "<?php echo \Yii::getAlias('@web'); ?>listing/ebay-template/edit?template_id=0&style_id="+f.new_nav/*+f.new_Photo+f.new_policy*/;
						}else{
							window.location = "<?php echo \Yii::getAlias('@web'); ?>listing/ebay-template/edit?template_id=0&style_id="+f.new_nav/*+f.new_Photo+f.new_policy*/+"&theme="+encodeURIComponent(f.new_sam);}
					}
					e.preventDefault();
					if(v==1) $.prompt.close();
					if(v==-1) $.prompt.goToState('state0');
				}
		},
	};
	if( '<?php echo isset($_REQUEST['template_id']) ? 'yes' : 'no';?>' === 'no'){ //if hasn't template id 

		// $.prompt(statesdemo, 
		// {
			// callback:function() {
				window.location = "<?php echo \Yii::getAlias('@web'); ?>/listing/ebay-template/edit?template_id=0";
			// }
		// });
	}
	$("#gochanget").click(function () {
		var layout = $('input[name=RlayoutH]:checked').val()+'_'+$('input[name=RlayoutM]:checked').val()+ '_'+$('input[name=RlayoutB]:checked').val();
		$('#layout_style_name').val(layout+'.php');
		window.location = global.baseUrl+"listing/ebay-template/edit?template_id=<?php echo $tempalteId;?>&style_id="+ layout;
		//return false;
	});

	$('#showhide').click(function () {
		$("#bigb").css('top', 50);
		$("#bigb").css('left', 10);
		$('#bigb').show("fast");
		})
	$('#hideall').click(function () {
		$('#bigb').hide("fast");
	})
	
	var data = {
		'theme':'',
		'layout': $('#layout_style_name').val(),
	    'template_id': <?php echo $tempalteId;?>        
	};
	$.ajax({
	    type: 'POST',
	    data: data,
	    dataType: 'html',
	    url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=toolBar',
	    success: function (data) {
	        $('#tool').html(data).ready(function () {
	            FS_Auto();
	            $('input:radio[name="RMenubarstyle"][value="' + $('#menu_bar').val() + '"]').prop('checked', true);
	            $("input[name='RMenubarstyle']").click(function () {
	                $('#menu_bar').val($(this).val());
	            });
				// $('.infodetclass').bind('click change keyup keydown', function () {
					// infochange();
					// $( ".Pre_info" ).click();
				// })
				$('.CorP').bind('click change', function () {// background color / partern switching
				if($(this).val()=='Color'){
				$('#'+$(this).attr('name')+'_c').show();
				$('#'+$(this).attr('name')+'_p').hide();
				}else{
				$('#'+$(this).attr('name')+'_p').show();
				$('#'+$(this).attr('name')+'_c').hide();
				}
	                
	            });
	            $('.mouse').bind('click change', function () {// background color / partern switching
				if($(this).val()=='Color'){
				$("#cat_bkgd_type_c1").show();
				$("#cat_bkgd_type1_p1").hide();
				}else{
				$("#cat_bkgd_type1_p1").show();
				$("#cat_bkgd_type_c1").hide();
				}
	                
	            });

	          
	            $('#eb_tp_mobile_desc_limargin').bind('keyup change', function () {
	                $('#mobile_details li').css({
	                    'margin-top': $('#eb_tp_mobile_desc_limargin').val() + 'px'
	                });
	            })
	            $('#eb_tp_tab_Font_style').bind('keyup change focusout', function () {
	                $('#tabHeader_1').css("font-family", $('#eb_tp_tab_Font_style').val());
	                $('#tabHeader_2').css("font-family", $('#eb_tp_tab_Font_style').val());
	                $('#tabHeader_3').css("font-family", $('#eb_tp_tab_Font_style').val());
	                $('#tabHeader_4').css("font-family", $('#eb_tp_tab_Font_style').val());
	                $('#tabHeader_5').css("font-family", $('#eb_tp_tab_Font_style').val());
	                $('.tabActiveHeader').css("font-family", $('#eb_tp_tab_Font_style').val());
	            })
	   //          if($("#sh_ch_info_Policy1_header").val()==""){
				// 	$("#tabHeader_1").hide();
				// 	$("#policy_box1_text").hide();
				// }
				
	            $('#eb_tp_tab_Font_size').bind('keyup change', function () {
	                $('#tabHeader_1').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	                $('#tabHeader_2').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	                $('#tabHeader_3').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	                $('#tabHeader_4').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	                $('#tabHeader_5').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	                $('.tabActiveHeader').css({
	                    'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
	                });
	            })
	            
	            // shop name banner 和 notice banner 的图片 upload完会触发一个click事件跳到这里更换image source
	            $('.preview').click(function () {
					
					newString = $(this).attr('id').replace('_P', '');
	                var str = $("#" + this.title).val();
	                if (str) {
	                    $("#sample_" + this.title).show("fast");
	                    if (str.search("http") >= 0) {
	                        if (str.search("swf") >= 0) {
	                            var H = $('#'+this.title + '_HW .HW_H').val() + 'PX';
	                            var W = $('#'+this.title + '_HW .HW_W').val() + 'PX';
	                            $("#sample2_" + this.title).empty();
	                            $("#sample2_" + this.title).flash({
	                                src: $("#" + this.title).val(),
	                                'width': W,
	                                'height': H
	                            }, {
	                                version: '6.0.65'
	                            });
	
	                        } else {
	                            $("#sample_" + this.title).attr("src", $("#" + this.title).val());
								var rlink =  $("#" + this.title).val();
	                        }
	                    } else {
	                        var str = $("#" + this.title).val();
	                        if (str.search("http") >= 0) {
							
	                            $("#sample_" + this.title).attr("src", $("#" + this.name).val() + $("#" + this.title).val());
								var rlink =  $("#" + this.name).val() + $("#" + this.title).val();
	                        } else {
	                            $("#sample_" + this.title).attr("src", $("#BasedPath").val() + $("#" + $(this).attr('name')).val() + $("#" + this.title).val());
								var rlink =  $("#BasedPath").val() + $("#" + $(this).attr('name')).val() + $("#" + this.title).val();
							}
	                    }
	                } else {
	                    $("#sample_" + this.title).hide("fast");
	                }
					if('SNB' != newString){
					//$('#'+newString+'anner').hide();	
					}
	            })
	            $('.save').click(function () {
	                //save									
	                save();
	            })
	            $('#del').click(function () {
	                //del
	                DEL();
	            })
	            $('.saveas').click(function () {
	                //saveas
	                saveas();
	            })
	             
	            $('#Auto_Fill_ONNOFF').change(function () {
	                if ($('#Auto_Fill_ONNOFF').val() == 'On') {
						$('#Item_Specifcationsb').show("fast");
	                    $('.Auto_Fill_ON').show("fast");
						$('.ItemSpecificstableALL').show("fast");
						
	                } else {
					
						$('#Item_Specifcationsb').hide("fast");
	                    $('.Auto_Fill_ON').hide("fast");
						$('.ItemSpecificstableALL').hide("fast");
	                }
	
	            })
	            $('#eb_tp_mobile_true').change(function () {
	                if ($('#eb_tp_mobile_true').val() == '1') {
	                    $('#Mobile_tab').show("fast");
						
	                } else {
	                    $('#Mobile_tab').hide("fast");
	                }
	
	            })

	            $.domReady(function($el){

					$el("#previews").click(function(){
						$(".SNB").show();
					})
	            })

				// 
	     //        $('.R_UNL').change(function () {
	     //            var name = $(this).attr('name').substring(0, $(this).attr('name').length - 1);
	     //            if ($(this).val() == 'upload') {
	     //                $('.' + name + '_upload').show("fast");
	     //                $('.' + name + '_url').hide("fast");
	     //                $(".SNB").hide("fast");
	     //            } else {
						// if ($(this).val() == 'File') {
						//  $('#layout_select_'+ name).hide("fast");
						// }else{
						// 	if($(this).val() == 'Self'){
						// 		$('.imgmyself').show();
						// 		$('.imgmedia').hide();
						// 	}else{
						// 		$('.imgmedia').show();
						// 		$('.imgmyself').hide();
						// 	}
						// 	$('#layout_select_'+ name).show("fast").ready(function () {
						// 		$('#layout_select_'+ name).click();
						// 	});
						// }
	     //                $('.' + name + '_url').show("fast");
	     //                $('.' + name + '_upload').hide("fast");
	     //            }
	     //        });
	   //        	$('#myTab a').click(function (e) {
				//   e.preventDefault();
				//   $(this).tab('show');
				// });
	   //        	$('#myTab a').click(function (e) {
				//   e.preventDefault();
				//   $(this).tab('show');
				// });
				$('.shop_name').bind('click change keyup keydown focusout',function () {
					shop_name();
				});
				
				$('input:radio[name=MENU_r]').bind('click change focusout',function () {
	                $('#tb_shop_master_Setting_menu_bar').val($(this).val());
	                $('#MENU_P').click();
	            });
	            // $('input:radio[name=SNB_r]').bind('click change focusout',function () {
	            //     $('#graphic_setting_Shop_Name_Banner').val($(this).val());
	            //     $("#previews").html("<img src='"+$(this).val()+"' style='width:250px;height:100px;'/>");
	            //     $('#SNB_P').click();
	            // });
	            $.domReady(function($el){

					$el('input:radio[name=SNB_r]').bind('click change focusout',function () {
		                $('#graphic_setting_Shop_Name_Banner').val($(this).val());
		                $("#previews").html("<img src='"+$(this).val()+"' style='width:250px;height:100px;'/>");
		                $('#SNB_P').click();
		            });
	            })
	            $('input:radio[name=NB_r]').bind('click change focusout',function () {
	                $('#graphic_setting_Notice_Banner').val($(this).val());
	                $('#NB_P').click();
	            })
	            $('input:radio[name=HBP_r]').bind('click change focusout',function () {
	                $('#graphic_setting_Horizontal_Banner').val($(this).val());
	                $('#HBP_P').click();
	            })
	            $('input:radio[name=VBP_r]').bind('click change focusout',function () {
	                $('#graphic_setting_Vertical_Banner').val($(this).val());
	                $('#VBP_P').click();
	            })
	            ///////////////////////////////////////////////////////////////
	            //////////////////////////MOB CHANGE///////////////////////////
	            ///////////////////////////////////////////////////////////////
				$('input:radio[name=eb_tp_cat_Pattern_r]').bind('click change focusout',function () {
	                $('#eb_tp_cat_Pattern').val($(this).val());
	      
	            })
				$('#eb_tp_cat_Shop_Search').bind('change focusout',function () {
				if($('#eb_tp_cat_Shop_Search').val() == 'Yes'){
				$('#Store-Search').show("fast");
				}else{
				$('#Store-Search').hide("fast");
				}
				})
	            
	            
	           
	            if ('true' == 'false') {
	                $('#Mobile_buy').show("fast");
	                $('#Mobile_tab').hide("fast");
	                $('#Mobile_true').hide("fast");
	            } else {
	                $('#Mobile_buy').hide("fast");
	                $('#Mobile_tab').show("fast");
	                $('#Mobile_true').show("fast");
	            }
	            ///////////////////////////////////////////////////////////////
	            //////////////////////////MOB    END///////////////////////////
	            ///////////////////////////////////////////////////////////////
	
	            ///////////////////////////////////////////////////////////////
	            //////////////////////////DES CHANGE///////////////////////////
	            ///////////////////////////////////////////////////////////////
	           
	            $('#tb_eb_CFI_style_master_BP').change(function () {
	                if ($(this).val() == "Color") {
	                    $('#bg_P').hide("fast");
	                    $('#bg_C').show("fast");
	                    $('#subbody').css("background-image", "none");
	                    $('#subbody').css("background-color", $('input[name=eb_tp_clr_Mainbody_background]').val());
						$('#mobilebox').css("background-image", "none");
	                    $('#mobilebox').css("background-color", $('input[name=eb_tp_clr_Mainbody_background]').val());
						
	                } else {
	                    $('#bg_C').hide("fast");
	                    $('#bg_P').show("fast");
	                    $('#subbody').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
						$('#mobilebox').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
	                }
	            })
	            $('input:radio[name=tb_eb_CFI_style_master_background_Pattern_r]').bind('click change focusout',function () {
					$('#tb_eb_CFI_style_master_background_Pattern').val($(this).val());
	                $('#subbody').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
					$('#mobilebox').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
	
	            })
	           
				// $('#eb_tp_policy_Pattern').change(function () {
						// $('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
						// $('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
				// })
				$('input:radio[name=pattern_r]').bind('click change focusout',function () {
					$('#eb_tp_policy_Pattern').val($(this).val());
					$('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
					$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
				});
	            $('#tb_eb_CFI_style_policy_BP').change(function () {
	
	                if ($(this).val() == "Color") {
	                    $('#Pg_P').hide("fast");
	                    $('#Pg_C').show("fast");
	                    $('.policy_box').css("background-image", "none");
	                    $('.policy_box').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
						$('#policy_html').css("background-image", "none");
	                    $('#policy_html').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
	                } else {
	                    $('#Pg_C').hide("fast");
	                    $('#Pg_P').show("fast");
	                    $('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
						$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
	                }
	
	            })
	            $('input:radio[name=tb_eb_CFI_style_master_desc_header]').bind('click change focusout',function () {
	                $('#desc_header').css({
	                    'text-align': $('input:radio[name=tb_eb_CFI_style_master_desc_header]:checked').val()
	                });
	            })
	            $('#tb_eb_CFI_style_master_desc_details').change(function () {
	                $('.desc_details').css({
	                    'margin-right': '0'
	                });
	                $('.desc_details').css({
	                    'margin-left': '0'
	                });
	
	                $('.desc_details').css({
	                    'text-align': $('#tb_eb_CFI_style_master_desc_details').val()
	                });
	                if ($('#tb_eb_CFI_style_master_desc_details').val() == 'right') {
	                    $('.desc_details').css({
	                        'margin-left': 'auto'
	                    });
	                }
	                if ($('#tb_eb_CFI_style_master_desc_details').val() == 'left') {
	                    $('.desc_details').css({
	                        'margin-right': 'auto'
	                    });
	                }
	                if ($('#tb_eb_CFI_style_master_desc_details').val() == 'center') {
	                    $('.desc_details').css({
	                        'margin': 'auto'
	                    });
	                }
	            })
				$(".eazy_pic1").click(function(){
			  		$("#attr_t_b_div_p").show();
			  		$("#attr_t_c_div_p").hide();
				  });
			   $(".eazy_pic2").click(function(){
			  		$("#attr_t_b_div_p").hide();
			  		$("#attr_t_c_div_p").show();
			  	});
			   $(".eazy_pic3").click(function(){
			   		$("#attr_t_b_div_p").hide();
			   		$("#attr_t_c_div_p").hide();
			   });
			   $(".eazy_pic4").click(function(){
			  		$("#attr_b_b_div_p").show();
			  		$("#attr_b_c_div_p").hide();
				  });
			   $(".eazy_pic5").click(function(){
			  		$("#attr_b_b_div_p").hide();
			  		$("#attr_b_c_div_p").show();
			  	});
			   $(".eazy_pic6").click(function(){
			   		$("#attr_b_b_div_p").hide();
			   		$("#attr_b_c_div_p").hide();
			   });
			   $(".eazy_pic7").click(function(){
			  		$("#attr_b2_b_div_p").show();
			  		$("#attr_b2_c_div_p").hide();
				  });
			   $(".eazy_pic8").click(function(){
			  		$("#attr_b2_b_div_p").hide();
			  		$("#attr_b2_c_div_p").show();
			  	});
			   $(".eazy_pic9").click(function(){
			   		$("#attr_b2_b_div_p").hide();
			   		$("#attr_b2_c_div_p").hide();
			   });

				$('#c_theme').bind('click blur',function () {
	                $('.theme').css({
	                    'background': $('#c_theme').val()
	                    
	                });
	            });
	            $('#Title_f_size').bind('click blur',function () {
	                $('.theme').css({
	                    'font-size': $('#Title_f_size').val()
	                });
	            });
				$('#Title_f_align').bind('click blur',function(){
					$('.theme').css({
						textAlign:$('#Title_f_align').val()
					});
				});
				$('#Text_f_align').bind('click blur',function(){
					// console.log($('#Text_f_align').val())
					$('#Attrid2 td').css({
						textAlign:$('#Text_f_align').val()
					});
				});
				$('#Text_f_size').bind('click blur',function(){
					// console.log($('#Text_f_align').val())
					$('#Attrid2 td').css({
						'font-size':$('#Text_f_size').val()
					});
				});
				$('#attributes_border_style').bind('click blur',function(){
					$('#Attrid2 td').css({
						'border':$('#attributes_border_style').val()
					});
				});
				$('#attributes_size').bind('click blur',function(){
					$('#Attrid2 td').css({
						'border-width':$('#attributes_size').val()
					});
				});
				$('select[name="name_text_style"]').bind('click blur',function(){
					console.log($('#name_text_style').val());
					$('.theme').css({
						'font-family':$('#name_text_style option:selected').val()
					});
				});
				$('select[name="Text_f_FS"]').bind('click blur',function(){
					console.log($('#Text_f_FS').val());
					$('#Attrid2 td').css({
						'font-family':$('#Text_f_FS option:selected').val()
					});
				});
				$('input:radio[name=title_bar_style]').bind('click change focusout',function () {
			                $('#theme').val($(this).val());
							
			                $('.theme').css("background-image", "url(" + $('#theme').val() + ")");
							
			
			            });
				$('input:radio[name=alternate_background_style]').bind('click change focusout',function(){
					$('#attr_b_b').val($(this).val());
					$('.smalltext').css("background-image","url(" + $('#attr_b_b').val() + ")");
				});
				$('input:radio[name=alternate_background_style_r]').bind('click change focusout',function(){
					$('#attr_b2_b').val($(this).val());
					$('.littletext').css("background-image","url(" + $('#attr_b2_b').val() + ")");
				});
				var timeout;
				function hide() {
				    timeout = setTimeout(function () {
				        $(".tooltips").hide('fast');
				    }, 500);
				};
					$(document).on('click', ".tip" ,function (e) {
					clearTimeout(timeout);
					$(".tooltips").hide('fast');
					$("." + this.name).css({'position': 'fixed'});	
					if( e.clientY > ($(window).height() * (2 / 3) ) ){
						$("." + this.name).css({  "top": e.clientY -500 +"px", "left": e.clientX+15+"px" });
					}else{
						$("." + this.name).css({  "top": e.clientY-50+"px", "left": e.clientX+15+"px" });
					}
				    $("."+this.name).stop().show('fast');
				}).mouseout();
				$(".tooltips").mouseover(function () {
				    clearTimeout(timeout);
				}).mouseout(hide);
				$('#nbynn').bind('change focusout', function () {
					if($('#nbynn').val() == 'On'){
					$('.nbynn').show("fast");
					$('.NBanner').show("fast");
					$('#sample2_graphic_setting_Notice_Banner').show("fast");
					}else{
					$('.nbynn').hide("fast");
					$('.NBanner').hide("fast");
					$('#NBanner').hide("fast");
					$('#sample2_graphic_setting_Notice_Banner').hide("fast");
					}
					
				})
				
				$('#vpynn').bind('change focusout', function () {
					$('#graphic_setting_Vertical_Banner').val('');
					$('#VBP_P').click();
					if($('#vpynn').val() == 'On'){
					$('.vpynn').show("fast");
					}else{
					$('.vpynn').hide("fast");
					}
					
				})
				 $('#eb_tp_font_Description').bind('change focusout click', function () {	
				$('.desc_details').css("font-family", $('input[name=eb_tp_font_Description]').val());
				$('.desc_details').css("font-family", $('input[name=eb_tp_font_Description]').val());
				})
	            $('#DescriptionsfontSize').bind('change focusout', function () {						
					$('.desc_details').css({
	                    'font-size': $('#DescriptionsfontSize').val() + 'px'
	                });
	            })
	            $('#Descriptionlimargin').bind('change focusout', function () {
	                $('.descdiv').css({
	                    'margin-top': $('#Descriptionlimargin').val() + 'px'
	                });
	            })
				
	            $('#eb_tp_clr_infobox_border_size').bind('keyup change focusout', function () {
	                $('.tabpage').css('border', $('#eb_tp_clr_infobox_border_size').val() + "px " + $('#eb_tp_clr_infobox_border_style').val() + " " + findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
	                $('.tabpage').css('border-top', "0px");
	                $('.policy_box').css('border', $('#eb_tp_clr_infobox_border_size').val() + "px " + $('#eb_tp_clr_infobox_border_style').val() + " " + findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
	             })
	            $('#eb_tp_clr_infobox_border_style').bind('change focusout', function () {
					
					if($('#eb_tp_clr_infobox_border_style').val() == 'none'){
					$('.Policy_B_hide').hide("fast");
					}else{
					$('.Policy_B_hide').show("fast");
					}
					
	                $('.tabpage').css({
	                    'border-style': $('#eb_tp_clr_infobox_border_style').val()
	                });
	                $('.tabpage').css('border-top', "0px");
	                $('.policy_box').css({
	                    'border-style': $('#eb_tp_clr_infobox_border_style').val()
	                });
	            
	            })
				
				
	            $('#tb_eb_CFI_style_master_desc_list').change(function () {
	                if ($('#tb_eb_CFI_style_master_desc_list').val() == "none") {
	                    $('.desc_details ul').css({
	                        'margin-left': '0px'
	                    });
	                } else {
	                    $('.desc_details ul').css({
	                        'margin-left': '16px'
	                    });
	                }
	                $('.desc_details ul').css({
	                    'list-style': $('#tb_eb_CFI_style_master_desc_list').val()
	                });
	
	            }) 
				
				
				$(".detail").each(function () {
					$(this).attr("title", "Edit Detail");
	            });
				$(".btn_del").each(function () {
					$(this).attr("title", "Delete");
	            });
				$(".infoadd").each(function () {
					$(this).attr("title", "Add");
	            });
	            $('#eb_tp_mobile_desc_list').change(function () {
	                if ($('#eb_tp_mobile_desc_list').val() == "none") {
	                    $('#mobile_details ul').css({
	                        'margin-left': '0px'
	                    });
	                } else {
	                    $('#mobile_details ul').css({
	                        'margin-left': '16px'
	                    });
	                }
	                $('#mobile_details ul').css({
	                    'list-style': $('#eb_tp_mobile_desc_list').val()
	                });
	
	            })
	            $('#Theme_id').bind('change focusout',function () {
	                $('.gboxout').css({
	                    'background': $('#Theme_id').val()
	                });
	            });
				$('.Pre_info').click();
				// all input here 
				$('input').bind('click change focusout keydown keyup', function() { 
					if($(this).attr('name') == 'sh_ch_info_Policy1_header'){$('#tabHeader1').html($(this).val());}
					if($(this).attr('name') == 'sh_ch_info_Policy2_header'){$('#tabHeader2').html($(this).val());}
					if($(this).attr('name') == 'sh_ch_info_Policy3_header'){$('#tabHeader3').html($(this).val());}
					if($(this).attr('name') == 'sh_ch_info_Policy4_header'){$('#tabHeader4').html($(this).val());}
					if($(this).attr('name') == 'sh_ch_info_Policy5_header'){$('#tabHeader5').html($(this).val());}
				});
	            $('input:radio[name=bodyborderstyle]').bind('click change focusout', function () {
	
	                $('#subbody').css({
	                    'border-style': $('input:radio[name=bodyborderstyle]:checked').val()
	                });
					 $('#mobilebox').css({
	                    'border-style': $('input:radio[name=bodyborderstyle]:checked').val()
	                });
	
	            })
	            $('.close_x').click(function () {
	                
	                    $($(this).attr('name')).hide("slow");
	                    
	                    //$($(this).attr('name')).hide("fast");
	                    $('.upfin').hide("fast");
	                
	                return false;
	            });
				
				$( "#tool_tab > ul").accordion({
					header: "li.tab", 
					clearStyle: true ,
					collapsible: true,
					// 初始全部关闭
					// alwaysOpen:false,
					// active:-1,
					activate: function( event, ui ) {// 展开或关闭都触发
						// debugger
						// $( "#tool_tab > ul").css('padding-right','0');
						if($(ui.newPanel).length > 0){
							if($(ui.newPanel).children('div.P_height').attr('id') == "T_Mobile"){
								$('#subbody').hide("fast");
								$('#mobilebox').css('width','auto');
								$('#mobilebox').show("fast").ready(function () {
									$('#mobilephone').show("fast").ready(function () {
										$('#outhtml').height(400);
									}).ready(function () {								
										$('.m_Pre_info').click();
									})
								});
							
							}else{
								$('#mobilebox').hide("fast");
								$('#mobilephone').hide("fast");
								$('#subbody').show("fast");
								$('#outhtml').height(800);
							}
						}else{
							// $( "#tool_tab > ul").css('padding-right','12px');
						}
					},
				});
				$( ".tab4").click(function(){
					// $("#mobilephone").toggle();
					// $('#subbody').hide("fast");
					
								$('#subbody').hide("fast");
								$('#mobilebox').css('width','auto');
								$('#mobilebox').show("fast").ready(function () {
									$('#mobilephone').show("fast").ready(function () {
										$('#outhtml').height(400);
									}).ready(function () {								
										$('.m_Pre_info').click();
									})
								});
							
						

				});

				// $("#tool_tab").tabs({
				
	                // select: function (event, ui) {
					
	                    // if ($(ui.tab)[0]['hash'] == '#T_Mobile') {
							
							// $('#subbody').hide("fast");
							// $('#mobilebox').css('width','auto');
							// $('#mobilebox').show("fast").ready(function () {
								// $('#mobilephone').show("fast").ready(function () {
							
									// $('#outhtml').height(400);
								// }).ready(function () {								
									// $('.m_Pre_info').click();
								// })
							// });
							
	                    // } else {
							// debugger
	                        // $('#mobilebox').hide("fast");
							// $('#mobilephone').hide("fast");
	                        // $("#tool_tab").tabs("enable");
							 // $('#subbody').show("fast");
	                        // $('#outhtml').height(800);
	                    // }
					
	                // }
					
	            // });
				// $( "#tool_tab" ).on('click','.btn_del',function () {
				// 	$(".delinfo"+ $(this).attr('id')).remove();
				// });
$( "#T_Mobile" ).on('click','.btn_del',function () {
					$(".delinfo"+ $(this).attr('id')).remove();
				});
$( "#branch1" ).on('click','.btn_del',function () {
					$(".delinfo"+ $(this).attr('id')).remove();
				});
$( "#branch2" ).on('click','.btn_del',function () {
					$(".delinfo"+ $(this).attr('id')).remove();
				});
				$( "#desc_info" ).on('change','#d_infobox_type_id', function () {
					if($('#d_infobox_type_id').val()=='eaglegallery'){
					$( "#desc_info label" ).hide();}else{
					$( "#desc_info label" ).show();
					}
				})
				$( "#sortable" ).on('click','li.sort .cat_details',function () {
				// $( ".cat_details" ).on('click', function () {
	           // Dropzone.autoDiscover = false;
				var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'cat'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=catDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Category details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					
					return false;
				});
				$( ".d_attr_details" ).on('click', function () {
					var copy = '';
					if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()){
						var copytest = 'yes';
					}else{
						var copytest = 'no';
					}
					 var cup = $('#msortable [value="'+$('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()+'"]').prev();
					 
					if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val() !=''){
						copy = 'copy';
					}
					//Dropzone.autoDiscover = false;
					var param={'contentid': $(this).attr('id'),
							'content': $('#d_add'+$(this).attr('id')).val(),
							'layout': $('#layout_style_name').val(),
							'find':'d_attr',
							'type':'attr',
							'copy':copy};
					$.post( "" ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Attributes Box Settings",
							buttons:{"Confirm":1,"Cancel":0},
							focus:0,
							submit:function(e,v,m,f){
							
								if(v){
								$('#d_add'+$('#boxcontent').val()).val($('#alllist').serialize());
								if($('#copymobile').is(':checked')){
								if(copytest == 'yes'){
								cup.val($('#alllist').serialize());
								}
								}}
								$('#D_Pre_info').click();
							},
							
						})
					})
					
				});
			// $( "#dsortable" ).on('click','li.dsort .d_item_details',function () {
			// 	// $( ".d_item_details" ).on('click', function () {
			// 		var copy = '';
			// 		if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()){
			// 		var copytest = 'yes';
			// 		}else{
			// 		var copytest = 'no';
			// 		}
					
			// 		var cup = $('#msortable [value="'+$('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()+'"]').prev();
			// 		if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val() !=''){
			// 			copy = 'copy';
			// 		}
			// 		Dropzone.autoDiscover = false;
			// 		var param={'contentid': $(this).attr('id'),
			// 				'content': $('#d_add'+$(this).attr('id')).val(),
			// 				'layout': $('#layout_style_name').val(),
			// 				'find':'d_item',
			// 				'type':'item',
			// 				'copy':copy};
			// 		$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemSpecifics',param ,function(data, textStatus, jqXHR){
			// 			$.prompt(data,{prefix:"jqi",
			// 				title:"Item Specifics Box Settings",
			// 				buttons:{"Confirm":1,"Cancel":0},
			// 				focus:0,
			// 				submit:function(e,v,m,f){
							
			// 					if(v){
			// 					$('#d_add'+$('#boxcontent').val()).val($('#alllist').serialize());
			// 					// if($('#copymobile').is(':checked')){
								
			// 					// if(copytest == 'yes'){
			// 					// cup.val($('#alllist').serialize());
			// 					// }
								
			// 					// }
			// 				}
			// 					$('#D_Pre_info').click();
			// 				},
			// 			})
			// 		})


							
			// 	});
			// 	function htmlEncode( html ) {
			// 		return document.createElement( 'a' ).appendChild( 
			// 		document.createTextNode( html ) ).parentNode.innerHTML;
			// 	};
				
			// 	$( ".m_item_details" ).on('click', function () {
					
			// 		Dropzone.autoDiscover = false;
			// 		var param={'contentid': $(this).attr('id'),
			// 			'content': $('#m_add'+$(this).attr('id')).val(),
	  //                   'layout': $('#layout_style_name').val(),
			// 			'find':'d_attr',
			// 			'type':'item'
			// 		};
			// 		$.post( "" ,param ,function(data, textStatus, jqXHR){
			// 			$.prompt(data,{prefix:"jqi",
			// 				title:"Item Specifics Box Settings",
			// 				buttons:{"Confirm":1,"Cancel":0},
			// 				focus:0,
			// 				submit:function(e,v,m,f){
			// 					if(v){
			// 					$('#m_add'+$('#boxcontent').val()).val($('#alllist').serialize());
			// 					}
			// 					$('.m_Pre_info').click();
			// 				},
							
			// 			})
			// 		})
					
			// 	});
			// 	$( ".m_attr_details" ).on('click', function () {		
				
			// 		Dropzone.autoDiscover = false;
			// 		var param={'contentid': $(this).attr('id'),
			// 			'content': $('#m_add'+$(this).attr('id')).val(),
	  //                   'layout': $('#layout_style_name').val(),
			// 			'find':'d_attr',
			// 			'type':'attr'
			// 			};
			// 		$.post( "" ,param ,function(data, textStatus, jqXHR){
			// 			$.prompt(data,{prefix:"jqi",
			// 				title:"Attributes Box Settings",
			// 				buttons:{"Confirm":1,"Cancel":0},
			// 				focus:0,
			// 				submit:function(e,v,m,f){
			// 					if(v){
			// 					$('#m_add'+$('#boxcontent').val()).val($('#alllist').serialize());
			// 					}
			// 				},
							
			// 			})
			// 		})
					
			// 	});
				$( "#Title_ONNOFF" ).change(function() {
					if($(this).val() == 'ON'){
						$( "#TitleONNOFF" ).show("fast");
						$( ".subtitle" ).show("fast");
					}else{
						$( "#TitleONNOFF" ).hide("fast");
						$( ".subtitle" ).hide("fast");
					}
				})	
				$( "#product_photo_ONNOFF" ).change(function() {
					if($(this).val() == 'ON'){
						$( ".product_photo_need_hide" ).show("fast");
					}else{
						$( ".product_photo_need_hide" ).hide("fast");
					}
				})			
			// 	// $( ".d_pic_details" ).on('click', function () {
			// 		// var copy = '';
			// 		// var cup = $('#msortable [value="'+$('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()+'"]').prev();
			// 		// if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val() !=''){
			// 			// copy = 'copy';
			// 		// }
			// 		// Dropzone.autoDiscover = false;
			// 		// var param={'contentid': $(this).attr('id'),
			// 				// 'content': $('#d_add'+$(this).attr('id')).val(),
	  //                   // 'layout': $('#layout_style_name').val(),
			// 			// 'find':'d_pic',
			// 			// 'copy':copy};
			// 		// $.post( "" ,param ,function(data, textStatus, jqXHR){
			// 			// $.prompt(data,{prefix:"jqi",
			// 				// title:"Picture details",
			// 				// buttons:{"Add":1,"Cancel":0},
			// 				// focus:0,
			// 				// submit:function(e,v,m,f){
			// 					// if(v){
			// 						// array_content = new Array;
			// 						// var i = 0;
			// 						// $(".content_src_url").each(function () {
			// 							// array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
			// 							// i++;
			// 						// });
			// 					// $('#d_add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
			// 					// if($('#d_pic_copymobile').is(':checked')){
			// 					// if(cup){
			// 					// cup.val(JSON.stringify(array_content));
			// 					// }
								
			// 					// }}
			// 				// },
							
			// 			// })
			// 		// })
					
			// 	// });
			// 	// $( ".D_youtubedetails" ).on('click', function () {
			// 		// var copy = '';
			// 		// var cup = $('#msortable [value="'+$('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val()+'"]').prev().prev().prev();
			// 		// if($('.'+$(this).attr('id').replace(/content/, "infono")+' .d_infobox_en_key').val() !=''){
			// 			// copy = 'copy';
			// 		// }
			// 		// Dropzone.autoDiscover = false;
			// 		// var param={'contentid': $(this).attr('id'),
			// 			// 'content': $('#d_add'+$(this).attr('id')).val(),
	  //                   // 'layout': $('#layout_style_name').val(),
			// 			// 'find':'d_you',
			// 			// 'copy':copy};
			// 		// $.post( "" ,param ,function(data, textStatus, jqXHR){
			// 			// $.prompt(data,{prefix:"jqi",
			// 				// title:"Youtube details",
			// 				// buttons:{"Add":1,"Cancel":0},
			// 				// focus:0,
			// 				// submit:function(e,v,m,f){
			// 					// if(v){
			// 						// array_content = new Array;
			// 						// var i = 0;
			// 						// $(".content_src_url").each(function () {
			// 							// array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
			// 							// i++;
			// 						// });
			// 					// $('#d_add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
			// 					// if($('#d_pic_copymobile').is(':checked')){
			// 					// if(cup){
			// 					// cup.val(JSON.stringify(array_content));
			// 					// }
			// 					// }
			// 					// }
			// 				// },
			// 				// loaded:function(e){
							
			// 				// }
			// 			// })
			// 		// })
					
			// 		// return false;
			// 	// });
			// $( "#dsortable" ).on('click','li.dsort .d_item_details',function () {
			// 					var param={'contentid': $(this).attr('id'),
			// 						'content': $('#d_add'+$(this).attr('id')).val(),
			// 	                    'layout': $('#layout_style_name').val(),
			// 						'find':'d_item',
			// 						'type':'item'
			// 					};
			// 					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemSpecifics',param ,function(data, textStatus, jqXHR){
			// 						$.prompt(data,{prefix:"jqi",
			// 							title:"Item Specifics Box Settings",
			// 							buttons:{"确定":1},
			// 							focus:0,
			// 							submit:function(e,v,m,f){
			// 								// console.log(arguments);
			// 								if(v){
			// 								$('#d_add'+$('#boxcontent').val()).val($('#alllist').serialize());
			// 								}
											
			// 							}

										
			// 						})

			// 					})
			// 					return false;
								
								
			// });
			$( "#dsortable" ).on('click','li.dsort .D_call_for_action',function () {
				
					//Dropzone.autoDiscover = false;
					var param={'contentid': $(this).attr('id'),
						'content': $('#d_add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'d_call'
					};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=actionButton',param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Action Button",
							buttons:{"Add":1,"Cancel":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
								$('#d_add'+$('#boxcontent').val()).val($('#alllist').serialize());
								}
							},
							loaded:function(e){
							
							}
						})
					})
					
					return false;
				});
				// $( ".M_call_for_action" ).on('click', function () {
				  
					// Dropzone.autoDiscover = false;
					// var param={'contentid': $(this).attr('id'),
						// 'content': $('#m_add'+$(this).attr('id')).val(),
	                    // 'layout': $('#layout_style_name').val(),
						// 'find':'m_call'
					// };
					// $.post( "" ,param ,function(data, textStatus, jqXHR){
						// $.prompt(data,{prefix:"jqi",
							// title:"Action Button",
							// buttons:{"Add":1,"Cancel":0},
							// focus:0,
							// submit:function(e,v,m,f){
								// if(v){
								// $('#m_add'+$('#boxcontent').val()).val($('#alllist').serialize());
								// }
							// },
							// loaded:function(e){
							
							// }
						// })
					// })
					
					// return false;
				// });
				// $( ".mpic_details" ).on('click', function () {					
					// Dropzone.autoDiscover = false;					
					// var param={'contentid': $(this).attr('id'),
						// 'content': $('#m_add'+$(this).attr('id')).val(),
	                    // 'layout': $('#layout_style_name').val(),
						// 'find':'m_picp'};
					// $.post( "" ,param ,function(data, textStatus, jqXHR){
						// $.prompt(data,{prefix:"jqi",
							// title:"Picture details",
							// buttons:{"Add":1,"Cancel":0},
							// focus:0,
							// submit:function(e,v,m,f){
								// if(v){
									// array_content = new Array;
									// var i = 0;
									// $(".content_src_url").each(function () {
										// array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										// i++;
									// });
								// $('#m_add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								// }
							// },
							// loaded:function(e){
							
							// }
						// })
					// })
					
					// return false;
				// });
				// $( ".d_html_details" ).on('click', function () {
					// Dropzone.autoDiscover = false;
					// var param={'contentid': $(this).attr('id'),
						// 'content': $('#d_add'+$(this).attr('id')).val(),
	                    // 'layout': $('#layout_style_name').val(),
						// 'find':'d_html'};
					// $.post( "" ,param ,function(data, textStatus, jqXHR){
						// $.prompt(data,{prefix:"jqi",
							// title:"HTML details",
							// buttons:{"Add":1,"Cancel":0},
							// focus:0,
							// submit:function(e,v,m,f){
								// if(v){
									// array_content = new Array;
									// var i = 0;
									
								// $('#d_add'+$('#boxcontent').val()).val(CKEDITOR.instances['d_html'].getData().trim());
								// }
							// },
							// loaded:function(e){
							
							// }
						// })
					// })
					
					// return false;
				// });
				// $( ".myoutube_details" ).on('click', function () {
		
					// var data = {
						// 'contentid': $(this).attr('id'),
						// 'content': $('#m_add'+$(this).attr('id')).val(),
						// 'layout': $('#layout_style_name').val(),
						// 'find':'m_youtube'
					// };
					// $.ajax({
							// type: 'POST',
							// data: data,
							// dataType: 'html',
							// url: '',
							// success: function (data) {
								// $('#m_pic_info').html(data);
							   // $( "#m_pic_info" ).dialog( "open" );
							// }
						// });					
						// return false;
				// });
				
				$( "#sortable" ).on('click','li.sort .pic_details',function () {  
				// $( ".pic_details" ).on('click', function () {
	           //
	            Dropzone.autoDiscover = false;
				var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'pic'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=picDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{
							title:"Picture details",
							buttons:{"Add":1,"Cancel":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					
					return false;
				  });
			// $( ".new_details" ).on('click', function () {
				// var data = {
	                    // 'contentid': $(this).attr('id'),
						// 'content': $('#add'+$(this).attr('id')).val(),
	                    // 'layout': $('#layout_style_name').val(),
						// 'find':'new'
	                // };
				// $.ajax({
	                    // type: 'POST',
	                    // data: data,
	                    // dataType: 'html',
	                    // url: '',
	                    // success: function (data) {
	                        // $('#pic_info').html(data);
	                       // $( "#pic_info" ).dialog( "open" );
	                    // }
	                // });
					
					// return false;
				// });
				$( "#sortable" ).on('click','li.sort .youtube_details',function () {
					var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'youtube'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=youtubeDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Youtube details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					return false;
				
				});
				$( "#sortable" ).on('click','li.sort .flash_details',function () {
					var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'flash'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=flashDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"flash details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					return false;
				
				});
				$( "#sortable" ).on('click','li.sort .cus_details',function () {
	           // Dropzone.autoDiscover = false;
				var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'cus'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=cusDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Custom item",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					
					return false;
				});
				// $( ".html_details" ).on('click', function () {
					//// Dropzone.autoDiscover = false;
					// var param={'contentid': $(this).attr('id'),
						// 'content': $('#add'+$(this).attr('id')).val(),
	                    // 'layout': $('#layout_style_name').val(),
						// 'find':'html'};
					// $.post( "" ,param ,function(data, textStatus, jqXHR){
						// $.prompt(data,{prefix:"jqi",
							// title:"HTML details",
							// buttons:{"Add":1,"Cancel":0},
							// focus:0,
							// submit:function(e,v,m,f){
								// if(v){
									// array_content = new Array;
									// var i = 0;
								// $('#add'+$('#boxcontent').val()).val(CKEDITOR.instances['d_html2'].getData().trim());
								// }
							// },
							// loaded:function(e){
							
							// }
						// })
					// })
					
					// return false;
				// });
				$( "#sortable" ).on('click','li.sort .text_details',function () {
					var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'text'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=textDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"text details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					return false;
				
				});
				$( "#sortable" ).on('click','li.sort .item_details',function () {
				// $( ".item_details" ).on('click', function () {
					var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
	                    'layout': $('#layout_style_name').val(),
						'find':'item'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemDetails' ,param ,function(data, textStatus, jqXHR){
						$.prompt(data,{prefix:"jqi",
							title:"Item details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});
								$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){
							
							}
						})
					})
					return false;
				
				});
				$( "#sortable" ).on('click','li.sort .newlistitem_details',function () {
					// $( ".item_details" ).on('click', function () {
					var param={'contentid': $(this).attr('id'),
						'content': $('#add'+$(this).attr('id')).val(),
						'layout': $('#layout_style_name').val(),
						'find':'newlistitem'};
					$.post( global.baseUrl+'listing/ebay-template/get-partial-template?partial=newlistitemDetails' ,param ,function(data, textStatus, jqXHR){
						console.log(data);
						$.prompt(data,{prefix:"jqi",
							title:"New List Item details",
							buttons:{"确定":1,"取消":0},
							focus:0,
							submit:function(e,v,m,f){
								if(v){
									array_content = new Array;
									var i = 0;
									$(".content_src_url").each(function () {
										array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
										i++;
									});

									$('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
								}
							},
							loaded:function(e){

							}
						})
					})
					return false;

				});
	            $('#outhtml').on("click", "#sample2_graphic_setting_Notice_Banner", function (e) {
	               $.prompt('', {
						title: "",
						outerBoxWidth: $('#NBanner').width()+50+'px',
						buttons: { "OK": true },
						submit: function(e,v,m,f){
							$('#bigb').append($('#NBanner'));
							$('.jqiclose').show();
							$('#NBanner').hide();
						},
						
						loaded:function(e){
							$('.jqiclose').hide();
							$('#NBanner').show();
								var popup=e.target;
								$(popup).find('.jqimessage').append($('#NBanner'));
							}
					});
	            });
				$(".changeDesc").bind('click focusout', function () {
	                change_Descriptions();
	                return false;
	            });
	            $('#outhtml').on("click", "#sample2_graphic_setting_Shop_Name_Banner", function (e) {
	                $.prompt('', {
					title: "",
					outerBoxWidth: $('#SNBanner').width()+30+'px',
					buttons: { "OK": true },
					submit: function(e,v,m,f){
						$('#bigb').append($('#SNBanner'));
						$('.jqiclose').show();
						$('#SNBanner').hide();
					},
					
					loaded:function(e){
						$('.jqiclose').hide();
						$('#SNBanner').show();
							var popup=e.target;
							$(popup).find('.jqimessage').append($('#SNBanner'));
						$.modalReady($('#jqi'));
						}
				});
				
	            });

	            $('#outhtml').on("click", ".change_Descriptions", function (e) {
	                change_Descriptions();
	                return false;
	            });
	            $('#tb_eb_CFI_style_master_body_border_style').bind('change focusout', function () {
					if($('#tb_eb_CFI_style_master_body_border_style').val()=='none'){
						$('.Canvas_B_Hide').hide("fast");
					}else{
						$('.Canvas_B_Hide').show("fast");
					}
	                $('#subbody').css({
	                    'border-style': $('#tb_eb_CFI_style_master_body_border_style').val()
	                });
					$('#mobilebox').css({
	                    'border-style': $('#tb_eb_CFI_style_master_body_border_style').val()
	                });
	            })
	            $('#eb_tp_Title_Size').bind('keyup change keydown focusout', function () {
	                $('#desc_html .subtitle').css({
	                    'font-size': $('#eb_tp_Title_Size').val() + 'px'
	                });
	            })
				
				$(".FS_Auto").bind('click focusout', function () {
				 $(this).autocomplete("search",'');
				})
				$('#infobox_bkgd_type').bind('change focusout', function () {
				if($('#infobox_bkgd_type').val() == 'Color'){
				
				$('#Catg_B').show();
				$('#Catg_P').hide();
				}else{
				
				$('#Catg_P').show();
				$('#Catg_B').hide();
				}
	              
	            }) 

				$('input[name=infobox_bkgd_pattern_r]').bind('change focusout click', function () {
					$('#infobox_bkgd_pattern').val($(this).val())
					$('.layoutborder').css("background-image", "url(" + $('#infobox_bkgd_pattern').val() + ")");
				})
				$('.upimg_r').bind('keyup change keydown focusout', function () {					
				 $('#'+$(this).attr('name')).val($(this).val());					 
				 $('#'+$(this).attr('name')).change();
				 })
				 
	            $('input[name=eb_tp_font_Title]').bind('keyup keydown change keydown mouseenter focusout', function () {
	                $('.subtitle').css({
	                    'font-family': $('input[name=eb_tp_font_Title]').val()
	                });
	            })
				
	            $('#tb_eb_CFI_style_master_body_size').bind('change focusout', function () {
	                $('#subbody').css({
	                    'border': $('#tb_eb_CFI_style_master_body_size').val() + 'px' + ' ' + $('#tb_eb_CFI_style_master_body_border_style').val() + ' ' + findcolor($('input[name=eb_tp_clr_Mainbody_border]').val())
	                });
					$('#mobilebox').css({
	                    'border': $('#tb_eb_CFI_style_master_body_size').val() + 'px' + ' ' + $('#tb_eb_CFI_style_master_body_border_style').val() + ' ' + findcolor($('input[name=eb_tp_clr_Mainbody_border]').val())
	                });
	            })
	            $('#tb_shop_master_Setting_menu_On').change(function () {
	                if ($('#tb_shop_master_Setting_menu_On').val() == 'No') {
//						$('.MenuD').hide("fast");
						$("[name='MenuD']").hide();
	                    $('#menudisplay').hide("fast");
	                } else {
						$("[name='MenuD']").css('display','initial');
	                    $('#menudisplay').show("fast");
	                }
	            })
	             $('#tb_shop_name_On').change(function () {
	                if ($('#tb_shop_name_On').val() == 'No') {
//						$('.SNBanner').hide("fast");
						$("[name='SNBanner']").hide();
						$('#sample2_graphic_setting_Shop_Name_Banner').hide("fast");
	                } else {
						$("[name='SNBanner']").css('display','initial');
						$('#sample2_graphic_setting_Shop_Name_Banner').show("fast");
	                }
	            })
				$('#nbynn').change(function () {
					if ($('#nbynn').val() == 'No') {
//						$('.SNBanner').hide("fast");
						$("[name='NBanner']").css('display','none');
						$('#sample2_graphic_setting_Notice_Banner').hide();
					} else {
						$("[name='NBanner']").css('display','initial');
						$('#sample2_graphic_setting_Notice_Banner').show();

					}
				}).trigger('change')


	            //////////////////////////////////////////////////////////////////////////
	         				
	            $(".ckeditor").each(function () {
					if($(this).attr('id')){
						CKEDITOR.replace($(this).attr('id'));
					}
	            });
	            $(".catbutton").mouseover(function(){
	            	$('.catbutton').css({
	                        'background-color': findcolor($('#btn_overcolor').val())
	                    });
	            });

	            $('.Multiple').jPicker({
	                    window: {
	                        position: {
	                            x: 'right',
	                            y:'bottom'
	                        }
	                    },
	                    effects: {
	                        type: 'fade', // effect used to show/hide an expandable picker. Acceptable values "slide", "show", "fade"
	                        speed: {
	                            show: 'fast', // duration of "show" effect. Acceptable values are "fast", "slow", or time in ms
	                            hide: 'fast' // duration of "hide" effect. Acceptable value are "fast", "slow", or time in ms
	                        }
	                    },
	
	                    images: {
	                        clientPath:  global.baseUrl+"js/project/listing/ebayVisualTemplate/jpicker/"
	                    }
	                },
	                function (color, context) {
	                     $('.theme').css("color", findcolor($('input[name=Mainbody_background]').val()));
	                     $('.theme').css("background", findcolor($('input[name=title_background]').val()));
						 $('.littletext').css("background", findcolor($('input[name=b1_Mainbody_background]').val()));
						  $('.smalltext').css("background", findcolor($('input[name=b2_Mainbody_background]').val()));
						  $('#Attrid2 td').css("border-color", findcolor($('input[name=border_Mainbody_background]').val()));	
	                    $('#policy_html').css("background-color", findcolor($('input[name=eb_tp_clr_infobox_background]').val()));
	                    $('.tabpage').css("border-color", findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
	                    $('.policy_box').css("background-color", findcolor($('input[name=eb_tp_clr_infobox_background]').val()));
	                    $('.policy_box').css("border-color", findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
						$('.mobpovh').css("background-color", findcolor($('input[name=eb_tp_tab_Header_color]').val()));
						 $('#tabs > ul > li').css("background-color", findcolor($('input[name=eb_tp_tab_Header_color]').val()));
	                    $('#tabs > ul > li').css("color", findcolor($('input[name=eb_tp_tab_Header_font]').val()));
	             		 $('#tabs > ul > li.tabActiveHeader').css("background-color", findcolor($('input[name=eb_tp_tab_Header_selected]').val()));
	                    $('.subtitle').css("color", findcolor($('input[name=eb_tp_clr_Title]').val()));
						$('#mobile_subtitle').css("color", findcolor($('input[name=eb_tp_clr_Title_mobile]').val()));
	                    $('#desc_header').css("color", findcolor($('input[name=eb_tp_clr_Description_header]').val()));
	                    $('.desc_details').css("color", findcolor($('input[name=eb_tp_clr_Description_details]').val()));
	                    $('#smail_pic_box').css("background-color", findcolor($('input[name=eb_tp_clr_Small_photo_background]').val()));
	                    $('.smail_pic').css("border-color", findcolor($('input[name=eb_tp_clr_Small_photo_border]').val()));
	                    $('#subbody').css("background-color", findcolor($('input[name=eb_tp_clr_Mainbody_background]').val()));
	                    $('#subbody').css('border', $('#tb_eb_CFI_style_master_body_size').val() + "px " + $('#tb_eb_CFI_style_master_body_border_style').val() + " #" + $('input[name=eb_tp_clr_Mainbody_border]').val());
	                    $('#mobilebox').css("background-color", findcolor($('input[name=eb_tp_clr_Mainbody_background]').val()));
	                    $('#mobilebox').css('border', $('#tb_eb_CFI_style_master_body_size').val() + "px " + $('#tb_eb_CFI_style_master_body_border_style').val() + " #" + $('input[name=eb_tp_clr_Mainbody_border]').val());
	                    $('#mobilebox').css('border', $('#tb_eb_CFI_style_master_body_size').val() + "px " + $('#tb_eb_CFI_style_master_body_border_style').val() + " #" + $('input[name=eb_tp_clr_Mainbody_border]').val());
	                   $('.gbox').css({
	                        'color': findcolor($('#text_fontcolor').val())
	                    });		
	                     $('.Cat_gbox').css({
	                        'color': findcolor($('#text_fontcolor').val())
	                    });	
						
						$('#ItemSpecificstable td').css('border', $('#Item_Specifcations_size').val() + "px " + $('#Item_Specifcations_border_style').val() + " " + findcolor($('#Item_Specifcations_color').val()));
						$('.navitemitem').css({
							'color': findcolor($('#text_fontcolor').val())
						})
						
						$('.catbutton').css({
	                        'color': findcolor($('#btn_textcolor').val())
	                    });
	                    $('#mobile_details').css({
	                        'color': findcolor($('#eb_tp_mobile_Color').val())
	                    });
	                    $('.mobile_details').css({
	                        'color': findcolor($('#eb_tp_mobile_Color').val())
	                    });
						$('.Cat-List').css({
	                        'color': findcolor($('#text_fontcolor').val())
	                    });
						$('.Cat-List').css({
	                        'background-color': findcolor($('#cat_bkgd_color').val())
	                    });
						$('.gboxout').css({
	                       'color': findcolor($('#title_fontColor').val())
	                    });
						if($('#title_bkgd_type').val() == 'Color' ){
						$('.gboxout').css({
	                        'background-color': findcolor($('#title_bkgd_color').val())
	                    });
						}
						if($('#cat_bkgd_type').val()=='Color'){
							$('.Cat_List').css({
								'background-color':findcolor($('#cat_bkgd_color').val())
							});
						}
						$('.Cat-List').css({
							'color': findcolor($('#text_fontcolor').val())
						})	
						if($('#infobox_bkgd_type').val() == 'Color' ){
						$('.layoutborder').css("background-image", "url('')");
						
						$('.layoutborder').css({
	                        'background-color': findcolor($('#infobox_bkgd_color').val())
	                    });
						}
						$('.layoutborder').css('border', $('#cat_layoutborder_size').val() + "px " + $('#cat_layoutborder_style').val() + " " + findcolor($('input[name=cat_layoutborder_color]').val()));
						$('.catbutton').css({
	                        'background-color': findcolor($('#btn_bkgdcolor').val())
	                    });
						$('.catbutton').css({
	                        'color': findcolor($('#btn_textcolor').val())
	                    });
						$('.shopsubnameaddon').css({
						   'color': findcolor($('#shop_name_sub_text_color').val())
						});
						$('.shopnameaddon').css({
						   'color': findcolor($('#shop_name_text_color').val())
						});
						var css='.Cat-List:hover{background-color: '+findcolor($('#cat_overbkgd_color').val())+'}'+
						'.Cat-List:hover a{color: '+findcolor($('#text_overcolor').val())+';text-decoration: none;}'+
						'.catbutton:hover{background-color: '+findcolor($('#btn_overcolor').val())+';}';
					 style=document.createElement('style');
						if (style.styleSheet)
							{style.styleSheet.cssText=css;}
						else 
							{style.appendChild(document.createTextNode(css));}
						document.getElementsByTagName('head')[0].appendChild(style);
									},
	                function (color, context) {}
	               
	           		); 
	            // $("#tool_tab").tabs("enable");
	            if ($('#outhtml') == '') {
	                window.location.href = "";
	            }
				
// 				$('#tb_eb_CFI_style_master_HBP').change();
				$( "#bigb" ).draggable({ handle: "#moveheader" });	
				$('#D_Pre_info').click();
	        });
			
			window.startaction = setTimeout('ready_test()', 1000);
	    }
	
	});
	
});


function save(){
	var txt = 'Are your sure to save this Template';
	var submitFunc = function (event, v, m, f) {
		if (v === "Save") {
			mycallbackform(event, v, m, f);
			//return false;
		} else if (v === "SaveAs") {
			$.prompt.nextState();
			return false;
		} else if (v === "Sure") {

			mycallbackform(event, v, m, f);
			//return false;
		}
	},
	states = [{
		title: 'Save',
		html: txt,
		buttons: {
			save: 'Save',
			cancel: 'Cancel'
		},
		submit: submitFunc
	}];
	var $jqi = $.prompt(states);
	$jqi.bind('promptclose', function (e) {});

	function mycallbackform(e, v, m, f) {
		if (v != undefined && v != "Cancel") {
			$("#Loading").dialog({
				width: 'auto',
				modal: true
			});
			arrayeditor = new Array;
			$(".editor").each(function () {
				arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData().trim());
			});

			for(var i=1; i <= arrayeditor.length; i++ ){
				if(i < 6)
					$('#sh_ch_info_Policy'+(i)).val(arrayeditor[i-1]);
				else
					$('#sh_ch_info_Policybot').val(arrayeditor[i-1]);
			}
			var data = {
				'switchType': $('#layout_type').attr('class'),
				'productType':$('.shutdown.active').attr('id'),
				'allItem': $('#allItem').serializeArray(),
				'sortable': $('#sortablef').serializeArray(),	
				'msortable': $('#msortablef').serializeArray(),
				'dsortable': $('#dsortablef').serializeArray(),					
				'editor[]': arrayeditor,					
				'infodetclass':$(".infodetclass").serializeArray(),
				'template_id': <?php echo $tempalteId;?>,
				'isSaveas':0,
			};

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'html',
				url: global.baseUrl+'listing/ebay-template/save-template',
				success: function (data) {

					window.location =  global.baseUrl+'listing/ebay-template/edit?template_id='+data;
				}
			});
		}
	}
}

function saveas() {
	var txt = '请输入模板名称:<br />' +
		'<input type="text" id="alertName"' +
		'name="alertName" value="New" />';
	var submitFunc = function (event, v, m, f) {
		if (v === "Save") {
			mycallbackform(event, v, m, f);
			//return false;
		} else if (v === "SaveAs") {
			$.prompt.nextState();
			return false;
		} else if (v === "Sure") {
			mycallbackform(event, v, m, f);
			//return false;
		}
	},
	states = [{
		title: '另存为',
		html: txt,
		buttons: {
			'另存为': 'Save',
			取消: 'Cancel'
		},
		submit: submitFunc
	}];
	var $jqi = $.prompt(states);
	$jqi.bind('promptclose', function (e) {});

	function mycallbackform(e, v, m, f) {
		$("#Loading").dialog({
			width: 'auto',
			modal: true
		});
		if (v != undefined && v != "Cancel") {
			arrayeditor = new Array;
			$(".editor").each(function () {
				arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData().trim());
			});
			for(var i=1; i <= arrayeditor.length; i++ ){
				if(i < 6)
					$('#sh_ch_info_Policy'+(i)).val(arrayeditor[i-1]);
				else
					$('#sh_ch_info_Policybot').val(arrayeditor[i-1]);
			}
			var data = {
				'switchType': $('#layout_type').attr('class'),
				'productType':$('.shutdown.active').attr('id'),
				'template_id': <?php echo $tempalteId;?>,
				'allItem': $('#allItem').serializeArray(),
				'editor[]': arrayeditor,
				'sortable': $('#sortablef').serializeArray(),	
				'msortable': $('#msortablef').serializeArray(),
				'dsortable': $('#dsortablef').serializeArray(),					
				'infodetclass':$(".infodetclass").serializeArray(),
				'name': f.alertName,
				'isSaveas':1,
				'name1':$('#userAccount').val(),
	
			};
//			console.log($('#sortablef').serializeArray())
//			console.log(($('.shutdown.active').attr('id')));
			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'html',
				url: global.baseUrl+'listing/ebay-template/save-template',
				success: function (data) {

					window.location =  global.baseUrl+'listing/ebay-template/edit?template_id='+data;
				}
			});
		}
	}
}

function DEL() {
	var txt = '';
	var submitFunc = function (event, v, m, f) {
		if (v === "Save") {
			mycallbackform(event, v, m, f);
			//return false;
		} else if (v === "SaveAs") {
			$.prompt.nextState();
			return false;
		} else if (v === "Sure") {

			mycallbackform(event, v, m, f);
			//return false;
		}
	},
	states = [{
		title: 'Delete Template style',
		html: 'Delete Template style!',
		buttons: {
			Delete: 'Save',
			cancel: 'Cancel'
		},
		submit: submitFunc
	}];
	var $jqi = $.prompt(states);
	$jqi.bind('promptclose', function (e) {
		if (window.console) {}

	});

	function mycallbackform(e, v, m, f) {
		$("#Loading").dialog({
			width: 'auto',
			modal: true
		});
		$.ajax({
			type: 'POST',
			data: {
				'template_id': <?php echo $tempalteId;?> ,
			},
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/del-template',
			success: function (data) {
				window.location =  global.baseUrl+'listing/ebay-template/edit';
			}
		});

	}

	return false;
}

</script>
<script type="text/javascript">
		
</script>


