<?php

namespace eagle\models\listing;

use Yii;

/**
 * This is the model class for table "aliexpress_group_info".
 *
 * @property integer $group_id
 * @property string $group_name
 * @property integer $parent_group_id
 * @property string $selleruserid
 */
class AliexpressGroupInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_group_info';
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
            [['group_id', 'group_name', 'selleruserid'], 'required'],
            [['group_id', 'parent_group_id'], 'integer'],
            [['group_name', 'selleruserid'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'parent_group_id' => 'Parent Group ID',
            'selleruserid' => 'Selleruserid',
        ];
    }
}
