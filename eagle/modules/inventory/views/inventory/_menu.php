<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/catalog/product-classification.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
.left_menu{
	min-width: 180px !important;
}
.product_title{
	width: 120px; 
	display: inline-block !important;
}
.img-info>div{margin: 5px 0;}
input[name='add_image_url']{width: 80%;}
.image-name{
	text-overflow: ellipsis;
	overflow: hidden;
	text-align: center;
	white-space: nowrap;
	line-height: 20px;
}
.dropdown-menuTree {
    min-width: 160px;
    padding: 0;
    margin: 2px 0 0;
    font-size: 13px;
    text-align: left;
    background-color: #fff;
    /*border: 1px solid #ccc;*/
}
.dropdown-menuTree li.tit1 {
    height: 32px;
    line-height: 32px;
    /*color: #000;*/
    list-style: none;
    vertical-align: top;
    /*padding-left: 5px;*/
	font-size: 12px;
}
.pRight10 {
    padding-right: 10px;
}
.mRight10 {
    margin-right: 10px;
}
ul .classification {
    list-style: none;
    margin: 0;
    padding-left: 10px;
    width: 100%;
    margin-bottom: 5px;
    /*color: #000;*/
	font-size: 14px;
	line-height: 22px;
}
ul .classification ul {
    list-style: none;
    margin: 0;
    padding-left: 17px;
}
ul .classification li div.outDiv {
    height: 22px;
    padding-top: 3px;
}
ul .classification li span.chooseTreeName {
    cursor: pointer;
    padding-left: 3px;
    padding-right: 3px;
}
ul .classification span.glyphicon-triangle-right, ul .classification span.glyphicon-triangle-bottom {
    cursor: pointer;
}
.bgColor {
    background: rgb(1,189,240);
	color: rgb(255,255,255);
}
.margin-left-4{
	margin-left: 4px;
}
label{
	line-height:17px;
}
.chooseTreeName:hover {
	color: #428bca !important;
}
.choose_class{
	background: rgb(1,189,240);
	color: rgb(255,255,255);
}
.content-wrapper{
	margin-left: 20px; 
	margin-top: 10px;
}
</style>
<?php
//重新读取分类信息
if(empty($class_html)){
	$class_html = ProductHelper::GetProductClass();
}
?>
<!-- 左侧标签快捷区域 -->
<div class="left_menu menu_v2" onload="$(this).bind_hideScroll();" style="overflow: hidden;">
	<ul class="menu-lv-1">
		<li>
			<a class="clearfix" onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">库存列表</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2 classification" class_key="inventory">
				<li>
					<div class="<?= yii::$app->controller->id == 'inventory' && yii::$app->controller->action->id == 'index' && (!isset($_REQUEST['class_id']) || $_REQUEST['class_id'] == '') ? 'choose_class' : ''; ?>">
						<span class="gly glyphicon glyphicon-triangle-bottom" data-isleaf="open"></span>
						<label>
							<span class="chooseTreeName" class_id="">所有分类</span>
							<span class=""></span>
						</label>
					</div>
					<ul data-cid="0" style="display: block;">
    					<li>
        					<div class="<?= yii::$app->controller->id == 'inventory' && yii::$app->controller->action->id == 'index' && isset($_REQUEST['class_id']) && $_REQUEST['class_id'] == '0' ? 'choose_class' : ''; ?>">
        						<label>
        							<span class="chooseTreeName" onclick="null" class_id="0">未分类</span>
        						</label>
        					</div>
    				    </li>
						<?= $class_html ?>
					</ul>
				</li>
			</ul>
		</li>
		<li>
			
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">出入库操作</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/inventory/inventory/stockchange" class="clearfix <?=((yii::$app->controller->id == 'inventory') && (yii::$app->controller->action->id == 'stockchange'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">出入库记录</span>
					</a>
				</li>
				<li>
					<a href="/inventory/inventory/stock_in" class="clearfix  <?=((yii::$app->controller->id == 'inventory') && (yii::$app->controller->action->id == 'stock_in'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">新建入库</span>
					</a>
				</li>
				<li>
					<a href="/inventory/inventory/stock_out" class="clearfix <?=((yii::$app->controller->id == 'inventory') && (yii::$app->controller->action->id == 'stock_out'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">新建出库</span>
					</a>
				</li>
				<li>
					<a href="/inventory/inventory/stock-allocation" class="clearfix <?=((yii::$app->controller->id == 'inventory') && (yii::$app->controller->action->id == 'stock-allocation'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">仓库调拨</span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">库存盘点</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/inventory/inventory/stocktake" class="clearfix <?=((yii::$app->controller->id == 'inventory') && (yii::$app->controller->action->id == 'stocktake'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">库存盘点</span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">仓库设置</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/inventory/warehouse/warehouse_list" class="clearfix <?=((yii::$app->controller->id == 'warehouse') && (yii::$app->controller->action->id == 'warehouse_list'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">仓库列表</span>
					</a>
				</li>
			</ul>
		</li>
	</ul>	
</div>
