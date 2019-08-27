<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbaySite;
use eagle\modules\listing\helpers\MubanHelper;
use common\helpers\Helper_xml;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayLogMubanDetail;
use eagle\modules\listing\models\EbayLogMuban;
use eagle\models\SaasEbayUser;
use eagle\models\EbayAutoadditemset;
use eagle\modules\listing\models\EbayMuban;
use common\helpers\Helper_Util;
use common\helpers\Helper_Array;
use eagle\modules\listing\models\EbayAutoTimerListing;
/**
 * 刊登产品 
 * 
 * 为 AddItem, AddFixedPriceItem,VerifyAddItem ,VerifyAddFixedPriceItem .共用 .  
 *  @package interface.ebay.tradingapi
 */ 
class additem extends base{
    /**
     * 接口类型
     *
     * @var string
     */
    public $verb ='AddItem';
    /**
     * 是否只是检测
     *
     * @var bool
     */
    protected $_is_verify =false;
    /**
     * 查看/设定是否只是检测而不是真正发送
     * 
     * @param boolean $is 省略，函数返回是否为验证状态
     * @return EbayInterface_additem|boolean
     */
    public function isVerify($is=null){
        if ($is === null){
            return $this->_is_verify;
        }
        $this->_is_verify=$is;
        return $this;
    }
    /**
     * [apiFromDraft 直接读取模板变量并进行刊登操作]
     * @author willage 2017-04-13T15:00:49+0800
     * @update willage 2017-04-13T15:00:49+0800
     */
    function apiFromDraft($muban,$uid,$selleruserid,$storefront_storecategoryid=null,$storefront_storecategory2id=null,$timer=null,$uuid='',$caogao=0){
        if ($timer instanceof EbayAutoTimerListing){
            $timerid=$timer->id;
        }else {
            $timerid=null;
        }

        $data=$muban->attributes;
        $detaildata=$muban->detail->attributes;
        $data=array_merge($data,$detaildata);
        $values=additem::valueMergeWithDefault($data);

        $siteid=$values['siteid'];
        $es=EbaySite::findOne(['siteid'=>$siteid]);
        $values['site']=$es->site;

        $this->MEU=$Ebay_User=SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);

        $values['paypal']=trim($values['paypal']);

