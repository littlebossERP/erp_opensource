<?php
?>
<style type="text/css">
#tool_tab{
	width:100px;
	height: 480px;
}
#branch{
	width: 250px;
	height:600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}

#branch1{
	width: 250px;
	height:600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}

#branch2{
	width: 250px;
	height: 600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}

#T_Mobile{
	width: 250px;
	height: 600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}
#branch4{
	width: 250px;
	height: 600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}
#changeLayout{
	width: 250px;
	height: 600px;
	position: fixed;
	margin-left: 110px;
	margin-top: -510px;
	background-color: #ffffff;
	border: 1px solid #f2f2f2;
}

.toright{
	margin-left: 80px;
	margin-top: 4px;
}
.trtop{
	width: 200px;
}
.yanse{
	width: 150px;
	margin-top: 10px;
	margin-left: 20px;
}
.tinct{
	margin-top: 20px;
	margin-left: 20px;
}
.picture{
	margin-left: 50px;
	width: 100px;

}
.hezi{
	margin-left: 20px;
	text-align: center;
	background-color: #90ee90;
	width:200px;
}
.choose{
	margin-left: 65px;
	margin-top: 10px;
}
.box{
	width:200px;
	margin-padding:20px;
	font-size:14px;
}
.yan1{
	color: #ababab;
}
.yan2{
	color: #ababab;
}
.yan3{
	color: #ababab;
}
.yan4{
	color: #ababab;
}
.yan5{
	color: #ababab;
}
.tab1:hover{
	background-color:#0080ff;
}
.tab2:hover{
	background-color:#0080ff;
}
.tab3:hover{
	background-color:#0080ff;
}
.tab4:hover{
	background-color:#0080ff;
}
.tab5:hover{
	background-color:#0080ff;
}
.tubiao{
	background:#f2f2f2;text-align: center;
}
.bkgd{
	background-color:#0080ff; 
}
.bkgd1{
	background-color:#f2f2f2; 
}
.bkgd2{
	color: #ababab;
}
.bkgd3{
	color: #fff;
}
</style>

<div id="tool_tab" style="cursor: pointer;">
		<ul>
			<li class="tab_layout" style="border-bottom:1px solid #ccc;text-align: center;line-height: 1.5">
				<span class="iconfont icon-gengaimoban" style="font-size:2em;color:#ababab;margin-top:20px"></span>
				<div style=""><span>更改排版</span></div>
			</li>
			<ul style="background:#f2f2f2;">
				<li class="tab1 tubiao" style="line-height: 1.5">
					<div><a><img src="/images/ebay/template/PNG/23.png" alt='' Title='' style="margin-top:10px;"></a></div>
					<div style="margin-top:10px"><span class="yan1">总体设计</span></div>
				</li>
				<li class="tab2 tubiao" style="line-height: 1.5">
					<div><a><img src="/images/ebay/template/PNG/24.png" alt='Basic' Title='Basic' style="margin-top:10px;"></a></div>
					<div style="margin-top:10px"><span class="yan2">侧边设计</span></div>
				</li>
				<li class="tab3 tubiao" style="line-height: 1.5">
					<div><a><img src="/images/ebay/template/PNG/25.png" alt='Basic' Title='Basic' style="margin-top:10px;"></a></div>
					<div style="margin-top:10px"><span class="yan3">产品设计</span></div>
				</li>
				<li class="tab4 tubiao" style="line-height: 1.5">
					<div class="iconfont icon-shouji bkgd2" style="font-size:2em"></div>
					<div style=""><span class="yan4">手机设计</span></div>
				</li>
				<li class="tab5 tubiao" style="line-height: 1.5">
					<div class="iconfont icon-shezhi bkgd2" style="font-size:2em;"></div>
					<div style=""><span class="yan5">设  置</span></div>
				</li>
			</ul>
		</ul>
</div>
<div id="dialogflash" title="Basic modal dialog">
<div id="flash"></div>
</div>
<div id='butset'>
<?php if(!empty($_REQUEST['template_id'])):?>
	<div class="save" style="margin-top:-70px;margin-left:-6px;" href='#' title='Save'>
	<button type="button" class="btn btn-success" style="width: 100px;" alt='save'>保存</button>
	</div> 
<?php endif;?>
	<div style="margin-top:-70px;margin-left:-6px;" href='#' title='Save As'>
	<button type="button" class=" saveas btn btn-success" style="width: 100px;" alt='save_as'>另存为</button>
	</div> 
<?php if(!empty($_REQUEST['template_id'])):?>
	<div id="del" href='#' style="margin-top:10px;margin-left:-6px;" title='Delete Template'>
		<button type="button" class="btn btn-success" style="width: 100px;" alt='Delete'>退出</button>
	</div>

<?php endif;?> 
	<div id="" class='showall' style="margin-top:10px;margin-left:-6px;" href='#' title='Load Sample Item to Preview'>
	<button type="button" class="btn btn-success" style="width: 100px;" alt='save_as'>图片上传预览</button>
	</div>
</div>
		
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
<div id="branch" style="display:none;overflow-y:auto;">
		<table>
			<div>
				<label style="margin-left: 220px;margin-top:10px;"><span id="guanbi" class="iconfont icon-guanbi"></span></label>
			</div>
			<div style="margin-top:30px">
				<tr>
					<td style="width:150px;">
						<label><p style="margin-left: 20px;margin-top: 10px;">背景</p></label>
					</td>
					<td>
						<p style="margin-left:55px;"><span class="iconfont icon-bangzhu" style="color:green" title="背景&#10在本部分中,卖方可以选择的颜色和风格的背景和外部边缘模板."></span></p>
					</td>
				</tr>
			</div>
			<table style="background:#eee;margin-left:20px;">
				<tr>
					<td>
						<p class="tinct">颜色</p>
					</td>
					</tr>
					<tr>
						<td>
							<select id="tb_eb_CFI_style_master_BP" name="tb_eb_CFI_style_master_BP" style="width:150px" class="yanse">
								<option value="Color" selected="selected">颜色</option>
								<option value="Pattern">图案</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=3>
							<div id="bg_P" style="display: none;">
								<input type="text" style="margin-left: 10px;" class="yanse"
										name="tb_eb_CFI_style_master_background_Pattern"
										id="tb_eb_CFI_style_master_background_Pattern"
										value="<?php echo \Yii::$app->			
								urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png" />
								<a style="color: black;" name="backgroundPattern" class="iconfont icon-wenjianjia tip"></a>
								<div class="backgroundPattern tooltips tipdiv">
									<table>
										<tr>
											<td>
												<table>
													<tr>
														<td>
														<form target='iframe_upload' class='dropzone smimg'
															name='subbody' title='tb_eb_CFI_style_master_background_Pattern' id='Body_up'
															method='POST' enctype='multipart/form-data'
															dz-message='Drop files here to upload'
															style=''>
														<div class='fallback'><input type='file' multiple='multiple'
															name='file' /><br />
														<input type='submit' name='action' value='Upload Now!' /><br />
														<div id='gallery_uploads'></div>
														</div>
														<div>
															<label>本地上传</label>
														</div>
														</form>
														</td>
													</tr>
													<tr>
														<td style='width: 200px;'>
															<label style='width: 260px;'>
																<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
														checked>Background Pattern style 1
																<a
														style='background-image: url("<?php echo \Yii::$app->
																	urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
																</a>
															</label>
														</td>
													</tr>

													<tr>
														<td style='width: 200px;'>
															<label style='width: 260px;'>
																<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
													Pattern style 2
																<a
														style='background-image: url("<?php echo \Yii::$app->
																	urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
																</a>
															</label>
														</td>
													</tr>
<!--  -->
													 <!-- <tr>			
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
													Pattern style 3
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 100px; height: 25px; display: inline-block;'>
															</a>
														</label>
													</td>
												</tr> -->
												
												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
													Pattern style 4
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
													Pattern style 5
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
													Pattern style 6
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
													Pattern style 7
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
													Pattern style 8
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
													Pattern style 9
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>

												<tr>
													<td style='width: 200px;'>
														<label style='width: 260px;'>
															<input
														type='radio' class='font8'
														name='tb_eb_CFI_style_master_background_Pattern_r'
														value='<?php echo \Yii::$app->			
															urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
													Pattern style 10
															<a
														style='background-image: url("<?php echo \Yii::$app->
																urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 200px; height: 25px; display: inline-block;margin-top: 10px;'>
															</a>
														</label>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bg_C" class="trtop">
							<input class='font8 Multiple'
										type="text" name="eb_tp_clr_Mainbody_background" value="#ffffff" />			
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="tinct">边界</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="tb_eb_CFI_style_master_body_border_style" id="tb_eb_CFI_style_master_body_border_style" style="width:150px" class="yanse">
							<option value='none'>none</option>
							<option value='solid' selected>solid</option>
							<option value='dashed'>dashed</option>
							<option value='dotted'>dotted</option>
							<option value='double'>double</option>
							<option value='groove'>groove</option>
							<option value='ridge'>ridge</option>
							<option value='inset'>inset</option>
							<option value='outset'>outset</option>
						</select>
					</td>
				</tr>
				<tr>
					<td style='width: 40px;'>
						<select
								name='tb_eb_CFI_style_master_body_size'
								id="tb_eb_CFI_style_master_body_size" class="yanse">
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
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bg_C" class="trtop">
							<input class='font8 Multiple'
										type="text" name="eb_tp_clr_Mainbody_border" value="#666666" />			
						</div>
					</td>
				</tr>
			</table>
		</table>
		
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
				
		<table>
			<tr>
				<td>
					<label><p style="margin-left: 20px;margin-top: 10px;width:180px;">模板顶部</p></label>
				</td>
				<td>
					<p><span class="iconfont icon-bangzhu"  style="color:green" title="台头部分&#10备注：通告栏是用于卖方通知买方对于一些特殊的事件或问题，假日店：如，黄金推广"></span></p>
				</td>
			</tr>
			<table style="background:#eee;margin-left:20px;width:200px">
				<tr>
					<td>
						<p class="tinct">商店名称</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="tb_shop_name_On" id="tb_shop_name_On"  class="yanse">
							<option value="Yes" selected="selected">开</option>
							<option value="No">关</option>
						</select>
						<span class="iconfont icon-tanchushezhi detail" name='SNBanner'></span>
					</td>
				</tr>
				<tr>
					<td>
						<p class="tinct">目录栏</p>
					</td>
					
				</tr>
				<tr>
					<td>
						<select id="tb_shop_master_Setting_menu_On" name="tb_shop_master_Setting_menu_On" class="yanse">
							<option value="Yes" selected="selected">开</option>
							<option value="No">关</option>
						</select>
						<span class="iconfont icon-tanchushezhi detail" name="MenuD"></span>
					</td>
				</tr>
				<tr>
					<td>
						<p class="tinct">通告横幅</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="Notice_Banner_ONNOFF" id="nbynn" style="margin-bottom:10px;" class="yanse">
						<option value="No" selected=""selected>关</option>
						<option value="Yes">开</option>
					</select>
						<span class="iconfont icon-tanchushezhi detail" name="NBanner" style="display: none;"></span>
					</td>
				</tr>
			</table>
			
		</table>
		
						<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button  class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff; " alt='save_as'>保存</button>
					</div> 
					
		<table class="moban">
			<tr>
				<td>
					<label><p style="margin-left: 20px;margin-top: 20px;margin-bottom:20px;">最底政策内容部分</p></label>
				</td>
				<td>
					<p><span class="iconfont icon-bangzhu" style="color:green;margin-left:70px;" title="政策内容&#10卖家可以在不同的模板选择不同的政策内容。因此，政策（如退货政策，航运政策等）可根据不同的产品的需要编辑不同内容的政策和模板（如退货政策的数码产品可能不同服装）"></span></p>
				</td>
			</tr>
			<table style="background:#eee;margin-left:20px;margin-bottom:20px;">
				<tr>
					<td>
						<p class="tinct">背景</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="tb_eb_CFI_style_policy_BP" id="tb_eb_CFI_style_policy_BP" class="yanse">
						<option value="Pattern">图案</option>
						<option value="Color" selected="selected">颜色</option>
					</select>
					</td>
					
				</tr>
					<tr>
					<td>
						<div id="Pg_C" class="trtop"><input class='font8 Multiple'
										type="text" name="eb_tp_clr_infobox_background" value="#ffffff" />
						</div>
					</td>
				</tr>
				<tr id="Pg_P" class="" style="display: none;">
									<td colspan=3><input type='text' style="margin-left:5px;width:180px;margin-top: 10px;margin-right: -20px;" name='eb_tp_policy_Pattern'
										id='eb_tp_policy_Pattern' class='in100 font8 infodetclass'
										value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png' />&nbsp;<a style="color: black;" 
										name='eb_tp_policy_Pattern_u' class='iconfont icon-wenjianjia tip'></a>
									<div class='eb_tp_policy_Pattern_u tooltips tipdiv' style="width:300px;">
									<div class='navbar'>
									<table>
										<tr>
											<td>
											<table>
												<tr>
													<td>
													<form target='iframe_upload' class='dropzone smimg'
														name='' title='eb_tp_policy_Pattern' id='policy_up'
														method='POST' enctype='multipart/form-data'
														dz-message='Drop files here to upload'
														style=''>
													<div class='fallback'><input type='file' multiple='multiple'
														name='file' /><br />
													<input type='submit' name='action' value='Upload Now!' /><br />
													<div id='gallery_uploads'></div>
													</div>
													<div>
														<label>本地上传</label>
													</div>
													</form>
													</td>
												</tr>
												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'
														checked>Background Pattern style 1 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
													Pattern style 2 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
													Pattern style 3 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
													Pattern style 4 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
													Pattern style 5 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
													Pattern style 6 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
													Pattern style 7 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
													Pattern style 8 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
													Pattern style 9 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
												</tr>

												<tr>
													<td style='width: 200px;'><label style='width: 260px;'> <input
														type='radio' name='pattern_r' class='upimg_r'
														value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
													Pattern style 10 <a
														style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 200px; height: 25px; display: inline-block;'></a>
													</label></td>
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
					<td>
						<p class="tinct">边界</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="eb_tp_clr_infobox_border_style" id="eb_tp_clr_infobox_border_style" class="yanse">
						<option value='none'>none</option>
											<option value='solid' selected>solid</option>
											<option value='dashed'>dashed</option>
											<option value='dotted'>dotted</option>
											<option value='double'>double</option>
											<option value='groove'>groove</option>
											<option value='ridge'>ridge</option>
											<option value='inset'>inset</option>
											<option value='outset'>outset</option>
					</select>
					</td>
				</tr>
				<tr>
					<td style='width: 40px;' class="Policy_B_hide"><select
								name='eb_tp_clr_infobox_border_size'
								id="eb_tp_clr_infobox_border_size" class="yanse">
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
				</tr>
				<tr>
						<td class="Policy_B_hide">
							<div><input class='font8 Multiple'
											type="text" name="eb_tp_clr_infobox_border" value="#ffffff" />
							</div>
						</td>
				</tr>
					<td>
						<p class="tinct">政策标题</p>
					</td>
				</tr>
				<tr>
					<td>
						<button type="button" class="detail" style="margin-left:20px !important;margin-bottom:20px;width:150px" name="tabtool">编辑设置</button>
					</td>
				</tr>
			</table>
		</table>
		
						<div  style="margin-top:-10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border:1px solid;line-height: 0.5;background-color:#fff; " alt='save_as'>保存</button>
					</div> 
					
