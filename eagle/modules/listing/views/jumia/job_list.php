<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\helpers\HtmlHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/jumiaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$jumia_listing_version );
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/jumia_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$jumia_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("jumiaListing.list_init()", \yii\web\View::POS_READY);
 
$this->title = TranslateHelper::t("导入任务管理");
?>
<style>
.table td{
	border:1px solid #d9effc !important;
	text-align:center;
	vertical-align: middle !important;
}
.table th{
	text-align:center;
	vertical-align: middle !important;
}
.job-list .header{margin-bottom: 10px;}

.jumiaImportErrMsg .bootbox-body{word-break: break-all;}
</style>
<?php 
$menu = LazadaApiHelper::getLeftMenuArr('jumia');
echo $this->render('//layouts/new/left_menu_2',[
'menu'=>$menu,
'active'=>$this->title 
]);
?>
<div class="col2-layout jumia-listing job-list">
        <div class="header">
            <button type="button" class="iv-btn btn-important" data-toggle="modal" data-target="#import_job" onclick="jumiaListing.reset2()" >Excel 导入 </button>
            
        </div>
		<div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 200px">店铺名称</th>
                        <th style="width: 200px">文件</th>
                        <th style="width: 125px">导入任务</th>
                        <th style="width: 125px">提交任务时间</th>
                        <th style="width: 125px">触发任务时间</th>
                        <th style="width: 125px">执行状态</th>
                        <th style="width: 50px">操作</th>
                    </tr>
                 </thead>
                 <?php if(!empty($jobs)):?>
                 <tbody class="">
                 <?php foreach ($jobs as $job):?>
                 <tr class="striped-row">
                    <td><?= $job['store_name'];?></td>
                    <td>原名：<a target="_blank" href="/attachment/tmp_export_file/<?= $job['fileName'];?>"><?= $job['originFileName'];?></a><br>重命名为：<?= $job['fileName'];?></td>
                    <td><?= $job['op'];?></td>
                    <td><?= $job['create_time'];?></td>
                    <td><?= $job['next_execution_time'];?></td>
                    <td>
                        <?= $job['status_name'];?>
                        <?php if($job['status'] == 2): // 执行完成?>
                        <div>
                        <?= isset($job['totalNum'])?"总共提交：".$job['totalNum']."，":""?>
                        <?= isset($job['failNum'])?'失败<font class="text-danger">'.$job['failNum']."</font>个，":""?>
                        <?= isset($job['failNum'])&&isset($job['totalNum'])?'成功<font class="text-success">'.($job['totalNum']-$job['failNum'])."</font>个，":""?>
                        </div>
                        <?php elseif ($job['status'] == 5 || $job['status'] == 6): // 间歇暂停，终止任务?>
                        <div><?= "已经处理".($job['execution_request']-1)."个产品"?></div>
                        <?php endif;?>
                    </td>
                    
                    <td>
                        <?php if($job["opBan"] !== 1):// 其他账号的任务不能操作?>
                        <?php if($job['status'] == 3 || $job['status'] == 5): // 错误或间歇停止中的可以中止任务?>
                        <a title="中止任务" onclick="jumiaListing.stopImportJob(<?=$job['id'] ?>)"><span class="iconfont icon-xiajia"></span></a>
                        <?php endif;?>
                        
                        <?php if($job['status'] == 2 || $job['status'] == 0 || $job['status'] == 6): // 未执行，或执行完成可以删除?>
                        <a title="删除" onclick="jumiaListing.deleteImportJob(<?=$job['id'] ?>)"><span class="iconfont icon-shanchu"></span></a>
                        <?php endif;?>
                        <?php endif;?>
                        
                        <?php if($job['status'] == 2 && isset($job['failNum']) && $job['failNum'] > 0):?>
                        <a title="显示报错信息" onclick="jumiaListing.showImportErrMsg(<?=$job['id'] ?>)"><span class="iconfont icon-fuzhi"></span></a>
                        <p id="err-msg-<?=$job['id'] ?>" class="hidden"><?=($job['error_message']) ?></p>
                        <?php endif;?>
                    </td>
                 </tr>
                 <?php endforeach;?>
                 </tbody>
                 <?php endif;?>
            </table>
        </div>
        <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pagination,'options'=>['class'=>'pagination']]);?>
        	</div>
                <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
        </div>
</div>
 
 
<!-- excel导入模态层 -->
<div class="modal fade" id="import_job" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog" style="width: 750px;">
		<div class="modal-content">
			<!--modalHead-->
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title" id="myModalLabel">Excel 导入</h4>
			 </div>
			<!--conter-->
			<div class="modal-body tab-content p10">
				<button id="import_file_btn" class="iv-btn btn-info" style="" onclick="$('#import_file').click();">选择文件</button>
                <input id="import_file" type="file" class="" style="display: none;" onchange="$('#import_file_name').text(this.files[0].name);" />
                <span id="import_file_name" style="padding-left:15px;"></span> 
                <br />
                <span style=""><label for="import_type">选择导入任务：</label><?=Html::dropDownList('import_type',"",$jobsDropdownList,['onchange'=>"",'class'=>'eagle-form-control','id'=>'import_type_select','style'=>'width:260px;','prompt'=>'请选择导入任务'])?></span>
                <br />
                <span style=""><label for="lazada_uid">选择站点：</label><?=Html::dropDownList('countryCode',"",$siteList,['onchange'=>"jumiaListing.reShowAccounts(this)",'class'=>'eagle-form-control','id'=>'lazada_countryCode_select','style'=>'width:260px;','prompt'=>'请选择站点店铺'])?></span>
                <?php foreach ($jumiaUsersSiteMap as $site=>$accounts):?>
                <?=Html::checkboxList('lazada_uid[]',"",$accounts,['onchange'=>"",'class'=>'hidden js-checkbox-mp site-'.$site,'id'=>'accounts-options-div-'.$site,'style'=>'width: 80%;margin: auto;'])?>
                <?php endforeach;?>
                
                <br />
                <span style="">预计最早执行时间：
                <input type="datetime-local" id="excute_time" name="excute_time" value="" class="eagle-form-control" style="margin:0px;height:28px;margin-right:20px;" />
                </span>
                <div class="ret_msg" style="margin-top: 10px;color: red;"></div> 
			</div>
			<!--modalFoot-->
			<div class="modal-footer" style="clear:both;">
			    <button id="import_comfirm" type="button" class="btn btn-primary" onclick="jumiaListing.importSubmit()">导入</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>		
			</div>
		</div>
	</div>
</div>
 