        $this->siteID=$siteid;
        $this->resetConfig($Ebay_User->listing_devAccountID);
        $this->eBayAuthToken=$Ebay_User->listing_token;
        //UUID
        $_str = @$timer->id;
        if(empty($_str) || is_null($_str) || $_str == '')
        {
            $_str = time();
        }
        $uuid=substr($muban->mubanid.'F'.$_str.'F'.md5($selleruserid), 0,32);
        if (strlen($uuid) <32){
            $uuid.=str_repeat('F',32-strlen($uuid));
        }elseif (strlen($uuid)>32){
            $uuid=Helper_Util::getLongUuid();
        }
        $values['UUID']=$uuid;
        //SetValues
        $this->setValues($values);
        return $this->api($muban,$uid,$timerid,$selleruserid,$uuid,$caogao);
    }
    /**
     * 直接读取模板变量并进行刊登操作
     *
     * @param Ebay_Muban $muban
     * @param string $uid
     * @param string $selleruserid
     * @param string $storefront_storecategoryid    店铺类目1
     * @param string $storefront_storecategory2id   店铺类目2
     * @param string $timerid   定时器ID
     * @param boolean $caogao 是否是采集草稿箱过来的
     * @return unknown
     */
    function apiFromMuban($muban,$uid,$selleruserid,$storefront_storecategoryid=null,$storefront_storecategory2id=null,$timer=null,$uuid='',$caogao=0){
        if ($timer instanceof EbayAutoadditemset){
            $timerid=$timer->timerid;
        }else {
            $timerid=null;
        }
        
        $data=$muban->attributes;
        $detaildata=$muban->detail->attributes;
        $data=array_merge($data,$detaildata);
        $values=additem::valueMergeWithDefault($data);
        
        $siteid=$values['siteid'];
        $es=EbaySite::findOne(['siteid'=>$siteid]);
        $values['site']=$es->site;
//         if ($timer->itemtitle){
//             $values['itemtitle']=$timer->itemtitle;
//         }
        $this->MEU=$Ebay_User=SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);
        
        $values['paypal']=trim($values['paypal']);

        $this->siteID=$siteid;
        $this->resetConfig($Ebay_User->listing_devAccountID);
        $this->eBayAuthToken=$Ebay_User->listing_token;
        //UUID
        $_str = @$timer->timerid;
        if(empty($_str) || is_null($_str) || $_str == '')
        {
        	$_str = time();
        }
        $uuid=substr($muban->mubanid.'F'.$_str.'F'.md5($selleruserid), 0,32);
        if (strlen($uuid) <32){
        	$uuid.=str_repeat('F',32-strlen($uuid));
        }elseif (strlen($uuid)>32){
        	$uuid=Helper_Util::getLongUuid();
        }
        $values['UUID']=$uuid;
        //SetValues
        $this->setValues($values);
        return $this->api($muban,$uid,$timerid,$selleruserid,$uuid,$caogao);
    }
    static function StrToHex($string)
    {
    	$hex= " ";
    	for ($i=0;$i <strlen($string);$i++){
    		$hex.=dechex(ord($string[$i]));
    	}
    	$hex=strtoupper($hex);
    	return   $hex;
    }
    /**
     * 接口
     * 
     * @param Ebay_Muban $muban   模板 对象
     * @param int $uid 用户
     */
    public function api($muban,$uid,$timerid=0,$selleruserid=0,$uuid ='',$caogao=0){
    	//$data, Yii::app()->muser->getPuid(), 0, $seu->selleruserid ,$uuid
    	if (strlen($uuid)){
    		$this->setValues(array('UUID'=>$uuid));
    	}
        if(!isset($this->siteID)){
            $this->siteID=0;
        }
        if ($this->values['listingtype'] =='FixedPriceItem'){
            $this->verb='AddFixedPriceItem';
        }
        if ($this->isVerify()){
            $this->verb='Verify'.$this->verb;
        }
        $this->addItemCheckValues();
        
        // 组织 发送 数组 
        $xmlArr=array(
            'ErrorLanguage'=>'zh_CN',
            'MessageID'=>1,
            'Version'=>$this->config['compatabilityLevel'],
            'Item'=>array(
                'DispatchTimeMax'=>$this->values['dispatchtime']<1?1:$this->values['dispatchtime'],
                'AutoPay'=>'false',
//              'Country'=>$this->values['location'],//
                'Currency'=>$this->values['currency'],//
                'Site'=>$this->values['site'],//
                'SKU' =>$this->values['sku'],
                'Description'=>$this->values['itemdescription'],
                'ListingDuration'=>$this->values['listingduration'],
                'ListingType'=>$this->values['listingtype'],
                'Location'=>$this->values['location'],//
                'PostalCode'=>$this->values['postalcode'],//
                'Country'=>$this->values['country'],
                'PaymentMethods'=>$this->values['paymentmethods'],
                'PayPalEmailAddress'=>$this->values['paypal'],
                'PrimaryCategory'=>array(
                    'CategoryID'=>$this->values['primarycategory']
                ),
                'SecondaryCategory'=>array(
                    'CategoryID'=>$this->values['secondarycategory']
                ),
                'Title'=>$this->values['itemtitle'],
                'SubTitle'=>'<![CDATA['.$this->values['itemtitle2'].']]>',
                'ReturnPolicy'=>array(
                    'ReturnsAcceptedOption'=>'ReturnsNotAccepted'
                ),
//              'ShippingDetails'=>$this->values['shippingdetails'],
                'Quantity'=>(int)$this->values['quantity'],
                'LotSize'=>(int)$this->values['lotsize'],
                'StartPrice'=>$this->values['startprice'],
                'BuyItNowPrice'=>$this->values['buyitnowprice'],
                'HitCounter'=>$this->values['hitcounter'],
                'ShippingTermsInDescription'=>'True',
            ),
        );
        //echo   $xmlArr['Item']['Description']."\n";
        //Vat
        if ($this->values['vatpercent']>0){
        	$xmlArr['Item']['VATDetails']['VATPercent']=$this->values['vatpercent'];
        }
        
        //如果有设置crossbordertrade，进行处理
        if ($this->values['crossbordertrade']==1){
            switch ($this->values['siteid']){
                case 0:
                    $xmlArr['Item']['CrossBorderTrade']='UK';
                    break;
                case 2:
                    $xmlArr['Item']['CrossBorderTrade']='UK';
                    break;
                case 3:
                    $xmlArr['Item']['CrossBorderTrade']='North America';
                    break;
                case 205:
                    $xmlArr['Item']['CrossBorderTrade']='North America';
                    break;
            }
        }
        //如果有设置privatelisting，进行处理
        if($this->values['privatelisting']=='true'){
            $xmlArr['Item']['PrivateListing']=true;
        }
        //如果设置了有效的宝地拍卖价，进行处理
        if($this->values['listingtype']=='Chinese'&&@$this->values['reserveprice']>0){
            $xmlArr['Item']['ReservePrice']=$this->values['reserveprice'];
        }
        //如果设置了立即付款atuopay进行处理
        if($this->values['autopay']=='1'){
            $xmlArr['Item']['AutoPay']='true';
        }
        if (isset($this->values['conditionid'])){
            $xmlArr['Item']['ConditionID']=$this->values['conditionid'];
        }
        
        //如果有细节的，进行细节内容上传
        $_variation_names=array();
        if (!empty($this->values['variation'])&&$this->values['listingtype'] =='FixedPriceItem'){

            //xml 处理
            if ( !($this->values['variation'] instanceof \SimpleXMLElement )){
                $variations=new \SimpleXMLElement('<Variations></Variations>');
                $_vss=$variations->addChild('VariationSpecificsSet');
                foreach ($this->values['variation']['VariationSpecificsSet']['NameValueList'] as $nvl){
                    $_nvl=$_vss->addChild('NameValueList');
                    $_name=$_nvl->addChild('Name',$nvl['Name']);
                    // Helper_xml::addCData($_name, $nvl['Name']);
                    if (!is_array($nvl['Value'])){
                        $temp_arr=array(0=>$nvl['Value']);
                        $nvl['Value']=$temp_arr;
                    }
                    foreach ($nvl['Value'] as $value){
                        $_value=$_nvl->addChild('Value',$value);
                        // Helper_xml::addCData($_value,$value);
                    }
                    //需要处理specific
                    $_variation_names[strtolower($nvl['Name'])]=strtolower($nvl['Name']);
                }
                //属性处理
                $i=0;
                foreach ($this->values['variation']['Variation'] as $v){
                    $_v=$variations->addChild('Variation');
                    $_v->addChild('StartPrice',$v['StartPrice']);
                    $_v->addChild('Quantity',$v['Quantity']);
                    $_tmpsku=$_v->addChild('SKU', $v['SKU']);
                    // Helper_xml::addCData($_tmpsku, $v['SKU']);
                    $_vs=$_v->addChild('VariationSpecifics');
                    if (isset($v['VariationProductListingDetails'])){
                    	$_tmpv = $_v->addChild('VariationProductListingDetails');
                        if (!is_array($v['VariationProductListingDetails'])) {
                            $v['VariationProductListingDetails']=array($v['VariationProductListingDetails']);
                        }
                    	foreach ($v['VariationProductListingDetails'] as $_vk=>$_vv){
                    		$_tmpv->addChild($_vk,$_vv);
                    	}
                    }

                    foreach ($v['VariationSpecifics']['NameValueList'] as $nvl){
                        $_nvl=$_vs->addChild('NameValueList');
                        $_name=$_nvl->addChild('Name',$nvl['Name']);
                        // Helper_xml::addCData($_name,$nvl['Name']);
                        $_value=$_nvl->addChild('Value',$nvl['Value']);
                        // Helper_xml::addCData($_value,$nvl['Value']);
                    }
                    $i++;
                }
                //图片处理
                if (!empty($this->values['variation']['assoc_pic_count'])){
                    if (empty($this->values['variation']['assoc_pic_key'])){
                        $this->values['variation']['assoc_pic_key']=$this->values['variation']['VariationSpecificsSet']['NameValueList'][0]['Name'];
                    }
                    //图片绑定属性
                    $pnode=$variations->addChild('Pictures');
                    $pnode->addChild('VariationSpecificName',$this->values['variation']['assoc_pic_key']);
                    
                    $picture_array=Helper_Array::groupBy($this->values['variation']['Pictures'],'Value');
                    //remove duplicated
                    foreach ($picture_array as $k11 => $_p_group){
                    	$_duplicate_p=array();
                    	foreach ($_p_group as $k12 => $_p_node){
                    		if (in_array($_p_node['VariationSpecificPictureSet']['PictureURL'], $_duplicate_p)){
                    			unset($picture_array[$k11][$k12]);
                    		}
                    		$_duplicate_p[]=$_p_node['VariationSpecificPictureSet']['PictureURL'];
                    	}
                    }
                    //generate xml
                    foreach ($picture_array as $pvalue => $parray){
                        if ($pvalue=='none_skip'){
                            continue;
                        }
                        $vspnode=$pnode->addChild('VariationSpecificPictureSet');
                        $_vsv_node=$vspnode->addChild('VariationSpecificValue', $pvalue);
                        // Helper_xml::addCData($_vsv_node, $pvalue);
                        foreach ($parray as $ppp){
                            
//                             if ($this->_watermark){
//                                 Helper_builditemdescription::AddWatermarkImg($selleruserid,$ppp['VariationSpecificPictureSet']['PictureURL']);
//                             }
                            if (is_array($ppp['VariationSpecificPictureSet']['PictureURL'])){
                                $tmpPicture=$ppp['VariationSpecificPictureSet']['PictureURL'];
                                foreach ($tmpPicture as $keyPurl => $valPurl) {
                                     $_purl_node=$vspnode->addChild('PictureURL', $valPurl);
                                }
                            	$ppp['VariationSpecificPictureSet']['PictureURL']=$ppp['VariationSpecificPictureSet']['PictureURL'][0];
                            }else{
                                $_purl_node=$vspnode->addChild('PictureURL', $ppp['VariationSpecificPictureSet']['PictureURL']);
                            }

                            // Helper_xml::addCData($_purl_node, $ppp['VariationSpecificPictureSet']['PictureURL']);
//                             $vspnode->addChild('PictureURL',$ppp['VariationSpecificPictureSet']['PictureURL']);
                        }
                    }
                }
                //$this->values['variation']=$variations;
            }

            $variations=self::xmlToArray($variations);
            $xmlArr['Item']['Variations']=$variations;//self::xmlToArray($variations);      
            unset($xmlArr['Item']['StartPrice']);
        }
        #物流处理
        //如果有设置specifics属性的，进行处理
        if (!empty($this->values['specific'])){
            foreach ($this->values['specific'] as $k=>$v){
            	if (in_array(strtolower($k), $_variation_names)){
            		//如果多属性里面命名重复，取消细节
            		continue;
            	}
                if (is_array($v)) {//多选的项
                    foreach ($v as $key => $value) {
                        $valArr[]='<![CDATA['.$value.']]>';
                    }
                    $xmlArr['Item']['ItemSpecifics']['NameValueList'][]=array('Name'=>'<![CDATA['.$k.']]>','Value'=>$valArr);
                }else{
                    $xmlArr['Item']['ItemSpecifics']['NameValueList'][]=array('Name'=>'<![CDATA['.$k.']]>','Value'=>'<![CDATA['.$v.']]>');
                }
            }
        }
        if (!empty($this->values['shippingdetails'])){
            //对shippingDeatils的处理,未选择物流的不进行request
            foreach ($this->values['shippingdetails']['ShippingServiceOptions'] as $vv=>$v){
                if ($v['ShippingService']==''){
                    unset($this->values['shippingdetails']['ShippingServiceOptions'][$vv]);
                }
            }
            foreach ($this->values['shippingdetails']['InternationalShippingServiceOption'] as $vv=>$v){
                if ($v['ShippingService']==''){
                    unset($this->values['shippingdetails']['InternationalShippingServiceOption'][$vv]);
                }
            }
            $xmlArr['Item']['ShippingDetails']=$this->values['shippingdetails'];
            //针对物流费用填0的过滤为0.0
            foreach($xmlArr['Item']['ShippingDetails']['ShippingServiceOptions'] as &$ssi){
                if ($ssi['ShippingServiceCost']==0){
                    $ssi['ShippingServiceCost']='0.00';
                }
                if ($ssi['ShippingServiceAdditionalCost']==0){
                    $ssi['ShippingServiceAdditionalCost']='0.00';
                }
            	if (!isset($ssi['ShippingSurcharge'])||$ssi['ShippingSurcharge']=='0'||$ssi['ShippingSurcharge']=='0.00'||is_null(@$ssi['ShippingSurcharge'])){
                    unset($ssi['ShippingSurcharge']);
                }
            }
            $_shipping_duplicate=array();
            foreach ($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'] as $i=> &$ssj){
            	
                if ($ssj['ShippingServiceCost']==0){
                    $ssj['ShippingServiceCost']='0.00';
                }
                if ($ssj['ShippingServiceAdditionalCost']==0){
                    $ssj['ShippingServiceAdditionalCost']='0.00';
                }
                if(empty($ssj['ShipToLocation'])){
                    $ssj['ShipToLocation']='None';
                }
            }
            $d=$e=1;
            //多重物流进行优先级设置
            foreach($xmlArr['Item']['ShippingDetails']['ShippingServiceOptions'] as $k=>&$SSOd){
            	$SSOd['ShippingServicePriority']=$d++;
            }
            
            //如果3个国际物流为empty，不进行requests
            if (empty($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'])){
                unset($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption']);
            }else{
            	foreach($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'] as  $k=>&$SSOd){
            		if(isset($SSOd['ShippingService'])&&$SSOd['ShippingService']){
            			$SSOd['ShippingServicePriority']=$e++;
            		}else{
            			unset($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'][$k]);
            		}
            	}
            }
            
            if(@$muban['shippingdetails']['shippingdomtype']=='Calculated'||@$muban['shippingdetails']['shippinginttype']=='Calculated')
            {
            	$ShippingIrregular = 'false';
            	if(!empty($this->values['shippingdetails']['CalculatedShippingRate']['ShippingIrregular']))
            	{
            		$ShippingIrregular = @$muban['shippingdetails']['CalculatedShippingRate']['ShippingIrregular'];
            	}
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts'] = @$muban['shippingdetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts'];
            	if ($this->values['siteid'] == '0'){
            		$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['MeasurementUnit'] = 'English';
            	}else{
            		$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['MeasurementUnit'] = 'Metric';
            	}
            	
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['OriginatingPostalCode'] = @$muban['shippingdetails']['CalculatedShippingRate']['OriginatingPostalCode'];
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['PackageDepth'] = @$muban['shippingdetails']['CalculatedShippingRate']['PackageDepth'];
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['PackageLength'] = @$muban['shippingdetails']['CalculatedShippingRate']['PackageLength'];
            	
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['PackageWidth'] = @$muban['shippingdetails']['CalculatedShippingRate']['PackageWidth'];
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['PackagingHandlingCosts'] = @$muban['shippingdetails']['CalculatedShippingRate']['PackagingHandlingCosts'];
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['ShippingIrregular'] = $ShippingIrregular;
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['ShippingPackage'] = @$muban['shippingdetails']['CalculatedShippingRate']['ShippingPackage'];
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['WeightMajor'] = @$muban['shippingdetails']['CalculatedShippingRate']['WeightMajor'];
            	
            	$xmlArr['Item']['ShippingDetails']['CalculatedShippingRate']['WeightMinor'] = @$muban['shippingdetails']['CalculatedShippingRate']['WeightMinor'];
            }
        }
        //如果有设置买家需求标准的，进行处理
        if (@$this->values['buyerrequirementdetails']['LinkedPayPalAccount']=='true'){
            $xmlArr['Item']['BuyerRequirementDetails']['LinkedPayPalAccount']='true';
        }
        if (@$this->values['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Count']>0&&strlen(@$this->values['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Period'])){
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumBuyerPolicyViolations']['Count']=$this->values['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Count'];
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumBuyerPolicyViolations']['Period']=$this->values['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Period'];
        }
        if (@$this->values['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Count']>0&&strlen(@$this->values['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Period'])){
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumUnpaidItemStrikesInfo']['Count']=$this->values['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Count'];
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumUnpaidItemStrikesInfo']['Period']=$this->values['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Period'];
        }
        if(@$this->values['buyerrequirementdetails']['MaximumItemRequirements']['MaximumItemCount']>0){
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumItemRequirements']['MaximumItemCount']=$this->values['buyerrequirementdetails']['MaximumItemRequirements']['MaximumItemCount'];
        }
        if (strlen(@$this->values['buyerrequirementdetails']['MaximumItemRequirements']['MinimumFeedbackScore'])){
            $xmlArr['Item']['BuyerRequirementDetails']['MaximumItemRequirements']['MinimumFeedbackScore'] = @$this->values['buyerrequirementdetails']['MaximumItemRequirements']['MinimumFeedbackScore'];
        }
        
        //如果有设置屏蔽国家，则进行处理
        if (!empty($this->values['shippingdetails']['ExcludeShipToLocation'])){
            $xmlArr['Item']['ShippingDetails']['ExcludeShipToLocation']=$this->values['shippingdetails']['ExcludeShipToLocation'];
        }
        
        if(empty($this->values['shippingdetails']['ExcludeShipToLocation']))
        {
        	unset($xmlArr['Item']['ShippingDetails']['ExcludeShipToLocation']);
        }
        
        if (strlen($this->values['shippingdetails']['PaymentInstructions'])){
        	$xmlArr['Item']['ShippingDetails']['PaymentInstructions']=$this->values['shippingdetails']['PaymentInstructions'];
        }
        $xmlArr['Item']['BuyerRequirementDetails']['ShipToRegistrationCountry']=true;
        #location
        if (!strlen($xmlArr['Item']['Location'])){
            $xmlArr['Item']['Location']=$xmlArr['Item']['Country'];
        }
        #退货规则
        if (!empty($this->values['return_policy'])){
            $xmlArr['Item']['ReturnPolicy']=$this->values['return_policy'];
            if (isset($xmlArr['Item']['ReturnPolicy']['Description'])){
                $xmlArr['Item']['ReturnPolicy']['Description']='<![CDATA['.$xmlArr['Item']['ReturnPolicy']['Description'].']]>';
            }
        }
        /**
         * 商品介绍处理
         */
        #自动发帖模版
        /*
         * 改到从 apiFromMuban 直接赋值
        if ($timerid>0){
            $timer=Ebay_Autoadditemset::find('timerid=?',$timerid)->getOne();
            if (strlen($timer->template)){
                $this->values['template']=$timer->template;
            }
        }
        */ 
        $xmlArr['Item']['Description']=MubanHelper::buildDescription($selleruserid,$this->values,$xmlArr['Item']['Title'],$xmlArr['Item']['Description']);
        //echo $xmlArr['Item']['Description']."\n";
        // 限制描述过短
        if (strlen($xmlArr['Item']['Description'])<500){
        		$responseArr=array(
        			'Ack'=>'Failure',
        			'Errors'=>array (
        				'ShortMessage' => '描述内容太少。',
        				'LongMessage' => $xmlArr['Item']['Description'],
        				'ErrorCode' => '0',
        				'SeverityCode' => 'Error',
        				'ErrorClassification' => 'RequestError',
        			),
        		);
        		$this->_last_response_array=$responseArr;
        		if(!$this->isVerify() && $caogao == 0){
        			$this->logResult($uid,$muban,$selleruserid,$timerid,null,$responseArr);
        		}
        		return $responseArr;
        }
        
        #一口价商品处理
        if (strpos($this->verb,'AddFixedPriceItem') !== false){
            $xmlArr['Item']['StartPrice']=$xmlArr['Item']['BuyItNowPrice'];
        }else {
            #拍卖商品个数只能为1
            $xmlArr['Item']['Quantity']=1;
        }
        

        if($this->values['listingtype'] !='Chinese'&&$this->values['bestoffer']!=1){
        	$xmlArr['Item']['OutOfStockControl']=$this->values['outofstockcontrol']=='1'?1:0;
        }
        
        //对有bestoffer价格的数据进行补充修改
        if ($this->values['listingtype'] =='FixedPriceItem'&&$this->values['bestoffer']==1){
            $this->verb='AddItem';
            if ($this->isVerify()){             
                $this->verb='Verify'.$this->verb;
            }
            //修复bestoffer时变换接口导致的一口价无法刊登
            unset($xmlArr['Item']['BuyItNowPrice']);
            $xmlArr['Item']['BestOfferDetails']=array('BestOfferEnabled'=>'True');
            if ($this->values['bestofferprice']>0){
                $xmlArr['Item']['ListingDetails']['BestOfferAutoAcceptPrice']=$this->values['bestofferprice'];
            }
            if ($this->values['minibestofferprice']>0){
                $xmlArr['Item']['ListingDetails']['MinimumBestOfferPrice']=$this->values['minibestofferprice'];
            }
        }
        if(strlen($this->values['epid'])){
        	$xmlArr['Item']['ProductListingDetails']['ProductReferenceID']=$this->values['epid'];
        }
        if(strlen($this->values['isbn'])){
        	$xmlArr['Item']['ProductListingDetails']['ISBN']=$this->values['isbn'];
        }
        if(strlen($this->values['upc'])){
        	$xmlArr['Item']['ProductListingDetails']['UPC']=$this->values['upc'];
        }
        if(strlen($this->values['ean'])){
        	$xmlArr['Item']['ProductListingDetails']['EAN']=$this->values['ean'];
        }
        #加亮、加粗等。。
        if(!is_null($this->values['listingenhancement'])&&is_array($this->values['listingenhancement'])){
	        if (count(array_filter($this->values['listingenhancement']))){
	            $xmlArr['Item']['ListingEnhancement']=$this->values['listingenhancement'];
	        }
        }
        //gallery选择处理
        if($this->values['gallery']!='0'&&!empty($this->values['gallery'])){
            $xmlArr['Item']['PictureDetails']['GalleryType']=$this->values['gallery'];
        }
        #lotSize特殊处理
        if (intval($xmlArr['Item']['LotSize']) < 2){
            unset($xmlArr['Item']['LotSize']);
        }
        #CDATA
        foreach (array('Title','Description','Country','Location') as $field){
            $temp = $xmlArr['Item'][$field];
            //echo $field." string ".$temp."\n";
            //echo "bbb"."\n";
            $xmlArr['Item'][$field]='<![CDATA['.$temp.']]>';
        }
       // echo  $xmlArr['Item']['Description']."\n";
        if(isset($this->values['storecategoryid'])){
            $xmlArr['Item']['Storefront']['StoreCategoryID']=$this->values['storecategoryid'];
        }
        if(isset($this->values['storecategory2id'])){
            $xmlArr['Item']['Storefront']['StoreCategory2ID']=$this->values['storecategory2id'];
        } 
        if (strlen($this->values['UUID'])){
        	$xmlArr['Item']['UUID']=$this->values['UUID'];
        }
        
        //不需要ShippingServiceCost
        if(@$muban['shippingdetails']['shippingdomtype']=='Calculated'||@$muban['shippingdetails']['shippinginttype']=='Calculated')
        {
        	for($i = 0; $i < count($xmlArr['Item']['ShippingDetails']['ShippingServiceOptions']); $i++)
        	{
	        	unset($xmlArr['Item']['ShippingDetails']['ShippingServiceOptions'][$i]['ShippingServiceAdditionalCost']);
	        	unset($xmlArr['Item']['ShippingDetails']['ShippingServiceOptions'][$i]['ShippingServiceCost']);
        	}
        	
        	for($i =0; $i < count($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption']); $i++)
        	{
	        	unset($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'][$i]['ShippingServiceCost']);
	        	unset($xmlArr['Item']['ShippingDetails']['InternationalShippingServiceOption'][$i]['ShippingServiceAdditionalCost']);
        	}
        }
        $xmlArr=$this->addItemCheckXmlArr($xmlArr,$selleruserid);
        \yii::info($xmlArr,"ebayapi");
        //得到 上传结果
        $responseXml=$this->setRequestMethod($this->verb)
                         ->setRequestBody($xmlArr)
                         ->sendRequest(1);
        $responseSXml=simplexml_load_string($responseXml);
        $responseArr=parent::simplexml2a($responseSXml);
        //print_r($responseArr,1);
        // \Yii::info(print_r($responseArr,1),"ebayapi");
        $baselog=array(
            "base_data"=>"siteID: ".@$this->siteID.", devAccountID: ".@$this->devAccountID." api:".@$this->verb,
            "additem_data"=>$xmlArr,
            "base_resp"=>"[Ack]: ".@$this->_last_response_array['Ack'].", [Errors]: ".print_r(@$this->_last_response_array['Errors'],1));
        \Yii::info(print_r($baselog,1),"ebayapi");
        //取得总费用
        $responseArr['ebayfee']=self::readListingfee($responseSXml);
        // 保存 item
        if(!empty($responseArr['ItemID'])){
            $xmlArr['Item']['Description'] = substr($xmlArr['Item']['Description'],9,-3);
            $this->values['itemdescription']=$xmlArr['Item']['Description'];
            $responseArr=$this->save($responseArr,$selleruserid,$uid,$muban);
        }
        if ($this->isVerify()){
            return $responseArr;
        }
        //保存 发货记录,或是 出错信息
        if ($caogao == 0){
        	$this->logResult($uid,$muban,$selleruserid,$timerid,@$responseArr['ItemID'],$responseArr);
        }
        
        return $responseArr;
    }
    /**
     * 生成刊登Item信息，并修改 responseArr
     *
     * @param array $responseArr
     * @param string $selleruserid
     * @param int $uid
     * @param Ebay_Muban $muban
     * 
     * @return $responseArr
     */
    function save($responseArr,$selleruserid,$uid,$muban){
        $itemID=$responseArr['ItemID'];
        $ed=new getebaydetails();
        $ed->siteID=$this->siteID;
        $viewItemURL=$ed->urldetail().$itemID;
        if ($viewItemURL == $itemID){
            $viewItemURL='http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$itemID;
        }
        $xmlArr['ListingDetails']['ViewItemURL']=$viewItemURL;
        $responseArr['getItem']=array(
            'Title'=>$this->values['itemtitle'],
            'ListingDetails'=>array('ViewItemURL'=>$viewItemURL)
        );
        //保存 item 发布详情到  Item 表中 
        try {
            //保存更多资料
            $getItemAPI=new getitem();
            $xmlArr=Helper_xml::simplexml2a(Helper_xml::xmlparse($this->_last_request_xml));
            $xmlArr['Item']['ItemID']=$itemID;
            $xmlArr['Item']['Seller']['UserID']=$selleruserid;
            $xmlArr['Item']['ListingDetails']['StartTime']=base::dateTime(time());
            if ($xmlArr['Item']['ListingDuration']=='GTC'){
                $next=30*3600*24;
            }else {
                $next=str_replace('Days_','',$xmlArr['Item']['ListingDuration'])*3600*24;
            }
            $endtime=time()+$next;
            $xmlArr['Item']['ListingDetails']['EndTime']=base::dateTime($endtime);
            
            try {
                $getItemAPI->save($xmlArr['Item']);
                throw new \Exception();
            }catch (\Exception $ex1){
                
            }
            $item=EbayItem::find()->where(['itemid'=>$itemID])->one();
            $item->setAttributes(array(
                'mubanid'=>$muban['mubanid'],
            ));
            $item->save();
            $itemdetail=EbayItemDetail::find()->where(['itemid'=>$itemID])->one();
            $itemdetail->setAttributes(array(
            	'additemfee'=>$responseArr['ebayfee']['fee'],
            	'additemfeecurrency'=>$responseArr['ebayfee']['currency'],
            ));
            $itemdetail->save();
        }catch (\Exception $ex){
            \Yii::info(print_r($ex,true));
        }
        return $responseArr;
    }
    /**
     * 记录刊登结果
     *
     * @param int $uid
     * @param Ebay_Muban $muban
     * @param string $selleruserid
     * @param int $timerid
     * @param int $itemID
     * @param array $responseArr
     */
    function logResult($uid,$muban,$selleruserid,$timerid,$itemID,$responseArr,$crosssellingid=NULL){
        $detail=new EbayLogMubanDetail();
        $log=new EbayLogMuban();
        $log->setAttributes(array(
            'uid'=>$uid,
            'selleruserid'=>$selleruserid,
            'title'=>$this->values['itemtitle'],
            'mubanid'=>$muban['mubanid'],
            'method'=>$timerid != 0?'自动':'手动',
            'timerid'=>$timerid,
            'siteid'=>$muban['siteid'],
        	'createtime'=>time(),
        ));
        if($responseArr['Ack']=='Success'){
            $log->itemid=$itemID;
            $log->result=1;
        }elseif($responseArr['Ack']=='Warning'){
            $log->itemid=$itemID;
            $log->result=2;
            $detail->error=$responseArr;
        }else{ //Failure
            $log->result=0;
            $detail->error=$responseArr['Errors'];
        }
        $log->save();
        //日志描述
        $detail->logid=$log->logid;
        $detail->message=@$responseArr['Message'];
        $description="";
        if($log->itemid){
        	$site=EbaySite::findOne(['siteid'=>$muban['siteid']]);
            $description.="在 Ebay 站点".$site->site."上刊登商品成功. ";
            $detail->fee=$responseArr['ebayfee'];
        }else{
            $description.="刊登商品失败.";
            if($detail->error){
                $error=$detail->error;
                $description.="失败原因 是:";
                if (isset($error['LongMessage'])){
                    $description.=$error['LongMessage'];
                }else {
                    foreach ($error as $e){
                        if ($e['SeverityCode'] == 'Error'){
                            $description.=$e['LongMessage'].',';
                        }
                    }
                }
            }
        }
        $detail->description=$description;
        $detail->save();
    }
    /**
     * 在发送之前对 values 数组进行 校验 
     */
    function addItemCheckValues(){
        //货币,是对应国家的.
        $this->values['currency']=Helper_Siteinfo::getSiteCurrency($this->values['siteid']);
        $this->values['site']=EbaySite::find()->where('siteid = :siteid',array('siteid'=>$this->values['siteid']))->one()->site;
        //物流    
        if(empty($this->values['shippingdetails']['ShippingType'])){
            $this->values['shippingdetails']['ShippingType']='Flat';
        }
        if (strlen($this->values['storecategoryid'])==0||$this->values['storecategoryid']==0){
        	unset($this->values['storecategoryid']);
        }
        if (strlen($this->values['storecategory2id'])==0||$this->values['storecategory2id']==0){
        	unset($this->values['storecategory2id']);
        }
        if(!isset($this->values['shippingdetails']['SalesTax']['ShippingIncludedInTax'])||$this->values['shippingdetails']['SalesTax']['ShippingIncludedInTax']==0){
        	unset($this->values['shippingdetails']['SalesTax']);
        }
        
        //如果有设置过calculate物流的，数据进行处理
        if($this->values['shippingdetails']['shippingdomtype']=='Calculated' ||$this->values['shippingdetails']['shippinginttype']=='Calculated' )
        {
        	//如果国内包裹费用为空，则赋值0.00
        	if($this->values['shippingdetails']['CalculatedShippingRate']['PackagingHandlingCosts'] == '')
        	{
        		$this->values['shippingdetails']['CalculatedShippingRate']['PackagingHandlingCosts'] = 0.00;
        	}
        
        	//如果国际包裹费用为空，则赋值0.00
        	if($this->values['shippingdetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts'] == '')
        	{
        		$this->values['shippingdetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts'] = 0.00;
        	}
        	//度量系统
        	$this->values['shippingdetails']['CalculatedShippingRate']['MeasurementUnit'] = ($this->values['siteid'] == '0') ? 'English' : 'Metric';
        
        	//输入方式类型
        	$this->values['shippingdetails']['ShippingType'] = 'Calculated';
        	unset($this->values['shippingdetails']['shippingdomtype']);
        	unset($this->values['shippingdetails']['shippinginttype']);
        }
        else
        {
        	unset($this->values['shippingdetails']['CalculatedShippingRate']);
        	unset($this->values['shippingdetails']['shippingdomtype']);
        	unset($this->values['shippingdetails']['shippinginttype']);
        	//输入方式类型
        	$this->values['shippingdetails']['ShippingType'] = 'Flat';
        }
    }
    
    function addItemCheckXmlArr(&$xmlArr,$selleruserid){
        //清除 空白 未赋值的项 .
        foreach($xmlArr['Item'] as $k=>$v){
            if($v===''){
                unset($xmlArr['Item'][$k]);
            }
        }
        $eus = SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);
        if(is_array($this->values['imgurl'])&&count($this->values['imgurl'])>0){
            //图片处理，多图自动上传到eBay
            if(isset($xmlArr['Item']['Variations'])&&isset($xmlArr['Item']['Variations']["Pictures"])){
                if (isset($xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"][0])) {
                    if (isset($xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"][0]["PictureURL"][0])) {//多系列多图片
                        $pictureTmp=$xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"][0]["PictureURL"][0];
                    }else{//多系列单图片
                        $pictureTmp=$xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"][0]["PictureURL"];
                    }
                }else{
                    if (isset($xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"]["PictureURL"][0])) {//单系列多图片
                        $pictureTmp=$xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"]["PictureURL"][0];
                    }else{//单系列单图片
                        $pictureTmp=$xmlArr['Item']['Variations']["Pictures"]["VariationSpecificPictureSet"]["PictureURL"];
                    }
                }
            }
        	if (count($this->values['imgurl']) > 1 || (strpos($this->values['imgurl'][0],'ebayimg.com')>0 && isset($xmlArr['Item']['Variations']))||(isset($pictureTmp)&&strpos($pictureTmp, 'ebayimg.com')===true)){
            	foreach ($this->values['imgurl'] as $k => $v){
            		if (strpos($v, 'ebayimg.com')===false){
            			$xmlArr['Item']['PictureDetails']['PhotoDisplay ']='VendorHostedPictureShow';
            			
            			$failurecount=0;
            			$pictureManager=new uploadsitehostedpictures();
            			$pictureManager->siteID=$this->siteID;
                        $pictureManager->resetConfig($eus->listing_devAccountID);
            			$pictureManager->eBayAuthToken=$this->eBayAuthToken;
            			$url=$pictureManager->upload($v);
            			while($url['Ack']=='Failure'&&$failurecount<3){
            				$url=$pictureManager->upload($v);
            				$failurecount++;
            			}
            			if (isset($url['SiteHostedPictureDetails']['FullURL'])){
		            		$url=$url['SiteHostedPictureDetails']['FullURL'];
		            		$this->values['imgurl'][$k]=$url;
            			}
            		}
            	}
            	//Variation处理
               if (isset($xmlArr['Item']['Variations'])) {
                    if ((isset($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet']))&&count($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'])){
                        if (isset($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'][0])) {
                            \Yii::info("1!!! ".print_r($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'],1),"ebayapi");
                            foreach ($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'] as $key=>$vsps){
                                if (!is_array($vsps['PictureURL'])) {
                                    $vsps['PictureURL']=array($vsps['PictureURL']);
                                }
                                // \yii::info("vv varpic: ".print_r($vsps['PictureURL'],1),"ebayapi");
                                for($i=0;$i<count($vsps['PictureURL']);$i++){
                                    $v=(string)$vsps['PictureURL'][$i];
                                    if (strpos($v, 'ebayimg.com')===false){
                                        $failurecount=0;
                                        $pictureManager=new uploadsitehostedpictures();
                                        $pictureManager->siteID=$this->siteID;
                                        $pictureManager->resetConfig($eus->listing_devAccountID);
                                        $pictureManager->eBayAuthToken=$this->eBayAuthToken;
                                        $url=$pictureManager->upload($v);
                                        while($url['Ack']=='Failure'&&$failurecount<3){
                                            $url=$pictureManager->upload($v);
                                            $failurecount++;
                                        }
                                        $url=$url['SiteHostedPictureDetails']['FullURL'];
                                        // $vsps['PictureURL'][$i]=$url;
                                        if (count($vsps['PictureURL'])==1) {
                                            $xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'][$key]['PictureURL']='<![CDATA['.str_replace(' ','%20',trim($url)).']]>';
                                        }else{
                                            $xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'][$key]['PictureURL'][$i]='<![CDATA['.str_replace(' ','%20',trim($url)).']]>';
                                        }
                                        \yii::info("1varpic: ".print_r($url,1),"ebayapi");
                                    }
                                }
                            }
                        }else{
                            //['VariationSpecificPictureSet'] =>
                            //    array{
                            //          [VariationSpecificValue] => black
                            //          [PictureURL] => Array
                            //         }
                            \Yii::info("2!!! ".print_r($xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'],1),"ebayapi");
                            $vsps = $xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet'];
                            if (!is_array($vsps['PictureURL'])) {
                                $vsps['PictureURL']=array($vsps['PictureURL']);
                            }
                            for($i=0;$i<count($vsps['PictureURL']);$i++){
                                $v=(string)$vsps['PictureURL'][$i];
                                if (strpos($v, 'ebayimg.com')===false){
                                    $failurecount=0;
                                    $pictureManager=new uploadsitehostedpictures();
                                    $pictureManager->siteID=$this->siteID;
                                    $pictureManager->resetConfig($eus->listing_devAccountID);
                                    $pictureManager->eBayAuthToken=$this->eBayAuthToken;
                                    $url=$pictureManager->upload($v);
                                    while($url['Ack']=='Failure'&&$failurecount<3){
                                        $url=$pictureManager->upload($v);
                                        $failurecount++;
                                    }
                                    $url=$url['SiteHostedPictureDetails']['FullURL'];
                                    if (count($vsps['PictureURL'])==1) {
                                        $xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet']['PictureURL']='<![CDATA['.str_replace(' ','%20',trim($url)).']]>';
                                    }else{
                                        $xmlArr['Item']['Variations']['Pictures']['VariationSpecificPictureSet']['PictureURL'][$i]='<![CDATA['.str_replace(' ','%20',trim($url)).']]>';
                                    }
                                    \yii::info("2varpic: ".print_r($url,1),"ebayapi");
                                }
                            }
                        }
                    }
                }
            	
            }
            foreach($this->values['imgurl'] as $k=>$v){
            	//$this->values['imgurl'][$k]=$v;
            	$this->values['imgurl'][$k]='<![CDATA['.str_replace(' ','%20',trim($v)).']]>';
            }
            $xmlArr['Item']['PictureDetails']['PictureURL']=$this->values['imgurl'];
            
            
        }
        //份数
        if(!empty($this->values['lotsize'])&&$this->values['lotsize']>1){
            $xmlArr['Item']['LotSize']=$this->values['lotsize'];
        }
        //一口价
        if(strlen(($this->values['buyitnowprice']))==0){
            unset($xmlArr['Item']['BuyItNowPrice']);
        }
        //次分类
        if(empty($this->values['secondarycategory'])){
            unset($xmlArr['Item']['SecondaryCategory']);
        }
        if(!isset($xmlArr['Item']['UUID'])){
        	$xmlArr['Item']['UUID']=Helper_Util::getLongUuid();
        }
        return $xmlArr;
    }
    /***
     * 对 对象赋值
     */
    function setValues($values){
        if(isset($this->values)){
            $this->values=array_merge($this->values,$values);
        }else{
            $this->values=$values;
        }
    }
    
    //风格处理
    public function setTemplate($a){
        $m = Ebay_Muban::find('id = ?',$a)->getOne();
        $tpl = $m->template;
        $t=substr($tpl,0,2);
        $n=substr($tpl,2);
        if($t=='pu'){
            $tpl = Ebay_Auctemplates::find('id = ?',$n)->getOne()->template;
        }elseif($t=='pr'){
            $tpl = Ebay_User_Template::find('id = ?',$n)->getOne()->template;
        }
        $tpl = str_replace('[TITLE]',$m->itemtitle,$tpl);
        $tpl = str_replace('[DESCRIPTION_AND_IMAGES]',$m->itemdescription,$tpl);
        return $tpl;
    }
    
    /**
     *
     * @author lxqun
     * @
     */
    public function ApiB($data){
        // 判断所使用的 接口
        if ($data['ListingType']=='FixedPriceItem'){
            $this->verb='AddFixedPriceItem';
        }else{
            $this->verb='AddItem';
        }
        if ($data['ListingType']=='FixedPriceItem' &&$data['BestOfferDetails']['BestOfferEnabled']=='True'){
            $this->verb='AddItem';              
        }
        if ($this->isVerify()){             
            $this->verb='Verify'.$this->verb;           
        }
                // 组织 发送 数组 
        $xmlArr=array(
            'ErrorLanguage'=>'zh_CN',
            'MessageID'=>1,
            'Version'=>$this->config['compatabilityLevel'],
            'Item'=>$data
        );
        
        $responseXml=$this->setRequestMethod($this->verb)
                 ->setRequestBody($xmlArr)
                 ->sendRequest(1);
                 
        $responseSXml=simplexml_load_string($responseXml);
        $responseArr=parent::simplexml2a($responseSXml);
        dump($responseArr,null,90);
        die;

    }
    
    
    /****
     * 与默认值进行 与或 使未填值 全部有默认值.  
     *   
     */
    static function valueMergeWithDefault($v=array()){
        return array_merge(
                array_intersect_key($v,EbayMuban::$default),
                array_diff_key(EbayMuban::$default,$v)
            );
    }
    /*****
     * 从 add item 的 api 的反馈的 ApiRequest 中取出 总费用 ListingFee 
     *  @ $response  是SimpleXMLElement
     *  返回值为 数组:   array(
                        'fee'=>,
                        'currency'=>
                    );
     */
    static function readListingfee($response){
        if(($response instanceof \SimpleXMLElement)&&isset($response->Fees->Fee)){
            foreach($response->Fees->children() as $fee){
                if($fee->Name=='ListingFee'){
                    return array(
                        'fee'=>(string)$fee->Fee,
                        'currency'=>(string)$fee->Fee->attributes()->currencyID
                    );
                    break;
                }
            }
        }
        return false;
    }
    
    /***
     * 转换模板中绑定的商品 名称及 内容的 常量
     * fix bind product title & description
     * @$contents('itemtitle'=>.. ,'itemdescription')
     * @$vars('BINDPRODUCTNAME'=>.. , 'BINDPRODUCTDESCRIPTION'=> .. )
     */
    static function fixBindProductContant(&$content,$vars=array(),$muban=null){
    do{
        if(is_object($muban)&&$muban->goods_id&&$muban->languageid){
            $Ebay_Mygood = Ebay_MyGood::find ( 'id = ?',$muban->goods_id )->getOne();
        
            $Ebay_Mygoodtranslate = Ebay_Mygoodtranslate::find ( 'goods_id = ? And languageid=?',$muban->goods_id ,$muban->languageid )->getOne();
            if($Ebay_Mygood->isNewRecord()||$Ebay_Mygoodtranslate->isNewRecord()) break;
            $vars=array(
            'BINDPRODUCTNAME'=>$Ebay_Mygoodtranslate->title,
            'BINDPRODUCTDESCRIPTION'=>$Ebay_Mygoodtranslate->content,
            'BINDPRODUCTSKU'=>$Ebay_Mygood->sn,
            'BINDPRODUCTPICTURE'=>$Ebay_Mygood->imgurl,
            );
        }
    }while(0);
    if(empty($vars)){
        $vars['BINDPRODUCTNAME']='';
        $vars['BINDPRODUCTDESCRIPTION']='';
        $vars['BINDPRODUCTSKU']='';
        $vars['BINDPRODUCTPICTURE']='';
    }
    foreach($vars as $k=>$v){
        if(isset($content['itemtitle'])) $content['itemtitle']=str_replace('{-'.strtoupper($k).'-}',$v,$content['itemtitle']);
        if(isset($content['itemdescription'])) $content['itemdescription']=str_replace('{-'.strtoupper($k).'-}',$v,$content['itemdescription']);
        if(isset($content['sku'])) $content['sku']=str_replace('{-'.strtoupper($k).'-}',$v,$content['sku']);
        if(isset($content['imgurl'])&&is_array($content['imgurl'])){
        foreach($content['imgurl'] as $k1=>$v1){
            $content['imgurl'][$k1]=str_replace('{-'.strtoupper($k).'-}',$v,$v1);
        }
        }
    }
    return true;    
    }
    
    /***
     * 目前仅用于 标题 .做简单的 置换
     *
     */              
    static function getBindProductContant($content,$vars=array(),$muban=null){
    do{
        if(is_object($muban)&&$muban->goods_id&&$muban->languageid){
            $Ebay_Mygood = Ebay_MyGood::find ( 'id = ?',$muban->goods_id )->getOne();

            $Ebay_Mygoodtranslate = Ebay_Mygoodtranslate::find ( 'goods_id = ? And languageid=?',$muban->goods_id ,$muban->languageid )->getOne();
            if($Ebay_Mygood->isNewRecord()||$Ebay_Mygoodtranslate->isNewRecord()) break;
            $vars=array(
            'BINDPRODUCTNAME'=>$Ebay_Mygoodtranslate->title,
            'BINDPRODUCTDESCRIPTION'=>$Ebay_Mygoodtranslate->content,
            'BINDPRODUCTSKU'=>$Ebay_Mygood->sn,
            'BINDPRODUCTPICTURE'=>$Ebay_Mygood->imgurl,
            );
        }
    }while(0);
    if(empty($vars)){
        $vars['BINDPRODUCTNAME']='';
        $vars['BINDPRODUCTDESCRIPTION']='';
        $vars['BINDPRODUCTSKU']='';
        $vars['BINDPRODUCTPICTURE']='';

    }
    foreach($vars as $k=>$v){
        $content=str_replace('{-'.strtoupper($k).'-}',$v,$content);
    }
    return $content;
    }
    
    function changepicurltoebay($xmlarr,$selleruserid){
    	$token = SaasEbayUser::findOne(['selleruserid'=>$selleruserid])->listing_token;
        $DevAcccountID = SaasEbayUser::findOne(['selleruserid'=>$selleruserid])->listing_devAccountID;
    	$xmlarr2=array();
    	$ushp = new uploadsitehostedpictures();
        $ushp->resetConfig($DevAcccountID);
    	$ushp->eBayAuthToken=$token;
    	foreach ($xmlarr as $i=>$j){
    		set_time_limit(0);
    		$result = $ushp->upload (str_replace(' ','%20',trim($j)));
    		if ($result['Ack']=='Success'||$result['Ack']=='Warning'){
    			$xmlarr2[$i]=$result['SiteHostedPictureDetails']['FullURL'];
    		}
    	}
    	unset($this->values['imgurl']);
    	$this->values['imgurl']=$xmlarr2;
    }



function xmlToArray($simpleXmlElement){
    $retValue=array();
    $tmp=(array)$simpleXmlElement;
    foreach($tmp as $k=>$v){
        if($v instanceof \SimpleXMLElement||(is_array($v)) ){  //||is_array($v)
            $retValue[$k]=self::xmlToArray($v);
        }else{
            $retValue[$k]=$v;
        }
    }
    return $retValue;
}


}//end class