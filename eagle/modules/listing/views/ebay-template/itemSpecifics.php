
<input type="text"  id="boxcontent" value="content2" style="display: none;">
<div id="d_item_details" class="">
	<form id="alllist">
		<table width="100%"></table>
		<table>
			<tbody>
				<tr>
					<td style="width: 100px;">Theme</td>
				</tr>
				<tr>
					<td style="width: 200px;">
						<select style="width: 170px;" id="c_theme" name="c_theme" class="form-control">
							<option value="Red">Red</option>
							<option value="Blue">Blue</option>
							<option value="Green">Green</option>
							<option value="Orange">Orange</option>
							<option value="Purple">Purple</option>
							<option value="Grey">Grey</option>
							<option value="Black" selected>Black</option>
							<option value="Clear">Clear</option>
						</select>
					</td>
				</tr>
					<tr class="Auto_Fill_ON">
					<td>
						<div style="display: inline-block;">边界</div>
					</td>
				</tr>
				<tr class="Auto_Fill_ON">
					<td><select name="attributes_border_style" id="attributes_border_style" class="in80 afchange form-control" style="">
							<option value="none">none</option>
							<option value="solid">solid</option>
							<option value="dashed">dashed</option>
							<option value="dotted">dotted</option>
							<option value="double">double</option>
							<option value="groove">groove</option>
							<option value="ridge">ridge</option>
							<option value="inset">inset</option>
							<option value="outset">outset</option>
						</select></td>
				</tr>
				<tr class="Auto_Fill_ON">
					<td>
						<select name="attributes_size" id="attributes_size" class="in40 afchange form-control">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="6">6</option>
							<option value="7">7</option>
							<option value="8">8</option>
							<option value="9">9</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
						</select>
					</td>
				</tr>
				<tr class="Auto_Fill_ON">
					<td style="line-height: 10px;">
						<input class='pMultiple' type="text" name="border_Mainbody_background" style="height: 30px;" value="#e5e5e5" />
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="hideitems" id="items" value="hideitems">隐藏于模板上，增加在ebay 被搜寻机会
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox">复制到手机模板
					</td>
				</tr>
			</tbody>
		</table>
		<table id="attributes" style="">
			<tbody>
				<tr>
					<td colspan="4">
						<hr></td>
				</tr>
				<tr>
					<td>
						Title Bar Style
						<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

							<input type="radio" value="pic" class="eazy_pic1 ui-helper-hidden-accessible" data-himnameshow="attr_t_b_div_p" data-himnamehide="attr_t_b_div_c" data-himnameclear="attr_t_c" data-button="attr_tcheckb" id="attr_tchecka" name="radioattr_t">	
							<label for="attr_tchecka" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
								<span class="ui-button-text">picture</span>
							</label>

							<input type="radio" value="color" class="eazy_pic2 ui-helper-hidden-accessible" data-himnameshow="attr_t_b_div_c" data-himnamehide="attr_t_b_div_p" data-himnameclear="attr_t_b" data-button="attr_tchecka" id="attr_tcheckb" name="radioattr_t">	
							<label for="attr_tcheckb" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">color</span>
							</label>
							<input type="radio" value="off" class="eazy_pic3 ui-helper-hidden-accessible" data-himnameshow="attr_t_b_div_c" data-himnamehide="attr_t_b_div_p" data-himnameclear="attr_t_c" data-button="attr_t_b" id="attr_tcheckall" name="radioattr_t">	
							<label for="attr_tcheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">off</span>
							</label>
							<div id="attr_t_b_div_p" style="margin-left: 6px;">
								<input type="text" name="tb_eb_CFI_style_master_background_Pattern" id="theme" class="in170 infodetclass noneishide" data-noneishide="attr_t_b_div_p" value="<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png">	
								<a name="backgroundPattern" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text"></span>
								</a>
							</div>
							<div  id="attr_t_c_div_p" style="display: none;line-height: 10px;margin-top: -13px;"><input  class='font8 pMultiple'
									type="text" name="title_background" value="" style="height:30px;" />
							</div>
						</div>
						<div class="backgroundPattern tooltips tipdiv">
								<table>
									<tr>
										<td>
										<table>
											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
													checked>Background Pattern style 1 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										<input type='button' name='Go' class='goCancel btn_01 goright'
									value='Confirm' />
										</td>
									</tr>
								</table>
						</div>
						Title Font :
						<div>
							<select name="name_text_style" id="name_text_style" class="form-control">
								<option value="select">select</option>
								<option value="Arial">Arial</option>
								<option value="Times">Times</option>
								<option value="Andale Mono">Andale Mono</option>
							</select>
						</div>
						
						<span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
						<select name="Title_f_size" id="Title_f_size" class="form-control">
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
						</select>
						<div style="line-height: 10px;">
							<input class='pMultiple' type="text" name="Mainbody_background" style="height: 30px;" value="#ffffff" />
						</div>
						
					
						<select name="Title_f_align" id="Title_f_align" class="form-control">
							<option value="left">偏左</option>
							<option value="right">偏右</option>
						</select>
						<hr>	
						Alternate background style:
						<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

							<input type="radio" value="pic" class="eazy_pic4 ui-helper-hidden-accessible" data-himnameshow="attr_b_b_div_p" data-himnamehide="attr_b_b_div_c" data-himnameclear="attr_b_c" data-button="attr_bcheckb" id="attr_bchecka" name="radioattr_b">	
							<label for="attr_bchecka" class="ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
								<span class="ui-button-text">picture</span>
							</label>

							<input type="radio" value="color" class="eazy_pic5 ui-helper-hidden-accessible" data-himnameshow="attr_b_b_div_c" data-himnamehide="attr_b_b_div_p" data-himnameclear="attr_b_b" data-button="attr_bchecka" id="attr_bcheckb" name="radioattr_b">	
							<label for="attr_bcheckb" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">color</span>
							</label>
							<input type="radio" value="off" class="eazy_pic6 ui-helper-hidden-accessible" data-himnameshow="attr_b_b_div_c" data-himnamehide="attr_b_b_div_p" data-himnameclear="attr_b_c" data-button="attr_b_b" id="attr_bcheckall" name="radioattr_b">	
							<label for="attr_bcheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">off</span>
							</label>
							<div id="attr_b_b_div_p" style="margin-left: 6px; display: none;">
								<input type="text" name="attr_b_b" id="attr_b_b" class="in170 infodetclass noneishide" data-noneishide="attr_b_b_div_p" value="">	
								<a name="attr_b_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text"></span>
								</a>
							</div>
							<div id="attr_b_c_div_p" style="line-height: 10px;margin-top: -13px;"><input class='font8 pMultiple'
									type="text" name="b1_Mainbody_background" style="height:30px;" value="#ffffff" />
							</div>
						</div>
						<div class="attr_b_b_u tooltips tipdiv">
								<table>
									<tr>
										<td>
										<table>
											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
													checked>Background Pattern style 1 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										<input type='button' name='Go' class='goCancel btn_01 goright'
									value='Confirm' />
										</td>
									</tr>
								</table>
						</div>

						<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

							<input type="radio" value="pic" class="eazy_pic7 ui-helper-hidden-accessible" data-himnameshow="attr_b2_b_div_p" data-himnamehide="attr_b2_b_div_c" data-himnameclear="attr_b2_c" data-button="attr_b2checkb" id="attr_b2checka" name="radioattr_b2">	
							<label for="attr_b2checka" class="ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
								<span class="ui-button-text">picture</span>
							</label>

							<input type="radio" value="color" class="eazy_pic8 ui-helper-hidden-accessible" data-himnameshow="attr_b2_b_div_c" data-himnamehide="attr_b2_b_div_p" data-himnameclear="attr_b2_b" data-button="attr_b2checka" id="attr_b2checkb" name="radioattr_b2">	
							<label for="attr_b2checkb" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">color</span>
							</label>
							<input type="radio" value="off" class="eazy_pic9 ui-helper-hidden-accessible" data-himnameshow="attr_b2_b_div_c" data-himnamehide="attr_b2_b_div_p" data-himnameclear="attr_b2_c" data-button="attr_b2_b" id="attr_b2checkall" name="radioattr_b2">	
							<label for="attr_b2checkall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
								<span class="ui-button-text">off</span>
							</label>
							<div id="attr_b2_b_div_p" style="margin-left: 6px; display: none;">
								<input type="text" name="attr_b2_b" id="attr_b2_b" class="in170 infodetclass noneishide" data-noneishide="attr_b2_b_div_p" value="">	
								<a name="attr_b2_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text"></span>
								</a>
							</div>
								<div id="attr_b2_c_div_p" style="line-height: 10px;margin-top: -13px;"><input class='font8 pMultiple'
									type="text" name="b2_Mainbody_background" style="height: 30px;" value="#e5e5e5" />
								</div>
						</div>
						<div class="attr_b2_b_u tooltips tipdiv">
								<table>
									<tr>
										<td>
										<table>
											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
													checked>Background Pattern style 1 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='alternate_background_style_r'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 100px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										<input type='button' name='Go' class='goCancel btn_01 goright'
									value='Confirm' />
										</td>
									</tr>
								</table>
						</div>
						<hr>	
						Text Font :
						<div>
							<select id="Text_f_FS" name="Text_f_FS" class="form-control">
								<option value="Arial">Arial</option>
								<option value="Times">Times</option>
								<option value="Andale Mono">Andale Mono</option>
							</select>
						</div>
						
						<span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
						<select name="Text_f_size" id="Text_f_size" class="form-control">
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
						</select>
						<div style="line-height: 10px;"><input class='pMultiple'
									type="text" name="text_Mainbody_background" style="height: 30px;" value="" />
						</div>
						<select name="Text_f_align" id="Text_f_align" class="form-control">
							<option value="left">偏左</option>
							<option value="right">偏右</option>
						</select>
						<br>	
						Bold Label
						<div>
							<select name="Bold_label" id="Bold_label" class="form-control">
								<option value="yes">yes</option>
								<option value="no">no</option>
							</select>
						</div>
						<hr></td>
				</tr>

				<tr>
					<td>
						<div class="toptoptop"></div>
					</td>
				</tr>
			
			</tbody>
		</table>
	</form>
</div>


<script type="text/javascript">
	$(document).ready(function(){
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

		    
});
</script>
<script type="text/javascript">
	$(document).ready(function(){
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
		})
	
		
	});
	

</script>
<script type="text/javascript">
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


</script>
<script type="text/javascript">        
  $(document).ready(
    function()
    {
    	// var desc=$('#Attrid2');
      $('.pMultiple').jPicker({
	                    window: {
	                        position: {
	                            x: 'right',
	                            y: 'bottom'
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
					})          
    });  
</script>

