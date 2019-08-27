<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\util\models\TranslateCache;
use eagle\helpers\UserHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';

$this->registerJsFile($baseUrl."js/project/catalog/online_product_list.js?version=1.1", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/create_product.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/order/orderCommon.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("online_product.list.init();" , \yii\web\View::POS_READY);

//使用popover实现图片放大功能
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

?>
<style>
.popover{
	min-width: 200px;
	min-height: 200px;
	max-width: inherit;
    max-height: inherit;
}
.service_a{
	margin: 0 20px 5px 0;
	line-height: 16px;
	display: inline-block;
}
.div_choose{
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_choose > span{
	font-size: 15px; 
	line-height: 24px;
	float:left;
}
#carrier-list-table th{
	text-align: center !important;
	white-space: nowrap !important;;
}
#carrier-list-table td , #carrier-list-table th{
	padding: 4px !important;
	border: 1px solid rgb(202,202,202) !important;;
	vertical-align: middle !important;
	word-break:break-word !important;;
}
#carrier-list-table tr:hover {
	background-color: #afd9ff !important;
}
span.badge.badge_orange{
	background:rgb(255,153,0);
/* 	border-radius:8px; */
 	color:#fff;
}

span.badge.badge_grey{
	background:#999;
	color:#fff;
}
.center{
	text-align: center;
}

</style>

<?php 
echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$menu_active,
]);
?>
<form class="form-inline" id="searchForm" name="form1" action="" method="post">
	<div class="div-input-group" style="margin-left:20px; margin-bottom:10px;">
	  	<SELECT name="matching_searchval_type" class="eagle-form-control" style="width:80px;margin:0px;">
	  		<OPTION value="sku" <?= (empty($search_con['matching_searchval_type']) || $search_con['matching_searchval_type'] == 'sku') ? "selected":'';?>>SKU</OPTION>
	  		<?php
				foreach ($personality_info['serach'] as $key => $val){
					$html = "<OPTION value='".$key."' ";
					if(!empty($search_con['matching_searchval_type']) && $search_con['matching_searchval_type'] == $key)
						$html .= 'selected';
					$html .= ">".$val."</OPTION>";
					echo $html;
				}?>
	  	</SELECT>
	  	<input name="matching_searchval" class="eagle-form-control" type="text" value="<?= (empty($search_con['matching_searchval'])?"":$search_con['matching_searchval']);?>"
	    	style="width:200px;margin:0px 30px 0px 5px;height:28px;"/>
		<button class="iv-btn btn-search btn-spacing-middle" onclick="online_product.list.searchButtonClick()">搜索</button>
	</div>

	<div style="border: 1px solid #ccc; float: left; margin-bottom: 10px; width: 100%; ">
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; ">店铺账号：</span>
		    <div style="width: 92%; float: left;">
			    <div display: inline-block;'>
				<?php
					echo "<a class='service_a ".(empty($search_con['selleruserid']) ? 'text-rever-important' : '')."' value='' onclick='online_product.list.searchBtnPubChange(this,\"selleruserid\")'>全部</a>";
					
					foreach ($stores as $id => $val){
						echo "<a class='service_a ".((!empty($search_con['selleruserid']) && $search_con['selleruserid'] == $id) ? 'text-rever-important' : '')."' value='".$id."' onclick='online_product.list.searchBtnPubChange(this,\"selleruserid\")'>".$val['name']."</a>";
					}
				?>
				</div>
			</div>
		</div>
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; ">配对状态：</span>
		    <div style="width: 92%; float: left;">
			    <div display: inline-block;'>
				<?php
					echo "<a class='service_a ".(empty($search_con['matching_type']) ? 'text-rever-important' : '')."' value='' onclick='online_product.list.searchBtnPubChange(this,\"matching_type\")'>".'全部'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 1) ? 'text-rever-important' : '')."' value='1' onclick='online_product.list.searchBtnPubChange(this,\"matching_type\")'>".'未识别'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 2) ? 'text-rever-important' : '')."' value='2' onclick='online_product.list.searchBtnPubChange(this,\"matching_type\")'>".'待确认'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 3) ? 'text-rever-important' : '')."' value='3' onclick='online_product.list.searchBtnPubChange(this,\"matching_type\")'>".'已配对'."</a>";
				?>
				</div>
			</div>
		</div>
		<?php if(!empty($product_status_arr['on_line'])){?>
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; "><?= $product_status_arr['on_line_name']?>：</span>
		    <div style="width: 92%; float: left;">
			    <div display: inline-block;'>
				<?php
					$product_status = isset($search_con['product_status']) ? $search_con['product_status'] : 1;
					echo "<a class='service_a ".($product_status == 0 ? 'text-rever-important' : '')."' value='0' onclick='online_product.list.searchBtnPubChange(this,\"product_status\")'>".'全部'."</a>";
					foreach ($product_status_arr['on_line'] as $key => $val){
						echo "<a class='service_a ".($product_status == $key ? 'text-rever-important' : '')."' value='".$key."' onclick='online_product.list.searchBtnPubChange(this,\"product_status\")'>".$val."</a>";
					}
				?>
				</div>
			</div>
		</div>
		<?php }?>
		<?php if(!empty($product_status_arr['check'])){?>
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; "><?= $product_status_arr['check_name']?>：</span>
		    <div style="width: 90%; float: left;">
			    <div display: inline-block;'>
				<?php
					$check_status = isset($search_con['check_status']) ? $search_con['check_status'] : '0';
					echo "<a class='service_a ".($check_status == '0' ? 'text-rever-important' : '')."' value='0' onclick='online_product.list.searchBtnPubChange(this,\"check_status\")'>".'全部'."</a>";
					foreach ($product_status_arr['check'] as $key => $val){
						echo "<a class='service_a ".($check_status == $key ? 'text-rever-important' : '')."' value='".$key."' onclick='online_product.list.searchBtnPubChange(this,\"check_status\")'>".$val."</a>";
					}
				?>
				</div>
			</div>
		</div>
		<?php }?>
	</div>
	
	<input type="hidden" name="platform" value="<?=empty($search_con['platform']) ? '' : $search_con['platform']; ?>" />
	<input type="hidden" name="selleruserid" value="<?=empty($search_con['selleruserid']) ? '' : $search_con['selleruserid']; ?>" />
	<input type="hidden" name="matching_type" value="<?=empty($search_con['matching_type']) ? '' : $search_con['matching_type']; ?>" />
	<input type="hidden" name="product_status" value="<?=isset($search_con['product_status']) ? $search_con['product_status'] : 1; ?>" />
	<input type="hidden" name="check_status" value="<?=isset($search_con['check_status']) ? $search_con['check_status'] : 0; ?>" />
	<input type="hidden" name="per-page" value="<?=empty($search_con['per-page']) ? '' : $search_con['per-page']; ?>" />
	<input type="hidden" name="page" value="<?=empty($search_con['page']) ? '' : $search_con['page']; ?>" />
	
