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

$this->registerJsFile($baseUrl."js/project/catalog/matching_list.js?v=1.1", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/order/orderCommon.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/create_product.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("matching.list.init();" , \yii\web\View::POS_READY);
?>
<style>
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

</style>

<?php 
echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$menu_active,
]);
?>
<form class="form-inline" id="searchForm" name="form1" action="" method="post">
	<div class="div-input-group" style="margin-left:20px; margin-bottom:10px;">
	    <LABEL class="lb_choose_title">筛选时间：</LABEL>
		<input name="start_date" class="eagle-form-control" type="text" placeholder="'下单日期从 此日期后" 
			value="<?= (empty($search_con['start_date'])?"":$search_con['start_date']);?>" style="width:135px;margin:0px;height:28px;margin-right:0px;"/>
		<LABEL class="lb_choose_title"> ~ </LABEL>
		<input name="end_date" class="eagle-form-control" type="text" placeholder="至 此日期前" 
			value="<?= (empty($search_con['end_date'])?"":$search_con['end_date']);?>" style="width:120px;margin:0px;height:28px;margin-right:20px;"/>
		
	  	<SELECT name="matching_searchval_type" class="eagle-form-control" style="width:110px;margin:0px;">
			<OPTION value="sku" <?= (empty($search_con['matching_searchval_type']) || $search_con['matching_searchval_type'] == 'sku') ? "selected":'';?>>店铺SKU</OPTION>
			<OPTION value="root_sku" <?= (!empty($search_con['matching_searchval_type']) && $search_con['matching_searchval_type'] == 'root_sku') ? "selected":'';?>>本地SKU</OPTION>
	  		<OPTION value="title" <?= (!empty($search_con['matching_searchval_type']) && $search_con['matching_searchval_type'] == 'title') ? "selected":'';?>>产品标题</OPTION>
	  		<OPTION value="order_source_order_id" <?= (!empty($search_con['matching_searchval_type']) && $search_con['matching_searchval_type'] == 'order_source_order_id') ? "selected":'';?>>平台订单号</OPTION>
	  		<OPTION value="order_id" <?= (!empty($search_con['matching_searchval_type']) && $search_con['matching_searchval_type'] == 'order_id') ? "selected":'';?>>小老板订单号</OPTION>
	  	</SELECT>
	  	<input name="matching_searchval" class="eagle-form-control" type="text" value="<?= (empty($search_con['matching_searchval'])?"":$search_con['matching_searchval']);?>"
	    	style="width:200px;margin:0px 30px 0px 5px;height:28px;"/>
		<button class="iv-btn btn-search btn-spacing-middle" onclick="matching.list.searchButtonClick()">搜索</button>
	</div>

	<div style="border: 1px solid #ccc; float: left; margin-bottom: 10px; width: 100%; ">
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; ">平台渠道：</span>
		    <div style="width: 92%; float: left;">
			    <div style='display: inline-block;'>
				<?php
					echo "<a class='service_a ".(empty($search_con['platform']) ? 'text-rever-important' : '')."' value='' onclick='matching.list.searchBtnPubChange(this,\"platform\")'>全部</a>";
					
					foreach ($platformAccount as $plat){
						if(!empty($plat))
							echo "<a class='service_a ".((!empty($search_con['platform']) && $search_con['platform'] == $plat) ? 'text-rever-important' : '')."' value='".$plat."' onclick='matching.list.searchBtnPubChange(this,\"platform\")'>".$plat."</a>";
					}
				?>
				</div>
			</div>
		</div>
		<div class="div_choose">
		    <span style="float: left; line-height: 30px; ">店铺账号：</span>
		    <div style="width: 92%; float: left;">
			    <div display: inline-block;'>
				<?php
					echo "<a class='service_a ".(empty($search_con['selleruserid']) ? 'text-rever-important' : '')."' value='' onclick='matching.list.searchBtnPubChange(this,\"selleruserid\")'>全部</a>";
					
					foreach ($stores as $id => $val){
						if(!empty($val) && (empty($search_con['platform']) || $search_con['platform'] == $val['platform']))
							echo "<a class='service_a ".((!empty($search_con['selleruserid']) && $search_con['selleruserid'] == $val['id']) ? 'text-rever-important' : '')."' value='".$val['id']."' onclick='matching.list.searchBtnPubChange(this,\"selleruserid\")'>".$val['name']."</a>";
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
					echo "<a class='service_a ".(empty($search_con['matching_type']) ? 'text-rever-important' : '')."' value='' onclick='matching.list.searchBtnPubChange(this,\"matching_type\")'>".'全部'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 1) ? 'text-rever-important' : '')."' value='1' onclick='matching.list.searchBtnPubChange(this,\"matching_type\")'>".'未识别'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 2) ? 'text-rever-important' : '')."' value='2' onclick='matching.list.searchBtnPubChange(this,\"matching_type\")'>".'待确认'."</a>";
					echo "<a class='service_a ".((!empty($search_con['matching_type']) && $search_con['matching_type'] == 3) ? 'text-rever-important' : '')."' value='3' onclick='matching.list.searchBtnPubChange(this,\"matching_type\")'>".'已配对'."</a>";
				?>
				</div>
			</div>
		</div>
	</div>
	
	<input type="hidden" name="platform" value="<?=empty($search_con['platform']) ? '' : $search_con['platform']; ?>" />
	<input type="hidden" name="selleruserid" value="<?=empty($search_con['selleruserid']) ? '' : $search_con['selleruserid']; ?>" />
	<input type="hidden" name="matching_type" value="<?=empty($search_con['matching_type']) ? '' : $search_con['matching_type']; ?>" />
	<input type="hidden" name="per-page" value="<?=empty($search_con['per-page']) ? '' : $search_con['per-page']; ?>" />
	<input type="hidden" name="page" value="<?=empty($search_con['page']) ? '' : $search_con['page']; ?>" />
	
