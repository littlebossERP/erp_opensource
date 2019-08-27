<?php 
use yii\helpers\Html;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>美图秀秀开放平台</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	<meta name="description" content="美图WEB开放平台是国内首个图片处理工具类型的开放平台，为了助力各大网站发展，美图秀秀在线功能全部免费开放！包括美图秀秀完整版（集美化图片、人像美容及拼图功能为一体）、美图秀秀美化图片、美图秀秀拼图。开发者可以同时使用三款插件，也可以任意选择一款插件使用。" />
	<meta name="keywords" content="美图秀秀网页版,开放平台,开放api,开放接口,在线图片处理,图片处理接口,flash">
	
	
	<script src="http://open.web.meitu.com/sources/xiuxiu.js" type="text/javascript"></script>
	<?php 
	$this->registerJsFile(\Yii::getAlias('@web')."/js/bootbox.min.js", ['depends' => ['yii\bootstrap\BootstrapAsset'],'position' => \yii\web\View::POS_BEGIN]);
	?>
	<style type="text/css">
		html, body { height:100%;}
		body { margin:0; }
	</style>
</head>
<body>
	<?=Html::hiddenInput('imgurl',$imgurl,['id'=>'imgurl'])?>
	<?=Html::hiddenInput('newurl',$newurl,['id'=>'newurl'])?>
	<div id="flashEditorOut">
        <div id="altContent2">
            <h1>美图秀秀2</h1>
        </div>
	</div>
</body>
<script type="text/javascript">
	// 获取窗口宽度
	if (window.innerWidth)
	winWidth = window.innerWidth;
	else if ((document.body) && (document.body.clientWidth))
	winWidth = document.body.clientWidth;

	// 获取窗口高度
	if (window.innerHeight)
	winHeight = window.innerHeight;
	else if ((document.body) && (document.body.clientHeight))
	winHeight = document.body.clientHeight;

	//获取url中的参数值
	function getQueryString(name) { 
		var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
		var r = window.location.search.substr(1).match(reg); 
		if (r != null) return unescape(r[2]); return null; 
	} 
	var oldimgurl = document.getElementById('imgurl').value;
	var newimgrul = document.getElementById('newurl').value;
	//var images = getQueryString('url');
	
	xiuxiu.setLaunchVars("customMenu", [{"decorate":['basicEdit', 'inpaint', 'trinket', 'text', 'particle', 'effect', 'border', 'magic', 'localFixes']},"facialMenu"]);
	xiuxiu.embedSWF("altContent2", 3, winWidth, winHeight);

	xiuxiu.onInit = function (id)
	{
        xiuxiu.setUploadURL("http://"+window.location.host+"/imageeditor/default/upload-from-meitu");
        xiuxiu.setUploadType(2);
        xiuxiu.setUploadDataFieldName("product_photo_file");

        
        xiuxiu.loadPhoto(newimgrul);
	}
	
	xiuxiu.onUploadResponse = function (data)
	{
        //clearFlash();
		result = eval("("+data+")");
        if(result.status){
        	imageUrl = result.data.original;
     		window.opener &&  window.opener.refresholdImage && window.opener.refresholdImage(oldimgurl,imageUrl);
     		alert('上传成功');
        }else{
			alert('上传失败');
        }
	}
	
	xiuxiu.onDebug = function (data)
	{
        alert("错误响应" + data);
	}
	
	xiuxiu.onClose = function (id)
	{
        //alert(id + "关闭");
        clearFlash();
	}
	
	//清除flash
    function clearFlash()
    {
        document.getElementById("flashEditorOut").innerHTML='<div id="flashEditorContent"><p><a href="http://www.adobe.com/go/getflashplayer"><img alt="Get Adobe Flash player" src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif"></a></p></div>';
    }
	</script>
</html>