<?php
        
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use eagle\assets\AppAsset;
use frontend\widgets\Alert;
use yii\jui\JuiAsset;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\MenuHelper;
use eagle\modules\app\apihelpers\AppApiHelper;

use eagle\models\UserNotice;

//  app参数设置
$this->registerJs('
			$("#app-configset-a").click(function(){
				appKey=$(this).parent().attr("appkey");
				appName=$(this).parent().attr("appname");
				$.get( global.baseUrl+"app/appconfig/view?key="+appKey,
						 function (data){
								$.hideLoading();
								bootbox.dialog({
									closeButton: true, 
									className: "app-configset-modal", 
									backdrop: false,
									title : appName,									
									buttons: {  
										Cancel: {  
					                        label: Translator.t("返回"),  
					                        className: "btn-default",  
					                    },
					                    success: {
					                        label:"保存",
					                        className:"appconfig-save-btn btn-info",
					                        callback: function() {
					                        //  return false; //不要自动关闭弹出的对话框
					                        }
					                    }
					                   
									},
								    message: data,
								});	
					});				
			});			
		
		', \yii\web\View::POS_READY);




//lolo20151008 menu for 授权网站只有小老板logo，没有其他菜单选项
//这里 授权网站是单独部署

if (isset(\Yii::$app->params["appName"]) and \Yii::$app->params["appName"]=="shop" ){
	NavBar::begin([
	//             'brandLabel' => 'Home',
	'brandLabel' => '', // 为显示logo图片，这里不要写内容
	'brandUrl' => Yii::$app->homeUrl,
	'renderInnerContainer' => false, // 改成宽度100%
	'options' => [
	'class' => 'navbar-inverse navbar-fixed-top',
	],
	]);
	
	echo Nav::widget([
			'options' => ['class' => 'navbar-nav navbar-left'],
			'items' =>[],
			]);
	NavBar::end();
	return;	
}


//菜单一共分成2行。
//第一行分成左边的一级菜单和右边的账号菜单
//第二行分成左边的二级菜单和右边的app参数设置
NavBar::begin([
//             'brandLabel' => 'Home',
			'brandLabel' => '', // 为显示logo图片，这里不要写内容
            'brandUrl' => Yii::$app->homeUrl,
            'renderInnerContainer' => false, // 改成宽度100%
             'options' => [
                  'class' => 'navbar-inverse navbar-fixed-top',
              ],
]);
    

if (isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ){ // dzt20150523
	$firstLevelhtmlArr = ['<li>
								<div><a style="line-height: 50px;" href="/"><img border="0" src="/images/tracker-logo.png" alt="" title=""></a></div>
			 				</li>'];
// 	$firstLevelhtmlArr = [['label' => '物流跟踪','url' => ['/'] , 'options' => ['class' => 'active']]];
}else{
	if (Yii::$app->user->isGuest) {
		$firstLevelhtmlArr=[];
	}else{
		//获取整理后的菜单
		list($firstLevelhtmlArr,$currentSecondLevelMenuItems)=MenuHelper::getMenu();
		
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			// 覆盖原本的menu ，显示所有menu 合并app ,order menu
			$firstLevelhtmlArr = AppApiHelper::genMenuWithLv2Menu();
		}
	}

}



//1. 第一行 左边的一级菜单
echo Nav::widget([
'options' => ['class' => 'navbar-nav navbar-left'],
//		'items' => $appMenuItems,
'items' => $firstLevelhtmlArr,
]);



//2. 第一行 右边的账号菜单
$menuItems = [
// 	['label' => 'About', 'url' => ['/site/about']],
// 	['label' => 'Contact', 'url' => ['/site/contact']],
];
 
if (isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ){ // dzt20150523
	if (Yii::$app->user->isGuest) {
		// 这个登录注册界面都是在ealge2的 ，tracker 登录注册都需要返回主页
		if(isset(\Yii::$app->params["isBanSiteLogin"]) && \Yii::$app->params["isBanSiteLogin"] == 0){// 根据配置项决定是否屏蔽site/login
			$menuItems[] = ['label' => 'Signup', 'url' => ['/site/register-view']];
			$menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
		}
	} else {
// 		$menuItems[] = [
// 		'label' => 'Logout (' . Yii::$app->user->identity->username . ')',
// 		'url' => ['/site/logout'],
// 		'linkOptions' => ['data-method' => 'post']
// 		];

		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='test' ){ // dzt20150625
			$userNotice = UserNotice::find()->where(['puid'=>\Yii::$app->user->identity->getParentUid()])->orderBy(['update_time'=>SORT_DESC])->limit(1)->all();
			foreach ($userNotice as $usernotice){
				$menuItems[] = '<li>
									<a href="'.empty($usernotice->url)?'javascript:void(0)':$usernotice->url.'" style="color:#0088CC;">'.$usernotice->content.'</a>
			 					</li>';
			}
			$menuItems[] = '<li>
								<a href="/basic/show-notes">更新日志</a>
			 				</li>';
		}
		
		$menuItems[] = '<li>
							<a target="_blank" href="http://wpa.qq.com/msgrd?v=3&amp;uin=3041145477&amp;site=qq&amp;menu=yes"><span class="egicon-qq-icon"></span>QQ交谈</a>
		 				</li>';
		$menuItems[] = '<li>
		 					<a target="_blank" href="http://shang.qq.com/wpa/qunwpa?idkey=50a62755bf4d6941a0315a4eb33cca6902a000147f947f7b406b62e7aab68da1"><span class="egicon-qqun-icon"></span>加入QQ群</a>
		 				</li>';
		$menuItems[] = '<li class="weixin-qr-code">
			 				<a href="javascript:void(0)"><span class="egicon-weixin-icon"></span>关注微信</a>
							<div class="weixin-qr-code-div"><img border="0" src="/images/weixin-qr-code.png" alt="关注微信" title="关注微信"></div>
						</li>';
		
		// 账号管理内的选项，目前只有“退出”这个选项
		$accountSettingItems = [];
		//$accountSettingItems[] = array('label' => '工单' , 'url' =>  ['/ticket/ticket'],'linkOptions' => ['data-method' => 'post']);
		$accountSettingItems[] = array('label' => '修改账号信息' , 'url' =>  ['/permission/user/account-edit'],'linkOptions' => ['data-method' => 'post']);
		$accountSettingItems[] = array('label' => '退出' , 'url' =>  ['/site/logout'],'linkOptions' => ['data-method' => 'post']);
		
		$menyHeadIconHtml = '<span class="egicon-head_portrait_icon"></span>';
		
		
		$menuItems[] = [
			'label' => $menyHeadIconHtml,//label写HTML需要 在nav 配置'encodeLabels'=>false,
			'items' => $accountSettingItems,
			'options' => ['class'=>'account-setting'],
		];
		
		// 设置按钮
		$menuItems[] = ['label' => '<span class="egicon-setting-icon"></span>', 'url' => AppApiHelper::getAutoLoginPlatformBindUrl() ,  'options'=>['class'=>'nav-setting' ] , 'linkOptions'=>['target'=>'_blank']];
	
	}
}else if(isset(\Yii::$app->params["isOnlyForDemo"]) and \Yii::$app->params["isOnlyForDemo"]==1){
	if (Yii::$app->user->isGuest) {
		// $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
	} else {
// 		$menuItems[] =['label' => TranslateHelper::t('ebay账号绑定'), 'url' => ['/platform/ebay-accounts/list']];// dzt20150710

//$menuItems[] =['label' => \Yii::$app->user->identity->username, 'url' => ['/platform/platform/all-platform-account-binding']];
	//	$menuItems[] =['label' => TranslateHelper::t('平台绑定'), 'url' => ['/platform/platform/all-platform-account-binding']];
	    $menuItems[]=AppApiHelper::getPlatformMenu();
		
		// 账号管理内的选项，目前只有“退出”这个选项
		$personalItems = [];
	//	$personalItems[] = array('label' => '工单' , 'url' =>  ['/ticket/ticket'],'linkOptions' => ['data-method' => 'post']);
		$personalItems[] = array('label' => '修改账号信息' , 'url' =>  ['/permission/user/account-edit'],'linkOptions' => ['data-method' => 'post']);
		$personalItems[] = array('label' => '退出' , 'url' =>  ['/site/logout'],'linkOptions' => ['data-method' => 'post']);
		
		$menyHeadIconHtml = '<span class="egicon-head_portrait_icon"></span>';
		 
		$menuItems[] = [
		'label' => $menyHeadIconHtml,//label写HTML需要 在nav 配置'encodeLabels'=>false,
		'items' => $personalItems
		];
	}
}else{
	if (Yii::$app->user->isGuest) {
		if(isset(\Yii::$app->params["isBanSiteLogin"]) && \Yii::$app->params["isBanSiteLogin"] == 0){// 根据配置项决定是否屏蔽site/login
			$menuItems[] = ['label' => 'Signup', 'url' => ['/site/register-view']];
			$menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
		}
	} else {
		$menuItems[] =['label' => TranslateHelper::t('平台绑定'), 'url' => ['/platform/platform/all-platform-account-binding']];
// 		$menuItems[] =['label' => TranslateHelper::t('app管理'), 'url' => ['/app/app/installed-list']];
		
		// 账号管理内的选项，目前只有“退出”这个选项
		$personalItems = [];
	//	$personalItems[] = array('label' => '工单' , 'url' =>  ['/ticket/ticket'],'linkOptions' => ['data-method' => 'post']);
		$personalItems[] = array('label' => '修改账号信息' , 'url' =>  ['/permission/user/account-edit'],'linkOptions' => ['data-method' => 'post']);
		$personalItems[] = array('label' => '退出' , 'url' =>  ['/site/logout'],'linkOptions' => ['data-method' => 'post']);
		
		$menyHeadIconHtml = '<span class="egicon-head_portrait_icon"></span>';
		
		
		$menuItems[] = [
		'label' => $menyHeadIconHtml,//label写HTML需要 在nav 配置'encodeLabels'=>false,
		'items' => $personalItems
		];
 	}
}
       