</div>
<div id="branch1" style="display:none;overflow-y: auto;">
	
		<table class="moban">
			<div>
				<label style="margin-left: 220px;margin-top:10px;"><span id="guanbi1" class="iconfont icon-guanbi"></span></label>
			</div>
			<tr>
				<td style="width:150px;">
					<label><p style="margin-left: 20px;margin-top: 10px;">背景</p></label>
				</td>
			<!-- 	<td>
					<p style="margin-left:55px;"><span class="iconfont icon-bangzhu" style="color:green"></span></p>
				</td> -->
			</tr>
			<table style="background:#eee;margin-left:20px;">
				<tr>
					<td>
						<p class="tinct">颜色</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="infobox_bkgd_type" id="infobox_bkgd_type" style="width:150px" class="CorP yanse">
						<option value="Color" selected>颜色</option>
						<option value="Pattern">图案</option>
					</select>
					</td>
				</tr>
				<tr>
					<td>
						<div id="infobox_bkgd_type_c" class="trtop"><input class='font8 Multiple'
										type="text" name="infobox_bkgd_color" id="infobox_bkgd_color" value="#ffffff" />
						</div>
						<div id="infobox_bkgd_type_p" style="display:none;">
							<input
								type='text' name='infobox_bkgd_pattern' id='infobox_bkgd_pattern'
								class='infodetclass' value='' style="width:150px;margin-left:10px;"/>&nbsp;<a style="color: black;" 
								name='infobox_bkgd_pattern_u' class='iconfont icon-wenjianjia tip'></a>
								<div class='infobox_bkgd_pattern_u tooltips tipdiv' style="width:250px;">
							<div class='navbar'>
							<table>
								<tr>
									<td>
									<table>
										<tr>
											<td>
											<form target='iframe_upload' class='dropzone smimg'
												name='' title='infobox_bkgd_pattern' id='policy_up'
												method='POST' enctype='multipart/form-data'
												dz-message='Drop files here to upload'
												style='width: 210px;'>
											<div class='fallback'><input type='file' multiple='multiple'
												name='file' /><br />
											<input type='submit' name='action' value='Upload Now!' /><br />
											<div id='gallery_uploads'></div>
											</div>
											<div><label>本地上传</label></div>
											</form>
											</td>
										</tr>
										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png'>Background
											Pattern style 1 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
											Pattern style 2 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png'>Background
											Pattern style 3 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern3.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
											Pattern style 4 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
											Pattern style 5 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
											Pattern style 6 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
											Pattern style 7 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
											Pattern style 8 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
											Pattern style 9 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>

										<tr>
											<td style='width: 200px;'><label style='width: 260px;'> <input
												type='radio' name='infobox_bkgd_pattern_r' class='upimg_r'
												value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
											Pattern style 10 <a
												style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 200px; height: 25px; display: inline-block;'></a>
											</label></td>
										</tr>
									</table>
									</td>
								</tr>
							</table>
							</div>
							<input type='button' name='Go' class='goCancel btn_01 goright'
								value='Confirm' />
						</div>
						</div>
						
					</td>
				</tr>
				<tr>
					<td>
						<p class="tinct">主题设定</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="Theme_id" id="Theme_id" style="width:150px;margin-bottom:20px" class="yanse">
								<option value="Black">Black</option>
								<option value="Blue">Blue</option>
								<option value="Gold">Gold</option>
								<option value="Green" selected="">Green</option>
								<option value="Grey">Grey</option>
								<option value="Magenta">Magenta</option>
								<option value="Orange">Orange</option>
								<option value="Pink">Pink</option>
								<option value="Red">Red</option>
								<option value="YellowGreen">YellowGreen</option>
						</select>
						<span name='infodetail' class="iconfont icon-tanchushezhi detail"></span>
					</td>
				</tr>
			</table>
			
		</table>
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color: #fff;" alt='save_as'>保存</button>
					</div> 
		<table style="margin-top:30px;">
			<tr>
				<td>
					<label><p style="margin-left: 20px;">功能模块</p></label>
				</td>
				<td>
					<p style="margin-left:125px;"><span id="Pre_info" class="iconfont icon-shuaxin Pre_info"></span></p>
				</td>
			</tr>
			<table style="background:#eee;margin-left:20px;margin-bottom:20px;width:200px;">
				<tr>
					<td>
						<p class="tinct">新增功能格</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name='infobox_type_id' id='infobox_type_id' style="width:100px;margin-bottom:20px;" class="yanse">
							<option value="search">搜寻</option>
							<option value="picture">图片</option>
							<option value="text_Link">Text.Link</option>
							<option value="hot_item">Hot Item</option>
							<option value="new_item">New List Item</option>
<!--							<option value="shop_cat">Store Cat.</option>-->
							<!-- <option value="newsletter">Newsletter</option> -->
							<option value="cus_item">Custom Item</option>
							<option value="youtube">Youtube</option>
							<option value="flash">Flash</option>
						</select>
						<button type="button" id="create_info" class="btn btn-success" style="line-height: 0.5">确定</button>
					</td>
				</tr>
			</table>
			
			<tr>
				<td colspan=4>
					<form id='sortablef'>
					<ul id="sortable" style="margin-left:50px;">
						<li class='sort infono0 delinfono0' style="margin-left: -30px;">
						<table class='info7 infono' style="background:#eee;width:200px;margin-bottom:20px">
							<tr class='font8'>
								<td><span class="block">功能框种类:</span></td>
								<td><a class='btn_del' href='#' id='no0'></a></td>
							</tr>
							<tr class="font8">
								<td><input type='text' class='content_type_id'
									name='content_type_id' value='Search' readonly='readonly' /></td>
								
							</tr>
							<tr class='font8'>
								<td><span class="block">功能框标题:</span></td>
								
							</tr>
							<tr class="font8">
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
						<li class='sort infono1 delinfono1' style="margin-left: -30px;">
						<table class='info7 infono' style="background:#eee;width:200px;margin-bottom:20px">
							<tr class='font8'>
								<td><span class="block">功能框种类:</span></td>
								<td><a class='btn_del' href='#' id='no1'></a></td>
							</tr>
							<tr class='font8'>
								<td>
									<input type='text' class='content_type_id'
									name='content_type_id' value='Picture' readonly='readonly' />
								</td>
							</tr>
							<tr class='font8'>
								<td><span class="block">功能框标题:</span></td>
								
							</tr>
							<tr class="font8">
								<td><input type='text' class='content_text in110'
									name='content_text' value='Picture' />	
								</td>
								<td><span class='pic_details iconfont icon-tanchushezhi' title='Edit Detail' id='content1'
									href='#'></span></td>
							</tr>
							<td class='disno'>
								<input type='number' class='content_displayorder' name='content_displayorder' value='1' />
								<input type='text' class='content_target_display_mode' name='content_target_display_mode' value='' />
								<input type='text' class='infobox_content' name='infobox_content' name='infobox_content' id='addcontent1' value='["<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl.'/images/ebay/template/sidebarPics/csm10.png';?>::"]' />
							</td>
							</tr>
						</table>
						</li>
						<li class='sort infono2 delinfono2' style="margin-left: -30px;">
						<table class='info7 infono' style="background:#eee;width:200px;margin-bottom:20px">
							<tr class='font8'>
								<td><span class="block">功能框种类:</span></td>
								
								<td><a class='btn_del' href='#' id='no2'></a></td>
							</tr>
							<tr class="font8">
								<td><input type='text' class='content_type_id'
									name='content_type_id' value='Hot Item' readonly='readonly' /></td>
							</tr>
							<tr class='font8'>
								<td><span class="block">功能框标题:</span></td>
								
							</tr>
							<tr class="font8">
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
						<li class='sort infono4 delinfono4' style="margin-left: -30px;">
						<table class='info7 infono' style="background:#eee;width:200px;margin-bottom:20px;">
							<tr class='font8'>
								<td><span class="block">功能框种类:</span></td>
								
								<td><a class='btn_del' href='#' id='no4'></a></td>
							</tr>
							<tr class="font8">
								<td><input type='text' class='content_type_id'
									name='content_type_id' value='Text.Link' readonly='readonly' /></td>
							</tr>
							<tr class='font8'>
								<td><span class="block">功能框标题:</span></td>
								
							</tr>
							<tr class="font8">
								<td><input type='text' class='content_text in110'
									name='content_text' value='Text.Link' /></td>
								<td><span class="text_details iconfont icon-tanchushezhi" title='Edit Detail' id='content4' href='#'></span></td>
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
				</td>
			</tr>
			
			
		</table>
		
					<div  style="margin-top:-10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
		<div>
			<label class="hezi">拉动盒子以改动在工具列的显示次序</label>
		</div>

</div>
<div id="branch2" style="display:none;overflow-y: auto;">
	<table>
			<div>
				<label style="margin-left: 220px;margin-top:10px;"><span id="guanbi2" class="iconfont icon-guanbi"></span></label>
			</div>
			<tr>
				<td style="padding-left: 8px;">
					<label><p style="margin-top:30px;margin-left:20px">产品描述部分</p></label>
				</td>
				<td><p style="margin-left:90px;"><span class="iconfont icon-bangzhu" style="color:green" title='产品描述:&#10描述部分包括产品名称，产品描述。标题和描述的实际内容是由会根据每个选用的数据表。但卖家可以在此设计模板颜色，字体样式，点的形式，内容间距。&#10如卖家要显示此部分的模型编号及该项目的大小（在数据表中设定），他们只需要在数据表的产品描述上模型和大小字段中键入一个标籤。'></span></p></td>
			</tr>
			<table style="background:#eee;margin-left:20px;">
				<tr>
					<td>
						<p class="tinct">标题</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name="Title_ONNOFF" id="Title_ONNOFF" style="width:150px" class="yanse">
						<option value="ON" selected="selected">ON</option>
								<option value="OFF">OFF</option>
					</select>
					</td>
				</tr>
				<tr>
					<td>
						<input type="text" id="eb_tp_font_Title"
								name="eb_tp_font_Title" class="FS_Auto yanse" value="Impact" />
				
					</td>
				</tr>
				<tr>
					<td>
						<select name='eb_tp_Title_Size' id="eb_tp_Title_Size" class="yanse">
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
					</select>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bg_C" class="trtop"><input class='font8 Multiple'
										type="text" name="eb_tp_clr_Title" value="#4c4c4c" />
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="tinct">标题</p>
					</td>
				</tr>
				<tr>
					<td>
						<input type="text" id="eb_tp_font_Description"
								name="eb_tp_font_Description" class="FS_Auto yanse"
								value="Arial" />
					</td>
				</tr>
				<tr>
					<td>
						<select name="tb_eb_CFI_style_master_desc_fontSize" id="DescriptionsfontSize" class="yanse">
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
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bg_C" class="trtop"><input class='font8 Multiple'
										type="text" name="eb_tp_clr_Description_details" value="#4c4c4c" />
						</div>
					</td>
				</tr>
			</table>
			
		</table>
				<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
		<table style="margin-top: 30px;">
			<tr>
				<td>
					<label><p style="margin-left: 20px;">功能模块</p></label>
				</td>
				<td>
					<p style="margin-left:125px;"><span id="D_Pre_info" class="iconfont icon-shuaxin D_Pre_info"></span></p>
				</td>
			</tr>
			<table style="background:#eee;margin-left:20px;margin-bottom:20px;width:200px;">
				<tr>
					<td>
						<p class="tinct">新增产品描述方格</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name='d_infobox_type_id' id='d_infobox_type_id' class="yanse" style="margin-bottom:20px;width:100px;">
							<option value="basic_descriptions">Basic Descriptions</option>
