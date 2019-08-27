<?php namespace eagle\modules\listing\helpers;

use common\api\ensogointerface\EnsogoProxyConnectHelper;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\models\EnsogoStoreMoveLog;
use eagle\models\EnsogoApiAjaxLog;
use eagle\modules\listing\service\Log;
use eagle\models\SaasEnsogoUser;
class EnsogoStoreMoveHelper {

    public static $token;

    public static function call($action,$get=[],$post=[]){
        $response = EnsogoProxyConnectHelper::call_ENSOGO_api($action,array_merge([
            'access_token'=>self::$token,
            'lb_auth'=>self::_getAuthCode()
        ],$get,$post));

        return $response['proxyResponse'];
    }

    private static function _getAuthCode(){
        return '123';
        // return EnsogoProxyConnectHelper::call_ENSOGO_api($action,array_merge([
        // 	'access_token'=>self::$token
        // ],$get),$post)['proxyResponse']['data']['code'];
    }

    private static function _getAccessToken($site_id){
        $ensogo_info = SaasEnsogoUser::find()->where(["site_id"=>$site_id])->asArray()->one();
        if(isset($ensogo_info['token']) && !empty($ensogo_info["token"])){
            self::$token = $ensogo_info['token'];
            return true;
        }
        return false;
    }
    /**
     * WISH 商品搬家
     * @version 2016-05-04 当没有设置价格时候从商品信息里读
     * @param $wish_product_info
     * @param $wish_variance_info
     * @param $shipp_time
     * @param $category_id
     */
    public static function wishStoreMove(&$product_info,&$variance_info,$shipping_time,$category_id,$log_id,$ensogo_site_id){
        $bool = self::_getAccessToken($ensogo_site_id);

        if(!$bool){
            return ['success'=>false,"message"=>'不存在ensogo账号',"error_message"=>""];
        }

        $return = ['success'=>true,"message"=>'',"error_message"=>""];

        $variance = $variance_info[0];
        // 如果变体没有价格，则用商品的
        if(!isset($variance['price'])){
            $variance['price'] = $product_info['price'];
        }
        if(!isset($variance['shipping'])){
            $variance['shipping'] = $product_info['shipping'];
        }
        if( !isset($variance) || !isset($variance['sku']) || !$variance['price'] || !isset($variance['shipping']) ){
            return ['success'=>false,"message"=>'没有变体或变体价格未设置',"error_message"=>""];
        }
        //拼装WISH商品转换成ENSOGO商品
        $ensogo_product_info = self::_setProductInfo($product_info,$variance_info[0],$shipping_time,$category_id);
        $product_api = true;
        $variation_api = true;
        $error_message = [];
        $api_error_message = [];
        $puid = \Yii::$app->user->identity->getParentUid();
        $wish_product_id = $product_info['wish_product_id'];
        //发布商品信息
        $result = self::call("createProduct",$ensogo_product_info);
        if( !$result['success'] || ( isset($result['data']['code']) && $result['data']['code']) ){//刊登失败
            // var_dump($result);die;
            if(!isset($result['data']['message'])){
                $result['data']['message'] = $result['message'];
            }
            $product_api =false;
            $message = is_array($result['data']['message']) ? $result['data']['message'] : [$result['message']];
            $return = ['success'=>false,'message'=>$variance_info[0]['sku']."刊登失败",'error_message'=>$message];
            $error_message[] = $return['message'];
            $api_error_message[] = $return['error_message'];
        } else {
            $online_product_id = $result['data']['data']['product_id'];
            $request_id = $result['data']['data']['request_id'];
            $variant_id = $result['data']['data']['variant_id'];
            //记录异步请求数据
            self::_saveApiAjaxLog($puid,$online_product_id,$variant_id,$request_id);
            unset($variance_info[0]);
            foreach($variance_info as $key => $variance){
                $ensogo_variation_info = self::_getVariationInfo($variance,$online_product_id,$shipping_time);
                $result = self::call("createProductVariants",$ensogo_variation_info);
                if(!$result['success']  || ( isset($result['data']['code']) && $result['data']['code']) ){
                    if(!isset($result['data']['message'])){
                        $result['data']['message'] = $result['message'];
                    }
                    $variation_api = false;
                    $message = is_array($result['data']['message']) ? $result['data']['message'] : [$result['message']];
                    $return = ['success'=>false,'message'=>$ensogo_variation_info['sku']."刊登失败",'error_message'=>$message];
                    $error_message[] = $return['message'];
                    $api_error_message[] = $return['message'];
                } else {
                    $request_id = $result['data']['data']['request_id'];
                    $variation_id = isset($result['data']['data']['variant_id']) ? $result['data']['data']['variant_id'] : json_encode($result);
                    self::_saveApiAjaxLog($puid,$online_product_id,$variation_id,$request_id);
                }
            }
        }
        //记录搬家日志
        self::_saveStoreMoveLog($log_id,$error_message,$api_error_message,$product_api,$variation_api,$wish_product_id);

        return $return;
    }

