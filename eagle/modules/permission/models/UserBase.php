<?php

namespace eagle\modules\permission\models;

use eagle\models\UserInfo;


class UserBase extends \eagle\models\UserBase {

    /**
     * @return array relational rules.
     */
    public function getInfo(){
    	return $this->hasOne(UserInfo::className(),['uid'=>'uid']);
    }
    
    /**
     * @param object $info.
     */
    public function setInfo($info){
    	$this->info = $info;
    }
}
