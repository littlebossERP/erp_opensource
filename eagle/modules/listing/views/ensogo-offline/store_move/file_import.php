<?php 
use yii\widgets\LinkPager;
$this->title = "Excel搬家";

$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("ensongoStoreMoveByFileReady()", \yii\web\View::POS_READY);

echo $this->render('//layouts/new/left_menu',[
	'menu' => $menu,
	'active' => $active
]);
?>
<style>
.ensongo-store-move-by-file{
	width: 900px;
	margin: auto;
	margin-top: 20px;
}
.store-move-result{
	flex: 0 0 100%;
	margin: 40px 0;
}
#result-table{
	width: 100%;
}
#result-table td,#result-table th{
	text-align: center;
}
.store-move-steps{
	border: 1px solid;
	padding: 10px;
	width: 248px;
	text-align: center;
}
.rotate90{
	-webkit-transform: rotate(90deg);
	-moz-transform: rotate(90deg);
	-ms-transform: rotate(90deg);
	transform: rotate(90deg);
}
.download-result{
	flex: 0 0 100%;
	display: flex;
	display: -webkit-flex; 
	flex-wrap: wrap;
	align-items: center;
	padding: 10px;
}
.all_tabs{
	width:100%;
}
.float_left{
/* 	float:left; */
	padding-left:7%;
	padding-top:15px;
}
.drop{
	width: 100%;
    height: 100px;
}
#update_tabs{
	padding-left:7%;
}
.publish_status{
	text-align: left !important;
}
.iv-input{
/* 	width:150px; */
}
</style>
<div class="ensongo-store-move-by-file">
	<div class="all_tabs">
	  <div>
	  	<a style="color:red;line-height:30px;font-size:13px;" href="http://www.littleboss.com/announce_info_24.html" target="_blank">
	  		<span class="glyphicon glyphicon-question-sign"></span> 搬家教程
	  	</a>
      </div>
      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#download_tabs" aria-controls="home" role="tab" data-toggle="tab">第一步：下载Excel（速卖通商品数据）</a></li>
        <li role="presentation"><a style="background-color: #CCC;color: #555;" href="#" aria-controls="profile" role="tab" data-toggle="tab">第二步：修改Excel（填写ensogo分类和运费）</a></li>
        <li role="presentation"><a href="#update_tabs" aria-controls="profile" role="tab" data-toggle="tab">第三步：上传Excel</a></li>
      </ul>
    
      <!-- Tab panes -->
      <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="download_tabs">
            <div class="drop">
                <!-- 如果下载模板时未选择店铺显示“请先选择要搬家的店铺 -->
		        <!-- 如果后台正在组织数据，把框内所有组件禁用 -->
                <p class="float_left">
        			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;要搬家的店铺：<select name="ali_site_id" class="iv-input" placeholder="请选择" id="select-export-alistore">
        				<option value="">请选择</option>
        				<?php 
        				$opt = array();
        				foreach($data['aliexpressUsers'] as $user){
        					$selected = "";
        					$opt[] = "<option value='{$user['sellerloginid']}' {$selected} >{$user['sellerloginid']}</option>";
        				}
        				echo implode(PHP_EOL,$opt);
        				?>
        			</select>
        		</p>
        		<!-- 用户上传文件时触发将商品上传到Ensogo店铺 -->
        		<p class="float_left">
        			目标Ensogo店铺：<select name="ensogo_site_id" class="iv-input" placeholder="请选择" id="select-import-ensogostore">
        				<option value="">请选择</option>
        				<?php 
        				$opt = array();
        				foreach($data['ensogoUsers'] as $user){
        					$selected = "";
        					$opt[] = "<option value='{$user['site_id']}' {$selected} >{$user['store_name']}</option>";
        				}
        				echo implode(PHP_EOL,$opt);
        				?>
        			</select>
        		</p>
        	</div>
            <div  style="padding-left: 19%;">
                <p style="margin: 10px 0;"><button id="ensogo-file-export-btn" class="iv-btn btn-info download-excel">组织数据生成Excel</button></p>
            </div>
            <div id="ali-listing-download-result" class="download-result">
        		<?php foreach ($data['exportExecutionInfo'] as $eachAccountExecution):?>
        		<div style="flex: 0 0 440px;" date-alicount="<?= $eachAccountExecution['custom_name']?>">
        		<?php if(2 == $eachAccountExecution['status']):?>
        			店铺 &nbsp;<strong style="font-weight: bold;"> <?= $eachAccountExecution['custom_name']?></strong>&nbsp;数据组织完成。
        			
        		<?php else: // 0,1?>	
        			<IMG alt="loading" src="/images/loading2.gif" width="16" height="16">
        			店铺 &nbsp;<strong style="font-weight: bold;"> <?= $eachAccountExecution['custom_name']?></strong>&nbsp;的商品正在组织，请耐心等待。
        		<?php endif;?>
        		</div>
        		<div style="flex: 0 0 440px;" date-alicount="<?= $eachAccountExecution['custom_name']?>">
        		<?php if(2 == $eachAccountExecution['status']):?>
        			<button type="button" class="btn btn-default" onclick="downloadFile('<?=$eachAccountExecution['custom_name']?>')">下载</button>
        		<?php endif;?>
        		</div>
        		<?php endforeach;?>
        	</div>
        </div>
        <div role="tabpanel" class="tab-pane" id="update_tabs">
       		<?php $displayPublishFailBtn = "";?>
       		<?php if(empty($data['listingExecutionInfo'])) $displayPublishFailBtn = 'display: none;';?>
            <p style="margin: 20px 0;">
            	<button id="import_file_btn" class="iv-btn btn-info" style="" <?= !empty($data['importExecutionInfo'])?"disabled":"" ?> onclick="$('#import_file').click();">上传文件</button>
            	<button id="ensogo-file-export-publish-fail-btn" class="iv-btn btn-info" style="margin-left: 30px;<?= $displayPublishFailBtn;?>" onclick="window.open('/listing/ensogo-offline/download-error-listing-to-excel','_blank');">导出发布失败商品</button>
            	<input id="import_file" type="file" class="" style="display: none;" <?= !empty($data['importExecutionInfo'])?"disabled":"" ?> onchange="importFile(this)"/>
            </p>
            
            <p class="import-loading">
            	<?php if(!empty($data['importExecutionInfo'])): ?>
             	<?php $additionalInfo = json_decode($data['importExecutionInfo']['additional_info'],true);?>
            	<IMG alt="loading" src="/images/loading2.gif" width="16" height="16">文件 <?= empty($additionalInfo['originFileName'])?$additionalInfo['fileName']:$additionalInfo['originFileName']; ?>中的商品正在导入到ensogo
            	<?php endif;?>
            </p>
        </div>
      </div>
    
    </div>
	<div id="" class="store-move-result">
		<TABLE id="result-table" class="table table-border">
			<THEAD>
				<TR>
					<TH colspan="4">搬家结果</TH>
				</TR>
			</THEAD>
			<TBODY>
				<tr>
					<td style="width:20%">店铺</td>
				    <td style="width:15%">父SKU</td>
				    <td style="width:15%">子SKU</td>
				    <td style="width:50%" class="publish_status">状态</td>
				</tr>
				
				<?php 
				if(!empty($data['listingExecutionInfo'])):
				foreach ($data['listingExecutionInfo'] as $listingInfo):
				?>
				<tr class="result-item">
					<td><?=$listingInfo['ensogo_store'] ?></td>
				    <td><?=$listingInfo['parent_sku'] ?></td>
				    <td><?=$listingInfo['sku'] ?></td>
				    <?php if($listingInfo['status'] == 1 || $listingInfo['status'] == 2):?>
				    <td style="" class="publish_status text-warning">发布中</td>
				    <?php elseif($listingInfo['status'] == 3):?>
				    <td style="" class="publish_status text-success">发布成功</td>
				    <?php elseif($listingInfo['status'] == 4):?>
				    <td style="" class="publish_status text-danger">发布失败  <?= $listingInfo['error_message']?></td>
				    <?php endif;?>
				</tr>
				
				<?php 
				endforeach;
				endif;
				?>
			</TBODY>
		</TABLE>
	</div>
