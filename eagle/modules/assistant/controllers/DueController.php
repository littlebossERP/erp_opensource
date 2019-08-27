<?php namespace eagle\modules\assistant\controllers;

use yii\data\Pagination;
use yii\data\Sort;

use eagle\models\assistant\DpRule;
use eagle\models\assistant\DpEnable;
use eagle\modules\assistant\models\DpInfo;
use eagle\models\SysCountry;
use eagle\models\assistant\OmOrderMessageTemplate;

use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class DueController extends \eagle\components\Controller
{
	public $puid;

	public function __construct(){
		call_user_func_array('parent::__construct',func_get_args());
    	$this->puid = \Yii::$app->user->identity->getParentUid();

//        if($this->puid != 1505 && $this->puid != 602 && $this->puid != 552 && $this->puid != 1999 && $this->puid != 297 ){
//            $this->redirect('/');
//        }
	}

	public function actionList(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/due/list");
    	$info = DpInfo::find()
            ->where([
                'status'=>1,
                'contacted'=>0
            ]);
        // 条件查询
        if(isset($_GET['searchtype']) && $_GET['searchtype'] && $_GET['searchtype_val']){
            $info->andWhere(['like',$_GET['searchtype'],$_GET['searchtype_val']]);
        }
        // 时间查询
        if(isset($_GET['timetype']) && $_GET['timetype']){
            if($_GET['timetype_val1']){
                $info->andWhere(['>',$_GET['timetype'],$_GET['timetype_val1']]);
            }
            if($_GET['timetype_val2']){
                $info->andWhere(['<',$_GET['timetype'],$_GET['timetype_val2']]);
            }
        }
        // 店铺查询
        if(isset($_GET['shop_id']) && $_GET['shop_id']){
            $info->andWhere(['shop_id'=>$_GET['shop_id']]);
        }
        // 状态查询
        if(isset($_GET['due_status']) && $_GET['due_status']){
            $info->andWhere(['due_status'=>$_GET['due_status']]);
        }
        
        //只显示有权限的账号，lrq20170828
        $selleruserids = array();
        $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
        foreach($account_data as $key => $val){
        	$selleruserids[] = $key;
        }
        $info->andWhere(['shop_id' => $selleruserids]);

    	$page = new Pagination([
    	    'totalCount' => $info->count(), 
    	    'defaultPageSize' => 10,
    	    'pageSizeLimit'=>[5,10,20,50]
    	]);
    	$sort = new Sort([
            'attributes' => [
        		// 'pay_time','create_time',
                'order_time'
        	],
            'defaultOrder'=>[
                'order_time'=>SORT_DESC 
            ]
        ]);
        $shops = DpEnable::find()
            ->where(['dp_puid'=>$this->puid, 'dp_shop_id' => $selleruserids])
            ->all();
    	$data = $info
            ->offset($page->offset)
            ->limit($page->limit)
            ->orderBy('order_time DESC')
            ->all();

    	return $this->render('list',[
    		'dpInfo' => $data,
            'shops' => $shops,
    		'pages' => $page,
    		'sort' => $sort
    	]);
	}

    /**
     * 统计
     */
    public function actionDueinfo(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/due/dueinfo");
        $_shops = DpEnable::find()
            ->where(['dp_puid'=>$this->puid]);
        $rules = DpRule::find()
            ->where([
                'puid'=>$this->puid
            ]);
        $page = new Pagination([
            'totalCount' => $rules->count(), 
            'defaultPageSize' => 10,
            'pageSizeLimit'=>[5,10,20,50]
        ]);
        $rules
            ->offset($page->offset)
            ->limit($page->limit)
            ->orderBy('status DESC,is_active DESC');
        $shops = [];
        foreach($_shops->all() as $shop){
            $shops[$shop->dp_shop_id] = $shop;
        }

        $info2 = [];
        // 取所有规则
        foreach($rules->all() as $rule){
            $dpinfo = DpInfo::find()
                ->where([
                    'status'=>1,
                    'contacted'=>0
                ])
                ->andWhere(['rule_id'=>$rule->rule_id])
                ->distinct('source_id');
            $info2[$rule->rule_id]=[
                'message_content'=>$rule->message_content,
                'message_content2'=>$rule->message_content2,
                'message_content3'=>$rule->message_content3,
                'order_message'=>$rule->order_message,
                'timeout'=>$rule->timeout,
                'timeout2'=>$rule->timeout2,
                'timeout3'=>$rule->timeout3,
                'country'=>$rule->country,
                'expire_time'=>$rule->expire_time,
                'count'=>0,
                'successCount'=>0,
                'total'=>0,
                'money'=>'$'.($rule->min_money?$rule->min_money:'0').' - '.($rule->max_money?'$'.$rule->max_money:'不限'),
                'status'=>$rule->status?($rule->is_active==2?'启用':'停用'):'历史'
            ];

            foreach($dpinfo->each(1) as $dp){
                $result = $dp->cost;
                $info2[$rule->rule_id]['count']++;
                if($dp->due_status===2){
                    $info2[$rule->rule_id]['successCount']++;
                    // if(!isset($info2[$dp->shop_id])){
                    //     $info2[$dp->shop_id] = ['total'=>0];
                    // }
                    // $info2[$dp->shop_id]['total'] += $result ;
                }
            }

        }


        // echo(count($info2));die();


        $country = SysCountry::find()->all();
        $countries = [];
        foreach($country as $c){
            $countries[$c['country_code']] = $c['country_zh'];
        }

        return $this->render('info',[
            'info2' => $info2,
            'country' => $countries,
            'shops' => $shops,
            'pages' => $page
        ]);
    }

    /**
     * 统计
     */
    public function actionByshop(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/due/byshop");
        //只显示有权限的账号，lrq20170828
        $selleruserids = array();
        $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
        foreach($account_data as $key => $val){
        	$selleruserids[] = $key;
        }
        
        $_shops = DpEnable::find()
            ->where([
                'dp_puid'=>$this->puid, 
            	'dp_shop_id' => $selleruserids
            ]);
        $shops = [];
        foreach($_shops->all() as $shop){
            $shops[$shop->dp_shop_id] = $shop;
        }

        $data = DpInfo::find()->where(['contacted'=>0, 'shop_id' => $selleruserids])->groupBy('source_id');
        $info1 = [];
        foreach($data->each(1) as $item){
            if(!isset($info1[$item->shop_id])){
                $info1[$item->shop_id] = [
                    'platform'=>$shops[$item->shop_id]->platform,
                    'count'=>0,
                    'successCount'=>0,
                    'total'=>0
                ];
            }
            //统计店铺相关内容
            $result = $item->cost;
            $info1[$item->shop_id]['count']++;
            if($item->due_status===2){
                $info1[$item->shop_id]['successCount']++;
                $info1[$item->shop_id]['total'] += $result ;
            }
        }
        $page = new Pagination([
            'totalCount' => count($info1), 
            'defaultPageSize' => 10,
            'pageSizeLimit'=>[5,10,20,50]
        ]);

        $country = SysCountry::find()->all();
        $countries = [];
        foreach($country as $c){
            $countries[$c['country_code']] = $c['country_zh'];
        }

        return $this->render('byshop',[
            'info1' => $info1,
            'country' => $countries,
            'shops' => $shops,
            'pages' => $page
        ]);
    }


    /**
     * 统计 ajax
     * @return [type] [description]
     */
    public function actionStatistics(){
        $rules = DpInfo::find()
            ->where([
                'status'=>1
            ]);
        if($this->request('rule_id')){
            $rules->andWhere(['in','rule_id',$this->request('rule_id')]);
        }

        return $this->renderJson($rules->all());
    }

    private function request($key){
        return isset($_GET[$key])?$_GET[$key]:null;
    }

    public function actionShow()
    {
        $request = \Yii::$app->request;
        $template = $request->post('m_id', '');
        $tpl = OmOrderMessageTemplate::findOne($template);
        return $this->renderAjax('showtpl', [
            'tpl' => $tpl
        ]);
    }
}