<!--							<option value="item_specifics">Item Specifics</option>-->
						</select>
						<button type="button" id="Desc_info" class="btn btn-success" style="line-height: 0.5;">确定</button>
					</td>
				</tr>
			</table>
			
			<tr>
				<td colspan=4>
					<form id="dsortablef">
						<ul id="dsortable" style="margin-left:50px;">
							<li class='dsort infono1 delinfodno1' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block in110">功能框种类:</span></td>
									
									<td><a class='btn_del' href='#' id='dno1'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_type_id in110'
										name='d_content_type_id' value='Basic Descriptions'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
									
									
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_text in110' name='d_content_text'
										value='Basic Descriptions' /></td>
									<td>
										<span id='content1' name='d_desc_details' class="iconfont icon-tanchushezhi d_desc_details detail"></span>
									</td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框位置:</span></td>
								</tr>
								<tr class="font8">
										<td>
											<select class='d_content_pos in110' name='d_content_pos'>
												<option value='above_product_Photo' >Above Product Photo</option>
												<option value='Next_to_Product_Photo' selected >Next to Product Photo</option>
												<option value='Below_All_Product_Photo' >Below All Product Photo</option>
											</select>
										</td>
								</tr>
								<tr class='font8' style=''>
									<td><span class="block in110">横幅宽度:</span></td>
									
								</tr>
								<tr class="font8">
									<td>
										<select class='d_content_width r80 in110' name='d_content_width' style="width:180px;margin-left:10px">
											<option value='full' >Full Width</option>
											<option value='1/2' selected >1/2 width</option>
											<option value='1/3'  >1/3 width</option>
											<option value='2/3'  >2/3 width</option>
											<option value='1/4'  >1/4 width</option>
											<option value='2/4'  >2/4 width</option>
											<option value='3/4' >3/4 width</option>
										</select>
									</td>
								</tr>
								<tr class="font8">
									<td><select class='d_content_align in110' name='d_content_align' style="width:180px;margin-left:10px;margin-top:-1px;">
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

							<li class='dsort infono2 delinfodno2' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block">功能框种类:</span></td>
									
									<td><a class='btn_del' href='#' id='dno2'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_type_id in110'
										name='d_content_type_id' value='Item Specifics'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_text in110' name='d_content_text'
										value='Item Specifics' /></td>
									<td>
										<span id='content2' name='d_item_details' class="iconfont icon-tanchushezhi d_item_details detail"></span>
									</td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框位置:</span></td>
								</tr>
								<tr class="font8">
										<td>
											<select class='d_content_pos in110' name='d_content_pos'>
												<option value='above_product_Photo' >Above Product Photo</option>
												<option value='Next_to_Product_Photo' >Next to Product Photo</option>
												<option value='Below_All_Product_Photo' selected>Below All Product Photo</option>
											</select>
										</td>
								</tr>
								<tr class='font8' style=''>
									<td><span class="block">横幅宽度:</span></td>
									
								</tr>
								<tr class="font8">
									<td>
										<select class='d_content_width r80 in110' name='d_content_width' style="width:180px;margin-left:10px">
											<option value='full' selected>Full Width</option>
											<option value='1/2'>1/2 width</option>
											<option value='1/3'>1/3 width</option>
											<option value='2/3'>2/3 width</option>
											<option value='1/4'>1/4 width</option>
											<option value='2/4'>2/4 width</option>
											<option value='3/4'>3/4 width</option>
										</select>
									</td>
								</tr>
								<tr class="font8">
									<td><select class='d_content_align in110' name='d_content_align' style="width:180px;margin-left:10px;margin-top: -1">
										<option value='left' selected >左边</option>
										<option value='center'  >置中</option>
										<option value='right'  >右边</option>
									</select></td>
								</tr>
								<tr class='font8'>
									<td class='disno'>
									<input type='number' class='d_content_displayorder' name='d_content_displayorder' value='2' />
									<input type='text' class='d_infobox_content' name='d_infobox_content' id='d_addcontent2' value="" />
									<input type='text' class='d_infobox_en_key' name='d_infobox_en_key' value=''></td>
									</td>
								</tr>
							</table>
							</li>

							<!-- <li class='dsort infono3 delinfodno3'>
							<table class='info7 infono'>
								<tr class='font8'>
									<td>功能框种类:</td>
									
									<td><a class='btn_del' href='#' id='dno3'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_type_id'
										name='d_content_type_id' value='Action button'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td>功能框标题:</td>
									
									<td><a class='D_call_for_action' name="D_call_for_action" 
										title='Edit Detail' id='content3' href='#'><img
										src='/images/ebay/template/edit_property.png' alt='create_info'
										height='16' height='16'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_text' name='d_content_text'
										value='Action button' /></td>
								</tr>
								<tr class='font8'>
									<td>功能框位置:</td>
								</tr>
								<tr class="font8">
										<td><select class='d_content_pos' name='d_content_pos'>
										<option value='above_product_Photo' >Above Product Photo</option>
										<option value='Next_to_Product_Photo' >Next to Product Photo</option>
										<option value='Below_All_Product_Photo' selected="0">Below All Product Photo</option>
									</select></td>
								</tr>
								<tr class='font8' style=''>
									<td>横幅宽度:</td>
									
								</tr>
								<tr class="font8">
									<td>
										<select class='d_content_width r80' name='d_content_width' style="width:120px;">
											<option value='full' >Full Width</option>
											<option value='1/2' selected >1/2 width</option>
											<option value='1/3'  >1/3 width</option>
											<option value='2/3'  >2/3 width</option>
											<option value='1/4'  >1/4 width</option>
											<option value='2/4'  >2/4 width</option>
											<option value='3/4' >3/4 width</option>
										</select>
									</td>
								</tr>
								<tr class="font8">
									<td><select class='d_content_align' name='d_content_align' style="width:120px;">
										<option value='left' selected >左边</option>
										<option value='center'  >置中</option>
										<option value='right'  >右边</option>
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

							<li class='dsort infono4 delinfodno4' style="display:none;">
							<table class='info7 infono'>
								<tr class='font8'>
									<td>功能框种类:</td>
									
									<td><a class='btn_del' href='#' id='dno4'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_type_id'
										name='d_content_type_id' value='HTML'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td>功能框标题:</td>
									
									<td><a class='d_html' name="d_html" 
										title='Edit Detail' id='content4' href='#'><img
										src='/images/ebay/template/edit_property.png' alt='create_info'
										height='16' height='16'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='d_content_text' name='d_content_text'
										value='HTML' /></td>
								</tr>
								<tr class='font8'>
									<td>功能框位置:</td>
								</tr>
								<tr class="font8">
										<td><select class='d_content_pos' name='d_content_pos'>
										<option value='above_product_Photo' >Above Product Photo</option>
										<option value='Next_to_Product_Photo' >Next to Product Photo</option>
										<option value='Below_All_Product_Photo' selected="0">Below All Product Photo</option>
									</select></td>
								</tr>
								<tr class='font8' style=''>
									<td>横幅宽度:</td>
									
								</tr>
								<tr class="font8">
									<td>
										<select class='d_content_width r80' name='d_content_width'>
											<option value='full' >Full Width</option>
											<option value='1/2' selected >1/2 width</option>
											<option value='1/3'  >1/3 width</option>
											<option value='2/3'  >2/3 width</option>
											<option value='1/4'  >1/4 width</option>
											<option value='2/4'  >2/4 width</option>
											<option value='3/4' >3/4 width</option>
										</select>
									</td>
								</tr>
								<tr class="font8">
									<td><select class='d_content_align' name='d_content_align'>
										<option value='left' selected >左边</option>
										<option value='center'  >置中</option>
										<option value='right'  >右边</option>
									</select></td>
								</tr>
								<tr class='font8'>
									<td class='disno'><input type='number'
								class='d_content_displayorder' name='d_content_displayorder'
								value='4' /><input type='text' class='d_infobox_content'
								name='d_infobox_content' id='d_addcontent4'
								value="Action_button_style=vert&aset_f_FS=Arial&aset_f_size=35&aset_f_c=ff0000&bla_h=bla_h&pt_aabl=Place+Bid&attr_aabl_c=&pt_aabl_f_FS=Arial&pt_aabl_f_size=18&pt_aabl_f_c=ffffff&fp_buy=Buy+Now&radioattr_buy=pic&attr_buy_c=&buy_f_FS=Arial&buy_f_size=18&buy_f_c=ffffff&algtci=algtci&tfl=Don't+want+to+wait.&fpabl=Direct+Buy+&radioattr_fpabl=pic&attr_fpabl_c=&fpabl_f_FS=Arial&fpabl_f_size=18&fpabl_f_c=ffffff&Action_button_style_f=none&Action_button_size_f=1&Action_button_color_f=" /><input
								type='text' class='d_infobox_en_key' name='d_infobox_en_key'
								value=''></td>
								</tr>
							</table>
							</li> -->
						</ul>
					</form>
				</td>
			</tr>
		</table>
					<div  style="margin-top:-10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
		<div>
			<label class="hezi">拉动盒子以改动在工具列的显示次序</label>
		</div>
</div>
<div id="T_Mobile" style="display:none;overflow-y: auto;" class='P_height'>
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
		
			<!-- <tr>
				<td>
					<span>手机模板</span>
				</td>
				<td>
				
					<span style="">开启</span>
						<input name="abc" class="iosSwitch" type="checkbox" value="1" style="width:60px;">
					<span>关闭</span>
				</td>
			</tr> -->
			<div>
				<label style="margin-left: 220px;margin-top:10px;"><span id="guanbi3" class="iconfont icon-guanbi"></span></label>
			</div>
			<table>	
				<tr>
					<td style="padding-left: 8px;">
						<label><p style="margin-left: 20px;margin-top: 10px;width:180px;">功能模块</p></label>	
					</td>
					<td>
						<span id="m_Pre_info" class="iconfont icon-shuaxin m_Pre_info"></span>
					</td>
							
				</tr>
			</table>
			
		
		
			<table style="background:#eee;margin-left:20px;margin-bottom:20px;width:200px;">
				<tr>
					<td>
						<p class="tinct">新增产品描述方格</p>
					</td>
				</tr>
				<tr>
					<td>
						<select name='m_infobox_type_id' id='m_infobox_type_id' class="yanse" style="margin-bottom:20px;width:100px;">
							<option value="shopname">Shop Name banner</option>
							<option value="mpicture">Hori. Product Photo</option>
							<option value="desc">Description</option>
							<option value="policy">Policy</option>
						</select>
						<button type="button" id="Mobile_info" class="btn btn-success" style="line-height: 0.5">确定</button>
					</td>
				</tr>
			</table>
			
			<tr>
				<td>
					<form id="msortablef">
						<ul id="msortable" style="margin-left:50px;">
													
							<li class='msort infono1 delinfomobileno1' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block">功能框种类:</span></td>
									<td><a class='btn_del' href='#' id='mobileno1'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_type_id in110'
										name='m_content_type_id' value='Shop Name banner'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
									
								</tr>
								<tr class="font8">
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
									
							<li class='msort infono2 delinfomobileno2' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block">功能框种类:</span></td>
									<td><a class='btn_del' href='#' id='mobileno2'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_type_id in110'
										name='m_content_type_id' value='Hori. Product Photo'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_text in110'
										name='m_content_text' value='Hori. Product Photo' /></td>
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
									
							<li class='msort infono3 delinfomobileno3' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block">功能框种类:</span></td>
									<td><a class='btn_del' href='#' id='mobileno3'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_type_id in110'
										name='m_content_type_id' value='Description'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
								</tr>
								<tr class="font8">
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
									
							<li class='msort infono4 delinfomobileno4' style="background:#eee;margin-left:-30px;width:200px;margin-bottom:20px">
							<table class='info7 infono'>
								<tr class='font8'>
									<td><span class="block">功能框种类:</span></td>
									<td><a class='btn_del' href='#' id='mobileno4'></a></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_type_id in110'
										name='m_content_type_id' value='Policy'
										readonly='readonly' /></td>
								</tr>
								<tr class='font8'>
									<td><span class="block">功能框标题:</span></td>
								</tr>
								<tr class="font8">
									<td><input type='text' class='m_content_text in110'
										name='m_content_text' value='Policy' /></td>
								</tr>
								<tr>
								<td class='disno'>
									<input type='number' class='m_content_displayorder' name='m_content_displayorder' value='4' />
									<input type='text' class='m_content_target_display_mode' name='m_content_target_display_mode' value='' />
									<input type='text' class='m_infobox_content' name='m_infobox_content' id='m_addm_content4' value='' />
									<input type='text' class='m_infobox_en_key' name='m_infobox_en_key' value=''>
								</td>
								</tr>
							</table>
							</li>
						</ul>
					</form>
				</td>
			</tr>
		</table>
					<div  style="margin-top:-10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 25px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
		<div>
			<label class="hezi">拉动盒子以改动在工具列的显示次序</label>
		</div>
	</table>
