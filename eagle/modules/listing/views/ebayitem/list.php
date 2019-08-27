<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;
$puid = \Yii::$app->user->identity->getParentUid();
?>
<style>
.do{
	border-radius:3px;
	padding:3px;
}
.doaction1{
	width:220px;
}
.doaction2{
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
.paixu{
	cursor:pointer;
	font-size:12px;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>

<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
<!-- 搜索 -->
<div>
<form action="" method="post" name="a" id="a">
	<div class="dianpusearch form-inline">
		<span class="iconfont icon-dianpu"></span><?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
		<button type="submit" class="iv-btn btn-search">
			GO	
		</button>
	</div>
	<div class="mianbaoxie">
		<span></span>在线Item列表
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
		<?=Html::hiddenInput('xu',@$_REQUEST['xu'],['id'=>'xu'])?>
		<?=Html::hiddenInput('xusort',@$_REQUEST['xusort'],['id'=>'xusort'])?>
</form>
</div>
<div class="table-action">
	<div class="pull-left">
		<div class="dropdown">
		  <button class="btn btn-default dropdown-toggle doaction2" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		  <span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>  批量操作<span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
		    <li><a href="#" onclick='javascript:mltichangeall();'>批量修改</a></li>
		    <li><a href="#" onclick='javascript:addpromotion();'>添加促销</a></li>
		    <li><a href="#" onclick='javascript:mltichange();'>修改在线刊登数量价格(一口价)</a></li>
		    <li><a href="#" onclick='javascript:mltiend();'>批量下架</a></li>
		    <?php if($puid == 297):?>
		    <li><a href="#" onclick='javascript:mltibukucun();'>设置补库存</a></li> 
		    <?php endif;?>
		  </ul>
		</div>
<!-- <button type="button" class='btn btn-default doaction2' onclick='javascript:mltichangeall();'><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>批量修改</button>
	<button type="button" class='btn btn-default doaction2' onclick='javascript:addpromotion();'><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>添加促销</button>
	<button type="button" class='btn btn-default doaction1' onclick='javascript:mltichange();'><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>修改在线刊登数量价格(一口价)</button> -->	
	</div>
	<div class="pull-right">
	<?=Html::button('手动同步',['class'=>'btn btn-warning doaction2','onclick'=>'javascript:mtsyncitem();'])?>
	</div>
</div>
<table class="table">
	<thead>
	<tr>
		<th width="3%"><div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		</div></th>
		<th width="8%">ItemID </th><th width="8%">缩略图</th><th width="15%">标题</th><th width="6%">SKU</th><th width="8%">eBay账号</th><th width="6%">刊登方式</th>
		<th width="4%">站点</th>
		<th width="8%">当前价格<span class="iconfont <?php if (@$_REQUEST['xu'] == 'price' && @$_REQUEST['xusort']=='asc'){echo 'icon-shengxu';}else{echo 'icon-jiangxu';}?> paixu" onclick="doxu('price')"></span></th>
		<th width="6%">数量<span class="iconfont <?php if (@$_REQUEST['xu'] == 'quantity' && @$_REQUEST['xusort']=='asc'){echo 'icon-shengxu';}else{echo 'icon-jiangxu';}?> paixu" onclick="doxu('quantity')"></span></th>
		<th width="14%">开始结束时间</th><th width="8%">操作</th>
	</tr>
	</thead>
	<?php if (count($items)):foreach ($items as $item):?>
	<tr>
		<td><input type="checkbox" class="ck" name="itemid[]" value="<?=$item->itemid?>"></td>
		<td>
		<a target="_blank" href="<?=in_array($item->site,Helper_Siteinfo::getSite())?Helper_Siteinfo::getSiteViewUrl()[$item->site].$item->itemid:$item->viewitemurl?>"><?=$item->itemid?></a></td>
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
			        'update'=>'修改',
			        'sync'=>'同步',
			        'close'=>'下架',
			        // 'bukucun'=>'补库存',
			        'saveasfanben'=>'另存为范本',
			        'addfitment'=>'添加汽配信息',
			        'history'=>'修改记录'
			    ];
			?>
			<?=Html::dropDownList('do','',$do,['onchange'=>"doactionone($(this).val(),'".$item->itemid."');",'class'=>'do','prompt'=>'操作','onmousedown'=>'$(this).val("")']);?>
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

<!-- 关闭的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="closeModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>

<!-- 手动同步的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="syncModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
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
		case 'update':
			window.open("<?=Url::to(['/listing/ebayitem/update'])?>"+"?itemid="+id,'_blank')
			break;
		case 'sync':
			$.showLoading();
			$.post('<?=Url::to(['/listing/ebayitem/sync'])?>',{itemid:id},function(r){
				$.hideLoading();
				if(r=='success'){
					bootbox.alert('操作已成功');
					window.location.reload();
				}else{
					bootbox.alert('操作失败:'+r);
				}
			  });
			break;
		case 'close':
			closeitem(id);
			break;
		case 'bukucun':
			bukucun(id);
			break;
		case 'addfitment':
			addfitment(id);
			break;
		case 'saveasfanben':
			window.open("<?=Url::to(['/listing/ebaymuban/edit'])?>"+"?itemid="+id,'_blank');
			break;
		case 'history':
			window.open("<?=Url::to(['/listing/ebayitem/history'])?>"+"?itemid="+id,'_blank');
			break;
	}
}

