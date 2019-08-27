<input type="text"  id="boxcontent" value="<?php echo $_REQUEST['contentid']; ?>" style="display: none;">
<div><label>Text.link</label></div>
<div style="background: #eee">
	<a class = 'infoadd' name='infoaddhere' style="margin-left: 10px;"><span class="iconfont icon-zengjia"></span>click here to ADD a new text</a>
</div>

<div id='infoaddhere'>
	<table class='pictable infopictable1' style="margin-top: 20px;background: #eee;width:260px;">
		<tr >
			<td><label style="margin-left: 10px">文本</label></td>
			<td>
				<a class='inbtn_del' id='pictable1' href='#'></a>
			</td>
		</tr>
		<tr>
			<td>
				<input type='text' class='content_src_url in200' value='Payment Policy' style="margin-left: 10px" />
			</td>
		</tr>
		<tr>
			<td><label style="margin-left: 10px">目标路径</label></td>
		</tr>
		<tr >
			<td>
				<input type='text' class='content_target_url in200' value='#'  style="margin-left: 10px"/>
			</td>
		</tr>
	</table>
	<table class='pictable infopictable2' style="margin-top: 20px;background: #eee;width:260px;">
		<tr >
			<td><label style="margin-left: 10px">文本</label></td>
			
			<td>
				<a class='inbtn_del' id='pictable2' href='#'></a>
			</td>
		</tr>
		<tr>
			<td>
				<input type='text' class='content_src_url in200' value='Shipping Policy' style="margin-left: 10px"/>
			</td>
		</tr>
		<tr>
			<td><label style="margin-left: 10px">目标路径</label></td>
		</tr>
		<tr >
			<td>
				<input type='text' class='content_target_url in200' value='#' style="margin-left: 10px"/>
			</td>
		</tr>
	</table>
	<table class='pictable infopictable3' style="margin-top: 20px;background: #eee;width:260px;">
		<tr >
			<td><label style="margin-left: 10px">文本</label></td>
			<td>
				<a class='inbtn_del' id='pictable3' href='#'></a>
			</td>
		</tr>
		<tr>
			<td>
				<input type='text' class='content_src_url in200' value='Return Policy'  style="margin-left: 10px"/>
			</td>
		</tr>
		<tr>
			<td><label style="margin-left: 10px">目标路径</label></td>
		</tr>
		<tr >
			<td>
				<input type='text' class='content_target_url in200' value='#' style="margin-left: 10px"/>
			</td>
		</tr>
	</table>
	<table class='pictable infopictable4' style="margin-top: 20px;background: #eee;width:260px;">
		<tr >
			<td><label style="margin-left: 10px">文本</label></td>
			<td>
				<a class='inbtn_del' id='pictable4' href='#'></a>
			</td>
		</tr>
		<tr>
			<td>
				<input type='text' class='content_src_url in200' value='FAQ' style="margin-left: 10px"/>
			</td>
		</tr>
		<tr>
			<td><label style="margin-left: 10px">目标路径</label></td>
		</tr>
		<tr >
			<td>
				<input type='text' class='content_target_url in200' value='#' style="margin-left: 10px"/>
			</td>
		</tr>

	</table>
	<table class='pictable infopictable5' style="margin-top: 20px;background: #eee;width:260px;">
		<tr >
			<td><label style="margin-left: 10px">文本</label></td>
			<td>
				<a class='inbtn_del' id='pictable5' href='#'></a>
			</td>
		</tr>
		<tr>
			<td>
				<input type='text' class='content_src_url in200' value='About Us' style="margin-left: 10px" />
			</td>
		</tr>
		<tr>
			<td><label style="margin-left: 10px">目标路径</label></td>
		</tr>
		<tr >
			<td>
				<input type='text' class='content_target_url in200' value='#' style="margin-left: 10px" />
			</td>
		</tr>
	</table>
</div>
 <Script>
  $(function() {
  
	$( ".infoadd" ).click( function () {
	var n = $(".pictable").length + 1;
						$('#'+$( this ).attr('name')).append("<table class='pictable infopictable"+n+"' style='margin-top: 20px;background: #eee;width:260px;'><tr ><td><label style='margin-left:10px;'>文本</label></td><td><a class='inbtn_del' id='pictable"+n+"'></a></td></tr>"+"<tr><td><input type='text' class='content_src_url in200' value='' style='margin-left:10px;'/></td></tr>"+"<tr ><td><label style='margin-left:10px;'>目标路径</label></td></tr>"+"<tr><td><input type='text' class='content_target_url in200' value='' style='margin-left:10px;'/></td></tr></table>");
						return false;
					  });
					  });
  $('#infoaddhere').on('click','.inbtn_del', function () {
	  $(".info"+ $(this).attr('id')).remove();
		return false;
});
</Script>

