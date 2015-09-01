<?php
namespace frontend\controllers;

use Yii;
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

    public function actionUpdateorder(){

        $request = Yii::$app->request;

        $shop = preg_replace('#^https?://(.*)$#', '$1', $request->post('shop'));

        $session = $request->post('session');
        $order_id = $request->post('order_id');

        $error = null;
        $status = false;

        // if user dont have a session - something wrong
        if (!$session){

            $error = 'Session not exists';

        } elseif (!$order_id){

            $error = 'Order id not exists';

        } else {

            $cart = Usercart::getByParams(['store_name' => $shop, 'session' => $session, 'is_complete' => 0]);

            if (!is_null($cart)){

                $cart->date_order = date('Y-m-d H:i:s');
                $cart->is_complete = 1;
                $cart->order_id = $request->post('order_id');
                $cart->save();

                $status = true;
            } else {
                $error = 'No cart found';
            }

        }

        echo json_encode(array('error' => $error, 'status' => $status));

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

        if ($cart){
            $data = array(
                'locker_id' => $cart->locker_id,
                //'email' => $cart->email,
                'phone' => $cart->phone,
                'type' => $cart->type,
            );
        } else {
            $data = array();
        }

        // get info about possible APIs
        $userSettings = Usersettings::getByParams(['store_name' => $shop]);
        $data['api_exists'] =
        $data['app_settings'] =
            array();
        if ($userSettings){
            $data['api_exists']['boxit'] = trim($userSettings->boxit_api_key) != '' ? true : false;
            $data['api_exists']['shopandcollect'] = trim($userSettings->shopandcollect_api_key) != '' ? true : false;
            $data['app_settings']['checkout_button_id'] = trim($userSettings->checkout_button_id) != '' ? $userSettings->checkout_button_id : '';
        }

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

        /**
         * @var $shopifyModule \common\components\ShopifyApp
         */
        $shopifyModule = \Yii::$app->get('ShopifyApp');

        /**
         * @var $ShopifyAPI \common\components\ShopifyAPI
         */
        $ShopifyAPI = \Yii::$app->get('ShopifyAPI');
        $ShopifyAPI->handleRequest();

        // check request from shopify server
        $data = file_get_contents('php://input');
        $verified = $this->_verifyWebhook($data, $ShopifyAPI->getRequestVerifyCode()/*$_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256']*/);

        \Yii::error('Uninstalling app from '.$ShopifyAPI->getRequestShop().' ...');

        if ($verified){

            $appSettings = $shopifyModule->getAppSettings();
            $userSettings = Usersettings::getByParams(['store_name' => $ShopifyAPI->getRequestShop()]);

            if (!empty($userSettings)){

                // send API call to the BoxIt
                $ShopifyAPI->activateClient($ShopifyAPI->getRequestShop(), $appSettings['api_key'], $userSettings['access_token']);

                // uninstall templates
                try {
                    $shopifyModule->uninstallTemplates($ShopifyAPI);
                } catch (\Exception $e){
                    \Yii::error('New exception on '.$ShopifyAPI->getRequestShop().' with '.$userSettings['access_token'].': '.$e->getMessage());
                }

                // uninstall carrier services
                try {
                    $shopifyModule->uninstallCarrierServices($ShopifyAPI);
                } catch (\Exception $e){
                    \Yii::error('New exception on '.$ShopifyAPI->getRequestShop().' with '.$userSettings['access_token'].': '.$e->getMessage());
                }

                // uninstall webhooks
                $shopifyModule->uninstallWebhooks($ShopifyAPI);

                if(is_null($userSettings))
                    return false;

                $userSettings->delete();

                \Yii::error('Uninstalled successfull '.$ShopifyAPI->getRequestShop().'!');

                unset($ShopifyAPI);
            }
        }

        $this->_responseOk();
    }


}
