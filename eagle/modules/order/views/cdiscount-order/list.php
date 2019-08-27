<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\widgets\SizePager;
use eagle\models\SaasCdiscountUser;
use eagle\models\catalog\Product;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\order\helpers\OrderListV3Helper;
use eagle\modules\util\helpers\RedisHelper;

/* OMS 2.1
if (!empty($_REQUEST['country'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['country']),true)).";" , \yii\web\View::POS_READY);
}

$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.customCondition=".json_encode(@$counter['custom_condition']).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);
*/


$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerCssFile(\Yii::getAlias('@web')."/css/message/customer_message.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/profit/import_file.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);

//$this->registerJs("bindingSysTagClickEvent();" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/cdiscountOrder.js", ['depends' => ['yii\web\JqueryAsset']]);
//自动刷新左侧菜单统计数
//$this->registerJs("cdiscountOrder.OMSLeftMenuAutoLoad();" , \yii\web\View::POS_READY);

$orderStatus21 = OdOrder::getOrderStatus('oms21'); // oms 2.1 的订单状态

$uid = \Yii::$app->user->id;

$profix_permission = UserApiHelper::checkOtherPermission('profix',$uid);

//$next_show = \Yii::$app->redis->hget('CdiscountOms_DashBoard',"user_$uid".".next_show");
$next_show = RedisHelper::RedisGet('CdiscountOms_DashBoard',"user_$uid".".next_show");
$showDashBoard=true;
if(!empty($next_show)){
	if(time()<strtotime($next_show))
		$showDashBoard=false;
}
if(!empty($_REQUEST))
	$showDashBoard=false;

/*
######################################	重要提示展示start	#################################################################
$important_change_tip_show = false;
//$important_change_tip_times= \Yii::$app->redis->hget('CdiscountOms_VerChangeTip',"user_$uid".".offerTerminator");//redis记录根据需要变换
$important_change_tip_times = RedisHelper::RedisGet('CdiscountOms_VerChangeTip',"user_$uid".".offerTerminator");
if(empty($important_change_tip_times))
	$important_change_tip_times = 0;
if($important_change_tip_times<3){
	$important_change_tip_show = true;
}
if($important_change_tip_show){
	$showDashBoard = false;
	$this->registerJs("showImportantChangeTip();" , \yii\web\View::POS_READY);
	$important_change_tip_times = (int)$important_change_tip_times+1;
	//\Yii::$app->redis->hset('CdiscountOms_VerChangeTip',"user_$uid".".offerTerminator",$important_change_tip_times);;
	RedisHelper::RedisSet('CdiscountOms_VerChangeTip',"user_$uid".".offerTerminator",$important_change_tip_times);
}
#####################################	重要提示展示end		#################################################################
*/


#####################################	左侧菜单新功能提示start	#################################################################
//$this->registerJs("$('ul.menu-lv-1 > li > a').last().append('<span class=\"left_menu_red_new\">new</span>');" , \yii\web\View::POS_READY);
// $this->registerJs("$('ul.menu-lv-1 > li > a').last().append('<span class=\"left_menu_red_new click-to-tip\" data-qtipkey=\"message_and_recommend\">new</span>');" , \yii\web\View::POS_READY);
#####################################	左侧菜单新功能提示end	#################################################################
$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);

/*
if($showDashBoard)
	$this->registerJs("showDashBoard(1);" , \yii\web\View::POS_READY);
*/

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.icon-ignore_search').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.profit_detail').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.list_profit_tip').popover();" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/cdiscount_manual_sync.js", ['depends' => ['yii\web\JqueryAsset','eagle\assets\PublicAsset']]);

$cd_source_status_mapping = CdiscountOrderHelper::$cd_source_status_mapping;

$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();

//合并订单显示
$showMergeOrder = 0;
if (200 == @$_REQUEST['order_status'] && 223 == @$_REQUEST['exception_status']){
	$this->registerJs("OrderCommon.showMergeRow();" , \yii\web\View::POS_READY);
	$nowMd5 = "";
	$showMergeOrder = 1;
}

$tmpCustomsort = Odorder::$customsort;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderListV3.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
 
?>
<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<style>
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
	background-position:-200px -10px;
}
.text-invalid{
	color: #8a6d3b;
	text-decoration: line-through;
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

.div_add_tag{
	width: 600px;
}
.cd_fbc_inco{
	width:15px;
	height:15px;
	background:url("/images/cdiscount/clogpicto.jpg") no-repeat;
	display: block;
    background-size: 15px;
	float:left;
}
#dash-board-enter{
	position: fixed;
	bottom: 30px;
	left: 0px;
	width: 34px;
	height: 56px;
	padding: 3px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 0px 5px 5px 0px;	
	cursor: pointer;
}
#cd-oms-reminder-content{
	left: 0px;
	padding: 5px;
    border: 2px solid transparent;
    border-radius: 5px;
	float: left;
    width: 100%;
	padding-bottom: 10px;
}
#cd-oms-reminder-close{
	-webkit-appearance: none;
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
	color: #609ec4;
	float: right;
    font-size: 21px;
    font-weight: bold;
    line-height: 1;
    text-shadow: 0 1px 0 #fff;
}
#cd-oms-reminder-close-day{
	-webkit-appearance: none;
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
	color: #609ec4;
	float: right;
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
    text-shadow: 0 1px 0 #fff;
}
.cd-oms-weird-status-wfs{
	background: url(/images/cdiscount/cdiscount_icon.png) no-repeat -1px -1627px;
    background-size: 100%;
    float: left;
    width: 18px;
    height: 18px;
}
td .popover{
	max-width: inherit;
    max-height: inherit;
}
.popover{
	min-width: 200px;
}
.oms_list_profit input{

}
.text-success{
	color: #2ecc71!important;
}
.text-warning{
	color: #8a6d3b!important;
}
.iv-btn.btn-important{
	padding-right:15px!important;
}
</style>	
<div class="tracking-index col2-layout">
<?=$this->render('_leftmenu',['counter'=>$counter]);?>

