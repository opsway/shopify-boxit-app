<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use phpish\shopify;
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


        $shopify = shopify\client(
            $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $appSettings['api_key'], $userSettings['access_token']
        );

        $hooks = $shopify('GET /admin/webhooks.json');
        foreach($hooks as $hook)
        {
            if($hook['topic'] == 'app/uninstalled')
                $shopify('DELETE /admin/webhooks/' . $hook['id'] . '.json');
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

    /**
     * @return bool
     * TODO: refactor
     */
    public function actionUninstalled()
    {
        Yii::info('$result', 'info');
        $appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
        $userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);

        if (!empty($userSettings)){

            $shopify = shopify\client(
                $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $appSettings['api_key'], $userSettings['access_token']
            );

            try {

                $result = $shopify('GET /admin/themes.json',['role' => 'main']);

                if (!$result || empty($result)){

                    // loggin bad themes list
                    \Yii::error('Bad themes list for user '.$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'].' with access_token '.$userSettings['access_token'], 'ShopifyApp/UnInstalling');
                    \Yii::error($result, 'ShopifyApp/UnInstalling');

                    throw new \yii\base\UserException('Bad themes list on uninstalling App');

                }

            } catch (\yii\base\UserException $e){

                // rethrow excpetion
                throw new \yii\base\UserException($e->getMessage());

            } catch (\Exception $e){

                \Yii::error('New exception for user '.$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'].' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
            Yii::info($result, 'info');

            /*$cartHtml = str_replace('"','\'',$userSettings['old_cart']);
            $cartHtml = str_replace("\n","\\n",$cartHtml);
            $cartHtml = str_replace("\t","\\t",$cartHtml);
            $cartHtml = str_replace("\r","\\r",$cartHtml);*/

            /*
             * get concrete cart template
             */
            try {

                $asset = $shopify('GET /admin/themes/' . $result[0]['id'] . '/assets.json', ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

                if (!$asset || empty($asset)){

                    // loggin bad cart template
                    \Yii::error('Cannot find cart template on user '.$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'].' with access_token '.$userSettings['access_token'], 'ShopifyApp/UnInstalling');
                    \Yii::error($asset, 'ShopifyApp/Installing');

                    throw new \yii\base\UserException('Cannot find cart template to apply App changes');

                }

            } catch (\yii\base\UserException $e){

                // rethrow excpetion
                throw new \yii\base\UserException($e->getMessage());

            } catch (\Exception $e){

                \Yii::error('New exception for user '.$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'].' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            $asset['value'] = preg_replace('#<!-- BOXIT-APP -->.*?<!-- END BOXIT-APP -->#is', '', $asset['value']);

            $shopify('PUT /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'templates/cart.liquid',
                    'value'	=>	$asset['value']
                ]
            ]);

            $shopify('DELETE /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'snippets/boxitapp.liquid'
                ]
            ]);

            $shopify('DELETE /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'assets/boxitapp.jquery.js'
                ]
            ]);

            $shopify('DELETE /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'assets/boxitapp.bootstrap.js'
                ]
            ]);

            $hooks = $shopify('GET /admin/webhooks.json');
            foreach($hooks as $hook)
            {
                if($hook['topic'] == 'app/uninstalled')
                    $shopify('DELETE /admin/webhooks/' . $hook['id'] . '.json');
            }

            if(is_null($userSettings))
                return false;
            $userSettings->delete();
        }
    }

}
