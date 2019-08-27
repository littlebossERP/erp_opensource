<?php
namespace eagle\widgets;

use yii\helpers\Html;
use yii\data\Sort;
use yii\helpers\Inflector;
/**
 * 配合js 通过ajax触发获取页面的排序插件, 主要是为了避免点击 <a> 标签 它url导致的页面跳转
 * （目前可以通过jquery捕捉click事件 ，返回false来阻止跳转，但是修改代码去掉href的更加保险。）
 */

class ESort extends Sort{
	/**
	 * Generates a hyperlink that links to the sort action to sort by the specified attribute.
	 * Based on the sort direction, the CSS class of the generated hyperlink will be appended
	 * with "asc" or "desc".
	 * @param string $attribute the attribute name by which the data should be sorted by.
	 * @param array $options additional HTML attributes for the hyperlink tag.
	 * There is one special attribute `label` which will be used as the label of the hyperlink.
	 * If this is not set, the label defined in [[attributes]] will be used.
	 * If no label is defined, [[\yii\helpers\Inflector::camel2words()]] will be called to get a label.
	 * Note that it will not be HTML-encoded.
	 * @return string the generated hyperlink
	 * @throws InvalidConfigException if the attribute is unknown
	 */
	
	public $isAjax = false;
	
	public function link($attribute, $options = [])
	{
		if (($direction = $this->getAttributeOrder($attribute)) !== null) {
			$class = $direction === SORT_DESC ? 'desc' : 'asc';
			if (isset($options['class'])) {
				$options['class'] .= ' ' . $class;
			} else {
				$options['class'] = $class;
			}
		}
		// ajax 形式获取页面html时,去掉 url 避免页面跳转
		if(!empty($this->isAjax))
			$url = null;
		else
			$url = $this->createUrl($attribute);
		
		$options['data-sort'] = $this->createSortParam($attribute);
		$options['data-url'] = $this->createUrl($attribute);
		$options['style'] = "cursor: pointer;";
		
		
		if (isset($options['label'])) {
			$label = $options['label'];
			unset($options['label']);
		} else {
			if (isset($this->attributes[$attribute]['label'])) {
				$label = $this->attributes[$attribute]['label'];
			} else {
				$label = Inflector::camel2words($attribute);
			}
		}

		return Html::a($label, $url, $options);
	}
}
?>