function closeitem(itemid){
	var Url='<?=Url::to(['/listing/ebayitem/close'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {itemid : itemid},
		url: Url,
        success:function(response) {
        	$('#closeModal .modal-content').html(response);
        	$('#closeModal').modal('show');
        }
    });
}

//设置补库存的modal弹出
//@author fanjs
function bukucun(itemid){
	$.showLoading();
	var Url='<?=Url::to(['/listing/ebayitem/bukucunset'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {itemid : itemid},
		url: Url,
        success:function(response) {
            $.hideLoading();
        	$('#closeModal .modal-content').html(response);
        	$('#closeModal').modal('show');
        }
    });
}

/*
 * 补库存的参数设置入库
 * @author fanjs
 */
function dobukucunset(){
	var itemid = $('#itemid').val();
	var bukucun = $('.bukucun:checked').val();
	var less = $('input[name=less]').val();
	var bu = $('input[name=bu]').val();
	//补库存数量及预警值的参数验证
	if(bukucun == '2'){
		if(less.length > 0){
			if(parseInt(less) == less){
				if(less.indexOf('-')>=0 || less.indexOf('.')>=0){
					bootbox.alert('少于X件的数值必须正整数');return false;
				}
			}else{
				bootbox.alert('少于X件的数值必须正整数');return false;
			}
		}else{
			bootbox.alert('请输入少于X件的数值');return false;
		}
		if(bu.length > 0){
			if(parseInt(bu) == bu){
				if(bu.indexOf('-')>=0 || bu.indexOf('.')>=0){
					bootbox.alert('补货X件的数值必须正整数');return false;
				}
			}else{
				bootbox.alert('补货X件的数值必须正整数');return false;
			}
		}else{
			bootbox.alert('请输入补货X件的数值');return false;
		}
	}
	//ajax提交后台入库
	$('#closeModal').modal('hide');
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebayitem/ajax-bukucunset'])?>",{itemid:itemid,bukucun:bukucun,less:less,bu:bu},function(result){
		$.hideLoading();
		if(result=='success'){
			bootbox.alert('设置保存成功');
		}else{
			bootbox.alert(result);
		}
	});
}


/**
 * 批量设置补库存
 */
function mltibukucun(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.showLoading();
	var Url='<?=Url::to(['/listing/ebayitem/mltibukucunset'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {itemids : idstr},
		url: Url,
        success:function(response) {
            $.hideLoading();
        	$('#closeModal .modal-content').html(response);
        	$('#closeModal').modal('show');
        }
    });
}

function ajaxclose(itemid,reason){
	$('#closeModal').modal('hide');
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebayitem/ajax-close'])?>",{itemid:itemid,reason:reason},function(result){
		$.hideLoading();
		if(result=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

/**
 * 批量提交修改的页面
 */
function mltichange(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	window.open('<?=Url::to(['/listing/ebayitem/revise'])?>'+'?keys='+idstr);
}

/**
 * 批量提交修改的页面-所有值
 */
function mltichangeall(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});

	$.showLoading();
	$.get("<?=Url::to(['/listing/ebayitem/mltichangeall'])?>",{itemid:idstr},function(result){
		$.hideLoading();
		$('#syncModal .modal-content').html(result);
       	$('#syncModal').modal('show');
	});
}

/**
 * 手动同步在线item的操作
 */
function mtsyncitem(){
	$.showLoading();
	$.get("<?=Url::to(['/listing/ebayitem/mtsync'])?>",{},function(result){
		$.hideLoading();
		$('#syncModal .modal-content').html(result);
       	$('#syncModal').modal('show');
	});
}

/**
 * 给item添加汽配的相应信息
 * @author fanjs
 */
function addfitment(itemid){
	$.showLoading();
	$.get("<?=Url::to(['/listing/ebayitem/addfitment'])?>",{itemid:itemid,time:new Date().getTime()},function(result){
		$.hideLoading();
		$('#syncModal .modal-content').html(result);
       	$('#syncModal').modal('show');
	});
}

/**
 * 添加促销方案
 * @author fanjs
 */
function addpromotion(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});

	$.showLoading();
	$.post("<?=Url::to(['/listing/ebayitem/addpromotionverify'])?>",{itemid:idstr},function(result){
		if(result != 'success'){
			$.hideLoading();
			bootbox.alert(result);return false;
		}
	});
	
	$.post("<?=Url::to(['/listing/ebayitem/addpromotion'])?>",{itemid:idstr},function(result){
		$.hideLoading();
		$('#syncModal .modal-content').html(result);
       	$('#syncModal').modal('show');
	});
}

/**
 * 批量下架
 * @author fanjs
 */
function mltiend(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的刊登");return false;
    }
	idstr='';
	$('input[name="itemid[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});

	closeitem(idstr);
}

/**
 * 价格，数量排序
 */
function doxu(str){
	$('#xu').val(str);
	var sort = $('#xusort').val();
	if(sort == '' || sort == 'asc'){
		sort = 'desc';
	}else{
		sort = 'asc';
	}
	$('#xusort').val(sort);
	document.a.submit();
}
</script>