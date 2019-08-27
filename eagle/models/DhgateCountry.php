<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "dhgate_country".
 *
 * @property string $countryid
 * @property string $area
 * @property string $callingcode
 * @property string $chinaarea
 * @property integer $countrycode
 * @property string $currency
 * @property string $description
 * @property string $name
 * @property integer $sortvalue
 * @property string $vaild
 */
class DhgateCountry extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dhgate_country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['countryid'], 'required'],
            [['countrycode', 'sortvalue'], 'integer'],
            [['countryid'], 'string', 'max' => 2],
            [['area', 'callingcode', 'chinaarea', 'currency', 'description', 'name'], 'string', 'max' => 50],
            [['vaild'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'countryid' => 'Countryid',
            'area' => 'Area',
            'callingcode' => 'Callingcode',
            'chinaarea' => 'Chinaarea',
            'countrycode' => 'Countrycode',
            'currency' => 'Currency',
            'description' => 'Description',
            'name' => 'Name',
            'sortvalue' => 'Sortvalue',
            'vaild' => 'Vaild',
        ];
    }
}
