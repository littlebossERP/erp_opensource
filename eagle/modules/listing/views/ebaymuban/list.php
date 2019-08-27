<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;
?>
<style>
.do{
	border-radius:3px;
	padding:3px;
}
.doaction{
	border-width:0px;
	padding:3px;
	margin:0px 10px 10px 0px;
	color:rgb(102,102,102);
}
.doaction2{
	padding:3px;
	width:140px;
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
.quantity{
	margin:0px 3px;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'范本列表']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
<!-- 搜索 -->
<div>
<form action="" method="post" class="form-inline">
	<div class="dianpusearch form-inline">
		<span class="iconfont icon-dianpu"></span><?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
		<button type="submit" class="iv-btn btn-search">
			GO	
		</button>
	</div>
	<div class="mianbaoxie">
		<span></span>详细范本列表
	</div>
	<div class="mutisearch">
		<?=Html::textInput('sku',@$_REQUEST['sku'],['placeholder'=>'SKU','class'=>"iv-input"])?>
		<?=Html::textInput('paypal',@$_REQUEST['paypal'],['placeholder'=>'PayPal','class'=>"iv-input"])?>
		<?=Html::textInput('itemtitle',@$_REQUEST['itemtitle'],['placeholder'=>'标题','class'=>"iv-input"])?>
		<?=Html::textInput('desc',@$_REQUEST['desc'],['placeholder'=>'备注','class'=>"iv-input"])?>
		<?=Html::dropDownList('listingtype',@$_REQUEST['listingtype'],['Chinese'=>'拍卖','FixedPriceItem'=>'一口价'],['prompt'=>'刊登类型','class'=>"iv-input"])?>
		<?=Html::dropDownList('siteid',@$_REQUEST['siteid'],Helper_Siteinfo::getEbaySiteIdList('no','en'),['prompt'=>'平台','class'=>"iv-input"])?>
		<?=Html::dropDownList('isvariation',@$_REQUEST['isvariation'],['0'=>'否','1'=>'是'],['prompt'=>'多属性','class'=>"iv-input"])?>
		<?=Html::dropDownList('outofstockcontrol',@$_REQUEST['outofstockcontrol'],['0'=>'否','1'=>'是'],['prompt'=>'永久在线','class'=>"iv-input"])?>
		<?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
	</div>
</form>
</div>
<form name="a" id="a" action="" method="post">
<div class="table-action">
	<div class="pull-left">
	<button type="button" class='btn btn-default doaction' onclick="doaction('deletemuban');"><span class="iconfont icon-shanchu"></span>删除</button>
	<button type="button" class='btn btn-default doaction' onclick="doaction('list');"><span class="iconfont icon-lijikandeng"></span>立即刊登</button>
	<button type="button" class='btn btn-default doaction' onclick="doaction('additemset');"><span class="iconfont icon-zengjiadingshi"></span>添加定时</button>
	<button type="button" class='btn btn-default doaction' onclick="doaction('deleteitemset');"><span class="iconfont icon-shanchudingshi"></span>删除定时</button>
	<button type="button" class='btn btn-default doaction' onclick="doaction('verify');"><span class="iconfont icon-jiance"></span>检测</button>
	</div>
	<div class="pull-right">
	<?=Html::button('新建刊登范本',['class'=>'btn btn-warning doaction2','onclick'=>"window.open('".Url::to(['/listing/ebaymuban/edit'])."')"])?>
	</div>
</div>
<table class="table table-condensed">
	<tr>
		<th><div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">全选
		</div></th>
		<th>范本编号</th><th>缩略图</th><th>标题 </th><th>SKU</th><th>刊登方式</th><th>数量</th><th>拍卖价</th>
		<th>一口价</th><th>卖家账号</th><th>多属性</th><th>PayPal</th><th>永久在线</th><th>操作</th>
	</tr>
	<?php if (count($mubans)):foreach ($mubans as $muban):?>
	<tr>
	<td><input type="checkbox" class="ck" name="muban_id[]" value="<?=$muban->mubanid?>"></td>
	<td><?=$muban->mubanid?></td>
	<td><img src="<?=$muban->mainimg?>" width="50px" height="50px"></td>
	<td>
		<?=$muban->itemtitle?><br/>
		<b><?=$muban->desc?></b>
	</td>
	<td><?=$muban->sku?></td>
	<td><?=$muban->listingtype=='Chinese'?'拍卖':'一口价'?></td>
	<td><?=$muban->quantity?></td>
	<td><?=$muban->startprice?></td>
	<td><?=$muban->buyitnowprice?></td>
	<td><?=$muban->selleruserid?></td>
	<td><?=$muban->isvariation==1?'√':'X'?></td>
	<td><?=$muban->paypal?></td>
	<td><?=$muban->outofstockcontrol==1?'√':'X'?></td>
	<td>
	<?php $doarr_one=[
			'edit'=>'编辑',
			'delete'=>'删除',
			'listnow'=>'立即刊登',
			// 'list'=>'定时刊登',
			'history'=>'记录',
		];
		?>
	<?=Html::dropDownList('do','',$doarr_one,['onchange'=>"doactionone($(this).val(),'".$muban->mubanid."');",'class'=>'do','style'=>'width:70px;','prompt'=>'操作','onmousedown'=>'$(this).val("")']);?>
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
</form>
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
		case 'edit':
			window.open("<?=Url::to(['/listing/ebaymuban/edit'])?>"+"?mubanid="+id,'_blank')
			break;
		case 'delete':
			if(confirm('确定删除该范本吗?')){
				$.showLoading();
				$.post('<?=Url::to(['/listing/ebaymuban/delete'])?>',{mubanid:id},function(r){
					$.hideLoading();
					if(r=='success'){
						bootbox.alert('操作已成功');
						window.location.reload();
					}else{
						bootbox.alert('操作失败'+r);
					}
				  });
			}else{
				return false;
			}
			break;
		case 'listnow':
			window.open("<?=Url::to(['/listing/ebaymuban/listselectadd'])?>"+"?mubanid="+id,'_blank');
			break;
		case 'list':
			window.open("<?=Url::to(['/listing/ebaymuban/additemset'])?>"+"?mubanid="+id,'_blank');
			break;
		case 'history':
			window.open("<?=Url::to(['/listing/ebaymuban/history'])?>"+"?mubanid="+id,'_blank');
			break;
	}
}

