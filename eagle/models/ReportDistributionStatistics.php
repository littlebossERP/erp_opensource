<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "report_distribution_statistics".
 *
 * @property integer $id
 * @property string $order_create_time
 * @property string $puid
 * @property string $order_sources
 * @property string $selleruserids
 * @property string $json_params
 */
class ReportDistributionStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'report_distribution_statistics';
    }
    
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
    	return Yii::$app->get('db_queue');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_create_time'], 'safe'],
            [['puid'], 'integer'],
            [['selleruserids', 'json_params'], 'string'],
            [['order_sources'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_create_time' => 'Order Create Time',
            'puid' => 'Puid',
            'order_sources' => 'Order Sources',
            'selleruserids' => 'Selleruserids',
            'json_params' => 'Json Params',
        ];
    }
}
