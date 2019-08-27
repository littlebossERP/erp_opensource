<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;

$this->registerJsFile ( $baseUrl . "/js/jquery.json-2.4.js", [
		'depends' => [
		'yii\jui\JuiAsset',
		'yii\bootstrap\BootstrapPluginAsset'
	]
] );
$this->registerCssFile ( $baseUrl . "/css/statistics/statistics.css" );

$this->registerJsFile($baseUrl."/js/project/statistics/statisticsList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/statistics/achievementList.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("statistics.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("achievement.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

?>

<style>
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.div_choose
{
	border: 1px solid #ccc;
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_choose > span
{
	font-size: 15px; 
	line-height: 24px;
	float:left;
}
th
{
	text-align: center !important;
}
</style>

<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/_menu') ?>
	<?php if(isset($ischeck) && $ischeck == 0){?>
    	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
        	<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
        </div>
    <?php }
    else{?>
    	<div class="content-wrapper" >
			<div style="width: 100%; float:left;">
			    <div class="div-input-group" style="float: left;margin-left:20px; margin-bottom:10px;">
			    	<LABEL class="lb_choose_title">筛选时间：</LABEL>
					<input id="statistics_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('付款日期从 此日期后')?>" 
						value="<?= (empty($_GET['start_date'])?"":$_GET['start_date']);?>" style="width:120px;margin:0px;height:28px;float:left; margin-right:0px;"/>
					<LABEL class="lb_choose_title"> ~ </LABEL>
					<input id="statistics_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
						value="<?= (empty($_GET['end_date'])?"":$_GET['end_date']);?>" style="width:120px;margin:0px;height:28px;float:left; margin-right:20px;"/>
					
					<LABEL class="lb_choose_title">统计周期：</LABEL>
			        <SELECT id="select_period" class="eagle-form-control" style="float:left; width:60px;margin:0px 20px 0px 0px;">
			            <OPTION value="D" >按日</OPTION>
		  				<OPTION value="W" >按周</OPTION>
		  				<OPTION value="M" >按月</OPTION>
		  			</SELECT>
		  			
		  			<LABEL class="lb_choose_title">币种：</LABEL>
			        <SELECT id="select_currency" class="eagle-form-control" style="float:left; width:60px;margin:0px 30px 0px 0px;">
			            <OPTION value="USD" >USD</OPTION>
		  				<OPTION value="RMB" >RMB</OPTION>
		  			</SELECT>
		
					<button id="achievement_search" class="iv-btn btn-search btn-spacing-middle">搜索</button>
					<button type="button" class="iv-btn btn-primary" onclick="achievement.list.exportExecl()" style="border-style: none; margin-left: 20px;">导出</button>
				</div>
				<div class="div_choose">
				    <span>平台渠道：</span>
				    <div style="width: 90%; float:left;">
				    	<label class="lb_check_node"><input type="checkbox" name="select_platform_all" checked>全部</label>
			  		    <?php foreach($platformAccount as $key=>$v){?>	
			      		    <label style="margin-left:0;margin-right:20px; line-height: 25px; ">
			    				<input type="checkbox" name="select_platform" checked value="<?=$v ?>">
			    				<?=$v ?>
			    			</label>
						<?php } ?>
					</div>
				</div>
				<div class="div_choose" style="border-top:none;">
				    <span>店铺账号：</span>
				    <div style="width: 90%; float:left;">
				    	<label class="lb_check_node" style="min-width:240px;"><input type="checkbox" name="select_store_all" checked>全部</label>
			  		    <?php foreach($stores as $key=>$v){?>	
			      		    <label class="lb_check_node" style="min-width:240px;">
			    				<input type="checkbox" name="select_store" checked value="<?=$v ?>" info="<?=$key ?>">
			    				<?=$key ?>
			    			</label>
						<?php } ?>
					</div>
				</div>
				<div id="div_order_type" class="div_choose" style="border-top:none; display:none;">
				    <span>订单类型：</span>
				    <div style="width: 90%; float:left;">
				    	<label class="lb_check_node"><input type="checkbox" name="select_order_type_all" checked>全部</label>
			  		    <label style="margin-left:0;margin-right:20px; line-height: 25px; ">
			    			<input type="checkbox" name="select_order_type" checked value="normal">
			    			普通订单
			    		</label>
			    		<label id="order_type_fba" style="margin-left:0;margin-right:20px; line-height: 25px; ">
			    			<input type="checkbox" name="select_order_type" checked value="fba">
			    			FBA订单
			    		</label>
			    		<label id="order_type_fbc" style="margin-left:0;margin-right:20px; line-height: 25px; ">
			    			<input type="checkbox" name="select_order_type" checked value="fbc">
			    			FBC订单
			    		</label>
					</div>
				</div>
			</div>
				
			<!-- table -->
			<div class="shoplist" style="float:left;">
			    <table id="achievement_table" cellspacing="0" cellpadding="0" class="table table-hover" style="float:left;font-size: 12px;">
					<tr class="achievement_title">
						<th style="min-width:250px;">日期</th>
						<th style="min-width:250px;">订单总量</th>
						<th style="min-width:250px;">订单总金额</th>
						<th style="min-width:250px;">订单总利润</th>
					</tr>
			        
			    </table>
			    <!-- Modal -->
				<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			    <div class="modal-dialog">
			        <div class="modal-content">
			        </div><!-- /.modal-content -->
			    </div>
			    </div>
			    <!-- /.modal-dialog -->
			</div>
			<div style="width: 100%; float: left;">
			    <div id="statistics_pager_group" >
			    </div>
			</div>
			<div>
			    <input type="hidden" id="choose_start_date" value="" />
			    <input type="hidden" id="choose_end_date" value="" />
			    <input type="hidden" id="choose_selectplatform" value="" />
			    <input type="hidden" id="choose_selectstore" value="" />
			    <input type="hidden" id="choose_period" value="" />
			    <input type="hidden" id="choose_currency" value="" />
			    <input type="hidden" id="choose_order_type" value="" />
			</div>
		</div>
		
		<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
    <?php }?>
</div>

