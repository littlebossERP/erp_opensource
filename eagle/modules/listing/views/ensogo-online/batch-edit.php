<?php
$this->title="在线商品";
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ensogo.js",['depends' => [
	'yii\web\JqueryAsset',
	'eagle\assets\PublicAsset',
	'yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset'
]]);
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ensogo_online.js',['depends' => [
    'yii\web\JqueryAsset',
    'yii\bootstrap\BootstrapAsset',
    'eagle\assets\PublicAsset'
]]);
echo $this->render('//layouts/new/left_menu',[
	'menu'=>$menu,
	'active'=>$active
]);
?>
<style>

	input::-webkit-input-placeholder { /* WebKit browsers */
	  padding-left:5px;
	}
	input:-moz-placeholder { /* Mozilla Firefox 4 to 18 */
	  padding-left:5px;
	}
	input::-moz-placeholder { /* Mozilla Firefox 19+ */
	  padding-left:5px;
	}
	input:-ms-input-placeholder { /* Internet Explorer 10+ */
	  padding-left:5px;
	}
	
	.iv-select{
		padding: 3px 5px;
		border-radius: 3px;
		border: 1px solid #cdced0;

	}
	.iv-select:hover{
	    border-color: #666666;
	}

	.radio-box {
		height:20px;
		position:relative;
		top: 3px;
	}
	.radio-box input{
		opacity: 0;
		filter: alpha(opacity=0);
	}

	
	.radio-checked{
		padding:5px;
		border-radius: 12px;
		border: 1px solid #FF9B00;
		display: inline-block;
		height:10px;
		width: 10px;
		position: absolute;
		top:4px;
		margin-right: 5px;
	}
	.radio-checked i{
		padding: 1px 2px;
		display:inline-block;
		height: 6px;
		width: 6px;
		background-color: #FF9B00;
		border-radius:5px;
		position: absolute;
		top: 20%;
		left: 20%;
	}
	.radio {
		padding: 5px;
		border-radius: 12px;
		border: 1px solid #CCC;
		display: inline-block;
		height: 10px;
		width: 10px;
		position: absolute;
		top: -6px;
	}
	.btn {
		font-size: 12px;
		width: 120px;
	}
	.btn-success,.btn-warning{
		color:#fff !important;
	}
	.btn-background{
		color: #696969;
	}
	#ensogo_product_table td{
		text-align:  center;
		background-color: white;
	}
	.nav-tabs > li > a{
		color: #9B9B9B;
	}
	.nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus {
		color: #9B9B9B;
		border: 1px solid #D2D2D4;
		border-bottom-color: transparent;
	}
	.disabled {
		background-color: #EEEEEE ;
	}
	.modify-modal{
		margin: 100px 30px 100px 75px;
	}
</style>
<!-- 
<div>
	<span class="radio-checked"><span></span></span>
	<span class="radio"></span>
</div> -->
<div class="table-action clearfix">
	<div style="font:bold 15px/20px Microsoft Yahei;color:black;margin-bottom:20px;">批量修改</div>
    <div class="pull-left">
        <a href="#" class="btn btn-success batch-edit" target="#batch-edit-modal" title="批量修改" onclick="$.overLay(1)">批量修改</a>
        <a href="#" class="btn btn-success" onclick="location.href='/listing/ensogo-online/online-product-list?site_id=<?=$site_id ?>'">返回在线商品</a>
        <a href="#" class="btn btn-success" onclick="window.location.reload();">还原</a>
    </div>
    <div class="pull-right">
    	<a href="#" class="btn btn-warning form-ensure" style="margin:0;"  data-href="/listing/ensogo-online/<?=$menu_type?>-product-list?site_id=<?=$site_id?>">提交</a>
    </div>
</div>
<div class="iv-alert alert-remind" style="margin-bottom:10px;">
	此表格以变体为单位显示,如果商品含有多个变体将被拆分为多行.
</div>
<table id="ensogo_product_table" class="iv-table table-default mTop20" data-site_id = <?=$site_id?>>
	<thead>
		<tr>
			<th style="width:65px;">图片</th>
			<th style="width:10%;">多属性</th>
			<th style="width:40%;">标题</th>
			<th style="width:20%;">SKU</th>
			<th style="width:100px;">运输时间</th>
			<th style="width:75px;">库存</th>
			<th style="width:80px;">操作</th>
		</tr>
	</thead>
	<tbody id="online_product_list">
		<?php foreach($data as $product): ?>
			<?php foreach($product['variance'] as $variance): ?>
			<?php 
				$attrs = '无';
				if(!empty($variance['color'])) $attrs = $variance['color'];
				if(!empty($variance['size'])){
					if(!empty($attrs))  
						$attrs .= '-'.$variance['size'];	
					else      
						$attrs = $variance['size'];
				}
			?>
			<tr name="main" class="variance_<?=$variance['sku']?>" data-parentSku= "<?=$product['parent_sku']?>" data-product_id="<?=$product['product_id']?>">
				<td><img src="<?=$product['main_image']?>" width="60" height="60"/></td>
				<td><?= $attrs ?></td>
				<td><?= $product['name'] ?></td>
				<td><?= $variance['sku']?></td>
				<td><?= $variance['shipping_time']?></td>
				<td><?= $variance['inventory']?></td>
				<td rowspan="2">
					<a class="btn btn-icon" style="width:60px;height:22px;font:12px/10px Microsoft Yahei;" onclick="$('tr.variance_<?=$variance['sku']?>').remove()">移除</a>
				</td>
			</tr>
			<tr name="less" class="variance_<?=$variance['sku']?>">
				<td colspan="6">
					<div style="margin-left:80px;margin-right:170px;text-align:left;">
						<label style="float:left;margin-right:20px;">售价/市场价/运费($):</label>
						<?php foreach($variance['sites'] as $site): ?>
								<div style="border:1px solid #CCC;margin-right:10px;height:24px;float:left;margin-bottom:10px;">
									<span class="site" style="border-right:1px solid #CCC;height:100%;padding:0 5px;">
										<?= \eagle\modules\listing\config\params::$ensogo_sites[$site['country_code']]?>
									</span>
									<span class="site_info" style="margin:0 5px;" data-price="<?=$site['price']?>" data-msrp="<?=$site['msrp']?>" data-shipping="<?=$site['shipping']?>" data-site="<?=$site['country_code']?>">
										<?=$site['price']?>/<?=$site['msrp']?>/<?=$site['shipping']?>
									</span>
								</div>
						<?php endforeach;?>
					</div>
				</td>
			</tr>
			<?php endforeach;?>
		<?php endforeach;?>
	</tbody>
</table>
<?= $this->render('edit-modal')?>