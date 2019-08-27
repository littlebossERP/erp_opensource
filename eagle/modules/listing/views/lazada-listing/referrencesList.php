<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$searchCondition=[
'Name'=>'标题',
'ParentSku'=>'ParentSku',
'SellerSku'=>'Sku'
];
?>
<style>
.lazada-listing-reference .modal-body {
	max-height: 500px;
	overflow-y: auto;
}

.lazada-listing-reference .modal-dialog {
	width: 830px;
}

.pageSize-dropdown-div.btn-group.dropup {
  width: 49%;
}

#select-reference-table td,#select-reference-table th{
	border: 1px solid rgb(202,202,202);
	vertical-align: middle;
}
</style>

<script type="text/javascript">
</script>
<div>
	<?php // 此处不能用form 包裹，否则search_val input 触发keypress enter 事件会 导致刷新页面 ?>
	<?=Html::dropDownList('lazada_uid',@$_REQUEST['lazada_uid'],$lazadaUsersDropdownList,['onchange'=>"lazadaListing.queryReferences(this);",'class'=>'eagle-form-control','id'=>'','style'=>'width:130px;','prompt'=>'全部lazada店铺'])?>
    <?=Html::dropDownList('search_type',@$_REQUEST['search_type'],$searchCondition,['class'=>'eagle-form-control','id'=>'','style'=>'width:130px;'])?>
    <input name="search_val" class="eagle-form-control" value='' style="width:200px;"/>
	<button type="button" id="btn-select-reference-search"  class="btn btn-success btn-sm">搜索</button>
	
	<div style="width:100%;float:left;">
		<FORM id="references-form" name="references-form" action="/listing/lazada-listing/use-reference">
		<table id="select-reference-table" cellspacing="0" cellpadding="0" style="width=100%;font-size:12px;float:left;" class="table table-hover">
		<tr>
			<th style="width:20px;">选择</th>
			<th nowrap style="width:65px;"><?=TranslateHelper::t('图片') ?></th>
			<th><?=TranslateHelper::t('店铺') ?></th>
			<th nowrap style="width:300px;">标题</th>
			<th nowrap style="width:150px;"><?=$sort->link('ParentSku',['label'=>TranslateHelper::t('ParentSku')])?></th>
			<th nowrap ><?=$sort->link('SellerSku',['label'=>TranslateHelper::t('SellerSku')]) ?></th>
		</tr>
	        <?php foreach($references['data'] as $row):?>
	        	<tr>
	        		<td><input type="radio" name="listing_id" value="<?=$row['id'] ?>" /></td>
					<td>
						<div style="height: 50px;">
							<img style="max-height: 50px; max-width: 50px;"
								src="<?=$row['MainImage'] ?>" />
						</div>
					</td>
					<td><?=$lazadaUsersDropdownList[$row['lazada_uid_id']] ?></td>
					<td><?=$row['Name'] ?></td>
					<td><?=$row['ParentSku'] ?></td>
					<td><?=$row['SellerSku'] ?> </td>
				</tr>
	        <?php endforeach;?>
	    </table>
	    </FORM>
	</div>
	<?php if($references['pagination']):?>
	<div>
		<div id="select-reference-pager" style="clear:both;float:left;width:100%;">
		    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$references['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%;text-align: right;">
		    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $references['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
	</div>
	<?php endif;?>
</div>

<?php 
$options = array();
$options['pagerId'] = 'select-reference-pager';// 包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo(); // ajax请求的 action
$options['page'] = $references['pagination']->getPage();
$options['per-page'] = $references['pagination']->getPageSize();
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#select-reference-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>

