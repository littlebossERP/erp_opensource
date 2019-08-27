<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJs("StationLetter.isRefreshMatching=false;" , \yii\web\View::POS_READY);
?>



<style>
.xlbox .modal-dialog{
    max-width: 900px;
	width:auto;
}
</style>
<div class="mail-template-list">
	<div style="margin: 10px;">
		<button class="btn btn-success btn-sm" type="button" onclick="StationLetter.showTemplateBox()"><?= TranslateHelper::t('新增发信模板') ?></button>
		<button class="btn btn-success btn-sm" id="btn_set_roles" onclick="StationLetter.showRoleSettingBox(0)"><?=TranslateHelper::t('设置匹配规则')?></button>
	</div>
	<table id="mail-template-list-tb" class="table table-hover">
		<thead>
	    <tr class="list-firstTr">
	    	<th style="width:10%;border:1px solid #ddd"><?= TranslateHelper::t('编号') ?></th>
			<th style="width:15%;border:1px solid #ddd"><?= TranslateHelper::t('模板名称') ?></th>
			<th style="width:20%;border:1px solid #ddd"><?= TranslateHelper::t('模板主题') ?></th>
			<th style="width:40%;border:1px solid #ddd"><?= TranslateHelper::t('内容') ?></th>
			<th style="width:15%;border:1px solid #ddd"><?= TranslateHelper::t('操作')?></th>
		</tr>
		</thead>
		<tbody>
		<?php 
		$rowIndex = 1;
		if(!empty($templateData['data'])):
		foreach( $templateData['data'] as $template):?>
			<tr>
				<td style="width:10%;border:1px solid #ddd"><?=$rowIndex ?></td>
				<td style="width:15%;border:1px solid #ddd"><?=$template['name'] ?></td>
				<td style="width:20%;border:1px solid #ddd"><?=$template['subject'] ?></td>
				<td style="width:40%;border:1px solid #ddd"><?php
				$template_body = nl2br($template['body']);
				$template_body =str_replace('[', '<b style="color:#337ab7">&#91;', $template_body);
				$template_body =str_replace(']', '&#93;</b>', $template_body);
				echo $template_body;
				?></td>
				<td style="width:15%;border:1px solid #ddd">
					<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="StationLetter.showTemplateBox(<?=$template['id'] ?>)"><?= TranslateHelper::t('修改') ?></a>
					<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="StationLetter.deleteTemplate(<?=$template['id'] ?>,this)"><?= TranslateHelper::t('删除') ?></a>
					<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="StationLetter.showPreviewTemplateBox('<?="demodata01"?>','<?=$template['id'] ?>')"><?= TranslateHelper::t('预览') ?></a>
				</td>
			</tr>
		<?php $rowIndex++;?>
		<?php endforeach;?>
		<?php endif;?>
		</tbody>
	</table>
	<!-- pagination -->
	<?php if($templateData['pagination']):?>
	<div>
		<div id="mail-template-list-pager" class="pager-group">
			<?= \eagle\widgets\SizePager::widget(['isAjax'=>true ,'pagination'=>$templateData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			<div class="btn-group" style="width: 49.6%;text-align: right;">
				<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true ,'pagination' => $templateData['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
	</div>
	
	<?php 
	// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
		$options = array();
		$options['pagerId'] = 'mail-template-list-pager';// 下方包裹 分页widget的id
		$options['action'] = 'order/od-lt-message/mail_template_setting'; // ajax请求的 action
		$options['page'] = $templateData['pagination']->getPage();// 当前页码
		$options['per-page'] = $templateData['pagination']->getPageSize();// 当前page size
		$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
		$this->registerJs('$("#mail-template-list-tb").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
	?>
	
	<?php endif;?>
	<!-- /.pagination-->
	
	<div class="xlbox" style="display:none"></div>
</div>	