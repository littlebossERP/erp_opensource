<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cloudcart_demand_user_tag".
 *
 * @property string $id
 * @property string $tag
 * @property string $description
 * @property string $module_id
 */
class CloudcartDemandUserTag extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cloudcart_demand_user_tag';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description'], 'string'],
            [['tag'], 'string', 'max' => 200],
            [['module_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'description' => 'Description',
            'module_id' => 'Module ID',
        ];
    }
}
