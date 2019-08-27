<?php

namespace eagle\models\tracking;

use Yii;

/**
 * This is the model class for table "lt_tracking".
 *
 * @property integer $id
 * @property string $seller_id
 * @property string $order_id
 * @property string $track_no
 * @property string $status
 * @property string $state
 * @property string $source
 * @property string $platform
 * @property integer $parcel_type
 * @property integer $carrier_type
 * @property string $is_active
 * @property string $batch_no
 * @property string $create_time
 * @property string $update_time
 * @property string $from_nation
 * @property string $to_nation
 * @property string $mark_handled
 * @property string $notified_seller
 * @property string $notified_buyer
 * @property string $shipping_notified
 * @property string $pending_fetch_notified
 * @property string $delivery_failed_notified
 * @property string $rejected_notified
 * @property string $received_notified
 * @property string $ship_by
 * @property string $delivery_fee
 * @property string $ship_out_date
 * @property integer $total_days
 * @property string $all_event
 * @property string $first_event_date
 * @property string $last_event_date
 * @property integer $stay_days
 * @property string $msg_sent_error
 * @property string $from_lang
 * @property string $to_lang
 * @property string $first_track_result_date
 * @property string $remark
 * @property string $addi_info
 * @property string $ignored_time
 */
class Tracking extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_tracking';
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
            [['parcel_type', 'carrier_type', 'total_days', 'stay_days'], 'integer'],
            [['create_time', 'update_time', 'ship_out_date', 'first_event_date', 'last_event_date', 'first_track_result_date', 'ignored_time'], 'safe'],
            [['delivery_fee'], 'number'],
            [['all_event', 'remark', 'addi_info'], 'string'],
            [['seller_id', 'order_id', 'track_no', 'batch_no', 'ship_by'], 'string', 'max' => 100],
            [['status', 'state', 'platform'], 'string', 'max' => 30],
            [['source', 'is_active', 'mark_handled', 'notified_seller', 'notified_buyer', 'shipping_notified', 'pending_fetch_notified', 'delivery_failed_notified', 'rejected_notified', 'received_notified', 'msg_sent_error'], 'string', 'max' => 1],
            [['from_nation', 'to_nation'], 'string', 'max' => 2],
            [['from_lang', 'to_lang'], 'string', 'max' => 50],
            [['track_no', 'order_id'], 'unique', 'targetAttribute' => ['track_no', 'order_id'], 'message' => 'The combination of Order ID and Track No has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'seller_id' => 'Seller ID',
            'order_id' => 'Order ID',
            'track_no' => 'Track No',
            'status' => 'Status',
            'state' => 'State',
            'source' => 'Source',
            'platform' => 'Platform',
            'parcel_type' => 'Parcel Type',
            'carrier_type' => 'Carrier Type',
            'is_active' => 'Is Active',
            'batch_no' => 'Batch No',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'from_nation' => 'From Nation',
            'to_nation' => 'To Nation',
            'mark_handled' => 'Mark Handled',
            'notified_seller' => 'Notified Seller',
            'notified_buyer' => 'Notified Buyer',
            'shipping_notified' => 'Shipping Notified',
            'pending_fetch_notified' => 'Pending Fetch Notified',
            'delivery_failed_notified' => 'Delivery Failed Notified',
            'rejected_notified' => 'Rejected Notified',
            'received_notified' => 'Received Notified',
            'ship_by' => 'Ship By',
            'delivery_fee' => 'Delivery Fee',
            'ship_out_date' => 'Ship Out Date',
            'total_days' => 'Total Days',
            'all_event' => 'All Event',
            'first_event_date' => 'First Event Date',
            'last_event_date' => 'Last Event Date',
            'stay_days' => 'Stay Days',
            'msg_sent_error' => 'Msg Sent Error',
            'from_lang' => 'From Lang',
            'to_lang' => 'To Lang',
            'first_track_result_date' => 'First Track Result Date',
            'remark' => 'Remark',
            'addi_info' => 'Addi Info',
            'ignored_time' => 'Ignored Time',
        ];
    }
}
