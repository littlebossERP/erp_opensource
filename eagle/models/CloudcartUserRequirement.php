<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cloudcart_user_requirement".
 *
 * @property string $id
 * @property integer $create_time
 * @property string $description
 */
class CloudcartUserRequirement extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cloudcart_user_requirement';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time'], 'integer'],
            [['description'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_time' => 'Create Time',
            'description' => 'Description',
        ];
    }
}
