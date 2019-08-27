<?php
	use eagle\modules\util\helpers\TranslateHelper;

	$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.css' );
	$this->registerCssFile(\Yii::getAlias('@web')."/css/listing/ensogo.css");
	$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
	$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends'=> ['yii\web\JqueryAsset']]);
	$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ensogo_batchImagesUploader.js", ['depends'=> ['yii\bootstrap\BootstrapPluginAsset']]);
	$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ensogo.js",['depends' => [
		'yii\web\JqueryAsset',
		'eagle\assets\PublicAsset',
		'yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
	// $this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditor.js' );
	// $this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/lang/zh_CN.js' );
	// $this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.js');







$ImageList = [];
$row['thumbnail'] = '';
$row['original'] = '';
$ImageList[] = $row;

// 图片数据 生成 
for($i=1;$i<=10;$i++){
	$row['thumbnail'] = '';
	$row['original'] = '';
	$ImageList[] = $row;
}
$this->registerJs("Ensogo.existingImages=".json_encode($ImageList).";" , \yii\web\View::POS_READY);
$this->registerJs("Ensogo.initPhotosSetting();" , \yii\web\View::POS_READY);
$this->registerJs("Ensogo.selSites=".json_encode(array_keys($sites)).";",\yii\web\View::POS_READY);
$this->registerJs("Ensogo.sites=".json_encode($sites).";",\yii\web\View::POS_READY);
$this->registerJs("Ensogo.init()",\yii\web\View::POS_READY);
?>

<?php
	echo $this->render('//layouts/new/left_menu',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>	
<style>
	.title_tips{
		border: 1px solid #9CCACF;
		height: 30px;
	}
	.title_center{
		font: 14px/30px Microsoft Yahei;
		margin-left: 30px;
		color: #666666;
	}
	.mTop5{
		margin-top: 5px;
	}
	.mTop10{
		margin-top: 10px;
	}
	.mTop20{
		margin-top: 20px;
	}
	.mTop30{
		margin-top: 30px;
	}
	.mTop35{
		margin-top: 35px;
	}
	.mTop50{
		margin-top: 50px;
	}
	.minW110{
		min-width: 110px;
	}
	.minW120{
		min-width: 120px;
	}
	.text-right{
		text-align: right;
	}
	.text-left{
		text-align: left;
	}
	input.add_image_url {
	    width: 350px;
	    margin-top: 15px;
	}
	.checkboxSpanCss {
	    border: 1px solid #ddd;
	    padding: 0px 11px;
	}
	
	.fWhite {
	    color: #FFF;
	}
	.fBlack {
	    color: #000;
	}
	.left-panel {
	    height: auto;
	    background: url('/images/wish/profile_menu_bg1.png') -23px 0 repeat-y;
	    position: fixed !important;
	    right: 10px;
	    top: 70px;
	}
	.left_panel_first {
	    background: url('/images/wish/profile_menu_bg.png') 0 -6px no-repeat;
	    margin-bottom: 55px;
	    margin-left: -15px;
	    height: 12px;
	    padding-left: 12px;
	}
	.left-panel>p {
	    margin: 50px 0 50px -15px;
	    background: url('/images/wish/profile_menu_bg.png') 0 -41px no-repeat;
	    padding-left: 16px;
	}
	.left-panel>p>a {
	    color: #333;
	    font-weight: bold;
	    cursor: pointer;
	}
	.left_panel_last {
	    background: url('/images/wish/profile_menu_bg.png') 0 -6px no-repeat;
	    margin-top: 5px;
	    margin-left: -15px;
	    height: 12px;
	    padding-left: 12px;
	}
	.bottom_btn {
	    position: fixed !important;
	    bottom: 0;
	    left: 0;
	    background-color: #F5F5F5;
	    height: 50px;
	    z-index: 999;
	}

	#Category_modal .nav{
		border: 1px solid #DDDDDD;
		border-radius: 5px;
		width: 250px;
		height: 300px;
		overflow-y: scroll;
		margin-left: 15px;
	}

	#Category_modal .nav a{
		color:#666A72;
		padding-left:0;
		padding-right:0;
		width: 100%;
	}

	#Category_modal .nav .active a{
		/*background-color: #DDDDDD;*/
		color: white;
	}
	.select_photo{
		border-color: #FE9900 !important;
    	position: relative;
	}
	.main_image_tips {
	    width: 38px;
	    height: 38px;
	    position: absolute;
	    left: 15px;
	    top: 0px;
	    background: url('/images/wish/main_image_tips.png') 0 0;
	}
	.lnk-del-img {
	    background-color: #B3B3B3 !important;
	    width: 18px;
	    height: 18px;
	    border-radius: 8px;
	    color: #EEEEEE !important;
	    opacity: 0.8;
	    filter:alpha(opacity=80);
	}
	.lnk-del-img span {
	    margin-top: -3px;
	    display: block;
	}
	.red{
		color: red;
	}
	.create_btn{
		color: blue !important;
		text-align: center !important;
		display:block;
	}
	#goodsList tr,#goodsList td{
		background-color: #f4f9fc;
	}
