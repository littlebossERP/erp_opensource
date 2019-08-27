<?php 
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\tracking\helpers\TrackingHelper;
use frontend\assets\AppAsset;


$baseUrl = \Yii::$app->urlManager->baseUrl.'/';
AppAsset::register($this);

$this->registerCssFile($baseUrl."css/site.css");
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
//$this->registerCssFile($baseUrl."css/bootstrap.css");
$this->registerJsFile($baseUrl."js/project/message/tracking/trackingInfo_and_recommendProduct.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("message.recommend.puid=".$puid.";" , \yii\web\View::POS_READY);
//$this->registerJs("message.recommend.recommendPageSize=".$recom_prod_count.";" , \yii\web\View::POS_READY);
if(count($recommendProduct)>0){
	foreach ($recommendProduct as $i=>$prod){
		$this->registerJs("message.recommend.recommendProducts.push(".$prod['id'].");" , \yii\web\View::POS_READY);
	}
}


if($isReady){
	$this->registerJs("message.preload.allReadyGetRecommend=true;" , \yii\web\View::POS_READY);
	$this->registerJs("message.recommend.init();" , \yii\web\View::POS_READY);
	$this->registerJs("$(function() {
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	  	ga('create', 'UA-51696898-2', 'auto');
	  	ga('send', 'pageview');
	});" , \yii\web\View::POS_READY);
}else{
	$this->registerJs("message.preload.allReadyGetRecommend=false;" , \yii\web\View::POS_READY);
	$this->registerJs("var ajaxbg = $('#background,#progressBar');ajaxbg.show();" , \yii\web\View::POS_READY);
	$this->registerJs("message.preload.init();" , \yii\web\View::POS_READY);
}

$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();
$carrier_type = empty($trackingInfo->carrier_type)?0:$trackingInfo->carrier_type;
if(!in_array($carrier_type,$non17Track) && !empty($trackingInfo->track_no)){
	$this->registerJs("doTrack('".$trackingInfo->track_no."');" , \yii\web\View::POS_READY);
}
?>
<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<style>
.Content{
	text-align: center;
	float: left;
	width: 100%;
}
.TrackingInfo{

}
.TrackingInfo.layout1{
	width:840px;
	margin: auto;
	/*padding: 0px 10px;*/
}
.TrackingInfo.layout2{
	width:650px;
	float:left;
	margin: auto;
}
.TrackingInfo.layout3{
	width:840px;
	margin: auto;
}

.Recommend{
	margin: auto;
	background-color:rgb(247,247,247);
}
.Recommend.layout1{
	width:840px;
	/*height:180px;*/
	margin:auto;
}
.Recommend.layout2{
	width:180px;
	/*height:840px;*/
	float:right;
}
.Recommend.layout3{
	width:840px;
	/*height:180px;*/
	margin:auto;
}

.hidden{
	display:none;
}
.prev_recommends.horizontal {
	background-image: url("/images/step-prev.png");
	/*transform: rotateX(180deg);*/
	background-size: 70px;
	background-repeat: no-repeat;
	width:180px;
	height:30px;
	float:left;
	clear:left;
	background-position:50px 0px;
	cursor: pointer;
}
.next_recommends.horizontal {
	background-image: url("/images/step-prev.png");
	-moz-transform:scaleY(-1);
	-webkit-transform:scaleY(-1);
	-o-transform:scaleY(-1);
	transform:scaleY(-1);
	filter:FlipH();
	
	/*transform: rotateX(180deg);*/
	background-size: 70px;
	background-repeat: no-repeat;
	width:180px;
	height:30px;
	float:left;
	clear:left;
	background-position:50px 0px;
	cursor: pointer;
}
.prev_recommends.vertical{
	background-image: url("/images/step-prev-v.png");
	/*transform: rotateX(180deg);*/
	background-size: 15px;
	background-repeat: no-repeat;
	width:30px;
	height:180px;
	float:left;
	clear:left;
	background-position:0px 75px;
	cursor: pointer;
}
.next_recommends.vertical{
	background-image: url("/images/step-prev-v.png");
	-moz-transform:scaleX(-1);
	-webkit-transform:scaleX(-1);
	-o-transform:scaleX(-1);
	transform:scaleX(-1);
	filter:FlipV();
	
	/*transform: rotateY(180deg);*/
	background-size: 15px;
	background-repeat: no-repeat;
	width:30px;
	height:180px;
	float:left;
	clear:right;
	background-position:0px 75px;
	cursor: pointer;
}
.tracking_info_table td.buttomStep{
	background-image: url("/images/step-vertical-01.png");
	background-position: 0px 52%;
	/*transform: rotateX(180deg);*/
	background-size: 15px;
	background-repeat: no-repeat;
}
.tracking_info_table td.midStep{
	background-image: url("/images/step-vertical-02.png");
	background-position: 0px 50%;
	/*transform: rotateX(180deg);*/
	background-size: 15px;
	background-repeat: no-repeat;
}
.tracking_info_table td i{
	width: 15px;
	height: 15px;
	float: left;
}
.recommend_a{
	/*width:160px;*/
	/*height:160px;*/
	/*background-color: rgb(234, 234, 234);*/
}

