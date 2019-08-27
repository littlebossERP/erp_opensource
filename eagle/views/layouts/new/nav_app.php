<?php 
use eagle\modules\util\helpers\MenuHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;

$menu = NULL;
if ( Yii::$app->user->isGuest ){
}else{
    $t1 = eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
	$menu = MenuHelper::getTopMenu();
	$t2 = eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
		
	// debug log
	eagle\modules\util\helpers\SysBaseInfoHelper::addFrontDebugLog("MenuHelper::getTopMenu t=".($t2-$t1));
}

// echo json_encode($menu);
//die;
?>

<nav class="top_nav flex-row space-between">
	<div class="item-left flex-row">
		<div class="logo flex" style="margin-right:33px">
			<!-- logo for erp -->
			<a class="brand flex-row center" href="/">
				<img src="/images/logos/littleboss-erp.png" alt="首页" title="首页" width="120" />
			</a>
		</div>
		<?php if($menu): ?>
		<ul class="flex-row">
			<?php 
			foreach($menu['menuData'] as $name=>$param){
				?>
				<li class="top_nav_menu" style="margin-right:11px">
					<?php
					$dropdown = new \render\form\select\Dropdown();
					// $dropdown = new \render\form\select\BsDropdown();
					$dropdown->lists = [];
					$dropdown->value = [];
					$dropdown->window = isset($param['target'])?$param['target']:'';
					if(isset($param['subMenu'])){
						foreach($param['subMenu'] as $key=>$v){
							if(isset($v['isMatch']) && $v['isMatch']){
								$dropdown->value[] = $v['url'];
							}
							$dropdown->lists[$key] = $v;
						}
					}
					$dropdown->target = 'top_nav';
					$dropdown->title = $name;
					$dropdown->href = $param['url'];
					if(isset($param['isMatch']) && $param['isMatch']){
						$dropdown->value[] = $param['url'];
					}
					$dropdown->addClass('main-link');

					
					echo $dropdown;
					?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php endif; ?>
		
	</div>
	<?php if($menu): 
	//是否子账号
	$isMainAccount = UserApiHelper::isMainAccount();
	?>
	<div class="item-right">
		<ul class="user_profile flex-row">
			 <?php // 小老板显示平台绑定?>
			<li class="platform-bind">
				<?php 
				//当是主账号时，才显示平台授权，20170614_lrq
				if($isMainAccount){
					// 绑定平台
					list($url,$label) = AppApiHelper::getPlatformMenuData();
					?>
					<a target="_blank" href="<?=$url ?>"><?=$label ?></a>
				<?php }?>
			</li>

			<li>
				<?php
				
				$dropdown = new \render\form\select\Dropdown();
				$dropdown->align = 'left';
				$dropdown->target = 'top_nav_right';

			    
			    if($isMainAccount){
			        $dropdown->lists = [
			               
			                '子账号管理'=>'/permission/user/list',
							'操作日志'=>'/permission/user/operation-log',
			                '修改账号信息'=>'/permission/user/account-edit',
			                '退出'=>[
			                        'href'=>'/site/logout',
			                        'data-method'=>'post'
			                ]
			        ];
			    }else{
			        $dropdown->lists = [
							'操作日志'=>'/permission/user/operation-log',
			                '修改账号信息'=>'/permission/user/account-edit',
			                '退出'=>[
			                        'href'=>'/site/logout',
			                        'data-method'=>'post'
			                ]
			        ];
			    }
				

				
				// $dropdown->title = "头像";
				$dropdown->title = '<img src="/images/User-image.gif" alt="" />';
				$dropdown->href = '#';
				echo $dropdown;
				?>
			</li>
			<li>
				<a target="_blank" href="//help.littleboss.com/word_list.html">帮助中心</a>
			</li>
		</ul>
	</div>
	<?php endif; ?>
</nav>

<!-- QQ交谈 -->
<div class="about_qq flex-row hidden">
	<div class="slide-toggle" status-class="['slide-close','slide-open']" status="qqtalk"></div>
	<div class="toggle-group" status-hide="qqtalk" >
		<a target="_blank" class="hidden" href="#"><img border="0" src="/images/qqtalk.png" />QQ交流<br>工作时间</a>
		<a target="_blank" href="#"><img border="0" src="/images/qqgroup.png" />加入QQ群</a>
	</div>
</div>
<!-- QQ交谈 end -->


<div class="alert-box alert hide" id="alert-box">
	<div class="content"></div>
</div>


<div class="confirm-box hide" id="confirm-box">
	<div class="content"></div>
	<div class="action">
		<button class="iv-btn btn-primary t">确定</button>
		<button class="iv-btn btn-default f">取消</button>
	</div>
</div>

<div id="mask-layer"></div>

<script>


</script>



