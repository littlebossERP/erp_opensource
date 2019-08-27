<?php
namespace eagle\modules\ebay\apihelpers;

use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use common\api\ebayinterface\base;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\getuser;
use common\api\ebayinterface\getstore;
use common\api\ebayinterface\getebaydetails;
use common\api\ebayinterface\getcategories;
use common\api\ebayinterface\getcategoryfeatures;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbayCategory;
use eagle\models\EbaySite;
use eagle\models\EbaySpecific;
use eagle\models\EbaySystemAutosyncStatus;
use common\api\ebayinterface\getcategoryspecifics;
/**
 * 获取ebay通用信息
 */
class EbayCommonInfoApiHelper{
    public static $cronJobId=0;
    private static $UserInfoVersion = null;
    private static $StoreInfoVersion = null;
    public static function getCronJobId() {
        return self::$cronJobId;
    }
    public static function setCronJobId($cronJobId) {
        self::$cronJobId = $cronJobId;
    }
    private static function checkNeedExitNot($jobType){
        if ($jobType=='user') {
            $ebayCommonInfoVersionFromConfig = ConfigHelper::getGlobalConfig("Order/ebayCommonUserInfoVersion",'NO_CACHE');
        }else if($jobType=='store'){
            $ebayCommonInfoVersionFromConfig = ConfigHelper::getGlobalConfig("Order/ebayCommonStoreInfoVersion",'NO_CACHE');
        }else{
            echo "EbayCommonInfoApiHelper error jobType :".$jobType."\n";
            return false;
        }
        if (empty($ebayCommonInfoVersionFromConfig))  {
            //数据表没有定义该字段，不退出。
            return false;
        }
        switch ($jobType) {
            case 'user':
                if (self::$UserInfoVersion===null){
                    self::$UserInfoVersion = $ebayCommonInfoVersionFromConfig;
                }
                $tmpVersion=self::$UserInfoVersion;
                break;
            case 'store':
                if (self::$StoreInfoVersion===null){
                    self::$StoreInfoVersion = $ebayCommonInfoVersionFromConfig;
                }
                $tmpVersion=self::$StoreInfoVersion;
                break;
        }
        if (empty($tmpVersion))  {
            return false;
        }
        echo $tmpVersion."tmpVersion\n";
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if ($tmpVersion <> $ebayCommonInfoVersionFromConfig){
            echo $jobType." Version new $ebayCommonInfoVersionFromConfig , this job ver ".$tmpVersion." exits \n";
            return true;
        }
        return false;
    }
    /**
     * [getAllUserinfo 遍历getuser，保存信息]
     * @Author   willage
     * @DateTime 2016-11-01T10:54:20+0800
     * @return   [type]                   [description]
     */
    public static function getAllUserinfo()
    {
        //0. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $ret=self::checkNeedExitNot("user");
        if ($ret===true) exit;
        //No.1-get record获取记录
        $EUs = SaasEbayUser::find()->where ( ['item_status'=>1] )->andWhere('expiration_time>='.time())->all();
        $bgJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
        if (count ( $EUs ) == 0) {
            echo $bgJobId."get no Suitable ebay user"."\n";
            return false;
        }
        echo $bgJobId." count total ".count ( $EUs )."\n";
        foreach ( $EUs as $eu ) {//遍历记录
             
            echo $bgJobId." uid ".$eu->uid."\n";
            //No.3-getuser
            $getuserApi = new getuser();
            $getuserApi->eBayAuthToken=$eu->token;
            try {
                $ret=$getuserApi->api();
            }catch (EbayInterfaceException_Connection_Timeout $ex){
                throw new EbayInterfaceException_Connection_Timeout('连接eBay服务器超时');
            }
            //No.4-保存
            if ($ret['Ack']==='Success') {
                $getuserApi->save($eu->selleruserid,$ret["User"]);
                echo $bgJobId." save ok"."\n";
            }else{
            //No.5-出错处理
                echo $bgJobId." getuser error"."\n";
                continue;
            }
        }//foreach
        return true;
    }

