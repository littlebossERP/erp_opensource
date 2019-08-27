<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//$this->registerJs("amazoncs.EmailList.initBtn()", \yii\web\View::POS_READY);
$active = '';
$uid = \Yii::$app->user->id;

$status_ch_mapping = [
	'active'=>'启用',
	'unActive'=>'停用',
];
?>


<style>

</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper">
		<form>
		</<form>
	
		<div style="margin-bottom: 10px;">
			<a type="button" class="iv-btn btn-important" id="btn_create_binding_email" onclick="amazoncs.EmailList.openCreateEmailBindWin()">
			  <?=TranslateHelper::t('添加绑定')?>
			</a>
		</div>
		
		<div>
			<table class="table">
				<tr>
					<th style="display:none;">销售平台</th>
					<th>seller</th>
					<th>站点</th>
					<th>Email</th>
					<th>状态</th>
					<th>操作</th>
				</tr>
				<?php if(!empty($csEmailInfos)): foreach ($csEmailInfos as $index=>$csEmail){
				
				?>
				<tr>
					<td style="display:none;"><?=$csEmail->platform?></td>
					<td><?=empty($MerchantId_StoreName_Mapping[$csEmail->seller_id])?$csEmail->seller_id:$MerchantId_StoreName_Mapping[$csEmail->seller_id]?></td>
					<td><?=empty($MarketPlace_CountryCode_Mapping[$csEmail->site_id])?$csEmail->site_id:$MarketPlace_CountryCode_Mapping[$csEmail->site_id]?></td>
					<td><?=$csEmail->email_address?></td>
					<td><?=empty($status_ch_mapping[$csEmail->status])?$csEmail->status:$status_ch_mapping[$csEmail->status] ?></td>
					<td>
					<?php if($csEmail->status=='active'){ ?>
					<a href="javascript:void(0)" style="" onclick="amazoncs.EmailList.switchEmailAddressStatus(<?=$csEmail->id?>,'unActive')" >停用</a>	
					<?php } ?>
					<?php if($csEmail->status=='unActive'){ ?>
					<a href="javascript:void(0)" style="" onclick="amazoncs.EmailList.switchEmailAddressStatus(<?=$csEmail->id?>,'active')" >启用</a>	
					<?php } ?>
					<a href="javascript:void(0)" style="margin-left:5px;padding-left: 5px;border-left: 1px solid;" onclick="amazoncs.EmailList.unbindEmailAddress(<?=$csEmail->id?>)" >解绑</a>	
					</td>
				</tr>	
				<?php }endif; ?>
			</table>
		</div>
	</div>
</div>