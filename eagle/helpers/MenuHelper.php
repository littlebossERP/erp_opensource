<?php namespace eagle\helpers;

use eagle\modules\util\helpers\TranslateHelper;

Class MenuHelper
{

	static protected function parentActive($parent){
		extract($parent);
		$controller = \yii::$app->controller->id;
		$_action = \yii::$app->controller->action->id;
		$class='';
		if(isset($action) && $action && $controller.'/'.$_action==$action){
			$class="active";
		}else{
			foreach($items as $item){
				if($item['action']==$controller.'/'.$_action){
					$class="active";
				}
			}
		}
		return $class;
	}


	/**
	 * $items
	 *   name:
	 *   action:
	 *   badge:
	 */
    static public function left_menu_ul($param=[]){
            extract($param);
            $module = \yii::$app->controller->module->id;
            $controller = \yii::$app->controller->id;
            $_action = \yii::$app->controller->action->id;


            // var_dump($module);
            // var_dump($controller);
            // var_dump($_action);die;

            $text = TranslateHelper::t($name);
            $text = isset($action)?"<a href='/{$module}/{$action}'>$text</a>":$text;
            $html = '<div class="sidebarLv1Title">
            <div><span class="glyphicon glyphicon-'.$icon.'" style="margin: 3px 5px 0px 0px;color:#00CCFF;"></span>'.$text.'
            </div></div>    
    <ul class="ul-sidebar-one">';
            foreach($items as $item){
                    $html.='<li class="ul-sidebar-li '.($module.'/'.$_action==$item['action']?'active':'').'">
                    <a href="/'.$module.'/'.$item['action'].'">
                            <font>'.TranslateHelper::t($item['name']).'</font>
                            <span class="badge">'.(isset($item['badge']) && $item['badge']>0?$item['badge']:'' ).'</span>
                    </a>
                    </li>';
            }
            $html.='</ul>';
            return $html;
    }


	static public function left_menu_ul_min($param=[]){
		extract($param);
		$module = \yii::$app->controller->module->id;
		$class=self::parentActive($param);
		$text = TranslateHelper::t($name);
		$href = isset($action)?"/{$module}/{$action}":'';
		return "<a class='left-menu-ul-min-link glyphicon glyphicon-{$icon} {$class}' href='$href' tips='{$text}'></a>";
	}


}

