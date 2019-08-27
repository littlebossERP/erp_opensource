<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
 ?>
<div class="order-operate" style="margin-top:10px;margin-bottom:10px;">
   <button data-toggle="modal" data-target="#checkOrder" onclick="editCarrier('','carrier')" class="btn btn-success"><?=TranslateHelper::t('新增物流商') ?></button>
</div>
<?php 
$sort = new Sort(['attributes' => ['carrier_code','carrier_name','carrier_type','api_class','create_time']]);
?>
<!-- table -->
    <table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
        <tr class="list-firstTr">
            <td class="text-nowrap"><?=$sort->link('carrier_code',['label'=>TranslateHelper::t('物流商代码')]) ?></td>
	        <td class="text-nowrap"><?=$sort->link('carrier_name',['label'=>TranslateHelper::t('物流商名')]) ?></td>
	        <td class="text-nowrap"><?=$sort->link('carrier_type',['label'=>TranslateHelper::t('物流商类型')]) ?></td>
	        <td class="text-nowrap"><?=$sort->link('api_class',['label'=>TranslateHelper::t('调用接口类名')]) ?></td>
	        <td class="text-nowrap"><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></td>
	        <td class="text-nowrap"><?= TranslateHelper::t('操作')?></td>
	      </tr>
        <?php foreach($carriers as $c):?>
            <tr>
                <td class="text-nowrap"><?=$c['carrier_code'] ?></td>
	            <td class="text-nowrap"><?=$c['carrier_name'] ?></td>
	            <td class="text-nowrap"><?=$c['carrier_type']==0?'货代':'海外仓' ?></td>
	            <td class="text-nowrap"><?=$c['api_class'] ?></td>
	            <td class="text-nowrap"><?=date('Y-m-d H:i:s',$c['create_time']) ?></td>
	            <td class="text-nowrap">
				<button data-toggle="modal" onclick="editCarrier('<?=$c['carrier_code'] ?>','carrier')" data-target="#checkOrder" class="btn btn-default btn-sm"><?=TranslateHelper::t('编辑') ?></button>
				<button onclick="location.href='<?= \Yii::$app->urlManager->createUrl(['carrier/carrier/params','code'=>$c['carrier_code']]) ?>'" class="btn btn-default btn-sm"><?=TranslateHelper::t('参数') ?></button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
    </table>
    <!-- Modal -->
	<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
        </div><!-- /.modal-content -->
    </div>
    </div>
    <!-- /.modal-dialog -->
<script>
	function editCarrier(carrierCode,op) {//添加订单标签
		var reUrl = '';
		if(op=='carrier')reUrl='<?= \Yii::$app->urlManager->createUrl("carrier/carrier/create") ?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code : carrierCode},
			url: reUrl,
	        success:function(response) {
	        	$('#checkOrder .modal-content').html(response);
	        }
	    });
	}
</script>