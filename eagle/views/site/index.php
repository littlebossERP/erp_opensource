<?php
use \eagle\modules\platform\apihelpers\PlatformAccountApi;
use \eagle\modules\app\apihelpers\AppApiHelper;
/* @var $this yii\web\View */
$this->title = '欢迎使用小老板';
$platformsArr = array("aliexpress");
$ensogo_platformsArr = array("ensogo");
$is_aliexpress = PlatformAccountApi::getAllPlatformBindingSituation($platformsArr);
$is_ensogo = PlatformAccountApi::getAllPlatformBindingSituation($ensogo_platformsArr);;
if($is_aliexpress['aliexpress']){
    $favourableComment_url = "/comment/comment/rule";
    $pressMoney_url = "/assistant/rule/list";
}else{
    $favourableComment_url = AppApiHelper::getAutoLoginPlatformBindUrl();
    $pressMoney_url = AppApiHelper::getAutoLoginPlatformBindUrl();
}
if($is_ensogo['ensogo']){
    $ensogo_url = "/listing/ensogo-offline/ensogo-post";
}else{
    $ensogo_url = AppApiHelper::getAutoLoginPlatformBindUrl();
}
?>
<style>
#page-content{
	background:url(/images/ensogo/big-background.jpg) no-repeat fixed; 
	background-size: cover;
	background-position:center center;
}
.main-width{
	width:962px;
	height:600px;
	margin-left:auto;
	margin-right:auto;
	margin-top:4%;
}
.left-picture{
	float:left;
}
.IconLocation{
	position:relative;
	right:4px;
}
.break{
	text-align: justify;
}


.view-left-top-right-bottom-content {
    position: relative;
    width: 380px;
    color: white;
    top: -55px;
    font-size: 14px;
    left: 20px;
    line-height: 20px;
}

.view-left-bottom {
    width: 635px;
    height: 220px;
    background-color: #c70b45;
    position: relative;
    top: 300px;

}
.index-container {
    height: 600px;
    width: 1300px;
    margin-left: auto;
    margin-right: auto;
	margin-top: 100px;
}
.center{
	margin-left: auto;
    margin-right: auto;
}
.cloud-background{
    position: relative;
	width:638px;
	height:521px;
	background: url(/images/index/cloudDashbord.png) no-repeat;
}
.button-background{
    position: absolute;
	color:red;
}
.clound-input{
  font-size: 30px;
  color: rgb( 255, 255, 255 );
  width:403px;
  height:52px;	
  background: url(/images/index/cloudButton.png) no-repeat;
  border:0px;
  margin-top:417px;
  border-radius: 27px;
  margin-left:119px;
}

/* 左边div star*/
.Ad-cloud-background{
    position: relative;
	width:638px;
	height:521px;
	background: url(/images/index/linioRegisterBackground.png) no-repeat;
}
.Ad-cloud-logo{
	position: absolute;
	top:74px;
	left:241px;
}
.Ad-cloud-title{
	position: absolute;
	top:171px;
	left:65px;
}
.Ad-cloud-button{
    position: absolute;
    right: 145px;
    bottom: 160px;
}
/* 左边div end*/
</style>

<script type="text/javascript"> 
window.onload = function() {
if (document.getElementById("index-container").clientWidth-document.documentElement.clientWidth>0){
	//div大于 当前屏幕的宽度 则移动到中间
	window.scrollTo((document.getElementById("index-container").clientWidth-document.documentElement.clientWidth)/2,0);
}

} 

</script> 
<?= eagle\modules\util\helpers\TopMessage::getMessage(false);?>
<div id="index-container" class="index-container">
	<div class="view-left pull-left">
        <a href="/platform/platform/linio-register-page" target="_blank">
            <div class="Ad-cloud-background">
              <div class="Ad-cloud-logo"><img src="/images/index/linioRegisterLogo.png"></div>
              <div class="Ad-cloud-title"><img src="/images/index/linioRegisterTitle.png"></div>
              <div class="Ad-cloud-button"><img src="/images/index/linioRegisterButton.png"></div>
            </div>
        </a>
	</div>

	<div class="view-right pull-right">
		<div>
		  <a href="/platform/platform/cdiscount-register-page" target="_blank"><img src="/images/cdiscount/cdiscountDashbord.png"></a>
		</div>
	</div>
</div>
