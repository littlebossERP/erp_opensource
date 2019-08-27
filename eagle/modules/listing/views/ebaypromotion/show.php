<?php 

use yii\helpers\Html;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\modules\listing\models\EbayPromotion;
use yii\widgets\LinkPager;
use eagle\widgets\SizePager;


?>
<style>
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.doaction2{
	padding:3px;
	width:140px;
	margin:5px 0px;
}
.table a{
	color:rgb(170,170,170);
}
button{
	width:80px;
	margin:0px 15px;
}
.act{
	text-align:center;
}
.title{
	vertical-align:center;
	font-size:12px;
}
.row{
	margin-top:7px;
}
.discountvalue{
	margin-left:40px;
}
#selleruserid{
	width:150px;
}
p{
	margin-top:5px;
}
table button{
	border-width:0px;
	width:0px;
	background-color:#fff;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'促销规则']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
	<div class="mianbaoxie">
		<span></span>促销规则列表
	</div>
<!-- 搜索 -->
<div class="form-group mutisearch">
	<form action="" method="post" class="form-inline">
	<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>'iv-input','prompt'=>'eBay账号'])?>
	<?=Html::submitButton('查询',['class'=>'iv-btn btn-search'])?>
	</form>
</div>
<div class="table-action">
	<div class="pull-left">
	</div>
	<div class="pull-right">
	<?=Html::button('新规则',['class'=>'btn btn-warning doaction2','onclick'=>'javascript:doedit();'])?>
	<?=Html::button('同步在线规则',['class'=>'btn btn-warning doaction2','onclick'=>'javascript:sync();'])?>
	</div>
</div>
<!-- datalist -->
<table class="table table-condensed">
	<tr>
		<th>促销名</th><th>eBay账号</th><th>有效期</th><th>折扣</th><th>促销类型</th><th>状态</th><th>操作</th>
	</tr>
	<?php if (count($proms)):foreach ($proms as $prom):?>
	<tr>
		<td><?=$prom->promotionalsalename?></td>
		<td><?=$prom->selleruserid?></td>
		<td><?=date('Y-m-m H:i:s',$prom->promotionalsalestarttime)?>-<?=date('Y-m-d H:i:s',$prom->promotionalsaleendtime)?></td>
		<td>
			<?php if (!is_null($prom->discounttype)):?>
			<p>折扣类型:<?=EbayPromotion::$discounttype[$prom->discounttype]?></p>
			<p>折扣:<?=$prom->discountvalue?><?php if ($prom->discounttype == 'Percentage'){echo '%';}?></p>
			<?php endif;?>
		</td>
		<td><?=EbayPromotion::$promotiontype[$prom->promotionalsaletype]?></td>
		<td><?=$prom->status?></td>
		<td>
			<!-- <button onclick="doactionone('edit','<?=$prom->id?>');" title="编辑"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span></button>
			<span style="color:#ddd">|</span> -->
			<button onclick="doactionone('delete','<?=$prom->id?>');" title="删除"><span class="iconfont icon-guanbi"></span></button>
		</td>
	</tr>	
	<?php endforeach;endif;?>
</table>
<div class="btn-group" >
<?=LinkPager::widget([
    'pagination' => $pages,
]);
?>
</div>
<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
<div class="btn-group" >
</div>
</div>
</div>
</div>
</div>

<!-- 模态框（Modal） -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">创建促销规则</h4>
	      </div>
	      <div class="modal-body">
	      	<div class="addform">
				<div class="row">
					<div class="col-md-2" class="title">名称</div>
					<div class="col-md-10"><?=Html::textInput('pname','',['class'=>'iv-input'])?></div>
				</div>
				<div class="row">
					<div class="col-md-2" class="title">账号</div>
					<div class="col-md-10"><?=Html::dropDownList('selleruserid','',Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>'iv-input','id'=>'selleruserid'])?></div>
				</div>
				<div class="row">
					<div class="col-md-2" class="title">时间</div>
					<div class="col-md-10">
						<p><?=Html::input('date','startdate','',['class'=>'iv-input'])?><?=Html::input('time','starttime','',['class'=>'iv-input'])?> </p>
						<p><?=Html::input('date','enddate','',['class'=>'iv-input'])?><?=Html::input('time','endtime','',['class'=>'iv-input'])?></p>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2" class="title">优惠明细</div>
					<div class="col-md-10">
					<?=Html::radio('promotionalsaletype','true',['value'=>'PriceDiscountOnly','onclick'=>'choosepro("PriceDiscountOnly")'])?>价格优惠<br>
						<div id='PriceDiscountOnly' class='discountvalue'>
						<?=Html::radio('discounttype_only','true',['value'=>'Percentage'])?>比例折扣 <?=Html::textInput('discountvaluepercent_only','',['class'=>'iv-input'])?>%<br>
						<?=Html::radio('discounttype_only','',['value'=>'Price'])?>金额折扣 <?=Html::textInput('discountvalueprice_only','',['class'=>'iv-input'])?>
						</div>
					<?=Html::radio('promotionalsaletype','',['value'=>'PriceDiscountAndFreeShipping','onclick'=>'choosepro("PriceDiscountAndFreeShipping")'])?>价格优惠且免运费(第一运输)<br>
						<div id='PriceDiscountAndFreeShipping' class='discountvalue' style="display:none;">
						<?=Html::radio('discounttype_shipping','true',['value'=>'Percentage'])?>比例折扣 <?=Html::textInput('discountvaluepercent_shipping','',['class'=>'iv-input'])?>%<br>
						<?=Html::radio('discounttype_shipping','',['value'=>'Price'])?>金额折扣 <?=Html::textInput('discountvalueprice_shipping','',['class'=>'iv-input'])?>
						</div>
					<?=Html::radio('promotionalsaletype','',['value'=>'FreeShippingOnly','onclick'=>'choosepro("FreeShippingOnly")'])?>免运费(第一运输)
					</div>
				</div>
			</div>
			<div class="act">
				<button class="iv-btn btn-search" onclick="dosubmit()">确定</button>
				<button class="iv-btn btn-default" data-dismiss="modal">取消</button>
			</div>
	      </div>  
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>

