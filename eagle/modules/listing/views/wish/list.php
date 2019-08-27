<?php
	use yii\helpers\Url;
	use eagle\modules\util\helpers\TranslateHelper;
	use yii\data\Sort;
	use yii\helpers\Html;
	$this->registerCssFile(\Yii::getAlias('@web') . '/css/listing/wish_list.css');
	$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/wish_list.js',['depends' => ['yii\web\JqueryAsset','yii\bootstrap\BootstrapAsset']]);
	$sort = new Sort(['attributes' => ['status','id','parent_sku','sku','name','tags','colorsize','price','shipping','inventory','site_id','enable','error_message','create_time','update_time']]);
	foreach($WishListingData['data'] as $key => $val){
		$wishdata[$key]['wish_product_fanben_id'] = $val['id'];
		$wishdata[$key]['wish_product_image'] = $val['main_image'];
		$wishdata[$key]['wish_product_name'] = addslashes($val['name']);
		$wishdata[$key]['wish_product_sku'] = $val['parent_sku'];
		$wishdata[$key]['wish_product_price'] = $val['price'];
		$wishdata[$key]['wish_product_create_time'] = $val['create_time'];
		$wishdata[$key]['wish_product_store_name'] = $val['store_name'];
		$wishdata[$key]['wish_product_fanben_id'] = $val['id'];
		$wishdata[$key]['wish_product_site_id'] = $val['site_id'];
		$wishdata[$key]['wish_error_message'] = $val['error_message'];
		$wishdata[$key]['wish_product_count'] =0;
		foreach($val['variance_data'] as $k => $v){
			$wishdata[$key]['wish_product_count'] += $v['inventory'];
		}
	}
	// var_dump(count($store));
