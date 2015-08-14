<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use phpish\shopify;
use common\models\Usersettings;

/**
 * Site controller
 */
class SiteController extends Controller
{

    public $enableCsrfValidation = false;
    /**
     * @inheritdoc
     */
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

    /**
     * Base action for controller
     * TODO: REFACTOR
     */
    public function actionIndex()
    {
        \Yii::$app->view->renderFile('@app/views/shopify_frontend/cart.php', ['test' => 'test']);
    }

    /**
     * Callback action for Shopify API
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function actionCallback()
    {

        $command = Yii::$app->db->createCommand('SELECT * FROM app_settings');
        $settings = $command->queryOne();
        $get = Yii::$app->request->get();
        $shop = isset($get['shop']) ? $get['shop'] : null;

        if(isset($get['code']))
        {
            $access_token = shopify\access_token(
                $shop, $settings['api_key'], $settings['shared_secret'], $get['code']
            );

            try {
                $shopify = shopify\client(
                    $shop, $settings['api_key'], $access_token
                );
            } catch (\Exception $e){
                var_dump($e); die();
            }

            $hooks = array(
                'app/uninstalled',
                'orders/create',
                'orders/updated',
                'fulfillments/create',
                'fulfillments/update',
            );

            foreach($hooks as $hook)
            {
                $arguments = array(
                    'webhook' => array(
                        'topic' => $hook,
                        'address' => (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=' . $hook,
                        'format' => "json"
                    )
                );
                try {
                    $shopify('POST /admin/webhooks.json', array(), $arguments);
                } catch (\Exception $e){
                    \Yii::error($e->getMessage(), 'ShopifyApp/Installing');
                }

            }

            /**
             * uncomment when you will know how much BoxIt get paid for delivery
             */
            /*$arguments = array(
                    'carrier_service'	=>	array(
                            'name' => 'Boxit',
                            'callback_url' => (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=boxit/carrier',
                            'format' => 'json',
                            'service_discovery' => true
                    )
            );

            $shopify('POST /admin/carrier_services.json', array(), $arguments); */