//批量操作
function doaction(val){
	//如果没有选择订单，返回；
	if(val==""){
        return false;
    }
    if($('.ck:checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的订单");return false;
    }
    idstr='';
	$('input[name="muban_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
    switch(val){
    	case 'deletemuban':
        	$.showLoading();
    		$.post('<?=Url::to(['/listing/ebaymuban/delete'])?>',{mubanid:idstr},function(result){
        		$.hideLoading();
				if(result == 'success'){
					location.reload();
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert(result);
				}
			});
        	break;
    	case 'list':
    		if(idstr.substr(0,1)==','){
				idstr=idstr.substr(1);
           	}
    		window.open("<?=Url::to(['/listing/ebaymuban/listselectadd'])?>"+"?mubanid="+idstr,'_blank');
        	break;
    	case 'additemset':
        	if(idstr.substr(0,1)==','){
				idstr=idstr.substr(1);
           	}
    		window.open("<?=Url::to(['/listing/ebaymuban/additemset'])?>"+"?mubanid="+idstr,'_blank');
        	break;
    	case 'deleteitemset':
    		$.showLoading();
    		$.post('<?=Url::to(['/listing/additemset/delete'])?>',{mubanid:idstr},function(result){
    			$.hideLoading();
				if(result == 'success'){
					location.reload();
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert(result);
				}
			});
        	break;
    	case 'verify':
    		if(idstr.substr(0,1)==','){
				idstr=idstr.substr(1);
           	}
    		window.open("<?=Url::to(['/listing/ebaymuban/listselectverify'])?>"+"?mubanid="+idstr,'_blank');
        	break;
		case 'signshipped':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/signshipped'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				document.a.target="_blank";
				document.a.action="<?=Url::to(['/order/order/deleteorder'])?>";
				document.a.submit();
				document.a.action="";
			}
			break;
		default:
			return false;
			break;
	}
}
</script>