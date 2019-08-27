<?php

namespace eagle\modules\platform\models;

use Yii;

/**
 * This is the model class for table "cd_shop_opening_application".
 *
 * @property integer $id
 * @property string $company_license_picture
 * @property string $sponsor_id_card_picture
 * @property string $company_e_name
 * @property string $company_license_code
 * @property string $company_e_address
 * @property string $company_postcode
 * @property string $shop_e_name
 * @property string $director_e_name
 * @property string $phone
 * @property string $e_mail
 * @property string $all_website_link
 * @property string $oversea_warehouse_name
 * @property string $third_party_payment_method
 * @property string $brand_picture
 * @property string $create_time
 * @property string $desc
 *
 */
class CdiscountRegister extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cd_shop_opening_application';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_license_picture', 'sponsor_id_card_picture', 'company_e_name', 'company_license_code', 'company_e_address', 'company_postcode','shop_e_name','director_e_name','phone','e_mail'], 'required'],
            [['id'], 'integer'],
            [['create_time'], 'safe'],
            [['company_license_picture','sponsor_id_card_picture','brand_picture','e_mail'], 'string', 'max' => 255],
            [['company_e_name','shop_e_name','director_e_name'], 'string', 'max' => 100],
            [['company_license_code'], 'string', 'max' => 80],
            [['company_postcode'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_license_picture' => 'company License Picture',
            'sponsor_id_card_picture' => 'sponsor_id_card_picture',
            'company_e_name' => 'Company E Name',
            'company_license_code' => 'company License Code',
            'company_e_address' => 'Company E Address',
            'company_postcode' => 'Company Postcode',
            'shop_e_name' => 'Shop E Name',
            'director_e_name' => 'Director E Name',
            'phone' => 'Phone',
            'e_mail' => 'E Mail',
            'all_website_link' => 'All Website Link',
            'oversea_warehouse_name' => 'Oversea Warehouse Name',
            'third_party_payment_method' => 'Third Party Payment Method',
            'brand_picture' => 'Brand Picture',
            'create_time' => 'Create Time',
            'desc' => 'Desc',
        ];
    }
}
