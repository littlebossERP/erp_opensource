<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/dashboard/dash_board.js?v=1.13',['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
 
$uid = \Yii::$app->user->id;

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
.preview{
	color:black;
    font-size: 16px;
    font-weight: bold;
    padding: 5px;
    background-color: #eee;
	border-bottom:1px solid;
}
.nav-tabs > li.active > a{
	background-color:#7cc3ff;
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
 
.divMakeImgBtnOutA {border: 1px solid #fff;display: inline-block;margin: 20px;}
.divMakeImgBtnOutA .divMakeBtnWish {position:relative;height:75px;width:250px;background:url(../images/platform_logo/wishBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnSmt {position:relative;height:75px;width:250px;background:url(../images/platform_logo/smtBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnEbay {position:relative;height:75px;width:250px;background:url(../images/platform_logo/ebayBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnAmazon {position:relative;height:75px;width:250px;background:url(../images/platform_logo/amazonBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnLazada {position:relative;height:75px;width:250px;background:url(../images/platform_logo/lazadaBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnDh {position:relative;height:75px;width:250px;background:url(../images/platform_logo/dhBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnCd {position:relative;height:75px;width:250px;background:url(../images/platform_logo/cdBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnLinio {position:relative;height:75px;width:250px;background:url(../images/platform_logo/linioBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnJumia {position:relative;height:75px;width:250px;background:url(../images/platform_logo/jumiaBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnPriceminister {position:relative;height:75px;width:250px;background:url(../images/platform_logo/priceministerBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnBonanza {position:relative;height:75px;width:250px;background:url(../images/platform_logo/bonanzaBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnRumall {position:relative;height:75px;width:250px;background:url(../images/platform_logo/rumallBindingLogo.jpg);background-repeat:repeat;}
.divMakeImgBtnOutA .divMakeBtnNewegg {position:relative;height:75px;width:250px;background:url(../images/platform_logo/neweggBindingLogo.jpg);background-repeat:repeat;}

.border3 {border: 1px solid #ccc;}

.divMakeImgBtnOutA a.shouquanDiv {cursor: pointer;display:block;height:100%;width:100%; background-color:#000;-moz-opacity:0.7; /* Moz + FF 透明度20%*/opacity: 0.7; /* 支持CSS3的浏览器（FF 1.5也支持）透明度20%*/}
.divMakeImgBtnOutA a.shouquanDiv:hover {-moz-opacity:0.6; /* Moz + FF 透明度20%*/opacity: 0.6; /* 支持CSS3的浏览器（FF 1.5也支持）透明度20%*/}
.divMakeImgBtnOutA span.shouquanSpan {cursor: pointer;font-size: 14px;position: absolute;top: 26px;left: 96px;color: #fff;font-weight: 600;text-decoration: underline;}
.divMakeImgBtnOutA .helpLink {float: right;}
</style>
<div class="col2-layout col-xs-12">
	<div class="" style="/*max-width:1024px;margin:auto;text-align:center;*/">
		<div class="content-left hidden" style="padding:0px;float:left;width:265px;">
			<?=$this->render('_left',[]) ?>
		</div>
		
		<?php if(!empty($bingingPlatforms)){ ?>
	
		<div class="col-xs-11" style="padding:0px;float:left;margin-bottom:15px;">
			<div class="col-xs-12" style="width:100%;padding-left:10px;font-family:'Microsoft Yahei';">
				<?=$this->render('_pending',[
					'pendingOrders'=>$pendingOrders,
					'messagePendings'=>$messagePendings,
					'authErr'=>$authErr,
					'signShippedErr'=>$signShippedErr,
					'authErrorMsg'=>$authErrorMsg,
				]) ?>
			</div>
			
			<div class="col-xs-12" style="width:100%;font-family:'Microsoft Yahei';padding:10px 30px 5px 25px">
				<div class="col-xs-12" style="border:1px solid;padding: 0px;">
					<h5 class="preview select_bar" style="border-bottom:1px solid;width:100%;display:inline-block;">
						<span style="height:26px;line-height:26px;">业绩统计</span>
						<a><span class="glyphicon glyphicon-refresh" title="觉得数据不准确？可以尝试点击刷新，来立即统计一下近14日的数据" onclick="DashBoard.refreshSalesCount()" style="margin-left:10px;"></span></a>
						<div style="float:right">
						<span style="height:26px;line-height:28px;">统计周期:</span>
						<?php $periodic_arr = ['daily'=>'按日','weekly'=>'按周','monthly'=>'按月'];?>
						<?=Html::dropDownList('periodic',@$_REQUEST['periodic'],$periodic_arr,['class'=>'iv-input','style'=>'width:50px;margin:0px;','onchange'=>"periodic_change()"])?>
						</div>
					</h5>
					<div style="width:100%;">
						<input type="hidden" id="select_platform" name="select_platform" value="<?=empty($_REQUEST['select_platform'])?'all':$_REQUEST['select_platform'] ?>">
						<input type="hidden" id="periodic" name="periodic" value="<?=empty($_REQUEST['periodic'])?'daily':$_REQUEST['periodic'] ?>">
						<input type="hidden" id="order_type" name="order_type" value="<?=empty($_REQUEST['order_type'])?'':$_REQUEST['order_type'] ?>">
						<div style="width:100%;float:left;">
							<ul class="nav nav-tabs" role="tablist" style="width:100%;float:left;padding-right:100px">
							<?php if(empty($bingingPlatforms)){?>
								<li role="presentation" class="active" data-platform="all">
								<a class="" href="javascript:void(0);">
								全部
								</a>
								</li>
							<?php }else{ ?>
								<li role="presentation" class="<?=(empty($_REQUEST['select_platform']) || $_REQUEST['select_platform']=='all')?'active':'' ?>" data-platform="all">
									<a class="" href="javascript:void(0);" 
									 onclick="platform_change('all')">全部</a>
								</li>
							
								
							<?php foreach ($bingingPlatforms as $platform){?>
								<li role="presentation" class="<?=(!empty($_REQUEST['select_platform']) && $_REQUEST['select_platform']==$platform)?'active':'' ?>" data-platform="<?=$platform;?>">
									<a class="" href="javascript:void(0);" 
									 onclick="platform_change('<?=$platform ?>')">
										<?=isset($platfromChNameMapping[$platform])?$platfromChNameMapping[$platform]:$platform ?>
									</a>
								</li>
							<?php } } ?>
							</ul>
						</div>
						 <div style="width:100%;float:left;">
							<main class="main-view" style="float:left;width:100%;">
								<?=$this->render('_chart',[
										'chartData'=>$chartData,
										'platform_sellers'=>empty($platform_sellers)?[]:$platform_sellers,
									]) ?>
							</main>
						</div>
					</div>
				</div>
			</div>
			
		</div>
		<?php }else if(!UserApiHelper::isMainAccount()){
			//当是子账号时，不显示授权信息，lrq20180305
		?>
		<div class="col-xs-9" style="padding:0px;float:left;margin-bottom:15px;">
			<div class="bind_tip alert alert-warning" style="margin: 5px 0 0 5px;">
				<span style="background-color:#f66;padding-left:5px;color:#fff;">特别提醒！</span><span> 授权店铺即可激活使用，多店铺防关联！</span>
			</div>
			<!-- 通知/公告 end -->
			<div class="col-xs-12" style="width:100%;padding-left:10px;font-family:'Microsoft Yahei';">
				<?=$this->render('_pending',[
					'pendingOrders'=>$pendingOrders,
					'messagePendings'=>$messagePendings,
					'authErr'=>$authErr,
					'signShippedErr'=>$signShippedErr,
					'authErrorMsg'=>$authErrorMsg,
				]) ?>
			</div>
		</div>
		<?php }else{
// 			$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
// 			$this->registerJs("DashBoard.initClickTip();" , \yii\web\View::POS_READY);
			?>
		<div class="col-xs-9" style="padding:0px;float:left;margin-bottom:15px;">
			<div class="bind_tip alert alert-warning" style="margin: 5px 0 0 5px;">
				<span style="background-color:#f66;padding-left:5px;color:#fff;">特别提醒！</span><span> 授权店铺即可激活使用，多店铺防关联！</span>
			</div>
			
			<div class="col-xs-12" style="margin-bottom: 20px;">
				<div class="col-xs-12">
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnAmazon">
							<a class="shouquanDiv" onclick="shouquanDiv('amazon')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('amazon')">添加授权</span>
						</div>
						<a class="helpLink" href="http://help.littleboss.com/word_list_247_516.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnSmt">
							<a class="shouquanDiv" onclick="shouquanDiv('aliexpress')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('aliexpress')">添加授权</span>
						</div>
						<a class="helpLink" href="http://help.littleboss.com/word_list_247_274.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnEbay">
							<a class="shouquanDiv" onclick="shouquanDiv('ebay')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('ebay')">添加授权</span>
						</div>
						<a class="helpLink" href="http://help.littleboss.com/word_list_247_272.html" target="_blank">查看授权帮助</a>
					</div>
				
					<br>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnCd">
							<a class="shouquanDiv" onclick="shouquanDiv('cdiscount')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('cdiscount')">添加授权</span>
						</div>
						<a class="helpLink" href="http://www.littleboss.com/word_list_247_279.html" target="_blank">查看授权帮助</a>
					</div>
				
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnWish">
							<a class="shouquanDiv" onclick="shouquanDiv('wish')" ></a>
							<span class="shouquanSpan" onclick="shouquanDiv('wish')" >添加授权</span>
						</div>
						<a class="helpLink" href="http://help.littleboss.com/word_list_247_276.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnLazada">
							<a class="shouquanDiv" onclick="shouquanDiv('lazada')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('lazada')">添加授权</span>
						</div>
						<a class="helpLink" href="http://www.littleboss.com/word_list_247_273.html" target="_blank">查看授权帮助</a>
					</div>
					
					<br>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnDh">
							<a class="shouquanDiv" onclick="shouquanDiv('dhgate')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('dhgate')">添加授权</span>
						</div>
						<a class="helpLink" href="http://www.littleboss.com/word_list_247_280.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnLinio">
							<a class="shouquanDiv" onclick="shouquanDiv('linio')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('linio')">添加授权</span>
						</div>
						<a class="helpLink" href="http://auth.littleboss.com/docs/linioHelper.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnJumia">
							<a class="shouquanDiv" onclick="shouquanDiv('jumia')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('jumia')">添加授权</span>
						</div>
						<a class="helpLink" href="http://auth.littleboss.com/docs/jumiaHelper.html" target="_blank">查看授权帮助</a>
					</div>
					
					<br>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnPriceminister">
							<a class="shouquanDiv" onclick="shouquanDiv('priceminister')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('priceminister')">添加授权</span>
						</div>
						<a class="helpLink" href="http://help.littleboss.com/faq_57.html" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnBonanza">
							<a class="shouquanDiv" onclick="shouquanDiv('bonanza')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('bonanza')">添加授权</span>
						</div>
						<a class="helpLink" href="" target="_blank">查看授权帮助</a>
					</div>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnRumall">
							<a class="shouquanDiv" onclick="shouquanDiv('rumall')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('rumall')">添加授权</span>
						</div>
						<a class="helpLink" href="" target="_blank">查看授权帮助</a>
					</div>
					
					<br>
					
					<div class="text-center divMakeImgBtnOutA">
						<div class="border3 divMakeBtnNewegg">
							<a class="shouquanDiv" onclick="shouquanDiv('newegg')"></a>
							<span class="shouquanSpan" onclick="shouquanDiv('newegg')">添加授权</span>
						</div>
						<a class="helpLink" href="" target="_blank">查看授权帮助</a>
					</div>
			
				</div>
			</div>
		</div>
			<?php
		} ?>
		
		<div class="col-xs-1"></div>
	</div>
</div>

<script>
function periodic_change(){
	var periodic = $("select[name='periodic']").val();
	$("input[name='periodic']").val(periodic);
	var select_platform = $("input[name='select_platform']").val();
	var order_type = $("input[name='order_type']").val();
	var Url=global.baseUrl +'dash_board/dash-board/ajax-chart-data?select_platform='+select_platform+'&periodic='+periodic+'&order_type='+order_type;
	$.location.state(Url,'小老板',[],0,'post',false);
}

function platform_change(platform){
	var periodic = $("select[name='periodic']").val();
	var select_platform = $("input[name='select_platform']").val(platform);
	var order_type = '';
	$("input[name='order_type']").val('');
	$(".nav-tabs li").each(function(){
		var p = $(this).data('platform');
		//console.log(p);
		if(p!==platform)
			$(this).removeClass("active");
	});
	var Url=global.baseUrl +'dash_board/dash-board/ajax-chart-data?select_platform='+platform+'&periodic='+periodic+'&order_type='+order_type;
	$.location.state(Url,'小老板',[],0,'post',false);
}

function orderType_change(){
	var order_type = $("select[name='order_type']").val();
	$("input[name='order_type']").val(order_type);
	var periodic = $("select[name='periodic']").val();
	var select_platform = $("input[name='select_platform']").val();
	var Url=global.baseUrl +'dash_board/dash-board/ajax-chart-data?select_platform='+select_platform+'&periodic='+periodic+'&order_type='+order_type;
	$.location.state(Url,'小老板',[],0,'post',false);
}

function shouquanDiv(name){
	<?php
	list($url,$label) = AppApiHelper::getPlatformMenuData();
	?>
	
	pf_binding_url = '<?php echo $url; ?>';
	
	window.open(pf_binding_url+'?platform='+name);
}
</script>

