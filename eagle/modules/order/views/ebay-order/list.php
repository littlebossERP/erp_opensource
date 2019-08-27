<?php
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\helpers\EbayOrderHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\order\helpers\OrderListV3Helper;

$this->registerCssFile(\Yii::getAlias('@web')."/css/message/customer_message.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
#####################################	手工同步 start	#############################################################
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/ebayOrder/ebay_manual_sync.js", ['depends' => ['yii\web\JqueryAsset','eagle\assets\PublicAsset']]);
#####################################	手工同步 end	#############################################################
if (!empty($tag_class_list)){
	$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
}

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);

#####################################	左侧菜单新功能提示start	#############################################################
// $this->registerJs("$('ul.menu-lv-1 > li > a').last().append('<span class=\"left_menu_red_new click-to-tip\" data-qtipkey=\"message_and_recommend\" style=\"width:15px;padding:1px 4px;height:15px;\">?</span>');" , \yii\web\View::POS_READY);
#####################################	左侧菜单新功能提示end	#################################################################
$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/ebayOrder/ebayOrder.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("ebayOrder.OMSLeftMenuAutoLoad();" , \yii\web\View::POS_READY);

$uid = \Yii::$app->user->id;

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$this->registerJs("OrderTag.init();" , \yii\web\View::POS_READY);
//评价等级mapping
$commentType = EbayOrderHelper::$ebayCommentType;

$tmpCustomsort = Odorder::$customsort;

if(empty($this->title))
    $this->title = "eBay 订单列表";
    

#####################################	合并订单显示 start	#############################################################
$showMergeOrder = 0;
if (200 == @$_REQUEST['order_status'] && 223 == @$_REQUEST['exception_status']){
	$this->registerJs("OrderCommon.showMergeRow();" , \yii\web\View::POS_READY);
	$nowMd5 = "";
	$showMergeOrder = 1;
}
#####################################	合并订单显示 end 	#############################################################

#####################################	利润统计权限  start	#############################################################
$uid = \Yii::$app->user->id;

$profix_permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkOtherPermission('profix',$uid);
$this->registerJs("$('.profit_detail').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.list_profit_tip').popover();" , \yii\web\View::POS_READY);
#####################################	利润统计权限  end    	#############################################################

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderListV3.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
?>
<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<style>
.popover{
	max-width: 500px;
	min-width:200px;
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
	margin: 5px 0 0 0;
}

</style>	
<!--  
<div class="tracking-index"></div>
-->
<?php 

echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$menu_active,
]);
?>

    <!-- 账号异常提示区域 -->
    <?php
    if(!empty($problemAccounts)):?>
	<div class="alert alert-danger" role="alert" style="width:100%;line-height: 18px;">
	   <?php
	   if (!empty($problemAccounts['token_expired'])){?>
	   <span>您绑定的ebay账号有<?= count($problemAccounts['token_expired']); ?>个验证信息已经过期且自动更新失败！</span><a data-toggle="collapse" href="#expired-alert" aria-controls="expired-alert" aria-expanded="false" class="collapsed">点击展开详情</a><br>
	   <div class="collapse" id="expired-alert" >
	   <?php
	   	foreach ($problemAccounts['token_expired'] as $detailAccounts):?>
			<span>您绑定的ebay账号：<?=(isset($selleruserids_new[$detailAccounts["selleruserid"]]) ? $selleruserids_new[$detailAccounts["selleruserid"]] : $detailAccounts["selleruserid"]) ?> 的验证信息已经过期且自动更新失败！</span><br>
	   <?php endforeach;?>
	   </div><br>
	   <?php } ?>
	   
	   <?php if (!empty($problemAccounts['nosync'])){?>
	   <span>您绑定的ebay账号有<?= count($problemAccounts['nosync']); ?>个已经关闭同步！</span><a data-toggle="collapse" href="#expired-alert" aria-controls="expired-alert" aria-expanded="false" class="collapsed">点击展开详情</a><br>
	   <div class="collapse" id="expired-alert" >
	   <?php
	   	foreach ($problemAccounts['nosync'] as $detailAccounts):?>
			<span>您绑定的ebay账号：<?=(isset($selleruserids_new[$detailAccounts["selleruserid"]]) ? $selleruserids_new[$detailAccounts["selleruserid"]] : $detailAccounts["selleruserid"]) ?> 已经关闭同步！</span><br>
	   <?php endforeach;?>
	   </div>
	   <?php } ?>
	   
	</div>
	<?php endif;?>
	
	<?php if(!empty($SignShipWarningCount)){?>
	<div class="alert alert-danger" role="alert" style="width:100%;">
		<span>您绑定的ebay账号有<?=$SignShipWarningCount?>张订单通知平台发货失败，请尽快确认！<a href="/order/ebay-order/list?order_sync_ship_status=F" >点击</a><br>
	</div>
	<?php } ?>

