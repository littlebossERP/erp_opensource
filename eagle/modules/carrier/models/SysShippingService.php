<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "sys_shipping_service".
 *
 * @property string $id
 * @property string $carrier_code
 * @property string $carrier_params
 * @property string $ship_address
 * @property string $return_address
 * @property integer $is_used
 * @property string $service_name
 * @property string $service_code
 * @property integer $auto_ship
 * @property string $web
 * @property string $create_time
 * @property string $update_time
 * @property integer $carrier_account_id
 */
class SysShippingService extends \eagle\models\carrier\SysShippingService
{
   	public static $amazon_carrier_code = array(
   			"USPS"=>"USPS",
   			"UPS"=>"UPS",
   			"UPSMI"=>"UPSMI",
   			"FedEx"=>"FedEx",
   			"DHL"=>"DHL",
   			"Fastway"=>"Fastway",
   			"GLS"=>"GLS",
   			"GO!"=>"GO!",
   			"Hermes Logistik Gruppe"=>"Hermes Logistik Gruppe",
   			"Royal Mail"=>"Royal Mail",
   			"Parcelforce"=>"Parcelforce",
   			"City Link"=>"City Link",
   			"TNT"=>"TNT",
   			"Target"=>"Target",
   			"SagawaExpress"=>"SagawaExpress",
   			"NipponExpress"=>"NipponExpress",
   			"YamatoTransport"=>"YamatoTransport",
   			"DHL Global Mail"=>"DHL Global Mail",
   			"UPS Mail Innovations"=>"UPS Mail Innovations",
   			"FedEx SmartPost"=>"FedEx SmartPost",
   			"OSM"=>"OSM",
   			"OnTrac"=>"OnTrac",
   			"Streamlite"=>"Streamlite",
   			"Newgistics"=>"Newgistics",
   			"Canada Post"=>"Canada Post",
   			"Blue Package"=>"Blue Package",
   			"Chronopost"=>"Chronopost",
   			"Deutsche Post"=>"Deutsche Post",
   			"DPD"=>"DPD",
   			"La Poste"=>"La Poste",
   			"Parcelnet"=>"Parcelnet",
   			"Poste Italiane"=>"Poste Italiane",
   			"SDA"=>"SDA",
   			"Smartmail"=>"Smartmail",
   			"FEDEX_JP"=>"FEDEX_JP",
   			"JP_EXPRESS"=>"JP_EXPRESS",
   			"NITTSU"=>"NITTSU",
   			"SAGAWA"=>"SAGAWA",
   			"YAMATO"=>"YAMATO",
   			"BlueDart"=>"BlueDart",
   			"AFL/Fedex"=>"AFL/Fedex",
   			"Aramex"=>"Aramex",
   			"India Post"=>"India Post",
   			"Professional"=>"Professional",
   			"DTDC"=>"DTDC",
   			"Overnite Express"=>"Overnite Express",
   			"First Flight"=>"First Flight",
   			"Delhivery"=>"Delhivery",
   			"Lasership"=>"Lasership",
   			"Yodel"=>"Yodel",
   			"Other"=>"Other",
   	);
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('carrier_params','ship_address','return_address','service_code','address','custom_template_print','proprietary_warehouse','customer_number_config','print_params'),
    			)
    	);
    }
}