    /**
     * [getAllUserinfo description]
     * @Author   willage
     * @DateTime 2016-11-11T09:40:38+0800
     * @return   [type]                   [description]
     */
    public static function getAllStoreInfo()
    {
        //0. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $ret=self::checkNeedExitNot("store");
        if ($ret===true) exit;
        //No.1-get record获取记录
        $EUs = SaasEbayUser::find()->where ( ['item_status'=>1] )->andWhere('expiration_time>='.time())->all();
        $bgJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
        if (count ( $EUs ) == 0) {
            return false;
        }
        echo $bgJobId." count total ".count ( $EUs )."\n";
        foreach ( $EUs as $eu ) {//遍历记录
            echo "uid ".$eu->uid."\n";
            //No.3-getstore
            $getstoreApi = new getstore();
            $getstoreApi->eBayAuthToken=$eu->token;
            try {
                $ret=$getstoreApi->api($eu->selleruserid);
            }catch (EbayInterfaceException_Connection_Timeout $ex){
                throw new EbayInterfaceException_Connection_Timeout('连接eBay服务器超时');
            }
            //No.4-保存
            if ($ret['Ack']==='Success') {
                $getstoreApi->save($eu->selleruserid,$ret["Store"]);
                echo $bgJobId." save ok"."\n";
            }else{
            //No.5-出错处理
                echo $bgJobId." getstore error"."\n";
            }
            continue;
        }//foreach
        return true;
    }

