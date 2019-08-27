<?php

namespace eagle\modules\listing\controllers;

use Yii;
use eagle\modules\listing\models\EbayAutoTimerListing;
use eagle\modules\listing\models\EbayAutoTimerListingSearch;
use eagle\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\listing\helpers\EbayCommonFrontHelper;
use eagle\modules\listing\helpers\EbayAutoTimerListingCtrlHelper;
use eagle\modules\listing\models\EbayMuban;
use eagle\modules\listing\models\EbayMubanDetail;
use eagle\modules\util\helpers\ResultHelper;
/**
 * EbayAutoTimerListingController implements the CRUD actions for EbayAutoTimerListing model.
 */
class EbayAutoTimerListingController extends \eagle\components\Controller
{
    // public function behaviors()
    // {
    //     return [
    //         'verbs' => [
    //             'class' => VerbFilter::className(),
    //             'actions' => [
    //                 'delete' => ['post'],
    //             ],
    //         ],
    //     ];
    // }
    public $enableCsrfValidation = FALSE;

    /**
     * Lists all EbayAutoTimerListing models.
     * @return mixed
     */
    public function actionIndex()
    {
        /*
         * No.1-获取有效用户
         */
        $puid=\Yii::$app->user->identity->getParentUid();
        $sellers=EbayCommonFrontHelper::activeUser($puid);
        /*
         * No.2-筛选数据
         */
        $searchModel = new EbayAutoTimerListingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$sellers);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single EbayAutoTimerListing model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new EbayAutoTimerListing model.
     * 包含3个操作:
     * 1、展示所有未添加定时的范本(支持搜索);
     * 2、展示设置页面;
     * 3、检查并保存;
     */
    public function actionCreate()
    {
        \yii::info($_REQUEST,"file");
        $puid=\Yii::$app->user->identity->getParentUid();
        if (!(isset($_REQUEST ['type']))) {//step-1-展示所有未添加定时的范本;
            $sellers=EbayCommonFrontHelper::activeUser($puid);
            list($models,$pages)=EbayAutoTimerListingCtrlHelper::getDraftItem($sellers,$_REQUEST);
            return $this->render('create', [
                'sellers'=>$sellers,
                'models' => $models,
                'pages' => $pages,
            ]);
        }else{
            if ($_REQUEST ['type']=='setting') {//step-2-展示设置页面;
                // $variation=$this->findDraftVariation($_REQUEST ['draft_id']);
                $draft_model = $this->findDraft($_POST ['draft_id']);
                $model = new EbayAutoTimerListing();
                \yii::info(print_r($model->status),"file");
                return $this->renderAjax('_editCreate', [
                    'model' => $model,
                    'draft_model'=> $draft_model,
                ]);
            }else if ($_REQUEST ['type']=='save'){//step-3-检查并保存;
                try{
                    /**
                     * [params检测]
                     */
                    list($ret,$mesg)=EbayCommonFrontHelper::paramsCheck($_REQUEST,'timer_listing',$_REQUEST['EbayAutoTimerListing']['selleruserid'],$puid,1);
                    if (!$ret) {
                       return ResultHelper::getResult(201, '1', $mesg);
                    }
                    /**
                     * [刊登检测]
                     */
                    $result=EbayAutoTimerListingCtrlHelper::varifyDraftItem($_REQUEST['EbayAutoTimerListing']['draft_id']);
                    if ($result['Ack']=='Failure') {
                        return ResultHelper::getResult(202, '1', $result['show']);
                    }
                    /**
                     * [保存]
                     */
                    EbayAutoTimerListingCtrlHelper::createRecord($_REQUEST,$puid,$result);

                    return ResultHelper::getResult(200, '1', '保存成功 !');
                }catch (Exception $e){
                    \yii::info($e->msg(),"file");
                    return ResultHelper::getResult(201, '1', $e->msg());
                }

            }

        }
    }

    /**
     * Updates an existing EbayAutoTimerListing model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * 完成3个操作:
     * 1、展示窗口;
     * 2、修改定时时间;
     * 3、切换状态;
     */
    public function actionUpdate()
    {
        \yii::info($_POST,"file");
        $model = $this->findModel($_REQUEST['id']);
        if ($_REQUEST ['type']=='setting') {//模态显示修改框
            return $this->renderAjax('update', [
                'model' => $model,
            ]);
        }else if ($_REQUEST ['type']=='save') {//修改定时时间
            $puid=\Yii::$app->user->identity->getParentUid();

            list($ret,$mesg)=EbayAutoTimerListingCtrlHelper::updateRecord($_REQUEST,$puid,$model);

            return ResultHelper::getResult($ret, '1', $mesg);
            // list($ret,$mesg)=EbayCommonFrontHelper::paramsCheck($_REQUEST,'timer_listing',$_REQUEST['EbayAutoTimerListing']['selleruserid'],$puid,1);
            // if (!$ret) {
            //    return ResultHelper::getResult(201, '1', $mesg);
            // }
            // $model->status=$_REQUEST['EbayAutoTimerListing']['status'];
            // $model->set_gmt=$_REQUEST['EbayAutoTimerListing']['set_gmt'];
            // $model->set_date=$_REQUEST['EbayAutoTimerListing']['set_date'];
            // $model->set_hour=$_REQUEST['EbayAutoTimerListing']['set_hour'];
            // $model->set_min=$_REQUEST['EbayAutoTimerListing']['set_min'];
            // $model->updated=time();
            // $model->save(false);
            // EbayCommonFrontHelper::_switchSaasStatus($_REQUEST['EbayAutoTimerListing']['selleruserid'],"timer_listing");
            // return ResultHelper::getResult(200, '1', "修改成功 !");
        }

    }

    /**
     * Deletes an existing EbayAutoTimerListing model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete()
    {
        $model=$this->findModel($_POST['id']);
        $seller=$model->selleruserid;
        $model->delete();
        if (!EbayCommonFrontHelper::_switchSaasStatus($seller,"timer_listing")) {
           return ResultHelper::getResult(201, '1', "删除异常");
        }
        return ResultHelper::getResult(200, '1', "ok");
    }

    /**
     * Finds the EbayAutoTimerListing model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EbayAutoTimerListing the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EbayAutoTimerListing::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findDraft($draftid)
    {
        if (($model = EbayMuban::findOne(['mubanid'=>$draftid])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findDraftVariation($draftid)
    {
        if (($model = EbayMubanDetail::findOne(['mubanid'=>$draftid])) !== null) {
            return $model->variation;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


    protected function _updateStatus($model,$setSts){
        /**
         * 检查执行状态(执行中不能修改状态)
         */
        if (($model->status_process==EbayAutoTimerListing::CHECK_RUNNING)||($model->status_process==EbayAutoTimerListing::INV_RUNNING)) {//执行中不能修改状态
            $errCode=201;
            $message='FAILURE 正在运行,请稍后设置';
        }else{
        /**
         * 设置'暂停',多少都可以设置
         * 设置'开启',检查limit数量
         */
            if ($setSts==1) {
                $puid=\Yii::$app->user->identity->getParentUid();
                $ret=EbayCommonFrontHelper::_limitCheck($model->selleruserid,$puid,1);
                if (!$ret) {
                    $errCode=201;
                    $message='FAILURE 超出限制数量';
                    return [$errCode,$message];
                }
            }
            $errCode=200;
            $message='OK';
            $model->status=$setSts;
            $model->updated=time();
            $model->save(false);
            EbayCommonFrontHelper::_switchSaasStatus($model->selleruserid,"timer_listing");
        }
        return [$errCode,$message];
    }
}
