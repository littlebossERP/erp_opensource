<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\modules\util\helpers\RedisHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
//$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("cdOffer.list.init()", \yii\web\View::POS_READY);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$uid = \Yii::$app->user->id;
$AccountVipInfo=CdiscountAccountsApiHelper::getCdAccountVipInfo($uid);
$vip_rank=0;
foreach ($AccountVipInfo as $row){
	if(!empty($row['vip_rank']))
		if((int)$row['vip_rank']>$vip_rank)
			$vip_rank = (int)$row['vip_rank'];
}
$frequency_description = CdiscountOfferTerminatorHelper::get_vip_fetch_frequency_description($vip_rank);

?>
<style>
.offer-list > tbody > tr:hover {
    background-color: #B9D6E8!important;
}
.offer-list td{
    text-align: center!important;
	padding:0px!important;
}
.offer-list th{
    text-align: center!important;
	padding:0px
}
.icon-shoucang-2:before{
	color:#FFA500;
}
td .popover{
	max-width: inherit;
    max-height: inherit;
}
.popover{
	min-width: 200px;
}
.inner-table td{
	text-align: center;
	border: 1px solid #DDD;
	padding:1px;
}
table.offer-list .iconfont{
	cursor: pointer;
}table.offer-list .icon-active{
	color: red;
}

.left_menu.menu_v2 .iconfont.icon-jinggao{
	float:none;
	color:red;
}
.left_menu.menu_v2 .iconfont.icon-jinggao+span{
	margin-right:0px;
}
</style>