    /**
     * [AutoSyncEbayBase 同步eBay的Base信息]
     * 说明：必须保证所有站点的其他job不在运行，并且5分钟内不会运行
     * @Author willage
     * @Editor willage 2016-11-15T15:35:48+0800
     */
    static function AutoSyncEbayBase(){
        $referTime=time()+5*60;
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        $sitesEn=array_column($sites, 'en');
        //保证其他job不在运行，并且5分钟内不准备执行
        $eSysSyncs=EbaySystemAutosyncStatus::find()
            ->where(['site' =>$sitesEn])
            ->andwhere("base_process<>1 AND feature_process<>1 AND specific_process<>1")
            ->andwhere("base_next_execute_time >".$referTime." AND feature_next_execute_time>".$referTime." AND specific_next_execute_time>".$referTime)
            ->asArray()
            ->all();
        echo "base>>> all:".count($sitesEn)."\n";
        echo "base>>> find:".count($eSysSyncs)."\n";
        if (count($sitesEn) != count($eSysSyncs)) {//保证所有站点其他job都不运行
            echo "base>>> do not run the command!\n";
            return;
        }
        echo "base>>> start sync ebay base\n";
        $api=new getebaydetails();
        set_time_limit(0);
        $api->syncEbayBase();
    }
    /**
     * [AutoSyncEbaySite 同步eBay的Site detail信息]
     * @Author fanjs
     * @Editor willage 2016-11-15T15:35:48+0800
     */
    static function AutoSyncEbaySiteDetails(){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        foreach ($sites as $site){
            echo "details>>> start site:".$site['en']."\n";
            $api=new getebaydetails();
            set_time_limit(0);
            $api->syncEbaySiteDetail($site['no']);
            // $api->syncEbaySiteDetail(0);//$site['no']
            echo "details>>> site:".$site['en']."updated!\n";
        }
    }
    /**
     * [AutoSyncEbayCategory 同步eBay的刊登类目信息]
     * @Author fanjs
     * @Editor willage 2016-11-15T15:37:46+0800
     */
    static function AutoSyncEbayCategory(){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        //No.1-遍历站点获取目录
        foreach ($sites as $site){
            echo "Category>>> start sync category,site:".$site['en']."\n";
            //No.2-同步并保存
            $api=new getcategories();
            set_time_limit(0);
            $api->syncEbayCategory($site['no']);
            // $api->syncEbayCategory(0);
            echo "Category>>> category,site:".$site['en']." updated!\n";
        }
    }
    /**
     * [AutoSyncEbaySiteBaseInfo description]
     * @Author willage 2017-02-15T11:53:57+0800
     * @Editor willage 2017-02-15T11:53:57+0800
     */
    static function AutoSyncEbaySiteBaseInfo(){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        foreach ($sites as $site){
            //No.2-抢记录(为多进程服务,每条进程执行一个站点)
            $connection = \Yii::$app->db;
            $runSql="UPDATE `ebay_system_autosync_status` SET base_process=1 WHERE siteid =".$site['no']." AND base_process<>1 AND feature_process<>1 AND specific_process<>1 AND base_next_execute_time <".time();
            $command = $connection->createCommand($runSql) ;
            $affectRows = $command->execute();
            if ($affectRows <= 0)   continue; //抢不到

            $eSysSync=EbaySystemAutosyncStatus::find()
                ->where('siteid = :s',[':s'=>$site['no']])
                ->one();
            //No.3-同步eBay的Site detail信息
            echo "details>>> start site:".$site['en']."\n";
            $api=new getebaydetails();
            set_time_limit(0);
            $api->syncEbaySiteDetail($site['no']);
            echo "details>>> site:".$site['en']."updated!\n";

            //No.4-同步eBay的刊登类目信息
            echo "Category>>> start sync category,site:".$site['en']."\n";
            $api=new getcategories();
            set_time_limit(0);
            $api->syncEbayCategory($site['no']);
            echo "Category>>> category,site:".$site['en']." updated!\n";

            //N0.5-process状态设置
            $eSysSync->base_process=2;
            $eSysSync->base_next_execute_time=time()+7*24*3600;
            $eSysSync->save(false);
        }
    }
    /**
     * [AutoSyncEbayFeature 同步eBay的feature信息]
     * @Author fanjs
     * @Editor willage 2016-11-15T15:40:20+0800
     * 注意:
     * 1、不要一个个叶目录传输获取,每个site有超过2W条叶目录,1秒一个,大概要6小时
     * 2、但是,按site获取,只要几分钟
     */
    static function AutoSyncEbayFeature(){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        $ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        foreach ($sites as $site){//遍历站点
            //No.1-抢记录(为多进程服务,每条进程执行一个站点)
            $connection = \Yii::$app->db;
            $runSql="UPDATE `ebay_system_autosync_status` SET feature_process=1 WHERE siteid =".$site['no']." AND base_process<>1 AND feature_process<>1 AND specific_process<>1 AND feature_next_execute_time <".time();
            $command = $connection->createCommand($runSql) ;
            $affectRows = $command->execute();
            if ($affectRows <= 0)   continue; //抢不到

            $eSysSync=EbaySystemAutosyncStatus::find()
                ->where('siteid = :s',[':s'=>$site['no']])
                ->one();
            // $eSite=EbaySite::find()
            //     ->where('siteid = :s',[':s'=>$site['no']])
            //     ->orderBy('feature_version DESC')
            //     ->one();
            echo "feature>>> feature updating, site:".$site['en']." !\n";
            //No.2-获取version
            $api=new getcategoryfeatures();
            $api->resetConfig($ue->DevAcccountID);
            $api->eBayAuthToken=$ue->token;
            $api->siteID=$site['no'];
            $r=$api->apiVersion();
            $version=$r['CategoryVersion'];
            //No.3-同步sitedefault和featureDefinitions
            echo "feature>>> start check default feature version, site:".$site['en']." !\n";
            echo "feature>>> local ver: ".$eSysSync->feature_version."\n";
            echo "feature>>> online ver: ".$version."\n";
            if (!empty($eSysSync->feature_version) && ($eSysSync->feature_version <=$version)) {//版本检查
                echo "feature>>> no updates default feature version\n";
            }else{
                try {
                    $api->syncSiteDefaults();//同步
                    echo "feature>>> start update specifics version site:".$site['en']."\n";
                    $eSysSync->feature_version=$version;//保存feature版本
                    $eSysSync->save(false);
                }catch ( Exception $ex ) {
                    echo 'Feature Defaults Error Message:' . $ex->getMessage () . "\n";
                }

            }

            //No.4-同步categorys差异化feature
            echo "feature>>> start check category feature version!\n";//版本校验,如果版本没有做更新则不更新
            $categorys=EbayCategory::find()
                ->select("categoryid")
                ->where(["leaf"=>1])
                ->andwhere(["siteid"=>$site['no']] )
                ->andwhere("feature_version <".$version." or feature_version IS NULL")
                ->asArray()
                ->all();
            $cidArry=array_column($categorys,'categoryid');
            echo "feature>>> leaf categorys count:".count($categorys)."\n";
            foreach ($cidArry as $cid){//遍历leaf目录
                echo "feature>>> start: site:".$site['en'].",categoryid:".$cid."\n";
                set_time_limit(0);
                try {
                    $api->syncCategoryFeatures($cid);
                }catch ( Exception $ex ) {
                    echo 'Feature sync Error categoryid:'.$cid.' Message:'.$ex->getMessage ()."\n";
                    continue;
                }
                $cs=EbayCategory::find()
                    ->where(["categoryid"=>$cid])
                    ->andwhere(["leaf"=>1])
                    ->andwhere(["siteid"=>$site['no']] )
                    ->one();
                $cs->feature_version=$version;
                $cs->save(false);
                echo "feature>>> site:".$site['en'].",categoryid:".$cid." updated!\n";
            }
            echo "feature>>> feature ending, site:".$site['en']." !\n";
            //No.5-process状态清0
            $eSysSync=EbaySystemAutosyncStatus::find()
                ->where('siteid = :s',[':s'=>$site['no']])
                ->one();
            $eSysSync->feature_next_execute_time=time()+24*3600;
            $eSysSync->feature_process=2;
            $eSysSync->save(false);

        }

    }

