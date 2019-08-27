<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<script type="text/javascript">
StationLetter.platformAccount=<?= json_encode($addi_info['accounts'])?>;
StationLetter.NationList=<?= json_encode($addi_info['country'])?>;
StationLetter.NationMapping=<?= json_encode($addi_info['country_mapping'])?>;
<?php 
//array_unshift($addi_info['ship_status'],'N/A');
$tmp_shipStatus_arr = array('na'=>'所有状态');
$tmp_shipStatus_arr += $addi_info['ship_status'];
$addi_info['ship_status'] = $tmp_shipStatus_arr;
?>
StationLetter.StatusList=<?= json_encode($addi_info['ship_status'])?>;
StationLetter.PlatformLabel=<?= json_encode($addi_info['platform_label'])?>;
StationLetter.roleTableList=<?= json_encode($roles)?>;
</script>
<style>
.basic-qtip.z-index-top{
	z-index:9000!important;
}
</style>
<div id="div_role_pannel">
	<div style="margin-bottom: 5px;">
		<span><?= TranslateHelper::t('当前规则')?></span>
		<input class="btn btn-success btn-sm" type="button" id="btn_add_role" name="btn_add_role" value="<?= TranslateHelper::t('增加规则')?>"/>
	</div>
	<table id="role_table" class="table">
		<thead>
			<tr>
				<th><?= TranslateHelper::t('优先顺序')?></th>
				<th><?= TranslateHelper::t('名称')?></th>
				<th><?= TranslateHelper::t('匹配平台账号')?></th>
				<th><?= TranslateHelper::t('匹配目的地国家')?></th>
				<th><?= TranslateHelper::t('物流状态')?></th>
				<th><?= TranslateHelper::t('留言模板')?></th>
				<th><?= TranslateHelper::t('操作')?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	
	</table>

</div>

