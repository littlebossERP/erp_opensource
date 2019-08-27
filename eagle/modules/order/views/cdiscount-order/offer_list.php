<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
//$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("cdOffer.list.init()", \yii\web\View::POS_READY);

?>
<style>
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>	
<div class="tracking-index col2-layout">
	<div class="" style="padding-top: 10px;">
		<div>
			<!-- 搜索区域 -->
			<form class="form-inline" id="form1" name="form1" action="/order/cdiscount-order/view-offer-list" method="get" style="float:left;">
			<div style="margin:5px">
				<?=Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$cdiscountUsersDropdownList,['class'=>'form-control input-sm','id'=>'seller_id','style'=>'margin:0px','prompt'=>'卖家账号'])?>
				<?php 
					$active_status=['Active'=> '在售' , 'unActive'=>'非在售', ];
					$bestseller_status=['Y'=> '是' , 'N'=>'否', ];
				?>
				<?=Html::dropDownList('offer_state',@$_REQUEST['offer_state'],$active_status,['class'=>'form-control input-sm','id'=>'offer_state','style'=>'margin:0px','prompt'=>'是否在售'])?>
				<?=Html::dropDownList('is_bestseller',@$_REQUEST['is_bestseller'],$bestseller_status,['class'=>'form-control input-sm','id'=>'is_bestseller','style'=>'margin:0px','prompt'=>'是否首选卖家'])?>
				<div class="input-group">
			      	<?=Html::textInput('keyword',@$_REQUEST['keyword'],['class'=>'form-control input-sm','id'=>'keyword','style'=>'width:200px'])?>
			    </div>
			    <?=Html::submitButton('搜索',['class'=>"btn-xs",'id'=>'search'])?>
		    	<?=Html::button('重置',['class'=>"btn-xs",'onclick'=>"javascript:cleform();"])?>
		    </div>
			</form>
			<div class="input-group" style="float:right;">
				<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.print_offers()" >打印选中的商品</a>
			</div>
		</div>
		<br>
		<div style="">
			<table class="table table-condensed table-bordered offer-list" style="font-size:12px;">
			<tr>
				<th width="20px" ><input id="ck_all" class="ck_all" type="checkbox"></th>
				<th width="80px"><b>图片</b></th>
				<th width="100px"><b>EAN</b></th>
				<th width="100px"><b>Product Id/<br>Seller SKU</b></th>
				<th width="250px"><b>产品名称</b></th>
				<th width="80px"><b><?=$sort->link('stock',['label'=>TranslateHelper::t('库存')]) ?></b></th>
				<th width="80px"><b><?=$sort->link('price',['label'=>TranslateHelper::t('售价')]) ?></b></th>
				<th width="100px"><b>品牌</b></th>
				<th width="80px"><b>是否在售</b></th>
				<th width="80px"><b><?=$sort->link('last_15_days_sold',['label'=>TranslateHelper::t('最近15售出')]) ?></b><span qtipkey=""></span></th>
				<th width="130px"><b><?=$sort->link('creation_date',['label'=>TranslateHelper::t('创建日期')]) ?></b></th>
				<th width="130px"><b>店铺账号</b></th>
				<th width="80px"><b>是否BestSeller</b></th>
				<th width="130px"><b>BestSeller name</b></th>
				<th width="100px"><b>Best Seller Price</b></th>
				<th width="130px"><b>操作</b></th>
			</tr>
			<?php if (count($offerList['rows'])):foreach ($offerList['rows'] as $index=>$offer):?>
			<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
				<td><input id="ck_one" class="ck_one" type="checkbox"></td>
				<?php $photo_primary='';
					if(!empty($offer['img']))
						$photos = json_decode($offer['img'],true);
						if(!empty($photos[0]))
							$photo_primary = $photos[0];
				?>
				<td><img src="<?=$photo_primary ?>" style="width:80px;height:80px;"></td>
				<td><?=$offer['product_ean'] ?></td>
				<td><?=$offer['product_id'] ?><br><?=$offer['seller_product_id'] ?></td>
				<td style="word-break: break-word;"><?=$offer['name'] ?></td>
				<td><?=$offer['stock'] ?></td>
				<td><?=$offer['price'] ?></td>
				<td style="word-break: break-word;"><?=$offer['brand'] ?></td>
				<td><?=$offer['offer_state']=='Active'?'在售':'非在售' ?></td>
				<td><?=empty($offer['last_15_days_sold'])?0:$offer['last_15_days_sold'] ?></td>
				<td><?=$offer['creation_date'] ?></td>
				<td style="word-break: break-word;"><?=$offer['seller_id'] ?></td>
				<td><?=$offer['is_bestseller']=='Y'?'是':'否' ?></td>
				<td style="word-break: break-word;"><?=$offer['is_bestseller']=='N'?$offer['bestseller_name']:'' ?></td>
				<td><?=$offer['is_bestseller']=='N'?$offer['bestseller_price']:'' ?></td>
				<td>
					<a style="border:1px solid #00bb9b;" href="javascript:void(0)" onclick="cdOffer.list.view_offer(<?=$offer['id']?>)" ><font color="#00bb9b">详情</font></a>
					<?php if(!empty($offer['product_url'])) echo "<a style='border:1px solid #00bb9b;' href='".$offer['product_url']."' target='_blank'><font color='#00bb9b'>网站</font></a>" ?>
				</td>
			</tr>	
			<?php endforeach;endif;?>
			</table>
			<?php if(! empty($offerList['pagination'])):?>
			<div>
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$offerList['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			    <div class="btn-group" style="width: 49.6%;text-align: right;">
			    	<?=\yii\widgets\LinkPager::widget(['pagination' => $offerList['pagination'],'options'=>['class'=>'pagination']]);?>
				</div>
			</div>
			<?php endif;?>
		</div>
	
	<div style="clear: both;"></div>
	<div class="show-offer-detail"></div>
	</div>
</div>
<script>
function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
}
</script>