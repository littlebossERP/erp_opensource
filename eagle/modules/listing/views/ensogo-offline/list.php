<?php
	use yii\helpers\Url;
	use eagle\modules\util\helpers\TranslateHelper;
	use yii\data\Sort;
	use yii\helpers\Html;

	// $this->registerCssFile(\Yii::getAlias('@web') . '/css/listing/ensgo.css');
	$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ensogo.js',['depends' => ['yii\web\JqueryAsset','eagle\assets\PublicAsset','yii\bootstrap\BootstrapAsset']]);
	$sort = new Sort(['attributes' => ['status','id','parent_sku','sku','name','tags','colorsize','price','shipping','inventory','site_id','enable','error_message','create_time','update_time']]);
	if(isset($EnsogoListingData['data'])){
		foreach($EnsogoListingData['data'] as $key => $val){
			$Ensogodata[$key]['Ensogo_product_id'] = $val['id'];
			$Ensogodata[$key]['Ensogo_product_image'] = $val['main_image'];
			$Ensogodata[$key]['Ensogo_product_name'] = $val['name'];
			$Ensogodata[$key]['Ensogo_product_sku'] = $val['parent_sku'];
			$Ensogodata[$key]['Ensogo_show_product_sku'] = $val['show_parent_sku'];
			// $Ensogodata[$key]['Ensogo_product_price'] = $val['price'];
			$Ensogodata[$key]['Ensogo_product_create_time'] = $val['create_time'];
			$Ensogodata[$key]['Ensogo_product_store_name'] = $val['store_name'];
			// $Ensogodata[$key]['Ensogo_product_fanben_id'] = $val['id'];
			$Ensogodata[$key]['Ensogo_product_site_id'] = $val['site_id'];
			// $Ensogodata[$key]['Ensogo_product_count'] =0;
			$Ensogodata[$key]['Ensogo_error_message'] = $val['error_message'];
			// foreach($val['variance_data'] as $k => $v){
			// 	$Ensogodata[$key]['Ensogo_product_count'] += $v['inventory'];
			// }
		}
	}

	$this->registerJs("Ensogo.ListInit()",\yii\web\View::POS_READY);
