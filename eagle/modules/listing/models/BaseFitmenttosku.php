<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "base_fitmenttosku".
 *
 * @property integer $id
 * @property integer $fid
 * @property string $name
 * @property string $sku
 * @property integer $addstatus
 */
class BaseFitmenttosku extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'base_fitmenttosku';
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
            [['id', 'sku'], 'required'],
            [['id', 'fid', 'addstatus'], 'integer'],
            [['name', 'sku'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fid' => 'Fid',
            'name' => 'Name',
            'sku' => 'Sku',
            'addstatus' => 'Addstatus',
        ];
    }
}
