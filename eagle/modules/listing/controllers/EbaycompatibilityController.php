<?php

namespace eagle\modules\listing\controllers;

use Yii;
use yii\base\Controller;
use eagle\modules\listing\models\BaseFitmentmuban;
use yii\data\Pagination;
use eagle\models\EbayCategory;
use common\api\ebayinterface\product\getcompatibilitysearchvalues;
use common\api\ebayinterface\shopping\getsingleitem;
use common\helpers\Helper_Siteinfo;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SaasEbayUser;
/**
 * @author fanjs
 * 处理汽配类目的兼容性
 *
 */
class EbaycompatibilityController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * 展示ebay汽配的模板列表
     * @author fanjs
     */
    public function actionShow()
    {
        AppTrackerApiHelper::actionLog('listing_ebay','/ebaycompatibility/show');
        $ebayselleruserid = SaasEbayUser::find()
                                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                                ->andwhere('listing_status = 1')
                                ->andwhere('listing_expiration_time > '.time())
                                ->select('selleruserid')
                                ->asArray()
                                ->all();
        $ebaydisableuserid = SaasEbayUser::find()
                        ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                        ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                        ->asArray()
                        ->all();
        $data = BaseFitmentmuban::find();
        if (isset($_REQUEST['name']) && strlen($_REQUEST['name'])){
            $sql_name = str_replace('delete', '', $_REQUEST['name']);
            $sql_name = str_replace('truncate', '', $_REQUEST['name']);
            $sql_name = str_replace('alert', '', $_REQUEST['name']);
            $sql_name = str_replace('insert', '', $_REQUEST['name']);
            $data->andWhere('name like "%'.$sql_name.'%"');
        }
        if (isset($_REQUEST['site']) && strlen($_REQUEST['site'])){
            $data->andWhere(['siteid'=>$_REQUEST['site']]);
        }
        $pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
        $mubans = $data->offset($pages->offset)
        ->limit($pages->limit)
        ->all();
        return $this->render('show',['mubans'=>$mubans,'pages'=>$pages,'ebaydisableuserid'=>$ebaydisableuserid]);
    }
    
    /**
     * 删除某个汽配模板
     * @author fanjs
     */
    public function actionDelete(){
        if (\Yii::$app->request->isPost){
            try {
                BaseFitmentmuban::deleteAll(['id'=>$_POST['mubanid']]);
            }catch (\Exception $e){
                return $e->getMessage();
            }
            return 'success';
        }else{
            return '非法请求';
        }
    }
    
    /**
     * 新建或编辑汽配范本
     * @author fanjs
     */
    public function actionEdit(){
        AppTrackerApiHelper::actionLog('listing_ebay','/ebaycompatibility/edit');
        if (isset($_REQUEST['fid'])){
            $fitment = BaseFitmentmuban::findOne(['id'=>$_REQUEST['fid']]);
        }else{
            $fitment = new BaseFitmentmuban();
        }
        if ($fitment->isNewRecord){
            if (\Yii::$app->request->isPost){
                $siteid=$_POST['site'];
                $name=$_POST['mubanname'];
//              $sku=$_POST['sku'];
                $primarycategory=$_POST['primarycategory'];
//              $fit=Base_Fitmentmuban::find('name = ?',$name)->getOne();
//              if (count($_POST['itemcompatibilitylist'])==0){
//                  return $this->_redirectFailureMessage('fitment属性组为空,请重新输入',url('fitment/singlecreate'));
//              }
                ################################Fitment begin#######################################
                if(isset($_POST['itemcompatibilitylist'])){
                    $itemcompatibilitylistarr=array();
                    foreach ($_POST['itemcompatibilitylist'] as $key=>$itemcompatibility){
                        for ($i=0;$i<count($itemcompatibility);$i++){
                            $itemcompatibilitylistarr[$i][$key]=$itemcompatibility[$i];
                        }
                    }
                }
                $fitment->siteid=$siteid;
                $fitment->primarycategory=$primarycategory;
                $fitment->name=$name;
                $fitment->itemcompatibilitylist=$itemcompatibilitylistarr;
                $fitment->created = time();
                $fitment->save();
                //如果有设置sku，进行对sku映射的保存 2015-12-22 暂时不保存sku的数据
//              if (strlen($sku)){
//                  $sku=explode(',',$sku);
//                  foreach ($sku as $sk){
//                      $fim=Base_Fitmenttosku::find('sku = ? and name != ? and siteid = ?',$sk,$name,$siteid)->getOne();
//                      if (!$fim->isNewRecord()){
//                          continue;
//                      }else{
//                          $fim=Base_Fitmenttosku::find('sku = ? and name = ? and siteid = ?',$sk,$name,$siteid)->getOne();
//                          $fim->fid=$fit->id;
//                          $fim->sku=$sk;
//                          $fim->name=$name;
//                          $fim->siteid=$siteid;
//                          $fim->save();
            
//                          $itemids=Helper_Array::getCols(Ebay_Item::find('sku = ? and listingstatus = ?',$sk,'Active')->setColumns(array('itemid'))->asArray()->getAll(),'itemid');
//                          if (count($itemids)>0){
//                              $ibaysets=Ebay_Item_Ibayset::find('itemid in (?) and [hasfitment] = 1',$itemids)->getAll();
//                              foreach ($ibaysets as $ibayset){
//                                  $ibayset->hasfitment=0;
//                                  $ibayset->save();
//                              }
//                          }
//                      }
//                  }
//              }
                echo "<meta http-equiv='Content-Type'' content='text/html; charset=utf-8'>";
                echo '<script charset="utf-8">alert("保存成功");window.close();</script>';exit();
                ################################Fitment end#############################################
            }
        }else {
//          $skus=Helper_Array::getCols(Base_Fitmenttosku::find('name = ?',$fitment->name)->setColumns(array('sku'))->asArray()->getAll(),'sku');
//          if (count($skus)){
//              $this->_view['sku']=implode(',',$skus);
//          }
            if (\Yii::$app->request->isPost){
                $name=$_POST['mubanname'];
                //              $sku=$_POST['sku'];
                $primarycategory=$_POST['primarycategory'];
            ################################Fitment begin#######################################
                if(isset($_POST['itemcompatibilitylist'])){
                    $itemcompatibilitylistarr=array();
                    foreach ($_POST['itemcompatibilitylist'] as $key=>$itemcompatibility){
                        for ($i=0;$i<count($itemcompatibility);$i++){
                            $itemcompatibilitylistarr[$i][$key]=$itemcompatibility[$i];
                        }
                    }
                    $siteid=$_POST['site'];
                    $fitment->siteid=$siteid;
                    $fitment->primarycategory=$primarycategory;
                    $fitment->name=$name;
                    $fitment->itemcompatibilitylist=$itemcompatibilitylistarr;
                    $fitment->save();
//                  $sku=$_POST['sku'];
//                  $name=$fitment->name;
//                  if (strlen($sku)){
//                      $sku=explode(',',$sku);
//                      Base_Fitmenttosku::meta()->deleteWhere('fid = ?',$fitment->id);
//                      foreach ($sku as $sk){
//                          $fim=Base_Fitmenttosku::find('sku = ? and name != ? and siteid = ?',$sk,$name,$siteid)->getOne();
//                          if (!$fim->isNewRecord()){
//                              continue;
//                          }else{
//                              $fim=Base_Fitmenttosku::find('sku = ? and name = ? and siteid = ? ',$sk,$name,$siteid)->getOne();
//                              $fim->fid=$fitment->id;
//                              $fim->sku=$sk;
//                              $fim->name=$name;
//                              $fim->siteid=$siteid;
//                              $fim->save();
                                
//                          //针对已经已经添加过fitment的数据改为未添加过
//                              $itemids=Helper_Array::getCols(Ebay_Item::find('sku = ? and listingstatus = ?',$sk,'Active')->setColumns(array('itemid'))->asArray()->getAll(),'itemid');
//                              if (count($itemids)>0){
//                                  $ibaysets=Ebay_Item_Ibayset::find('itemid in (?) and [hasfitment] = 1',$itemids)->getAll();
//                                  foreach ($ibaysets as $ibayset){
//                                      $ibayset->hasfitment=0;
//                                      $ibayset->save();
//                                  }
//                              }
//                          }
//                      }
//                  }
                    ################################Fitment end#############################################
                    echo "<meta http-equiv='Content-Type'' content='text/html; charset=utf-8'>";
                    echo '<script charset="utf-8">alert("保存成功");window.close();</script>';exit();
                }else{
                    echo "<meta http-equiv='Content-Type'' content='text/html; charset=utf-8'>";
                    echo '<script charset="utf-8">alert("请设置汽配数据");return false;</script>';exit();
                }
            }
        }
        return $this->render('edit',['fitment'=>$fitment]);
    }
    
    /**
     * 汽配选择平台类目操作
     * @author fanjs
     */
    public function actionSelectcategroy(){
        $elementid = $_REQUEST['elementid'];
        if (\Yii::$app->request->isPost) {
            echo <<<EOF
            <script language="javascript">
                var es=window.opener.document.getElementById('primarycategory');
                es.value="{$_POST['primaryCategory']}";
                window.opener.vaildfitment();
                window.close();
            </script>
EOF;
            die ();
        }
        return $this->render('selectcategory',['elementid'=>$elementid,'siteid'=>$_REQUEST['siteid']]);
    }
    
    /**
     * 检测汽配模板的模板名是否可用
     * @author fanjs
     */
    public function actionCheckname(){
        if(\Yii::$app->request->isPost){
            $muban = BaseFitmentmuban::findOne(['name'=>$_POST['value']]);
            if (!empty($muban)){
                return 'failure';
            }else{
                return 'success';
            }
        }
    }
    
    /**
     * 检测汽配模板的选择类目是否支持汽配兼容
     * @author fanjs
     */
    public function actionCheckcategory(){
        if (\Yii::$app->request->isPost) {
            $category = EbayCategory::findOne(['siteid'=>$_POST['siteid'],'categoryid'=>$_POST['category'],'leaf'=>'1']);
            if(empty($category)){
                return 'failure';
            }else{
                if ($category->iscompatibility == '1'){
                    return $category->compatibilityname;
                }else{
                    return 'failure';
                }
            }
        }
    }
    
    /**
     * ajax获取当前类目的fitment属性名与值
     * @author fanjs
     */
    public function actionAjaxgetcompatibilitysearchnames(){
        $siteid=$_GET['siteid'];
        $categoryid=$_GET['category'];
        $fitmentname = $_GET['name'];
        $cnames = explode(',',$fitmentname);
        foreach ($cnames as $k=>$v){
            $cnames[$k] = str_replace(' ','',$v);
        }
        reset($cnames);
        $first_name=current($cnames);
        $apisearchvalue = new getcompatibilitysearchvalues();
        $cvalues=$apisearchvalue->get($first_name,$categoryid, $siteid);
        
        return $this->renderPartial('ajaxgetcompatibilitysearchnames',['cnames'=>$cnames,'cvalues'=>$cvalues,'siteid'=>$siteid]);
    }
    
    /**
     * 获取fitment下拉级联狂后续的值
     * @author fanjs
     */
    public function actionAjaxgetcompatibilitysearchvalues(){
        if($_REQUEST['PropertyName_next']=='ProductionPeriod'){$_REQUEST['PropertyName_next']='Production Period';}
        if($_REQUEST['PropertyName_next']=='CarsType'){$_REQUEST['PropertyName_next']='Cars Type';}
        if($_REQUEST['PropertyName_next']=='CarsYear'){$_REQUEST['PropertyName_next']='Cars Year';}
        $siteid=$_REQUEST['siteid'];
        $categoryid=$_REQUEST['categoryid'];    //6763
        $propertyFilter=$_REQUEST['propertyFilter'];
        
        $pf=array();$i=0;
        if (count($propertyFilter)==1){
            foreach($propertyFilter as $PropertyName=>$PropertyValue ){
                $pf['propertyName']=filterpropertyname($PropertyName);
                $pf['value']['text']['value']=$PropertyValue;
            }
        }else{
            foreach($propertyFilter as $PropertyName=>$PropertyValue ){
                $pf[$i]['propertyName']=filterpropertyname($PropertyName);
                $pf[$i]['value']['text']['value']=$PropertyValue;$i+=1;
            }
        }
        //error_log('$pf:'.print_r($pf,1),3,INDEX_DIR.'/1.txt');
        $apisearchvalue = new getcompatibilitysearchvalues();
        $r3=$apisearchvalue->get($_REQUEST['PropertyName_next'], $categoryid, $siteid, $pf);
        if($r3) echo json_encode($r3);
        exit();
    }
    
    /**
     * 抓取ebay汽配信息的后台处理
     * @author fanjs
     */
    public function actionAjaxgetfromitem(){//291636486064,281887720355
        AppTrackerApiHelper::actionLog('listing_ebay','/ebaycompatibility/ajaxgetfromitem');
        if(\Yii::$app->request->isPost){
            $itemid = $_POST['itemid'];
            $api = new getsingleitem();
            $result = $api->getitemfitment($itemid);
            if($api->responseIsFail){
                return $result['Errors']['0']['LongMessage'];
            }else{
                if (!isset($result['ItemCompatibilityList'])){
                    return '该Item无汽配信息';
                }else{
                    try {
                        $site_map = Helper_Siteinfo::getEbaySiteIdList('en','no');
                        $muban = new BaseFitmentmuban();
                        $muban->primarycategory = $result['PrimaryCategoryID'];
                        $muban->siteid = $result['Site']!='CoustomCode'?$site_map[$result['Site']]:100;
                        $_tmps = [];$_tmp = [];
                        $category = EbayCategory::findOne(['categoryid'=>$result['PrimaryCategoryID'],'siteid'=>$muban->siteid]);
                        if(is_null($category->compatibilityname)){
                            return '该类目api可能还未开通汽配信息,请联系技术核实';
                        }else{
                            $compatibilityname = $category->compatibilityname;
                            $compatibilityname = explode(',', $compatibilityname);
                        }
                        $compatibilityname_map = array_combine($compatibilityname, $compatibilityname);
                        foreach ($result['ItemCompatibilityList']['Compatibility'] as $cv){
                            $compatibilityname_map_tmp = $compatibilityname_map;
                            foreach ($cv['NameValueList'] as $cvv){
                                if (!isset($cvv['Name'])){
                                    continue;
                                }
                                $_tmp[$cvv['Name']] = $cvv['Value']['0'];
                                unset($compatibilityname_map_tmp[$cvv['Name']]);
                            }
                            if (count($compatibilityname_map_tmp)>0){
                                foreach ($compatibilityname_map_tmp as $tv){
                                    $_tmp[$tv] = 'All';
                                }
                            }
                            $_tmps[]=$_tmp;
                            
                        }
                        $muban->itemcompatibilitylist = $_tmps;
                        $muban->created = time();
                        $muban->updated = time();
                        $muban->name = $_POST['mubanname'];
                        if($muban->save()){
                            return 'success';
                        }else{
                            return print_r($muban->getErrors());
                        }
                    }catch (\Exception $e){
                        return $e->getMessage();
                    }
                }
            }
        }
    }
}

function filterpropertyname($str){
    switch ($str){
        case 'CarMake':
            $str2='Car Make';break;
        case 'CarsType':
            $str2='Cars Type';break;
        case 'CarsYear':
            $str2='Cars Year';break;
        default:
            $str2=$str;break;
    }
    return $str2;
}