    static function AutoPickMissingEbayFeature(){
        //No.1-提取当前表的遗漏记录
        $ecfArr=EbayCategoryFearture::findBySql('select * from ebay_category where leaf=1 AND siteid='.$site['no'])->all();
        if (empty($ecfArr)) {
            return;
        }
        //No.2-遍历拉取
        foreach ($ecfArr as $key => $ecfVal) {
            //删除
            //批量插入
        }
    }
    /**
     * [AutoSyncEbaySpecific 同步eBay的Specifics信息]
     * @Author fanjs
     * @Editor willage 2016-11-15T15:38:22+0800
     */
    // static function AutoSyncEbaySpecific(){
    //  $sites=Helper_Siteinfo::getEbaySiteIdList();
    //  $ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
    //  // foreach ($sites as $site){
    //      // echo "start specific site:".$site['en']."------>\n";
    //      //No.1-get specifics

    //              $gcs=new getcategoryspecifics();
    //              $gcs->eBayAuthToken=$ue->token;
    //              $gcs->siteID=0;
    //              // $gcs->siteID=$site['no'];
    //              $gcs->syncLeafSpecific();
    //      // echo "specific site:".$site['en']." updated!------>\n";
    //  // }
    // }

    /**
     * [AutoSyncEbaySpecific 同步eBay的Specifics信息,目前使用遍历方式]
     * @Author willage 2016-11-23T09:38:15+0800
     * @Editor willage 2016-11-23T09:38:15+0800
     */
    static function AutoSyncEbaySpecific(){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        $ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();

        foreach ($sites as $site){//遍历站点
            //No.1-抢记录(为多进程服务,每条进程执行一个站点)
            $connection = \Yii::$app->db;
            $runSql="UPDATE `ebay_system_autosync_status` SET specific_process=1 WHERE siteid =".$site['no']." AND base_process<>1 AND feature_process<>1 AND specific_process<>1 AND specific_next_execute_time <".time();
            $command = $connection->createCommand($runSql) ;
            $affectRows = $command->execute();
            if ($affectRows <= 0)   continue; //抢不到

            $eSysSync=EbaySystemAutosyncStatus::find()
                ->where('siteid = :s',[':s'=>$site['no']])
                ->one();
            //No.2-检查版本
            $gcs=new getcategoryspecifics();
            $gcs->resetConfig($ue->DevAcccountID);
            $gcs->eBayAuthToken=$ue->token;
            $gcs->siteID=$site['no'];
            $result=$gcs->api();
            if ( ($result['Ack']=='Failure') ||($result['Ack']!=='Success') ){//判断是否成功
                echo "get fails: ".json_encode($result)."\n";
                $eSysSync->specific_process=3;
                $eSysSync->specific_next_execute_time=time();
                $eSysSync->save(false);
                continue;
            }
            echo "Specific>>> start check specifics version site:".$site['en']."\n";
            $fileReferenceId = $result['FileReferenceID'];
            $taskReferenceId = $result['TaskReferenceID'];
            if (strlen($fileReferenceId)==0&&strlen($taskReferenceId)==0){//判断文件ID长度
                echo "Specific>>> no file-task ID\n";
                echo print_r($result,false)."\n";
                echo "Specific>>> TaskReferenceID ID".$taskReferenceId."\n";
                $eSysSync->specific_process=0;
                $eSysSync->specific_next_execute_time=time();
                $eSysSync->save(false);
                continue;
            }
            // $s = EbaySite::findOne('siteid = '.$site['no']);
            if ($fileReferenceId.'-'.$taskReferenceId == $eSysSync->specifics_jobid){//判断是否最新
                echo "Specific>>> ".$eSysSync->specifics_jobid."\n";
                echo "Specific>>> ".$fileReferenceId.'-'.$taskReferenceId."\n";
                echo "Specific>>> no updates\n";
                $eSysSync->specific_process=2;
                $eSysSync->specific_next_execute_time=time()+24*3600;
                $eSysSync->save(false);
                continue;
            }
            $version=$fileReferenceId.'-'.$taskReferenceId;
            echo "Specific>>> version : ".$version."\n";
            $categorys=EbayCategory::find()//提取未更新的(通过版本判断)
                        ->select("categoryid")
                        ->where(["leaf"=>1])
                        ->andwhere(["siteid"=>$site['no']] )
                        ->andwhere("specifics_jobid !='$version' or specifics_jobid IS NULL")
                        ->asArray()
                        ->all();

            //No.3-遍历leaf目录
            $cidArry=array_column($categorys,'categoryid');
            echo "Specific>>> leaf categorys count:".count($categorys)."\n";
            foreach ($cidArry as $cid){//遍历叶目录
                if(empty($cid)){
                    echo "Specific>>> categoryid is empty\n";
                    continue;
                }
                echo "Specific>>> start site:".$site['en'].",categoryid:".$cid."\n";
                set_time_limit(0);
                $gcs->categoryid=$cid;//
                try{
                    $r=$gcs->syncLeafSpecific();
                }catch ( Exception $ex ) {
                    echo 'Specific sync Error categoryid:'.$cid.' Message:'.$ex->getMessage ()."\n";
                    continue;
                }
                $cs=EbayCategory::find()//记录页目录的specifics版本
                        ->where(["categoryid"=>$cid])
                        ->andwhere(["leaf"=>1])
                        ->andwhere(["siteid"=>$site['no']])
                        ->one();
                $cs->specifics_jobid=$version;
                $cs->save(false);
                echo "Specific>>> site:".$site['en'].",categoryid:".$cid." updated!\n";
            }
            //No.4-保存specifics版本,process状态清0
            echo "Specific>>> start update specifics version site:".$site['en']."\n";
            $eSysSync->specifics_jobid=$fileReferenceId.'-'.$taskReferenceId;
            $eSysSync->specific_next_execute_time=time()+24*3600;
            $eSysSync->specific_process=2;
            $eSysSync->save(false);
        }
    }