</div>
<div id="branch4" style="display:none;overflow-y: auto;">
	<table>
		<div>
				<label style="margin-left: 220px;margin-top:10px;"><span id="guanbi4" class="iconfont icon-guanbi"></span></label>
			</div>
		<tr>
			<td>
				<div style="float: left;margin-top: 20px;margin-left: 20px;">
					URL文件设定
				</div>
			</td>
		</tr>
		<table style='background:#eee;margin-left:30px;width:200px;margin-bottom:20px'>
			<tr>
				<td>
					<p class="tinct">图片</p>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="tinct" name="graphic_setting_photo_base_path" id='BasedPath' placeholder="[http://www.example.com/images/]">
				</td>
			</tr>
			<tr>
				<td>
					<p class="tinct">G图文件夹/URL</p>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="tinct" name="graphic_setting_gallery_path" id='Gallery' placeholder="[gallery/]">
				</td>
			</tr>
			<tr>
				<td>
					<p class="tinct">货品图片文件夹/URL</p>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="tinct" name="graphic_setting_product_path" id='Product' placeholder="[Product/]">
				</td>
			</tr>
			<tr>
				<td>
					<p class="tinct">横幅图片文件夹/URL</p>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="tinct" name="graphic_setting_poster_path" id='Poster' placeholder="[Poster/]">
				</td>
			</tr>
			<tr>
				<td>
					<p class="tinct">管理图片文件夹/URL</p>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="tinct" name="graphic_setting_admin_path" id='Admin' placeholder="[Admin/]" style="margin-bottom:20px;">
				</td>
			</tr>
		</table>
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 35px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
			<tr>
				<td>
					<div style="float: left;margin-top: 20px;margin-left: 30px;">
						主要设定
					</div>
				</td>
			</tr>
			<table style='background:#eee;margin-left:30px;width:200px;margin-bottom:20px'>
			<tr>
				<td><p class="tinct">显示产品图片</p></td>
			</tr>
			<tr>
				<td>
					<select name='product_photo_ONNOFF' id='product_photo_ONNOFF' class="tinct" style="width:180px;">
						<option value="ON" selected="selected">ON</option>
						<option value="OFF">OFF</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><p class="tinct">在HTML内重新显示G图吗?</p></td>
			</tr>
			<tr>
				<td>
					<select name='tb_eb_CFI_style_master_GPA' class="tinct" style="width:180px;margin-bottom:20px;">
						<option value="Yes" selected="selected">Yes</option>
						<option value="No">No</option>
					</select>
				</td>
			</tr>
		</table>
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 35px;border:1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
			<tr>
				<td>
					<div style="float: left;margin-top: 20px;margin-left: 30px;">
						图片名称主要设定
					</div>
				</td>
			</tr>
			<table style='background:#eee;margin-left:30px;width:200px;margin-bottom:20px'>
			<tr>
				<td><p class="tinct">自动档案名称分隔符</p></td>
			</tr>
			<tr>
				<td>
					<select name="graphic_setting_Auto_file_Separate" class="tinct" style="width:180px;">
						<option value="-" selected="selected">- (Dash sign)</option>
						<option value="_">_ Underscore sign)</option>
						<option value="=">= (Equals sign)</option>
						<option value="">(Not Anything)</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><p class="tinct">自动文件名前加零</p></td>
			</tr>
			<tr>
				<td>
					<select name="graphic_setting_Auto_Leading_Zero"  class="tinct" style="width:180px;">
						<option value="Yes" selected="selected">Yes</option>
						<option value="No">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><p class="tinct">自动文件名格式</p></td>
			</tr>
			<tr>
				<td>
					<select name="graphic_setting_Auto_file_Format" class="tinct" style="width:180px;margin-bottom:20px;">
						<option value="jpg" selected="selected">jpg</option>
						<option value="jpeg">jpeg</option>
						<option value="gif">gif</option>
					</select>
				</td>
			</tr>
		</table>
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 35px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
			<tr>
				<td>
					<div style="float: left;margin-top: 20px;margin-left: 30px;">
						Javascript功能
					</div>
				</td>
			</tr>
			<table style='background:#eee;margin-left:30px;width:200px;margin-bottom:20px'>
				<tr>
					<td>
						<p class="tinct">Javascript功能</p>
					</td>

				</tr>
				<tr>
					<td>
						<select name="javascript_fun" class="tinct" style="width:180px;margin-bottom: 30px;">
							<option value="auto" selected="selected">Auto</option>
							<option value="enable">Enable</option>
							<option value="disable">Disable</option>
						</select>
					</td>
				</tr>
			</table>
					<div  style="margin-top:10px;margin-left:-6px;" href='#' title='Save As'>
					<button class="saveas btn baocun" type="button" style="width: 200px;margin-left: 35px;border: 1px solid;line-height: 0.5;background-color:#fff " alt='save_as'>保存</button>
					</div> 
		
	</table>
</div>

<div id="changeLayout" style="display:none;overflow-y: auto;">
	<table>
		<tr>
			<td>
				<div style="float: left;margin-top: 20px;margin-left: 20px;">
					<label>排版设置</label>
				</div>
			</td>
		</tr>
		<table>
			<tr>
				<td>
					<P class="tinct"><img src="/images/ebay/template/PNG/layout_left.png" style="width:45px;height:54px;"></P>
				</td>
				<td>
					<P  class="tinct"><img src="/images/ebay/template/PNG/layout_right.png" style="width:45px;height:54px;"></P>
				</td>
				<td>
					<p class="tinct"><img src="/images/ebay/template/PNG/layout.png" style="width:45px;height:54px;"></p>
				</td>
			</tr>
			<tr>
				<td><p class="tinct"><input type="radio" name="switchType" value="layout_left" checked>左边</p></td>
				<td><p class="tinct"><input type="radio" name="switchType" value="layout_right">右边</p></td>
				<td><p class="tinct"><input type="radio" name="switchType" value="layout">没有</p></td>
			</tr>
			<tr>
				<td>
					<p class="tinct"><img src="/images/ebay/template/PNG/4.png" style="width:45px;height:54px;"></p>
				</td>
				<td>
					<P class="tinct"><img src="/images/ebay/template/PNG/5.png" style="width:45px;height:54px;"></P>
				</td>
				<td>
					<P  class="tinct"><img src="/images/ebay/template/PNG/8.png" style="width:45px;height:54px;"></P>
				</td>
			</tr>
			<tr>
				<td><p class="tinct"><input type="radio" name="switchProduct" value="product_layout_left" checked>置下小图</p></td>
				<td><p class="tinct"><input type="radio" name="switchProduct" value="product_layout_right">置右小图</p></td>
				<td><p class="tinct"><input type="radio" name="switchProduct" value="product_layout_center">大图</p></td>
			</tr>
		</table>
	</table>
	<div>
		<input id="changeType" type="button" class="btn btn btn-success" style="width:100px;position:absolute;bottom:10px;right:10px;" value="确定">
	</div>
</div>


<div id="SNBanner" style="display: none;">
	<table id='SNBanner_path'> 
			<!-- <tr>
				<td>
					<input type="radio" name="SNBR" class="R_UNL" value="upload" CHECKED>上载文件
				</td>
				<td>
					<input type="radio" name="SNBR" class="R_UNL" value="lin">媒体库
				</td>

			</tr> -->
			<ul class="main-tab">
					<li tab-show="tab" tab-id="tab1" class="active"><a>上载文件</a></li>
					<li tab-show="tab" tab-id="tab2"><a>媒体库</a></li>
					<input type="hidden" name="isActive" tab-value="tab" value="tab1" />
			</ul>
			<table>
				<tr tab-view="tab" tab-id="tab1" class='SNB_upload active' id="SNB_upload">
					<td colspan='2'>
					<form target="iframe_upload" class="dropzone"
						name="graphic_setting_Shop_Name_Banner" title='SNB'
						id="form_upload" method='POST' enctype="multipart/form-data"
						dz-message="Drop files here to upload" style="width: 250px;height:100px;margin-left: 10">
					
					<div class="fallback">
						<input type="file" multiple="multiple" name="file" /><br />
					</div>
					<div id="pictures" class="dropzone-previews" style="width:250px;height:100px;">
						<label style="margin-left:20px;"><p style="margin-left:50px;">点击上传</p>最佳尺寸宽度1080px所有</label>
					</div>
					</form>
					</td>
				</tr>
			</table>
			<table>
				<tr tab-view="tab" tab-id="tab2" class='SNB_url' id="SNB_url">
					<td>
					<table>
						<td align="left">
							<a name="Admin" class="preview" href="#"
							title="graphic_setting_Shop_Name_Banner" id='SNB_P' style="display:none"></a>
						</td>
					</table>
					<div  id="previews" style="width:250px;height:100px;margin-left: 10px;">
						<label style="margin-left:20px;margin-top: 20px"><p style="margin-left:50px;">请选择媒体库图片</p><p style="margin-left: 20px;">最佳尺寸宽度1080px所有</p></label>
					</div>
						<div align="left"><input type="text"
							name="graphic_setting_Shop_Name_Banner" class='new imgborder'
							id="graphic_setting_Shop_Name_Banner" style="margin-top:20px;width: 250px;margin-left: 10px;"
							value="http://1e60194d2ecb9cce3358-6c3816948ff1e081218428d1ffca5b0d.r1.cf4.rackcdn.com/953bba0926fce846ed3064a7ea3ffbf4_plain_color_white_pure.jpg">
						</div>
					</td>
				</tr>
			</table>
				
			<table>
				
				<tr>
					<td>
					<div class='SNB tooltips tipdiv'>
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

					<td class="name font8"> Width:<input type="text"
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
	</table>
	<table>
			<!-- <div class="form-group">
				<input type="text" class="form-control" placeholder="图片url"><button type="button"><span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span></button>
			</div> -->
			<div>
				<label style="margin-left: 10">链接设置</label>
			</div>
			<table style="background: #eee;height:80px;width:250px;margin-left: 10px;">
				<tr>
					<td>
						<label for="exampleInputEmail1" style="margin-left: 20px;margin-top: 10px;">链接</label>
					</td>
				</tr>
				<tr>
					<td>
						<input type="text" style="margin-left: 20px;width:200px;">
					</td>
				</tr>
				 
			    
			</table>
			<div>
				<label class="topsuname" style="margin-left: 10">商店名称</label>
			</div>
			<table style="background: #eee;margin-left: 10px;">
				<tr>
					<td> <label style="margin-left: 20px;margin-top: 10px;">文字</label></td>
				</tr>
				<tr>
					<td> <input type="text" style="margin-left: 20px;width:200px;" class="shop_name" name="shop_name_text" id="shop_name_text"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 20px;margin-top: 10px;">字型</label></td>
				</tr>
				<tr>
					<td><input type="text" id="shop_name_text_style"
							name="shop_name_text_style" class="FS_Auto shop_name"
							value="Impact" style="margin-left: 20px;width:200px;"/></td>
				</tr>
				<tr>
					<td><select style="margin-left: 20px;width:200px;margin-top: 10px;" name="shop_name_text_size" id="shop_name_text_size" class="shop_name">
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
			    </select></td>
				</tr>
				<tr>
					<td>
						<div style="line-height: 8px;"><input class="font8 Multiple shop_name"
										type="text" id="shop_name_text_color" name="shop_name_text_color" value="#4c4c4c" style="width:240px;margin-left:1px;line-height:2;" />
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<label style="margin-left: 20px;margin-top: 10px;">位置</label>
					</td>
				</tr>
				<tr>
					<td>	
							 <input type="number" id="shop_name_text_left" name="shop_name_text_left" class="shop_name" style="width:100px;margin-left: 20px;margin-bottom: 20px;" value="25">x<input type="number" id="shop_name_text_top" name="shop_name_text_top" class="shop_name" value="10" style="width:100px;">
					</td>
				</tr>
			</table>
			<div>
				<label class="topsuname" style="margin-left: 10px;">副店铺标题</label>
			</div>
			<table style="background: #eee;margin-left: 10px;">
				<tr>
					<td>
						 <label style="margin-left: 20px;margin-top: 10px;">文字</label>
					</td>
				</tr>
				<tr>
					<td><input type="text" style="margin-left: 20px;width:200px;" class="shop_name" name="shop_name_sub_text" id="shop_name_sub_text" placeholder="[Your best online business partner]"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 20px;margin-top: 10px;">字型</label></td>
				</tr>
				<tr>
					<td><input type="text" id="shop_name_sub_text_style"
							name="shop_name_sub_text_style" class="FS_Auto shop_name"
							value="Orbitron" style="margin-left: 20px;width:200px;"/></td>
				</tr>
				<tr>
					<td>
						 <select name="shop_name_sub_text_size" id="shop_name_sub_text_size" class="shop_name" style="margin-left: 20px;width:200px;margin-top: 10px;">
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
			    </select>
					</td>
				</tr>
				<tr>
					<td><div style="line-height: 8px;"><input class="font8 Multiple shop_name"
										type="text" id="shop_name_sub_text_color" name="shop_name_sub_text_color" value="#4c4c4c" style="width:240px;margin-left:1px;line-height:2;" />
						</div></td>
				</tr>
				<tr>
					<td><label style="margin-left: 20px;margin-top: 10px;">位置</label></td>
				</tr>
				<tr>
					<td> <input type="number" id="shop_name_sub_text_left" name="shop_name_sub_text_left" class="shop_name" style="width:100px;margin-left: 20px;margin-bottom: 20px" value="25">x<input type="number" id="shop_name_sub_text_top" name="shop_name_sub_text_top" class="shop_name" value="75" style="width:100px;"></td>
				</tr>
			</table>
	</table>
</div>
<div id="NBanner" style="display:none;">
	<table>
		<tr>
			<td>
				<label style="margin-left: 10px;margin-top: 10px;">通告横幅：</label>
			</td>
			<td>
				<input type="text" class="shop_name" value="" id="">
			</td>
		</tr>
	</table>
