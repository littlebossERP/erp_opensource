<?php
use eagle\modules\tracking\helpers\TrackingHelper;
list($menu , $active) = TrackingHelper::getMenuParams();
echo $this->render('//layouts/new/left_menu_2',[
		'menu'=>$menu,
		'active'=>$active
		]);

?>
