<?php 
// 当template id 为0或空时，获取此页面的input值
?>
<style>
#tool{background-color: #424443;}
#tool_tab{
	width: 260px;
	margin: auto;
	overflow-x: hidden;
	overflow-y: visible;
	height: 550px;
	background-color: #ffffff;
}
#tool_tab::-webkit-scrollbar {
    width: 12px;
	height: 12px;
}
#tool_tab::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3); 
    -webkit-border-radius: 10px;
    border-radius: 10px;
}
#tool_tab::-webkit-scrollbar-thumb {
    -webkit-border-radius: 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.8); 
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5); 
}
#tool_tab>ul {
	webkit-background-clip: border-box;
	-webkit-background-origin: padding-box;
	-webkit-background-size: auto;
	background-attachment: scroll;
	background-clip: border-box;
	background-color: rgb(204, 204, 204);
	border-color: rgb(170, 170, 170);
	border-radius: 4px;
	border: 1px solid;
	border-color: rgb(170, 170, 170);
	color: rgb(34, 34, 34);
	cursor: auto;
	display: block;

	line-height: 14px;
	margin: 0px;
	padding: 2px 2px 0px 2px;
	
	zoom: 1;
/* edit by liang */
	background-color: white;
	
	line-height: 12px;
	margin: 0px;
	padding: 0px;
/*	width: 259px; */
	border: 0px;
/* edit by liang */

	margin: auto;
}
#tool_tab > div#T_Basic,#tool_tab > div#T_Cat,#tool_tab > div#T_Mobile,
#tool_tab > div#T_desc,#tool_tab > div#T_Path{height: auto;}
#tool_tab>ul>li.tab {
	list-style: none;
/*	float: left; */
	position: relative;
	top: 0;
	border-bottom: 0;
	white-space: nowrap;
/* edit by liang */
/*	margin-right: 1px;*/
	padding: 0px;
	background-color: #58afb9;
/*	width: 259px;  */
	height: 102px;
/* edit by liang */

	text-align:center;
	border: 1px solid #ffffff;
	border-top: 0px;
	position: relative;
}
#tool_tab>ul>li.tab.ui-accordion-header-active{background-image: none;background-color: #61c6ce;}
#tool_tab>ul>li.tab.ui-state-default{background-image: none;background-color: #58afb9;}
#tool_tab>ul>li.tab.ui-state-hover{background-color: #61c6ce;background-image: none;}

#tool_tab>ul>li.tab:hover{background-color: #61c6ce;}
#tool_tab>ul>li.content{/*display: none;*/ padding: 0;width: 260px;}
#tool_tab>ul>li.content>.content-head{
	width: 100%;
	height: 30px;
	background-color: #61c6ce;
	display: none;
}
#tool_tab>ul>li.tab>center{text-align: left;padding-left: 30px;height: 102px;}
#tool_tab>ul>li.tab a {
	font-family: Verdana, Arial, sans-serif;
	font-size: 24px;
	font-weight: normal;
	text-decoration: none;
	color: #ffffff;
	line-height: 18px;
/* edit by liang */

	border: 0px;
	padding: 8px 0px;
/* edit by liang */
	display: inline-block;
}
#tool_tab>ul>li.tab a font{position: absolute;top: 40%;left: 50%;} 
/*edit by liang*/
li.ui-tabs-active a{background-color: #61c6ce;}
li.ui-state-active a{background-color: #61c6ce;}
/*edit by liang*/
#butset{background-color: #424443;}

#tool table tr td {
	font-family: Verdana, Arial, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

#tool tr.topname td {
	border-collapse: collapse;
	border-color: rgb(34, 34, 34);
	border-style: none;
	border-width: 0px;
	color: rgb(34, 34, 34);
	cursor: auto;
	display: table-cell;
	font-family: Verdana, Arial, sans-serif;
	font-size: 12px;
	font-weight: bold;
	height: 18px;
	line-height: 11px;
	margin: 0px;
	padding: 5px;
	width: 240px;
	zoom: 1;
}
#sortable > li{
	border-collapse: collapse;
	color: rgb(34, 34, 34);
	font-family: Verdana, Arial, sans-serif;
	font-size: 12px;
	font-weight: normal;
	line-height: 11px;
	list-style: none outside none;
	text-align: left;
	vertical-align: baseline;
	width: 240px;
	zoom: 1;
}
.topname {
	padding: 5px;
	font-weight: bold;
}

.toright {
	border-collapse: collapse;
	border: 0;
	display: block;
	float: right;
	font-family: Verdana, Arial, sans-serif;
	font-size: 12px;
	font-weight: bold;
	height: 12px;
	line-height: 11px;
	margin: 0;
	padding: 0;
	width: 30px;
	zoom: 1;
}

.toright>a {
	text-decoration: none;
	color: rgb(34, 34, 34);
}

.detail {
	margin-left: 10px;
	background-image: url(/images/ebay/template/engineering_.png);
	background-size: 20px 20px;
	width: 20px;
	height: 20px;
	cursor: pointer;
	top: 4px;
	position: relative;
	border: 0px;
	display: inline-block;
}
#dsortable .detail {
	background-image: url('');
	background-size: 0px;
	width: initial;
	height: initial;
	position: static;
	display: block;
	margin-left: 0px;
}
#dsortable table tr>td:first-child {
	width: 80px;
}
#dsortable table tr>td:nth-child(2){
	width: 131px;
}
#msortable .detail {
	background-image: none;
	background-size: 0px;
	width: initial;
	height: initial;
	position: static;
	display: block;
	margin-left: 0px;
}
.editor {
	min-height: 40px;
}

