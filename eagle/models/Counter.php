<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "counter".
 *
 * @property integer $id
 * @property integer $times
 */
class Counter extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'counter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'times'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'times' => 'Times',
        ];
    }
}