            /**
             * get only main themes from themes list and patching it
             */
            try {

                $result = $shopify('GET /admin/themes.json',['role' => 'main']);

                if (!$result || empty($result)){

                    // loggin bad themes list
                    \Yii::error('Bad themes list for user '.$shop.' with access_token '.$access_token, 'ShopifyApp/Installing');
                    \Yii::error($result, 'ShopifyApp/Installing');

                    throw new \yii\base\UserException('Bad themes list on installing App');

                }

            } catch (\yii\base\UserException $e){

                // rethrow excpetion
                throw new \yii\base\UserException($e->getMessage());

            } catch (\Exception $e){

                \Yii::error('New exception for user '.$shop.' with access_token '.$access_token.': '.$e->getMessage(), 'ShopifyApp/Installing');
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            /*
             * get concrete cart template
             */
            try {

                $asset = $shopify('GET /admin/themes/' . $result[0]['id'] . '/assets.json', ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

                if (!$asset || empty($asset)){

                    // loggin bad cart template
                    \Yii::error('Cannot find cart template on user '.$shop.' with access_token '.$access_token, 'ShopifyApp/Installing');
                    \Yii::error($asset, 'ShopifyApp/Installing');

                    throw new \yii\base\UserException('Cannot find cart template to apply App changes');

                }

            } catch (\yii\base\UserException $e){

                // rethrow excpetion
                throw new \yii\base\UserException($e->getMessage());

            } catch (\Exception $e){

                \Yii::error('New exception for user '.$shop.' with access_token '.$access_token.': '.$e->getMessage(), 'ShopifyApp/Installing');
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            /*
             * check if we always have installed BoxIT plugin
             */
            if (strpos($asset['value'], '<!-- BOXIT-APP -->')){

                \Yii::info('Found old BoxIT cart App template on user '.$shop.' with access_token '.$access_token, 'ShopifyApp/Installing');

                // remove old boxit-app
                $asset['value'] = preg_replace('#<!-- BOXIT-APP -->.*?<!-- END BOXIT-APP -->#is', '', $asset['value']);

            }

            /*
             * check if we have necessary code
             */
            if (!preg_match('|({%\s+if\s+additional_checkout_buttons\s+%})|sm', $asset['value'], $match)){

                // loggin bad cart template
                \Yii::error('Users cart template not compatible with App on user '.$shop.' with access_token '.$access_token, 'ShopifyApp/Installing');
                \Yii::error($asset['value'], 'ShopifyApp/Installing');

                throw new \yii\base\UserException('Unfortunatelly, your cart not compatible with this App');


            }

            $content = preg_replace(
                '|({%\s+if\s+additional_checkout_buttons\s+%})|sm',
                \Yii::$app->view->renderFile('@app/views/shopify_frontend/include.php') . "\r\t\t" . '$1',
                $asset['value']);

            //$content = str_replace('"','\'',$content);
            //$content = str_replace("\n","\\n",$content);
            //$content = str_replace("\t","\\t",$content);
            //$content = str_replace("\r","\\r",$content);


            $shopify('PUT /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'templates/cart.liquid',
                    'value'	=>	$content
                ]
            ]);

            $shopify('PUT /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'assets/boxitapp.jquery.js',
                    'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/shopify_frontend/jquery.js'))
                ]
            ]);

            $shopify('PUT /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'assets/boxitapp.bootstrap.js',
                    'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/shopify_frontend/common.js'))
                ]
            ]);

            /*
             * push main boxit template
             */
            $content = \Yii::$app->view->renderFile('@app/views/shopify_frontend/cart.php', ['test' => 'test']);
            //$content = str_replace("\r","\\r",$content);
            //$content = str_replace("\n","\\n",$content);
            //$content = str_replace("\t","\\t",$content);
            $shopify('PUT /admin/themes/' . $result[0]['id'] . '/assets.json', array(), [
                'asset'	=>	[
                    'key'	=>	'snippets/boxitapp.liquid',
                    'value'	=>	$content
                ]
            ]);

            $userSettings = new Usersettings();
            $userSettings->access_token = $access_token;
            $userSettings->store_name = $shop;
            $userSettings->old_cart = $asset['value'];
            $userSettings->access_token_hash = md5($access_token . $shop . \Yii::$app->params['store_hash_salt']);
            $userSettings->save();

            //Yii::$app->db->createCommand('INSERT INTO user_settings(`access_token`,`store_name`,`old_cart`) VALUES("' . $access_token . '", "' . $shop . '","' . \Yii::$app->db->quoteValue() . '")')->execute();

            $this->redirect('https://' . $shop . '/admin/apps', 302);

        } else {

            $userSettings = Usersettings::getByParams(['store_name' => $shop]);

            // For example when we try to install the app from App store (https://apps.shopify.com/boxit-connector).
            if (empty($userSettings)) {
                // Get the permission url.
                $permission_url = shopify\authorization_url($shop, $settings['api_key'], json_decode($settings['permissions'], true));

                $permission_url .= '&redirect_uri=' . rawurlencode((\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=site/callback');

                // Redirect to the permission url.
                header('Location: ' . $permission_url);
                exit;
            } else {
                echo \Yii::$app->view->renderFile('@app/views/shopify_backend/settings.php', [
                    'app_url' => (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web'),
                    'document_domain' => \Yii::$app->params['document.domain'],
                    'store_settings' => $settings,
                    'user_settings' => $userSettings
                ]);
            }

        }


        /*} else {
            echo \Yii::$app->view->renderFile('@app/views/shopify_frontend/settings.php',['test' => 'test']);
        }*/
    }

    /*public function actionReadorder(){

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

            $order = $shopify('GET /admin/orders/1127053829.json');

            echo "<pre>", var_dump($order); die();

        } catch (\yii\base\UserException $e){

            // rethrow excpetion
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error('New exception for user '.$request->get('shop').' with access_token '.$user_settings['access_token'].': '.$e->getMessage(), 'ShopifyApp/GetOrder');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }


    }*/

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
            }
            $settings->save();

            $result['success'] = true;
        }
        echo json_encode($result);
    }

}
