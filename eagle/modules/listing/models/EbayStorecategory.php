<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_storecategory".
 *
 * @property string $id
 * @property integer $uid
 * @property string $selleruserid
 * @property string $categoryid
 * @property string $category_name
 * @property integer $category_order
 * @property string $category_parentid
 */
class EbayStorecategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_storecategory';
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
            [['uid', 'categoryid', 'category_order', 'category_parentid'], 'integer'],
            [['selleruserid', 'category_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'selleruserid' => 'Selleruserid',
            'categoryid' => 'Categoryid',
            'category_name' => 'Category Name',
            'category_order' => 'Category Order',
            'category_parentid' => 'Category Parentid',
        ];
    }
}
