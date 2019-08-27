<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\models\SaasEbayUser;
use eagle\models\EbaySite;
use eagle\models\EbayCountry;
use eagle\models\EbayRegion;
use eagle\models\EbayShippingservice;
use eagle\models\EbayExcludeshippinglocation;
use eagle\models\EbayShippinglocation;
/**
 * 获得eBay的基础信息,例如平台、物流等
 * @package interface.ebay.tradingapi 
 */ 
class getebaydetails extends base{
    public $detailNameArr=array(
        'BuyerRequirementDetails',
        'CountryDetails',
        'CurrencyDetails',
        'CustomCode',//使用 CustomCode ,会取得全部
        'DispatchTimeMaxDetails',
        'ItemSpecificDetails',
        'ListingFeatureDetails',
        'ListingStartPriceDetails',
        'PaymentOptionDetails',
        'RegionDetails',
        'RegionOfOriginDetails',
        'ReturnPolicyDetails',
        'ShippingCarrierDetails',
        'ShippingLocationDetails',
        'ShippingPackageDetails',
        'ShippingServiceDetails',
        'SiteDetails',
        'TaxJurisdiction',
        'TimeZoneDetails',
        'UnitOfMeasurementDetails',
        'URLDetails',
        'VariationDetails',
    	'ExcludeShippingLocationDetails'
    );
    public function api($DetailName,$Version=825){
        $this->verb = 'GeteBayDetails';
        $xmlArr=array(
            'ErrorLanguage'=>'zh_CN',
            'RequesterCredentials'=>array(
            'eBayAuthToken'=>$this->eBayAuthToken,
            ),
        );
        $string = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<GeteBayDetailsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
</GeteBayDetailsRequest>
XML;
        $xmlObj = simplexml_load_string($string);
        $xmlObj->addchild("ErrorLanguage",'zh_CN');
        $xmlObj->addchild("RequesterCredentials")->addchild('eBayAuthToken',$this->eBayAuthToken);
        if (!is_array($DetailName)) {
            // print_r("hello\n",false);
            $DetailName  = array($DetailName);
        }
// print_r($DetailName,false);
        foreach ($DetailName as $keyD => $valD) {
        $xmlObj->addchild("DetailName",$valD);
        }

        // print_r($xmlObj->asXML(),false);
        return $this->sendHttpRequest ($xmlObj->asXML(),0,300,null);
    }
    /***
     *
     *
     */
    public function getOne($userToken){
        $this->eBayAuthToken=$userToken;
        $this->siteID=77;
        $result=$this->api('ShippingLocationDetails');
        $r=$result['ShippingLocationDetails'];
        // print_r($r);
        die();
    }
    
