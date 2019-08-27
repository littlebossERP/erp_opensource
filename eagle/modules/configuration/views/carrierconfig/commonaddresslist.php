<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;

?>
<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<style>
.table > tbody + tbody, .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td{
	text-align:center;
}

</style>

<table class="table text-center" style="table-layout:fixed;line-height:50px; margin:0;">
	<tr>
		<th class="text-nowrap"><?= TranslateHelper::t('常用地址名')?></th>
		<th class="text-nowrap"><?=TranslateHelper::t('地址') ?></th>
		<th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
	</tr>
<?php if(isset($address_list) && count($address_list)>0){
	foreach ($address_list as $ad){
		$ad = $ad['response']['data'];
	?>
	<tr>
		<td class="text-nowrap"><?= TranslateHelper::t(@$ad['address_name'])?></td>
		<td class="text-nowrap"><?=TranslateHelper::t(@$ad['address_params']['shippingfrom']['street']) ?></td>
		<td class="text-nowrap"><a class="btn btn-xs" onclick="delAddress('<?=@$ad['id']?>','<?=@$ad['address_name']?>')">删除</a></td>
	</tr>
<?php }}?>
</table>


<script>
function delAddress(address_id,name){
	if(typeof address_id == 'undefined'){
		$.alert('无传入地址编号','danger');
		return;
	}
	$event = $.confirmBox('确认删除地址 《'+name+'》 吗？');
	$event.then(function(){
		var Url=global.baseUrl +'configuration/carrierconfig/deladdressnow';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {id:address_id},
			url: Url,
	        success:function(response) {
	        	if(response[0] == 0){
	        		$e = $.alert(response.substring(2),'success');
	        		$e.then(function(){
	        			location.reload();
	        		});
		        }
	        	else{
	        		$.alert(response.substring(2),'danger');
		        }
	        }
	    });
	});
}
</script>