    /**
     * [AutoSyncEbaySpecificByCategoryID 同步eBay的Specifics信息,通过特定的类目]
     * @Author fanjs
     * @Editor willage 2016-11-15T15:40:40+0800
     * @param  integer $siteid                  [description]
     * @param  [type]  $categoryid              [description]
     */
    static function AutoSyncEbaySpecificByCategoryID($siteid=0,$categoryid){
        $sites=Helper_Siteinfo::getEbaySiteIdList();
        $ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        echo "start specific site:".$siteid." categoryid:".$categoryid."------>\n";

        $s = EbaySite::findOne('siteid = '.$siteid);
        $gcs=new getcategoryspecifics();
        $gcs->eBayAuthToken=$ue->token;
        $gcs->categoryid = $categoryid;
        $gcs->siteID=$siteid;
        $result=$gcs->api();
        if ($result['Ack']!=='Success'&&$result['Ack']!=='Failure'){
            echo "getcategoryspecifics returns not OK, so stop\n";
            return ;
        }
        EbaySpecific::deleteAll('categoryid = :categoryid and siteid = :siteid',[':categoryid'=>$categoryid,':siteid'=>$siteid]);
        foreach ($result['Recommendations']['NameRecommendation'] as $xrnr){

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
                'categoryid'=>$categoryid,
                'siteid'=>$siteid,
                'name'=>$name,
//              'value'=>$val,
//              'relationship'=>$relationship,
                'maxvalue'=>$maxvalue,
                'minvalue'=>$minvalue,
                'selectionmode'=>$selectionmode,
                'record_updatetime'=>time(),
            ));
            $es->relationship = $relationship;
            $es->value = $val;
            $es->save();
        }
        echo "specific site:".$siteid." categoryid:".$categoryid."!------>\n";
    }

    /**
     * [ManualSyncEbaySpecificByCategoryID description]
     * @Author willage 2017-01-11T14:30:11+0800
     * @Editor willage 2017-01-11T14:30:11+0800
     * @param  integer $siteid                  [description]
     * @param  [type]  $categoryid              [description]
     * 手动更新specifics,由于跟Category拉取异步,specifics_jobid单独保存在自己
     */
    static function ManualSyncEbaySpecificByCategoryID($siteid=0,$categoryid){
        $ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
        $rtMsg=array(
            "result"=>true,
            "message"=>"init"
            );
        //No.1-specifics版本检查
        $gcs=new getcategoryspecifics();
        $gcs->resetConfig($ue->DevAcccountID);
        $gcs->eBayAuthToken=$ue->token;
        $gcs->siteID=$siteid;
        $gcs->categoryid=$categoryid;//
        $result=$gcs->api();
        // print_r($result,false);
        if ( ($result['Ack']==='Failure') ||($result['Ack']!=='Success') ){//判断是否成功
            $rtMsg["result"]=false;
            $rtMsg["message"]="get fails: ".json_encode($result)."\n";
            echo $rtMsg["message"];
            return $rtMsg;
        }
        echo "start check specifics version site:".$siteid."\n";
        $fileReferenceId = $result['FileReferenceID'];
        $taskReferenceId = $result['TaskReferenceID'];
        if (strlen($fileReferenceId)===0&&strlen($taskReferenceId)===0){//判断文件ID长度
            $rtMsg["result"]=false;
            $rtMsg["message"]="no file-task ID\n";
            echo $rtMsg["message"];
            return $rtMsg;
        }
        $s = EbaySpecific::find()
                ->where('siteid=:s',[':s'=>$siteid])
                ->andwhere('categoryid=:cid',[':cid'=>$categoryid])
                ->orderBy(['specifics_jobid' => SORT_ASC])
                ->asArray()
                ->all();

        $oldSpecs=array_column($s,"name");
        if ((!empty($s)) && ($fileReferenceId.'-'.$taskReferenceId === $s[0]["specifics_jobid"])){//判断是否最新
            $rtMsg["result"]=false;
            $rtMsg["message"]="no updates\n";
            echo $rtMsg["message"]." ".$s[0]["specifics_jobid"]."\n";
            echo $fileReferenceId.'-'.$taskReferenceId."\n";
            return $rtMsg;
        }
        $version=$fileReferenceId.'-'.$taskReferenceId;
        echo "new version:".$version."\n";
        //No.2-specifics修改
        if (isset($result['Recommendations']['NameRecommendation'][0])) {//单个变成多个结构
            $nameValue=$result['Recommendations']['NameRecommendation'];
        }else{
            $nameValue[0]=$result['Recommendations']['NameRecommendation'];
        }
        $newSpecs=array();
        foreach ($result['Recommendations']['NameRecommendation'] as $xrnr){//修改或插入
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
            foreach ($val as &$v){
                $v=(string)$v;
            }
            $newSpecs[]=$xrnr["Name"];
            $specOne=EbaySpecific::find()
                ->where('siteid=:s',[':s'=>$siteid])
                ->andwhere('categoryid=:cid',[':cid'=>$categoryid])
                ->andwhere('name=:na',[':na'=>$xrnr["Name"]])
                ->one();
            if (empty($specOne)) {//插入
                echo "Insert specs\n";
                try{
                    $es=new EbaySpecific();
                    $es->setAttributes(array(
                        'categoryid'=>$categoryid,
                        'siteid'=>$siteid,
                        'name'=>$name,
                        'maxvalue'=>$maxvalue,
                        'minvalue'=>$minvalue,
                        'selectionmode'=>$selectionmode,
                        'specifics_jobid'=>$version,
                        'record_updatetime'=>time(),
                    ));
                    $es->relationship = $relationship;
                    $es->value = $val;
                    $es->save(false);
                    print_r($es->categoryid,false);
                }catch (Exception $e) {
                    echo $e->getMessage()."\n";
                }
            }else{//修改
                echo "Modify specs\n";
                $specOne->value =$val;
                $specOne->relationship =$relationship;
                $specOne->maxvalue =$maxvalue;
                $specOne->minvalue =$minvalue;
                $specOne->selectionmode =$selectionmode;
                $specOne->specifics_jobid =$version;
                $specOne->record_updatetime=time();
                $specOne->save(false);
            }
        }
        //No.3-specifics删除
        $deleSpec=array_diff($oldSpecs,$newSpecs);
        if (!empty($deleSpec)) {
            echo "delete data\n";
            print_r($deleSpec,false);
            EbaySpecific::deleteAll(['siteid'=>$siteid,'categoryid'=>$categoryid,'name'=>$deleSpec]);
        }
        $rtMsg["result"]=true;
        $rtMsg["message"]="get siteid:".$siteid." categoryid:".$categoryid." specifics OK\n";
        return $rtMsg;
    }

}//end of class
?>