</form>

<!-- 功能按钮  -->
<div style="float: left; width: 100%; ">
	<button type="button" class="iv-btn btn-important" onclick="online_product.list.ShowAutomaticMatchingBox()" style="border-style: none; margin-right: 20px;" onclick="OverseaWarehouse.list.showAutomaticMatchingBox()">自动识别SKU</button>
	<button type="button" class="iv-btn btn-important" onclick="online_product.list.ShowCreateProductBox()" style="border-style: none; margin-right: 20px;" onclick="OverseaWarehouse.list.showAutomaticMatchingBox()">一键生成商品</button>
</div>

<!-- table -->
<div id="abcd" style="width:100%;float:left;">
<?php
if(! empty($productInfo['pagination'])):?>
<div>
    <div id="carrier-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$productInfo['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $productInfo['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

	<table id="carrier-list-table" cellspacing="0" cellpadding="0" style="font-size: 12px;width:100%;float:left;"
		class="table table-hover">
		<thead>
		<tr id='carrier-list-header' page='<?=empty($search_con['page']) ? '' : $search_con['page']; ?>' per_page='<?=empty($search_con['per-page']) ? '' : $search_con['per-page']; ?>'>
		<?php foreach($personality_info['col'] as $index => $col){?>
			<th nowrap width="<?=$col['width'] ?>"><?=$index ?></th>
		<?php } ?>
			<th nowrap width="150px"><?=TranslateHelper::t('本地SKU')?></th>
			<th nowrap width="80px"><?=TranslateHelper::t('状态')?></th>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
        <?php foreach($productInfo['data'] as $index => $row){?>
            <tr name="matching_item_<?=$row['pro_id'] ?>" <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
            	<?php foreach($personality_info['col'] as $col){
            		$html = '<td ';
            		//合并行
            		$merge_col = ['pro_id', 'shopname', 'photo_primary_url', 'product_name', 'ParentSKU'];
            		if(in_array($col['name'], $merge_col) && !empty($row['rowspan'])){
						if($row['rowspan'] == -1){
							continue;
						}
						else{
							$html .= 'rowspan='.$row['rowspan'];	
						}
					}
					//居中
					$center_col = ['pro_id', 'shopname', 'photo_primary_url', 'sku', 'ParentSKU'];
					if(in_array($col['name'], $center_col)){
						$html .= ' class="center" ';
					}
					$html .= '>';
					
            		switch($col['name']){
						case 'photo_primary_url':
							//$html .= '<div style="float: left; border: 1px solid #cccccc;width: 62px;height: 62px;"><img class="prod_img" style="max-width:100%; max-height:100%;" src="'.$row[$col['name']].'"></div>';
							$html .= '<div style="float: left; border: 1px solid #cccccc;width: 62px;height: 62px;"><img class="prod_img" style="max-width:100%; max-height:100%;" src="'.$row[$col['name']].'" data-toggle="popover" data-content=\'<img src="'.$row[$col['name']].'" style="max-width: 350px">\' data-html="true" data-trigger="hover"></div>';
							break;
						case 'product_name':
							if(!empty($row['product_name_url']))
								$html .= '<a href="'.$row['product_name_url'].'" target="_blank"><span >'.$row[$col['name']].'</span></a>';
							else
								$html .= '<span >'.$row[$col['name']].'</span>';
							break;
						case 'sku':
							$html .= '<span name="online_sku[]">'.$row[$col['name']].'</span>';
							if(count($row['attributes_arr']) > 0){
								$html .= '<p style="line-height: 25px;">';
								foreach ($row['attributes_arr'] as $attributes){
									$html .= '<span class="label label-warning">'.$attributes.'</span>';
								}
							    $html .= '</p>';
							}
							break;
						default:
							$html .= $row[$col['name']];
							break;
					}
					$html .= '</td>';
					echo $html;
				
				} ?>
				<td style="text-align: center;">
    			<?php if(empty($row['root_sku']) && !empty($row['matching_pending'])){
    				foreach ($row['matching_pending'] as $one){
						echo '<span>'.$one.'</span><button type="button" class="iv-btn btn-important" onclick="online_product.list.ConfirmMatching(this)" style="border-style: none; margin-left:5px; padding:5px; ">配对</button><br>';
					}
				}
				else if(!empty($row['root_sku'])){
					echo '<span>'.$row['root_sku'].'</span>';
				}?>
    			</td>
    			<td style="text-align: center;">
    				<?php if($row['matching_status'] == 3)
    						echo '<span style="color:green;">已配对</span>';
    					else if($row['matching_status'] == 2)
    						echo '<span style="color:red;">待确认</span>';
    					else 
    						echo '未识别'?>
    			</td>
				<td style="text-align: center;">
    				<?php if(!empty($row['root_sku'])){
    					echo '<a style="display:block" href="javascript:void(0);" onclick="online_product.list.changge_matching(\''.$row['pro_id'].'\',\''.$row['sku'].'\')">更换配对</a>'
							 .'<a style="display:block" href="javascript:void(0);" onclick="online_product.list.delete_matching(\''.$row['pro_id'].'\',\''.$row['sku'].'\',\''.$row['root_sku'].'\')">解除关系</a>';
    				}else {
    					echo '<a href="javascript:void(0);" onclick="online_product.list.changge_matching(\''.$row['pro_id'].'\',\''.$row['sku'].'\')">手动配对</a>';
    				}?>
    			</td>
            </tr>
        <?php }?>
        </tbody>
        <?php if (Yii::$app->request->isAjax){?>
        <script>$('.prod_img').popover();</script>
        <?php }?>
    </table>
	<!-- table -->