</div>

<script type="text/javascript">
function ensongoStoreMoveByFileReady(){
	if (typeof ensongoStoreMoveByFile === 'undefined')  ensongoStoreMoveByFile = new Object();
	ensongoStoreMoveByFile.toCheckDownload = [];
	ensongoStoreMoveByFile.addHtmlJq = {};
	ensongoStoreMoveByFile.downLoadSeed = {};// 检查下载任务id
	ensongoStoreMoveByFile.publishSeed = false;// 检查刊登任务id

	// 添加检查下载任务
	$('#ali-listing-download-result>div[date-alicount]:odd').each(function(){
		if($.trim($(this).html()) == ""){// 判断如果第二行没有下载按钮就认为是 需要检查结果。
			ensongoStoreMoveByFile.toCheckDownload.push($(this).attr('date-alicount'));
			ensongoStoreMoveByFile.addHtmlJq[$(this).attr('date-alicount')] = $(this).parent().find('div[date-alicount="'+$(this).attr('date-alicount')+'"]');
			checkIsDownloadFinish($(this).attr('date-alicount'));
		}
	});

	if(ensongoStoreMoveByFile.toCheckDownload.length > 0){// 屏蔽导入
		$('#select-export-alistore').attr('disabled',true);
		$('#select-import-ensogostore').attr('disabled',true);
		$('#ensogo-file-export-btn').attr('disabled',true);
	}

	// 添加检查导入任务
	if($.trim($('.import-loading').html()) != ""){
		checkIsPublishFinish();
	}
	
	$('#ensogo-file-export-btn').click(function(){
		if($('#select-export-alistore').val() == ""){
			alert("请先选择要搬家的速卖通店铺");
		}else if($('#select-import-ensogostore').val() == ""){
			alert("请先选择要搬家的目标店铺");
		}else{
			if($(this).hasClass('download-excel')){
				requestFile($('#select-export-alistore').val(),$('#select-import-ensogostore').val(),"excel");
			}

			if($(this).hasClass('download-csv')){
				requestFile($('#select-export-alistore').val(),$('#select-import-ensogostore').val(),"csv");
			}
		}	
	});
}