</style>
<div id="tool_tab">
<ul>
	<li class="tab">
	<center><a href="#T_Basic"><img
		src="/images/ebay/template/layout_normal_highlighed_small.png"
		alt='Basic' Title='Basic'>
	<font>主要设计</font></a></center>
	</li>
	<li class="content">
		<div class="content-head"></div>
		<div id="T_Basic" class='P_height'>
		<table>
			<tr class='topname'>
				<td style="padding-left: 4px;">背景
				<p class='toright'><a
					title="<span>Background:</span><br><br>In this part, seller can choose the color and style of the background and outmost border of the template."
					class="tiplink" href="javascript:;">[?]</a></p>
				</td>
			</tr>
			<tr>
				<td>
				<table class='ws280'>
					<tr>
						<td class='font8' style="width: 30px;">颜色:</td>
						<td colspan=3>
						<table>
							<tr class="trtop">
								<td><select name='tb_eb_CFI_style_master_BP'
									id="tb_eb_CFI_style_master_BP" class='font8'>
									<option value="Pattern">图案</option>
									<option value="Color" selected="selected">颜色</option>
								</select></td>
								<td colspan=3>
								<div id="bg_P" class="trtop" style="display: none;"><input
									type="text" class="in50 font8"
									name="tb_eb_CFI_style_master_background_Pattern"
									id="tb_eb_CFI_style_master_background_Pattern"
									value="<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png" />
								<a name="backgroundPattern" class="layout_select tip"></a>
								<div class="backgroundPattern tooltip tipdiv">
								<table>
									<tr>
										<td>
										<table>
											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
													checked>Background Pattern style 1 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
												Pattern style 3 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='tb_eb_CFI_style_master_background_Pattern_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										</td>
										<td>
										<table>
											<tr>
												<td>
												<form target="iframe_upload" class="dropzone smimg"
													name="subbody"
													title='tb_eb_CFI_style_master_background_Pattern'
													id="Body_up" method='POST' enctype="multipart/form-data"
													dz-message="Drop files here to upload"
													style="width: 210px; min-height: 50px;">
												<div class="fallback"><input type="file" multiple="multiple"
													name="file" /><br />
												<input type="submit" name="action" value="Upload Now!" /><br />
												<div id="gallery_uploads"></div>
												</div>
												</form>
												</td>
											</tr>
										</table>
										</td>
									</tr>
								</table>
								</div>
								</a></div>
								<div id="bg_C" class="trtop"><input class='font8 Multiple'
									type="text" name="eb_tp_clr_Mainbody_background" value="#ffffff" />
								</div>
								</td>
							</tr>

						</table>
						</td>
					</tr>
					<tr>
						<td class='font8' style="width: 30px;">边界:</td>
						<td class='in80'>
						<table>
							<tr>
								<td><select name='tb_eb_CFI_style_master_body_border_style'
									id='tb_eb_CFI_style_master_body_border_style' class='font8 in80'
									style=''>
									<option value='none'>none</option>
									<option value='solid' selected>solid</option>
									<option value='dashed'>dashed</option>
									<option value='dotted'>dotted</option>
									<option value='double'>double</option>
									<option value='groove'>groove</option>
									<option value='ridge'>ridge</option>
									<option value='inset'>inset</option>
									<option value='outset'>outset</option>
								</select></td>
							</tr>
						</table>
						</td>
						<td style='width: 40px;'><select
							name='tb_eb_CFI_style_master_body_size'
							id="tb_eb_CFI_style_master_body_size" class="font8 in40">
							<option value='1'>1</option>
							<option value='2'>2</option>
							<option value='3'>3</option>
							<option value='4'>4</option>
							<option value='5'>5</option>
							<option value='6'>6</option>
							<option value='7'>7</option>
							<option value='8'>8</option>
							<option value='9'>9</option>
							<option value='10' selected>10</option>
							<option value='11'>11</option>
							<option value='12'>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
						</select></td>
						<td><input type="text" name="eb_tp_clr_Mainbody_border"
							class="font8 Multiple" value="#666666" /></td>
					</tr>

				</table>
				</td>
			</tr>
			<tr>
				<td colspan=4 class='hr'></td>
			</tr>
			<tr class='topname'>
				<td>模板顶部
				<p class='toright'><a
					title="<span>Header Section:</span><br><br>
		Header section includes the Shop Name banner, Menu bar and Notice banner.<br><br>
		Remark: Notice banner is used for seller to notify buyer for some special events or issues, such as Shop Holiday, or Promotion.  "
					class="tiplink" href="javascript:;">[?]</a></p>
				</td>
			</tr>
			<tr>
				<td>
				<table class='' style="margin-top: -10px;">
					<tr>
						<td class='font8'>商店名称:</td>
						<td></td>
						<td></td>
						<td><a class='detail font8' name='SNBanner' href='#'></a></td>
					</tr>
					<tr>
						<td class='font8'>目录栏:</td>
						<td><select name='tb_shop_master_Setting_menu_On'
							id='tb_shop_master_Setting_menu_On' class='font8'>
							<option value="Yes" selected="selected">开</option>
							<option value="No">关</option>
						</select></td>
						<td colspan=2><a class='detail MenuD font8' name='MenuD' href='#'></a>



						</td>
					</tr>
					<tr>
						<td class='font8'>通告横幅:</td>
						<td colspan=2><select name='Notice_Banner_ONNOFF' id="nbynn"
							class='font8'>
							<option value="On">开</option>
							<option value="Off" selected>关</option>
						</select></td>
						<td><a class='detail NBanner font8' name='NBanner'
							style="display: none;" href='#'></a></td>
					</tr>
				</table>
				</td>
			</tr>

			<tr>
				<td colspan=4 class='hr'></td>
			</tr>
			<tr class='topname'>
				<td>最底政策内容部分
				<p class='toright'><a
					title="<span>Footer Policy Section:</span><br><br>Sellers can define different Policies in different templates. So, the policies (e.g. Return Policy, Shipping Policy, etc) can be different based on different products (e.g. Return Policy for Digital Product may different from Clothing). "
					class="tiplink" href="javascript:;">[?]</a></p>
				</td>
			</tr>
			<tr>
				<td>
				<table>
					<tr>
						<td>
						<div id="Policy_background">
						<table class="" style="width: 250px;">


							<tr class="trtop">
								<td class="font8">背景:</td>
								<td><select name='tb_eb_CFI_style_policy_BP'
									id="tb_eb_CFI_style_policy_BP" class="font8">
									<option value="Pattern">图案</option>
									<option value="Color" selected="selected">颜色</option>
								</select></td>

								<td colspan=3 id="Pg_C" class="trtop"><input type="text"
									name="eb_tp_clr_infobox_background" class="Multiple font8"
									value="#ffffff" /></td>
							</tr>
							<tr id="Pg_P" class="" style="display: none;">
								<td></td>
								<td colspan=3><input type='text' name='eb_tp_policy_Pattern'
									id='eb_tp_policy_Pattern' class='in100 font8 infodetclass'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png' />&nbsp;<a
									name='eb_tp_policy_Pattern_u' class='layout_select tip'></a>
								<div class='eb_tp_policy_Pattern_u tooltip tipdiv'>
								<div class='navbar'>
								<table>
									<tr>
										<td>
										<table>
											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
													checked>Background Pattern style 1 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
												Pattern style 3 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' name='pattern_r' class='upimg_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										</td>
										<td>
										<table>
											<tr>
												<td>
												<form target='iframe_upload' class='dropzone smimg font8'
													name='' title='eb_tp_policy_Pattern' id='policy_up'
													method='POST' enctype='multipart/form-data'
													dz-message='Drop files here to upload'
													style='width: 210px; min-height: 50px;'>
												<div class='fallback'><input type='file' multiple='multiple'
													name='file' /><br />
												<input type='submit' name='action' value='Upload Now!' /><br />
												<div id='gallery_uploads'></div>
												</div>
												</form>
												</td>
											</tr>
										</table>
										</td>
									</tr>
								</table>
								</div>
								<input type='button' name='Go' class='goCancel btn_01 goright'
									value='Confirm' /></div>
								</td>
							</tr>
							<tr>
							
							
							<tr class="trtop">
								<td class="font8">边界:</td>
								<td><select name='eb_tp_clr_infobox_border_style'
									id='eb_tp_clr_infobox_border_style' class='in50 font8' style=''>
									<option value='none'>none</option>
									<option value='solid' selected>solid</option>
									<option value='dashed'>dashed</option>
									<option value='dotted'>dotted</option>
									<option value='double'>double</option>
									<option value='groove'>groove</option>
									<option value='ridge'>ridge</option>
									<option value='inset'>inset</option>
									<option value='outset'>outset</option>
								</select></td>
								<td class="trtop Policy_B_hide"><select
									name='eb_tp_clr_infobox_border_size'
									id="eb_tp_clr_infobox_border_size" class='tp_mobile in50 font8'
									style="width: 35px;">
									<option value='1'>1</option>
									<option value='2' selected>2</option>
									<option value='3'>3</option>
									<option value='4'>4</option>
									<option value='5'>5</option>
									<option value='6'>6</option>
									<option value='7'>7</option>
									<option value='8'>8</option>
									<option value='9'>9</option>
									<option value='10'>10</option>
									<option value='11'>11</option>
									<option value='12'>12</option>
									<option value='13'>13</option>
									<option value='14'>14</option>
									<option value='15'>15</option>
									<option value='16'>16</option>
									<option value='17'>17</option>
									<option value='18'>18</option>
									<option value='19'>19</option>
									<option value='20'>20</option>
								</select></td>
								<td class="trtop Policy_B_hide"><input type="text"
									name="eb_tp_clr_infobox_border" class="Multiple font8"
									value="#7f7f7f" /></td>
							</tr>
						</table>
						<table id='tabtool' class="info3 tardisplay tabtool"
							style="width: 370px;">
							<tr class='disno'>
								<td><input type="text" id="bsh_ch_info_Policy1" value="" /> <input
									type="text" id="bsh_ch_info_Policy2" value="" /> <input
									type="text" id="bsh_ch_info_Policy3" value="" /> <input
									type="text" id="bsh_ch_info_Policy4" value="" /> <input
									type="text" id="bsh_ch_info_Policy5" value="" /> <input
									type="text" id="bsh_ch_info_Policybot" value="" /></td>
							</tr>
							<tr>
								<td>政策1头部分</td>
								<td><input type="text" name="sh_ch_info_Policy1_header"
									id="sh_ch_info_Policy1_header" value="Payment" /></td>
							</tr>
							<tr>
								<td>政策2头部分</td>
								<td><input type="text" name="sh_ch_info_Policy2_header"
									id="sh_ch_info_Policy2_header" value="Shipping" /></td>
							</tr>
							<tr>
								<td>政策3头部分</td>
								<td><input type="text" name="sh_ch_info_Policy3_header"
									id="sh_ch_info_Policy3_header" value="Returns" /></td>
							</tr>
							<tr>
								<td>政策4头部分</td>
								<td><input type="text" name="sh_ch_info_Policy4_header"
									id="sh_ch_info_Policy4_header" value="Customer Services" /></td>
							</tr>
							<tr>
								<td>政策5头部分</td>
								<td><input type="text" name="sh_ch_info_Policy5_header"
									id="sh_ch_info_Policy5_header" value="" /></td>
							</tr>
							<tr>
								<td colspan='2'>
								<hr>
								</td>

							</tr>
							<tr id="Pg_C" class="trtop">
								<td></td>

							</tr>
							<tr id="Pg_C" class="trtop">
								<td>已选版头:</td>
								<td><input type="text" name="eb_tp_tab_Header_selected"
									class="Multiple" value="#7f7f7f" /></td>
							</tr>
							<tr id="Pg_C" class="trtop">
								<td>上页未选/手机上页:</td>
								<td><input type="text" name="eb_tp_tab_Header_color"
									class="Multiple" value="#b2b2b2" /></td>
							</tr>
							<tr id="Pg_C" class="trtop">
								<td>字型:</td>
								<td><input type="text" name="eb_tp_tab_Header_font"
									class="Multiple" value="#ffffff" /></td>
							</tr>

							<tr id="Pg_C" class="trtop">
								<td>布局风格:</td>
								<td><input type="text" id="eb_tp_tab_Font_style"
									name="eb_tp_tab_Font_style" class="FS_Auto" value="Arial" /></td>
							</tr>
							<tr id="Pg_C" class="trtop">
								<td>大小:</td>
								<td><input type="text" id="eb_tp_tab_Font_size"
									name="eb_tp_tab_Font_size" class="" value="12" /></td>
							</tr>

						</table>
						</div>

						</td>
					</tr>
					<tr>
						<td class="font8"><a class='pr17'>政策标题</a><a class="detail"
							name="tabtool" href="#" title="Edit"></a></td>
					</tr>
				</table>
				</td>
			</tr>

		</table>
		</div>
	</li>
	<li class="tab">
	<center><a href="#T_Cat"><img
		src="/images/ebay/template/layout_navbar_highlighed_small.png"
		alt='Nav.bar' Title='Nav.bar'>
	<font>页旁工具</font></a></center>
	</li>
	<li class="content">
		<div class="content-head"></div>
		<div id="T_Cat" class='P_height'>
		<table>

			<tr>
				<td colspan='5'>
				<table>
					<tr>
						<td style="font-weight: bold;">背景</td>
						<td style="width: 60px;"><select name="infobox_bkgd_type"
							class='CorP font8' id="infobox_bkgd_type">
							<option value='Color' selected>颜色</option>
							<option value='Pattern'>图案</option>
						</select></td>
						<td style="width: 100px;" id="infobox_bkgd_type_c" class="trtop"><input
							type="text" name="infobox_bkgd_color" class="Multiple in80 font8"
							id="infobox_bkgd_color" value="#ffffff" style="width: 70px" /></td>
						<td id="infobox_bkgd_type_p" class="trtop" style="display: none;"><input
							type='text' name='infobox_bkgd_pattern' id='infobox_bkgd_pattern'
							class='in80 infodetclass' value='' />&nbsp;<a
							name='infobox_bkgd_pattern_u' class='layout_select tip'></a>
						<div class='infobox_bkgd_pattern_u tooltip tipdiv'>
						<div class='navbar'>
						<table>
							<tr>
								<td>
								<table>
									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'>Background
										Pattern style 1 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
										Pattern style 2 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
										Pattern style 3 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
										Pattern style 4 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
										Pattern style 5 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
										Pattern style 6 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
										Pattern style 7 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
										Pattern style 8 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
										Pattern style 9 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>

									<tr>
										<td style='width: 200px;'><label style='width: 260px;'> <input
											type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
											value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
										Pattern style 10 <a
											style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
										</label></td>
									</tr>
								</table>
								</td>
								<td>
								<table>
									<tr>
										<td>
										<form target='iframe_upload' class='dropzone smimg font8'
											name='' title='infobox_bkgd_pattern' id='policy_up'
											method='POST' enctype='multipart/form-data'
											dz-message='Drop files here to upload'
											style='width: 210px; min-height: 50px;'>
										<div class='fallback'><input type='file' multiple='multiple'
											name='file' /><br />
										<input type='submit' name='action' value='Upload Now!' /><br />
										<div id='gallery_uploads'></div>
										</div>
										</form>
										</td>
									</tr>
								</table>
								</td>
							</tr>
						</table>
						</div>
						<input type='button' name='Go' class='goCancel btn_01 goright'
							value='Confirm' /></div>
						</td>
					</tr>
				</table>
				</td>
			</tr>
			<tr style="display: none;">
				<td colspan=2>
				<table>
					<tr>
						<td style="font-weight: bold;">Theme Style</td>
						<td><select name='Theme_id' id="Theme_id" class='font8'>
							<option value="">Select</option>
							<option value="Black">Black</option>
							<option value="Blue">Blue</option>
							<option value="Gold">Gold</option>
							<option value="Green">Green</option>
							<option value="Grey">Grey</option>
							<option value="Magenta">Magenta</option>
							<option value="Orange">Orange</option>
							<option value="Pink">Pink</option>
							<option value="Red">Red</option>
							<option value="YellowGreen">YellowGreen</option>
						</select></td>
						<td><a class='detail font8' name='infodetail' href='#'></a></td>
					</tr>

				</table>
				</td>
			</tr>
			<tr>
				<td colspan='5'>
				<hr>
				</td>
			</tr>
			<tr>
				<td colspan='5'>
				<div style="width: 240px;"><a id="create_info" class="info_click"
					style="float: left;" href='#'><img
					src="/images/ebay/template/add_property-26.png" alt='create info'
					title='Create Info' height="23" width="23"></a><a
					style="position: relative; top: 3px; left: 1px;">新增功能格</a> <a
					id="Pre_info" class="Pre_info" Title='Refresh View' href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='Pre_info'
					height="23" width="23"></a></div>
				</td>
			</tr>
			<tr>
				<td colspan=4>
				<form id='sortablef'>
		<!--		
				<ul id="sortable">
					<li class='sort infono1 delinfono1'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Search' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no1'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Search' /></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='1' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent1' value='' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono2 delinfono2 disno'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Store Cat.' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no2'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Shop Category' /></td>
							<td><a class='cat_details' title='Edit Detail' id='content2'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='2' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent2' value='' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono3 delinfono3'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Hot Item' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no3'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Hot Item' /></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='3' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent3' value='' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono4 delinfono4'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Picture' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no4'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Picture' /></td>
							<td><a class='pic_details' title='Edit Detail' id='content4'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='4' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent4'
							value='["<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl.'/images/ebay/template/sidebarPics/csm10.png';?>::"]' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono5 delinfono5'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='New List Item' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no5'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='New List Item' /></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='5' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent5' value='' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono6 delinfono6'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Text.Link' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no6'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Help & Info' /></td>
							<td><a class='text_details' title='Edit Detail' id='content6'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='6' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent6'
							value='["Payment Policy::#policy_html","Shipping Policy::#policy_html","Return Policy::#policy_html","FAQ::#policy_html","About Us::#policy_html"]' /></td>
						</tr>
					</table>
					</li>
					<li class='sort infono7 delinfono7 disno'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='QR Code' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no7'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='QR Code' /></td>
						</tr>
						<td class='disno'><input type='number' class='content_displayorder'
							name='content_displayorder' value='7' /><input type='text'
							class='content_target_display_mode'
							name='content_target_display_mode' value='' /><input type='text'
							class='infobox_content' name='infobox_content'
							name='infobox_content' id='addcontent7' value='' /></td>
						</tr>
					</table>
					</li>
				</ul>
		-->
				<ul id="sortable">
					<li class='sort infono0 delinfono0'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Search' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no0'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Search' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='content_displayorder' name='content_displayorder' value='0' />
							<input type='text' class='content_target_display_mode' name='content_target_display_mode' value='' />
							<input type='text' class='infobox_content' name='infobox_content' name='infobox_content' id='addcontent0' value='' />
						</td>
						</tr>
					</table>
					</li>
					<li class='sort infono1 delinfono1'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Picture' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no1'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Picture' />	
							</td>
							<td><a class='pic_details' title='Edit Detail' id='content1'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<td class='disno'>
							<input type='number' class='content_displayorder' name='content_displayorder' value='1' />
							<input type='text' class='content_target_display_mode' name='content_target_display_mode' value='' />
							<input type='text' class='infobox_content' name='infobox_content' name='infobox_content' id='addcontent1' value='["<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl.'/images/ebay/template/sidebarPics/csm10.png';?>::"]' />
						</td>
						</tr>
					</table>
					</li>
					<li class='sort infono2 delinfono2'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Hot Item' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no2'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Hot Item' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='content_displayorder' name='content_displayorder' value='2' />
							<input type='text' class='content_target_display_mode' name='content_target_display_mode' value='' />
							<input type='text' class='infobox_content' name='infobox_content' name='infobox_content' id='addcontent2' value='' />
						</td>
						</tr>
					</table>
					</li>
					<li class='sort infono4 delinfono4'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='content_type_id'
								name='content_type_id' value='Text.Link' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no4'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='content_text in110'
								name='content_text' value='Text.Link' /></td>
							<td><a class="text_details" title='Edit Detail' id='content4' href='#'><img src='/images/ebay/template/edit_property.png' alt='infodetail' height='16' height='16'></a></td>
						</tr>
						<td class='disno'>
							<input type='number' class='content_displayorder' name='content_displayorder' value='4' />
							<input type='text' class='content_target_display_mode' name='content_target_display_mode' value='' />
							<input type='text' class='infobox_content' name='infobox_content' name='infobox_content' id='addcontent4' value='["Payment Policy::#policy_html","Shipping Policy::#policy_html","Return Policy::#policy_html","FAQ::#policy_html","About Us::#policy_html"]' />
						</td>
						</tr>
					</table>
					</li>
				</ul>
				</form>
				<div style="width: 240px;"><a id="Pre_info" class="Pre_info"
					Title='Refresh View' href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='Pre_info'
					height="23" width="23"></a>
				</div>
				</td>
			</tr>
			<tr>
				<td colspan=4>*拉动盒子以改动在工具列的显示次序</td>
			</tr>
		</table>
		</div>
	</li>
	<li class="tab">
	<center><a href="#T_desc"><img
		src="/images/ebay/template/layout_desc.png" 
		alt='Desc' Title='Desc'>
	<font>产品资料</font></a></center>
	</li>
	<li class="content">
		<div class="content-head"></div>
		<div id="T_desc" class='P_height'>
		<table>
			<tr class='topname'>
				<td>产品描述部分
				<p class='toright'><a
					title='<span>Description Section:</span><br><br>Description Section includes Item Title, Item Descriptions. The actual wordings of Title and Descriptions are defined by each Datasheet item. But sellers can design the Color, Font Style, Point-form, Spacing of the contents.<br><br>If sellers want to show the Model and the Size of the item (defined in Datasheet) in this section, they just need to type a Label in the Model and Size field. (e.g. "Model", "SKU", etc).<br><br>If seller don’t want to show the Model and Size, just leave it BLANK.'
					class="tiplink" href="javascript:;">[?]</a></p>
				</td>
			</tr>
			<tr>
				<td>
				<div id="Desc">
				<table>
					<tr>
						<td class='topname'><a>标题 </a></td>
						<td><select class="font8" name="Title_ONNOFF" id="Title_ONNOFF">
							<option value="ON" selected="selected">ON</option>
							<option value="OFF">OFF</option>
						</select></td>
					</tr>
					<tr id='TitleONNOFF' style="">
						<td></td>
						<td class='in50'><input type="text" id="eb_tp_font_Title"
							name="eb_tp_font_Title" class="FS_Auto in50 font8" value="Impact" /></td>
						<td><select name='eb_tp_Title_Size' id="eb_tp_Title_Size"
							class="font8">
							<option value='10'>10</option>
							<option value='11'>11</option>
							<option value='12'>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
							<option value='21'>21</option>
							<option value='22'>22</option>
							<option value='23'>23</option>
							<option value='24'>24</option>
							<option value='25' selected>25</option>
							<option value='26'>26</option>
							<option value='27'>27</option>
							<option value='28'>28</option>
							<option value='29'>29</option>
							<option value='30'>30</option>
							<option value='31'>31</option>
							<option value='32'>32</option>
							<option value='33'>33</option>
							<option value='34'>34</option>
							<option value='35'>35</option>
							<option value='36'>36</option>
							<option value='37'>37</option>
							<option value='38'>38</option>
							<option value='39'>39</option>
							<option value='40'>40</option>
						</select></td>
						<td colspan=4><input type="text" name="eb_tp_clr_Title"
							class="Multiple font8" value="#4c4c4c" /><br>
						</td>
					</tr>
					<tr>

						<td class='topname'><a>详情 </a></td>

						<td class='in50'><input type="text" id="eb_tp_font_Description"
							name="eb_tp_font_Description" class="FS_Auto in50 font8"
							value="Arial" /></td>
						<td><select name="tb_eb_CFI_style_master_desc_fontSize"
							class="font8" id="DescriptionsfontSize">
							<option value='5'>5</option>
							<option value='6'>6</option>
							<option value='7'>7</option>
							<option value='8'>8</option>
							<option value='9'>9</option>
							<option value='10'>10</option>
							<option value='11'>11</option>
							<option value='12' selected>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
							<option value='21'>21</option>
							<option value='22'>22</option>
							<option value='23'>23</option>
							<option value='24'>24</option>
							<option value='25'>25</option>
							<option value='26'>26</option>
							<option value='27'>27</option>
							<option value='28'>28</option>
							<option value='29'>29</option>
							<option value='30'>30</option>
						</select></td>
						<td colspan=4><input type="text"
							name="eb_tp_clr_Description_details" class="Multiple in50 font8"
							value="#4c4c4c" /></td>
					</tr>


				</table>
				</div>
				</td>
			</tr>
			
			<tr>
				<td colspan=4 class='hr'></td>
			</tr>

			<tr>
				<td style="height: 27px;">
				<div class='topname' style="display: inline-block;">EagleGallery :</div>
				<select class='font8' name='tb_eb_CFI_style_master_HBP'
					id='tb_eb_CFI_style_master_HBP'>
					<option value="Yes">ON</option>
					<option value="No" selected="selected">OFF</option>
				</select> <a class='goset' target="_blank"
					href="#"></a></td>
			</tr>
			<tr>
				<td colspan=4 class='hr'></td>
			</tr>
		
			<tr>
				<td colspan='4'>

				<div style="width: 240px;"><a id="Desc_info" class="info_click"
					style="float: left;" href='#'><img
					src="/images/ebay/template/add_property-26.png" alt='create info'
					title='Create Info' height="23" width="23"></a><a
					style="position: relative; top: 4px; left: 1px"> 新增功能框</a>
				<a id="D_Pre_info" class="D_Pre_info" Title='Refresh View' href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='Pre_info'
					height="23" width="23"></a></div>


				</td>
			</tr>
			<tr>
				<td colspan=4>
				<form id="dsortablef">
		<!--
				<ul id="dsortable">
					<li class='dsort infono1 delinfono14'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='d_content_type_id'
								name='d_content_type_id' value='Basic Descriptions'
								readonly='readonly' /></td>
							<td style=''><a class='btn_del' href='#' id='no14'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='d_content_text' name='d_content_text'
								value='Basic Descriptions' /></td>
							<td><a class='d_desc_details detail' name='d_desc_details'
								title='Edit Detail' id='content1' href='#'><img
								src='/images/ebay/template/edit_property.png' alt='create_info'
								height='16' height='16'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box position:</td>
							<td><select class='d_content_pos' name='d_content_pos'>
								<option value='above_product_Photo'>Above Product Photo</option>
								<option value='Next_to_Product_Photo' selected>Next to Product
								Photo</option>
								<option value='Below_All_Product_Photo'>Below All Product Photo</option>
							</select></td>
						</tr>
						<tr class='font8' style=''>
							<td>Box width:</td>
							<td><select class='d_content_width r80' name='d_content_width'>
								<option value='full'>Full Width</option>
								<option value='1/2' selected>1/2 width</option>
								<option value='1/3'>1/3 width</option>
								<option value='2/3'>2/3 width</option>
								<option value='1/4'>1/4 width</option>
								<option value='2/4'>2/4 width</option>
								<option value='3/4'>3/4 width</option>
							</select><select class='d_content_align' name='d_content_align'>
								<option value='left' selected>Left</option>
								<option value='center'>Center</option>
								<option value='right'>Right</option>
							</select></td>
						</tr>
						<tr class='font8'>
							<td class='disno'><input type='number'
								class='d_content_displayorder' name='d_content_displayorder'
								value='1' /><input type='text' class='d_infobox_content'
								name='d_infobox_content' id='d_addcontent1' value="" /><input
								type='text' class='d_infobox_en_key' name='d_infobox_en_key'
								value=''></td>
						</tr>
					</table>
					</li>
					<li class='dsort infono2 delinfono15 disno'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='d_content_type_id'
								name='d_content_type_id' value='Item Specifics'
								readonly='readonly' /></td>
							<td style=''><a class='btn_del' href='#' id='no15'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='d_content_text' name='d_content_text'
								value='Item Specifics' /></td>
							<td><a class='d_item_details' title='Edit Detail' id='content2'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box position:</td>
							<td><select class='d_content_pos' name='d_content_pos'>
								<option value='above_product_Photo'>Above Product Photo</option>
								<option value='Next_to_Product_Photo'>Next to Product Photo</option>
								<option value='Below_All_Product_Photo' selected>Below All Product
								Photo</option>
							</select></td>
						</tr>
						<tr class='font8' style=''>
							<td>Box width:</td>
							<td><select class='d_content_width r80' name='d_content_width'>
								<option value='full' selected>Full Width</option>
								<option value='1/2'>1/2 width</option>
								<option value='1/3'>1/3 width</option>
								<option value='2/3'>2/3 width</option>
								<option value='1/4'>1/4 width</option>
								<option value='2/4'>2/4 width</option>
								<option value='3/4'>3/4 width</option>
							</select><select class='d_content_align' name='d_content_align'>
								<option value='left' selected>Left</option>
								<option value='center'>Center</option>
								<option value='right'>Right</option>
							</select></td>
						</tr>
						<tr class='font8'>
							<td class='disno'><input type='number'
								class='d_content_displayorder' name='d_content_displayorder'
								value='2' /><input type='text' class='d_infobox_content'
								name='d_infobox_content' id='d_addcontent2'
								value="radioattr_t=pic&attr_t_c=&Title_f_FS=Arial&Title_f_size=14&Title_f_c=ffffff&Title_f_align=left&radioattr_b=color&attr_b_b=&attr_b_c=ffffff&radioattr_b2=color&attr_b2_b=&attr_b2_c=e5e5e5&Text_f_FS=&Text_f_size=12&Text_f_c=&Text_f_align=left&Bold_label=yes&attributes_separator=%3A&attributes_border_style=solid&attributes_size=1&attributes_color=e5e5e5" /><input
								type='text' class='d_infobox_en_key' name='d_infobox_en_key'
								value=''></td>
						</tr>
					</table>
					</li>
					<li class='dsort infono3 delinfono16 disno'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='d_content_type_id'
								name='d_content_type_id' value='Action button' readonly='readonly' /></td>
							<td style=''><a class='btn_del' href='#' id='no16'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='d_content_text' name='d_content_text'
								value='Action button' /></td>
							<td><a class='D_call_for_action' title='Edit Detail' id='content3'
								href='#'><img src='/images/ebay/template/edit_property.png'
								alt='create_info' height='16' height='16'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box position:</td>
							<td><select class='d_content_pos' name='d_content_pos'>
								<option value='above_product_Photo'>Above Product Photo</option>
								<option value='Next_to_Product_Photo' selected>Next to Product
								Photo</option>
								<option value='Below_All_Product_Photo'>Below All Product Photo</option>
							</select></td>
						</tr>
						<tr class='font8' style=''>
							<td>Box width:</td>
							<td><select class='d_content_width r80' name='d_content_width'>
								<option value='full'>Full Width</option>
								<option value='1/2' selected>1/2 width</option>
								<option value='1/3'>1/3 width</option>
								<option value='2/3'>2/3 width</option>
								<option value='1/4'>1/4 width</option>
								<option value='2/4'>2/4 width</option>
								<option value='3/4'>3/4 width</option>
							</select><select class='d_content_align' name='d_content_align'>
								<option value='left'>Left</option>
								<option value='center' selected>Center</option>
								<option value='right'>Right</option>
							</select></td>
						</tr>
						<tr class='font8'>
							<td class='disno'><input type='number'
								class='d_content_displayorder' name='d_content_displayorder'
								value='3' /><input type='text' class='d_infobox_content'
								name='d_infobox_content' id='d_addcontent3'
								value="Action_button_style=vert&aset_f_FS=Arial&aset_f_size=35&aset_f_c=ff0000&bla_h=bla_h&pt_aabl=Place+Bid&attr_aabl_c=&pt_aabl_f_FS=Arial&pt_aabl_f_size=18&pt_aabl_f_c=ffffff&fp_buy=Buy+Now&radioattr_buy=pic&attr_buy_c=&buy_f_FS=Arial&buy_f_size=18&buy_f_c=ffffff&algtci=algtci&tfl=Don't+want+to+wait.&fpabl=Direct+Buy+&radioattr_fpabl=pic&attr_fpabl_c=&fpabl_f_FS=Arial&fpabl_f_size=18&fpabl_f_c=ffffff&Action_button_style_f=none&Action_button_size_f=1&Action_button_color_f=" /><input
								type='text' class='d_infobox_en_key' name='d_infobox_en_key'
								value=''></td>
						</tr>
					</table>
					</li>
					<li class='dsort infono4 delinfono17 disno'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='d_content_type_id'
								name='d_content_type_id' value='EagleGallery' readonly='readonly' /></td>
							<td style='display: none'><a class='btn_del' href='#' id='no17'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='d_content_text' name='d_content_text'
								value='EagleGallery' /></td>
						</tr>
						<tr class='font8'>
							<td>Box position:</td>
							<td><select class='d_content_pos' name='d_content_pos'>
								<option value='above_product_Photo'>Above Product Photo</option>
								<option value='Next_to_Product_Photo'>Next to Product Photo</option>
								<option value='Below_All_Product_Photo' selected>Below All Product
								Photo</option>
							</select></td>
						</tr>
						<tr class='font8' style='display: none'>
							<td>Box width:</td>
							<td><select class='d_content_width r80' name='d_content_width'>
								<option value='full' selected>Full Width</option>
								<option value='1/2'>1/2 width</option>
								<option value='1/3'>1/3 width</option>
								<option value='2/3'>2/3 width</option>
								<option value='1/4'>1/4 width</option>
								<option value='2/4'>2/4 width</option>
								<option value='3/4'>3/4 width</option>
							</select><select class='d_content_align' name='d_content_align'>
								<option value='left' selected>Left</option>
								<option value='center'>Center</option>
								<option value='right'>Right</option>
							</select></td>
						</tr>
						<tr class='font8'>
							<td class='disno'><input type='number'
								class='d_content_displayorder' name='d_content_displayorder'
								value='4' /><input type='text' class='d_infobox_content'
								name='d_infobox_content' id='d_addcontent4' value="" /><input
								type='text' class='d_infobox_en_key' name='d_infobox_en_key'
								value=''></td>
						</tr>
					</table>
					</li>
				</ul>
		-->		
				<ul id="dsortable">
					<li class='dsort infono1 delinfodno1'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='d_content_type_id'
								name='d_content_type_id' value='Basic Descriptions'
								readonly='readonly' /></td>
							<td style=''><a class='btn_del' href='#' id='dno1'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='d_content_text' name='d_content_text'
								value='Basic Descriptions' /></td>
							<td><a class='d_desc_details detail' name='d_desc_details'
								title='Edit Detail' id='content1' href='#'><img
								src='/images/ebay/template/edit_property.png' alt='create_info'
								height='16' height='16'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框位置:</td>
							<td><select class='d_content_pos' name='d_content_pos'>
								<option value='above_product_Photo' >Above Product Photo</option>
								<option value='Next_to_Product_Photo' selected >Next to Product Photo</option>
								<option value='Below_All_Product_Photo' >Below All Product Photo</option>
							</select></td>
						</tr>
						<tr class='font8' style=''>
							<td>横幅宽度:</td>
							<td><select class='d_content_width r80' name='d_content_width'>
								<option value='1/2' selected >1/2 width</option>
								<option value='1/3'  >1/3 width</option>
								<option value='2/3'  >2/3 width</option>
								<option value='1/4'  >1/4 width</option>
								<option value='2/4'  >2/4 width</option>
								<option value='3/4' >3/4 width</option>
							</select><select class='d_content_align' name='d_content_align'>
								<option value='left' selected >左边</option>
								<option value='center'  >置中</option>
								<option value='right'  >右边</option>
							</select></td>
						</tr>
						<tr class='font8'>
							<td class='disno'>
							<input type='number' class='d_content_displayorder' name='d_content_displayorder' value='1' />
							<input type='text' class='d_infobox_content' name='d_infobox_content' id='d_addcontent1' value="" />
							<input type='text' class='d_infobox_en_key' name='d_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
				</ul>
				</form>
				<div style="width: 240px;"><a id="D_Pre_info" class="D_Pre_info"
					Title='Refresh View' href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='Pre_info'
					height="23" width="23"></a></div>
				</td>
			</tr>
			<tr>
				<td colspan=4>*拉动盒子以改动在工具列的显示次序</td>
			</tr>
		</table>
		</div>
	</li>
	<li class="tab">
	<center><a href="#T_Mobile"><img
		src="/images/ebay/template/layout_mobile_highlight_small.png"
		alt='Mobile' Title='Mobile'>
	<font>手机模板</font></a></center>
	</li>
	<li class="content">
		<div class="content-head"></div>
		<div id="T_Mobile" class='P_height'>
		<div id="Mobile_buy">
		<center><br>
		<br>
		<strong style="font-size: 15px;">Mobile Template Generator</strong><br>
		<br>
		<br>
		<a target='_blank' href='#' class="mbbutton"
			disabled="disabled"
			style="background-color: rgb(92, 226, 29); color: rgb(255, 255, 255); font-size: 20px; font-family: Arial;">Upgrade
		Now</a> <br>
		<br>
		<br>
		<br>
		<img src="/images/ebay/template/mobile_250.jpg" alt=' info' title=''>
		</center>
		</div>

		<div id="Mobile_true"><br>
		手机模板 开 / 关 <select name='eb_tp_mobile_true'
			id='eb_tp_mobile_true'>
			<option value="1" selected="selected">On</option>
			<option value="0">Off</option>
		</select><br>
		<br>

		<table>
			<tr>
				<td style="width: 200px;" colspan='4'><a id="Mobile_info" href='#'><img
					src="/images/ebay/template/add_property-26.png" alt='create info'
					title='Create mobile' height="23" width="23"></a> <a
					style="position: relative; top: -7px;">新增手机
				</a> <a
					id="m_Pre_info" class="m_Pre_info" href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='m_Pre_info'
					height="23" width="23"></a></td>
			</tr>
			<tr>
				<td colspan=2>
				<form id="msortablef">
		<!--
				<ul id="msortable">
					<li class='msort infono1 delinfono8'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Shop Name banner'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no8'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Shop Name banner' /></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='1' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content1' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
					<li class='msort infono2 delinfono9'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Hori. Product Photo'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no9'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Mobile Picture' /></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='2' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content2' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
					<li class='msort infono3 delinfono10'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Description' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no10'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Description' /></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='3' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content3' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
					<li class='msort infono4 delinfono11'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Item Specifics'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no11'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Item Specifics' /></td>
							<td><a class='m_item_details' name='' title='Edit Detail'
								id='m_content4' href='#'><img
								src='/images/ebay/template/edit_property.png' alt='create_info'
								height='16' height='16'></a></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='4' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content4' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
					<li class='msort infono5 delinfono12'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Poster' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no12'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Poster' /></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='5' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content5' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
					<li class='msort infono6 delinfono13'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>Box Type:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Policy' readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='no13'></a></td>
						</tr>
						<tr class='font8'>
							<td>Box Title:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Policy' /></td>
							<td><a class='detail' name='tabtool' title='Edit Detail'
								id='m_content6' href='#'><img
								src='/images/ebay/template/edit_property.png' alt='create_info'
								height='16' height='16'></a></td>
						</tr>
						<td class='disno'><input type='number'
							class='m_content_displayorder' name='m_content_displayorder'
							value='6' /><input type='text'
							class='m_content_target_display_mode'
							name='m_content_target_display_mode' value='' /><input type='text'
							class='m_infobox_content' name='m_infobox_content'
							id='m_addm_content6' value='' /><input type='text'
							class='m_infobox_en_key' name='m_infobox_en_key' value=''></td>
						</tr>
					</table>
					</li>
				</ul>
		-->
				<ul id="msortable">
													
					<li class='msort infono1 delinfomobileno1'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Shop Name banner'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='mobileno1'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Shop Name banner' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='m_content_displayorder' name='m_content_displayorder' value='1' />
							<input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value='' />
							<input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content1' value='' />
							<input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value=''>
						</td>
						</tr>
					</table>
					</li>
							
					<li class='msort infono2 delinfomobileno2'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Hori. Product Photo'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='mobileno2'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Mobile Picture' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='m_content_displayorder' name='m_content_displayorder' value='2' />
							<input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value='' />
							<input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content2' value='' />
							<input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value=''>
						</td>
						</tr>
					</table>
					</li>
							
					<li class='msort infono3 delinfomobileno3'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Description'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='mobileno3'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Description' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='m_content_displayorder' name='m_content_displayorder' value='3' />
							<input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value='' />
							<input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content3' value='' />
							<input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value=''>
						</td>
						</tr>
					</table>
					</li>
							
					<li class='msort infono6 delinfomobileno6'>
					<table class='info7 infono'>
						<tr class='font8'>
							<td>功能框种类:</td>
							<td><input type='text' class='m_content_type_id'
								name='m_content_type_id' value='Policy'
								readonly='readonly' /></td>
							<td><a class='btn_del' href='#' id='mobileno6'></a></td>
						</tr>
						<tr class='font8'>
							<td>功能框标题:</td>
							<td><input type='text' class='m_content_text in110'
								name='m_content_text' value='Policy' /></td>
						</tr>
						<td class='disno'>
							<input type='number' class='m_content_displayorder' name='m_content_displayorder' value='6' />
							<input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value='' />
							<input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content6' value='' />
							<input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value=''>
						</td>
						</tr>
					</table>
					</li>
				</ul>

				</form>
				<a id="m_Pre_info" class="m_Pre_info" href='#'><img
					src="/images/ebay/template/sinchronize.png" alt='m_Pre_info'
					height="23" width="23"></a></td>
			</tr>
		</table>
		*拉动盒子以改动在工具列的显示次序</div>

		</div>
	</li>
	<li class="tab" style='display: none;'>
	<center><a href="#T_Path"><img
		src="/images/ebay/template/layout_setting_highligh_small.png"
		alt='Path' Title='Path'>
	<font>Setting</font></a></center>
	</li>
	<li class="content">
		<div class="content-head"></div>
		<div id="T_Path" class='P_height'>
		<table class="">
			<tr>
				<td>
				<div class="top">File URL Settings</div>
				</td>
			</tr>
			<tr>
				<td class="font8">
				<div class="left2">Photo Based-URL:</div>
				</td>
			</tr>
			<tr>
				<td align="left" colspan='4'><input type="text" class='o100b'
					name="graphic_setting_photo_base_path" id='BasedPath'
					placeholder="[http://www.example.com/images/]" value=""></td>
			</tr>
			<tr>
				<td class="font8">
				<div class="left2">Gallery Photo Folder/URL:</div>
				</td>

			</tr>
			<tr>
				<td align="left" colspan='4'><input type="text" class='o100b'
					name="graphic_setting_gallery_path" id='Gallery'
					placeholder="[gallery/]" value=""></td>
			</tr>
			<tr>
				<td class="font8">
				<div class="left2">Product Photo Folder/URL:</div>
				</td>

			</tr>
			<tr>
				<td align="left" colspan='4'><input type="text" class='o100b'
					name="graphic_setting_product_path" id='Product'
					placeholder="[Product/]" value=""></td>
			</tr>
			<tr>
				<td class="font8">
				<div class="left2">Poster Photo Folder/URL:</div>
				</td>
			</tr>
			<tr>
				<td align="left" colspan='4'><input type="text" class='o100b'
					name="graphic_setting_poster_path" id='Poster'
					placeholder="[Poster/]" value=""></td>
			</tr>
			<tr>
				<td class="font8">
				<div class="left2">Admin Photo Folder/URL:</div>
				</td>

			</tr>
			<tr>
				<td align="left"><input type="text" class='o100b'
					name="graphic_setting_admin_path" id='Admin' placeholder="[Admin/]"
					value=""></td>
			</tr>

			<tr>
				<td>
				<div class="top">General Settings</div>
				</td>
			</tr>

			<tr>
				<td class='font8 cml2'>
				<div class="">Show Product Pictures?</div>
				
				</td>
			</tr>
			<tr>
				<td><select name='product_photo_ONNOFF' id='product_photo_ONNOFF'
					class='font8'>
					<option value="ON" selected="selected">ON</option>
					<option value="OFF">OFF</option>
				</select></td>
			</tr>
			<tr class='product_photo_need_hide'>
				<td class='font8 cml2'>
				<div class="">Show Gallery Pictures again in HTML ?
				</div>
				</td>
			</tr>
			<tr class='product_photo_need_hide'>
				<td><select name='tb_eb_CFI_style_master_GPA' class='font8'>
					<option value="Yes" selected="selected">Yes</option>
					<option value="No">No</option>
				</select></td>
			</tr>
			<tr>
				<td>
				<div class="top">Photo File Name Generate Settings</div>
				</td>
			</tr>

			<tr>
				<td colspan='4' class="font8">Auto filename separate Symbol:</td>
			</tr>
			<tr>
				<td class="font8"><select class="Autoimg"
					name="graphic_setting_Auto_file_Separate" class="font8">
					<option value="-" selected="selected">- (Dash sign)</option>
					<option value="_">_ Underscore sign)</option>
					<option value="=">= (Equals sign)</option>
					<option value="">(Not Anything)</option>
				</select></td>
			</tr>
			<tr>
				<td class="font8">Auto filename Leading Zero:<select class="Autoimg"
					name="graphic_setting_Auto_Leading_Zero" class="inputwidth">
					<option value="Yes" selected="selected">Yes</option>
					<option value="No">No</option>
				</select></td>
				<td align="left">

				<div class="Autotip16 tooltip tipdiv">Auto filename Leading Zero.</div>
				</td>
			</tr>
			<tr>
				<td class="font8">Auto filename Format:<select class="Autoimg"
					name="graphic_setting_Auto_file_Format" class="inputwidth">
					<option value="jpg" selected="selected">jpg</option>
					<option value="jpeg">jpeg</option>
					<option value="gif">gif</option>
				</select></td>
				<td align="left">
				<div class="Auto18 tooltip tipdiv">Auto filename Format.</div>
				</td>
			</tr>

			<tr>
				<td>
				<div class="top">Javascript function</div>
				</td>
			</tr>

			<tr>
				<td class="font8">Javascript function:<select class="Autoimg"
					name="javascript_fun" class="inputwidth">
					<option value="auto" selected="selected">Auto</option>
					<option value="enable">Enable</option>
					<option value="disable">Disable</option>
				</select></td>
			</tr>

		</table>
		</div>
		
	</li>
	<li class="tab" >
		<center><a href="#T_Path"><img
					src="/images/ebay/template/layout_setting_highligh_small.png"
					alt='Path' Title='Path'>
				<font>更改排版</font></a></center>
	</li>
	<li class="content">
		<div class="content-head" id="switchType"></div>
		<div>
			<input type="radio" value="layout_left"  name="switchType"><label>左边</label>
		</div>
		<div>
			<input type="radio" value="layout_right"  name="switchType"><label>右边</label>
		</div>
		<div>
			<input type="radio" value="layout"  name="switchType"><label>无</label>
		</div>
	</li>