<div class="tracking-index col2-layout">
	<?=$this->render('_leftmenu',['counter'=>$counter]);?>
	<div class="" style="padding-top: 10px;">
		<div>
			<?php if(!empty($_REQUEST['lostbs'])){?>
			<div class="alert alert-info" style="text-align:left">本筛选页列出的商品为：自上次打开本筛选页后开始，被抢走BestSeller位置的商品。<br>每次打开此页，被列出的商品都会取消此标识（即短时间内再打开或刷新此页，这些商品就不会再显示在此页）</div>
			<?php } ?>
			<!-- 搜索区域 -->
			<form class="form-inline" id="form1" name="form1" action="/listing/cdiscount/index" method="post" style="float:left;">
			<input type="hidden" name="focuse_status" value="<?=empty($_REQUEST['focuse_status'])?'':$_REQUEST['focuse_status'] ?>">
			<input type="hidden" name="lostbs" value="<?=empty($_REQUEST['lostbs'])?0:1 ?>">
			<div style="margin:5px">
				<?=Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$cdiscountUsersDropdownList,['class'=>'form-control input-sm','id'=>'seller_id','style'=>'margin:0px','prompt'=>'卖家账号'])?>
				<?php 
					$active_status=['Active'=> '在售' , 'Inactive'=>'非在售', ];
					$bestseller_status=['Y'=> '是BestSeller' , 'N'=>'不是BestSeller', ];
				?>
				<?php if (empty($_REQUEST['focuse_status']) && isset($_REQUEST['offer_state']) && $_REQUEST['offer_state']=='Inactive'){?>
				<?=Html::dropDownList('offer_state','Inactive',$active_status,['class'=>'form-control input-sm','id'=>'offer_state','style'=>'margin:0px','prompt'=>'是否在售','readonly'=>'readonly'])?>
				<?php } else{?>
				<?=Html::dropDownList('offer_state',@$_REQUEST['offer_state'],$active_status,['class'=>'form-control input-sm','id'=>'offer_state','style'=>'margin:0px','prompt'=>'是否在售'])?>
				<?php }?>
				<?php if(empty($_REQUEST['lostbs'])){?>
				<?php if (empty($_REQUEST['focuse_status']) && isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']=='Y'){?>
				<?=Html::dropDownList('is_bestseller','Y',$bestseller_status,['class'=>'form-control input-sm','id'=>'is_bestseller','style'=>'margin:0px','prompt'=>'是否首选卖家','readonly'=>'readonly'])?>
				<?php }
				elseif (empty($_REQUEST['focuse_status']) && isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']=='N'){?>
				<?=Html::dropDownList('is_bestseller','N',$bestseller_status,['class'=>'form-control input-sm','id'=>'is_bestseller','style'=>'margin:0px','prompt'=>'是否首选卖家','readonly'=>'readonly'])?>
				<?php } 
				else {?>
				<?=Html::dropDownList('is_bestseller',@$_REQUEST['is_bestseller'],$bestseller_status,['class'=>'form-control input-sm','id'=>'is_bestseller','style'=>'margin:0px','prompt'=>'是否首选卖家'])?>
				<?php } ?>
				<?php } ?>
				<div class="input-group">
			      	<?=Html::textInput('keyword',@$_REQUEST['keyword'],['class'=>'form-control input-sm','id'=>'keyword','style'=>'width:300px','placeholder'=>'输入SKU,CD平台商品id,商品名称或EAN搜索'])?>
			    </div>
			    <?=Html::submitButton('搜索',['class'=>"btn btn-default btn-sm",'id'=>'search'])?>
		    	<?=Html::button('重置',['class'=>"btn btn-default btn-sm",'onclick'=>"javascript:cleform();"])?>
		    	<?php if(!empty($isCustomizationUser)){	//为了减低负荷，关闭手动拉取完整listing的按钮 ?>
		    	<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.openExcelUpload()" style="margin-left:50px">Excel导入商品</a>
				<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.openAccountList()" style="margin-left:50px">后台获取店铺商品</a>
				<?php }?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.viewQuota()" style="margin-left:20px">额度信息</a>
		    </div>
			</form>
			<div class="input-group" style="float:left;width:100%;margin:5px 0px;">批量操作：
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']!=='H') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchHotSale()" style="margin:5px">批量爆款监视</a>
				<?php } ?>
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='H') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchUnHotSale()" style="margin:5px">取消爆款监视</a>
				<?php } ?>
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']!=='F') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchConcerned()" style="margin:5px">批量关注</a>
				<?php } ?>
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='F') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchUnConcerned()" style="margin:5px">批量取消关注</a>
				<?php } ?>
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='I') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchUnIgnore()" style="margin:5px">批量取消忽略</a>
				<?php } ?>
				<?php if (((isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']!=='I') || !isset($_REQUEST['focuse_status'])) && !isset($_REQUEST['t_active'])) { ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchIgnore()" style="margin:5px">批量忽略</a>
				<?php } ?>
				<?php if ((isset($_REQUEST['t_active']) && $_REQUEST['t_active']=='N')){ ?>
				<a class="btn btn-info" href="javascript:void(0)" onclick="cdOffer.list.batchReActive()" style="margin:5px">批量恢复</a>
				<?php } ?>
			</div>
			<!-- 
			<div class="input-group" style="float:right;">
				<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.print_offers()" >打印选中的商品</a>
			</div>
			 -->
		</div>
		<?php if (empty($_REQUEST['focuse_status']) && isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']=='-'){ ?>
		<div style="float:left;width:100%;" class="alert alert-warning">此分类下的商品为您已下架商品、或未获取到BestSeller信息的商品</div>
		<?php } ?>
		<br>
		<div style="">
			<table class="table table-condensed table-bordered offer-list" style="font-size:12px;">
				<tr>
					<th width="3%"><input id="ck_all" class="ck_all" type="checkbox"></th>
					<th width="10%">SKU</th>
					<th width="16%">商品中文名</th>
					<th width="8%">是否BestSeller<span qtipkey="cdTermininator_is_bestseller"></th>
					<th width="10%">BestSeller名<span qtipkey="cdTermininator_bestseller_name"></th>
					<th width="8%">BestSeller价格<span qtipkey="cdTermininator_bestsellerPrice"></span></th>
					<th width="8%"><b><?=$sort->link('price',['label'=>TranslateHelper::t('售价')]) ?></b><span qtipkey="cdTermininator_yourPrice"></span></th>
					<th width="8%">采购价<span qtipkey="cdTermininator_yourPurchasePrice"></span></th>
					<th width="8%"><b><?=$sort->link('stock',['label'=>TranslateHelper::t('库存')]) ?></b><span qtipkey="cdTermininator_yourInventory"></span></th>
					<th width="10%"><b><?=$sort->link('last_15_days_sold',['label'=>TranslateHelper::t('最近15天售出')]) ?></b><span qtipkey="sales_within_15_days"></span></th>
					<th width="5%"><b>操作</b></th>
				</tr>
				<?php $index=0; ?>
				<?php $product_id_arr = [];?>
				<?php if (count($offerList['rows'])):foreach ($offerList['rows'] as $product_id=>$offer):?>
				<?php $product_id_arr[] = $product_id;?>
				<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>><td colspan="11" style="">
				<?php $index++; ?>
					<table style="width:100%;border:2px solid #d9effc;padding:0px" class="inner-table">
						<tr>
							<td width="3%" style="vertical-align: middle;"><input name="offer_id[]" class="ck_one" type="checkbox" value="<?=$offer['id'] ?>" data-concerned="<?=$offer['concerned_status'] ?>" data-sku="<?=$offer['seller_product_id'] ?>" data-seller="<?=$offer['seller_id'] ?>"> </td>
							<td width="10%" style="vertical-align: middle;"><b style="font-weight:bold;"><?=$offer['seller_product_id'] ?></b>
							<?php if(!empty($offer['parent_product_id'])){
								$tmpArr = json_decode($offer['parent_product_id'],true);
								if(empty($tmpArr))
									echo '<br>('.$offer['parent_product_id'].'的系列商品)';
							}?>
							</td>
						<?php $prod_name_ch='';
							$sku = ProductHelper::getRootSkuByAlias($offer['seller_product_id']);
							if(!empty($sku)){
								$pd = Product::find()->where(['sku'=>$sku])->asArray()->one();
								if(!empty($pd)){
									$prod_name_ch = $pd['prod_name_ch'];
									$purchase_price = $pd['purchase_price'];
								}
							}
							if(empty($prod_name_ch))
								$prod_name_ch = "<span style='color:red'>".TranslateHelper::t('商品未于商品模块创建或未设置中文名称')."</span>";
							?>
							<td width="16%" style="vertical-align: middle;"><?=$prod_name_ch?></td>
							<td width="8%" style="vertical-align: middle;">
								<?php if($offer['is_bestseller']=='Y') echo '<span style="color:green">是</span>';
									  elseif($offer['is_bestseller']=='N') echo '<span style="color:red">否</span>';
									  elseif($offer['is_bestseller']=='-' || empty($offer['is_bestseller'])) echo '<span style="color:red" title="下架或未有获取到BestSeller信息">N/A</span>';
								?>
							</td>
							<td width="10%" style="vertical-align: middle;"><?=$offer['is_bestseller']=='N'?'<span style="color:red">'.$offer['bestseller_name'].'</span>':$offer['bestseller_name'] ?></td>
							<td width="8%" style="vertical-align: middle;"><?=empty($offer['bestseller_price'])?'--':$offer['bestseller_price'].'€' ?></td>
							<td width="8%" style="vertical-align: middle;"><?=empty($offer['price'])?'--':$offer['price'].'€' ?></td>
							<td width="8%" style="vertical-align: middle;"><?=!isset($purchase_price)?'--':'¥'.$purchase_price ?></td>
							<td width="8%" style="vertical-align: middle;"><?=(int)$offer['stock'] ?></td>
							<td width="10%" style="vertical-align: middle;"><?=empty($offer['last_15_days_sold'])?0:$offer['last_15_days_sold'] ?></td>
							<?php if(empty($offer['concerned_status']) || $offer['concerned_status']=='N' ){
								$frequency_key = 'N';
								$shoucangIcon = 'icon-shoucang-1';
								$shoucangTitle = '未关注';
							}elseif($offer['concerned_status']=='H'){
								$frequency_key = 'H';
								$shoucangIcon = 'icon-baokuan';
								$shoucangTitle = '爆款';
							}elseif($offer['concerned_status']=='F'){
								$frequency_key = 'F';
								$shoucangIcon = 'icon-shoucang-2';
								$shoucangTitle = '已关注';
							}else{
								$frequency_key = 'I';
								$shoucangIcon = 'icon-toggle_collapse';
								$shoucangTitle = '已忽略';
							}?>
							<td width="5%" style="vertical-align: middle;">
								<?php if($frequency_key=='N'){?>
								<i class="iconfont icon-baokuan" title="点击标记成爆款监视，3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<i class="iconfont icon-shoucang-1" title="未关注，点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<i class="iconfont icon-toggle_collapse" title="未关注，点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
								<?php }elseif($frequency_key=='F'){?>
								<i class="iconfont icon-shoucang-2" title="已关注，点击取消关注，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unConcerned(<?=$offer['id']?>)"></i>
								<i class="iconfont icon-baokuan" title="点击标记成爆款监视，3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<i class="iconfont icon-toggle_collapse" title="已关注，点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
								<?php }elseif($frequency_key=='I'){?>
								<i class="iconfont icon-toggle_collapse" style="color:orange;" title="已忽略，点击取消忽略，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unIgnore(<?=$offer['id']?>)"></i>
								<i class="iconfont icon-shoucang-1" title="已忽略，点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<i class="iconfont icon-baokuan" title="点击标记成爆款监视，3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<?php }elseif ($frequency_key=='H'){?>
								<i class="iconfont icon-baokuan icon-active" title="点击取消爆款监视，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unHotSale(<?=$offer['id']?>)"></i>
								<i class="iconfont icon-shoucang-1" title="点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
								<i class="iconfont icon-toggle_collapse" title="点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
								<?php } ?>
								<?php if($offer['terminator_active']=='N'){ ?>
								<i class="iconfont icon-jinggao" style="color:#FB8E92" title="vip等级下降导致超出限额，系统随机失效了此商品的爆款监视/关注状态，改为6天更新一次。点击恢复(视乎还有没有可用额度)" onclick="cdOffer.list.reActive('<?=$offer['id'].'@@'.$offer['seller_id'].'@@'.$offer['concerned_status'] ?>')"></i>
								<?php }?>
								<br>
								<button type="button" style="display:inline-block;" class="btn btn-default btn-xs" onclick="cdOffer.list.view_offer(<?=$offer['id']?>)" >详情</button>
								<?php if(!empty($offer['product_url'])) echo "<button type='button' style='display:inline-block;' class='btn btn-default btn-xs' ><a href='".$offer['product_url']."' target='_blank'>网站</a></button>" ?>
								
							</td>
						</tr>
						<tr>
							<td>
							<?php if($frequency_key=='N'){?>
							未关注<br>
							<i class="iconfont icon-baokuan" title="点击标记成爆款监视，3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<i class="iconfont icon-shoucang-1" title="未关注，点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<i class="iconfont icon-toggle_collapse" title="未关注，点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
							<?php }if($frequency_key=='F'){?>
							已关注<br>
							<i class="iconfont icon-shoucang-2" title="已关注，点击取消关注，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unConcerned(<?=$offer['id']?>)"></i>
							<i class="iconfont icon-baokuan" title="点击标记成爆款监视，3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<i class="iconfont icon-toggle_collapse" title="已关注，点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
							<?php }if($frequency_key=='I'){?>
							已忽略<br>
							<i class="iconfont icon-toggle_collapse" style="color:orange;" title="已忽略，点击取消忽略，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unIgnore(<?=$offer['id']?>)"></i>
							<i class="iconfont icon-shoucang-1" title="已忽略，点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<i class="iconfont icon-baokuan" title="点击标记成爆款监视3小时同步一次" onclick="cdOffer.list.HotSale('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<?php }if($frequency_key=='H'){?>
							爆款监视<br>
							<i class="iconfont icon-baokuan icon-active" title="点击取消爆款监视，恢复成普通状态(未关注，6天同步一次)" onclick="cdOffer.list.unHotSale(<?=$offer['id']?>)"></i>
							<i class="iconfont icon-shoucang-1" title="点击标记成关注，6小时同步一次" onclick="cdOffer.list.Concerned('<?=$offer['id'].'@@'.$offer['seller_id']?>')"></i>
							<i class="iconfont icon-toggle_collapse" title="点击标记成忽略，不再同步" onclick="cdOffer.list.Ignore(<?=$offer['id']?>)"></i>
							<?php } ?>
							<?php if($offer['terminator_active']=='N'){ ?>
							<i class="iconfont icon-jinggao" style="color:#FB8E92" title="vip等级下降导致超出限额，系统随机失效了此商品的爆款监视/关注状态，改为6天更新一次。点击恢复(视乎还有没有可用额度)" onclick="cdOffer.list.reActive('<?=$offer['id'].'@@'.$offer['seller_id'].'@@'.$offer['concerned_status'] ?>')"></i>
							<?php }?>
							</td>
							<?php $photo_primary='';$photos=[];
								if(!empty($offer['img']))
									$photos = json_decode($offer['img'],true);
									if(!empty($photos[0]))
										$photo_primary = $photos[0];
								if(!empty($photo_primary)) $photo_primary = ImageCacherHelper::getImageCacheUrl($photo_primary,$uid,1);
							?>
							<td><img class="prod_img" src="<?=$photo_primary ?>" style="width:80px;height:80px;"  data-toggle="popover" data-content="<img src='<?=$photo_primary?>'>" data-html="true" data-trigger="hover"></td>
							<td colspan="2"><?=empty($offer['product_url'])?'':'<a href="'.$offer['product_url'].'" target="_blank">' ?><?=empty($offer['name'])?$offer['comments']:$offer['name'] ?><?=empty($offer['product_url'])?'':'</a>' ?></td>
							<td>商品所属店铺:<br><?php if(!empty($offer['seller_id'])){
								echo $offer['seller_id'];
							}else{
								if(!empty($offer['shopname']))
									echo $offer['shopname'];
							} ?></td>
							<td colspan="2">
								创建时间:<?=$offer['creation_date'] ?><br>
								<b style="color:red;font-weight:bolder;">抓取时间:<?=empty($offer['last_terminator_time'])?'--':$offer['last_terminator_time'] ?></b><br>
								<b style="color:red;font-weight:bolder;">抓取频率:<?=empty($frequency_description[$frequency_key])?'--':$frequency_description[$frequency_key] ?></b><br>
								商品状态:<b style="font:bold 12px SimSun,Arial;"><?=($offer['offer_state']=='Active' || empty($offer['offer_state']))?'在售':'非在售' ?><span qtipkey="cdTermininator_offer_state"></b>
							</td>
							<td colspan="2">
								商品ID: <?=$offer['product_id'] ?><br>
								商品EAN: <?=$offer['product_ean'] ?><br>
								品牌: <?=$offer['brand'] ?>
							</td>
							<td></td>
							<td>
								<button type="button" class="btn btn-primary" onclick="cdOffer.list.checkHistroy('<?=$offer['product_id'] ?>')" >历史价格</button>
							</td>
						</tr>
					</table>
				</td></tr>
				<?php endforeach;endif;?>
			</table>
			<?php if(!empty($_REQUEST['lostbs']) && !empty($product_id_arr)){
				CdiscountOfferTerminatorHelper::deleteProductIdFromLostBsRedis($product_id_arr);
				//如果打开了lostbs页面，则清除上次发送的邮件ProductId记录，下次自动邮件有任何新productId都发
				//\Yii::$app->redis->hdel("LastSentCdTerminatorAnnounceProductIds","user_$uid");
				RedisHelper::RedisDel("LastSentCdTerminatorAnnounceProductIds","user_$uid" );
			}?>
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
	<div class="show-account-list"></div>
	<div class="view-quota"></div>
	<div class="show-bestseller-histroy"></div>
	</div>
</div>
<script>
function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
}
</script>