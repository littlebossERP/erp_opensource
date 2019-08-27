<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;
?>
<style>
.doaction1{
	font-size:10px;
	width:220px;
}
.doaction2{
	font-size:10px;
	width:120px;
}
.dianpusearch span{
	vertical-align:middle;
}
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.mutisearch{
	margin:10px 0px;
}
.table-action button{
	padding:3px;
	margin:0px 0px 10px 0px;
}
.quantity{
	margin:0px 3px;
}
/* .table th{ */
/* 	background-color:rgb(153,153,153); */
/* 	color:#fff; */
/* } */
/* .table th:first-child{ */
/* 	border-radius:7px 0px 0px 0px; */
/* } */
/* .table th:last-child{ */
/* 	border-radius:0px 7px 0px 0px; */
/* } */
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'下线Item']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
<!-- 搜索 -->
<div>
<form action="" method="get">
	<div class="dianpusearch form-inline">
		<span class="iconfont icon-dianpu"></span><?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
		<button type="submit" class="iv-btn btn-search">
			GO	
		</button>
	</div>
	<div class="mianbaoxie">
		<span></span>下线Item列表
	</div>
	<div class="mutisearch">
		<?=Html::textInput('itemid',@$_REQUEST['itemid'],['placeholder'=>'ItemID','class'=>"iv-input"])?>
		<?=Html::textInput('sku',@$_REQUEST['sku'],['placeholder'=>'SKU','class'=>"iv-input"])?>
		<?=Html::textInput('itemtitle',@$_REQUEST['itemtitle'],['placeholder'=>'刊登标题','class'=>"iv-input"])?>
		<?=Html::dropDownList('listingtype',@$_REQUEST['listingtype'],['Chinese'=>'拍卖','FixedPriceItem'=>'一口价'],['prompt'=>'刊登类型','class'=>"iv-input"])?>
		<?=Html::dropDownList('site',@$_REQUEST['site'],Helper_Siteinfo::getEbaySiteIdList('en','en'),['prompt'=>'eBay站点','class'=>"iv-input"])?>
		<?=Html::dropDownList('hassold',@$_REQUEST['hassold'],['0'=>'否','1'=>'是'],['prompt'=>'有售出','class'=>"iv-input"])?>
		<?=Html::dropDownList('outofstockcontrol',@$_REQUEST['outofstockcontrol'],['0'=>'否','1'=>'是'],['prompt'=>'永久在线','class'=>"iv-input"])?>
		<?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
		</div>
</form>
</div>
<div class="table-action">
	<div class="pull-left">
	<button type="button" class='btn btn-default doaction2' onclick='javascript:mltirelist();'><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>批量重上</button>
	<button type="button" class='btn btn-default doaction2' onclick='javascript:mltidel();'><span class="iconfont icon-shanchu"></span>批量删除</button>
	</div>
	<div class="pull-right">
	</div>
</div>
<table class="table">
	<thead>
	<tr>
		<th width="3%"><div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		</div></th>
		<th width="8%">ItemID </th><th width="8%">缩略图</th><th width="15%">标题</th><th width="6%">SKU</th><th width="8%">eBay账号</th><th width="6%">刊登方式</th>
		<th width="4%">站点</th><th width="8%">当前价格</th><th width="6%">数量</th><th width="14%">开始结束时间</th><th width="8%">操作</th>
	</tr>
	</thead>
	<?php if (count($items)):foreach ($items as $item):?>
	<tr>
		<td><input type="checkbox" class="ck" name="itemid[]" value="<?=$item->itemid?>"></td>
		<td><a href="<?=$item->viewitemurl?>"><?=$item->itemid?></a></td>
		<td><img src="<?=$item->mainimg?>" width="60px" height="60px"></td>
		<td><?=$item->itemtitle?></td>
		<td><?=$item->sku?></td>
		<td><?=$item->selleruserid?></td>
		<td><?=$item->listingtype=='Chinese'?'拍卖':'一口价'?></td>
		<td><?=$item->site?></td>
		<td><?=$item->currentprice?>&nbsp;<?=$item->currency?></td>
		<td>
		库存<span class="quantity"><?=$item->quantity-$item->quantitysold?></span><br>
		售出<span class="quantity"><?=$item->quantitysold?></span>
		</td>
		<td><?=strlen($item->starttime)?date('Y-m-d H:i:s',$item->starttime):''?><br>
			<?=strlen($item->endtime)?date('Y-m-d H:i:s',$item->endtime):''?>
		</td>
		<td>
			<?php 
				$do=[
					'relist'=>'立即重上',
					'relistmodify'=>'修改重上',
					'history'=>'修改记录'
				];
			?>
			<?=Html::dropDownList('do','',$do,['onchange'=>"doactionone($(this).val(),'".$item->itemid."');",'class'=>'iv-input do','style'=>'width:70px;','prompt'=>'操作']);?>
		</td>
	</tr>
	<?php endforeach;endif;?>
</table>
<?php echo LinkPager::widget(['pagination' => $pages]);?>
<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
</div>
</div>
<script>
function checkall(){
	if($("#ck_all").attr("ck")=="2"){
		$(".ck").prop("checked","checked");
		$("#ck_all").attr("ck","1");
	}else{
		$(".ck").prop("checked",false);
		$("#ck_all").attr("ck","2");
	}
}			
//单独的操作
function doactionone(obj,id){
	switch(obj){
		case 'relist':
			$.showLoading();
			$.post('<?=Url::to(['/listing/ebayitem/relist'])?>',{itemid:id},function(r){
				$.hideLoading();
				if(r=='success'){
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert(r);
				}
			  });
			break;
		case 'relistmodify':
			window.open("<?=Url::to(['/listing/ebayitem/relistmodify'])?>"+"?itemid="+id,'_blank')
			break;
		case 'history':
			window.open("<?=Url::to(['/listing/ebayitem/history'])?>"+"?itemid="+id,'_blank');
			break;
		default:
			break;
	}
}

//批量重上
function mltirelist(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});

	doactionone('relist',idstr);
}

//批量删除
function mltidel(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});

	$.showLoading();
	$.post('<?=Url::to(['/listing/ebayitem/mltidel'])?>',{itemid:idstr},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(r);
		}
	  });
}
</script>