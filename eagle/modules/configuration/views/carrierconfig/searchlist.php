<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;

echo Html::hiddenInput('type',@$_GET['type']);
?>
<?php echo $this->render('../leftmenu/_leftmenu');?>

<style>
.table > tbody + tbody, .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td{
	text-align:center;
}
</style>

<ul class="main-tab">
	<li <?php if(@$_GET['type'] == "ebayoms")echo"class='active'"?>><a href="/configuration/carrierconfig/searchlist?type=ebayoms">eBay OMS</a></li>
	<li <?php if(@$_GET['type'] == "aliexpressoms")echo"class='active'"?>><a href="/configuration/carrierconfig/searchlist?type=aliexpressoms">AliExpress OMS</a></li>
	<li <?php if(@$_GET['type'] == "delivery")echo"class='active'"?>><a href="/configuration/carrierconfig/searchlist?type=delivery">发货</a></li>
	<li <?php if(@$_GET['type'] == "carrier")echo"class='active'"?>><a href="/configuration/carrierconfig/searchlist?type=carrier">物流</a></li>
</ul>
<table class="table text-center" style="table-layout:fixed;line-height:50px; margin:0;">
	<tr>
		<th class="text-nowrap"><?= TranslateHelper::t('搜索名')?></th>
		<th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
	</tr>
<?php if(isset($searchlist) && count($searchlist)>0){
	foreach ($searchlist as $sname){
	?>
	<tr>
		<td class="text-nowrap"><?= TranslateHelper::t(@$sname)?></td>
		<td class="text-nowrap"><a class="btn btn-xs" onclick="delcommonsearch('<?=@$sname?>')">删除</a></td>
	</tr>
<?php }}?>
</table>

<script>

function delcommonsearch(name){
	if(typeof name == 'undefined'){
		$.alert('无传入搜索名','danger');
		return;
	}
	type = $('input[name=type]').val();
	if(typeof type == 'undefined'){
		$.alert('请先选择管理种类','danger');
		return;
	}
	$event = $.confirmBox('确认删除常用搜索条件 《'+name+'》 吗？');
	$event.then(function(){
		var Url=global.baseUrl +'configuration/carrierconfig/del-common-search';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {type:type,name:name},
			url: Url,
	        success:function(response) {
	        	if(response[0]==0){
	        		$.alert(response.substr(1),'danger');
	        	}
	        	else{
	        		$e = $.alert(response.substr(1),'success');
	        		$e.then(function(){
	        			location.reload();
	        		});
	        	}
	        }
	    });
	});
}
</script>