<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;

$menu_message=MessageApiHelper::getMenuStatisticData();
$no_answer_num=isset($no_answer)?count($no_answer):0;
if(isset($menu_message['menu']['客户管理']['items']['所有客户']['tabbar'])){
    $menu_message['menu']['客户管理']['items']['所有客户']['tabbar'] = $no_answer_num;
}
echo $this->render('//layouts/new/left_menu_2',[
    'menu'=>$menu_message['menu'],
    'active'=>$menu_message['active']
]);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>