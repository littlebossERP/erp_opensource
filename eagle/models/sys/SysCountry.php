<?php namespace eagle\models\sys;

use common\helpers\Helper_Array;

class SysCountry extends \eagle\models\SysCountry
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


	static public function getGroupByRegion(){
		$countries = self::find()->all();
		$data = [];
		foreach($countries as $country){
			$data[$country->region][]=$country;
		}
		return $data;
	}

    /**
     * 转换国家代码到显示名称
     * @param Array $countries [description]
     * @param string $lang      [description]
     * @return Array
     */
    static public function getCountriesName($countries,$lang='zh'){
        $count = self::find()->count();
        $c = self::find()
            ->select(['country_'.$lang])
            ->where([
                'IN','country_code',$countries
            ]);

        $result = $c->asArray()->all();
        if(count($result) == $count)return ['全部国家'];
        return Helper_Array::getCols($result,'country_'.$lang);
    }
	
    public static function getCountryCodeByCountryEnName($name){
    	if(empty($name))
    		return '';
    	$country = self::find()->where(['country_en'=>$name])->One();
    	if(!empty($country))
    		return $country->country_code;
    	else 
    		return $name;
    }

}