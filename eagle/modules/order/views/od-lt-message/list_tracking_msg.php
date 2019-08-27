<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\data\Sort;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\order\helpers\AliexpressOrderHelper ;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\order\helpers\OrderTrackingMessageHelper;
use eagle\modules\order\helpers\EbayOrderHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\models\UserEdmQuota;
use eagle\modules\util\helpers\SysBaseInfoHelper;


$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJsFile($baseUrl."js/project/order/station_letter.js", ['depends' => ['yii\jui\JuiAsset']]);
//$this->registerJsFile($baseUrl."js/project/tracking/tracking_tag.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderActionPublic.js", ['depends' => ['yii\web\JqueryAsset']]);

//$this->registerJs("TrackingTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
//$this->registerJs("TrackingTag.TagList=".json_encode($AllTagData).";" , \yii\web\View::POS_READY);

//$this->registerJs("TrackingTag.init();" , \yii\web\View::POS_READY);
//$this->registerCssFile($baseUrl."css/tracking/tracking.css", ['depends'=>["eagle\assets\AppAsset"]]);
$this->registerJs("$(\"a[title=\'立即更新\']\").remove();" , \yii\web\View::POS_READY);//删除立即跟新按钮

$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerCssFile(\Yii::getAlias('@web')."/css/message/customer_message.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/profit/import_file.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/trackingMessage.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.icon-ignore_search').popover();" , \yii\web\View::POS_READY);

if (empty($_REQUEST['platform']))
	$sortAttr = ['order_id','track_no','seller_id','create_time','update_time','status','ship_by','stay_days','delivery_fee','notified_seller'];
else
	$sortAttr = ['order_id','track_no','seller_id','ship_out_date','update_time','status','ship_by','stay_days','delivery_fee','notified_seller'];
$sort = new Sort(['attributes' =>$sortAttr]);

$parcel_label_mapping = [
//'all_parcel'=>TranslateHelper::t('全部包裹'),
//'normal_parcel'=>TranslateHelper::t('正常包裹'),
'shipping_parcel'=>TranslateHelper::t('运输途中'),
'no_info_parcel'=>TranslateHelper::t('查询不到'),
'suspend_parcel'=>TranslateHelper::t('延迟查询'),
//'exception_parcel'=>TranslateHelper::t('异常包裹'),
'rejected_parcel'=>TranslateHelper::t('异常退回'),
'ship_over_time_parcel'=>TranslateHelper::t('运输过久'),
'arrived_pending_fetch_parcel'=>TranslateHelper::t('到达待取'),
'unshipped_parcel'=>TranslateHelper::t('无法交运'),
'received_parcel'=>TranslateHelper::t('已签收'),
"unregistered_parcel"=>TranslateHelper::t("无挂号"),
"expired_parcel"=>TranslateHelper::t("过期物流号"),
"received_parcel"=>TranslateHelper::t("成功签收"),
"platform_confirmed_parcel"=>TranslateHelper::t("买家已确认"),
"ignored_parcel"=>TranslateHelper::t("忽略(不再查询)"),
];

$menu_platform = (!empty($_REQUEST['platform'])?strtolower($_REQUEST['platform']):"");
$menu_parcel_classification = (!empty($_REQUEST['parcel_classification'])?strtolower($_REQUEST['parcel_classification']):"");
$puid = \Yii::$app->user->identity->getParentUid ();
$divTagHtml = "";
$div_event_html = "";
//$IsShowProgressBar = TRUE;
$distinct_account_list = [];

$uid = \Yii::$app->user->id;

$warehouses = $warehouseids;
if (count($warehouses)>1){
	$warehouses +=['-1'=>'未分配'];
}
$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();

#####################################	左侧菜单新功能提示start	#############################################################
//$this->registerJs("$('ul.menu-lv-1 > li > a').last().append('<span class=\"left_menu_red_new click-to-tip\" data-qtipkey=\"message_and_recommend\">?</span>');" , \yii\web\View::POS_READY);
#####################################	左侧菜单新功能提示end	#################################################################
$this->registerJs("OrderTrackingMessage.init();" , \yii\web\View::POS_READY);

