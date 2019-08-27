<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("cdOffer.list.init()", \yii\web\View::POS_READY);
$this->registerJs("cdOffer.excel.init()", \yii\web\View::POS_READY);
?>
<style>
.left_menu.menu_v2 .iconfont.icon-jinggao{
	float:none;
	color:red;
}
.left_menu.menu_v2 .iconfont.icon-jinggao+span{
	margin-right:0px;
}
</style>	
<div class="tracking-index col2-layout">
	<?=$this->render('_leftmenu',[]);?>
	<div class="" style="padding-top: 10px;">
		<div>
			<!-- 搜索区域 -->
			<form class="form-inline" id="form1" name="form1" action="/listing/cdiscount/excel-stock-price" method="post" style="float:left;">
			<div style="margin:5px">
				<?=Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$cdiscountUsersDropdownList,['class'=>'form-control input-sm','id'=>'seller_id','style'=>'margin:0px','prompt'=>'卖家账号'])?>
				<?php 
					$active_status=['Active'=> '在售' , 'unActive'=>'非在售', ];
					$bestseller_status=['Y'=> '是' , 'N'=>'否', ];
					$key_types = ['seller_product_id'=>'SKU','product_ean'=>'EAN','product_id'=>'CD平台商品ID'];
				?>
				<?=Html::dropDownList('concerned_status',@$_REQUEST['concerned_status'],['F'=>'已关注','N'=>'未关注','I'=>'已忽略'],['class'=>'form-control input-sm','id'=>'concerned_status','style'=>'margin:0px','prompt'=>'关注等级'])?>
				<?=Html::dropDownList('offer_state',@$_REQUEST['offer_state'],$active_status,['class'=>'form-control input-sm','id'=>'offer_state','style'=>'margin:0px','prompt'=>'是否在售'])?>
				<?=Html::dropDownList('is_bestseller',@$_REQUEST['is_bestseller'],$bestseller_status,['class'=>'form-control input-sm','id'=>'is_bestseller','style'=>'margin:0px','prompt'=>'是否首选卖家'])?>
				<div class="input-group" style="width:200px;">
					<span style="float:left;height:30px;padding-top:8px;font-size:14px;">关键字类型:</span>
					<?=Html::dropDownList('key_type',empty($_REQUEST['key_type'])?'seller_product_id':@$_REQUEST['key_type'],$key_types,['class'=>'form-control input-sm','id'=>'key_type','style'=>'margin:0px;width:120px;float:right',])?>
				</div>
		    </div>
		    <div style="margin:5px">
		    	
				
				<div class="" style="float:left;">
					<textarea class="form-control" data-percent-width="true" name="keyword" id="keyword" style="width:704px;height:128px"><?=empty($_REQUEST['keyword'])?'':$_REQUEST['keyword'] ?></textarea>
			    </div>
			    <?=Html::submitButton('搜索',['class'=>"btn-xs",'id'=>'search','style'=>'margin: 5px 0px 0px 5px;'])?>
		    	<?=Html::button('重置',['class'=>"btn-xs",'onclick'=>"javascript:cleform();"])?>
		    	<?php $url_parmas[]='/listing/cdiscount/stock-price2-excel';
		    		foreach ($_REQUEST as $R=>$V){
		    			$url_parmas[$R] = $V;
		    		}
		    	?>
		    	<a class="btn btn-success" href="<?=Url::to($url_parmas) ?>" target="_blank" >导出查询到的商品</a>
		    </div>
			</form>
			<!--
			<div class="input-group" style="float:right;">
				<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.print_offers()" >打印选中的商品</a>
			</div>
			-->
		</div>
		<br>
		<div style="">
			<table class="table table-condensed table-bordered offer-list" style="font-size:12px;">
			<tr>
				<th width="20px" ><input id="ck_all" class="ck_all" type="checkbox"></th>
				<th width="80px">图片</th>
				<th width="100px"><b>SKU</b></th>
				<th width="100px"><b>ProducrId</b></th>
				<th width="100px">EAN</th>
				<th width="200px">商品名称</th>
				<th width="80px"><b>库存</b></th>
				<th width="80px"><b>售价</b></th>
				<th width="100px">BestSeller价格</th>
				<th width="80px">是否BestSeller</th>
				<th width="100px">BestSeller店名</th>
				<th width="100px">操作</th>
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
				<td><?=$offer['seller_product_id'] ?></td>
				<td><?=$offer['product_id'] ?></td>
				<td><?=$offer['product_ean'] ?></td>
				<td><?=$offer['name'] ?></td>
				<td><?=$offer['stock'] ?></td>
				<td><?=$offer['price'] ?> €</td>
				<td><?=$offer['bestseller_price'] ?> €</td>
				<td><?=($offer['is_bestseller']=='Y')?'是':'否' ?></td>
				<td><?=$offer['bestseller_name'] ?></td>
				<td>
					<a style="border:1px solid #00bb9b;" href="javascript:void(0)" onclick="cdOffer.list.view_offer(<?=$offer['id']?>)" ><font color="#00bb9b">详情</font></a>
					<?php if(!empty($offer['product_url'])) echo "<a style='border:1px solid #00bb9b;' href='".$offer['product_url']."' target='_blank'><font color='#00bb9b'>网站</font></a>" ?>
				</td>
			</tr>	
			<?php endforeach;endif;?>
			</table>
			<?php if(! empty($offerList['pagination'])):?>
			<div>
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$offerList['pagination'] , 'pageSizeOptions'=>array( 20 , 50 , 100 , 200 ,500) , 'class'=>'btn-group dropup']);?>
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