</ul>
</div>
<div id="dialogflash" title="Basic modal dialog">
<div id="flash"></div>
</div>

<div id='butset'>
<?php if(!empty($_REQUEST['template_id'])):?>
	<a id="save" href='#' title='Save'>
		<img src="/images/ebay/template/save.png" alt='save' height="25" width="25">
	</a> 
<?php endif;?>
	<a id="saveas" href='#' title='Save As'>
		<img src="/images/ebay/template/save_as.png" alt='save_as' height="25" width="25">
	</a> 
<?php if(!empty($_REQUEST['template_id'])):?>
	<a id="del" href='#' style="margin-left: -2px;" title='Delete Template'>
		<img src="/images/ebay/template/trash.png" alt='Delete' height="25" width="25">
	</a>

<?php endif;?> 
	<a id="" class='showall' style="float: right;" href='#' title='Load Sample Item to Preview'>
		<img src="/images/ebay/template/open_in_browser-26.png" alt='save_as' height="25" width="25">
	</a>
</div>
<div id='MenuD'>
<table class="info3">
	<tr>

	</tr>
	<tr>
		<td class="name150">菜单风格:</td>
		<td align="left"><input type='text'
			name='tb_shop_master_Setting_menu_bar'
			id='tb_shop_master_Setting_menu_bar'
			class='new inputwidth imgborder infodetclass'
			value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png' />&nbsp;<a
			name='tb_shop_master_Setting_menu_bar_u'
			class='layout_select tip folder26'></a>
		<div class='tb_shop_master_Setting_menu_bar_u tooltip tipdiv'>
		<div class='navbar'>
		<table>
			<tr>
				<td>
				<table>
					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
						Pattern style 1 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'>Background
						Pattern style 2 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
						Pattern style 3 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
						Pattern style 4 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
						Pattern style 5 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
						Pattern style 6 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png'>Background
						Pattern style 7 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png'>Background
						Pattern style 8 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png'>Background
						Pattern style 9 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
						Pattern style 10 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
						Pattern style 11 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png'>Background
						Pattern style 12 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png'>Background
						Pattern style 13 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png'>Background
						Pattern style 14 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png'>Background
						Pattern style 15 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png'>Background
						Pattern style 16 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png'>Background
						Pattern style 17 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png'>Background
						Pattern style 18 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png'>Background
						Pattern style 19 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png'>Background
						Pattern style 20 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png'>Background
						Pattern style 21 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png'>Background
						Pattern style 22 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png'>Background
						Pattern style 23 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png'>Background
						Pattern style 24 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png'>Background
						Pattern style 25 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png'>Background
						Pattern style 26 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png'>Background
						Pattern style 27 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png'>Background
						Pattern style 28 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='MENU_r'
							class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png'>Background
						Pattern style 29 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>
				</table>
				</td>
				<td>
				<table>
					<tr>
						<td>
						<form target='iframe_upload' class='dropzone smimg font8' name=''
							title='tb_shop_master_Setting_menu_bar' id='policy_up'
							method='POST' enctype='multipart/form-data'
							dz-message='Drop files here to upload'
							style='width: 210px; min-height: 50px;'>
						<div class='fallback'><input type='file' multiple='multiple'
							name='file' /><br />
						<input type='submit' name='action' value='Upload Now!' /><br />
						<div id='gallery_uploads'></div>
						</div>
						</form>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		</div>
		<input type='button' name='Go' class='goCancel btn_01 goright'
			value='Confirm' />
		</div>
		</td>

	</tr>

	<tr>
		<td class="name150">文本链接1:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_DT" class='new inputwidth'
			id="DESCRIPTION" value="DESCRIPTION"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">链接1点击网址:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_DT_t" class='new inputwidth'
			id="DESCRIPTION" value="#"></td>
	</tr>
	<tr>
		<td class="name150">文本链接2:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_PM" class='new inputwidth'
			id="PAYMENT" value="PAYMENT"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">链接2的目标:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_PM_t" class='new inputwidth'
			id="DESCRIPTION" value="#"></td>
	</tr>
	<tr>
		<td class="name150">文本链接3:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_SP" class='new inputwidth'
			id="SHIPPING" value="SHIPPING"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">链接3的目标:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_SP_t" class='new inputwidth'
			id="DESCRIPTION" value="#"></td>
	</tr>
	<tr>
		<td class="name150">文本链接4:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_RP" class='new inputwidth'
			id="RETURN" value="RETURN POLICY"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">链接4的目标:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_RP_t" class='new inputwidth'
			id="DESCRIPTION" value="#"></td>
	</tr>
	<tr>
		<td class="name150">文本链接5:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_CU" class='new inputwidth'
			id="CONTACT" value="CONTACT US"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">链接5的目标:</td>
		<td align="left"><input type="text"
			name="tb_shop_master_Setting_menu_CU_t" class='new inputwidth'
			id="DESCRIPTION" value="#"></td>
	</tr>