$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderTag.init();" , \yii\web\View::POS_READY);
$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);
?>

<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>

<style>
.popover{
	max-width: 500px;
}

.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
.sprite_pay_0,.sprite_pay_1,.sprite_pay_2,.sprite_pay_3,.sprite_shipped_0,.sprite_shipped_1,.sprite_check_1,.sprite_check_0
{
	display:block;
	background-image:url(/images/MyEbaySprite.png);
	overflow:hidden;
	float:left;
	width:20px;
	height:20px;
	text-indent:20px;
}
.sprite_pay_0
{
	background-position:0px -92px;
}
.sprite_pay_1
{
	background-position:-50px -92px;
}
.sprite_pay_2
{
	background-position:-95px -92px;
}
.sprite_pay_3
{
	background-position:-120px -92px;
}
.sprite_shipped_0
{
	background-position:0px -67px;
}
.sprite_shipped_1
{
	background-position:-50px -67px;
}
.sprite_check_1
{
	background-position:-100px -15px;
}
.sprite_check_0
{
	background-position:-77px -15px;
}
.exception_201,.exception_202,.exception_221,.exception_210,.exception_222,.exception_223,.exception_299{
	display:block;
	background-image:url(/images/icon-yichang-eBay.png);
	overflow:hidden;
	float:left;
	width:30px;
	height:15px;
	text-indent:20px;
}
.div_add_tag{
	width: 600px;
}
.exception_201{
	background-position:-3px -10px;
}
.exception_202{
	background-position:-26px -10px;
}
.exception_221{
	background-position:-55px -10px;
	width:50px;
}
.exception_210{
	background-position:-107px -10px;
}
.exception_222{
	background-position:-135px -10px;
}
.exception_223{
	background-position:-170px -10px;
}
.exception_299{
	background-position:-199px -10px;
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

.multiitem{
	padding:0 4px 0 4px;
	background:rgb(255,153,0);
	border-radius:8px;
	color:#fff;
}

.dash-board .modal-dialog{
	width: 900px;
}
.nopadingAndnomagin{
	padding:0px;
	margin:0px;
}

.17track-trackin-info-win .modal-dialog{
	height:80%;
}
</style>
<!--<div class="tracking-index col2-layout">-->
<?php 
if(!isset($_REQUEST['platform'])){
	echo "没有选择有效的销售平台！";echo "</div>";exit();
}
$menu=[];
switch ($_REQUEST['platform']){
	case 'aliexpress':
		//$menu = AliexpressOrderHelper::get_aliexpress_left_menu_data($counter);
		break;
	case 'ebay':
		//$menu = EbayOrderHelper::getLeftMenuTree();
		break;
	case 'cdiscount':
		//$menu = [];
		break;
	case 'amazon':
		//$menu = [];
		break;
	default:
		echo "该功能暂时不支持此平台..."; echo "</div>";
		exit();
}
$doarr_one=[
'TrackingEvent'=>'查看物流进度',
'showMessageBox'=>'发信站内信',
'ignoreMsgSend'=>'忽略发送',
];

?>
<?php 
	//echo $this->render('_leftmenu_2',['counter'=>$counter,'menu'=>$menu]);
?>
<!--<div class="content-wrapper" >-->
<div>
<?php if(!empty($_REQUEST['platform']) && in_array($_REQUEST['platform'],['cdiscount','amazon'])){
	$listStoreMailInfo = OrderTrackingMessageHelper::listPlatformStoreMailAddressSetting($_REQUEST['platform']);
	$allSetted = true;
	foreach($listStoreMailInfo as $store=>$info){
		if(empty($info['setted_mail_address']))
			$allSetted = false;
	}
	if($allSetted){ 
		echo '<button class="btn btn-info" onclick="setStoreMailInfo(\''.$_REQUEST['platform'].'\')">设置店铺email</button>';
	}else{ 
		echo '<button class="btn btn-danger" onclick="setStoreMailInfo(\''.$_REQUEST['platform'].'\')">设置店铺email</button>';
	}
	$quotaInfo = UserEdmQuota::findOne($puid);
	if(empty($quotaInfo)) $remaining_quota = 20;
	else $remaining_quota = $quotaInfo->remaining_quota;
	echo "<label class='btn btn-default' style='cursor: auto;margin-left:10px;border-radius:5px 0px 0px 5px'>剩余邮件额度：".$remaining_quota."</label>";
	echo '<button class="btn btn-info" onclick="viewQuotaHistory()" style="border-radius:0px 5px 5px 0px">查看额度历史</button>';
	echo '<span class="alert alert-warning" qtipkey="why_send_email_recommend" style="margin-left:10px;padding: 10px 15px;">为何要设置店铺邮件地址？</span>';
} ?>
<a class="btn btn-info" target="_blank" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?>" style="margin-left: 10px;">功能介绍</a>
</div>
<form id="searchForm" name="form1" action="" method="post">
	<input type="hidden" name="platform" value="<?=$_REQUEST['platform']?>">
	<input type="hidden" name="pos" value="<?=$_REQUEST['pos']?>">
	<div>
		<select name="selleruserid" class="eagle-form-control">
			<option value=""><?= TranslateHelper::t('平台账号 ')?></option>
		<?php 
		if (!empty($account_data)){
		foreach($account_data as $selleruserid=>$store_name):?>
			<?php if (!in_array($selleruserid,$distinct_account_list)):?>
			<option value="<?=$selleruserid?>"  <?php if (! empty($_REQUEST['selleruserid'])) if ($_REQUEST['selleruserid']==$selleruserid) echo " selected " ?>><?=$store_name?></option>
			<?php 
				$distinct_account_list [] = $selleruserid;
			endif;
			?>
		<?php endforeach;
		}?>
		</select>
		<!-- <div class="eagle-form-control" style="width:110px;display:inline-block;background-color: rgb(235, 235, 228);">未获得评价的订单</div> -->
		<select name="is_send" class="eagle-form-control">
			<option value="" <?= (empty($_REQUEST['is_send'])?" selected ":"")?>><?= TranslateHelper::t('当前状态下是否发信')?></option>
			<option value="Y"
				<?php if (! empty($_REQUEST['is_send'])) if ($_REQUEST['is_send']=='Y') echo " selected " ?>><?= TranslateHelper::t('已发信')?></option>
			<option value="N"
				<?php if (! empty($_REQUEST['is_send'])) if ($_REQUEST['is_send']=='N') echo " selected " ?>><?= TranslateHelper::t('未发信')?></option>
			<option value="I"
				<?php if (! empty($_REQUEST['is_send'])) if ($_REQUEST['is_send']=='I') echo " selected " ?>><?= TranslateHelper::t('已忽略(默认不显示)')?></option>
		</select> 
		<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'eagle-form-control','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
		订单时间
		<input type="date" id="startdate" class="eagle-form-control" name="startdate" value="<?= (empty($_REQUEST['startdate'])?"":$_REQUEST['startdate']);?>">
    	<?= TranslateHelper::t('到')?>
    	<input type="date" id="enddate" class="eagle-form-control" name="enddate" value="<?= (empty($_REQUEST['enddate'])?"":$_REQUEST['enddate']);?>">
		
    	<input type="text" id='txt_search' name="txt_search"  class="eagle-form-control" placeholder="<?= TranslateHelper::t('物流号 订单号')?>" value="<?= (empty($_REQUEST['txt_search'])?"":$_REQUEST['txt_search']);?>">
		
		<?=Html::Button('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search','onclick'=>"searchButtonClick()"])?>
					
    </div>
	<div id="div_btn_bar" style="margin-bottom:5px;">
		<button type="button" class="btn-xs btn-transparent font-color-1"
			onclick="OrderTrackingMessage.batchIgnoreMsgSend()" style="font-size:14px;"
			qtipkey="tracker_mark_handled_button">
			<span class="iconfont icon-mail_sended" aria-hidden="true" style="color:red;height:16px"></span>
			<?= TranslateHelper::t('标记已发信(忽略发信)')?>
		</button>
		
		<button type="button"  class="btn-xs btn-transparent font-color-1"
			onclick="StationLetter.batchShowMessageBox()"
			style="font-size:14px;">
			<span class="egicon-envelope" aria-hidden="true" style="height:16px"></span>
			<?= TranslateHelper::t('批量发信')?>
		</button>
		<?php if(!empty($_REQUEST['pos']) && $_REQUEST['pos']=='RGE'){ ?>
		<button type="button" class="btn-xs btn-info" onclick="StationLetter.setServiceDeliveryDays('all')" qtipkey="od-lt-message-set-rge-days" style="border:0px;">
			设置不同物流的订单求好评时机
		</button>
		<?php } ?>
	</div>
		<!-- Table -->
	<table class="table table-condensed table-bordered" id="lt-msg-list-table" style="font-size:12px;">
		<tr>
			<th width="1%">
				<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span>
				<input  id="ck_all" class="ck_0" type="checkbox" onclick="ck_allOnClick(this)" >
			</th>
			<th width="4%"><b>订单号</b></th>
			<th width="12%"><b>商品SKU</b></th>
			<th width="5%"><b>总价</b></th>
			<th width="18%"><b>付款日期</b></th>
			<th width="6%"><b>收件国家</b></th>
			<th width="25%"><b>运输服务</b></th>
			<th width="6%"><b>平台状态</b></span></th>
			<th width="5%"><b>小老板状态</b><span qtipkey="oms_order_lb_status_description"></span></th>
			<th width="13%"><b>物流状态</b><span qtipkey="oms_order_carrier_status_description"></span></th>
			<th ><b>操作</b><span qtipkey="oms_lt_message_action"></span></th>
		</tr>
			<?php $carriers=CarrierApiHelper::getShippingServices(false); ?>
			<?php if (count($models)){foreach ($models as $order){?>
			<?php $generateOrderHtmls = OrderTrackingMessageHelper::generateOneOrderInfoTr($_REQUEST['platform'], $order, $country_list, $carriers)?>
			<?php echo  $generateOrderHtmls['trHtml'];?>
			<?php $divTagHtml.=$generateOrderHtmls['divTagHtml']; ?>
			<?php $div_event_html.=$generateOrderHtmls['div_event_html']; ?>
		
			<?php $lastMessage = OrderFrontHelper::getLastMessage($order->order_source_order_id)?>
			<?php if (count($order->items)){
				if($order->order_source=='cdiscount'){//CD特殊处理
					$nonDeliverySku=CdiscountOrderInterface::getNonDeliverySku();
					$tmpItems=[];
					foreach($order->items as $key=>$item){
						if(!in_array($item->sku,$nonDeliverySku))
							$tmpItems[] = $item;
					}
					$order->items = $tmpItems;
				}
					foreach ($order->items as $key=>$item){?>
			<?php $generateItemHtmls = OrderTrackingMessageHelper::generateOneOrderItemTr($_REQUEST['platform'], $order, $item, $key, $warehouses, $lastMessage)?>
			<?php echo  $generateItemHtmls;?>
		
			<?php }}?>
			<?php } }?>
			</table>

