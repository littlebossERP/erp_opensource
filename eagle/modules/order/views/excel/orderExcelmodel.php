<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\ExcelHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<style>
.w1039{
	width: 1039px;
}
.p0 {
    padding: 0;
}
.mTop10 {
    margin-top: 10px;
}
.lh24 {
    line-height: 24px;
}
.myj-table {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
}
.modal-content table tr td {
    font-size: 13px;
}
table.excl td {
    min-width: 139px;
    height: 32px;
    text-align: left;
    padding: 2px 5px;
}
.myj-table tr td {
    border: 1px solid #ccc;
    text-align: center;
    font-size: 13px;
    padding: 3px;
    word-wrap: break-word;
    word-break: break-all;
	background-color: #eee;
}
table.excl .spanObjOther {
    display: inline-block;
    width: 130px;
    height: 25px;
    /*overflow: hidden;*/
    line-height: 25px;
    margin-left: 0;
	
}
table.excl .checkbox {
    margin-top: 0;
    margin-bottom: 0;
}
.pull-left {
    text-align: left;
}
.alert-warning {
    color: #8a6d3b;
    background-color: #fcf8e3;
    border-color: #faebcc;
/* 	margin: 10px; */
    text-align: left;
	display:none;
}
label {
    font-weight: 400;
	margin-bottom: 5px;
}
label input[type="radio"], label input[type="checkbox"] {
    position: relative;
    top: 2px;
}
.top7{
	position: relative;
    top: -7px;
}
.left6{
	position: relative;
    left: 15px;
}
</style>
<div class="col-xs-12 w1009">
						<div class="col-xs-12 p0 lh24 mTop10">
							<div class="col-xs-1 p0" style="margin-top: 3px;">导出方式：</div>
							<div class="col-xs-10">
								<!-- <label><input type="radio" name="isMaster" cid="isMaster" value="1">按包裹导出（每个包裹导出一行）</label>-->
								<label><input type="radio" name="isMaster" value="0">按订单导出（每个订单导出一行）</label>
								<label><input type="radio" name="isMaster" value="2" checked>按产品导出（每个产品导出一行）</label>
								<input type="button" value="订单导出指引" id="help_toggle" class="btn btn-info" style="margin-left:25px;">
							</div>
						</div>
						<div id="help_toggle_text" class="col-xs-12 alert-warning">
						1. 系统支持勾选或者所有页批量导出订单。导出的方式分按订单导出和按产品导出:<br><br>2. 按订单导出是将每条订单的商品合并成一行导出，格式示例入下图：<br><br><div style="clear:both;text-align:left;"><img src="/images/excel/excel_print1.png" width="50%"></div><br>3.按产品导出是每张订单的每条商品导出一行，格式示例入下图：<br><br><div style="clear:both;text-align:left;"><img src="/images/excel/excel_print2.png" width="50%"></div> 
						</div>
						<div class="col-xs-12 p0 lh24 mTop10">
						<div class="col-xs-1 p0 pTop1" style="margin-top: 7px;">范本类型：</div>
						<div class="col-xs-10">
							<div id="templateDiv" style="display:inline-block;vertical-align: middle"></div>
							<span qtipkey="excel_order_model" class="top7"></span>
							<a href="javascript:;" onclick="getTemplate('order');" class="mLeft10 mRight10" title="刷新范本"><span class="glyphicon glyphicon-refresh"></span></a>
							<a href="/configuration/elseconfig/excel-model-list" target="_blank" class="left6"><span class="fW600">+</span> 添加自定义范本</a>
						</div>
						</div>
						<div class="col-xs-12 p0"><span style="color: red;font-weight: 500;">*最多导出10000条订单</span></div>
						<div class="col-xs-12 mTop10 allCheckbox myj-hide myj-show" id="exportSelectAll" style="display: none;">
							<label><input type="checkbox" checked="" name="exportKey" value="all" onclick="cancelSelectAll(this);" style="margin-left:5px;"> 全部</label>
						</div>
						
						<div class="col-xs-12 mTop10" id="exportTable" style="display: none;">
						</div>
						
</div>
<div class="modal-footer col-xs-12 w1009">
	<button type="button" class="btn btn-primary exportPackage">导出</button>
	<button class="btn-default btn modal-close">关 闭</button>
</div>



<script>
$.initQtip();
$(document).ready(function(){ 
	$('.modal-box').addClass('w1039');
	getTemplate('order');
});
									
//选择模板
function changeExportTemplate(type){
	var templateId = $("#exportTemplateSelect").val();	
	if(templateId == -1 && $.isNumeric(templateId)){
		// 默认标准模板
		$("#exportSelectAll").show();
		$("#exportTable").show();
		$('input[name=exportKey]').prop("checked",true);
		getExportTemplate(templateId,'orderbiaozhun');	
	} else if(templateId !=-1 && $.isNumeric(templateId)) {
		// 自定义模板
		$("#exportSelectAll").hide();
		$("#exportTable").show();
		getExportTemplate(templateId,type);
	} else {
		// 未选模板
		$("#exportSelectAll").hide();
   		$("#exportTable").hide();
		$('#exportTable').find('table.excl td').html("");
	}
}
//获取字段
function getExportTemplate(tempId,templateType,obj){
	$.ajax({
        type: "POST",
        url: global.baseUrl+'order/excel/get-order-excel-id',
        data: {"id":tempId},
        dataType: "json",
        success: function(data){
        	if(data.code == 0){
        		var field = data.orderField;
        		// 转换为数组
//         		var arr = formatFieldFromStringToArray(field);
        		exportOpen(templateType,field);
        	}
        	else
        		$('#exportTable').find('table.excl td').html("");
        },
        error:function(){
        	$('#exportTable').find('table.excl td').html("");
        }
    });
}

//生成页面
function exportOpen(content,arr){
	if (arr != ''){
// 		if (content == 'order'){

			$.ajax({
		        type: "POST",
		        url: global.baseUrl+'configuration/elseconfig/export-html-table-view',
		        data: {"arr":arr,
			        "templateType":content},
		        success: function(data){
		        	$('#exportTable').html('');
					$('#exportTable').append(data);
		        },
		        error:function(){
		        	$('#exportTable').find('table.excl td').html("");
		        }
		    });
// 		}
	}
	else{
		$('#exportTable').find('table.excl td').html("");
	}
}

/** 格式化模板字段(字符串-》数组) **/
function formatFieldFromStringToArray(field){
	var arr = [];
	if(field != null && field != ""){
		var arr1 = field.split(",");
		for(var idx in arr1){
			if(idx!='remove'){
			    var arr2 = arr1[idx].split(":");
	 			var len = arr2.length;
	 			if(len > 0){
	 				var obj = {};
	 				obj.namecode = arr2[0];
	 				obj.name = arr2[1];
	 				obj.custom = arr2[2];
	 				arr.push(obj);
	 			}
			}
		}
	}
	return arr;
}

function cancelSelectAll(obj){
	if($(obj).is(":checked")){
		$("input[name='exportKey']").each(function(){
			$(this).prop("checked",true);
		});
	} else {
		$("input[name='exportKey']").each(function(){
			$(this).prop("checked",false);
		});
	}
}

/** 得到模板 **/
function getTemplate(tempType){
	$.ajax({
        type: "POST",
        url: global.baseUrl+'order/excel/get-by-type-html',
        data: {"type":tempType},
        dataType: "html",
        success: function(data){
        	$("#templateDiv").html(data);
        },
        error:function(){
        	$('#loading').modal('hide');
        }	
    });
};

$('#help_toggle').click(function(){
	$('#help_toggle_text').toggle(300);
});


</script>