</form>

<!-- 功能按钮  -->
<div style="float: left; width: 100%; ">
	<button type="button" class="iv-btn btn-important" onclick="matching.list.ShowAutomaticMatchingBox()" style="border-style: none; margin-right: 20px;" >自动识别SKU</button>
	<button type="button" class="iv-btn btn-important" onclick="matching.list.ShowCreateProductBox()" style="border-style: none; margin-right: 20px;" >一键生成商品</button>
	
	<button type="button" class="iv-btn btn-important" onclick="matching.list.RefreshMatching()" style="border-style: none; margin-right: 10px; float: right; ">更新所有未配对订单</button>
	
	<?php if(!empty($search_con['matching_type']) && $search_con['matching_type'] == 2){?>
	<button type="button" class="iv-btn btn-important" onclick="matching.list.BathConfirmMatching()" style="border-style: none; margin-right: 10px; float: right; ">确认配对本页订单</button>
	<?php }?>
</div>

<!-- table -->
<div id="abcd" style="width:100%;float:left;">
<?php
if(! empty($orderInfo['pagination'])):?>
<div>
    <div id="carrier-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$orderInfo['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $orderInfo['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

	<table id="carrier-list-table" cellspacing="0" cellpadding="0" style="font-size: 12px;width:100%;float:left;"
		class="table table-hover">
		<thead>
		<tr id='carrier-list-header' page='<?=empty($search_con['page']) ? '' : $search_con['page']; ?>' per_page='<?=empty($search_con['per-page']) ? '' : $search_con['per-page']; ?>'>
			<th nowrap width="100px"><?=TranslateHelper::t('平台订单号<br>「小老板订单号」')?></th>
			<th nowrap width="150px"><?=TranslateHelper::t('店铺<br>「平台」')?></th>
			<th nowrap width="200px"><?=TranslateHelper::t('产品标题')?></th>
			<th nowrap width="250px"><?=TranslateHelper::t('产品信息')?></th>
			<th nowrap width="150px"><?=TranslateHelper::t('本地SKU')?></th>
			<th nowrap width="80px"><?=TranslateHelper::t('状态')?></th>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
        <?php foreach($orderInfo['data'] as $index=>$row){?>
            <tr name="matching_item_<?=$row['order_item_id'] ?>" <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
    			<td style="text-align: center;">
    			    <?=$row['platform_order_no'] ?><br>
    			    <span title="小老板订单号">「<?=$row['order_id'] ?>」</span>
    			</td>
    			<td style="text-align: center;"><?=$row['order_source'].'<br>「'.(empty($stores[$row['order_source'].'_'.$row['selleruserid']]) ? '' : $stores[$row['order_source'].'_'.$row['selleruserid']]['name']).'」' ?></td>
    			<td>
    			    <?php if(!empty($row['product_name_url']))
    			        echo "<a href='".$row['product_name_url']."' target='_blank' >
						        <span >".$row['product_name']."</span>
					         </a>";
                    else 
                        echo "<span >".$row['product_name']."</span>";
    			    ?>
    			   
				</td>
				<td>
    				<div style='float: left; border: 1px solid #cccccc;width: 62px;height: 62px;'>
    					<img class="prod_img" style='max-width:100%; max-height:100%; width: auto;height: auto;' src="<?=$row['photo_primary_url'] ?>" data-toggle="popover" data-content="<img width='350px' src='<?=str_replace('.jpg_50x50','',$row['big_photo_primary_url'])?>'>" data-html="true" data-trigger="hover">
    				</div>
    				<div style='float: left;'>
    					<p class='p_newline_v3'>
    						<?='<span name="order_sku[]">'.$row['sku'].'</span> x '.'<span class="badge '.($row['quantity'] > 1 ? 'badge_orange' : 'badge_grey').'" >'.$row['quantity'].'</span>' ?>
    					</p>
    					
    					<p><?=$row['currency'].' '.$row['price'] ?></p>
    					
    					<?php if(count($row['product_attributes_arr']) > 0){?>
     					<p>
     					<?php
	    					foreach ($row['product_attributes_arr'] as $tmp_product_attributes){
	    						echo '<span class="label label-warning">'.$tmp_product_attributes.'</span>';
	    					}
     					?>
    					</p>
    					<?php }?>
    					--
    				</div>
    			</td>
    			<td style="text-align: center;">
    			<?php if(empty($row['root_sku']) && !empty($row['matching_pending'])){
    				foreach ($row['matching_pending'] as $one){
						echo '<span name="to_be_confirmed_sku">'.$one.'</span><button type="button" class="iv-btn btn-important" onclick="matching.list.ConfirmMatching(this)" style="border-style: none; margin-left:5px; padding:5px; ">配对</button><br>';
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
    					echo '<a style="display:block" href="javascript:void(0);" onclick="matching.list.changge_matching(\''.$row['order_item_id'].'\',\''.$row['sku'].'\',\''.$row['root_sku'].'\')">更换配对</a>'
							 .'<a style="display:block" href="javascript:void(0);" onclick="matching.list.delete_matching(\''.$row['order_item_id'].'\',\''.$row['sku'].'\',\''.$row['root_sku'].'\')">解除关系</a>';
    				}else {
    					echo '<a href="javascript:void(0);" onclick="matching.list.changge_matching(\''.$row['order_item_id'].'\',\''.$row['sku'].'\',\'\')">手动配对</a>';
    				}?>
    			</td>
    		</tr>
        <?php }?>
        </tbody>
    </table>
	<!-- table -->


<?php
if(! empty($orderInfo['pagination'])):?>
<div>
    <div id="carrier-list-pager2" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$orderInfo['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $orderInfo['pagination'],'options'=>['class'=>'pagination']]);?>
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
	$options['page'] = $orderInfo['pagination']->getPage();// 当前页码
	$options['per_page'] = $orderInfo['pagination']->getPageSize();// 当前page size
	$this->registerJs('$("#carrier-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
</div>

<div class="modal-body tab-content" id="dialog2" style="display:none;">
	<div id="titlePairproduct" class="col-xs-12 mTop10 mBottom10 pLeft10s f14">确认要解除配对关系？</div>
    <label class="mLeft30" ><input type="radio" value="0" name="warningPairproduct" id="warningPairproduct1" checked> 仅对当前订单的SKU生效</label><br>
    <label class="mLeft30" ><input type="radio" value="1" name="warningPairproduct" id="warningPairproduct2"> 修改相同SKU的所有「已付款」订单</label><br>
    <label class="mLeft30" ><input type="radio" value="2" name="warningPairproduct" id="warningPairproduct3"> 修改相同SKU的所有「已付款」订单和以后的订单</label>
</div>

<div class="modal-body tab-content" id="dialog3" style="display:none;">
	<textarea class="create_product_edit_value" value="" placeholder="请输入批量修改的内容" style="width: 100%; height: 60px; "></textarea>
</div>
