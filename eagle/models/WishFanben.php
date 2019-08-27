<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%wish_fanben}}".
 *
 * @property integer $id
 * @property string $brand
 * @property integer $type
 * @property string $status
 * @property integer $lb_status
 * @property integer $site_id
 * @property string $parent_sku
 * @property integer $variance_count
 * @property string $name
 * @property string $tags
 * @property string $upc
 * @property string $landing_page_url
 * @property string $internal_sku
 * @property string $msrp
 * @property string $shipping_time
 * @property string $main_image
 * @property string $extra_image_1
 * @property string $extra_image_2
 * @property string $extra_image_3
 * @property string $extra_image_4
 * @property string $extra_image_5
 * @property string $extra_image_6
 * @property string $extra_image_7
 * @property string $extra_image_8
 * @property string $extra_image_9
 * @property string $extra_image_10
 * @property string $create_time
 * @property string $update_time
 * @property string $description
 * @property integer $capture_user_id
 * @property string $wish_product_id
 * @property string $error_message
 * @property string $addinfo
 * @property string $price
 * @property string $shipping
 */
class WishFanben extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_fanben}}';
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
            [['type', 'lb_status', 'site_id', 'variance_count', 'capture_user_id','is_enable'], 'integer'],
            [['status', 'name', 'tags', 'main_image', 'description', 'capture_user_id'], 'required'],
            [['site_id', 'variance_count', 'capture_user_id','type','lb_status','inventory','number_saves','number_sold','is_enable'], 'integer'],
            [['landing_page_url', 'main_image', 'extra_image_1', 'extra_image_2', 'extra_image_3', 'extra_image_4', 'extra_image_5', 'extra_image_6', 'extra_image_7', 'extra_image_8', 'extra_image_9', 'extra_image_10', 'description', 'error_message'], 'string'],
            [['msrp', 'price', 'shipping'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['brand', 'parent_sku', 'upc', 'internal_sku', 'wish_product_id'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['name', 'tags', 'addinfo'], 'string', 'max' => 255],
            [['shipping_time'], 'string', 'max' => 250],
            [['site_id', 'parent_sku'], 'unique', 'targetAttribute' => ['site_id', 'parent_sku'], 'message' => 'The combination of 帐号编号 and 如果是变参的，填入parent_sku .has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand' => '品牌',
            'type' => '商品类型 1在线商品 2小老板刊登商品',
            'status' => '状态：error,editing, uploading, complete',
            'lb_status' => '小老板刊登商品状态 1待发布 2平台审核中 3发布成功 4发布失败 5标记删除',
            'site_id' => '帐号编号',
            'parent_sku' => '如果是变参的，填入parent_sku',
            'variance_count' => '该范本包含的变参子产品数量',
            'name' => '产品名称',
            'tags' => 'Tags',
            'upc' => 'UPC,EAN,barcode',
            'landing_page_url' => '自营网店的URL',
            'internal_sku' => '小老板内部商品SKU',
            'msrp' => '原售价，被划掉的',
            'shipping_time' => 'just the estimated days,e.g. \"3-7\"',
            'main_image' => '主图片，必填',
            'extra_image_1' => 'Extra Image 1',
            'extra_image_2' => 'Extra Image 2',
            'extra_image_3' => 'Extra Image 3',
            'extra_image_4' => 'Extra Image 4',
            'extra_image_5' => 'Extra Image 5',
            'extra_image_6' => 'Extra Image 6',
            'extra_image_7' => 'Extra Image 7',
            'extra_image_8' => 'Extra Image 8',
            'extra_image_9' => 'Extra Image 9',
            'extra_image_10' => 'Extra Image 10',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'description' => 'Description',
            'capture_user_id' => '操作者ID',
            'wish_product_id' => 'Wish 平台的product id，用于update一个product',
            'error_message' => 'Wish返回的错误信息',
            'addinfo' => '备用信息，json格式',
            'price' => '商品价格',
            'shipping' => '运费美元',
            'inventory' => 'Inventory',
            'number_saves' => '商品WISH平台收藏数',
            'number_sold' => '商品WISH平台销售数',
            'is_enable' => '变种商品是否存在下架商品 1不存在 2存在',
        ];
    }
}
