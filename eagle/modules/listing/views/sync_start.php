<?php 
// var_dump($shops);
 ?>
<style>
.input-area{
	width:450px;
}
.shop{
	width:45%;
}
</style>
<div class="iv-form form-base" role="sync-product">
	<input type="hidden" name="type" value="<?= $manual_sync ?>" />
	<div class="form-group">
		<label class="row">
			选择店铺
		</label>
		<div class="row">
			<div class="input-area">
				<?php
				foreach($shops as $k=>$v):
				?>
				<label class="shop">
					<input type="checkbox" name="shop[]" value="<?= $k ?>" />
					<?= $v ?>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label class="row">
			同步数量
		</label>
		<div class="row">
			<div class="input-area">
				<div class="iv-progress">
				    <progress max="100" value="0" style="width:100%;"></progress>
				    <p class="sending">
				        已成功同步 <span data-count>0</span> 个商品
				    </p>
				    <p class="text-success" style="display:none;">恭喜已经同步成功，此次完成了<span data-count>0</span>个商品的同步</p>
				    <p class="text-danger" style="display:none;">同步失败</p>
				</div>
			</div>
		</div>
	</div>
	<div class="sync-action" style="text-align:center;margin-top:50px;">
		<a class="iv-btn btn-important">开始</a>
		<a class="iv-btn btn-default modal-close">取消</a>
	</div>
</div>