
<input type="text"  id="boxcontent" value="<?php echo $_REQUEST['contentid']; ?>" style="display: none;">
<a class = 'infoadd' name='infoaddhere' href='#' style="display:none;"><img src="/images/ebay/template/add_image-26.png" alt='infoadd' height="20" width="20"><p class='cell'>Click here to ADD a new picture</p></a>
<div id='infoaddhere'>
	<table class='pictable infopictable1'>
		<tr >
			<td>图片路径</td>
		</tr>
		<tr>
			<td>
				<input type='text'  name='infobox_img1' id='infobox_img1' class='content_src_url in200 infodetclass' value='<?php echo \Yii::$app->
				urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm10.png' />&nbsp;
				<a name='infobox_img1_u' class='iconfont icon-wenjianjia tip' style="color: black;"></a>
				&nbsp;
				<div class='infobox_img1_u tooltips tipdiv' style="width: 250px;">
					<div class='cattip' style="overflow: auto;">
						<table>
							<tr>
								<td style="" >
									<table class= 'cattipbox'>
										<tr>
											<td >
												<form target='iframe_upload'  class='dropzone smimg font8 newdrop' name='' title='infobox_img1' id='policy_up' method='POST'  enctype='multipart/form-data' dz-message='Drop files here to upload' style='width: 195px;'>
													<div class="dz-default dz-message" data-dz-message="">
														<span>Drop files here to upload</span>
													</div>
													<div class='fallback'>
														<input type='file' multiple='multiple' name='file'/>
														<br/>
														<input type='submit' name='action' value='Upload Now!'/>
														<br/>
														<div id='gallery_uploads'></div>
													</div>
													<div><label style="font-size:20;margin-left: 30px;">本地上传</label></div>
												</form>
											</td>
										</tr>
										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm1.png' >Background Pattern style 1
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm1.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm2.png' >Background Pattern style 2
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm2.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm3.png' >Background Pattern style 3
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm3.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm4.png' >Background Pattern style 4
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm4.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm5.png' >Background Pattern style 5
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm5.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm6.png' >Background Pattern style 6
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm6.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm7.png' >Background Pattern style 7
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm7.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm8.png' >Background Pattern style 8
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm8.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm9.png' >Background Pattern style 9
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm9.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl.'/images/ebay/template/sidebarPics/csm10.png';?>' checked>Background Pattern style 10
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl.'/images/ebay/template/sidebarPics/csm10.png';?>' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm11.png' >Background Pattern style 11
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm11.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm12.png' >Background Pattern style 12
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm12.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm13.png' >Background Pattern style 13
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm13.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm14.png' >Background Pattern style 14
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm14.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm15.png' >Background Pattern style 15
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm15.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm16.png' >Background Pattern style 16
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm16.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm17.png' >Background Pattern style 17
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm17.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm18.png' >Background Pattern style 18
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm18.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm19.png' >Background Pattern style 19
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm19.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm20.png' >Background Pattern style 20
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm20.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm21.png' >Background Pattern style 21
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm21.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm22.png' >Background Pattern style 22
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm22.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm23.png' >Background Pattern style 23
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm23.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>

										<tr>
											<td style='width: 200px;'>
												<label style='width: 260px;' name='infobox_img1' class='upimg_r'>
													<input type='radio'   value='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm24.png' >Background Pattern style 24
													<img src='<?php echo \Yii::$app->
													urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm24.png' alt='' height='' style='width: 200px;'>
												</label>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
					<input type='button' name='Go' class='goCancel btn_01 goright' value='Confirm'/>
				</div>
			</td>
			<td>
				<a class='inbtn_del' id='pictable1' href='#' style="display: none;" ></a>
			</td>
		</tr>
		<tr style="display: none;">
			<td>Target.URL</td>
			<td>
				<input type='text' class='content_target_url in200' value=''  />
			</td>
		</tr>
	</table>
</div>

