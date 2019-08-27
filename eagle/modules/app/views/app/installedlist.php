
<?php 
/**
 * 已安装的app管理---  app启用，停用，参数设置
 */

use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/app/appInstalledList.js", ['depends' => ['yii\web\JqueryAsset']]);

$imageBasePath=Yii::getAlias('@web')."/images";

$activeAppNum=count($activeAppList);
$unActiveAppNum=count($unActiveAppList);



?>
<style>
.active-applist-div table th {vertical-align:middle; text-align:center;}
.active-applist-div table td {vertical-align:middle; text-align:center;}
.unactive-applist-div table th {vertical-align:middle; text-align:center;}
.unactive-applist-div table td {vertical-align:middle; text-align:center;}

.box_head {
height: 40px;
padding: 5px 20px;
border-bottom: 1px solid #ddd;
}
.box_title {
color: #555;
font-size: 20px;
font-weight:bold;
line-height: 30px;
overflow: hidden;
float: left;
}
</style>


<div class="box_head" ><div class="box_title"><?=TranslateHelper::t('已安装的app') ?></div></div>


<!-- 已启用app列表 -->
<div class="active-applist-div" style="width:80%;margin-top:30px;">
    <div style="margin-top: 15px;margin-bottom:15px;font-weight:bold"><?=TranslateHelper::t('启用的app(%s)',$activeAppNum) ?></div>
    <table cellspacing="0" cellpadding="0" width="60%" class="table table-hover table-bordered">
    <thead>
		<tr>
		    <th width="30%" ><?=TranslateHelper::t('app名称') ?></td>		    
		    <th width="16%" ><?=TranslateHelper::t('启用时间') ?></td>
			<th width="18%"><?=TranslateHelper::t('操作') ?></td>
		</tr>
	</thead>
	<tbody>	
        <?php foreach($activeAppList as $appInfo):?>
            <tr  appkey="<?=$appInfo->key ?>" appname="<?=TranslateHelper::t($appInfo->name) ?>"  >
                <td><?=$appInfo->name ?></td>                
                <td><?=$appInfo->update_time ?></td>
	            <td>
	                 <button type="button" class="btn btn-sm btn-default app-unactivate-btn"><?=TranslateHelper::t('停用') ?></button>
	                 <button type="button"  class="btn btn-sm btn-default app-configset-btn"><?=TranslateHelper::t('设置') ?></button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
        </tbody>
    </table>    
</div>


<!-- 未启用app列表 -->
<div class="unactive-applist-div" style="width:80%;margin-top:30px;">
    <div style="margin-top: 15px;margin-bottom:15px;font-weight:bold"><?=TranslateHelper::t('未启用的app(%s)',$unActiveAppNum) ?></div>
    <table cellspacing="0" cellpadding="0" width="60%" class="table table-hover table-bordered">
      <thead>
		<tr>
		    <th width="30%" ><?=TranslateHelper::t('app名称') ?></td>		    
		    <th width="16%" ><?=TranslateHelper::t('停用时间') ?></td>
			<th width="18%"><?=TranslateHelper::t('操作') ?></td>
		</tr>
		</thead>
		<tbody>
            <?php foreach($unActiveAppList as $appInfo):?>
            <tr  appkey="<?=$appInfo->key ?>" appname="<?=TranslateHelper::t($appInfo->name) ?>"  >
                <td><?=$appInfo->name ?></td>                
                <td><?=$appInfo->update_time ?></td>
	            <td>
	                 <button type="button" class="btn btn-sm btn-default app-activate-btn"><?=TranslateHelper::t('启用') ?></button>
	                 <button type="button"  class="btn btn-sm btn-default app-configset-btn"><?=TranslateHelper::t('设置') ?></button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
        </tbody>
    </table>    
</div>





