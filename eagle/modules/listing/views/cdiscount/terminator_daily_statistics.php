<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\util\helpers\ConfigHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("cdOffer.list.init()", \yii\web\View::POS_READY);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$uid = \Yii::$app->user->id;


?>
<style>
.offer-list > tbody > tr:hover {
    background-color: #B9D6E8!important;
}
.offer-list td{
    text-align: center!important;
	vertical-align: middle!important;
	/*padding:0px!important;*/
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

</style>

<div class="tracking-index col2-layout">
	<?=$this->render('_leftmenu',['counter'=>$counter]);?>
	<div class="" style="padding-top: 10px;">
		<div>
			<!-- 
			<div style="width:100%;display:inline-block">
				<?php $canSendMail = ConfigHelper::getConfig("Listing/send_cd_terminator_mail",'NO_CACHE');?>
				<div style="font-size:14px;padding:5px 5px;float:left;margin:0px;" class="alert alert-success" role="alert">
					<label>是否接收提示邮件：</label>
					<label for="send_Y">是</label><input type="radio" name="can_send_mail" id="send_Y" value="Y" <?=(empty($canSendMail) || $canSendMail=='Y')?'checked':''?> ><span style="margin:0px 5px;"></span>
					<label for="send_N">否</label><input type="radio" name="can_send_mail" id="send_N" value="N" <?=(!empty($canSendMail) && $canSendMail=='N')?'checked':''?>><span style="margin:0px 5px;"></span>
					<button type="button" onclick="setSendMailConfig()" class="btn-xs btn-primary">设置</button>
					<span qtipkey="send_terminator_statistics_mail"></span>
				</div>
			</div>
			 -->
			<!-- 搜索区域 -->
			<form class="form-inline" id="form1" name="form1" action="/listing/cdiscount/daily-statistics" method="post" style="float:left;">
			<div style="margin:5px">
				<?=Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$accounts,['class'=>'form-control input-sm','id'=>'seller_id','style'=>'margin:0px','prompt'=>'店铺名称'])?>
				<?php 
					$type=['H'=> '爆款' , 'F'=>'关注','N'=>'普通'];
					$been_surpassed=['Y'=> '当日曾被抢走BestSeller位置' , 'N'=>'当日没有抢走BestSeller位置', ];
				?>
				<?=Html::dropDownList('type',@$_REQUEST['type'],$type,['class'=>'form-control input-sm','id'=>'type','style'=>'margin:0px','prompt'=>'监视等级'])?>
				<?=Html::dropDownList('been_surpassed',@$_REQUEST['been_surpassed'],$been_surpassed,['class'=>'form-control input-sm','id'=>'ever_been_surpassed','style'=>'margin:0px','prompt'=>'当日被抢走过BestSeller?'])?>
				统计日期: <?=Html::input('date','date',@$_REQUEST['date'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
				
				<div class="input-group">
			      	<?=Html::textInput('key_word',@$_REQUEST['key_word'],['class'=>'form-control input-sm','id'=>'keyword','style'=>'width:300px','placeholder'=>'输入SKU,CD平台商品id搜索,多个之间用;分隔'])?>
			    </div>
			    
			    <?=Html::submitButton('搜索',['class'=>"btn-xs",'id'=>'search'])?>
		    	<?=Html::button('重置',['class'=>"btn-xs",'onclick'=>"javascript:cleform();"])?>
		    	
				
		    </div>
			</form>
			<div class="input-group" style="float:left;width:100%;margin:5px 0px;">批量操作：
				
			</div>

		</div>
		<br>
		<div style="">
			<table class="table table-condensed table-bordered offer-list" style="font-size:12px;">
				<tr>
					<!-- <th width="3%"><input id="ck_all" class="ck_all" type="checkbox"></th> -->
					<th width="10%">图片</th>
					<th width="10%">SKU</th>
					<th width="20%">商品名称</th>
					<th width="10%">所属店铺</th>
					<th width="10%">统计日期</th>
					<th width="10%">监视等级</th>
					<th width="10%">当日被抢过BestSeller?</th>
					<th width="20%">BestSeller变更信息</th>
				</tr>
				<?php $index=0; ?>
				<?php if (count($data)):foreach ($data as $offer):?>
				<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?> >
				<?php $index++; ?>
					<!-- 
					<td width="3%" style="vertical-align: middle;">
						<input name="offer_id[]" class="ck_one" type="checkbox" value="<?=$offer['id'] ?>" data-sku="<?=$offer['seller_product_id'] ?>" data-seller="<?=$offer['seller_id'] ?>"> 
					</td>
					 -->
					<?php $photo_primary='';
						if(!empty($offer['img']))
							$photo_primary =$offer['img'];
						$photo_primary = ImageCacherHelper::getImageCacheUrl($photo_primary,$uid,1);
					?>
					<td><img class="prod_img" src="<?=$photo_primary ?>" style="width:80px;height:80px;"  data-toggle="popover" data-content="<img src='<?=$photo_primary?>'>" data-html="true" data-trigger="hover"></td>
					<td>
						SKU: <?=$offer['seller_product_id'] ?><br>
						商品ID: <?=$offer['product_id'] ?>
					</td>
					<td><?=empty($offer['product_url'])?'':'<a href="'.$offer['product_url'].'" target="_blank">' ?><?=empty($offer['name'])?$offer['comments']:$offer['name'] ?><?=empty($offer['product_url'])?'':'</a>' ?></td>
					<td>
						店铺名称: <br><?=$offer['shop_name'] ?><br>
						店铺账号: <br><?=$offer['seller_id'] ?><br>
					</td>
					<td><?=$offer['date'] ?></td>
					<td><?=isset($type[$offer['type']])?$type[$offer['type']]:$offer['type'] ?></td>
					<td><?=isset($been_surpassed[$offer['ever_been_surpassed']])?$been_surpassed[$offer['ever_been_surpassed']]:'N/A' ?></td>
					<td>
					<?php 
						$tmp_his = '';
						$change_str = '';
						$hidden_html = '';
						if(!empty($offer['change_history']))
							$tmp_his = json_decode($offer['change_history'],true);
						if(!empty($tmp_his) && is_array($tmp_his)){
							$tmp_sroted_his=[];
							foreach($tmp_his as $his){
								$tmp_sroted_his[$his['time']] = $his;
							}
							krsort($tmp_sroted_his);
							//rsort($tmp_his);//原始数据是按时间升序排序的，展示的时候按降续
							$his_count = 0;
							foreach ($tmp_sroted_his as $his){
								$his_count++;
								$tmp_html = '';
								$tmp_html .= $his['time'].':<br>';
								if(in_array($his['bestseller_name'], array_keys($shop_names))){
									if($his['is_bestseller']=='Y')
										$tmp_html .= '<span style="color:green">您  获得了BestSeller</span>';
									elseif($his['is_bestseller']=='O')
									$tmp_html .= '<span style="color:orange">您的其他店铺'.(empty($shop_names[$his['bestseller_name']])?$his['bestseller_name']:$shop_names[$his['bestseller_name']]).' 获得了BestSeller</span>';
								}else{
									if($his['bestseller_name']!=='--')
										$tmp_html .= '<span style="color:red">'.$his['bestseller_name'].' 获得了BestSeller</span>';
									else
										$tmp_html .= '<span style="">未获取到BestSeller信息</span>';
								}
								if($his['bestseller_name']!=='--')
									$tmp_html .= '('.$his['bestseller_price'].' €)<br>';
								else
									$tmp_html .= '<br>';
								
								if($his_count<=3)
									$change_str .=  $tmp_html;
								if($his_count==4 && count($tmp_his)){
									//$change_str .=  $tmp_html;
									$change_str .= '<div style="width:100%"><a class="btn-info btn-xs" onclick="show_hidden_history('.$index.')">查看全部</a></div>';
								}
								
								$hidden_html .= $tmp_html;
								
							}
						}
						echo $change_str;
						echo '<div style="display:none" id="hidden_history_detail_'.$index.'">'.$hidden_html.'</div>';
					?>
					</td>
				</tr>
				<tr style="background-color: #d9d9d9;">
					<td colspan="8" class="row"  style="border:1px solid #d1d1d1;padding:3px">
					</td>
				</tr>
				<?php endforeach;endif;?>
			</table>
			<?php if(! empty($pagination)):?>
			<div>
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			    <div class="btn-group" style="width: 49.6%;text-align: right;">
			    	<?=\yii\widgets\LinkPager::widget(['pagination'=>$pagination,'options'=>['class'=>'pagination']]);?>
				</div>
			</div>
			<?php endif;?>
		</div>
	
	<div style="clear: both;"></div>
	<div class="show-full-history-detail"></div>
	</div>
</div>
<script>
function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
}
function show_hidden_history(index){
	var id = '#hidden_history_detail_'+index;
	var html = $(id).html();
	bootbox.dialog({
		className : "show-full-history-detail",
		title: "",//Translator.t("添加商品")
		message: html,
		buttons:{
			Cancel: {  
				label: Translator.t("返回"),  
				className: "btn-default",  
				callback: function () {  
				}
			},  
		}
	});
}
function setSendMailConfig(){
	var canSend = $("input[name='can_send_mail']:checked").val();
	$.showLoading();
	$.ajax({
		type: "POST",
		dataType:'json',
		url:'/listing/cdiscount/set-send-mail-config',
		data:{can_send_mail:canSend},
		success: function (result) {
			$.hideLoading();
			if(result.success==true){
				bootbox.alert(Translator.t('设置成功，即将刷新页面'));
				window.location.reload();
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}
</script>