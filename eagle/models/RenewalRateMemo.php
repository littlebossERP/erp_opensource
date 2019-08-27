<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "renewal_rate_memo".
 *
 * @property string $id
 * @property string $type
 * @property string $date_section
 * @property integer $create_time
 * @property integer $update_time
 * @property string $memo
 */
class RenewalRateMemo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'renewal_rate_memo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'integer'],
            [['memo'], 'string'],
            [['type'], 'string', 'max' => 20],
            [['date_section'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'date_section' => 'Date Section',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'memo' => 'Memo',
        ];
    }
}
