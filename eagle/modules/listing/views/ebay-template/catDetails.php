
<input type="text"  id="boxcontent" value="content5" style="display: none;">

<div id='infoaddhere'>

				<table >
					<tr >
						<td>Default Extend to</td>
					</tr>
					<tr>
						<td>
							<input type='text' style='display: none;' class='content_src_url form-control' value='default'/>				
							<select  class='content_target_url form-control' >
								<option value="l1">Level 1</option>
								<option value="l2">Level 2</option>
								<option value="l3">Level 3</option>
							</select>
						</td>
					</tr>
					<tr >
						<td>Show & Exclude</td>
					</tr>
					<tr>
						<td>
							<input type='text' style='display: none;' class='content_src_url form-control' value='ShowExclude'/>				
							<select  id='catsne' class='content_target_url form-control'>
								<option value="all">All</option>
								<option value="show">Show</option>
								<option value="exclude">Exclude</option>
							</select>
						</td>

					</tr>
					<tr class='catidshow' >
						<td style="vertical-align: top;">
							<select   class='content_src_url form-control'>
								<option value="lv1">Level 1</option>
								<option value="lv2">Level 2</option>
								<option value="lv3">Level 3</option>

							</select>
							<input type='text'  class='content_target_url form-control' value='' style='' />				
							<input type='text' style='display: none;' class='content_target_url' value='likeandabsolute'/>				
							<select  id='catsne' class='content_src_url form-control' style=''>
								<option value="equal">equal</option>
								<option value="like">like</option>
							</select>
						</td>

					</tr>
					<tr class=''>
						<td>Max. Store Categories shown</td>
					</tr>
					<tr>
						<td>
							<input type='text' style='display: none;' class='content_target_url' value='CategoriesMax'/>				
							<input type='number'  class='content_src_url form-control' value='0' ></td>

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