<?php
if(! empty($productInfo['pagination'])):?>
<div>
    <div id="carrier-list-pager2" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$productInfo['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $productInfo['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
	$tmp_and_where = '';
	try{
		foreach ($_REQUEST as $tmp_and_key => $tmp_and_val){
			if (is_array($tmp_and_val)){
				foreach($tmp_and_val as $tmp_sub_value){
					$tmp_and_where .= '&'.$tmp_and_key.'[]='.$tmp_sub_value;
				}
				
			}else{
				$tmp_and_where .= '&'.$tmp_and_key.'='.$tmp_and_val;
			}
			
			//$tmp_and_where .= '&'.$tmp_and_key.'='.$tmp_and_val;
		}
	}catch(\Exception $e){
		$e->getMessage();
	}
	$tmp_and_where = rtrim($tmp_and_where,'&');
	
	$options = array();
	$options['pagerId'] = 'carrier-list-pager';// 上方包裹 分页widget的id
	$options['pagerId2'] = 'carrier-list-pager2';// 下方包裹 分页widget的id
	$options['action'] = \Yii::$app->request->getPathInfo().'?'.$tmp_and_where; // ajax请求的 action
	$options['page'] = $productInfo['pagination']->getPage();// 当前页码
	$options['per_page'] = $productInfo['pagination']->getPageSize();// 当前page size
	$this->registerJs('$("#carrier-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
</div>

<div class="modal-body tab-content" id="dialog3" style="display:none;">
	<textarea class="create_product_edit_value" value="" placeholder="请输入批量修改的内容" style="width: 100%; height: 60px; "></textarea>
</div>
