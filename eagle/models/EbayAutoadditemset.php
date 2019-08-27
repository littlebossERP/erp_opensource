<?php

namespace eagle\models;

use Yii;
use eagle\modules\listing\models\EbayMuban;

/**
 * This is the model class for table "ebay_autoadditemset".
 *
 * @property integer $timerid
 * @property integer $uid
 * @property string $gmt
 * @property string $day_start_date
 * @property string $day_start_date2
 * @property string $day_start_time
 * @property integer $last_runtime
 * @property integer $next_runtime
 * @property integer $mubanid
 * @property string $itemtitle
 * @property string $selleruserid
 * @property integer $createtime
 * @property integer $updatetime
 */
class EbayAutoadditemset extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_autoadditemset';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'last_runtime', 'next_runtime', 'mubanid', 'createtime', 'updatetime'], 'integer'],
            [['gmt'], 'string', 'max' => 10],
            [['day_start_date', 'day_start_date2', 'day_start_time'], 'string', 'max' => 50],
            [['itemtitle'], 'string', 'max' => 150],
            [['selleruserid'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'timerid' => 'Timerid',
            'uid' => 'Uid',
            'gmt' => 'Gmt',
            'day_start_date' => 'Day Start Date',
            'day_start_date2' => 'Day Start Date2',
            'day_start_time' => 'Day Start Time',
            'last_runtime' => 'Last Runtime',
            'next_runtime' => 'Next Runtime',
            'mubanid' => 'Mubanid',
            'itemtitle' => 'Itemtitle',
            'selleruserid' => 'Selleruserid',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
    
    /**
     * 恢复时区
     */
    static function timeZoneRestore(){
    	return date_default_timezone_set("Etc/GMT-8");
    }
    /**
     * 以记录的时区为时区
     */
    function timeZoneActive ()
    {
    	$gmt = 0 - $this->gmt;
    	if ($gmt >= 0) {
    		$gmt = "+" . $gmt;
    	}
    	return date_default_timezone_set('Etc/GMT' . $gmt);
    }
    
    /**
     * 保存前自动形成下次刊登时间
     */
    function beforeSave($insert){
    	if (parent::beforeSave($insert)){
	    	$nextlistdate=$this->getNextListDate(true);
	    	if (!$nextlistdate){
	    		$this->next_runtime='error';
	    	}else{
	    		$this->timeZoneActive();
	    		$this->next_runtime=strtotime($nextlistdate.' '.$this->day_start_time.'00');
	    		$this->timeZoneRestore();
	    	}
	    	return true;
	    }else{
	    	return false;
    	}
    }
    
    /**
     * 获得下次执行自动刊登的日期
     *
     * 开启/重新开启 定时刊登设置 需要注意：
     * 	1, day_start_date >= 设置当天日期 （如果设置时间在当天且还未到，为当天）
     * 	2, 定时开始时间 设在0点时, 可能 会导致 因为跑的太快,提前执行,所以 简略 0点 到 10分钟内的
     * 	3, timezone_last_run_date 设置为null
     *
     */
    function getNextListDate($before_save_runing=false){
    		
    	$this->timeZoneActive();
    	if ($this->day_start_date<date('Ymd')){
    		return $this->restoreTimeZoneAndReturn(false);
    	}
    	return $this->restoreTimeZoneAndReturn($this->day_start_date);
    }
    /**
     * 重置时区，并返回指定值
     *
     */
    protected function restoreTimeZoneAndReturn($r){
    	$this->timeZoneRestore();
    	return $r;
    }
    
    /**
     * 关联属性
     */
    public function getEbay_muban(){
    	return $this->hasOne(EbayMuban::className(),['mubanid'=>'mubanid']);
    }
}
