<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

// $this->registerJs("customProduct.init();", \yii\web\View::POS_READY);
// $platform_array = [
//     '1'=>'bonanza',
//     '2'=>'cdiscount',
// ];
$currency_array = [
    'USD'=>'USD',
    'EUR'=>'EUR',
];
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
	width: 558px;
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
	width:280px;
}
.edit-input{
	width:200px;
}
.select-style{
	height:25px;
	width:150px;
}
.product-bottom{
	margin-left:20px;
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
.custom-tips{
	padding-left: 19px;
	font-size: 13px;
	padding-bottom: 8px;
	color:#01bdf0;
}
</style>
<div class="base-product">
    <form id="custom_product">
    <div>
        <input type="hidden" id="saveType" name="saveType" value="<?php echo !empty($data)?'edit':'save';?>">
        <input type="hidden" id="prouductId" name="prouductId" value="<?php echo !empty($data)?$data['id']:'';?>">
        <input type="hidden" id="accountValue" name="accountValue" value="<?php echo !empty($data['seller_id'])?$data['seller_id']:'';?>">
        <input type="hidden" id="userHabit" name="userHabit" value="<?php echo !empty($userHabit['userHabit'])?$userHabit['userHabit']:'';?>">
    </div>
    <div class="product-save">
        <div class="custom-tips"><span>提示：该商品(网址)必须是来自展示店铺的，否则可能会被认为店铺关联。</span></div>
        <?php if(!empty($data['photo_url'])):?>
            <img src="<?php echo $data['photo_url'];?>" style="width:60px;height:60px;float:left;margin-left:20px;">
        <?php endif;?>
        <div class="product-save-split">
            <div class="input-control">
				<div class="input-group">
				    <label>商品名称(仅卖家可见)&nbsp;<span class="red">*</span></label>
					<input type="text" class="iv-input <?php echo empty($data)?"new-input":"edit-input"?>"  data-name="商品名称" name="product_name" id="product_name" value="<?php echo !empty($data['product_name'])?$data['product_name']:'';?>"/>
				</div>
			</div>
			
			<div class="input-control">
				<label>展示平台</label>
				<select class="iv-select select-style" data-name="展示平台" name="platform" id="platform" onchange="customProduct.customPlatformChange(this);">
				    <option value="">选择平台</option>
				    <?php 
				        if(!empty($platform_array)):
                            foreach ($platform_array as $platform_key => $platform_value):				            
				    ?>
        					<option value="<?php echo $platform_key?>" 
        					<?php 
        					   if(!empty($data['platform'])&&$platform_key == $data['platform']){
        					       echo 'selected';
        					   }else if(!empty($userHabit['platformHabit'])&&$platform_key == $userHabit['platformHabit']){
        					       echo 'selected';
        					   }else{
        					       echo '';
        					   }
        					?>>
        					<?php echo $platform_value?></option>
					<?php 
					        endforeach;
				        endif;
					?>
				</select>
			</div>
        </div>  
        
        <div class="product-save-split" style="margin-left: 2px;">
        
            <div class="input-control">
				<div class="input-group">
				    <label>商品SKU(仅卖家可见)&nbsp;&nbsp;</label>
					<input type="text" class="iv-input <?php echo empty($data)?"new-input":"edit-input"?>" <?php echo !empty($data)?"style='margin-left:2px;'":''?> name="sku" id="sku" value="<?php echo !empty($data['sku'])?$data['sku']:'';?>"/>
				</div>
			</div>
			
			<div class="input-control">
			    <label>展示店铺</label>   
				<select class="iv-select select-style" data-name="展示店铺" name="seller_id" id="seller_id">
				    <option value="">选择店铺</option>
				</select>
			</div>
	   </div>
	   
	   <div class="product-bottom">
   			<div class="input-control">
				<label>商品组名&nbsp;</label>
				<select class="iv-select" style="height:25px;margin-left: 2px;" data-name="商品组名" name="group_name" id="group_name" onchange="customProduct.platformChange(this)">
					<option value="">选择组别</option>
				    <?php 
				        if(!empty($group_array)):
                            foreach ($group_array as $group_name_key => $group_name_value):				            
				    ?>
        					<option value="<?php echo $group_name_key?>" <?php echo (!empty($data['group_id'])&&$group_name_key == $data['group_id'])?'selected':''?>><?php echo $group_name_value?></option>
					<?php 
					        endforeach;
				        endif;
					?>
				</select>
				<lable id="group_attr">
				    <?php 
				        if(!empty($data)&&!empty($groups_detail)&&isset($groups_detail[$data['group_id']])){
				            echo '<span id="belong_platform" data-name="'.$groups_detail[$data['group_id']]['platform'].'">所属平台：'.$groups_detail[$data['group_id']]['platform'].'</span>&nbsp;&nbsp;<span id="belong_seller" data-name="'.$groups_detail[$data['group_id']]['seller_id'].'">所属店铺：'.$groups_detail[$data['group_id']]['seller_id'].'</span>';
				        }
				    ?>
				</lable> 
			</div>
			
			<div class="input-control">
				<div class="input-group">
				    <label>单件价格&nbsp;<span class="red">*</span></label>
					<input type="text" class="iv-input" data-name="单件价格" onkeyup="value=value.replace(/[^0-9.]/g,'')" name="price" id="price" value="<?php echo !empty($data['price'])?$data['price']:''?>"/>
					<select class="iv-select" style="height:25px" data-name="币种" name="currency" id="currency">
					    <option value="">币种</option>
						<?php 
        			        if(!empty($currency_array)):
                                foreach ($currency_array as $currency_key => $currency_value):				            
        			    ?>
            					<option value="<?php echo $currency_key?>" 
            					<?php
                					if(!empty($data['currency'])&&$currency_key == $data['currency']){
                					    echo 'selected';
                					}else if(!empty($userHabit['currencyHabit'])&&$currency_key == $userHabit['currencyHabit']){
                					    echo 'selected';
                					}else{
                					    echo '';
                					} 
            					?>>
            					<?php echo $currency_value?></option>
        				<?php 
        				        endforeach;
        			        endif;
        				?>
					</select>
				</div>
			</div>
	
			<div class="input-control">
				<div class="input-group">
				    <label>商品标题&nbsp;<span class="red">*</span></label>
					<input type="text" class="iv-input bottom-select" data-name="商品标题" name="title" id="title" value="<?php echo !empty($data['title'])?$data['title']:'';?>"/>
				</div>
			</div>
			
			<div class="input-control">
				<div class="input-group">
				    <label>图片地址&nbsp;<span class="red">*</span></label>
					<input type="text" class="iv-input bottom-select" data-name="图片地址" name="photo_url" id="photo_url" value="<?php echo !empty($data['photo_url'])?$data['photo_url']:'';?>"/>
				</div>
			</div>
			
			<div class="input-control">
				<div class="input-group">
				    <label>购买网址&nbsp;<span class="red">*</span></label>
					<input type="text" class="iv-input bottom-select" data-name="产品地址" name="product_url" id="product_url" value="<?php echo !empty($data['product_url'])?$data['product_url']:'';?>"/>
				</div>
			</div>
			
			<div class="input-control">
				<div class="input-group">
				<label>商品描述&nbsp;&nbsp;<br>(仅卖家可见)</label>
					<textarea class="iv-input comment-area" style="margin-left:2px;" name="comment" id="comment"><?php echo !empty($data['comment'])?$data['comment']:'';?></textarea>
				</div>
			</div>

		</div>
	</div>
    </form>
    <div class="button-group-style">
	   <button class="btn btn-success" id="saveNewProduct">保 存</button>
	   <button class="btn btn-success" id="windowDisplay">返回</button>
	</div>
</div>