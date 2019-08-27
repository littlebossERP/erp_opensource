<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;

$menu_message=MessageApiHelper::getMenuStatisticData();
$selected_type=!empty($_REQUEST['selected_type'])?$_REQUEST['selected_type']:"";
$no_answer_num=isset($no_answer)?count($no_answer):0;
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
// print_r($selected_type);
// print_r($no_answer);
// exit();
// $menu_platform = (!empty($_GET['platform'])?strtolower($_GET['platform']):"");
// $menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");

// //var_dump($menu_parcel_classification);

// $menu_label_count = TrackingHelper::getMenuStatisticData();
// $lv3Str = "";

// $d=strtotime("-7 days");
// $startdate = date("Y-m-d", $d);
// $RequestGoodEvaluationLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RGE&parcel_classification=received_parcel&select_parcel_classification=received_parcel&is_send=N&startdate='.$startdate]);
// $RequestPendingFetchLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RPF&parcel_classification=arrived_pending_fetch_parcel&select_parcel_classification=arrived_pending_fetch_parcel&is_send=N&startdate='.$startdate]);
// $RequestShippingLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RSHP&parcel_classification=shipping_parcel&select_parcel_classification=shipping_parcel&is_send=N&startdate='.$startdate]);
// $RequestRejectedLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RRJ&parcel_classification=rejected_parcel&select_parcel_classification=rejected_parcel&is_send=N&startdate='.$startdate]);

