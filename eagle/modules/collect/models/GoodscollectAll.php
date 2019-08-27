<?php

namespace eagle\modules\collect\models;

use Yii;

/**
 * This is the model class for table "goodscollect_all".
 *
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $mainimg
 * @property string $img
 * @property string $link
 * @property string $platform
 * @property double $price
 * @property string $toplatform
 * @property integer $wish
 * @property integer $ebay
 * @property integer $lazada
 * @property integer $ensogo
 * @property integer $createtime
 * @property string $customcode
 */
class GoodscollectAll extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'goodscollect_all';
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
            [['title', 'link', 'platform', 'createtime'], 'required'],
            [['description', 'img', 'toplatform', 'customcode'], 'string'],
            [['price'], 'number'],
            [['wish', 'ebay', 'lazada', 'ensogo', 'createtime'], 'integer'],
            [['title', 'mainimg', 'link'], 'string', 'max' => 255],
            [['platform'], 'string', 'max' => 55]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'mainimg' => 'Mainimg',
            'img' => 'Img',
            'link' => 'Link',
            'platform' => 'Platform',
            'price' => 'Price',
            'toplatform' => 'Toplatform',
            'wish' => 'Wish',
            'ebay' => 'Ebay',
            'lazada' => 'Lazada',
            'ensogo' => 'Ensogo',
            'createtime' => 'Createtime',
            'customcode' => 'Customcode',
        ];
    }
}
