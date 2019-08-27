
<input type="text"  id="boxcontent" value="content2" style="display: none;">

<div id='infoaddhere'>

				<table >
					<tr >
						<td>显示项目部分</td>
					</tr>
					<tr><td>
							<input type='text' style='display: none;' class='content_src_url' value='default'/>				
							<select  class='content_target_url form-control' >
								<option value="l1">相同的存储类别</option>
								<option value="l2">相同的ebay类别选择</option>
								<option value="l3">全部</option>
							</select>
						</td>
					</tr>
				</table>
</div>
 <Script>
  $(function() {
  
	$( "#catsne" ).change( function () {
						if($(this).val() == 'all'){
						 $('.catidshow').hide();
						}else{
						 $('.catidshow').show();
						}
						
						return false;
					  });
		$( "#catsne" ).change();
 });
 
</Script>



