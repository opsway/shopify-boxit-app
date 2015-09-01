<?php

use yii\db\Schema;
use yii\db\Migration;

class m150901_143332_update_backend_settings extends Migration
{
    public function up()
    {
        $this->addColumn('user_settings', 'checkout_button_id', Schema::TYPE_STRING . ' NOT NULL');
    }

    public function down()
    {
        echo "m150901_143332_update_backend_settings cannot be reverted.\n";

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
