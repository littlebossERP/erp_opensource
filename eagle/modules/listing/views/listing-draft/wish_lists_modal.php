<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\helpers\HtmlHelper;
//$this->registerCssFile(\Yii::getAlias('@web') . "/css/comment-v2.css");
$this->registerCssFile(\Yii::getAlias('@web') . "/css/listing-draft/wish_lists_modal.css");
$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/listing-draft/wish_lists_modal.js");

?>
<style>
	.main{
		width:598px;
		margin-top:35px;
		height:580px;
		overflow:hidden;
	}

</style>

<div class="main" style="width:598px;">

		<?php 
		if(!is_array($parent_sku)){
			$parent_sku = [$parent_sku];
		}
		foreach($parent_sku as $sku):
		 ?>
		<input type="hidden" class="parent_sku" name='parent_sku[]' value="<?=$sku ?>">
		<?php endforeach; ?>
		<div class="line">
			<div class="line-top">
				<div class="little-box">
					<a class="qiehuan">
						<span class="minus">-</span>
						<span class="plus">+</span>
					</a>
				</div>
				<div class="many-check">
					<input type="checkbox" check-all="xxx" style="width:15px;height:15px;"><span class='a-fontsize'>xxx</span>
				</div>
			</div>
			<div class="line-bottom">
				<?php foreach($shopLists as $shopList):?>
				<div class="many-check">
					<input type="checkbox" name="shops[]" data-check="xxx" value="<?= $shopList->id?>" value="<?=$shopList->sellerloginid ?>" style="width:15px;height:15px;"><span class='b-fontsize'>
						<?= $shopList->store_name?>
					</span>
				</div>
				<?php endforeach; ?>
			</div>
			
		</div>


		<div class="comman-fon">
			<div class="comman-fon-left">
				<span class='b-fontsize'>已选中项</span>
			</div>
			<div class="comman-fon-right">
				<span class="glyphicon glyphicon-trash icon-button a-fontsize"></span> 	
				<a href='javascript:' class='b-fontsize b-fontsize-color'>清除所有</a>
			</div>
		</div>
		

		<div class="bottom-box">
			
		</div>
		
		<div class="sure-button">
			<center>
			<a target="_modal" title="搬家成功" href="move" method="post" param="$(this).closest('.main').serializeObject()" class="iv-btn btn-success modal-close" id="sures" value="确定" style="width:102px;line-height:20px;background:#04CE59;border:none;border-radius:5px;font-weight:400;color:#FFFAFA;">确定</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<button style="width:102px;height:36px;background:#EFEFEF;border:none;border-radius:5px;font-weight:400;color:#6B6B6B;" class="modal-close">取消</button>
			</center>
		</div>

</div>
			


