<?php

namespace eagle\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "ebay_site".
 *
 * @property integer $siteid
 * @property integer $detailversion
 * @property integer $updatetime
 * @property integer $status_process
 * @property string $site
 * @property string $currency
 * @property string $cn
 * @property string $domain
 * @property integer $categoryversion
 * @property integer $feature_version
 * @property string $specifics_jobid
 * @property string $dispatchtimemax
 * @property string $payment_option
 * @property string $return_policy
 * @property string $listing_startprice
 * @property string $buyer_requirement
 * @property string $listing_feature
 * @property string $feature_definitions
 * @property string $variation
 * @property string $tax_jurisdiction
 * @property string $url_details
 * @property integer $record_updatetime
 * @property string $feature_sitedefault
 */
class EbaySite extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_site';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteid', 'site', 'currency'], 'required'],
            [['siteid', 'detailversion', 'updatetime', 'status_process', 'categoryversion', 'feature_version', 'record_updatetime'], 'integer'],
            [['dispatchtimemax', 'payment_option', 'return_policy', 'listing_startprice', 'buyer_requirement', 'listing_feature', 'feature_definitions', 'variation', 'tax_jurisdiction', 'url_details', 'feature_sitedefault'], 'string'],
            [['site'], 'string', 'max' => 50],
            [['currency'], 'string', 'max' => 5],
            [['cn'], 'string', 'max' => 20],
            [['domain'], 'string', 'max' => 100],
            [['specifics_jobid'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'siteid' => 'Siteid',
            'detailversion' => 'Detailversion',
            'updatetime' => 'Updatetime',
            'status_process' => 'Status Process',
            'site' => 'Site',
            'currency' => 'Currency',
            'cn' => 'Cn',
            'domain' => 'Domain',
            'categoryversion' => 'Categoryversion',
            'feature_version' => 'Feature Version',
            'specifics_jobid' => 'Specifics Jobid',
            'dispatchtimemax' => 'Dispatchtimemax',
            'payment_option' => 'Payment Option',
            'return_policy' => 'Return Policy',
            'listing_startprice' => 'Listing Startprice',
            'buyer_requirement' => 'Buyer Requirement',
            'listing_feature' => 'Listing Feature',
            'feature_definitions' => 'Feature Definitions',
            'variation' => 'Variation',
            'tax_jurisdiction' => 'Tax Jurisdiction',
            'url_details' => 'Url Details',
            'record_updatetime' => 'Record Updatetime',
            'feature_sitedefault' => 'Feature Sitedefault',
        ];
    }

    public function behaviors(){
        return array(
                'SerializeBehavior' => array(
                        'class' => SerializeBehavior::className(),
                        'serialAttributes' => array('dispatchtimemax','return_policy','payment_option','listing_startprice','buyer_requirement','listing_feature','variation','tax_jurisdiction','url_details'),
                )
        );
    }
}