<!-- 
<div class="" id="dash-board-enter" onclick="showDashBoard(0)" style="background-color:#374655;color: white;" title="展开dash-board">展开订单监测面板</div>
 -->
 
<div class="content-wrapper" >
	<?php $problemAccounts = CdiscountOrderInterface::getUserAccountProblems($uid); ?>
	<?php if(!empty($problemAccounts['token_expired'])){ $problemAccountNames=[];?>
	<?php foreach ($problemAccounts['token_expired'] as $account){
		$problemAccountNames[] = $account['store_name'];
	}?>
	<!-- 账号异常提示区域 -->
	<div class="alert alert-danger" role="alert" style="width:100%;">
		<span>您绑定的Cdiscount账号：<?=implode(' , ', $problemAccountNames) ?> 的验证信息已经过期且自动更新失败！<br>请检查您的绑定信息(如账号密码/API账号API密码)是否有误。</span>
	</div>
	<?php } ?>
<!-- oms 2.1 nav start  -->
	<!-- 
	<?php 
	if (in_array(@$_REQUEST['order_status'], [OdOrder::STATUS_NOPAY, OdOrder::STATUS_PAY  ,OdOrder::STATUS_WAITSEND , OdOrder::STATUS_SHIPPED]) ){
		echo $order_nav_html;
	}
	?>
	 -->
<!-- oms 2.1 nav end  -->

<!-- ----------------------------------CD快速筛序 start--------------------------------------------------------------------------------------------- -->	
	<!-- 
	<?php //if (isset($_REQUEST['menu_select']) && in_array($_REQUEST['menu_select'],array('all'))){
		
		$fixed_url = Url::to(['/order/cdiscount-order/list']);
		
		$todayUrl = $fixed_url ."?timetype=soldtime&startdate=".date('Y-m-d');
		$cdStautsArray= [
				TranslateHelper::t('今日新订单')=>[ 'url'=>$todayUrl.'&select_bar=todayorder','tabbar'=>@$counter['todayorder']],
				TranslateHelper::t('等待您发货')=>[ 'url'=>$fixed_url.'?order_source_status=WaitingForShipmentAcceptation&order_status=200'.'&select_bar=sendgood','tabbar'=>@$counter['sendgood']],
				TranslateHelper::t('有纠纷的订单')=>[ 'url'=>$fixed_url.'?order_source_status=IN_ISSUE'.'&select_bar=issueorder','tabbar'=>@$counter['issueorder']],
				TranslateHelper::t('有未读留言')=>[ 'url'=>$fixed_url.'?new_msg_tag=1'.'&select_bar=newmessage','tabbar'=>@$counter['newmessage']],
		]
		?>
	<ul class="nav nav-pills">
	 <li role="presentation" ><div style="line-height: 32px;width:120px;text-align:right;"><?=TranslateHelper::t('特别关注：')?></div></li>
	  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['select_bar'])&&$_REQUEST['select_bar'] == 'todayorder'){echo 'btn-important';}?>" href="<?php echo $cdStautsArray[TranslateHelper::t('今日新订单')]['url']?>"><?=TranslateHelper::t('今日新订单').'('.@$counter['todayorder'].')'?></a></li>
	</ul>
	<ul class="nav nav-pills">
	 <li role="presentation" ><div style="line-height: 32px;width:120px;text-align:right;"><?=TranslateHelper::t('等待您操作的订单：')?></div></li>
	  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['select_bar'])&&$_REQUEST['select_bar'] == 'sendgood'){echo 'btn-important';}?>" href="<?php echo $cdStautsArray[TranslateHelper::t('等待您发货')]['url']?>"><?=TranslateHelper::t('等待您发货').'('.@$counter['sendgood'].')'?></a></li>
	  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['select_bar'])&&$_REQUEST['select_bar'] == 'issueorder'){echo 'btn-important';}?>" href="<?php echo $cdStautsArray[TranslateHelper::t('有纠纷的订单')]['url']?>" ><?=TranslateHelper::t('有纠纷的订单').'('.@$counter['issueorder'].')'?></a></li>
	  <li role="presentation" ><a class ="iv-btn <?php if (isset($_REQUEST['select_bar'])&&$_REQUEST['select_bar'] == 'newmessage'){echo 'btn-important';}?>" href="<?php echo $cdStautsArray[TranslateHelper::t('有未读留言')]['url']?>"><?=TranslateHelper::t('未读留言').'('.@$counter['newmessage'].')'?></a></li>
	</ul>
	 -->
	<!-- 
	<ul class="nav nav-pills">
	 <li role="presentation" ><div style="line-height: 32px;width:120px;text-align:right;"><?=TranslateHelper::t('等待买家操作的订单：')?></div></li>
	</ul>
	 -->
	<?php //}?>
<!-- ----------------------------------CD快速筛序 end--------------------------------------------------------------------------------------------- -->
	
<!-- ----------------------------------已付款订单分类筛选 start--------------------------------------------------------------------------------------------- -->	
		<?php 
		if (@$_REQUEST['order_status'] == 200 || in_array(@$_REQUEST['exception_status'], ['0','210','203','222','202','223','299']) ):	
		echo '<ul class="clearfix"><li style="float: left;line-height: 22px;">订单类型：</li><li style="float: left;line-height: 22px;">';
		echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'cdiscount',[(string)OdOrder::PAY_PENDING]);
		echo '</li><li class="clear-both"></li></ul>';
		endif;
		?>
<!-- ----------------------------------已付款订单分类筛选 end--------------------------------------------------------------------------------------------- -->
	
