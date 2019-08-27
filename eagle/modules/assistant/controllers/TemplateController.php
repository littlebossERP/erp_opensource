<?php namespace eagle\modules\assistant\controllers;

use yii\data\Pagination;
use yii\data\Sort;
use \Yii;
// models
use eagle\models\assistant\DpRule;
use eagle\models\assistant\DpEnable;
use eagle\models\assistant\DpTemplates;
use eagle\models\SysCountry;
use eagle\modules\assistant\models\SaasAliexpressUser;
use eagle\models\assistant\OmOrderMessageTemplate;



// helpers
use eagle\modules\assistant\helpers\HtmlHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class TemplateController extends \eagle\components\Controller
{
	public $puid;
    public $rule;

	public function __construct(){
		call_user_func_array('parent::__construct',func_get_args());
    	$this->puid = \Yii::$app->user->identity->getParentUid();
        $this->rule = DpRule::find()->where([
            'status'=>1,
            'puid'=>$this->puid
        ]);
//        if($this->puid != 1505 && $this->puid != 602 && $this->puid != 552 && $this->puid != 1999 && $this->puid != 297 ){
//            $this->redirect('/');
//        }
	}
    //模板列表
    public function actionList(){
        $omtpl = OmOrderMessageTemplate::find();
        $page = new Pagination([
            'totalCount' => $omtpl->where('status = 1')->count(),
            'defaultPageSize' => 10,
            'pageSizeLimit'=>[5,10,20,50]
        ]);
        return $this->render('list',[
            'pages' => $page,
            'omtpl' => $omtpl->where('status = 1')->offset($page->offset)->limit($page->limit)->all()
        ]);
    }
    //新增模板
    public function actionAdd(){
        $tpls = DpTemplates::find()->all();
        return $this->renderAjax('add',[
            'tpls' => $tpls,
        ]);
    }
    //修改模板
    public function actionEdit(){

        $tpls = DpTemplates::find()->all();

        $request = \Yii::$app->request;
        $id = $request->get('id','');
        $omtpl = OmOrderMessageTemplate::findOne($id);
        return $this->renderAjax('edit',[
            'tpls' => $tpls,
            'omtpl'=>$omtpl
        ]);
    }
    public function actionSave(){
        $request = \Yii::$app->request;
        $id = $request->post('tpl_id','');
        $t_name = $request->post('template_name','');
        $t_content = $request->post('message_content','');
        $is_active = $request->post('is_active',1);
        // 写入数据库
        if(empty($id)){
            $motpl = new OmOrderMessageTemplate();
            $motpl->template_name =$t_name ;
            $motpl->content = $t_content;
            $motpl->status = 1;
            $motpl->create_time = time();
            $motpl->update_time = time();
            $motpl->is_active = $is_active;
        }else{
            $motpl = OmOrderMessageTemplate::findOne($id);
            $motpl->template_name =$t_name ;
            $motpl->content = $t_content;
            $motpl->is_active =$is_active ;
            $motpl->update_time=time();
        }
       $result =  $motpl->save();
        return $this->renderJson([
            'error' =>$result?0:500
        ]);
    }

    // 翻译语言  Ajax
    public function actionTranslate(){
        $template = DpTemplates::findOne($_GET['id']);
        $lang = $_GET['language'];
        return $this->renderJson([
            'content'=>$template->$lang
        ]);
    }
    public function actionDeletetpl(){
        // AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/deletetpl");
        $rule1 = DpRule::find()->where(['message_content'=>$_POST['t_id']])->andWhere(['status'=>1])->all();
        $rule2 = DpRule::find()->where(['message_content2'=>$_POST['t_id']])->andWhere(['status'=>1])->all();
        $rule3 = DpRule::find()->where(['message_content3'=>$_POST['t_id']])->andWhere(['status'=>1])->all();
        $rule4 = DpRule::find()->where(['order_message'=>$_POST['t_id']])->andWhere(['status'=>1])->all();
        if(count($rule1) || count($rule2) || count($rule3) || count($rule4)){
            $result = ['error' =>500 ,'message'=>'请先删除绑定该模板的规则！'];
        }else{
            $template = OmOrderMessageTemplate::findOne($_POST['t_id']);
            $template->status = 0;
            if($template->save()){
                $result = ['error'=>0,'message'=>'ok'];
            }else{
                $result = ['error'=>500,'message'=>'fail'];
            }
        }
        return $this->renderJson($result);
    }
}
