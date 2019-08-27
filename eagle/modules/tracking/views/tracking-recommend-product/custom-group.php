<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/tracking/custom_product.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJs("customProduct.init();", \yii\web\View::POS_READY);
// $platform_array = [
//     '1'=>'bonanza',
//     '2'=>'cdiscount',
// ];
// $seller_array = [
//     '1'=>'jack',
//     '2'=>'mary',
// ];
?>


<style>
.base-product h3{
	margin: 20px 0;
    font-weight: bold;
    padding-left: 6px;
    border-left: 3px solid #01bdf0;
}
.red{
	color:red;
}
.comment-area{
	width: 571px;
    height: 200px;
}
.product-save{
	width:700px;
}
.product-save-split{
	width:100%;
	height:30px;
	margin-top: 2px;
}
.product-save-split .input-control{
	float:left;
	margin-left:20px;
}
.new-input{
	width:300px;
}
.edit-input{
	width:218px;
}
.select-style{
	height:25px;
	width:200px;
}
.product-bottom{
	margin-left:10px;
}
.product-bottom .input-control{
	margin-top:5px;
}
.product-bottom .bottom-select{
	width:571px
}
.button-group-style{
	text-align:center;
	margin-top: 6px;
}
.button-group-style button{
	width:100px;
}
.new-group-name{
    margin-left: 19px;
    margin-bottom: 6px;
}
</style>
<div class="base-product">
    <form id="custom_group">
    <div>
        <input type="hidden" id="saveType" name="saveType" value="save">
    </div>
    <div class="product-save">
    
        <div class="input-control">
			<div class="input-group new-group-name">
			    <label>商品组名<span class="red">*</span></label>
				<input type="text" class="iv-input <?php echo empty($data)?"new-input":"edit-input"?>"  data-name="商品组名" name="group_name" id="group_name" value="<?php echo !empty($data['group_name'])?$data['group_name']:'';?>"/>
			</div>
		</div>
		
        <div class="product-save-split">
			<div class="input-control">
				<label>展示平台</label>
				<select class="iv-select select-style" data-name="展示平台" name="platform" id="platform" onchange="customProduct.customPlatformChange(this);">
				    <option value="">选择平台</option>
				    <?php 
				        if(!empty($platform_array)):
                            foreach ($platform_array as $platform_key => $platform_value):				            
				    ?>
        					<option value="<?php echo $platform_key?>" <?php echo (!empty($data['platform'])&&$platform_key == $data['platform'])?'selected':''?>><?php echo $platform_value?></option>
					<?php 
					        endforeach;
				        endif;
					?>
				</select>
			</div>
        </div>  
        
        <div class="product-save-split">
        
			
			<div class="input-control">
			    <label>展示店铺</label>   
				<select class="iv-select select-style" data-name="展示店铺" name="seller_id" id="seller_id">
				    <option value="">选择店铺</option>
				    <?php 
				        if(!empty($seller_array)):
                            foreach ($seller_array as $seller_key => $seller_value):				            
				    ?>
        					<option value="<?php echo $seller_key?>" <?php echo (!empty($data['seller_id'])&&$seller_key == $data['seller_id'])?'selected':''?>><?php echo $seller_value?></option>
					<?php 
					        endforeach;
				        endif;
					?>
				</select>
			</div>
	   </div>
	   
	   <div class="product-bottom">
			<div class="input-control">
				<div class="input-group">
				<label>商品组描述</label>
					<textarea class="iv-input comment-area" style="margin-left:2px;" name="group_comment" id="group_comment"><?php echo !empty($data['group_comment'])?$data['group_comment']:'';?></textarea>
				</div>
			</div>

		</div>
	</div>
    </form>
    <div class="button-group-style">
	   <button class="btn btn-success" id="saveNewGroup">保 存</button>
	   <button class="btn btn-success" id="windowDisplay">返回</button>
	</div>
</div>