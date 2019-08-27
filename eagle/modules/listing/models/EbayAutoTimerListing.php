<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_auto_timer_listing".
 *
 * @property integer $id
 * @property string $selleruserid
 * @property string $draft_id
 * @property string $itemid
 * @property string $itemtitle
 * @property integer $status
 * @property integer $status_process
 * @property integer $runtime
 * @property string $set_gmt
 * @property string $set_date
 * @property integer $set_hour
 * @property integer $set_min
 * @property string $verify_result
 * @property string $listing_result
 * @property integer $err_cnt
 * @property integer $ebay_uid
 * @property integer $puid
 * @property integer $created
 * @property integer $updated
 */
class EbayAutoTimerListing extends \yii\db\ActiveRecord
{
    //状态机
    const VERIFY_PENDING=0;
    const VERIFY_RUNNING=1;
    const VERIFY_EXECEPT=3;
    const VERIFY_NOITEM=4;
    const ADDITEM_PENDING=2;
    const ADDITEM_RUNNING=10;
    const ADDITEM_FINISH=20;
    const ADDITEM_EXECEPT=30;
    //错误码
    const CODE_SUCCESS =1;
    const CODE_ERR_API = 3;
    const CODE_ERR_DB = 4;
    //状态
    const STATUS_SUSPEND=0;
    const STATUS_OPEND=1;
    const STATUS_CLOSED=1;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_auto_timer_listing';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['draft_id', 'itemid', 'status', 'status_process', 'runtime', 'set_hour', 'set_min', 'err_cnt', 'ebay_uid', 'puid', 'created', 'updated'], 'integer'],
            [['set_date'], 'safe'],
            [['verify_result', 'listing_result'], 'string'],
            [['selleruserid'], 'string', 'max' => 50],
            [['itemtitle'], 'string', 'max' => 100],
            [['set_gmt'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleruserid' => 'Selleruserid',
            'draft_id' => 'Draft ID',
            'itemid' => 'Itemid',
            'itemtitle' => 'Itemtitle',
            'status' => 'Status',
            'status_process' => 'Status Process',
            'runtime' => 'Runtime',
            'set_gmt' => 'Set Gmt',
            'set_date' => 'Set Date',
            'set_hour' => 'Set Hour',
            'set_min' => 'Set Min',
            'verify_result' => 'Verify Result',
            'listing_result' => 'Listing Result',
            'err_cnt' => 'Err Cnt',
            'ebay_uid' => 'Ebay Uid',
            'puid' => 'Puid',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
    /**
     * 关联属性
     */
    public function getEbay_muban(){
        return $this->hasOne(EbayMuban::className(),['mubanid'=>'draft_id']);
    }
    // /**
    //  * 恢复时区
    //  */
    // public  function timeZoneRestore(){
    //     return date_default_timezone_set("Etc/GMT-8");
    // }
    // /**
    //  * 以记录的时区为时区
    //  */
    // static function timeZoneActive ()
    // {
    //     $gmt = 0 - $this->set_gmt;
    //     if ($gmt >= 0) {
    //         $gmt = "+" . $gmt;
    //     }
    //     return date_default_timezone_set('Etc/GMT' . $gmt);
    // }
    /**
     * [calcRuntime 计算运行时间]
     * 注意：不提供暂停/重启,因此不判断设定时间是否已经pass
     * @auhor  willage 2017-04-09T23:29:17+0800
     * @update willage 2017-04-011T23:29:17+0800
     */
    public function beforeSave($insert){
        if (parent::beforeSave($insert)){
            //计算时间戳
            $time_str=$this->set_date." ".$this->set_hour.":".$this->set_min.":00";
            $datetime = new \DateTime($time_str,new \DateTimeZone('GMT'.$this->set_gmt));
            $this->runtime=$datetime->getTimestamp();
            return true;
        }else{
            return false;
        }
    }


}//end class
