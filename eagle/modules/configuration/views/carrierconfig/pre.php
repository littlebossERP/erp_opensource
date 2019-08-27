<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/carrier.js");
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/pre.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/custom.js");

?>
<style>
	body{
		color:black;
	}
	.headTitle{
		font-family: 'Applied Font Bold', 'Applied Font';
		white-space: nowrap;
		font-size: 32px;
		font-weight: 700;
	}
	.Title2{
		font-family: 'Applied Font Bold', 'Applied Font';
		white-space: nowrap;
		font-size: 24px;
		font-weight: 700;
		min-height:24px;
		margin-bottom:15px;
	}
	.Title3{
		font-family: 'Applied Font Bold', 'Applied Font';
		white-space: nowrap;
		font-size: 14px;
		font-weight: 700;
		min-height:14px;
	}
	.showDIV{
		background:#F5F5F5;
		min-height:130px;
		border:1px solid #CCCCCC;
		margin-top:30px;
		padding:8px;
		
	}
	.p3{
		font-family: 'Applied Font Regular', 'Applied Font';
		word-wrap: break-word;
		font-size: 13px;
		margin:15px 0;
	}
	.teachlink{
		float:right;
		color:#6633FF;
		margin-right:20px;
	}
	.openBtn{
		padding-left:20px;
		padding-right:20px;
	}
	.excelDIV{
		float:left;
		width:50%;
		padding-right:15px;
		border-right-color: rgb(204, 204, 204);
	    border-right-style: solid;
	    border-right-width: 1px;
	}
	.clear {
	    clear: both;
	    height: 0px;
	}
	.rightDIV{
		float:left;
		width:50%;
		padding-left:15px;
	}
</style>


<?php echo $this->render('../leftmenu/_leftmenu');?>

<div class="main_body">
	<div class="headTitle">您还没开启任何物流!</div>
	<div class="showDIV">
		<div class="Title2">API对接物流</div>
		<p class="p3">小老板ERP对接了一些卖家常用的货代公司的物流系统，只需要“开启物流”，并录入物流商提供的授权信息即可使用该物流商！</p>
		<div>
			<input type="button" class="iv-btn btn-search pull-right openBtn" onclick="openCarrierModel('open')" value="开启物流" />
			<a class="iv-btn teachlink hidden">设置教程</a>
		</div>
	</div>
	<div class="showDIV">
		<div class="excelDIV">
			<div class="Title2">自定义物流</div>
			<div class="Title3">一、Excel导数据</div>
			<p class="p3">小老板ERP还没有完成API对接或者不支持API的物流系统，可以通过Excel将订单从小老板ERP导出成物流系统需要的格式。</p>
			<div>
				<input type="button" class="iv-btn btn-search pull-right openBtn" onclick="openCarrierModel('newcarrier')" value="新建自定义物流" />
				<a class="iv-btn teachlink hidden">设置教程</a>
			</div>
			<div class="clear"></div>
		</div>
		<div class="rightDIV">
			<div class="Title2"></div>
			<div class="Title3">二、无数据交互</div>
			<p class="p3">不需要数据交互的物流，比如中国邮政的中邮小包，会给用户一批可用的物流号。根据物流号打印中邮小包的物流标签即可发货。</p>
			<div>
				<input type="button" class="iv-btn btn-search pull-right openBtn" onclick="openCarrierModel('newcarrier')" value="新建自定义物流" />
				<a class="iv-btn teachlink hidden">设置教程</a>
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
</div>
<!-- modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
</div>