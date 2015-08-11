<?php

use yii\db\Schema;
use yii\db\Migration;

class m150811_093100_add_users_hash extends Migration
{
    public function up()
    {
        $this->addColumn('user_settings', 'access_token_hash', Schema::TYPE_STRING . '(255) NOT NULL');
        $this->createIndex('hash_store_name', 'user_settings', array('access_token_hash', 'store_name'));
    }

    public function down()
    {
        echo "m150811_093100_add_users_hash cannot be reverted.\n";

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
