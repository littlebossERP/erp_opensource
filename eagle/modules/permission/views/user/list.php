<?php 

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/permission/userList.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("permission.userList.init();" , \yii\web\View::POS_READY);

$this->title = TranslateHelper::t("子账号管理");

$platform_source = OdOrder::$orderSource;
?>
<style>
.account-list b {
	font-weight: bold;
}

</style>

<div class="btn-group" role="group" id='permission-user-list-btn-group' style="margin-top: 15px;margin-bottom:15px;" >
	<a href='#' class='btn btn-info' onclick='permission.userList.userAdd();'><?= TranslateHelper::t('增 加');?></a>
</div>

<div id="permission-user-list-menu" class="easyui-menu" style="width:120px" >
    <div id="permission-user-list-menu-modify"></div>
</div>

<div class="account-list">
    <table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
		<tr class="list-firstTr">
			<td></td>
			<!-- <td><INPUT id="check-all-record" type="checkbox" /></td> -->
			<td><?=TranslateHelper::t('登录邮箱/用户名 ') ?></td>
			<td style="width: 30%;"><?=TranslateHelper::t('权限 ') ?></td>
			<td><?=$sort->link('register_date',['label'=>TranslateHelper::t('创建日期 ')]) ?></td>
			<td><?=$sort->link('last_login_date',['label'=>TranslateHelper::t('最后登陆时间 ')]) ?></td>
			<td><?=$sort->link('is_active',['label'=>TranslateHelper::t('启用')]) ?></td>
			<td><?= TranslateHelper::t('操作')?></td>
		</tr>
        <?php 
        $rowIndex = 1; 
        if(!empty($erpBaseUsers)):foreach($erpBaseUsers as $erpBaseUser):?>
            <tr>
               	<td><?=$rowIndex ?></td>
                <td ><?= $erpBaseUser->email ?><?= !empty($erpBaseUser->info)?"/".$erpBaseUser->info->familyname:"" ?></td>
                <td >
                	<?= !empty($permission)&&!empty($permission[$erpBaseUser->uid])&&!empty($permission[$erpBaseUser->uid]['modules'])?"模块：".implode(' , ', $permission[$erpBaseUser->uid]['modules']):"" ?><br>
                	<?= !empty($permission)&&!empty($permission[$erpBaseUser->uid])&&!empty($permission[$erpBaseUser->uid]['setting_modules'])?"设置：".implode(',', $permission[$erpBaseUser->uid]['setting_modules']):"" ?><br>
	               	<?php
	               	if(!empty($permission)&&!empty($permission[$erpBaseUser->uid])&&!empty($permission[$erpBaseUser->uid]['platforms'])){
	               		$permission_platforms = $permission[$erpBaseUser->uid]['platforms'];
	               		$tmp_platform_list  =[];
	               		//print_r($permission_platforms);
	               		foreach ($permission_platforms as $platform=>$platformData){
               				if(is_array($platformData)){
								if(in_array('all',$platformData)){
									$tmp_platform_list[$platform] = '<b>'.(empty($platform_source[$platform])?$platform:$platform_source[$platform]).'</b>(全部)';
									continue;
								}else{
									$tmp_account_store_name = [];
									foreach ($platformData as $account_key){
										$tmp_account_store_name[$account_key] = empty($platformAccountList[$platform][$account_key])?$account_key:$platformAccountList[$platform][$account_key];
									}
	               					$tmp_platform_list[$platform] = '<b>'.(empty($platform_source[$platform])?$platform:$platform_source[$platform]).'</b>('.implode(', ', $tmp_account_store_name).')';
								}               				
							}else 
               					$tmp_platform_list[$platform] = '<b>'.(empty($platform_source[$platformData])?$platformData:$platform_source[$platformData]).'</b>(全部)';
	               		}
	               		echo "平台：<br>".implode(',<br>', $tmp_platform_list);
	               	}
        			?>
        			<br>	
	               	<?= !empty($permission)&&!empty($permission[$erpBaseUser->uid])&&!empty($permission[$erpBaseUser->uid]['others'])?"其他：".implode(',', $permission[$erpBaseUser->uid]['others']):"" ?>	<br>
                </td>
	            <td ><?=date("Y-m-d H:i:s" , $erpBaseUser->register_date) ?></td>
	            <td ><?=date("Y-m-d H:i:s" , $erpBaseUser->last_login_date ) ?></td>
	            <?php if( $erpBaseUser['is_active'] == '1'):?>
	            <td >已启用</td>
	            <?php else:?>
	            <td >已关闭</td>
	            <?php endif;?>
	            <td >
		            <?php if( $erpBaseUser['is_active'] == '1'):?>
		            <a class="btn btn-default btn-xs" href="#" onclick="permission.userList.setSync(0,'<?=$erpBaseUser->uid ?>');" title="点击关闭">关闭</a>
		            <button type="button" onclick="permission.userList.userEdit('<?=$erpBaseUser->uid ?>')" class="btn btn-xs btn-default"><?=TranslateHelper::t('编辑') ?></button>
		            <?php else:?>
		            <a class="btn btn-default btn-xs" href="#" onclick="permission.userList.setSync(1,'<?=$erpBaseUser->uid ?>');" title="点击开启">开启</a>
		            <?php endif;?>
	            </td>
	        </tr>
	        <?php $rowIndex++;?>
        <?php endforeach;endif;?>
    </table>
</div>

<?php if(!empty($pagination)):?>
<div id='pagination-container'>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>