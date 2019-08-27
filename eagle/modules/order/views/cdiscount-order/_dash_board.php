<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\app\apihelpers\AppApiHelper;

//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'/js/project/order/OrderDashBoard.js',['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("OmsDashBoard.orderCountChartData=".json_encode($chartData['order_count']).";" , \yii\web\View::POS_READY);
$this->registerJs("OmsDashBoard.ProfitChartData=".json_encode($chartData['profit_count']).";" , \yii\web\View::POS_READY);
$this->registerJs("OmsDashBoard.initOrderCountChart(false);" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<?php 
$uid = \Yii::$app->user->id;
$platformUrlData = AppApiHelper::getPlatformMenuData();
$platformUr = empty($platformUrlData[0])?'':$platformUrlData[0];
?>
<style>
.dash-board .modal-dialog{
	min-width:860px;
	max-width:80%;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
.dash-board .modal-body{
	padding: 0px 15px 15px 15px;
}
.nav a.active{
	background-color: #83CBFF;
}
.nav > li > a:hover, .nav > li > a:focus {
    background-color: #83CBFF;!important
}
</style>
<div style="width: 835px;">
	<table style="width:100%">
		<tr>
		<!-- 统计与错误提示列 -->
			<td width="<?=empty($advertData)?'100%':'75%' ?>" style="float:left;">
				<ul class="nav nav-tabs">
				  <li role="presentation" class=""><a class="active" href="javascript:void(0)" onclick="OmsDashBoard.initOrderCountChart(this)">订单数量统计</a></li>
				  <li role="presentation" class=""><a class="" href="javascript:void(0)" onclick="OmsDashBoard.initOrderProfitChart(this)">订单利润统计</a></li>
				</ul>
				<!-- 订单统计视图 -->
				<div style="width:100%;display:block;">
					<div id="chart" style="min-width:600px;max-width:100%;min-height:400px;margin-bottom:40px"></div>
				</div>
				<!-- 操作提示 -->
				<div>
				<?php 
				$reminder=[];
				if(!empty($uid)){
					$ret=CdiscountOrderInterface::getCdiscountReminder($uid);
					if($ret['success'] && !empty($ret['remind']))
						$reminder = $ret['remind'];
				}
				if(!empty($reminder)){
				?>
					<div id="cd-oms-reminder">
						<div id="cd-oms-reminder-content" class="bg-warning">
							<div style="float:left;width:100%;clear:both;">
								<?php if(!empty($reminder['YiFuKuan'])): ?>
								<span style="width:100%;"><a href="<?=Url::to(['/order/cdiscount-order/list','weird_status'=>'wfs'])?>" target="_blank">您有 <b style="font-weight:600;"><?=$reminder['YiFuKuan'] ?> </b>单已付款订单超过2天未处理</a></span><span qtipkey="oms_order_status_prolonged_paid"></span><br>
								<?php endif;
									  if(!empty($reminder['FaHuoZhong'])):?>
								<span style="width:100%;"><a href="<?=Url::to(['/order/cdiscount-order/list','weird_status'=>'wfd'])?>" target="_blank">您有 <b style="font-weight:600;"><?=$reminder['FaHuoZhong'] ?> </b>单发货中订单超过2天未处理</a></span><span qtipkey="oms_order_status_prolonged_in delivery"></span><br>
								<?php endif;
									  if(!empty($reminder['YiFaHuo'])):?>
								<span style="width:100%;"><a href="<?=Url::to(['/order/cdiscount-order/list','weird_status'=>'wfss'])?>" target="_blank">您有 <b style="font-weight:600;"><?=$reminder['YiFaHuo'] ?> </b>单已发货单超过2天未处理</a></span><span qtipkey="oms_order_status_prolonged_shipped"></span><br>
								<?php endif;
									  if(!empty($reminder['ZhuangTaiYiChang'])):?>
								<span style="width:100%;"><a href="<?=Url::to(['/order/cdiscount-order/list','weird_status'=>'sus'])?>" target="_blank">您有 <b style="font-weight:600;"><?=$reminder['ZhuangTaiYiChang'] ?> </b>单订单小老板状态与CD后台状态不匹配。</a></span><span qtipkey="cd_order_weird_status_sus"></span><br>
								<?php endif;
									  if(!empty($reminder['WuTianWeiShangWang'])):?>
								<span style="width:100%;"><a href="<?=Url::to(['/order/cdiscount-order/list','weird_status'=>'tuol'])?>" target="_blank">您有 <b style="font-weight:600;"><?=$reminder['WuTianWeiShangWang'] ?> </b>单订单超过五天物流仍未上网。</a></span><span qtipkey="cd_order_weird_status_tuol"></span><br>
								<?php endif;?>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
				<!-- 错误提示 -->
				<div style="font-size: 12px;">
					<?php 
					$AccountProblems = CdiscountOrderInterface::getUserAccountProblems($uid);
					if(!empty($AccountProblems['initial_failed'])){
						foreach ($AccountProblems['initial_failed'] as $initial_failed_account){
					?>
					<span style="width:100%;float:left;clear:both;">您绑定的Cdiscount账号：<?=$initial_failed_account['store_name'] ?> 初次绑定时获取一个月前订单失败，请联系技术人员查询原因</span>
					<?php }
					}
					if(!empty($AccountProblems['token_expired'])){
						foreach ($AccountProblems['token_expired'] as $token_expired){
					?>
					<span style="width:100%;float:left;clear:both;">
						您绑定的Cdiscount账号：<?=$token_expired['store_name'] ?> 授权已经过期！&#8594;
						<a href="<?=$platformUr ?>" target="_blank">编辑绑定设置</a>
					</span>
					<?php }
					}
					if(!empty($AccountProblems['order_retrieve_failed'])){
						foreach ($AccountProblems['order_retrieve_failed'] as $retrieve_failed){
					?>
					<span style="width:100%;float:left;clear:both;">您绑定的Cdiscount账号：<?=$retrieve_failed['store_name'] ?> 获取最近订单失败：<?=$retrieve_failed['order_retrieve_message'] ?>，请联系技术人员查询原因</span>
					<?php }
					}
					if(!empty($AccountProblems['unActive'])){
						foreach ($AccountProblems['unActive'] as $unActive){
					?>
					<span style="width:100%;float:left;clear:both;">
						您绑定的Cdiscount账号：<?=$unActive['store_name'] ?> 未开启同步！&#8594;
						<a href="<?=$platformUr ?>" target="_blank">编辑绑定设置</a>
					</span>
					<?php }
					}
					?>
				</div>
			</td>
		<!-- 推荐/新功能列 -->
			<td width="<?=empty($advertData)?'0px':'25%' ?>" style="margin-top: 10px;float:right;display:<?=empty($advertData)?'none':'block' ?>">
				<div style="width:100%;overflow-y: auto;" id="Cdiscount_oms_adver">
					<table class="table" style="table-layout:fixed;">
						<?php if(!empty($advertData)):?>
						<?php foreach ($advertData as $advert):?>
						<tr><th width"100%" style="font-size: 14px;text-align:center;font-weight:600;color:blue;padding:0px;white-space:normal;"><?=$advert['title']?>:</th></tr>
						<tr>
							<td width"100%" style="padding:0px">
								<div style="width:100%;text-align:left;display:inline-block;"><?=$advert['content']?></div>
								<?php if(!empty($advert['url'])):?><a href="<?=$advert['url']?>" target="_blank" style="font-weight:600;font-size: 14px;">&gt;&gt;点击查看详情&lt;&lt;</a><?php endif;?>
							</td>
						</tr>
						<tr><td width"100%" style="padding:0px"></td></tr>
						<?php endforeach; ?>
						<?php endif; ?>
					</table>
				</div>
			</td>
		</tr>
	
	</table>
	<div style="width:100%;margin-top:20px;display:inline-block;clear:both;text-align:center">
		<button class="btn btn-primary" onclick="hideDashBoard()">
			收起
		</button>
	</div>
</div>
<script>
function hideDashBoard(){
	$("#dash-board-enter").toggle();
	var dash_board_top = $("#dash-board-enter").offset().top;
	var  dash_board_height= $("#dash-board-enter").height();
	if(typeof(dash_board_height)=='undefined')
		dash_board_height = 0;
	if(typeof(dash_board_top)=='undefined')
		var dash_board_top = 800;
	else 
		top = dash_board_top + dash_board_height/2;
	$(".bootbox.modal").animate(
		{
		   left:0,
		   top:dash_board_top,
		   height:'0px',
		   width:'0px',
	  	},
	 	800,
	 	function(){
	  		$('.dash-board').modal('hide');
			$('.dash-board').on('hidden.bs.modal', '.modal', function(event) {
				$(this).removeData('bs.modal');
			});
			$.post('/order/cdiscount-order/hide-dash-board',{},function(){return true;});
		}
	);
}
</script>