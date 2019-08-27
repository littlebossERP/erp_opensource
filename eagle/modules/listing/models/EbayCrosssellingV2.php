<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_crossselling_v2".
 *
 * @property integer $id
 * @property string $puid
 * @property string $title
 * @property integer $create_time
 * @property integer $update_time
 * @property string $sort
 * @property string $additional_info
 * @property integer $type
 * @property string $is_system_default
 * @property integer $status
 */
class EbayCrosssellingV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_crossselling_v2';
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
            [['puid', 'create_time', 'update_time', 'type', 'status'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['sort'], 'string', 'max' => 20],
            [['additional_info'], 'string', 'max' => 500],
            [['is_system_default'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'title' => 'Title',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'sort' => 'Sort',
            'additional_info' => 'Additional Info',
            'type' => 'Type',
            'is_system_default' => 'Is System Default',
            'status' => 'Status',
        ];
    }
}