<!-- ----------------------------------搜索区域 start--------------------------------------------------------------------------------------------- -->	
	<div>
		<form class="form-inline" id="form1" name="form1" action="/order/cdiscount-order/list" method="post">
		<input type="hidden" name ="select_bar" value="<?php echo isset($_REQUEST['select_bar'])?$_REQUEST['select_bar']:'';?>">
		<input type="hidden" name ="order_status" value="<?php echo isset($_REQUEST['order_status'])?$_REQUEST['order_status']:'';?>">
		<input type="hidden" name ="exception_status" value="<?php echo isset($_REQUEST['exception_status'])?$_REQUEST['exception_status']:'';?>">
		<input type="hidden" name ="pay_order_type" value="<?php echo isset($_REQUEST['pay_order_type'])?$_REQUEST['pay_order_type']:'';?>">
		<input type="hidden" name ="is_merge" value="<?php echo isset($_REQUEST['is_merge'])?$_REQUEST['is_merge']:'';?>">
		<?=Html::hiddenInput('customsort', @$_REQUEST['customsort'],['id'=>'customsort']);?>
		<?=Html::hiddenInput('ordersorttype', @$_REQUEST['ordersorttype'],['id'=>'ordersorttype']);?>
		<!-- ----------------------------------第一行 --------------------------------------------------------------------------------------------- -->
		<div style="margin:10px 0px 0px 0px">
		<?php // echo Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$cdiscountUsersDropdownList,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px;width:150px','prompt'=>'卖家账号'])?>
			
			<?php
			//店铺查找代码 S
			?>
			<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
			<?php
			$omsPlatformFinds = array();
