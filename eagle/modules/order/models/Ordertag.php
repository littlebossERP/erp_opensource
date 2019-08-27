<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "lt_ordertag".
 *
 * @property integer $tag_id
 * @property string $tag_name
 * @property string $color
 */
class Ordertag extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_ordertag';
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
            [['tag_name'], 'string', 'max' => 100],
            [['color'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tag_id' => 'Tag ID',
            'tag_name' => 'Tag Name',
            'color' => 'Color',
        ];
    }
}