</table>
<table class="info3">
	<tr>
		<td class="name150">字体样式:</td>
		<td align="left"><input type="text" name="eb_tp_font_style_menurow"
			class='new inputwidth FS_Auto' id="FontStyle" value="Arial"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">字体大小:</td>
		<td align="left"><select name='tb_eb_CFI_style_master_menu_font_size'
			id="FontSize" style="width: 100px;">
			<option value='1'>1</option>
			<option value='2'>2</option>
			<option value='3'>3</option>
			<option value='4'>4</option>
			<option value='5'>5</option>
			<option value='6'>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12' selected>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17'>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
		</select></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">字体颜色:</td>
		<td align="left"><input type="text" name="eb_tp_clr_Font_Color"
			class='new inputwidth Multiple' id="FontColor" value="#f7f7f6"></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
	<tr>
		<td class="name150">文本分隔符:</td>
		<td align="left"><label> <input type="radio"
			name="tb_eb_CFI_style_master_menu_separator" value="Yes" CHECKED />YES</label><label>
		<input type="radio" name="tb_eb_CFI_style_master_menu_separator"
			value="No" />NO</label></td>
		<td align="left">

		<div class="tipmb1 tooltip tipdiv"></div>
		</td>
	</tr>
</table>
</div>
<div id="NBanner">
<table class="">
	<tr>
		<td></td>
	</tr>
	<table class="info6 nbynn">
		<tr>
			<td>
				<input type="radio" name="NBR" class="R_UNL" value="upload"
				CHECKED>&nbsp;<img src="/images/ebay/template/upload-26.png"
				title='Upload file' height="23" width="23">&nbsp;Upload file 
				<input type="radio" name="NBR" class="R_UNL" value="File">&nbsp;
				<img
				src="/images/ebay/template/link-26.png" title='Link file'
				height="23" width="23">&nbsp;Link file 
				<input type="radio" name="NBR" class="R_UNL" value="lin">&nbsp;
				<img src="/images/ebay/template/stack_of_photos-26.png"
				title='Media library' height="23" width="23">&nbsp;Media library 
				<input style="display: none;" type="radio" name="NBR" class="R_UNL" value="Self">&nbsp;
				<img style="display: none;" src="/images/ebay/template/stack_of_photos-26.png"
				title='Self library' height="23" width="23"><!-- &nbsp;Self Library -->
				<br />
			<br />
			</td>
			<td></td>
			<td></td>
		</tr>
		<tr class='NB_upload'>
			<td colspan='2'>
			<form target="iframe_upload" class="dropzone"
				name="graphic_setting_Notice_Banner" title='NB' id="form_upload"
				method='POST' enctype="multipart/form-data"
				dz-message="Drop files here to upload" style="width: 215px;">
			<div class="fallback"><input type="file" multiple="multiple"
				name="file" /><br />
			<input type="submit" name="action" value="Upload Now!" /><br />
			<div id="gallery_uploads"></div>
			</div>
			</form>
			<center>
			<div class='NB_upfin upfin'>Finish</div>
			</center>
			</td>
			<td align="left"></td>
		</tr>
		<tr class='NB_url'>
			<td>Notice Banner File/URL:<input style="width: 280px" type="text"
				class='new imgborder' name="graphic_setting_Notice_Banner"
				id='graphic_setting_Notice_Banner' value=""> <a name="NB"
				id='layout_select_NB' class="layout_select tip"></a> <a name="Admin"
				class="DLimg" href="#" title="graphic_setting_Notice_Banner"></a> <a
				name="Admin" class="preview" href="#"
				title="graphic_setting_Notice_Banner" id='NB_P'></a></td>

		</tr>
		<tr>
			<td>
			<div class='NB tooltip tipdiv'>
			<div class='height400'>
			<table>
				<tr class='imgmyself'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r' value=''> <img src='' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/00.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/00.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/01.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/01.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/02.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/02.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/03.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/03.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/04.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/04.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/05.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/05.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/06.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/06.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/07.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/07.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/08.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/08.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/09.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/09.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/10.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/10.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/11.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/11.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/12.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/12.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/13.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/13.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/14.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/14.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/15.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/15.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/16.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/16.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/17.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/17.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/18.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/18.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/19.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/19.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/20.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/20.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/21.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/21.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/22.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/22.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/23.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/23.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/24.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/24.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/25.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/25.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/26.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/26.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/27.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/27.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/28.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/28.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/29.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/29.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/30.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/30.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/31.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/31.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/32.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/32.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/33.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/33.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/34.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/34.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/35.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/35.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/36.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/36.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/37.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/37.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/38.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/38.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/39.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/39.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/40.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/40.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/41.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/41.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/42.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/42.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/43.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/43.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
				<tr class='imgmedia'>
					<td style='width: 300px;'><label style='width: 250px;'> <input
						type='radio' name='NB_r'
						value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/44.png'> <img
						src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/44.png' height='50'
						width='200' alt='img'> </label></td>
				</tr>
			</table>
			</div>
			<input type='button' name='Go' class='goCancel btn_01 goright'
				value='Confirm' /></div>
			</td>
		</tr>
		<tr style="display: none" id="graphic_setting_Notice_Banner_HW"
			class="HWdisplay">
			<td class="name">>> Width:<input type="text" class='HW_W inputHW'
				name="graphic_setting_Notice_Banner_W" id="HW_W" value="0"></td>
			<td class="name100">Height:<input type="text"
				name="graphic_setting_Notice_Banner_H" class='HW_H inputHW'
				id="HW_H" value="0"></td>
		</tr>
		<tr style="display: none;">
			<td colspan='5'>Click-to URL: <input type="text" class='in200'
				name="graphic_setting_Notice_Banner_Target" style="width: 344PX;"
				id="graphic_setting_Notice_Banner_Target" value="" /></td>
		</tr>
	</table>
</table>

<div style="display: none;"><textarea
	id="sh_ch_info_text_Shop_Notice_Content"
	name="sh_ch_info_text_Shop_Notice_Content" class="ckeditor">test</textarea>
