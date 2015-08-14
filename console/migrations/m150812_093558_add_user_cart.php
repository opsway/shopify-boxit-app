<?php

use yii\db\Schema;
use yii\db\Migration;

class m150812_093558_add_user_cart extends Migration
{
    public function up()
    {

        $this->createTable('user_cart', array(
            'id' => Schema::TYPE_PK,
            'locker_id' => Schema::TYPE_STRING . ' NOT NULL',
            'store_name' => Schema::TYPE_STRING . '(128) NOT NULL',
            'phone'	=>	Schema::TYPE_STRING . '(20) NOT NULL',
            'customer_id'	=>	Schema::TYPE_STRING . '(128) NOT NULL',
            'type'	=>	Schema::TYPE_STRING . '(128) NOT NULL',
            'address'	=>	Schema::TYPE_TEXT . ' NOT NULL',
            'session'	=>	Schema::TYPE_STRING . '(128) NOT NULL',
            'date_add'	=>	Schema::TYPE_TIMESTAMP . ' NOT NULL',
            'date_fulfilled'	=>	Schema::TYPE_TIMESTAMP . ' NULL',
            'date_order'	=>	Schema::TYPE_TIMESTAMP . ' NULL',
            'order_id'  =>  Schema::TYPE_STRING . '(128) NOT NULL',
            'is_complete' => Schema::TYPE_INTEGER . ' NOT NULL',
            'is_fulfilled' => Schema::TYPE_INTEGER . ' NOT NULL',
        ),NULL,true);

        $this->createIndex('cart_by_user', 'user_cart', array(
            'store_name', 'session', 'phone'
        ));

        $this->createIndex('complete_phone', 'user_cart', array(
            'is_complete', 'phone', 'store_name'
        ));

        $this->createIndex('order_fulfilled', 'user_cart', array(
            'is_fulfilled', 'order_id', 'store_name'
        ));


    }

    public function down()
    {
        echo "m150812_093558_add_user_cart cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
