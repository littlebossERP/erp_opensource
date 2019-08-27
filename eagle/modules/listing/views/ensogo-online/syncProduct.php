<div class="iv-form form-horizontal">
	<div class="form-group">
		<label class="row">
			选择店铺
		</label>
		<div class="row">
			<div class="input-control">
				<select name="site_id" class="iv-input">
					<?php 
					if(count($accounts)>1){
						echo "<option value=''>请选择店铺</option>";
					}
					foreach($accounts as $account): ?>
					<option value="<?= $account->site_id ?>"><?= $account->store_name ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label class="row">
			已同步商品数
		</label>
		<div class="row">
			<span id="progress"></span>
		</div>
	</div>
</div>

<button style="margin-left:150px;" class="iv-btn btn-primary" id="beginSync" disabled="disabled">开 始</button>
<button style="margin-left:150px;display:none;" class="iv-btn btn-icon modal-close" onclick="$.location.reload();">关 闭</button>