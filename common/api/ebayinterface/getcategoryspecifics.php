<?php
namespace common\api\ebayinterface;

use yii;
use common\api\ebayinterface\base;
use eagle\models\EbaySite;
use eagle\models\EbaySpecific;
use common\api\ebayinterface\lms\downloadfile;
use common\helpers\Helper_Filesys;
use common\helpers\Helper_XmlLarge;
use common\helpers\Helper_xml;
/**
 * 获得eBay分类Specific信息
 * @package interface.ebay.tradingapi
 */ 
class getcategoryspecifics  extends base{
	public $verb = 'GetCategorySpecifics';
	public $categoryid = 0;
	public function api(){
		$xmlArr=array(
			'DetailLevel'=>'ReturnAll',
			'CategorySpecificsFileInfo'=>true,
		);
		if ($this->categoryid > 0){
			$xmlArr=array(
				'DetailLevel'=>'ReturnAll',
				'CategoryID'=>$this->categoryid,
				'CategorySpecificsFileInfo'=>true,
			);
		}
		$xml=$this->setRequestBody($xmlArr)->sendRequest(1,600);
		// print_r($xml,false);
		$result=parent::xmlparse($xml);
		// $this->xmlCache($this->categoryid,$xml);
		return $result;
	}


    public function syncLeafSpecific(){
        //No.1-同步specifics
        echo "Specific>>> start sync specifics\n";
        $result=$this->api();
        // print_r($result,false);
        echo "Specific>>> start analyze specific\n";
        $now=time();//同一个站点同一个目录的属性使用同一个update时间
        //No.3-解析返回数据
        if (isset($result['Recommendations']['NameRecommendation'])) {
            if (!isset($result['Recommendations']['NameRecommendation'][0])) {//单数据时候,转换成array
                $rBak=array(0=>$result['Recommendations']['NameRecommendation']);
                // print_r($rBak,false);
                $result['Recommendations']['NameRecommendation']=$rBak;
            }
            //No.4-删除旧数据
            // EbaySpecific::deleteAll('categoryid = :categoryid and siteid = :siteid',[':categoryid'=>$this->categoryid,':siteid'=>$this->siteID]);
            foreach ($result['Recommendations']['NameRecommendation'] as $xrnr){
                $name=$xrnr['Name'];
                $maxvalue='';
                $minvalue='';
                $val=array();
                $relationship=array();
                $varspecdis='';
                if (isset($xrnr['ValidationRules']['VariationSpecifics'])) {//多属性不支持此specifics
                    $varspecdis=$xrnr['ValidationRules']['VariationSpecifics'];
                }
                if (isset($xrnr['ValidationRules']['MaxValues'])){
                    $maxvalue=$xrnr['ValidationRules']['MaxValues'];
                }
                if (isset($xrnr['ValidationRules']['MinValues'])){
                    $minvalue=$xrnr['ValidationRules']['MinValues'];
                }
                if (isset($xrnr['ValidationRules']['Relationship'])){
                    if(!empty($xrnr['ValidationRules']['Relationship']['ParentName'])){
                        $relationship['ParentName']=$xrnr['ValidationRules']['Relationship']['ParentName'];
                    }
                    if(!empty($xrnr['ValidationRules']['Relationship']['ParentValue'])){
                        $relationship['ParentValue']=$xrnr['ValidationRules']['Relationship']['ParentValue'];
                    }
                }
                if (isset($xrnr['ValidationRules']['SelectionMode'])){
                    $selectionmode=$xrnr['ValidationRules']['SelectionMode'];
                }

                if (isset($xrnr['ValueRecommendation']['Value'])){
                    $xrnr['ValueRecommendation']=array($xrnr['ValueRecommendation']);
                }
                if (isset($xrnr['ValueRecommendation'])){
                    foreach ($xrnr['ValueRecommendation'] as $xrnrvr){
                        array_push($val,$xrnrvr['Value']);
                    }
                }
                $es=EbaySpecific::find()
                    ->where(['categoryid'=>$this->categoryid])
                    ->andwhere(['siteid'=>$this->siteID])
                    ->andwhere(['name'=>$name])
                    ->one();
                if (empty($es)) {
                    echo "new\n";
                    $es=new EbaySpecific();
                }
                //No.4-保存新数据
                foreach ($val as &$v){
                    $v=(string)$v;
                }

                $es->setAttributes([
                    'categoryid'=>$this->categoryid,
                    'siteid'=>$this->siteID,
                    'maxvalue'=>$maxvalue,
                    'minvalue'=>$minvalue,
                    'selectionmode'=>$selectionmode,
                    'name'=>$name,
                    // 'value'=>$val,
                    // 'relationship'=>$relationship,
                    'record_updatetime'=>$now,
                ]);
                $es->relationship = $relationship;
                $es->value = $val;
                $es->save(false);
            }
        }
        //No.5-删除旧数据
        echo "Specific>>> delete Expired record!\n";
        EbaySpecific::deleteAll('categoryid = :categoryid AND siteid = :siteid AND record_updatetime < :recordtime',[':categoryid'=>$this->categoryid,':siteid'=>$this->siteID,':recordtime'=>($now-3600)]);

    }