function requestFile(aliAccount,ensogoAccount,type){
	// 屏蔽导入
	$('#select-export-alistore').attr('disabled',true);
	$('#select-import-ensogostore').attr('disabled',true);
	$('#ensogo-file-export-btn').attr('disabled',true);

	ensongoStoreMoveByFile.toCheckDownload.push(aliAccount);
	
	if($('[date-alicount="'+aliAccount+'"]').length > 0){
		$('[date-alicount="'+aliAccount+'"]').eq(0).html('<IMG alt="loading" src="/images/loading2.gif" width="16" height="16"> 店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;的商品正在组织，请耐心等待。');
	}else{
		var addHtml = "";
		addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'"><IMG alt="loading" src="/images/loading2.gif" width="16" height="16"> 店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;的商品正在组织，请耐心等待。</div>';
		addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'"></div>';
		addHtmlJq = $(addHtml);
		$('#ali-listing-download-result').append(addHtmlJq);
	}
	
	$.ajax({
		type: "POST",
		url: "/listing/ensogo-offline/export-ali-listing",
		data: {ali_account:aliAccount,ensogo_account:ensogoAccount,type:type},
		dataType:'json',
		success: function(result){
			if(result.code == 200){ // 200 导出未完成
				checkIsDownloadFinish(aliAccount);
				// 保存这个变量 addHtmlJq,以防出错时候不能消除 loading 图样
				ensongoStoreMoveByFile.addHtmlJq = addHtmlJq;
				
// 				bootbox.alert(result.message);
			}else if(result.code == 201){// 201 导出已完成
				// 显示下载链接
				if($('[date-alicount="'+aliAccount+'"]').length > 0){
					$('[date-alicount="'+aliAccount+'"]').eq(0).html('店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;数据组织完成。');
					$('[date-alicount="'+aliAccount+'"]').eq(1).html('<button type="button" class="btn btn-default" onclick="downloadFile(\''+aliAccount+'\')">下载</button>');
				}else{
					var addHtml = "";
					addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'">店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;数据组织完成。</div>';
					addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'"><button type="button" class="btn btn-default" onclick="downloadFile(\''+aliAccount+'\')">下载</button></div>';
	 				$('#ali-listing-download-result').append(addHtml);
				}

			}else{// 400 导出失败
				if(typeof addHtmlJq == "object" ){
					checkIsShowDownloadButton($(addHtmlJq).eq(0).attr('date-alicount'));
					$(addHtmlJq).html('');// 清空添加的等待HTML
				}
				
				bootbox.alert(result.message);
			}
		},
		error:function(a,b,c,d,e){
			if(typeof addHtmlJq == "object" ){
				checkIsShowDownloadButton($(addHtmlJq).eq(0).attr('date-alicount'));
				$(addHtmlJq).html('');// 清空添加的等待HTML
			}
			bootbox.alert("网络错误！");
		}
	});
}

