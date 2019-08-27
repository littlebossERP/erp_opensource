

<input type="text"  id="boxcontent" value="<?php echo $_REQUEST['contentid']; ?>" style="display: none;">
<a class = 'infoadd' name='infoaddhere' href='#'><img src="/images/ebay/template/add_image-26.png" alt='infoadd' height="20" width="20"><p class='cell'>add </p></a>
<div id='infoaddhere'>
<table class='pictable infopictable1'>
<tr ><td>flash链接</td><td><input type='text' class='content_src_url in200' value=''  /></td><td><a class='inbtn_del' id='pictable1' href='#'></a></td></tr>
</table></div>
 <Script>
  $(function() {
  
	$( ".infoadd" ).click( function () {
	var n = $(".pictable").length + 1;
	$('#'+$( this ).attr('name')).append("<table class='pictable infopictable"+n+"'><tr ><td>flash链接</td><td><input type='text' class='content_src_url in200' value=''  /></td><td><a class='inbtn_del' id='pictable"+n+"'></a></td></tr></table>");
						return false;
					  });
					  });
  $('.inbtn_del').on('click', function () {
	  $(".info"+ $(this).attr('id')).remove();
		return false;
});
</Script>