</div>
<div id="MenuD">
	<table style="background: #eee;width:250px;">
		<tr>
			<td>
				<label style="margin-left: 10px;margin-top: 10px;">文字分隔符</label>
			</td>
			<td>
				<input class="iosSwitch" type="checkbox" name="tb_eb_CFI_style_master_menu_separator" value="Yes" checked style="vertical-align: top;margin-left:20px;" />
			</td>
		</tr>
	</table>
	<table>
			<div>
				<label>目录栏风格</label>
			</div>
			<table style="background: #eee;width:250px;">
				<tr>
					<td><label style="margin-left: 10px;">背景</label></td>
				</tr>
				<tr>
					<td> <input type="text" name='tb_shop_master_Setting_menu_bar' id='tb_shop_master_Setting_menu_bar' class='new inputwidth imgborder infodetclass'
			    style="margin-top: 5px;width:200px;margin-left: 10px;" value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>
			    <a name='tb_shop_master_Setting_menu_bar_u' class='iconfont icon-wenjianjia tip' style="color: black"></a></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">字型</label></td>
				</tr>
				<tr>
					<td> <input type="text" name="eb_tp_font_style_menurow"
					class='new inputwidth FS_Auto' id="FontStyle" value="Arial" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td>
						<select name="tb_eb_CFI_style_master_menu_font_size" id="FontSize" style="margin-left: 10px;width:200px;margin-top: 10px;">
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
				    </select>
					</td>
				</tr>
				<tr style="line-height: 0.8;">
					<td>
						<input type="text" name="eb_tp_clr_Font_Color"
							class="new inputwidth Multiple" id="FontColor" value="#666666" style="width:210px;margin-left: 10px;margin-bottom: 10px;" />
					</td>
				</tr>
			</table>
			<div style="margin-left: 10px;">			   
			    <div class='tb_shop_master_Setting_menu_bar_u tooltips tipdiv' style="width:250px;">
				<div class='navbar'>
				<div>
					<div style="">
							<form target='iframe_upload' class='dropzone smimg' name=''
								title='tb_shop_master_Setting_menu_bar' id='policy_up'
								method='POST' enctype='multipart/form-data'
								dz-message='Drop files here to upload'
								style='width: 210px;'>
							<div class='fallback'><input type='file' multiple='multiple'
								name='file' /><br />
							<input type='submit' name='action' value='Upload Now!' /><br />
							<div id='gallery_uploads'>
							</div>
							</div>
							<div>
								<label style="margin-left: 45px;">本地上传</label>
							</div>
							</form>
					</div>
					<div style="width:200px;float:left;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
							Pattern style 1 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
					<div style="width:200px;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'>Background
							Pattern style 2 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
					<div style="width:200px;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
							Pattern style 3 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
					<div style="width:200px;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
							Pattern style 4 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
					<div style="width:200px;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
							Pattern style 5 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
					<div style="width:200px;">
						<input
								type='radio' name='MENU_r'
								class='upimg_r'
								value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
							Pattern style 6 <a
								style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
					</div>
				
				</div>
				
				</div>
				<input type='button' name='Go' class='goCancel btn_01 goright'
					value='Confirm' />
				</div>
			</div>
			<div>
				<label>目录栏内容</label>
			</div>
			<table style="background: #eee;width:250px;">
				<tr>
					<td><label style="margin-left: 10px;">1-文字</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_DT" id="DESCRIPTION" class="new inputwidth" value="DESCRIPTION" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">1-链接</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_DT_t" id="DESCRIPTION" class="new inputwidth" value="#" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">2-文字</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_PM" id="PAYMENT" class="new inputwidth" value="ss" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">2-链接</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_PM_t" id="DESCRIPTION" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">3-文字</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_SP" id="SHIPPING" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">3-链接</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_SP_t" id="DESCRIPTION" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">4-文字</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_RP" id="RETURN" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">4-链接</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_RP_t" id="DESCRIPTION" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">5-文字</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_CU" id="CONTACT" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
				<tr>
					<td><label style="margin-left: 10px;">5-链接</label></td>
				</tr>
				<tr>
					<td><input type="text" name="tb_shop_master_Setting_menu_CU_t" id="DESCRIPTION" class="new inputwidth" value="ddd" style="margin-left: 10px;width:200px;"></td>
				</tr>
			</table>
	</table>
</div>

<div id="tabtool" class="tabtool tardisplay" style="display: none;">
	<table>
			<div class="disno">
				<input type="text" id="bsh_ch_info_Policy1" value="" /> <input
									type="text" id="bsh_ch_info_Policy2" value="" /> <input
									type="text" id="bsh_ch_info_Policy3" value="" /> <input
									type="text" id="bsh_ch_info_Policy4" value="" /> <input
									type="text" id="bsh_ch_info_Policy5" value="" /> <input
									type="text" id="bsh_ch_info_Policybot" value="" />
			</div>
			<div>
				<label>政策标题风格</label>
			</div>
			<div style="margin-left: 10px;background: #eee;">
				
				<div id="Pg_C">
					<div>
						<label style="margin-left: 10px;margin-top: 10px">已选标题版头</label>
					</div>
					 <div style="line-height:10px;">
					 	<input type="text"  name="eb_tp_tab_Header_selected"
								class="new inputwidth Multiple" value="#7f7f7f" style="width:200px;margin-left:10px;" />
					 </div>
				     <div>
				     	<label style="margin-left: 10px;margin-top: 10px">上页未选/手机上页</label>
				     </div>
					<div style="line-height: 10px;">
						<input type="text" name="eb_tp_tab_Header_color"
								class="Multiple" value="#b2b2b2" style="width:200px;margin-left:10px;"/>
					</div>
					<div>
						<label style="margin-left: 10px;margin-top: 10px">字型</label>
					</div>
					<div>
						<input type="text" id="eb_tp_tab_Font_style"
										name="eb_tp_tab_Font_style" class="FS_Auto" value="Arial" style="margin-left: 10px;width:200px;" />
					</div>
					<div>
						<input type="text" id="eb_tp_tab_Font_size"
										name="eb_tp_tab_Font_size" class="" value="12" style="margin-left: 10px;width:200px;margin-top: 10px;"/>
					</div>
					<div style="line-height: 10px;">
						<input type="text" name="eb_tp_tab_Header_font"
							class="Multiple" value="#ffffff" style="width:200px;margin-left:10px;margin-bottom: 10px;margin-right: 10px;"/>
					</div>
				    
				</div>
			   
			</div>
			<div>
				<label style="margin-top: 20px;">政策标题内容</label>
			</div>
			<div style="margin-left: 10px;background: #eee;">
				<div>
					 <label style="margin-top: 10px;margin-left: 10px;">Policy 1 header</label>
				</div>
				<div>
					 <input type="text" name="sh_ch_info_Policy1_header" id="sh_ch_info_Policy1_header"value="Policy 1 header" style="margin-top: 10px;margin-left: 10px;width: 200px">
				</div>
				<div>
					<label style="margin-top: 10px;margin-left: 10px;" >Policy 2 header</label>
				</div>
				<div>
					<input type="text" name="sh_ch_info_Policy2_header" id="sh_ch_info_Policy2_header" value="Policy 2 header" style="margin-top: 10px;margin-left: 10px;width:200px">
				</div>
				<div>
					<label style="margin-top: 10px;margin-left: 10px;">Policy 3 header</label>
				</div>
				<div>
					<input type="text" name="sh_ch_info_Policy3_header" id="sh_ch_info_Policy3_header" value="Policy 3 header" style="margin-top: 10px;margin-left: 10px;width:200px">
				</div>
				<div>
					<label style="margin-top: 10px;margin-left: 10px;">Policy 4 header</label>
				</div>
				<div>
					<input type="text" name="sh_ch_info_Policy4_header" id="sh_ch_info_Policy4_header" value="Policy 4 header" style="margin-top: 10px;margin-left: 10px;width:200px">
				</div>
				<div>
					<label style="margin-top: 10px;margin-left: 10px;">Policy 5 header</label>
				</div>
				<div>
					<input type="text" name="sh_ch_info_Policy5_header" id="sh_ch_info_Policy5_header"
					style="margin-top: 10px;margin-left: 10px;width:200px;" value="Policy 5 header">
				</div>
			</div>
	</table>
</div>
<div id="infodetail" style="display:none">
	<table>
		<div>
			<label>标题栏</label>
		</div>
		<div style="background: #eee;">
			<label for="exampleInputEmail1" style="margin-left: 10px;">边界</label>


			<div>
				<select name="cat_layoutborder_style" id="cat_layoutborder_style" class="infodetclass" style="margin-left: 10px;width:200px;">
					<option value='none'>none</option>
					<option value='solid' selected>solid</option>
					<option value='dashed'>dashed</option>
					<option value='dotted'>dotted</option>
					<option value='double'>double</option>
					<option value='groove'>groove</option>
					<option value='ridge'>ridge</option>
					<option value='inset'>inset</option>
					<option value='outset'>outset</option>
				</select>
			</div>
			<div>
				<select name='cat_layoutborder_size' id="cat_layoutborder_size" class="infodetclass" style="margin-left: 10px;width:200px;margin-top: 10px;">
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
				</select>
			</div>
			<div style="line-height: 9px;">
				<input class='infodetclass Multiple' type="text" name="cat_layoutborder_color" id="cat_layoutborder_color" value="#dddddd" style="margin-left:10px;width:200px;margin-right: 10px;" />
			</div>
			<div>
				<label style="margin-left: 10px;margin-top: 10px;">背景</label>
			</div>
			<div>
				<select style="margin-left: 10px;width:200px;" name="title_bkgd_type" id="title_bkgd_type" class="CorP infodetclass">
					<option value='Color' selected>Color</option>
					<option value='Pattern'>Pattern</option>
				</select>
			</div>
			<div id='title_bkgd_type_p' style="display: none;">
				<input type='text' name='title_bkgd_pattern' id='title_bkgd_pattern'
					   class=' infodetclass' value='' style="width:200px;margin-left: 10px;margin-top:10px;" />&nbsp;<a name='title_bkgd_pattern_u'
																														class='layout_select tip folder26'></a>
				<div class='title_bkgd_pattern_u tooltips tipdiv'>
					<div class='navbar'>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar3.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar4.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar5.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar6.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar7.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar8.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar9.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='title_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar12.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
					</div>
				</div>
			</div>
			<div id='title_bkgd_type_c' style="line-height:10px;">
				<input type="text"
					   name="title_bkgd_color" class="Multiple infodetclass"
					   id="title_bkgd_color" value="#5bc7d1" style="margin-left:10px;width:200px;margin-right: 10px;" />
			</div>
			<div>
				<label style="margin-left: 10px;margin-top: 10px;">字型</label>
			</div>
			<div>
				<input type="text" name="title_fontStyle"
					   class="FS_Auto infodetclass" id="title_fontStyle"
					   value="Squada One" style="margin-left: 10px;width:200px;" />
			</div>
			<div>
				<select class="infodetclass" name='title_fontSize' id="title_fontSize" style="width:200px;margin-left: 10px;margin-top: 10px;">
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
				</select>
				<div id="bg_C" class="trtop" style="line-height: 9px;width:250px;">
					<input class='infodetclass Multiple' type="text" name="title_fontColor" id="title_fontColor" value="#ffffff" style="margin-left:10px;width:200px;margin-bottom: 20px;margin-right: 10px;"/>
				</div>
			</div>
		</div>
		<div>
			<label>文本/链接格式</label>
		</div>
		<div style="background: #eee;">
			<div>
				<label for="exampleInputEmail1" style="margin-left: 10px;">字体(普通)</label>
			</div>
			<div>
				<input type="text" name="text_fontstyle"
					   class="FS_Auto infodetclass" id="text_fontstyle" value="Arial" style="margin-left: 10px;width:200px;" />
			</div>
			<div>
				<select class="infodetclass" name='text_fontsize' id="text_fontsize" style="margin-left: 10px;width:200px;margin-top: 10px;">
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
				</select>
			</div>

			<div class="trtop" style="line-height: 9px;width:250px;">
				<input class='Multiple infodetclass' type="text" name="text_fontcolor" id="text_fontcolor" value="#776b71" style="margin-left:10px;width:200px;margin-right: 10px;"/>
			</div>
			<div><label style="margin-left: 10px;margin-top: 10px;">移至滑鼠上改动字型</label></div>
			<div class="trtop" style="line-height: 8px;width:250px;">
				<input class='Multiple infodetclass' type="text" name="text_overcolor" id="text_overcolor"  value="#ffffff" style="margin-left:10px;width:200px;margin-bottom: 20px;margin-right: 10px;"/>
			</div>
		</div>
		<div>
			<label>按钮格式</label>
		</div>
		<div style="background: #eee;">
			<div><label for="exampleInputEmail1" style="margin-left: 10px;margin-top: 10px;">标准</label></div>

			<div id="bg_C" class="trtop" style="line-height: 8px;width:250px;">
				<input class='Multiple infodetclass' type="text" name="btn_bkgdcolor" id="btn_bkgdcolor" value="#333333" style="margin-left:10px;width:200px;margin-right: 10px"/>
			</div>
			<div>
				<label style="margin-left: 10px;margin-top: 10px;">移至滑鼠上</label>
			</div>

			<div id="bg_C" class="trtop" style="line-height: 9px;width:250px;">
				<input class='Multiple infodetclass' type="text" name="btn_overcolor" id="btn_overcolor" value="#7f7f7f" style="margin-left:10px;width:200px;margin-right: 10px;"/>
			</div>
			<div><label style="margin-left: 10px;margin-top: 10px;">标准字体</label></div>

			<div>
				<input type="text" name="btn_textstyle"
					   class="FS_Auto  infodetclass" id="btn_textstyle" value="Arial" style="margin-left: 10px;width:200px;" />
			</div>
			<select name='btn_textsize' id="btn_textsize" class="infodetclass" style="margin-left: 10px;width: 200px;margin-top: 10px;">
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
			</select>
			<div id="bg_C" class="trtop" style="line-height: 9px;width:250px;">
				<input class='Multiple infodetclass' type="text" name="btn_textcolor" id="btn_textcolor" value="#ffffff" style="margin-left:10px;width:200px;margin-bottom: 20px;margin-right: 10px;"/>
			</div>
		</div>
		<label>eBay Store目录</label>
		<div style="background: #eee;">
			<div> <label for="exampleInputEmail1" style="margin-left: 10px;margin-top: 10px;">背景</label></div>

			<div>
				<select name="cat_bkgd_type" class='CorP infodetclass'
						id="cat_bkgd_type" style="margin-left: 10px;width:200px;">
					<option value='Color' selected>Color</option>
					<option value='Pattern'>Pattern</option>
				</select>
			</div>
			<div id="cat_bkgd_type_c" class="trtop" style="line-height: 9px;width:250px;">
				<input class='Multiple infodetclass' type="text" id="cat_bkgd_color" name="cat_bkgd_color" value="#ffffff" style="margin-left:10px;width:200px;margin-right: 10px;"/>
			</div>
			<div id='cat_bkgd_type_p' style="display: none;">
				<input
					type='text' name='cat_bkgd_pattern' id='cat_bkgd_pattern'
					class=' infodetclass' style="width:200px;margin-left: 10px;margin-top: 10px;"
					value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png'/>&nbsp;<a
					name='cat_bkgd_pattern_u' class='layout_select tip folder26'></a>
				<div class='cat_bkgd_pattern_u tooltips tipdiv'>
					<div class='navbar'>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
								Pattern style 2 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_bkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
								Pattern style 3 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
					</div>
				</div>
			</div>
			<div>
				<label style="margin-left: 10px;margin-top: 10px;">移至滑鼠上</label>
			</div>
			<div>
				<select name="cat_bkgd_type1" class='mouse infodetclass'
						id="cat_bkgd_type1" style="margin-left: 10px;width:200px;">
					<option value='Color' selected>Color</option>
					<option value='Pattern'>Pattern</option>
				</select>
			</div>
			<div id="cat_bkgd_type_c1" class="trtop" style="line-height: 9px;width:250px;">
				<input class='Multiple infodetclass' type="text" id="cat_overbkgd_color" name="cat_overbkgd_color" value="#ffffff" style="margin-left:10px;width:200px;margin-bottom: 20px;margin-right: 10px;"/>
			</div>
			<div id='cat_bkgd_type1_p1' style="display: none;">
				<input
					type='text' name='cat_overbkgd_pattern' id='cat_overbkgd_pattern'
					class=' infodetclass' style="width:200px;margin-left: 10px;margin-top: 10px;"
					value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar2.png' />&nbsp;<a
					name='cat_overbkgd_pattern_u' class='layout_select tip folder26'></a>
				<div class='cat_overbkgd_pattern_u tooltips tipdiv'>
					<div class='navbar'>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_overbkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png'>Background
								Pattern style 1 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar1.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_overbkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png'>Background
								Pattern style 2 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar10.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
						<div style='width: 200px;'>
							<label style='width: 260px;'> <input
									type='radio' name='cat_overbkgd_pattern' class='upimg_r'
									value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png'>Background
								Pattern style 3 <a
									style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/webbar11.png"); width: 200px; height: 25px; display: inline-block;'></a>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	</table>
