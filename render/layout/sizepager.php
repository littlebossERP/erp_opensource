<?php
namespace render\layout;

use yii\base\InvalidConfigException;
use yii\data\Pagination;
use Yii;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;

class SizePager {
	
	 /**
     * @var array the default page size options . 
     */
    public $defaultPageSizeOptions = [ 10 , 20 , 50 , 100 , 200 ];
    
    /**
     * @var array|boolean the page size options. If this is false, it means [[pageSize]] should always return the array $defaultPageSizeOptions .
     */
    public $pageSizeOptions = false;
    
     /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make SizePager work.
     */
    public $pagination = null;
    
    /**
     * @var string the outer DIV class. 
     */
    public $class = '';
    
     /**
     * This method render the dropdown button for client to choose how many items shows in one page.
     * The widget rendering result is returned by this method.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return string the rendering result of the widget.
     */
    public static function widget( $config = [] ){
    	$widget = Yii::createObject(array('class'=>get_called_class()));
    	if (!empty($config)) {
    		Yii::configure($widget, $config);
    	}

    	if ($widget->pagination === null || !$widget->pagination instanceof Pagination) {
    		throw new InvalidConfigException('The "pagination" property must be set.');
    	}
    	
    	return $widget->renderSizePagerDropdown();
    }
    
    // render "每页显示X页" 按钮
    private function renderSizePagerDropdown(){
    	$out = '';
    	
    	$pageSize = $this->pagination->getPageSize();
    	$nowPage = $this->pagination->getPage();
    	
        // $selectBox = new \render\form\select\Select;
        // $selectBox->option = $this->getPageSizeOptions();
        // $selectBox->data([
        //     'location-query'=>"{'per-page':$(this).val()}"
        // ]);

        $out .= '<select class="iv-input" location-query="{\'per-page\':$(this).val()}">';
		foreach ($this->getPageSizeOptions() as $pSizeOpt){
			$options = ['class' =>  null ];
			$linkOptions = isset($this->linkOptions) ? $this->linkOptions : [];
			$linkOptions['data-url'] = $this->pagination->createUrl( $nowPage , $pSizeOpt);
			$linkOptions['data-per-page'] = $pSizeOpt;
			$label = $pSizeOpt;
			
			// ajax 形式获取页面html时,去掉 url 避免页面跳转
			if(!empty($this->isAjax) || $pageSize == $pSizeOpt){
				$url = null;
				if($pageSize != $pSizeOpt)
					$options['style'] = "cursor: pointer;";
			} else {
				$url = $this->pagination->createUrl( $nowPage , $pSizeOpt);
			}	
            $out .= "<option value='{$pSizeOpt}' ".( $pageSize == $pSizeOpt?'selected = "selected"':'')." data-link='{$url}'>每页{$pSizeOpt}条</option>";
		}
    	
    	$out .= '</select>';
    	
    	// $out .= '<div style="padding: 6px 12px;display: inline-block;">'.
    	// 	TranslateHelper::t('共 %d 页 %d 记录' , $this->pagination->getPageCount() ,  $this->pagination->totalCount )
    	// 	.'</div></div>';
    	return $out;
    }
    
	/**
	 * @return $_pageSizeOptions
	 */
	public function getPageSizeOptions() {
		if (empty($this->pageSizeOptions)) {
			$pageSizeOption = $this->defaultPageSizeOptions;
			$this->setPageSizeOptions($pageSizeOption);
		} 
		
		return $this->pageSizeOptions;
	}

	/**
	 * @param array $_pageSizeOptions
	 */
	public function setPageSizeOptions($_pageSizeOptions) {
		$this->pageSizeOptions = $_pageSizeOptions;
	}

    
    
}

?>