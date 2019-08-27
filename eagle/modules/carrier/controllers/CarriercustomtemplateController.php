<?php namespace eagle\modules\carrier\controllers;

use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\data\Sort;

use common\helpers\simple_html_dom;
use eagle\models\carrier\CrTemplate;
use eagle\models\CrCarrierTemplate;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SysShippingMethod;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\SysCarrier;
use eagle\models\sys\SysCountry;

class CarriercustomtemplateController extends \eagle\components\Controller
{

    // public function __construct($id,$module,$config=[]){
    //     parent::__construct($id,$module,$config);
    // }
    public $enableCsrfValidation = false;

        // 模版列表
    public function actionIndex(){
//     	return false;
//     	return "<a href='/configuration/carrierconfig/carrier-custom-label-list'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/index");
    			
        $templates = CrTemplate::find();
        $self_sort_params = $_GET;
        $self_sort_params['tab_active'] = 'self';
        $sort = new Sort([
            'attributes' => ['template_name','update_time','template_type'],
            'params'=>$self_sort_params
        ]);
        //根据当前选项卡页面 搜索对应的数据
        if(strlen(\Yii::$app->request->get('template_name')) && strlen(\Yii::$app->request->get('selftemplate')))
            $templates->andWhere(['like','template_name',\Yii::$app->request->get('template_name')]);


        $self_page_params = $_GET;
        $self_page_params['tab_active'] = 'self';
        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'pageSizeLimit'=>[15,20,50,100,200],
            'params' => $self_page_params
        ]);
        $pagination->totalCount = $templates_total = $templates->count();
        $data = $templates
                    ->offset($pagination->offset)
                    ->limit($pagination->limit)
                    ->orderBy($sort->orders)
                    ->all();
        

        //查询出系统模版
        $sys_templates = CrCarrierTemplate::find();
        $sys_templates->where(['is_use'=>1]);

        $sys_sort_params = $_GET;
        $sys_sort_params['tab_active'] = 'sys'; 
        $sys_sort = new Sort([
            'attributes' => ['template_name','create_time','template_type'],
            'params'=>$sys_sort_params
        ]);
        //如果有筛选条件
        $size = '';
        if($size = \Yii::$app->request->get('size')){
            switch($size){
                case 0:$height = 100;$width = 100;break;
                case 1:$height = 50;$width = 100;break;
                case 2:$height = 297;$width = 210;break;
                case 3:$height = 30;$width = 80;break;
                default:$height = 100;$width = 100;break;
            }
            $sys_templates->andWhere(['template_width'=>$width,'template_height'=>$height]);
        }
        //根据当前选项卡页面 搜索对应的数据
        if(strlen(\Yii::$app->request->get('template_name')) && strlen(\Yii::$app->request->get('selftemplate')))
            $sys_templates->andWhere(['like','template_name',\Yii::$app->request->get('template_name')]);

        $sys_page_params = $_GET;
        $sys_page_params['tab_active'] = 'sys';
        $sys_pagination = new Pagination([
            'defaultPageSize' => 20,
            'pageSizeLimit'=>[15,20,50,100,200],
            'params' => $sys_page_params,
        ]);
        $sys_pagination->totalCount = $sys_templates_total = $sys_templates->count();

        $sys_data = $sys_templates
            ->offset($sys_pagination->offset)
            ->limit($sys_pagination->limit)
            ->orderBy($sys_sort->orders)
            ->all();
        

        //页面上tab的激活标识
        $tab_active = \Yii::$app->request->get('tab_active');
        return $this->render('index',[
            'templates_total'=>$templates_total,
            'templates' => $data,
            'pages' => $pagination,
            'sort'=>$sort,
            'sys_templates_total'=>$sys_templates_total,
            'systemplates'=>$sys_data,
            'syspages' => $sys_pagination,
            'syssort'=>$sys_sort,
            'size'=>$size,
            'selftemplate'=>'',
            'tab_active'=>$tab_active
        ]);
    }

    /**
     * 编辑模版
     */
    public function actionEdit(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/edit");
        // 获取模版信息
        $id = isset($_GET['id'])?$_GET['id']:'';
        $uid = \Yii::$app->user->id;
        
        
        if(($id == -1) && ($uid == 1)){
        	$addressListType = ['地址单'=>'地址单','报关单'=>'报关单','配货单'=>'配货单'];
        	$sysShippingMethodArr = SysShippingMethod::find()->select(['id','carrier_code','shipping_method_name','template'])->where(['is_print'=>1])->orderBy('carrier_code')->asArray()->all();
        	
        	$sysCarrierArr = SysCarrier::find()->select(['carrier_code','carrier_name'])->asArray()->all();
        	$sysCarrierArr = Helper_Array::toHashmap($sysCarrierArr, 'carrier_code', 'carrier_name');
        	
        	$shippingMethodCarrierMap = array();
        	
        	foreach ($sysShippingMethodArr as $sysShippingMethod){
        		if(isset($sysCarrierArr[$sysShippingMethod['carrier_code']]))
        			$shippingMethodCarrierMap[$sysShippingMethod['id']] = $sysCarrierArr[$sysShippingMethod['carrier_code']].'-'.$sysShippingMethod['shipping_method_name'].(empty($sysShippingMethod['template']) ? '' : '-'.$sysShippingMethod['template']).':'.$sysShippingMethod['carrier_code'];
        	}
        	
//         	$order = current($data);
//         	reset($data);
        	
//         	$sysTemplateOne = CrCarrierTemplate::find()->where(['shipping_method_id'=>key($shippingMethodCarrierMap),'template_type'=>'地址单'])->asArray()->one();
        	
        	$sysTemplateArr = array();
        	$sysTemplate = CrCarrierTemplate::find()->select(['id','template_name','template_type','shipping_method_id','country_codes'])->asArray()->all();
        	
        	foreach ($sysTemplate as $tmpsysTemplateone){
        		$sysTemplateArr[$tmpsysTemplateone['id']] = $tmpsysTemplateone['template_name'].'-'.$tmpsysTemplateone['template_type'].
        			'-'.$tmpsysTemplateone['shipping_method_id'].'-'.$tmpsysTemplateone['country_codes'];
        	}
        	
        	
        	$template = new CrTemplate();
        	$template->template_id = '';
        	$template->template_name = '';	//current($shippingMethodCarrierMap)
        	$template->template_type = empty($sysTemplateOne['template_type']) ? '地址单' : $sysTemplateOne['template_type'];
        	$template->template_width = empty($sysTemplateOne['template_width']) ? '100' : $sysTemplateOne['template_width'];
        	$template->template_height = empty($sysTemplateOne['template_height']) ? '100' : $sysTemplateOne['template_height'];
        	$template->template_content = empty($sysTemplateOne['template_content']) ? '' : $sysTemplateOne['template_content'];
        	
//         	reset($shippingMethodCarrierMap);
        	
        	$userCrTemplate = CrTemplate::find()->asArray()->all();
        	
        	$userCrTemplateArr = Helper_Array::toHashmap($userCrTemplate, 'template_id', 'template_name');
        }else{
        	if(!$template = CrTemplate::findOne($id)){
        		$template = new CrTemplate();
        		$template->template_id = '';
        		$template->template_name = $_GET['template_name'];
        		$template->template_type = $_GET['template_type'];
        		$template->template_width = $_GET['width'];
        		$template->template_height = $_GET['height'];
        		$template->template_content = '';
        	}
        }
        
        
        $query = SysCountry::find();
        $regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
        $countrys =[];
        foreach ($regions as $region){
        	$arr['name']= $region['region'];
        	$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
        	$countrys[]= $arr;
        }
        
        $country_mapping = [];
        include __DIR__.'/../views/carriercustomtemplate/edit.php';
        // return $this->render('customprint');
    }

    /*
     * 复制系统模版到自定义
     */
    public function actionCopytemplate(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/copytemplate");
        //验证
        if(!\Yii::$app->request->getIsAjax())return false;
        $id = \Yii::$app->request->post('template_id');
        $name = \Yii::$app->request->post('template_name');
        if($id == null || $name == null)return false;
        //复制
        $systemplate = CrCarrierTemplate::findOne($id);
        if($systemplate === null) return false;
        $selftemplate = new CrTemplate;
        $selftemplate->template_name = $name;
        $selftemplate->template_content = $systemplate->template_content;
        $selftemplate->create_time = time();
        $selftemplate->template_height = $systemplate->template_height;
        $selftemplate->template_width = $systemplate->template_width;
        $selftemplate->template_type = $systemplate->template_type;
        try{
            if($selftemplate->save())
            return true;
        }catch(\Exception $e){return false;}
        return false;
    }

    public function actionGetmenu(){
        // 设置菜单项
        $allItems = require(__DIR__.'/../config/customtemplate_menu.php');
        $items = $allItems[$_GET['template_type']];
        return $this->renderJson($items);
    }

    /**
     * 保存模版
     */
    public function actionSavecustomprint(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/savecustomprint");
        if(!isset($_POST['id']) || !$template = CrTemplate::findOne($_POST['id'])){
            $template = new CrTemplate();
            $template->create_time = time();
            $template->template_type = $_POST['template_type'];
            $template->template_name = $_POST['name'];
            $template->template_width = $_POST['width'];
            $template->template_height = $_POST['height'];
        }
        $template->update_time = time();
        $template->template_name = $_POST['name'];
        $template->template_content = base64_decode($_POST['html']);
        $template->template_content_json = empty($_POST['template_content_json']) ? '' : $_POST['template_content_json'];
        $template->template_version = empty($_POST['template_version']) ? '0' : $_POST['template_version'];
        
        if($template->save()){
            $res = ['error'=>0,'data'=>$template];
        }else{
            $res = ['error'=>500,'message'=>'保存失败'];
        }
        return $this->renderJson($res);

    }

    /**
     * 预览 
     * 预览分三种情况：
     * 1 编辑中未保存时预览：POST提交template_content
     * 2 已保存未编辑预览（列表页）：GET提交template_id
     * 3 打印前预览：替换参数
     */
    public function actionPreview(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/preview");
        if(isset($_GET['template_id'])){
            //如果是系统模版，则从cr_sys_template
            if(\Yii::$app->request->get('is_sys'))
                $template = CrCarrierTemplate::findOne(\Yii::$app->request->get('template_id'));
            else
                $template = CrTemplate::findOne($_GET['template_id']);
        }else{
            $template = new CrTemplate();
            $template->template_width = $_POST['width'];
            $template->template_height = $_POST['height'];
            $template->template_content = base64_decode($_POST['template_content']);
        }
        include __DIR__."/../views/carriercustomtemplate/preview.php";
    }

    /**
     * 删除模版
     */
    public function actionDelete(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carriercustomtemplate/delete");
        $data = [];
        if(isset($_GET['template_id']) && $template = CrTemplate::findOne($_GET['template_id'])){
            if($template->delete()){
                $data = ['error'=>0,'message'=>'操作成功'];
            }else{
                $data = ['error'=>500,'message'=>'操作失败'];
            }
        }else{
            $data = ['error'=>400,'message'=>'非法操作'];
        }
        return $this->renderJson($data);
    }

    /*
     * 检查模版名称是否重复
     */
    public function actionChecktemplatename(){
        if(!\Yii::$app->request->getIsAjax())return false;
        $name = $_POST['templatename'];
        return CrTemplate::find()->where(['template_name'=>$name])->one()?'exists':false;
    }
    
    /**
     * 获取模板代码
     *
     * @param 
     * @return Array
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/01/09				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetCarrierTemplate(){
    	$data = array('error'=>1,'template'=>'');
    	
    	if(!isset($_POST['type'])){
    		return $this->renderJson($data);
    	}
    	
    	if($_POST['type'] == 'system'){
	    	if(!isset($_POST['shipping_methodid']) && !isset($_POST['lable_type'])){
	    		return $this->renderJson($data);
	    	}
	    	
	    	$shipping_methodid = $_POST['shipping_methodid'];
	    	$lable_type = $_POST['lable_type'];
	    	
	    	$sysTemplate = CrCarrierTemplate::find()->where(['shipping_method_id'=>$shipping_methodid,'template_type'=>$lable_type])->asArray()->one();
	    	
	    	if(count($sysTemplate) != 0){
	    		$data['error'] = 0;
	    		$data['template'] = base64_encode($sysTemplate['template_content']);
	    		$data['country_codes'] = $sysTemplate['country_codes'];
	    	}
    	}else if($_POST['type'] == 'system2'){
    		if(!isset($_POST['template_id'])){
    			return $this->renderJson($data);
    		}
    		
    		$template_id = $_POST['template_id'];
    		
    		$sysTemplate = CrCarrierTemplate::find()->where(['id'=>$template_id])->asArray()->one();
    		
    		if(count($sysTemplate) != 0){
    			$data['error'] = 0;
    			$data['template'] = base64_encode($sysTemplate['template_content']);
    			$data['country_codes'] = $sysTemplate['country_codes'];
    			$data['template_name'] = $sysTemplate['template_name'];
    			$data['carrier_code'] = $sysTemplate['carrier_code'];
    			$data['is_use'] = $sysTemplate['is_use'];
    		}
    	}
    	else{
    		if(!isset($_POST['user_cr_template_id'])){
    			return $this->renderJson($data);
    		}
    		
    		$id = $_POST['user_cr_template_id'];
    		$template = CrTemplate::findOne($id);
    		
    		if($template == null){
    			return $this->renderJson($data);
    		}
    		
    		$data['error'] = 0;
    		$data['template'] = base64_encode($template['template_content']);
    	}
    	
    	return $this->renderJson($data);
    }
    
    /**
     * 保存系统模板
     *
     * @param
     * @return Array
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/01/09				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveSysTemplate(){
    	if(empty($_POST['name'])){
    		$res = ['error'=>500,'message'=>'模板名称必填'];
    		return $this->renderJson($res);
    	}
    	
    	if((empty($_POST['shipping_method_id']) || empty($_POST['lable_type'])) && empty($_POST['sys_cr_template_id'])){
    		$res = ['error'=>500,'message'=>'请选择运输方式跟标签类型'];
    	
    		return $this->renderJson($res);
    	}
    	
    	if($_POST['shipping_method_id'] == -1){
    		$_POST['shipping_method_id'] = 0;
    	}
    	
    	if(!empty($_POST['sys_cr_template_id'])){
    		$sysTemplate = CrCarrierTemplate::find()->where(['id'=>$_POST['sys_cr_template_id']])->one();
    		
    		if($sysTemplate == null){
    			$res = ['error'=>500,'message'=>'保存失败2'];
    			return $this->renderJson($res);
    		}
    	}else{
    		$sysTemplate = CrCarrierTemplate::find()->where(['carrier_code'=>$_POST['carrier_code_sys'],'shipping_method_id'=>$_POST['shipping_method_id'],'template_type'=>$_POST['lable_type'],'country_codes'=>$_POST['country_sys']])->one();
    		
    		if($sysTemplate == null){
	    		$sysTemplate = new CrCarrierTemplate();
	    		$sysTemplate->create_time = time();
	    		$sysTemplate->shipping_method_id = $_POST['shipping_method_id'];
	    		$sysTemplate->template_type = $_POST['lable_type'];
	    		$sysTemplate->carrier_code = $_POST['carrier_code_sys'];
    		}
    	}
    	
    	$countryArr = explode(',', $_POST['country_sys']);
    	
//     	$sysTemplateFind = CrCarrierTemplate::find()->where(['id'=>$sysTemplate->id,'shipping_method_id'=>$sysTemplate->shipping_method_id,'template_type'=>$sysTemplate->template_type]);
    	
    	if($sysTemplate->shipping_method_id != 0){
	    	if ($sysTemplate->isNewRecord){
	    		$sysTemplateFind = CrCarrierTemplate::find()->where('shipping_method_id=:shipping_method_id and template_type=:template_type and carrier_code=:carrier_code',
	    				[':shipping_method_id'=>$sysTemplate->shipping_method_id,':template_type'=>$sysTemplate->template_type,':carrier_code'=>$_POST['carrier_code_sys']]);
	    	}else{
	    		$sysTemplateFind = CrCarrierTemplate::find()->where('shipping_method_id=:shipping_method_id and template_type=:template_type and id<>:id and carrier_code=:carrier_code',
	    				[':shipping_method_id'=>$sysTemplate->shipping_method_id,':template_type'=>$sysTemplate->template_type,':id'=>$sysTemplate->id,':carrier_code'=>$_POST['carrier_code_sys']]);
	    	}
	    	
	    	if(is_array($countryArr)){
	    		$tmpSql = '';
	    		foreach ($countryArr as $countryKey => $countryOne){
	    			if($countryKey == 0)
	    				$tmpSql = "country_codes like '%".$countryOne."%'";
	    			else
	    				$tmpSql .= " or country_codes like '%".$countryOne."%'";
	    		}
	    		 
	    		$sysTemplateFind->andWhere($tmpSql);
	    	}
	    	
	    	$tmpsysTemplateCount = $sysTemplateFind->count();
	    	 
	    	if($tmpsysTemplateCount > 0){
	    		$res = ['error'=>500,'message'=>'该标签的国家已存在其它标签格式，保存失败'];
	    	
	    		return $this->renderJson($res);
	    	}
    	}
    	
    	if(empty($_POST['sys_cr_template_open_close'])){
    		$_POST['sys_cr_template_open_close'] = 0;
    	}
    	
    	$sysTemplate->template_height = $_POST['height'];
    	$sysTemplate->template_width = $_POST['width'];
    	$sysTemplate->template_name = $_POST['name'];
    	$sysTemplate->template_content = base64_decode($_POST['html']);
    	$sysTemplate->country_codes = $_POST['country_sys'];
    	$sysTemplate->is_use = $_POST['sys_cr_template_open_close'];
    	 
    	if($sysTemplate->save(false)){
    		$res = ['error'=>0,'data'=>$sysTemplate];
    	}else{
    		$res = ['error'=>500,'message'=>'保存失败'];
    	}
    	return $this->renderJson($res);
    	
    	
//     	if($sysTemplate == null){
//     		$sysTemplate = new CrCarrierTemplate();
//     		$sysTemplate->create_time = time();
//     		$sysTemplate->shipping_method_id = $_POST['shipping_method_id'];
//     		$sysTemplate->template_type = $_POST['lable_type'];
//     	}
    	
//     	$sysTemplate->template_height = $_POST['height'];
//     	$sysTemplate->template_width = $_POST['width'];
//     	$sysTemplate->template_name = $_POST['name'];
//     	$sysTemplate->template_content = base64_decode($_POST['html']);
//     	$sysTemplate->country_codes = $_POST['country_sys'];
//     	$sysTemplate->is_use = 1;
    	
//     	if($sysTemplate->save(false)){
//     		$res = ['error'=>0,'data'=>$sysTemplate];
//     	}else{
//     		$res = ['error'=>500,'message'=>'保存失败'];
//     	}
//     	return $this->renderJson($res);
    }
}


