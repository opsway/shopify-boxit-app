<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use phpish\shopify;
use common\models\Usersettings;
use common\models\Usercart;

/**
 * Site controller
 */
class AppController extends ShopifyController
{


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

    /**
     * method generates session id
     * @return string
     */
    protected function _generateSessionId(){

        Yii::$app->session->open();
        $session = Yii::$app->session->getId();
        Yii::$app->session->close();

        return $session;

    }

    public function actionSave()
    {
        $request = Yii::$app->request;

        $shop = preg_replace('#^https?://(.*)$#', '$1', $request->post('shop'));

        $session = $request->post('session');
        $session_created = false;

        if (!$session){

            $session = $this->_generateSessionId();
            $session_created = true;
        }

        $cart = Usercart::getByParams(['store_name' => $shop, 'session' => $session, 'is_complete' => 0]);

        if (is_null($cart)){
            $cart = new Usercart();
            $cart->session = $session;
            $cart->store_name = $shop;
        }

        $cart->locker_id = $request->post('locker_id');
        $cart->phone = $request->post('phone');
        $cart->type = $request->post('type');
        $cart->address = $request->post('address');
        $cart->save();

        echo json_encode($session_created ? array('session' => $session) : array());
    }

    public function actionCart()
    {
        $request = Yii::$app->request;

        $shop = preg_replace('#^https?://(.*)$#', '$1', $request->get('shop'));

        $session = $request->post('session');
        $session_created = false;

        if (!$session){

            $session = $this->_generateSessionId();
            $session_created = true;
        }

        $cart = Usercart::getByParams(['store_name' => $shop, 'session' => $session, 'is_complete' => 0]);
        $data = array(
            'locker_id' => $cart->locker_id,
            //'email' => $cart->email,
            'phone' => $cart->phone,
            'type' => $cart->type,
        );

        if ($session_created){
            $data['session'] = $session;
        }

        echo json_encode($data);
    }

    /**
     * method uninstall app
     * @return bool
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function actionUninstalled()
    {

        $request = Yii::$app->request;

        // check request from shopify server
        $data = file_get_contents('php://input');
        $verified = $this->_verifyWebhook($data, $request->getHeaders()->get('x-shopify-hmac-sha256')/*$_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256']*/);

        \Yii::error('Uninstalling app from '.$request->getHeaders()->get('x-shopify-shop-domain').' ...');

        if ($verified){

            $appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
            $userSettings = Usersettings::getByParams(['store_name' => $request->getHeaders()->get('x-shopify-shop-domain')]);

            if (!empty($userSettings)){

                $shopify = shopify\client(
                    $request->getHeaders()->get('x-shopify-shop-domain'), $appSettings['api_key'], $userSettings['access_token']
                );

                $result = null;

                try {

                    $result = $shopify('GET /admin/themes.json',['role' => 'main']);

                    if (!$result || empty($result)){

                        // loggin bad themes list
                        \Yii::error('Bad themes list for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'], 'ShopifyApp/UnInstalling');
                        \Yii::error($result, 'ShopifyApp/UnInstalling');

                        throw new \yii\base\UserException('Bad themes list on uninstalling App');

                    }

                } catch (\yii\base\UserException $e){

                    // rethrow excpetion
                    throw new \yii\base\UserException($e->getMessage());

                } catch (\Exception $e){

                    \Yii::error('New exception for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                    //throw new \Exception($e->getMessage(), $e->getCode(), $e);
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
                        \Yii::error('Cannot find cart template on user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'], 'ShopifyApp/UnInstalling');
                        \Yii::error($asset, 'ShopifyApp/Installing');

                        throw new \yii\base\UserException('Cannot find cart template to apply App changes');

                    }

                } catch (\yii\base\UserException $e){

                    // rethrow excpetion
                    throw new \yii\base\UserException($e->getMessage());

                } catch (\Exception $e){

                    \Yii::error('New exception for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                    //throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }


                // if we have access to assets
                if (isset($asset) && $asset['value'])
                    $asset['value'] = preg_replace('#<!-- BOXIT-APP -->.*?<!-- END BOXIT-APP -->#is', '', $asset['value']);

                // if we have access to themes
                if ($result){

                    try {


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

                    } catch (\yii\base\UserException $e){

                        // rethrow excpetion
                        throw new \yii\base\UserException($e->getMessage());

                    } catch (\Exception $e){

                        \Yii::error('New exception for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                        //throw new \Exception($e->getMessage(), $e->getCode(), $e);
                    }

                }

                // try to get webhooks
                try{

                    $hooks = $shopify('GET /admin/webhooks.json');

                } catch (\yii\base\UserException $e){

                    // rethrow excpetion
                    throw new \yii\base\UserException($e->getMessage());

                } catch (\Exception $e){

                    \Yii::error('New exception for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                    //throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }


                if (isset($hooks) && $hooks){
                    foreach($hooks as $hook)
                    {
                        \Yii::error($hook);
                        if ($hook['topic'] == 'orders/create' ||
                            $hook['topic'] == 'orders/updated' ||
                            $hook['topic'] == 'fulfillments/create' ||
                            $hook['topic'] == 'fulfillments/update' ||
                            $hook['topic'] == 'app/uninstalled'
                        ){
                            try {

                                $shopify('DELETE /admin/webhooks/' . $hook['id'] . '.json');

                            } catch (\yii\base\UserException $e){

                                // rethrow excpetion
                                throw new \yii\base\UserException($e->getMessage());

                            } catch (\Exception $e){

                                \Yii::error('New exception for user '.$request->getHeaders()->get('x-shopify-shop-domain').' with access_token '.$userSettings['access_token'].': '.$e->getMessage(), 'ShopifyApp/UnInstalling');
                                //throw new \Exception($e->getMessage(), $e->getCode(), $e);
                            }
                        }

                    }

                }

                if(is_null($userSettings))
                    return false;

                $userSettings->delete();

                \Yii::error('Uninstalled successfull '.$request->getHeaders()->get('x-shopify-shop-domain').'!');
            }
        }

        $this->_responseOk();
    }


}