<Script>
 $(function() {

 function pattern_set2(id,name,value,css){
	var pattern = "<input type='text'  name='" + id + "' id='" + id + "' class='" + css + " infodetclass' value='" + value + "' />";
	pattern +=  "&nbsp;<a name='"+name+ "' class='layout_select tip folder26'></a>";
	pattern +=  "&nbsp;<a name='"+name+ "' class='layout_select tip upload26'></a>";
	pattern +=  "<div class='" + name + " tooltips tipdiv'><div class='cattip'><table><tr><td></td></tr><tr><td class='cattip tdvalingtop'><table class='cattipbox'>";
					if('pic' == 'pic'){
					for (var i = 1; i <= 24; i++) 
					{
					
						var cssst = '';
						var key = i;
						var img = "<?php echo \Yii::$app->urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm" + key + ".png";
						var cssimg = '"<?php echo \Yii::$app->urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/sidebarPics/csm' + "" + key + "" + '.png"';
						
						if(img == value){
							cssst = 'checked';
						}
						pattern +=  "<tr>"+
							"<td style='width: 200px;'>"+
							"<label style='width: 260px;' name='" + id + "' class='upimg_r'>"+
					"<input type='radio'   value='" + img + "' " + cssst + ">Background Pattern style " + key + 
					"<img src='" + img + "' alt='' height=''  style='width: 200px;' width=''>"+
							"</label>"+
							"</td>"+
						"</tr>";	
					};
					}else{
					for (var i = 0; i <= 44; i++) 
					{
					
						var cssst = '';
						if(i < 10){
							var key = '0'+i;
							}else{
							var key = i;
							}
						
						var img = "<?php echo \Yii::$app->urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/" + key + ".png";
						var cssimg = '"<?php echo \Yii::$app->urlManager->hostInfo.\Yii::$app->urlManager->baseUrl ;?>/images/ebay/template/' + "" + key + "" + '.png"';
						
						if(img == value){
							cssst = 'checked';
						}
						pattern +=  "<tr>"+
							"<td style='width: 200px;'>"+
							"<label style='width: 260px;' name='" + id + "' class='upimg_r'>"+
					"<input type='radio'   value='" + img + "' " + cssst + ">Background Pattern style " + key + 
					"<img src='" + img + "' alt='' height=''  style='width: 200px;' width=''>"+
							"</label>"+
							"</td>"+
						"</tr>";	
					};
					}
	pattern += "</table></td><td class='cattip tdvalingtop'>"+
	"<table class='cattipbox'><tr><td><form target='iframe_upload'  class='dropzone smimg font8 newdrop drop" + id + "' name='' title='" + id + "' id='policy_up' method='POST'  enctype='multipart/form-data' dz-message='Drop files here to upload' style='width: 195px;'>"+
						"<div class='fallback'>"+
						"<input type='file' multiple='multiple' name='file'/><br/>"+
						"<input type='submit' name='action' value='Upload Now!'/><br/>"+
						"<div id='gallery_uploads'></div>"+
						 "</div>"+
						"</form></td></tr></table>"+	
	"</td></tr></table></div><input type='button' name='Go' class='goCancel btn_01 goright' value='Confirm'/></div>";
	pattern = pattern.replace(/ididididid/g, id);
	return pattern;
}

$('.close_x').on( "click" ,function () {
                            $($(this).attr('name')).hide("slow");
                            
                            //$($(this).attr('name')).hide("fast");
                            $('.upfin').hide("fast");
                        
                        return false;
                    });
  $('.upimg_r').on('keyup change keydown click', function () {
					 $('#'+$(this).attr('name')).val($(this).find('input').val());
					 $(".tooltips").hide('fast');
					 })
	// $( ".infoadd" ).click( function () {
	// var n = $(".pictable").length + 1;
						// $('#'+$( this ).attr('name')).append("<table class='pictable infopictable"+n+"'><tr ><td>Pic.Src.URL</td><td>"+pattern_set2('infobox_img'+n,'infobox_img'+n+'_u','','content_src_url in200')+"</td><td><a class='inbtn_del' id='pictable"+n+"'></a></td></tr>"+
															// "<tr ><td>Target.URL</td><td><input type='text' class='content_target_url in200' value=''  /></td></tr></table>");
				// $(".dropinfobox_img"+n).each(function(){
					// var name = $(this).attr('name');
					// var title = $(this).attr('title');
					// var P_id = $(this).attr('id');
					// $(this).dropzone({
                    // url: global.baseUrl+'ebayTemplate/imageUpload',
					
                    // dictDefaultMessage:$(this).attr("dz-message"),
					
                    // init:function(){
                        // this.on("error",function(file,response){
                            // console.log(file);
                            // console.log(response);
                            // console.log(arguments);
                        // });
						// this.on("sending",function(file,response){
                            // $("#Loading").dialog({
								// width: 'auto',
								// modal: true
							// });
                        // });
                        // this.on("success",function(file,response){	
						// var data = {
							// 'uploadurl': response,
							// 'id': P_id,
							// 'find':'uploadlog'
							
						// };
						// $.ajax({
						// type: 'POST',
						// data: data,
						// dataType: 'html',
						// url: "",
						// success: function (data) {
							// }
						// });						
							// $('#'+title).val(response);
							// $('.'+title).click();
							// $(".Loadingdialog").dialog("close");
							// $(".tooltip").hide('fast');								
                        // });
                        // this.on("addedfile", function(file) {
                            // var removeButton = Dropzone.createElement("<button class='btn_00'>Remove</button>");
                            // var _this = this;
							// _this.removeAllFiles(true)                           
                        // });
                    // }
                // });
				// });
						// return false;
	// });
// 	  $('#infoaddhere').on('click','.inbtn_del', function () {
// 	  $(".info"+ $(this).attr('id')).remove();
// 		return false;
// });
	
	var uploadedGalleryFiles=[];
	var uploadedVariationFiles={};
//	Dropzone.autoDiscover = false;
	$(".infobox_img1_u form.dropzone").each(function(){// form.dropzone 前面已经初始化了
		var name = $(this).attr('name');
		var title = $(this).attr('title');
		var P_id = $(this).attr('id');
			
		$(this).dropzone({
			// 不定义url dropzone还是会发出一个有问题的请求，虽然不影响图片上传但还是写一条没用的链接给它
		   url: global.baseUrl+ 'listing/ebay-template/no-return',
			previewsContainer:"#pictures",
			// dictDefaultMessage:$(this).attr("dz-message"),
		
		   init:function(){
// 			   this.on("error",function(file,response){
// 					debugger
// 					console.log(file);
// 					console.log(response);
// 					console.log(arguments);
// 					$(".Loadingdialog").dialog("close");
// 					$(".tooltip").hide('fast');
// 			   });
// 			   this.on("success",function(file,response){
// 				   debugger
// 					if(P_id=='Menubar_up' || P_id == 'mobile_up' || P_id == 'policy_up' || P_id == 'Body_up' || P_id == 'cat_up'){
// 					$('#'+title).val(response);
// 					$('.'+title).click();
// 					$(".tooltip").hide('fast');
// 					$('#'+name).css("background-image", "url('" + response + "')");
// 					if(P_id == 'policy_up'){
// 					$('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
// 					$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
// 					}
// 					}else{
					
// 					$('#'+name+'').val(response);
// 					$('#'+title+'_P').click();
// 					//$('#'+title+'anner').hide();	
// 				   if(response!="") {
					 
// 				   }
// 					}
// 					$(".Loadingdialog").dialog("close");
// 			   });
// 			   this.on("addedfile", function(file) {
// 				   debugger
// 					$("#Loading").dialog({
// 						width: 'auto',
// 						height: 61,
// 						modal: true
// 					});
// 				   var removeButton = Dropzone.createElement("<button class='btn_00'>Remove</button>");
// 				   var _this = this;
// 					_this.removeAllFiles(true)                           
// 			   });
			
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
		});
	});
});

</Script>

