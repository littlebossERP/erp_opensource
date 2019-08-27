<?php 
if(!function_exists('option')){
	function option(Array $options=[],$val=''){
		$opt = [];
		foreach($options as $value=>$label){
			$selected = $val == $value ? 'selected="selected"':'';
			$opt[] = "<option value='$value' {$selected} >{$label}</option>";
		}
		return implode(PHP_EOL,$opt);
	}
}

function ensogoLink($product){
	$links = [
		'hk'=>'http://www.beecrazy.hk/',
		'id'=>'http://www.ensogo.co.id/',
		'my'=>'http://www.ensogo.com.my/',
		'ph'=>'http://www.ensogo.com.ph/',
		'sg'=>'http://www.deal.com.sg/',
		'th'=>'http://www.ensogo.co.th/'
	];
	$regex = '/[^\w]+/i';
	$href = '#';
	$variant = $product->variant[0];
	$country_code = explode('|',$variant->countries()->country_code)[0];
	if(isset($links[$country_code])){
		$href = $links[$country_code].'deals/'.strtolower(preg_replace($regex, '-', $product->name));
	}
	return $href;
}
?>

<table class="iv-table" id="main-table">
	<thead>
		<tr>
			<th>
			</th>
			<th><input type="checkbox" name="" check-all="products"></th>
			<th>缩略图</th>
			<th>标题</th>
			<th>sku</th>
			<th width="80">销量</th>
			<th width="80">收藏</th>
			<th width="110">ensogo店铺</th>
			<!-- <th style="min-width:60px;">商品状态</th> -->
			<th width="60">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php 
		$data = array_filter($data,function($var){
			return !empty($var); 
		});
		foreach($data as $product): 
			$editLink = 'edit?parent_sku='.$product->parent_sku.'&site_id='.$product->site_id.'&menu_type='.$menu_type;
			$enabledLink = 'edit?parent_sku='.$product->parent_sku;
			$checkAllKey = 'variance_'.$product->id.'_'.uniqid();
			$ivStatusKey = "ensogo-product-{$product->parent_sku}";
			$lb_status_view = [
				7=>'待审核',
				8=>'审核通过',
				9=>'审核失败'
			];
			$online_status = [
				0=>'审核中',
				1=>'审核通过'
			];
			if($product->is_enable < 3){
				$attr = 'data-disable';
				$icon = 'icon-xiajia';
				$text = '下架';
			}else{
				$attr = 'data-enable';
				$icon = 'icon-shangjia';
				$text = '上架';
			}
			$param = json_encode([
				'type'=>'product',
				'parent_sku'=>$product->parent_sku
			]);
		?>
		<tr>
			<td>
				<?php if(count($product->variant)): ?>
				<span status-hide="<?= $ivStatusKey ?>" class="glyphicon glyphicon-plus" onclick="$.ivStatus('<?= $ivStatusKey ?>',true)" ></span>
				<span status-show="<?= $ivStatusKey ?>" class="glyphicon glyphicon-minus" onclick="$.ivStatus('<?= $ivStatusKey ?>',false)" ></span>
				<?php endif; ?>
			</td>
			<td style="text-align:center;"><input type="checkbox" name="products[]" data-check="products" check-all="<?=$checkAllKey ?>"></td>
			<td>
				<img src="<?= $product->main_image ?>" class="table_thumb_img" />
			</td>
			<td>
				<?php  
				if($menu_type=='online'):
				?>
				<a href="<?= ensogoLink($product); ?>" target="_blank">
					<?= $product->name ?>
				</a>
				<?php else:
				echo $product->name;
				endif; ?>
			</td>
			<td><?= $product->parent_sku ?></td>
			<td><?= $product->number_saves ?></td>
			<td><?= $product->number_sold ?></td>
			<td>
				<?php 
				foreach($accounts as $account){
					if($account->site_id == $product->site_id){
						echo $account->store_name;
						break;
					}
				}
				?>
			</td>
			<!-- <td><?= $online_status[$product->blocked]; ?></td> -->
			<td>
				<div class="action-group">
					<a href="<?= $editLink ?>" class="iconfont icon-xiugai" title="编辑"></a>
					<a href="#" <?= $attr ?> data-params='<?= $param ?>' class="iconfont <?= $icon ?>" title="<?= $text ?>"></a>
				</div>
			</td>
		</tr>
		<?php 
		if(count($product->variant)):
		foreach($product->variant as $variance): 
			$param = json_encode([
				'type'=>'variants',
				'parent_sku'=>$variance->parent_sku,
				'pvid'=>$variance->variance_product_id
			]);
			if($variance->enable=='Y'){
				$attr = 'data-disable';
				$icon = 'icon-xiajia';
				$text = '下架';
			}else{
				$attr = 'data-enable';
				$icon = 'icon-shangjia';
				$text = '上架';
			}
		?>
		<tr status-class="['tr_hide','tr_show']" status="<?= $ivStatusKey ?>">
			<td></td>
			<td style="text-align:center;">
				<input type="checkbox" name="variance[]" data-check="<?= $checkAllKey ?>" check-name="variance" data-sku="<?= $variance->sku ?>"/>
			</td>
			<td colspan="6" style="text-align:center;">
				<span style="margin:0 20px;">颜色：<?= $variance->color ?></span>
				<span style="margin-left:20px;">尺码：<?= $variance->size ?></span>
				<span style="margin-left:20px;">SKU：<?= $variance->sku ?></span>
				<span style="margin-left:20px;">售价：$<?= $variance->price ?></span>
				<span style="margin-left:20px;">库存：<?= $variance->inventory ?></span>
				<span style="margin-left:20px;">运费：<?= $variance->shipping ?></span>
				<span style="margin-left:20px;">运输时间：<?= $variance->shipping_time ?></span>
			</td>
			<td>
				<div class="action-group">
					<a href="<?= $editLink ?>" class="iconfont icon-xiugai" title="编辑"></a>
					<a href="#" <?= $attr ?> data-params='<?= $param ?>' class="iconfont <?= $icon ?>" title="<?= $text ?>"></a>
				</div>
			</td>
		</tr>
		<?php 
		endforeach;
		endif;
		endforeach; 
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="9">
				<?php 
				$pageBar = new \render\layout\Pagebar();
				$pageBar->page = $page;   // $page是现有的page类实例
				echo $pageBar;
				?>
			</td>
		</tr>
	</tfoot>
</table>