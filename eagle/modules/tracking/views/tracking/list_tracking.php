<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\BootstrapAsset;


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/tracking/list_tracking.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."js/project/tracking/station_letter.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/tracking/tracking_tag.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("TrackingTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJs("TrackingTag.TagList=".json_encode($AllTagData).";" , \yii\web\View::POS_READY);

$this->registerJs("TrackingTag.init();" , \yii\web\View::POS_READY);
$this->registerCssFile($baseUrl."css/tracking/tracking.css", ['depends'=>["eagle\assets\AppAsset"]]);

$this->registerJsFile($baseUrl."js/project/order/orderCommon.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);


$titlelist = ['list-tracking'=>'查询物流更新'  , 'list-platform-tracking'=>'订单物流跟踪'];
//$titlelist[yii::$app->controller->action->id]
$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;

$platform_header = ['all'=>TranslateHelper::t('全部订单') , 'ebay'=>TranslateHelper::t('ebay订单') ,'aliexpress'=>TranslateHelper::t('速卖通订单') ];
$parcel_classification_header = [
'all_parcel'=>TranslateHelper::t('全部包裹') ,
'normal_parcel'=>TranslateHelper::t('正常包裹') ,
'shipping_parcel'=>TranslateHelper::t('运输途中') ,
'no_info_parcel'=>TranslateHelper::t('未查询到') ,
'exception_parcel'=>TranslateHelper::t('异常包裹') ,
'rejected_parcel'=>TranslateHelper::t('异常退回 ') ,
'ship_over_time_parcel'=>TranslateHelper::t('运输过久') ,
'arrived_pending_fetch_parcel'=>TranslateHelper::t('到达待取') ,
'unshipped_parcel'=>TranslateHelper::t('无法交运') ,
'received_parcel'=>TranslateHelper::t('已签收') ,
];

$this->registerJs("$(function(){
window.onload=function(){  
	changeFooterPos();
}  
window.onresize=function(){
	changeFooterPos();
} 
});" , \yii\web\View::POS_READY);

?>

<style>
<!--

-->

#startdate , #enddate{
	width: 100px;
}

section {
	word-break: break-word;
}

.div_progress{
	padding-top:5px;
}

span.arrived {
  color: #00bb4f;
  font-weight: bold;
  font-style: italic;
}


.xlbox .modal-body{
	max-height: 500px;
	overflow-y: auto;	
}

.xlbox .modal-dialog{
	width: 1200px;
}

.pageSize-dropdown-div{
	width:auto!important;
}


#txt_search{
	  border-right: 0px;
}

.search-mini-btn{
	height: 28px;
    display: inline-block;
    vertical-align: middle;
    margin-left: -4px;
    border: 1px solid #b9d6e8;
    border-left: 0px;
	background-color: white;
}

#div_btn_bar{
	margin-bottom: 5px;	
}
#div_btn_bar a{
	cursor: pointer;
}

#div_btn_bar a:hover, #div_btn_bar a:focus {
  text-decoration: none;
}
/**/
.table >tbody>tr>td>a{
	margin-right :5px;
}

.table >tbody>tr>td>a:last-child{
	margin-right :0px;
}

.input-group-btn > button{
  padding: 0px;
  height: 28px;
  width: 30px;
  border-radius: 0px;
  border: 1px solid #b9d6e8;
}

.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}

.div-input-group>.input-group>input{
	height: 28px;
}

.div_select_tag>.input-group , .div_new_tag>.input-group{
  float: left;
  width: 32%;
  vertical-align: middle;
  padding-right: 10px;
  padding-left: 10px;
  margin-bottom: 10px;
}

.div_select_tag{
	display: inline-block;
	border-bottom: 1px dotted #d4dde4;
	margin-bottom: 10px;
	width: 100%;
}

.div_new_tag {
  display: inline-block;
}

.span-click-btn{
	cursor: pointer;
}

.btn_tag_qtip a {
  margin-right: 5px;
}

.div_add_tag{
	width: 600px;
}

</style>
<script type="text/javascript">
var int = self.setInterval("ListTracking.AutoCheckTrackingProcess()",6000);
var int = self.setInterval("ListTracking.initNewAccountBinding()",5000);
function changeFooterPos(){
	var footerTop = $('.footer').offset().top;
	$('.17_track_link').offset({top: footerTop-20, left: 0});
}
</script>

<div class="tracking-index col2-layout">
	
	<?= $this->render('_menu') ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	<?= $this->render('_list_tracking_content',[
    			'TrackingData' => $TrackingData,
				'IsShowProgressBar'=> $IsShowProgressBar,
				'using_carriers'=>$using_carriers ,
				'account_data'=> $account_data,
				'AllTagData'=>$AllTagData,
				'country_list'=>$country_list,
				'tag_class_list'=>$tag_class_list,
    			]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
	<div class="17_track_link" style="display:inline-block;width:100%;text-align:center;font-size:100%;vertical-align:baseline;"><a href="http://www.17track.net/zh-cn" target="_blank">Tracking Data Powered by 17track.net</a></div>
</div>



<?= $this->render('_modal_import_excecl.php')?>