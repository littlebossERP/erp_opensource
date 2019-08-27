<?php

namespace eagle\modules\util\helpers;

class TopMessage {
	
	/**
	 * 在主体内容之上，菜单栏之下，侧边栏的右方（如果有的话） 显示提示消息。
	 * $isAbsolute为false是为没有侧边栏准备的
	 * @param string $isAbsolute
	 * @return string
	 */
	public static function getMessage($isAbsolute=true){
		return '<div class="alert alert-warning" style="background-color: #ffffc9;border-radius: 0;padding: 5px 10px;text-align: center;line-height: 19px;margin-bottom: 0;border-color: #ffd25b;border-width: 0 0 1px;'.($isAbsolute?'position: absolute;width: 100%;top: 0;left: 0;':'').'">
					<a href="javascript:void(0)" target="_blank" class="text-warning" style="color: #a87a01 !important;">用户交流群1: 317561579（已满）  用户交流群2: 376681462（已满）  用户交流群3: 866409466</a>
				</div>
				'.($isAbsolute?'<div class="paddingDiv" style="padding: 5px 10px; text-align: center;line-height: 19px;"></div>':'');
	}
}

?>