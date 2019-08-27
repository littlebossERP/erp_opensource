<div id="D_call_for_action">
	<form id="alllist">
		<!-- <div class="greenbox" info="<img src='/ssl/css/img/icon/i_icon_200.png' alt='infoadd' height='20' width='20'>
			ction button设计令买家更易购买产品。按钮会在不同情形显示....
			<br>	
			<br>	
			1)当该刊登为拍卖时，只会显示“Place bid”和 “Direct-Buy” 按钮会同时出现。
			<br>	
			2)当该刊登为定价时，只会显示“Buy now”和“Add to Cart” 按钮。
			<br>	
			3)当该刊登为多选项产品时，将不会有任何按钮出现。
			<br>	
			<br>	
			机制:
			<br>	
			<br>	
			当列表为拍卖时和“Direct-Buy”启用时，系统会从列表是自动搜索 GTC 和 相同的销售最佳SKU为“Direct-Buy”的连结。
			<br>	
			<br>	
			当买家按“Direct-Buy”他会到GTC 的物品的价钱，拍卖清单可以继续吸引买家出价。" gshow="
			<img src='/ssl/css/img/icon/i_icon_200.png' alt='infoadd' height='20' width='20'>	
			Action button设计令买家更易购买产品。按钮会在不同情形显示....">
			<img src="/ssl/css/img/icon/i_icon_200.png" alt="infoadd" height="20" width="20">Action button设计令买家更易购买产品。按钮会在不同情形显示....</div> -->
		<input type="text" id="boxcontent" value="content3" style="display: none;">	
		<div class="form-section">
			<div class="section-title">Action Button Style</div>
			<table class="form-table" style="">
				<tbody>
					<tr class="Auto_Fill_ON">
						<div colspan="4">
							<div style="display: inline-block;">边界</div>
							<div>
								<select name="Action_button_style_f" id="Action_button_style_f" class="in80 afchange form-control" style="">
								<option value="none">none</option>
								<option value="solid">solid</option>
								<option value="dashed">dashed</option>
								<option value="dotted">dotted</option>
								<option value="double">double</option>
								<option value="groove">groove</option>
								<option value="ridge">ridge</option>
								<option value="inset">inset</option>
								<option value="outset">outset</option>
							</select>
							</div>
							<div>
								<select name="Action_button_size_f" id="Action_button_size_f" class="in40 afchange form-control">
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
							</div>	
							<div style="line-height: 10px;"><input style="" type="text" name="Action_button_color_f" id="Action_button_color_f" class="pMultiple afchange" value="b2b2b2"></div>			
						</div>
					</tr>
					<tr style="margin-top:15px; ">
						<div>
							主题设定
							<div>
								<select style="width: 110px;" id="c_theme" class="form-control">
								<option value="">Select</option>
								<option value="Blue+Green">Blue+Green</option>
								<option value="Black+Red">Black+Red</option>
								<option value="Green+Orange">Green+Orange</option>
								<option value="Black+Grey">Black+Grey</option>
								<option value="Orange+Black">Orange+Black</option>
								<option value="Pink+Purple">Pink+Purple</option>
							</select>
							</div>
							
						</div>
					</tr>
					<tr style="margin-top: 15px;">
						<div colspan="2">
							怖局风格
							<div>
								<select style="width: 170px;" id="Action_button_style" name="Action_button_style" class="form-control">
								<option value="hori">Hori. Action button</option>
								<option value="vert">Vert. Action button</option>
							</select>
							</div>
							
						</div>
					</tr>

					<tr style="margin-top: 15px;">
						<div>
							Text Message Setting
							<div>
								<input type="text" name="tfl_f_FS" id="tfl_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="Arial">
							</div>
							<div>
								<select name="tfl_f_size" id="tfl_f_size" class="form-control" style="">
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
							</div>
							<div style="line-height: 10px;">
								<input type="text" class="pMultiple" value="c9c9c9" style="">
							</div>
						</div>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="form-section" style="margin-top: 10px;">
			<div class="section-title">Buttons for AUCTION Listing</div>
			<table class="form-table" style="">
				<tbody>

					<tr>
						<div>
							<input type="checkbox" name="algtci" value="algtci" class="checkturn" data-hide="gtc" checked="checked"></div>
						<label>Auction linkto GTC item</label>
					</tr>
					<tr class="gtc">
						<div>按钮标籤固定价格</div>
						<div><input type="text" name="fpabl" id="fpabl" value="Buy-Direct"></div>
					</tr>
					<tr class="disnone">
						<div></div>
						<div colspan="3">
							<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

								<input type="radio" value="pic" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_fpabl_b_div_p" data-himnamehide="attr_fpabl_b_div_c" data-himnameclear="attr_fpabl_c" data-button="attr_fpablcheckb" id="attr_fpablchecka" name="radioattr_fpabl">	
								<label for="attr_fpablchecka" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
									<span class="ui-button-text">picture</span>
								</label>

								<input type="radio" value="color" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_fpabl_b_div_c" data-himnamehide="attr_fpabl_b_div_p" data-himnameclear="attr_fpabl_b" data-button="attr_fpablchecka" id="attr_fpablcheckb" name="radioattr_fpabl">	
								<label for="attr_fpablcheckb" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">color</span>
								</label>
								<input type="radio" value="off" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_fpabl_b_div_c" data-himnamehide="attr_fpabl_b_div_p" data-himnameclear="attr_fpabl_c" data-button="attr_fpabl_b" id="attr_fpablcheckall" name="radioattr_fpabl">	
								<label for="attr_fpablcheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">off</span>
								</label>
								<div id="attr_fpabl_b_div_p" style="margin-left: 6px;">
									<input type="text" name="attr_fpabl_b" id="attr_fpabl_b" class="in170 infodetclass noneishide" data-noneishide="attr_fpabl_b_div_p" value="">	
									<a name="attr_fpabl_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
									<a name="attr_fpabl_b_u" class="layout_select upload26 tip ui-button ui-widget ui-state-default ui-button-text-only ui-corner-right" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
								</div>
								<div id="attr_fpabl_b_div_c" style="display: none;">
									<input type="text" name="attr_fpabl_c" id="attr_fpabl_c" class="font8 pMultiple afchange noneishide" data-noneishide="attr_fpabl_b_div_c" value="" style="color: rgb(255, 255, 255); background-color: rgb(0, 0, 0);">
								</div>
							</div>
							Text setting
							<div>
								<input type="text" name="fpabl_f_FS" id="fpabl_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="Arial" autocomplete="off">
							</div>
							<div>
								<select name="fpabl_f_size" id="fpabl_f_size" class="form-control">
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
							</div>
							<div style="line-height: 10px;">
								<input type="text" name="fpabl_f_c" id="fpabl_f_c" class="pMultiple in40" value="#000000" style="">
							</div>
							<div>
								Button Width:
							</div>
							<div>
								<input style="width: 90px;" type="text" name="fpabl_f_w" id="fpabl_f_w" value="" class="form-control">
							</div>
							<div>
								Height:
							</div>	
							<div>
								<input style="width: 90px;" type="text" name="fpabl_f_h" id="fpabl_f_h" value="">
							</div>
							
					</tr>
					<tr class="gtc">
					<div>
						Text for linking
					</div>
					<div>
						<input type="text" name="tfl" id="tfl" value="What are you waiting for?Place the bid NOW">
					</div>
					</tr>
					<tr>
						<div>
							<input type="checkbox" name="bla_h" value="bla_h" class="checkturn" data-hide="bla_h_hide" checked="checked"></div>
						<div>按钮标籤拍卖</div>
						<div class="bla_h_hide">
							<input type="text" name="pt_aabl" id="pt_aabl" value="Place Bid"></div>
						<div class="bla_h_hide">
							<a href="#" class="aset attrdetailsi"></a>
						</div>
					</tr>

					<tr class="disnone bla_h_hide_h">
						<div></div>
						<div colspan="3">
							<a style="margin-right: 83px;">Text for linking</a>
							<input type="text" name="bla_h_fl" id="bla_h_fl" value="Don't want to wait...">	
							<br>	

							<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

								<input type="radio" value="pic" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_aabl_b_div_p" data-himnamehide="attr_aabl_b_div_c" data-himnameclear="attr_aabl_c" data-button="attr_aablcheckb" id="attr_aablchecka" name="radioattr_aabl">	
								<label for="attr_aablchecka" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
									<span class="ui-button-text">picture</span>
								</label>

								<input type="radio" value="color" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_aabl_b_div_c" data-himnamehide="attr_aabl_b_div_p" data-himnameclear="attr_aabl_b" data-button="attr_aablchecka" id="attr_aablcheckb" name="radioattr_aabl">	
								<label for="attr_aablcheckb" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">color</span>
								</label>
								<input type="radio" value="off" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_aabl_b_div_c" data-himnamehide="attr_aabl_b_div_p" data-himnameclear="attr_aabl_c" data-button="attr_aabl_b" id="attr_aablcheckall" name="radioattr_aabl">	
								<label for="attr_aablcheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">off</span>
								</label>
								<div id="attr_aabl_b_div_p" style="margin-left: 6px;">
									<input type="text" name="attr_aabl_b" id="attr_aabl_b" class="in170 infodetclass noneishide" data-noneishide="attr_aabl_b_div_p" value="">	
									<a name="attr_aabl_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
									<a name="attr_aabl_b_u" class="layout_select upload26 tip ui-button ui-widget ui-state-default ui-button-text-only ui-corner-right" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
								</div>
								<div id="attr_aabl_b_div_c" style="display: none;">
									<input type="text" name="attr_aabl_c" id="attr_aabl_c" class="font8 pMultiple afchange noneishide" data-noneishide="attr_aabl_b_div_c" value="" style="color: rgb(255, 255, 255); background-color: rgb(0, 0, 0);">	
								</div>
							</div>
							Text setting
							<div>
								<input type="text" name="pt_aabl_f_FS" id="pt_aabl_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="" autocomplete="off">
							</div>
							<div>
								<select name="pt_aabl_f_size" id="pt_aabl_f_size" class="form-control">
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
							</div>
							<div style="line-height: 10px;">
								<input type="text" name="pt_aabl_f_c" id="pt_aabl_f_c" class="pMultiple in40" value="#000000" style="">
							</div>
							<div>
								Button Width:
							</div>
							<div>
								<input style="width: 90px;" type="text" name="pt_aabl_w" id="pt_aabl_w" value="" class="form-control">
							</div>
							<div>
								Height:
							</div>
							<div><input style="width: 90px;" type="text" name="pt_aabl_h" id="pt_aabl_h" value="" class="form-control"></div>
							</div>
							
					</tr>
				</tbody>
			</table>
		</div>
		<div class="form-section">
			<div class="section-title">Buttons for FIXED PRICE Listing</div>
			<table class="form-table">
				<tbody>

					<tr>
						<div>Text for linking</div>
						<div class="">
							<input type="text" name="fprice_fl" id="fprice_fl" value="Limited stock,what are you waiting for??">
						</div>
					</tr>
					<tr>
						<div>
							<input type="checkbox" name="blfp_h" value="blfp_h" class="checkturn" data-hide="blfp_h_hide" checked="checked"></div>
						<div>按钮标籤固定价格</div>
						<div class="blfp_h_hide">
							<input type="text" name="fp_buy" id="fp_buy" value="Buy Now"></div>
						<div class="blfp_h_hide">
							<a href="#" class="aset attrdetailsi"></a>
						</div>
					</tr>
					<tr class="disnone blfp_h_hide_h">
						<div></div>
						<div colspan="3">

							<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

								<input type="radio" value="pic" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_buy_b_div_p" data-himnamehide="attr_buy_b_div_c" data-himnameclear="attr_buy_c" data-button="attr_buycheckb" id="attr_buychecka" name="radioattr_buy">	
								<label for="attr_buychecka" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
									<span class="ui-button-text">picture</span>
								</label>

								<input type="radio" value="color" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_buy_b_div_c" data-himnamehide="attr_buy_b_div_p" data-himnameclear="attr_buy_b" data-button="attr_buychecka" id="attr_buycheckb" name="radioattr_buy">	
								<label for="attr_buycheckb" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">color</span>
								</label>
								<input type="radio" value="off" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_buy_b_div_c" data-himnamehide="attr_buy_b_div_p" data-himnameclear="attr_buy_c" data-button="attr_buy_b" id="attr_buycheckall" name="radioattr_buy">	
								<label for="attr_buycheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">off</span>
								</label>
								<div id="attr_buy_b_div_p" style="margin-left: 6px;">
									<input type="text" name="attr_buy_b" id="attr_buy_b" class="in170 infodetclass noneishide" data-noneishide="attr_buy_b_div_p" value="">	
									<a name="attr_buy_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
									<a name="attr_buy_b_u" class="layout_select upload26 tip ui-button ui-widget ui-state-default ui-button-text-only ui-corner-right" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
								</div>
								<div id="attr_buy_b_div_c" style="display: none;">
									<input type="text" name="attr_buy_c" id="attr_buy_c" class="font8 pMultiple afchange noneishide" data-noneishide="attr_buy_b_div_c" value="" style="color: rgb(255, 255, 255); background-color: rgb(0, 0, 0);">	
								</div>
							</div>
							Text setting
							<div>
								<input type="text" name="buy_f_FS" id="buy_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="Arial" autocomplete="off">
							</div>
							<div>
								<select name="buy_f_size" id="buy_f_size" class="form-control">
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
							</div>
							<div style="line-height: 10px;">
								<input type="text" name="buy_f_c" id="buy_f_c" class="pMultiple in40" value="#000000" style="">
							</div>
							
							<div>
								Button Width:
							
							</div>
							<div>
								<input style="width: 90px;" type="text" name="buy_f_w" id="buy_f_w" value="">
							</div>
							<div>
								Height:
							</div>	
							<div>
								<input style="width: 90px;" type="text" name="buy_f_h" id="buy_f_h" value="">
							</div>
						</div>
					</tr>
					<tr>
						<div>
							<input type="checkbox" name="bat_h" value="bat_h" class="checkturn" data-hide="bat_h_hide" checked="checked"></div>
						<div>Button Add to cart</div>
						<div class="bat_h_hide">
							<input type="text" name="pt_batt" id="pt_batt" value="Add to cart"></div>
						<div class="bat_h_hide">
							<a href="#" class="aset attrdetailsi"></a>
						</div>
					</tr>
					<tr class="disnone bat_h_hide_h">
						<div></div>
						<div colspan="3">

							<div style="display: -webkit-box;" class="eazy_set ui-buttonset">

								<input type="radio" value="pic" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_batt_b_div_p" data-himnamehide="attr_batt_b_div_c" data-himnameclear="attr_batt_c" data-button="attr_battcheckb" id="attr_battchecka" name="radioattr_batt">	
								<label for="attr_battchecka" class="ui-state-active ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" role="button" aria-disabled="false">
									<span class="ui-button-text">picture</span>
								</label>

								<input type="radio" value="color" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_batt_b_div_c" data-himnamehide="attr_batt_b_div_p" data-himnameclear="attr_batt_b" data-button="attr_battchecka" id="attr_battcheckb" name="radioattr_batt">	
								<label for="attr_battcheckb" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">color</span>
								</label>
								<input type="radio" value="off" class="eazy_pic ui-helper-hidden-accessible" data-himnameshow="attr_batt_b_div_c" data-himnamehide="attr_batt_b_div_p" data-himnameclear="attr_batt_c" data-button="attr_batt_b" id="attr_battcheckall" name="radioattr_batt">	
								<label for="attr_battcheckall" class="ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
									<span class="ui-button-text">off</span>
								</label>
								<div id="attr_batt_b_div_p" style="margin-left: 6px;">
									<input type="text" name="attr_batt_b" id="attr_batt_b" class="in170 infodetclass noneishide" data-noneishide="attr_batt_b_div_p" value="">	
									<a name="attr_batt_b_u" class="layout_select folder26 tip ui-button ui-widget ui-state-default ui-button-text-only" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
									<a name="attr_batt_b_u" class="layout_select upload26 tip ui-button ui-widget ui-state-default ui-button-text-only ui-corner-right" role="button" aria-disabled="false">
										<span class="ui-button-text"></span>
									</a>
								</div>
								<div id="attr_batt_b_div_c" style="display: none;">
									<input type="text" name="attr_batt_c" id="attr_batt_c" class="font8 pMultiple afchange noneishide" data-noneishide="attr_batt_b_div_c" value="" style="color: rgb(255, 255, 255); background-color: rgb(0, 0, 0);">	
								</div>
							</div>
							Text setting
							<div>
								<input type="text" name="pt_batt_f_FS" id="pt_batt_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="" autocomplete="off">
							</div>
							<div>
								<select name="pt_batt_f_size" id="pt_batt_f_size" class="form-control">
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
							</div>
							<div style="line-height: 10px;">
								<input type="text" name="pt_batt_f_c" id="pt_batt_f_c" class="pMultiple in40" value="#000000" style="color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);">
							</div>
								
							<div>
								Button Width:
							
							</div>
							<div>
								<input style="width: 90px;" type="text" name="pt_batt_w" id="pt_batt_w" value="">
							</div>
							<div>
								Height:
							
							</div>	
							<div>
								<input style="width: 90px;" type="text" name="pt_batt_h" id="pt_batt_h" value="">
							</div>
						</div>
					</tr>

				</tbody>
			</table>
		</div>
		<table width="100%">

			<tbody>
				<tr class="fhide">
					<div>
						<input type="checkbox" name="price_h" value="price_h" id="price_h" class="checkturn" data-hide="price_h_hide"></div>
					<div>标题文字</div>
					<div></div>
					<div class="price_h_hide" style="display: none;">
						<a href="#" class="aset attrdetailsi" id="price_n_h"></a>
					</div>
				</tr>
				<tr class="disnone price_h_hide_h">
					<div></div>
					<div colspan="3">
						Text setting
						<div>
							<input type="text" name="aset_f_FS" id="aset_f_FS" class="FS_Auto in50 ui-autocomplete-input form-control" value="Arial" autocomplete="off">	
						</div>
						<div>
							<select name="aset_f_size" id="aset_f_size" class="form-control">
							<option value="20">20</option>
							<option value="21">21</option>
							<option value="22">22</option>
							<option value="23">23</option>
							<option value="24">24</option>
							<option value="25">25</option>
							<option value="26">26</option>
							<option value="27">27</option>
							<option value="28">28</option>
							<option value="29">29</option>
							<option value="30">30</option>
							<option value="31">31</option>
							<option value="32">32</option>
							<option value="33">33</option>
							<option value="34">34</option>
							<option value="35" selected="">35</option>
						</select>
						</div>
						<div style="line-height: 10px;">
							<input type="text" name="aset_f_c" id="aset_f_c" class="pMultiple in40" value="#000000" style="">	
						</div>
						
					</div>
				</tr>
			</tbody>
		</table>
	</form>
</div>

<script type="text/javascript">        
  $(document).ready(
    function()
    {
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
      });
    });
  
</script>