    /**
     * 速卖通搬家商品至ENSOGO
     * @param $product_info 商品信息
     * @param $variance_info 变种信息
     * @param $shipping_time 运输时间
     * @param $category_id 分类ID
     * @param $log_id 日志ID
     * @return array [success => false/true message=>"小老板错误信息" error_message => "接口错误信息"]
     */
    public static function smtStoreMove(&$product_info,&$variance_info,$shipping_time,$category_id,$log_id,$ensogo_site_id,$puid){
        $bool = self::_getAccessToken($ensogo_site_id);

        if(!$bool){
            return ['success'=>false,"message"=>'不存在ensogo账号',"error_message"=>""];
        }
        $return = ['success'=>true,"message"=>'',"error_message"=>""];
        $ensogo_product_info = self::_setProductInfo($product_info,$variance_info[0],$shipping_time,$category_id);
        $product_api = true;
        $variation_api = true;
        $error_message = [];
        $api_error_message = [];
        //发布商品信息
        $result = self::call("createProduct",$ensogo_product_info);
        error_log(var_export($result,true)."\r\n",3,"/tmp/smt.log");
        if( !isset($result['success']) || !$result['success'] || ( isset($result['data']['code']) && $result['data']['code']) ){//刊登失败
            if(!isset($result['data']['message'])){
                $result['data']['message'] = $result['message'];
            }
            $product_api =false;
            $message = is_array($result['data']['message']) ? $result['data']['message'] : [$result['message']];
            $return = ['success'=>false,'message'=>$variance_info[0]['sku']."刊登失败",'error_message'=>$message];
            $error_message[] = $return['message'];
            $api_error_message[] = $return['error_message'];
        } else {
            $online_product_id = $result['data']['data']['product_id'];
            $request_id = $result['data']['data']['request_id'];
            $variant_id = $result['data']['data']['variant_id'];
            //记录异步请求数据
            self::_saveApiAjaxLog($puid,$online_product_id,$variant_id,$request_id);
            unset($variance_info[0]);
            foreach($variance_info as $key => $variance){
                $ensogo_variation_info = self::_getVariationInfo($variance,$online_product_id,$shipping_time);
                $result = self::call("createProductVariants",$ensogo_variation_info);
                error_log(var_export($result,true)."\r\n",3,"/tmp/smt.log");
                if(  !isset($result['success']) || !$result['success'] || ( isset($result['data']['code']) && $result['data']['code']) ){
                    if(!isset($result['data']['message'])){
                        $result['data']['message'] = $result['message'];
                    }
                    $variation_api = false;
                    $message = is_array($result['data']['message']) ? $result['data']['message'] : [$result['message']];
                    $return = ['success'=>false,'message'=>$ensogo_variation_info['sku']."刊登失败",'error_message'=>$message];
                    $error_message[] = $return['message'];
                    $api_error_message[] = $return['message'];
                } else {
                    $request_id = $result['data']['data']['request_id'];
                    $variation_id = isset($result['data']['data']['variant_id']) ? $result['data']['data']['variant_id'] : json_encode($result);
                    //error_log(var_export($result)."\r\n",3,"/tmp/smt_ensogo.log");
                    self::_saveApiAjaxLog($puid,$online_product_id,$variation_id,$request_id);
                }
            }
        }
        //记录搬家日志
        self::_saveStoreMoveLog($log_id,$error_message,$api_error_message,$product_api,$variation_api,0);

        return $return;
    }

