<?php
namespace eagle\components;

use Yii;
use \yii\web\Response;
use \eagle\helpers\HtmlHelper;
use yii\web\BadRequestHttpException;
use eagle\modules\util\helpers\SysBaseInfoHelper;

class Controller extends \yii\web\Controller 
{

    public $viewPath;

	public function behaviors()
	{
		return [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
		];
	}	
    public function init()
    {
       parent::init();
    	if (\Yii::$app->subdb->isUserDbMoving()){
		    return \Yii::$app->getResponse()->redirect("/site/status");
		}
       
		if(!empty($_GET['debugToken'])){
		    $token = SysBaseInfoHelper::getDebugModeToken();
		    if($token == $_GET['debugToken']) SysBaseInfoHelper::$debugMode = true;
		}
		 
       //当isOnlyForOneApp 开关打开的时候，所有继承该controller的页面，都会使用改变layout文件，为main_for_tracker.php---目前还有tracker
   //    if (isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 
    //   &&  isset(\Yii::$app->params["currentEnv"]) && "production" == \Yii::$app->params["currentEnv"]) // dzt20150523
     //  	      $this->layout = '@app/views/layouts/main_for_tracker';
       
    }

    public function beforeAction($action){
    	if (parent::beforeAction($action)) {
    		//选择商品跳过子账号权限限制
    		if($action->id == 'select_product'){
    			return true;
    		}
    		$isMainAccount = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
    		if(!$isMainAccount){
    			$requiredAuths = \eagle\modules\permission\apihelpers\UserApiHelper::getCurrentPageRequiredAuth();
    			foreach ($requiredAuths as $requiredAuth){
    				if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission($requiredAuth))
    					throw new BadRequestHttpException(Yii::t('yii', '你没有权限访问此页面'));
    				
    			}
    		}
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /**
     * 以json格式输出，主要用于ajax响应
     */
    public function renderJson($data){
    	\Yii::$app->response->format = Response::FORMAT_JSON;
    	// return \yii\helpers\Json::encode($data);
    	return is_array($data)?$data:(isset($data->attributes)?$data->attributes:$data);
    }

    
    /**
     * 弹出层视图，用于加载ajax页面
     * @return [type] [description]
     */
    public function renderModal($viewPath,$param=[],$title=''){
    	return '<div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="gridSystemModalLabel">'.$title.'</h4>
		      </div>
		      <div class="modal-body">'.$this->renderAjax($viewPath,$param).'</div>';
    }
    
    /**
     * 返回json结果
     */
    public function jsonResult($error = 1,$data = [],$msg = '数据错误.'){
    	return $this->renderJson(['error'=>$error,'data'=>$data,'msg'=>$msg]);
    }

    public function setModalHeader($modalOption){
        \Yii::$app->response->headers->set('X-Target','_modal');
        if($modalOption){
            \Yii::$app->response->headers->set('X-Modal-Option',json_encode($modalOption));
        }
    }

    public function renderAuto($view, $params = []){
        if(is_array($view)){
            $params = $view; 
            $view = $params['url'];
        }
        if(isset($params['response'])){
            // 根据response出现结果
            switch($params['response']['type']){
                case '_modal':
                    $data = $params['response']['data'];
                    break;
                case 'fail':
                    $data = array_merge([
                        'message'=>$params['response']['message'],
                        'code'=>isset($params['response']['code'])?$params['response']['code']:500,
                    ],$params['response']['data']);
                    $view = '//alertMsg/fail';
                    break;
                case 'success':
                default:
                    return $this->renderJson($params);
                    break;
            }
            $option = $params['response']['_modal'];
            $this->setModalHeader($option);
            return $this->renderAuto($view,$data);
        }else{
            // 标准的加载视图
            $this->view = new \eagle\components\View;
            if($this->viewPath){
                $this->view->_path = $this->viewPath;
            }
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest'){
                return parent::renderAjax($view,$params);
            }else{
                return parent::render($view,$params);
            }
        }
    }


}