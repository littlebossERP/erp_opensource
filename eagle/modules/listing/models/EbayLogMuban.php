<?php

namespace eagle\modules\listing\models;

use Yii;
/**
 * This is the model class for table "ebay_log_muban".
 *
 * @property integer $logid
 * @property integer $uid
 * @property string $selleruserid
 * @property integer $timerid
 * @property integer $mubanid
 * @property string $itemid
 * @property integer $result
 * @property integer $siteid
 * @property string $method
 * @property string $title
 * @property integer $createtime
 */
class EbayLogMuban extends \yii\db\ActiveRecord
{
	const RESULT_ERROR=0;
	const RESULT_SUCCESS=1;
	const RESULT_WARMING=2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_log_muban';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'timerid', 'mubanid', 'itemid', 'result', 'siteid', 'createtime'], 'integer'],
            [['selleruserid'], 'string', 'max' => 32],
            [['method'], 'string', 'max' => 8],
            [['title'], 'string', 'max' => 125]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'logid' => 'Logid',
            'uid' => 'Uid',
            'selleruserid' => 'Selleruserid',
            'timerid' => 'Timerid',
            'mubanid' => 'Mubanid',
            'itemid' => 'Itemid',
            'result' => 'Result',
            'siteid' => 'Siteid',
            'method' => 'Method',
            'title' => 'Title',
            'createtime' => 'Createtime',
        ];
    }
    
    public function getDetail(){
    	return $this->hasOne(EbayLogMubanDetail::className(),['logid'=>'logid']);
    }
}