?>


	
	<!-- 左侧标签快捷区域 -->
	<div id="sidebar" style="width: 197px;">
	<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>	
	
		<ul class="ul-sidebar-one" style="margin-top: 12px;">
			<li class="ul-shrinkHead-li">
				<span class="egicon-people"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('客户管理')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">					
					<li  class="ul-sidebar-li<?php echo $selected_type=="customer"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/customer-list','selected_type'=>'customer'])?>"><font><?= TranslateHelper::t('所有客户')?><?php echo $no_answer_num!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$no_answer_num}</span>":null?></font></a></li>								
				</ul>
			</li>
			<?php if(isset($menu_message['ebay'])):
			$num=$menu_message['ebay'];
			?>
			<li class="ul-shrinkHead-li">
				<span class="egicon-envelope3"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('eBay 站内信/留言')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="ebay"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','selected_type'=>'ebay'])?>"><font><?= TranslateHelper::t('所有信息')?><?php echo $num['all_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num['all_unread_msg']}</span>":null?></font></a></li>					
					<li  class="ul-sidebar-li<?php echo $selected_type=="ebay_order"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'O','selected_type'=>'ebay_order'])?>"><font><?= TranslateHelper::t('订单相关')?><?php echo $num['order_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num['order_unread_msg']}</span>":null?></font></a></li>
					<li  class="ul-sidebar-li<?php echo $selected_type=="ebay_product"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'P','selected_type'=>'ebay_product'])?>"><font><?= TranslateHelper::t('商品相关')?><?php echo $num['product_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num['product_unread_msg']}</span>":null?></font></a></li>
					<li  class="ul-sidebar-li<?php echo $selected_type=="ebay_system"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'S','selected_type'=>'ebay_system'])?>"><font><?= TranslateHelper::t('系统平台')?><?php echo $num['system_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num['system_unread_msg']}</span>":null?></font></a></li>
					<li  class="ul-sidebar-li<?php echo $selected_type=="ebay_disputes"?" active":null;?>"><a class="" href="<?= Url::to(['/message/all-customer/show-ebay-disputes'])?>"><font><?= TranslateHelper::t('ebay纠纷')?></font></a></li>	
				</ul>
			</li>
			<?php endif;?>
			
			<?php if(isset($menu_message['aliexpress'])):
			$num2=$menu_message['aliexpress']['order'];
			$num6=$menu_message['aliexpress']['station'];
			$all_num = $num2['all_unread_msg'] + $num6['all_unread_msg'];
			?>
			<li class="ul-shrinkHead-li">
				<span class="egicon-envelope3"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('速卖通  站内信/留言')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="aliexpress"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','selected_type'=>'aliexpress'])?>"><font><?= TranslateHelper::t('所有信息')?><?php echo $all_num!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$all_num}</span>":null?></font></a></li>					
					<li  class="ul-sidebar-li<?php echo $selected_type=="Order_aliexpress_order"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>1,'select_type'=>'O','selected_type'=>'Order_aliexpress_order'])?>"><font><?= TranslateHelper::t('订单留言')?><?php echo $num2['order_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num2['order_unread_msg']}</span>":null?></font></a></li>
					<li  class="ul-sidebar-li<?php echo $selected_type=="Station_aliexpress_order"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'O','selected_type'=>'Station_aliexpress_order'])?>"><font><?= TranslateHelper::t('站内信-订单')?><?php echo $num6['order_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num6['order_unread_msg']}</span>":null?></font></a></li>
					<li  class="ul-sidebar-li<?php echo $selected_type=="Station_aliexpress_product"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'P','selected_type'=>'Station_aliexpress_product'])?>"><font><?= TranslateHelper::t('站内信-商品')?><?php echo $num6['product_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num6['product_unread_msg']}</span>":null?></font></a></li>
				    <li  class="ul-sidebar-li<?php echo $selected_type=="Station_aliexpress_other"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'M','selected_type'=>'Station_aliexpress_other'])?>"><font><?= TranslateHelper::t('站内信-其他')?><?php echo $num6['other_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num6['other_unread_msg']}</span>":null?></font></a></li>					
				</ul>
			</li>
			<?php endif;?>
			
			<?php if(isset($menu_message['dhgate'])):
			$num3=$menu_message['dhgate'];
			?>
			<li class="ul-shrinkHead-li">
				<span class="egicon-envelope3"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('敦煌  站内信/留言')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="dhgate"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','selected_type'=>'dhgate'])?>"><font><?= TranslateHelper::t('所有信息')?><?php echo $num3['all_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num3['all_unread_msg']}</span>":null?></font></a></li>					
					<li  class="ul-sidebar-li<?php echo $selected_type=="dhgate_order"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'O','selected_type'=>'dhgate_order'])?>"><font><?= TranslateHelper::t('订单相关')?><?php echo $num3['order_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num3['order_unread_msg']}</span>":null?></font></a></li>				
					<li  class="ul-sidebar-li<?php echo $selected_type=="dhgate_product"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'P','selected_type'=>'dhgate_product'])?>"><font><?= TranslateHelper::t('商品相关')?><?php echo $num3['product_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num3['product_unread_msg']}</span>":null?></font></a></li>
                    <li  class="ul-sidebar-li<?php echo $selected_type=="dhgate_system"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'S','selected_type'=>'dhgate_system'])?>"><font><?= TranslateHelper::t('系统平台')?><?php echo $num3['system_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num3['system_unread_msg']}</span>":null?></font></a></li>			    
				</ul>
			</li>
			<?php endif;?>
			
			<?php if(isset($menu_message['wish'])):
			$num4=$menu_message['wish'];
			?>
			<li class="ul-shrinkHead-li">
				<span class="egicon-envelope3"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('wish 站内信/留言')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="wish"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'wish','selected_type'=>'wish'])?>"><font><?= TranslateHelper::t('所有信息')?><?php echo $num4['all_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num4['all_unread_msg']}</span>":null?></font></a></li>					
				</ul>
			</li>
			<?php endif;?>
			
			<?php if(isset($menu_message['cdiscount'])):
			$num5 = $menu_message['cdiscount'];
			?>
			<li class="ul-shrinkHead-li">
				<span class="egicon-envelope3"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('cdiscount 订单留言')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="cdiscount"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','selected_type'=>'cdiscount'])?>"><font><?= TranslateHelper::t('所有信息')?><?php echo $num5['all_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num5['all_unread_msg']}</span>":null?></font></a></li>					
				    <li  class="ul-sidebar-li<?php echo $selected_type=="cdiscount_order"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','select_type'=>'O','selected_type'=>'cdiscount_order'])?>"><font><?= TranslateHelper::t('Your claims')?><?php echo $num5['order_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num5['order_unread_msg']}</span>":null?></font></a></li>					
				    <li  class="ul-sidebar-li<?php echo $selected_type=="cdiscount_order_question"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','select_type'=>'Q','selected_type'=>'cdiscount_order_question'])?>"><font><?= TranslateHelper::t('Orders questions')?><?php echo $num5['order_question_unread_msg']!=0?"<span class='badge no-qtip-icon' style='background-color:#ff9900; color:white;' qtipkey='cs_os_unread_number'>{$num5['order_question_unread_msg']}</span>":null?></font></a></li>					
				</ul>
			</li>
			<?php endif;?>
			
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-book"></span><span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('模版管理')?></font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">	
					<li  class="ul-sidebar-li<?php echo $selected_type=="template-manage"?" active":null;?>"><a  class="" href="<?= Url::to(['/message/all-customer/mail-template','select_platform'=>'template-manage','selected_type'=>'template-manage'])?>"><font><?= TranslateHelper::t('所有模版')?></font></a></li>					
				</ul>
			</li>
		</ul>
		
		
		<hr class="sidebar-hr hidden">
			<!-- 左侧快捷操作区域 -->
		<div class="sidebarLv1Title hidden">
			<div>
			<span class="egicon-binding"></span>
			<?= TranslateHelper::t('平台绑定')?></div>
		</div>
		<ul  class="ul-sidebar-one hidden">
			<li class="ul-sidebar-li"><a class="" href="#"> <font><?= TranslateHelper::t('eBay')?></font></a></li>
			<li class="ul-sidebar-li"><a class="" href="#"> <font><?= TranslateHelper::t('速卖通')?></font></a></li>
		</ul>
	</div>



