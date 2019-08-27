<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\permission\helpers\UserHelper;

$uid = \Yii::$app->user->id;
$profix_permission = UserApiHelper::checkOtherPermission('profix',$uid);
$maxSeriesShow = 2;
$this->registerJs("DashBoard.maxSeriesShow=$maxSeriesShow;" , \yii\web\View::POS_READY);

$this->registerJs("DashBoard.orderCountChartData=".json_encode($chartData).";" , \yii\web\View::POS_READY);
//$this->registerJs("DashBoard.ProfitChartData=".json_encode($chartData).";" , \yii\web\View::POS_READY);
$this->registerJs("DashBoard.initOrderCountChart(".((empty($_REQUEST['select_platform']) || $_REQUEST['select_platform']=='all')?'true':'false').");" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
//$this->registerJs("SelectLegend();" , \yii\web\View::POS_READY);

?>

<?php 
$platformLogoUrl = [
'ebay'=>'/images/platform_logo/ebay.png',
'amazon'=>'/images/platform_logo/amazon.png',
'aliexpress'=>'/images/platform_logo/aliexpress.png',
'wish'=>'/images/platform_logo/wish.png',
'dhgate'=>'/images/platform_logo/dhgate.png',
'cdiscount'=>'/images/platform_logo/cdiscount.png',
'lazada'=>'/images/platform_logo/lazada.jpg',
'linio'=>'/images/platform_logo/linio.png',
'jumia'=>'/images/platform_logo/jumia.png',
'priceminister'=>'/images/platform_logo/priceminister.png',
'bonanza'=>'/images/platform_logo/bonanza.png',
'rumall'=>'/images/platform_logo/rumall.png',

];

$select_platform = (empty($_REQUEST['select_platform']) || $_REQUEST['select_platform']=='all')?'':$_REQUEST['select_platform'];
$PlatformOrderTypeList = DashBoardStatisticHelper::getPlatformOrderTypeList([$select_platform]);
$divHtml='';
if(!empty($PlatformOrderTypeList[$select_platform])){
	$divHtml.='<div style="float:right" class="select_order_type"><span style="height:26px;line-height:28px;">订单类型：</span>';
	$tmpSelectHtml = Html::dropDownList('order_type',@$_REQUEST['order_type'],$PlatformOrderTypeList[$select_platform],['class'=>'iv-input','style'=>'width:150px;margin:0px;','prompt'=>'所有类型','onchange'=>"orderType_change()"]);
	$tmpSelectHtml = str_replace( chr(10), '', $tmpSelectHtml);
	$divHtml.=$tmpSelectHtml;
	$divHtml.='</div>';
}
$this->registerJs('$(".select_order_type").remove();' , \yii\web\View::POS_READY);
$this->registerJs('$(".select_bar").append(\''.$divHtml.'\');' , \yii\web\View::POS_READY);
?>
<style>
.nav a.active{
	background-color: #83CBFF;
}
.nav > li > a:hover, .nav > li > a:focus {
    background-color: #83CBFF!important;
}
.highcharts-container{
	width: auto!important;
}
.platform-logo{
	max-width:150px;
	max-height:40px;
}
.select_order_type select{
	padding:0px!important;
	margin-top: -2px!important;
}
</style>
<?php 
//if(!empty($_REQUEST['select_platform']))
//	$platform_account_permission = UserHelper::getUserAuthorizePlatformAccounts($_REQUEST['select_platform'],$uid);

if($profix_permission){
?>
<div style="line-height:28px;margin-right:10px;position:absolute;right:0px;top:44px;font-size:16px;font-weight:600;color:black;">
	显示利润<input type="checkbox" id="show_profit" value="show_profit">
</div>
<?php } ?>
<div class="dash-board" style="width:100%;float:left;">

	<div style="width:100%;display:inline-block;padding:10px;padding-bottom:30px;">
		<div style="position: relative;">
			<div class="chart_title" style="width:100%;display:table;text-align:center;vertical-align:middle;">
				<?php $select_platform=empty($_REQUEST['select_platform'])?'':$_REQUEST['select_platform'];
					if(isset($platformLogoUrl[$select_platform]))
						$logoUrl = $platformLogoUrl[$select_platform];
					else 
						$logoUrl='';
				?>
				<?php if(!empty($logoUrl)){?>
				<span style="vertical-align:middle;display:inline-block;"><img src="<?=$logoUrl?>" class="platform-logo" 
					style="
					<?=($select_platform=='amazon')?'background-color:black;':'' ?>
					<?=($select_platform=='wish')?'background-color:#4680A6;padding:5px;':'' ?>
					">
				</span>
				<?php }else{?>
				<span style="vertical-align:middle;font-weight:600;font-size:16px;"><?=(empty($_REQUEST['select_platform']) || $_REQUEST['select_platform']=='all')?'所有':$_REQUEST['select_platform'] ?>平台</span>
				<?php }?>
				<span style="margin-left:5px;font-weight:600;font-size:16px;vertical-align:middle"><?=empty($chartData['title'])?'':$chartData['title']?></span>
			</div>
			<?php if(count($platform_sellers)>1){?>
			<div class="legend" style="margin:5px 5px;position:absolute;right:0px;top:0px;">
				当前显示账号：
				<select class="">
					<option value='all' data-index='0'>所有账号合计</option>
			
					<?php $index=1;foreach ($platform_sellers as $k=>$v){ ?>
					<?php //if(!empty($platform_account_permission) && in_array($k,$platform_account_permission)){ ?>
					<option value='<?=$k?>' data-index="<?=$index?>"><?=$v?></option>
					<?php $index++;?>
					<?php //} ?>
					<?php } ?>	
				</select>
			</div>
			<?php } ?>
		</div>
	</div>
	<div style="width:100%;left;clear:both;">
		<div id="chart" class="" style="/*width:100%;float:left;padding:5px;*/"></div>
	</div>
</div>

<script>

</script>