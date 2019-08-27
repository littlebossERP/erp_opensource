<?php
namespace eagle\widgets;


use yii\widgets\LinkPager;
use yii\helpers\Html;
/**
 * 配合js 通过ajax触发获取页面的分页插件
 */

class ELinkPager extends LinkPager{
	
	/**
	 * Executes the widget.
	 * This overrides the parent implementation by displaying the generated page buttons.
	 */
	
	public $isAjax = false;
	
	public function run()
	{
		if ($this->registerLinkTags) {
			$this->registerLinkTags();
		}
		echo $this->renderPageButtons();
	}
	
	/**
	 * Renders a page button.
	 * You may override this method to customize the generation of page buttons.
	 * @param string $label the text label for the button
	 * @param integer $page the page number
	 * @param string $class the CSS class for the page button.
	 * @param boolean $disabled whether this page button is disabled
	 * @param boolean $active whether this page button is active
	 * @return string the rendering result
	 */
	protected function renderPageButton($label, $page, $class, $disabled, $active) {
		$options = ['class' => $class === '' ? null : $class];
		$linkOptions = $this->linkOptions;
		
		if ($active) {
			Html::addCssClass($options, $this->activePageCssClass);
		}else{
			$linkOptions['style'] = "cursor: pointer;";
		}
		if ($disabled) {
			Html::addCssClass($options, $this->disabledPageCssClass);
			return Html::tag('li', Html::tag('span', $label), $options);
		}
		
		$linkOptions['data-page'] = $page;
		$linkOptions['data-url'] = $this->pagination->createUrl($page);
		
		$url = null; 
		if(empty($this->isAjax) && !$active){
			$url = $this->pagination->createUrl($page);
		}
		return Html::tag('li', Html::a($label, $url , $linkOptions), $options);
	}
}

?>