<!-- ----------------------------------已付款订单分类搜索 start--------------------------------------------------------------------------------------------- -->	
	<?php 
	$counter = [];
	echo OrderFrontHelper::displayOrderPaidProcessHtml($counter)?>
	
<!-- ----------------------------------已付款订单分类搜索 end--------------------------------------------------------------------------------------------- -->

<!-- ----------------------------------搜索区域 start--------------------------------------------------------------------------------------------- -->	
	<div>
		<form class="form-inline" id="form1" name="form1" action="/order/ebay-order/list" method="post">
		<input type="hidden" name ="menu_select" value="<?php echo isset($_REQUEST['menu_select'])?$_REQUEST['menu_select']:'';?>">
		<input type="hidden" name ="select_bar" value="<?php echo isset($_REQUEST['select_bar'])?$_REQUEST['select_bar']:'';?>">
		<input type="hidden" name ="order_status" value="<?php echo isset($_REQUEST['order_status'])?$_REQUEST['order_status']:'';?>">
		<input type="hidden" name ="exception_status" value="<?php echo isset($_REQUEST['exception_status'])?$_REQUEST['exception_status']:'';?>">
		<input type="hidden" name ="pay_order_type" value="<?php echo isset($_REQUEST['pay_order_type'])?$_REQUEST['pay_order_type']:'';?>">
		<input type="hidden" name ="is_merge" value="<?php echo isset($_REQUEST['is_merge'])?$_REQUEST['is_merge']:'';?>">
		<?=Html::hiddenInput('customsort', @$_REQUEST['customsort'],['id'=>'customsort']);?>
		<?=Html::hiddenInput('ordersorttype', @$_REQUEST['ordersorttype'],['id'=>'ordersorttype']);?>
		<!-- ----------------------------------第一行 --------------------------------------------------------------------------------------------- -->
		<div style="margin:10px 0px 0px 0px">
		<?php // echo Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px;width:150px','prompt'=>'卖家账号'])?>
		
		<?php
		//店铺查找代码 S
		?>
		<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
		<?php
		$omsPlatformFinds = array();
