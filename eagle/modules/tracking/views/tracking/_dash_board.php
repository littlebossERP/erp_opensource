<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/tracking/tracking_dash_board.js',['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("TrackingDashBoard.chartData=".json_encode($chartData).";" , \yii\web\View::POS_READY);
$this->registerJs("TrackingDashBoard.initChart();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<?php 
$uid = \Yii::$app->user->id;
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
#tracker-reminder-content{
	left: 0px;
	padding: 5px;
    border: 2px solid transparent;
    border-radius: 5px;
	float: left;
    width: 100%;
	padding-bottom: 10px;
}
</style>
<div style="width: 835px;">
	<table style="width:100%">
		<tr>
		<!-- 统计与错误提示列 -->
			<td width="<?=empty($advertData)?'100%':'75%' ?>" style="float:left;">
				<!-- 订单统计视图 -->
				<div style="width:100%;display:block;">
					<div id="chart" style="min-width:600px;max-width:100%;min-height:400px"></div>
				</div>
				<!-- 操作提示 -->
				<div>
				<?php 
				$reminder=[];
				/*if(!empty($uid)){
					$ret=[];//@todo
					if($ret['success'] && !empty($ret['remind']))
						$reminder = $ret['remind'];
				}*/
				if(!empty($reminder)){
				?>
					<div id="tracker-reminder">
						<div id="tracker-reminder-content" class="bg-warning">
							<div style="float:left;width:100%;clear:both;">
							<?php //@todo //错误、提示?>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
				<!-- 错误提示 -->
				<div style="font-size: 12px;">
					<?php //@todo // ?>
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
			$.post('/tracking/tracking/hide-dash-board',{},function(){return true;});
		}
	);
}
</script>