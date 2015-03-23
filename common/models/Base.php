<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class Base extends ActiveRecord
{
    public function behaviors()
    {
        return [
            /*TimestampBehavior::className(),*/
        ];
    }

    public function rules()
    {
        return [
        ];
    }

    public static function findByShopifyId($id)
    {
        return static::findOne(['shopify_id' => $id]);
    }
    
    public static function getByParams($data)
    {
        return static::findOne($data);
    }
    
    public static function findByStoreName($storeName)
    {
        return static::findOne(['store_name' => $storeName]);
    }
    
    public static function getByName($name)
    {
        return static::findOne(['title' => $name]);
    }
    
    public function getId()
    {
        return $this->getPrimaryKey();
    }
}
