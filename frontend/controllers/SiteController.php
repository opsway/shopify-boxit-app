<?php
namespace frontend\controllers;

use Yii;
use phpish\shopify;
use common\models\Usersettings;

/**
 * Site controller
 */
class SiteController extends ShopifyController
{

    /**
     * Base action for controller
     */
    public function actionIndex()
    {
        //\Yii::$app->view->renderFile('@app/views/shopify_frontend/cart.php', ['test' => 'test']);
        //throw new \Exception('You are not authorized');
        echo 'You are not authorized';
    }

    /**
     * Callback action for Shopify API
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function actionCallback()
    {

        /**
         * @var $shopifyModule \common\components\ShopifyApp
         */
        $shopifyModule = \Yii::$app->get('ShopifyApp');

        $settings = $shopifyModule->getAppSettings();

        $get = Yii::$app->request->get();
        $shop = isset($get['shop']) ? $get['shop'] : null;

        /**
         * @var $ShopifyAPI \common\components\ShopifyAPI
         */
        $ShopifyAPI = \Yii::$app->get('ShopifyAPI');

        if(isset($get['code']))
        {
            $access_token = $ShopifyAPI->requestAccessToken(
                $shop,
                $settings['api_key'],
                $settings['shared_secret'],
                $get['code']
            );

            $ShopifyAPI->
                activateClient($shop, $settings['api_key'], $access_token);

            // prepare webhooks for all
            $shopifyModule->uninstallWebHooks($ShopifyAPI);
            $shopifyModule->installWebHooks($ShopifyAPI);

            // install templates
            $old_cart = $shopifyModule->installTemplates($ShopifyAPI);

            // now check if user always in our database
            $userSettings = Usersettings::getByParams(['store_name' => $shop]);

            if ($userSettings){
                $userSettings->access_token = $access_token;
                $userSettings->old_cart = $old_cart;
                $userSettings->access_token_hash = md5($access_token . $shop . \Yii::$app->params['store_hash_salt']);
            } else {
                $userSettings = new Usersettings();
                $userSettings->access_token = $access_token;
                $userSettings->store_name = $shop;
                $userSettings->old_cart = $old_cart;
                $userSettings->access_token_hash = md5($access_token . $shop . \Yii::$app->params['store_hash_salt']);
            }
            $userSettings->save();

            // install carrier services
            if (trim($userSettings->boxit_api_key) != '' || trim($userSettings->shopandcollect_api_key) != ''){
                $shopifyModule->installCarrierServices($ShopifyAPI);
            }

            $this->redirect('https://' . $shop . '/admin/apps', 302);

        } else {

            $userSettings = Usersettings::getByParams(['store_name' => $shop]);

            // make additional call, maybe we lost API key
            // For example when we try to install the app from App store (https://apps.shopify.com/boxit-connector).
            if (!empty($userSettings) &&
                $ShopifyAPI->activateClient($get['shop'], $settings['api_key'], $userSettings['access_token']) &&
                ($is_access_token_valid = $shopifyModule->isAccessTokenValid($ShopifyAPI))) {

                $ShopifyAPI->activateClient($get['shop'], $settings['api_key'], $userSettings['access_token']);

                echo \Yii::$app->view->renderFile('@app/views/shopify_backend/settings.php', [
                    'app_url' => (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web'),
                    'document_domain' => \Yii::$app->params['document.domain'],
                    'store_settings' => $settings,
                    'user_settings' => $userSettings,
                    'is_templates_installed' => $shopifyModule->isTemplateInstalled($ShopifyAPI),
                ]);

            } else {

                // Get the permission url.
                $permission_url = $ShopifyAPI->getAuthorizationUrl(
                    $shop,
                    $settings['api_key'],
                    json_decode($settings['permissions'], true),
                    (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=site/callback'
                );

                // Redirect to the permission url.
                header('Location: ' . $permission_url);
                exit;

            }

        }


    }

    public function actionReadorder(){

        $command = Yii::$app->db->createCommand('SELECT * FROM app_settings');
        $settings = $command->queryOne();

        $request = Yii::$app->request;

        $user_settings = Usersettings::getByParams(['store_name' => $request->get('shop')]);

        try {
            $shopify = shopify\client(
                $request->get('shop'), $settings['api_key'], $user_settings['access_token']
            );
        } catch (\Exception $e){
            var_dump($e); die();
        }

        try {

            $order = $shopify('GET /admin/webhooks.json');

            echo "<pre>", var_dump($order); die();

        } catch (\yii\base\UserException $e){

            // rethrow excpetion
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error('New exception for user '.$request->get('shop').' with access_token '.$user_settings['access_token'].': '.$e->getMessage(), 'ShopifyApp/GetOrder');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }


    }


    /**
     * backend method to manually change app installation
     */
    public function actionUpdateinstall(){

        $request = Yii::$app->request;

        $user_settings = Usersettings::getByParams(['store_name' => $request->post('store'), 'access_token_hash' => $request->post('hash')]);

        $result = array(
            'success'	=>	false,
            'errors' => array()
        );

        if(is_null($user_settings))
        {
            $result['errors'][] = 'Store not found';
            sleep(3);

        } elseif ($request->post('method') && in_array((string)$request->post('method'), array('install', 'uninstall')) ) {

            /**
             * @var $shopifyModule \common\components\ShopifyApp
             */
            $shopifyModule = \Yii::$app->get('ShopifyApp');

            $settings = $shopifyModule->getAppSettings();

            /**
             * @var $ShopifyAPI \common\components\ShopifyAPI
             */
            $ShopifyAPI = \Yii::$app->get('ShopifyAPI');

            $ShopifyAPI->
                activateClient($request->post('store'), $settings['api_key'], $user_settings['access_token']);

            \Yii::error('Starting updateinstall for store '.$request->post('store').': '.$request->post('method').'...');

            switch ((string)$request->post('method')){

                case 'install':

                    // install webhooks
                    try {
                        $shopifyModule->installWebHooks($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Webhooks install error: '.$e->getMessage());
                    }

                    // install carrier services
                    try{
                        $shopifyModule->installCarrierServices($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Carrier install error: '.$e->getMessage());
                    }

                    // install templates
                    try{
                        $shopifyModule->installTemplates($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Templates install error: '.$e->getMessage());
                    }

                    $user_settings->is_uninstalled = 0;
                    $user_settings->save();

                    break;

                case 'uninstall':

                    // uninstall templates
                    try{
                        $shopifyModule->uninstallTemplates($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Templates uninstall error: '.$e->getMessage());
                    }

                    // uninstall carrier services
                    try{
                        $shopifyModule->uninstallCarrierServices($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Carriers uninstall error: '.$e->getMessage());
                    }

                    // uninstall webhooks
                    try{
                        $shopifyModule->uninstallWebhooks($ShopifyAPI);
                    } catch (\Exception $e){
                        \Yii::error('Webhooks uninstall error: '.$e->getMessage());
                    }

                    $user_settings->is_uninstalled = 1;
                    $user_settings->save();

                    break;

            }

            \Yii::error('Success updateinstall for store '.$request->post('store').': '.$request->post('method').'...');

            $result['success'] = true;
        }
        echo json_encode($result);

    }

    /**
     * action reset all hooks
     */
    public function actionUpdatehooks(){

        $request = Yii::$app->request;

        $user_settings = Usersettings::getByParams(['store_name' => $request->post('store'), 'access_token_hash' => $request->post('hash')]);

        $result = array(
            'success'	=>	false,
            'errors' => array()
        );

        if(is_null($user_settings))
        {
            $result['errors'][] = 'Store not found';
            sleep(3);

        } else {

            \Yii::error('Starting reinstall hooks for store '.$request->post('store').'...');

            /**
             * @var $shopifyModule \common\components\ShopifyApp
             */
            $shopifyModule = \Yii::$app->get('ShopifyApp');

            $settings = $shopifyModule->getAppSettings();

            /**
             * @var $ShopifyAPI \common\components\ShopifyAPI
             */
            $ShopifyAPI = \Yii::$app->get('ShopifyAPI');

            $ShopifyAPI->
                activateClient($request->post('store'), $settings['api_key'], $user_settings['access_token']);

            // uninstall webhooks
            $shopifyModule->uninstallWebhooks($ShopifyAPI);

            // install webhooks
            $shopifyModule->installWebHooks($ShopifyAPI);

            \Yii::error('Success reinstall hooks for store '.$request->post('store').'...');

            $result['success'] = true;
        }
        echo json_encode($result);

    }

    /**
     * backend action to save config settings
     */
    public function actionSaveconfig()
    {

        $request = Yii::$app->request;

        $settings = Usersettings::getByParams(['store_name' => $request->post('store'), 'access_token_hash' => $request->post('hash')]);

        $result = array(
            'success'	=>	false,
            'errors' => array()
        );

        if(is_null($settings))
        {
            $result['errors'][] = 'Store not found';
            sleep(3);

        } else {

            foreach($request->post('formData') as $input)
            {
                if($input['name'] == 'boxit_api_key')
                    $settings->boxit_api_key = $input['value'];
                if($input['name'] == 'shopandcollect_api_key')
                    $settings->shopandcollect_api_key = $input['value'];
                if($input['name'] == 'boxit_carrier_cost')
                    $settings->boxit_carrier_cost = $input['value'];
                if($input['name'] == 'shopandcollect_carrier_cost')
                    $settings->shopandcollect_carrier_cost = $input['value'];
                if($input['name'] == 'checkout_button_id')
                    $settings->checkout_button_id = $input['value'];

            }
            $settings->save();

            $result['success'] = true;

            // additionaly checking for exists Boxit/Shopandcollect methods
            /**
             * @var $shopifyModule \common\components\ShopifyApp
             */
            $shopifyModule = \Yii::$app->get('ShopifyApp');

            /**
             * @var $ShopifyAPI \common\components\ShopifyAPI
             */
            $ShopifyAPI = \Yii::$app->get('ShopifyAPI');
            $ShopifyAPI->handleRequest();

            // send API call to the BoxIt
            $appSettings = $shopifyModule->getAppSettings();
            $ShopifyAPI->activateClient($ShopifyAPI->getRequestShop(), $appSettings['api_key'], $settings->access_token);

            // turn off delivery service
            $shopifyModule->uninstallCarrierServices($ShopifyAPI);

            if (trim($settings->boxit_api_key) != '' || trim($settings->shopandcollect_api_key) != ''){
                // turn on delivery service
                $shopifyModule->uninstallCarrierServices($ShopifyAPI);
            }

            unset($ShopifyAPI);
            unset($shopifyModule);
            unset($appSettings);

        }
        echo json_encode($result);
    }

}
