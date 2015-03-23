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
class AppController extends Controller
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
		echo __FUNCTION__;
	}
	
	public function actionUninstalled()
	{
		$appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
		$userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);
		
		Yii::info($_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], 'info');
		
		$shopify = shopify_api\client(
				$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $userSettings['access_token'], $appSettings['api_key'], $appSettings['shared_secret']
		);		
		
		$result = $shopify('GET','/admin/themes.json',['role' => 'main']);
		
		$cartHtml = str_replace('"','\'',$userSettings['old_cart']);
		$cartHtml = str_replace("\n","\\n",$cartHtml);
		$cartHtml = str_replace("\t","\\t",$cartHtml);
		$cartHtml = str_replace("\r","\\r",$cartHtml);
		
		$shopify('PUT','/admin/themes/' . $result[0]['id'] . '/assets.json',[
			'asset'	=>	[
				'key'	=>	'templates/cart.liquid',
				'value'	=>	$cartHtml
			]
		]);
		
		$shopify('DELETE','/admin/themes/' . $result[0]['id'] . '/assets.json',[
			'asset'	=>	[
				'key'	=>	'snippets/boxit.liquid'
			]
		]);		
		
		if(is_null($userSettings))
			return false;
		$userSettings->delete();
	}

}
?>