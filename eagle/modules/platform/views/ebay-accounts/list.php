<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/platform/ebayAccountsList.js');
$this->registerJs('platform.ebayAccountsList.initWidget();', \yii\web\View::POS_READY);

?>


<!-- Modal -->
<div id="platform-ebayAccounts-list-commonwindow" class="modal fade" tabindex="-1" data-backdrop="static"  >
    <div class="modal-dialog">
        <div class="modal-content"></div><!-- /.modal-content -->
    </div>
</div>
<!-- /.modal-dialog -->

<div class="btn-group" role="group" id='platform-ebayAccounts-list-datagrid-toolbar' style="margin-top: 15px;margin-bottom:15px;" >
	<a class="btn btn-info" href='#' onclick='platform.ebayAccountsList.menuAdd();'><?php echo TranslateHelper::t('增 加');?></a>
	<a class="btn btn-info" href='#' id='platform-ebayAccounts-delete' disabled='disabled' onclick='platform.ebayAccountsList.menuDelete();'><?php echo TranslateHelper::t('删 除');?></a>
</div>
 
<div class="account-list">
    <table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
		<tr class="list-firstTr">
			<td></td>
			<td><INPUT id="check-all-record" type="checkbox" /></td>
			<td><?=$sort->link('ebay_uid',['label'=>TranslateHelper::t('ID')]) ?></td>
			<td><?=$sort->link('selleruserid',['label'=>TranslateHelper::t('卖家')]) ?></td>
			<td><?=$sort->link('item_status',['label'=>TranslateHelper::t('同步Item状态')]) ?></td>
			<td><?=$sort->link('expiration_time',['label'=>TranslateHelper::t('过期时间')]) ?></td>
		</tr>
        <?php 
        $rowIndex = 1;
        foreach($ebayUserList as $ebayUser):?>
            <tr>
	            <td><?=$rowIndex ?></td>
				<td><INPUT id="ebayUser-row-<?=$rowIndex ?>" type="checkbox" data-euid="<?=$ebayUser['ebay_uid'] ?>" /></td>
                <td ><?=$ebayUser['ebay_uid'] ?></td>
	            <td ><?=$ebayUser['selleruserid'] ?></td>
	            <?php if( $ebayUser['item_status'] == '1'):?>
	            <td ><a class="btn btn-default btn-sm" href="#" onclick="set(0,'<?=$ebayUser['ebay_uid'] ?>','item_status');" title="点击关闭">开启</a></td>
	            <?php else:?>
	            <td ><a class="btn btn-default btn-sm" href="#" onclick="set(1,'<?=$ebayUser['ebay_uid'] ?>','item_status');" title="点击开启">关闭</a></td>
	            <?php endif;?>
	            <td ><?=$ebayUser['expiration_time'] ?></td>
	        </tr>
	       <?php $rowIndex++;?>
        <?php endforeach;?>
    </table>
</div>

<?php if(!empty($pagination)):?>
<div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>
