<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
?>
<style>
.div-900{
	width:900px;
	overflow: auto;
	margin-top:10px;
	margin-bottom:50px;
}
.div-400{
	width:500px;
	float: left;
}
.div-500{
	width:400px;
	float: left;
	text-align: left;
}
.div-50{
	width:50%;
	margin-top:40px;
	float:left;
}
</style>

<?php 

?>
<center>
<div class="div-900">
<div class="div-400">
<p><span style="font-size:25px;">当前数据镜像</span></p>
<div style="width:100%;margin-top:10px;"><?=Html::dropDownList('source',array(),$mirroringlist,['multiple'=>'multiple','size'=>'20','id'=>'source','style'=>'width:400px;overflow:scroll;'])?></div>
</div>
<div class="div-500">
<div style="width:100%;overflow: auto;">
<div class="div-50">PUID:&nbsp;&nbsp;<input id="textpuid" type="text" value=""></div>
<div class="div-50"><input class="iv-btn btn-important" type="button" value="还原镜像"  onclick="copy()"><br/><br/><input class="iv-btn btn-important"  type="button" value="生成镜像" onclick="create()"></div>
</div>

<form id="form_import_file">
<div style="width:100%;margin-top:20px;">
<input  type="file" id="input_import_file" name="input_import_file"  /><br/>
SQL语句&nbsp;<textarea id="Sentence" name="Sentence" rows="5" cols="50"></textarea>
</div>

<div style="width:100%;margin-top:20px;text-align:right;">
<input class="iv-btn btn-important" id="runbtn" type="button" value="运行SQL conversion" onclick="run()">
</div>
</form>
</div>
</div>
</center>
<div id="dialog"  style="display:none;">
镜像名称:&nbsp;&nbsp;<input id="mirr_name" name="mirr_name" type="text" value="">
</div>


<script>
function create(){
	$textpuid=$('#textpuid').val();

	bootbox.dialog({
		title: Translator.t("生成镜像"),
		className: "order_info", 
		message: $('#dialog').html(),
		buttons:{
			Ok: {  
				label: Translator.t("确定"),  
				className: "btn-success",  
				callback: function () { 
					$mirr_name=$('input[name=mirr_name]:eq(1)').val();
					$.showLoading();
					$.ajax({
						type: "POST",
						dataType: 'json',
						url:'/tool/mirroring/create', 
						data: {textpuid:$textpuid,mirr_name:$mirr_name},
						success: function (result) {
							$.hideLoading();
							var  tmpMsg ;
							if (result.code == true){
								$.alertBox('<p class="text-success" style="font-size:24px;">生成成功</p>');
								window.location.reload();
								if (typeof callback != 'undefined'){
									callback();
								}
							}else{
								bootbox.alert(result.message);
							}
										
							return true;
						},
						error: function(){
							$.hideLoading();
							bootbox.alert("Internal Error");
							return false;
						}
					});
				}
			}, 
			Cancel: {  
				label: Translator.t("取消"),  
				className: "btn-default",  
				callback: function () {  
				}
			}, 
		}
	});	


}




function copy(){
	$textpuid=$('#textpuid').val();	
	if($textpuid==''){
		bootbox.alert("puid为空");
		return false;
	}

	var selectid= $("#source").val();
	if(selectid==null){
		bootbox.alert("没有选择镜像");
		return false;
	}

	if (!confirm("\n确定还原吗?", true)) {
		return;
	}

	$.showLoading();
	$.ajax({
		type: "POST",
		dataType: 'json',
		url:'/tool/mirroring/copy', 
		data: {textpuid:$textpuid,selectid:selectid},
		success: function (result) {
			$.hideLoading();
			var  tmpMsg ;
			if (result.code == true){
				$.alertBox('<p class="text-success" style="font-size:24px;">导入成功</p>');
				if (typeof callback != 'undefined'){
					callback();
				}
			}else{
				bootbox.alert(result.message);
			}
						
			return true;
		},
		error: function(){
			$.hideLoading();
			bootbox.alert("Internal Error");
			return false;
		}
	});

}


function run(){	
// 	$.ajax({
// 		type: "POST",
// 		url:'/tool/mirroring/run', 
// 		data:{form:$("#form_import_file")[0]},
// 		success: function (result) {
// 			return true;
// 		},
// 		error: function(){
// 			bootbox.alert("Internal Error");
// 			return false;
// 		}
// 	});

	// 请求的后端方法
    var url="/tool/mirroring/run";
    // 获取文件
    var file = document.getElementById('input_import_file').files[0];
   	$Sentence=$('#Sentence').val();

    // 初始化一个 XMLHttpRequest 对象
    var xhr = new XMLHttpRequest();
    // 初始化一个 FormData 对象
    var form = new FormData();

    // 携带文件
    form.append("file", file);
    form.append("Sentence", $Sentence);
    //开始上传
    xhr.open("POST", url, true);
    //在readystatechange事件上绑定一个事件处理函数
    xhr.onreadystatechange=callback;
    xhr.send(form);

    function callback() {
        if(xhr.readyState == 4){
            if(xhr.status == 200){
                if(xhr.responseText == 1){
                    return true;
                }else{
                   return false;
               }
            }
        }
   }
}

function getMsg(){
	$.ajax({
		type: "POST",
		dataType: 'json',
		url:'/tool/mirroring/getmsg', 
		data:{a:1},
		success: function (result) {
			if (result.code == false){
				$('#runbtn').attr("disabled",'disabled');
			}
			else{
				$("#runbtn").removeAttr("disabled");
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown){
//             alert(XMLHttpRequest.status);
//             alert(XMLHttpRequest.readyState);
//             alert(textStatus);
// 			bootbox.alert("Internal Error");
			return false;
		}
	});
}

//每隔3秒执行一次GetExcelUrl方法
var id_Interval = window.setInterval("getMsg()",4000);
</script>