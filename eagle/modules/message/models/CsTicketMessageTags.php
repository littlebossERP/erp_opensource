<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_ticket_message_tags".
 *
 * @property integer $cs_ticket_tag_id
 * @property string $cs_ticket_id
 * @property integer $tag_id
 */
class CsTicketMessageTags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_ticket_message_tags';
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
            [['cs_ticket_id', 'tag_id'], 'required'],
            [['tag_id'], 'integer'],
            [['cs_ticket_id'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cs_ticket_tag_id' => 'Cs Ticket Tag ID',
            'cs_ticket_id' => 'Cs Ticket ID',
            'tag_id' => 'Tag ID',
        ];
    }
}
