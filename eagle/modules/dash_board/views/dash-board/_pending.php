<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\permission\apihelpers\UserApiHelper;
				
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJs("DashBoard.initClickTip();" , \yii\web\View::POS_READY);
?>

<?php 
$platfromChNameMapping = [
'amazon'=>'Amazon',
'ebay'=>'eBay',
'aliexpress'=>'速卖通',
'wish'=>'Wish',
'dhgate'=>'敦煌',
'cdiscount'=>'Cdiscount',
'lazada'=>'Lazada',
'linio'=>'Linio',
'jumia'=>'Jumia',
'priceminister'=>'Priceminister',
'bonanza'=>'bonanza',
'rumall'=>'丰卖网',
'customized'=>'自定义店铺',
];
?>
<style>

.qtip.qtip-default.basic-qtip.nopending.qtip-pos-tc.qtip-focus{
	width:300px!important;
}
.pending_to_do td,{
}

.announces ul{
	border: 1px solid;
}

.announces ul li{
	list-style: none;
    border-bottom: 1px solid #ccc;
    line-height: 28px;
	width:100%;
}

.announces ul li a{
	width:100%;
	display: inline-block;
}
.pending_list{
	background-color:#fff;
	border: 1px solid;
	position:fixed;
	display: none;
	top: 45px;
	right: 50px;
	z-index: 999;
	border-radius:5px;
}
</style>
<table class="pending_to_do col-xs-12" style="width:100%;float:left;clear:both;margin:5px 0px;">
<tr>
	<td class="col-xs-4" style="pending:5px;">
	<div class="pending_order" style="border:1px solid;/*margin:5px;*/width:100%;float:left;height:140px">
		<h5 class="preview">订单待处理<a class="glyphicon glyphicon-refresh" style="float:right" title="刷新数据" onclick="DashBoard.refreshPendingOrderNum()"></a></h5>
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$pending_paied_urls = DashBoardHelper::$PLATFORM_PAIED_ORDER_URL;
				$total_pending_paied = 0;
				$pending_paied_list = '';
				if(!empty($pendingOrders)){
					foreach ($pendingOrders as $platform=>$pendings){
						if(!empty($pendings['pending_paied'])){
							$total_pending_paied += $pendings['pending_paied'];
							$pending_paied_list .=empty($pending_paied_list)?'':'<br>';
							$url=empty($pending_paied_urls[strtoupper($platform)])?'':$pending_paied_urls[strtoupper($platform)];
							$pending_paied_list .= 
    							'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.(empty($platfromChNameMapping[$platform])?$platform:$platfromChNameMapping[$platform]).'订单: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;">'.$pendings['pending_paied'].'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;margin-bottom:8px;"><?=$total_pending_paied?></span>
				<?php if(empty($pending_paied_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">已付款</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="order_paied" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">已付款</a>
				<?php } ?>
			</div>
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$pending_to_ship_urls = DashBoardHelper::$PLATFORM_PENDING_TO_SHIP_ORDER_URL;
				$total_pending_to_ship = 0;
				$pending_to_ship_list = '';
				if(!empty($pendingOrders)){
					foreach ($pendingOrders as $platform=>$pendings){
						if(!empty($pendings['pending_to_ship'])){
							$total_pending_to_ship += $pendings['pending_to_ship'];
							$pending_to_ship_list .=empty($pending_to_ship_list)?'':'<br>';
							$url=empty($pending_to_ship_urls[strtoupper($platform)])?'':$pending_to_ship_urls[strtoupper($platform)];
							$pending_to_ship_list .=
								'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.(empty($platfromChNameMapping[$platform])?$platform:$platfromChNameMapping[$platform]).'订单: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;">'.$pendings['pending_to_ship'].'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;margin-bottom:8px;"><?=$total_pending_to_ship?></span>
				<?php if(empty($pending_to_ship_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">发货中</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="order_pending_to_ship" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">发货中</a>
				<?php } ?>
			</div>
		</div>
		
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$shipment_suspend_urls = DashBoardHelper::$PLATFORM_SHIPMENT_SUSPEND_ORDER_URL;
				$total_shipment_suspend = 0;
				$shipment_suspend_list = '';
				if(!empty($pendingOrders)){
					foreach ($pendingOrders as $platform=>$pendings){
						if(!empty($pendings['shipment_suspend'])){
							$total_shipment_suspend += $pendings['shipment_suspend'];
							$shipment_suspend_list .=empty($shipment_suspend_list)?'':'<br>';
							$url=empty($shipment_suspend_urls[strtoupper($platform)])?'':$shipment_suspend_urls[strtoupper($platform)];
							$shipment_suspend_list .= 
								'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.(empty($platfromChNameMapping[$platform])?$platform:$platfromChNameMapping[$platform]).'订单: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;">'.$pendings['shipment_suspend'].'</span></div>
								</a>';
							
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;margin-bottom:8px;"><?=$total_shipment_suspend?></span>
				<?php if(empty($shipment_suspend_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">暂停发货</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="order_shipment_suspend" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">暂停发货</a>
				<?php } ?>
			</div>
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$pending_purchase_urls = DashBoardHelper::$PLATFORM_PENDING_PURCHASE_ORDER_URL;
				$total_pending_purchase = 0;
				$pending_purchase_list = '';
				if(!empty($pendingOrders)){
					foreach ($pendingOrders as $platform=>$pendings){
						if(!empty($pendings['pending_purchase'])){
							$total_pending_purchase += $pendings['pending_purchase'];
							$pending_purchase_list .=empty($pending_purchase_list)?'':'<br>';
							$url=empty($pending_purchase_urls[strtoupper($platform)])?'':$pending_purchase_urls[strtoupper($platform)];
							$pending_purchase_list .= 
								'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.(empty($platfromChNameMapping[$platform])?$platform:$platfromChNameMapping[$platform]).'订单: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;">'.$pendings['pending_purchase'].'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;margin-bottom:8px;"><?=$total_pending_purchase?></span>
				<?php if(empty($pending_purchase_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">缺货</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="order_pending_purchase" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">缺货</a>
				<?php } ?>
			</div>
		</div>
	</div>
	</td>
	
	<td class="col-xs-4 hidden" style="pending:5px;">
	<div class="message_pending" style="border:1px solid;/*margin:5px;*/width:100%;float:left;height:140px">
		<h5 class="preview">消息或投诉</h5>
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$total_unreadMessage = 0;
				$unreadMessage_list = '';
				$cs_urls = DashBoardHelper::$PLATFORM_CUSTOMER_MESSAGE_URL;
				if(!empty($messagePendings)){
					foreach ($messagePendings as $platform=>$message){
						if(!empty($message['unreadMessage'])){
							$total_unreadMessage += $message['unreadMessage'];
							$url=empty($cs_urls[strtoupper($platform)])?'':$cs_urls[strtoupper($platform)];
							$unreadMessage_list .=empty($pending_to_ship_list)?'':'<br>';
							$unreadMessage_list .= 
								'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.(empty($platfromChNameMapping[$platform])?$platform:$platfromChNameMapping[$platform]).'消息: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;">'.$message['unreadMessage'].'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;margin-bottom:8px;"><?=$total_unreadMessage?></span>
				<?php if(empty($unreadMessage_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">未读消息</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="unread_message" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">未读消息</a>
				<?php } ?>
			</div>
		</div>
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				
			
			
			</div>
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				
			
			
			</div>
		</div>
	</div>
	</td>
	
	<td class="col-xs-4" style="pending:5px;">
	<div class="exception" style="border:1px solid;/*margin:5px;*/width:100%;float:left;height:140px">
		<h5 class="preview">异常待处理</h5>
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$total_auth_err = 0;
				$auth_err_list = '';
				
				//判断是否子账号，子账号直接显示错误信息    20170615_lrq
				$isMainAccount = UserApiHelper::isMainAccount();
				if(!$isMainAccount){
					if(!empty($authErrorMsg)){
						$auth_err_list = '<div style="overflow-y: scroll; line-height: 17px; max-height:300px">';
						foreach ($authErrorMsg as $platform => $err){
							if(!empty($err)){
								$auth_err_list .= '<b style="font-size: 20px; color: red;">'.$platform.'</b><br>';
								if(!empty($err['title'])){
									$auth_err_list .= $err['title'].'<br>';
								}
								foreach ($err['msg'] as $msg){
									$auth_err_list .= '<span style="margin-left:20px;">'.$msg.'</span><br>';
									$total_auth_err++;
								}
							}
						}
						$auth_err_list .= '</div>';
					}
				}
				else{
					if(!empty($authErr)){
						foreach ($authErr as $platform=>$count){
							if(!empty($count)){
								$total_auth_err += $count;
								$auth_err_list .=empty($auth_err_list)?'':'<br>';
								// 绑定平台
								list($to_url,$label) = \eagle\modules\app\apihelpers\AppApiHelper::getPlatformMenuData();
								 
								$auth_err_list .= 
									'<a href="'.$to_url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
	    							<div style="width:70%;float:left"><span style="float:right">'.$platform.'账号授权异常: </span></div>
									<div style="width:30%;float:left"><span style="float:left;margin-left:15px;color:red;">'.$count.'</span></div>
									</a>';
							}
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;color:red;margin-bottom:8px;"><?=$total_auth_err?></span>
				<?php if(empty($auth_err_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">平台账号授权异常</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="auth_error" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">平台账号授权异常</a>
				<?php } ?>
			</div>
			
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				
				<?php 
				$pending_paied_urls = DashBoardHelper::$PLATFORM_SIGNSHIPPED_ERR_ORDER_URL;
				$total_signShippedErr = 0;
				$signShippedErr_list = '';
				if(!empty($signShippedErr)){
					foreach ($signShippedErr as $platform=>$count){
						if(!empty($count)){
							$total_signShippedErr += $count;
							$url=empty($pending_paied_urls[strtoupper($platform)])?'':$pending_paied_urls[strtoupper($platform)];
							$signShippedErr_list .=empty($signShippedErr_list)?'':'<br>';
							$signShippedErr_list .= 
								'<a href="'.$url.'" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:70%;float:left"><span style="float:right">'.$platform.'通知平台发货失败: </span></div>
								<div style="width:30%;float:left"><span style="float:left;margin-left:15px;color:red;">'.$count.'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;color:red;margin-bottom:8px;"><?=$total_signShippedErr?></span>
				<?php if(empty($signShippedErr_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">通知平台发货失败</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="sign_shipped_error" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">通知平台发货失败</a>
				<?php } ?>
				
			</div>
		</div>
		<div style="width:100%;float:left;clear:both;margin-top:10px">
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				<?php 
				$total_failMessage = 0;
				$failMessage_list = '';
				if(!empty($messagePendings)){
					foreach ($messagePendings as $platform=>$message){
						if(!empty($message['failMessage'])){
							$total_failMessage += $message['failMessage'];
							$failMessage_list .=empty($failMessage_list)?'':'<br>';
							$failMessage_list .= 
								'<a href="http://v2.littleboss.com/message/all-customer/customer-list" target="_blank" style="font-size:14px;line-height:1.5;width:100%">
    							<div style="width:50%;float:left"><span style="float:right">'.$platform.'消息: </span></div>
								<div style="width:50%;float:left"><span style="float:left;margin-left:15px;color:red;">'.$message['failMessage'].'</span></div>
								</a>';
						}
					}
				}
				?>
				<span style="width:100%;text-align:center;float:left;clear:both;font-size:16px;font-weight:600;color:red;margin-bottom:8px;"><?=$total_failMessage?></span>
				<?php if(empty($failMessage_list)){?>
				<span style="width:100%;text-align:center;float:left;clear:both;">消息发送失败</span>
				<?php }else{?>
				<a href="javascript:void(0);" data-qtipkey="fail_message" class ="click-to-tip" style="width:100%;text-align:center;float:left;clear:both;">消息发送失败</a>
				<?php } ?>
			
			
			</div>
			<div style="width:50%;text-align:center;float:left;font-size:14px;">
				
			
			</div>
		</div>
	</div>
	</td>
</tr>
</table>

<div id="order_paied" style="display: none">
<?=$pending_paied_list ?>
</div>
<div id="order_pending_to_ship" style="display: none">
<?=$pending_to_ship_list ?>
</div>
<div id="order_shipment_suspend" style="display: none">
<?=$shipment_suspend_list ?>
</div>
<div id="order_pending_purchase" style="display: none">
<?=$pending_purchase_list ?>
</div>
<div id="unread_message" style="display: none">
<?=$unreadMessage_list ?>
</div>
<div id="fail_message" style="display: none">
<?=$failMessage_list ?>
</div>
<div id="sign_shipped_error" style="display: none">
<?=$signShippedErr_list ?>
</div>
<div id="auth_error" style="display: none">
<?=$auth_err_list ?>
</div>

<script>

</script>