</style>
<div class="panel panel-default" style="border:0">
	<div class="panel-body col-xs-11 mbottom20">
		<form id="fanben-capture" name="fanben_capture" method="post"  class="block">
			<input type="hidden" name="ensogo_id" data-type="offline" value=""/>
			<div class="col-xs-12 mTop20 ensogo_product_baseinfo">
				 <div class="title_tips">
				 	<a name="ensogo_product_baseinfo"><span class="title_center">产品基本信息</span></a>
				 </div>
			</div>
			<div class="col-xs-10 ">
				<div class="form-group">
					<div class="row mTop20 ensogo_site_id_list">
						<label for="ensogo_site_id" class="col-xs-2 control-label text-right">
							<i class="red">* </i>刊登店铺:						
						</label>
						<div class="col-xs-4">	
							<select id="ensogo_site_id iv-select" name="site_id" style="margin-top:0;height:30px;">
								<?php if(count($store) > 1): ?>	
									<option value="">请选择Ensogo店铺</option>
								<?php endif;?>
								<?php if(isset($store)): ?>
									<?php foreach($store as $key => $val): ?>
											<option value="<?=$val['site_id']?>"<?php if(count($store) == 1): ?>selected<?php endif;?>><?=$val['store_name']?></option>
									<?php endforeach;?>
								<?php endif;?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-10 ">
				<div class="form-group">
					<div class="row ensogo_category">
						<label for="ensogo_category_tip" class="col-xs-2 contorl-label text-right">
							<i class="red">* </i>产品分类:
						</label>
						<div class="col-xs-10">
							<span class="ensogo_product_category">未选择分类</span>
						</div>
						<div class="col-xs-10 col-xs-offset-2 mTop10">
							<a class="btn btn-default ensogo_product_category_btn" style="width:64px;height:24px;font:12px/13px Miscrsoft Yahei;padding-left:8px;">选择分类</a>
							<!-- <a href="get-parent-category-id" target="_modal" class="btn btn-default">选择分类</a> -->
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-10">
				<div class="form-group">
					<div class="row ensogo_website_list">
						<label for="ensogo_website" class="col-xs-2 control-label text-right">
						发布站点
						</label>
						<div id="ensogo_website" class="col-xs-8">
							<span style="margin-right:10px;"><input type="checkbox" name="all_sites" value="all" style="margin-top:3px;margin-left:0px;"  checked="checked"/>全部</span>
							<?php foreach($sites as $k_s => $site): ?>
								<span style="margin-right:10px;"><input type="checkbox" name="sites" data-val="<?=$site?>" data-key="<?=$k_s?>" checked="checked"/><?=$site?></span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-10">
				<div class="form-group">
					<div class="row ensogo_sale_style_choose">
						<label for="ensogo_sale_style" class="col-xs-2 control-label text-right mTop5">
						售卖形式
						</label>
						<div id="ensogo_sale_style" class="col-xs-8">
							<span style="margin-right:30px;"><input type="radio" name="sale_choose" value="single" checked="checked">单品</span>
							<span><input type="radio" name="sale_choose" value="multi">多变体</span>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-10 ">
				<div class="form-group">	
					<div class="row product_title">
						<label for="ensogo_product_name" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>产品标题:
						</label>
						<div class="col-xs-8">
							<input class="col-xs-10 form-control" id="ensogo_product_name" type="text" name="name" value="">
						</div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row">
						<label for="ensogo_product_tag" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>产品标签:
						</label>
						<div class="col-xs-8" id="goods_tags">
							<input class="col-xs-10 form-control ui-autocomplete-input noBorder" placeholder="输入标签名，输入 回车 或 逗号 完成添加" id="ensogo_product_tags" type="text" name="tags" value="" autocomplete="off">
						</div>
						<span class="col-xs-1" style="line-height: 30px;"><i style="color:#FF9600" class="tags_num">0</i>/10</span>
					</div>
					<!-- <div class="row mTop10">
						<label for="ensogo_product_tag" class="col-xs-2 control-label text-nowrap"></label>
						<div class="col-xs-10 col-xs-offset-2 example">
							<span > 例：Women,Women Fanshion，至少填写2个以上，最多支持10个 <span>
						</div>
					</div> -->
				</div>
				
				<div class="form-group">	
					<div class="row mTop10">
						<label for="ensogo_product_mainSku" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>主SKU:
						</label>
						<div class="col-xs-4">
							<input class="col-xs-10 form-control" id="ensogo_product_parentSku" type="text" name="parent_sku" value="">
						</div>
						<div class="col-xs-4 sku_tips"></div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row mTop10">
						<label for="ensogo_product_price" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>售价:
						</label>
						<div class="col-xs-4">
							<div class="input-group">
								<input class="form-control" id="ensogo_product_price" aria-describedby="money-type" type="text" name="price" value="">
								<span class="input-group-addon" id="money-type">&#36;</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row mTop20">
						<label for="ensogo_product_sale_price" class="col-xs-2 control-label text-right">
							市场价:
							<span qtipkey="ensogo_sale_price_tip"></span>
						</label>
						<div class="col-xs-4">
							<div class="input-group">
								<input class="form-control" id="ensogo_product_sale_price" aria-describedby="money-type" type="text" name="msrp" value="">
								<span class="input-group-addon" id="money-type">&#36;</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row mTop10">
						<label for="ensogo_product_shipping" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>运费:
						</label>
						<div class="col-xs-4">
							<div class="input-group">
								<input class="form-control" id="ensogo_product_shipping" aria-describedby="money-type" type="text" name="shipping" value="">
								<span class="input-group-addon" id="money-type">&#36;</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row mTop20">
						<label for="ensogo_product_count" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>库存:
						</label>
						<div class="col-xs-4">
							<input class="col-xs-10 form-control" id="ensogo_product_count" type="text" name="inventory" value="">
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row mTop20">
						<label for="ensogo_product_shipping_time" class="col-xs-2 control-label text-right">
							<i class="red">* </i>运输时间:
						</label>
						<div class="col-xs-10" id="ensogo_product_shipping_time">
							<input class="col-xs-2" type="text" name="shipping_short_time" placeholder="最小预估数" style="border:1px solid #b9d6e8;line-height:25px;" value="15"/>
							<span class="col-xs-1" style="width:30px;padding:0 10px;margin:0;line-height:25px;">—</span>
							<input class="col-xs-2" type="text" name="shipping_long_time"  placeholder="最大预估数" style="border:1px solid #b9d6e8;line-height:25px;" value="30"/>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row mTop20">
						<label for="ensogo_product_brand" class="col-xs-2 control-label text-right mTop10">
							品牌:
						</label>
						<div class="col-xs-4">
							<input class="form-control" id="ensogo_product_brand" type="text" name="brand" value="">
						</div>
					</div>
				</div>
				<div class="form-group">	
					<div class="row mTop10">
						<label for="ensogo_product_upc" class="col-xs-2 control-label text-right">
							UPC（通用产品代码):
						</label>
						<div class="col-xs-4">
							<input class="form-control" id="ensogo_product_upc" type="text" name="upc" value="">
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row mTop10">
						<label for="ensogo_product_ladding_page_url" class="col-xs-2 control-label text-right">
							Ladding Page URL:
						</label>
						<div class="col-xs-8">
							<input class="form-control" id="ensogo_product_ladding_page_url" type="text" name="landing_page_url" value="">
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<label for="ensogo_product_description" class="col-xs-2 control-label text-right mTop10">
							<i class="red">* </i>产品描述:
						</label>
						<div class="col-xs-8">
							<textarea class="form-control iv-editor" id="ensogo_product_description"  items='<?=$items;?>' name="description" rows="6" style="width:570px;height:400px;"></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-12 mTop50 ensogo_product_image">
				<div class="title_tips" style="height:30px;">
				 	<a name="ensogo_product_image"><span class="title_center">产品图片信息</span></a>	
				</div>
			</div>
			 <!--产品图片-->
			<div class="col-xs-12">
			 	<div class="col-xs-1 text-right minW120 mTop35">
			 		<label><i style="color:red;">*</i> 产品图片:</label>
			 	</div>
			 	<div class="col-xs-10">
		 			<div role="image-uploader-container">
						<div class="col-xs-12" style="margin-bottom:20px;">
							<button type="button" class="btn btn-default col-xs-2" id="btn-uploader" style="margin-right:20px;"><span class="glyphicon glyphicon-folder-open"></span> <?= TranslateHelper::t('从我的电脑选取'); ?></button>
							<button type="button" class="btn btn-default col-xs-2" id="btn-upload-from-lib" data-toggle="modal" data-target="#addImagesBox" ><span class="glyphicon glyphicon-globe"></span> <?= TranslateHelper::t('从网络URL选取'); ?>
							</button>
							<span class="col-xs-6" style="color:red;font-size:12px;margin-top:10px;">(点击图片可选择主图,仅支持jpg,jpeg,gif,pjpeg,png格式的图片)</span>
						</div>
					</div>
			 	</div>
			</div>
			<div class="col-xs-12 mTop50 ensogo_product_variance">
				 <div class="title_tips" style="height:30px;">
				 	<a name="ensogo_product_variance"><span class="title_center">产品变参信息</span></a>
				 </div>
			</div>
			<div class="multi_list" style="display:none;">
				 <!--添加颜色-->
				<div class="col-xs-12 mTop20 color_list">
			 		<div class="col-xs-1 text-right mTop10">
			 			<label>颜色:</label> 
			 		</div>
			 		<div class="col-xs-11">
				 		<div class="goods_select" style="height:auto;">
				 			<div class="form-group" id="goodsColor" style="margin-bottom:30px;position:relative;min-height:80px;">
				 			</div>
				 		</div>
				 	</div>
				 	<div class="col-xs-offset-1 col-xs-11">
	 					<div class="input-group col-xs-3" style="margin-top:-3px;">
	 						<input type="text" class="form-control" id="otherColor" name="otherColor"  placeholder="输入您想添加的颜色">
		 					<span class="input-group-btn">	
	 							<button class="btn btn-success  colorAdd"  type="button" style="height:30px">添加</button>
	 						</span>
	 					</div>
		 			</div>
				</div>
				 <!--添加尺寸-->
				<div class="col-xs-12 mTop20 size_list">
				 	<div class="col-xs-1 text-right mTop10">
				 		<label>尺寸:</label>
				 	</div>
			 		<div class="col-xs-11 size_select " style="height:auto">
			 			<div class="form-group" id="goodsSize" style="margin-bottom:30px;position:relative;min-height:30px;">
			 				<div class="col-xs-1 mTop10 minW110 text-left">
			 					<input name="checkbox" type="checkbox" value="S"/><span>S</span>
			 				</div>
			 				<div class="col-xs-1 mTop10 minW110 text-left">
			 					<input name="checkbox" type="checkbox" value="L"/><span>L</span>
			 				</div>
			 				<div class="col-xs-1 mTop10 minW110 text-left">
			 					<input name="checkbox" type="checkbox" value="XL"/><span>XL</span>
			 				</div>
			 				<div class="col-xs-1 mTop10 minW110 text-left">
			 					<input name="checkbox" type="checkbox" value="XXL"/><span>XXL</span>
			 				</div>
			 			</div>
			 		</div>
			 		<div class="col-xs-offset-1 col-xs-11">
	 					<div class="input-group col-xs-3 text-left" style="margin-top:-3px;">
	 						<input type="text" class="form-control" id="otherSize" name="otherSize"  placeholder="输入您想添加的尺寸">
		 					<span class="input-group-btn">	
	 							<button class="btn btn-success sizeAdd"  type="button" style="height:30px">添加</button>
	 						</span>
	 					</div>
		 			</div>
				</div>
			</div>
			<!--新增产品列表-->
			<div class="col-xs-12">
				<div class="mTop10 goodsList" style="min-height:105px;margin-left:-20px;">
					<input type="hidden" name="opt_method" value="">
					<div class="form-group">
					<!-- 	<div class="col-xs-12">
							<a class="btn btn-success col-xs-1 minW120" onclick="Ensogo.AddVariance();">新增变参商品</a>
							<span class="col-xs-6" style="font-size:12px;color:red;margin-top:10px;">(* 请注意修改变参商品的SKU，防止ensogo平台发布失败)<span>
						</div> -->
						<div class="col-xs-12 mTop10">
							<table class="table table-striped">
								<thead>
									<tr class="bgColor1">
										<!-- <th class="col-xs-1" style="text-align:center;line-height:30px;">颜色</th>
										<th class="col-xs-1" style="text-align:center;line-height:30px;">尺寸</th> -->
										<th class="col-xs-2" style="text-align:center;line-height:30px;">SKU</th>
										<th class="col-xs-1" style="text-align:center">库存<a data-name="库存" class="create_btn" onclick="Ensogo.createNum('inventory')">[一键生成]</a></th>
										<th class="col-xs-2" style="text-align:center;min-width:110px;">运输时间<a data-name="运输时间" class="create_btn" onclick="Ensogo.createNum('shipping_time')">[一键生成]</a></th>
										<th class="col-xs-1" style="text-align:center;min-width:80px;line-height:30px;">站点</th>
										<th class="col-xs-1" style="text-align:center">售价($)<a data-name="售价" class="create_btn" onclick="Ensogo.createNum('price')">[一键生成]</a></th>
										<th class="col-xs-1" style="text-align:center">市场价($)<a data-name="市场价" class="create_btn" onclick="Ensogo.createNum('msrp')">[一键生成]</a></th>
										<th class="col-xs-1" style="text-align:center">运费($)<a data-name="运费" class="create_btn" onclick="Ensogo.createNum('shipping')">[一键生成]</a></th>
										<!-- <th class="col-xs-1" style="text-align:center">操作</th> -->
									</tr>
								</thead>
								<tbody id="goodsList">
									<tr name="main" style="border-top: 1px solid #CCC;">
										<!-- <td  style="text-align:center;" rowspan="7" class="rowspan"><input type="hidden" name="color" style="width:60px;"></td> -->
										<!-- <td style="text-align:center;" rowspan="7" class="rowspan"><input type="hidden" name="size" style="width:60px;"></td> -->
										<td style="text-align:center" rowspan="7" class="rowspan"><input type="text" name="sku"></td>
										<td style="text-align:center" rowspan="7" class="rowspan"><input type="text" name="inventory" style="width:80px;"></td>
										<td style='text-align:center;min-width:110px;' rowspan="7" class="rowspan">
											<input type='text' name='shipping_short_time' style='width:40px;' value="15"> - <input type='text' name='shipping_long_time' style='width:40px;' value="30">
										</td>
										<?php 
											$key = 0;
											foreach($sites as $k_s => $site): 
										?>
												<td class="site_info <?=$k_s?>" style="text-align:center;min-width:70px;" name="sites" data-site="<?=$k_s?>"><?=$site?></td>
												<td class="site_info <?=$k_s?>" style="text-align:center"><input type="text" name="price"></td>
												<td class="site_info <?=$k_s?>" style="text-align:center"><input type="text" name="msrp"></td>
												<td class="site_info <?=$k_s?>" style="text-align:center"><input type="text" name="shipping" style="width:80px;"></td>
												<?php  if($key != 1): echo '</tr><tr name="less">'; endif; ?>
										<?php endforeach;?>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div> 
		</form>
	</div>
	<div class="left-panel col-xs-1" id="floatnav">
		<div class="left_panel_first"></div>
		<p>
			<a onclick="Ensogo.goto('ensogo_product_baseinfo')" style="color: rgb(51,51,51)">基本信息</a>
		</p>
		<p>
			<a onclick="Ensogo.goto('ensogo_product_image')" style="color: rgb(51,51,51);">图片信息</a>	
		</p>
		<p>
			<a onclick="Ensogo.goto('ensogo_product_variance')" style="color: rgb(51,51,51);">变参信息</a>
		</p>
		<div href="#ensogo_product_variance" class="left_panel_last"></div>
	</div>
	<div class="col-xs-12 bottom_btn p0">
		<div class="col-xs-4 col-xs-offset-4">	
			<div class="col-xs-offset-3 col-xs-3 minW120">
				<button type="button" class="btn btn-success btn-block mTop10" onclick="Ensogo.Save(1)">保存</button>
			</div>
			<div class="col-xs-3 minW120">
				<button  type="button" class="btn btn-success btn-block mTop10" onclick="Ensogo.Save(2)">保存并发布</button>
			</div>
		</div>
	</div>
	<div class="modal fade bs-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" id="common_modal">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="hide();"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
					<button type="button" class="btn btn-primary" onclick="hide();">确定</button>
				</div>
			</div>
		</div>
	</div> 
</div>


