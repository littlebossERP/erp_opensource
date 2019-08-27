<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use yii\base\Exception;
class EbayInterface_UploadSiteHostedPicturesException_UrlTooLong extends Exception {}
class EbayInterface_UploadSiteHostedPicturesException_UploadFailure extends Exception {}
/**
 * 上传图片到eBay图床
 * @package interface.ebay.tradingapi
 */
class uploadsitehostedpictures extends base{
	//上传拍卖物品
	public $verb ='UploadSiteHostedPictures';
	public function upload($url){
	    if (strlen($url) >1024){
	        throw new EbayInterface_UploadSiteHostedPicturesException_UrlTooLong(__t('地址不能超过1024个字符'));
	    }
	    $xmlArr=array(
	        'ExternalPictureURL'=>'<![CDATA['.$url.']]>',
	    );
	    $response=$this->setRequestMethod($this->verb)
	        ->setRequestBody($xmlArr)
	        ->sendRequest();
	    return $response;    
        /*if (!$this->responseIsFailure()){
            return $response['SiteHostedPictureDetails']['FullURL'];
        }
        throw new EbayInterface_UploadSiteHostedPicturesException_UploadFailure($response);*/
	}
}