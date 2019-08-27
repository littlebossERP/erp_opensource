<input type="text"  id="boxcontent" value="<?php echo $_REQUEST['contentid']; ?>" style="display: none;">
<a class = 'infoadd' name='infoaddhere' href='#'><img src="/images/ebay/template/add_image-26.png" alt='infoadd' height="20" width="20"><p class='cell'>add </p></a>
<div id='infoaddhere'>
<table class='pictable infopictable1'>
<tr ><td>Item ID</td><td><input type='text' class='content_src_url in200' value='Payment Policy'  /></td><td><a class='inbtn_del' id='pictable1' href='#'></a></td></tr>
<tr ><td>Desc</td><td><input type='text' class='content_target_url in200' value='#'  /></td></tr>
</table>
</div>
 <Script>
  $(function() {
  
	$( ".infoadd" ).click( function () {
	var n = $(".pictable").length + 1;
						$('#'+$( this ).attr('name')).append("<table class='pictable infopictable"+n+"'><tr ><td>Item ID</td><td><input type='text' class='content_src_url in200' value='11'  /></td><td><a class='inbtn_del' id='pictable"+n+"'></a></td></tr>"+
															"<tr ><td>Desc</td><td><input type='text' class='content_target_url in200' value='11'  /></td></tr></table>");
						return false;
					  });
					  });
  $('#infoaddhere').on('click','.inbtn_del', function () {
	  $(".info"+ $(this).attr('id')).remove();
		return false;
});
</Script>