// 			$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'cdiscount\',\'\')','label'=>'全部');
			
			if(count($cdiscountUsersDropdownList) > 0){
				$cdiscountUsersDropdownList['select_shops_xlb'] = '选择账号';
	
				foreach ($cdiscountUsersDropdownList as $tmp_selleruserKey => $tmp_selleruserid){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'cdiscount\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
				}
				
				$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'cdiscount'));
				if(count($pcCombination) > 0){
					foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
						$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'cdiscount\',\''.'com-'.$pcCombination_K.'-com'.'\')',
							'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'oms\',\'cdiscount\',\''.$pcCombination_K.'\')');
					}
				}
			}
			echo OrderListV3Helper::getDropdownToggleHtml('卖家账号', $omsPlatformFinds);
			if(!empty($_REQUEST['selleruserid_combined'])){
				echo '<span onclick="OrderCommon.order_platform_find(\'oms\',\'cdiscount\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
			}
			//店铺查找代码 E
			?>
			
			<div class="input-group iv-input">
		        <?php $sel = [
		        	'order_source_order_id'=>'Cdiscount订单号',
					'sku'=>'SKU',
					'tracknum'=>'物流号',
					'buyeid'=>'买家账号',
		        	'consignee'=>'买家姓名',
					'order_id'=>'小老板单号',
					'root_sku'=>'配对SKU',
					'product_name'=>'产品标题',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:140px;margin:0px','onchange'=>'OrderCommon.keys_change_find(this)'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:250px','placeholder'=>'多个请用空格分隔或Excel整列粘贴'])?>
		      	
		    </div>
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    <a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>
		    <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		    <!-- 
	    	<?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
 			-->
	    	<a target="_blank" title="手工订单" class="iv-btn btn-important" href="/order/order/manual-order-box?platform=cdiscount">手工订单</a>
	    	<!----------------------------------------------------------- 同步情况 ----------------------------------------------------------->
			<?php if (!empty($_REQUEST['menu_select']) && $_REQUEST['menu_select'] =='all'):?>
			<div class="pull-right" style="height: 40px;">
				<a href="order-sync-info" target="_modal" title="同步情况" class="iv-btn btn-important" auto-size style="color:white;" btn-resolve="false" btn-reject="true">同步情况</a>
			</div>
			<!-- 
			<div class="pull-right" style="height: 40px;">
				<?=Html::button(TranslateHelper::t('手工同步'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.showManualSyncInfo(this);",'data-url'=>'/order/cdiscount-order/manual-sync-order'])?><span qtipkey="cd_manual-sync-order"></span>
			</div>
			 -->
			<?php endif;?>
						<!----------------------------------------------------------- 手工同步  ----------------------------------------------------------->
			<div style="display: inline-block;">
				<a id="sync-btn" href="sync-order-ready" target="_modal" title="拉取订单" class="iv-btn btn-important" auto-size style="color:white;" btn-resolve="false" btn-reject="false">同步订单</a>
				<span qtipkey="cd_manual-sync-order"></span>
			</div>
	    	<?php
	    	/*
	    	if (!empty($counter['custom_condition'])){
	    		$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($counter['custom_condition']));
	    	}else{
	    		$sel_custom_condition =['0'=>'加载常用筛选'];
	    	}
	    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input']);
	    	echo Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition']);
	    	*/
	    	?>
	    	
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap(); 
			if (count($warehouses)>1){
				$warehouses +=['-1'=>'未分配'];
				echo Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:200px;margin:0px']);
				echo ' ';
			}
			?>
			
			<?php 
			// 物流商
			$carriersProviderList = CarrierOpenHelper::getOpenCarrierArr('2');
			
			echo Html::dropDownList('carrier_code',@$_REQUEST['carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code','style'=>'width:200px;margin:0px'])
			?>
			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
			<?php $TrackerStatusList = [''=>'物流跟踪状态'];
			 	$tmpTrackerStatusList = Tracking::getChineseStatus('',true);
				$TrackerStatusList+= $tmpTrackerStatusList;?>
			 <?=Html::dropDownList('logistic_status',@$_REQUEST['logistic_status'],$TrackerStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			<?php if($profix_permission) {?> 
			<?=Html::dropDownList('profit_calculated',@$_REQUEST['profit_calculated'],[2=>'未计算',1=>'已计算'],['class'=>'iv-input','prompt'=>'是否计算过利润','id'=>'profit_calculated','style'=>'width:200px;'])?>
			<?php } ?>
			</div>
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<?=Html::dropDownList('order_capture',@$_REQUEST['order_capture'],['N'=>'普通订单','Y'=>'手工订单'],['class'=>'iv-input','prompt'=>'订单类型','id'=>'order_capture','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_type',@$_REQUEST['order_type'],['normal'=>'普通订单','fbc'=>'FBC订单'],['class'=>'iv-input','prompt'=>'CD订单类型','id'=>'order_type','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_status',@$_REQUEST['order_status'],$orderStatus21,['class'=>'iv-input','prompt'=>'小老板订单状态','id'=>'order_status','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_source_status',@$_REQUEST['order_source_status'],$cd_source_status_mapping,['class'=>'iv-input','prompt'=>'CD原始状态','id'=>'order_source_status','style'=>'width:200px;margin:0px'])?>
			
			 <?php $PayOrderTypeList = [''=>'已付款订单类型'];
				$PayOrderTypeList+=Odorder::$payOrderType ;
				$PayOrderTypeList[OdOrder::PAY_EXCEPTION] = '待合并';
				?>
			 <?=Html::dropDownList('pay_order_type',@$_REQUEST['pay_order_type'],$PayOrderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=Odorder::$reorderType ;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			
			 <?php 
				$weirdstatus=CdiscountOrderHelper::$CD_OMS_WEIRD_STATUS;
			 ?>
			
			 
			 </div>
			 <!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			 <div style="margin:20px 0px 0px 0px">
			 <?=Html::dropDownList('weird_status',@$_REQUEST['weird_status'],$weirdstatus,['class'=>'iv-input','prompt'=>'操作异常标签','id'=>'weird_status','style'=>'width:250px;margin:0px'])?>
			 
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','modifytime'=>'平台最后更新日期'],['class'=>'iv-input'])?>
        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	<?=Html::input('time','starttime',@$_REQUEST['starttime'],['class'=>'iv-input','style'=>'width:100px;margin:0px'])?>
        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			<?=Html::input('time','endtime',@$_REQUEST['endtime'],['class'=>'iv-input','style'=>'width:100px;margin:0px'])?>
			
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<div id="div_sys_tag" style="margin:20px 0px 0px 0px;display: inline-block;width:100%">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
			}
			//echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			</div>
			<!-- ----------------------------------第六行--------------------------------------------------------------------------------------------- -->
			<?php if (!empty($all_tag_list)):?>
			<div style="margin:20px 0px 0px 0px;display:inline-block;width:100%">

			<strong style="font-weight: bold;font-size:12px;float:left;margin:5px 0px 0px 0px;">自定义标签：</strong>


			<?=Html::checkboxlist('sel_tag',@$_REQUEST['sel_tag'],$all_tag_list);?>

			</div>
			<?php endif;?>
			</div> 
			<!-- ----------------------------------虚拟发货 状态--------------------------------------------------------------------------------------------- -->
			
			<?php 
			if (@$_REQUEST['order_status'] != OdOrder::STATUS_CANCEL){
				\eagle\modules\order\helpers\OrderFrontHelper::displayOrderSyncShipStatusToolbar(@$_REQUEST['order_sync_ship_status'],'cdiscount');
			}
			?>
			<div style="margin:20px 0px 0px 0px">
				<strong style="font-weight: bold;font-size:12px;">排序方式：</strong>
				<?php
				if(empty($_REQUEST['customsort'])){
					$_REQUEST['customsort'] = 'soldtime';
				}
				
				foreach ($tmpCustomsort as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".($_REQUEST['customsort'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code.
						"' sorttype='".@$_REQUEST['ordersorttype']."' onclick='OrderCommon.sortModeBtnClick(this)'>".$label.
						($_REQUEST['customsort'] == $tag_code ? " <span class='glyphicon glyphicon-sort-by-attributes".((empty($_REQUEST['ordersorttype']) || strtolower($_REQUEST['ordersorttype'])=='desc') ? '-alt' : '')."'></span>" : '').
						"</a>";
				}
				?>
			</div>
	    </div>
		</form>
	</div>
	<br><br>
<!-- ----------------------------------搜索区域 end--------------------------------------------------------------------------------------------- -->	
	
	<br>
	<div style="">
		<form name="a" id="a" action="" method="post">
		<?php 
		/*
		$doarr=[
			''=>'批量操作',
			'checkorder'=>'检查订单',
			'signshipped'=>'cdiscount标记发货',
			'mergeorder'=>'合并已付款订单',
			'changeshipmethod'=>'更改运输服务',
			'calculat_profit'=>'计算订单利润'
		];
		*/
		?>
		<?php if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeItemDeclarationInfo');"]). "&nbsp;";
			
			$doarr += ['changeWHSM'=>'修改仓库和运输服务'];
			$doarr += ['outOfStock'=>'标记为缺货'];
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('mergeorder');"]);
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (!empty($_REQUEST['is_merge'])){
				echo Html::button(TranslateHelper::t('取消合并'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('cancelMergeOrder');"]).'&nbsp;';
			}
			
			// 所有订单检测去除流程，所以所有都可以移入发货中
			echo Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('signwaitsend');"]);
			echo '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
			echo $PayBtnHtml;
			
		}
		if (@$_REQUEST['pay_order_type'] == 'reorder' || in_array(@$_REQUEST['exception_status'], ['203','222','202']) ){
			//echo Html::checkbox( 'chk_refresh_force','',['label'=>TranslateHelper::t('强制重新检测')]);
		}
		$doarr += ['signshipped'=>'虚拟发货(标记发货)'];
		if($profix_permission)
			$doarr += ['calculat_profit'=>'计算订单利润'];
		if(isset($doarr['givefeedback']))
			unset($doarr['givefeedback']);
		if(isset($doarr['refreshOrder']))
			unset($doarr['refreshOrder']);
		?>
		
		<?php
		$tmp_is_show = true;
		if(@$_REQUEST['order_status'] == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		if(($tmp_is_show == false) && (empty($_REQUEST['issuestatus']))){
			unset($doarr['signshipped']);
			unset($doarr['calculat_profit']);
		}
		
		if(!empty($_REQUEST['issuestatus'])){
			$doarr['signcomplete'] = '标记为已完成';
			$doarr['setSyncShipComplete'] = '标记为提交成功';
		}
		
		?>
		
		<!--<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>--> 
		
		<?php
			$doDownListHtml = OrderListV3Helper::getDropdownToggleHtml('批量操作', $doarr, 'orderCommonV3.doaction3');
			echo $doDownListHtml;
			//echo Html::dropDownList('do','',$doarr,['onchange'=>"orderCommonV3.doaction2(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
		?>
		<?php 
			$excelActionItems = array(
				'0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'),
				 '1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出')
			);
			$excelDownListHtml = OrderListV3Helper::getDropdownToggleHtml('导出订单', $excelActionItems);
			echo $excelDownListHtml;
			//echo Html::dropDownList('orderExcelprint','',['-1'=>'导出订单','0'=>'按勾选导出','1'=>'按所有页导出'],['onchange'=>"OrderCommon.orderExcelprint($(this).val())",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
		?>
		
		 
		<?php if($profix_permission){ ?>
		<div class="oms_list_profit" style="line-height: 20px;position: relative;display: inline-block;">
			<label style="border: 1px transparent;color: #555;font-size: 14px;height: 30px;padding: 5px;margin: 5px 0 10px 0;">选中订单的利润</label>
			<input type="text" id="selected_profit_calculated_rate" value="" readonly class='iv-input' style="width:50px;"> 
			<input type="text" id="selected_profit_total" value="" readonly class='iv-input' style="width:100px;">
			<img class="list_profit_tip" style="cursor: pointer;margin: 10px 0 0px 0;" width="16" src="/images/questionMark.png" data-toggle="popover" data-content="显示已经勾选的订单的利润统计情况;<br>第一项数值为:已统计/已勾选;<br>第二项数值为:已统计的总利润(RMB)" data-html="true" data-trigger="hover" >
		</div>
		<?php } ?>
		<?php 
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			echo "<div style='float:right'>";
			echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox('cdiscount')"]);
			echo "</div>";
		}
		?>
		<?php $divTagHtml = "";?>
		<?php $div_event_html = "";?>
		<br>
			<?php
			 
				echo $this->render('../order/order_list_v3',[ 'carriers'=>$carriers, 'models'=>$models,
						'warehouses'=>$warehouses, 'non17Track'=>$non17Track, 'tag_class_list'=>$tag_class_list,
						'current_order_status' => empty($_REQUEST['order_status']) ? '' : $_REQUEST['order_status'],
						'current_exception_status' => empty($_REQUEST['exception_status']) ? '' : $_REQUEST['exception_status'],
						'platform_type'=>'cdiscount']);
			?>
			
			
			<div class="btn-group" >
			<?=LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
			</div>
			<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ,500), 'class'=>'btn-group dropup'])?>
		</form>
	</div>
	<?php if (@$_REQUEST['order_status'] == 200 ):?>
	<div>
	<ul>
		<li style="float: left;line-height: 60px;">你还可以查看以下处理阶段的订单：</li>
		<li style="float: left;line-height: 60px;"><?php echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'cdiscount')?></li>
	</ul>
	
	</div>
	<?php endif;?>
<div style="clear: both;"></div>
</div></div>
<div style="display: none;">
<?=$divTagHtml?>
<?=$div_event_html?>
<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,CarrierApiHelper::getShippingServices())?></div>
<div class="dash-board"></div>
<div class="profit-order"></div>
</div>
<div class="17track-trackin-info-win">  </div>
<div class="important-change-tip-win"></div>
<div class="manual-sync-order-win"></div>

