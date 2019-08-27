<?php
namespace eagle\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * FakeUser 
 * 主要功能为了可以通过带着token的url进行登录。
 * 只要通过main.php配置User对应的identifyClass是FakeUser便可。
 * 目前只为17tracker服务。
 */
class FakeUser  implements IdentityInterface
{
    const STATUS_DELETED = 0;
    //const STATUS_ACTIVE = 10;
    const STATUS_ACTIVE = 1;
    
    public  $id=0; // 这里id表示puid
    public $username="";
    
     
    public function getId(){
    	return $this->id;
    }

    public function getUsername(){
    	return $this->username;
    	
    }
    
    
    /**
     * 获取当前登陆用户的父ID(主账号的uid)
     */    
    public function getParentUid(){
    	//当this->puid == 0  表示当前用户已经是主账号，只要直接返回uid
    //	if ($this->puid == 0) return $this->uid;
    	return $this->id;
    	
    }
    
    
   

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
   /* public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }*/

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
    	$fu=new FakeUser;
    	$fu->id=$id;
    	return $fu;
       // return static::findOne(['uid' => $id, 'is_active' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        //return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    	return static::findOne(['user_name' => $username, 'is_active' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

  

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        //return $this->auth_key;   lolochange
        return "auth";
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        //return $this->getAuthKey() === $authKey;  lolochange
        return true;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        //return Yii::$app->security->validatePassword($password, $this->password_hash);
        if ($password==$this->password) return true; 
        return false;
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
}