    /**
     * 添加搬家日志
     * @param $puid 小老板用户ID
     * @param $site_id 待搬家账户ID
     * @param $total 搬家总商品数（但是商品的个数 不算变种）
     * @param $shipping_time 运输时间
     * @param $category_id ENSOGO分类ID
     * @param $platform 搬家源渠道
     * @return array
     */
    public static function saveEnsogoStoreMoveLog($puid,$site_id,$total,$shipping_time,$category_id,$platform){
        $store_obj = new EnsogoStoreMoveLog();
        $store_obj->puid = $puid;
        $store_obj->store_id = $site_id;
        $store_obj->platform = $platform;
        $store_obj->move_number = $total;
        $store_obj->success_number = 0;
        $store_obj->error_api_message = "";
        $store_obj->error_message = "";
        $store_obj->shipping_time = $shipping_time;
        $store_obj->category_id = $category_id;
        $store_obj->wish_product_id = 0;
        $store_obj->create_time = time();
        $store_obj->update_time = time();
        $bool = $store_obj->save(false);
        if($bool){
            $result = ["success"=>true,"message"=>"添加成功","id"=>$store_obj->id];
        } else {
            $result = ["success"=>false,"message"=>$store_obj->getErrors()];
        }
        return $result;
    }

    /**
     *  保存创建商品变种接口查询参数
     * @param $puid
     * @param $product_id
     * @param $variation_id
     * @param $request_id
     * @return int
     */
    public static function _saveApiAjaxLog($puid,$product_id,$variation_id,$request_id){
        $api_obj = new EnsogoApiAjaxLog();
        $api_obj->puid = $puid;
        $api_obj->product_id = $product_id;
        $api_obj->variation_id = $variation_id;
        $api_obj->request_id = $request_id;
        $api_obj->error_message = "";
        $api_obj->create_time = time();
        $api_obj->update_time = time();
        $bool = $api_obj->save(false);
        return $bool ? $api_obj->id : 0 ;
    }

    /**
     * @param $wish_product_info
     * @param $wish_variance_info
     * @param $shipp_time
     * @param $category_id
     * @return array
     */
    private static function _setProductInfo(&$product_info,&$variance_info,$shipping_time,$category_id){
        $product = [
            'name'=> $product_info['name'],
            'description'=>$product_info['description'],
            'tags'=>$product_info['tags'],
            'sku'=>$variance_info['sku'],
            'variant_name'=>self::_getVariantName($variance_info),
            'main_image'=>$product_info['main_image'],
            'category_id' => $category_id ,
            'countries' => self::_getCountries(),
            'prices'=>self::_getDataByCountries($variance_info['price']),
            'price' => $variance_info['price'],
            'shippings'=> self::_getDataByCountries($variance_info['shipping']),
            'shipping' => $variance_info['shipping'],
            'inventory' => $variance_info['inventory'],
            'parent_sku' => $variance_info['sku'],
            'sold_in_countries' => self::_getCountries()
        ];

        if(!empty($variance_info['color'])) {
            $product['color'] = $variance_info['color'];
        }
        if(!empty($variance_info['size'])) {
            $product['size'] = $variance_info['size'];
        }
        if(!empty($product_info['msrp']) && $product_info['msrp'] != '0.00'){
            $product['msrps'] = self::_getDataByCountries($product_info['msrp']);
        }
        if(!empty($shipping_time)){
            $product['shipping_time'] = $shipping_time;
        }
        if(!empty($product_info['brand'])){
            $product['brand'] = $product_info['brand'];
        }
        if(!empty($product_info['landing_page_url'])){
            $product['landing_page_url'] = $product_info['landing_page_url'];
        }
        if(!empty($product_info['upc'])){
            $product['upc'] = $product_info['upc'];
        }
        $extra_images = self::_getExtraImages($product_info);
        if(!empty($extra_images)){
            $product['extra_images'] = $extra_images;
        }

        return $product;
    }

