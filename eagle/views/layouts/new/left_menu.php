<?php 
include "left_menu_2.php";
return;
$statusName = 'left_menu_'.\Yii::$app->controller->module->id;
// $this->registerCssFile(\Yii::getAlias('@web') . "/css/left_menu.css");
// $statusName = 'left_menu';
 ?>
<div class="flex-row">
<div class="left_menu">
<div class="left_menu left_menu_auto" onload="$(this).bind_hideScroll();" status-hide="<?= $statusName ?>"'>
	<dl>
		<?php 
		foreach($menu as $name=>$param){
			$menu[$name]['tabbarCount'] = 0;
			$menu[$name]['isActive'] = false;
			?>
			<dt class="flex-row center space-between">
				<i class="iconfont <?=$param['icon']?>"></i>
				<a><?=$name?></a>
				<i class="toggle cert cert-small cert-default down"></i>
			</dt>
			<?php
			if(isset($param['items'])){
				foreach($param['items'] as $cname=>$cparam){
					// var_dump($cparam);
					if($isActive = ( isset($active) && $active == $cname )){
						$menu[$name]['isActive'] = true;
					}
					$qtipkey = isset($cparam['qtipkey'])?"qtipkey = '{$cparam['qtipkey']}'":"";
					?>
					<dd class="flex-row center space-between <?= $isActive?'active':''?>" >
						<a href="<?=$cparam['url']?>" <?=$qtipkey?> ><?=$cname?></a>
						<?php if(isset($cparam['tabbar'])):?>
						<span class="new"><?=$cparam['tabbar']?></span>
						<?php 
						$menu[$name]['tabbarCount'] += $cparam['tabbar'];
						endif;
						if($isActive):
						?>
						<i class="cert cert-large cert-light left"></i>
						<?php endif;
						if(isset($cparam['items'])): ?>
						<i class="toggle cert cert-small cert-default down"></i>
						<?php endif; ?>
					</dd>
					<?php
					if(isset($cparam['items'])):
					?>
					<ul>
						<?php 
						foreach($cparam['items'] as $dname => $dparam):
							if($isActive = ( isset($active) && $active == $dname )){
								$menu[$name]['isActive'] = true;
							}
						?>
						<li class="<?= $isActive?'active':''?>">
							<a href="<?=$dparam['url'] ?>"><?= $dname ?>
							<?php 
							if(isset($dparam['tabbar'])):
								$menu[$name]['tabbarCount'] += $dparam['tabbar'];
							?>
							<span class="new"><?= $dparam['tabbar'] ?></span>
							<?php endif; ?>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif;
				}
			}
		}
		?>
	</dl>
</div>
</div>
<div class="left_menu_2" status-show="<?= $statusName ?>">
	<ul>
		<?php foreach($menu as $name=>$param): ?>
		<li <?= $param['isActive'] ? "class='active'":"" ?> title="<?= $name ?>" >
			<i class="iconfont <?= $param['icon']?>"></i>
			<?php if($param['tabbarCount']>0): ?>
			<span class="new"><?= $param['tabbarCount']?></span>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</div>

<span class="toggle_menu iconfont icon-iconfont42" status-hide="<?= $statusName ?>" onclick="$.toggleStatus('<?= $statusName ?>')" onload="$(this).css('left',$('.left_menu').width()-13)"></span>
<span class="toggle_menu iconfont icon-iconfont41" status-show="<?= $statusName ?>" onclick="$.toggleStatus('<?= $statusName ?>')" ></span>

<?php define('_IS_USE_LEFTMENU',TRUE); ?>
<div class="right_content">
