<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_category_attr".
 *
 * @property integer $id
 * @property string $site
 * @property integer $categoryid
 * @property string $attributes
 * @property integer $create_time
 * @property integer $update_time
 */
class LazadaCategoryAttr extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_category_attr';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['site', 'categoryid', 'attributes'], 'required'],
            [['categoryid', 'create_time', 'update_time'], 'integer'],
            [['attributes'], 'string'],
            [['site'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site' => 'Site',
            'categoryid' => 'Categoryid',
            'attributes' => 'Attributes',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
