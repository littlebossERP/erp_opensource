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

function option(Array $options=[],$val=''){
	$opt = [];
	foreach($options as $value=>$label){
		$selected = $val == $value ? 'selected="selected"':'';
		$opt[] = "<option value='$value' {$selected} >{$label}</option>";
	}
	return implode(PHP_EOL,$opt);
}

?>
<form method="get" action="" class="block">
	<span class="iconfont icon-stroe"></span>
	<select redirect-location name="site_id" class="iv-input" placeholder="请选择店铺">
		<?php 
		$opt = [];
		foreach($accounts as $account){
			$opt[$account->site_id] = $account->store_name;
		}
		echo option($opt,$request->site_id);
		?>
	</select>
	<div class="input-group iv-input">
		<select name="search_type" class="iv-input">
			<?= option(
				[
					'name'=>'标题',
					'parent_sku'=>'SKU'
				],$request->search_type
			) ?>
		</select>
		<input name="search_value" type="text" class="iv-input" placeholder="" value="<?=$request->search_value ?>" />
		<button type="submit" class="iv-btn btn-search">
			<span class="iconfont icon-sousuo"></span>
		</button>
	</div>
</form>

<div class="block table-action clearfix">
    <div class="pull-left">
        <button class="iv-btn btn-primary" id="multi-xiajia" data-enabled="disable">
        	<span class="iconfont icon-xiajia"></span> 批量下架
        </button>
        <button class="iv-btn btn-primary" id="multi-xiugai" data-menu="<?= $menu_type ?>" onclick="$.overLay(1)">
        	<span class="iconfont icon-xiugai"></span> 批量修改
        </button>
    </div>
    <div class="pull-right">
        <a id="sync-btn" href="sync-product-ready" target="_modal" title="同步商品" class="iv-btn btn-important" auto-size style="color:white;">同步商品</a>
    </div>
</div>

<div class="block">
	<div class="iv-alert alert-remind" style="margin-bottom:10px;">
		由于ensogo需要从其他平台读取图片，搬家商品图片一段时间不会显示。<br />
由于上传商品后ensogo需要对商品进行审核，可能出现小老板后台商品数量与ensogo后台店铺商品数量不一致的情况。
	</div>
<?php
echo $this->render('_main_data_table',[
	'accounts' 	=> $accounts,
	'data' 		=> $products,
	'sort' 		=> 1,
	'page' 		=> $page,
	'request' 	=> $request,
	'menu_type' => $menu_type
]);
?>
	
</div>


