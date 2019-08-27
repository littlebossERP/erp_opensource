<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

$active = '';
$uid = \Yii::$app->user->id;
?>


<style>

.bold-dot{
    width: 8px;
    height: 8px;
    border-radius: 5px;
    display: inline-block;
    background-color: #666666;
}
.help-contents span{
	margin-bottom:5px;
}
span b {
	font-weight: bold;
	color:#01bdf0;
}
</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper help-contents">
		<h4 style="margin-bottom: 10px;">功能介绍</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;">
				<span style="float:left;">小老板 Amazon客服模块 旨在为用户统一管理Amazon订单售后客服提供便捷服务。</span>
			</div>
			<!-- 
			<div style="float:left;clear:both;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;"></span><span style="float:left;">售后客服：</span><br>
				<span style="margin-left:60px;float:left;">买家下单后至订单完成前，进行咨询/退款/退货 等Message往来，可以通过小老板系统进行处理。</span><br>
			</div>
			 -->
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;clear:both;"></span><span style="float:left;">售后推广：</span><br>
				<span style="margin-left:60px;float:left;clear:both;">订单交易完成后，适时、按需地发送邮件给买家，请求对方对订单或订单商品留评价，可以提高商品在Amazon网站的后续销售表现。</span><br>
				<span style="margin-left:60px;float:left;clear:both;">也可以根据买家的购买习惯，推荐一些相关产品给买家，达到一定的营销效果。</span>
			</div>
		</div>
		
		<h4 style="margin:20px 0px 10px 0px;clear:both;">使用方法</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;clear:both;"></span><span style="float:left;">绑定邮箱：</span>
				<span style="margin-left:60px;float:left;clear:both;">在使用 小老板 Amazon客服模块 发送邮件之前，您需要先对指定的Amazon账号&站点，<b>绑定一个有效的邮箱，并授权小老板为其使用Amazon邮件服务</b>。</span>
				<span style="margin-left:60px;float:left;clear:both;">通过左侧菜单“邮箱列表”，进入绑定的邮箱列表，点击顶部的“添加绑定”新建；</span><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_257_258.html')?>" target="_blank">查看图文教程</a>
			</div>
			
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;"></span><span style="float:left;">售后推广：</span>
				<span style="margin-left:60px;float:left;clear:both;">1. 为已经绑定了邮箱的店铺站点，设置一个<b>任务模板 </b>。<a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_257_259.html')?>" target="_blank">查看图文教程</a></span>
				<span style="margin-left:90px;float:left;clear:both;">通过左侧菜单“任务模板”，进入模板列表，点击顶部的“添加模板”新建；</span>
				<span style="margin-left:90px;float:left;clear:left;">设置时，很多规则需要了解清楚，可以查看文字后面的</span><span style="float:left;"><img width="16" src="/images/questionMark.png"></span><span style="float:left;clear:right;">来查看说明；</span>
				
				<span style="margin-left:60px;float:left;clear:both;">2. 管理已经创建好的任务模板。</span>
				<span style="margin-left:90px;float:left;clear:both;">模板列表中，在模板的“操作”栏中选择，有编辑、预览、生成任务、查看任务生成日志、删除模板等操作；</span>
				
				<span style="margin-left:60px;float:left;clear:both;">3. 定期为任务模板<b>生成邮件发送任务</b>。<a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_257_260.html')?>" target="_blank">查看图文教程</a></span>
				<span style="margin-left:90px;margin-top:5px;float:left;clear:both;font-weight: bold;color:red">如果你的模板设置了 排除给过review或者feedback的订单，那么您每次在生成发送任务之前都需要通过小老板Amazon客户端下载review或者feedback！</span>
				<span style="margin-left:90px;margin-bottom:5px;float:left;clear:both;font-weight: bold;"><a class="iv-btn btn-important" href="/attachment/amazoncs/xlbAmazonFetchV01.zip" target="_blank">小老板Amazon客户端下载链接</a> <a class="iv-btn btn-info" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_257_262.html')?>" target="_blank">Amazon客户端功能介绍</a></span>
				<span style="margin-left:90px;float:left;clear:both;">模板列表中，在模板的“操作”栏中选择；</span>
				<span style="margin-left:90px;float:left;clear:both;">生成任务后，各符合模板规则的订单会生成一条待发送邮件任务。在到了其指定的发送时间之前，该邮件不会自动发出，到了发送时间，则会尽快自动发送。</span>
				<span style="margin-left:90px;float:left;clear:both;">在待发送邮件发出之前，可以对其进行取消发送、立即发送等操作；</span>
				
				<span style="margin-left:60px;float:left;clear:both;">4. 等候邮件任务发出，及时留意任务的发送结果。</span>
				<span style="margin-left:90px;float:left;clear:both;">“待发送的邮件”、“已发送的邮件”、“已取消/发送失败” 分别列出了不同状态的邮件任务；</span>
				<span style="margin-left:90px;float:left;clear:both;">不同状态的邮件任务对应不同的操作。比如待发送的邮件可以选择取消或者立即发送，已发送或者发送失败的可以选择删除。</span>
			</div>
		</div>
		
		<h4 style="margin:20px 0px 10px 0px;clear:both;">说明、帮助</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;margin-top:10px;">
				<span style="margin-left:60px;float:left;clear:both;">Q: 模板设置中可以设置排除已经给过Review和Feedback的订单，这些Review和Feedback怎么获得？</span>
				<span style="margin-left:60px;float:left;clear:both;">A: Review和Feedback需要通过小老板Amazon客户端来下载获得。</span>
			</div>
			<div style="float:left;clear:both;margin-top:15px;">
				<span style="margin-left:60px;float:left;clear:both;">Q: 用客户端下载Review和Feedback的时候，需要登录Amazon账号吗？会关联吗？</span>
				<span style="margin-left:60px;float:left;clear:both;">A: 通过小老板Amazon客户端来下载Review和Feedback需要登录您的Amazon账号，因此您<b style="font-weight:bold;color:red">不能在同一台设备上登录，否则会被关联！</b>如果您有多个Amazon账号，可以分别在对应账号使用的设备上分别下载。</span>
				<span style="margin-left:60px;float:left;clear:both;">下载完之后，您可以在任意设备登录小老板ERP操作进行邮件发送。</span>
			</div>
			<div style="float:left;clear:both;margin-top:15px;">
				<span style="margin-left:60px;float:left;clear:both;">Q: 为什么我在自己的邮箱发件箱里面看不到通过小老板发出去的邮件？我怎么确认邮件是否真的发送成功？</span>
				<span style="margin-left:60px;float:left;clear:both;">A: 小老板Amazon邮件客服使用Amazon的邮件代发服务，因此用户的邮箱是看不到发送记录的。</span>
				<span style="margin-left:60px;float:left;clear:both;">如果在邮件发出之后一小时内，没有收到Amazon(发件人为‘MAILER-DAEMON’)发给你的报错邮件(有可能被标识未垃圾邮件，请邮件垃圾箱也确认一下)，则邮件发送成功。</span>
			</div>
		</div>
		
		
	</div>
	<div class=""></div>
</div>