// var_dump($WishListingData);
// var_dump($WishListingData['data']);die;
// var_dump($wishdata);die;
	$active = isset($active)? $active : ($lb_status == 1 ? '待发布':'刊登失败');
	$menu = isset($menu)?$menu:[
		'刊登管理'=>[
			'icon'=>'icon-shezhi',
			'items'=>[
				'待发布'=>[
					'url'=>'/listing/wish/wish-list',
					// 'tabbar'=>81,
					// 'qtipkey'=>'',
				],
				'刊登失败'=>[
					'url'=>'/listing/wish/wish-list?type=2&lb_status=4',
					'tabbar' => $WishListCount,
				],
			]
		],
		'商品列表'=>[
            'icon'=>'icon-pingtairizhi',
            'items'=>[
                'Wish平台商品'=>[
                    'url'=>'/listing/wish-online/wish-product-list',
                ],
            ]
        ],
	];
	echo $this->render('//layouts/new/left_menu_2',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>	
 
<div class="form-group">
	<form method="get" action="" id="wish_site_search" class="block">
		<input type="hidden" name="type" value="<?=$type?>">
		<input type="hidden" name="lb_status" value="<?=$lb_status?>">
		<div class="filter-bar">
			<span class="iconfont icon-stroe"></span>
			<select name="site_id" class="wish_site_id iv-input" name="site_id" placeholder="全部wish店铺">
				<option value="">全部wish店铺</option>
				<?php foreach($store as $k => $v): ?>
							<option value="<?=$v['site_id']?>" <?php if(isset($site_id)):?><?php if($site_id == $v['site_id']):?>selected<?php endif;?><?php endif;?>><?=$v['store_name']?></option>
				<?php endforeach;?>
			</select>
			<div class="input-group iv-input">
				<select name="select_status" class="wish_search iv-input" value="">
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
		<?php if($type!=3): ?>
			<div class="pull-left">
				<!-- <a href="#" onclick="batchPost();"><span class="iconfont icon-fabu"></span> 批量发布</a> -->
				<!-- <a href="#" onclick="batchDel();"><span class="iconfont icon-shanchu"></span> 批量删除</a> -->
				<button class="btn btn-info wish-list-btn"  onclick="batchPost();"><span class="glyphicon glyphicon-send"></span> 批量发布</button>
				<button class="btn btn-info wish-list-btn" onclick="batchDel();"><span class="glyphicon glyphicon-trash"></span> 批量删除</button>

			</div>
		<?php 
		endif;
		if(isset($lb_status) && $lb_status == '1' && $type!=3):
		?>
			<!-- 	<div class="col-xs-2 col-xs-offset-6">
					<button class="col-xs-offset-4 col-xs-7 btn btn-warning" onclick="window.location.href='/listing/wish/fan-ben-capture'"><span class="glyphicon glyphicon-plus"></span> 新建产品</button>
				</div> -->
			<div class="pull-right">
				<!-- <button class="iv-btn btn-important btn-spacing-middle" onclick="window.location.href='/listing/wish/fan-ben-capture'">新建产品</button> -->
				<button class="btn btn-warning"  onclick="window.location.href='/listing/wish/fan-ben-capture'">新建产品</button>
			</div>
		<?php 
		endif;
		?>
	</div>
	<?php endif;?>
</div>
<div class="form-group">
	<table class="iv-table table-default mTop20">
		<thead>
			<tr>
				<?php if($type!=3): ?>
				<th style="width:20px;">
					<input type="checkbox" name="chk_wish_fanben_all">
				</th>
				<?php endif; ?>
				<th><?=TranslateHelper::t('图片');?></th>
				<th style="width:280px;"><?=$sort->link('name',['label'=>TranslateHelper::t('标题')]);?></th>
				<th><?=$sort->link('parent_sku',['label'=>TranslateHelper::t('SKU')]) ?></th>
				<th><?=$sort->link('price',['label'=>TranslateHelper::t('价格')])?></th>
				<th><?=TranslateHelper::t('库存')?></th>
				<th><?=TranslateHelper::t('wish店铺');?></th>
				<th><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')])?></th>
				<?php if($lb_status == '4'): ?>
					<th><?=TranslateHelper::t('错误信息');?></th>
				<?php endif; ?>
				<?php if(isset($lb_status) && $type!=3): ?>
					<?php if($lb_status !='2'): ?>
						<th><?=TranslateHelper::t('操作');?></th>
					<?php endif;?>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody id="wish_list">
			<?php if(isset($wishdata)): ?>
				<?php foreach($wishdata as $n => $vo): ?>
					<tr>
						<?php if($type!=3): ?>
						<td>
							<input type="checkbox" name="fanben_id" class="fanben_id" value="<?=$vo['wish_product_fanben_id']?>">
						</td>
						<?php endif; ?>
						<td><img src="<?=$vo['wish_product_image']?>" name="wish_product_image" width="50"></td>
						<td><?=$vo['wish_product_name']?></td>
						<td><?=$vo['wish_product_sku']?></td>
						<td><?=$vo['wish_product_price']?></td>
						<td><?=$vo['wish_product_count']?></td>
						<td><?=$vo['wish_product_store_name']?></td>
						<td><?=$vo['wish_product_create_time']?></td>
						<?php if($lb_status == '4'): ?>
							<td><?=$vo['wish_error_message']?></td>
						<?php endif;?>
						<?php if(isset($lb_status) && $type!=3):?>
							<?php if($lb_status != '2'):?>
								<td class="wish_offline_operation">
									<span class="iconfont icon-fuzhi" style="margin-left:10px;" onclick="location.href='/listing/wish/copy-fan-ben?id=<?=$vo['wish_product_fanben_id']?>&type=<?=$type?>&lb_status=<?=$lb_status?>'" title="复制商品"></span>
									<span class="iconfont icon-fabu" style="margin-left:10px;" onclick="sendfanben(this)" title="发布商品"></span>
									<span class="iconfont icon-xiugai" style="margin-left:10px;" onclick="location.href='/listing/wish/fan-ben-edit?id=<?=$vo['wish_product_fanben_id']?>&type=<?=$type?>&lb_status=<?=$lb_status?>'" title="编辑商品"></span>
									<?php if($lb_status != '4'):?>
										<span class="iconfont icon-shanchu" style="margin-left:10px;" onclick="delfanben(this)" title="删除商品"></span>
										
									<?php endif;?>
									<input type="hidden" name="wish_product_site_id"  value="<?=$vo['wish_product_site_id']?>"/>	
								</td>
							<?php endif;?>
						<?php endif;?>	
					</tr>
				<?php endforeach;?>
			<?php endif;?>
		</tbody>
		<?php if(! empty($WishListingData['pagination'])):?>
			<tfoot>
	            <tr>
	                <td colspan = "10">
	                    <?php 
	                        $pageBar = new \render\layout\Pagebar();
	                        $pageBar->page = $WishListingData['pagination'];
	                        echo $pageBar;
	                    ?>
	                </td>
	            </tr>
	        </tfoot>
		<?php endif;?>
	</table>
</div>