<div id="oms_order_pending_check" style="display: none">
	<div>新同步订单的默认都为“待检测”的状态，用户需要都为新订单手动做一次检测订单来开始订单流程操作</div>
</div>
<div id="oms_order_reorder" style="display: none">
	<div>操作暂停订单、缺货订单、已出库订单、取消订单和废弃订单的【重新发货】功能的订单都会汇总到当前状态，集中处理</div>
</div>
<div id="oms_order_exception" style="display: none">
	<div>汇总【检测订单】操作和该页面的【自动检测】操作中发现的有异常的订单，该异常订单都是需要用户处理后才能发货的订单。<br>异常详情参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_66.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_66.html')?></a></div>
</div>
<div id="oms_order_can_ship" style="display: none">
	<div>汇总所有没有异常可以直接发货的订单</div>
</div>
<div id="oms_order_status_check" style="display: none">
	<div>更新所有已付款订单的状态。<br>监测订单主要检测的内容参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_61.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_66.html')?></a></div>
</div>
<div id="oms_batch_ship_pack" style="display: none">
	<div>批量提交订单到 发货 物流模块<br><b style="font-weight: 600;color:red">该操作后，订单商品将不会出现于采购模块的见单采购中！</b><br>已付款的订单可以被多选，批量进行物流以及发货，请到 菜单顶部的 <b style="font-weight: 600;color:blue">物流</b>模块 或者 <b style="font-weight: 600;color:blue">发货</b>模块，进行物流商的运单申请 以及 批量拣货 配货 打印标签。<br>如果有部分订单处于 <b style="font-weight: 600;color:red">异常</b>状态的，建议点击右边的<b style="font-weight: 600;color:red">有异常</b>按钮，对异常订单进行处理，然后刷新该页面.</div>