?>
<?php
	echo $this->render('//layouts/new/left_menu',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>	

<div class="form-group">
	<form method="get" action="" id="Ensogo_site_search" class="block">
		<input type="hidden" name="lb_status" value="<?=$lb_status?>">
		<div class="filter-bar">
			<span class="iconfont icon-stroe"></span>
			<select name="site_id" class="Ensogo_site_id iv-input" name="site_id" placeholder="全部Ensogo店铺">
				<option value="">全部Ensogo店铺</option>
				<?php if(isset($store)): ?>
					<?php foreach($store as $k => $v): ?>
					<option value="<?=$v['site_id']?>"<?php if(isset($site_id)):?><?php if($v['site_id'] == $site_id): ?>selected<?php endif;?><?php endif;?>><?=$v['store_name']?></option>
					<?php endforeach;?>
				<?php endif;?>
			</select>
			<div class="input-group iv-input">
				<select name="select_status" class="Ensogo_search iv-input" value="">
					<option value="parent_sku" <?php if(isset($select_status)):?><?php if($select_status == 'parent_sku'):?>selected <?php endif;?><?php endif;?>>SKU</option>
					<option value="name" <?php if(isset($select_status)):?><?php if($select_status == 'name'):?>selected <?php endif;?><?php endif;?>>标题</option>
				</select>
				<input type="text" name="search_key" class="select_status iv-input" value="<?php if(isset($search_key)):?><?=$search_key?><?php endif;?>"> 
				<button type="submit" class="iv-btn btn-search">
					<span class="iconfont icon-sousuo"></span>	
				</button>
			</div>
		</div>
	</form>
	<?php if($lb_status != '2'):?>
	<div class="table-action clearfix">
			<div class="pull-left">
				<!-- <a href="#" onclick="batchPost();"><span class="iconfont icon-fabu"></span> 批量发布</a> -->
				<!-- <a href="#" onclick="batchDel();"><span class="iconfont icon-shanchu"></span> 批量删除</a> -->
			<button class="iv-btn btn-primary Ensogo-list-btn batch_post"><span class="glyphicon glyphicon-send"></span> 批量发布</button>
			<button class="iv-btn btn-primary Ensogo-list-btn batch_del" ><span class="glyphicon glyphicon-trash"></span> 批量删除</button>

			</div>
		<?php if(isset($lb_status)):?>
			<?php if($lb_status == '1'):?>
			<!-- 	<div class="col-xs-2 col-xs-offset-6">
					<button class="col-xs-offset-4 col-xs-7 btn btn-warning" onclick="window.location.href='/listing/Ensogo/fan-ben-capture'"><span class="glyphicon glyphicon-plus"></span> 新建产品</button>
				</div> -->
				<div class="pull-right">
					<!-- <button class="iv-btn btn-important btn-spacing-middle" onclick="window.location.href='/listing/Ensogo/fan-ben-capture'">新建产品</button> -->
					<button class="iv-btn btn-warning"  onclick="window.location.href='/listing/ensogo-offline/add'">新建产品</button>
				</div>
			<?php endif;?>
		<?php endif;?>
	</div>
	<?php endif;?>
</div>
<div class="form-group">
	<table class="iv-table mTop20">
		<thead>
			<tr>
				<th>
					<input type="checkbox" name="chk_ensogo_fanben_all">
				</th>
				<th><?=TranslateHelper::t('图片');?></th>
				<th style="width:280px;"><?=$sort->link('name',['label'=>TranslateHelper::t('标题')]);?></th>
				<th><?=$sort->link('parent_sku',['label'=>TranslateHelper::t('SKU')]) ?></th>
				<!-- <th><?=$sort->link('price',['label'=>TranslateHelper::t('价格')])?></th>
				<th><?=TranslateHelper::t('库存')?></th> -->
				<th><?=TranslateHelper::t('Ensogo店铺');?></th>
				<th><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')])?></th>
				<?php if($lb_status == 4): ?>
					<th><?=TransLateHelper::t('失败原因')?></th>
				<?php endif;?>
				<?php if(isset($lb_status)): ?>
					<?php if($lb_status !='2'): ?>
						<th style="min-width:110px;"><?=TranslateHelper::t('操作');?></th>
					<?php endif;?>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody id="Ensogo_list">
			<?php if(isset($Ensogodata)): ?>
				<?php foreach($Ensogodata as $n => $vo): ?>

					<tr>
						<td data-id="<?=$vo['Ensogo_product_id']?>" data-sku="<?=$vo['Ensogo_product_sku']?>" data-site_id = "<?=$vo['Ensogo_product_site_id']?>">
							<input type="checkbox" name="product_sku" class="product_sku" value="<?=$vo['Ensogo_product_sku']?>">
							<!-- <input type="hidden" name="product_id" class="product_id" value="<?=$vo['Ensogo_product_id']?>"> -->
						</td>
						<td><img src="<?=$vo['Ensogo_product_image']?>" name="Ensogo_product_image" width="50"></td>
						<td><?=$vo['Ensogo_product_name']?></td>
						<td><?=$vo['Ensogo_show_product_sku']?></td>
						<td><?=$vo['Ensogo_product_store_name']?></td>
						<td><?=$vo['Ensogo_product_create_time']?></td>
						<?php if($lb_status == 4): ?>
							<td><?=$vo['Ensogo_error_message']?></td>
						<?php endif;?>
						<?php if(isset($lb_status)):?>
							<?php if($lb_status != '2'):?>
								<td class="Ensogo_offline_operation">
									<span class="iconfont icon-fabu post_product" style="margin-left:10px;cursor:pointer;cursor:pointer;"title="发布商品" data-id="<?=$vo['Ensogo_product_id']?>" data-sku="<?=$vo['Ensogo_product_sku']?>" data-site_id = "<?=$vo['Ensogo_product_site_id']?>"></span>
									<span class="iconfont icon-xiugai" style="margin-left:10px;cursor:pointer;" onclick="location.href='/listing/ensogo-offline/edit?product_id=<?=$vo['Ensogo_product_id']?>&parent_sku=<?=$vo['Ensogo_product_sku']?>&lb_status=<?=$lb_status?>&site_id=<?=$vo['Ensogo_product_site_id']?>'" title="编辑商品"></span>
									<span class="iconfont icon-shanchu del_product" style="margin-left:10px;cursor:pointer;" title="删除商品" data-id="<?=$vo['Ensogo_product_id']?>" data-sku="<?=$vo['Ensogo_product_sku']?>" data-site_id = "<?=$vo['Ensogo_product_site_id']?>" ></span>
									<input type="hidden" name="Ensogo_product_site_id"  value="<?=$vo['Ensogo_product_site_id']?>"/>	
								</td>
							<?php endif;?>
						<?php endif;?>	
					</tr>
				<?php endforeach;?>
			<?php endif;?>
		</tbody>
		<?php if(! empty($EnsogoListingData['pagination'])):?>
			<tfoot>
				<tr>
					<td colspan="10">
						<?php 
						$pageBar = new \render\layout\Pagebar();
						$pageBar->page = $EnsogoListingData['pagination'];   // $page是现有的page类实例
						echo $pageBar;
						?>
					</td>
				</tr>
			</tfoot>
		<?php endif;?>
	</table>
</div>


