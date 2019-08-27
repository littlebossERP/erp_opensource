<?php
namespace eagle\modules\order\controllers;

use yii;
use eagle\modules\order\models\EbayFeedbackTemplate;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
/**
 * 好评列表的操作处理
 * @author witsionjs
 *
 */
class CustomController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * 好评范本的列表 @author fanjs
	 * @return Ambigous <string, string>
	 */
    public function actionFeedbackTemplateList()
    {
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/feedback/list");
    	$list = EbayFeedbackTemplate::find()->all();
        return $this->render('feedback-template-list',['lists'=>$list]);
    }
    
    /**
     * 修改/创建好评范本
     */
    public function actionCreate(){
    	if(\Yii::$app->request->isPost){
    		if (isset($_POST['templateid'])){
    			$template = EbayFeedbackTemplate::findOne($_POST['templateid']);
    		}else{
    			$template = new EbayFeedbackTemplate();
    		}
    		try {
    			$template->template_type = $_POST['feedbacktype'];
    			$template->template = $_POST['feedbackval'];
    			if ($template->isNewRecord){
    				$template->create_time = time();
    			}
    			$template->update_time = time();
    			$template->save();
    			return $this->actionFeedbackTemplateList();
    		}catch (\Exception $e){
    			print_r($e->getMessage());
    		}
    	}
    	if(isset($_GET['id'])&&$_GET['id']>0){
    		$template = EbayFeedbackTemplate::findOne($_GET['id']);
    	}else{
    		$template = new EbayFeedbackTemplate();
    	}
    	return $this->renderPartial('create',['template'=>$template]);
    }
    
    /**
     * 删除好评范本
     */
    function actionDelete(){
    	if(\Yii::$app->request->isPost){
    		try {
    			EbayFeedbackTemplate::deleteAll('id = '.$_POST['id']);
    			return 'success';
    		}catch (Exception $e){
    			return print_r($e->getMessage());
    		}
    	}
    }

}
