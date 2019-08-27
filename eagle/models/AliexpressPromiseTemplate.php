<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_category".
 *
 * @property integer $cateid
 * @property integer $pid
 * @property integer $level
 * @property string $name_zh
 * @property string $name_en
 * @property string $isleaf
 * @property string $attribute
 * @property integer $created
 * @property integer $updated
 */
class AliexpressPromiseTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_promise_template';
    }
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
            [['templateid','selleruserid'], 'required'],
            [['uid', 'created', 'updated'], 'integer'],
            [['selleruserid','name'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'templateid' => 'TemplateId',
            'uid' => 'Uid',
            'selleruserid' => 'selleruserId',
            'name' => 'Name',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
