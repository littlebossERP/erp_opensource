<?php

namespace eagle\modules\tracking\controllers;

use Yii;
use yii\web\Controller;
use eagle\modules\message\models\CsRecommendProduct;
use yii\data\Pagination;
use eagle\modules\message\models\CsRecmProductPerform;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\LtCustomizedRecommendedGroup;
use eagle\models\LtCustomizedRecommendedProd;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ConfigHelper;


class TrackingRecommendProductController extends \eagle\components\Controller{
    public $enableCsrfValidation = FALSE;
    
    public function actionProductList(){
        if(empty($_REQUEST['product_startdate'])&&empty($_REQUEST['product_enddate'])){
            $initial=array();
            $initial['star']=date('Y-m-d',time()-7*24*3600);
            $initial['end']=date('Y-m-d',time());
        }else{
            $initial=array();
            $initial['star']=$_REQUEST['product_startdate'];
            $initial['end']=$_REQUEST['product_enddate'];
        }
        
        
        if($_REQUEST==null){
            $sql="select product_id, sum(view_count) as total_view_count, sum(click_count) as total_click_count from cs_recm_product_perform where (theday >='{$initial['star']}' and theday <='{$initial['end']}' ) group by product_id";             
            $counts=CsRecmProductPerform::findBySql($sql)->asArray()->all();
            if(count($counts)>0){
                $all_id=array();
                foreach ($counts as $count){
                    $all_id[]=$count['product_id'];
                }
            }
            if(empty($all_id)){
                $allrecord=CsRecommendProduct::find()->where(['in','id',-1]);
            }else{
                $allrecord=CsRecommendProduct::find()->where(['in','id',$all_id]);
            }
        }else if((empty($_REQUEST['product_startdate'])&&empty($_REQUEST['product_enddate']))&&empty($_REQUEST['seller_account'])){   //没日期，没账户
            $initial['star']=null;
            $initial['end']=null;
            $sql="select product_id, sum(view_count) as total_view_count, sum(click_count) as total_click_count from cs_recm_product_perform group by product_id";
            $counts=CsRecmProductPerform::findBySql($sql)->asArray()->all();
            $allrecord=CsRecommendProduct::find();
        }else if((!empty($_REQUEST['product_startdate'])&&!empty($_REQUEST['product_enddate']))&&empty($_REQUEST['seller_account'])){ //有日期没账户
            $sql="select product_id, sum(view_count) as total_view_count, sum(click_count) as total_click_count from cs_recm_product_perform where (theday >='{$initial['star']}' and theday <='{$initial['end']}' ) group by product_id";             
            $counts=CsRecmProductPerform::findBySql($sql)->asArray()->all();
            if(count($counts)>0){
                $all_id=array();
                foreach ($counts as $count){
                    $all_id[]=$count['product_id'];
                }
            }
            if(empty($all_id)){
                $allrecord=CsRecommendProduct::find()->where(['in','id',-1]);
            }else{
                $allrecord=CsRecommendProduct::find()->where(['in','id',$all_id]);
            }
        }else if((empty($_REQUEST['product_startdate'])&&empty($_REQUEST['product_enddate']))&&!empty($_REQUEST['seller_account'])){  //有账户，没日期
            $initial['star']=null;
            $initial['end']=null;
            $sql="select product_id, sum(view_count) as total_view_count, sum(click_count) as total_click_count from cs_recm_product_perform group by product_id";
            $counts=CsRecmProductPerform::findBySql($sql)->asArray()->all();
            $accountid=$_REQUEST['seller_account'];
            $allrecord=CsRecommendProduct::find()->where(['platform_account_id'=>$accountid]);
        }else{                       //日期账户都有 
            $sql="select product_id, sum(view_count) as total_view_count, sum(click_count) as total_click_count from cs_recm_product_perform where (theday >='{$initial['star']}' and theday <='{$initial['end']}' ) group by product_id";             
            $counts=CsRecmProductPerform::findBySql($sql)->asArray()->all();
            if(count($counts)>0){
                $all_id=array();
                foreach ($counts as $count){
                    $all_id[]=$count['product_id'];
                }
            }
            if(empty($all_id)){
                $allrecord=CsRecommendProduct::find()->where(['in','id',-1]);
            }else{
                $allrecord=CsRecommendProduct::find()->where(['and',"platform_account_id='{$_REQUEST['seller_account']}'",['in','id',$all_id]]);
            }
        }
        

        $account=CsRecommendProduct::find()->distinct('platform_account_id')->select('platform_account_id')->all();//平台帐号
        $pages = new Pagination(['totalCount' =>$allrecord->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);//defaultPageSize默认页数,'params'=>$_REQUEST带参过滤
        $models = $allrecord->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('product-list',['products'=>$models,'pages'=>$pages,'accounts'=>$account,'date'=>$initial,'counts'=>$counts,]);
    }
    
    public function actionCustomProductList(){
        $puid = \Yii::$app->user->identity->getParentUid();
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        
        $query = LtCustomizedRecommendedProd::find()->where(["puid"=>$puid]);
        
        if(!empty($_POST['platform_search'])){
            $query->andWhere(['platform'=>$_POST['platform_search']]);
        }
        if(!empty($_POST['seller_search'])){
            $query->andWhere(['seller_id'=>$_POST['seller_search']]);
        }
        if(!empty($_POST['condition_search'])){
            $query->andWhere(['and', "title like :search or sku like :search "],[':search'=>'%'.$_POST['condition_search'].'%']);
        }
        
        //只显示有权限的账号，lrq20170828
        $platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
        foreach($platformAccountInfo as $key => $val){
        	if(is_array($val)){
        		foreach($val as $key2 => $val2){
        			$selleruserids[] = $key2;
        		}
        	}
        }
        $query->andWhere(['seller_id'=>$selleruserids]);
        
        $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
        $prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        
        return $this->render('custom-product-list',['data'=>$prodData,'pages'=>$pages,'platform'=>$platforms]);
    }
    
    public function actionCustomizedRecommendedProduct(){
        $puid = \Yii::$app->user->identity->getParentUid();
        //取缓存
        
        $userHabit = json_decode(ConfigHelper::getConfig("ErciCustomeProduct/CustomUserHabit","NO_CACHE"),true);
        
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        
        $groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
        $group_array = array();
        if(!empty($groups)){
            foreach ($groups as $detail_group){
                $group_array[$detail_group['id']] = $detail_group['group_name'];
            }
        }
        return $this->renderAjax('recommended-product-list',['group_array'=>$group_array,'platform_array'=>$platforms,'userHabit'=>$userHabit]);
    }
    
    public function actionSaveProduct(){
        
        $puid = \Yii::$app->user->identity->getParentUid();
        if($puid == 0||empty($puid)){
            return ResultHelper::getResult(400,'','帐号信息有误，保存失败');
        }
        $nowTime = time();
        if(isset($_POST['saveType'])){
            if($_POST['saveType'] == 'save'){
                $new_product = new LtCustomizedRecommendedProd();
                $new_product->puid = $puid;
                $new_product->seller_id = $_POST['seller_id'];
                $new_product->platform = $_POST['platform'];
                $new_product->product_name = $_POST['product_name'];
                $new_product->title = $_POST['title'];
                $new_product->photo_url = $_POST['photo_url'];
                $new_product->product_url = $_POST['product_url'];
                $new_product->price = $_POST['price'];
                $new_product->currency = $_POST['currency'];
                $new_product->sku = $_POST['sku'];
                $new_product->comment = $_POST['comment'];
                $new_product->group_id = $_POST['group_name'];
                $new_product->create_time = $nowTime;
                
                if(!$new_product->save(false)){
                    \Yii::error('$new_product->save() fail , error:'.print_r($new_product->errors,true),"file");
                    return ResultHelper::getResult(400, '', "自定义商品保存失败，".$new_product->errors);
                }else{
                    //假如保存商品的时候，保存到商品组，增加相关的数量
                    if($_POST['group_name'] != ''){
                        $group_count = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid,'id'=>$_POST['group_name']])->one();
                        $group_count->member_count = $group_count->member_count + 1;
                        $group_count->save(false);
                    }
                    //select框设置缓存
                    $customUserHabit = array();
                    $customUserHabit['platformHabit'] = $_POST['platform'];
                    $customUserHabit['userHabit'] =  $_POST['seller_id'];
                    $customUserHabit['currencyHabit'] = $_POST['currency'];
                    ConfigHelper::setConfig("ErciCustomeProduct/CustomUserHabit",json_encode($customUserHabit));
                    
                    return ResultHelper::getResult(200, '', "自定义商品保存成功。");
                }
                
            }else if($_POST['saveType'] == 'edit'){
                $one_product = LtCustomizedRecommendedProd::find()->where(['id'=>$_POST['prouductId']])->one();
                if(empty($one_product)){
                    return ResultHelper::getResult(400,'','没有找到相关的产品');
                }else{
                    //检查该商品是否有转换商品组
                    if($one_product->group_id == $_POST['group_name']){
                        $has_change = false;
                    }else{
                        $has_change = true;
                        $orgin_group_id = $one_product->group_id;
                        $new_group_id = $_POST['group_name'];
                    }
                    $one_product->seller_id = $_POST['seller_id'];
                    $one_product->platform = $_POST['platform'];
                    $one_product->product_name = $_POST['product_name'];
                    $one_product->title = $_POST['title'];
                    $one_product->photo_url = $_POST['photo_url'];
                    $one_product->product_url = $_POST['product_url'];
                    $one_product->price = $_POST['price'];
                    $one_product->currency = $_POST['currency'];
                    $one_product->sku = $_POST['sku'];
                    $one_product->comment = $_POST['comment'];
                    $one_product->group_id = $_POST['group_name'];
                    $one_product->update_time = $nowTime;
                    
                    if(!$one_product->save(false)){
                        \Yii::error('$new_product->save() fail , error:'.print_r($new_product->errors,true),"file");
                        return ResultHelper::getResult(400, '', "编辑商品失败，".$new_product->errors);
                    }else{
                        //假如保存商品的时候，保存到商品组，增加或减少相关的数量
                        if($has_change){
                            if($orgin_group_id != ''){
                                $group_add_count = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid,'id'=>$orgin_group_id])->one();
                                $group_add_count->member_count = $group_add_count->member_count - 1;
                                $group_add_count->save(false);
                            }
                            if($new_group_id != ''){
                                $group_reduce_count = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid,'id'=>$new_group_id])->one();
                                $group_reduce_count->member_count = $group_reduce_count->member_count + 1;
                                $group_reduce_count->save(false);
                            }
                        }
                        return ResultHelper::getResult(200, '', "编辑商品成功。");
                    }
                }
            }
        }else{
            return ResultHelper::getResult(400,'','保存类型有误，保存失败');
        }