</form>

<?php
//exit(print_r($TrackingData,true));
if(! empty($pagination)):?>
<div>
	<div id="lt-msg-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$pagination , 'pageSizeOptions'=>array( 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
	$options = array();
	$options['pagerId'] = 'lt-msg-list-pager';// 下方包裹 分页widget的id
	$options['action'] = 'order/od-lt-message/list-tracking-message'; // ajax请求的 action
	$options['page'] = $pagination->getPage();// 当前页码
	$options['per-page'] = $pagination->getPageSize();// 当前page size
	$param_str='platform='.$_REQUEST['platform'];
	if(!empty($_REQUEST['pos']))
		$param_str.= '&pos='.$_REQUEST['pos'];
	if(!empty($_REQUEST['is_send']))
		$param_str.= '&is_send='.$_REQUEST['is_send'];
	if(!empty($_REQUEST['selleruserid']))
		$param_str.= '&selleruserid='.$_REQUEST['selleruserid'];
	if(!empty($_REQUEST['startdate']))
		$param_str.= '&startdate='.$_REQUEST['startdate'];
	if(!empty($_REQUEST['enddate']))
		$param_str.= '&enddate='.$_REQUEST['enddate'];
	if(!empty($_REQUEST['txt_search']))
		$param_str.= '&txt_search='.$_REQUEST['txt_search'];
	if(!empty($_REQUEST['shipmethod']))
		$param_str.= '&shipmethod='.$_REQUEST['shipmethod'];
	if(!empty($param_str))
		$options['action'] .= '?'.$param_str;
	
	$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
	$this->registerJs('$("#lt-msg-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
	$this->registerJs("OrderTrackingMessage.init();" , \yii\web\View::POS_READY);
?>
<?php endif;?>

<!--</div>-->
<!--</div>-->
<div style="display:none">
	<?= $divTagHtml;?>
	<?= $div_event_html;?>
</div>

<div class="report-no-info-win"></div>
<div class="17track-trackin-info-win"></div>
<div class="set-store-mail-win"></div>
<div class="view-quota-history-win"></div>
<div class="set-service-delivery-days-win"></div>
<div id="message_and_recommend" style="display: none">
	<div>对符合条件的订单,可发送站内信通知给买家，进行提示或催促。<br>同时根据平台规则，允许的可以发送店铺商品信息进行推广。<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?>" target="_blank">介绍： <?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?></a></div>
</div>


<script type="text/javascript">

function reportTrackerNoInfo(id){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/report-no-info?id='+id, 
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "report-no-info-win",
				title: Translator.t('物流反馈'),
				message: result,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开提交界面失败,后台返回异常'));
			return false;
		}
	});
}