</div>
</div>
<div id='infodetail' style='width: 425px' title="infobox detail">
<table class="">
	<tr>
		<td colspan=4></td>
	</tr>
	<tr>
		<td colspan=4 class='top'><img
			src="/images/ebay/template/top_navigation_toolbar-26.png"
			height="25" width="25"><a class='top_Title'>&nbsp;Title Bar:</a></td>
	</tr>
	<tr>
		<td class='top' style="width: 125px;">Border:</td>
		<td class='in80'>
		<table>
			<tr>
				<td><select name='cat_layoutborder_style'
					id='cat_layoutborder_style' class='infodetclass'
					style='width: 60px;'>
					<option value='none'>none</option>
					<option value='solid' selected>solid</option>
					<option value='dashed'>dashed</option>
					<option value='dotted'>dotted</option>
					<option value='double'>double</option>
					<option value='groove'>groove</option>
					<option value='ridge'>ridge</option>
					<option value='inset'>inset</option>
					<option value='outset'>outset</option>
				</select></td>
			</tr>
		</table>
		</td>
		<td><select name='cat_layoutborder_size' id="cat_layoutborder_size"
			class="infodetclass">
			<option value='1' selected>1</option>
			<option value='2'>2</option>
			<option value='3'>3</option>
			<option value='4'>4</option>
			<option value='5'>5</option>
			<option value='6'>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12'>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17'>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
		</select></td>
		<td><input type="text" name="cat_layoutborder_color"
			id="cat_layoutborder_color" class="infodetclass Multiple"
			value="#dddddd" /></td>
	</tr>
	<tr>
		<td class='top'>Background</td>
		<td class='top'><select name="title_bkgd_type"
			class='CorP infodetclass' id="title_bkgd_type">
			<option value='Color' selected>Color</option>
			<option value='Pattern'>Pattern</option>
		</select></td>
		<td id='title_bkgd_type_p' style="display: none;" colspan=3><input
			type='text' name='title_bkgd_pattern' id='title_bkgd_pattern'
			class=' infodetclass' value='' />&nbsp;<a name='title_bkgd_pattern_u'
			class='layout_select tip folder26'></a>
		<div class='title_bkgd_pattern_u tooltip tipdiv'>
		<div class='navbar'>
		<table>
			<tr>
				<td>
				<table>
					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
						Pattern style 1 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'>Background
						Pattern style 2 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
						Pattern style 3 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
						Pattern style 4 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
						Pattern style 5 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
						Pattern style 6 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png'>Background
						Pattern style 7 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png'>Background
						Pattern style 8 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png'>Background
						Pattern style 9 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
						Pattern style 10 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
						Pattern style 11 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png'>Background
						Pattern style 12 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png'>Background
						Pattern style 13 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png'>Background
						Pattern style 14 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png'>Background
						Pattern style 15 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png'>Background
						Pattern style 16 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png'>Background
						Pattern style 17 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png'>Background
						Pattern style 18 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png'>Background
						Pattern style 19 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png'>Background
						Pattern style 20 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png'>Background
						Pattern style 21 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png'>Background
						Pattern style 22 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png'>Background
						Pattern style 23 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png'>Background
						Pattern style 24 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png'>Background
						Pattern style 25 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png'>Background
						Pattern style 26 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png'>Background
						Pattern style 27 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png'>Background
						Pattern style 28 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='title_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png'>Background
						Pattern style 29 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>
				</table>
				</td>
				<td>
				<table>
					<tr>
						<td>
						<form target='iframe_upload' class='dropzone smimg font8' name=''
							title='title_bkgd_pattern' id='policy_up' method='POST'
							enctype='multipart/form-data'
							dz-message='Drop files here to upload'
							style='width: 210px; min-height: 50px;'>
						<div class='fallback'><input type='file' multiple='multiple'
							name='file' /><br />
						<input type='submit' name='action' value='Upload Now!' /><br />
						<div id='gallery_uploads'></div>
						</div>
						</form>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		</div>
		<input type='button' name='Go' class='goCancel btn_01 goright'
			value='Confirm' /></div>
		</td>
		<td colspan=3 id='title_bkgd_type_c'><input type="text"
			name="title_bkgd_color" class="Multiple infodetclass"
			id="title_bkgd_color" value="#5bc7d1" /></td>
	</tr>



	<tr>
		<td class='top'>Font</td>
		<td><input type="text" name="title_fontStyle"
			class="FS_Auto in50 infodetclass" id="title_fontStyle"
			value="Squada One" /></td>
		<td><select name='title_fontSize' id="title_fontSize"
			class='infodetclass'>
			<option value='1'>1</option>
			<option value='2'>2</option>
			<option value='3'>3</option>
			<option value='4'>4</option>
			<option value='5'>5</option>
			<option value='6'>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12'>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17' selected>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
			<option value='21'>21</option>
			<option value='22'>22</option>
			<option value='23'>23</option>
			<option value='24'>24</option>
			<option value='25'>25</option>
			<option value='26'>26</option>
			<option value='27'>27</option>
			<option value='28'>28</option>
			<option value='29'>29</option>
			<option value='30'>30</option>
			<option value='31'>31</option>
			<option value='32'>32</option>
		</select></td>
		<td><input type="text" name="title_fontColor"
			class="Multiple infodetclass" id="title_fontColor" value="#ffffff" /></td>
	</tr>
	<tr>
		<td colspan=4 class='hr'></td>
	</tr>
	<tr>
		<td colspan=4 class='top'><img
			src="/images/ebay/template/text-26.png" height="25" width="25"><a
			class='top_Title'>&nbsp;Text/Link Format:</a></td>
	</tr>
	<tr>
		<td class='top'>Font(Normal)</td>
		<td><input type="text" name="text_fontstyle"
			class="FS_Auto in50 infodetclass" id="text_fontstyle" value="Arial" /></td>

		<td><select name='text_fontsize' id="text_fontsize"
			class='infodetclass	'>
			<option value='1'>1</option>
			<option value='2'>2</option>
			<option value='3'>3</option>
			<option value='4'>4</option>
			<option value='5'>5</option>
			<option value='6'>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12' selected>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17'>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
		</select></td>
		<td><input type="text" name="text_fontcolor"
			class="Multiple infodetclass" id="text_fontcolor" value="#7f7f7f" /></td>
	</tr>
	<tr>
		<td class='top' colspan=3>Mouseover Font Style:</td>
		<td><input type="text" name="text_overcolor"
			class="Multiple infodetclass" id="text_overcolor" value="#333333" /></td>
	</tr>
	<tr>
		<td colspan=4 class='hr'></td>
	</tr>
	<tr>
		<td colspan=4 class='top'><img
			src="/images/ebay/template/redeem-26.png" height="25" width="25"><a
			class='top_Title'>&nbsp;Button Format:</a></td>
	</tr>
	<tr>
		<td class='top'>Normal</td>
		<td><input type="text" name="btn_bkgdcolor"
			class="Multiple infodetclass" id="btn_bkgdcolor" value="#333333" /></td>
	</tr>
	<tr>
		<td class='top'>Mouseover:</td>
		<td><input type="text" name="btn_overcolor"
			class="Multiple infodetclass" id="btn_overcolor" value="#7f7f7f" /></td>
	</tr>
	<tr>
		<td class='top'>Label Font</td>
		<td><input type="text" name="btn_textstyle"
			class="FS_Auto in50 infodetclass" id="btn_textstyle" value="Arial" /></td>
		<td><select name='btn_textsize' id="btn_textsize" class='infodetclass'>
			<option value='1'>1</option>
			<option value='2'>2</option>
			<option value='3'>3</option>
			<option value='4'>4</option>
			<option value='5'>5</option>
			<option value='6'>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10' selected>10</option>
			<option value='11'>11</option>
			<option value='12'>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17'>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
		</select></td>
		<td><input type="text" name="btn_textcolor"
			class="Multiple infodetclass" id="btn_textcolor" value="#FFFFFF" /></td>
	</tr>
	<tr>
		<td colspan=4 class='hr'></td>
	</tr>
	<!-- sidebar not supporting 'Store Category' right now -->
	<!--
	<tr>
		<td colspan=4 class='top'><img
			src="/images/ebay/template/parallel_tasks-26.png" height="25"
			width="25"><a class='top_Title'>&nbsp;Store Category:</a></td>
	</tr>
	<tr>
		<td class='top'>Bkground</td>
		<td><select name="cat_bkgd_type" class='CorP infodetclass'
			id="cat_bkgd_type">
			<option value='Color' selected>Color</option>
			<option value='Pattern'>Pattern</option>
		</select></td>
		<td id='cat_bkgd_type_c'><input type="text" name="cat_bkgd_color"
			class="Multiple infodetclass" id="cat_bkgd_color" value="#FFFFFF" /></td>
		<td colspan=3 id='cat_bkgd_type_p' style="display: none;"><input
			type='text' name='cat_bkgd_pattern' id='cat_bkgd_pattern'
			class=' infodetclass'
			value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png' />&nbsp;<a
			name='cat_bkgd_pattern_u' class='layout_select tip folder26'></a>
		<div class='cat_bkgd_pattern_u tooltip tipdiv'>
		<div class='navbar'>
		<table>
			<tr>
				<td>
				<table>
					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
						Pattern style 1 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png' checked>Background
						Pattern style 2 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
						Pattern style 3 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
						Pattern style 4 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
						Pattern style 5 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
						Pattern style 6 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png'>Background
						Pattern style 7 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png'>Background
						Pattern style 8 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png'>Background
						Pattern style 9 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
						Pattern style 10 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
						Pattern style 11 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png'>Background
						Pattern style 12 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png'>Background
						Pattern style 13 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png'>Background
						Pattern style 14 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png'>Background
						Pattern style 15 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png'>Background
						Pattern style 16 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png'>Background
						Pattern style 17 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png'>Background
						Pattern style 18 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png'>Background
						Pattern style 19 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png'>Background
						Pattern style 20 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png'>Background
						Pattern style 21 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png'>Background
						Pattern style 22 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png'>Background
						Pattern style 23 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png'>Background
						Pattern style 24 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png'>Background
						Pattern style 25 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png'>Background
						Pattern style 26 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png'>Background
						Pattern style 27 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png'>Background
						Pattern style 28 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_bkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png'>Background
						Pattern style 29 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>
				</table>
				</td>
				<td>
				<table>
					<tr>
						<td>
						<form target='iframe_upload' class='dropzone smimg font8' name=''
							title='cat_bkgd_pattern' id='policy_up' method='POST'
							enctype='multipart/form-data'
							dz-message='Drop files here to upload'
							style='width: 210px; min-height: 50px;'>
						<div class='fallback'><input type='file' multiple='multiple'
							name='file' /><br />
						<input type='submit' name='action' value='Upload Now!' /><br />
						<div id='gallery_uploads'></div>
						</div>
						</form>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		</div>
		<input type='button' name='Go' class='goCancel btn_01 goright'
			value='Confirm' /></div>
		</td>
	</tr>

	<tr>
		<td class='top'>Mouseover</td>
		<td><select name="cat_overbkgd_type" class='CorP infodetclass'
			id="cat_overbkgd_type">
			<option value='Color' selected>Color</option>
			<option value='Pattern'>Pattern</option>
		</select></td>
		<td id='cat_overbkgd_type_c'><input type="text"
			name="cat_overbkgd_color" class="Multiple infodetclass"
			id="cat_overbkgd_color" value="#FFFFFF" /></td>
		<td colspan=3 id='cat_overbkgd_type_p' style="display: none;"><input
			type='text' name='cat_overbkgd_pattern' id='cat_overbkgd_pattern'
			class=' infodetclass' value='' />&nbsp;<a
			name='cat_overbkgd_pattern_u' class='layout_select tip folder26'></a>
		<div class='cat_overbkgd_pattern_u tooltip tipdiv'>
		<div class='navbar'>
		<table>
			<tr>
				<td>
				<table>
					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
						Pattern style 1 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'>Background
						Pattern style 2 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
						Pattern style 3 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
						Pattern style 4 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
						Pattern style 5 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
						Pattern style 6 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png'>Background
						Pattern style 7 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png'>Background
						Pattern style 8 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png'>Background
						Pattern style 9 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
						Pattern style 10 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
						Pattern style 11 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png'>Background
						Pattern style 12 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png'>Background
						Pattern style 13 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar13.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png'>Background
						Pattern style 14 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar14.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png'>Background
						Pattern style 15 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar15.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png'>Background
						Pattern style 16 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar16.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png'>Background
						Pattern style 17 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar17.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png'>Background
						Pattern style 18 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar18.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png'>Background
						Pattern style 19 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar19.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png'>Background
						Pattern style 20 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar20.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png'>Background
						Pattern style 21 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar21.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png'>Background
						Pattern style 22 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar22.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png'>Background
						Pattern style 23 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar23.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png'>Background
						Pattern style 24 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar24.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png'>Background
						Pattern style 25 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar25.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png'>Background
						Pattern style 26 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar26.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png'>Background
						Pattern style 27 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar27.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png'>Background
						Pattern style 28 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar28.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>

					<tr>
						<td style='width: 200px;'><label style='width: 260px;'> <input
							type='radio' name='cat_overbkgd_pattern' class='upimg_r'
							value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png'>Background
						Pattern style 29 <a
							style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar29.png"); width: 200px; height: 25px; display: inline-block;'></a>
						</label></td>
					</tr>
				</table>
				</td>
				<td>
				<table>
					<tr>
						<td>
						<form target='iframe_upload' class='dropzone smimg font8' name=''
							title='cat_overbkgd_pattern' id='policy_up' method='POST'
							enctype='multipart/form-data'
							dz-message='Drop files here to upload'
							style='width: 210px; min-height: 50px;'>
						<div class='fallback'><input type='file' multiple='multiple'
							name='file' /><br />
						<input type='submit' name='action' value='Upload Now!' /><br />
						<div id='gallery_uploads'></div>
						</div>
						</form>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		</div>
		<input type='button' name='Go' class='goCancel btn_01 goright'
			value='Confirm' /></div>
		</td>
	</tr>
	-->
	<tr>
		<td colspan=4 style="color: #aaaaaa;">
		<p>*Category text style is based on Text/Link Format</p>
		</td>
	</tr>
</table>
</div>

<div id='d_desc_details' class='disno'>
<table class='' style="margin-top: -10px;">

	<tr>
		<td class='topsuname'>Deseription Text</td>
	</tr>
	<tr class='trtop'>
		<td class='font8'>Align:</td>
		<td class='in50'><select name='tb_eb_CFI_style_master_desc_details'
			id="tb_eb_CFI_style_master_desc_details" class='in40 font8'>
			<option value="left" selected>Left</option>
			<option value="center">Center</option>
			<option value="right">Right</option>
		</select></td>
		<td style='display: none;' class='font8'>Space:</td>
		<td style='display: none;' class='in50 font8'><select
			name="tb_eb_CFI_style_master_desc_limargin" id="Descriptionlimargin">
			<option value='5'>5</option>
			<option value='6' selected>6</option>
			<option value='7'>7</option>
			<option value='8'>8</option>
			<option value='9'>9</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12'>12</option>
			<option value='13'>13</option>
			<option value='14'>14</option>
			<option value='15'>15</option>
			<option value='16'>16</option>
			<option value='17'>17</option>
			<option value='18'>18</option>
			<option value='19'>19</option>
			<option value='20'>20</option>
			<option value='21'>21</option>
			<option value='22'>22</option>
			<option value='23'>23</option>
			<option value='24'>24</option>
			<option value='25'>25</option>
			<option value='26'>26</option>
			<option value='27'>27</option>
			<option value='28'>28</option>
			<option value='29'>29</option>
			<option value='30'>30</option>
		</select></td>
	</tr>
	<tr style='display: none;'>
		<td class='font8' style="width: 40px;">Point:</td>
		<td><select name='tb_eb_CFI_style_master_desc_list'
			id="tb_eb_CFI_style_master_desc_list" class='in50 font8'>
			<option value="none">None</option>
			<option value="disc">Disc</option>
			<option value="circle">Circle</option>
			<option value="square" selected>Square</option>
		</select></td>

	</tr>
	<tr style='display: none;'>
		<td class='font8'>Label Model:</td>
		<td><input type="text" id="InputDesc" class="in50 font8 changeDesc"
			name="tb_eb_CFI_style_master_Desc_string" value="" /></td>
		<td class='font8'>Size:</td>
		<td colspan=4><input type="text" id="Inputsize"
			class="in50 font8 changeDesc"
			name="tb_eb_CFI_style_master_size_string" value="Size" /></td>
	</tr>
	<tr class='disno'>
		<td colspan=4>
		<div class='font8 cm4' style="display: inline-block;">Auto Fill Item
		Specifics:</div>
		<select name='Auto_Fill_ONNOFF' id="Auto_Fill_ONNOFF" class='font8'>
			<option value="On" selected>On</option>
			<option value="Off">Off</option>
		</select><a class="detail font8" name="Item_Specifcations"
			id='Item_Specifcationsb' href="#" title="Edit Item Specifcations"></a>
		</td>


	</tr>
	<tr>
		<td colspan=4>* If you Dont't want to display "Model" or "Size", just
		EMPTY the fields above.</td>
	</tr>

