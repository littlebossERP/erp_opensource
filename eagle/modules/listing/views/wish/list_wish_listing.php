<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/listing/list_wish_listing.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$sort = new Sort(['attributes' => ['status','id','parent_sku','sku','name','tags','colorsize','price','shipping','inventory','site_id','enable','error_message','update_time']]);
//exit(var_dump($WishListingData['data'][0]['variance_data'] ));
//exit(var_dump($WishListingData['data']));
?>
<style>
<!--

-->
table img{
	max-width: 80px;
	max-height: 50px;
}

input[type="text"]{
	min-width: 60px;
}
.dialog_box .modal-body {
	max-height: 500px;
	overflow-y: auto;
}

.dialog_box .modal-dialog {
	width: 900px;
}

.table{
	font-size:12px;
}

.product_btn_menu {
	width:75px;
}

ul.dropdown-menu>li>a {
  font-size: 12px;
}

.form-control.noMargin{
	margin:0px;
}
</style>
<div class="panel">
<div class="panel-body">
	<form  class="form-horizontal"
	action="<?= Url::to(['/'.yii::$app->controller->module->id.'/'.yii::$app->controller->id.'/'.yii::$app->controller->action->id])?>"
	method="get">
	<div class="form-group">
		<div class="col-sm-6">
			<div class="btn-group" role="group" aria-label="...">
				<button id="btn_create" type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('新建刊登范本')?></button>
				<button id="btn_batch_del"  type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('批量删除刊登范本')?></button>
				<button id="btn_batch_sync"  type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('批量立即刊登')?></button>
				<button id="btn_batch_cancel"  type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('批量取消刊登')?></button>
				<button  id="btn_save" type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('应用修改')?></button>
				<button  id="btn_binding" type="button" class="btn btn-default btn-sm"><?=TranslateHelper::t('关联商品')?></button>
				
			</div>
		</div>
		<div class="col-sm-2">
			<select name='select_status' class="form-control noMargin">
				<option value="all"><?= TranslateHelper::t('全部')?></option>
				<?php if (!empty($StatusMapping) && is_array($StatusMapping)):?>
				<?php foreach($StatusMapping as $status=>$label):?>
				<option value="<?= $status?>" <?php echo (!empty($_GET['select_status']) &&  $_GET['select_status'] == $status)? "selected":""?>><?= $label?></option>
				<?php endforeach;?>	
				<?php endif;?>
			</select>
		</div>
		<div class="col-sm-4">
			
			<div class="input-group">
				
				<input name='txt_search'  class="form-control"
				value='<?= (empty($_GET['txt_search'])?'':$_GET['txt_search'])?>' />
				<span class="input-group-btn">
					<button type="submit" class="btn btn-default btn-sm">
						<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							<?= TranslateHelper::t('搜索')?>
				    </button>
			    </span>
			</div>
		
		</div>
		
	</div>
	<div class="row" style="margin-bottom: 5px;">
			<div class="col-sm-12">
				<small>
				<?= TranslateHelper::t('上次同步时间 :')?>
				<?= empty($addi_params['last_product_success_retrieve_time'])?TranslateHelper::t('暂无同步记录'):$addi_params['last_product_success_retrieve_time']?>
				</small>
				<button id="btn_sync_fanben" type="button" class="btn btn-success btn-sm" data-last-sync-time="<?= empty($addi_params['last_sync_prodct_time'])?"":$addi_params['last_sync_prodct_time']?>"><?=TranslateHelper::t('从平台同步新商品')?></button>
			</div>
		</div>
	</form>
	
	<table  class="table">
		<tr>
			<th nowrap>
				<div class="checkbox">
					<label> <input type="checkbox" name="chk_wish_fanben_all">
					</label>
				</div>
			</th>
			<th style="width: 100px;"><?=TranslateHelper::t('操作') ?></th>
			<th><?=$sort->link('status',['label'=>TranslateHelper::t('状态')]) ?></th>
			<th><?=$sort->link('id',['label'=>TranslateHelper::t('编号')]) ?></th>
			<th><?=TranslateHelper::t('缩略图') ?></th>
			<th><?=$sort->link('parent_sku',['label'=>TranslateHelper::t('变参父产品ID')]) ?></th>
			<th><?=$sort->link('name',['label'=>TranslateHelper::t('刊登范本标题')]) ?></th>
			<th><?=$sort->link('tags',['label'=>TranslateHelper::t('标签(逗号分隔多个)')]) ?></th>
			<th><?=TranslateHelper::t('内部sku')?></th>
			<th><?=TranslateHelper::t('商品唯一码')?></th>
			<th><?=TranslateHelper::t('颜色/Size') ?></th>
			<th><?=TranslateHelper::t('售价') ?></th>
			<th><?=TranslateHelper::t('运费') ?></th>
			<th><?=TranslateHelper::t('数量')?></th>
			<th><?=TranslateHelper::t('启用') ?></th>
			<th><?=$sort->link('site_id',['label'=>TranslateHelper::t('账号编号')]) ?></th>
			<th><?=$sort->link('error_message',['label'=>TranslateHelper::t('刊登备注')]) ?></th>
			<th style="width: 100px;"><?=$sort->link('update_time',['label'=>TranslateHelper::t('最后更新时间')]) ?></th>
		</tr>
		<?php foreach($WishListingData['data'] as $row):
		
			if (!empty($row['variance_data'])){
				if (is_array($row['variance_data'])){
					$rowspanHtml = ' rowspan="'.count($row['variance_data']).'" ';
					$firstRowHtml = "";
					$OtherRowHtml = "";
					foreach($row['variance_data'] as $oneVariance){
						//first row 
						if (empty($firstRowHtml)){
							
							$firstRowHtml .="<td>".$oneVariance['internal_sku']."</td>";
							$firstRowHtml .="<td>".$oneVariance['sku']."</td>";
							$firstRowHtml .="<td>".$oneVariance['color']."/".$oneVariance['size']."</td>";
							$firstRowHtml .="<td><input type='text' name='price' class=\"form-control\"  value='".$oneVariance['price']."' data-origrin='".$oneVariance['price']."' data-sku='".$oneVariance['sku']."'/></td>";
							$firstRowHtml .="<td><input type='text'  name='shipping' class=\"form-control\"   value='".$oneVariance['shipping']."' data-origrin='".$oneVariance['shipping']."' data-sku='".$oneVariance['sku']."' /></td>";
							$firstRowHtml .="<td><input type='text'  name='inventory' class=\"form-control\"   value='".$oneVariance['inventory']."' data-origrin='".$oneVariance['inventory']."' data-sku='".$oneVariance['sku']."' /></td>";
							$firstRowHtml .="<td><div class=\"checkbox\">
										    <label>
										      <input type=\"checkbox\" name='variance_enable' ".((strtoupper($oneVariance['enable']) == "Y")?"checked":"")."   data-origrin='".$oneVariance['enable']."' data-sku='".$oneVariance['sku']."'/>
										    </label>
										  </div>"."</td>";
						}else{
							//other row
							$tmpRowHtml = "";
							$tmpRowHtml .="<td>".$oneVariance['internal_sku']."</td>";
							$tmpRowHtml .="<td>".$oneVariance['sku']."</td>";
							$tmpRowHtml .="<td>".$oneVariance['color']."/".$oneVariance['size']."</td>";
							$tmpRowHtml .="<td><input type='text'  name='price' class=\"form-control\"   value='".$oneVariance['price']."' data-origrin='".$oneVariance['price']."' data-sku='".$oneVariance['sku']."'/></td>";
							$tmpRowHtml .="<td><input type='text'  name='shipping' class=\"form-control\"   value='".$oneVariance['shipping']."' data-origrin='".$oneVariance['shipping']."' data-sku='".$oneVariance['sku']."'/></td>";
							$tmpRowHtml .="<td><input type='text'  name='inventory' class=\"form-control\"  value='".$oneVariance['inventory']."' data-origrin='".$oneVariance['inventory']."' data-sku='".$oneVariance['sku']."'/></td>";
							$tmpRowHtml .="<td><div class=\"checkbox\">
										    <label>
										      <input type=\"checkbox\" name='variance_enable' ".((strtoupper($oneVariance['enable']) == "Y")?"checked":"")."  data-origrin='".$oneVariance['enable']."' data-sku='".$oneVariance['sku']."'/>
										    </label>
										  </div>"."</td>";

							$OtherRowHtml .= "<tr data-id='".$row['id']."' data-parent-sku='".$row['parent_sku']."' data-site-id='".$row['site_id']."'>".$tmpRowHtml."</tr>";

						}
					}//end of 
				}
			}else{
				$rowspanHtml = 1;
				$firstRowHtml = "";
				$OtherRowHtml = "";
				
				$firstRowHtml .="<td></td>";
				$firstRowHtml .="<td></td>";
				$firstRowHtml .="<td></td>";
				$firstRowHtml .="<td></td>";
				$firstRowHtml .="<td></td>";
				$firstRowHtml .="<td></td>";
			}
		
		?>
			<tr data-id="<?= $row['id']?>" data-parent-sku="<?= $row['parent_sku']?>" data-site-id="<?= $row['site_id']?>">
				<td <?=$rowspanHtml?>>
					<div class="checkbox">
						<label><input type="checkbox" name="chk_wish_fanben" value="<?= $row['id']?>"></label>
					</div>
				</td>
				<td <?=$rowspanHtml?>>
					<div class="btn-group product_btn_menu" style="white-space: nowrap">
						<button type="button" class="btn btn-default btn-sm"
							onclick="listWishListing.editFanbenCapture('<?= $row['id']?>')"><?=TranslateHelper::t('修改') ?> </button>
						<button type="button" class="btn btn-default btn-sm dropdown-toggle"
							data-toggle="dropdown" aria-expanded="false">
							<span class="caret"></span> <span class="sr-only">Toggle Dropdown</span>
						</button>
						<ul class="dropdown-menu" role="menu">
							<li><a
								onclick="listWishListing.doDelete('<?= $row['id']?>','delete-fan-ben','<?= TranslateHelper::t('确定要删除刊登范本?')?>')"><?=TranslateHelper::t('删除') ?></a></li>
						</ul>
					</div>
				</td>
				<td <?=$rowspanHtml?>><?= empty($StatusMapping[$row['status']])?$row['status']:$StatusMapping[$row['status']]?></td>
				<td <?=$rowspanHtml?>><?= $row['id']?></td>
				<td <?=$rowspanHtml?>><img style="max-" src="<?= $row['main_image']?>"></td>
				<td <?=$rowspanHtml?>><?= $row['parent_sku']?></td>
				<td <?=$rowspanHtml?>><?= $row['name']?></td>
				<td <?=$rowspanHtml?> style="
    word-break: break-word;
"><?= $row['tags']?></td>
				<?= $firstRowHtml?>
				<td <?=$rowspanHtml?>><?= $row['site_id']?></td>
				
				<td <?=$rowspanHtml?>><?= $row['error_message']?></td>
				<td <?=$rowspanHtml?>><?= $row['update_time']?></td>
			</tr>
			 <?= $OtherRowHtml?>
		<?php endforeach;?>
		
	</table>
	
	<?php
//exit(print_r($WishListingData,true));
if(! empty($WishListingData['pagination'])):?>
<div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$WishListingData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $WishListingData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

</div>
</div>



 