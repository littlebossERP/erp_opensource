<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use common\helpers\Helper_Curl;
use Yii;
use eagle\models\EbayCategory;
use eagle\models\EbayCategoryfeature;
use eagle\models\EbaySite;
/**
 * 获得eBay分类属性
 * @package interface.ebay.tradingapi
 */ 
class getcategoryfeatures extends base{
    public $verb = 'GetCategoryFeatures';
    /**
     * [api description]
     * @Author
     * @Editor willage 2016-11-20T09:46:18+0800
     */
    public function api($CategoryID=0){
        // $cache=$this->xmlCache($CategoryID);
        // if ($cache){
        //     echo "use cache version\n";
        //     return parent::xmlparse($cache);
        // }
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            'DetailLevel'=>'ReturnAll',//ReturnSummary
            'ViewAllNodes'=>true,
//          'FeatureID'=>'ReturnPolicyEnabled',
            'CategoryID'=>$CategoryID,
//        	'FeatureID'=>'ItemSpecificsEnabled,BestOfferEnabled',
        );
        $xmlArr['OutputSelector']='SiteDefaults,Category.CategoryID,Category.ConditionEnabled,Category.ConditionValues,Category.VariationsEnabled,Category.ISBNEnabled,Category.UPCEnabled,Category.EANEnabled';
        if($CategoryID==0){
            unset($xmlArr['CategoryID']);
        }
        try{
            Helper_Curl::$timeout=600;
            Helper_Curl::$connecttimeout=600;
            $xml=$this->sendHttpRequest(array('GetCategoryFeaturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
           ),1,600);
        }catch(EbayInterfaceException_Connection_Timeout $ex){
            echo $ex->getMessage();
        }
        // print_r($xml,false);
        // $result=parent::xmlParseWithAttr($xml);
        if ($this->responseIsSuccess()){
            $this->xmlCache($CategoryID,$xml);
        }
        $cache=$this->xmlCache($CategoryID);
        return parent::xmlparse($cache);

    }

    public function apiVersion(){
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            // 'DetailLevel'=>'ReturnSummary'
        );
        try{
            Helper_Curl::$timeout=600;
            Helper_Curl::$connecttimeout=600;
            $xml=$this->sendHttpRequest(array('GetCategoryFeaturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
           ),1,600);
        }catch(EbayInterfaceException_Connection_Timeout $ex){
            echo $ex->getMessage();
        }
        if ($this->responseIsSuccess()){
            $this->xmlCache(NULL,$xml);
        }
        $cache=$this->xmlCache(NULL);
        return parent::xmlparse($cache);

    }

    /**
     * 取得一个 站点下的一些信息 
     * 
     */
    public function requestAll($category=null){
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            'DetailLevel'=>'ReturnAll',
        	'ViewAllNodes'=>true,
        );
        if(isset($category)){
        	$xmlArr['CategoryID']=$category;
        }
        $xmlArr['OutputSelector']='SiteDefaults,Category.CategoryID,Category.ConditionEnabled,Category.ConditionValues,Category.VariationsEnabled,Category.ISBNEnabled,Category.UPCEnabled,Category.EANEnabled';
    	try{
    	   Helper_Curl::$timeout=600;
    	   Helper_Curl::$connecttimeout=600;
    	   $xml=$this->sendHttpRequest(array(
    	       'GetCategoryFeaturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
    	   ),0,600);
    	}catch(EbayInterfaceException_Connection_Timeout $ex){
    	   echo $ex->getMessage();
    	}
        return $xml;
    }
    public function getSiteDefaults($category=null){
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            'DetailLevel'=>'ReturnAll',
            'ViewAllNodes'=>true,
        );
        if(isset($category)){
            $xmlArr['CategoryID']=$category;
        }
        $xmlArr['OutputSelector']='SiteDefaults,FeatureDefinitions,CategoryVersion';
        try{
           Helper_Curl::$timeout=600;
           Helper_Curl::$connecttimeout=600;
           $xml=$this->sendHttpRequest(array(
               'GetCategoryFeaturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
           ),1,600);
        }catch(EbayInterfaceException_Connection_Timeout $ex){
           echo $ex->getMessage();
        }
        // return $xml;
        $result=parent::xmlParseWithAttr($xml);
        return $result;
    }
    public function getCategoryFeatures($category=null){
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            'DetailLevel'=>'ReturnAll',
            'ViewAllNodes'=>true,
        );
        if(isset($category)){
            $xmlArr['CategoryID']=$category;
        }
        $xmlArr['OutputSelector']='Category';
        try{
           Helper_Curl::$timeout=600;
           Helper_Curl::$connecttimeout=600;
           $xml=$this->sendHttpRequest(array(
               'GetCategoryFeaturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArr
           ),1,600);
        }catch(EbayInterfaceException_Connection_Timeout $ex){
           echo $ex->getMessage();
        }
        // return $xml;
        $result=parent::xmlParseWithAttr($xml);
        return $result;
    }

   public function syncSiteDefaults(){
        //No.1-同步feature
        echo "start sync SiteDefaults\n";
        $xmlArr=$this->getSiteDefaults();
        // print_r($xmlArr,false);
        if ($xmlArr['Ack']=='Failure') {//响应错误处理
            echo(print_r($xmlArr['Errors'],false)."\n");
            return;
        }
        $sArr=$xmlArr['SiteDefaults'];
        $ECF=EbaySite::find()->where('siteid=:s',[':s'=>$this->siteID])->one();
        if (empty($ECF)){
            echo "no site default record\n";
            return;
        }
        echo "start analyze SiteDefaults\n";
        //@todo No.2-获取父features
        //No.4-保存指定CategoryID features
        $ECF->setAttributes([
            'feature_version'=>$xmlArr['CategoryVersion'],
            'feature_definitions'=>json_encode($xmlArr['FeatureDefinitions']['ListingDurations']),
            'feature_sitedefault'=>json_encode($sArr),
            'record_updatetime'=>time(),
        ]);
        $ECF->save(false);
    }

    public function syncCategoryFeatures($categoryid=null){
        //No.1-同步feature
        $xmlArr=$this->getCategoryFeatures($categoryid);
        // print_r($xmlArr,false);
        if ($xmlArr['Ack']=='Failure') {//响应错误处理
            echo(print_r($xmlArr['Errors'],false)."\n");
            return;
        }
        if(isset($xmlArr['Category'])){
            $xmlAllArr=$xmlArr['Category'];
        }else{
            echo("invalid CategoryID to get features\n");
            return;
        }
        $now=time();//使用同一时间
        $xmlC=$xmlArr['Category'];
        if(!empty($xmlC)){
            //No.2-没有字段,手工赋值
            if (!isset($xmlC['ConditionEnabled'])){
                $xmlC['ConditionEnabled']=NULL;
            }
            if (!isset($xmlC['VariationsEnabled'])){
                $xmlC['VariationsEnabled']=0;
            }
            if (!isset($xmlC['ConditionValues'])){
                $xmlC['ConditionValues']=NULL;
            }
            if (!isset($xmlC['ISBNEnabled'])){
                $xmlC['ISBNEnabled']=NULL;
            }
            if (!isset($xmlC['UPCEnabled'])){
                $xmlC['UPCEnabled']=NULL;
            }
            if (!isset($xmlC['EANEnabled'])){
                $xmlC['EANEnabled']=NULL;
            }
            //No.3-删除指定CategoryID features
            // EbayCategoryfeature::deleteAll('siteid=:s and categoryid=:c',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']]);
            //No.3-找到指定CategoryID features
            // $MECF= EbayCategoryfeature::find('siteid=:s and categoryid=:c',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])
            $MECF= EbayCategoryfeature::find()
                ->where(['siteid'=>$this->siteID])
                ->andwhere(['categoryid'=>$xmlC['CategoryID']])
                ->one();
            if (empty($MECF)) {//没有则新建
                $MECF = new EbayCategoryfeature();
            }
            //No.4-保存指定CategoryID features
            $MECF->setAttributes([
                'siteid'=>$this->siteID,
                'categoryid'=>$xmlC['CategoryID'],
                'conditionenabled'=>$xmlC['ConditionEnabled'],
                'conditionvalues'=>$xmlC['ConditionValues'],
                // 'conditionvalues'=>json_encode($xmlC['ConditionValues']),
                'variationsenabled'=>@$xmlC['VariationsEnabled'],
                'isbnenabled'=>@$xmlC['ISBNEnabled'],
                'upcenabled'=>@$xmlC['UPCEnabled'],
                'eanenabled'=>@$xmlC['EANEnabled'],
                'category_feature'=>json_encode($xmlC),
                'record_updatetime'=>$now,
            ]);
            $MECF->save(false);
            //No.5-删除指定CategoryID features
            EbayCategoryfeature::deleteAll('siteid=:s and categoryid=:c and record_updatetime < :recordtime',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID'],':recordtime'=>($now-3600)]);
            //No.6-兼容过去版本，多属性enable到EbayCategory
             if (@$xmlC['VariationsEnabled']=='true'){
                 $category=EbayCategory::find()->where('siteid=:s and categoryid=:c and leaf =1',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])->one();
                 if (!empty($category)){
                     $category->variationenabled=1;
                     $category->save(false);
                 }
             }
             if (@$xmlC['VariationsEnabled']=='false'||!isset($xmlC['VariationsEnabled'])){
                 $category=EbayCategory::find()->where('siteid=:s and categoryid=:c and leaf =1',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])->one();
                 if (!empty($category)){
                     $category->variationenabled=0;
                     $category->save(false);
                 }
             }
        }

    }
    /**
     * [syncLeafFeaturesBatch 批量保存所有类目相关的 ,只有有相关信息的才保存 ]
     * 未完成（按站点批量下载）
     * @Author
     * @Editor willage 2016-11-20T09:31:28+0800
     */
    public function syncLeafFeaturesBatch($categoryid=null){
        //No.1-同步feature
        $xmlArr=$this->api($categoryid);
        if(isset($xmlArr['Category'])){
            $xmlAllArr=$xmlArr['Category'];
        }else{
            echo("invalid CategoryID to get features");
            return;
        }
        $sArr=$xmlArr['SiteDefaults'];
        $batchInsertArr=array();
        $categorys=EbayCategory::findBySql('select siteid from ebay_category where leaf=1 AND siteid='.$this->siteID)->asArray()->all();
        //No.2-创建缓冲新表
        
        //No.3-批量插入
        echo "start establish category feature data!\n";
        echo "categorys count: ".count($categorys)."\n";
        if(isset($xmlAllArr) && (!empty($categorys))){
            foreach ($categorys as $c){//遍历叶目录
                foreach($xmlAllArr as $key=>$xmlCArr){
                    if ($c->categoryid==$xmlCArr["CategoryID"]) {
                        //No.1-获取Site Default Features
                        // echo "CategoryID == ".$xmlCArr["CategoryID"]."\n";
                        if (isset($sArr['ConditionEnabled'])&&!isset($xmlCArr['ConditionEnabled'])){
                            $xmlCArr['ConditionEnabled']=$sArr['ConditionEnabled'];
                        }
                        if (isset($sArr['VariationsEnabled'])&&!isset($xmlCArr['VariationsEnabled'])){
                            $xmlCArr['VariationsEnabled']=$sArr['VariationsEnabled'];
                        }
                        if (isset($sArr['ConditionValues'])&&!isset($xmlCArr['ConditionValues'])){
                            $xmlCArr['ConditionValues']=$sArr['ConditionValues'];
                        }
                        if (isset($sArr['ISBNEnabled'])&&!isset($xmlCArr['ISBNEnabled'])){
                            $xmlCArr['ISBNEnabled']=$sArr['ISBNEnabled'];
                        }
                        if (isset($sArr['UPCEnabled'])&&!isset($xmlCArr['UPCEnabled'])){
                            $xmlCArr['UPCEnabled']=$sArr['UPCEnabled'];
                        }
                        if (isset($sArr['EANEnabled'])&&!isset($xmlCArr['EANEnabled'])){
                            $xmlCArr['EANEnabled']=$sArr['EANEnabled'];
                        }
                        //No.2-组织指定CategoryID features
                        $xmlCArr['ConditionValues']=isset($xmlCArr['ConditionValues'])?$xmlCArr['ConditionValues']:NULL;
                        $batchInsertArr[]=array(
                            $this->siteID,
                            $xmlCArr['CategoryID'],
                            $xmlCArr['ConditionEnabled'],
                            $xmlCArr['ConditionValues'],
                            // json_encode($xmlCArr['ConditionValues']),
                            @$xmlCArr['ISBNEnabled'],
                            @$xmlCArr['UPCEnabled'],
                            @$xmlCArr['EANEnabled'],
                        );
                    }
                }
            }
            //@todo No.3-获取父features
            //No.4-删除指定CategoryID features
            echo "start delete all category feature!\n";
            $dbase=\Yii::$app->db;
            EbayCategoryfeature::deleteAll(['siteid'=>$this->siteID]);

            print_r($batchInsertArr,false);
            $columnArr=array(
                'siteid',
                'categoryid',
                'conditionenabled',
                'conditionvalues',
                'isbnenabled',
                'upcenabled',
                'eanenabled',
            );
            //No.5-批量插入新的categories记录(注意数据组织时,$columnArr和$batchInsertArr对应)
            echo "start batchinsert category feature!\n";
            $dbase->createCommand()->batchInsert("ebay_categoryfeature", $columnArr, $batchInsertArr)->execute();
        }
    }





    /**
     * [saveAllConditions 保存所有类目相关的 ,只有有相关信息的才保存 ]
     * @Author
     * @Editor willage 2016-11-20T09:31:28+0800
     */
