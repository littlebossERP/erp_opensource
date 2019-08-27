<?php namespace eagle\modules\assistant\helpers;

use eagle\models\SysCountry;
use eagle\models\assistant\OmOrderMessageTemplate;

class HtmlHelper extends \eagle\helpers\HtmlHelper
{

	public static $regions = [
        'Europe'                            =>'欧洲',
        'Africa'                            =>'非洲',
        'Central America and Caribbean'     =>'美国中部和加勒比海',
        'Oceania'                           =>'大洋洲',
        'Middle East'                       =>'中东',
        'North America'                     =>'北美洲',
        'Southeast Asia'                    =>'东南亚',
        'South America'                     =>'南美洲',
        'Asia'                              =>'亚洲'
    ];

	 /**
     * 获取国家信息并组成数组 
     * key:code,val:zh
     * @author huaqingfeng <80506313@qq.com>
     * @param  boolean $region 是否按照洲进行分组，如果传入字符串则会对洲进行筛选（即增加搜索条件）
     * @param  string $lang 输出显示的语言种类
     * @return [array]
     */
    static public function getSysCountries($region = false, $lang='zh'){
        $country = SysCountry::find();
        if(is_string($region)){
            $country->where(['region'=>$region]);
        }
        $countries = [];
        foreach($country->all() as $c){
            if($region === true){
                $countries[$c['region']][$c['country_code']] = $c['country_'.$lang];
            }else{
                $countries[$c['country_code']] = $c['country_'.$lang];
            }
        }
        return $countries;
    }

    /**
     * 选择大洲
     */
    static public function SelectRegion($attrs = []){
        $html = '<select '.self::setAttr($attrs).'>
            <option value="">请选择洲</option>';
            foreach(self::$regions as $key=>$val){
                $html .= "<option value='{$key}'>{$val}</option>";
            }
        $html .= '</select>';
        return $html;
    }

    /**
     * 选择国家
     * @param boolean $region [description]
     * @param array   $attrs  [description]
     */
    static public function SelectCountry($region=true,$attrs = []){
        $countries = self::getSysCountries($region);
        $html = '<select '.self::setAttr($attrs).'>
                    <option value="">请选择国家</option>
                    <option value="*">所有国家</option>';
        $_countries = [];
        foreach($countries as $group=>$v){
            $html .= '<optgroup label="'.$group.'">';
            foreach($v as $country_code=>$country_zh){
                $_countries[$country_code] = $country_zh;
                $html .= '<option value="'.$country_code.'">'.$country_zh.'</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * 选择催款/留言模板
     */
    static public function SelectTemplate($attrs = [],$type){
        $html = '<select'.self::setAttr($attrs).'>
            <option value="">请选择模板</option>';
        $template = self::getTemplate();
        foreach($template as $key=>$val){
            if($template[$key]['id'] == $type){
                $html .= '<option value="'.$template[$key]['id'].'" selected>'.$template[$key]['template_name'].'</option>';
            }else{
            $html .= '<option value="'.$template[$key]['id'].'">'.$template[$key]['template_name'].'</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }
    /**
     * 获取催款/留言模板并组成数组
     */
    static public function getTemplate(){
        $template = [];
        $tpl = OmOrderMessageTemplate::find();
        foreach($tpl->where(['status'=>1])->all() as $key=> $t){
            $template[$key]['id'] = $t['id'];
            $template[$key]['template_name'] = $t['template_name'];
            $template[$key]['content'] = $t['content'];
        }
        return $template;
    }
    /**
     * 预览催款/留言模板内容
     */
    static public function showTemplate(){

        $template = self::getTemplate();
        $html = '<div>';
        foreach($template as $k=>$v){
            $html .='<p name="Model" style="display:none;" id="showModel_'.$template[$k]['id'].'">'.$template[$k]['content'].'</p>';
        }
        $html .= '</div>';
        return $html;
    }

}
