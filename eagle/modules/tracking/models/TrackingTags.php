<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "lt_tracking_tags".
 *
 * @property integer $tracking_tag_id
 * @property string $tracking_id
 * @property string $tag_id
 */
class TrackingTags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_tracking_tags';
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
            [['tag_id'], 'integer'],
            [['tracking_id'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tracking_tag_id' => 'Tracking Tag ID',
            'tracking_id' => 'Tracking ID',
            'tag_id' => 'Tag ID',
        ];
    }
}
