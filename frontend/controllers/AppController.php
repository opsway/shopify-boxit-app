<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use common\models\Usersettings;
use common\models\Usercart;
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
            $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] = "stder.myshopify.com";
		$appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
		$userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);
		
                
		$shopify = shopify_api\client(
				$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $userSettings['access_token'], $appSettings['api_key'], $appSettings['shared_secret']
		);		

                $hooks = $shopify('GET', '/admin/webhooks.json');
                foreach($hooks as $hook)
                {
                    if($hook['topic'] == 'app/uninstalled')
                        $shopify('DELETE', '/admin/webhooks/' . $hook['id'] . '.json');
                }
	}
	
        public function actionSave()
        {
            $shop = str_replace('http://','',$_POST['shop']);
            $shop = str_replace('https://','',$shop);
            $cart = Usercart::getByParams(['shop' => $shop]);
            if(is_null($cart))
                $cart = new Usercart();
            $cart->locker_id = $_POST['locker_id'];
            $cart->shop = $shop;
            $cart->phone = $_POST['phone'];
            $cart->type = $_POST['type'];
            $cart->address = $_POST['address'];
            $cart->save();
        }
        
	public function actionCart()
	{
		$shop = str_replace('http://','',$_GET['shop']);
		$shop = str_replace('https://','',$shop);
		$cart = Usercart::getByParams(['shop' => $shop]);
		$data = array(
			'locker_id' => $cart->locker_id,
			'email' => $cart->email,
			'phone' => $cart->phone,
			'type' => $cart->type,
		);
		
		echo json_encode($data);
	}
        
	public function actionUninstalled()
	{
        Yii::info('$result', 'info');
		$appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
		$userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);
		
                
		$shopify = shopify_api\client(
				$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $userSettings['access_token'], $appSettings['api_key'], $appSettings['shared_secret']
		);		
		
		$result = $shopify('GET','/admin/themes.json',['role' => 'main']);
                Yii::info($result, 'info');

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

                $hooks = $shopify('GET', '/admin/webhooks.json');
                foreach($hooks as $hook)
                {
                    if($hook['topic'] == 'app/uninstalled')
                        $shopify('DELETE', '/admin/webhooks/' . $hook['id'] . '.json');
                }
                
		if(is_null($userSettings))
			return false;
		$userSettings->delete();
	}

}
?>