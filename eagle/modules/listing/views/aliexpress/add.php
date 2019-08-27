<?php 

	$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/aliexpress.css',['depends'=>'eagle\assets\AppAsset']);
	$this->registerJsFile(\Yii::getAlias('@web').'/js/project/listing/aliexpress.js',['depends'=>'eagle\assets\AppAsset']);
	$image = [];
?>
<?php 
	echo $this->render('//layouts/new/left_menu',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>
<style>
.ke-icon-imgbank {  
    background-image: url(/images/aliexpress/imgbank.png);  
    width: 16px;  
    height: 16px;  
} 
.ke-icon-infoModule{
	background-image: url(/images/aliexpress/infoModule.png);
	width:16px;
	height:16px;
}
</style>
<div class="content" data-type="add">
	<form>
		<div class="ali-title">
			<a id="new_product">账号</a>
		</div>
		<div class="selleruser_box left-spacing-m">
			<div class="form-group">
				<label for="selleruserid">刊登店铺<i class="sign">*</i></label>
				<div>
					<select class="iv-input input-spacing max-width" id="selleruserid" name="selleruserid">
						<option value="">---请选择店铺---</option>
						<?php foreach($users as $user): ?>	
								<option value="<?=$user['sellerloginid'];?>"><?=$user['sellerloginid']?></option>
						<?php endforeach;?>
					</select>
					<p class="error-tips max-width" style="display:none;">
						<span class="iconfont icon-cuowu sign right-spacing-m"></span> 刊登店铺为必选项
					</p>
				</div>
			</div>
		</div>
		<div class="category_box left-spacing-m">
			<div class="form-group">
				<label for="">产品类目<i class="sign"></i></label>
				<div class="input-spacing">
					<select class="iv-input" name="categoryid" class="categoryid" style="width:675px;">
						<option value="">---请选择分类---</option>
					</select>
					<a class="iv-btn btn-success left-spacing-s " target="#category-modal" title="选择类目">选择类目</a>
				</div>
				<p class="error-tips" style="width:752px;display:none;">
					<span class="iconfont icon-cuowu sign right-spacing-m"></span> 产品类目为必选项
				</p>
			</div>
		</div>
		<div class="ali-title">
			<a id="product_attributes">产品属性</a>
		</div>
		<div style="clear:both;"></div>
		<div class="product_attributes_box left-spacing-m">
		</div>
		<div class="user_defined_area left-spacing-m">
			<div class="form-group">
				<label class="user_defined label-inline">自定义属性</label>
				<div class="user-defined-box label-spacing">
						<a class="iv-btn btn-white input-spacing left-spacing-l add_attr">添加自定义属性</a>
				</div>
			</div>
		</div>
		<div class="ali-title">
			<a id="product_info">产品信息</a>
		</div>
		<div style="clear:both;"></div>
		<div class="product_info_box">
			<div class="form-group left-spacing-m">
				<label>产品标题<i class="sign">*</i></label>
				<div class="product_title">
					<input class="iv-input input-spacing max-width check_len" type="text" name="subject">
					<span class="left-spacing-s"><i class="product_title_length sign">0</i>/128</span>
					<p class="error-tips max-width" style="display:none;">
						<span class="iconfont icon-cuowu sign right-spacing-m"></span> 产品标题为必填项
					</p>
				</div>
			</div>
			<div class="form-group left-spacing-m input-spacing">
				<label class="text-left">产品分组</label>
				<div class="product_group_list_box left-spacing-l" style="display:inline-block;">
					<span>未选择分组</span>
					<a class="iv-btn btn-success left-spacing-s selProductGroup">选择分组</a>
				</div>
			</div>
			<div class="iv-form form-base left-spacing-m">
				<div class="form-group">
					<label for="" class="row" id="select-img-lib">产品图片</label>
					<div class="row">
						<div class="input-control input-area">
							<?= $this->renderFile(\Yii::getAlias('@modules').'/util/views/ui/img-list.php',[
								'name'=>'extra_images',
								'max'=>12,
								'primaryKey'=>'main_image',
								'btn' => [
									'shanchu','link'
								], 
								'images'=>$image
							]) ?>
							<p class="input-spacing">
							图片格式<i class="sign">JPEG</i>,文件大小<i class="sign">5M</i>以内；图片像素建议大于<i class="sign">800*800</i>;横向和纵向比例建议<i class="sign">1:1到1:3</i>之间；</p>
							<p class="input-spacing">
							图片中产品主题占比建议大于<i class="sign">70%</i>；背景<i class="sign">白色或纯色</i>,风格统一；如果有LOGO，建议放置在左上角，不宜过大。</p>
							<p class="input-spacing">
							不建议自行添加促销标签或文字。切勿盗用他人图片，以免受网规处罚	
							<span class="left-spcing-m link">了解更多</span>
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group left-spacing-m input-spacing">
				<div class="sale_unit rows">
					<label class="text-left">最小计量单位<i class="sign">*</i></label>
					<div class="input-spacing">
						<select class="iv-input max-width" name="product_unit" class="product_unit" placeholder="件/个(piece/pieces)">
							<?php foreach($product_unit as $k => $unit): ?>
								<option value="<?=$unit['id']?>" data-name_zh="<?=$unit['zh']?>" data-name_en="<?=$unit['en']?>" <?php if($unit['id'] == 100000015 ):?>selected<?php endif;?>><?=$unit['zh']?><?=$unit['en']?></option>
							<?php endforeach;?>
						</select>
					</div>
				</div>
				<div class="rows sale_ways" style="font:12px/20px Microsoft Yahei;">
					<label class="text-left">销售方式<i class="sign">*</i></label>
					<div class="input-spacing">
						<input class="iv-radio" type="radio" name="package_type"  hide="lot_num" value="0" checked>
						<span class="package_unit">按件/个(piece/pieces)出售</span>	
						<input class="iv-radio left-spacing-l" type="radio" name="package_type"  show="lot_num" value="1">
						<span>打包出售</span>
						<label show-content="lot_num" class="left-spacing-l" style="display:none">每包 <input class="iv-input" name="lot_num"  check-valid="number"> <span class="unit">件</span></label>
					</div>
				</div>
				<div class="rows reduce_ways">
					<label class="text-left">库存扣减方式</label>
					<div class="input-spacing">
						<input class="iv-radio" type="radio" name="reduce_strategy" value="2" checked>付款减库存
						<input class="iv-radio left-spacing-l" type="radio" name="reduce_strategy" value="1">
						下单减库存
					</div>
				</div>
				<div class="rows delivery_time_box">
					<label class="text-left">发货期<i class="sign">*</i></label>
					<div class="input-spacing">
						<input class="iv-input max-width" check-valid="day" type="text" name="delivery_time"> 天
						<p class="error-tips input-spacing max-width" style="display:none;"> 	
							<span class="iconfont icon-cuowu sign right-spacing-m"></span>
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="ali-title">
			<a id="variance_info">变体信息</a>
		</div>
		<div style="clear:both;"></div>
		<div class="variance_info_box left-spacing-m">
			<div class="form-group">
				<div class="rows input-spacing single_product">
					<label class="text-left">零售价<i class="sign">*</i></label>
					<div class="input-spacing">
						<input name="sale_price" check-valid="price" class="iv-input max-width">&nbsp;USD/
						<span class="unit">件</span>
						<p class="error-tips input-spacing max-width" style="display:none;">
							<span class="iconfont icon-cuowu sign right-spacing-m"></span>
						</p>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="rows input-spacing single_product">
					<label class="text-left">库存<i class="sign">*</i></label>
					<div class="input-spacing">
						<input name="inventory" check-valid="inventory" class="iv-input max-width">
						&nbsp;/<span class="unit">件</span>
						<p class="error-tips input-spacing max-width" style="display:none;"><
							<span class="iconfont icon-cuowu sign right-spacing-m"></span>
						</p>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="rows input-spacing single_product">
					<label class="text-left">商品编码</label>
					<div class="input-spacing">
						<input class="iv-input max-width" name="product_code">
						<p class="error-tips input-spacing max-width" style="display:none;">
							<span class="iconfont icon-cuowu sign right-spacing-m"></span>
						</p>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="rows input-spacing">
					<label class="text-left">批发价</label>
					<div class="input-spacing">
						<label>
							<input class="iv-radio" type="radio" name="is_bulk" value="0" hide="bulk_detail" checked="">不支持
						</label>
						<label>
							<input class="iv-radio left-spacing-l" type="radio" name="is_bulk" value="1" show="bulk_detail">支持
						</label>
						<p class="" show-content="bulk_detail" style="display:none">购买数量 <input class="iv-input short-input" type="text" name="bulk_order"> 件及以上时，每件价格在零售价的基础上减免 
							<input class="iv-input short-input" check-valid="discount" type="text" name="bulk_discount"> %,即
							<span data-name="bulk_discount">--</span>折
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="ali-title">
			<a id="product_describe">商品描述</a>
		</div>
		<div class="product_describe_box">
			<div class="form-group left-spacing-m">
				<label>产品详细描述<i class="sign">*</i></label>
				<div class="input-spacing">
					<textarea class="ali-editor"></textarea>
				</div>
			</div>
		</div>
		<div class="ali-title">
			<a id="logistics_service_info">物流服务信息</a>
		</div>
		<div style="clear:both;"></div>
		<div class="logistics_service_info_box">
			<div class="form-group">
				<div class="rows">
					<label class="label-inline">产品包装后的重量<i class="sign">*</i></label>
					<label>
						<input type="text" name="product_gross_weight" check-valid="weight" class="iv-input">&nbsp;公斤/<span class="unit">件</span>
						<p class="error-tips input-spacing product_gross_weight_tips" style="display:none;width:180px;"></p>
					</label>
					<div class="input-spacing" style="margin-left:110px;">
						<p>
							<input class="no-spacing" type="checkbox" name="isPackSell" for="pack_sell">自定义称重
						</p>
						<p show-sign="pack_sell" class="input-spacing" style="display:none">
						买家购买 <input type="text" name="baseUnit" check-valid="number" class="iv-input short-input"> <span class="product_unit">件/个</span> 以内，按单件产品重量计算运费。	
						</p>
						<p show-sign="pack_sell" class="input-spacing" style="display:none">
						在此基础上，买家每多买 <input type="text" name="addUnit" check-valid="number" class="iv-input short-input"> <span class="product_unit">件/个</span>，重量增加 <input type="text" name="addWeight" check-valid="weight" class="iv-input short-input"> kg。
						</p>
					</div>
				</div>
				<div class="rows">
					<label class="label-inline">产品包装后的尺寸<i class="sign">*</i></label> 
					<label>
						<input type="text" class="iv-input short-input" check-valid="size" name="product_length" placeholder="长（cm）">
						&nbsp;x&nbsp;
						<input type="text" class="iv-input short-input" check-valid="size" name="product_width" placeholder="宽（cm）">
						&nbsp;x&nbsp;
						<input type="text" class="iv-input short-input" check-valid="size" name="product_height" placeholder="高（cm）">
					</label>
				</div>
				<div class="rows input-spacing">
					<label class="label-inline">产品运费模板<i class="sign">*</i></label>
					<select class="iv-select" name="freight_templateid" style="min-width:170px;">
						<option value="">---请选择产品运费模板---</option>
					</select>
					<a class="iv-btn btn-success left-spacing-l sync_freight_template">同步模板</a>
					<p class="text-success input-spacing freight_tip"  style="margin-left:110px;"></p>
				</div>
				<div class="rows input-spacing">
					<label class="label-inline">服务设置<i class="sign">*</i></label>
					<select class="iv-select" name="promise_templateid" style="min-width:170px;">
						<option value="">---请选择服务设置---</option>
					</select>
					<a class="iv-btn btn-success left-spacing-l sync_promise_template">同步模板</a>
					<p class="text-success input-spacing promise_tip"  style="margin-left:110px;"></p>
				</div>
			</div>
		</div>
		<div class="ali-title">
			<a id="rest_info">其他信息</a>
		</div>
		<div class="rest_info_box">
			<label class="label-left">产品有效期</label>	
			<label>
				<input class="iv-radio left-spacing-l" type="radio" name="wsValidNum" value="14">14天
			</label>
			<label>
				<input class="iv-radio left-spacing-l" type="radio" name="wsValidNum" value="30" checked="checked">30天
			</label>
		</div>
	</form>
</div>
<div class="bottom_btn">
	<div>
		<a class="btn btn-success aliexpress_save">保存</a>
		<a class="btn btn-success aliexpress_push">发布</a>
	</div>
</div>
<?= $this->render('right_menu');?>
<?= $this->render('modal');?>