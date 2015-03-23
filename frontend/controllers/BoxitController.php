<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use common\models\Usersettings;
use common\models\Appsettings;

/**
 * Site controller
 */
class BoxitController extends Controller
{

	public $enableCsrfValidation = false;
	
    public function behaviors()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
			'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
	
	public function actionIndex()
	{
		$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] = 'stder.myshopify.com';
		$appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
		$userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);
		
		var_dump($userSettings['access_token']);die;
		$shopify = shopify_api\client(
				$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $userSettings['access_token'], $appSettings['api_key'], $appSettings['shared_secret']
		);		
		
		$result = $shopify('GET','/admin/themes.json',['role' => 'main']);
	}
	
	public function actionCallback()
	{
		Yii::info('asd', 'info');
	}

}
?>