.div_more_tracking_info dl {
	max-height: 700px;
}
.toNation h6,.fromNation h6{
	float:left;
}
.div_more_tracking_info dl dd div p{
	text-align:left;
	padding-left: 10px;
}
h4,h5,h6,span,time,p{
	font-family: Arial;
}
h4{
	background-color: rgb(2,198,240);
    padding: 10px;
}

.toNation , .fromNation{
	font-family: Arial;
}

.toNationIcon{
	position: absolute;
    left: -24px;
    top: 0;
    display: block;
    width: 48px;
    height: 52px;
    background-color: #f8f8f8;
    background-image: url(/images/tracking/inquirydisplayicon.png);
    background-repeat: no-repeat;
    background-position: -314px -108px;
    overflow: hidden;
}

.toNation dl{
	padding: 10px 0px;
    border-bottom: 1px dotted #cbcbcb;
	margin-bottom: 0px;
}
.toNation dt{
	float: left;
    height: 30px;
    padding-left: 58px;
    font-size: 14px;
    font-weight: bold;
    line-height: 30px;
    background-image: url(/images/tracking/inquirydisplayicon.png);
	background-position: -314px -108px;
    background-repeat: no-repeat;
}

.fromNation dl{
	padding: 0px 10px 10px 0px;
}
.fromNation dt{
	float: left;
    height: 30px;
    padding-left: 58px;
    font-size: 14px;
    font-weight: bold;
    line-height: 30px;
    background-image: url(/images/tracking/inquirydisplayicon.png);
	background-position: -314px -138px;
    background-repeat: no-repeat;
}
.all_events{
	text-align: left;
}
.all_events dd{
	background-image: url(/images/tracking/inquiremessage_dt_green.png);
    background-repeat: repeat-y;
    background-position: left 0;
	position: relative;
    min-height: 24px;
    padding: 8px 0 0 38px;
    margin-left: 20px;
	width: 90%;
}
.all_events dd.new i{
	position: absolute;
    left: -24px;
    top: 0;
    display: block;
    width: 48px;
    height: 48px;
    background-color: #f8f8f8;
    background-image: url(/images/tracking/inquirydisplayicon.png);
    background-repeat: no-repeat;
    background-position: -313px 12px;
    overflow: hidden;
}
.all_events dd.begin{
	background-repeat: no-repeat;
    background-position: left -18px;
}
.all_events dd.begin i{
	background-position: -345px -38px;
}
.all_events dd i{
	position: absolute;
    left: -9px;
    top: 20px;
    display: block;
    width: 16px;
    height: 16px;
    background-image: url(/images/tracking/inquirydisplayicon.png);
    background-repeat: no-repeat;
    background-position: -313px -38px;
    overflow: hidden;
}
.recommend_a p{
	margin:0px;
}
#progressBar{
	padding: 12px 10px 10px 50px !important;
	width: 300px !important;
	height: auto !important;
	margin-left: -150px !important;
	top :80% !important;
	font-size: 14px !important;
}
</style>
<div id="background" class="background" style="display: none;"></div>
<input type="hidden" id="isReady" value="<?=($isReady)?'true':'false' ?>">
<input type="hidden" id="platform" value="<?=isset($platform)?$platform:'' ?>">
<input type="hidden" id="seller_id" value="<?=isset($seller_id)?$seller_id:'' ?>">
<input type="hidden" id="layout" value="<?=isset($layout)?$layout:1 ?>">
<input type="hidden" id="recom_prod_group" value="<?=isset($recom_prod_group)?$recom_prod_group:0 ?>">
<input type="hidden" id="recom_prod_count" value="<?=isset($recom_prod_count)?$recom_prod_count:8 ?>">
<input type="hidden" id="site_id" value="<?=isset($site_id)?$site_id:'' ?>">
<textarea id="errorMsg" style="display:none;"><?=isset($errorMsg)?$errorMsg:'' ?></textarea>

