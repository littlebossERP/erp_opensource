<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "common_declared_info".
 *
 * @property string $id
 * @property string $custom_name
 * @property string $ch_name
 * @property string $en_name
 * @property string $declared_value
 * @property string $declared_weight
 * @property string $detail_hs_code
 * @property integer $is_default
 */
class CommonDeclaredInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common_declared_info';
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
            [['declared_value', 'declared_weight'], 'number'],
            [['is_default'], 'integer'],
            [['custom_name', 'ch_name', 'en_name'], 'string', 'max' => 100],
            [['detail_hs_code'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'custom_name' => 'Custom Name',
            'ch_name' => 'Ch Name',
            'en_name' => 'En Name',
            'declared_value' => 'Declared Value',
            'declared_weight' => 'Declared Weight',
            'detail_hs_code' => 'Detail Hs Code',
            'is_default' => 'Is Default',
        ];
    }
}