    public function save($items){
    }
    /***
     * 取 Ebay Detail 
     *  eBayAuthToken
        siteID
        都 在外前 执行这个函数之前设置 . 
     */
    public function getDetail($detailName){
        if(in_array($detailName,$this->detailNameArr)==false) return false;
        $result=$this->api($detailName);
        if(isset($result[$detailName])) return $result[$detailName];
        else return false;
    }


/**
 * [syncEbayBase 同步ebay base信息,按系统区分的信息]
 * @Author willage 2016-11-19T11:45:39+0800
 * @Editor willage 2016-11-19T11:45:39+0800
 */
    static function syncEbayBase($siteId=0){
        //接口需要  User Token,这里取任一用户的 .
        $ue=SaasEbayUser::find()->where('selleruserid= :s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        $h=new self();
        //No.1-getebaydetails API
        $h->resetConfig($ue->DevAcccountID);
        $h->eBayAuthToken=$ue->token;
        $h->siteID=$siteId;
        $DetailNameArr=array('CountryDetails','SiteDetails');
        $result=$h->api($DetailNameArr);//使用 CustomCode ,会取得全部
        set_time_limit(0);
        echo "base>>> ".__FUNCTION__."-- getebaydetails api finish\n";
        // print_r($result,false);
        $dbase=\Yii::$app->db;
        //No.2-记录SiteDetails(保存在 EbaySite)
        if (isset($result['SiteDetails'])){
            $ec=EbaySite::find()->one();//'siteid=:s',[':s'=>$siteId]
            if(empty($ec)||($ec->updatetime<strtotime($result['SiteDetails'][0]['UpdateTime']))){
                echo "update SiteDetails start\n";
                //删除数据
                EbaySite::deleteAll();//'siteid=:s',[':s'=>$siteId]
                //组织数据
                $ecBinsertArr=array();
                foreach ($result['SiteDetails'] as $r){
                    $ecBinsertArr[]=array(//batch insert value
                        $r['SiteID'],
                        $r['Site'],
                        $r['DetailVersion'],
                        strtotime($r['UpdateTime']),
                        strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                // print_r($ecBinsertArr,false);
                $ecKeyArr=array(//batch insert key
                    'siteid',
                    'site',
                    'detailversion',
                    'updatetime',
                    'record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_site", $ecKeyArr, $ecBinsertArr)->execute();
                echo "base>>> update SiteDetails finish!\n";
            }else{
                echo "base>>> no update SiteDetails!\n";
            }
        }
        //No.3-记录CountryDetails(保存在 EbayCountry)
        if (isset($result['CountryDetails'])){
            // print_r($result['CountryDetails'],false);
            $ec=EbayCountry::find()->one();
            if(empty($ec)||($ec->updatetime<strtotime($result['CountryDetails'][0]['UpdateTime']))){//
                echo "update CountryDetails start\n";
                //删除数据
                EbayCountry::deleteAll();
                //组织数据
                $ecBinsertArr=array();
                foreach ($result['CountryDetails'] as $rCd){
                    $ecBinsertArr[]=array(//batch insert value
                        $rCd['Country'],$rCd['Description'],$rCd['DetailVersion'],
                        strtotime($rCd['UpdateTime']),strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                // print_r($ecBinsertArr,false);
                $ecKeyArr=array(//batch insert key
                    'country','description','detailversion',
                    'updatetime','record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$ecKeyArr和$ecBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_country", $ecKeyArr, $ecBinsertArr)->execute();
                echo "base>>> update CountryDetails finish!\n";
            }else{
                echo "base>>> no update CountryDetails!\n";
            }
        }
        //currency details
        //time zone details
    }
    /**
     * [syncEbaySiteDetail 同步ebay site details,按站点区分的信息]
     * @Author willage 2016-11-19T15:21:31+0800
     * @Editor willage 2016-11-19T15:21:31+0800
     */
    static function syncEbaySiteDetail($siteId=0){
        //接口需要  User Token,这里取任一用户的 .
        $ue=SaasEbayUser::find()->where('selleruserid= :s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        $h=new self();
        //No.1-getebaydetails API
        $h->resetConfig($ue->DevAcccountID);
        $h->eBayAuthToken=$ue->token;
        $h->siteID=$siteId;
        $DetailNameArr=array(
            'ShippingLocationDetails','ShippingServiceDetails','ExcludeShippingLocationDetails',
            'BuyerRequirementDetails','DispatchTimeMaxDetails','ListingFeatureDetails',
            'PaymentOptionDetails','ReturnPolicyDetails','TaxJurisdiction',
            'URLDetails','VariationDetails');

        $result=$h->api($DetailNameArr);//使用 CustomCode ,会取得全部
        set_time_limit(0);
        echo "details>>>".__FUNCTION__."getebaydetails api finish\n";
        // print_r($result,false);
        $dbase=\Yii::$app->db;
        //No.2-不再保存,记录RegionDetails,国家及地区(保存在 EbayRegion)
        //No.3-记录 ShippingLocationDetails(保存在 EbayShippinglocation)
        if (isset($result['ShippingLocationDetails'])){
            $esl=EbayShippinglocation::find()->where('siteid=:s',[':s'=>$siteId])->one();
            // if(1){
            if(empty($esl)||($esl->updatetime<strtotime($result['ShippingLocationDetails'][0]['UpdateTime']))){
                echo "update ShippingLocationDetails start\n";
                EbayShippinglocation::deleteAll('siteid=:s',[':s'=>$siteId]);//删除数据
                //组织数据
                $eslBinsertArr=array();
                foreach ($result['ShippingLocationDetails'] as $re){
                    $eslBinsertArr[]=array(
                        $re['ShippingLocation'],$re['Description'],$re['DetailVersion'],
                        $siteId,strtotime($re['UpdateTime']),
                        strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                // print_r($eslBinsertArr,false);
                $eslKeyArr=array(
                    'shippinglocation','description','detailversion',
                    'siteid','updatetime','record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_shippinglocation", $eslKeyArr, $eslBinsertArr)->execute();
                echo "details>>> update ShippingLocationDetails finish!\n";
            }else{
                echo "details>>> no update ShippingLocationDetails!\n";
            }
        }
       //No.4-记录ShippingServiceDetails(保存在 EbayShippingservice)
        if (isset($result['ShippingServiceDetails'])){
            $ess=EbayShippingservice::find()->where('siteid = :i',[':i'=>$siteId])->one();
            // if(1){
            if(empty($ess)||($ess->detailversion<$result['ShippingServiceDetails'][0]['DetailVersion'])){
                echo "details>>> update ShippingServiceDetails start\n";
                //删除数据
                EbayShippingservice::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $essBinsertArr=array();
                // print_r($result['ShippingServiceDetails'],false);
                foreach ($result['ShippingServiceDetails'] as $r){
                    $essBinsertArr[]=array(
                    isset($r['Description'])?$r['Description']:NULL,
                    isset($r['InternationalService'])?$r['InternationalService']:NULL,
                    isset($r['ShippingService'])?($r['ShippingService']):NULL,
                    isset($r['ShippingServiceID'])?$r['ShippingServiceID']:NULL,
                    isset($r['ShippingTimeMax'])?$r['ShippingTimeMax']:NULL,
                    isset($r['ShippingTimeMin'])?$r['ShippingTimeMin']:NULL,
                    isset($r['ServiceType'])?json_encode($r['ServiceType']):NULL,
                    // EbayShippingservice::$servicetype[$r['ServiceType']],
                    // isset(EbayShippingservice::$servicetype[$r['ServiceType']])?EbayShippingservice::$servicetype[$r['ServiceType']]:NULL,
                    isset($r['ShippingPackage'])?json_encode($r['ShippingPackage']):NULL,
                    isset($r['ShippingCarrier'])?$r['ShippingCarrier']:NULL,
                    isset($r['ShippingServicePackageDetails'])?json_encode($r['ShippingServicePackageDetails']):NULL,
                    isset($r['WeightRequired'])?$r['WeightRequired']:NULL,
                    isset($r['DimensionsRequired'])?$r['DimensionsRequired']:NULL,
                    isset($r['ValidForSellingFlow'])?$r['ValidForSellingFlow']:NULL,
                    isset($r['ExpeditedService'])?$r['ExpeditedService']:NULL,
                    isset($r['SurchargeApplicable'])?$r['SurchargeApplicable']:NULL,
                    isset($r['DetailVersion'])?$r['DetailVersion']:NULL,
                    strtotime($r['UpdateTime']),$siteId,
                    strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                // print_r($essBinsertArr,false);
                $essKeyArr=array(
                    'description','internationalservice',
                    'shippingservice',
                    'shippingserviceid',
                    'shippingtimemax','shippingtimemin',
                    'servicetype','shippingpackage',
                    'shippingcarrier','shippingservicepackagedetails',
                    'weightrequired','dimensionsrequired',
                    'validforsellingflow','expeditedservice',
                    'surchargeapplicable','detailversion',
                    'updatetime','siteid',
                    'record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$essKeyArr$essBinsertArr)
                $dbase->createCommand()->batchInsert("ebay_shippingservice", $essKeyArr, $essBinsertArr)->execute();
                echo "details>>> update ShippingServiceDetails finish!\n";
            }else{
                echo "details>>> no update ShippingServiceDetails!\n";
            }
        }
       //No.6-记录ExcludeShippingLocationDetails(保存在 EbayExcludeshippinglocation)
        if (isset($result['ExcludeShippingLocationDetails'])){
            $eesl=EbayExcludeshippinglocation::find()->where('siteid=:s',[':s'=>$siteId])->one();
            // if(1){
            if(empty($eesl)||($eesl->updatetime<strtotime($result['ExcludeShippingLocationDetails'][0]['UpdateTime']))){
                echo "details>>> update ExcludeShippingLocationDetails start\n";
                //删除数据
                EbayExcludeshippinglocation::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $eeslBinsertArr=array();
                foreach ($result['ExcludeShippingLocationDetails'] as $re){
                    $eeslBinsertArr[]=array(
                        $re['Location'],$re['Description'],$re['Region'],
                        $siteId,$re['DetailVersion'],strtotime($re['UpdateTime']),
                        strtotime($result['Timestamp']),//使用ebay服务器返回时间)
                    );
                }
                // print_r($eslBinsertArr,false);
                $eeslKeyArr=array(
                    'location','description','region',
                    'siteid','detailversion','updatetime',
                    'record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_excludeshippinglocation", $eeslKeyArr, $eeslBinsertArr)->execute();
                echo "details>>> update ExcludeShippingLocationDetails finish!\n";
            }else{
                echo "details>>> no update ExcludeShippingLocationDetails!\n";
            }
        }
        /**
         * [No.7-其他保存项]
         * 注意：此处保存的字段不能用string，
         * 若要使用，则在EbaySite.php中behaviors去掉对应的字段
         * @var [$site Ebay_site]
         * 另外这个没有做版本判断,每次做更新(1条记录不会很久),
         * 理由是,需要做类似判断,及其麻烦,干脆保存
         * $result['DispatchTimeMaxDetails']['UpdateTime']
         * $result['DispatchTimeMaxDetails'][0]['UpdateTime']
         */
        $site=EbaySite::findOne(['siteid'=>$siteId]);
        // print_r($site,false);
        if (isset($result['BuyerRequirementDetails'])){
            $site->buyer_requirement=($result['BuyerRequirementDetails']);
        }
        if (isset($result['DispatchTimeMaxDetails'])){
            $site->dispatchtimemax=($result['DispatchTimeMaxDetails']);
        }
        if (isset($result['ListingFeatureDetails'])){
            $site->listing_feature=($result['ListingFeatureDetails']);
        }
        if (isset($result['ListingStartPriceDetails'])){
            $site->listing_startprice=($result['ListingStartPriceDetails']);

        }
        if (isset($result['PaymentOptionDetails'])){
            $site->payment_option=($result['PaymentOptionDetails']);

        }
        if (isset($result['ReturnPolicyDetails'])){
            $site->return_policy=($result['ReturnPolicyDetails']);

        }
        if (isset($result['TaxJurisdiction'])){
            $site->tax_jurisdiction=($result['TaxJurisdiction']);
        }
        if (isset($result['URLDetails'])){
            $site->url_details=($result['URLDetails']);
        }
        if (isset($result['VariationDetails'])){
            $site->variation=($result['VariationDetails']);
        }
        $site->record_updatetime=strtotime($result['Timestamp']);
        $site->save(false);
        echo "details>>> update others into EbaySite finish\n";

    }
    static function renewEbayDetail($siteId=0){
        //接口需要  User Token,这里取任一用户的 .
        $ue=SaasEbayUser::find()->where('selleruserid= :s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        $h=new self();
        //No.1-getebaydetails API
        $h->eBayAuthToken=$ue->token;
        $h->siteID=$siteId;
        $result=$h->api('CustomCode');//使用 CustomCode ,会取得全部
        set_time_limit(0);
        echo "getebaydetails api finish , save record start\n";
        $dbase=\Yii::$app->db;
        //No.2-记录SiteDetails(保存在 EbaySite)
        // if (isset($result['SiteDetails'])){
        //     $ec=EbaySite::find()->where('siteid=:s',[':s'=>$siteId])->one();
        //     if(empty($ec)||($ec->updatetime<strtotime($result['SiteDetails'][0]['UpdateTime']))){
        //         echo "update SiteDetails start\n";
        //         //删除数据
        //         EbaySite::deleteAll('siteid=:s',[':s'=>$siteId]);
        //         //组织数据
        //         $ecBinsertArr=array();
        //         foreach ($result['SiteDetails'] as $r){
        //             $ecBinsertArr[]=array(//batch insert value
        //                 $r['SiteID'],
        //                 $r['Site'],
        //                 $r['DetailVersion'],
        //                 strtotime($r['UpdateTime']),
        //                 strtotime($result['Timestamp']),//使用ebay服务器返回时间
        //             );
        //         }
        //         print_r($ecBinsertArr,false);
        //         $ecKeyArr=array(//batch insert key
        //             'siteid',
        //             'site',
        //             'detailversion',
        //             'updatetime',
        //             'record_updatetime',//使用ebay服务器返回时间
        //             );
        //         //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
        //         $dbase->createCommand()->batchInsert("ebay_country", $ecKeyArr, $ecBinsertArr)->execute();
        //         echo "update SiteDetails finish!\n";
        //     }
        // }
        if(isset($result['SiteDetails'])) foreach($result['SiteDetails'] as $r){
            $es=EbaySite::find()->where('siteid = :s',[':s'=>$r['SiteID']])->one();
            if (empty($es)||$es->updatetime<$r['UpdateTime']){
            	if (empty($es)){
            		$es=new EbaySite();
            	}
                $es->setAttributes(array(
                    'siteid'=>$r['SiteID'],
                    'site'=>$r['Site'],
                    'detailversion'=>$r['DetailVersion'],
                    'updatetime'=>strtotime($r['UpdateTime']),
                    'record_updatetime'=>strtotime($result['Timestamp']),//使用ebay服务器返回时间
                ));
                $es->save(false);
                echo "update SiteDetails!\n";
            }
        }
        //No.3-记录CountryDetails(保存在 EbayCountry)
        if (isset($result['CountryDetails'])){
            $ec=EbayCountry::find()->one();
            if(empty($ec)||($ec->updatetime<strtotime($result['CountryDetails'][0]['UpdateTime']))){
                echo "update CountryDetails start\n";
                //删除数据
                EbayCountry::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $ecBinsertArr=array();
                foreach ($result['CountryDetails'] as $r){
                    $ecBinsertArr[]=array(//batch insert value
                        $r['Country'],$r['Description'],$r['DetailVersion'],
                        strtotime($r['UpdateTime']),strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                print_r($ecBinsertArr,false);
                $ecKeyArr=array(//batch insert key
                    $r['Country'],$r['Description'],$r['DetailVersion'],
                    strtotime($r['UpdateTime']),strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_country", $ecKeyArr, $ecBinsertArr)->execute();
                echo "update CountryDetails finish!\n";
            }
        }
        // if(isset($result['CountryDetails'])) foreach($result['CountryDetails'] as $r){
        //     $ec=EbayCountry::find()->where('country = :c',[':c'=>$r['Country']])->one();
        //     if(empty($ec)||($ec->updatetime<$r['UpdateTime']&&!is_null($ec->updatetime))){
        //     	if (empty($ec)){
        //     		$ec=new EbayCountry();
        //     	}
        //         $ec->setAttributes(array(
        //             'country'=>$r['Country'],
        //             'description'=>$r['Description'],
        //             'detailversion'=>$r['DetailVersion'],
        //             'updatetime'=>strtotime($r['UpdateTime']),
        //             'record_updatetime'=>strtotime($result['Timestamp']),//使用ebay服务器返回时间
        //         ));
        //         $ec->save();
        //         echo "update CountryDetails!\n";
        //     }
        // }
        //No.4-记录RegionDetails,国家及地区(保存在 EbayRegion)
        if (isset($result['RegionDetails'])){
            $er=EbayRegion::find()->where('siteid=:s',[':s'=>$siteId])->one();
            if(empty($er)||($er->updatetime<strtotime($result['RegionDetails'][0]['UpdateTime']))){
                echo "update RegionDetails start\n";
                //删除数据
                EbayRegion::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $erBinsertArr=array();
                foreach ($result['RegionDetails'] as $r){
                    $erBinsertArr[]=array(//batch insert value
                        $r['RegionID'],$r['Description'],$siteId,
                        $r['DetailVersion'],strtotime($r['UpdateTime']),
                        strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                print_r($erBinsertArr,false);
                $erKeyArr=array(//batch insert key
                    'regionid', 'description','siteid',
                    'detailversion','updatetime','record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_region", $erKeyArr, $erBinsertArr)->execute();
                echo "update RegionDetails finish!\n";
            }
        }
        // if(isset($result['RegionDetails'])) foreach($result['RegionDetails'] as $r){
        //     $er=EbayRegion::find()->where('regionid = :r',[':r'=>$r['RegionID']])->one();
        //     if(empty($er)||$er->updatetime<$r['UpdateTime']){
        //     	if (empty($er)){
        //     		$er=new EbayRegion();
        //     	}
        //         $er->setAttributes(array(
        //             'regionid'=>$r['RegionID'],
        //             'description'=>$r['Description'],
        //             'siteid'=>$siteId,
        //             'detailversion'=>$r['DetailVersion'],
        //             'updatetime'=>strtotime($r['UpdateTime']),
        //             'record_updatetime'=>strtotime($result['Timestamp']),//使用ebay服务器返回时间
        //         ));
        //         $er->save();
        //         echo "update RegionDetails!\n";
        //     }
        // }
        //No.5-记录 ShippingLocationDetails(保存在 EbayShippinglocation)
        if (isset($result['ShippingLocationDetails'])){
            $esl=EbayShippinglocation::find()->where('siteid=:s',[':s'=>$siteId])->one();
            if(empty($esl)||($esl->updatetime<strtotime($result['ShippingLocationDetails'][0]['UpdateTime']))){
                echo "update ShippingLocationDetails start\n";
                EbayShippinglocation::deleteAll('siteid=:s',[':s'=>$siteId]);//删除数据
                //组织数据
                $eslBinsertArr=array();
                foreach ($result['ShippingLocationDetails'] as $re){
                    $eslBinsertArr[]=array(
                        $re['ShippingLocation'],$re['Description'],$re['DetailVersion'],
                        $siteId,strtotime($re['UpdateTime']),
                        strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                print_r($eslBinsertArr,false);
                $eslKeyArr=array(
                    'shippinglocation','description','detailversion',
                    'siteid','updatetime','record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_shippinglocation", $eslKeyArr, $eslBinsertArr)->execute();
                echo "update ShippingLocationDetails finish!\n";
            }
        }
        //CurrencyDetails
//         if(isset($result['CurrencyDetails'])) foreach($result['CurrencyDetails'] as $r){
//         	var_dump($result['CurrencyDetails']);
//         	Yii::log(print_r($result['CurrencyDetails'],1));
//             $n=Ebay_Currency::find('currency=?',$r['Currency'])->getOne();
//             if($n->isNewRecord||$n->updatetime<$r['UpdateTime']){
//                 $n->setAttributes(array(
//                     'currency'=>$r['Currency'],
//                     'description'=>$r['Description'],
//                     'detailversion'=>$r['DetailVersion'],
//                     'updatetime'=>strtotime($r['UpdateTime'])
//                 ));
//                 $n->save();
//             }
//        }
        //No.6-记录ShippingServiceDetails(保存在 EbayShippingservice)
        if (isset($result['ShippingServiceDetails'])){
            $ess=EbayShippingservice::find()->where('siteid = :i',[':i'=>$siteId])->one();
            if(empty($ess)||($ess->detailversion<$result['ShippingServiceDetails'][0]['DetailVersion'])){
                echo "update ShippingServiceDetails start\n";
                //删除数据
                EbayShippingservice::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $essBinsertArr=array();
                foreach ($result['ShippingServiceDetails'] as $r){
                    $essBinsertArr[]=array(
                    @$r['Description'],@$r['InternationalService'],
                    @$r['ShippingService'],$r['ShippingServiceID'],
                    @$r['ShippingTimeMax'],@$r['ShippingTimeMin'],
                    @EbayShippingservice::$servicetype[$r['ServiceType']],@$r['ShippingPackage'],
                    @$r['ShippingCarrier'],@$r['ShippingServicePackageDetails'],
                    @$r['WeightRequired'],@$r['DimensionsRequired'],
                    @$r['ValidForSellingFlow'],@$r['ExpeditedService'],
                    @$r['SurchargeApplicable'],@$r['DetailVersion'],
                    @strtotime($r['UpdateTime']),$siteId,
                    strtotime($result['Timestamp']),//使用ebay服务器返回时间
                    );
                }
                print_r($essBinsertArr,false);
                $essKeyArr=array(
                    'description','internationalservice',
                    'shippingservice','shippingserviceid',
                    'shippingtimemax','shippingtimemin',
                    'servicetype','shippingpackage',
                    'shippingcarrier','shippingservicepackagedetails',
                    'weightrequired','dimensionsrequired',
                    'validforsellingflow','expeditedservice',
                    'surchargeapplicable','detailversion',
                    'updatetime','siteid',
                    'record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$essKeyArr$essBinsertArr)
                $dbase->createCommand()->batchInsert("ebay_shippingservice", $essKeyArr, $essBinsertArr)->execute();
                echo "update ShippingServiceDetails finish!\n";
            }
        }

        // if (isset($result['ShippingServiceDetails'])) foreach($result['ShippingServiceDetails'] as $r){
        //     $ess=EbayShippingservice::find()->where('shippingserviceid = :s and siteid = :i',[':s'=>$r['ShippingServiceID'],':i'=>$siteId])->one();
        //     if(empty($ess)||$ess->detailversion<$r['DetailVersion']){
        //     	if (empty($ess)){
        //     		$ess=new EbayShippingservice();
        //     	}
        //         $ess->setAttributes(array(
        //             'description'=>@$r['Description'],
        //             'internationalservice'=>@$r['InternationalService'],
        //             'shippingservice'=>@$r['ShippingService'],
        //             'shippingserviceid'=>$r['ShippingServiceID'],
        //             'shippingtimemax'=>@$r['ShippingTimeMax'],
        //             'shippingtimemin'=>@$r['ShippingTimeMin'],
        //             'servicetype'=>@EbayShippingservice::$servicetype[$r['ServiceType']],
        //             'shippingpackage'=>@$r['ShippingPackage'],
        //             'shippingcarrier'=>@$r['ShippingCarrier'],
        //             'shippingservicepackagedetails'=>@$r['ShippingServicePackageDetails'],
        //             'weightrequired'=>@$r['WeightRequired'],
        //             'dimensionsrequired'=>@$r['DimensionsRequired'],
        //             'validforsellingflow'=>@$r['ValidForSellingFlow'],
        //             'expeditedservice'=>@$r['ExpeditedService'],
        //             'surchargeapplicable'=>@$r['SurchargeApplicable'],
        //             'detailversion'=>@$r['DetailVersion'],
        //             'updatetime'=>@strtotime($r['UpdateTime']),
        //             'siteid'=>$siteId,
        //             'record_updatetime'=>strtotime($result['Timestamp']),//使用ebay服务器返回时间
        //         ));
        //         $ess->save();
        //         echo "update ShippingServiceDetails!\n";
        //     }
        // }
        //No.7-记录ExcludeShippingLocationDetails(保存在 EbayExcludeshippinglocation)
        //
        if (isset($result['ExcludeShippingLocationDetails'])){
            $eesl=EbayExcludeshippinglocation::find()->where('siteid=:s',[':s'=>$siteId])->one();
            if(empty($eesl)||($eesl->updatetime<strtotime($result['ExcludeShippingLocationDetails'][0]['UpdateTime']))){
                echo "update ExcludeShippingLocationDetails start\n";
                //删除数据
                EbayExcludeshippinglocation::deleteAll('siteid=:s',[':s'=>$siteId]);
                //组织数据
                $eeslBinsertArr=array();
                foreach ($result['ExcludeShippingLocationDetails'] as $re){
                    $eeslBinsertArr[]=array(
                        $re['Location'],$re['Description'],$re['Region'],
                        $siteId,strtotime($result['Timestamp']),//使用ebay服务器返回时间)
                    );
                }
                print_r($eslBinsertArr,false);
                $eeslKeyArr=array(
                    'location','description','region',
                    'siteid','record_updatetime',//使用ebay服务器返回时间
                    );
                //批量插入(注意数据组织时,$eslKeyArr和$eslBinsertArr对应)
                $dbase->createCommand()->batchInsert("ebay_shippinglocation", $eeslKeyArr, $eeslBinsertArr)->execute();
                echo "update ExcludeShippingLocationDetails finish!\n";
            }
        }
        // if (isset($result['ExcludeShippingLocationDetails'])){
        // 	foreach ($result['ExcludeShippingLocationDetails'] as $re){
        // 		$eesl=EbayExcludeshippinglocation::find()->where('location = :l and siteid=:s',[':l'=>$re['Location'],':s'=>$siteId])->one();
        // 		if (empty($eesl)){
        // 			$eesl=new EbayExcludeshippinglocation();
        // 		}
        // 		$eesl->setAttributes(array(
        // 				'location'=>$re['Location'],
        // 				'description'=>$re['Description'],
        // 				'region'=>$re['Region'],
        // 				'siteid'=>$siteId,
        //                 'record_updatetime'=>strtotime($result['Timestamp']),//使用ebay服务器返回时间
        // 		));
        // 		$eesl->save();
        //         echo "update ExcludeShippingLocationDetails!\n";
        // 	}
        // 	EbayExcludeshippinglocation::deleteAll('siteid is null');
        // }
        // 其他保存项
        /* @var $site Ebay_site */
        $site=EbaySite::findOne('siteid='.$siteId);
        if (isset($result['BuyerRequirementDetails'])){
            $site->buyer_requirement=$result['BuyerRequirementDetails'];
        }
        if (isset($result['DispatchTimeMaxDetails'])){
            $site->dispatchtimemax=$result['DispatchTimeMaxDetails'];
        }
        if (isset($result['ListingFeatureDetails'])){
            $site->listing_feature=$result['ListingFeatureDetails'];
        }
        if (isset($result['ListingStartPriceDetails'])){
            $site->listing_startprice=$result['ListingStartPriceDetails'];
        }
        if (isset($result['PaymentOptionDetails'])){
            $site->payment_option=$result['PaymentOptionDetails'];
        }
        if (isset($result['ReturnPolicyDetails'])){
            $site->return_policy=$result['ReturnPolicyDetails'];
        }
        if (isset($result['TaxJurisdiction'])){
            $site->tax_jurisdiction=$result['TaxJurisdiction'];
        }
        if (isset($result['URLDetails'])){
            $site->url_details=$result['URLDetails'];
        }
        if (isset($result['VariationDetails'])){
            $site->variation=$result['VariationDetails'];
        }
        $site->save();

    }

    /***
     * 取 Ebay 的 url 
     */
    public function urldetail(){
    	switch($this->siteID){
    		case '0':
    			$str='http://www.ebay.com/itm/';
    			break;
    		case '3':
    			$str='http://www.ebay.co.uk/itm/';
    			break;
    		case '77':
    			$str='http://www.ebay.de/itm/';
    			break;
    		case '15':
    			$str='http://www.ebay.com.au/itm/';
    			break;
    		default:
    			$str='http://www.ebay.com/itm/';
    			break;
    	}
    	return $str;
    }
}