//多语言选择菜单
$mutiLanItems = array();
foreach (TranslateHelper::$_translate_lan_type as $key => $value){
	$mutiLanItems[] = array('label' => $value , 'url' => '#','linkOptions' =>['onclick' => 'changeLanguage("'.$key.'")']);
}
		
// $menuItems[] = [
// 	'label' => 'Language',
// 	'items' => $mutiLanItems,
// ];
echo Nav::widget([
	'options' => ['class' => 'navbar-nav navbar-right'],
	'items' => $menuItems,
	'encodeLabels'=>false,
]);
NavBar::end();

            
            
         //3. 第二行的左边的二级菜单
         
if (!Yii::$app->user->isGuest && (!isset(\Yii::$app->params["isOnlyForOneApp"]) or \Yii::$app->params["isOnlyForOneApp"]==0 )):

        ?>
		
		
	<!--  二级菜单 -->
	<div id="second-level-menu-div" class="container-fluid navbar-collapse" style="margin-top: 0px">
		<ul id="w1" class="navbar-nav navbar-left nav">
		
		<?php foreach ($currentSecondLevelMenuItems as $secondMenuLabel=>$secondMenuItem): 
		         if (isset($secondMenuItem["subMenu"])){
                    ?>
                     <li class="dropdown">            
                          <a href="<?=$secondMenuItem["url"] ?>" class="dropdown-toggle" data-toggle="dropdown"><?=$secondMenuLabel ?><b class="caret"></b></a>
                          <ul class="dropdown-menu">
                          <?php
                          //三级菜单
                            $thirdMenuItems=$secondMenuItem["subMenu"];
                            foreach($thirdMenuItems as $thirdMenuLabel=>$thirdMenuItem){
                              ?>
                              <li><a href="<?=$thirdMenuItem["url"] ?>"><?=$thirdMenuLabel ?></a></li>
                              <?php 
                           }
                          
                          ?>
                          
                          </ul>
                    </li>
                    <?php                     

                  }   //end if isset
                  else{ ?>
                  <li >    
                      <a href="<?=$secondMenuItem["url"] ?>" ><?=$secondMenuLabel ?></a>
                  </li>    
                 <?php  } 
                  
		endforeach; ?>
		</ul>
		
		<?php
		//4. 第二行的右边的app参数配置
        $currentKey=AppApiHelper::getCurrentAppKey();
        
        if(false){// 目前屏蔽app配置
        
        if ($currentKey<>""){
		?>
       <ul id="w2" class="navbar-nav navbar-right nav">
            <li appkey="<?=$currentKey?>" appname="<?=AppApiHelper::getCurrentAppLabel() ?>"   > 
                <a id="app-configset-a" href="#" >app配置</a>
            </li>   
       </ul>		
	   <?php 
	    }
	    }
	   ?>
	
	</div>
     <?php endif; ?>     
          
          
          
          
          