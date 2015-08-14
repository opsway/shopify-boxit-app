<?php

use yii\db\Schema;
use yii\db\Migration;

class m150305_154036_init extends Migration
{
    public function up()
    {
		$this->createTable('app_settings', array(
            'id' => Schema::TYPE_PK,
            'api_key' => Schema::TYPE_STRING . '(300) DEFAULT NULL',
            'redirect_url' => Schema::TYPE_STRING . '(300) DEFAULT NULL',
            'permissions' => Schema::TYPE_STRING,
            'shared_secret' => Schema::TYPE_STRING . '(300) NOT NULL'
        ),NULL,true);
        
        /*$this->insert('app_settings',array(
            'api_key'   => '031313979e237b705f70bfb8702ed814',
            'redirect_url'  =>  'http://boxit.view-source.ru/frontend/web/index.php?r=site/callback',
            'permissions'   =>  '["read_content","write_content","read_products","write_products","read_customers","write_customers","read_orders","read_shipping","write_shipping","write_orders"]',
            'shared_secret' =>  'e02fa16a4d14c9166549101eadf6e47a'
        ));*/

        $this->insert('app_settings',array(
            'api_key'   => '703f67cde06b037199b92e1a49a0fd28',
            'redirect_url'  =>  'https://shopify-boxit-app.dev/index.php?r=site/callback',
            'permissions'   =>  '["read_themes","write_themes","read_shipping","write_shipping","read_script_tags","write_script_tags", "read_orders", "write_orders", "read_fulfillments", "write_fulfillments"]',
            'shared_secret' =>  'e02fa16a4d14c9166549101eadf6e47a'
        ));

        
        $this->createTable('user_settings', array(
            'id' => Schema::TYPE_PK,
            'access_token' => Schema::TYPE_STRING . ' NOT NULL',
            'store_name' => Schema::TYPE_STRING . '(300) NOT NULL',
			'old_cart'	=>	Schema::TYPE_TEXT,
        ),NULL,true);
    }

    public function down()
    {
        echo "m150305_154036_init cannot be reverted.\n";

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