</table>
</div>
<div id="SNBanner">
<table class="" style="">
	<tr>
		<td>
		<table id='SNBanner_path' class="">
			<tr>
				<td colspan="4">
					<input type="radio" name="SNBR" class="R_UNL" value="upload" CHECKED>&nbsp;
					<img src="/images/ebay/template/upload-26.png" title='Upload file' height="23" width="23">&nbsp;上载文件
					<input type="radio" name="SNBR" class="R_UNL" value="File">&nbsp;
					<img src="/images/ebay/template/link-26.png" title='Link file' height="23" width="23">&nbsp;文件链接
					<input type="radio" name="SNBR" class="R_UNL" value="lin">&nbsp;
					<img src="/images/ebay/template/stack_of_photos-26.png" title='Media library' height="23" width="23">&nbsp;媒体库
					<input style="display: none;" type="radio" name="SNBR" class="R_UNL" value="Self">&nbsp;
					<img style="display: none;" src="/images/ebay/template/stack_of_photos-26.png"
					title='Self library' height="23" width="23"><!-- &nbsp;Self Library -->
					<br />
				<br />
				</td>

				<td></td>
			</tr>
			<tr>
				<td colspan="4">最佳尺寸宽度1080px所有</td>

				<td></td>
			</tr>
			<tr class='SNB_upload'>
				<td colspan='2'>
				<form target="iframe_upload" class="dropzone"
					name="graphic_setting_Shop_Name_Banner" title='SNB'
					id="form_upload" method='POST' enctype="multipart/form-data"
					dz-message="Drop files here to upload" style="width: 215px;">
				<div class="fallback"><input type="file" multiple="multiple"
					name="file" /><br />
				<input type="submit" name="action" value="Upload Now!" /><br/>
				<div id="gallery_uploads"></div>
				</div>
				</form>
				<center>
				<div class='SNB_upfin upfin font8'>Finish</div>
				</center>
				</td>
				<td align="left"></td>
			</tr>
			<table>
				<tr>
					<td>
				
				
				<tr class='SNB_url'>
					<td>
					<table>
						<td class='font8'>File/URL:</td>
						<td align="left"><input type="text"
							name="graphic_setting_Shop_Name_Banner" class='new imgborder'
							id="graphic_setting_Shop_Name_Banner" style="width: 368px;"
							value="http://1e60194d2ecb9cce3358-6c3816948ff1e081218428d1ffca5b0d.r1.cf4.rackcdn.com/953bba0926fce846ed3064a7ea3ffbf4_plain_color_white_pure.jpg">
						</td>
						<td align="left"><a name="SNB" id='layout_select_SNB'
							class="layout_select tip"></a> <a name="Admin" class="DLimg"
							href="#" title="graphic_setting_Shop_Name_Banner"></a> <a
							name="Admin" class="preview" href="#"
							title="graphic_setting_Shop_Name_Banner" id='SNB_P'></a></td>
					</table>
					</td>
				</tr>
				<tr>
					<td>
					<div class='SNB tooltip tipdiv'>
					<div class='height400'>
					<table>
						<tr class='imgmyself'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='http://1e60194d2ecb9cce3358-6c3816948ff1e081218428d1ffca5b0d.r1.cf4.rackcdn.com/953bba0926fce846ed3064a7ea3ffbf4_plain_color_white_pure.jpg'>
							<img
								src='http://1e60194d2ecb9cce3358-6c3816948ff1e081218428d1ffca5b0d.r1.cf4.rackcdn.com/953bba0926fce846ed3064a7ea3ffbf4_plain_color_white_pure.jpg'
								height='50' width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/00.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/00.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/01.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/01.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/02.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/02.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/03.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/03.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/04.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/04.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/05.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/05.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/06.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/06.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/07.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/07.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/08.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/08.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/09.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/09.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/10.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/10.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/11.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/11.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/12.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/12.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/13.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/13.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/14.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/14.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/15.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/15.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/16.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/16.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/17.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/17.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/18.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/18.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/19.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/19.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/20.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/20.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/21.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/21.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/22.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/22.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/23.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/23.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/24.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/24.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/25.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/25.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/26.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/26.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/27.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/27.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/28.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/28.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/29.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/29.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/30.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/30.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/31.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/31.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/32.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/32.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/33.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/33.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/34.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/34.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/35.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/35.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/36.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/36.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/37.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/37.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/38.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/38.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/39.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/39.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/40.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/40.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/41.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/41.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/42.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/42.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/43.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/43.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
						<tr class='imgmedia'>
							<td style='width: 300px;'><label style='width: 250px;'> <input
								type='radio' name='SNB_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/44.png'> <img
								src='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/44.png' height='50'
								width='200' alt='img'> </label></td>
						</tr>
					</table>
					</div>
					<input type='button' name='Go' class='goCancel btn_01 goright'
						value='Confirm' /></div>
					</td>
				</tr>

				<tr style="display: none" id="graphic_setting_Shop_Name_Banner_HW"
					class="HWdisplay">

					<td class="name font8">>> Width:<input type="text"
						class='HW_W inputHW' name="graphic_setting_Shop_Name_Banner_W"
						id="HW_W" value="0"></td>
					<td class="name100 font8">Height:<input type="text"
						name="graphic_setting_Shop_Name_Banner_H" class='HW_H inputHW'
						id="HW_H" value="0"></td>

				</tr>
				<tr style="display: none;">
					<td>
					<table>
						<tr>
							<td class='font8'>Click-to URL:</td>
							<td><input type="text" class='in350'
								name="graphic_setting_Shop_Name_Banner_Target"
								id="graphic_setting_Shop_Name_Banner_Target" value="" /></td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</td>
			</tr>
			<tr>
				<td colspan='5' class='hr'></td>
			</tr>
			<tr>
				<td class='font8 topsuname' colspan='5' style="float: left;">商品名称</td>
			</tr>
			<tr>
				<td>
				<table>
					<tr>
						<td class='font8'>文字:</td>
						<td><input type="text" name="shop_name_text"
							placeholder="[Your Super eBay Store]" class='shop_name in350'
							id="shop_name_text" value="" /></td>
					</tr>

				</table>
				</td>
			</tr>
			<tr>
				<td colspan='5'>
				<table>
					<tr>

						<td class='font8'>字型:&nbsp;</td>
						<td><input type="text" id="shop_name_text_style"
							name="shop_name_text_style" class="FS_Auto in50 font8 shop_name"
							value="Impact" />&nbsp;</td>
						<td><select name='shop_name_text_size' id="shop_name_text_size"
							class="font8 shop_name">
							<option value='10'>10</option>
							<option value='11'>11</option>
							<option value='12'>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
							<option value='21'>21</option>
							<option value='22'>22</option>
							<option value='23'>23</option>
							<option value='24'>24</option>
							<option value='25'>25</option>
							<option value='26'>26</option>
							<option value='27'>27</option>
							<option value='28'>28</option>
							<option value='29'>29</option>
							<option value='30'>30</option>
							<option value='31'>31</option>
							<option value='32'>32</option>
							<option value='33'>33</option>
							<option value='34'>34</option>
							<option value='35'>35</option>
							<option value='36'>36</option>
							<option value='37'>37</option>
							<option value='38'>38</option>
							<option value='39'>39</option>
							<option value='40'>40</option>
							<option value='41'>41</option>
							<option value='42'>42</option>
							<option value='43'>43</option>
							<option value='44'>44</option>
							<option value='45'>45</option>
							<option value='46'>46</option>
							<option value='47'>47</option>
							<option value='48'>48</option>
							<option value='49'>49</option>
							<option value='50'>50</option>
							<option value='51'>51</option>
							<option value='52'>52</option>
							<option value='53'>53</option>
							<option value='54'>54</option>
							<option value='55'>55</option>
							<option value='56'>56</option>
							<option value='57'>57</option>
							<option value='58'>58</option>
							<option value='59'>59</option>
							<option value='60'>60</option>
							<option value='61'>61</option>
							<option value='62'>62</option>
							<option value='63'>63</option>
							<option value='64'>64</option>
							<option value='65'>65</option>
							<option value='66'>66</option>
							<option value='67'>67</option>
							<option value='68'>68</option>
							<option value='69'>69</option>
							<option value='70'>70</option>
							<option value='71'>71</option>
							<option value='72'>72</option>
							<option value='73'>73</option>
							<option value='74'>74</option>
							<option value='75' selected>75</option>
							<option value='76'>76</option>
							<option value='77'>77</option>
							<option value='78'>78</option>
							<option value='79'>79</option>
							<option value='80'>80</option>
							<option value='81'>81</option>
							<option value='82'>82</option>
							<option value='83'>83</option>
							<option value='84'>84</option>
							<option value='85'>85</option>
							<option value='86'>86</option>
							<option value='87'>87</option>
							<option value='88'>88</option>
							<option value='89'>89</option>
							<option value='90'>90</option>
							<option value='91'>91</option>
							<option value='92'>92</option>
							<option value='93'>93</option>
							<option value='94'>94</option>
							<option value='95'>95</option>
							<option value='96'>96</option>
							<option value='97'>97</option>
							<option value='98'>98</option>
							<option value='99'>99</option>
						</select>&nbsp;</td>
						<td colspan=4><input type="text" id="shop_name_text_color"
							name="shop_name_text_color" class="Multiple font8 shop_name"
							value="#4c4c4c" />&nbsp;</td>
						<td class='font8 topsuname' style="float: left;">位置&nbsp;</td>
						<td class='font8'>X:&nbsp;</td>
						<td><input type="number" id="shop_name_text_left"
							name="shop_name_text_left" class="shop_name font8 in40"
							value="25" /></td>
						<td class='font8'>Y:&nbsp;</td>
						<td><input type="number" id="shop_name_text_top"
							name="shop_name_text_top" class="shop_name font8 in40" value="10" />
						</td>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td></td>
			</tr>
			<tr>
				<td colspan='5' class='hr'></td>
			</tr>
			<tr>
				<td class='font8 topsuname' colspan='5' style="float: left;">副店铺标题</td>
			</tr>
			<tr>
				<td>
				<table>
					<tr>
						<td class='font8'>文字:</td>
						<td><input type="text" name="shop_name_sub_text"
							class="shop_name in350"
							placeholder="[Your best online business partner]"
							id="shop_name_sub_text" value="" /></td>
					</tr>

				</table>
				</td>
			</tr>
			<tr>
				<td colspan='5'>
				<table>
					<tr>

						<td class='font8'>字型:&nbsp;</td>
						<td><input type="text" id="shop_name_sub_text_style"
							name="shop_name_sub_text_style"
							class="shop_name FS_Auto in50 font8" value="Orbitron" />&nbsp;</td>
						<td><select name='shop_name_sub_text_size'
							id="shop_name_sub_text_size" class="font8 shop_name">
							<option value='10'>10</option>
							<option value='11'>11</option>
							<option value='12'>12</option>
							<option value='13'>13</option>
							<option value='14'>14</option>
							<option value='15'>15</option>
							<option value='16'>16</option>
							<option value='17'>17</option>
							<option value='18'>18</option>
							<option value='19'>19</option>
							<option value='20'>20</option>
							<option value='21'>21</option>
							<option value='22'>22</option>
							<option value='23'>23</option>
							<option value='24'>24</option>
							<option value='25'>25</option>
							<option value='26' selected>26</option>
							<option value='27'>27</option>
							<option value='28'>28</option>
							<option value='29'>29</option>
							<option value='30'>30</option>
							<option value='31'>31</option>
							<option value='32'>32</option>
							<option value='33'>33</option>
							<option value='34'>34</option>
							<option value='35'>35</option>
							<option value='36'>36</option>
							<option value='37'>37</option>
							<option value='38'>38</option>
							<option value='39'>39</option>
							<option value='40'>40</option>
							<option value='41'>41</option>
							<option value='42'>42</option>
							<option value='43'>43</option>
							<option value='44'>44</option>
							<option value='45'>45</option>
							<option value='46'>46</option>
							<option value='47'>47</option>
							<option value='48'>48</option>
							<option value='49'>49</option>
							<option value='50'>50</option>
							<option value='51'>51</option>
							<option value='52'>52</option>
							<option value='53'>53</option>
							<option value='54'>54</option>
							<option value='55'>55</option>
							<option value='56'>56</option>
							<option value='57'>57</option>
							<option value='58'>58</option>
							<option value='59'>59</option>
							<option value='60'>60</option>
							<option value='61'>61</option>
							<option value='62'>62</option>
							<option value='63'>63</option>
							<option value='64'>64</option>
							<option value='65'>65</option>
							<option value='66'>66</option>
							<option value='67'>67</option>
							<option value='68'>68</option>
							<option value='69'>69</option>
							<option value='70'>70</option>
							<option value='71'>71</option>
							<option value='72'>72</option>
							<option value='73'>73</option>
							<option value='74'>74</option>
							<option value='75'>75</option>
							<option value='76'>76</option>
							<option value='77'>77</option>
							<option value='78'>78</option>
							<option value='79'>79</option>
							<option value='80'>80</option>
							<option value='81'>81</option>
							<option value='82'>82</option>
							<option value='83'>83</option>
							<option value='84'>84</option>
							<option value='85'>85</option>
							<option value='86'>86</option>
							<option value='87'>87</option>
							<option value='88'>88</option>
							<option value='89'>89</option>
							<option value='90'>90</option>
							<option value='91'>91</option>
							<option value='92'>92</option>
							<option value='93'>93</option>
							<option value='94'>94</option>
							<option value='95'>95</option>
							<option value='96'>96</option>
							<option value='97'>97</option>
							<option value='98'>98</option>
							<option value='99'>99</option>
						</select>&nbsp;</td>
						<td colspan=4><input type="text" id="shop_name_sub_text_color"
							name="shop_name_sub_text_color" class="Multiple font8 shop_name"
							value="#999999" />&nbsp;</td>
						<td class='font8 topsuname' style="float: left;">位置&nbsp;</td>
						<td class='font8'>X:&nbsp;</td>
						<td><input type="number" id="shop_name_sub_text_left"
							name="shop_name_sub_text_left" class="shop_name font8 in40"
							value="25" /></td>
						<td class='font8'>Y:&nbsp;</td>
						<td><input type="number" id="shop_name_sub_text_top"
							name="shop_name_sub_text_top" class="shop_name font8 in40"
							value="95" /></td>
					</tr>
				</table>
				</td>
			</tr>

		</table>

		</td>
	</tr>
</table>
<div style="display: none;"><textarea
	id="sh_ch_info_text_Shop_Name_Text"
	name="sh_ch_info_text_Shop_Name_Text" class="ckeditor">test</textarea>
</div>

</div>

<div id="pic_info" title="Config"></div>
<div id="m_pic_info" title="Config"></div>
<div id="d_pic_info" title="Config"></div>
<div id="Policy_text"><textarea id="sh_ch_info_Policy1"
	name="sh_ch_info_Policy1" class="ckeditor">
<p><span style="color: #A9A9A9"><span
	style="font-family: arial, helvetica, sans-serif, verdana; font-size: 12px">[ Policy: This is the contents of your policies. You can add a banner for this policy as header and add text descriptions here. The text descriptions can be different Font Size, Font Color, Style and even graphics and icons are also accepted. ]</span></span></p></textarea>
<textarea id="sh_ch_info_Policy2" name="sh_ch_info_Policy2"
	class="ckeditor">
<p><span style="color: #A9A9A9"><span
	style="font-family: arial, helvetica, sans-serif, verdana; font-size: 12px">[ Shipping: This is the contents of your Shipping.&nbsp;You can add a banner for this Shipping as header and add text descriptions here. The text descriptions can be different Font Size, Font Color, Style and even graphics and icons are also accepted. ]</span></span></p></textarea>
<textarea id="sh_ch_info_Policy3" name="sh_ch_info_Policy3"
	class="ckeditor">
<p><span style="color: #A9A9A9"><span
	style="font-family: arial, helvetica, sans-serif, verdana; font-size: 12px">[ Return: This is the contents of your Return.&nbsp;You can add a banner for this Return&nbsp;as header and add text descriptions here. The text descriptions can be different Font Size, Font Color, Style and even graphics and icons are also accepted. ]</span></span></p></textarea>
<textarea id="sh_ch_info_Policy4" name="sh_ch_info_Policy4"
	class="ckeditor">
<p><span style="color: #A9A9A9"><span
	style="font-family: arial, helvetica, sans-serif, verdana; font-size: 12px">[ Customer Services: This is the contents of your &nbsp;Customer Services.&nbsp;You can add a banner for this &nbsp;Customer Servicesas header and add text descriptions here. The text descriptions can be different Font Size, Font Color, Style and even graphics and icons are also accepted. ]</span></span></p></textarea>
<textarea id="sh_ch_info_Policy5" name="sh_ch_info_Policy5"
	class="ckeditor"></textarea> 
<textarea id="sh_ch_info_Policybot"
	name="sh_ch_info_Policybot" class="ckeditor">
<p><span style="color: #A9A9A9">Copyright of XXXX. All rights reserved.</span></p></textarea>
</div>
<div id="dialog_info" title="Config">
<form>
<fieldset><img src="/images/ebay/template/add_property-26.png"
	alt="create info" title="Create mobile" height="23" width="23"> <a
	class='top'>新增功能格</a> <select name='infobox_type_id'
	id='infobox_type_id'>
	<option value="search">搜寻</option>
	<option value="picture">图片</option>
	<option value="text_Link">Text.Link</option>
	<option value="hot_item">Hot Item</option>

<!--	
	<option value="new_item">New List Item</option>
	<option value="shop_cat">Store Cat.</option>
	<option value="newsletter">Newsletter</option>
	<option value="cus_item">Custom Item</option>
	<option value="youtube">Youtube</option>
	<option value="flash">Flash</option>
	<option value="qr_code">QR Code</option>
	<option value="s_html">HTML box</option>
-->	
</select></fieldset>
</form>
</div>
<div id='Item_Specifcations'><input type="text"
	id="Item_Specifcations_string" name="Item_Specifcations_string"
	value="Item Specifications" />
</td>
</div>

<div id="mobile_info" title="Config">
<form>
<fieldset><img src="/images/ebay/template/add_property-26.png"
	alt="create info" title="Create mobile" height="23" width="23"> <a
	class='top'>新增手机模板方格</a> <select name='m_infobox_type_id'
	id='m_infobox_type_id'>
	<option value="shopname">Shop Name banner</option>
	<option value="notice">Notice banner</option>
	<option value="policy">Policy</option>
	<option value="desc">Description</option>
<!--	
	<option value="mpicture">Hori. Product Photo</option>
	<option value="mpicture2">Vert. Product Photo</option>
	<option value="item_s">Item Specifics</option>
	<option value="matt">Attributes box</option>
	<option value="poster">Poster</option>
	<option value="mpic">Picture box</option>
	<option value="youtube">Youtube</option>
	<option value="m_call_for_action">Action button</option>
-->
</select></fieldset>
</form>
</div>
<div id="desc_info" title="Config">
<form>
<fieldset>

<table>
	<tr>
		<td><img src="/images/ebay/template/add_property-26.png"
			alt="create info" title="Create mobile" height="23" width="23"><a
			class='top'>新增产品描述方格</a></td>
	</tr>
	<tr>
		<td><select name='d_infobox_type_id' id='d_infobox_type_id'>
			<option value="basic_descriptions">Basic Descriptions</option>
<!--
			<option value="item_specifications">Item Specifics</option>
			<option value="attributes">Attributes Table</option>
			<option value="picture">Picture Banner</option>
			<option value="html">HTML</option>
			<option value="youtube">Youtube</option>
			<option value="call_for_action">Action button</option>
			<option value="d_poster">Poster</option>
-->
		</select></td>
	</tr>
	<tr>
		<td><label><input type="checkbox" name="copymobile" id="copymobile"
			value="copymobile" checked>复制到手机模板</label></td>
	</tr>
</table>

