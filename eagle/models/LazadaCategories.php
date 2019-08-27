<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_categories".
 *
 * @property integer $id
 * @property string $site
 * @property string $categories
 * @property integer $create_time
 * @property integer $update_time
 */
class LazadaCategories extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['site', 'categories'], 'required'],
            [['categories'], 'string'],
            [['create_time', 'update_time'], 'integer'],
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
            'categories' => 'Categories',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