</div>
<div id="dialog_upload" style="display: none;overflow-y: auto;">
	<label>本地上传</label>
	<div style="margin-left: 10px;">
		<label>媒体库</label>
		<div>
				<td><input type="radio" name="back-picture"></td>
				<td>背景图片方案一</td>
		</div>
		<div><img src="" alt="111"></div>
		<div>
				<td><input type="radio" name="back-picture"></td>
				<td>背景图片方案二</td>
		</div>
		<div><img src="" alt="111"></div>
		<div>
				<td><input type="radio" name="back-picture"></td>
				<td>背景图片方案三</td>
		</div>
		<div><img src="" alt="111"></div>
		<div>
				<td><input type="radio" name="back-picture"></td>
				<td>背景图片方案四</td>
		</div>
		<div><img src="" alt="111"></div>
		<div>
				<td><input type="radio" name="back-picture"></td>
				<td>背景图片方案五</td>
		</div>
		<div><img src="" alt="111"></div>
	</div>
</div>
<!-- <div id="dialog_info" title="Config" style="overflow:hidden;">
<form>
<fieldset><img src="/images/ebay/template/add_property-26.png"
	alt="create info" title="Create mobile" height="23" width="23"> <a
	class='top'>新增功能格</a> <select name='infobox_type_id'
	id='infobox_type_id'>
	<option value="search">搜寻</option>
	<option value="picture">图片</option>
	<option value="text_Link">Text.Link</option>
	<option value="hot_item">Hot Item</option>
	<option value="new_item">New List Item</option>
	<option value="shop_cat">Store Cat.</option>
	<!-- <option value="newsletter">Newsletter</option> -->
	<!-- <option value="cus_item">Custom Item</option>
	<option value="youtube">Youtube</option>
	<option value="flash">Flash</option> -->
	<!-- <option value="qr_code">QR Code</option> -->
	<!-- <option value="s_html">HTML box</option> -->
	
<!-- </select></fieldset>
</form>
</div> -->
<!-- <div id="desc_info" title="Config" style="overflow:hidden;">
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

			<option value="item_specifics">Item Specifics</option>
			<option value="attributes">Attributes Table</option>
			<option value="picture">Picture Banner</option>
			<option value="html">HTML</option>
			<option value="youtube">Youtube</option>
			<!-- <option value="action_button">Action button</option> -->
			<!-- <option value="d_poster">Poster</option> -->

		<!-- </select></td>
	</tr>
	<tr>
		<td><label><input type="checkbox" name="copymobile" id="copymobile"
			value="copymobile" checked>复制到手机模板</label></td>
	</tr>
</table>

</fieldset>
</form>
</div> -->

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
<div id="d_item_details" class="disno">
		<table width="100%"></table>
		<table>
			<label>Item Specifics</label>
		</table>
		<table style="background: #eee;">
			<tbody>
				<tr>
					<td style="width: 100px;"><label style="margin-left: 10px;">Theme</label></td>
				</tr>
				<tr>
					<td style="width: 200px;">
						<select style="width: 200px;margin-left: 10px;" id="c_theme" name="c_theme">
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
						<label style="margin-left: 10px;margin-top:10px;">边界</label>
					</td>
				</tr>
				<tr class="Auto_Fill_ON">
					<td><select name="attributes_border_style" id="attributes_border_style" class="in80" style="width:200px;margin-left: 10px;">
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
						<select name="attributes_size" id="attributes_size" class="in40" style="width: 200px;margin-left: 10px;margin-top:10px">
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
						<input class='Multiple' type="text" name="border_Mainbody_background" style="height: 30px;width:200px;margin-left:10;margin-right: 10px;" value="#e5e5e5" />
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="hideitems" id="items" value="hideitems" style="margin-left: 10px;margin-top: 10px;">隐藏于模板上，增加在ebay 被搜寻机会
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" style="margin-left: 10px;">复制到手机模板
					</td>
				</tr>
			</tbody>
		</table>
		<table id="attributes" >
			<tbody>
				<tr>
					<td><label style="margin-top:20px;">Title Bar Style</label></td>
				</tr>
				<table style="background: #eee;margin-top: 10px;">
				<tr>
					<td>
						<ul class="main-tab" style="">
								<li tab-show="tab" tab-id="tab1" class="active"><a>picture</a></li>
								<li tab-show="tab" tab-id="tab2"><a>color</a></li>
								<input type="hidden" name="isActive" tab-value="tab" value="tab1" />
						</ul>
					</td>
				</tr>
					
					<tr tab-view="tab" tab-id="tab1" id="attr_t_b_div_p">
						<td>
							<input type="text" name="" id="theme" style="margin-left: 10px;width:200px;" class="in170 infodetclass noneishide" data-noneishide="attr_t_b_div_p" value="<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png">	
								<a name="backgroundPattern" class="tip" style="color: black;">
									<span class="iconfont icon-wenjianjia"></span>
								</a>
						</td>
					</tr>
					<tr tab-view="tab" tab-id="tab2" id="attr_t_c_div_p" style="line-height: 0.8">
						<td>
							<input  class='font8 Multiple'
									type="text" name="title_background" value="" style="height:30px;width:200px;" />
						</td>
					</tr>
					<tr>
					<td>
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
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern1.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png'>Background
												Pattern style 2 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern2.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png'>Background
												Pattern style 4 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern4.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png'>Background
												Pattern style 5 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern5.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png'>Background
												Pattern style 6 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern6.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png'>Background
												Pattern style 7 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern7.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png'>Background
												Pattern style 8 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern8.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png'>Background
												Pattern style 9 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern9.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>

											<tr>
												<td style='width: 200px;'><label style='width: 260px;'> <input
													type='radio' class='font8'
													name='title_bar_style'
													value='<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png'>Background
												Pattern style 10 <a
													style='background-image: url("<?php echo \Yii::$app->urlManager->hostInfo.Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/pattern10.png"); width: 200px; height: 25px; display: inline-block;'></a>
												</label></td>
											</tr>
										</table>
										<input type='button' name='Go' class='goCancel btn_01 goright'
									value='Confirm' />
										</td>
									</tr>
								</table>
						</div>
						<div><label style="margin-left: 10px;">Title Font</label></div>
						<div>
							<select name="name_text_style" id="name_text_style" style="width: 200px;margin-left: 10px;">
								<option value="select">select</option>
								<option value="Arial">Arial</option>
								<option value="Times">Times</option>
								<option value="Andale Mono">Andale Mono</option>
							</select>
						</div>
						<div>
							<select name="Title_f_size" id="Title_f_size" style="width:200px;margin-left: 10px;margin-top:10px;">
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
							<input class='Multiple' type="text" name="Mainbody_background" style="width:200px;margin-left:10;margin-right: 10px;" value="#ffffff" />
						</div>
						
					
						<select name="Title_f_align" id="Title_f_align" style="width:200px;margin-left:10px;margin-top:10px;">
							<option value="left">偏左</option>
							<option value="right">偏右</option>
						</select>
						<hr>	
						
						
						<hr></td>
				</tr>

				<tr>
					<td>
						<div class="toptoptop"></div>
					</td>
				</tr>
				</table>
				
			
			</tbody>
		</table>
		<table><label>Alternate background style:</label></table>
		<div style="background: #eee;width:235px;">
			<ul class="main-tab" style="">
				<li tab-show="tab" tab-id="tab1" class="active">
					<a>picture</a>
				</li>
				<li tab-show="tab" tab-id="tab2">
					<a>color</a>
				</li>
				<input type="hidden" name="isActive" tab-value="tab" value="tab1" />			
			</ul>
			<section tab-view="tab" tab-id="tab1" id="attr_b_b_div_p" style="margin-left: 10px;" class="active">
				<input type="text" name="attr_b_b" id="attr_b_b" class="infodetclass noneishide" data-noneishide="attr_b_b_div_p" style="width:200px;">			
				<a name="attr_b_b_u" class="tip" role="button" aria-disabled="false" style="color: black;">
					<span class="iconfont icon-wenjianjia"></span>
				</a>
			</section>
			<section tab-view="tab" tab-id="tab2" id="attr_b_c_div_p" style="line-height: 10px;margin-top: -13px;">
				<input class='font8 Multiple'
									type="text" name="b1_Mainbody_background" style="height:30px;width:200px;" value="#ffffff" />			
			</section>

			<ul class="main-tab" style="">
				<li tab-show="tab" tab-id="tab3" class="active">
					<a>picture</a>
				</li>
				<li tab-show="tab" tab-id="tab4">
					<a>color</a>
				</li>
				<input type="hidden" name="isActive" tab-value="tab"/>			
			</ul>
			<div tab-view="tab" tab-id="tab3" id="attr_b2_b_div_p" class="active" style="margin-left:10px;">
				<input type="text" name="attr_b2_b" id="attr_b2_b" class="infodetclass noneishide" data-noneishide="attr_b2_b_div_p" style="width:200px;">			
				<a name="attr_b2_b_u" class="tip" role="button" style="color: black;">
					<span class="iconfont icon-wenjianjia"></span>
				</a>
			</div>
			<div tab-view="tab" tab-id="tab4" id="attr_b2_c_div_p" style="line-height: 10px;margin-top: -13px;">
				<input class='font8 Multiple'
									type="text" name="b2_Mainbody_background" style="height: 30px;width:200px;" value="#e5e5e5" />			
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
					
		</div>
		<table><label style="margin-top: 20px;">Text Style</label></table>
		<div style="background: #eee;width:235px;">
						<div><label style="margin-left: 10px;margin-top: 10px;">Text Font:</label></div>
						<div>
							<select id="Text_f_FS" name="Text_f_FS" style="width:200px;margin-left:10px;">
								<option value="Arial">Arial</option>
								<option value="Times">Times</option>
								<option value="Andale Mono">Andale Mono</option>
							</select>
						</div>
						<div>
							<select name="Text_f_size" id="Text_f_size" style="width:200px;margin-left: 10px;margin-top: 10px">
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
						
						<div style="line-height: 10px;"><input class='Multiple'
									type="text" name="text_Mainbody_background" style="width:200px;margin-left:10;margin-right: 10px;" value="" />
						</div>
						<div>
							<select name="Text_f_align" id="Text_f_align" style="width:200px;margin-left:10px;margin-top: 10px;">
								<option value="left">偏左</option>
								<option value="right">偏右</option>
							</select>
						</div>
						
						<div><label style="margin-left: 10px;margin-top: 10px;">Bold Label</label></div>
						<div>
							<select name="Bold_label" id="Bold_label" style="width:200px;margin-left: 10px;margin-bottom: 20px;">
								<option value="yes">yes</option>
								<option value="no">no</option>
							</select>
						</div>
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
<!-- <div id="d_html">
	<textarea class="iv-editor" rows="5" required resize-type="1"></textarea>