</fieldset>
</form>
</div>
<Script>
var desc_content_set = {"":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"basic_descriptions":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"item_specifications":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"attributes":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"picture":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"html":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"youtube":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"eaglegallery":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"call_for_action":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"d_poster":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}}};
$(function() {


	$(document).on('change','[name=switchType]',function(){
//		$.showLoading();
		var switchType = $(this).val();
		alert(switchType);
		$("#layout_type").attr('class',switchType);
//		$.hideLoading();
//		$.post('/listing/ebay-template/edit',{switchType:switchType},function(){
//			alert('test');
//			return true;
//		})
	});

	var nedc = ["cat_layoutborder_color", "title_bkgd_color", "title_fontColor", "text_fontcolor", "text_overcolor", "btn_bkgdcolor", "btn_overcolor", "btn_textcolor", "cat_bkgd_color", "cat_overbkgd_color"];   
	array_Multiple = new Array;
   var mi = 0;
	$(".Multiple").each(function () {				
		array_Multiple[$(this).attr('name')] = mi;
		mi++
	});
	$( "#Theme_id" ).change(function() {
		if( $(this).val() != ''){
			// $.ajax({
                // type: 'POST',
                // data: {
                    // 'Theme_id': $(this).val() ,
                    // 'find': 'theme_id'
                // },
                // dataType: 'html',
                // url: '',
                // success: function (data) {
					 // $('#infodetail').html(data);
					// eval(data).ready(function () {	
				// for (var i = 0; i < nedc.length; i++) {					
					// $.jPicker.List[array_Multiple[nedc[i]]].color.active.val('hex',$('#'+nedc[i]).val());
				// }					
			// });
                // }
            // });
		}
	})
	$( "#allItem" ).tooltip();
   $( "#sortable" ).sortable({
   placeholder: "ui-state-highlight",
	update: function(event, ui) {
	var ii = 1;
			$(".sort").each(function () {
					$(this).removeAttr('class');
					$(this).addClass('sort');
					$(this).addClass('infono'+ii);
					$(this).addClass('delinfono'+ii);
					$(this).find('.content_displayorder').val(ii);
					ii++;
                });
		
			}
	}	);
	$( "#msortable" ).sortable({
   placeholder: "ui-state-highlight",
	update: function(event, ui) {
	var ii = 1;
			$(".msort").each(function () {
					$(this).removeAttr('class');
					$(this).addClass('msort');
					$(this).addClass('infono'+ii);
					$(this).addClass('delinfomobileno'+ii);
					$(this).find('.m_content_displayorder').val(ii);
					ii++;
                });
		
			}
	});
	 $( "#dsortable" ).sortable({
   placeholder: "ui-state-highlight",
	update: function(event, ui) {
	var ii = 1;
			$(".dsort").each(function () {
					$(this).removeAttr('class');
					$(this).addClass('dsort');
					$(this).addClass('infono'+ii);
					$(this).addClass('delinfodno'+ii);
					$(this).find('.d_content_displayorder').val(ii);
					ii++;
                });
		
			}
	}	);
	var infobox_type = new Array();
		infobox_type['search'] = "Search";
		infobox_type['shop_cat'] = "Store Cat.";
		infobox_type['picture'] = "Picture";
		infobox_type['text_Link'] = "Text.Link";
		infobox_type['newsletter'] = "Newsletter";
		infobox_type['hot_item'] = "Hot Item";
		infobox_type['new_item'] = "New List Item";
		infobox_type['cus_item'] = "Custom Item";
		infobox_type['youtube'] = "Youtube";		
		infobox_type['flash'] = "Flash";
		infobox_type['qr_code'] = "QR Code";
		infobox_type['s_html'] = "HTML box";
    var infobox_type_id = $( "#infobox_type_id" ),
      allFields = $( [] ).add( infobox_type_id )
    $("#pic_info").dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
				array_content = new Array;
				var i = 0;
				$(".content_src_url").each(function () {
                    array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
					i++;
                });
            $('#add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
            $( this ).dialog( "close" );
			
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
        allFields.val( "" ).removeClass( "ui-state-error" );
      }
    });
	$("#m_pic_info").dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
				array_content = new Array;
				var i = 0;
				$(".content_src_url").each(function () {
                    array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
					i++;
                });
            $('#m_add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
            $( this ).dialog( "close" );
			
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
        allFields.val( "" ).removeClass( "ui-state-error" );
      }
    });
	$("#d_pic_info").dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
				array_content = new Array;
				var i = 0;
				$(".content_src_url").each(function () {
                    array_content.push($(this).val()+'::'+$('.content_target_url').eq(i).val());
					i++;
                });
            $('#d_add'+$('#boxcontent').val()).val(JSON.stringify(array_content));
            $( this ).dialog( "close" );
			
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
        allFields.val( "" ).removeClass( "ui-state-error" );
      }
    });
	var d_infobox_type = new Array();
		d_infobox_type['basic_descriptions'] = "Basic Descriptions";
		d_infobox_type['item_specifications'] = "Item Specifics";
		d_infobox_type['attributes'] = "Attributes Table";
		d_infobox_type['picture'] = "Picture Banner";
		d_infobox_type['html'] = "HTML";
		d_infobox_type['youtube'] = "Youtube";
		d_infobox_type['eaglegallery'] = "EagleGallery";
		d_infobox_type['call_for_action'] = "Action button";
		d_infobox_type['d_poster'] = "Poster";
		d_infobox_type['d_title'] = "Title";		
    var d_infobox_type_id = $( "#d_infobox_type_id" ),allFields = $( [] ).add( d_infobox_type_id );
	var copymobile = $( "#copymobile" ); 
	function dsortable(n,d_n,d_infobox_type_id,boxposition,boxwidth,boxalign,encode,D_class)
	{
	var hidee
	if(d_infobox_type_id === 'eaglegallery'){
	hidee = 'display:none;'
	}
	var tableapp = "<li class='dsort infodno"+n+" delinfodno"+n+"'><table class='info7 infono'>"+
		  "<tr class='font8'><td>箱体式:</td><td><input type='text' class='d_content_type_id' name='d_content_type_id' value='"+d_infobox_type[d_infobox_type_id]+"' readonly /></td><td style='"+hidee+"'><a  class='btn_del' href='#' id='dno"+n+"'></a></td></tr>"+
		  "<tr class='font8'><td>框标题:</td><td><input type='text' class='d_content_text' name='d_content_text' value='"+d_infobox_type[d_infobox_type_id]+"'  /></td><td style='"+hidee+"'><a "+D_class+" title='Edit Detail' id='content"+n+"' href='#'><img src='/images/ebay/template/edit_property.png' alt='infodetail' height='16' height='16'></a></td></tr>"+
		  "<tr class='font8'><td>框位置:</td><td>"+boxposition+"</td></tr>"+
		  "<tr class='font8' style='"+hidee+"'><td>Box width:</td><td>"+boxwidth+boxalign+"</td></tr>"+		  
		  "<tr >"+
		  "<td class='disno'><input type='number' class='d_content_displayorder' name='d_content_displayorder' value='"+n+"'  /><input type='text' class='d_infobox_content' name='d_infobox_content' id='d_addcontent"+n+"' value=''  /><input type='text' class='d_infobox_content_id' value=''  /><input type='text' class='d_infobox_en_key' name='d_infobox_en_key' value='"+encode+"'></td></tr></table></li>";
	$( "#dsortable" ).append(tableapp);
	return false;
	}
	function msortable(m_n,m_d_n,encode,value,D_class)
	{
	if(D_class != ''){
	D_class = "<td><a "+D_class+" title='Edit Detail' id='m_content"+m_n+"' href='#'><img src='/images/ebay/template/edit_property.png' alt='create_info' height='16' height='16'></a></td>";
	}
	var apptable = "<li class='msort infono"+m_n+" delinfomobileno"+m_n+"'><table class='info7 infono'>"+
		  "<tr class='font8'><td>Box Type:</td><td><input type='text' class='m_content_type_id' name='m_content_type_id' value='"+value+"' readonly /></td><td><a  class='btn_del' href='#' id='mobileno"+m_n+"'></a></td></tr>"+
		  "<tr class='font8'><td>Box Title:</td><td><input type='text' class='m_content_text in110' name='m_content_text' value='"+value+"'  /></td>"+D_class+
		  "<td class='disno'><input type='number' class='m_content_displayorder' name='m_content_displayorder' value='"+m_n+"'  /><input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value=''><input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content"+m_n+"' value=''  /><input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value='"+encode+"'></td></tr></table></li>"
	
	if($( "#msortable .m_content_type_id[value=Description]").length > 0){
	$( "#msortable .m_content_type_id[value=Description]").first().closest("li").after(apptable);
	}else{
	$( "#msortable" ).append(apptable);
	}
		var ii = 0;
			$(".msort").each(function () {
					$(this).find('.m_content_displayorder').val(ii);
					ii++;
                });
	return false;
	}
	var n;	
	$( "#desc_info" ).dialog({
      autoOpen: false,
      height: 160,
      width: 220,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
		  n = $(".dsort").length + 1;	
		  var m_n = $(".msort").length + 1;
		  var d_n = $(".info7").length + 1;	
		  var m_d_n = d_n++;	
		  var n1 = $(".dsort").length;
		  if("no"==='yes'){
		  var boxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		  var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		   var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
		   var boxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		  }else{
		  var boxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		  var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		  var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' selected>Center</option><option value='right' >Right</option></select>";
		  var boxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		  } 
		 var pboxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		 var eposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		 var boxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
		 var encode = "";
		  if(d_infobox_type_id.val() == 'basic_descriptions'){
		  if($('#copymobile').is(':checked')){
		  encode = $.now();
		   msortable(m_n,m_d_n,encode,m_infobox_type['desc'],'')
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_desc_details detail' name='d_desc_details'")
		  }else if (d_infobox_type_id.val() == 'picture'){
		   if($('#copymobile').is(':checked')){
		  encode = $.now();		  
		  msortable(m_n,m_d_n,encode,m_infobox_type['mpic'],"class='mpic_details'")	
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),pboxposition,pboxwidth,boxalign,encode,"class='d_pic_details' name='m_addm_content"+m_n+"'")
		  }else if (d_infobox_type_id.val() == 'd_title'){
		   if($('#copymobile').is(':checked')){
		  encode = $.now();		  
		  msortable(m_n,m_d_n,encode,m_infobox_type['m_title'],"")	
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"")		  		  
		  }else if (d_infobox_type_id.val() == 'html'){		  
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_html_details'")		  		  
		  }else if (d_infobox_type_id.val() == 'item_specifications'){
		  if($('#copymobile').is(':checked')){
		  encode = $.now();		  
		   msortable(m_n,m_d_n,encode,m_infobox_type['item_s'],"class='m_item_details' name=''")
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_item_details' ")
		  }else if (d_infobox_type_id.val() == 'attributes'){
		  if($('#copymobile').is(':checked')){
		  encode = $.now();
		   msortable(m_n,m_d_n,encode,m_infobox_type['matt'],"class='m_attr_details'")	
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_attr_details'")	
		  }else if (d_infobox_type_id.val() == 'd_poster'){
		  if($('#copymobile').is(':checked')){
		  encode = $.now();
		   msortable(m_n,m_d_n,encode,m_infobox_type['poster'],"")	
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"")	
		  }else if (d_infobox_type_id.val() == 'youtube'){
		  dsortable(n,d_n,d_infobox_type_id.val(),pboxposition,pboxwidth,boxalign,encode,"class='D_youtubedetails'")
		  }else if (d_infobox_type_id.val() == 'call_for_action'){
		  if($('#copymobile').is(':checked')){
		  encode = $.now();
		   msortable(m_n,m_d_n,encode,m_infobox_type['m_call_for_action'],"class='M_call_for_action'")	
		  }
		  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='D_call_for_action'")
		  
		  }else if (d_infobox_type_id.val() == 'eaglegallery'){
		  if($('.d_content_type_id[value="EagleGallery"]').val()){
		  alert('only one EagleGallery')
		  }else{	
		  dsortable(n,d_n,'eaglegallery',eposition,pboxwidth,boxalign,encode,"class='D_eaglegallery'")
		  }}
           
            $( this ).dialog( "close" );
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
        allFields.val( "" ).removeClass( "ui-state-error" );
		 $( ".D_Pre_info" ).click();
		  $( ".m_Pre_info" ).click();	
		setTimeout(function(){$('#dsortablef #content'+n).click()}, 500);
		
      }
    });
	 $('#tb_eb_CFI_style_master_HBP').change(
                        function () {
							var eposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo'>Above Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
							var pboxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
							var boxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
							var n = $(".dsort").length + 1;	
							var d_n = $(".info7").length + 1;	
                            if ($('#tb_eb_CFI_style_master_HBP').val() == 'No') {
							if($('.d_content_type_id[value="EagleGallery"]').val()){
									$('.d_content_type_id[value="EagleGallery"]').parent().parent().parent().parent().parent().remove();
								$( ".D_Pre_info" ).click();
								}
                            } else {
								if($('.d_content_type_id[value="EagleGallery"]').val()){
									//alert('only one EagleGallery');
								  }else{		  
								  $( "#dsortable" ).append(
									dsortable(n,d_n,'eaglegallery',eposition,pboxwidth,boxalign,'',"class='D_eaglegallery'")
										
								  );
								  $( ".D_Pre_info" ).click();								 
								  }
							
                            }
						 
                        }
                    )
	var m_infobox_type = new Array();
		m_infobox_type['eaglegallery'] = "EagleGallery";
		m_infobox_type['mpicture'] = "Hori. Product Photo";
		m_infobox_type['mpicture2'] = "Vert. Product Photo";
		m_infobox_type['desc'] = "Description";
		m_infobox_type['poster'] = "Poster";
		m_infobox_type['policy'] = "Policy";
		m_infobox_type['youtube'] = "Youtube";
		m_infobox_type['shopname'] = "Shop Name banner";
		m_infobox_type['notice'] = "Notice banner";
		m_infobox_type['mpic'] = "Picture box";
		m_infobox_type['matt'] = "Attributes box";
		m_infobox_type['item_s'] = "Item Specifics";
		m_infobox_type['m_call_for_action'] = "Action button";
		m_infobox_type['m_title'] = "Title";
    var m_infobox_type_id = $( "#m_infobox_type_id" ),
      allFields = $( [] ).add( m_infobox_type_id )	 
	$( "#mobile_info" ).dialog({
      autoOpen: false,
      height: 140,
      width: 220,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
		  n = $(".msort").length + 1;	
		  var n1 = $(".msort").length;
		  var d_n = $(".info7").length + 1;
		  if(m_infobox_type_id.val() == 'poster' || m_infobox_type_id.val() == 'm_title' || m_infobox_type_id.val() == 'mpicture' || m_infobox_type_id.val() == 'mpicture2' || m_infobox_type_id.val() == 'desc' || m_infobox_type_id.val() == 'shopname' || m_infobox_type_id.val() == 'notice' ){
		   msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],'')
		  }else if (m_infobox_type_id.val() == 'policy'){
		   msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='detail' name='tabtool'")
		  }else if (m_infobox_type_id.val() == 'youtube' ){
			msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='myoutube_details'")	
		  }else if (m_infobox_type_id.val() == 'mpic' ){
			msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='mpic_details'")
		  }else if (m_infobox_type_id.val() == 'matt' ){
			msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='m_attr_details'")
		  }else if (m_infobox_type_id.val() == 'item_s' ){
			msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='m_item_details'")
		  }else if (m_infobox_type_id.val() == 'm_call_for_action' ){
			msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='M_call_for_action'")
		  }
           
            $( this ).dialog( "close" );
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
	  $( ".m_Pre_info" ).click();
        allFields.val( "" ).removeClass( "ui-state-error" );
		 setTimeout(function(){$('#msortablef #m_content'+n).click()}, 500);
      }
    });
	function sortable(n,d_n,value,D_class)
	{
	if(D_class != ''){
	D_class = "<td><a "+D_class+" title='Edit Detail' id='content"+n+"' href='#'><img src='/images/ebay/template/edit_property.png' alt='infodetail' height='16' height='16'></a></td>";
	}
	var showtable = "<li class='sort info7 infono"+n+" delinfono"+d_n+" infono'><table class='info7 infono'>"+
		  "<tr class='font8'><td>Box Type:</td><td><input type='text' class='content_type_id' name='content_type_id' value='"+value+"' readonly /></td><td><a  class='btn_del' href='#' id='no"+d_n+"'></a></td></tr>"+
		  "<tr class='font8'><td>Box Title:</td><td><input type='text' class='content_text in110' name ='content_text' value='"+value+"'  /></td>"+D_class+"</tr>"+
		  "<tr >"+
		  "<td class='disno'><input type='number' class='content_displayorder' name='content_displayorder' value='"+n+"'  /><input type='text' class='infobox_content' name='infobox_content' id='addcontent"+n+"' value=''  /><input type='text' class='infobox_content_id' value=''  /></td></tr></table></li>"
	$( "#sortable" ).append(showtable);
	return false;
	}
    $( "#dialog_info" ).dialog({
      autoOpen: false,
      height: 140,
      width: 200,
      modal: true,
      buttons: {
        "确定": function() {
          var bValid = true;
          allFields.removeClass( "ui-state-error" );
          if ( bValid ) {
		  n = $(".sort").length + 1;	
		  var n1 = $(".sort").length;
		  var d_n = $(".info7").length + 1;
		  if(infobox_type_id.val() == 'search' || infobox_type_id.val() == 'new_item' || infobox_type_id.val() == 'hot_item' || infobox_type_id.val() == 'qr_code'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],'')
		  }else if (infobox_type_id.val() == 'shop_cat'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='cat_details'")	 
		  }else if (infobox_type_id.val() == 'picture'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='pic_details'")
		  }else if (infobox_type_id.val() == 'text_Link'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='text_details'")
		  }else if (infobox_type_id.val() == 'newsletter'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='new_details'")
		  }else if (infobox_type_id.val() == 'cus_item'){	
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='item_details'")
		  }else if (infobox_type_id.val() == 'youtube' ){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='youtube_details'")
		  }else if ( infobox_type_id.val() =='flash'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='flash_details'")
		  }else if ( infobox_type_id.val() =='s_html'){
		  sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='html_details'")
		  }
            $( this ).dialog( "close" );
          }
        },
        取消: function() {
          $( this ).dialog( "close" );
        }
      },
      close: function() {
		$( ".Pre_info" ).click();
        allFields.val( "" ).removeClass( "ui-state-error" );
		setTimeout(function(){$('#sortablef #content'+n).click()}, 500);
      }
    });
 
	$( "#create_info" ).click(function() {
		$( "#dialog_info" ).dialog( "open" );
		return false;
	});
	$( "#Desc_info" ).click(function() {
		$( "#desc_info" ).dialog( "open" );
		return false;
	});
	$( "#Mobile_info" ).click(function() {
		$( "#mobile_info" ).dialog( "open" );
		return false;
	});
	$( ".D_Pre_info" ).click(function() {
		// array_d_content_displayorder = new Array;
		// $(".d_content_displayorder").each(function () {
			// array_d_content_displayorder.push($(this).val());
		// });
		// array_d_content_type_id = new Array;
		// $(".d_content_type_id").each(function () {
			// array_d_content_type_id.push($(this).val());
		// });
		// array_d_infobox_content = new Array;
		// $(".d_infobox_content").each(function () {
			// array_d_infobox_content.push($(this).val());
		// });				
		// array_d_content_text = new Array;
		// $(".d_content_text").each(function () {
			// array_d_content_text.push($(this).val());
		// });
		// array_d_content_width = new Array;
		// $(".d_content_width").each(function () {
			// array_d_content_width.push($(this).val());
		// });
		// array_d_content_width = new Array;
		// $(".d_content_width").each(function () {
			// array_d_content_width.push($(this).val());
		// });
		// array_d_content_align = new Array;
		// $(".d_content_align").each(function () {
			// array_d_content_align.push($(this).val());
		// });
		// arrayeditor = new Array;
		// $(".editor").each(function () {
			// arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData().trim());
		// });
		// array_d_content_pos = new Array;
		// $(".d_content_pos").each(function () {
			// array_d_content_pos.push($(this).val());
		// });
		var data = {
			// 'editor[]': arrayeditor,
			'contentid': $(this).attr('id'),
			'content': $('#add'+$(this).attr('id')).val(),
			'layout': $('#layout_style_name').val(),
			// 'd_content_displayorder[]':array_d_content_displayorder,
			// 'd_content_type_id[]':array_d_content_type_id,
			// 'd_infobox_content[]':array_d_infobox_content,
			// 'd_content_text[]':array_d_content_text,
			// 'd_content_width[]':array_d_content_width,
			// 'd_content_pos[]':array_d_content_pos,
			// 'd_content_align[]':array_d_content_align,
			'dsortable': $('#dsortablef').serializeArray(),
			'allItem': $('#allItem').serializeArray(),
			'find':'d_pre'
		};
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemContent&pos=Next_to_Product_Photo',
			success: function (data) {
			$('#Next_to_Product_Photo').html(data);
			}
		});
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemContent&pos=Below_All_Product_Photo',
			success: function (data) {
			$('#Below_All_Product_Photo').html(data);
			}
		});
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemContent&pos=Below_All_Product_Posters',
			success: function (data) {
			$('#Below_All_Product_Posters').html(data);
			}
		});
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=itemContent&pos=above_product_Photo',
			success: function (data) {
			$('#above_product_Photo').html(data);
			$('.desc_details').css({'font-size': $('#DescriptionsfontSize').val() + 'px'});
			$('.descdiv').css({'margin-top': $('#Descriptionlimargin').val() + 'px'});
			}
		});

		var n1 = $(".dsort").length;
		if(n1 > 0){
		//$( ".d_Pre_info" ).show();
		}else{
		//$( ".d_Pre_info" ).hide();
		}
		return false;
      });
	   
	$( ".m_Pre_info" ).click(function() {
				
		array_m_content_displayorder = new Array;
		$(".m_content_displayorder").each(function () {
			array_m_content_displayorder.push($(this).val());
		});
		array_m_content_type_id = new Array;
		$(".m_content_type_id").each(function () {
			array_m_content_type_id.push($(this).val());
		});
		array_m_infobox_content = new Array;
		$(".m_infobox_content").each(function () {
			array_m_infobox_content.push($(this).val());
		});				
		array_m_content_text = new Array;
		$(".m_content_text").each(function () {
			array_m_content_text.push($(this).val());
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
			'editor[]': arrayeditor,
			'layout': $('#layout_style_name').val(),
			// 'm_content_displayorder[]':array_m_content_displayorder,
			// 'm_content_type_id[]':array_m_content_type_id,
			// 'm_infobox_content[]':array_m_infobox_content,
			// 'm_content_text[]':array_m_content_text,
			'msortable': $('#msortablef').serializeArray(),
			'allItem': $('#allItem').serializeArray(),
		};
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=mobileView',
			success: function (data) {
				$('#mobilebox').html(data).ready(function () {
				 $('#tb_eb_CFI_style_master_desc_list').change();
					// mobile view background:
					// if ($('#tb_eb_CFI_style_master_BP').val() == "Color") {
						// $('#bg_P').hide("fast");
						// $('#bg_C').show("fast");
						// $('#mobilebox').css("background-image", "none");
						
						// var findc = $('input[name=eb_tp_clr_Mainbody_background]').val().indexOf("#");
						// if(findc > -1)
							// $('#mobilebox').css("background-color", $('input[name=eb_tp_clr_Mainbody_background]').val());
						// else	
							// $('#mobilebox').css("background-color", "#" + $('input[name=eb_tp_clr_Mainbody_background]').val());
					// }else{
						// $('#bg_P').show("fast");
						// $('#bg_C').hide("fast");
						// $('#mobilebox').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
					// }
					
				if ($('#tb_eb_CFI_style_policy_BP').val() == "Color") {
					$('#Pg_P').hide("fast");
					$('#Pg_C').show("fast");
					$('.policy_box').css("background-image", "none");
					$('.policy_box').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
					$('.mobpovinfo').css("background-image", "none");
					$('.mobpovinfo').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
					$('#policy_html').css("background-image", "none");
					$('#policy_html').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
				} else {
					$('#Pg_C').hide("fast");
					$('#Pg_P').show("fast");
					$('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
					$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
						// $('.mobpovinfo').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
				}
				shop_name();
				});
				
			}
		});
		var n1 = $(".msort").length;
		if(n1 > 0){
		$( ".m_Pre_info" ).show();
		}else{
		$( ".m_Pre_info" ).hide();
		}
		return false;
	});
	  
	$( ".Pre_info" ).click(function() {

		var data = {
			'allItem': $('#allItem').serializeArray(),
			'sortable': $('#sortablef').serializeArray(),	
			'msortable': $('#msortablef').serializeArray(),
			'dsortable': $('#dsortablef').serializeArray(),								
			'infodetclass':$(".infodetclass").serializeArray(),
		};
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=sideBar',
			success: function (data) {
				$('#menu').html(data);
				infochange();
			}
		});
		var n1 = $(".infono").length;
		if(n1 > 0){
		$( "#menu" ).show();
		}else{
		$( "#menu" ).hide();
		}
		return false;
      });
  });
  
    $(document).on('keydown keypress input', 'div[data-placeholder]' , function() {
        if (this.textContent) {
            this.dataset.divPlaceholderContent = 'true';
        }
        else {
            delete(this.dataset.divPlaceholderContent);
        }
    });
	var FX = {
easing: {
linear: function(progress) {
return progress;
},
quadratic: function(progress) {
return Math.pow(progress, 2);
},
swing: function(progress) {
return 0.5 - Math.cos(progress * Math.PI) / 2;
},
circ: function(progress) {
return 1 - Math.sin(Math.acos(progress));
},
back: function(progress, x) {
return Math.pow(progress, 2) * ((x + 1) * progress - x);
},
bounce: function(progress) {
for (var a = 0, b = 1, result; 1; a += b, b /= 2) {
if (progress >= (7 - 4 * a) / 11) {
return -Math.pow((11 - 6 * a - 11 * progress) / 4, 2) + Math.pow(b, 2);
}
}
},
elastic: function(progress, x) {
return Math.pow(2, 10 * (progress - 1)) * Math.cos(20 * Math.PI * x / 3 * progress);
}
},
animate: function(options) {

var start = new Date;
var id=this.animateID= setInterval(function() {
var timePassed = new Date - start;
var progress = timePassed / options.duration;
if (progress > 1) {
progress = 1;
}
options.progress = progress;
var delta = options.delta(progress);
options.step(delta);
if (progress == 1) {
clearInterval(id);
options.complete();
}
}, options.delay || 10);
},
fadeIn: function(element, options) {
var to = 0;
this.animate({
duration: options.duration,
delta: function(progress) {
progress = this.progress;
return FX.easing.swing(progress);
},
complete: options.complete,
step: function(delta) {
var browserName=navigator.appName; 
var ieo = (to + delta)*100;
if (browserName=='Microsoft Internet Explorer') { 
element.style.filter = 'alpha(opacity='+ieo+')';
}else{
element.style.opacity = to + delta;
}
}
});
}
};
window.FX = FX;

