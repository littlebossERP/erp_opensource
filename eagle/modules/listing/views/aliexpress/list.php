<?php
	use eagle\modules\listing\helpers\AliexpressHelper;
	// $this->registerCssFile(\Yii::getAlias('@web').'/css/listing/aliexpress.css',['depends'=>'eagle\assets\AppAsset']);
	$this->registerJsFile(\Yii::getAlias('@web').'/js/project/listing/aliexpress.js',['depends'=>'eagle\assets\PublicAsset',]);

	$this->registerJs("$.aliexpress.initAliexpressList()",\yii\web\View::POS_READY);
?>
<?php
	echo $this->render('//layouts/new/left_menu',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>	
<style>
	.text-center {
		text-align: center;
	}
	.text-left {
		text-align: left;
	}
	.btn-together{
    	padding-top: 7px;
	    padding-left: 10px;
	    padding-right: 10px;
	    padding-bottom: 7px;
	    line-height: 1;
	    border: 0;
    	display: inline-block;
    	vertical-align: middle;
	}
	progress{
		width: 430px;
		height:15px;
	    border: 1px solid #e3e3e3;  
	    background-color:#f5f5f5;
	    color: #03bdf0; /*IE10*/
	    border-radius:3px;
	    box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);
	}
	progress::-moz-progress-bar { background: #03bdf0;border-radius:3px;box-shadow: 0 0 0 1px #03bdf0;}
	progress::-webkit-progress-bar { background: #f5f5f5;border-radius:3px;}
	progress::-webkit-progress-value  { background: #03bdf0;border-radius:3px;box-shadow: 0px 0 0 1px #03bdf0;}
</style>
<div class="form-group">
	<form method="get" action="" id="aliexpress_search" class="block">
		<div class="filter-bar">
			<span class="iconfont icon-stroe"></span>
			<select name="selleruserid" class="selleruserid iv-input" placeholder="全部速卖通店铺">
				<option value="">全部速卖通店铺</option>
				<?php if(isset($users)): ?>
					<?php foreach($users as $k => $user): ?>
					<?php if(count($users) == 1): $checked = 'selected'; else: $checked = ''; endif;?>
					<option value="<?=$user['sellerloginid']?>" <?=$checked?>><?=$user['sellerloginid']?></option>
					<?php endforeach;?>
				<?php endif;?>
			</select>
			<div class="input-group iv-input">
				<select name="select_status" class="aliexpress_search iv-input" value="">
					<option value="subject">产品标题</option>
					<option value="product_code">商品编码</option>
				</select>
				<input type="text" name="search_key" class="select_status iv-input" value=""> 
				<button type="submit" class="iv-btn btn-search">
					<span class="iconfont icon-sousuo"></span>	
				</button>
			</div>
		</div>
	</form>
	<?php if($edit_status != 1): ?>
		<div class="table-action clearfix">
			<div class="pull-left">
				<button class="iv-btn btn-primary batch_post" data-target="/listing/aliexpress/show-process">
					<span class="glyphicon glyphicon-send"></span> 批量发布
				</button>
				<button class="iv-btn btn-primary batch_del" >
					<span class="glyphicon glyphicon-trash"></span> 批量删除
				</button>
			</div>
			<?php if($edit_status == 0): ?>
				<div class="pull-right">
					<button class="iv-btn btn-warning" onclick="window.open('/listing/aliexpress/add')">新建产品</button>
				</div>
			<?php endif;?>
		</div>
	<?php endif;?>
</div>
<?php 
	$pageBar = new \render\layout\Pagebar();
	$pageBar->page = $page;
?>
<div class="block clearfix">
	<div class="pull-left">
		<?= $pageBar ?>
	</div>
</div>
<div class="form-group">
	<table class="iv-table mTop20">
		<thead>
			<tr>
				<th style="width:80px;">
					<input type="checkbox" name="check_all">
				</th>
				<th style="width:100px;">图片</th>
				<th>标题</th>
				<th style="width:150px;">分组</th>
				<th style="width:100px;">店铺</th>
				<th style="width:140px;">创建时间</th>
				<?php if($edit_status == 3):?>
					<th style="width:200px">失败原因</th>
				<?php endif;?>
				<?php if($edit_status != 1):
					$style = $product_status == 0 ? 'width:200px;' : 'width:170px;';
				?>
					<th style="<?=$style?>">操作</th>
				<?php endif;?>
			</tr>
		</thead>
		<tbody id="Aliexpress_list">
			<?php 
				if(count($productInfo)):
					foreach($productInfo as $key => $product): 
						$productDetail = $product->detail;
						$group_name = AliexpressHelper::GetProductGroupsName($product->selleruserid,explode(',',$productDetail->product_groups))
			?>
				<tr>
					<td class="text-center"><input type="checkbox" name="check_one" data-id="<?=$product->id?>"></td>
					<td class="text-center"><img src="<?=$product->photo_primary?>" qtip style="width:50px;"></td>
					<td class="text-left"><?=$product->subject?></td>
					<td class="text-center"><?=$group_name?></td>
					<td class="text-center"><?=$product->selleruserid?></td>
					<td class="text-center"><?=AliexpressHelper::getTime($product->created)?></td>
					<?php if($edit_status == 3):?>
						<td style="color:red;"><?=json_decode($product->error_message)->error_message?></td>
					<?php endif;?>
					<?php if($edit_status == 0): ?>
						<td>
							<div class="btn-group text-center" role="group" style="display:block;">
								<a class="btn btn-together btn-success" style="margin-right:3px;" href="/listing/aliexpress/copy?id=<?=$product->id?>" target="_blank">复制</a>	
								<a class="btn btn-together btn-success pushProduct" style="margin-right:3px;">发布</a>
								<a class="btn btn-together btn-success" href="/listing/aliexpress/edit?id=<?=$product->id?>&edit_status=<?=$edit_status?>" target="_blank">编辑</a>
								<a class="btn btn-together btn-success dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a class="delOne" href="#" data-id="<?=$product->id?>">删除</a></li>
								</ul>
							</div>
						</td>
					<?php endif;?>
					<?php if($edit_status == 3): ?>
						<td>
							<div class="btn-group text-center" role="group" style="display:block;">
								<a class="btn btn-together btn-success pushProduct">发布</a>
								<a class="btn btn-together btn-success" href="/listing/aliexpress/edit?id=<?=$product->id?>&edit_status=<?=$edit_status?>" target="_blank" style="margin:0 3px;">编辑</a>
								<a class="btn btn-together btn-success delOne" href="#" data-id="<?=$product->id?>">删除</a> 
							</div>
						</td>
					<?php endif;?>
				</tr>
		<?php 
				endforeach;
 			endif;
 		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="10"><?=$pageBar?></td>
			</tr>
		</tfoot>
	</table>
</div>


