<?php

namespace eagle\modules\listing\models;

use eagle\modules\listing\models\EnsogoVariance;

class EnsogoVarianceCountries extends \eagle\models\EnsogoVarianceCountries {

    public static function multiSaveByVarianceId($product, $variances){
        $variance_info = EnsogoVariance::find()->where(["product_id"=>$product->id])->asArray()->all();
        if( $variance_info === false){
            $rtn = [
                'success' => 'false',
                'msg' => '不存在变种信息！'
            ];
        } else {
            $tableName = self::tableName();
            $sql = "DELETE FROM {$tableName} WHERE product_id = {$product->id}";
            $query = self::getDb()->createCommand($sql);
            $msg = [];
            if($query->execute()!==false){
                foreach($variances as $variance){
                    $countries = isset($variance['countries']) ? explode('|',$variance['countries']) : self::_getCountries();//国家
                    $prices = explode('|',$variance['prices']); //单价
                    $shippings = explode('|',$variance['shippings']);//运费
                    $msrps = empty($variance['msrps']) ? "" : explode('|',$variance['msrps']);//原价
                    foreach($variance_info as $k => $v){
                        if($variance['sku'] != $v['sku']){
                            continue;
                        } else {
                            $variance_id = $v['id'];
                            //unset($variance_info[$k]);
                            foreach($countries as $key => $country){//获取商品变种ID
                                $ensogo_variance_countries_obj = new EnsogoVarianceCountries();
                                $ensogo_variance_countries_obj->product_id = $product->id;
                                $ensogo_variance_countries_obj->variance_id = $variance_id;
                                $ensogo_variance_countries_obj->country_code = $country;
                                $ensogo_variance_countries_obj->price = isset($prices[$key]) && $prices[$key] ? $prices[$key] : 0.00;
                                $ensogo_variance_countries_obj->shipping = isset($shippings[$key]) && $shippings[$key] ? $shippings[$key] : 0.00;
                                $ensogo_variance_countries_obj->msrp = isset($msrps[$key]) && $msrps[$key] ? $msrps[$key] : 0.00;
                                $ensogo_variance_countries_obj->status = 1;
                                $ensogo_variance_countries_obj->create_time = date('Y-m-d H:i:s');
                                $ensogo_variance_countries_obj->update_time = date('Y-m-d H:i:s');
                                if(!$ensogo_variance_countries_obj->save(false)){
                                    $msg[] = $ensogo_variance_countries_obj->getErrors();
                                }
                            }
                        }
                    }
                }
                $rtn = [
                    'success' => !empty($msg) ? false : true,
                    'msg' => !empty($msg) ? join('|',$msg) : ''
                ];
            } else {
                $rtn = [
                    'success' => 'false',
                    'msg' => '更新多站点信息失败！'
                ];
            }
        }
        return $rtn;
    }

    /**
     * 保存单变体的多站点信息
     * @param EnsogoVariance $variance
     * @param $data
     * @return array|string
     * @throws \yii\db\Exception
     */
    public static function saveVarianceCountriesInfo(EnsogoVariance $variance,$data){

        $tableName = self::tableName();
        $sql = "DELETE FROM {$tableName} WHERE product_id = {$variance->product_id} and variance_id = {$variance->id}";
        $query = self::getDb()->createCommand($sql);
        $msg = [];
        //删除该变种下面所有的商品信息
        if($query->execute()!==false){
            
            $countries = $data->exists('countries') ? explode('|',$data->countries) : self::_getCountries();//国家
            // var_dump($countries);die;
            $prices = explode('|',$data->prices); //单价
            $shippings = explode('|',$data->shippings);//运费
            $msrps = $data->msrps ? explode('|',$data->msrps) : "";//原价
            foreach($countries as $key => $country){//获取商品变种ID
                $ensogo_variance_countries_obj = new EnsogoVarianceCountries();
                $ensogo_variance_countries_obj->product_id = $variance->product_id;
                $ensogo_variance_countries_obj->variance_id = $variance->id;
                $ensogo_variance_countries_obj->country_code = $country;
                $ensogo_variance_countries_obj->price = isset($prices[$key]) ? $prices[$key] : 0.00;
                $ensogo_variance_countries_obj->shipping = isset($shippings[$key]) ? $shippings[$key] : 0.00;
                $ensogo_variance_countries_obj->msrp = isset($msrps[$key]) ? $msrps[$key] : 0.00;
                $ensogo_variance_countries_obj->status = 1;
                $ensogo_variance_countries_obj->create_time = date('Y-m-d H:i:s');
                $ensogo_variance_countries_obj->update_time = date('Y-m-d H:i:s');
                if(!$ensogo_variance_countries_obj->save(false)){
                    return $ensogo_variance_countries_obj->getErrors();
                }
            }
        }
        return '';
    }

    private static function _getCountries(){
        return explode('|','hk|th|id|ph|sg|my|us');
    }

}