<!--ban-->
<div class="banner" style="background-color: rgb(243,197,0);"></div>
<!--/ban-->

<!--mainContent-->
<div class="Content">
	<?php if(!isset($layout) or $layout==1){?>
	<div style="width: 840px;margin: auto;">
		<h4 style="float:left;padding:15px 0px;font-size:26px;font-weight:600;color:rgb(251,169,25);background-color:rgb(35,64,147);border-bottom:5px solid rgb(0,1,2);">
			<span style="width:155px;height:35px;float:left;background-image:url('/images/project/trackerhome/index/index/hz-002.jpg');background-position:0px 50%;margin-left:10px;"></span>
			<span style="width:675px;float:left;text-align:left;padding-left:130px;padding-top:3px;"><?=$translate_contents['The Parcel Information'];?></span>
		</h4>
		<div id="TrackingInfo" class="TrackingInfo layout<?=$layout ?>" style="">
		<?php
		if(count($trackingInfo)>0){
			
				$eventList = json_decode($trackingInfo->all_event,true);
				//$result = "<table class='tracking_info_table' style='float:left;padding:10px;border-spacing: 0px;'>";
				/*
				$result .=
					"<tr><th colspan=3>
						<div style='width:100%;text-align:center;clear:both;font-size:28px;'>
							<span style='padding-right:20px'>".$trackingInfo->track_no."</span>
							<span style='padding-left:20px;'>".Tracking::getEnglishStatus($trackingInfo->status)."</span>
						</div>";
				if(CarrierTypeOfTrackNumber::$expressCode[$trackingInfo->carrier_type])
					$result.= "<div style='width:100%;text-align:center;clear:both;font-size:24px;color:rgb(153,153,153);'><span>".CarrierTypeOfTrackNumber::$expressCode[$trackingInfo->carrier_type]."</span></div>";
				else
					$result.= "<div style='width:100%;text-align:right;clear:both;font-size:24px;color:rgb(153,153,153);'><span>Carrier '".$trackingInfo->carrier_type."' have no name mapping</span></div>";
				
				$result.= "<div style='width:100%;text-align:left;clear:both;font-size:18px'>This package is for the ".$trackingInfo->platform.'\'s order '.$trackingInfo->order_id.",which contains products:<br>";
				$item_html = '';
				if(!empty($orderItems) && count($orderItems)>0){
					foreach ($orderItems as $item){
						$item_html.="<p>".$item['product_name']."(".$item['sent_quantity']."*)<br>";
					}
				}
				$result.= $item_html."</th></tr>";
				*/
				//$result .="<tr><th colspan=3></th></tr>";
				
				
			if(!empty($orderItems) && count($orderItems)>0){
				
				$itemHtml="<div style='padding:0px 25px;'><table style='width:100%;float:left;margin-bottom:10px;'>";
				$itemHtml.="<tr><td colspan='3'><div style='padding:10px 0px;font-size:14px'><span style='color: #777;'>".$translate_contents['Order ID']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".$orderInfo['order_source_order_id']."</span>";
				$itemHtml.="<span style='color: #777;padding-left:50px;'>".$translate_contents['Order Date']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".(!empty($orderInfo['create_time'])?date('Y-m-d',$orderInfo['create_time']):'--')."</span>";
				$itemHtml.="</div></td>";
				if(in_array($orderInfo['order_source'],['cdiscount','amazon']))
					$itemHtml.="<td><div style='width:100%'><a class='btn btn-info' href='/order/order-customer/order-invoice?app=msg&order_id=".$orderInfo['order_source_order_id']."&parcel=".$_REQUEST['parcel']."' target='_blank' style='float:right;'>".$translate_contents['Download Invoice']."</a></div></td>";
				else 
					$itemHtml.="<td></td>";
				$itemHtml.="</tr>";
				foreach ($orderItems as $item){
					if(empty($item['photo_primary']))
						$itemHtml.="<tr><td width='100px'><img style='width:80px;margin: auto;'/></td>";
					else 
						$itemHtml.="<tr><td width='100px'><img src='".$item['photo_primary']."' style='width:80px;'/></td>";
					$itemHtml.="<td width='350px'>".$item['product_name']."</td>";
					$itemHtml.="<td width='150px' style='text-align:center;'>".$translate_contents['quantity'].' : <b>'.$item['quantity']."</b></td>";
					$itemHtml.="<td width='100px'></td>";
					$itemHtml.="</tr>";
				}
				$itemHtml.="</table></div>";
				echo $itemHtml;
			}
			if(in_array($trackingInfo->carrier_type,$non17Track)){
				$formNationStr=$translate_contents['Origin Country'];
				$toNationStr=$translate_contents['Destination Country'];

				$div_event_html = "<div id='div_more_info_".$trackingInfo->id."' class='div_more_tracking_info' style='padding:0px 25px;'>";

				$all_events_str = "";
				$all_events_rt = TrackingHelper::generateTrackingEventHTML_forMSG([$trackingInfo->track_no],[],$formNationStr,$toNationStr,$originCountry,$destinationCountry);
				if (!empty($all_events_rt[$trackingInfo->track_no])){
					$all_events_str = $all_events_rt[$trackingInfo->track_no];
				
				
				}
					
				$div_event_html .=  $all_events_str;
				
				$div_event_html .= "</div>";
				echo $div_event_html;
			}
			/*
			foreach($eventList as $index=>$event){
				if($index==0) $stepClass = "topStep";
				elseif($index== count($eventList)-1 ) $stepClass = "buttomStep";
				else $stepClass = "midStep";
				$result .="
					<tr style='list-style:none;float:left;width:100%;'>
						<td style='vertical-align:top;padding-right:20px;'>
							<div style='min-width:112px;max-width:130px;float:left;padding:0px 0px 10px 0px;'><small><time>".$event['when']."</time></small></div>
						</td>
						<td class='".$stepClass."' style='vertical-align:top;width:30px;'><i></i></td>
						<td style='text-align: left;vertical-align:top;'>
							<div style='min-width:370px;max-width:600px;float:left;padding:0px 0px 10px 20px;'><small>".nl2br(base64_decode($event['what']))."</small></div>
						</td>
					</tr>";
			}
			$result .= "</table>";
			echo $result;
			*/
			
		}
		?>
		</div>
		<div class="Recommend layout<?=$layout ?>" style="">
		<?php if(count($recommendProduct)>0){
			echo "<h4 style='text-align:left;width:100%;margin:auto;background-color:transparent;border-bottom:2px solid rgb(251,169,25);color:rgb(251,169,25);font-weight:600;'>".$translate_contents['Hot Sale']."</h5>";
			echo "<div style='width:100%;margin:auto;display:inline-block;'>";
			echo "<a class='prev_recommends vertical'></a>";
			echo "<div id='show_recommends_div' style='width:775px;min-height:170px;overflow:hidden;float:left;'>";
			foreach ($recommendProduct as $index=>$aRecommend){
				echo "<a class='recommend_a hidden' index='$index' style='width:25%;float:left;' href='".$aRecommend['product_url']."' target='_blank' onclick='message.recommend.countProductClicked(".$aRecommend['id'].")'>";
				echo "<input id='recommend_$index' type='hidden' name='recommendProd_id' value='".$aRecommend['id']."'>";
				echo "<div style='width:100px;height:100px;padding:10px;clear:both;text-align:center;margin:auto;display:table;'>";
				echo "<div style='display:table-cell;vertical-align:middle;'><img src='".$aRecommend['product_image']."' style='width:80px;clear:both'></div></div>";
				$product_name = $aRecommend['product_name'];
				if(strlen($aRecommend['product_name'])>50)
					$product_name = substr($aRecommend['product_name'],0,97)."...";
				echo "<p style='width:100%;font-size:12px;height:68px;clear:both;text-align:center;padding:0px;/*display:table;*/'><span style='/*display:table-cell;vertical-align:middle;*/'>".$product_name."</span></p>";
				echo "<p style='width:100%;font-size:12px;float:right;clear:both;text-align:center;padding-top:5px;'>".$aRecommend['sale_currency'].$aRecommend['sale_price']."</p>";
				echo "</a>";
			}
			echo "</div>";
			echo "<a class='next_recommends vertical'></a>";
			echo "</div>";
		}
		?>
		</div>
	</div>
<?php }
	if (isset($layout) && $layout==2){?>
	<div style="width:840px;margin: auto;">
		<h4 style="float:left;padding:15px 0px;font-size:26px;font-weight:600;color:rgb(251,169,25);background-color:rgb(35,64,147);border-bottom:5px solid rgb(0,1,2);">
			<span style="width:155px;height:35px;float:left;background-image:url('/images/project/trackerhome/index/index/hz-002.jpg');background-position:0px 50%;margin-left:10px;"></span>
			<span style="width:675px;float:left;text-align:left;padding-left:130px;padding-top:3px;"><?=$translate_contents['The Parcel Information'];?></span>
		</h4>
		<div class="Recommend layout<?=$layout ?>" style="">
		<?php if(count($recommendProduct)>0){
			echo "<h4 style='text-align:left;margin:auto;background-color:transparent;border-bottom:2px solid rgb(251,169,25);color:rgb(251,169,25);font-weight:600;'>".$translate_contents['Hot Sale']."</h5>";
			echo "<div style='width:180px;max-height:840px;margin:auto;border-top:0px;display: inline-block;'>";
			echo "<a class='prev_recommends horizontal'></a>";
			echo "<div id='show_recommends_div' style='width:179px;max-height:780px;overflow:hidden;float:left;'>";
			foreach ($recommendProduct as $index=>$aRecommend){
				echo "<a class='recommend_a hidden' index='$index' style='width:178px;float:left;clear:both;' href='".$aRecommend['product_url']."' target='_blank' onclick='message.recommend.countProductClicked(".$aRecommend['id'].")'>";
				echo "<input id='recommend_$index' type='hidden' name='recommendProd_id' value='".$aRecommend['id']."'>";
				echo "<div style='width:100px;height:100px;padding:10px;clear:both;text-align:center;margin:auto;display:table;'><div style='display:table-cell;vertical-align:middle;'><img src='".$aRecommend['product_image']."' style='width:80px;clear:both'></div></div>";
				$product_name = $aRecommend['product_name'];
				if(strlen($aRecommend['product_name'])>50)
					$product_name = substr($aRecommend['product_name'],0,97)."...";
				echo "<div style='width:100%;font-size:12px;height:56px;clear:both;text-align:center;padding:0px;display:table;'><span style='display:table-cell;vertical-align:middle;'>".$product_name."</span></div>";
				echo "<p style='width:100%;font-size:12px;float:right;clear:both;text-align:center;padding-top:5px;'>".$aRecommend['sale_currency'].$aRecommend['sale_price']."</p>";
				echo "</a>";
			}
			echo "</div>";
			echo "<a class='next_recommends horizontal'></a>";
			echo "</div>";
		}
		?>
		</div>
		
		<div id="TrackingInfo" class="TrackingInfo layout<?=$layout ?>" style="">
		<?php
		if(count($trackingInfo)>0){
			
			$eventList = json_decode($trackingInfo->all_event,true);
			if(!empty($orderItems) && count($orderItems)>0){
				$itemHtml="<div style='padding-left:25px;'><table style='width:100%;float:left;margin-bottom:10px;'>";
				$itemHtml.="<tr><td colspan='4'><span style='color: #777;'><div style='padding:10px 0px;font-size:14px;'>".$translate_contents['Order ID']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".$orderInfo['order_source_order_id']."</span>";
				$itemHtml.="<span style='color: #777;padding-left:50px;'>".$translate_contents['Order Date']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".(!empty($orderInfo['create_time'])?date('Y-m-d',$orderInfo['create_time']):'--')."</span>";
				$itemHtml.="</div></td></tr>";
				foreach ($orderItems as $item){
					if(empty($item['photo_primary']))
						$itemHtml.="<tr><td width='100px'><img style='width:80px;margin: auto;'/></td>";
					else 
						$itemHtml.="<tr><td width='100px'><img src='".$item['photo_primary']."' style='width:80px;'/></td>";
					$itemHtml.="<td width='350px'>".$item['product_name']."</td>";
					$itemHtml.="<td width='150px' style='text-align:center;'>".$translate_contents['quantity'].' : <b>'.$item['sent_quantity']."</b></td>";
					$itemHtml.="<td width='100px'></td>";
					
				}
				$itemHtml.="</tr>";
				$itemHtml.="</table></div>";
				echo $itemHtml;
			}
			if(in_array($trackingInfo->carrier_type,$non17Track)){
				$formNationStr=$translate_contents['Origin Country'];
				$toNationStr=$translate_contents['Destination Country'];
				
				$div_event_html = "<div id='div_more_info_".$trackingInfo->id."' class='div_more_tracking_info' style='padding-left:25px;'>";

				$all_events_str = "";
				$all_events_rt = TrackingHelper::generateTrackingEventHTML_forMSG([$trackingInfo->track_no],[],$formNationStr,$toNationStr,$originCountry,$destinationCountry);
				if (!empty($all_events_rt[$trackingInfo->track_no])){
					$all_events_str = $all_events_rt[$trackingInfo->track_no];
				}
					
				$div_event_html .=  $all_events_str;
				
				$div_event_html .= "</div>";
				echo $div_event_html;
			}
		}
		?>
		</div>
	</div>
<?php }
if(isset($layout) && $layout==3){?>
	<div style="width:840px;margin: auto;">
		<h4 style="float:left;padding:15px 0px;font-size:26px;font-weight:600;color:rgb(251,169,25);background-color:rgb(35,64,147);border-bottom:5px solid rgb(0,1,2);">
			<span style="width:155px;height:35px;float:left;background-image:url('/images/project/trackerhome/index/index/hz-002.jpg');background-position:0px 50%;margin-left:10px;"></span>
			<span style="width:675px;float:left;text-align:left;padding-left:130px;padding-top:3px;"><?=$translate_contents['The Parcel Information'];?></span>
		</h4>	
		<div class="Recommend layout<?=$layout ?>" style="float:left;">
		<?php if(count($recommendProduct)>0){
			echo "<h4 style='text-align:left;width:100%;margin:auto;background-color:transparent;border-bottom:2px solid rgb(251,169,25);color:rgb(251,169,25);font-weight:600;'>".$translate_contents['Hot Sale']."</h5>";
			echo "<div style='width:100%;margin:auto;display:inline-block;clear:both;float:left;border-bottom:2px solid rgb(251,169,25);'>";
			echo "<a class='prev_recommends vertical'></a>";
			echo "<div id='show_recommends_div' style='width:777px;max-height:170px;overflow:hidden;display:table;vertical-align:middle;float:left;'>";
			foreach ($recommendProduct as $index=>$aRecommend){
				echo "<a class='recommend_a hidden' index='$index' style='width:25%;float:left;' href='".$aRecommend['product_url']."' target='_blank' onclick='message.recommend.countProductClicked(".$aRecommend['id'].")'>";
				echo "<input id='recommend_$index' type='hidden' name='recommendProd_id' value='".$aRecommend['id']."'>";
				echo "<div style='width:100px;height:100px;padding:10px;clear:both;text-align:center;margin:auto;display:table;'>";
				echo "<div style='display:table-cell;vertical-align:middle;'><img src='".$aRecommend['product_image']."' style='width:80px;clear:both'></div></div>";
				$product_name = $aRecommend['product_name'];
				if(strlen($aRecommend['product_name'])>50)
					$product_name = substr($aRecommend['product_name'],0,97)."...";
				echo "<p style='width:100%;font-size:12px;height:68px;clear:both;text-align:center;padding:0px;/*display:table;*/'><span style='/*display:table-cell;vertical-align:middle;*/'>".$product_name."</span></p>";
				echo "<p style='width:100%;font-size:12px;float:right;clear:both;text-align:center;padding-top:5px;'>".$aRecommend['sale_currency'].$aRecommend['sale_price']."</p>";
				echo "</a>";
			}
			echo "</div>";
			echo "<a class='next_recommends vertical'></a>";
			echo "</div>";
		}
		?>
		</div>
		<div id="TrackingInfo" class="TrackingInfo layout<?=$layout ?>" style="padding-top:10px;float:left;">
		<?php
		if(count($trackingInfo)>0){
			$eventList = json_decode($trackingInfo->all_event,true);
			if(!empty($orderItems) && count($orderItems)>0){
				
				$itemHtml="<div style='padding:0px 25px;'><table style='width:100%;float:left;margin-bottom:10px;'>";
				$itemHtml.="<tr><td colspan='4'><div style='padding:10px 0px;font-size:14px;'><span style='color: #777;'>".$translate_contents['Order ID']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".$orderInfo['order_source_order_id']."</span>";
				$itemHtml.="<span style='color: #777;padding-left:50px;'>".$translate_contents['Order Date']." : </span><span style='color:rgb(38, 38, 38);font-weight:600;'>".(!empty($orderInfo['create_time'])?date('Y-m-d',$orderInfo['create_time']):'--')."</span>";
				$itemHtml.="</div></td></tr>";
				foreach ($orderItems as $item){
					if(empty($item['photo_primary']))
						$itemHtml.="<tr><td width='100px'><img style='width:80px;margin: auto;'/></td>";
					else 
						$itemHtml.="<tr><td width='100px'><img src='".$item['photo_primary']."' style='width:80px;'/></td>";
					$itemHtml.="<td width='350px'>".$item['product_name']."</td>";
					$itemHtml.="<td width='150px' style='text-align:center;'>".$translate_contents['quantity'].' : <b>'.$item['sent_quantity']."</b></td>";
					$itemHtml.="<td width='100px'></td>";
					$itemHtml.="</tr>";
				}
				$itemHtml.="</table></div>";
				echo $itemHtml;
			}
			if(in_array($trackingInfo->carrier_type,$non17Track)){	
				$formNationStr=$translate_contents['Origin Country'];
				$toNationStr=$translate_contents['Destination Country'];

				$div_event_html = "<div id='div_more_info_".$trackingInfo->id."' class='div_more_tracking_info' style='padding:0px 25px;'>";

				$all_events_str = "";
				$all_events_rt = TrackingHelper::generateTrackingEventHTML_forMSG([$trackingInfo->track_no],[],$formNationStr,$toNationStr,$originCountry,$destinationCountry);
				if (!empty($all_events_rt[$trackingInfo->track_no])){
					$all_events_str = $all_events_rt[$trackingInfo->track_no];
				}
					
				$div_event_html .=  $all_events_str;
				
				$div_event_html .= "</div>";
				echo $div_event_html;
			}
		}
		?>
		</div>
		
	</div>
<?php } ?>
</div>
<!--/mainContent-->
<div id="progressBar" class="progressBar" style="display: none;">Tracking your parcel information , Please wait for a few seconds...</div>

<!-- Cooperation Advertisement -->
<div class="Co_Ad">

</div>
<!-- /Cooperation Advertisement -->

<div class="footer" style="width:100%;height:35px;border:0px;text-align:center;float:left;clear:both;background-color:transparent;">
	<div style="display:table;width:840px;background-color:rgb(179,179,179);margin: auto;height: 35px;">
		<p style="display:table-cell;vertical-align:middle;color:white;"><span>Copyright © 2014-2018 </span><a href="http://www.littleboss.com" target="blank">littleboss.com</a><span>  All Rights Reserved </span>&nbsp;&nbsp;<a href="http://www.miitbeian.gov.cn" style="color: #808d9a; text-decoration: none;">沪ICP备15025693号-4</a></p>
	</div>
</div>
<script>
function doTrack(num) {
    if(num===""){
        alert("Enter your number."); 
        return;
    }
    YQV5.trackSingle({
        YQ_ContainerId:"TrackingInfo", 
        YQ_Height:600, 
        YQ_Fc:"0",       //可选，指定运输商，默认为自动识别。
        YQ_Lang:"en",
        YQ_Num:num
    });
}
</script>