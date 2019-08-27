<?php
namespace eagle\modules\carrier\models;

use yii\db\ActiveRecord;

class PlatformShippingMappingLog extends ActiveRecord {

    public static function tableName() {
        return 'platform_shipping_mapping_log';
    }
}