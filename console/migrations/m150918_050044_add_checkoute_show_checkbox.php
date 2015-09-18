<?php

use yii\db\Schema;
use yii\db\Migration;

class m150918_050044_add_checkoute_show_checkbox extends Migration
{
    public function up()
    {
        $this->addColumn('user_settings', 'is_show_on_checkout', Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 1');
    }

    public function down()
    {
        echo "m150918_050044_add_checkoute_show_checkbox cannot be reverted.\n";

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
