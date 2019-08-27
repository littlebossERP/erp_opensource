<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/tracking/tracking_tag.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/manual_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/list_tracking.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/platform/ebayAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/aliexpressAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/WishAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/dhgateAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/LazadaAccountsList.js");

$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs("$(function(){
window.onberforload=function(){  
	changeFooterPos();
}  
window.onresize=function(){
	changeFooterPos();
} 
});" , \yii\web\View::POS_READY);

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/dashboard/dash_board.js?v=1.02',['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJs("DashBoard.rightAdv();" , \yii\web\View::POS_READY);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<script type="text/javascript">
function changeFooterPos(){
	var footerTop = $('.footer').offset().top;
	$('.17_track_link').offset({top: footerTop-20, left: 0});
}
</script>
<style>
.td_space_toggle{
	height: auto;
	padding: 0!important;
}

.ra-ad{
	position:fixed;
	right:0px;
	top:110px;
	max-width:132px;
	max-height:299px;
	z-index:999;
	border:1px solid #ccc;
	border-radius:5px;
	box-shadow:0 6px 12px rgba(0,0,0,.175);
	-webkit-box-shadow:0 6px 12px rgba(0,0,0,.175);
	background-color:#fff;
 }
 .rz-ad{
	position:fixed;
	right:0px;
	top:415px;
	max-width:132px;
	max-height:182px;
	z-index:999;
	border:1px solid #ccc;
	border-radius:5px;
	box-shadow:0 6px 12px rgba(0,0,0,.175);
	-webkit-box-shadow:0 6px 12px rgba(0,0,0,.175);
	background-color:#fff;
 }
 .closeAdver{height:17px;text-align:right;padding:2px;}
</style>
<div class="tracking-index col2-layout">
	
	<?= $this->render('_menu') ?>
	<!-- 右侧table内容区域 -->
	
	<div class="content-wrapper" style='padding-right:120px;' >
	    <?= $this->render('_manual_import_tracking_content') ?>
	    <?= $this->render('_bind_platform_quick_entry') ?>
 

	</div> 
	<div class="17_track_link" style="display:inline-block;width:100%;text-align:center;font-size:100%;vertical-align:baseline;"><a href="http://www.17track.net/zh-cn" target="_blank">Tracking Data Powered by 17track.net</a></div>
</div>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
    	
        <div class="modal-content">
        	<div class="modal-header">
	        	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        	<h4 class="modal-title"><?= TranslateHelper::t('Tracker 物流查询助手 - 复制粘贴excel多列指引')?></h4>
	        </div>
	        <div class="modal-body">
	        	<?= $this->render('_manual_help') ?>
	        </div>
        </div><!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- Modal -->
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
    	
        <div class="modal-content">
        	<div class="modal-header">
	        	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        	<h4 class="modal-title">


					<?= TranslateHelper::t('Tracker 物流查询助手 - 无限制Excel上传物流号')?></h4>
	        </div>
	        <div class="modal-body">
	        	<?= $this->render('_excel_help') ?>
	        </div>
        </div><!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<?= $this->render('_modal_import_excecl.php')?>


<input id='query_empty_message' type="hidden" value='<?= TranslateHelper::t('物流号不能为空!如需帮助请点击右上角的指引') ?>'>
