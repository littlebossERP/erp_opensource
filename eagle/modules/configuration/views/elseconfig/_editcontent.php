<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\ExcelHelper;
//$this->registerJsFile(\Yii::getAlias('@web').'js/project/configuration/elseconfig/addexportshow.js?v=1.0', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
.modal-box{
	width:200px;
}
.modal-body {
    position: relative;
    padding: 15px;
}
.fW600 {
    font-weight: 600;
}
.col-xs-6 {
    width: 50%;
}
.mTop10 {
    margin-top: 10px;
}
.pTop8 {
    padding-top: 8px;
}
.p0 {
    padding: 0;
}
.modal-backdrop.in {
    filter: alpha(opacity=50);
    opacity: .3;
}
.modal-backdrop {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    background-color: #000;
}
.two{
	z-index:1;
}
</style>
<div class="modal-body tab-content col-xs-12">
					<div class="col-xs-12">
						<div class="col-xs-3 fW600 p0 text-left">字段名称:</div>
						<div class="col-xs-6">
							<span class="orderTitEditName" data-field="<?php echo $_POST['val'];?>" data-customname="<?php echo $_POST['customname'];?>"><?php echo $ordername; ?></span>
						</div>
					</div>
					<div class="col-xs-12 mTop10">
						<div class="col-xs-3 fW600p0 text-left pTop8">重命名：</div>
						<div class="col-xs-9">
							<input class="form-control newName" maxlength="20" type="text" value="<?php echo $name; ?>">
						</div>
					</div>
					<?php 
					if($val=='custom'){ 
						$value=empty($_POST['cusval'])?'':$_POST['cusval'];
					?>
						<div class="col-xs-12 mTop10">
							<div class="col-xs-3 fW600p0 text-left pTop8">默认值：</div>
							<div class="col-xs-9">
								<input class="form-control newValue" maxlength="20" type="text" value="<?php echo $value; ?>">
							</div>
						</div>
					<?php 
					}
					?>
</div>
<div class="modal-footer col-xs-12">
	<button type="button" class="alertsave btn btn-primary">保存</button>
	<button class="btn-default btn  modal-close">关 闭</button>
</div>