//         print_r($_POST);
//         return ResultHelper::getResult(200,'','');
    }
    
    public function actionEditProduct(){
        $puid = \Yii::$app->user->identity->getParentUid();
        
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        $data = LtCustomizedRecommendedProd::find()->where(['id'=>$_POST['product_id']])->asArray()->one();
        
        $groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
        $group_array = array();
        $groups_detail = array();
        if(!empty($groups)){
            foreach ($groups as $detail_group){
                $group_array[$detail_group['id']] = $detail_group['group_name'];
                $groups_detail[$detail_group['id']] = $detail_group;
            }
        }
        return $this->renderAjax('recommended-product-list',['data'=>$data,'group_array'=>$group_array,'platform_array'=>$platforms,'groups_detail'=>$groups_detail]);
    }
    
    public function actionNewGroup(){
        $puid = \Yii::$app->user->identity->getParentUid();
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        return $this->renderAjax('custom-group',['platform_array'=>$platforms]);
    }
    
    public function actionSaveGroup(){
        
        $puid = \Yii::$app->user->identity->getParentUid();
        if($puid == 0||empty($puid)){
            return ResultHelper::getResult(400,'','帐号信息有误，保存失败');
        }
        $nowTime = time();
        if(isset($_POST['saveType'])){
            if($_POST['saveType'] == 'save'){
                $new_product = new LtCustomizedRecommendedGroup();
                $new_product->puid = $puid;
                $new_product->seller_id = $_POST['seller_id'];
                $new_product->platform = $_POST['platform'];
                $new_product->group_name = $_POST['group_name'];
                $new_product->group_comment = $_POST['group_comment'];
                $new_product->create_time = $nowTime;
        
                if(!$new_product->save(false)){
                    \Yii::error('$new_product->save() fail , error:'.print_r($new_product->errors,true),"file");
                    return ResultHelper::getResult(400, '', "商品组保存失败，".$new_product->errors);
                }else{
                    return ResultHelper::getResult(200, '', "商品组保存成功。");
                }
        
            }
        }else{
            return ResultHelper::getResult(400,'','保存类型有误，保存失败');
        }
    }
    
    public function actionAddProductsToGroup(){
        $puid = \Yii::$app->user->identity->getParentUid();
        
        $ids = '';
        if(!empty($_POST['ids'])){
            $ids = json_encode($_POST['ids']);
        }
        
        $groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
        $group_array = array();
        if(!empty($groups)){
            foreach ($groups as $detail_group){
                $group_array[$detail_group['id']] = $detail_group['group_name'];
            }
        }
        return $this->renderAjax('add-product-group',['ids'=>$ids,'group_array'=>$group_array]);
    }
    
    public function actionAddProductsToGroupSave(){
        $puid = \Yii::$app->user->identity->getParentUid();
        
        if(!empty($_POST['product_ids'])&&!empty($_POST['group_name'])){
            $product_ids = json_decode($_POST['product_ids'],true);
            //批量加入商品组，改变商品组的数量以及检查是否符合加入商品组
            $orgin_group_id = '';
            $new_group_id = '';
            $add_result = [
              'success'=>0,
              'fail'=>0  
            ];
            $group_data = LtCustomizedRecommendedGroup::find()->where(['id'=>$_POST['group_name'],'puid'=>$puid])->one();
            if(empty($group_data)){
                return ResultHelper::getResult(400,'','添加到商品组失败，没有相关商品组数据');
            }
            $group_data_count = $group_data->member_count;
            
            $custom_prouduct = LtCustomizedRecommendedProd::find()->where(['id'=>$product_ids,'puid'=>$puid])->all();
            if(!empty($custom_prouduct)&&!empty($group_data)){
                foreach ($custom_prouduct as $detail_prouduct){
                    $orgin_group_id = $detail_prouduct->group_id;
                    $new_group_id = $_POST['group_name'];
                    if($detail_prouduct->platform == $group_data->platform && $detail_prouduct->seller_id == $group_data->seller_id && $detail_prouduct->group_id != $_POST['group_name']){//平台与帐号必须要一致
                        $detail_prouduct->group_id = $_POST['group_name'];
                        if($detail_prouduct->save(false)){
                            $group_data_count = $group_data_count + 1;
                            $add_result['success'] = $add_result['success'] + 1;
                            if($orgin_group_id != ''){//假如保存成功且原来的商品组id不为空，需要维护被移走的商品组数量
                               $orgin_group_data = LtCustomizedRecommendedGroup::find()->where(['id'=>$orgin_group_id,'puid'=>$puid])->one();
                               if(!empty($orgin_group_data)){
                                   $orgin_group_data->member_count = $orgin_group_data->member_count - 1;
                                   $orgin_group_data->save(false);
                               }
                            }
                        }else{
                            $add_result['fail'] = $add_result['fail'] + 1;
                        }
                    }else{
                        $add_result['fail'] = $add_result['fail'] + 1;
                    }
                }
                $group_data->member_count = $group_data_count;//统计
                if($group_data->save(false)){
                    return ResultHelper::getResult(200,$add_result,'添加到商品组完成');
                }
            }else{
                return ResultHelper::getResult(400,'','添加到商品组失败，没有相关自定义商品数据');
            }
//             $custom_prouduct = new LtCustomizedRecommendedProd();   
//             if($custom_prouduct::updateAll(['group_id'=>$_POST['group_name']],['id'=>$product_ids])){
//                 return ResultHelper::getResult(200,'','添加到商品组成功');
//             }else{
//                 return ResultHelper::getResult(400,'','添加到商品组失败');
//             }
        }else{
            return ResultHelper::getResult(400,'','选择商品参数有误!');
        }
    }
    
    public function actionDeleteProduct(){
        if(empty($_POST['id'])){
            return ResultHelper::getResult(400,'','删除失败，删除数据有误!');
        }else{
            $one_product = LtCustomizedRecommendedProd::findOne($_POST['id']);
            if(empty($one_product)){
                return ResultHelper::getResult(400,'','删除失败，没有该自定义商品!');
            }else{
                $one_group_id = $one_product->group_id;
                $one_product->delete();
                if($one_group_id != ''){
                    $old_group = LtCustomizedRecommendedGroup::findOne($one_group_id);
                    if(!empty($old_group)){
                        $old_group->member_count = $old_group->member_count - 1;
                        $old_group->save(false);
                    }
                }
                return ResultHelper::getResult(200,'','删除成功!');
            }
        }
    }
    
    public function actionGroupList(){
        $puid = \Yii::$app->user->identity->getParentUid();
        $query =LtCustomizedRecommendedGroup::find()->where(["puid"=>$puid]);
        
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        
        if(!empty($_POST['platform_search'])){
            $query->andWhere(['platform'=>$_POST['platform_search']]);
        }
        if(!empty($_POST['seller_search'])){
            $query->andWhere(['seller_id'=>$_POST['seller_search']]);
        }
        if(!empty($_POST['condition_search'])){
            $query->andWhere(['and', "group_name like :search or group_comment like :search "],[':search'=>'%'.$_POST['condition_search'].'%']);
        }
        
        //只显示有权限的账号，lrq20170828
        $platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
        foreach($platformAccountInfo as $key => $val){
        	if(is_array($val)){
        		foreach($val as $key2 => $val2){
        			$selleruserids[] = $key2;
        		}
        	}
        }
        $query->andWhere(['seller_id'=>$selleruserids]);
        
        $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
        $prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        
        return $this->render('group-list',['data'=>$prodData,'pages'=>$pages,'platform'=>$platforms]);
    }
    
    public function actionEditGroupList(){
        $puid = \Yii::$app->user->identity->getParentUid();
        
        $platforms = [];
        $binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
        if(!empty($binding_account)){//查看绑定平台
            foreach ($binding_account as $account_key =>$account_value){
                if($account_key){
                    $platforms[$account_key] = $account_key;
                }
            }
        }
        $one_group = LtCustomizedRecommendedGroup::findOne($_REQUEST['id']);
        //获取该平台下、该puid下的所有帐号
        $seller_array = array();
        if(!empty($one_group)){
            $account_result = PlatformAccountApi::getPlatformAllAccount($puid, $one_group['platform']);
            if($account_result['success']){
                foreach ($account_result['data'] as $seller_key => $seller_value){
                    $seller_array[$seller_key] = $seller_value;
                }
            }
        }
        
        $query = LtCustomizedRecommendedProd::find()->where(["puid"=>$puid,"group_id"=>$_REQUEST['id']]);
        $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
        $prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
    
        return $this->render('edit-group-list',['data'=>$prodData,'pages'=>$pages,'group_data'=>$one_group,'platform_array'=>$platforms,'seller_array'=>$seller_array]);
    }
    
    public function actionEditGroupListSave(){
        if(!isset($_POST['groupId'])){
            return ResultHelper::getResult(400,'','商品组数据有误，保存失败！');
        }
        $puid = \Yii::$app->user->identity->getParentUid();
        $one_product = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid,'id'=>$_POST['groupId']])->one();
        if(empty($one_product)){
            return ResultHelper::getResult(400,'','没有找到相关的商品组');
        }else{
            $one_product->seller_id = $_POST['seller_id'];
            $one_product->platform = $_POST['platform'];
            $one_product->group_name = $_POST['group_name'];
            $one_product->group_comment = $_POST['group_comment'];
            $one_product->update_time = time();
    
            if(!$one_product->save(false)){
                \Yii::info('$new_product->save() fail , error:'.print_r($new_product->errors,true),"file");
                return ResultHelper::getResult(400, '', "编辑商品组失败，".$new_product->errors);
            }else{
                return ResultHelper::getResult(200, '', "编辑商品组成功。");
            }
        }
    }
    
    public function actionDeleteGroup(){
        if(empty($_POST['id'])){
            return ResultHelper::getResult(400,'','删除失败，删除数据有误!');
        }else{
            $one_product = LtCustomizedRecommendedGroup::findOne($_POST['id']);
            if(empty($one_product)){
                return ResultHelper::getResult(400,'','删除失败，没有该自定义商品分组!');
            }else{
                if($one_product->delete()){
                	$group_prods = LtCustomizedRecommendedProd::find()->where(['group_id'=>$_POST['id']])->asArray()->all();
                	if(!empty($group_prods)){//分组有商品
	                    $custom_prouduct = new LtCustomizedRecommendedProd();
	                    if($custom_prouduct::updateAll(['group_id'=>NULL],['group_id'=>$_POST['id']])){
	                        return ResultHelper::getResult(200,'','删除成功!');
	                    }else{
	                        return ResultHelper::getResult(200,'','删除商品组失败.');
	                    }
                	}else //分组没有商品
                		return ResultHelper::getResult(200,'','删除成功!');
                }else{
                    return ResultHelper::getResult(200,'','删除商品组失败!');
                }
                
            }
        }
        
    }
    
    public function actionGetGroupInfo(){
        $info = array();
        $one_product = LtCustomizedRecommendedGroup::findOne($_POST['group_id']);
        if(!empty($one_product)){
            $info = [
              'platform'=>$one_product['platform'],
              'seller'=>$one_product['seller_id']  
            ];
            return ResultHelper::getResult(200,$info,'获取商品组属性失败!');
        }else{
            return ResultHelper::getResult(400,'','获取商品组属性失败!');
        }
    }
    
    public function actionGetPlatformAccounts(){
        $puid = \Yii::$app->user->identity->getParentUid();
        
        if(empty($_POST['platform'])){
            return ResultHelper::getResult(400,'','获取平台失败!');
        }else{
           //$account_result = PlatformAccountApi::getPlatformAllAccount($puid, $_POST['platform']);
        	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts($_POST['platform']);
           if(!empty($account_data)){
               return ResultHelper::getResult(200,$account_data,'');
           }else{
               return ResultHelper::getResult(400,'','该平台没有相关帐号信息');
           }
        }
    }
}