function iframe_17Track(num, obj){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/show17-track-tracking-info?num='+num,
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "17track-trackin-info-win",
				title: Translator.t('17track查询结果'),
				message: result,
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function doTrack(num) {
    if(num===""){
        alert("Enter your number."); 
        return;
    }
    YQV5.trackSingle({
        YQ_ContainerId:"YQContainer",       //必须，指定承载内容的容器ID。
        YQ_Height:400,      //可选，指定查询结果高度，最大高度为800px，默认撑满容器。
        YQ_Fc:"0",       //可选，指定运输商，默认为自动识别。
        YQ_Lang:"zh-cn",       //可选，指定UI语言，默认根据浏览器自动识别。
        YQ_Num:num     //必须，指定要查询的单号。
    });
}

//展开，收缩订单商品
function spreadorder(obj,id){
	if(typeof(id)=='undefined'){
		//未传参数进入，全部展开或收缩
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.xiangqing').hide();
			$(obj).parent().html('<span class="glyphicon glyphicon-plus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).parent().html('<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}
}

function setStoreMailInfo(platform){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/od-lt-message/set-store-mail-info?platform='+platform,
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "set-store-mail-win",
				title: Translator.t('设置店铺eMail信息'),
				message: result,
				buttons:{
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
					OK: {  
						label: Translator.t("保存"),  
						className: "btn-primary",  
						callback: function () {
							saveStoreMailInfo();
						}  
					}, 
				}
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function saveStoreMailInfo(){
	$.ajax({
		type: "POST",
		url:'/order/od-lt-message/save-mail-info',
		data:$("#store_mail_info").serialize(),
		dataType:'json',
		async: false,
		success: function (result) {
			if(result.success){
				$('.set-store-mail-win').modal('hide');
				$('.set-store-mail-win').on('hidden.bs.modal', '.modal', function(event) {
					$(this).removeData('bs.modal');
				});
				bootbox.alert(Translator.t('操作成功！'));
				return true;
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			return Translator.t('操作失败,后台返回异常');
		}
	});
}

function viewQuotaHistory(){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/od-lt-message/view-quota-history',
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "view-quota-history-win",
				title: Translator.t('邮件发送额度充值历史'),
				message: result,
				buttons:{
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
				}
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
	
}

function ck_allOnClick(obj){
	if($(obj).prop("checked")==true){
		$(".ck").prop("checked",true);
	}else{
		$(".ck").prop("checked",false);
	}
}

</script>