function changeImages2(src){
	if(FX.animateID){
clearInterval(FX.animateID);
}
var browserName=navigator.appName; 
		document.getElementById('linkId').href = src;
       document.getElementById('sample_Bigimg').src = src;
	   FX.fadeIn(document.getElementById('sample_Bigimg'), {
duration: 500,
complete: function() {
}
});
    }

function add_uploaded_file(box_id,file_name){
   $("#"+box_id).append($("<p>"+file_name+"</p>"));
}
var uploadedGalleryFiles=[];
var uploadedVariationFiles={};
Dropzone.autoDiscover = false;
$("form.dropzone").each(function(){

	var name = $(this).attr('name');
	var title = $(this).attr('title');
	var P_id = $(this).attr('id');
		
	$(this).dropzone({
		// 不定义url dropzone还是会发出一个有问题的请求，虽然不影响图片上传但还是写一条没用的链接给它
		url: global.baseUrl+ 'listing/ebay-template/no-return',

   // dictDefaultMessage:$(this).attr("dz-message"),
	
   init:function(){
// 	   this.on("error",function(file,response){
// 		   console.log(file);
// 		   console.log(response);
// 		   console.log(arguments);
// 			$(".Loadingdialog").dialog("close");
// 	   });
// 	   this.on("success",function(file,response){
// 			if(P_id=='Menubar_up' || P_id == 'mobile_up' || P_id == 'policy_up' || P_id == 'Body_up' || P_id == 'cat_up'){
// 			$('#'+title).val(response);
// 			$('.'+title).click();
// 			$(".tooltip").hide('fast');
// 			$('#'+name).css("background-image", "url('" + response + "')");
// 			if(P_id == 'policy_up'){
// 			$('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
// 			$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
// 			}
// 			}else{
			
// 			$('#'+name+'').val(response);
// 			$('#'+title+'_P').click();
// 			//$('#'+title+'anner').hide();	
// 		   if(response!="") {
			 
// 		   }
// 			}
// 			$(".Loadingdialog").dialog("close");
// 	   });
// 	   this.on("addedfile", function(file) {
// 		$("#Loading").dialog({
// 			width: 'auto',
// 			modal: true
// 		});
// 		   var removeButton = Dropzone.createElement("<button class='btn_00'>Remove</button>");
// 		   var _this = this;
// 			_this.removeAllFiles(true)                           
// 	   });
	   	var dropzone = this;
		dropzone.on("addedfile", function(file) {
			$("#Loading").dialog({
			width: 'auto',
			height: 61,
			modal: true
			});

			var removeButton = Dropzone.createElement("<button class='btn_00'>Remove</button>");
			var _this = this;
			_this.removeAllFiles(true);

			$.uploadOne({
				
				file:file,
				// 当获取到服务器数据时，触发success回调函数
				onUploadSuccess: function (data){
					var response = data.original;
					
					if(P_id=='Menubar_up' || P_id == 'mobile_up' || P_id == 'policy_up' || P_id == 'Body_up' || P_id == 'cat_up'){
						$('#'+title).val(response);
						$('.'+title).click();
						$(".tooltip").hide('fast');
						$('#'+name).css("background-image", "url('" + response + "')");
						if(P_id == 'policy_up'){
							$('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
							$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
						}
					}else{
						$('#'+name+'').val(response);
						$('#'+title+'_P').click();
						//$('#'+title+'anner').hide();	
						if(response!="") {

						}
					}
					$(".Loadingdialog").dialog("close");
				},

				// 从服务器获取数据失败时，触发error回调函数。  
				onError: function(xhr, status, e){
					console.log(xhr);
					console.log(response);
					console.log(arguments);
					$(".Loadingdialog").dialog("close");
					$(".tooltip").hide('fast');
				}
			});                           
		});
   }
});});
				
 $(function() {

// sample_Bigimg pop effect 
popup_c = document.getElementById('popupbox');
popup_c.onclick = hide_popup;
popup('linkId');
create_bin();
create_qr();

// 产品图片 6张=> popup 触发0-5
popup('mbp0');
popup('mbp1');
popup('mbp2');
popup('mbp3');
popup('mbp4');
popup('mbp5');

$('.DLimg').click( function(){
	var str = $("#"+this.title).val();
	// if (str.search("http") >= 0 || true){// local link without http 
	if ($("#"+this.title).length > 0){
		$.fancybox([{href : $("#" + this.title).val(), title : '', width	: 800,Height	: 600}]);
	}else{
		var str = $("#"+this.name).val();
		if (str.search("http") >= 0){
			//$(this).attr("src",$("#BasedPath").val()+$("#Admin").val()+$("#" + this.name).val());
			$.fancybox([{href : $("#"+this.name).val()+$("#" + this.title).val(), title : '', width	: 800,Height	: 600}]);			
			}else{
			$.fancybox([
			{
			href : $("#BasedPath").val()+$("#"+this.name).val()+$("#" + this.title).val(), 
			title : '',
			autoSize : false,
			maxWidth:700,
			type:"iframe",
			maxHeight:500
			}]);
			
			}
	}
}) 

 var timeout;


function hide() {
    timeout = setTimeout(function () {
        $(".tooltip").hide('fast');
    }, 500);
};
$(document).on('click', ".tip" ,function (e) {
    // clearTimeout(timeout);
	// $(".tooltip").hide('fast');
	// $("." + this.name).css({'position': 'fixed'});	
	
	// if (window.innerWidth)
		// winWidth = window.innerWidth;
	// else if ((document.body) && (document.body.clientWidth))
		// winWidth = document.body.clientWidth;
	
	// if (window.innerHeight)
		// winHeight = window.innerHeight;
	// else if ((document.body) && (document.body.clientHeight))
		// winHeight = document.body.clientHeight;

	// if((winWidth -  e.clientX) < 920 || e.clientX > (winWidth * (2 / 3) )){
		// $("." + this.name).css({ "left": (e.clientX-920)+"px" });
	// }
	// if( (winHeight - e.clientY) < 500 || e.clientY > (winHeight * (2 / 3) ) ){
		// $("." + this.name).css({  "top": e.clientY -500 +"px", "left": e.clientX+15+"px" });
	// }else{
		// $("." + this.name).css({  "top": e.clientY-50+"px", "left": e.clientX+15+"px" });
	// }
	clearTimeout(timeout);
	$(".tooltip").hide('fast');
	$("." + this.name).css({'position': 'fixed'});	
	if( e.clientY > ($(window).height() * (2 / 3) ) ){
		$("." + this.name).css({  "top": e.clientY -500 +"px", "left": e.clientX+15+"px" });
	}else{
		$("." + this.name).css({  "top": e.clientY-50+"px", "left": e.clientX+15+"px" });
	}
    $("."+this.name).stop().show('fast');
}).mouseout();
// $(".tooltip").mouseover(function () {
//     clearTimeout(timeout);
// }).mouseout(hide);
 $(".imgborder").focusout(function () {
 var str = $(this).val();
 if (str.search("swf") >= 0){
   $("#"+$(this).attr('id')+"_HW").css('display', ""); 
 }
 });
 $(".imgborder").mouseover(function () {
}).mouseout(
imghide
);

 $(".imgborder").focus(function () {
 
  var str = $(this).val(); 
 if (str.search("swf") >= 0){
   $("#"+$(this).attr('id')+"_HW").css('display', ""); 
 }
}).focusout(
imghide
);


function imghide() {
 timeout = setTimeout(function () {
        $(".sampleimg").css('border', "solid 0px #FFFFFF");  
		 }, 500);
		 return false;
		
};

$(document).on('change','.d_content_pos',function () {
					cn = $(this).parent().parents('.dsort');
					var classNames = cn.attr('class').split(' ');
					
					if($(this).val() == 'Next_to_Product_Photo')
					{
						$('.'+classNames[1]+' .d_content_width').children('option[value="full"]').css('display','none');
						$('.'+classNames[1]+' .d_content_width')[0].selectedIndex = 1;
					}else{
						$('.'+classNames[1]+' .d_content_width').children('option[value="full"]').css('display','block');
					}
					})

$('.afchange').bind('keyup change focusout', function () {
					afchange();
					})
					
 })

// click toolbar field focus that fields'info on page  
 $( "#dsortable" ).on('click','li',function () {
	$('#d_pre'+$(this).find('.d_content_displayorder').val()).css('border', "5px solid #7ced04" );
	// $(this).css('border', "2px solid #7ced04" );
	$('#dsortable .infono'+$(this).find('.d_content_displayorder').val()).css('border', "2px solid #7ced04" );
	$("html, body") .animate({ scrollTop: $('#d_pre'+$(this).find('.d_content_displayorder').val()).offset().top - 200 +'px'});
});
 $( "#dsortable" ).on('mouseleave','li',function () {
 $('.cbp').css('border', "0px solid #7ced04" );
 $('.dsort').css('border', "0px solid #7ced04" );  
});
 $( "#sortable" ).on('click','li',function () {
 $('#pre'+$(this).find('.content_displayorder').val()).css('border', "5px solid #7ced04" );
	// $(this).css('border', "2px solid #7ced04" );
  $('#sortable .infono'+$(this).find('.content_displayorder').val()).css('border', "2px solid #7ced04" );
  $("html, body") .animate({ scrollTop: $('#pre'+$(this).find('.content_displayorder').val()).offset().top - 200 +'px'});
});
$( "#sortable" ).on('mouseleave','li',function () {
 $('.pbp').css('border', "0px solid #7ced04" );
 $('.sort').css('border', "0px solid #7ced04" );
});
 $( "#msortable" ).on('click','li',function () {
 $('#m_pre'+$(this).find('.m_content_displayorder').val()).css('border', "5px solid #7ced04" );
	// $(this).css('border', "2px solid #7ced04" );
 $('#msortable .infono'+$(this).find('.m_content_displayorder').val()).css('border', "2px solid #7ced04" );
   $("#mobilebox").scrollTop($('#m_pre'+$(this).find('.m_content_displayorder').val()).position().top + $("#mobilebox").scrollTop());
});
 $( "#msortable" ).on('mouseleave','li',function () {
 $('.mbp').css('border', "0px solid #7ced04" );
 $('.msort').css('border', "0px solid #7ced04" );  
});

$( "#allItem" ).on('click','.detail',function () {
	var cake = $(this);
	if(cake.attr('name') == 'tabtool'){
		$("html, body") .animate({ scrollTop: $('#feedback_html').offset().top - 200 +'px'});
	}	
	$.prompt('', {
		title: "",
		top:5,
		outerBoxWidth: $('#'+cake.attr('name')).width()+20+'px',
		buttons: { "确定": true },
		submit: function(e,v,m,f){
			$('#bigb').append($('#'+cake.attr('name')));
			$('.jqiclose').show();
			$('#'+cake.attr('name')).hide();
			if(cake.attr('name') == 'MenuD'){
				menu();	
			}	
									
		},
		loaded:function(e){
			$('.jqiclose').hide();
			$('#'+cake.attr('name')).show();
			var popup=e.target;
			$(popup).find('.jqimessage').append($('#'+cake.attr('name')));
		}
	});
		 
});

 </Script>

