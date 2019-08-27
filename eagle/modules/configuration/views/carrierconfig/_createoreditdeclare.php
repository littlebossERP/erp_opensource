<?php 

use yii\helpers\Html;
use yii\helpers\Url;


?>
<style>
	.drop{
		width:250px;
	}
	.modal_input{
		width:250px;
		height:25px;
		line-height:25px;
	}
	.myline{
		height:25px;
		line-height:1.5;
		margin:8px 0;
	}
	.modal-body{
		width:600px;
		min-height:300px;
	}
	.accountbodys{
		min-height:240px;
	}
.minW120 {
    min-width: 120px;
}
.minW220 {
    min-width: 220px;
}
.form-control {
    font-size: 13px;
}
.modal-footer {
    padding: 10px 15px;
    text-align: right;
    border-top: 1px solid #e5e5e5;
}
.tab-content{
	padding:0;
	width:570px;
}
.mTop12 {
    margin-top: 12px;
}
</style>
<form id="EditFORM" class="col-xs-12 accountbodys">
			<div class="modal-body tab-content col-xs-12">
				<div class="mTop10">
					<div class="col-xs-12 mTop10 form-group">
						<label class="col-xs-1 minW120 mTop12">自定义名称:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="hidden" name="cid" maxlength="30" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['id']; ?>">
							<input class="form-control" type="text" name="cfName" maxlength="50" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['custom_name']; ?>">
						</div>
					</div>
					<div class="col-xs-12 mTop10 form-group">
						<label class="col-xs-1 minW120 mTop12">中文报关名:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="text" name="nameCh" maxlength="100" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['ch_name']; ?>">
						</div>
					</div>
					<div class="col-xs-12 mTop20 form-group">
						<label class="col-xs-1 minW120 mTop12">英文报关名:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="text" name="nameEn" maxlength="100" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['en_name']; ?>">
						</div>
					</div>
					<div class="col-xs-12 mTop10 form-group">
						<label class="col-xs-1 minW120 mTop12">申报金额:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="text" name="declaredValue" maxlength="20" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['declared_value']; ?>">
						</div>
						<div class="col-xs-1 mTop12">(USD)</div>
					</div>
					<div class="col-xs-12 mTop10 form-group">
						<label class="col-xs-1 minW120 mTop12">申报重量:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="text" name="weight" maxlength="20" value="<?php echo empty($commonDeclaredInfo)?'':(float)$commonDeclaredInfo['declared_weight']; ?>">
						</div>
						<div class="col-xs-1 mTop12">(g)</div>
					</div>
					<div class="col-xs-12 mTop10 form-group">
						<label class="col-xs-1 minW120 mTop12">海关编码:</label>
						<div class="col-xs-2 minW220">
							<input class="form-control" type="text" name="defaultHsCode" maxlength="50" value="<?php echo empty($commonDeclaredInfo)?'':$commonDeclaredInfo['detail_hs_code']; ?>" placeholder="非必填">
						</div>
					</div>
				</div>
			</div>
</form>
<div class="modal-footer col-xs-12">
	<button type="button" class="btn btn-primary" onclick="saveDeclare(<?php echo $type; ?>)">确认</button>
	<button type="button" class="btn btn-primary" onclick="ResetDeclare()">重置</button>
	<button class="iv-btn btn-default btn-sm modal-close" style="line-height: 1.42857143;font-size: 14px;">关闭</button>
</div>