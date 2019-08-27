<?php 
if(\Yii::$app->request->isAjax){
	return;
}

$statusName = 'left_menu_'.\Yii::$app->controller->module->id;

function getQTip($val){
	if(isset($val['qtipkey'])){
		if(is_string($val['qtipkey'])){
			$val['qtipkey'] = [
				$val['qtipkey']=>true
			];
		}
		$key = key($val['qtipkey']);
		if(stripos($key,'@')===0){
			$key = substr($key,1);
			$val['qtipkey'] = [
				$key => false
			];
		}
		$qtipClass = $val['qtipkey'][$key]?'':'no-qtip-icon';
		$qtipKey = " qtipkey='{$key}'";
	}else{
		$qtipClass = '';
		$qtipKey = '';
	}
	return [
		'class'=>$qtipClass,
		'key'=>$qtipKey
	];
}

$height = isset($style['line-height']) ? $style['line-height']:'38px';

// var_dump($height);die;

function renderUl(&$items,$lv = 1,$active,$count = 0,$height=''){
	echo "<ul class='menu-lv-{$lv}' style='line-height:{$height};'>";
	$total = 0;
	$rtn = ['total'=>0,'isActive'=>false];
	foreach($items as $name => &$info){
		// var_dump($info);
		$_active = false;
		$class = '';
		$url = isset($info['url'])?"href='{$info['url']}'":"";
		if(isset($info['state'])){
			$url.=" target='_state' href='{$info['state']}'";
		}
		if(isset($info['target'])){
			$url.=" target='{$info['target']}' href='{$info['url']}'";
		}
		if($name == $active){
			$class .= " active";
			$_active = true;
		}
		if(isset($info['target'])){
			$target = " target='{$info['target']}'";
		}else{
			$target = "";
		}
		$title = isset($info['title'])?" title='{$info['title']}'":'';
		echo "<li><a {$url} class='clearfix {$class}' {$target} onclick='$(this).next().toggle()' {$title}>";
		if(isset($info['icon'])){
			echo "<i class='iconfont {$info['icon']}'></i>";
		}
		// qtip
		$qtipKey = getQTip($info);
		echo "<span class='menu_label {$qtipKey['class']}' {$qtipKey['key']}>{$name}</span>";
		if(isset($info['items'])){
			echo "<i class='toggle cert cert-small cert-default down'></i>";
		}
		if(isset($info['tabbar']) && $info['tabbar']>0){
			echo "<span class='new' style='height:{$height};'><p>{$info['tabbar']}</p></span>";
		}
		echo "</a>";
		if(isset($info['items'])){
			$child = renderUl($info['items'],$lv+1,$active,$count,$height);
			$childCount = $child['total'];
			$_active = $child['isActive'];
		}else{
			$childCount = 0;
		}
		$count = isset($info['tabbar'])?$info['tabbar']:0;
		$info['tabbarTotal'] = $count + $childCount;
		$rtn['isActive'] = $_active;
		$info['isActive'] = $_active;
		$rtn['total'] += $info['tabbarTotal'];
		echo "</li>";
	}
	echo "</ul>";
	return $rtn;
}

 ?>
<div class="flex-row">
	<?php if($menu): ?>
	<div class="left_menu menu_v2" onload="$(this).bind_hideScroll();">
		<?php 
		renderUl($menu,1,$active,0,$height);
		?>
	</div>
	<?php endif; ?>
	<?php define('_IS_USE_LEFTMENU',TRUE); ?>
	<div class="right_content">
	<main class="main-view">


