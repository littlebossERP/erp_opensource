<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_crossselling".
 *
 * @property integer $crosssellingid
 * @property string $selleruserid
 * @property string $title
 * @property integer $createtime
 * @property integer $updatetime
 */
class EbayCrossselling extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_crossselling';
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
            [['createtime', 'updatetime'], 'integer'],
            [['selleruserid', 'title'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'crosssellingid' => 'Crosssellingid',
            'selleruserid' => 'Selleruserid',
            'title' => 'Title',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
}
