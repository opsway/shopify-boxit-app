<?php

use yii\db\Schema;
use yii\db\Migration;

class m150805_100402_update_profile_settings extends Migration
{
    public function up()
    {
        $this->addColumn('user_settings', 'boxit_api_key', Schema::TYPE_STRING . '(255) NOT NULL');
        $this->addColumn('user_settings', 'shopandcollect_api_key', Schema::TYPE_STRING . '(255) NOT NULL');
    }

    public function down()
    {
        echo "m150805_100402_update_profile_settings cannot be reverted.\n";

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