function checkIsDownloadFinish(aliAccount){
	// 支持检查多个任务
	ensongoStoreMoveByFile.downLoadSeed[aliAccount] = setInterval(function(){
		$.ajax({
			type: "get",
			url: "/listing/ensogo-offline/check-export-ali-listing",
			data: {ali_account:aliAccount},
			dataType:'json',
			success: function(result){
				if(result.code == 200){ // 200 导出未完成
					
				}else if(result.code == 201){// 201 导出已完成
					// 显示下载链接
					if($('[date-alicount="'+aliAccount+'"]').length > 0){
						$('[date-alicount="'+aliAccount+'"]').eq(0).html('店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;数据组织完成。');
						$('[date-alicount="'+aliAccount+'"]').eq(1).html('<button type="button" class="btn btn-default" onclick="downloadFile(\''+aliAccount+'\')">下载</button>');
					}else{
						var addHtml = "";
						addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'">店铺 &nbsp;<strong style="font-weight: bold;">'+aliAccount+'</strong>&nbsp;数据组织完成。</div>';
						addHtml += '<div style="flex: 0 0 440px;" date-alicount="'+aliAccount+'"><button type="button" class="btn btn-default" onclick="downloadFile(\''+aliAccount+'\')">下载</button></div>';
		 				$('#ali-listing-download-result').append(addHtml);
					}

					checkIsShowDownloadButton(aliAccount);
					clearInterval(ensongoStoreMoveByFile.downLoadSeed[aliAccount]);
					
				}else{// 400 导出失败
					if(typeof ensongoStoreMoveByFile.addHtmlJq == "object"){
						var addHtmlJq = ensongoStoreMoveByFile.addHtmlJq[aliAccount];
						checkIsShowDownloadButton($(addHtmlJq).eq(0).attr('date-alicount'));
						$(addHtmlJq).html('');// 清空添加的等待HTML
					}

					clearInterval(ensongoStoreMoveByFile.downLoadSeed[aliAccount]);
					bootbox.alert(result.message);
				}
			},
			error:function(a,b,c,d,e){
				if(typeof ensongoStoreMoveByFile.addHtmlJq[aliAccount] == "object"){
					var addHtmlJq = ensongoStoreMoveByFile.addHtmlJq[aliAccount];
					checkIsShowDownloadButton($(addHtmlJq).eq(0).attr('date-alicount'));
					$(addHtmlJq).html('');// 清空添加的等待HTML
				}
				clearInterval(ensongoStoreMoveByFile.downLoadSeed[aliAccount]);
				bootbox.alert("网络错误！");
			}
		});
	},3000);
}

function downloadFile(aliAccount){
	window.open('/listing/ensogo-offline/download-listing-to-excel?ali_account='+aliAccount,'_blank');
	if($('[date-alicount="'+aliAccount+'"]').length > 0){
		$('[date-alicount="'+aliAccount+'"]').remove();// 去除下载链接
	}else{// 没有就刷新页面
		window.location.reload();
	}
}

function importFile(input){
	// get browser info
	var Sys = {};
	if(navigator.userAgent.indexOf("MSIE")>0) {
		Sys.ie = true;
		var version = navigator.userAgent.split(";"); 
		var trim_Version = version[1].replace(/[ ]/g,""); 
		Sys.ieVersion = trim_Version;
	}
	
	var file = $(input).not('.done');
	if( Sys.ie &&  Sys.ieVersion != "MSIE10.0"){// for lt IE 9
		var uploadFile = $(file).val();
	}else{
		var uploadFile = file[0].files[0];
	}

	$(input).val('');
	$(input).attr('disabled',true);
	$('#import_file_btn').attr('disabled',true);
	$('#ensogo-file-export-publish-fail-btn').hide();
	
	// 获取非空input 内容
	if(uploadFile){
		$('.import-loading').html('<IMG alt="loading" src="/images/loading2.gif" width="16" height="16">文件'+uploadFile.name+' 中的商品正在导入到ensogo');
		 
		$.ajaxFileUpload({  
			 url:"/listing/ensogo-offline/import-ensogo-listing-from-excel", 
		     uploadFile : uploadFile,//通过input 元素 change事件获取的 file 对象 或 上传文件的文件名
		     fileName:'input_import_file',
		     dataType: 'json',//返回数据的类型
		     isNotCheckFile : true,
		     success: function (result , status , context){ 
		    	 if(result.code == 200){
			    	 $('.result-item').remove();// 手工去除上一批刊登结果
			    	 checkIsPublishFinish();
				}else{
					$('.import-loading').html('');
					$('#import_file').attr('disabled',false);
					$('#import_file_btn').attr('disabled',false);
					$('#ensogo-file-export-publish-fail-btn').show();
					
					bootbox.alert(result.message);
				}
		     },
		     error: function( xhr, status, e ){
		    	 $('.import-loading').html('');
		    	 $('#import_file').attr('disabled',false);
		    	 $('#import_file_btn').attr('disabled',false);
		    	 $('#ensogo-file-export-publish-fail-btn').show();
		    	 bootbox.alert("网络错误！");
		     }
		});
	}
}