</div>
<div id="oms_order_exception_pending_merge" style="display: none">
	<div>订单的收件人和收件地址都相同的订单可以合并。<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_62.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_62.html')?></a></div>
</div>
<div id="oms_order_exception_pending_merge_merge" style="display: none">
	<div>选中订单进行合并，合并完成后需要再检测订单是否有其他异常</div>
</div>
<div id="ms_order_exception_pending_merge_skip" style="display: none">
	<div>选中订单被标记为不可合并，标记完成后需要再检测订单是否有其他异常</div>
</div>
<div id="oms_order_exception_sku_not_exist" style="display: none">
	<div>订单商品的SKU不存在于商品库中<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_63.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_63.html')?></a></div>
</div>
<div id="oms_order_exception_sku_not_exist_create" style="display: none">
	<div>汇总选中订单不存在的商品，然后在生成sku的页面填写相关的商品信息就能生成小老板商品库</div>
</div>
<div id="oms_order_exception_no_warehouse" style="display: none">
	<div>订单没有符合仓库分配规则<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_64.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_64.html')?></a></div>
</div>
<div id="oms_order_exception_no_warehouse_assign" style="display: none">
	<div>临时方案：给选中的订单分配仓库。<br>提示：分配库存的时候可以指定运输服务方式，节约时间</div>
</div>
<div id="oms_order_exception_out_of_stock" style="display: none">
	<div>订单中的SKU库存不足<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_65.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_65.html')?></a></div>
</div>
<div id="oms_order_exception_out_of_stock_manage" style="display: none">
	<div>给选中的订单快速录入入库单 ，保证高效地完成库存管理</div>
</div>
<div id="oms_order_exception_out_of_stock_mark" style="display: none">
	<div>把选中的订单移动到缺货订单中，保证不影响其他订单的发货</div>
</div>
<div id="oms_order_exception_no_shipment" style="display: none">
	<div>订单没有匹配到适应的运输服务，需要修该运输服务匹配规则。<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_67.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_67.html')?></a></div>
</div>
<div id="oms_order_exception_no_shipment_assign" style="display: none">
	<div>给选中的订单分配运输方式,<br>请注意这里也可以修改指定仓库，若指定的产品库存不足的话，流程会回到库存不足的异常</div>
</div>
<div id="message_and_recommend" style="display: none">
	<div>对符合条件的订单,可发送站内信通知给买家，进行提示或催促。<br>同时根据平台规则，允许的可以发送店铺商品信息进行推广。<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?>" target="_blank">介绍：<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?></a></div>
</div>
<div><input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
	    	 <input type='hidden' id='search_count' value='<?=$search_count ?>'></div>
<script>
function doaction2(obj){
	var val = $(obj).val();
	if (val != ''){
		doaction(val );
		$(obj).val('');
	}
}
//批量操作
function doaction(val){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
    if($('.ck:checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的订单");return false;
    }
	switch(val){
		case 'setSyncShipComplete':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
	
			OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
			break;
		case 'cancelMergeOrder':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
	
			OrderCommon.cancelMergeOrder(thisOrderList);
			break;
		case 'ExternalDoprint':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			OrderCommon.ExternalDoprint(thisOrderList);
			break;
		case 'changeItemDeclarationInfo':
			var thisOrderList =[];
			
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
	
			OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
			break;
		case 'addMemo':
			var idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			OrderCommon.showAddMemoBox(idstr);
			break;
		case 'checkorder':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signshipped':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/signshipped'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'generateProduct':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/generateproduct'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'signcomplete':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/signcomplete'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'suspendDelivery':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				document.a.target="_blank";
    			document.a.action="<?=Url::to(['/order/order/deleteorder'])?>";
    			document.a.submit();
    			document.a.action="";
			}
			break;
		case 'cancelorder':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/cancelorder'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signwaitsend':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			OrderCommon.shipOrderOMS(idstr);
			break;
		case 'signpayed':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'reorder':
			var thisOrderList = [];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			OrderCommon.reorder(thisOrderList);
			break;
		case 'givefeedback':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/feedback'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'dispute':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/dispute'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'mergeorder':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/mergeorder'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'skipMerge':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/skipmerge'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'changeWHSM':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				thisOrderList.push($(this).val());
			});
			if (thisOrderList.length == 0){
				bootbox.alert('只能修改已付款状态的运输服务');
				return;
			}
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
			break;
		case 'changeshipmethod':
			var thisOrderList =[];
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				
				thisOrderList.push($(this).val());
				if (idstr != '') idstr+=',';
				idstr+=$(this).val();
			});

			if (idstr ==''){
				bootbox.alert('只能修改已付款状态的运输服务');
				return;
			}

			var html  = '以下未发货订单已经被选中<br>'+idstr +'<br><br>请选择批量使用物流运输方式 ：<select name="change_shipping_method_code">'+$('select[name=demo_shipping_method_code]').html()+'</select>';
			bootbox.dialog({
				title: Translator.t("订单详细"),
				className: "order_info", 
				message: html,
				buttons:{
					Ok: {  
						label: Translator.t("确定"),  
						className: "btn-primary",  
						callback: function () { 
							return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
						}
					}, 
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
				}
			});	
			
			break;
		case 'getEmail':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/cdiscount-order/get-email'])?>',{orderIds:idstr},function(result){
				location.reload();
			});
			break;
		case 'outOfStock':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'calculat_profit':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.showLoading();
			$.ajax({
				type: "POST",
					//dataType: 'json',
					url:'/order/order/profit-order', 
					data: {order_ids : idstr},
					success: function (result) {
						$.hideLoading();
						bootbox.dialog({
							className : "profit-order",
							//title: ''
							message: result,
						});
					},
					error: function(){
						$.hideLoading();
						bootbox.alert("出现错误，请联系客服求助...");
						return false;
					}
			});
			break;
		case 'delete_manual_order':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			OrderCommon.deleteManualOrder(idstr);
			break;		
		default:
			return false;
			break;
	}
}
//更改运输服务

