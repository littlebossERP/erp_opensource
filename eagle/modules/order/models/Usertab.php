<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "usertab".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $tabname
 */
class Usertab extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'usertab';
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
            [['uid', 'tabname'], 'required'],
            [['uid'], 'integer'],
            [['tabname'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'tabname' => 'Tabname',
        ];
    }
}
