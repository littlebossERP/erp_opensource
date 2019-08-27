<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;

class SysTrackingNumber extends \eagle\models\carrier\SysTrackingNumber
{
    public static $is_used=[0=>'未分配',1=>'已分配'];
}
