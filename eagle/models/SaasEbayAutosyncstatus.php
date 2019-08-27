<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_ebay_autosyncstatus".
 *
 * @property string $id
 * @property string $selleruserid
 * @property integer $ebay_uid
 * @property integer $type
 * @property integer $status
 * @property integer $status_process
 * @property integer $lastrequestedtime
 * @property integer $lastprocessedtime
 * @property integer $next_execute_time
 * @property integer $created
 * @property integer $updated
 */
class SaasEbayAutosyncstatus extends \yii\db\ActiveRecord
{
	const BestOffer=8;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_ebay_autosyncstatus';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ebay_uid', 'type', 'status', 'status_process', 'lastrequestedtime', 'lastprocessedtime', 'next_execute_time', 'created', 'updated'], 'integer'],
            [['selleruserid'], 'string', 'max' => 50],
            [['ebay_uid', 'type'], 'unique', 'targetAttribute' => ['ebay_uid', 'type'], 'message' => 'The combination of Ebay Uid and Type has already been taken.']
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
            'ebay_uid' => 'Ebay Uid',
            'type' => 'Type',
            'status' => 'Status',
            'status_process' => 'Status Process',
            'lastrequestedtime' => 'Lastrequestedtime',
            'lastprocessedtime' => 'Lastprocessedtime',
            'next_execute_time' => 'Next Execute Time',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