function checkIsPublishFinish(){
	// 支持检查多个任务
	ensongoStoreMoveByFile.publishSeed = setInterval(function(){
		$.ajax({
			type: "get",
			url: "/listing/ensogo-offline/check-import-ali-listing",
			data: {},
			dataType:'json',
			success: function(result){
				if(result.code == 200){ // 200 导入未完成
					
				}else if(result.code == 201){// 201 导入已完成
					$('#import_file').attr('disabled',false);
					$('#import_file_btn').attr('disabled',false);
			    	$('#ensogo-file-export-publish-fail-btn').show();
					$('.import-loading').html('');
					// 导出刊登失败listing button
					clearInterval(ensongoStoreMoveByFile.publishSeed);

					// 组织结果
					var addHtml = '';

					for(var i=0 ; i<result.data.length ; i++){
						addHtml += '<tr class="result-item">';
						addHtml += '<td>'+result.data[i]['ensogo_store']+'</td>';
						addHtml += '<td>'+result.data[i]['parent_sku']+'</td>';
						addHtml += '<td>'+result.data[i]['sku']+'</td>';
						if(result.data[i]['status'] == 1 || result.data[i]['status'] == 2){
							addHtml += '<td style="" class="publish_status text-warning">发布中</td>';
						}else if(result.data[i]['status'] == 3){
							addHtml += '<td style="" class="publish_status text-success">发布成功</td>';
						}else if(result.data[i]['status'] == 4){
							if(!result.data[i]['error_message'])
								var errMsg = "";
							else
								var errMsg = result.data[i]['error_message']
							addHtml += '<td style="" class="publish_status text-danger">发布失败 '+errMsg+'</td>';
						}
						addHtml += '</tr>';
					}
					$('#result-table > tbody').append(addHtml);
				}else{// 400 导入失败
					$('.import-loading').html('');
					$('#import_file').attr('disabled',false);
					$('#import_file_btn').attr('disabled',false);
			    	$('#ensogo-file-export-publish-fail-btn').show();
					clearInterval(ensongoStoreMoveByFile.publishSeed);
					bootbox.alert(result.message);
				}
			},
			error:function(a,b,c,d,e){
				$('.import-loading').html('');
				$('#import_file').attr('disabled',false);
				$('#import_file_btn').attr('disabled',false);
		    	$('#ensogo-file-export-publish-fail-btn').show();
				clearInterval(ensongoStoreMoveByFile.publishSeed);
				bootbox.alert("网络错误！");
			}
		});
	},3000);
}

function checkIsShowDownloadButton(aliAccount){
	var newToCheckDownload = [];
	for(var i=0; i<ensongoStoreMoveByFile.toCheckDownload.length ; i++){
		if(ensongoStoreMoveByFile.toCheckDownload[i] != aliAccount){
			newToCheckDownload.push(ensongoStoreMoveByFile.toCheckDownload[i]);
		}
	}

	ensongoStoreMoveByFile.toCheckDownload = newToCheckDownload;
	if(ensongoStoreMoveByFile.toCheckDownload.length == 0){// 屏蔽导入
		$('#select-export-alistore').attr('disabled',false);
		$('#select-import-ensogostore').attr('disabled',false);
		$('#ensogo-file-export-btn').attr('disabled',false);
	}
}
</script>