    /**
     * [syncLeafSpecificBatch description]
     * @Author
     * @Editor willage 2016-11-20T11:23:15+0800
     */
    public function syncLeafSpecificBatch($categoryid=null){
        $result=$this->api();
        if ($result['Ack']!=='Success'&&$result['Ack']!=='Failure'){//判断是否成功
        	return;
        }
        echo "start check specifics version\n";
        $fileReferenceId = $result['FileReferenceID'];
        $taskReferenceId = $result['TaskReferenceID'];
        if (strlen($fileReferenceId)==0&&strlen($taskReferenceId)==0){//判断文件ID长度
        	return;
        }
        // $s = EbaySite::findOne('siteid = '.$this->siteID);
        $s = EbaySite::findOne('siteid = 0');
        if ($fileReferenceId.'-'.$taskReferenceId == $s->specifics_jobid){//判断是否最新
            echo "no updates\n";
            return;
        }
        echo "start download specifics\n";
        //No.2-download 下载文件
        $df=new downloadfile();//下载
        $df->eBayAuthToken=$this->eBayAuthToken;
        $filename=Yii::$app->basePath.'/runtime/xml/specifics/'.$this->siteID.'.zip';
        $response=$df->api($fileReferenceId,$taskReferenceId,$filename);
        echo "download here\n";
        set_time_limit(0);
        if (!$response){
            echo "download failed\n";
            return;
        }
        echo "start analyze specific\n";//解析
        //No.3-解析xml文件
        $xmlfile=dirname($filename).'/'.$taskReferenceId.'_report.xml';
        $reader=new Helper_XmlLarge($xmlfile);
        //No.4-批量插入
        while ($reader->read('Recommendations')){
            $xr=$reader->toSimpleXmlObj();
            $categoryID=(string)$xr->CategoryID;
            EbaySpecific::deleteAll('categoryid = :categoryid and siteid = :siteid',[':categoryid'=>$categoryID,':siteid'=>$site['no']]);
            foreach ($xr->NameRecommendation as $xrnr){
                $xrnr=Helper_xml::simplexml2a($xrnr);
                $name=$xrnr['Name'];
                $maxvalue='';
                $minvalue='';
                $val=array();
                $relationship=array();
                if (isset($xrnr['ValidationRules']['MaxValues'])){
                    $maxvalue=$xrnr['ValidationRules']['MaxValues'];
                }
                if (isset($xrnr['ValidationRules']['MinValues'])){
                    $minvalue=$xrnr['ValidationRules']['MinValues'];
                }
                if (isset($xrnr['ValidationRules']['Relationship'])){
                    if(!empty($xrnr['ValidationRules']['Relationship']['ParentName'])){
                        $relationship['ParentName']=$xrnr['ValidationRules']['Relationship']['ParentName'];
                    }
                    if(!empty($xrnr['ValidationRules']['Relationship']['ParentValue'])){
                        $relationship['ParentValue']=$xrnr['ValidationRules']['Relationship']['ParentValue'];
                    }
                }
                $selectionmode=$xrnr['ValidationRules']['SelectionMode'];
                if (isset($xrnr['ValueRecommendation']['Value'])){
                    $xrnr['ValueRecommendation']=array($xrnr['ValueRecommendation']);
                }
                if (isset($xrnr['ValueRecommendation'])){
                    foreach ($xrnr['ValueRecommendation'] as $xrnrvr){
                        array_push($val,$xrnrvr['Value']);
                    }
                }
                $es=new EbaySpecific();
                foreach ($val as &$v){
                    $v=(string)$v;
                }
                $es->setAttributes(array(
                    'categoryid'=>$categoryID,
                    'siteid'=>$site['no'],
                    'name'=>$name,
    //                         'value'=>$val,
    //                         'relationship'=>$relationship,
                    'maxvalue'=>$maxvalue,
                    'minvalue'=>$minvalue,
                    'selectionmode'=>$selectionmode
                ));
                $es->relationship = $relationship;
                $es->value = $val;
                @$es->save();
            }
        }
        //No5-更新fileReferenceId/taskReferenceId(记录在EbaySite)
        $sitetmp=EbaySite::find()->where('siteid = :s',[':s'=>$site['no']])->one();
        $sitetmp->specifics_jobid=$fileReferenceId.'-'.$taskReferenceId;
        $sitetmp->save();
        if(file_exists($xmlfile)){
            unlink($xmlfile);
            unlink($filename);
        }
    }

}//end class