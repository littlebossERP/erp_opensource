<?php
namespace common\api\ebayinterface;

use \Yii;
use common\api\ebayinterface\base;
use eagle\models\SaasEbayUser;
use eagle\models\EbayCategory;
use common\helpers\Helper_Filesys;
use common\helpers\Helper_XmlLarge;
/**
 * 获得eBay分类列表
 * @package interface.ebay.tradingapi
 */ 
class getcategories extends base{
	public $verb='GetCategories';
	public function api(){
    $this->verb = 'GetCategories';
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
            // 'LevelLimit'=>5,

            // 'CategorySiteID'=>0,
            // 'DetailLevel'=>'ReturnAll',
        );
        // $xmlArr+=$this->before_request_xmlarray;
        var_dump($xmlArr);
        return $this->setRequestBody($xmlArr)->sendRequest(0,600);
    }
    /**
     * [getverApi 获取最简单response,注意为了获取category 版本]
     * @Author willage 2016-11-21T15:48:20+0800
     * @Editor willage 2016-11-21T15:48:20+0800
     */
    public function getverApi($SiteID=0){
		$xmlArr=array(
			'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
			'CategorySiteID'=>$SiteID,//站点ID
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest(0,300);
		return $result;
	}
    //ebay getcategories接口实现，通过接口获取ebay的类别信息，生成文件，并解析进数据库
	public function getallApi($SiteID=0,$timeout=300,$responseSaveToFileName=null){
		$xmlArr=array(
			'RequesterCredentials'=>array(
                'eBayAuthToken'=>$this->eBayAuthToken,
            ),
			'CategorySiteID'=>$SiteID,//站点ID
			'LevelLimit'=>100,//表示拉取<=此目录深度
			'DetailLevel'=>'ReturnAll',//返回所有
			// 'CategoryParent'不指定此父级目录（表示拉取此父级的子目录）
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest(0,$timeout,$responseSaveToFileName);
		return $result;
	}
	//ebay getcategories接口实现，通过接口获取ebay的类别信息，生成文件，并解析进数据库
	public function realtimeApi($CategoryParent=0,$LevelLimit=1,$timeout=300,$responseSaveToFileName=null){
		$xmlArr=array(
			'CategorySiteID'=>$this->siteID,
			'CategoryParent'=>$CategoryParent,
			'LevelLimit'=>$LevelLimit,
			'DetailLevel'=>'ReturnAll',
		);
		if($CategoryParent==0){
			unset($xmlArr['CategoryParent']);
		}
		$result=$this->setRequestBody($xmlArr)->sendRequest(0,$timeout,$responseSaveToFileName);
		return $result;
	}

	/**
	 * [syncEbayCategory 检测版本,更新同步]
	 * @Author willage 2016-11-19T23:36:54+0800
	 * @Editor willage 2016-11-19T23:36:54+0800
	 */
	static function syncEbayCategory($siteId=0)
	{
	  	$ue=SaasEbayUser::find()->where('selleruserid = :s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
	  	$api=new getcategories();
        $api->resetConfig($ue->DevAcccountID);
	  	$api->eBayAuthToken=$ue->token;
		$api->siteID=$siteId;
		set_time_limit(0);
		$r=$api->getverApi($siteId);
		print_r($r,false);
		if ($r['Ack']=='Failure'){
	        echo "Category>>> getcategory api is failure!\n";
	        return;
		}
        $category=EbayCategory::find()->where('siteid = :s',[':s'=>$siteId])->orderBy('version DESC')->one();
        //No.3-如果版本没有做更新则不更新
        echo "Category>>> start check category version!\n";
        if (!empty($category)&&$category->version >= $r['CategoryVersion']){
	        echo "Category>>> no updates\n";
	        return;
        }
        //No.4-版本更新,保存成文件
        $version=$r['CategoryVersion'];
        echo "Category>>> new version :".$r['CategoryVersion']."\n";
        $xmlfilename=Yii::$app->basePath.'/runtime/xml/category/'.$siteId.'.xml';
        Helper_Filesys::mkdirs(dirname($xmlfilename));
        echo "start download all categories\n";
        $r=$api->realtimeApi(0,100,600,$xmlfilename);

        if ($r===false){//失败则继续拉取其他站点
        	echo "Category>>> update failed\n";
        	return;
        }
        echo "Category>>> start analyze\n";

        $reader=new Helper_XmlLarge($xmlfilename);
        try{
        $reader->read('Ack');
        }catch (Exception $ex){
        	echo "Category>>> read file failed\n";
        	return;
        }
        if ((string)$reader->toSimpleXmlObj()=='Failure'){
        	echo "Category>>> update failed\n";
        	return;
        }
        $fp=fopen($xmlfilename,'r');
        fseek($fp,-24,SEEK_END);
        if (fgets($fp)!='</GetCategoriesResponse>'){
        	echo "Category>>> update failed\n";
        	if($fp) fclose($fp);
        	return;
        }
        if($fp) fclose($fp);

        //No.5-根据site delete categories记录
        echo "Category>>> start delete all category for site!\n";
        EbayCategory::deleteAll('siteid=:s',[':s'=>$siteId]);
        //No.6-组织数据
        $dbase=\Yii::$app->db;
        $batchInsertArr=array();
        while ($reader->read('Category')){
            $c=$reader->toSimpleXmlObj();
            $eCategory=new EbayCategory();
            $eCategory->setAttributes([
                'categoryid'=>(int)$c->CategoryID,
                'name'=>(string)$c->CategoryName,
                'pid'=>(string)$c->CategoryParentID,
                'level'=>(int)$c->CategoryLevel,
                'leaf'=>((@$c->LeafCategory)?1:0),
                'siteid'=>$siteId,
                'islsd'=>((@$c->LSD)?1:0),//LSD="lot size disable",1表示不支持lot size
                'bestofferenabled'=>@$c->BestOfferEnabled==true?1:0,
                'autopayenable'=>@$c->AutoPayEnabled==true?1:0,
                'version'=>$version,
                'record_updatetime'=>time(),
                'orpa'=>@$c->ORPA==true?1:0,
                'orra'=>@$c->ORRA==true?1:0,
                'virtual'=>@$c->Vi,
                'expired'=>@$c->Expired==true?1:0,
                ]);
        //No.7-插入新的categories记录
            $eCategory->save(false);
        }
        // print_r($batchInsertArr,false);
        echo "Category>>> update Category finish!\n";


       //  $dbase=\Yii::$app->db;
       //  $batchInsertArr=array();
       //  while ($reader->read('Category')){
       //  	$c=$reader->toSimpleXmlObj();
       //  	$batchInsertArr[]=array(
    			// (int)$c->CategoryID,
    			// (string)$c->CategoryName,
    			// (string)$c->CategoryParentID,
    			// (int)$c->CategoryLevel,
    			// ((@$c->LeafCategory)?1:0),
    			// $siteId,
    			// ((@$c->LSD)?1:0),//LSD="lot size disable",1表示不支持lot size
    			// @$c->BestOfferEnabled==true?1:0,
    			// @$c->AutoPayEnabled==true?1:0,
    			// $version,
    			// time(),
       //          @$c->ORPA==true?1:0,
       //          @$c->ORRA==true?1:0,
       //          @$c->Virtual==true?1:0,
       //          @$c->Expired==true?1:0,
       //  	);
       //      }
       //  // print_r($batchInsertArr,false);
       //  $columnArr=array(
    			// 'categoryid',
    			// 'name',
    			// 'pid',
    			// 'level',
    			// 'leaf',
    			// 'siteid',
    			// 'islsd',//LSD="lot size disable",1表示不支持lot size
    			// 'bestofferenabled',
    			// 'autopayenable',
    			// 'version',
       //          'record_updatetime',
       //          'orpa',
       //          'orra',
       //          'virtual',
       //          'expired',);
       //  //No.7-批量插入新的categories记录(注意数据组织时,$columnArr和$batchInsertArr对应)
       //  echo "start batch insert category for site!\n";
       //  $dbase->createCommand()->batchInsert("ebay_category", $columnArr, $batchInsertArr)->execute();
       //  echo "update Category finish!\n";
	}
}//end class


?>
