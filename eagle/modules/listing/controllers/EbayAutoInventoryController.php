<?php

namespace eagle\modules\listing\controllers;

use Yii;
use eagle\modules\listing\models\EbayAutoInventory;
use eagle\modules\listing\models\EbayAutoInventorySearch;
use eagle\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\helpers\EbayAutoInventoryCtrlHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\listing\helpers\EbayCommonFrontHelper;

/**
 * EbayAutoInventoryController implements the CRUD actions for EbayAutoInventory model.
 */
class EbayAutoInventoryController extends \eagle\components\Controller
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

    /**
     * Lists all EbayAutoInventory models.
     * @return mixed
     */
    public $enableCsrfValidation = FALSE;
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
        $searchModel = new EbayAutoInventorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$_POST,$sellers);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'sellers'=>$sellers,
        ]);
    }

    /**
     * Displays a single EbayAutoInventory model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new EbayAutoInventory model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        //暂不提供批量创建
        \yii::info($_REQUEST,"file");
        if (\Yii::$app->request->isPost && isset($_REQUEST ['item_id'])) {//设置保存
            if (isset($_REQUEST ['varisation'])) {//保存设置
                $puid=\Yii::$app->user->identity->getParentUid();
                list($ret,$message)=EbayAutoInventoryCtrlHelper::creatRecord($_REQUEST,$puid);
                $result=ResultHelper::getResult(($ret?200:201), '1', $message);
                 \yii::info($result,"file");
                return $result;
            }else{//展示要设置的item
                $items=$this->findItem($_REQUEST ['item_id']);
                $variation=$this->findItemVariation($_REQUEST ['item_id']);
                return $this->renderAjax('_editCreate', [
                    'model' => $items,
                    'variation'=> $variation,
                ]);
            }
        }else{//显示在线item
            $puid=\Yii::$app->user->identity->getParentUid();
            $sellers=EbayCommonFrontHelper::activeUser($puid);
            list($models,$details,$pages)=EbayAutoInventoryCtrlHelper::getCreateItem($sellers,$_REQUEST);

            return $this->render('create', [
                'models' => $models,
                'details'=> $details,
                'pages'=>$pages,
                'sellers'=>$sellers,
            ]);
        }
    }
    /**
     * Updates an existing EbayAutoInventory model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate()
    {
        \yii::info($_POST,"file");
        $model = $this->findModel($_POST ['id']);
        if (\Yii::$app->request->isPost && isset($_POST ['inventory'])) {//修改设置库存
            $model->inventory=$_POST ['inventory'];
            $model->updated=time();
            $model->save(false);
            return $this->redirect(['index']);
        } else if(\Yii::$app->request->isPost && isset($_POST ['status'])){//修改状态
            list($errCode,$message)=$this->_updateStatus($model,$_POST ['status']);
            $tmp=ResultHelper::getResult($errCode, '1', $message);
            return $tmp;
        }else {//模态显示修改框
            return $this->renderAjax('update', [
                'model' => $model,
            ]);
        }
    }
    /**
     * Deletes an existing EbayAutoInventory model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete()
    {
        $model=$this->findModel($_POST['id']);
        $seller=$model->selleruserid;
        $model->delete();
        if (!EbayAutoInventoryCtrlHelper::_switchSaasStatus($seller)) {
            return ResultHelper::getResult(201, '1', "删除异常");
        }
        return ResultHelper::getResult(200, '1', "ok");
        // return $this->redirect(['index']);
    }

    /**
     * Finds the EbayAutoInventory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return EbayAutoInventory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EbayAutoInventory::findOne($id)) !== null) {
            return $model;
        } else {
            // \yii::info('The requested page does not exist '.$id,"file");
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findItem($itemid)
    {
        if (($model = EbayItem::findOne(['itemid'=>$itemid])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    protected function findItemVariation($itemid)
    {
        if (($model = EbayItemDetail::findOne(['itemid'=>$itemid])) !== null) {
            // $variation=unserialize($model->variation);
            // $variation=$model->variation;
            if (empty($model->variation)) {
                return $model->variation;
            }
            $variation=$this->_removeSavedVariation($itemid,$model->variation);
            return $variation;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    protected function _removeSavedVariation($itemid,$variation)
    {
        $invS=EbayAutoInventory::find()
                ->where(['itemid'=>$itemid])
                ->asArray()
                ->all();
        if (!isset($variation['Variation'][0])) {
            $variation['Variation']=array($variation['Variation']);
        }
        foreach ($variation['Variation'] as $key => $val) {
            // if (!isset($val['VariationSpecifics']['NameValueList'][0])){
            //     $val['VariationSpecifics']['NameValueList']=array($val['VariationSpecifics']['NameValueList']);
            // }
            $tmp=json_encode($val['VariationSpecifics']['NameValueList']);
            foreach ($invS as $ikey => $ival) {
                if (strcmp($tmp,$ival['var_specifics']) != 0) {//不相同记录下来
                    $copyVariation[]=$variation['Variation'][$key];
                    break;
                }
            }
        }
        if (isset($copyVariation)) {
            $variation['Variation']=$copyVariation;
        }
        return $variation;
    }
    protected function _updateStatus($model,$setSts){
        /**
         * 检查执行状态(执行中不能修改状态)
         */
        if (($model->status_process==EbayAutoInventory::CHECK_RUNNING)||($model->status_process==EbayAutoInventory::INV_RUNNING)) {//执行中不能修改状态
            $errCode=201;
            $message='FAILURE 正在运行,请稍后设置';
        }else{
        /**
         * 设置'暂停',多少都可以设置
         * 设置'开启',检查limit数量
         */
            if ($setSts==1) {
                $puid=\Yii::$app->user->identity->getParentUid();
                $ret=EbayAutoInventoryCtrlHelper::_limitCheckV2($model->selleruserid,$puid,1);
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
            EbayAutoInventoryCtrlHelper::_switchSaasStatus($model->selleruserid);
        }
        return [$errCode,$message];
    }
}