</div> -->
<script type="text/javascript">
	$(document).ready(function(){
		$("#changeType").click(function(){
			var switchType = $('input[name="switchType"]:checked ').val();
			var switchProduct = $('input[name="switchProduct"]:checked ').val();
			$.post('/listing/ebay-template/get-partial-template',{switchType:switchType,productType:switchProduct},function(data){
				$('#layout_type').attr('class',switchType);
				$("#"+switchProduct).addClass('active').siblings().removeClass('active');
				$("#changeLayout").hide();
				$('#subbody').css("margin-left",0);

			});

		})

		$(".tab_layout").toggle(function(){
			$("#changeLayout").toggle();
			$("#branch").hide();
			$("#branch1").hide();
			$("#branch2").hide();
			$("#T_Mobile").hide();
			$("#branch4").hide();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",(200+'px'));
		},function(){
			$("#changeLayout").toggle();
			$("#branch").hide();
			$("#branch1").hide();
			$("#branch2").hide();
			$("#T_Mobile").hide();
			$("#branch4").hide();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",0);
		})
		$(".tab1").click(function(){
			$("#changeLayout").hide();
			$("#branch").show();
			$("#branch1").hide();
			$("#branch2").hide();
			$("#T_Mobile").hide();
			$("#branch4").hide();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",(200+'px'));
			$(this).addClass("bkgd").find('img').attr('src','/images/ebay/template/PNG/26.png');
			$(".yan1").addClass('bkgd3');
			$('.tab2').removeClass("bkgd").find('img').attr('src','/images/ebay/template/PNG/24.png');
			$(".yan2").removeClass('bkgd3');
			$(".tab3").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/25.png');
			$(".yan3").removeClass('bkgd3');
			$(".icon-shouji").removeClass('bkgd3');
			$(".tab4").removeClass('bkgd');
			$(".yan4").removeClass('bkgd3');
			$(".icon-shezhi").removeClass('bkgd3');
			$(".tab5").removeClass('bkgd');
			$(".yan5").removeClass('bkgd3');
		});
		$(".tab2").click(function(){
			$("#changeLayout").hide();
			$("#branch").hide();
			$("#branch2").hide();
			$("#T_Mobile").hide();
			$("#branch4").hide();
			$("#branch1").show();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",(200+'px'));
			$(this).addClass("bkgd").find('img').attr('src','/images/ebay/template/PNG/26.png');
			$(".yan2").addClass('bkgd3');
			$(".tab1").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/23.png');
			// $(".tab1").addClass('bkgd1').find('img').attr('src','/images/ebay/template/PNG/23.png');
			$(".yan1").removeClass('bkgd3');
			$(".tab3").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/25.png');
			$(".yan3").removeClass('bkgd3');
			$(".icon-shouji").removeClass('bkgd3');
			$(".tab4").removeClass('bkgd');
			$(".yan4").removeClass('bkgd3');
			$(".icon-shezhi").removeClass('bkgd3');
			$(".tab5").removeClass('bkgd');
			$(".yan5").removeClass('bkgd3');
		});
		$(".tab3").click(function(){
			$("#branch").hide();
			$("#branch1").hide();
			$("#T_Mobile").hide();
			$("#branch4").hide();
			$("#branch2").show();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",(200+'px'));
			$(this).addClass('bkgd')
				.find('img').attr('src','/images/ebay/template/PNG/28.png');
			$(".yan3").addClass('bkgd3');
			$(".tab1").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/23.png');
			$(".yan1").removeClass('bkgd3');
			$(".tab2").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/24.png');
			$(".yan2").removeClass('bkgd3');
			$(".icon-shouji").removeClass('bkgd3');
			$(".tab4").removeClass('bkgd');
			$(".yan4").removeClass('bkgd3');
			$(".icon-shezhi").removeClass('bkgd3');
			$(".tab5").removeClass('bkgd');
			$(".yan5").removeClass('bkgd3');
		})
		$(".tab4").click(function(){
			$("#changeLayout").hide();
			$("#branch").hide();
			$("#branch1").hide();
			$("#branch2").hide();
			$("#branch4").hide();
			$("#T_Mobile").show();
			$('#subbody').css("margin-left",(200+'px'));
			$(this).addClass('bkgd');
			$(".icon-shouji").addClass('bkgd3');
			$(".yan4").addClass('bkgd3');
			$(".tab1").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/23.png');
			$(".yan1").removeClass('bkgd3');
			$(".tab2").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/24.png');
			$(".yan2").removeClass('bkgd3');
			$(".tab3").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/25.png');
			$(".yan3").removeClass('bkgd3');
			$(".icon-shezhi").removeClass('bkgd3');
			$(".tab5").removeClass('bkgd');
			$(".yan5").removeClass('bkgd3');
		})
		$(".tab5").click(function(){
			$("#changeLayout").hide();
			$("#branch").hide();
			$("#branch1").hide();
			$("#branch2").hide();
			$("#T_Mobile").hide();
			$("#branch4").show();
			$("#mobilephone").hide();
			$('#subbody').show("fast");
			$('#subbody').css("margin-left",(200+'px'));
			$(this).addClass('bkgd');
			$(".icon-shezhi").addClass('bkgd3');
			$(".yan5").addClass('bkgd3');
			$(".tab1").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/23.png');
			$(".yan1").removeClass('bkgd3');
			$(".tab2").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/24.png');
			$(".yan2").removeClass('bkgd3');
			$(".tab3").removeClass('bkgd').find('img').attr('src','/images/ebay/template/PNG/25.png');
			$(".yan3").removeClass('bkgd3');
			$(".icon-shouji").removeClass('bkgd3');
			$(".tab4").removeClass('bkgd');
			$(".yan4").removeClass('bkgd3');
		})
		// $("#media").click(function(){
		// 	$("#main1").hide();
		// 	$("#main2").toggle();

		// })
		// $("#upload_file").click(function(){
		// 	$("#main2").hide();
		// 	$("#main1").toggle();

		// })
	});