function changeShipMethod(orderIDList , shipmethod){
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/order/order/changeshipmethod', 
			data: {orderIDList : orderIDList , shipmethod : shipmethod },
			success: function (result) {
				//bootbox.alert(result.message);
				if (result.success == false) 
					bootbox.alert(result.message);
				else{
					bootbox.alert({message:Translator.t("修改成功！") , callback: function() {  
		                window.location.reload(); 
		            },  
		            });
				}
				return false;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
	return false;
}

//单独操作
function doactionone(val,orderid){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	if(orderid == ""){ bootbox.alert("订单号错误");return false;}
	switch(val){
		case 'setSyncShipComplete':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
			break;
		case 'cancelMergeOrder':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.cancelMergeOrder(thisOrderList);
			break;
		case 'ExternalDoprint':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.ExternalDoprint(thisOrderList);
			break;
		case 'changeWHSM':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
			break;
		case 'changeItemDeclarationInfo':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
			break;
		case 'checkorder':
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'addMemo':
			var idstr = [];
			idstr.push(orderid);
			OrderCommon.showAddMemoBox(idstr);
			break;
		case 'editOrder':
			idstr = [];
			idstr.push(orderid);
			window.open("<?=Url::to(['/order/cdiscount-order/edit'])?>"+"?orderid="+orderid,'_blank');
			break;
		case 'signshipped':
			window.open("<?=Url::to(['/order/order/signshipped'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'generateProduct':
			document.a.action="<?=Url::to(['/order/order/generateproduct'])?>"+"?order_id="+orderid;
			idstr = [];
			idstr.push(orderid);
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/generateproduct'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'suspendDelivery':
			idstr = [];
			idstr.push(orderid);
			
			$.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				window.open("<?=Url::to(['/order/order/deleteorder'])?>"+"?order_id="+orderid,'_blank');
			}
			break;
		case 'cancelorder':
			idstr = [];
			idstr.push(orderid);
			$.post('<?=Url::to(['/order/order/cancelorder'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
			});
			break;
		case 'signpayed':
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'reorder':
			idstr = [];
			idstr.push(orderid);
			OrderCommon.reorder(idstr);
			break;
		case 'givefeedback':
			window.open("<?=Url::to(['/order/order/feedback'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'dispute':
			window.open("<?=Url::to(['/order/order/dispute'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'mergeorder':
			window.open("<?=Url::to(['/order/order/mergeorder'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'history':
			window.open("<?=Url::to(['/order/logshow/list'])?>"+"?orderid="+orderid,'_blank');
			break;
		case 'getorderno':
			OrderCommon.setShipmentMethod(orderid);
			break;
		case 'signwaitsend':
			OrderCommon.shipOrder(orderid);
			break;
		case 'invoiced':
			window.open("<?=Url::to(['/order/order/order-invoice'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'completecarrier':
			completecarrier(orderid);
			break;
		case 'outOfStock':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:thisOrderList},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'delete_manual_order':
			idstr = [];
			idstr.push(orderid);
			OrderCommon.deleteManualOrder(idstr);
			break;
		default:
			return false;
			break;
	}
}
//导出订单
function exportorder(type){
	if(type==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&type!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	window.open('<?=Url::to(['/order/excel/export-excel'])?>'+'?orderids='+idstr+'&excelmodelid='+type);
}

//移动订单状态到其他状态
function movestatus(val){
	if(val==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&val!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post('<?=Url::to(['/order/order/movestatus'])?>',{orderids:idstr,status:val},function(result){
		bootbox.alert('操作已成功');
	});
}

//修改订单的挂起状态
function changemanual(orderid,obj){
	$.post('<?=Url::to(['/order/order/changemanual'])?>',{orderid:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			var str;
			str=$(obj).html();
			if(str.indexOf('取消挂起')>=0){
				$(obj).html('<span class="glyphicon glyphicon-open toggleMenuL" title="挂起"><\/span>');			
			}else{
				$(obj).html('<span class="glyphicon glyphicon-save toggleMenuL" title="取消挂起"><\/span>');	
			}
		}else{
			bootbox.alert(result);
		}
	});
}

//添加自定义标签
function setusertab(orderid,tabobj){
	var tabid = $(tabobj).val();
	$.post('<?=Url::to(['/order/order/setusertab'])?>',{orderid:orderid,tabid:tabid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
		}else{
			bootbox.alert(result);
		}
	});
}

//添加备注
function updatedesc(orderid,obj){
	var desc=$(obj).prev();
    var oiid=$(obj).attr('oiid');
	var html="<textarea name='desc' style='width:200xp;height:60px'>"+desc.text()+"</textarea><input type='button' onclick='ajaxdesc(this)' value='修改' oiid='"+oiid+"'>";	
    desc.html(html);
    $(obj).toggle();
}
function ajaxdesc(obj){
	 var obj=$(obj);
	 var desc=$(obj).prev().val();
	 var oiid=$(obj).attr('oiid');
	  $.post('<?=Url::to(['/order/order/ajaxdesc'])?>',{desc:desc,oiid:oiid},function(r){
		  retArray=$.parseJSON(r);
		  if(retArray['result']){
		      obj.parent().next().toggle();
		      var html="<font color='red'>"+desc+"</font> <span id='showresult' style='background:yellow;'>"+retArray['message']+"</span>"
		      obj.parent().html(html);
		      setTimeout("showresult()",3000);
		  }else{
		      alert(retArray['message']);
		  }
	  })
}
function completecarrier(orderid){
	$.showLoading();
	var url = '/carrier/default/completecarrier?order_id='+orderid;
	$.get(url,function (data){
			$.hideLoading();
			var retinfo = eval('(' + data + ')');
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		}
	);
}
function showresult(){
    $('#showresult').remove();
}

function dosearch(name,val){
	$('#'+name).val(val);
	document.form1.submit();
}
//添加备注函数结束

function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
	$('select[name=keys]').val('order_id');
	$('select[name=timetype]').val('soldtime');
	$('select[name=ordersort]').val('soldtime');
	$('select[name=ordersorttype]').val('desc');
}

function closeReminder(){
	var child=document.getElementById("cd-oms-reminder-content");
	var reminder=document.getElementById("cd-oms-reminder");
	reminder.removeChild(child);
}

function closeReminderToday(){
	var child=document.getElementById("cd-oms-reminder-content");
	var reminder=document.getElementById("cd-oms-reminder");
	$.post('<?=Url::to(['/order/cdiscount-order/close-reminder'])?>',{},function(result){
		if(result == 'success'){
			reminder.removeChild(child);
		}else{
			console(result);
			reminder.removeChild(child);
		}
	});
}

//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		return false;
	}
	
}

