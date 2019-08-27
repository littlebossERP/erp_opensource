<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "visit_log".
 *
 * @property integer $id
 * @property string $url_path
 * @property string $visit_time
 */
class VisitLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'visit_log';
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
            [['url_path', 'visit_time'], 'required'],
            [['visit_time'], 'safe'],
            [['url_path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url_path' => 'Url Path',
            'visit_time' => 'Visit Time',
        ];
    }
}
