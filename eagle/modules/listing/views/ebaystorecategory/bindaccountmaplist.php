<?php

use yii\helpers\Url;
?>
<style type="text/css"> 


</style> 
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'账户绑定管理']);?>
	<div class="content-wrapper" >
		<div class="table-action">
			<div class="pull-left">
			<button type="button" class='iv-btn btn-search' onclick="javascript:edit('');"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>新建</button>
			</div>
		</div>
	</div>
	<table class="table">
		<thead>
		<tr>
			<th>eBay账号</th><th>PayPal账号</th><th>备注</th><th>操作</th>
		</tr>
			<?php if (count($maps)):?>
			<?php foreach ($maps as $key=>$val):?>
			<tr>
			<td><?=$key?></td>
			<?php $paypal='';$desc='';?>
			<?php for ($i=0;$i<count($val);$i++){
			$paypal.= $val[$i]['paypal'].'<br>';
			$desc.= $val[$i]['desc'].'<br>';
			};?>
			<td><?=$paypal?></td>
			<td><?=$desc?></td>
			<td>
				<button type="button" class='iv-btn btn-search' onclick="javascript:edit('<?=$key?>');"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span>修改</button>
				<button type="button" class='iv-btn btn-search' onclick="javascript:deletemap('<?=$key?>');"><span class="iconfont icon-shanchu"></span>删除</button>
			</td>
			</tr>
			<?php endforeach;?>
			<?php endif;?>
		</thead>
	</table>
</div>
<script>
//编辑或新建映射关系
function edit(selleruserid){
	window.open("<?=Url::to(['/listing/ebaystorecategory/editmap'])?>"+'?selleruserid='+selleruserid,'_blank');
}
//删除已有的映射关系
function deletemap(selleruserid){
	$.showLoading();
	$.post('<?=Url::to(['/listing/ebaystorecategory/deletemap'])?>',{selleruserid:selleruserid},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败'+r);
		}
	  })
}
</script>