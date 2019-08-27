<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/index.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/configuration/carrierconfig/switch.css");
?>
<style>
.mTop10 {
    margin-top: 10px;
}
.p0 {
    padding: 0;
}
.myj-table {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
}
.myj-table tr th {
    text-align: center;
    font-size: 13px;
    padding: 5px;
    height: 40px;
    background-color: #eee;
    border-bottom: 1px solid #ccc;
}
.myj-table tr td {
    border: 1px solid #ccc;
    text-align: center;
    font-size: 13px;
    padding: 3px;
    word-wrap: break-word;
    word-break: break-all;
}
.myj-table tr:hover td {
    background-color: #f2f2f2;
}
</style>
<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<div class="col-xs-10 mTop10">
	<div class="col-xs-12">
		<div class="col-xs-12 p0">
			<button class="iv-btn btn-important" onclick="createoreditDeclare(1,0,0)">创建报关信息</button>
		</div>
	</div>
	<div class="col-xs-12 p0" id="defaultCustomsDiv">

<!--分页-->
<div class="col-xs-12 p0" style="text-align:right;">
	<ul class="pageDiv" style="margin:2px 0px 10px 0;"></ul>
</div>
<div class="col-xs-12 mTop10">
	<div>
		<table class="myj-table" style="width:100%">
			<thead>
			<tr>
				<th>自定义名称</th>
				<th>报关信息</th>
				<th>选择默认</th>
				<th>操作</th>
			</tr>
			</thead>
			<tbody id="subAccountList">
			<?php 
				foreach ($commonDeclaredInfo as $key=>$commonDeclaredInfoone){
			?>
				<tr id="tr2592279">
					<td style="width:120px;min-width:80px;"><?php echo $commonDeclaredInfoone['custom_name'];?></td>
					<td style="min-width:160px;">
							<?php 
								echo empty($commonDeclaredInfoone['ch_name'])?'':$commonDeclaredInfoone['ch_name'].'<br/>'; 
								echo empty($commonDeclaredInfoone['en_name'])?'':$commonDeclaredInfoone['en_name'].'<br/>';
								echo empty($commonDeclaredInfoone['declared_value'])?'':'$ '.$commonDeclaredInfoone['declared_value'].'<br/>';
								echo empty($commonDeclaredInfoone['declared_weight'])?'':(float)$commonDeclaredInfoone['declared_weight'].' g<br/>';
							?>							
					</td>
					<td style="min-width:60px;"><input type="radio" name="isDefault" <?php echo empty($commonDeclaredInfoone['is_default'])?'':'checked="checked"'; ?> onclick="createoreditDeclare(4,<?php echo $commonDeclaredInfoone['id']; ?>,1)"></td>
					<td style="min-width:60px">
						<a href="javascript:;" onclick="createoreditDeclare(2,<?php echo $commonDeclaredInfoone['id'].','.$commonDeclaredInfoone['is_default']; ?>)">修改</a><br>
						<a href="javascript:;" onclick="createoreditDeclare(3,<?php echo $commonDeclaredInfoone['id']; ?>,0)">删除</a>
					</td>
				</tr>
			<?php } ?>	
			</tbody>
		</table>
	</div>
</div>
<!--分页-->
<div class="col-xs-12 p0" style="text-align:right;">
	<ul class="pageDiv" style="margin:2px 0px 10px 0;"></ul>
</div>

</div>

</div>