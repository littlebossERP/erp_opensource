<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "label_tip".
 *
 * @property integer $id
 * @property string $tip_key
 * @property string $tip
 */
class LabelTip extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'label_tip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tip_key', 'tip'], 'required'],
            [['tip'], 'string'],
            [['tip_key'], 'string', 'max' => 100],
            [['tip_key'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tip_key' => 'Tip Key',
            'tip' => 'Tip',
        ];
    }
}