// 		$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'ebay\',\'\')','label'=>'全部');
		
		if(count($selleruserids_new) > 0){
			$selleruserids_new['select_shops_xlb'] = '选择账号';

			foreach ($selleruserids_new as $tmp_selleruserKey => $tmp_selleruserid){
				$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'ebay\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
			}
			
			$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'ebay'));
			if(count($pcCombination) > 0){
				foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'ebay\',\''.'com-'.$pcCombination_K.'-com'.'\')',
						'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'oms\',\'ebay\',\''.$pcCombination_K.'\')');
				}
			}
		}
		echo OrderListV3Helper::getDropdownToggleHtml('卖家账号', $omsPlatformFinds);
		if(!empty($_REQUEST['selleruserid_combined'])){
			echo '<span onclick="OrderCommon.order_platform_find(\'oms\',\'ebay\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
		}
		//店铺查找代码 E
		?>
		
			<div class="input-group iv-input">
		        <?php $sel = [
		       		'srn'=>'SRN',
		        	'ebay_orderid'=>'手工订单号',
					'sku'=>'SKU',
					//'itemid'=>'ItemID',
					'tracknum'=>'物流号',
					'buyerid'=>'买家账号',
		        	'consignee'=>'收件人',
					'email'=>'买家Email',
					'order_id'=>'小老板订单号',
					'root_sku'=>'配对SKU',
					'product_name'=>'产品标题',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:140px;margin:0px','onchange'=>'OrderCommon.keys_change_find(this)'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:250px','placeholder'=>'多个请用空格分隔或Excel整列粘贴'])?>
		      	
		    </div>
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
	    	<a id="simplesearch"  style="font-size:12px;text-decoration:none;" onclick=""  data-toggle="collapse" href="#div_custom_condition" aria-controls="div_custom_condition" aria-expanded="false">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
	    	
			<!-- 
			<button type="button" id="sync-btn"  title="手动同步订单" class="iv-btn btn-important"  onclick="ebayOrder.dosyncorder();" >同步订单</button>
			-->
			<a id="sync-btn" href="sync-order-ready" target="_modal" title="同步订单" class="iv-btn btn-important" auto-size style="color:white;" btn-resolve="false" btn-reject="false">同步订单</a>
			<!--  -->
			<a target="_blank"  title="手工订单" class="iv-btn btn-important" href="/order/order/manual-order-box?platform=ebay" >手工订单</a>
			 
			<!----------------------------------------------------------- 常用筛选 ----------------------------------------------------------->
			<div class="pull-right" style="height: 40px;display:none;">
				<?php
		    	if (!empty($counter['custom_condition'])){
		    		$sel_custom_condition = array_merge(['加载常用搜索'] , array_keys($counter['custom_condition']));
		    	}else{
		    		$sel_custom_condition =['0'=>'加载常用搜索'];
		    	}
		    	
		    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
		    	<?=Html::button('保存为常用搜索',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
			</div>
			
	    	<div id="div_custom_condition" class="collapse <?=$showsearch;?>" >
	    	
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<?=Html::dropDownList('country',@$_REQUEST['country'],$countrys,['class'=>'iv-input','prompt'=>'国家','id'=>'country','style'=>'width:200px'])?>
			
			<?php 
			
			echo Html::dropDownList('carrier_code',@$_REQUEST['carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code','style'=>'width:200px;margin:0px'])
			?>

			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
			<?php 
				echo Html::dropDownList('tracker_status',@$_REQUEST['tracker_status'],$TrackerStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px','prompt'=>'物流跟踪状态'])?>
			</div>

			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<?=Html::dropDownList('order_status',@$_REQUEST['order_status'],$orderStatus21,['class'=>'iv-input','prompt'=>'小老板订单状态','id'=>'order_status','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('fuhe',@$_REQUEST['fuhe'],$ebayCondition,['class'=>'iv-input','prompt'=>'ebay状态条件','id'=>'order_source_status','style'=>'width:200px;margin:0px'])?>
			
			
			 <?=Html::dropDownList('pay_order_type',@$_REQUEST['pay_order_type'],$PayOrderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px','prompt'=>'已付款订单类型'])?>
			 
			
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px','prompt'=>'重新发货类型'])?>
			
			<?=Html::dropDownList('seller_commenttype',@$_REQUEST['seller_commenttype'],$commentType,['class'=>'iv-input','style'=>'width:200px;margin:0px','prompt'=>'评价等级'])?>
			
			<?php if($profix_permission) {?> 
			<?=Html::dropDownList('profit_calculated',@$_REQUEST['profit_calculated'],[2=>'未计算',1=>'已计算'],['class'=>'iv-input','prompt'=>'是否计算过利润','id'=>'profit_calculated','style'=>'width:200px;'])?>
			<?php } ?>
			 <?php 
			 /*
			 $exceptionStatusList = [''=>'异常状态'];
				$exceptionStatusList+= ['223'=>'待合并'] ;
				
				?>
			 <?=Html::dropDownList('exception_status',@$_REQUEST['exception_status'],$exceptionStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])
			 */
			 ?>
			 </div>
			 <!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			 <div style="margin:20px 0px 0px 0px">
			<?php 
			if (count($warehouses)>1){
				echo Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:150px;margin:0px']);
				echo ' ';
			}
			$warehouses +=['-1'=>'未分配'];//table 中可能 有-1的值
			$orderCaptureList = ['N'=>'正常订单','Y'=>'手工订单'];
			echo Html::dropDownList('order_capture',@$_REQUEST['order_capture'],$orderCaptureList,['class'=>'iv-input','prompt'=>'是否手工订单']);
			
			$orderCaptureList = [OdOrder::ORDER_VERIFY_PENDING=>'待同步',OdOrder::ORDER_VERIFY_VERIFIED=>'已同步' ,'noneed'=>'无需同步'];
			echo Html::dropDownList('order_verify',@$_REQUEST['order_verify'],$orderCaptureList,['class'=>'iv-input','prompt'=>'是否同步paypal地址']);
			?> 
			 
			
			  
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'iv-input'])?>
        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<div id="div_sys_tag" style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach ($OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
			}
			//echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			<!-- 
			<div class="btn-group" data-toggle="buttons">
			<?php foreach ($OrderSysTagMapping as $tag_code=> $label){
				//echo Html::($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
				echo '<button type="button" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-ok-sign" style="margin-right: 5px;"></span>'.TranslateHelper::t($label).'</button>';
				
			}
			
			//echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			</div>
			-->
			</div>
			<!-- ----------------------------------第六行--------------------------------------------------------------------------------------------- -->
			<?php if (!empty($all_tag_list)):?>
			<div style="margin:20px 0px 0px 0px">
				<div class="pull-left">
				<strong style="font-weight: bold;font-size:12px;">自定义标签：</strong>
				</div>
				<div class="pull-left" style="height: 40px;">
				<?=Html::checkboxlist('sel_tag',@$_REQUEST['sel_tag'],$all_tag_list);?>
				</div>
			</div>
			<?php endif; // 自定义标签?>
			</div> 
			<!-- ----------------------------------虚拟发货 状态--------------------------------------------------------------------------------------------- -->
			
			<?php 
			if (@$_REQUEST['order_status'] != OdOrder::STATUS_CANCEL){
				\eagle\modules\order\helpers\OrderFrontHelper::displayOrderSyncShipStatusToolbar(@$_REQUEST['order_sync_ship_status'],'ebay');
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
	
<!-- ----------------------------------dashboard start--------------------------------------------------------------------------------------- -->

<div class="hidden" id="dash-board-enter" onclick="showDashBoard(0)" style="<?//$isDisplayDashBoardStr?>background-color:#374655;color: white;display: none;" title="展开dash-board">展开订单监测面板</div>
<!-- ----------------------------------dashboard end----------------------------------------------------------------------------------------- -->		
	
<!-- ----------------------------------列表 start--------------------------------------------------------------------------------------------- -->		
<div style="clear: both;">
		<form name="a" id="a" action="" method="post">
		<div class="pull-left" style="height: 40px;">
		<!--
		<?=Html::button('刷新左侧统计',['class'=>"iv-btn btn-refresh",'onclick'=>"refreshLeftMenu()",'name'=>'btn_refresh_left_menu'])?>
		<span qtipkey="oms_refresh_left_menu_ebay" class ="click-to-tip"></span>
		-->
		<?php 
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='100'){
			echo Html::button('确认到款',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('signpayed','ebay');"]);
			echo "&nbsp;";
			echo Html::button('取消订单',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('cancelorder');"]);
		}elseif (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeItemDeclarationInfo');"]). "&nbsp;";
			$PayBtnHtml .= Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('signwaitsend');"]). "&nbsp;";
			$PayBtnHtml .=  '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('mergeorder');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (!empty($_REQUEST['is_merge'])){
				echo Html::button(TranslateHelper::t('取消合并'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('cancelMergeOrder');"]).'&nbsp;';
			}
			
			echo $PayBtnHtml;
			
		}
		
		if (@$_REQUEST['pay_order_type'] == 'reorder' || in_array(@$_REQUEST['exception_status'], ['203','222','202']) ){
			//echo Html::checkbox( 'chk_refresh_force','',['label'=>TranslateHelper::t('强制重新检测')]);
		}
		?>
		<!-- 
		<button type="button" class="iv-btn btn-important" onclick="ebayOrder.retrieveEbayOrderItem()">更新无商品订单</button>	
		-->
		<?php
			//echo Html::dropDownList('do','',$doarr,['onchange'=>"orderCommonV3.doaction2(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
			echo \eagle\modules\order\helpers\OrderListV3Helper::getDropdownToggleHtml('批量操作', $doarr, 'orderCommonV3.doaction3');
		?>
		
		<!--<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"ebayOrder.exportorder(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>--> 
		<!--<?=Html::dropDownList('orderExcelprint','',['-1'=>'导出订单','0'=>'按勾选导出','1'=>'按所有页导出'],['onchange'=>"OrderCommon.orderExcelprint($(this).val())",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>-->
		
		<?php
		$excelActionItems = array('0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'), '1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出'));
		$excelDownListHtml = OrderListV3Helper::getDropdownToggleHtml('导出订单', $excelActionItems);
		echo $excelDownListHtml;
		?>
		<?php $divTagHtml = "";
			$div_event_html = "";
		?>
		<?php if($profix_permission){
			\eagle\modules\order\helpers\OrderFrontHelper::displayProfixCalcToolbar();
		} ?>
		
		</div>
<!-- ----------------------------------导入跟踪号start--------------------------------------------------------------------------------------------- -->	
	
		<div class="pull-right" style="height: 40px;">
			<button type="button" class="iv-btn btn-important" onclick="OrderCommon.importTrackNoBox('ebay')">导入跟踪号</button>	
		</div>
	
<!-- ----------------------------------导入跟踪号end--------------------------------------------------------------------------------------------- -->			

		<br>
			<?php
			 
				echo $this->render('../order/order_list_v3',['carriers'=>$carriers, 'models'=>$models,'countrys'=>$countrys,
						'warehouses'=>$warehouses, 'non17Track'=>$non17Track, 'tag_class_list'=>$tag_class_list,
						'current_order_status' => empty($_REQUEST['order_status']) ? '' : $_REQUEST['order_status'],
						'current_exception_status' => empty($_REQUEST['exception_status']) ? '' : $_REQUEST['exception_status'],
						'platform_type'=>'ebay']);
			?>
			
		</form>
		
<!-- ----------------------------------分页start--------------------------------------------------------------------------------------------- -->			
		<?php if($pages):?>
		<div class="btn-group" >
			<?=\yii\widgets\LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
		</div>
			<?=\eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
			
		<?php endif;?>
<!-- ----------------------------------分页end--------------------------------------------------------------------------------------------- -->	
		
		
		
		
</div>
<!-- ----------------------------------列表end--------------------------------------------------------------------------------------------- -->	
<?php if (@$_REQUEST['order_status'] == 200 ):?>
<div>
<ul>
	<li style="float: left;line-height: 60px;">你还可以查看以下处理阶段的订单：</li>
	<li style="float: left;"><?php echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'ebay',[(string)OdOrder::PAY_PENDING])?></li>
</ul>

</div>
<?php endif;?>


<div style="clear: both;"></div>

<div id="div_hidden_content" style="display: none;">
<?=$divTagHtml?>

<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,$carriers)?></div>
</div>
<!--  
<div class="_state" ></div>
-->

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
	<div>更新所有已付款订单的状态。<br>监测订单主要检测的内容参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_61.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_61.html')?></a></div>
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
	<div>对符合条件的订单,可发送站内信通知给买家，进行提示或催促。<br>同时根据平台规则，允许的可以发送店铺商品信息进行推广。<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?>" target="_blank">介绍： <?=SysBaseInfoHelper::getHelpdocumentUrl('faq_76.html')?></a></div>
</div>
<div><input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
	    	 <input type='hidden' id='search_count' value='<?=$search_count ?>'></div>

<!-- Modal 即时发送站内信的modal-->
<div class="modal fade" id="myMessage" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <form  enctype="multipart/form-data"?>">
  <div class="modal-dialog">
    <div class="modal-content">
      
    </div>
  </div>
  </form>
</div>

<!-- 手动同步订单的modal -->
<div class="modal fade" id="syncorderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>