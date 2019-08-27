<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;


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
if(empty($classCount)){
	$classCount = array();
}

//判断子账号是否有编辑权限
$is_catalog_edit = true;
$isMainAccount = UserApiHelper::isMainAccount();
if(!$isMainAccount){
	//查询版本号，当版本号空，则跳过，表明旧数据
	$version = UserApiHelper::GetPermissionVersion();
	if(!empty($version)){
		$ischeck = UserApiHelper::checkModulePermission('catalog_edit');
		if(!$ischeck){
			$is_catalog_edit = false;
		}
	}
}
?>
<!-- 左侧标签快捷区域 -->
<div class="left_menu menu_v2" onload="$(this).bind_hideScroll();" style="overflow: hidden;">
	<ul class="menu-lv-1">
		<li>
			<a class="clearfix product_title" onclick="$(this).next().next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">商品列表</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<div style="float: right; margin-right: 5px; line-height: 30px; ">
				<a class="<?= $is_catalog_edit ? 'class_setting' : ''?>" href="javascript:void(0);" data-toggle="modal" data-target="#editCategory" style="display: inline
					<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?> 
				>
					<span class="txt">设置分类</span>
				</a>
			</div>
			<ul class="menu-lv-2 classification" >
				<li>
					<div class="<?= yii::$app->controller->id == 'product' && (!isset($_REQUEST['class_id']) || $_REQUEST['class_id'] == '') ? 'choose_class' : ''; ?>">
						<span class="gly glyphicon glyphicon-triangle-bottom" data-isleaf="open"></span>
						<label>
							<span class="chooseTreeName" class_id="">所有分类 <?= isset($classCount['all']) ? ' ('.$classCount['all'].')' : '' ?></span>
							<span class=""></span>
						</label>
					</div>
					<ul data-cid="0" style="display: block;">
    					<li>
        					<div class="<?= yii::$app->controller->id == 'product' && isset($_REQUEST['class_id']) && $_REQUEST['class_id'] == '0' ? 'choose_class' : ''; ?>">
        						<label>
        							<span class="chooseTreeName" onclick="null" class_id="0">未分类 <?= isset($classCount[0]) ? ' ('.$classCount[0].')' : '' ?></span>
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
				<span class="menu_label ">额外信息管理</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/catalog/brand/index" class="clearfix <?=((yii::$app->controller->id == 'brand') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">品牌管理</span>
					</a>
				</li>
				<li>
					<a href="/catalog/tag/index" class="clearfix  <?=((yii::$app->controller->id == 'tag') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">标签管理</span>
					</a>
				</li>
				<li style="display: none; ">
					<a href="/catalog/rule/index" class="clearfix <?=((yii::$app->controller->id == 'rule') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">SKU解析规则</span>
					</a>
				</li>
			</ul>
		</li>
	</ul>	
</div>