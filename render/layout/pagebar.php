<?php namespace render\layout;

use eagle\modules\util\helpers\TranslateHelper;

class Pagebar extends \render\form\Base
{
	public $page;
	public $size = true;
	public $goto = false;
	public $bar = true;
	
	/**
	 * 分页工具条
	 */
	public function __toString(){
		$html = '<div class="flex-row space-between center">
				<div class="page">';
		if($this->bar){
			$html .= \yii\widgets\LinkPager::widget(['pagination'=>$this->page,  'class'=>'btn-group dropup']);
		}
		if($this->goto){
			$html .='第 <input class="goto-page iv-input" type="number" /> 页
					<button class="iv-btn btn-goto-page">GO</button>';
		}
		if($this->size){		
			$html .= \render\layout\SizePager::widget([
					'pagination'=>$this->page, 
					'pageSizeOptions'=>$this->page->pageSizeLimit
			]);
		}
		$html .='</div></div>';
		// $html .= '<div class="page-bar">
		// 			<a href="#" class="prev" disabled><i class="iconfont icon-shangyiye"></i></a>
		// 			<a href="#" class="active">1</a>
		// 			<a href="#">2</a>
		// 			<a href="#">3</a>
		// 			<a href="#">4</a>
		// 			<a href="#">5</a>
		// 			<a href="#">6</a>
		// 			<a href="#">7</a>
		// 			<a href="#">8</a>
		// 			<a>...</a>
		// 			<a href="#">20</a>
		// 			<a href="#" class="next"><i class="iconfont icon-xiayiye"></i></a>
		// 		</div>
		// 	</div>';

		return $html;
	}

}