</script>
<script type="text/javascript">
	// $(document).ready(function(){
	// 	$("#dialog_SNBanner").click(function(){
	// 		$("#SNBanner").dialog("open");
	// 		return false;

	// 	});
	// 	$("#SNBanner").dialog({
	// 			autoOpen:false,
	// 			modal:true,
	// 			buttons:{
	// 				"确定":function(){
	// 					$(this).dialog("close");
	// 				},
	// 				"取消":function(){
	// 					$(this).dialog("close");
	// 				}
	// 			}
	// 		});
	// });
	// $(document).ready(function(){
	// 	$("#dialog_mulu").click(function(){
	// 		$("#MenuD").dialog("open");
	// 		return false;

	// 	});
	// 	$("#MenuD").dialog({
	// 			autoOpen:false,
	// 			modal:true,
	// 			buttons:{
	// 				"确定":function(){
	// 					$(this).dialog("close");
	// 				},
	// 				"取消":function(){
	// 					$(this).dialog("close");
	// 				}
	// 			}
	// 		});
	// });
	// $(document).ready(function(){
	// 	$("#dialog_policy").click(function(){
	// 		$("#dialog_title").dialog("open");
	// 		return false;

	// 	});
	// 	$("#dialog_title").dialog({
	// 			autoOpen:false,
	// 			modal:true,
	// 			buttons:{
	// 				"确定":function(){
	// 					$(this).dialog("close");
	// 				},
	// 				"取消":function(){
	// 					$(this).dialog("close");
	// 				}
	// 			}
	// 		});
	// });
	// $(document).ready(function(){
	// 	$(".infodetail").click(function(){
	// 		$("#infodetail").dialog("open");
	// 		return false;

	// 	});
	// 	$("#infodetail").dialog({
	// 			autoOpen:false,
	// 			modal:true,
	// 			height:500,
	// 			buttons:{
	// 				"确定":function(){
	// 					$(this).dialog("close");
	// 				},
	// 				"取消":function(){
	// 					$(this).dialog("close");
	// 				}
	// 			}
	// 		});
	// });
	$(document).ready(function(){
		$("#dialog_uploading").click(function(){
			$("#dialog_upload").dialog("open");
			return false;

		});
		$("#dialog_upload").dialog({
			autoOpen:false,
			modal:true,
			width:300,
			height:400,
			buttons:{
				"确定":function(){
					$(this).dialog("close");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			}
		});
	});
</script>
<Script>
	var desc_content_set = {"":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"basic_descriptions":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"item_specifics":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"attributes":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"picture":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"html":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"youtube":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"eaglegallery":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"call_for_action":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}},"d_poster":{"top":{"position":"Next_to_Product_Photo","width":"1\/2","align":"left"},"bottom":{"position":"Below_All_Product_Photo","width":"1\/2","align":"left"}}};
	$(function() {
		$("#allItem").form('load', <?php echo json_encode($allItem);?> );
		$(".infodetclass").form('load', <?php echo json_encode($infodetclass);?> );
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
				//              type: 'POST',
				//              data: {
				//                  'Theme_id': $(this).val() ,
				//                  'find': 'theme_id'
				//              },
				//              dataType: 'html',
				//              url: '',
				//              success: function (data) {
				// 		 $('#infodetail').html(data);
				// 		eval(data).ready(function () {
				// 	for (var i = 0; i < nedc.length; i++) {
				// 		$.jPicker.List[array_Multiple[nedc[i]]].color.active.val('hex',$('#'+nedc[i]).val());
				// 	}
				// });
				//              }
				//          });
			}
		});
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
		d_infobox_type['item_specifics'] = "Item Specifics";
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
			var tableapp = "<li style='background:#eee;margin-left:-30px;width:200px;margin-bottom:20px' class='dsort infodno"+n+" delinfodno"+n+"'><table class='info7 infono'>"+
				"<tr class='font8'><td><span class='block'>功能框种类:</span></td><td style='"+hidee+"'><a  class='btn_del' href='#' id='dno"+n+"'></a></td></tr>"+
				"<tr class='font8'><td><input type='text' class='d_content_type_id in110' name='d_content_type_id' value='"+d_infobox_type[d_infobox_type_id]+"' readonly /></td></tr>"+
				"<tr class='font8'><td><span class='block'>功能框标题:</span></td></tr>"+
				"<tr class='font8'><td><input type='text' class='d_content_text in110' name='d_content_text' value='"+d_infobox_type[d_infobox_type_id]+"'  /></td><td style='"+hidee+"'><span "+D_class+" title='Edit Detail' id='content"+n+"' href='#'><span class='iconfont icon-tanchushezhi'></span></span></td></tr>"+
				"<tr class='font8'><td><span class='block'>功能框位置:</span></td></tr>"+
				"<tr class='font8'><td>"+boxposition+"</td></tr>"+
				"<tr class='font8' style='"+hidee+"'><td><span class='block'>横幅宽度:</span></td></tr>"+
				"<tr class='font8'><td><span>"+boxwidth+"</span></td></tr>"+
				"<tr class='font8'><td><span>"+boxalign+"</span></td></tr>"+
				"<tr >"+
				"<td class='disno'><input type='number' class='d_content_displayorder' name='d_content_displayorder' value='"+n+"'  /><input type='text' class='d_infobox_content' name='d_infobox_content' id='d_addcontent"+n+"' value=''  /><input type='text' class='d_infobox_content_id' value=''  /><input type='text' class='d_infobox_en_key' name='d_infobox_en_key' value='"+encode+"'></td></tr></table></li>";
			$( "#dsortable" ).append(tableapp);
			return false;
		}
		function msortable(m_n,m_d_n,encode,value,D_class)
		{
			if(D_class != ''){
				D_class = "<td><span "+D_class+" title='Edit Detail' id='m_content"+m_n+"' href='#'><span class='iconfont icon-tanchushezhi'></span></span></td>";
			}
			var apptable = "<li style='background:#eee;margin-left:-30px;width:200px;margin-bottom:20px' class='msort infono"+m_n+" delinfomobileno"+m_n+"'><table class='info7 infono'>"+
				"<tr class='font8'><td><span class='block'>功能框种类:</span></td><td><a  class='btn_del' href='#' id='mobileno"+m_n+"'></a></td></tr>"+
				"<tr class='font8'><td><input type='text' class='m_content_type_id' name='m_content_type_id' value='"+value+"' readonly /></td></tr>"+
				"<tr class='font8'><td><span class='block'>功能框标题</span></td>"+D_class+
				"<tr class='font8'><td><input type='text' class='m_content_text in110' name='m_content_text' value='"+value+"'  /></td></tr>"+
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
		// $( "#desc_info" ).dialog({
		//      autoOpen: false,
		//      height: 160,
		//      width: 220,
		//      modal: true,
		//      position:[500,300],
		//      buttons: {
		//        "确定": function() {
		//          var bValid = true;
		//          allFields.removeClass( "ui-state-error" );
		//          if ( bValid ) {
		// 	  n = $(".dsort").length + 1;
		// 	  var m_n = $(".msort").length + 1;
		// 	  var d_n = $(".info7").length + 1;
		// 	  var m_d_n = d_n++;
		// 	  var n1 = $(".dsort").length;
		// 	  if("no"==='yes'){
		// 	  var boxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		// 	  var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		// 	   var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
		// 	   var boxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='full' >Full Width</option><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		// 	  }else{
		// 	  var boxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		// 	  var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		// 	  var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' selected>Center</option><option value='right' >Right</option></select>";
		// 	  var boxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='full' >Full Width</option><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		// 	  }
		// 	 var pboxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='full' >Full Width</option><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
		// 	 var eposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
		// 	 var boxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
		// 	 var encode = "";
		// 	  if(d_infobox_type_id.val() == 'basic_descriptions'){
		// 	  if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	   msortable(m_n,m_d_n,encode,m_infobox_type['desc'],'')
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_desc_details detail' name='d_desc_details'")
		// 	  }else if (d_infobox_type_id.val() == 'picture'){
		// 	   if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	  msortable(m_n,m_d_n,encode,m_infobox_type['mpic'],"class='mpic_details'")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),pboxposition,pboxwidth,boxalign,encode,"class='d_pic_details' name='m_addm_content"+m_n+"'")
		// 	  }else if (d_infobox_type_id.val() == 'd_title'){
		// 	   if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	  msortable(m_n,m_d_n,encode,m_infobox_type['m_title'],"")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"")
		// 	  }else if (d_infobox_type_id.val() == 'html'){
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_html_details'")
		// 	  }else if (d_infobox_type_id.val() == 'item_specifics'){
		// 	  if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	   msortable(m_n,m_d_n,encode,m_infobox_type['item_s'],"class='m_item_details' name=''")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_item_details detail' ")
		// 	  }else if (d_infobox_type_id.val() == 'attributes'){
		// 	  if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	   msortable(m_n,m_d_n,encode,m_infobox_type['matt'],"class='m_attr_details'")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_attr_details'")
		// 	  }else if (d_infobox_type_id.val() == 'd_poster'){
		// 	  if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	   msortable(m_n,m_d_n,encode,m_infobox_type['poster'],"")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"")
		// 	  }else if (d_infobox_type_id.val() == 'youtube'){
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),pboxposition,pboxwidth,boxalign,encode,"class='D_youtubedetails'")
		// 	  }else if (d_infobox_type_id.val() == 'action_button'){
		// 	  if($('#copymobile').is(':checked')){
		// 	  encode = $.now();
		// 	   msortable(m_n,m_d_n,encode,m_infobox_type['m_call_for_action'],"class='M_call_for_action'")
		// 	  }
		// 	  dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='D_call_for_action'")

		// 	  }else if (d_infobox_type_id.val() == 'eaglegallery'){
		// 	  if($('.d_content_type_id[value="EagleGallery"]').val()){
		// 	  alert('only one EagleGallery')
		// 	  }else{
		// 	  dsortable(n,d_n,'eaglegallery',eposition,pboxwidth,boxalign,encode,"class='D_eaglegallery'")
		// 	  }}

		//            $( this ).dialog( "close" );
		//          }
		//        },
		//        取消: function() {
		//          $( this ).dialog( "close" );
		//        }
		//      },
		//      close: function() {
		//        allFields.val( "" ).removeClass( "ui-state-error" );
		// 	 $( ".D_Pre_info" ).click();
		// 	  $( ".m_Pre_info" ).click();
		// 	setTimeout(function(){$('#dsortablef #content'+n).click()}, 500);

		//      }
		//    });
		$('#tb_eb_CFI_style_master_HBP').change(
			function () {
				debugger
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
		// $( "#mobile_info" ).dialog({
		//      autoOpen: false,
		//      height: 140,
		//      width: 220,
		//      modal: true,
		//      buttons: {
		//        "确定": function() {
		//          var bValid = true;
		//          allFields.removeClass( "ui-state-error" );
		//          if ( bValid ) {
		// 	  n = $(".msort").length + 1;
		// 	  var n1 = $(".msort").length;
		// 	  var d_n = $(".info7").length + 1;
		// 	  if(m_infobox_type_id.val() == 'poster' || m_infobox_type_id.val() == 'm_title' || m_infobox_type_id.val() == 'mpicture' || m_infobox_type_id.val() == 'mpicture2' || m_infobox_type_id.val() == 'desc' || m_infobox_type_id.val() == 'shopname' || m_infobox_type_id.val() == 'notice' ){
		// 	   msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],'')
		// 	  }else if (m_infobox_type_id.val() == 'policy'){
		// 	   msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='detail' name='tabtool'")
		// 	  }else if (m_infobox_type_id.val() == 'youtube' ){
		// 		msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='myoutube_details'")
		// 	  }else if (m_infobox_type_id.val() == 'mpic' ){
		// 		msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='mpic_details'")
		// 	  }else if (m_infobox_type_id.val() == 'matt' ){
		// 		msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='m_attr_details'")
		// 	  }else if (m_infobox_type_id.val() == 'item_s' ){
		// 		msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='m_item_details'")
		// 	  }else if (m_infobox_type_id.val() == 'm_call_for_action' ){
		// 		msortable(n,d_n,'',m_infobox_type[m_infobox_type_id.val()],"class='M_call_for_action'")
		// 	  }

		//            $( this ).dialog( "close" );
		//          }
		//        },
		//        取消: function() {
		//          $( this ).dialog( "close" );
		//        }
		//      },
		//      close: function() {
		//   $( ".m_Pre_info" ).click();
		//        allFields.val( "" ).removeClass( "ui-state-error" );
		// 	 setTimeout(function(){$('#msortablef #m_content'+n).click()}, 500);
		//      }
		//    });
		function sortable(n,d_n,value,D_class)
		{
			if(D_class != ''){
				D_class = "<td><span "+D_class+" title='Edit Detail' id='content"+n+"' href='#'><span class='iconfont icon-tanchushezhi'></span></span></td>";
			}
			var showtable = "<li class='sort info7 infono"+n+" delinfono"+d_n+" infono'><table class='info7 infono' style='background:#eee;margin-left:-30px;width:200px;margin-bottom:20px'>"+
				"<tr class='font8'><td><span class='block'>功能框种类:</span></td><td><a  class='btn_del' href='#' id='no"+d_n+"'></a></td></tr>"+
				"<tr class='font8'><td><input type='text' class='content_type_id' name='content_type_id' value='"+value+"' readonly /></td></tr>"+
				"<tr class='font8'><td><span class='block'>功能框标题:</span></td></tr>"+
				"<tr class='font8'><td><input type='text' class='content_text in110' name ='content_text' value='"+value+"'  /></td>"+D_class+"</tr>"+
				"<tr >"+
				"<td class='disno'><input type='number' class='content_displayorder' name='content_displayorder' value='"+n+"'  /><input type='text' class='infobox_content' name='infobox_content' id='addcontent"+n+"' value=''  /><input type='text' class='infobox_content_id' value=''  /></td></tr></table></li>"
			$( "#sortable" ).append(showtable);
			return false;
		}
		//   $( "#dialog_info" ).dialog({
		//     autoOpen: false,
		//     height: 140,
		//     width: 200,
		//     modal: true,
		//     position:[500,300],
		//     buttons: {
		//       "确定": function() {
		//         var bValid = true;
		//         allFields.removeClass( "ui-state-error" );
		//         if ( bValid ) {
		//   n = $(".sort").length + 1;
		//   var n1 = $(".sort").length;
		//   var d_n = $(".info7").length + 1;
		//   if(infobox_type_id.val() == 'search' || infobox_type_id.val() == 'new_item' || infobox_type_id.val() == 'qr_code'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],'')
		//   }else if (infobox_type_id.val() == 'shop_cat'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='cat_details'")
		//   }else if (infobox_type_id.val() == 'picture'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='pic_details'")
		//   }else if (infobox_type_id.val() == 'text_Link'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='text_details'")
		//   }else if (infobox_type_id.val() == 'newsletter'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='new_details'")
		//    }else if (infobox_type_id.val() == 'hot_item'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='item_details'")
		//   }else if (infobox_type_id.val() == 'cus_item'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='cus_details'")
		//   }else if (infobox_type_id.val() == 'youtube' ){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='youtube_details'")
		//   }else if ( infobox_type_id.val() =='flash'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='flash_details'")
		//   }else if ( infobox_type_id.val() =='s_html'){
		//   sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='html_details'")
		//   }
		//           $( this ).dialog( "close" );
		//         }
		//       },
		//       取消: function() {
		//         $( this ).dialog( "close" );
		//       }
		//     },
		//     close: function() {
		// $( ".Pre_info" ).click();
		//       allFields.val( "" ).removeClass( "ui-state-error" );
		// setTimeout(function(){$('#sortablef #content'+n).click()}, 500);
		//     }
		//   });

		$( "#create_info" ).click(function() {
			var bValid = true;
			allFields.removeClass( "ui-state-error" );
			if ( bValid ) {
				n = $(".sort").length + 1;
				var n1 = $(".sort").length;
				var d_n = $(".info7").length + 1;
				if(infobox_type_id.val() == 'search' || infobox_type_id.val() == 'qr_code'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],'')
				}else if (infobox_type_id.val() == 'shop_cat'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='cat_details'")
				}else if (infobox_type_id.val() == 'picture'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='pic_details'")
				}else if (infobox_type_id.val() == 'text_Link'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='text_details'")
				}else if (infobox_type_id.val() == 'newsletter'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='new_details'")
				}else if (infobox_type_id.val() == 'hot_item'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"")
				}else if (infobox_type_id.val() == 'cus_item'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"")
				}else if (infobox_type_id.val() == 'youtube' ){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='youtube_details'")
				}else if ( infobox_type_id.val() =='flash'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='flash_details'")
				}else if ( infobox_type_id.val() =='s_html'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"class='html_details'")
				}else if( infobox_type_id.val() == 'new_item'){
					sortable(n,d_n,infobox_type[infobox_type_id.val()],"");
				}
			}
		});
		$( "#Desc_info" ).click(function() {
			var bValid = true;
			allFields.removeClass( "ui-state-error" );
			if ( bValid ) {
				n = $(".dsort").length + 1;
				var m_n = $(".msort").length + 1;
				var d_n = $(".info7").length + 1;
				var m_d_n = d_n++;
				var n1 = $(".dsort").length;
				if("no"==='yes'){
					var boxposition = "<select class='d_content_pos in110' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
					var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
					var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
					var boxwidth = "<select class='d_content_width r80 in110' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
				}else{
					var boxposition = "<select class='d_content_pos in110' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
					var pboxposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
					var pboxalign = "<select class='d_content_align' name='d_content_align'><option value='left' >Left</option><option value='center' selected>Center</option><option value='right' >Right</option></select>";
					var boxwidth = "<select class='d_content_width r80 in110' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
				}
				var pboxwidth = "<select class='d_content_width r80' name='d_content_width'><option value='1/2' >1/2 width</option><option value='1/3' >1/3 width</option><option value='2/3' >2/3 width</option><option value='1/4' >1/4 width</option><option value='2/4' >2/4 width</option><option value='3/4' >3/4 width</option></select>";
				var eposition = "<select class='d_content_pos' name='d_content_pos'><option value='above_product_Photo' >Above Product Photo</option><option value='Next_to_Product_Photo' >Next to Product Photo</option><option value='Below_All_Product_Photo' selected>Below All Product Photo</option></select>";
				var boxalign = "<select style='margin-top:-1' class='d_content_align in110' name='d_content_align'><option value='left' >Left</option><option value='center' >Center</option><option value='right' >Right</option></select>";
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
				}else if (d_infobox_type_id.val() == 'item_specifics'){
					if($('#copymobile').is(':checked')){
						encode = $.now();
						msortable(m_n,m_d_n,encode,m_infobox_type['item_s'],"class='m_item_details' name=''")
					}
					dsortable(n,d_n,d_infobox_type_id.val(),boxposition,boxwidth,boxalign,encode,"class='d_item_details detail' name='d_item_details'")
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
				}else if (d_infobox_type_id.val() == 'action_button'){
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
			}
			$( ".D_Pre_info" ).click();
			$( ".m_Pre_info" ).click();
		});
		$( "#Mobile_info" ).click(function() {
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
			}
			$( ".m_Pre_info" ).click();
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
					$('.menu').html(data);
					infochange();
				}
			});
			var n1 = $(".infono").length;
			if(n1 > 0){
				$( ".Pre_info" ).show();
			}else{
				$( ".Pre_info" ).hide();
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
							if(P_id=='form_upload'){
								$('#form_upload').css("background-image", "url('" + response + "')");
								$('#form_upload').css("background-repeat", "no-repeat");
								$('#form_upload').css("background-size", "260px 100px");
								$('#pictures').hide();
							}
							if(P_id=='Menubar_up' || P_id == 'mobile_up' || P_id == 'policy_up' || P_id == 'Body_up' || P_id == 'cat_up'){
								$('#'+title).val(response);
								$('.'+title).click();
								$(".tooltips").hide('fast');
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
							$(".tooltips").hide('fast');
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
				$(".tooltips").hide('fast');
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
		if(cake.attr('name') == 'SNBanner'){
			$("html, body") .animate({ scrollTop: $('#sample_graphic_setting_Shop_Name_Banner').offset().top -200 +'px'});
		}
		if(cake.attr('name') == 'MenuD'){
			$("html, body") .animate({ scrollTop: $('#menudisplay').offset().top -200 +'px'});
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
				$.modalReady($('#jqi'));
			}
		});

	});
	// $(".tab1").hover(function(){
	// 	$(this).css("background","#5cb4e8")
	// 	.find('img').attr('src','/images/ebay/template/PNG/26.png');
	// 	$(".yan1").css('color','#ffffff');
	// },function(){
	// 	$(this).css("background","#f2f2f2")
	// 	.find('img').attr('src','/images/ebay/template/PNG/23.png');
	// 	$(".yan1").css('color','#ababab');

	// });
	// $(".tab2").hover(function(){
	// 	$(this).css("background","#5cb4e8")
	// 	.find('img').attr('src','/images/ebay/template/PNG/27.png');
	// 	$(".yan2").css('color','#ffffff');
	// },function(){
	// 	$(this).css("background","#f2f2f2")
	// 	.find('img').attr('src','/images/ebay/template/PNG/24.png');
	// 	$(".yan2").css('color','#ababab');

	// });
	// $(".tab3").hover(function(){
	// 	$(this).css("background","#5cb4e8")
	// 	.find('img').attr('src','/images/ebay/template/PNG/28.png');
	// 	$(".yan3").css('color','#ffffff');
	// },function(){
	// 	$(this).css("background","#f2f2f2")
	// 	.find('img').attr('src','/images/ebay/template/PNG/25.png');
	// 	$(".yan3").css('color','#ababab');

	// });
	// $(".tab4").hover(function(){
	// 	$(this).css("background","#5cb4e8");
	// 	$(".icon-shouji").css("color","#ffffff");
	// 	$(".yan4").css('color','#ffffff');
	// },function(){
	// 	$(this).css("background","#f2f2f2");
	// 	$(".icon-shouji").css("color","#ababab");
	// 	$(".yan4").css('color','#ababab');

	// });
	// $(".tab5").hover(function(){
	// 	$(this).css("background","#5cb4e8");
	// 	$(".icon-shezhi").css('color','#ffffff');
	// 	$(".yan5").css('color','#ffffff');
	// },function(){
	// 	$(this).css("background","#f2f2f2");
	// 	$(".icon-shezhi").css('color','#ababab');
	// 	$(".yan5").css('color','#ababab');
	// });
	$(".baocun").hover(function(){
		$(this).css("background-color",'#5cb85c').css('color','#fff').css('border-color','#4cae4c');
	},function(){
		$(this).css("background-color",'#fff').css('color','black').css('border-color','black');
	});
	$("#guanbi").click(function(){
		$("#branch").hide();
		$('#subbody').css("margin-left",0);
	});
	$("#guanbi1").click(function(){
		$("#branch1").hide();
		$('#subbody').css("margin-left",0);
	});
	$("#guanbi2").click(function(){
		$("#branch2").hide();
		$('#subbody').css("margin-left",0);
	});
	$("#guanbi3").click(function(){
		$("#T_Mobile").hide();
		$('#subbody').css("margin-left",0);
	});
	$("#guanbi4").click(function(){
		$("#branch4").hide();
		$('#subbody').css("margin-left",0);
	});
</Script>
