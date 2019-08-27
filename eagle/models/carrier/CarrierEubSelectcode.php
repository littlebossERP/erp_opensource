<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "carrier_eub_selectcode".
 *
 * @property string $id
 * @property string $producttype
 * @property string $postcode
 * @property string $country
 * @property string $codenum
 * @property integer $bef_update_date
 * @property integer $aft_update_date
 */
class CarrierEubSelectcode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_eub_selectcode';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bef_update_date', 'aft_update_date'], 'integer'],
            [['producttype', 'postcode', 'country'], 'string', 'max' => 50],
            [['codenum'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'producttype' => 'Producttype',
            'postcode' => 'Postcode',
            'country' => 'Country',
            'codenum' => 'Codenum',
            'bef_update_date' => 'Bef Update Date',
            'aft_update_date' => 'Aft Update Date',
        ];
    }
}
