<?php namespace eagle\helpers;

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\models\sys\SysCountry;

Class HtmlHelper
{

    static public $loadJs = [];
    static public $domTag = [];

    function __construct()
    {
        $this->id = self::id();
    }

    /**
     * 魔术方法（兼容老版静态写法，不推荐再使用了）
     */
    static public function __callStatic($funcName, $arguments = [])
    {
        $dom = new self();
        return call_user_func_array([$dom, $funcName], $arguments);
    }


    static protected function setAttr($attributes = [], $defaultAttr = [])
    {
        $attr = '';
        foreach (array_merge($defaultAttr, $attributes) as $key => $val) {
            if ($val === true) {
                $val = $key;
            } elseif ($val === false) {
                continue;
            }
            $attr .= " $key='$val'";
        }
        return $attr;
    }

    static public function params($arr = [])
    {
        return " data-params='" . json_encode($arr) . "' ";
    }

    static public function showtpl($arr = [])
    {
        return " data-showtpl='" . json_encode($arr) . "' ";
    }

    static public function tips($text = '', $class = 'info')
    {
        return '<span style="position:relative"><span class="tips tips-' . $class . '" tips="' . $text . '">?</span></span>';
    }

    static private function id()
    {
        $s = debug_backtrace()[1]['function'];
        $id = 'u_' . time() . rand(10000, 99999);
        self::$domTag[$id] = $s;
        return $id;
    }

    static public function destruct()
    {
        foreach (self::$domTag as $key => $tag) {
            $method = '__destruct_' . $tag;
            self::$method($key);
        }
    }


    /**
     * 复选框组
     * @return [type] [description]
     */
    static public function checkboxGroup($name, $options = [], $active_val = [], $attr = [])
    {
        $html = '';
        if (is_string($active_val)) {
            $active_val = [$active_val];
        }
        if (!$options) {
            $options = [];
        }
        if (!$active_val) {
            $active_val = [];
        }
        foreach ($options as $key => $val) {
            $checked = in_array($key, $active_val) ? ' checked="checked"' : '';
            $html .= '<label class="col-lg-3 col-xs-5 col-md-4 col-sm-4" ' . self::setAttr($attr) . '>
						<input type="checkbox" id="comcheck" name="' . $name . '[]" value="' . $key . '" ' . $checked . ' /> ' . $val . '
					</label>';
        }
        return $html;
    }


    /**
     * 仿 IOS7 开关按钮
     * $name: input的name属性
     * $models: 模型对象
     * $active_val: [true的值,false的值]
     * attr(tracker-key) 设置tracker跟踪的键名
     * @author huaqingfeng <80506313@qq.com>
     */
    static public function SwitchButton($name, $models, Array $active_val = [2, 1], $attributes = [])
    {
        $class = urlencode(get_class($models));
        return '<span class="iosSwitch" ' . self::setAttr($attributes) . '>
		<input name="' . $name . '" data-pk="' . $models->primaryKey . '" data-val="' . urlencode(json_encode($active_val)) . '" data-class="' . $class . '" type="checkbox" ' . (array_search($models->$name, $active_val) == 0 ? 'checked="checked"' : '') . ' value="1" /><i></i></span>';
    }

    /**
     * 仿 IOS7 开关按钮
     * $name: input的name属性
     * $models: 模型对象
     * $active_val: [true的值,false的值]
     * attr(tracker-key) 设置tracker跟踪的键名
     * @author huaqingfeng <80506313@qq.com>
     */
    static public function SwitchButtonMG($name, $type, $models, Array $active_val = [2, 1], $attributes = [])
    {
        $class = urlencode(get_class($type));
        return '<span class="iosSwitch" ' . self::setAttr($attributes) . '>
		<input name="' . $name . '" data-pk="' . $models['_id']->{'$id'} . '" data-val="' . urlencode(json_encode($active_val)) . '" data-class="' . $class . '" type="checkbox" ' . (array_search($models[$name], $active_val) == 0 ? 'checked="checked"' : '') . ' value="1" /><i></i></span>';
    }

    /**
     * [TagsView description]
     * @author  huaqingfeng <80506313@qq.com>
     * $items
     *   key: = option的value
     *   value: = option的text
     */
    static public function TagsView($name, Array $items, $btn = 'info')
    {
        $html = '<div class="tags-view">';
        $html .= '<button style="display:none;" class="btn btn-xs btn-' . $btn . '" ><span></span> <span class="glyphicon glyphicon-remove"></san><input type="checkbox" name="' . $name . '[]" /></button>';
        foreach ($items as $key => $val) {
            if ($key && $val)
                $html .= '<button class="btn btn-xs btn-' . $btn . '" ><span>' . $val . '</span> <span class="glyphicon glyphicon-remove"></san><input type="checkbox" name="' . $name . '[]" value="' . $key . '" checked="checked" /></button>';
        }
        return $html . '</div>';
    }

    /**
     * 日期控件
     * @author huaqingfeng <80506313@qq.com>
     * @param $name
     * @param $value
     * @param $format 格式
     */
    static public function DateTimePicker($name, $value, $format = "yy-mm-dd")
    {
        if (!in_array(__METHOD__, self::$loadJs)) {
            self::$loadJs[] = __METHOD__;
            \Yii::$app->view->registerJsFile(\Yii::getAlias('@web') . "/js/lib/datetimepicker/js/bootstrap-datetimepicker.js", ['depends' => ['yii\web\JqueryAsset']]);
            \Yii::$app->view->registerCssFile(\Yii::getAlias('@web') . "/js/lib/datetimepicker/css/bootstrap-datetimepicker.css");
        }
        $html = '<div class="input-group">
		<input type="text" name="' . $name . '" data-picker-format="' . $format . '" class="js_datetimepicker form-control" value="' . urldecode($value) . '" autocomplete="off" />
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span></div>';
        return $html;
    }

    /**
     * 带输入栏的下拉框
     * @author huaqingfeng <80506313@qq.com>
     * @param [type] $name  [description]
     * @param [type] $value [description]
     * @param [type] $attrs [description]
     */
    static public function InputSelect($name, $value, $attrs)
    {

    }


    /**
     * 模拟select
     * @param  [type] $name       [description]
     * @param  [type] $option     [description]
     * @param  [type] $active_val [description]
     * @param  array $attr [description]
     * @return [type]             [description]
     */
    static public function select($name, $option, $active_val = NULL, $attr = [])
    {
        $attr = array_merge([
            'textOverflow' => 2,
            'multi' => false,
            'editable' => false,
            'placeholder' => '请选择',
            'className' => ''
        ], $attr);
        $items = '';
        $now = $attr['placeholder'];
        foreach ($option as $key => $val) {
            $class = $selected = '';
            if ($active_val == $key) {
                $now = $val;
                $class = 'class="active"';
                $selected = 'selected="selected"';
            }
            $items .= "<li data-val='{$key}' {$class} {$selected}>{$val}</li>";
        }
        $html = "
		<div id='" . self::id() . "' class='lb-select {$attr['className']}' overflow='{$attr['textOverflow']}'>
			<div class='select-viewer'>{$now}</div>
			<input type='hidden' name='$name' value='{$active_val}'/>
			<ul>{$items}</ul>
			";

        $html .= "</div>";
        return $html;
    }

    static protected function findViewFile($view)
    {
        if (strncmp($view, '//', 2) === 0) {
            $file = \Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . $view;
        } else {
            $file = \Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . $view;
        }
        return $file . '.php';
    }

    static protected function loadViewFile($file, $params = [])
    {
        ob_start();
        extract($params);
        include $file;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }


    static public function modalButton($text, $url, $params = [], $attr = [], $size = '')
    {
        $id = self::id();
        $content = '';
        if (stripos($url, '@') !== false) {
            $s = explode('@', $url);
            $title = $s[0];
            $url = $s[1];
            $body = self::loadViewFile(self::findViewFile($url), $params);
            $content = '<div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="gridSystemModalLabel">' . $title . '</h4>
		      </div>
		      <div class="modal-body">' . $body . '</div>';
        } else {
            $attr = array_merge([
                'data-param' => json_encode($params),
                'data-href' => $url
            ], $attr);
        }
        $html = '<a data-toggle="modal" data-target="#' . $id . '" ' . self::setAttr($attr) . ' >' . $text . '</a>
		<div class="modal-v2 modal fade" role="dialog" id="' . $id . '" aria-labelledby="gridSystemModalLabel">
		  <div class="modal-dialog ' . $size . '" role="document" data-dragabled=".modal-header">
		    <div class="modal-content">
		        ' . $content . '
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->';
        return $html;
    }

    static public function largeModalButton($text, $url, $params = [], $attr = [])
    {
        return self::modalButton($text, $url, $params, $attr, 'modal-lg');
    }


    /**
     * 分页工具条
     */
    static public function Pagination($page, $show = [1, 1, 1], $className = 'standard')
    {
        $html = '<div id="pager-group" class="lb-pagination page-' . $className . '">';
        $html .= '<div class="btn-group">' . \yii\widgets\LinkPager::widget(['pagination' => $page, 'options' => ['class' => 'pagination']]) . '</div>';
        $html .= \eagle\widgets\SizePager::widget(['pagination' => $page, 'pageSizeOptions' => $page->pageSizeLimit, 'class' => 'btn-group dropup']);
        $html .= '</div>';
        return $html;
    }

    static public function mainContentStart($class = '', $attr = [])
    {
        return "<div class='content-wrapper' style='margin-left:" . ((isset($_COOKIE['sidebar-status']) && $_COOKIE['sidebar-status']) ? '40' : '200') . "px;' " . self::setAttr($attr) . ">";
    }

    static public function mainContentEnd()
    {
        return "</div>";
    }

    public static function dropdownlistSearchButton($name, $arr, $title)
    {
        $lis = '';
        foreach ($arr as $a) {
            $lis .= "<li><a href=" . self::current([$name => $a]) . ">{$a}</a></li>";
        }
        $selectList = '<ul>' . $lis . '</ul>';
        $html = '<div class="listsearchbutton"><div class="listsearchbuttontitle">' . $title . ' <span class="glyphicon glyphicon-chevron-down" style="position:relative;top:2px;right:-5px;"></span></div>' . $selectList . '</div>';
        return $html;
    }

    /**
     * 自动生成连接 带当前参数
     */
    public static function current($newParams = [], $needCurrentParams = true)
    {
        //当前url
        $currentUrl = '/' . \Yii::$app->request->pathInfo;
        if (count($newParams) < 1 && $needCurrentParams === FALSE) return $currentUrl;
        //已存在的参数
        $params_str = '';
        if ($needCurrentParams && count($_REQUEST)) {
            foreach ($_REQUEST as $k => $v) {
                if (isset($newParams[$k])) $v = $newParams[$k];
                $params_str .= "{$k}={$v}&";
            }
            $params_str = rtrim($params_str, '&');
        }
        //新添加的参数
        $new_params_str = '';
        if (is_array($newParams) && count($newParams)) {
            foreach ($newParams as $k => $v) {
                //如果当前request里已经包含这些参数 则跳过
                if (isset($_REQUEST[$k])) continue;
                $new_params_str .= "{$k}={$v}&";
            }
            $new_params_str = rtrim($new_params_str, '&');
        }
        $params = empty($params_str) ? (empty($new_params_str) ? '' : '?' . $new_params_str) : '?' . $params_str . (empty($new_params_str) ? '' : '&' . $new_params_str);
        return $currentUrl . $params;
    }

    /**
     * 国家复选框组
     * @param  [type] $name  checkbox的name，比如： countries[]
     * @param  array $value 初始化默认值 是一个数组  比如：  ['US','GR']
     * @return [type]        [description]
     */
    static public function selCountries($name, $value = [])
    {
        $html = '<ul class="lb-countries">';
        $cData = SysCountry::getGroupByRegion();
        //选择所有国家
        if (!empty($value)) {
        	if ($value[0] == '*-') {
        		//新增时，需排除巴西，用于速卖通自动催付，lrq20171103
        		$html .= "<li>
				<h5 class='c-title'>
				<label><input type='checkbox' class='' data-check-all='countries_in_region' />所有国家</label>
				</h5>
				<div class='c-body transition row'>";
        	}
            else if ($value[0] == '*') {
                $html .= "<li>
				<h5 class='c-title'>
				<label><input type='checkbox' class='' data-check-all='countries_in_region' checked='checked;' />所有国家</label>
				</h5>
				<div class='c-body transition row'>";
            }
        } else {
            $html .= "<li>
			<h5 class='c-title'>
			<label><input type='checkbox' class='' data-check-all='countries_in_region' />所有国家</label>
			</h5>
			<div class='c-body transition row'>";
        }


        foreach ($cData as $region => $countries) {
            $str = [];
            $regionName = SysCountry::$regions[$region];
            $region = str_replace(' ', '', $region);
            $checkboxStatus = 'open';
            $data_check = 'false';
            $defaulticon = '+';
            foreach ($countries as $country) {
                if (!empty($value)) {
                    if ($value[0] == '*') {
                        $value[] = $country->country_code;
                    }
                    else if ($value[0] == '*-') {
                    	//新增时，需排除巴西，用于速卖通自动催付，lrq20171103
                    	if($country->country_code != 'BR'){
                        	$value[] = $country->country_code;
                    	}
                    }
                }
                if (in_array($country->country_code, $value)) {
                    $checked = 'checked="checked"';
                    $checkboxStatus = 'close';
                    $data_check = 'true';
                    $defaulticon = '-';
                } else {
                    $checked = '';
                }
                $str['body'][] = "<div class='col-xs-3 col-sm-3 col-md-3 col-lg-3'>
						<label><input type='checkbox' name='{$name}[]' class='' data-check='check_{$region}' value='{$country->country_code}' {$checked} />{$country->country_zh}</label>
					</div>";
            }
            //勾选大洲
            if (!empty($value)) {
                if ($value[0] == '*' || !empty($checked)) {
                    $str['head'] = "<li>
					<span class='glyphicon-checkbox glyphicon-checkbox-{$checkboxStatus}' data-open='{$data_check}'>{$defaulticon}</span>
					<h6 class='c-title'>
					<label><input type='checkbox' data-check='countries_in_region' class='' data-check-all='check_{$region}' checked='checked;' />{$regionName}</label>
					</h6>
					<div class='c-body transition row'>";
                }
            } else {
                $str['head'] = "<li>
				<span class='glyphicon-checkbox glyphicon-checkbox-{$checkboxStatus}' data-open='{$data_check}'>{$defaulticon}</span>
				<h6 class='c-title'>
				<label><input type='checkbox' data-check='countries_in_region' class='' data-check-all='check_{$region}' />{$regionName}</label>
				</h6>
				<div class='c-body transition row'>";
            }


            $str['foot'] = "</div></li>";

            //组装
            $html .= $str['head'] . implode('', $str['body']) . $str['foot'];
        }

        $html .= '</ul>';
        return $html;
    }


    static public function selCountriesV2($name, $value = [])
    {
        $html = '<ul class="lb-countries">';
        $cData = SysCountry::getGroupByRegion();
        //选择所有国家
        if (!empty($value)) {
            if ($value[0] == '*') {
                $html .= "<li>
				<h5 class='c-title'>
				<label><input type='checkbox' class='' data-check-all='countries_in_region' checked='checked;' />所有国家</label>
				</h5>
				<div class='c-body transition row'>";
            }
        } else {
            $html .= "<li>
			<h5 class='c-title'>
			<label><input type='checkbox' class='' data-check-all='countries_in_region' />所有国家</label>
			</h5>
			<div class='c-body transition row'>";
        }


        foreach ($cData as $region => $countries) {
            $str = [];
            $regionName = SysCountry::$regions[$region];
            $region = str_replace(' ', '', $region);
            $checkboxStatus = 'open';
            $data_check = 'false';
            $defaulticon = '+';
            foreach ($countries as $country) {
                if (!empty($value)) {
                    if ($value[0] == '*') {
                        $value[] = $country->country_code;
                    }
                }
                if (in_array($country->country_code, $value)) {
                    $checked = 'checked="checked"';
                    $checkboxStatus = 'close';
                    $data_check = 'true';
                    $defaulticon = '-';
                } else {
                    $checked = '';
                }
                $str['body'][] = "<div class='col-xs-3 col-sm-3 col-md-3 col-lg-3'>
						<label><input type='checkbox' name='{$name}[]' class='' data-check='check_{$region}' value='{$country->country_code}' {$checked} />{$country->country_zh}</label>
					</div>";
            }
            //勾选大洲
            if (!empty($value) && ($value[0] == '*' || !empty($checked))) {
                $str['head'] = "<li>
					<span class='glyphicon-checkbox glyphicon-checkbox-{$checkboxStatus}' data-open='{$data_check}'>{$defaulticon}</span>
					<h6 class='c-title'>
					<label><input type='checkbox' data-check='countries_in_region' class='' data-check-all='check_{$region}' checked='checked;' />{$regionName}</label>
					</h6>
					<div class='c-body transition row'>";
            } else {
                $str['head'] = "<li>
				<span class='glyphicon-checkbox glyphicon-checkbox-{$checkboxStatus}' data-open='{$data_check}'>{$defaulticon}</span>
				<h6 class='c-title'>
				<label><input type='checkbox' data-check='countries_in_region' class='' data-check-all='check_{$region}' />{$regionName}</label>
				</h6>
				<div class='c-body transition row'>";
            }


            $str['foot'] = "</div></li>";

            //组装
            $html .= $str['head'] . implode('', $str['body']) . $str['foot'];
        }

        $html .= '</ul>';
        return $html;
    }
    /**
     * 国家复选框组
     * @param  [type] $name  checkbox的name，比如： countries[]
     * @param  array $value 初始化默认值 是一个数组  比如：  ['US','GR']
     * @return [type]        [description]
     */
    /*
    static public function selCountries($name,$value=[]){
        $html = '<ul class="lb-countries">';
        $cData = SysCountry::getGroupByRegion();
        
        //选择所有国家
        $html .= "<li>
            <h5 class='c-title'>
            <label><input type='checkbox' class='' data-check-all='check_All' />所有国家</label>
            </h5>
            <div class='c-body transition row'>"; 

        foreach($cData as $region=>$countries){
            $str = [];
            $regionName = SysCountry::$regions[$region];
            $region = str_replace(' ', '', $region);
            $checkboxStatus = 'open';
            $data_check = 'false';
            $defaulticon = '+';
            foreach($countries as $country){
                if(in_array( $country->country_code, $value)){
                    $checked = 'checked="checked"';
                    $checkboxStatus = 'close';
                    $data_check = 'true';
                    $defaulticon = '-';
                }else{
                    $checked = '';
                }
                $str['body'][]="<div class='col-xs-3 col-sm-3 col-md-3 col-lg-3'>
                        <label><input type='checkbox' name='{$name}[]' class='' data-check='check_{$region}' value='{$country->country_code}' {$checked} />{$country->country_zh}</label>
                    </div>";
            }
            $str['head']="<li>
            <span class='glyphicon-checkbox glyphicon-checkbox-{$checkboxStatus}' data-open='{$data_check}'>{$defaulticon}</span>
            <h6 class='c-title'>
            <label><input type='checkbox' class='' data-check-all='check_{$region}' />{$regionName}</label>
            </h6>
            <div class='c-body transition row'>";
            
            $str['foot'] = "</div></li>";
            
            //组装
            $html .= $str['head'].implode('', $str['body']).$str['foot'];
        }

        $html.='</ul>';
        return $html;
    }
    */
}