function OmsViewTracker(obj,num){
	var s_trackingNo = $(obj).has('.text-success');
	if(typeof(s_trackingNo)!=='undefined' && s_trackingNo.length>0){
		var tracking_info_type=$(obj).data("info-type");
		if(tracking_info_type !== '17track'){
			var qtip = $(obj).find(".order-info").data('hasqtip');
			if(typeof(qtip)=='undefined')
				return false;
			var opened = $("#qtip-"+qtip).css("display");
			if(opened=='block')
				return true;
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/order/order/oms-view-tracker', 
				data: {invoker: 'Cdiscount-Oms'},
				success: function (result) {
					return true;
				},
				error :function () {
					return false;
				}
			});
		}else{
			$.showLoading();
			show17Track(num);
			$.hideLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/order/order/oms-view-tracker', 
				data: {invoker: 'Cdiscount-Oms'},
				success: function (result) {
					return true;
				},
				error :function () {
					return false;
				}
			});
		}
	}
}

function showDashBoard(autoShow){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/cdiscount-order/user-dash-board?autoShow='+autoShow, 
		success: function (result) {
			$.hideLoading();
			$("#dash-board-enter").toggle();
			bootbox.dialog({
				className : "dash-board",
				title: Translator.t('Cdiscount订单监控面板。该功能拉取validate状态订单，请在cdiscount后台设置<a href="http://help.littleboss.com/faq_55.html" target="_blank" style="font-weight:bolder;color:blue;font-size:15px;font-style:oblique;background-color:greenyellow;">auto validate</a>功能'),
				message: result,
				closeButton:false,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开Cdiscount订单监测面板失败'));
			return false;
		}
	});
}

function showImportantChangeTip(){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/cdiscount-order/important-change', 
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "important-change-tip-win",
				title: Translator.t('重要通知'),
				message: result,
				closeButton:true,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开重要改动提示窗口失败！'));
			return false;
		}
	});
}

function ignoreTrackingNo(order_id,track_no){
	bootbox.confirm({
		buttons: {  
			confirm: {  
				label: Translator.t("确认"),  
				className: 'btn-danger'  
			},  
			cancel: {  
				label: Translator.t("取消"),
				className: 'btn-primary'  
			}  
		}, 
		message: Translator.t("确认忽略物流号？<br><br>一般对平邮或者已经确认投递失败的包裹，进行此操作。如果确认忽略该物流号物流助手将不会再查询该物流号信息。您确定要忽略当前物流号吗？"),
		callback: function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "GET",
					url:'/order/order/ignore-tracking-no?order_id='+order_id+'&track_no='+track_no,
					dataType:'json',
					success: function (result) {
						$.hideLoading();
						if(result.success===true){
							bootbox.alert({
					            message: '操作成功',  
					            callback: function() {  
					            	window.location.reload();
					            },  
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert(Translator.t('操作失败,后台返回异常'));
						return false;
					}
				});
			}
		}
	});
}

function selected_switch(){
	/*
	var selected = 0;
	var selected_profited = 0;
	var profitToalt = 0;
	
	$('input[name="order_id[]"]:checked').each(function(){
		selected++;
		//console.log($(this).parents("tr").find(".profit_detail")[0]);
		var profit = $(this).parents("tr").find(".profit_detail").attr('data-profit');
		//console.log(profit);
		if(typeof(profit)!=='undefined' && profit!==''){
			profit = parseFloat(profit);
			selected_profited++;
			profitToalt += profit;
		}
	});
	$("#selected_profit_calculated_rate").val(selected_profited +'/'+selected);
	profitToalt = Math.round(profitToalt*Math.pow(10,2))/Math.pow(10,2);
	$("#selected_profit_total").val(profitToalt);
	*/
	OrderCommon.selected_switch();
}

function show17Track(num){
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

function bindingSysTagClickEvent(){
	$('#div_sys_tag :checkbox').on("click",function(){
		$('#div_sys_tag :checkbox[name!='+this.name+']').prop('checked',false);
		if (this.checked ==false) var tmpValue = 0;
		else var tmpValue = 1;
		window.location ="/order/<?=\Yii::$app->controller->id?>/<?=\Yii::$app->controller->action->id?>?"+this.name+'='+tmpValue;
	});
}
</script>