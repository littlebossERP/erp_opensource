<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\carrier\helpers;

class CarrierException extends \Exception
{ 
	// 重定义构造器使 message 变为必须被指定的属性 
	public function __construct($message, $code = 0) { 
	// 自定义的代码 
	// 确保所有变量都被正确赋值 
		parent::__construct($message, $code); 
	} 
	// 自定义字符串输出的样式 
	public function msg() {
		return '操作失败：'.$this->getMessage().PHP_EOL;
	} 
}
