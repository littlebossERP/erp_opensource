<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_category".
 *
 * @property integer $category_id
 * @property string $name
 * @property string $parent_id
 * @property integer $level
 * @property integer $has_children
 * @property string $addi_info
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 */
class Category extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_category';
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
            [['parent_id', 'level', 'has_children', 'capture_user_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 100],
            [['addi_info', 'comment'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id' => 'Category ID',
            'name' => 'Name',
            'parent_id' => 'Parent ID',
            'level' => 'Level',
            'has_children' => 'Has Children',
            'addi_info' => 'Addi Info',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
