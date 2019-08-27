<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_address".
 *
 * @property string $id
 * @property string $address_name
 * @property string $type
 * @property string $country_code
 * @property string $country
 * @property string $country_en
 * @property string $province_code
 * @property string $province
 * @property string $province_en
 * @property string $city
 * @property string $city_en
 * @property string $district
 * @property string $district_en
 * @property string $county
 * @property string $county_en
 * @property string $address
 * @property string $address_en
 * @property string $zip_code
 * @property string $company
 * @property string $company_en
 * @property string $connect
 * @property string $connect_en
 * @property string $phone
 * @property string $mobile
 * @property string $email
 * @property string $fax
 * @property integer $carete_time
 * @property integer $update_time
 */
class SysAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_address';
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
            [['address_name', 'type', 'country_code', 'country', 'country_en', 'province_code', 'province', 'province_en', 'city', 'city_en', 'connect', 'connect_en'], 'required'],
            [['carete_time', 'update_time'], 'integer'],
            [['address_name', 'country', 'country_en', 'company', 'company_en', 'connect', 'connect_en', 'email'], 'string', 'max' => 100],
            [['type', 'zip_code'], 'string', 'max' => 20],
            [['country_code', 'province_code'], 'string', 'max' => 10],
            [['province', 'province_en', 'city', 'city_en', 'district', 'district_en', 'county', 'county_en', 'phone', 'mobile', 'fax'], 'string', 'max' => 50],
            [['address', 'address_en'], 'string', 'max' => 225]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'address_name' => 'Address Name',
            'type' => 'Type',
            'country_code' => 'Country Code',
            'country' => 'Country',
            'country_en' => 'Country En',
            'province_code' => 'Province Code',
            'province' => 'Province',
            'province_en' => 'Province En',
            'city' => 'City',
            'city_en' => 'City En',
            'district' => 'District',
            'district_en' => 'District En',
            'county' => 'County',
            'county_en' => 'County En',
            'address' => 'Address',
            'address_en' => 'Address En',
            'zip_code' => 'Zip Code',
            'company' => 'Company',
            'company_en' => 'Company En',
            'connect' => 'Connect',
            'connect_en' => 'Connect En',
            'phone' => 'Phone',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'fax' => 'Fax',
            'carete_time' => 'Carete Time',
            'update_time' => 'Update Time',
        ];
    }
}