    private static function _getVariationInfo($variance_info,$online_product_id,$shipping_time){
        $variance = [
            'product_id' => $online_product_id,
            'sku' => $variance_info['sku'],
            'name' => self::_getVariantName($variance_info),
            'inventory' => $variance_info['inventory'],
            'countries' => self::_getCountries(),
            'prices' => self::_getDataByCountries($variance_info['price']),
            'shippings' => self::_getDataByCountries($variance_info['shipping']),
        ];

        if(!empty($variance_info['color'])){
            $variance['color'] = $variance_info['color'];
        }
        if(!empty($variance_info['size'])){
            $variance['size'] = $variance_info['size'];
        }
        if(!empty($shipping_time)){
            $variance['shipping_time'] = $shipping_time;
        }
        #WISH不存在该字段 msrps
        return $variance;
    }

    private static function _getCountries(){
        return 'hk|th|id|ph|sg|my|us';
        // return 'hk|th|id|ph|sg|my';
    }

    private static function _getDataByCountries($info){
        for($i=0;$i<6;$i++){
            $data[] = $info;
        }
        return join('|',$data);
    }

    /**
     * 获取商品名称
     * @param $variance_info
     * @return string
     */
    private static function _getVariantName($variance_info){
        $name = '';
        if(!empty($variance_info['color']) || !empty($variance_info['size'])){
            if(!empty($variance_info['color'])){
                $name = $variance_info['color'];
            }
            if(!empty($variance_info['size'])){
                $name = empty($name) ? $variance_info['size'] : $name.'-'.$variance_info['size'];
            }
        }else{
            $name = $variance_info['sku'];
        }
        return $name;
    }

    /**
     * 获取附图信息
     * @param $data
     * @return string
     */
    private static function _getExtraImages($data){
        $extra_image = [];
        for($i=1;$i<=10;$i++){
            if(isset($data["extra_image_{$i}"]) && !empty($data["extra_image_{$i}"])){
                $extra_image[] = $data["extra_image_{$i}"];
            }
        }
        return empty($extra_image) ? "" : join("|",$extra_image);
    }

    private static function _saveStoreMoveLog($log_id,$error_messages,$api_error_messages,$product_api,$variation_api,$wish_product_id = 0){
        $ensogo_obj = EnsogoStoreMoveLog::find()->where(["id"=>$log_id])->one();
        if($product_api && $variation_api){
            $ensogo_obj->success_number = $ensogo_obj->success_number + 1;
        }
        if(!empty($ensogo_obj->error_message)){
            $error_messages = json_decode($ensogo_obj->error_message,true) + $error_messages;
        }
        if(!empty($error_messages)){
            $ensogo_obj->error_message = json_encode($error_messages);
        }
        if(!empty($ensogo_obj->api_error_message)){
            $api_error_messages = json_decode($ensogo_obj->api_error_message,true) + $api_error_messages;
        }
        if(!empty($api_error_messages)){
            $ensogo_obj->error_api_message = json_encode($api_error_messages);
        }
        if(!empty($wish_product_id)){
            $wish_product = empty($ensogo_obj->wish_product_id) || $ensogo_obj->wish_product_id == 0 ? [$wish_product_id] : explode(',',$ensogo_obj->wish_product_id);
            $ensogo_obj->wish_product_id = join(',',$wish_product);
        }
        $ensogo_obj->update_time = time();
        $ensogo_obj->save(false);
    }



}
