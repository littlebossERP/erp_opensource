<?php

namespace eagle\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "ebay_shippingservice".
 *
 * @property integer $id
 * @property string $description
 * @property string $internationalservice
 * @property string $shippingservice
 * @property integer $shippingserviceid
 * @property integer $shippingtimemax
 * @property integer $shippingtimemin
 * @property string $servicetype
 * @property string $shippingpackage
 * @property string $shippingcarrier
 * @property string $shippingservicepackagedetails
 * @property string $weightrequired
 * @property string $dimensionsrequired
 * @property string $validforsellingflow
 * @property string $expeditedservice
 * @property string $surchargeapplicable
 * @property integer $record_updatetime
 * @property integer $detailversion
 * @property integer $updatetime
 * @property integer $siteid
 */
class EbayShippingservice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_shippingservice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shippingserviceid', 'shippingtimemax', 'shippingtimemin', 'record_updatetime', 'detailversion', 'updatetime', 'siteid'], 'integer'],
            [['shippingpackage', 'shippingservicepackagedetails'], 'string'],
            [['description', 'shippingservice', 'shippingcarrier'], 'string', 'max' => 100],
            [['internationalservice', 'servicetype'], 'string', 'max' => 30],
            [['weightrequired', 'dimensionsrequired', 'validforsellingflow', 'expeditedservice', 'surchargeapplicable'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'description' => 'Description',
            'internationalservice' => 'Internationalservice',
            'shippingservice' => 'Shippingservice',
            'shippingserviceid' => 'Shippingserviceid',
            'shippingtimemax' => 'Shippingtimemax',
            'shippingtimemin' => 'Shippingtimemin',
            'servicetype' => 'Servicetype',
            'shippingpackage' => 'Shippingpackage',
            'shippingcarrier' => 'Shippingcarrier',
            'shippingservicepackagedetails' => 'Shippingservicepackagedetails',
            'weightrequired' => 'Weightrequired',
            'dimensionsrequired' => 'Dimensionsrequired',
            'validforsellingflow' => 'Validforsellingflow',
            'expeditedservice' => 'Expeditedservice',
            'surchargeapplicable' => 'Surchargeapplicable',
            'record_updatetime' => 'Record Updatetime',
            'detailversion' => 'Detailversion',
            'updatetime' => 'Updatetime',
            'siteid' => 'Siteid',
        ];
    }
    static public $servicetype=array(
            'Flat'=>1,
            'Calculated'=>2,
    );

    public function behaviors(){
        return array(
                'SerializeBehavior' => array(
                        'class' => SerializeBehavior::className(),
                        'serialAttributes' => array('shippingpackage','shippingservicepackagedetails'),
                )
        );
    }
}
