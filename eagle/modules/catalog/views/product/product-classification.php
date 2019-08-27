<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper; 
use Qiniu\json_decode;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/catalog/product-classification-setting.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>

<style>
.modal-box{
	width:600px;
}
.class_tree {
    margin: 0;
    padding: 5px;
    color: #333;
	font-family: "Arial","Microsoft YaHei","黑体","宋体",sans-serif !important;
}
.class_tree li {
    padding: 0;
    margin: 0;
    list-style: none;
    line-height: 14px;
    text-align: left;
    white-space: nowrap;
    outline: 0;
	margin-top: 3px;
}
.class_tree li a {
    padding: 1px 3px 0 0;
    margin: 0;
    cursor: pointer;
    height: 22px;
    color: #333;
    background-color: transparent;
    text-decoration: none;
    vertical-align: top;
    display: inline-block;
	line-height: 19px;
	width: 100%;
	border-bottom: 1px solid #DDDDDD;
}
.class_tree li ul {
    margin: 0;
    padding: 0 0 0 18px;
}
.class_tree * {
    font-size: 16px;
}
.class_tree li span.button.noline_open {
    background-position: -92px -72px;
}
.class_tree li span.button.switch {
    width: 18px;
    height: 18px;
}
.class_tree li span.button {
    /*line-height: 0;*/
    margin: 0;
    width: 16px;
    height: 16px;
    display: inline-block;
   /*vertical-align: middle;*/
    border: 0 none;
    cursor: pointer;
    outline: none;
    background-color: transparent;
    background-repeat: no-repeat;
    background-attachment: scroll;
}
.class_tree li span.button.class_add{
	color: #61BD50;
}
.class_tree li span.button.class_edit{
	color: #137ABF;
}
.class_tree li span.button.class_remove{
	color: #EC5947;
}
ul.class_tree span.glyphicon-triangle-right, ul.class_tree span.glyphicon-triangle-bottom, ul.class_tree span.glyphicon-edit, ul.class_tree span.glyphicon-remove{
    cursor: pointer;
}
.bgeee{
	background-color:#76b6ec!important;
}
.class_tree li span.button {
    margin-right: 10px;
	float: right;
}
.displays{
	display:none!important;
}
.new{
	margin-left: 4px!important;
}
#page-content .modal-footer {
	border-top: 1px solid #e5e5e5!important;
	margin-bottom: -44px!important;
}
.class_tree_swith{
	position: absolute;
	margin-left: -17px;
	display: inline-block;
	font-family: 'Glyphicons Halflings';
	font-style: normal;
	font-weight: normal;
	line-height: 1;
}
.class_tree li a:hover {
	background-color: #DDDDDD !important;
}
</style>

<div style="margin-left: 20px;">
	<ul class="class_tree class_tree_setting">
		<li node_number="">
			<span id="categoryTreeB_0_switch" title="categoryTreeB_1_ul_0" class="gly1 glyphicon-triangle-bottom class_tree_swith" data-isleaf="open"></span>
			<a id="categoryTreeB_0_a" class="level0 curSelectedNode" target="_blank" style="">
				<span id="categoryTreeB_0_span">所有分类</span>
				<span class="button class_add glyphicon glyphicon-plus" id="addBtn_categoryTreeB_0" title="添加分类"></span>
			</a>
			<ul id="categoryTreeB_" class="level" style="display:block;margin-left:10px;margin-top:0px;">
			<?php echo $html; ?>
			</ul>
		</li>
	</ul>
</div>

<div><input id="removeli" type="hidden" value=""><input id="addli" type="hidden" value="0"></div>

<div class="modal-footer col-xs-12 w1009">
	<button type="button" class="btn btn-primary">关闭</button>
</div>