//     public function saveAllConditions($categoryid=null){
//          $xmlArr=$this->requestAll($categoryid);
//          $xmlArr=$xmlArr['Category'];
//          if($xmlArr){
// //               EbayCategoryfeature::model()->deleteWhere('siteid=? ',$this->siteID);
// //               Yii::app()->db->createCommand("OPTIMIZE TABLE ".EbayCategoryfeature::model()->tableName()." ;")->query();
//              if (!isset($xmlArr['0'])){
//                  $_tmp = $xmlArr;
//                  $xmlArr = [];
//                  $xmlArr['0'] = $_tmp;
//              }
//              $parent=$xmlArr['0'];
//              foreach($xmlArr as $key=>$xmlC){
//                  if (count($parent)>1){
//                      if (isset($parent['ConditionEnabled'])&&!isset($xmlC['ConditionEnabled'])){
//                          $xmlC['ConditionEnabled']=$parent['ConditionEnabled'];
//                      }
//                      if (isset($parent['VariationsEnabled'])&&!isset($xmlC['VariationsEnabled'])){
//                          $xmlC['VariationsEnabled']=$parent['VariationsEnabled'];
//                      }
//                      if (isset($parent['ConditionValues'])&&!isset($xmlC['ConditionValues'])){
//                          $xmlC['ConditionValues']=$parent['ConditionValues'];
//                      }
//                      if (isset($parent['ISBNEnabled'])&&!isset($xmlC['ISBNEnabled'])){
//                          $xmlC['ISBNEnabled']=$parent['ISBNEnabled'];
//                      }
//                      if (isset($parent['UPCEnabled'])&&!isset($xmlC['UPCEnabled'])){
//                          $xmlC['UPCEnabled']=$parent['UPCEnabled'];
//                      }
//                      if (isset($parent['EANEnabled'])&&!isset($xmlC['EANEnabled'])){
//                          $xmlC['EANEnabled']=$parent['EANEnabled'];
//                      }
//                  }
//                  // 对于无信息的类目 不保存
//                  if(count($xmlC)==1) continue 1;
//                  $MECF=EbayCategoryfeature::find()->where('siteid=:s and categoryid=:c',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])->one();
//                  Yii::info(print_r($xmlC,true));
//                  if (empty($MECF)){
//                      $MECF = new EbayCategoryfeature();
//                  }
//                  $MECF->setAttributes(
//                  [
//                      'siteid'=>$this->siteID,
//                      'categoryid'=>$xmlC['CategoryID'],
//                      'conditionenabled'=>@$xmlC['ConditionEnabled'],
// //                       'conditionvalues'=>@$xmlC['ConditionValues'],
//                  ]);
//                  $MECF->conditionvalues = @$xmlC['ConditionValues'];
//                  if (isset($xmlC['ISBNEnabled'])){
//                      $MECF->isbnenabled = @$xmlC['ISBNEnabled'];
//                  }
//                  if (isset($xmlC['UPCEnabled'])){
//                      $MECF->upcenabled = @$xmlC['UPCEnabled'];
//                  }
//                  if (isset($xmlC['EANEnabled'])){
//                      $MECF->eanenabled = @$xmlC['EANEnabled'];
//                  }

//                  $MECF->save();
//                     //多属性enable保存在category里面
//                  if (@$xmlC['VariationsEnabled']=='true'){
//                      $category=EbayCategory::find()->where('siteid=:s and categoryid=:c and leaf =1',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])->one();
//                      if (!empty($category)){
//                          $category->variationenabled=1;
//                          $category->save();
//                      }
//                  }
//                  if (@$xmlC['VariationsEnabled']=='false'||!isset($xmlC['VariationsEnabled'])){
//                      $category=EbayCategory::find()->where('siteid=:s and categoryid=:c and leaf =1',[':s'=>$this->siteID,':c'=>$xmlC['CategoryID']])->one();
//                      if (!empty($category)){
//                          $category->variationenabled=0;
//                          $category->save();
//                      }
//                  }
//              }
//          }
//      return false;
//     }








}//end class