<!-- 模态框（Modal） -->
<div class="modal fade" id="syncModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
	       
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>

<script>
//同步在线规则
function sync(){
	$.showLoading();
	$.get("<?=Url::to(['/listing/ebaypromotion/sync'])?>",{},function(result){
		$.hideLoading();
		$('#syncModal .modal-content').html(result);
       	$('#syncModal').modal('show');
	});
}
//添加新规则
function doedit(){
	$('#editModal').modal('show');
}
function choosepro(str){
	$('.discountvalue').hide();
	$('#'+str).show();
}
//提交前进行数据的检测
function dosubmit(){
	//检测卖家账号选择
	var selleruserid = $('#selleruserid').val();
	if(selleruserid == null){
		bootbox.alert('请先绑定eBay账号');return false;
	}
	//检测规则名
	var pname = $('input[name=pname]').val();
	if(pname == ''){
		bootbox.alert('请输入促销规则名,方便区分');return false;
	}
	//检测输入的时间
	var startdate = $('input[name=startdate]').val();
	var enddate = $('input[name=enddate]').val();
	var starttime = $('input[name=starttime]').val();
	var endtime = $('input[name=endtime]').val();
	if(startdate == '' || enddate == '' || starttime == '' || endtime == '' ){
		bootbox.alert('开始与结束时间务必都有填写');return false;
	}

	//优惠值
	var promotionalsaletype = $('input[name=promotionalsaletype]:checked').val();
	
	var discounttype_only = $('input[name=discounttype_only]:checked').val();
	var discounttype_shipping = $('input[name=discounttype_shipping]:checked').val();
	
	var discountvaluepercent_only = $('input[name=discountvaluepercent_only]').val();
	var discountvalueprice_only = $('input[name=discountvalueprice_only]').val();
	var discountvaluepercent_shipping = $('input[name=discountvaluepercent_shipping]').val();
	var discountvalueprice_shipping = $('input[name=discountvalueprice_shipping]').val();
	if(promotionalsaletype == 'PriceDiscountOnly'){
		if(discounttype_only == 'Percentage' && discountvaluepercent_only == ''){
			bootbox.alert('请务必填写价格优惠选项的比例折扣值');return false;
		}
		if(discounttype_only == 'Price' && discountvalueprice_only == ''){
			bootbox.alert('请务必填写价格优惠选项的金额折扣值');return false;
		}
	}
	if(promotionalsaletype == 'PriceDiscountAndFreeShipping'){
		if(discounttype_shipping == 'Percentage' && discountvaluepercent_shipping == ''){
			bootbox.alert('请务必填写价格优惠且免运费选项的比例折扣值');return false;
		}
		if(discounttype_shipping == 'Price' && discountvalueprice_shipping == ''){
			bootbox.alert('请务必填写价格优惠且免运费选项的金额折扣值');return false;
		}
	}

	$.showLoading();
	$.post("<?=Url::to(['/listing/ebaypromotion/ajax-add'])?>",{pname:pname,selleruserid:selleruserid,startdate:startdate,enddate:enddate,
		starttime:starttime,endtime:endtime,promotionalsaletype:promotionalsaletype,discounttype_only:discounttype_only,discounttype_shipping:discounttype_shipping,
		discountvaluepercent_only:discountvaluepercent_only,discountvalueprice_only:discountvalueprice_only,
		discountvaluepercent_shipping:discountvaluepercent_shipping,discountvalueprice_shipping:discountvalueprice_shipping},function(result){
		$.hideLoading();
		if(result=='success'){
			bootbox.alert('设置保存成功');
			window.location.reload();
		}else{
			bootbox.alert('设置保存失败:'+result);
		}
	});
}

//单个规则的操作
function doactionone(type,id){
	if(type == 'delete'){
		$.showLoading();
		$.post("<?=Url::to(['/listing/ebaypromotion/delete'])?>",{id:id},function(result){
			$.hideLoading();
			if(result == 'success'){
				bootbox.alert('操作已完成');
				window.location.reload();
			}
		});
	}
}
</script>