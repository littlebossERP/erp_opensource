<?php 

$opt = [];
foreach($account_list as $account){
	$opt[$account->site_id] = $account->store_name;
}
// var_dump($opt);die;
// actionGetParentCategoryId
// parentCategoryId
// site_id
?>

<form id="ensogo-move-confirm" class="block iv-form form-horizontal" style="min-width:900px;">
	<div class="form-group">
		<label for="" class="row" style="flex:0 0 120px;">选择 Ensogo 店铺：</label>
		<div class="row">
			<div class="input-control">
				<select required name="site_id" class="iv-input" placeholder="请选择店铺" id="sel_ensogo_site">
					<option value="0">请选择店铺</option>
					<?php 
					echo $options($opt,0);
					?>
				</select>
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<label for="" class="row" style="flex:0 0 120px;">选择类目：</label>
		<div class="row">
			<p id="selCate"></p>
		</div>
	</div>

	<div class="form-group">
		<div class="level-link-dropdown"  
			name="category" title="选择类目" 
			level="3" 
			url="get-parent-category-id" 
			paramKey="parentCategoryId" 
			method="post" 
			view="#selCate"
		>
		</div>
	</div>

	<div class="form-group">
		<label for="" class="row" style="flex:0 0 120px;">修改运输时间：</label>
		<div class="row">
			<div class="input-control">
				<input type="number" class="iv-input" name="inventory_1" min="1" value="15" /> - 
				<input type="number" class="iv-input" name="inventory_2" min="1" value="30" />
			</div>
		</div>
	</div>
	
	<div  style="text-align:center;">
		<input type="submit" class="iv-btn btn-success" value="确定" />
		<input type="button" class="iv-btn btn-default modal-close" value="取消" />
	</div>

</form>
	