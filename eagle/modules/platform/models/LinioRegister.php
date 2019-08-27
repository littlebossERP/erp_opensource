<?php

namespace eagle\modules\platform\models;

use Yii;

/**
 * This is the model class for table "linio_shop_opening_application".
 *
 */
class LinioRegister extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'linio_shop_opening_application';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_name','company_name','e_mail','other_website_link','bill_country','bill_province','bill_city', 'bill_street','warehouse_country','warehouse_province','warehouse_city', 'warehouse_street','cargo_info','payoneer_name'], 'string', 'max' => 255],
            [['director_name','tax_registration_certificate','contact','warehouse_contacts','payoneer_id'], 'string', 'max' => 100],
            [['phone','skype','bill_postal_code','warehouse_postal_code','serial_number'], 'string', 'max' => 50],
            [['mobile'], 'string', 'max' => 20],
            [['id','puid','create_time','status'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
                'id' => 'ID',
                'puid' => 'PUID',
                'shop_name' => 'SHOP NAME',
                'company_name' => 'COMPANY NAME',
                'director_name' => 'DIRECTOR NAME',
                'tax_registration_certificate' => 'TAX REGISTRATION CERTIFICATE',
                'e_mail' => 'E MAIL',
                'other_website_link' => 'OTHER WEBSITE LINK',
                'contact' => 'CONTACT',
                'phone' => 'PHONE',
                'mobile' => 'MOBILE',
                'skype' => 'SKYPE',
                'bill_country' => 'BILL COUNTRY',
                'bill_province' => 'BILL PROVINCE',
                'bill_city' => 'BILL CITY',
                'bill_postal_code' => 'BILL POSTAL CODE',
                'bill_street' => 'BILL STREET',
                'warehouse_contacts' => 'WAREHOUSE CONTACTS',
                'warehouse_country' => 'WAREHOUSE COUNTRY',
                'warehouse_province' => 'WAREHOUSE PROVINCE',
                'warehouse_city' => 'WAREHOUSE CITY',
                'warehouse_postal_code' => 'WAREHOUSE POSTAL CODE',
                'warehouse_street' => 'WAREHOUSE STREET',
                'cargo_info' => 'CARGO INFO',
                'payoneer_name' => 'PAYONEER NAME',
                'payoneer_id' => 'PAYONEER ID',
                'create_time' => 'CREATE TIME',
                'serial_number' => 'SERIAL NUMBER',
                'status' => 'STATUS',
        ];
    }
}
