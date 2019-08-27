<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "excelmodel".
 *
 * @property integer $id
 * @property string $name
 * @property string $content
 * @property integer $type
 * @property string $belong
 * @property string $tablename
 * @property string $keyname
 */
class Excelmodel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'excelmodel';
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
            [['name', 'content', 'type'], 'required'],
            [['content'], 'string'],
            [['type'], 'integer'],
            [['name'], 'string', 'max' => 200],
            [['belong', 'tablename', 'keyname'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'content' => 'Content',
            'type' => 'Type',
            'belong' => 'Belong',
            'tablename' => 'Tablename',
            'keyname' => 'Keyname',
        ];
    }
}
